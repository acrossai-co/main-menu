<?php

namespace AcrossAI_Main_Menu;

/**
 * Registers the AcrossAI parent menu and its shared submenus (Add-ons, Settings).
 *
 * Parent menu is registered at the default admin_menu priority (10).
 * Settings is registered at priority 20 so it lands right after the Dashboard.
 * Add-ons is registered at priority 1000 so it lands last.
 */
class MenuRegistrar {

	/** @var string */
	private $parent_slug;

	/** @var string */
	private $addons_slug;

	/** @var string */
	private $settings_slug;

	/** @var DashboardRenderer */
	private $dashboard_renderer;

	/** @var AddonsPageRenderer */
	private $addons_renderer;

	/** @var TabbedPageRenderer */
	private $settings_renderer;

	/** @var string|null Hook suffix returned by the Settings add_submenu_page(). */
	private $hook_suffix = null;

	/** @var string|null Hook suffix returned by the Add-ons add_submenu_page(). */
	private $addons_hook_suffix = null;

	public function __construct(
		string $parent_slug,
		string $addons_slug,
		string $settings_slug,
		DashboardRenderer $dashboard_renderer,
		AddonsPageRenderer $addons_renderer,
		TabbedPageRenderer $settings_renderer
	) {
		$this->parent_slug        = $parent_slug;
		$this->addons_slug        = $addons_slug;
		$this->settings_slug      = $settings_slug;
		$this->dashboard_renderer = $dashboard_renderer;
		$this->addons_renderer    = $addons_renderer;
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

	public function register_addons_submenu(): void {
		$this->addons_hook_suffix = add_submenu_page(
			$this->parent_slug,
			__( 'Add-ons', 'acrossai' ),
			__( 'Add-ons', 'acrossai' ),
			'install_plugins',
			$this->addons_slug,
			[ $this->addons_renderer, 'render' ]
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

	public function get_addons_hook_suffix(): ?string {
		return $this->addons_hook_suffix;
	}
}
