<?php

namespace AcrossAI_Main_Menu;

/**
 * Enqueues the React host bundle on the Settings screen.
 *
 * The bundle handle is exposed publicly as a constant so consumer
 * plugins can declare it as a dependency when registering their own
 * Fill bundles:
 *
 *   wp_enqueue_script(
 *       'plugin-a-settings-fill',
 *       $url,
 *       [ \AcrossAI_Main_Menu\Assets::HOST_HANDLE ],
 *       $ver,
 *       true
 *   );
 */
class Assets {

	const HOST_HANDLE = 'acrossai-settings-host';

	/** @var string */
	private $package_url;

	/** @var string */
	private $package_dir;

	/** @var string */
	private $settings_slug;

	/** @var string|null Set by SettingsPage after add_submenu_page() runs. */
	private $hook_suffix = null;

	public function __construct( string $package_url, string $package_dir, string $settings_slug ) {
		$this->package_url   = trailingslashit( $package_url );
		$this->package_dir   = $package_dir;
		$this->settings_slug = $settings_slug;
	}

	public function set_hook_suffix( ?string $hook_suffix ): void {
		$this->hook_suffix = $hook_suffix;
	}

	public function enqueue( string $hook ): void {
		if ( null === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		$asset_file = $this->package_dir . '/build/settings-host.asset.php';

		$deps    = [ 'wp-element', 'wp-components', 'wp-plugins', 'wp-i18n' ];
		$version = '1.0.0';

		if ( file_exists( $asset_file ) ) {
			$asset   = include $asset_file;
			$deps    = isset( $asset['dependencies'] ) ? $asset['dependencies'] : $deps;
			$version = isset( $asset['version'] ) ? $asset['version'] : $version;
		}

		wp_enqueue_script(
			self::HOST_HANDLE,
			$this->package_url . 'build/settings-host.js',
			$deps,
			$version,
			true
		);

		wp_set_script_translations( self::HOST_HANDLE, 'acrossai' );
	}
}
