'use strict';

const { mockCodePointLength } = require( '../utils.js' );

// Need to run this here as the import of ReportIncidentDialogStep2.vue
// without mediawiki.String defined causes errors in running these tests.
mockCodePointLength();

const ReportImmediateHarmStep = require( '../../../resources/ext.reportIncident/components/ReportImmediateHarmStep.vue' ),
	utils = require( '@vue/test-utils' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	{ mockApiGet } = require( '../utils.js' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	useFormStore = require( '../../../resources/ext.reportIncident/stores/Form.js' );

const renderComponent = ( testingPinia ) => utils.mount( ReportImmediateHarmStep, {
	global: {
		// eslint-disable-next-line es-x/no-nullish-coalescing-operators
		plugins: [ testingPinia ?? createTestingPinia( { stubActions: false } ) ]
	}
} );

/**
 * Mocks mw.log.error() and returns a jest.fn() for error()
 *
 * @return {jest.fn}
 */
function mockErrorLogger() {
	const mwLogError = jest.fn();
	mw.log.error = mwLogError;
	return mwLogError;
}

/**
 * Wait until the debounce performed by loadSuggestedUsernames
 * is complete by waiting 120ms (longer than the 100ms delay
 * in that function).
 *
 * @return {Promise}
 */
const waitUntilDebounceComplete = () => new Promise( ( resolve ) => {
	setTimeout( () => {
		resolve();
	}, 120 );
} );

describe( 'ReportImmediateHarmStep', () => {
	let jQueryCodePointLimitMock;
	beforeEach( () => {
		// Mock the codePointLimit which is added by a plugin.
		jQueryCodePointLimitMock = jest.fn();
		global.$.prototype.codePointLimit = jQueryCodePointLimitMock;
	} );

	it( 'renders correctly', () => {
		const wrapper = renderComponent();
		expect( wrapper.find( '.ext-reportincident-dialog-step2' ).exists() ).toBe( true );
		// Expect that the value of wgCommentCodePointLimit is passed to the codePointLimit call
		// for the additional details field.
		expect( jQueryCodePointLimitMock ).toHaveBeenCalledWith( Constants.detailsCodepointLimit );
	} );

	it( 'has all default form elements loaded', () => {
		const wrapper = renderComponent();

		expect( wrapper.find( '.ext-reportincident-dialog-step2__violator-name' ).exists() ).toBe( true );
		expect( wrapper.find( '.ext-reportincident-dialog-step2__additional-details' ).exists() ).toBe( true );
	} );

	it( 'Gets correct error messages for display', () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		store.isFormValidForSubmission();

		expect( wrapper.vm.formErrorMessages ).toStrictEqual( store.formErrorMessages );
	} );

	it( 'Sets correct status values', async () => {
		const wrapper = renderComponent();
		const store = useFormStore();

		// No errors should be displayed until a user tries to submit the form
		// or focuses out of the required field.
		expect( wrapper.vm.reportedUserStatus ).toBe( 'default' );

		// Call method used to indicate that form is about to be submitted.
		store.isFormValidForSubmission();

		// Errors should be displayed when a user tries to submit the form without
		// specifying required fields.
		expect( wrapper.vm.reportedUserStatus ).toBe( 'error' );
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

	it( 'Should query allusers API on call to onReportedUserInput', async () => {
		const apiGet = mockApiGet(
			Promise.resolve(
				{ query: { allusers: [
					{ userid: 1, name: 'testing' },
					{ userid: 2, name: 'testing1' },
					{ userid: 3, name: 'testing2' }
				] } }
			)
		);
		const wrapper = renderComponent();
		const store = useFormStore();
		// Fake that a previous submit has caused the user non-existent
		// error to display.
		store.reportedUserDoesNotExist = true;
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing' );
		expect( wrapper.vm.inputReportedUser ).toBe( 'testing' );
		// Change to the inputReportedUser value should invalidate previous
		// non-existent user error returned by the server.
		expect( store.reportedUserDoesNotExist ).toBe( false );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		await waitUntilDebounceComplete();
		// The suggestions should now be set.
		expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [
			{ value: 'testing' },
			{ value: 'testing1' },
			{ value: 'testing2' }
		] );
		expect( apiGet ).toHaveBeenCalledWith( {
			action: 'query',
			list: 'allusers',
			auprefix: 'testing',
			limit: '10'
		} );
	} );

	it( 'Call to onReportedUserInput but API promise rejects', async () => {
		const rejectedPromise = Promise.reject( 'error' );
		// Catch the rejected promise in a function that does nothing to
		// allow the tests to run (otherwise they fail with an
		// ERR_UNHANDLED_REJECTION error).
		rejectedPromise.catch( () => {} );
		const apiGet = mockApiGet( rejectedPromise );
		const mwLogError = mockErrorLogger();
		const wrapper = renderComponent();
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		await waitUntilDebounceComplete();
		// The suggestions should now be set.
		expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
		// Expect that mw.log.error() was called
		expect( mwLogError ).toHaveBeenCalledWith( 'error' );
		expect( apiGet ).toHaveBeenCalledWith( {
			action: 'query',
			list: 'allusers',
			auprefix: 'testing',
			limit: '10'
		} );
	} );

	it( 'Call to onReportedUserInput but input is updated before API request finished', async () => {
		const apiGet = mockApiGet(
			Promise.resolve(
				{ query: { allusers: [
					{ userid: 1, name: 'testing' },
					{ userid: 2, name: 'testing1' },
					{ userid: 3, name: 'testing2' }
				] } }
			)
		);
		const wrapper = renderComponent();
		const store = useFormStore();
		// Update the value of inputReportedUserMenuItems so that the test can verify it empties on a failed request.
		wrapper.vm.suggestedUsernames.value = [ { name: 'test123123123123123' } ];
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testingabc' );
		// Update the value of store.inputReportedUser before the debounce timer has finished.
		store.inputReportedUser = 'testing1234';
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		await waitUntilDebounceComplete();
		// The suggestions should now be set.
		expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
		expect( apiGet ).toHaveBeenCalledWith( {
			action: 'query',
			list: 'allusers',
			auprefix: 'testingabc',
			limit: '10'
		} );
	} );

	it( 'Call to onReportedUserInput but API returns unparsable response', async () => {
		const apiGet = mockApiGet( Promise.resolve( { test: 'test' } ) );
		const wrapper = renderComponent();
		// Update the value of inputReportedUserMenuItems so that the test can verify it empties on a failed request.
		wrapper.vm.suggestedUsernames = [ { name: 'testing123123' } ];
		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing12' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		await waitUntilDebounceComplete();
		// The suggestions should now be set.
		expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [] );
		expect( apiGet ).toHaveBeenCalledWith( {
			action: 'query',
			list: 'allusers',
			auprefix: 'testing12',
			limit: '10'
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

	it( 'Call to onReportedUserInput twice within the debounce period', async () => {
		const apiGet = mockApiGet(
			Promise.resolve(
				{ query: { allusers: [
					{ userid: 1, name: 'testing123' },
					{ userid: 2, name: 'testing1234' },
					{ userid: 3, name: 'testing12345' }
				] } }
			)
		);
		const wrapper = renderComponent();

		// Call the method under test
		wrapper.vm.onReportedUserInput( 'testing12' );
		// Call the method under test again.
		wrapper.vm.onReportedUserInput( 'testing123' );
		// Wait until the debounce time has expired and add around 20ms to be sure it has run.
		await waitUntilDebounceComplete();
		// The suggestions should now be set.
		expect( wrapper.vm.inputReportedUserMenuItems ).toStrictEqual( [
			{ value: 'testing123' },
			{ value: 'testing1234' },
			{ value: 'testing12345' }
		] );
		expect( apiGet ).toHaveBeenCalledWith( {
			action: 'query',
			list: 'allusers',
			auprefix: 'testing123',
			limit: '10'
		} );
	} );
} );