<?php

namespace AcrossAI_Main_Menu;

/**
 * Base class for admin pages that render a WordPress Settings API form,
 * optionally split into tabs contributed by third-party plugins.
 *
 * Layers a Settings-API form + Save button on top of the tab plumbing that
 * lives in {@see Tabs}. Subclasses declare two things:
 *
 *   - get_page_slug()  — the admin page slug (also used as Settings API option_group
 *                        in flat mode; each tab uses its own tab-scoped slug since 0.0.13)
 *   - get_tabs_key()   — (inherited from Tabs) short key that becomes part of the
 *                        tabs filter name, so every subclass gets its own filter
 *                        without any code duplication.
 *
 * Two rendering modes, chosen at runtime:
 *
 * 1. Flat — no plugin hooks the tabs filter. Renders one form against get_page_slug().
 *    Consumers call add_settings_section() / add_settings_field() with that slug.
 *
 * 2. Tabbed — at least one plugin returns a tab from the tabs filter. Renders a
 *    nav-tab bar; each tab has its own form and Save button, each posting with
 *    `option_page = tab_page_slug( $tab )` so WordPress walks only that tab's
 *    whitelist on save (fixes the cross-tab option-clobber bug — see 0.0.13).
 *
 * If you want the tab UI without the Settings-API form / Save flow, extend
 * {@see Tabs} directly instead of this class.
 */
abstract class TabbedPageRenderer extends Tabs {

	/**
	 * Page slug — also used as the Settings API option_group in flat mode
	 * and as the base of the tab-scoped slug in tabbed mode.
	 * Example: 'acrossai-settings'.
	 */
	abstract protected function get_page_slug(): string;

	/**
	 * Returns the tab-scoped page slug consumer plugins pass to
	 * add_settings_section() / add_settings_field() / do_settings_sections()
	 * (and to register_setting() as their option_group) when targeting a
	 * specific tab on this page.
	 */
	public function tab_page_slug( string $tab_slug ): string {
		return $this->get_page_slug() . '-' . sanitize_key( $tab_slug );
	}

	/**
	 * Emit the classic admin submenu URL scheme — `admin.php?page=<slug>&tab=<tab>` —
	 * so tab links round-trip through WP's admin page routing.
	 */
	protected function default_tab_url( string $tab_slug ): string {
		return (string) add_query_arg(
			[
				'page' => $this->get_page_slug(),
				'tab'  => $tab_slug,
			],
			admin_url( 'admin.php' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page_slug = $this->get_page_slug();
		$tabs      = $this->get_tabs();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors(); ?>
			<?php if ( empty( $tabs ) ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( $page_slug );
					do_settings_sections( $page_slug );
					submit_button();
					?>
				</form>
			<?php else : ?>
				<?php
				$active     = $this->get_active_tab( $tabs );
				$tab_scoped = $this->tab_page_slug( $active['slug'] );
				$this->render_tab_nav( $tabs, $active['slug'] );
				?>
				<form action="options.php" method="post">
					<?php
					// 0.0.13 — tab-scoped option_group. Each tab's form posts with
					// `option_page = <tab-scoped slug>` so WordPress walks only that
					// tab's whitelist on save. Prevents the cross-tab option-clobber
					// bug that shared `$page_slug` had in 0.0.12. Consumer plugins
					// must register against this same tab-scoped slug (see README
					// "Adding sections/fields to a tab").
					settings_fields( $tab_scoped );
					do_settings_sections( $tab_scoped );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
