/**
 * AcrossAI Settings — React host bundle.
 *
 * Mounts once on the Settings admin page, opens an "AcrossAISettingsTab" Slot,
 * and adapts its UI to however many Fills consumer plugins register:
 *   - 0 fills: placeholder
 *   - 1 fill : render directly (no tab strip)
 *   - 2+     : TabPanel sorted by optional `order` prop
 *
 * Consumer plugins register a Fill from their own bundles:
 *
 *   import { registerPlugin } from '@wordpress/plugins';
 *   import { Fill } from '@wordpress/components';
 *
 *   registerPlugin( 'plugin-a-settings', {
 *       render: () => (
 *           <Fill
 *               name="AcrossAISettingsTab"
 *               tab={ { name: 'plugin-a', title: 'Plugin A', order: 10 } }
 *           >
 *               <PluginAPanel />
 *           </Fill>
 *       ),
 *   } );
 */

import { createRoot, StrictMode } from '@wordpress/element';
import { SlotFillProvider, Slot, TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SLOT_NAME = 'AcrossAISettingsTab';
const MOUNT_ID = 'acrossai-settings-root';

function renderFills( fills ) {
	if ( ! fills || fills.length === 0 ) {
		return (
			<p>
				{ __(
					'No settings panels are installed yet.',
					'acrossai'
				) }
			</p>
		);
	}

	if ( fills.length === 1 ) {
		return fills[ 0 ];
	}

	const tabs = fills
		.map( ( fill ) => ( {
			name: fill.props?.tab?.name,
			title: fill.props?.tab?.title,
			order: fill.props?.tab?.order ?? 100,
			_fill: fill,
		} ) )
		.filter( ( t ) => t.name && t.title )
		.sort( ( a, b ) => a.order - b.order );

	return (
		<TabPanel tabs={ tabs }>
			{ ( tab ) =>
				tabs.find( ( t ) => t.name === tab.name )?._fill ?? null
			}
		</TabPanel>
	);
}

function Host() {
	return (
		<SlotFillProvider>
			<Slot name={ SLOT_NAME } bubblesVirtually>
				{ ( fills ) => renderFills( fills ) }
			</Slot>
		</SlotFillProvider>
	);
}

document.addEventListener( 'DOMContentLoaded', () => {
	const target = document.getElementById( MOUNT_ID );
	if ( ! target ) {
		return;
	}
	createRoot( target ).render(
		<StrictMode>
			<Host />
		</StrictMode>
	);
} );
