<?php

namespace AcrossAI_Addon;

class MenuRegistrar {

	const SUBMENU_SLUG = 'acrossai-addons';

	/** @var bool Process-wide guard so the Add-ons submenu is registered at most once. */
	private static $registered = false;

	/** @var string */
	private $parent_slug;

	/** @var PageRenderer */
	private $renderer;

	/** @var string|null Hook suffix returned by add_submenu_page(). */
	private $hook_suffix = null;

	public function __construct( string $parent_slug, PageRenderer $renderer ) {
		$this->parent_slug = $parent_slug;
		$this->renderer    = $renderer;
	}

	/** admin_menu callback. */
	public function register(): void {
		if ( self::$registered ) {
			// Another consumer plugin already added the Add-ons submenu under this parent.
			// Subsequent plugins still register their Freemius config and contribute add-ons;
			// they just don't add a second nav entry.
			return;
		}

		// Add-ons submenu temporarily disabled.
		// $this->hook_suffix = add_submenu_page(
		// 	$this->parent_slug,
		// 	__( 'Add-ons', 'acrossai' ),
		// 	__( 'Add-ons', 'acrossai' ),
		// 	'install_plugins',
		// 	self::SUBMENU_SLUG,
		// 	[ $this->renderer, 'render' ]
		// );

		self::$registered = true;
	}

	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}
