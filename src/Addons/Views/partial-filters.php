<?php
/**
 * Filter tabs: All / Free / Paid
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="acrossai-addons-page__filters" role="tablist" aria-label="<?php esc_attr_e( 'Filter add-ons', 'acrossai-addons-page' ); ?>">

	<button
		role="tab"
		class="acrossai-addons-page__filter is-active"
		data-filter="all"
		aria-selected="true"
		tabindex="0"
	>
		<?php esc_html_e( 'All', 'acrossai-addons-page' ); ?>
	</button>

	<button
		role="tab"
		class="acrossai-addons-page__filter"
		data-filter="free"
		aria-selected="false"
		tabindex="-1"
	>
		<?php esc_html_e( 'Free', 'acrossai-addons-page' ); ?>
	</button>

	<button
		role="tab"
		class="acrossai-addons-page__filter"
		data-filter="paid"
		aria-selected="false"
		tabindex="-1"
	>
		<?php esc_html_e( 'Paid', 'acrossai-addons-page' ); ?>
	</button>

</div>
