'use strict';

const ReportIncidentPage = require( '../pageobjects/reportincident.page' );
const Api = require( 'wdio-mediawiki/Api' );
const UserLoginPage = require( 'wdio-mediawiki/LoginPage' );
const Util = require( 'wdio-mediawiki/Util' );
const assert = require( 'assert' );

describe( 'ReportIncident dialog', () => {
	const waitOpts = { timeout: 10000 };
	let userName;

	// Create a new user and log in with it before the test suite runs
	before( async () => {
		const userPassword = Util.getTestString();
		const bot = await Api.bot();

		userName = Util.getTestString( 'Report-incident-' );

		await Api.createAccount( bot, userName, userPassword );
		await UserLoginPage.login( userName, userPassword );
	} );

	// Log in with the newly-created user before each test
	beforeEach( async () => {
		await ReportIncidentPage.open(
			userName,
			{ withconfirmedemail: 1 }
		);
	} );

	it( 'Should open the dialog if the tools menu is used', async () => {
		// Click the "More" menu containing the "Report incident" link
		await ReportIncidentPage.actionsMenu.click();
		await ReportIncidentPage.reportLinkInToolsMenu.waitForDisplayed( waitOpts );
		// The tools link should exist, otherwise fail.
		assert( await ReportIncidentPage.reportLinkInToolsMenu.isExisting() );
		// Initially the dialog should not be open
		assert.strictEqual( await ReportIncidentPage.reportIncidentDialog.isExisting(), false );

		// Click the report link in the tools menu
		await ReportIncidentPage.reportLinkInToolsMenu.click();
		await ReportIncidentPage.reportIncidentDialog.waitForDisplayed( waitOpts );
		assert( await ReportIncidentPage.reportIncidentDialog.isExisting() );

		// The dialog should initially be on step 1.
		assert( await ReportIncidentPage.stepOneContent.isExisting() );
		assert( await ReportIncidentPage.dialogFooterNextButton.isExisting() );
		assert( await ReportIncidentPage.dialogFooterBackButton.isExisting() );
	} );
	it( 'Should be able to advance to step 2 and see a form with the expected fields', async () => {
		// Click the "More" menu containing the "Report incident" link
		await ReportIncidentPage.actionsMenu.click();
		await ReportIncidentPage.reportLinkInToolsMenu.waitForDisplayed( waitOpts );

		// Click the report link in the tools menu
		await ReportIncidentPage.reportLinkInToolsMenu.click();
		await ReportIncidentPage.reportIncidentDialog.waitForDisplayed( waitOpts );
		assert( await ReportIncidentPage.reportIncidentDialog.isExisting() );

		// Select "Unacceptable user behavior"
		await ReportIncidentPage.dialogUnacceptableBehaviorsButton.click();
		// Advance to step 2.
		await ReportIncidentPage.dialogFooterNextButton.click();
		// Step two content for types of behavior should exist, along with
		// all the form items.
		assert( await ReportIncidentPage.typesOfBehviorScreenContent.isExisting() );
		assert( await ReportIncidentPage.harassmentOptionsFormFieldset.isExisting() );
		assert( await ReportIncidentPage.hateSpeechOrDiscriminationOption.isExisting() );
		assert( await ReportIncidentPage.sexualHarassmentOption.isExisting() );
		assert( await ReportIncidentPage.threatsOrViolenceOption.isExisting() );
		assert( await ReportIncidentPage.intimidationAggressionOption.isExisting() );
		assert( await ReportIncidentPage.somethingElseOption.isExisting() );

		// A textarea for additional details should not be shown, since the
		// "Something else" radio button is not selected
		assert( !await ReportIncidentPage.somethingElseTextbox.isExisting() );

		// Selecting an option other than "Something else" should not show the
		// "Something else" textbox
		await ReportIncidentPage.intimidationAggressionOption.click();
		assert( !await ReportIncidentPage.somethingElseTextbox.isExisting() );
	} );
	it( 'Should ask for additional details only when selecting "Something else"', async () => {
		// Click the "More" menu containing the "Report incident" link
		await ReportIncidentPage.actionsMenu.click();
		await ReportIncidentPage.reportLinkInToolsMenu.waitForDisplayed( waitOpts );

		// Click the report link in the tools menu
		await ReportIncidentPage.reportLinkInToolsMenu.click();
		await ReportIncidentPage.reportIncidentDialog.waitForDisplayed( waitOpts );
		assert( await ReportIncidentPage.reportIncidentDialog.isExisting() );

		// Select "Unacceptable user behavior"
		await ReportIncidentPage.dialogUnacceptableBehaviorsButton.click();
		// Advance to step 2.
		await ReportIncidentPage.dialogFooterNextButton.click();
		// Step two content for types of behavior should exist, along with
		// all the form items.
		assert( await ReportIncidentPage.typesOfBehviorScreenContent.isExisting() );
		assert( await ReportIncidentPage.harassmentOptionsFormFieldset.isExisting() );
		assert( await ReportIncidentPage.hateSpeechOrDiscriminationOption.isExisting() );
		assert( await ReportIncidentPage.sexualHarassmentOption.isExisting() );
		assert( await ReportIncidentPage.threatsOrViolenceOption.isExisting() );
		assert( await ReportIncidentPage.intimidationAggressionOption.isExisting() );
		assert( await ReportIncidentPage.somethingElseOption.isExisting() );

		// A textarea for additional details should not be shown, since the
		// "Something else" radio button is not selected
		assert( !await ReportIncidentPage.somethingElseTextbox.isExisting() );

		// Selecting the "Something else" option should show the associated textbox
		await ReportIncidentPage.somethingElseOption.click();
		assert( await ReportIncidentPage.somethingElseTextbox.isExisting() );

		// The non-emergency flow does not collect the 'user reported' anymore
		assert( !await ReportIncidentPage.violatorFormInput.isExisting() );
	} );
	it( 'Should display form errors when submit attempted with no form data', async () => {
		// Click the "More" menu containing the "Report incident" link
		await ReportIncidentPage.actionsMenu.click();
		await ReportIncidentPage.reportLinkInToolsMenu.waitForDisplayed( waitOpts );

		// Click the report link in the tools menu
		await ReportIncidentPage.reportLinkInToolsMenu.click();
		await ReportIncidentPage.reportIncidentDialog.waitForDisplayed( waitOpts );
		assert( await ReportIncidentPage.reportIncidentDialog.isExisting() );

		// Select "Unacceptable user behavior" & advance to step 2
		await ReportIncidentPage.dialogUnacceptableBehaviorsButton.click();
		await ReportIncidentPage.dialogFooterNextButton.click();
		// Go back to step 1.
		await ReportIncidentPage.dialogFooterBackButton.click();
		// Advance to step 2.
		await ReportIncidentPage.dialogFooterNextButton.click();
		// Assert the dialog is on the list of unacceptable behaviors
		assert( await ReportIncidentPage.typesOfBehviorScreenContent.isExisting() );

		// Attempt to submit the form with no data specified.
		await ReportIncidentPage.dialogFooterNextButton.click();
		// Check that the form displays errors on the required fields.
		assert( await ReportIncidentPage.harassmentOptionsFieldsetFormErrors.isExisting() );

		// The non-emergency flow does not collect the 'user reported' anymore
		assert( !await ReportIncidentPage.violatorFormInputErrors.isExisting() );
		// Assert that the dialog still exists.
		assert( await ReportIncidentPage.reportIncidentDialog.isExisting() );
	} );
	it( 'Should be able to submit a form with valid data', async () => {
		// Click the "More" menu containing the "Report incident" link
		await ReportIncidentPage.actionsMenu.click();
		await ReportIncidentPage.reportLinkInToolsMenu.waitForDisplayed( waitOpts );

		// Click the report link in the tools menu
		await ReportIncidentPage.reportLinkInToolsMenu.click();
		await ReportIncidentPage.reportIncidentDialog.waitForDisplayed( waitOpts );

		// Select "Unacceptable user behavior" & advance to step 2
		await ReportIncidentPage.dialogUnacceptableBehaviorsButton.click();
		await ReportIncidentPage.dialogFooterNextButton.click();
		// Assert the dialog is on the list of unacceptable behaviors
		assert( await ReportIncidentPage.typesOfBehviorScreenContent.isExisting() );

		// Fill out the form
		// Check hate speech from the harassment options, then switch to something else
		await ReportIncidentPage.hateSpeechOrDiscriminationOption.click();
		await ReportIncidentPage.somethingElseOption.click();

		// Something else textbox should appear once something else is checked.
		assert( await ReportIncidentPage.somethingElseTextbox.isExisting() );
		// Add something to the something else textbox
		await ReportIncidentPage.somethingElseTextbox.setValue( 'Testing1234' );

		// We don't collect the 'user reported' as the non-emergency flow
		// is not for actionable reports
		assert( !await ReportIncidentPage.violatorFormInput.isExisting() );

		// Listen for the API request
		await browser.setupInterceptor();

		// Go to the "Get support" screen, which also triggers the API request
		await ReportIncidentPage.dialogFooterNextButton.click();

		// Attempt to submit the form
		await ReportIncidentPage.dialogFooterNextButton.click();

		// Wait until the request is started before verifying the request
		// body, url and method.
		await browser.waitUntil(
			async () => ( await browser.getRequests( { includePending: true } ) ).length !== 0,
			{ timeout: 2 * 1000, timeoutMsg: 'API was not called in a reasonable time.' }
		);

		// Get the REST API request information (which will be the request at index 0
		// as no other requests are made in the interim).
		const request = await browser.getRequest( 0, { includePending: true } );

		// Assert that the URL in the request goes to the REST API for submitting report data
		const MW_SCRIPT_PATH = ( 'MW_SCRIPT_PATH' in process.env ? process.env.MW_SCRIPT_PATH : '' );
		const baseUri = ( MW_SCRIPT_PATH.endsWith( '/' ) ? MW_SCRIPT_PATH : `${ MW_SCRIPT_PATH }/` );
		assert.strictEqual( request.url, `${ baseUri }rest.php/reportincident/v0/report` );

		// Assert that the request method is POST.
		assert.strictEqual( request.method, 'POST' );
		const requestBody = request.body;
		// Remove the token from our verification as it will be different each time.
		delete requestBody.token;
		// Assert that the request body matches the data entered in the form
		assert.deepStrictEqual(
			request.body,
			{
				reportedUser: '',
				incidentType: 'unacceptable-user-behavior',
				behaviorType: 'something-else',
				somethingElseDetails: 'Testing1234',
				revisionId: 0
			}
		);
	} );
} );
