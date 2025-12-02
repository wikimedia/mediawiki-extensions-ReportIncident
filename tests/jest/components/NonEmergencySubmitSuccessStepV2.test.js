'use strict';

jest.mock( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

const NonEmergencySubmitSuccessStepV2 = require( '../../../resources/ext.reportIncident/components/NonEmergencySubmitSuccessStepV2.vue' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	utils = require( '@vue/test-utils' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	useInstrument = require( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

describe( 'NonEmergencySubmitSuccessStepV2', () => {
	function mount( formStoreState ) {
		return utils.mount( NonEmergencySubmitSuccessStepV2, {
			global: {
				plugins: [
					createTestingPinia( {
						stubActions: false,
						initialState: {
							form: formStoreState
						}
					} )
				]
			}
		} );
	}

	let logEvent;

	beforeEach( () => {
		jest.spyOn( mw, 'message' ).mockImplementation( ( key ) => ( {
			text() {
				return key;
			},
			parse() {
				return key;
			}
		} ) );

		logEvent = jest.fn();
		useInstrument.mockImplementation( () => logEvent );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'renders the default page for intimidation non-emergency resolution', () => {
		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
			inputBehavior: 'intimidation'
		} );

		const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );
		expect( headers ).toEqual( [
			'reportincident-nonemergency-nextsteps-header',
			'reportincident-nonemergency-requesthelp-header',
			'reportincident-nonemergency-other-header'
		] );

		const copy = wrapper.findAll( 'p, li' ).map( ( c ) => c.text() );
		expect( copy ).toEqual( [
			'reportincident-nonemergency-intimidation-header',
			'reportincident-nonemergency-generic-description',
			'reportincident-nonemergency-intimidation-nextstep-default',
			'reportincident-nonemergency-helpmethod-default',
			'reportincident-nonemergency-generic-nextstep-otheraction'
		] );

		expect( logEvent ).toHaveBeenCalledTimes( 1 );
		expect( logEvent ).toHaveBeenCalledWith( 'view', { source: 'get_support' } );
	} );

	it( 'renders the configured page for intimidation non-emergency resolution', () => {
		jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => {
			switch ( key ) {
				case 'wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL':
					return 'url';
				case 'wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin':
					return 'foo';
				case 'wgReportIncidentNonEmergencyIntimidationHelpMethodEmail':
					return 'bar';
				case 'wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity':
					return 'baz';
				default:
					throw new Error( 'Unknown key: ' + key );
			}
		} );

		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
			inputBehavior: Constants.harassmentTypes.INTIMIDATION
		} );
		const copy = wrapper.findAll( 'p, li' ).map( ( c ) => c.text() );
		expect( copy ).toEqual( [
			'reportincident-nonemergency-intimidation-header',
			'reportincident-nonemergency-generic-description',
			'reportincident-nonemergency-intimidation-nextstep-configured',
			'reportincident-nonemergency-helpmethod-contactadmin',
			'reportincident-nonemergency-helpmethod-email',
			'reportincident-nonemergency-helpmethod-contactcommunity',
			'reportincident-nonemergency-generic-nextstep-otheraction'
		] );
	} );

	it( 'records interaction event for links in non-emergency flow', async () => {
		// Manually mount instead of using mount() in order to overwrite global.$i18n
		const wrapper = utils.mount( NonEmergencySubmitSuccessStepV2, {
			global: {
				mocks: {
					$i18n: ( str ) => ( {
						text: () => str,
						parse: () => {
							if ( str === 'reportincident-nonemergency-generic-nextstep-otheraction' ) {
								return 'Test message <a href="https://example.com">link</a>';
							}
							return str;
						}
					} )
				},
				plugins: [
					createTestingPinia( {
						stubActions: false,
						initialState: {
							form: {
								incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
								inputBehavior: Constants.harassmentTypes.INTIMIDATION
							}
						}
					} )
				]
			}
		} );

		await wrapper.find( 'a' ).trigger( 'click' );

		expect( logEvent ).toHaveBeenCalledTimes( 2 );
		expect( logEvent ).toHaveBeenNthCalledWith( 1, 'view', { source: 'get_support' } );
		expect( logEvent ).toHaveBeenNthCalledWith( 2, 'click', {
			context: 'https://example.com',
			source: 'get_support'
		} );
	} );
} );
