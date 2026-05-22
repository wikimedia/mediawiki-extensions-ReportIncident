'use strict';

jest.mock( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

const NonEmergencyPostSubmitSuccessStep = require( '../../../resources/ext.reportIncident/components/NonEmergencyPostSubmitSuccessStep.vue' ),
	{ createTestingPinia } = require( '@pinia/testing' ),
	utils = require( '@vue/test-utils' ),
	Constants = require( '../../../resources/ext.reportIncident/Constants.js' ),
	useInstrument = require( '../../../resources/ext.reportIncident/composables/useInstrument.js' );

describe( 'NonEmergencyPostSubmitSuccessStep', () => {
	function mount( formStoreState ) {
		return utils.mount( NonEmergencyPostSubmitSuccessStep, {
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

	it( 'renders for non emergency direct report', () => {
		const wrapper = mount( {
			behaviorType: Constants.harassmentTypes.DOXING
		} );
		const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );
		expect( headers ).toEqual( [
			'reportincident-nonemergency-directreport-submitsuccess-next-header'
		] );
		expect( logEvent ).toHaveBeenCalledTimes( 1 );
		expect( logEvent ).toHaveBeenCalledWith( 'view', { source: 'direct_reporting_confirmation' } );
	} );
} );
