/**
 * Handles paid add-on "Buy" button clicks.
 * If Freemius checkout JS is loaded and the user is registered → open popup.
 * If not registered → redirect to connect_again flow.
 */
export function initCheckout( page, config ) {
	page.addEventListener( 'click', ( e ) => {
		const btn = e.target.closest( '.acrossai-addons-page__btn' );
		if ( ! btn || btn.dataset.action !== 'buy' ) return;

		e.preventDefault();
		handleBuy( btn, config );
	} );
}

function handleBuy( btn, config ) {
	const slug     = btn.dataset.slug;
	const addon    = config.addons.find( ( a ) => a.slug === slug );
	if ( ! addon ) return;

	if ( config.freemius.isRegistered && config.freemius.checkoutLoaded && addon.checkout_config ) {
		// Open the Freemius checkout popup directly.
		openCheckoutPopup( addon, config );
	} else {
		// Not opted-in: send to connect_again flow with pending slug.
		const url = new URL( config.freemius.connectAgainUrl );
		url.searchParams.set( 'slug', slug );
		window.location.href = url.toString();
	}
}

function openCheckoutPopup( addon, config ) {
	if ( typeof window.FS === 'undefined' || typeof window.FS.Checkout === 'undefined' ) {
		// Checkout JS not loaded yet — fall back to redirect.
		const url = new URL( config.freemius.connectAgainUrl );
		url.searchParams.set( 'slug', addon.slug );
		window.location.href = url.toString();
		return;
	}

	const cc = addon.checkout_config;
	window.FS.Checkout.configure( {
		plugin_id:  cc.plugin_id,
		plan_id:    cc.plan_id,
		public_key: cc.public_key,
	} ).open( {
		name:    addon.name,
		success: () => {
			// Freemius will auto-install; reload page to show updated state.
			window.location.reload();
		},
	} );
}
