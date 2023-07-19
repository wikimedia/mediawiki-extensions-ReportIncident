'use strict';

const Main = require( '../../../resources/ext.reportIncident/components/App.vue' );
const mount = require( '@vue/test-utils' ).mount;
const { createTestingPinia } = require( '@pinia/testing' );

const renderComponent = () => {
	return mount( Main, {
		global: {
			plugins: [ createTestingPinia( { stubActions: false } ) ]
		}
	} );
};

describe( 'Main Component Test Suite', () => {
	it( 'renders correctly', () => {
		const wrapper = renderComponent();
		expect( wrapper.exists() ).toEqual( true );
	} );

	it( 'mounts the report incident dialog on button click', () => {
		const wrapper = renderComponent();
		wrapper.find( '#ext-reportincident-dialog-button' ).trigger( 'click' ).then( function () {
			expect( wrapper.find( '.ext-reportincident-dialog' ).exists() ).toEqual( true );
		} );
	} );
} );
