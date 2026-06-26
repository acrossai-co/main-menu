<?php

namespace AcrossAI_Main_Menu;

/**
 * Renders the Settings page mount point. The page is intentionally
 * minimal — a single React root that the host bundle (built from
 * src/assets/js/settings-host.js) takes over on the client.
 *
 * Consumer plugins extend the page by registering @wordpress/components
 * Fills targeting the AcrossAISettingsTab slot.
 */
class PageRenderer {

	/** @var string */
	private $settings_slug;

	public function __construct( string $settings_slug ) {
		$this->settings_slug = $settings_slug;
	}

	public function render(): void {
		printf(
			'<div class="wrap"><div id="%s-root"></div></div>',
			esc_attr( $this->settings_slug )
		);
	}
}
