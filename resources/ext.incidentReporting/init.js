'use strict';

$( function () {
	const Vue = require( 'vue' ), App = require( './components/App.vue' );
	Vue.createMwApp( App ).mount( '#ext-incidentreporting-app' );
} );
