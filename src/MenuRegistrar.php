<?php

namespace AcrossAI_Main_Menu;

/**
 * Registers the AcrossAI parent menu and its submenus.
 *
 * Order under the AcrossAI parent:
 *   1. (auto) Dashboard           — duplicates the parent slug
 *   2. Abilities / MCP / Model    — manager submenus, priority 100
 *   3. Settings                   — priority 1000, always last
 *
 * Manager submenu render is delegated to ManagerPageRenderer, which fires an
 * `acrossai_render_{slug}_page` action that feature plugins can replace.
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

	/** @var array<int,array{slug:string,title:string,renderer:ManagerPageRenderer}> */
	private $manager_pages = [];

	/** @var string|null Hook suffix returned by the Settings submenu registration. */
	private $hook_suffix = null;

	public function __construct( string $parent_slug, string $settings_slug, DashboardRenderer $dashboard_renderer, PageRenderer $settings_renderer ) {
		$this->parent_slug        = $parent_slug;
		$this->settings_slug      = $settings_slug;
		$this->dashboard_renderer = $dashboard_renderer;
		$this->settings_renderer  = $settings_renderer;
	}

	/**
	 * Append a manager submenu page (Abilities, MCP, Model, …).
	 * Registration order under WP admin matches call order.
	 */
	public function add_manager_page( string $slug, string $title, ManagerPageRenderer $renderer ): void {
		$this->manager_pages[] = [
			'slug'     => $slug,
			'title'    => $title,
			'renderer' => $renderer,
		];
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

	public function register_manager_submenus(): void {
		foreach ( $this->manager_pages as $page ) {
			add_submenu_page(
				$this->parent_slug,
				$page['title'],
				$page['title'],
				'manage_options',
				$page['slug'],
				[ $page['renderer'], 'render' ]
			);
		}
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
