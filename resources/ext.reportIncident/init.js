'use strict';
// TODO: Include this file in test coverage and test it?
$( () => {
	const Vue = require( 'vue' );
	const App = require( './components/App.vue' );
	const SuccessConfirmationBanner = require( './components/SuccessConfirmationBanner.vue' );
	const Pinia = require( 'pinia' );
	const pinia = Pinia.createPinia();

	const reportIncidentApp = Vue.createMwApp( App )
		.use( pinia )
		.mount( '#ext-reportincident-app' );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.ext-reportincident-link' ).on( 'click', ( event ) => {
		event.preventDefault();
		reportIncidentApp.reportLinkInToolsMenuHandler();
	} );

	const $successConfirmationBanner = $( '<div>' );
	$successConfirmationBanner.attr( 'id', 'ext-reportincident-successconfirmation' );
	// eslint-disable-next-line no-jquery/no-global-selector,no-jquery/no-class-state
	if ( $( 'body' ).hasClass( 'skin-timeless' ) ) {
		// The Timeless skin does not have a sitenotice in the correct place
		// so place the confirmation banner in the #mw-content div
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '#mw-content' ).prepend( $successConfirmationBanner );
	} else {
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '#siteNotice' ).append( $successConfirmationBanner );
	}

	Vue.createMwApp( SuccessConfirmationBanner )
		.use( pinia )
		.mount( '#ext-reportincident-successconfirmation' );
} );
