'use strict';

const ReportIncidentDialogStep2 = require( '../../../resources/ext.reportIncident/components/ReportIncidentDialogStep2.vue' ),
	utils = require( '@vue/test-utils' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' );

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
		expect( wrapper.find( '.ext-reportincident-dialog-step2__additional-details' ).exists() ).toBe( true );
	} );

	it( 'Gets correct error messages for display', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		store.isFormValidForSubmission();

		expect( wrapper.vm.formErrorMessages ).toStrictEqual( store.formErrorMessages );
	} );

	it( 'Sets correct status values', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		// No errors should be displayed until a user tries to submit the form
		// or focuses out of the required field.
		expect( wrapper.vm.harassmentStatus ).toBe( 'default' );
		expect( wrapper.vm.reportedUserStatus ).toBe( 'default' );

		// Call method used to indicate that form is about to be submitted.
		store.isFormValidForSubmission();

		// Errors should be displayed when a user tries to submit the form without
		// specifying required fields.
		expect( wrapper.vm.harassmentStatus ).toBe( 'error' );
		expect( wrapper.vm.reportedUserStatus ).toBe( 'error' );
	} );

	it( 'Should not collect something else details', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		store.inputBehaviors = [];
		expect( wrapper.vm.collectSomethingElseDetails ).toBe( false );

		store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
		expect( wrapper.vm.collectSomethingElseDetails ).toBe( false );
	} );

	it( 'Should collect something else details', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		store.inputBehaviors = [ Constants.harassmentTypes.OTHER ];
		expect( wrapper.vm.collectSomethingElseDetails ).toBe( true );

		store.inputBehaviors = [
			Constants.harassmentTypes.OTHER,
			Constants.harassmentTypes.HATE_SPEECH
		];
		expect( wrapper.vm.collectSomethingElseDetails ).toBe( true );
	} );
} );
