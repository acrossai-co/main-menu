<?php

namespace AcrossAI_Addon;

/**
 * Simple Add-ons page. Registers an "Add-ons" submenu under the shared
 * `acrossai` parent menu and renders a hard-coded list of add-ons.
 *
 * Usage from a consumer plugin:
 *   new \AcrossAI_Addon\AddonsPage();
 */
class AddonsPage {

	const PARENT_SLUG  = 'acrossai';
	const SUBMENU_SLUG = 'acrossai-addons';

	/**
	 * Hard-coded add-ons list. Every entry:
	 *   slug        — WordPress plugin slug (folder name)
	 *   name        — display name
	 *   description — short description
	 *   icon        — icon URL (may be empty)
	 *   more_url    — link to the add-on's marketing / details page
	 */
	private const ADDONS = array(
		array(
			'slug'        => 'acrossai-model-manager',
			'name'        => 'AcrossAI Model Manager',
			'description' => 'Control which AI model is used per capability, set request time limits, and review a full audit log of every AI generation call on your site.',
			'icon'        => 'https://ps.w.org/acrossai-model-manager/assets/icon-128x128.png',
			'more_url'    => 'https://wordpress.org/plugins/acrossai-model-manager/',
		),
		array(
			'slug'        => 'turn-off-ai-features',
			'name'        => 'Turn Off AI Features',
			'description' => 'Disable AI functionality in WordPress without touching code. Hooks into wp_supports_ai to return false when the option is enabled.',
			'icon'        => 'https://ps.w.org/turn-off-ai-features/assets/icon-128x128.png',
			'more_url'    => 'https://wordpress.org/plugins/turn-off-ai-features/',
		),
		array(
			'slug'        => 'acrossai-mcp-manager',
			'name'        => 'AcrossAI MCP Manager',
			'description' => 'Seamless integration with Model Context Protocol (MCP) servers — lets AI assistants and code editors safely access your WordPress site via secure application passwords.',
			'icon'        => 'https://ps.w.org/acrossai-mcp-manager/assets/icon-128x128.png',
			'more_url'    => 'https://wordpress.org/plugins/acrossai-mcp-manager/',
		),
		array(
			'slug'        => 'acrossai-core-abilities',
			'name'         => 'AcrossAI Core Abilities',
			'description' => 'Register, manage, and expose site capabilities to AI agents and clients via the standard WordPress Abilities API.',
			'icon'        => '',
			'more_url'    => 'https://github.com/acrossai-co/acrossai-core-abilities/releases',
		),
	);

	/** Process-wide guard so the submenu is registered exactly once. */
	private static $registered = false;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ], 20 );
	}

	public function register_submenu(): void {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Add-ons', 'acrossai' ),
			__( 'Add-ons', 'acrossai' ),
			'install_plugins',
			self::SUBMENU_SLUG,
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Add-ons', 'acrossai' ) . '</h1>';
		echo '<div class="acrossai-addons-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:16px;">';

		foreach ( self::ADDONS as $addon ) {
			echo '<div class="acrossai-addon-card" style="background:#fff;border:1px solid #dcdcde;padding:16px;border-radius:4px;">';

			if ( ! empty( $addon['icon'] ) ) {
				printf(
					'<img src="%s" alt="" style="width:64px;height:64px;float:left;margin:0 12px 12px 0;" />',
					esc_url( $addon['icon'] )
				);
			}

			printf( '<h3 style="margin-top:0;">%s</h3>', esc_html( $addon['name'] ) );
			printf( '<p>%s</p>', esc_html( $addon['description'] ) );

			if ( ! empty( $addon['more_url'] ) ) {
				printf(
					'<p><a href="%s" class="button" target="_blank" rel="noopener noreferrer">%s</a></p>',
					esc_url( $addon['more_url'] ),
					esc_html__( 'More info', 'acrossai' )
				);
			}

			echo '<div style="clear:both;"></div></div>';
		}

		echo '</div></div>';
	}
}
