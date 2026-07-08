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
 * add_settings_field() against the 'acrossai-settings' page slug / option_group,
 * or against a tab-scoped slug obtained from get_settings_renderer()->tab_page_slug().
 * See README.md.
 */
class SettingsPage {

	const PARENT_SLUG   = 'acrossai';
	const SETTINGS_SLUG = 'acrossai-settings';

	/** @var SettingsPageRenderer|null Latest constructed renderer. */
	private static $settings_renderer_instance = null;

	/**
	 * Returns the Settings page renderer so consumer plugins can call
	 * $renderer->tab_page_slug( 'my-tab' ) when registering sections for
	 * a specific tab. Returns null if no SettingsPage has been constructed
	 * yet in this request.
	 */
	public static function get_settings_renderer(): ?SettingsPageRenderer {
		return self::$settings_renderer_instance;
	}

	/** @var MenuRegistrar */
	private $menu_registrar;

	/** @var DashboardRenderer */
	private $dashboard_renderer;

	/** @var SettingsPageRenderer */
	private $settings_renderer;

	public function __construct() {
		$this->dashboard_renderer = new DashboardRenderer();
		$this->settings_renderer  = new SettingsPageRenderer();
		$this->menu_registrar     = new MenuRegistrar(
			self::PARENT_SLUG,
			self::SETTINGS_SLUG,
			$this->dashboard_renderer,
			$this->settings_renderer
		);

		self::$settings_renderer_instance = $this->settings_renderer;

		add_action( 'admin_menu', [ $this->menu_registrar, 'register_parent' ] );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_settings_submenu' ], 1000 );
	}
}
