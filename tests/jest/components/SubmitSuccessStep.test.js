'use strict';

jest.mock( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

const SubmitSuccessStep = require( '../../../resources/ext.reportIncident/components/SubmitSuccessStep.vue' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	utils = require( '@vue/test-utils' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	useInstrument = require( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

describe( 'SubmitSuccessStep', () => {
	function mount( formStoreState ) {
		return utils.mount( SubmitSuccessStep, {
			global: {
				plugins: [
					createTestingPinia( {
						stubActions: false,
						initialState: {
							form: formStoreState
						}
					} )
				]
			},
			props: {
				links: {
					disputeResolution: 'Project:Dispute resolution',
					askTheCommunity: 'Project:Village pump',
					localIncidentReport: 'Project:Report an incident'
				}
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

	it( 'renders for emergency flow', () => {
		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.immediateThreatPhysicalHarm
		} );

		const message = wrapper.find( '#reportincident-form > .cdx-message' );
		const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );

		expect( message.classes( 'cdx-message--success' ) ).toBe( true );
		expect( message.text() ).toBe( 'reportincident-submit-emergency-success' );
		expect( headers ).toEqual( [
			'reportincident-submit-emergency-section-important-title',
			'reportincident-submit-emergency-section-next-title'
		] );

		expect( logEvent ).toHaveBeenCalledTimes( 1 );
		expect( logEvent ).toHaveBeenCalledWith( 'view', { source: 'submitted' } );
	} );

	it( 'renders for non-emergency flow', () => {
		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior
		} );

		const message = wrapper.find( '#reportincident-form > .cdx-message' );
		const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );

		expect( message.classes( 'cdx-message--notice' ) ).toBe( true );
		expect( message.text() ).toBe( 'reportincident-submit-behavior-notice' );
		expect( headers ).toEqual( [
			'reportincident-submit-behavior-section-support-title',
			'reportincident-submit-behavior-section-other-options-title'
		] );

		expect( logEvent ).toHaveBeenCalledTimes( 1 );
		expect( logEvent ).toHaveBeenCalledWith( 'view', { source: 'get_support' } );
	} );

	it( 'records interaction event for links in non-emergency flow', async () => {
		jest.spyOn( mw, 'message' ).mockImplementation( ( key ) => ( {
			text() {
				return key;
			},
			parse() {
				if ( key === 'reportincident-submit-behavior-section-other-options-item-contact-host' ) {
					return 'Test message <a href="https://example.com">link</a>';
				}

				return key;
			}
		} ) );

		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior
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
