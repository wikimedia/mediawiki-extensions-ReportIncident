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

	it( 'mounts the report incident dialog on report link click', () => {
		const wrapper = renderComponent();
		// Create a link that acts like the "Tools" report link
		// and add it to the body (importantly outside the wrapper).
		const node = document.createElement( 'a' );
		node.setAttribute( 'class', 'ext-reportincident-link' );
		node.setAttribute( 'href', '#' );
		document.body.appendChild( node );
		$( node ).trigger( 'click' );
		// setTimeout call is needed because vuejs doesn't update the
		// DOM immediately on the "click" call. Waiting around 100ms
		// should give enough time for the element to exist.
		setTimeout( function () {
			expect( wrapper.find( '.ext-reportincident-dialog' ).exists() ).toEqual( true );
		}, 100 );
	} );
} );
