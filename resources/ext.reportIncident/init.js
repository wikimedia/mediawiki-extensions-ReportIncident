'use strict';
// TODO: Include this file in test coverage and test it?
$( function () {
	const Vue = require( 'vue' );
	const App = require( './components/App.vue' );
	const SuccessConfirmationBanner = require( './components/SuccessConfirmationBanner.vue' );
	const Pinia = require( 'pinia' );
	const pinia = Pinia.createPinia();

	Vue.createMwApp( App )
		.use( pinia )
		.mount( '#ext-reportincident-app' );

	const $successConfirmationBanner = $( '<div>' );
	$successConfirmationBanner.attr( 'id', 'ext-reportincident-successconfirmation' );
	// eslint-disable-next-line no-jquery/no-global-selector
	$( '#siteNotice' ).append( $successConfirmationBanner );

	Vue.createMwApp( SuccessConfirmationBanner )
		.use( pinia )
		.mount( '#ext-reportincident-successconfirmation' );
} );
