<?php

namespace AcrossAI_Main_Menu;

/**
 * Base class for admin pages that render a WordPress Settings API form,
 * optionally split into tabs contributed by third-party plugins.
 *
 * Subclass and declare two things:
 *   - get_page_slug()  — the admin page slug (also used as Settings API option_group)
 *   - get_tabs_key()   — a short key that becomes part of the tabs filter name,
 *                        so every subclass gets its own filter (e.g. "acrossai_settings_tabs",
 *                        "acrossai_tools_tabs", …) without any code duplication.
 *
 * Two rendering modes, chosen at runtime:
 *
 * 1. Flat — no plugin hooks the tabs filter. Renders one form against get_page_slug().
 *    Consumers call add_settings_section() / add_settings_field() with that slug.
 *
 * 2. Tabbed — at least one plugin returns a tab from the tabs filter. Renders a
 *    nav-tab bar; each tab has its own form and Save button. Consumers target a
 *    tab by passing $renderer->tab_page_slug( 'my-tab' ) as the section's page slug.
 *
 * The option_group stays equal to get_page_slug() in both modes — register_setting()
 * calls are unchanged regardless of which tab a section belongs to.
 */
abstract class TabbedPageRenderer {

	/** @var array<int,array<string,mixed>>|null Cached normalized tabs. */
	private $tabs_cache = null;

	/**
	 * Page slug — also used as the Settings API option_group.
	 * Example: 'acrossai-settings'.
	 */
	abstract protected function get_page_slug(): string;

	/**
	 * Short key used to build the tabs filter name.
	 * Example: 'settings' → filter 'acrossai_settings_tabs'.
	 */
	abstract protected function get_tabs_key(): string;

	/**
	 * Full filter name plugins hook to contribute tabs for this page.
	 */
	public function get_tabs_filter_name(): string {
		return 'acrossai_' . $this->get_tabs_key() . '_tabs';
	}

	/**
	 * Returns the page slug consumer plugins pass to add_settings_section() /
	 * add_settings_field() / do_settings_sections() when targeting a specific
	 * tab on this page.
	 */
	public function tab_page_slug( string $tab_slug ): string {
		return $this->get_page_slug() . '-' . sanitize_key( $tab_slug );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page_slug = $this->get_page_slug();
		$tabs      = $this->resolve_tabs();
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
				$active = $this->resolve_active_tab( $tabs );
				$this->render_tab_nav( $tabs, $active['slug'] );
				?>
				<form action="options.php" method="post">
					<?php
					settings_fields( $page_slug );
					do_settings_sections( $this->tab_page_slug( $active['slug'] ) );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Runs the tabs filter and returns a normalized, capability-filtered,
	 * priority-sorted list of tabs.
	 *
	 * @return array<int,array{slug:string,label:string,priority:int,capability:string}>
	 */
	private function resolve_tabs(): array {
		if ( null !== $this->tabs_cache ) {
			return $this->tabs_cache;
		}

		$raw = apply_filters( $this->get_tabs_filter_name(), [] );
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$seen       = [];
		$normalized = [];
		$index      = 0;

		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['slug'] ) ) {
				continue;
			}

			$slug = sanitize_key( (string) $entry['slug'] );
			if ( '' === $slug ) {
				continue;
			}

			if ( isset( $seen[ $slug ] ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					_doing_it_wrong(
						esc_html( $this->get_tabs_filter_name() ),
						sprintf( 'Duplicate tab slug "%s" — first registration wins.', esc_html( $slug ) ),
						'0.0.4'
					);
				}
				continue;
			}
			$seen[ $slug ] = true;

			$label      = isset( $entry['label'] ) ? (string) $entry['label'] : '';
			$capability = isset( $entry['capability'] ) ? sanitize_key( (string) $entry['capability'] ) : 'manage_options';
			$priority   = isset( $entry['priority'] ) ? (int) $entry['priority'] : 10;

			if ( '' === $capability ) {
				$capability = 'manage_options';
			}

			if ( ! current_user_can( $capability ) ) {
				continue;
			}

			$normalized[] = [
				'slug'       => $slug,
				'label'      => '' !== $label ? $label : $slug,
				'priority'   => $priority,
				'capability' => $capability,
				'_index'     => $index++,
			];
		}

		usort(
			$normalized,
			static function ( $a, $b ) {
				if ( $a['priority'] !== $b['priority'] ) {
					return $a['priority'] <=> $b['priority'];
				}
				return $a['_index'] <=> $b['_index'];
			}
		);

		foreach ( $normalized as $i => $tab ) {
			unset( $normalized[ $i ]['_index'] );
		}

		$this->tabs_cache = $normalized;
		return $this->tabs_cache;
	}

	/**
	 * Returns the active tab for the current request — `$_GET['tab']` when it
	 * matches a registered slug, otherwise the first tab.
	 *
	 * @param array<int,array<string,mixed>> $tabs Normalized tab list (non-empty).
	 */
	private function resolve_active_tab( array $tabs ): array {
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		if ( '' !== $requested ) {
			foreach ( $tabs as $tab ) {
				if ( $tab['slug'] === $requested ) {
					return $tab;
				}
			}
		}
		return $tabs[0];
	}

	/**
	 * Renders the <h2 class="nav-tab-wrapper"> bar.
	 *
	 * @param array<int,array<string,mixed>> $tabs        Normalized tab list.
	 * @param string                         $active_slug Slug of the active tab.
	 */
	private function render_tab_nav( array $tabs, string $active_slug ): void {
		$page_slug = $this->get_page_slug();
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab ) {
			$url   = add_query_arg(
				[
					'page' => $page_slug,
					'tab'  => $tab['slug'],
				],
				admin_url( 'admin.php' )
			);
			$class = 'nav-tab' . ( $tab['slug'] === $active_slug ? ' nav-tab-active' : '' );
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $tab['label'] )
			);
		}
		echo '</h2>';
	}
}
