const Constants = {
	DIALOG_STEP_1: 'dialog_step_1',
	DIALOG_STEP_REPORT_BEHAVIOR_TYPES: 'dialog_step_report_unacceptable_behavior',
	DIALOG_STEP_REPORT_IMMEDIATE_HARM: 'dialog_step_report_immediate_harm',
	DIALOG_STEP_EMERGENCY_SUBMIT_SUCCESS: 'dialog_step_emergency_submit_success',
	DIALOG_STEP_NONEMERGENCY_SUBMIT_SUCCESS: 'dialog_step_nonemergency_submit_success',
	DIALOG_STEP_NONEMERGENCY_SUBMIT_SUCCESS_V2: 'dialog_step_nonemergency_submit_success_v2',

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
		OTHER: 'something-else'
	},
	// Pages implemented by the v2 non-emergency workflow. This is used to check if the
	// submit success page should use NonEmergencySubmitSuccessStepv2 or fall back to
	// NonEmergencySubmitSuccessStep and should be deprecated when all wikis have been
	// off-boarded from the legacy version.
	v2NonEmergencySubmitSuccessPages: [ 'intimidation', 'doxing' ],
	/**
	 * The number of Unicode codepoints accepted by textareas holding report details.
	 */
	detailsCodepointLimit: 1000
};

module.exports = Constants;
