<?php
/**
 * Returning customer / Login banner.
 * Shown only when the user has NOT opted in via Freemius.
 *
 * @var bool $banner_visible
 */
defined( 'ABSPATH' ) || exit;

if ( ! $banner_visible ) {
	return;
}

$connect_url = add_query_arg(
	[ 'action' => 'acrossai_addons_connect_again', 'nonce' => wp_create_nonce( 'acrossai_addons_connect' ) ],
	admin_url( 'admin-post.php' )
);
?>
<div class="acrossai-addons-page__banner">
	<p>
		<?php esc_html_e( 'Already purchased a paid add-on? Login here to see your purchases and install them with one click.', 'acrossai-addons-page' ); ?>
	</p>
	<a
		href="<?php echo esc_url( $connect_url ); ?>"
		class="button button-secondary acrossai-addons-page__btn-connect"
		data-action="connect"
	>
		<?php esc_html_e( 'Login / Connect', 'acrossai-addons-page' ); ?>
	</a>
</div>
