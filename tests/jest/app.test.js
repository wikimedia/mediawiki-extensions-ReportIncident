'use strict';

const Main = require( '../../resources/ext.reportIncident/components/App.vue' );
const utils = require( '@vue/test-utils' );

describe( 'Main Component Test Suite', () => {
	it( 'mounts the vue component', () => {
		const wrapper = utils.mount( Main );
		expect( wrapper.exists() ).toBe( true );
	} );

	it( 'mounts the report incident dialog on button click', () => {
		const wrapper = utils.mount( Main );
		wrapper.find( '#ext-reportincident-dialog-button' ).trigger( 'click' ).then( function () {
			expect( wrapper.find( '.ext-reportincident-dialog' ).exists() ).toBe( true );
		} );
	} );
} );
