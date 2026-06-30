<?php

namespace AcrossAI_Main_Menu;

/**
 * Registers the AcrossAI parent menu and the Settings submenu.
 *
 * Parent menu is registered at the default admin_menu priority (10).
 * The Settings submenu is registered at priority 1000 so it lands after
 * any submenus added by other plugins/components.
 */
class MenuRegistrar {

	/** @var string */
	private $parent_slug;

	/** @var string */
	private $settings_slug;

	/** @var DashboardRenderer */
	private $dashboard_renderer;

	/** @var PageRenderer */
	private $settings_renderer;

	/** @var string|null Hook suffix returned by add_submenu_page(). */
	private $hook_suffix = null;

	public function __construct( string $parent_slug, string $settings_slug, DashboardRenderer $dashboard_renderer, PageRenderer $settings_renderer ) {
		$this->parent_slug        = $parent_slug;
		$this->settings_slug      = $settings_slug;
		$this->dashboard_renderer = $dashboard_renderer;
		$this->settings_renderer  = $settings_renderer;
	}

	public function register_parent(): void {
		add_menu_page(
			__( 'AcrossAI', 'acrossai' ),
			__( 'AcrossAI', 'acrossai' ),
			'manage_options',
			$this->parent_slug,
			[ $this->dashboard_renderer, 'render' ]
		);
	}

	public function register_settings_submenu(): void {
		$this->hook_suffix = add_submenu_page(
			$this->parent_slug,
			__( 'Settings', 'acrossai' ),
			__( 'Settings', 'acrossai' ),
			'manage_options',
			$this->settings_slug,
			[ $this->settings_renderer, 'render' ]
		);
	}

	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}
