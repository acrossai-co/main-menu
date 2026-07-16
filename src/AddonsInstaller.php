<?php

namespace AcrossAI_Main_Menu;

/**
 * Silent plugin install / activate / deactivate for the Add-ons page.
 *
 * Two supported sources per add-on entry:
 *   - source = 'wordpress.org' → resolves download URL via plugins_api()
 *   - source = 'github'        → uses the entry's 'download_url' (ZIP)
 *
 * Add-ons page validates the slug against the registry (with acrossai_addons
 * filter applied) before calling into here — no client-supplied plugin_file
 * is trusted; find_plugin_file() resolves it server-side via exact folder
 * match (with optional 'install_folder' override for GitHub ZIPs whose
 * extracted folder differs from the slug).
 */
class AddonsInstaller {

	/** @var array<string,array>|null Memoized get_plugins() result. */
	private static $installed_plugins = null;

	/**
	 * Install an add-on from wp.org or a GitHub ZIP.
	 *
	 * @return array{success:bool, message:string, plugin_file:string}
	 */
	public function install( array $addon ): array {
		$this->load_upgrader_files();

		$download_url = $this->resolve_download_url( $addon );
		if ( is_wp_error( $download_url ) ) {
			return [
				'success'     => false,
				'message'     => $download_url->get_error_message(),
				'plugin_file' => '',
			];
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			return [
				'success'     => false,
				'message'     => $result->get_error_message(),
				'plugin_file' => '',
			];
		}

		if ( $skin->get_errors()->has_errors() ) {
			return [
				'success'     => false,
				'message'     => implode( ' ', $skin->get_errors()->get_error_messages() ),
				'plugin_file' => '',
			];
		}

		self::flush_cache();
		$plugin_file = $this->find_plugin_file( $addon );

		return [
			'success'     => true,
			/* translators: %s: add-on name */
			'message'     => sprintf( __( '%s installed.', 'acrossai' ), $addon['name'] ),
			'plugin_file' => $plugin_file ?? '',
		];
	}

	/** @return array{success:bool, message:string} */
	public function activate( string $plugin_file, string $addon_name ): array {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$result = activate_plugin( $plugin_file );
		if ( is_wp_error( $result ) ) {
			return [ 'success' => false, 'message' => $result->get_error_message() ];
		}
		return [
			'success' => true,
			/* translators: %s: add-on name */
			'message' => sprintf( __( '%s activated.', 'acrossai' ), $addon_name ),
		];
	}

	/** @return array{success:bool, message:string} */
	public function deactivate( string $plugin_file, string $addon_name ): array {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		deactivate_plugins( $plugin_file );
		return [
			'success' => true,
			/* translators: %s: add-on name */
			'message' => sprintf( __( '%s deactivated.', 'acrossai' ), $addon_name ),
		];
	}

	/**
	 * Locate the installed plugin file (e.g. "acme/acme.php") for an add-on.
	 * Exact folder match only — no substring fallback. Add-ons whose extracted
	 * folder differs from the slug (common for GitHub ZIPs) can declare
	 * 'install_folder' in the registry entry to override the match target.
	 */
	public function find_plugin_file( array $addon ): ?string {
		$slug = isset( $addon['slug'] ) ? (string) $addon['slug'] : '';
		if ( '' === $slug ) {
			return null;
		}
		$expected = isset( $addon['install_folder'] ) && '' !== $addon['install_folder']
			? (string) $addon['install_folder']
			: $slug;

		foreach ( array_keys( self::plugins() ) as $plugin_file ) {
			if ( explode( '/', $plugin_file )[0] === $expected ) {
				return $plugin_file;
			}
		}
		return null;
	}

	/** Invalidate the per-request get_plugins() cache after mutation. */
	public static function flush_cache(): void {
		self::$installed_plugins = null;
	}

	// -------------------------------------------------------------------------

	private function load_upgrader_files(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
			WP_Filesystem();
		}
	}

	/** @return string|\WP_Error */
	private function resolve_download_url( array $addon ) {
		$source = isset( $addon['source'] ) ? (string) $addon['source'] : '';

		if ( 'wordpress.org' === $source ) {
			$info = plugins_api( 'plugin_information', [
				'slug'   => $addon['slug'],
				'fields' => [ 'sections' => false, 'reviews' => false ],
			] );
			if ( is_wp_error( $info ) ) {
				return $info;
			}
			if ( empty( $info->download_link ) ) {
				return new \WP_Error( 'no_download_link', sprintf(
					/* translators: %s: plugin slug */
					__( 'Could not retrieve download URL for %s from WordPress.org.', 'acrossai' ),
					$addon['slug']
				) );
			}
			return $info->download_link;
		}

		if ( 'github' === $source ) {
			if ( empty( $addon['download_url'] ) ) {
				return new \WP_Error( 'missing_download_url', sprintf(
					/* translators: %s: add-on name */
					__( 'No download_url configured for %s.', 'acrossai' ),
					$addon['name']
				) );
			}
			return (string) $addon['download_url'];
		}

		return new \WP_Error( 'invalid_source', __( 'Invalid add-on source.', 'acrossai' ) );
	}

	/** @return array<string,array> */
	private static function plugins(): array {
		if ( null === self::$installed_plugins ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			self::$installed_plugins = get_plugins();
		}
		return self::$installed_plugins;
	}
}
