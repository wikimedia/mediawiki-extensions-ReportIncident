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

	const defaultNonEmergencyPageCases = [
		{
			title: 'intimidation resolution',
			config: {
				inputBehavior: Constants.harassmentTypes.INTIMIDATION
			},
			expected: {
				headers: [
					'reportincident-nonemergency-nextsteps-header',
					'reportincident-nonemergency-requesthelp-header',
					'reportincident-nonemergency-other-header'
				],
				copy: [
					'reportincident-nonemergency-intimidation-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-intimidation-nextstep-default',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'doxing resolution',
			config: {
				inputBehavior: Constants.harassmentTypes.DOXING
			},
			expected: {
				headers: [
					'reportincident-nonemergency-nextsteps-header',
					'reportincident-nonemergency-requesthelp-header',
					'reportincident-nonemergency-other-header'
				],
				copy: [
					'reportincident-nonemergency-doxing-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-doxing-nextstep-default',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'sexual harassment resolution',
			config: {
				inputBehavior: Constants.harassmentTypes.SEXUAL_HARASSMENT
			},
			expected: {
				headers: [
					'reportincident-nonemergency-nextsteps-header',
					'reportincident-nonemergency-requesthelp-header',
					'reportincident-nonemergency-other-header'
				],
				copy: [
					'reportincident-nonemergency-sexualharassment-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-sexualharassment-nextstep',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'trolling resolution',
			config: {
				inputBehavior: Constants.harassmentTypes.TROLLING
			},
			expected: {
				headers: [
					'reportincident-nonemergency-nextsteps-header',
					'reportincident-nonemergency-requesthelp-header',
					'reportincident-nonemergency-other-header'
				],
				copy: [
					'reportincident-nonemergency-trolling-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-trolling-nextstep',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'hate speech resolution',
			config: {
				inputBehavior: Constants.harassmentTypes.HATE_SPEECH
			},
			expected: {
				headers: [
					'reportincident-nonemergency-nextsteps-header',
					'reportincident-nonemergency-requesthelp-header',
					'reportincident-nonemergency-other-header'
				],
				copy: [
					'reportincident-nonemergency-hatespeech-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-hatespeech-nextstep',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		}
	];

	it.each( defaultNonEmergencyPageCases )(
		'renders the default page', ( { config, expected } ) => {
			const wrapper = mount( {
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
				inputBehavior: config.inputBehavior
			} );

			const headers = wrapper.findAll( 'h3' ).map( ( h ) => h.text() );
			expect( headers ).toEqual( expected.headers );

			const copy = wrapper.findAll( 'p, li' ).map( ( c ) => c.text() );
			expect( copy ).toEqual( expected.copy );

			expect( logEvent ).toHaveBeenCalledTimes( 1 );
			expect( logEvent ).toHaveBeenCalledWith( 'view', { source: 'get_support' } );
		}
	);

	const configuredNonEmergencyPageCases = [
		{
			title: 'intimidation resolution, configured',
			config: {
				inputBehavior: Constants.harassmentTypes.INTIMIDATION,
				get: {
					wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL: 'url',
					wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin: 'foo',
					wgReportIncidentNonEmergencyIntimidationHelpMethodEmail: 'bar',
					wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity: 'baz'
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-intimidation-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-intimidation-nextstep-configured',
					'reportincident-nonemergency-helpmethod-contactadmin',
					'reportincident-nonemergency-helpmethod-email',
					'reportincident-nonemergency-helpmethod-contactcommunity',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'doxing resolution, show notice',
			config: {
				inputBehavior: Constants.harassmentTypes.DOXING,
				get: {
					wgReportIncidentNonEmergencyDoxingShowWarning: true
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-doxing-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-doxing-notice',
					'reportincident-nonemergency-doxing-nextstep-default',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'doxing resolution, hide notice',
			config: {
				inputBehavior: Constants.harassmentTypes.DOXING,
				get: {
					wgReportIncidentNonEmergencyDoxingShowWarning: false
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-doxing-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-doxing-nextstep-default',
					'reportincident-nonemergency-helpmethod-default',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'doxing resolution, configured',
			config: {
				inputBehavior: Constants.harassmentTypes.DOXING,
				get: {
					wgReportIncidentNonEmergencyDoxingShowWarning: false,
					wgReportIncidentNonEmergencyDoxingHideEditURL: 'url',
					wgReportIncidentNonEmergencyDoxingHelpMethodWikiEmailURL: 'foo',
					wgReportIncidentNonEmergencyDoxingHelpMethodEmail: 'bar',
					wgReportIncidentNonEmergencyDoxingHelpMethodOtherURL: 'baz',
					wgReportIncidentNonEmergencyDoxingHelpMethodEmailStewards: true
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-doxing-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-doxing-nextsteps-configured',
					'reportincident-nonemergency-helpmethod-wikiemailurl',
					'reportincident-nonemergency-helpmethod-email',
					'reportincident-nonemergency-helpmethod-otherurl',
					'reportincident-nonemergency-helpmethod-emailstewards',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'sexual harassment resolution, configured',
			config: {
				inputBehavior: Constants.harassmentTypes.SEXUAL_HARASSMENT,
				get: {
					wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin: 'foo',
					wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail: 'bar',
					wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity: 'baz'
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-sexualharassment-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-sexualharassment-nextstep',
					'reportincident-nonemergency-helpmethod-contactadmin',
					'reportincident-nonemergency-helpmethod-email',
					'reportincident-nonemergency-helpmethod-contactcommunity',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'trolling resolution, configured',
			config: {
				inputBehavior: Constants.harassmentTypes.TROLLING,
				get: {
					wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin: 'foo',
					wgReportIncidentNonEmergencyTrollingHelpMethodEmail: 'bar',
					wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity: 'baz'
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-trolling-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-trolling-nextstep',
					'reportincident-nonemergency-helpmethod-contactadmin',
					'reportincident-nonemergency-helpmethod-email',
					'reportincident-nonemergency-helpmethod-contactcommunity',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		},
		{
			title: 'hate speech resolution, configured',
			config: {
				inputBehavior: Constants.harassmentTypes.HATE_SPEECH,
				get: {
					wgReportIncidentNonEmergencyHateSpeechHelpMethodContactAdmin: 'foo',
					wgReportIncidentNonEmergencyHateSpeechHelpMethodEmail: 'bar'
				}
			},
			expected: {
				copy: [
					'reportincident-nonemergency-hatespeech-header',
					'reportincident-nonemergency-generic-description',
					'reportincident-nonemergency-hatespeech-nextstep',
					'reportincident-nonemergency-helpmethod-contactadmin',
					'reportincident-nonemergency-helpmethod-email',
					'reportincident-nonemergency-generic-nextstep-otheraction'
				]
			}
		}
	];

	it.each( configuredNonEmergencyPageCases )(
		'renders the configured page', ( { config, expected } ) => {
			jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => {
				if ( key in config.get ) {
					return config.get[ key ];
				} else {
					return null;
				}
			} );

			const wrapper = mount( {
				incidentType: Constants.typeOfIncident.unacceptableUserBehavior,
				inputBehavior: config.inputBehavior
			} );
			const copy = wrapper.findAll( 'p, li' ).map( ( c ) => c.text() );
			expect( copy ).toEqual( expected.copy );
		}
	);

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
