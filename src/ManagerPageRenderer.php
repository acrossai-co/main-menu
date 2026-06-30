<?php

namespace AcrossAI_Main_Menu;

/**
 * Renders a manager submenu page (Abilities, MCP, Model).
 *
 * Each page fires a render action: `acrossai_render_{slug}_page`. A feature
 * plugin (e.g. acrossai-co/acrossai-abilities-manager) hooks that action to
 * print its own UI. If no plugin is hooked, a placeholder is rendered that
 * points to the WordPress.org and GitHub install links.
 *
 * The render action is dispatched without the `acrossai-` slug prefix, so the
 * action for `acrossai-abilities` is `acrossai_render_abilities_page`.
 */
class ManagerPageRenderer {

	/** @var string Page slug, e.g. 'acrossai-abilities'. */
	private $page_slug;

	/** @var string Translated page title, e.g. 'Abilities Manager'. */
	private $title;

	/** @var string Short translated description shown in the placeholder body. */
	private $description;

	/** @var string Public WordPress.org plugin URL. */
	private $wporg_url;

	/** @var string Public GitHub repo URL. */
	private $github_url;

	public function __construct( string $page_slug, string $title, string $description, string $wporg_url, string $github_url ) {
		$this->page_slug   = $page_slug;
		$this->title       = $title;
		$this->description = $description;
		$this->wporg_url   = $wporg_url;
		$this->github_url  = $github_url;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = $this->render_action();
		if ( has_action( $action ) ) {
			do_action( $action );
			return;
		}

		$this->render_placeholder();
	}

	/**
	 * Hook name a feature plugin uses to replace the placeholder.
	 * Strips the leading `acrossai-` from the page slug.
	 */
	private function render_action(): string {
		$key = preg_replace( '/^acrossai-/', '', $this->page_slug );
		return 'acrossai_render_' . str_replace( '-', '_', (string) $key ) . '_page';
	}

	private function render_placeholder(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->title ); ?></h1>
			<div class="notice notice-info inline" style="margin-top:16px;padding:16px 18px;">
				<p style="margin:0 0 6px;font-size:14px;">
					<strong><?php
						printf(
							/* translators: %s: page title (e.g. "Abilities Manager"). */
							esc_html__( 'The %s plugin is not active.', 'acrossai' ),
							esc_html( $this->title )
						);
					?></strong>
				</p>
				<p style="margin:0 0 12px;color:#555;"><?php echo esc_html( $this->description ); ?></p>
				<p style="margin:0;">
					<a class="button button-primary" href="<?php echo esc_url( $this->wporg_url ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Install from WordPress.org', 'acrossai' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( $this->github_url ); ?>" target="_blank" rel="noopener" style="margin-left:6px;">
						<?php esc_html_e( 'View on GitHub', 'acrossai' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
