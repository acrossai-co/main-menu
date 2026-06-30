<?php

namespace AcrossAI_Main_Menu;

/**
 * Renders the Settings page using the WordPress Settings API.
 *
 * Two modes:
 *
 * 1. Flat (default) — no plugin hooks `acrossai_settings_tabs`. Renders today's
 *    bare form against the shared page slug. Consumer plugins call:
 *
 *      register_setting( 'acrossai-settings', 'their_option', $args );
 *      add_settings_section( 'their_section', 'Their Section', $cb, 'acrossai-settings' );
 *      add_settings_field(  'their_field', 'Their Field', $cb, 'acrossai-settings', 'their_section' );
 *
 * 2. Tabbed — at least one plugin returns tabs from the `acrossai_settings_tabs`
 *    filter. The page renders a nav-tab bar; each tab has its own form and Save
 *    button. Consumer plugins target a tab with SettingsPage::tab_page_slug().
 *
 * The option_group stays `acrossai-settings` in both modes — `register_setting`
 * calls are unchanged regardless of which tab a section belongs to.
 */
class PageRenderer {

	/** @var string Page slug, also used as the Settings API option_group. */
	private $settings_slug;

	/** @var array<int,array<string,mixed>>|null Cached normalized tabs. */
	private $tabs_cache = null;

	public function __construct( string $settings_slug ) {
		$this->settings_slug = $settings_slug;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = $this->resolve_tabs();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors(); ?>
			<?php if ( empty( $tabs ) ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( $this->settings_slug );
					do_settings_sections( $this->settings_slug );
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
					settings_fields( $this->settings_slug );
					do_settings_sections( SettingsPage::tab_page_slug( $active['slug'] ) );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Runs the `acrossai_settings_tabs` filter and returns a normalized,
	 * capability-filtered, priority-sorted list of tabs.
	 *
	 * @return array<int,array{slug:string,label:string,priority:int,capability:string}>
	 */
	private function resolve_tabs(): array {
		if ( null !== $this->tabs_cache ) {
			return $this->tabs_cache;
		}

		$raw = apply_filters( 'acrossai_settings_tabs', [] );
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
						'acrossai_settings_tabs',
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
	 * Returns the active tab for the current request — `$_GET['tab']` when
	 * it matches a registered slug, otherwise the first tab.
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
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab ) {
			$url   = add_query_arg(
				[
					'page' => $this->settings_slug,
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
