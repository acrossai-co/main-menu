<?php

namespace AcrossAI_Main_Menu;

/**
 * Public entrypoint for the AcrossAI parent menu + Settings page.
 *
 * Usage from a consumer plugin:
 *   new \AcrossAI_Main_Menu\SettingsPage();
 *
 * Registers:
 *   - "AcrossAI" top-level menu (slug: acrossai)
 *   - "Settings" submenu          (slug: acrossai-settings, admin_menu priority 1000)
 *
 * The Settings page renders a standard WordPress Settings API form.
 * Consumer plugins extend it by calling register_setting(),
 * add_settings_section(), and add_settings_field() against the
 * 'acrossai-settings' page slug / option_group. See README.md.
 */
class SettingsPage {

	const PARENT_SLUG   = 'acrossai';
	const SETTINGS_SLUG = 'acrossai-settings';

	/** @var MenuRegistrar */
	private $menu_registrar;

	/** @var PageRenderer */
	private $renderer;

	public function __construct() {
		$this->renderer       = new PageRenderer( self::SETTINGS_SLUG );
		$this->menu_registrar = new MenuRegistrar( self::PARENT_SLUG, self::SETTINGS_SLUG, $this->renderer );

		add_action( 'admin_menu', [ $this->menu_registrar, 'register_parent' ] );
		add_action( 'admin_menu', [ $this->menu_registrar, 'register_settings_submenu' ], 1000 );
	}
}
