<?php

namespace AcrossAI_Main_Menu;

/**
 * Public entrypoint for the AcrossAI parent menu + Settings page.
 *
 * Usage from a consumer plugin — preferred:
 *   \AcrossAI_Main_Menu\SettingsPage::instance();
 *
 * Back-compat (still supported):
 *   new \AcrossAI_Main_Menu\SettingsPage();
 *
 * The class is bundled inside every AcrossAI consumer plugin via Composer and
 * deduped process-wide by jetpack-autoloader. Autoloading dedupes the class
 * definition, not construction — every consumer still runs their own boot
 * call. The singleton below ensures that admin_menu hooks (and the shared
 * renderer) are wired exactly once no matter how many consumers boot us.
 *
 * Registers:
 *   - "AcrossAI" top-level menu (slug: acrossai) — the Dashboard landing page
 *   - "Add-ons"  submenu          (slug: acrossai-addons,   admin_menu priority 20)
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
	const ADDONS_SLUG   = 'acrossai-addons';
	const SETTINGS_SLUG = 'acrossai-settings';

	/** @var self|null Shared instance — first construction wins for both `instance()` and `new`. */
	private static $_instance = null;

	/**
	 * Returns the shared SettingsPage instance, constructing it on first call.
	 * Preferred over `new SettingsPage()` — subsequent `new` calls short-circuit
	 * without re-wiring hooks, but calling instance() directly is clearer.
	 */
	public static function instance(): self {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Returns the Settings page renderer so consumer plugins can call
	 * $renderer->tab_page_slug( 'my-tab' ) when registering sections for
	 * a specific tab. Returns null if no SettingsPage has been constructed
	 * yet in this request.
	 */
	public static function get_settings_renderer(): ?SettingsPageRenderer {
		return self::$_instance ? self::$_instance->settings_renderer : null;
	}

	/** @var MenuRegistrar */
	private $menu_registrar;

	/** @var DashboardRenderer */
	private $dashboard_renderer;

	/** @var AddonsPageRenderer */
	private $addons_renderer;

	/** @var SettingsPageRenderer */
	private $settings_renderer;

	/** @var AddonsInstaller */
	private $addons_installer;

	/** @var AddonsAjaxHandlers */
	private $addons_ajax;

	public function __construct() {
		if ( null !== self::$_instance ) {
			// Legacy `new` path from a second consumer — first construction wins.
			return;
		}
		self::$_instance = $this;

		$this->dashboard_renderer = new DashboardRenderer();
		$this->addons_installer   = new AddonsInstaller();
		$this->addons_renderer    = new AddonsPageRenderer( $this->addons_installer );
		$this->addons_ajax        = new AddonsAjaxHandlers( $this->addons_installer, $this->addons_renderer );
		$this->settings_renderer  = new SettingsPageRenderer();
		$this->menu_registrar     = new MenuRegistrar(
			self::PARENT_SLUG,
			self::ADDONS_SLUG,
			self::SETTINGS_SLUG,
			$this->dashboard_renderer,
			$this->addons_renderer,
			$this->settings_renderer
		);

		add_action( 'admin_menu', [ $this->menu_registrar, 'register_parent' ] );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_addons_submenu' ], 20 );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_settings_submenu' ], 1000 );

		add_action( 'wp_ajax_acrossai_addons_install',    [ $this->addons_ajax, 'install' ] );
		add_action( 'wp_ajax_acrossai_addons_activate',   [ $this->addons_ajax, 'activate' ] );
		add_action( 'wp_ajax_acrossai_addons_deactivate', [ $this->addons_ajax, 'deactivate' ] );
	}
}
