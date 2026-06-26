<?php

namespace AcrossAI_Main_Menu;

/**
 * Public entrypoint for the AcrossAI parent menu + Settings page.
 *
 * Usage from a consumer plugin:
 *   new \AcrossAI_Main_Menu\SettingsPage( __FILE__ );
 *
 * The instance registers the "AcrossAI" top-level menu and an
 * "acrossai-settings" submenu whose page renders a React mount point
 * (#acrossai-settings-root). Consumer plugins extend the page by
 * registering @wordpress/components <Fill name="AcrossAISettingsTab" />
 * components in their own JS bundles.
 */
class SettingsPage {

	const PARENT_SLUG  = 'acrossai';
	const SETTINGS_SLUG = 'acrossai-settings';

	/** @var string Absolute path to the consumer plugin's main file. */
	private $consumer_main_file;

	/** @var MenuRegistrar */
	private $menu_registrar;

	/** @var PageRenderer */
	private $renderer;

	/** @var Assets */
	private $assets;

	/**
	 * @param string|null $consumer_main_file Absolute path to the consumer plugin's main file (__FILE__).
	 *                                        Used to compute the package URL for asset loading.
	 *
	 * @throws \RuntimeException If the file does not exist.
	 */
	public function __construct( ?string $consumer_main_file = null ) {
		$this->consumer_main_file = $this->resolve_consumer_file( $consumer_main_file );

		$this->renderer       = new PageRenderer( self::SETTINGS_SLUG );
		$this->menu_registrar = new MenuRegistrar( self::PARENT_SLUG, self::SETTINGS_SLUG, $this->renderer );

		$package_dir = dirname( __DIR__ );
		$package_url = $this->detect_package_url( $package_dir );
		$this->assets = new Assets( $package_url, $package_dir, self::SETTINGS_SLUG );

		$this->boot();
	}

	private function boot(): void {
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_parent' ] );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_settings_submenu' ], 1000 );
		add_action( 'admin_menu', [ $this, 'capture_hook_suffix' ], 1001 );
		add_action( 'admin_enqueue_scripts', [ $this->assets, 'enqueue' ] );
	}

	public function capture_hook_suffix(): void {
		$this->assets->set_hook_suffix( $this->menu_registrar->get_hook_suffix() );
	}

	private function resolve_consumer_file( ?string $file ): string {
		if ( null === $file ) {
			throw new \RuntimeException(
				'SettingsPage: pass __FILE__ from your consumer plugin so the package URL can be resolved.'
			);
		}
		if ( ! file_exists( $file ) ) {
			throw new \RuntimeException(
				"SettingsPage: consumer_main_file does not exist: {$file}"
			);
		}
		return $file;
	}

	private function detect_package_url( string $package_dir ): string {
		if ( defined( 'WP_PLUGIN_DIR' ) && defined( 'WP_PLUGIN_URL' ) ) {
			$relative = str_replace( WP_PLUGIN_DIR, '', $package_dir );
			if ( $relative !== $package_dir ) {
				return trailingslashit( WP_PLUGIN_URL . $relative );
			}
		}
		return trailingslashit( plugins_url( '', $this->consumer_main_file ) . '/vendor/acrossai-co/main-menu' );
	}
}
