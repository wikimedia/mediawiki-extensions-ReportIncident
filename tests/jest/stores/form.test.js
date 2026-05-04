const { setActivePinia, createPinia } = require( 'pinia' );
const { nextTick } = require( 'vue' );
const useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' );
const Constants = require( '../../../resources/ext.reportIncident/Constants.js' );

describe( 'Form Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
		jest.spyOn( mw, 'message' ).mockImplementation( ( key ) => ( {
			text() {
				return key;
			},
			parse() {
				return key;
			}
		} ) );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	const defaultNonEmergencyPageCases = [
		{
			title: 'intimidation resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.INTIMIDATION,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'doxing resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.DOXING,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'sexual harassment resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SEXUAL_HARASSMENT,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'trolling resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.TROLLING,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'hate speech resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.HATE_SPEECH,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'spam resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SPAM,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'other resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SOMETHING_ELSE,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'sockpuppetry resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SOCKPUPPETRY,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'vandalism resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.VANDALISM,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'user dispute resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.USER_DISPUTE,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'disruptive editing resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.DISRUPTIVE_EDITING,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'other resolution',
			configMocks: {},
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.OTHER,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			expected: [ 'reportincident-nonemergency-helpmethod-default' ]
		},
		{
			title: 'intimidation resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.INTIMIDATION,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyIntimidationHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity: 'baz',
				wgReportIncidentEnableDirectReporting: false
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'doxing resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.DOXING,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyDoxingHelpMethodEmail: 'foo'
			},
			expected: [ 'reportincident-nonemergency-helpmethod-email' ]
		},
		{
			title: 'sexual harassment resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SEXUAL_HARASSMENT,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'trolling resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.TROLLING,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyTrollingHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'hate speech resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.HATE_SPEECH,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyHateSpeechHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyHateSpeechHelpMethodEmail: 'bar'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email'
			]
		},
		{
			title: 'spam resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SPAM,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencySpamHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencySpamHelpMethodEmail: 'bar'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email'
			]
		},
		{
			title: 'something else resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SOMETHING_ELSE,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencySomethingElseHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencySomethingElseHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencySomethingElseHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'sockpuppetry resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.SOCKPUPPETRY,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencySockpuppetryHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencySockpuppetryHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencySockpuppetryHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'vandalism resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.VANDALISM,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyVandalismHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyVandalismHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencyVandalismHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'user dispute resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.USER_DISPUTE,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyUserDisputeHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyUserDisputeHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencyUserDisputeHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'disruptive editing resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.DISRUPTIVE_EDITING,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyDisruptiveEditingHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyDisruptiveEditingHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencyDisruptiveEditingHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		},
		{
			title: 'other resolution, configured',
			storeInputs: {
				inputBehavior: Constants.harassmentTypes.OTHER,
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior
			},
			configMocks: {
				wgReportIncidentNonEmergencyOtherHelpMethodContactAdmin: 'foo',
				wgReportIncidentNonEmergencyOtherHelpMethodEmail: 'bar',
				wgReportIncidentNonEmergencyOtherHelpMethodContactCommunity: 'baz'
			},
			expected: [
				'reportincident-nonemergency-helpmethod-contactadmin',
				'reportincident-nonemergency-helpmethod-email',
				'reportincident-nonemergency-helpmethod-contactcommunity'
			]
		}
	];

	it.each( defaultNonEmergencyPageCases )(
		'$title renders the page with expected help methods',
		async ( { storeInputs, configMocks, expected } ) => {
			jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => {
				if ( key in configMocks ) {
					return configMocks[ key ];
				} else {
					return null;
				}
			} );
			const form = useFormStore();
			Object.entries( storeInputs ).forEach( ( [ key, value ] ) => {
				form[ key ] = value;
			} );
			await nextTick();
			expect( form.validNonEmergencyHelpMethods ).toStrictEqual( expected );
		}
	);

	it( 'resets the form properly on call to $reset', () => {
		const form = useFormStore();
		form.inputBehavior = Constants.harassmentTypes.HATE_SPEECH;
		form.inputReportedUser = 'test value';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';
		form.overflowMenuData = { test: 'testing' };
		form.inputReportedUserDisabled = true;
		form.displaySomethingElseTextboxRequiredError = true;
		form.displayBehaviorsRequiredError = true;
		form.funnelEntryToken = 'foo';
		form.funnelName = 'bar';

		form.$reset();

		// Form fields should be empty
		expect( form.inputBehavior ).toStrictEqual( '' );
		expect( form.inputReportedUser ).toBe( '' );
		expect( form.inputDetails ).toBe( '' );
		expect( form.inputSomethingElseDetails ).toBe( '' );
		// DiscussionTools overflowMenuData should be empty
		expect( form.overflowMenuData ).toStrictEqual( {} );
		// Required field checks should be disabled again (they are enabled on
		// pressing submit or un-focusing that required field).
		expect( form.displaySomethingElseTextboxRequiredError ).toBe( false );
		expect( form.displayBehaviorsRequiredError ).toBe( false );
		// Username field should be un-disabled
		expect( form.inputReportedUserDisabled ).toBe( false );

		expect( form.funnelEntryToken ).toBe( '' );
		expect( form.funnelName ).toBe( '' );
	} );

	it( 'Generates correct rest data', () => {
		const form = useFormStore();
		form.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
		form.inputBehavior = Constants.harassmentTypes.INTIMIDATION;
		form.inputReportedUser = 'test user';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';

		// Something else details should not be specified as it is not in the behaviours array.
		expect( form.restPayload ).toStrictEqual( {
			reportedUser: 'test user',
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
			behaviorType: Constants.harassmentTypes.INTIMIDATION
		} );

		form.inputBehavior = Constants.harassmentTypes.SOMETHING_ELSE;
		expect( form.restPayload ).toStrictEqual( {
			reportedUser: 'test user',
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
			behaviorType: Constants.harassmentTypes.SOMETHING_ELSE,
			somethingElseDetails: 'test something else details'
		} );

		form.overflowMenuData = { 'thread-id': 'c-test_user-20230605040302' };
		expect( form.restPayload ).toStrictEqual( {
			reportedUser: 'test user',
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
			behaviorType: Constants.harassmentTypes.SOMETHING_ELSE,
			somethingElseDetails: 'test something else details',
			threadId: 'c-test_user-20230605040302'
		} );

		form.incidentType = Constants.typeOfIncident.immediateThreatPhysicalHarm;
		form.physicalHarmType = Constants.physicalHarmTypes.publicHarm;
		expect( form.restPayload ).toStrictEqual( {
			reportedUser: 'test user',
			incidentType: Constants.typeOfIncident.immediateThreatPhysicalHarm,
			physicalHarmType: Constants.physicalHarmTypes.publicHarm,
			details: 'test details',
			threadId: 'c-test_user-20230605040302'
		} );
	} );

	it( 'Generates no error messages before user has interacted with the form', () => {
		const form = useFormStore();

		// Test no errors are present before the user has interacted with the form (such
		// as attempting to submit or focusing the required fields).
		expect( form.formErrorMessages ).toStrictEqual( {} );
	} );

	it( 'Generates correct error messages for required fields (non-emergency flow)', () => {
		const form = useFormStore();

		// Test that no error messages are generated when the data is correct
		//
		form.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
		form.inputBehavior = Constants.harassmentTypes.SOMETHING_ELSE;

		form.isFormValidForSubmission(); // Triggers validations

		expect( form.formErrorMessages ).toStrictEqual( {
			inputBehaviors: {
				error: mw.msg( 'reportincident-dialog-something-else-empty' )
			}
		} );

		form.inputSomethingElseDetails = 'details';

		form.isFormValidForSubmission(); // Triggers validations

		expect( form.formErrorMessages ).toStrictEqual( {} );
	} );

	it( 'Generates correct error messages for required fields (emergency flow)', () => {
		const form = useFormStore();

		// Test that no error messages are generated when the data is correct
		//
		form.incidentType = Constants.typeOfIncident.immediateThreatPhysicalHarm;
		form.inputReportedUser = 'test value';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';

		form.isFormValidForSubmission(); // Triggers validations

		expect( form.formErrorMessages ).toStrictEqual( {} );

		form.isFormValidForSubmission(); // Triggers validations

		form.inputBehavior = Constants.harassmentTypes.TROLLING;

		form.isFormValidForSubmission(); // Triggers validations

		expect( form.formErrorMessages ).toStrictEqual( {} );
	} );

	it( 'Generates something-else error for empty something else textbox', () => {
		const form = useFormStore();
		// Test that emptying the something-else field while 'Something else' is a selected
		// behaviour causes an error
		form.inputBehavior = Constants.harassmentTypes.SOMETHING_ELSE;
		form.inputReportedUser = 'test value';
		form.inputDetails = 'test details';
		form.inputSomethingElseDetails = 'test something else details';

		form.isFormValidForSubmission(); // Triggers validations

		form.inputSomethingElseDetails = '';
		expect( form.formErrorMessages ).toStrictEqual( {
			inputBehaviors: { error: mw.msg( 'reportincident-dialog-something-else-empty' ) }
		} );
	} );

	it( 'physicalHarmTypeContext is "na" when incidentType is not set', () => {
		const form = useFormStore();

		expect( form.physicalHarmTypeContext ).toBe( 'na' );
	} );

	it( 'physicalHarmTypeContext is "na" when incidentType is not physical harm', () => {
		const form = useFormStore();

		form.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;

		expect( form.physicalHarmTypeContext ).toBe( 'na' );
	} );

	it( 'physicalHarmTypeContext is "na" when no harm type is selected', () => {
		const form = useFormStore();

		form.incidentType = Constants.typeOfIncident.immediateThreatPhysicalHarm;

		expect( form.physicalHarmTypeContext ).toBe( 'na' );
	} );

	it( 'physicalHarmTypeContext is derived from selected harm type', () => {
		const form = useFormStore();

		form.incidentType = Constants.typeOfIncident.immediateThreatPhysicalHarm;
		form.physicalHarmType = Constants.physicalHarmTypes.publicHarm;

		expect( form.physicalHarmTypeContext ).toBe( 'public' );
	} );
} );
