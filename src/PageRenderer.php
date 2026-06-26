<?php

namespace AcrossAI_Main_Menu;

/**
 * Renders the Settings page using the WordPress Settings API.
 *
 * The page slug and the option_group are the same string
 * ('acrossai-settings') so consumer plugins can call:
 *
 *   register_setting( 'acrossai-settings', 'their_option', $args );
 *   add_settings_section( 'their_section', 'Their Section', $cb, 'acrossai-settings' );
 *   add_settings_field(  'their_field', 'Their Field', $cb, 'acrossai-settings', 'their_section' );
 *
 * One Save button submits all registered options through options.php.
 */
class PageRenderer {

	/** @var string Page slug, also used as the Settings API option_group. */
	private $settings_slug;

	public function __construct( string $settings_slug ) {
		$this->settings_slug = $settings_slug;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->settings_slug );
				do_settings_sections( $this->settings_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
