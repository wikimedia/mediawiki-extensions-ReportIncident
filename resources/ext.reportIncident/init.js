'use strict';

$( function () {
	const Vue = require( 'vue' ), App = require( './components/App.vue' );
	const Pinia = require( 'pinia' );
	const pinia = Pinia.createPinia();

	Vue.createMwApp( App )
		.use( pinia )
		.mount( '#ext-reportincident-app' );
} );
