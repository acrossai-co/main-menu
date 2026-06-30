<?php

namespace AcrossAI_Main_Menu;

/**
 * Public entrypoint for the AcrossAI parent menu + Settings page.
 *
 * Usage from a consumer plugin:
 *   new \AcrossAI_Main_Menu\SettingsPage();
 *
 * Registers, in order under the AcrossAI parent menu:
 *   - Dashboard           (the parent landing page, slug: acrossai)
 *   - Abilities Manager   (slug: acrossai-abilities)
 *   - MCP Manager         (slug: acrossai-mcp)
 *   - Model Manager       (slug: acrossai-models)
 *   - Settings            (slug: acrossai-settings, admin_menu priority 1000)
 *
 * The three manager pages render a placeholder pointing at the relevant feature
 * plugin until that plugin hooks the `acrossai_render_{slug}_page` action.
 *
 * The Settings page renders a standard WordPress Settings API form. Consumer
 * plugins extend it by calling register_setting(), add_settings_section(), and
 * add_settings_field() against the 'acrossai-settings' page slug / option_group.
 * See README.md.
 */
class SettingsPage {

	const PARENT_SLUG    = 'acrossai';
	const SETTINGS_SLUG  = 'acrossai-settings';
	const ABILITIES_SLUG = 'acrossai-abilities';
	const MCP_SLUG       = 'acrossai-mcp';
	const MODELS_SLUG    = 'acrossai-models';

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

		$this->register_manager_pages();

		add_action( 'admin_menu', [ $this->menu_registrar, 'register_parent' ] );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_manager_submenus' ], 100 );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_settings_submenu' ], 1000 );
	}

	private function register_manager_pages(): void {
		$this->menu_registrar->add_manager_page(
			self::ABILITIES_SLUG,
			__( 'Abilities Manager', 'acrossai' ),
			new ManagerPageRenderer(
				self::ABILITIES_SLUG,
				__( 'Abilities Manager', 'acrossai' ),
				__( 'Turn WordPress functions into governed, permission-aware abilities your AI can call.', 'acrossai' ),
				'https://wordpress.org/plugins/acrossai-abilities-manager/',
				'https://github.com/acrossai-co/acrossai-core-abilities/'
			)
		);

		$this->menu_registrar->add_manager_page(
			self::MCP_SLUG,
			__( 'MCP Manager', 'acrossai' ),
			new ManagerPageRenderer(
				self::MCP_SLUG,
				__( 'MCP Manager', 'acrossai' ),
				__( 'Connect WordPress to Model Context Protocol servers and manage authorized connections.', 'acrossai' ),
				'https://wordpress.org/plugins/acrossai-mcp-manager/',
				'https://github.com/acrossai-co/'
			)
		);

		$this->menu_registrar->add_manager_page(
			self::MODELS_SLUG,
			__( 'Model Manager', 'acrossai' ),
			new ManagerPageRenderer(
				self::MODELS_SLUG,
				__( 'Model Manager', 'acrossai' ),
				__( 'Register AI providers, store API keys, and route requests across models from one place.', 'acrossai' ),
				'https://wordpress.org/plugins/acrossai-model-manager/',
				'https://github.com/acrossai-co/'
			)
		);
	}
}
