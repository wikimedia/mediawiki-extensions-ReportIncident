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

/**
 * Wait until the debounce performed by loadSuggestedUsernames
 * is complete by waiting 120ms (longer than the 100ms delay
 * in that function).
 *
 * @return {Promise}
 */
const waitUntilDebounceComplete = () => {
	return new Promise( ( resolve ) => {
		setTimeout( () => {
			resolve();
		}, 120 );
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

	it( 'Should update menu config on change in window height', () => {
		const wrapper = renderComponent();

		// Set the window height to 1 to test that the minimum visibleItemLimit will be 2.
		wrapper.vm.windowHeight = 1;
		expect( wrapper.vm.reportedUserLookupMenuConfig.visibleItemLimit ).toBe( 2 );

		// Set the window height to 1000 to test that the maximum visibleItemLimit is 5.
		wrapper.vm.windowHeight = 1;
		expect( wrapper.vm.reportedUserLookupMenuConfig.visibleItemLimit ).toBe( 2 );

		// Set the window height to 500 to test the x / 150 calculation
		wrapper.vm.windowHeight = 500;
		// The floor division of 500 by 150 is 3.
		expect( wrapper.vm.reportedUserLookupMenuConfig.visibleItemLimit ).toBe( 3 );
	} );

	it( 'Should update form store on call to onReportedUserSelected', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		// Set displayReportedUserRequiredError to true so that the function can update it
		store.displayReportedUserRequiredError = true;
		// Set inputReportedUserSelection to a different value to inputReportedUser to
		// so that the function can update the inputReportedUser
		store.inputReportedUser = 'test';
		wrapper.vm.inputReportedUserSelection = 'testing';
		// store.inputReportedUser should be unchanged until onReportedUserSelected is called.
		expect( store.inputReportedUser ).toBe( 'test' );
		wrapper.vm.onReportedUserSelected();
		// store.inputReportedUser should now be the value of inputReportedUserSelection
		expect( store.inputReportedUser ).toBe( 'testing' );
		// The required field check for the user field should now be disabled.
		expect( store.displayReportedUserRequiredError ).toBe( false );
	} );

	it( 'Should query allusers API on call to onReportedUserInput', () => {
		const wrapper = renderComponent();

		mw.Rest = jest.fn().mockImplementation( () => {
			return {
				get: ( data ) => {
					expect( data ).toStrictEqual( {
						action: 'query',
						list: 'allusers',
						auprefix: 'testing',
						limit: '10'
					} );
					return Promise.resolve(
						{ query: { allusers: [
							{ userid: 1, name: 'testing' },
							{ userid: 2, name: 'testing1' },
							{ userid: 3, name: 'testing2' }
						] } }
					);
				}
			};
		} );

		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		waitUntilDebounceComplete().then( () => {
			// The suggestions should now be set.
			expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [
				{ value: 'testing' },
				{ value: 'testing1' },
				{ value: 'testing2' }
			] );
		} );
	} );

	it( 'Call to onReportedUserInput but API promise rejects', () => {
		const wrapper = renderComponent();

		mw.Rest = jest.fn().mockImplementation( () => {
			return {
				get: ( data ) => {
					expect( data ).toStrictEqual( {
						action: 'query',
						list: 'allusers',
						auprefix: 'testing',
						limit: '10'
					} );
					return Promise.reject();
				}
			};
		} );
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		waitUntilDebounceComplete().then( () => {
			// The suggestions should now be set.
			expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
		} );
	} );

	it( 'Call to onReportedUserInput but input is updated before API request finished', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		mw.Rest = jest.fn().mockImplementation( () => {
			return {
				get: ( data ) => {
					expect( data ).toStrictEqual( {
						action: 'query',
						list: 'allusers',
						auprefix: 'testingabc',
						limit: '10'
					} );
					return Promise.reject();
				}
			};
		} );
		// Update the value of inputReportedUserMenuItems so that the test can verify it empties on a failed request.
		wrapper.vm.suggestedUsernames = [ { name: 'test123123123123123' } ];
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testingabc' );
		// Update the value of store.inputReportedUser before the debounce timer has finished.
		store.inputReportedUser = 'testing1234';
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		waitUntilDebounceComplete().then( () => {
			// The suggestions should now be set.
			expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
		} );
	} );

	it( 'Call to onReportedUserInput but API returns unparsable response', () => {
		const wrapper = renderComponent();

		mw.Rest = jest.fn().mockImplementation( () => {
			return {
				get: ( data ) => {
					expect( data ).toStrictEqual( {
						action: 'query',
						list: 'allusers',
						auprefix: 'testing12',
						limit: '10'
					} );
					return Promise.resolve( { test: 'test' } );
				}
			};
		} );
		// Update the value of inputReportedUserMenuItems so that the test can verify it empties on a failed request.
		wrapper.vm.suggestedUsernames = [ { name: 'testing123123' } ];
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing12' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		waitUntilDebounceComplete().then( () => {
			// The suggestions should now be set.
			expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
		} );
	} );

	it( 'Call to onReportedUserInput but input is empty', () => {
		const wrapper = renderComponent();
		// Update the value of inputReportedUserMenuItems so that the test can verify it empties on a failed request.
		wrapper.vm.suggestedUsernames = [ { name: 'testing123123' } ];
		// Call the method under test
		wrapper.vm.onReportedUserInput( '' );
		// The suggetions should be empty for an empty input.
		expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
	} );

	it( 'Call to onReportedUserInput twice within the debounce period', () => {
		const wrapper = renderComponent();

		mw.Rest = jest.fn().mockImplementation( () => {
			return {
				get: ( data ) => {
					// The get method should only be called once as the debounce
					// should prevent the duplicate API call.
					expect( data ).toStrictEqual( {
						action: 'query',
						list: 'allusers',
						auprefix: 'testing123',
						limit: '10'
					} );
					return Promise.resolve(
						{ query: { allusers: [
							{ userid: 1, name: 'testing123' },
							{ userid: 2, name: 'testing1234' },
							{ userid: 3, name: 'testing12345' }
						] } }
					);
				}
			};
		} );

		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing12' );
		// Call the method under test again.
		wrapper.vm.onReportedUserInput( 'testing123' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		waitUntilDebounceComplete().then( () => {
			// The suggestions should now be set.
			expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [
				{ value: 'testing123' },
				{ value: 'testing1234' },
				{ value: 'testing12345' }
			] );
		} );
	} );
} );
