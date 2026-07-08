<?php

namespace AcrossAI_Main_Menu;

/**
 * Reusable base class for WordPress admin tab bars.
 *
 * Owns everything to do with tabs *as data + UI* — filter dispatch, normalization,
 * capability gating, priority sorting, active-tab resolution, and the nav bar
 * markup — without any knowledge of Settings API pages, forms, or Save buttons.
 *
 * Subclass anywhere you want a tab bar (custom admin screen, meta box, dashboard
 * widget, Tools submenu, …). Declare a single method:
 *
 *   protected function get_tabs_key(): string { return 'my-key'; }
 *
 * That yields a filter named `"acrossai_{my-key}_tabs"`. Third-party plugins hook
 * that filter to contribute tabs:
 *
 *   add_filter( 'acrossai_my-key_tabs', function ( $tabs ) {
 *       $tabs[] = [ 'slug' => 'general', 'label' => 'General', 'priority' => 10 ];
 *       return $tabs;
 *   } );
 *
 * Then in your rendering code:
 *
 *   $tabs   = $renderer->get_tabs();
 *   $active = $renderer->get_active_tab( $tabs );
 *   $renderer->render_tab_nav( $tabs, $active['slug'] );
 *   // …render body for the active tab yourself…
 *
 * For non-URL contexts (block attrs, POST fields, hash fragments), override
 * `get_requested_slug()` to read from your source instead of `$_GET['tab']`.
 * For non-standard URL schemes, either override `default_tab_url()` in a
 * subclass or pass a per-call `$url_for` callable to `render_tab_nav()`.
 *
 * For a Settings-API page with per-tab forms and a Save button, use the
 * TabbedPageRenderer subclass instead — it layers the form on top of this
 * class's tab plumbing.
 */
abstract class Tabs {

	/** @var array<int,array<string,mixed>>|null Cached normalized tabs. */
	private $tabs_cache = null;

	/**
	 * Short key used to build the tabs filter name.
	 * Example: 'settings' → filter 'acrossai_settings_tabs'.
	 */
	abstract protected function get_tabs_key(): string;

	/**
	 * Full filter name plugins hook to contribute tabs for this instance.
	 */
	public function get_tabs_filter_name(): string {
		return 'acrossai_' . $this->get_tabs_key() . '_tabs';
	}

	/**
	 * Runs the tabs filter and returns a normalized, capability-filtered,
	 * priority-sorted list of tabs.
	 *
	 * @return array<int,array{slug:string,label:string,priority:int,capability:string}>
	 */
	public function get_tabs(): array {
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
	 * Returns the active tab entry — the one whose slug matches
	 * get_requested_slug(), or the first tab as a fallback.
	 *
	 * @param array<int,array<string,mixed>> $tabs Normalized tab list (non-empty).
	 */
	public function get_active_tab( array $tabs ): array {
		$requested = $this->get_requested_slug();
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
	 * Returns the slug of the currently-requested tab. Default source is
	 * `$_GET['tab']`. Override in a subclass when the active tab lives in a
	 * different place — block attribute, POST body, session, etc.
	 */
	protected function get_requested_slug(): string {
		return isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	}

	/**
	 * Renders the <h2 class="nav-tab-wrapper"> tab bar.
	 *
	 * URL for each tab is resolved as:
	 *   1. $url_for( $tab_slug ) if a caller-supplied builder was passed.
	 *   2. $this->default_tab_url( $tab_slug ) otherwise.
	 *
	 * @param array<int,array<string,mixed>> $tabs        Normalized tab list.
	 * @param string                         $active_slug Slug of the active tab.
	 * @param callable|null                  $url_for     Optional (string $slug): string.
	 */
	public function render_tab_nav( array $tabs, string $active_slug, ?callable $url_for = null ): void {
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab ) {
			$url   = null !== $url_for
				? (string) $url_for( $tab['slug'] )
				: $this->default_tab_url( $tab['slug'] );
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

	/**
	 * Default URL builder for the tab nav. Uses `add_query_arg( 'tab', $slug )`
	 * against the current request URL — works for any admin page, meta box, or
	 * embedded UI that lives at a stable URL. Subclasses that live under a
	 * WP submenu should override this to emit `admin.php?page=<slug>&tab=<tab>`.
	 */
	protected function default_tab_url( string $tab_slug ): string {
		return (string) add_query_arg( 'tab', $tab_slug );
	}
}
