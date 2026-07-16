<?php

namespace AcrossAI_Addon;

/**
 * Resolves an add-on's installed plugin file (e.g. "acme/acme.php") from a
 * validated registry slug. Used by both ButtonState (to compute the current
 * install/active state) and AjaxHandlers (to authoritatively resolve the file
 * to act on, ignoring any client-supplied plugin_file).
 *
 * Match rules — strictly exact, no fuzzy substring:
 *   1. If the add-on registry entry sets 'install_folder', match that folder
 *      exactly. Use this for GitHub ZIPs whose extracted folder differs from
 *      the slug (e.g. slug "acrossai-core-abilities", folder "core-abilities").
 *   2. Otherwise the folder must equal the slug exactly.
 *
 * Returning null means "not installed under a name we recognize". Callers
 * MUST NOT fall back to a guess — that's how you activate the wrong plugin.
 */
class PluginFileLocator {

	/** @var array<string,array>|null Memoized get_plugins() result. */
	private static $installed_plugins = null;

	/**
	 * Look up the plugin file for an installed add-on. Returns null when it
	 * isn't installed under the expected folder.
	 *
	 * @param array $addon Registry entry (must contain 'slug'; optional 'install_folder').
	 */
	public static function for_addon( array $addon ): ?string {
		$slug = isset( $addon['slug'] ) ? (string) $addon['slug'] : '';
		if ( '' === $slug ) {
			return null;
		}

		$expected_folder = isset( $addon['install_folder'] ) && '' !== $addon['install_folder']
			? (string) $addon['install_folder']
			: $slug;

		foreach ( array_keys( self::plugins() ) as $plugin_file ) {
			$folder = explode( '/', $plugin_file )[0];
			if ( $folder === $expected_folder ) {
				return $plugin_file;
			}
		}
		return null;
	}

	/**
	 * Invalidate the per-request memoized get_plugins() cache. Call after
	 * install/activate/deactivate so subsequent lookups see the new state.
	 */
	public static function flush(): void {
		self::$installed_plugins = null;
	}

	/** @return array<string,array> */
	private static function plugins(): array {
		if ( null === self::$installed_plugins ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			self::$installed_plugins = get_plugins();
		}
		return self::$installed_plugins;
	}
}
