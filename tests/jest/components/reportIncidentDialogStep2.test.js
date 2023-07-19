'use strict';

const ReportIncidentDialogStep2 = require( '../../../resources/ext.reportIncident/components/ReportIncidentDialogStep2.vue' ),
	utils = require( '@vue/test-utils' ),
	{ createTestingPinia } = require( '@pinia/testing' );

const renderComponent = () => {
	return utils.mount( ReportIncidentDialogStep2, {
		global: {
			plugins: [ createTestingPinia( { stubActions: false } ) ]
		}
	} );
};

describe( 'Report Incident Dialog Step 2', () => {
	it( 'renders correctly', () => {
		const wrapper = renderComponent();
		expect( wrapper.find( '.ext-reportincident-dialog-step2' ).exists() ).toBe( true );
	} );

	it( 'has all default form elements loaded', () => {
		const wrapper = renderComponent();

		expect( wrapper.find( '.ext-reportincident-dialog-step2__harassment-options' ).exists() ).toBe( true );
		expect( wrapper.find( '.ext-reportincident-dialog-step2__violator-name' ).exists() ).toBe( true );
		expect( wrapper.find( '.ext-reportincident-dialog-step2__evidence-links' ).exists() ).toBe( true );
		expect( wrapper.find( '.ext-reportincident-dialog-step2__additional-details' ).exists() ).toBe( true );
		expect( wrapper.find( '.ext-reportincident-dialog-step2__reporter-email' ).exists() ).toBe( true );
	} );
} );
