'use strict';

const Main = require( '../../resources/ext.incidentReporting/components/App.vue' );
const utils = require( '@vue/test-utils' );

describe( 'Main Component Test Suite', () => {
	test( 'Test app mounts vue component', () => {
		const wrapper = utils.mount( Main );
		expect( wrapper.exists() ).toBe( true );
	} );

} );
