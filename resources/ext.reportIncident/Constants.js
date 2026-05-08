const Constants = {
	DIALOG_STEP_1: 'dialog_step_1',
	DIALOG_STEP_REPORT_BEHAVIOR_TYPES: 'dialog_step_report_unacceptable_behavior',
	DIALOG_STEP_REPORT_IMMEDIATE_HARM: 'dialog_step_report_immediate_harm',
	DIALOG_STEP_EMERGENCY_SUBMIT_SUCCESS: 'dialog_step_emergency_submit_success',
	DIALOG_STEP_NONEMERGENCY_SUBMIT_SUCCESS: 'dialog_step_nonemergency_submit_success',

	typeOfIncident: {
		unacceptableUserBehavior: 'unacceptable-user-behavior',
		immediateThreatPhysicalHarm: 'immediate-threat-physical-harm'
	},
	physicalHarmTypes: {
		physicalHarm: 'threats-physical-harm',
		selfHarm: 'threats-self-harm',
		publicHarm: 'threats-public-harm'
	},
	harassmentTypes: {
		DOXING: 'doxing',
		HATE_SPEECH: 'hate-or-discrimination',
		INTIMIDATION: 'intimidation',
		SEXUAL_HARASSMENT: 'sexual-harassment',
		SPAM: 'spam',
		TROLLING: 'trolling',
		SOMETHING_ELSE: 'something-else',
		SOCKPUPPETRY: 'sockpuppetry',
		VANDALISM: 'vandalism',
		USER_DISPUTE: 'userdispute',
		DISRUPTIVE_EDITING: 'disruptiveediting',
		OTHER: 'other'
	},
	// Pages that describe next steps for the non-emergency incident. The type
	// should match up with what's defined in Constants.harassmentTypes.
	// A page is described by an object with the following keys:
	// id: the computer-friendly id; used in instrumentation, etc.
	// labelKey: the message key for the header of the next steps page
	// description: an object that summarizes the page; pass through the header (parsed
	// message) and text (parsed message)
	// notice (optional): an object that describes a notice displayed in a warning notice box;
	// pass through the text (parsed message) and a boolean determining if the box should be
	// displayed at all
	// nextSteps: an array that describes messages to display in descending priority. When
	// processed, it'll return the first valid message.
	// helpMethods: an array that describes the possible contact methods. When processed, it'll
	// return all valid messages.
	// helpMethodDefault: If no helpMethods are valid, this message will be returned
	harassmentTypesV2: {
		DOXING: {
			id: 'doxing',
			labelKey: 'reportincident-dialog-harassment-type-doxing',
			description: {
				header: mw.msg( 'reportincident-nonemergency-doxing-header' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			notice: {
				text: mw.msg( 'reportincident-nonemergency-doxing-notice' ),
				shouldDisplay: () => mw.config.get( 'wgReportIncidentNonEmergencyDoxingShowWarning' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-doxing-nextsteps-configured',
					requiredParams: [ 'wgReportIncidentNonEmergencyDoxingHideEditURL' ]
				},
				{
					msgKey: 'reportincident-nonemergency-doxing-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-wikiemailurl',
					requiredParams: [ 'wgReportIncidentNonEmergencyDoxingHelpMethodWikiEmailURL' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyDoxingHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-otherurl',
					requiredParams: [ 'wgReportIncidentNonEmergencyDoxingHelpMethodOtherURL' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-emailstewards',
					requiredParams: [ 'wgReportIncidentNonEmergencyDoxingHelpMethodEmailStewards' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		HATE_SPEECH: {
			id: 'hate-or-discrimination',
			labelKey: 'reportincident-dialog-harassment-type-hate-speech-or-discrimination',
			description: {
				header: mw.msg( 'reportincident-nonemergency-hatespeech-header' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-hatespeech-nextstep'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyHateSpeechHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyHateSpeechHelpMethodEmail' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		INTIMIDATION: {
			id: 'intimidation',
			labelKey: 'reportincident-dialog-harassment-type-intimidation',
			description: {
				header: mw.msg( 'reportincident-nonemergency-intimidation-header' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-intimidation-nextstep-configured',
					requiredParams: [ 'wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL' ]
				},
				{
					msgKey: 'reportincident-nonemergency-intimidation-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyIntimidationHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		SEXUAL_HARASSMENT: {
			id: 'sexual-harassment',
			labelKey: 'reportincident-dialog-harassment-type-sexual-harassment',
			description: {
				header: mw.msg( 'reportincident-nonemergency-sexualharassment-header' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-sexualharassment-nextstep'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		SPAM: {
			id: 'spam',
			labelKey: 'reportincident-dialog-harassment-type-spam',
			description: {
				header: mw.msg( 'reportincident-nonemergency-spam-header' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-spam-nextsteps-configured',
					requiredParams: [ 'wgReportIncidentNonEmergencySpamSpamContentURL' ]
				},
				{
					msgKey: 'reportincident-nonemergency-spam-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencySpamHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencySpamHelpMethodEmail' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		TROLLING: {
			id: 'trolling',
			labelKey: 'reportincident-dialog-harassment-type-trolling',
			description: {
				header: mw.msg( 'reportincident-nonemergency-trolling-header' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-trolling-nextstep'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyTrollingHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		SOMETHING_ELSE: {
			id: 'something-else',
			labelKey: 'reportincident-dialog-harassment-type-something-else',
			description: {
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-somethingelse-nextstep-configured',
					requiredParams: [ 'wgReportIncidentNonEmergencyOtherDisputeResolutionURL' ]
				},
				{
					msgKey: 'reportincident-nonemergency-somethingelse-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencySomethingElseHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencySomethingElseHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencySomethingElseHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		SOCKPUPPETRY: {
			id: 'sockpuppetry',
			labelKey: 'reportincident-dialog-harassment-type-sockpuppetry',
			description: {
				header: mw.msg( 'reportincident-dialog-harassment-type-sockpuppetry' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-sockpuppetry-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencySockpuppetryHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencySockpuppetryHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencySockpuppetryHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		VANDALISM: {
			id: 'vandalism',
			labelKey: 'reportincident-dialog-harassment-type-vandalism',
			description: {
				header: mw.msg( 'reportincident-dialog-harassment-type-vandalism' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-vandalism-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyVandalismHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyVandalismHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencyVandalismHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		USER_DISPUTE: {
			id: 'userdispute',
			labelKey: 'reportincident-dialog-harassment-type-userdispute',
			description: {
				header: mw.msg( 'reportincident-dialog-harassment-type-userdispute' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-userdispute-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyUserDisputeHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyUserDisputeHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencyUserDisputeHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		DISRUPTIVE_EDITING: {
			id: 'disruptiveediting',
			labelKey: 'reportincident-dialog-harassment-type-disruptiveediting',
			description: {
				header: mw.msg( 'reportincident-dialog-harassment-type-disruptiveediting' ),
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-disruptiveediting-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyDisruptiveEditingHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyDisruptiveEditingHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencyDisruptiveEditingHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		},
		OTHER: {
			id: 'other',
			labelKey: 'reportincident-dialog-harassment-type-other',
			description: {
				text: mw.msg( 'reportincident-nonemergency-generic-description' )
			},
			notice: {
				text: mw.msg( 'reportincident-nonemergency-other-notice' ),
				shouldDisplay: () => true
			},
			nextSteps: [
				{
					msgKey: 'reportincident-nonemergency-other-nextstep-default'
				}
			],
			helpMethods: [
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactadmin',
					requiredParams: [ 'wgReportIncidentNonEmergencyOtherHelpMethodContactAdmin' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-email',
					requiredParams: [ 'wgReportIncidentNonEmergencyOtherHelpMethodEmail' ]
				},
				{
					msgKey: 'reportincident-nonemergency-helpmethod-contactcommunity',
					requiredParams: [ 'wgReportIncidentNonEmergencyOtherHelpMethodContactCommunity' ]
				}
			],
			helpMethodDefault: {
				msgKey: 'reportincident-nonemergency-helpmethod-default'
			}
		}
	},
	/**
	 * The number of Unicode codepoints accepted by textareas holding report details.
	 */
	detailsCodepointLimit: 1000,
	// When selecting "something else" in the non-emergency workflow, the user is prompted
	// to add additional context which is then recorded via instrumentation, which limits
	// the character length possible. Set this limit here.
	somethingElseDetailsCodepointLimit: 200
};

module.exports = Constants;
