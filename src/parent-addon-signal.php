<?php
/**
 * Parent-addon global helper for the AcrossAI ecosystem.
 *
 * This file is intentionally in the GLOBAL namespace so paid child add-ons
 * (Claude Connectors, BuddyBoss Abilities, and any future paid add-ons of
 * the `acrossai-add-ons` Freemius umbrella) can use the canonical Freemius
 * parent-addon detection idiom:
 *
 *     if ( function_exists( 'acrossai_main_menu' ) ) {
 *         // Umbrella Freemius instance is loaded — safe to fs_dynamic_init
 *         // this add-on with `parent` pointing at the umbrella (34418).
 *     } elseif ( <parent host plugin active> ) {
 *         add_action( 'acrossai_loaded', <addon init> );
 *     }
 *
 * The `\acrossai_main_menu()` function returns the umbrella's Freemius instance (the
 * one registered under product ID `34418`), or null when the umbrella has
 * not yet been registered. The companion `do_action( 'acrossai_loaded' )`
 * is fired by `\AcrossAI_Addon\FreemiusInitializer::init()` the first time
 * the umbrella product is registered.
 *
 * This file is required on demand by FreemiusInitializer::init() (not via
 * composer's `files` autoload) so `\acrossai_main_menu()` only becomes callable
 * after the umbrella instance actually exists — the presence of the
 * function is itself the signal.
 *
 * @package AcrossAI_Main_Menu
 * @since   0.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'acrossai_main_menu' ) ) {
	/**
	 * Umbrella Freemius instance (product `34418`), or null if not yet registered.
	 *
	 * Callers MUST null-check the return value; the umbrella can be absent
	 * during unusual load orders (extremely early hooks, unit tests, etc.).
	 *
	 * @return \Freemius|null
	 * @since  0.0.19
	 */
	function acrossai_main_menu() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Global-namespace helper by design; name matches ecosystem convention.
		return \AcrossAI_Addon\FreemiusInitializer::umbrella_instance();
	}
}
