<?php
/**
 * Add-ons page wrapper.
 *
 * @var array   $addons
 * @var bool    $is_registered
 * @var bool    $banner_visible
 * @var string|null $pending_slug
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wpb-addons-page" data-wpb-addons-instance="<?php echo esc_attr( isset( $menu_slug ) ? $menu_slug : '' ); ?>">

	<h1><?php esc_html_e( 'Add-ons', 'wpb-addons-page' ); ?></h1>

	<?php $renderer->render_partial( 'partial-banner', [ 'banner_visible' => $banner_visible ] ); ?>
	<?php $renderer->render_partial( 'partial-filters' ); ?>
	<?php $renderer->render_partial( 'partial-grid', [
		'addons'       => $addons,
		'pending_slug' => $pending_slug,
		'renderer'     => $renderer,
	] ); ?>

</div>
