'use strict';

const ReportIncidentDialog = require( '../../../resources/ext.reportIncident/components/ReportIncidentDialog.vue' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	utils = require( '@vue/test-utils' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' );

const steps = {
	[ Constants.DIALOG_STEP_1 ]: '<p>Step 1</p>',
	[ Constants.DIALOG_STEP_2 ]: '<p>Step 2</p>'
};

const renderComponent = ( props, slots ) => {
	const defaultProps = { open: false, showPaginator: false };
	const defaultSlots = { title: '<h3>Report Harassment</h3>' };
	return utils.mount( ReportIncidentDialog, {
		global: {
			plugins: [ createTestingPinia( { stubActions: false } ) ]
		},
		props: Object.assign( {}, defaultProps, props ),
		slots: Object.assign( {}, defaultSlots, slots )
	} );
};

describe( 'Report Incident Dialog', () => {
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

	describe( 'footer navigation', () => {
		it( 'navigates from STEP 1 to STEP 2 when the next button is clicked', () => {
			const wrapper = renderComponent( { open: true } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );
			} );
		} );

		it( 'navigates from STEP 2 to STEP 1 when the back button is clicked', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			return wrapper.get( '.ext-reportincident-dialog-footer__back-btn' ).trigger( 'click' ).then( function () {
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
			} );
		} );

		it( 'Clears any form data if navigating back twice from STEP 2', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
			store.inputReportedUser = 'test user';

			return wrapper.get( '.ext-reportincident-dialog-footer__back-btn' ).trigger( 'click' ).then( function () {
				// Clicking back once should put us on STEP 1
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );

				wrapper.get( '.ext-reportincident-dialog-footer__back-btn' ).trigger( 'click' ).then( function () {
					// Clicking back should clear the form store data
					// as the dialog was closed.
					expect( store.inputBehaviors ).toHaveLength( 0 );
					expect( store.inputReportedUser ).toBe( '' );
				} );
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has invalid form data', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			store.inputBehaviors = [ Constants.harassmentTypes.OTHER ];
			expect( store.isFormValidForSubmission() ).toBe( false );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has valid form data', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			const consoleSpy = jest.spyOn( console, 'log' );

			mw.Rest = jest.fn().mockImplementation( () => {
				return {
					post: () => {
						return Promise.resolve();
					}
				};
			} );

			store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
			store.inputReportedUser = 'test user';
			expect( store.isFormValidForSubmission() ).toBe( true );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				// Should be dialog step one if the form submitted correctly.
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
				expect( consoleSpy ).not.toHaveBeenCalled();
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has valid form data in developer mode', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			const consoleSpy = jest.spyOn( console, 'log' );

			mw.Rest = jest.fn().mockImplementation( () => {
				return {
					post: () => {
						return Promise.resolve( { sentEmail: {
							to: [ { address: 'test@test.com' }, { address: 'testing@example.com' } ],
							from: { address: 'b@example.com' },
							subject: 'Testing subject',
							body: 'Testing email body.\nTesting.'
						} } );
					}
				};
			} );

			store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
			store.inputReportedUser = 'test user';
			expect( store.isFormValidForSubmission() ).toBe( true );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				// Should be dialog step one if the form submitted correctly.
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
				// Should have outputted the form data to the console.
				expect( consoleSpy ).toHaveBeenNthCalledWith( 1, 'An email has been sent for this report' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 2, 'Sent from:\nb@example.com' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 3, 'Sent to:\ntest@test.com, testing@example.com' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 4, 'Subject of the email:\nTesting subject' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 5, 'Body of the email:\nTesting email body.\nTesting.' );
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has valid form data', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			const consoleSpy = jest.spyOn( console, 'log' );

			mw.Rest = jest.fn().mockImplementation( () => {
				return {
					post: () => {
						return Promise.resolve();
					}
				};
			} );

			store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
			store.inputReportedUser = 'test user';
			expect( store.isFormValidForSubmission() ).toBe( true );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				// Should be dialog step one if the form submitted correctly.
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_1 );
				// Should have outputted the form data to the console.
				expect( consoleSpy ).not.toHaveBeenCalled();
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has valid form data but API rejects in developer mode', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			const consoleSpy = jest.spyOn( console, 'log' );

			mw.Rest = jest.fn().mockImplementation( () => {
				return {
					post: () => {
						return {
							then: ( _resolveHandler, rejectHandler ) => {
								rejectHandler(
									'http',
									{ xhr: { responseJSON: { sentEmail: {
										to: [ { address: 'test@test.com' }, { address: 'testing@example.com' } ],
										from: { address: 'b@example.com' },
										subject: 'Testing subject',
										body: 'Testing email body.\nTesting.'
									} } } }
								);
							}
						};
					}
				};
			} );

			store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
			store.inputLink = 'test';
			store.inputReportedUser = 'test user';
			expect( store.isFormValidForSubmission() ).toBe( true );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				// Should be dialog step two as the REST API call returned a rejected promise
				// which indicates a failure.
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );
				// Should have outputted the form data to the console.
				expect( consoleSpy ).toHaveBeenNthCalledWith( 1, 'An email has been sent for this report' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 2, 'Sent from:\nb@example.com' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 3, 'Sent to:\ntest@test.com, testing@example.com' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 4, 'Subject of the email:\nTesting subject' );
				expect( consoleSpy ).toHaveBeenNthCalledWith( 5, 'Body of the email:\nTesting email body.\nTesting.' );
			} );
		} );

		it( 'attempts to submit form when next is clicked on STEP 2 and has valid form data but API rejects', () => {
			const wrapper = renderComponent( { open: true, initialStep: Constants.DIALOG_STEP_2 } );
			expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );

			const store = useFormStore();

			const consoleSpy = jest.spyOn( console, 'log' );

			mw.Rest = jest.fn().mockImplementation( () => {
				return {
					post: () => {
						return {
							then: ( _resolveHandler, rejectHandler ) => {
								rejectHandler(
									'http',
									{ xhr: { responseJSON: {} } }
								);
							}
						};
					}
				};
			} );

			store.inputBehaviors = [ Constants.harassmentTypes.INTIMIDATION_AGGRESSION ];
			store.inputReportedUser = 'test user';
			expect( store.isFormValidForSubmission() ).toBe( true );

			return wrapper.get( '.ext-reportincident-dialog-footer__next-btn' ).trigger( 'click' ).then( function () {
				// Should be dialog step one if the form submitted correctly.
				expect( wrapper.vm.currentSlotName ).toBe( Constants.DIALOG_STEP_2 );
				// Should have outputted the form data to the console.
				expect( consoleSpy ).not.toHaveBeenCalled();
			} );
		} );
	} );
} );
