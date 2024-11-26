<template>
	<form id="reportincident-form" class="ext-reportincident-dialog-types-of-behavior">
		<cdx-message>
			<!-- eslint-disable vue/no-v-html -->
			<span
				v-html="$i18n(
					'reportincident-dialog-unacceptable-behavior-community-managed'
				).parse()"
			></span>
		</cdx-message>

		<!-- type of unacceptable behavior -->
		<cdx-field
			:is-fieldset="true"
			:status="harassmentStatus"
			:messages="formErrorMessages.inputBehaviors"
			class="ext-reportincident-dialog-types-of-behavior__harassment-options">
			<template #label>
				{{ $i18n( 'reportincident-dialog-harassment-type-label' ).text() }}
			</template>
			<cdx-radio
				v-for="radio in harassmentOptions"
				:key="'radio-' + radio.value"
				v-model="inputBehavior"
				name="reportincident-behavior-type-radio-group-field"
				:input-value="radio.value"
				@change="onRadioButtonOptionChanged( $event )"
			>
				{{ radio.label }}
			</cdx-radio>

			<character-limited-text-area
				v-if="collectSomethingElseDetails"
				v-model:text-content="inputSomethingElseDetails"
				v-model:remaining-characters="somethingElseDetailsCharacterCountLeft"
				:code-point-limit="somethingElseDetailsCodepointLimit"
				class="ext-reportincident-dialog-types-of-behavior__something-else-textarea"
				:placeholder="$i18n(
					'reportincident-dialog-additional-details-input-placeholder'
				).text()"
			>
			</character-limited-text-area>
			<template v-if="showSomethingElseDetailsCharacterCountLeft">
				{{ somethingElseDetailsCharacterCountLeft }} characters left.
			</template>
			<!-- eslint-disable vue/no-v-html -->
			<span
				class="ext-reportincident-dialog-types-of-behavior-footer"
				v-html="$i18n(
					'reportincident-dialog-record-for-statistical-purposes'
				).parse()"
			></span>
		</cdx-field>
	</form>
</template>

<script>

const useFormStore = require( '../stores/Form.js' );
const useInstrument = require( '../composables/useInstrument.js' );
const { storeToRefs } = require( 'pinia' );
const { computed, onMounted, ref } = require( 'vue' );
const { CdxField, CdxMessage, CdxRadio } = require( '@wikimedia/codex' );
const CharacterLimitedTextArea = require( './CharacterLimitedTextArea.vue' );
const Constants = require( '../Constants.js' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogTypesOfBehavior',
	components: {
		CdxField,
		CdxMessage,
		CdxRadio,
		CharacterLimitedTextArea
	},

	setup() {
		const store = useFormStore();
		const logEvent = useInstrument();

		const somethingElseDetailsCodepointLimit = Constants.detailsCodepointLimit;
		const somethingElseDetailsCharacterCountLeft = ref( '' );

		onMounted( () => logEvent( 'view', { source: 'describe_unacceptable_behavior' } ) );

		const {
			inputBehavior,
			inputSomethingElseDetails,
			showValidationError
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

		/**
		 * Whether the "Something else" textbox value should be sent
		 * in the request body to the REST endpoint on form submission.
		 */
		const collectSomethingElseDetails =
				computed( () => inputBehavior.value === Constants.harassmentTypes.OTHER );

		const formErrorMessages = computed( () => store.formErrorMessages );

		const showSomethingElseDetailsCharacterCountLeft =
				computed( () => somethingElseDetailsCharacterCountLeft.value !== '' && collectSomethingElseDetails.value );

		const harassmentStatus = computed( () => store.formErrorMessages.inputBehaviors ? 'error' : 'default' );

		/**
		 * Callback when the radio button selection changes for the behavior type.
		 */
		function onRadioButtonOptionChanged() {
			showValidationError.value = false;

			logEvent( 'click', {
				context: inputBehavior.value,
				source: 'describe_unacceptable_behavior'
			} );
		}

		return {
			harassmentOptions,
			inputBehavior,
			inputSomethingElseDetails,
			formErrorMessages,
			somethingElseDetailsCodepointLimit,
			somethingElseDetailsCharacterCountLeft,
			showSomethingElseDetailsCharacterCountLeft,
			harassmentStatus,
			collectSomethingElseDetails,
			onRadioButtonOptionChanged
		};
	}
};
</script>

<style lang="less">
@import ( reference ) 'mediawiki.skin.variables.less';

.ext-reportincident-dialog-types-of-behavior {
	line-height: @line-height-medium;

	&__text-block {
		margin-top: @spacing-125;
	}
}

.ext-reportincident-dialog-types-of-behavior-footer {
	font-size: @font-size-small;
	line-height: @color-base--subtle;
	margin-top: @spacing-125;
	display: block;
}
</style>
