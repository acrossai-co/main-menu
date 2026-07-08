<?php

namespace AcrossAI_Main_Menu;

/**
 * Renderer for the shared "Settings" submenu page under the AcrossAI parent.
 *
 * All rendering, tab normalization, and filter dispatch lives in
 * TabbedPageRenderer. This subclass just pins the page slug + tabs key:
 *
 *   - page slug: 'acrossai-settings' (also the Settings API option_group)
 *   - tabs filter: 'acrossai_settings_tabs' (derived from the 'settings' key)
 *
 * To add a similar tabbed page (e.g. "Tools"), subclass TabbedPageRenderer with
 * a different page slug and key — no rendering code needs to be re-written.
 */
final class SettingsPageRenderer extends TabbedPageRenderer {

	protected function get_page_slug(): string {
		return SettingsPage::SETTINGS_SLUG;
	}

	protected function get_tabs_key(): string {
		return 'settings';
	}
}
