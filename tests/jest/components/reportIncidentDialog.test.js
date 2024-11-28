'use strict';

jest.mock( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

jest.mock( '../../../resources/ext.reportIncident/components/icons.json', () => ( {
	cdxIconLock: '',
	cdxIconUserGroup: ''
} ), { virtual: true } );
const ReportIncidentDialog = require( '../../../resources/ext.reportIncident/components/ReportIncidentDialog.vue' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	utils = require( '@vue/test-utils' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' ),
	useInstrument = require( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

const { storeToRefs } = require( 'pinia' );
const { nextTick } = require( 'vue' );

const steps = {
	[ Constants.DIALOG_STEP_1 ]: '<p>Step 1</p>',
	[ Constants.DIALOG_STEP_2 ]: '<p>Step 2</p>'
};

/**
 * Mocks mw.Rest().post() and returns a jest.fn()
 * that is used as the post() method. This can
 * be used to expect that the post() method is
 * called with the correct arguments.
 *
 * If a function is provided as the returnValue,
 * the return value of that function is used.
 *
 * @param {*} returnValue
 * @return {jest.fn}
 */
function mockRestPost( returnValue ) {
	mw.Rest = () => {};
	const restPost = jest.fn();
	restPost.mockImplementation( () => {
		if ( returnValue instanceof Function ) {
			return returnValue();
		}
		return returnValue;
	} );
	jest.spyOn( mw, 'Rest' ).mockImplementation( () => ( {
		post: restPost
	} ) );
	return restPost;
}

const renderComponent = ( props, slots, initialState = {} ) => {
	const defaultProps = { open: false, showPaginator: false };
	const defaultSlots = { title: '<h3>Report Harassment</h3>' };
	return utils.mount( ReportIncidentDialog, {
		global: {
			plugins: [ createTestingPinia( {
				initialState: { form: initialState },
				stubActions: false
			} ) ]
		},
		props: Object.assign( {}, defaultProps, props ),
		slots: Object.assign( {}, defaultSlots, slots )
	} );
};

describe( 'Report Incident Dialog', () => {
	const logEvent = jest.fn();

	beforeEach( () => {
		useInstrument.mockImplementation( () => logEvent );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'mounts the component', () => {
		const wrapper = renderComponent( { open: true } );
		expect( wrapper.find( '.ext-reportincident-dialog' ).exists() ).toBe( true );
	} );

	it( 'should open the dialog based on "open" prop state', () => {
		const wrapper = renderComponent();
		expect( wrapper.find( '.ext-reportincident-dialog__content' ).exists() ).toBe( false );
		return wrapper.setProps( { open: true } ).then( () => {
			expect( wrapper.find( '.ext-reportincident-dialog__content' ).exists() ).toBe( true );
		} );
	} );

	it( 'should render content passed as step 1 by default', () => {
		const wrapper = renderComponent( { open: true }, steps );
		expect( wrapper.html() ).toContain( 'Step 1' );
		expect( wrapper.text() ).not.toContain( 'Step 2' );
	} );

	describe( 'footer messages', () => {
		it( 'Should not display a footer explanation when first rendered and no radio button is selected', () => {
			const wrapper = renderComponent( { open: true } );
			expect( wrapper.vm.showFooterHelpText ).toBe( false );
		} );

		it( 'Should show validation errors if no incident type is selected', async () => {
			const wrapper = renderComponent( { open: true } );
			const store = useFormStore();
			const { showValidationError } = storeToRefs( store );

			await wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' );
			await wrapper.vm.$nextTick();

			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
			expect( store.isIncidentTypeSelected() ).toBe( false );
			expect( showValidationError.value ).toBe( true );
		} );

		it( 'Should display help text only on step 1', () => {
			const wrapper = renderComponent( { open: true } );
			const store = useFormStore();
			store.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
			expect( wrapper.vm.showFooterHelpText ).toBe( true );
		} );

		it( 'Should not display form error messages on step 1', () => {
			const wrapper = renderComponent( { open: true } );
			wrapper.vm.footerErrorMessage = 'test';
			expect( wrapper.vm.showFooterErrorText ).toBe( false );
		} );

		it( 'Should display form error messages on step 2', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			wrapper.vm.footerErrorMessage = 'test';
			expect( wrapper.vm.showFooterErrorText ).toBe( true );
		} );

		describe( 'footer server error messages', () => {
			it( 'Should add footer error message on call to onReportSubmitFailure with no data', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				jest.spyOn( navigator, 'onLine', 'get' ).mockReturnValue( true );
				// No JSON in the error object should lead the generic error to display.
				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 0 }
				} );
				expect( wrapper.vm.showFooterErrorText ).toBe( true );
				expect( wrapper.vm.footerErrorMessage ).toBe( 'reportincident-dialog-generic-error' );
			} );

			it( 'Should add footer error message on call to onReportSubmitFailure with no data when offline', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				// Mock that navigator.onLine is false.
				jest.spyOn( navigator, 'onLine', 'get' ).mockReturnValue( false );
				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 0 }
				} );
				expect( wrapper.vm.showFooterErrorText ).toBe( true );
				// As navigator.onLine is false, the internet disconnected error should be shown
				expect( wrapper.vm.footerErrorMessage ).toBe( 'reportincident-dialog-internet-disconnected-error' );
			} );

			it( 'Should add footer error message on call to onReportSubmitFailure with xhr indicating server error', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				jest.spyOn( navigator, 'onLine', 'get' ).mockReturnValue( true );
				// Set the HTTP status code to 501, which is a 5XX code.
				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 501 }
				} );
				expect( wrapper.vm.showFooterErrorText ).toBe( true );
				// As the HTTP status code is 5XX, the server error message should be shown
				expect( wrapper.vm.footerErrorMessage ).toBe( 'reportincident-dialog-server-error' );
			} );

			it( 'Should add form specific error message on call to onReportSubmitFailure with errorKey indicating reported user does not exist', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				const store = useFormStore();
				// The error will cause the field to be un-disabled if it was
				// disabled, so set it to be disabled so that the test can
				// observe a change in value.
				store.inputReportedUserDisabled = true;
				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 404, responseJSON: { errorKey: 'reportincident-dialog-violator-nonexistent' } }
				} );
				// This error is shown for the reported user field, so the footer error
				// message should be empty.
				expect( wrapper.vm.showFooterErrorText ).toBe( false );
				expect( wrapper.vm.footerErrorMessage ).toBe( '' );
				// Expect that the error is shown for the reported user field.
				expect( store.reportedUserDoesNotExist ).toBe( true );
				expect( store.inputReportedUserDisabled ).toBe( false );
			} );

			it( 'Should use server-side error message on call to onReportSubmitFailure when available', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				const errMsg = 'This is some server error';

				jest.spyOn( mw.config, 'get' ).mockReturnValue( 'en' );

				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 404, responseJSON: { errorKey: 'some-example-error', messageTranslations: { en: errMsg } } }
				} );

				expect( mw.config.get.mock.calls ).toEqual( [ [ 'wgUserLanguage' ] ] );
				expect( wrapper.vm.showFooterErrorText ).toBe( true );
				expect( wrapper.vm.footerErrorMessage ).toBe( errMsg );
			} );

			it( 'Should use generic error message on call to onReportSubmitFailure when server-side error message is unlocalized', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				const errMsg = 'This is some server error';

				jest.spyOn( mw.config, 'get' ).mockReturnValue( 'de' );

				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 404, responseJSON: { errorKey: 'some-example-error', messageTranslations: { en: errMsg } } }
				} );

				expect( mw.config.get.mock.calls ).toEqual( [ [ 'wgUserLanguage' ] ] );
				expect( wrapper.vm.showFooterErrorText ).toBe( true );
				expect( wrapper.vm.footerErrorMessage ).toBe( 'reportincident-dialog-generic-error' );
			} );

			it( 'Should add footer error message on call to onReportSubmitFailure with errorKey that is not otherwise handled', () => {
				const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
				wrapper.vm.onReportSubmitFailure( 'http', {
					xhr: { status: 403, responseJSON: { errorKey: 'apierror-permissiondenied' } }
				} );
				// This error is not handled separately, so the generic error should be shown.
				expect( wrapper.vm.showFooterErrorText ).toBe( true );
				expect( wrapper.vm.footerErrorMessage ).toBe( 'reportincident-dialog-generic-error' );
			} );
		} );
	} );

	describe( 'footer navigation', () => {
		beforeEach( () => {
			jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => {
				switch ( key ) {
					case 'wgReportIncidentAdministratorsPage':
						return 'Wikipedia:Administrators';
					case 'wgReportIncidentUserHasConfirmedEmail':
						return true;
					case 'wgCurRevisionId':
						return 1;
					case 'wgPageName':
						return 'Test_page';
					default:
						throw new Error( 'Unknown key: ' + key );
				}
			} );
		} );

		it( 'navigates from STEP 1 to STEP 2 when the next button is clicked', async () => {
			const wrapper = renderComponent( { open: true } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
			const store = useFormStore();
			store.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
			await wrapper.vm.$nextTick();
			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( () => {
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );
			} );
		} );

		it( 'navigates from STEP 2 to STEP 1 when the back button is clicked', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			return wrapper.get( '.ext-reportincident-dialog-footer__back-btn' ).trigger( 'click' ).then( () => {
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
			} );
		} );

		it( 'Clears any form data if navigating back twice from STEP 2', async () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			store.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
			store.inputBehavior = Constants.harassmentTypes.INTIMIDATION;
			store.inputReportedUser = 'test user';

			await wrapper.get( '.ext-reportincident-dialog-footer__back-btn' ).trigger( 'click' );

			// Clicking back once should put us on STEP 1
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
			expect( logEvent ).not.toHaveBeenCalled();

			await wrapper.get( '.ext-reportincident-dialog-footer__back-btn' ).trigger( 'click' );

			// Clicking back should clear the form store data
			// as the dialog was closed.
			expect( store.inputBehavior ).toBe( '' );
			expect( store.inputReportedUser ).toBe( '' );

			expect( logEvent ).toHaveBeenCalledTimes( 1 );
			expect( logEvent ).toHaveBeenCalledWith( 'click', {
				source: 'form',
				subType: 'cancel'
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has invalid form data', async () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();
			const restPost = mockRestPost( Promise.resolve() );

			store.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
			store.inputBehavior = Constants.harassmentTypes.OTHER;
			expect( store.isFormValidForSubmission() ).toBe( false );

			// Set the footerErrorMessage value as it should be cleared if the
			// client side validation fails after a user presses submit.
			wrapper.vm.footerErrorMessage = 'test';

			// After providing the missing details, the submission succeeds
			store.inputSomethingElseDetails = 'test details';

			// Wait until the next tick so that the callback set for nextTick in
			// the code under-test has run.
			return nextTick( () => {
				expect( store.isFormValidForSubmission() ).toBe( true );

				return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( () => {
					expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_SUBMIT_SUCCESS );
					expect( wrapper.vm.footerErrorMessage ).toBe( '' );
					expect( restPost ).toHaveBeenCalledWith(
						'/reportincident/v0/report',
						{
							incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
							behaviorType: Constants.harassmentTypes.OTHER,
							reportedUser: '',
							somethingElseDetails: 'test details',
							revisionId: 1
						}
					);
				} );
			} );
		} );

		describe( 'attempts to submit form when next is clicked on STEP 2', () => {
			const validSubmitTestCases = {
				'valid form data': [
					{
						incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
						inputBehavior: Constants.harassmentTypes.HATE_SPEECH,
						inputReportedUser: 'test user'
					},
					{
						incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
						behaviorType: Constants.harassmentTypes.HATE_SPEECH,
						reportedUser: 'test user',
						revisionId: 1
					}
				],
				'valid form data in emergency flow': [
					{
						incidentType: Constants.typeOfIncident.immediateThreatPhysicalHarm,
						physicalHarmType: Constants.physicalHarmTypes.physicalHarm,
						inputDetails: 'some details',
						inputReportedUser: 'test user'
					},
					{
						incidentType: Constants.typeOfIncident.immediateThreatPhysicalHarm,
						physicalHarmType: Constants.physicalHarmTypes.physicalHarm,
						details: 'some details',
						reportedUser: 'test user',
						revisionId: 1
					}
				],
				'valid form data with "something else"': [
					{
						incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
						inputBehavior: Constants.harassmentTypes.OTHER,
						inputSomethingElseDetails: 'details',
						inputReportedUser: 'test user'
					},
					{
						incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
						behaviorType: Constants.harassmentTypes.OTHER,
						somethingElseDetails: 'details',
						reportedUser: 'test user',
						revisionId: 1
					}
				]
			};

			for ( const testName of Object.keys( validSubmitTestCases ) ) {
				const [ initialState, expectedRestPayload ] = validSubmitTestCases[ testName ];

				it( testName, async () => {
					const wrapper = renderComponent(
						{ open: true, initialStep: Constants.DIALOG_STEP_2 },
						undefined,
						initialState
					);
					expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

					const store = useFormStore();

					const restPost = mockRestPost( Promise.resolve() );

					expect( store.isFormValidForSubmission() ).toBe( true );

					await wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' );

					expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_SUBMIT_SUCCESS );

					expect( restPost ).toHaveBeenCalledWith(
						'/reportincident/v0/report',
						expectedRestPayload
					);
					expect( logEvent ).toHaveBeenCalledTimes( 1 );

					expect(
						wrapper.find( '.ext-reportincident-dialog__form-error-text' ).exists()
					).toBe( false );

					if ( store.incidentType === Constants.typeOfIncident.immediateThreatPhysicalHarm ) {
						expect( logEvent ).toHaveBeenCalledWith( 'click', {
							subType: 'continue',
							source: 'submit_report',
							context: JSON.stringify( {
								// eslint-disable-next-line camelcase
								addl_info: !!( store.inputSomethingElseDetails || store.inputDetails ),
								// eslint-disable-next-line camelcase
								reported_user: store.inputReportedUser
							} )
						} );
					} else {
						expect( logEvent ).toHaveBeenCalledWith( 'click', {
							context: store.inputBehavior,
							source: 'describe_unacceptable_behavior',
							subType: 'continue'
						} );
					}
				} );
			}
		} );

		it( 'should clear and close dialog when exiting from submit success screen', async () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_SUBMIT_SUCCESS } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_SUBMIT_SUCCESS );

			const store = useFormStore();

			store.incidentType = Constants.typeOfIncident.unacceptableUserBehavior;
			store.inputBehavior = Constants.harassmentTypes.INTIMIDATION;
			store.inputReportedUser = 'test user';

			await wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' );

			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
			expect( wrapper.vm.formSubmissionInProgress ).toBe( false );
			expect( store.inputReportedUser ).toBe( '' );
		} );

		const submitErrorTestCases = {
			'API error when submitting from non-emergency flow': [
				Constants.DIALOG_STEP_2,
				Constants.typeOfIncident.unacceptableUserBehavior,
				'',
				Constants.harassmentTypes.INTIMIDATION
			],
			'API error when submitting from emergency flow': [
				Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM,
				Constants.typeOfIncident.immediateThreatPhysicalHarm,
				Constants.physicalHarmTypes.publicHarm,
				''
			]
		};

		for ( const testName of Object.keys( submitErrorTestCases ) ) {
			const [
				initialStep, incidentType, physicalHarmType, behaviorType
			] = submitErrorTestCases[ testName ];

			it( testName, async () => {
				const wrapper = renderComponent( { open: true, initialStep } );
				expect( wrapper.vm.currentSlotName ).toBe( initialStep );

				const store = useFormStore();

				const userTokensSpy = jest.spyOn( mw.user.tokens, 'get' ).mockImplementation( ( tokenType ) => {
					switch ( tokenType ) {
						case 'csrfToken':
							return 'csrf-token';
						default:
							throw new Error( 'Unknown token type: ' + tokenType );
					}
				} );
				const restPost = mockRestPost( () => {
					// Form should be in submission when the REST API is called.
					expect( wrapper.vm.formSubmissionInProgress ).toBe( true );
					return {
						then: ( _resolveHandler, rejectHandler ) => {
							rejectHandler(
								'http',
								{ xhr: { responseJSON: {} } }
							);
						}
					};
				} );

				store.incidentType = incidentType;
				store.inputBehavior = behaviorType;
				store.physicalHarmType = physicalHarmType;
				store.inputReportedUser = 'test user';
				expect( store.isFormValidForSubmission() ).toBe( true );

				expect( wrapper.vm.formSubmissionInProgress ).toBe( false );

				await wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' );

				expect( wrapper.vm.currentSlotName ).toBe( initialStep );

				expect( restPost ).toHaveBeenCalledTimes( 1 );
				expect( userTokensSpy ).toHaveBeenCalledWith( 'csrfToken' );
				// Form should not be in submission if the form has finished submitting.
				expect( wrapper.vm.formSubmissionInProgress ).toBe( false );

				expect(
					wrapper.find( '.ext-reportincident-dialog__form-error-text' ).text()
				).toBe( 'reportincident-dialog-generic-error' );

				expect( logEvent ).toHaveBeenCalledTimes( 1 );

				if ( store.incidentType === Constants.typeOfIncident.immediateThreatPhysicalHarm ) {
					expect( logEvent ).toHaveBeenCalledWith( 'click', {
						subType: 'continue',
						source: 'submit_report',
						context: JSON.stringify( {
							// eslint-disable-next-line camelcase
							addl_info: !!( store.inputSomethingElseDetails || store.inputDetails ),
							// eslint-disable-next-line camelcase
							reported_user: store.inputReportedUser
						} )
					} );
				} else {
					expect( logEvent ).toHaveBeenCalledWith( 'click', {
						context: store.inputBehavior,
						source: 'describe_unacceptable_behavior',
						subType: 'continue'
					} );
				}
			} );
		}
	} );

	const closeTestCases = [
		[ 'STEP_1', Constants.DIALOG_STEP_1, 'form' ],
		[ 'REPORT_IMMEDIATE_HARM', Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM, 'submit_report' ],
		[ 'SUCCESS', Constants.DIALOG_STEP_SUBMIT_SUCCESS, 'success' ]
	];

	for ( const [ stepName, initialStep, source ] of closeTestCases ) {
		it( `closes the dialog via the close button on step ${ stepName }`, async () => {
			const mockConfig = {
				wgPageName: 'test'
			};
			jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => mockConfig[ key ] );

			const wrapper = renderComponent( { open: true, initialStep } );

			await wrapper.get( '.cdx-dialog__header__close-button' ).trigger( 'click' );

			expect( logEvent ).toHaveBeenCalledTimes( 1 );
			expect( logEvent ).toHaveBeenCalledWith( 'click', {
				source,
				subType: 'close'
			} );
		} );
	}

} );
