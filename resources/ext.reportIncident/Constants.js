const Constants = {
	DIALOG_STEP_1: 'dialog_step_1',
	DIALOG_STEP_2: 'dialog_step_2',
	DIALOG_STEP_REPORT_IMMEDIATE_HARM: 'dialog_step_report_immediate_harm',
	DIALOG_STEP_SUBMIT_SUCCESS: 'dialog_step_submit_success',
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
		HATE_SPEECH: 'hate-speech-or-discrimination',
		SEXUAL_HARASSMENT: 'sexual-harassment',
		THREATS_OR_VIOLENCE: 'threats-or-violence',
		INTIMIDATION_AGGRESSION: 'intimidation-aggression',
		OTHER: 'something-else'
	},
	/**
	 * The number of Unicode codepoints accepted by textareas holding report details.
	 */
	detailsCodepointLimit: 600
};

module.exports = Constants;
