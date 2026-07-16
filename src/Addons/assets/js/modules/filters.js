/**
 * All / Free / Paid filter tabs with keyboard (arrow key) navigation.
 */
export function initFilters( page ) {
	const tabList = page.querySelector( '.acrossai-addons-page__filters' );
	const grid    = page.querySelector( '.acrossai-addons-page__grid' );
	if ( ! tabList || ! grid ) return;

	const tabs = Array.from( tabList.querySelectorAll( '[role="tab"]' ) );
	const cards = Array.from( grid.querySelectorAll( '.acrossai-addons-page__card' ) );

	function activateTab( tab ) {
		tabs.forEach( ( t ) => {
			t.classList.remove( 'is-active' );
			t.setAttribute( 'aria-selected', 'false' );
			t.setAttribute( 'tabindex', '-1' );
		} );
		tab.classList.add( 'is-active' );
		tab.setAttribute( 'aria-selected', 'true' );
		tab.setAttribute( 'tabindex', '0' );

		const filter = tab.dataset.filter;
		cards.forEach( ( card ) => {
			const show = filter === 'all' || card.dataset.type === filter;
			card.hidden = ! show;
		} );
	}

	tabs.forEach( ( tab ) => {
		tab.addEventListener( 'click', () => activateTab( tab ) );
		tab.addEventListener( 'keydown', ( e ) => {
			const idx = tabs.indexOf( tab );
			if ( e.key === 'ArrowRight' ) {
				e.preventDefault();
				activateTab( tabs[ ( idx + 1 ) % tabs.length ] );
				tabs[ ( idx + 1 ) % tabs.length ].focus();
			} else if ( e.key === 'ArrowLeft' ) {
				e.preventDefault();
				const prev = ( idx - 1 + tabs.length ) % tabs.length;
				activateTab( tabs[ prev ] );
				tabs[ prev ].focus();
			}
		} );
	} );
}
