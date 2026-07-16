<?php

namespace AcrossAI_Addon;

/**
 * Loads the Freemius SDK and creates a per-plugin FS instance.
 *
 * Each consumer plugin passes its own Freemius product credentials so that
 * activations, opt-ins, and analytics are tracked separately per plugin in
 * the Freemius dashboard.
 */
class FreemiusInitializer {

	/** @var object[] Memoized FS instances keyed by product ID. */
	private static $instances = [];

	/**
	 * Default Freemius `menu` config values (excluding the required `slug`).
	 *
	 * Consumers override individual keys by passing $menu_overrides to init().
	 * `slug` is derived from the caller's $menu_slug argument and cannot be
	 * overridden via $menu_overrides — pass a different $menu_slug instead.
	 *
	 * Rationale for defaults:
	 * - account / contact / support default to `true` so operator-facing
	 *   Freemius surfaces (activation, support form, wp.org forum link)
	 *   are discoverable under every consumer plugin's AcrossAI submenu
	 *   without each plugin having to opt in.
	 * - upgrade / pricing default to `false` because the bundled Add-ons
	 *   page owns the upgrade + pricing UX for AcrossAI products.
	 * - addons defaults to `false` because a second Freemius-added Add-ons
	 *   row would duplicate the vendor's already-registered Add-ons submenu
	 *   (MenuRegistrar::register()).
	 */
	private const DEFAULT_MENU = array(
		'account' => true,
		'contact' => true,
		'support' => true,
		'upgrade' => false,
		'pricing' => false,
		'addons'  => false,
	);

	/**
	 * Load the SDK (if not already loaded) and return the FS instance for the given product.
	 *
	 * @param string $consumer_main_file Absolute path to the consumer plugin's main file.
	 * @param string $menu_slug          Consumer's parent admin menu slug (becomes `menu.slug`; cannot be overridden).
	 * @param string $product_id         Freemius product ID (numeric string).
	 * @param string $public_key         Freemius product public key (pk_...).
	 * @param string $slug               Freemius product slug.
	 * @param array  $menu_overrides     Optional per-consumer overrides for the Freemius `menu` config.
	 *                                    Accepted keys: `account`, `contact`, `support`, `upgrade`,
	 *                                    `pricing`, `addons` (all boolean). Any key omitted from this
	 *                                    array keeps its DEFAULT_MENU value. Unknown keys pass through
	 *                                    verbatim so future Freemius menu config extensions can be used
	 *                                    without a package bump. `slug` is stripped — pass $menu_slug.
	 *
	 * @return object Freemius instance.
	 */
	public static function init(
		string $consumer_main_file,
		string $menu_slug,
		string $product_id,
		string $public_key,
		string $slug,
		array $menu_overrides = array()
	): object {
		if ( isset( self::$instances[ $product_id ] ) ) {
			return self::$instances[ $product_id ];
		}

		self::load_sdk();

		if ( ! function_exists( 'fs_dynamic_init' ) ) {
			throw new \RuntimeException(
				'AddonsPage: Freemius SDK loaded but fs_dynamic_init() is unavailable. ' .
				'Ensure vendor/freemius/wordpress-sdk/start.php is accessible.'
			);
		}

		// Strip `slug` so consumers can't override the parent-menu slug via this array
		// (they'd have to pass a different $menu_slug to actually change the parent menu).
		unset( $menu_overrides['slug'] );

		self::$instances[ $product_id ] = fs_dynamic_init(
			array(
				'id'             => $product_id,
				'slug'           => $slug,
				'type'           => 'plugin',
				'public_key'     => $public_key,
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'menu'           => array_merge(
					self::DEFAULT_MENU,
					$menu_overrides,
					array( 'slug' => $menu_slug )
				),
				'navigation'     => 'menu',
				'file'           => $consumer_main_file,
			)
		);

		return self::$instances[ $product_id ];
	}

	/**
	 * Require the Freemius SDK start.php.
	 * Guards against double-loading — FS itself also guards internally.
	 */
	private static function load_sdk(): void {
		if ( function_exists( 'fs_dynamic_init' ) ) {
			return;
		}

		// When installed via Composer the SDK lives at:
		// {consumer}/vendor/freemius/wordpress-sdk/start.php
		// This file lives at: {consumer}/vendor/acrossai-co/main-menu/src/Addons/FreemiusInitializer.php
		// Walk up: Addons -> src -> main-menu -> acrossai-co -> vendor, then into freemius/wordpress-sdk.
		$sdk_path = dirname( __DIR__, 4 ) . '/freemius/wordpress-sdk/start.php';

		if ( ! file_exists( $sdk_path ) ) {
			throw new \RuntimeException(
				"AddonsPage: Freemius SDK not found at expected path: {$sdk_path}\n" .
				'Run `composer install` in your plugin directory to install dependencies.'
			);
		}

		require_once $sdk_path;
	}
}
