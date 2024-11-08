const Constants = {
	DIALOG_STEP_1: 'dialog_step_1',
	DIALOG_STEP_2: 'dialog_step_2',
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
	}
};

module.exports = Constants;
