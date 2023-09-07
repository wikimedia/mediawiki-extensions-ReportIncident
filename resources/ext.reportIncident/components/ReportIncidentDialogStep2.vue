<template>
	<div class="ext-reportincident-dialog-step2">
		<!-- type of harassment -->
		<div class="ext-reportincident-dialog-step2__harassment-options">
			<cdx-field :is-fieldset="true">
				<template #label>
					{{ $i18n( 'reportincident-dialog-harassment-type-label' ).text() }}
				</template>
				<cdx-checkbox
					v-for="checkbox in harassmentOptions"
					:id="'ext-reportincident-dialog-option__' + checkbox.value"
					:key="'checkbox-' + checkbox.value"
					v-model="inputHarassments"
					:input-value="checkbox.value"
				>
					{{ checkbox.label }}
				</cdx-checkbox>
			</cdx-field>
		</div>

		<cdx-text-area
			v-if="collectSomethingElseDetails"
			v-model="inputSomethingElseDetails"
			class="ext-reportincident-dialog-step2__something-else-details
			ext-reportincident-dialog-step2__form-item"
		></cdx-text-area>

		<!-- who is violating behavior guidelines -->
		<div
			class="ext-reportincident-dialog-step2__form-item
			ext-reportincident-dialog-step2__violator-name"
		>
			<span class="ext-reportincident-dialog__text-label">
				{{ $i18n( 'reportincident-dialog-violator-label' ).text() }}
			</span>
			<cdx-text-input
				v-model="inputViolator"
				:placeholder="$i18n( 'reportincident-dialog-violator-placeholder-text' )
					.text()"
			>
			</cdx-text-input>
		</div>

		<!-- links -->
		<div
			class="ext-reportincident-dialog-step2__form-item
			ext-reportincident-dialog-step2__evidence-links"
		>
			<span class="ext-reportincident-dialog__text-label">
				{{ $i18n( 'reportincident-dialog-links-evidence-label' ).text() }}
			</span>
			<cdx-text-input
				v-model="inputEvidence"
				:placeholder="$i18n( 'reportincident-dialog-links-evidence-placeholder' ).text()"
			>
			</cdx-text-input>
		</div>

		<!-- Additional details -->
		<div
			class="ext-reportincident-dialog-step2__form-item
			ext-reportincident-dialog-step2__additional-details"
		>
			<span class="ext-reportincident-dialog__text-label">
				{{ $i18n( 'reportincident-dialog-additional-details-input-label' ).text() }}
			</span>
			<span class="ext-reportincident-dialog__text-subtext">
				{{ optionalLabel }}
			</span>
			<cdx-text-area
				v-model="inputDetails"
				:placeholder="$i18n( 'reportincident-dialog-additional-details-input-placeholder' )
					.text()">
			</cdx-text-area>
		</div>

		<!-- Email -->
		<div
			class="ext-reportincident-dialog-step2__form-item
			ext-reportincident-dialog-step2__reporter-email"
		>
			<span class="ext-reportincident-dialog__text-label">
				{{ $i18n( 'reportincident-dialog-email-input-label' ).text() }}
			</span>
			<cdx-text-input
				v-model="inputEmail"
				:placeholder="$i18n( 'reportincident-dialog-email-placeholder-text' ).text()"
			>
			</cdx-text-input>
			<span class="ext-reportincident-dialog__text-subtext">
				{{ $i18n( 'reportincident-dialog-email-helper-text' ).text() }}
			</span>
		</div>
	</div>
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
			inputHarassments,
			inputViolator,
			inputEvidence,
			inputSomethingElseDetails,
			inputDetails,
			inputEmail
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

		const optionalLabel = ' (' + mw.msg( 'reportincident-dialog-optional-label' ) + ')';

		const collectSomethingElseDetails = computed( () => {
			return inputHarassments.value.filter(
				( input ) => input === Constants.harassmentTypes.OTHER
			).length > 0;
		} );

		return {
			harassmentOptions,
			inputHarassments,
			inputViolator,
			inputEvidence,
			inputDetails,
			inputSomethingElseDetails,
			inputEmail,
			optionalLabel,
			collectSomethingElseDetails
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

	.ext-reportincident-dialog__text-label {
		margin-bottom: @spacing-100;
	}
}
</style>
