/**
 * Handles free add-on Install, Activate, and Deactivate button clicks via admin-ajax.
 */
export function initInstall( page, config ) {
	page.addEventListener( 'click', ( e ) => {
		const btn = e.target.closest( '.wpb-addons-page__btn' );
		if ( ! btn ) return;

		const action = btn.dataset.action;
		if ( action !== 'install' && action !== 'activate' && action !== 'deactivate' ) return;

		e.preventDefault();
		handleAction( btn, action, config );
	} );
}

async function handleAction( btn, action, config ) {
	const card      = btn.closest( '.wpb-addons-page__card' );
	const errorEl   = card ? card.querySelector( '.wpb-addons-page__card-error' ) : null;
	const slug      = btn.dataset.slug;
	const source    = btn.dataset.source;
	const pluginFile = btn.dataset.pluginFile || '';

	// Busy state.
	const originalLabel    = btn.textContent.trim();
	const originalAriaLabel = btn.getAttribute( 'aria-label' );
	btn.disabled = true;
	btn.setAttribute( 'aria-busy', 'true' );

	if ( action === 'install' ) {
		btn.textContent = config.i18n.installing;
	} else if ( action === 'activate' ) {
		btn.textContent = config.i18n.activating;
	} else {
		btn.textContent = config.i18n.deactivating;
	}

	if ( errorEl ) errorEl.hidden = true;

	const body = new FormData();
	if ( action === 'install' ) {
		body.append( 'action', 'wpb_addons_install_free' );
		body.append( 'source', source );
	} else if ( action === 'activate' ) {
		body.append( 'action', 'wpb_addons_activate' );
		body.append( 'plugin_file', pluginFile );
	} else {
		body.append( 'action', 'wpb_addons_deactivate' );
		body.append( 'plugin_file', pluginFile );
	}
	body.append( 'nonce', config.nonce );
	body.append( 'slug', slug );

	try {
		const response = await fetch( config.ajaxUrl, { method: 'POST', body } );
		const json     = await response.json();

		if ( json.success ) {
			const confirmLabel = action === 'install'
				? config.i18n.installed
				: action === 'activate'
					? config.i18n.activated
					: config.i18n.deactivated;

			// Flash confirmation in the button.
			btn.textContent = confirmLabel;
			btn.classList.add( 'wpb-addons-page__btn--confirmed' );
			btn.setAttribute( 'aria-busy', 'false' );
			if ( json.data.plugin_file ) {
				btn.dataset.pluginFile = json.data.plugin_file;
			}

			// After 1.5 s apply the final server-returned state.
			setTimeout( () => {
				btn.classList.remove( 'wpb-addons-page__btn--confirmed' );
				const state = json.data.state;
				btn.textContent = state.label;
				btn.setAttribute( 'aria-label', state.label + ' ' + ( btn.dataset.slug || '' ) );
				btn.dataset.action = state.action;
				btn.disabled = ! state.enabled;
				btn.setAttribute( 'aria-disabled', String( ! state.enabled ) );
				btn.className = 'button ' + state.css_class + ' wpb-addons-page__btn';
				if ( state.plugin_file ) {
					btn.dataset.pluginFile = state.plugin_file;
				}
				btn.focus();
			}, 1500 );
		} else {
			restoreButton( btn, originalLabel, originalAriaLabel );
			showError( errorEl, json.data?.message || config.i18n.installFailed );
		}
	} catch ( err ) {
		restoreButton( btn, originalLabel, originalAriaLabel );
		showError( errorEl, config.i18n.installFailed );
	}
}

function restoreButton( btn, label, ariaLabel ) {
	btn.textContent = label;
	btn.setAttribute( 'aria-label', ariaLabel );
	btn.disabled = false;
	btn.setAttribute( 'aria-busy', 'false' );
}

function showError( errorEl, message ) {
	if ( ! errorEl ) return;
	errorEl.textContent = message;
	errorEl.hidden = false;
}
