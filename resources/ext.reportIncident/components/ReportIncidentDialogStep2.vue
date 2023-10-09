<template>
	<form id="reportincident-form" class="ext-reportincident-dialog-step2">
		<!-- type of harassment -->
		<cdx-field
			:is-fieldset="true"
			:status="harassmentStatus"
			:messages="formErrorMessages.inputBehaviors"
			class="ext-reportincident-dialog-step2__harassment-options">
			<template #label>
				{{ $i18n( 'reportincident-dialog-harassment-type-label' ).text() }}
			</template>
			<cdx-checkbox
				v-for="checkbox in harassmentOptions"
				:id="'ext-reportincident-dialog-option__' + checkbox.value"
				:key="'checkbox-' + checkbox.value"
				v-model="inputBehaviors"
				:input-value="checkbox.value">
				{{ checkbox.label }}
			</cdx-checkbox>
			<cdx-text-area
				v-if="collectSomethingElseDetails"
				v-model="inputSomethingElseDetails"
				class="ext-reportincident-dialog-step2__something-else-textarea"
				@focusout="displaySomethingElseTextboxRequiredError = true"
			></cdx-text-area>
		</cdx-field>

		<!-- who is violating behavior guidelines -->
		<cdx-field
			class="ext-reportincident-dialog-step2__form-item
							ext-reportincident-dialog-step2__violator-name"
			:status="reportedUserStatus"
			:messages="formErrorMessages.inputReportedUser"
		>
			<template #label>
				{{ $i18n( 'reportincident-dialog-violator-label' ).text() }}
			</template>
			<cdx-text-input
				v-model="inputReportedUser"
				:placeholder="$i18n( 'reportincident-dialog-violator-placeholder-text' ).text()"
				@focusout="displayReportedUserRequiredError = true"
			></cdx-text-input>
		</cdx-field>

		<!-- Additional details -->
		<cdx-field
			:optional-flag="$i18n( 'reportincident-dialog-optional-label' ).text()"
			class="ext-reportincident-dialog-step2__form-item
							ext-reportincident-dialog-step2__additional-details">
			<template #label>
				{{ $i18n( 'reportincident-dialog-additional-details-input-label' ).text() }}
			</template>
			<cdx-text-area
				v-model="inputDetails"
				:placeholder="$i18n(
					'reportincident-dialog-additional-details-input-placeholder'
				).text()">
			</cdx-text-area>
		</cdx-field>
	</form>
</template>

<script>

const Constants = require( '../Constants.js' );
const useFormStore = require( '../stores/Form.js' );
const { CdxCheckbox, CdxField, CdxTextInput, CdxTextArea } = require( '@wikimedia/codex' );
const { storeToRefs } = require( 'pinia' );
const { computed } = require( 'vue' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogStep2',
	components: {
		CdxCheckbox,
		CdxField,
		CdxTextInput,
		CdxTextArea
	},
	setup() {
		const store = useFormStore();

		const {
			inputBehaviors,
			inputReportedUser,
			displayReportedUserRequiredError,
			inputSomethingElseDetails,
			displaySomethingElseTextboxRequiredError,
			inputDetails
		} = storeToRefs( store );

		const harassmentOptions = [
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-hate-speech-or-discrimination' ),
				value: Constants.harassmentTypes.HATE_SPEECH
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-sexual-harassment' ),
				value: Constants.harassmentTypes.SEXUAL_HARASSMENT
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-threats-of-violence' ),
				value: Constants.harassmentTypes.THREATS_OR_VIOLENCE
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-intimidation' ),
				value: Constants.harassmentTypes.INTIMIDATION_AGGRESSION
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-something-else' ),
				value: Constants.harassmentTypes.OTHER
			}
		];

		const harassmentStatus = computed( () => {
			return store.formErrorMessages.inputBehaviors ? 'error' : 'default';
		} );

		const reportedUserStatus = computed( () => {
			return store.formErrorMessages.inputReportedUser ? 'error' : 'default';
		} );

		const formErrorMessages = computed( () => {
			return store.formErrorMessages;
		} );

		/**
		 * Whether the "Something else" textbox value should be sent
		 * in the request body to the REST endpoint on form submission.
		 */
		const collectSomethingElseDetails = computed( () => {
			return inputBehaviors.value.filter(
				( input ) => input === Constants.harassmentTypes.OTHER
			).length > 0;
		} );

		return {
			harassmentOptions,
			inputBehaviors,
			inputReportedUser,
			displayReportedUserRequiredError,
			inputDetails,
			inputSomethingElseDetails,
			displaySomethingElseTextboxRequiredError,
			collectSomethingElseDetails,
			formErrorMessages,
			harassmentStatus,
			reportedUserStatus
		};
	}
};
</script>

<style lang="less">
@import ( reference ) '../../../resources/lib/codex-design-tokens/theme-wikimedia-ui.less';

.ext-reportincident-dialog-step2 {
	&__form-item {
		margin-top: @spacing-125;
		margin-bottom: @spacing-125;
	}
}
</style>
