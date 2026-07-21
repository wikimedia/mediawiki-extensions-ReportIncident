'use strict';
// TODO: Include this file in test coverage and test it?
$( () => {
	const Vue = require( 'vue' );
	const App = require( './components/App.vue' );
	const Pinia = require( 'pinia' );
	const pinia = Pinia.createPinia();

	const reportIncidentApp = Vue.createMwApp( App, {} )
		.use( pinia )
		.mount( '#ext-reportincident-app' );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.ext-reportincident-link' ).on( 'click', ( event ) => {
		event.preventDefault();
		reportIncidentApp.reportLinkInToolsMenuHandler();
	} );

	mw.hook( 'discussionToolsOverflowMenuOnChoose' )
		.add( reportIncidentApp.discussionToolsOverflowMenuOnChooseHandler );

	// DiscussionTools no longer renders the item id as a DOM id, so tag our
	// thread menu item with a stable class for styling and tests.
	mw.hook( 'discussionToolsOverflowMenuOnAddItem' ).add( ( id, menuItem ) => {
		if ( id === 'reportincident' ) {
			menuItem.$element.addClass( 'ext-reportincident-thread-link' );
		}
	} );
} );
