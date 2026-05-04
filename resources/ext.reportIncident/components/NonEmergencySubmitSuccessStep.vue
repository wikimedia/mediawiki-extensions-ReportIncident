<template>
	<div v-if="page" ref="pageContent">
		<cdx-message v-if="isTestReport && !isDirectReportingCategory" type="warning">
			{{ $i18n( 'reportincident-e2e-tester-notice' ).text() }}
		</cdx-message>
		<cdx-message :icon="icons.cdxIconUserGroup">
			<p v-if="page.description.header">
				<strong>{{ page.description.header }}</strong>
			</p>
			<p>{{ page.description.text }}</p>
		</cdx-message>
		<cdx-message v-if="page.notice && page.notice.shouldDisplay()" type="warning">
			<p>{{ page.notice.text }}</p>
		</cdx-message>
		<h3 class="ext-reportincident-dialog__submit-success-section-header">
			{{ $i18n( 'reportincident-nonemergency-nextsteps-header' ).text() }}
		</h3>
		<!-- eslint-disable vue/no-v-html-->
		<p
			v-for="( paragraph, key ) in nextStepMsg"
			:key="key"
			v-html="paragraph"
		></p>
		<template v-if="isDirectReportingCategory">
			<p>
				{{ $i18n( 'reportincident-nonemergency-directreport-nextsteps' ).text() }}
			</p>
			<form id="directreporting-form" class="ext-reportincident-dialog-nonemergency">
				<cdx-field
					class="ext-reportincident-dialog-nonemergency__direct-report"
					:messages="shouldShowDirectReportFormValidationError ? formErrorMessages : {}"
					:status="directReportFormStatus"
				>
					<template #label>
						{{ $i18n( 'reportincident-nonemergency-directreport-reportlabel' ).text() }}
					</template>
					<character-limited-text-area
						v-model:text-content="directReportTextInput"
						:code-point-limit="directReportCharacterLimit"
						class="ext-reportincident-dialog--nonemergency__direct-reporting-textarea"
						:placeholder="$i18n(
							'reportincident-dialog-additional-details-input-placeholder'
						).text()"
					>
					</character-limited-text-area>
				</cdx-field>
			</form>
		</template>
		<template v-else>
			<!-- eslint-enable vue/no-v-html-->
			<h3 class="ext-reportincident-dialog__submit-success-section-header">
				{{ $i18n( 'reportincident-nonemergency-requesthelp-header' ).text() }}
			</h3>
			<ul>
				<!-- eslint-disable vue/no-v-html-->
				<li
					v-for="( helpMethod, key ) in validNonEmergencyHelpMethods"
					:key="key"
					v-html="helpMethod"
				></li>
				<!-- eslint-enable vue/no-v-html-->
			</ul>
		</template>
		<h3 class="ext-reportincident-dialog__submit-success-section-header">
			{{ $i18n( 'reportincident-nonemergency-other-header' ).text() }}
		</h3>
		<p v-i18n-html:reportincident-nonemergency-generic-nextstep-otheraction>
		</p>
	</div>
</template>

<script>

const { computed, onMounted, ref } = require( 'vue' );
const { storeToRefs } = require( 'pinia' );
const useFormStore = require( '../stores/Form.js' );
const Constants = require( '../Constants.js' );
const { CdxField, CdxMessage } = require( '@wikimedia/codex' );
const CharacterLimitedTextArea = require( './CharacterLimitedTextArea.vue' );
const useInstrument = require( '../composables/useInstrument.js' );
const icons = require( '../components/icons.json' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogNonEmergencySupport',
	components: { CdxField, CdxMessage, CharacterLimitedTextArea },
	setup() {
		const logEvent = useInstrument();
		const pageContent = ref( null );
		const store = useFormStore();
		const behavior = store.inputBehavior;
		const directReportCharacterLimit = Constants.detailsCodepointLimit;
		const {
			isDirectReportingCategory,
			validNonEmergencyHelpMethods,
			directReportTextInput,
			shouldShowDirectReportFormValidationError,
			isDirectReportFormValid
		} = storeToRefs( store );

		onMounted( () => {
			logEvent( 'view', { source: `get_support_${ behavior }` } );

			$( pageContent.value ).find( 'a' ).on( 'click', function () {
				logEvent( 'click', {
					context: $( this ).attr( 'href' ),
					source: `get_support_${ behavior }`
				} );
			} );
		} );

		const page = Object.values( Constants.harassmentTypesV2 )
			.find( ( obj ) => obj.id === behavior );

		// A message is considered valid if the requiredParams are found via mw.config.get()
		// If no requiredParams are found, the message is considered valid by default.
		function getValidMsgParams( msg ) {
			// No required params, message is automatically considered valid
			if ( !msg.requiredParams ) {
				return [];
			}

			// Check if all required parameters exist. No other validation is done here.
			// If all required parameters exist, message is considered valid.
			const msgParams = [];
			for ( const param of msg.requiredParams ) {
				const paramValue = mw.config.get( param );
				if ( !paramValue ) {
					// Found an invalid value, break as the message is considered invalid now
					break;
				}
				msgParams.push( paramValue );
			}

			// If all required parameters weren't found, message is invalid
			if ( msgParams.length !== msg.requiredParams.length ) {
				return false;
			}
			return msgParams;
		}

		// Cycle through all the next step messages, returning the first valid one
		let nextStepMsg = '';
		for ( const msg of page.nextSteps ) {
			const msgParams = getValidMsgParams( msg );
			if ( msgParams !== false ) {
				// * reportincident-nonemergency-intimidation-nextstep-configured
				// * reportincident-nonemergency-intimidation-nextstep-default
				nextStepMsg = mw.message( msg.msgKey, msgParams ).parse();
				break;
			}
		}
		nextStepMsg = nextStepMsg.split( '\n\n' );

		// TODO: As the message this supports can now affect multiple steps, migrate
		// the check and display to the parent component which should also fix a longstanding
		// bug where emergency reports were also ignored but the notice saying so didn't.
		const testers = mw.config.get( 'wgReportIncidentE2ETesterUsers' ) || [];
		const isTestReport = testers.includes( mw.user.getName() );

		const directReportFormStatus = computed( () => {
			if ( !shouldShowDirectReportFormValidationError.value ) {
				return 'default';
			}

			return isDirectReportFormValid.value ? 'default' : 'error';
		} );
		const formErrorMessages = computed( () => isDirectReportFormValid.value ? {} :
			{ error: mw.msg( 'reportincident-nonemergency-directreport-inputempty-error' ) }
		);

		return {
			page,
			pageContent,
			nextStepMsg,
			validNonEmergencyHelpMethods,
			isDirectReportingCategory,
			directReportFormStatus,
			shouldShowDirectReportFormValidationError,
			directReportCharacterLimit,
			directReportTextInput,
			icons,
			isTestReport,
			formErrorMessages
		};
	}
};
</script>

<style lang="less">
.ext-reportincident-dialog__submit-success-section-header {
	font-size: 100%;
	padding: 0;
	margin: 1em 0 0 0;
}
</style>
