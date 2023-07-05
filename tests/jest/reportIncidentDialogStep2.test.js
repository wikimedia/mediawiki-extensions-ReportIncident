'use strict';

const ReportIncidentDialogStep2 = require( '../../resources/ext.reportIncident/components/ReportIncidentDialogStep2.vue' ),
	utils = require( '@vue/test-utils' );

describe( 'Report Incident Dialog', () => {
	it( 'mounts the component', () => {
		const wrapper = utils.shallowMount( ReportIncidentDialogStep2 );
		expect( wrapper.find( '.ext-reportincident-dialog-step2' ).exists() ).toBe( true );
	} );
} );
