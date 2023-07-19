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

		form.inputHarassments = [ Constants.harassmentTypes.HATE_SPEECH ];
		form.inputViolator = 'test value';
		form.inputEvidence = 'test evidence';
		form.inputEmail = 'test email';

		expect( form.isFormValid ).toBe( true );
	} );
} );
