const { setActivePinia, createPinia } = require( 'pinia' );
const useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' );
const Constants = require( '../../../resources/ext.reportIncident/Constants.js' );

describe( 'Form Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'validates a form correctly', () => {
		const form = useFormStore();
		expect( form.isFormValid ).toBe( false );

		form.inputBehaviors = [ Constants.harassmentTypes.HATE_SPEECH ];
		form.inputReportedUser = 'test value';
		form.inputLink = 'test evidence';

		expect( form.isFormValid ).toBe( true );
	} );
} );
