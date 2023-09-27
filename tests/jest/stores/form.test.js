const { setActivePinia, createPinia } = require( 'pinia' );
const useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' );
const Constants = require( '../../../resources/ext.reportIncident/Constants.js' );

describe( 'Form Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'validates a form correctly', () => {
		const form = useFormStore();
		expect( form.isFormValidForSubmission() ).toBe( false );

		form.inputBehaviors = [ Constants.harassmentTypes.HATE_SPEECH ];
		form.inputReportedUser = 'test value';
		form.inputLink = 'test evidence';

		expect( form.isFormValidForSubmission() ).toBe( true );
	} );

	it( 'resets the form properly on call to $reset', () => {
		const form = useFormStore();
		form.inputBehaviors = [
			Constants.harassmentTypes.HATE_SPEECH, Constants.harassmentTypes.INTIMIDATION_AGGRESSION
		];
		form.inputReportedUser = 'test value';
		form.inputLink = 'test evidence';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';

		form.$reset();
		expect( form.inputBehaviors ).toStrictEqual( [] );
		expect( form.inputReportedUser ).toBe( '' );
		expect( form.inputLink ).toBe( '' );
		expect( form.inputDetails ).toBe( '' );
		expect( form.inputSomethingElseDetails ).toBe( '' );
	} );

	it( 'Generates correct rest data', () => {
		const form = useFormStore();
		form.inputBehaviors = [
			Constants.harassmentTypes.HATE_SPEECH, Constants.harassmentTypes.INTIMIDATION_AGGRESSION
		];
		form.inputReportedUser = 'test value';
		form.inputLink = 'test evidence';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';

		// Something else details should not be specified as it is not in the behaviours array
		expect( form.restPayload ).toStrictEqual( {
			reportedUserId: 'test value',
			link: 'test evidence',
			details: 'test details',
			behaviors: [
				Constants.harassmentTypes.HATE_SPEECH, Constants.harassmentTypes.INTIMIDATION_AGGRESSION
			]
		} );

		form.inputBehaviors = [ Constants.harassmentTypes.OTHER ];
		expect( form.restPayload ).toStrictEqual( {
			reportedUserId: 'test value',
			link: 'test evidence',
			details: 'test details',
			behaviors: [ Constants.harassmentTypes.OTHER ],
			somethingElseDetails: 'test something else details'
		} );
	} );

	it( 'Generates no error messages before user has interacted with the form', () => {
		const form = useFormStore();

		// Test no errors are present before the user has interacted with the form (such
		// as attempting to submit or focusing the required fields).
		expect( form.formErrorMessages ).toStrictEqual( {} );
	} );

	it( 'Generates correct error messages', () => {
		const form = useFormStore();

		// Test that no error messages are generated when the data is correct
		form.inputBehaviors = [
			Constants.harassmentTypes.HATE_SPEECH, Constants.harassmentTypes.INTIMIDATION_AGGRESSION
		];
		form.inputReportedUser = 'test value';
		form.inputLink = 'test evidence';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';
		expect( form.formErrorMessages ).toStrictEqual( {} );

		// Test that emptying some of the required fields causes errors for only those fields.
		form.inputLink = '';
		expect( form.formErrorMessages ).toStrictEqual( {
			inputLink: { error: mw.msg( 'reportincident-dialog-links-empty' ) }
		} );

		// Test that emptying all the required fields generates error messages
		form.inputReportedUser = '';
		form.inputBehaviors = [];
		expect( form.formErrorMessages ).toStrictEqual( {
			inputBehaviors: { error: mw.msg( 'reportincident-dialog-harassment-empty' ) },
			inputLink: { error: mw.msg( 'reportincident-dialog-links-empty' ) },
			inputReportedUser: { error: mw.msg( 'reportincident-dialog-violator-empty' ) }
		} );
	} );

	it( 'Generates something-else error for empty something else textbox', () => {
		const form = useFormStore();
		// Test that emptying the something-else field while 'Something else' is a selected
		// behaviour causes an error
		form.inputBehaviors = [ Constants.harassmentTypes.OTHER ];
		form.inputReportedUser = 'test value';
		form.inputLink = 'test evidence';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';
		form.isFormValidForSubmission();
		form.inputSomethingElseDetails = '';
		expect( form.formErrorMessages ).toStrictEqual( {
			inputBehaviors: { error: mw.msg( 'reportincident-dialog-something-else-empty' ) }
		} );
	} );
} );
