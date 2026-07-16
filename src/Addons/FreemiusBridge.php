<?php

namespace AcrossAI_Addon;

/**
 * Defensive wrapper around the Freemius instance created by FreemiusInitializer.
 * All public methods return safe defaults on failure so the page never fatals.
 */
class FreemiusBridge {

	/** @var object */
	private $fs;

	/**
	 * Per-request license cache: [ product_id => array|null ].
	 * Value is the full license array when the user owns a currently-valid
	 * license, or null when they don't. array_key_exists() is used to
	 * distinguish "cached miss" (null) from "never looked up" (unset).
	 *
	 * @var array<string, array|null>
	 */
	private static $license_cache = [];

	public function __construct( object $fs ) {
		$this->fs = $fs;
	}

	public function is_available(): bool {
		return method_exists( $this->fs, 'is_registered' );
	}

	public function is_registered(): bool {
		try {
			return $this->is_available() && (bool) $this->fs->is_registered();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Resets anonymous mode and redirects to the Freemius opt-in page.
	 * Calls fs->connect_again() which handles the reset + redirect internally.
	 * Falls back silently when the method is unavailable.
	 */
	public function trigger_connect_again(): void {
		try {
			if ( method_exists( $this->fs, 'connect_again' ) ) {
				$this->fs->connect_again();
			}
		} catch ( \Exception $e ) {
			// Fail gracefully — caller supplies the fallback redirect.
		}
	}

	/**
	 * Check whether the current registered user owns a license for a given
	 * standalone Freemius product (a paid add-on with its own fs_product_id).
	 *
	 * @TODO The cross-product license query API is semi-private in Freemius.
	 *       This implementation uses get_api_user_scope() which is the most
	 *       stable public surface. If this method returns unexpected results,
	 *       consult https://freemius.com/help/ or the FS PHP SDK source.
	 *
	 * @param string $fs_product_id The add-on's standalone Freemius product ID.
	 */
	public function owned_license_for( string $fs_product_id ): ?array {
		if ( ! $this->is_registered() ) {
			return null;
		}

		if ( array_key_exists( $fs_product_id, self::$license_cache ) ) {
			return self::$license_cache[ $fs_product_id ];
		}

		try {
			if ( ! method_exists( $this->fs, 'get_api_user_scope' ) ) {
				self::$license_cache[ $fs_product_id ] = null;
				return null;
			}

			$api      = $this->fs->get_api_user_scope();
			$response = $api->get( '/licenses.json?plugin_id=' . rawurlencode( $fs_product_id ) );

			if ( is_object( $response ) && ! empty( $response->licenses ) ) {
				foreach ( $response->licenses as $license ) {
					if ( ! self::is_license_currently_valid( $license ) ) {
						continue;
					}
					$as_array = (array) $license;
					self::$license_cache[ $fs_product_id ] = $as_array;
					return $as_array;
				}
			}
		} catch ( \Exception $e ) {
			// Fail gracefully — degrade to "Buy" button.
		}

		self::$license_cache[ $fs_product_id ] = null;
		return null;
	}

	public function is_owned( string $fs_product_id ): bool {
		return null !== $this->owned_license_for( $fs_product_id );
	}

	/**
	 * A license is considered "currently valid" for gating the Install button
	 * when it isn't cancelled AND isn't expired.
	 *
	 * Expiration signal: Freemius returns `expiration` as a datetime string
	 * (UTC, "Y-m-d H:i:s"), or `null` for lifetime licenses. Some SDK builds
	 * additionally set `is_expired`, so we honor either when present.
	 */
	private static function is_license_currently_valid( $license ): bool {
		if ( ! empty( $license->is_cancelled ) ) {
			return false;
		}
		if ( isset( $license->is_expired ) && $license->is_expired ) {
			return false;
		}
		if ( property_exists( $license, 'expiration' ) && null !== $license->expiration && '' !== $license->expiration ) {
			$expires_at = strtotime( (string) $license->expiration . ' UTC' );
			if ( false !== $expires_at && $expires_at < time() ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the config array the JS checkout module needs to call FS.Checkout.configure().
	 */
	public function checkout_config( array $addon ): array {
		return [
			'plugin_id'  => $addon['fs_product_id'],
			'plan_id'    => $addon['fs_plan_id'],
			'public_key' => $addon['fs_public_key'],
		];
	}

	/** Returns the URL for the Freemius checkout JS. */
	public static function checkout_js_url(): string {
		return 'https://checkout.freemius.com/checkout.min.js';
	}
}
