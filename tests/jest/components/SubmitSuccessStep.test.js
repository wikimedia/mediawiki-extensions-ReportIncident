'use strict';

const SubmitSuccessStep = require( '../../../resources/ext.reportIncident/components/SubmitSuccessStep.vue' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	utils = require( '@vue/test-utils' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' );

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

	beforeEach( () => {
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

	it( 'renders for emergency flow', () => {
		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.immediateThreatPhysicalHarm
		} );

		const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );

		expect( headers ).toEqual( [
			'reportincident-submit-emergency-section-important-title',
			'reportincident-submit-emergency-section-next-title'
		] );
	} );

	it( 'renders for non-emergency flow', () => {
		const wrapper = mount( {
			incidentType: Constants.typeOfIncident.unacceptableUserBehavior
		} );

		const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );

		expect( headers ).toEqual( [
			'reportincident-submit-behavior-section-support-title',
			'reportincident-submit-behavior-section-other-options-title'
		] );
	} );
} );
