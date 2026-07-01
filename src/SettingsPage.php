<?php

namespace AcrossAI_Main_Menu;

/**
 * Public entrypoint for the AcrossAI parent menu + Settings page.
 *
 * Usage from a consumer plugin:
 *   new \AcrossAI_Main_Menu\SettingsPage();
 *
 * Registers:
 *   - "AcrossAI" top-level menu (slug: acrossai) — the Dashboard landing page
 *   - "Settings" submenu          (slug: acrossai-settings, admin_menu priority 1000)
 *
 * The Settings page renders a standard WordPress Settings API form. Consumer
 * plugins extend it by calling register_setting(), add_settings_section(), and
 * add_settings_field() against the 'acrossai-settings' page slug / option_group.
 * See README.md.
 */
class SettingsPage {

	const PARENT_SLUG   = 'acrossai';
	const SETTINGS_SLUG = 'acrossai-settings';

	/**
	 * Returns the page slug consumer plugins should pass to
	 * add_settings_section() / add_settings_field() / do_settings_sections()
	 * when targeting a specific tab on the shared Settings page.
	 */
	public static function tab_page_slug( string $tab_slug ): string {
		return self::SETTINGS_SLUG . '-' . sanitize_key( $tab_slug );
	}

	/** @var MenuRegistrar */
	private $menu_registrar;

	/** @var DashboardRenderer */
	private $dashboard_renderer;

	/** @var PageRenderer */
	private $settings_renderer;

	public function __construct() {
		$this->dashboard_renderer = new DashboardRenderer();
		$this->settings_renderer  = new PageRenderer( self::SETTINGS_SLUG );
		$this->menu_registrar     = new MenuRegistrar(
			self::PARENT_SLUG,
			self::SETTINGS_SLUG,
			$this->dashboard_renderer,
			$this->settings_renderer
		);

		add_action( 'admin_menu', [ $this->menu_registrar, 'register_parent' ] );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_settings_submenu' ], 1000 );
	}
}
