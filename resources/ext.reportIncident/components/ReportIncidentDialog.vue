<template>
	<cdx-dialog
		v-model:open="wrappedOpen"
		:title="title"
		:close-button-label="$i18n( 'reportincident-dialog-close-btn' ).text()"
		class="ext-reportincident-dialog"
		@update:open="onDialogOpenStateChanged"
	>
		<!-- dialog content-->
		<div class="ext-reportincident-dialog__content">
			<slot :name="currentSlotName"></slot>
		</div>

		<!-- dialog footer -->
		<template #footer>
			<cdx-message
				v-if="showFooterHelpText"
				:icon="footerIconName"
				:inline="true"
				class="ext-reportincident-dialog__footer_help">
				<!-- eslint-disable-next-line vue/no-v-html -->
				<span v-html="footerHelpTextMessageHtml"></span>
			</cdx-message>
			<cdx-message
				v-if="showFooterErrorText"
				type="error"
				inline
				class="ext-reportincident-dialog__form-error-text">
				{{ footerErrorMessage }}
			</cdx-message>
			<div class="ext-reportincident-dialog-footer">
				<cdx-button
					v-if="showCancelOrBackButton"
					class="ext-reportincident-dialog-footer__back-btn"
					:disabled="formSubmissionInProgress || null"
					@click="navigatePrevious"
				>
					{{ cancelOrBackButtonLabel }}
				</cdx-button>
				<cdx-button
					class="ext-reportincident-dialog-footer__next-btn"
					weight="primary"
					action="progressive"
					:disabled="formSubmissionInProgress || null"
					@click="navigateNext"
				>
					{{ primaryButtonLabel }}
				</cdx-button>
			</div>
		</template>
	</cdx-dialog>
</template>

<script>

const useFormStore = require( '../stores/Form.js' );
const useInstrument = require( '../composables/useInstrument.js' );
const { storeToRefs } = require( 'pinia' );
const { toRef, ref, computed } = require( 'vue' );
const { CdxButton, CdxDialog, CdxMessage, useModelWrapper } = require( '@wikimedia/codex' );
const icons = require( '../components/icons.json' );
const Constants = require( '../Constants.js' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialog',

	components: {
		CdxButton,
		CdxDialog,
		CdxMessage
	},

	props: {
		initialStep: {
			type: String,
			default: Constants.DIALOG_STEP_1
		}
	},

	emits: [ 'update:open' ],

	setup( props, { emit } ) {
		const wrappedOpen = useModelWrapper( toRef( props, 'open' ), emit, 'update:open' );
		const currentStep = ref( props.initialStep );
		const footerErrorMessage = ref( '' );
		const formSubmissionInProgress = ref( false );

		const store = useFormStore();
		const logEvent = useInstrument();

		const title = computed( () => {
			const isEmergency =
				store.incidentType === Constants.typeOfIncident.immediateThreatPhysicalHarm;
			const titlesByStep = {
				[ Constants.DIALOG_STEP_1 ]: 'reportincident-dialog-describe-the-incident-title',
				[ Constants.DIALOG_STEP_2 ]: 'reportincident-dialog-describe-the-incident-title',

				[ Constants.DIALOG_STEP_REPORT_BEHAVIOR_TYPES ]: 'reportincident-type-unacceptable-user-behavior',
				[ Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM ]: 'reportincident-dialog-report-immediate-harm-title',
				[ Constants.DIALOG_STEP_SUBMIT_SUCCESS ]: isEmergency ? 'reportincident-submit-emergency-dialog-title' :
					'reportincident-submit-behavior-dialog-title'
			};

			// Possible message keys used here are listed above.
			// eslint-disable-next-line mediawiki/msg-doc
			return mw.msg( titlesByStep[ currentStep.value ] );
		} );

		const currentSlotName = computed( () => `${ currentStep.value }` );
		const showFooterErrorText = computed( () => currentStep.value === Constants.DIALOG_STEP_2 && footerErrorMessage.value !== '' );
		const showCancelOrBackButton = computed(
			() => currentStep.value !== Constants.DIALOG_STEP_SUBMIT_SUCCESS
		);

		const stepsWithHelpText = [
			Constants.DIALOG_STEP_1,
			Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM
		];

		const showFooterHelpText = computed(
			() => stepsWithHelpText.indexOf( currentStep.value ) !== -1 && store.incidentType !== ''
		);

		const primaryButtonLabel = computed( () => {
			switch ( currentStep.value ) {
				case Constants.DIALOG_STEP_1:
					return mw.msg( 'reportincident-dialog-continue' );

				case Constants.DIALOG_STEP_2:
					if ( store.incidentType ===
							Constants.typeOfIncident.unacceptableUserBehavior ) {
						return mw.msg( 'reportincident-dialog-continue' );
					}

					return mw.msg( 'reportincident-dialog-submit-btn' );

				case Constants.DIALOG_STEP_SUBMIT_SUCCESS:
					return mw.msg(
						'reportincident-submit-back-to-page',
						mw.config.get( 'wgPageName' ).replace( '_', ' ' )
					);

				default:
					return mw.msg( 'reportincident-dialog-submit-btn' );
			}
		} );

		const cancelOrBackButtonLabel = computed(
			() => currentStep.value === Constants.DIALOG_STEP_1 ?
				mw.msg( 'reportincident-dialog-cancel' ) :
				mw.msg( 'reportincident-dialog-back-btn' ) );

		const footerHelpTextMessageHtml = computed(
			() => store.incidentType === 'immediate-threat-physical-harm' ?
				mw.message( 'reportincident-physical-harm-footer' ).parse() :
				mw.message( 'reportincident-unacceptable-behavior-footer' ).parse() );

		const footerIconName = computed( () => {
			if ( !store.incidentType ) {
				return null;
			}
			return store.incidentType === 'immediate-threat-physical-harm' ?
				icons.cdxIconLock :
				icons.cdxIconUserGroup;
		} );

		/**
		 * Function called when the POST request to the
		 * ReportIncident reporting REST API succeeds.
		 */
		function onReportSubmitSuccess() {
			currentStep.value = Constants.DIALOG_STEP_SUBMIT_SUCCESS;
			formSubmissionInProgress.value = false;
			footerErrorMessage.value = '';
		}

		/**
		 * Function called when the POST request to the
		 * ReportIncident reporting REST API fails.
		 *
		 * @param {string} _err
		 * @param {Object} errObject
		 */
		function onReportSubmitFailure( _err, errObject ) {
			let errorKey = null;
			let errorText = null;
			const errJson = errObject.xhr.responseJSON;
			if ( errJson ) {
				if ( errJson.errorKey ) {
					errorKey = errJson.errorKey;
					errorText = errJson.messageTranslations ?
						errJson.messageTranslations[ mw.config.get( 'wgUserLanguage' ) ] : null;
				}
			}

			if ( errorKey === 'reportincident-dialog-violator-nonexistent' ) {
				// Show the server error next to the correct field.
				store.reportedUserDoesNotExist = true;
				// Remove any existing footer error message as a field
				// specific one exists.
				footerErrorMessage.value = '';
				// Re-enable the field if is disabled as the server has said
				// the user does not exist, so it will need to be fixed.
				store.inputReportedUserDisabled = false;
			} else if ( errorKey && errorText ) {
				// If a localized error message is available in the response, use that.
				footerErrorMessage.value = errorText;
				store.formSubmissionInProgress = false;
			} else {
				let message;
				if ( !navigator.onLine ) {
					// If the navigator.onLine is false, the user is definitely
					// offline so display the internet disconnected error. The user
					// may still be offline if this property is true and in this
					// case the generic error will be shown.
					message = mw.msg( 'reportincident-dialog-internet-disconnected-error' );
				} else if (
					errObject.xhr.status >= 500 &&
					errObject.xhr.status < 600
				) {
					// If the HTTP status code starts with 5, then this is a
					// server error and the footer error message should indicate
					// it is the server that was the problem.
					message = mw.msg( 'reportincident-dialog-server-error' );
				} else {
					// Otherwise use the generic error.
					message = mw.msg( 'reportincident-dialog-generic-error' );
				}
				footerErrorMessage.value = message;
			}
			formSubmissionInProgress.value = false;
		}

		function submitReport() {
			const restPayload = store.restPayload;
			restPayload.revisionId = mw.config.get( 'wgCurRevisionId' );
			// TODO: Simulate mw.Api.postWithToken() by re-trying if the REST API call fails
			// because the CSRF token does not match.

			restPayload.token = mw.user.tokens.get( 'csrfToken' );

			formSubmissionInProgress.value = true;
			new mw.Rest().post(
				'/reportincident/v0/report',
				restPayload
			).then( onReportSubmitSuccess, onReportSubmitFailure );
		}

		function navigateNext() {
			const { showValidationError } = storeToRefs( store );

			// if on the first page, navigate to the second page, if the user has
			// made the necessary selections
			if ( currentStep.value === Constants.DIALOG_STEP_1 ) {
				if ( !store.isIncidentTypeSelected() ) {
					showValidationError.value = true;
					return;
				}
				if ( store.isPhysicalHarmSelectedButNoSubtypeSelected() ) {
					showValidationError.value = true;
					return;
				}
				// Validation passed, so we can proceed to step 2.
				if ( store.incidentType === Constants.typeOfIncident.immediateThreatPhysicalHarm ) {
					currentStep.value = Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM;
				} else {
					currentStep.value = Constants.DIALOG_STEP_2;
				}

				logEvent( 'click', {
					// eslint-disable-next-line camelcase
					context: JSON.stringify( { harm_option: store.physicalHarmTypeContext } ),
					source: 'form',
					subType: 'continue'
				} );
			} else if ( currentStep.value === Constants.DIALOG_STEP_SUBMIT_SUCCESS ) {
				wrappedOpen.value = false;
				store.$reset();
				currentStep.value = Constants.DIALOG_STEP_1;
			} else if ( ( currentStep.value === Constants.DIALOG_STEP_2 ) &&
					store.isUnacceptableBehavior() ) {
				unacceptableBehaviorNavigateNextFromStep2();
			} else {
				// if on the second page, validate, then POST the data
				logEvent( 'click', {
					subType: 'continue',
					source: 'submit_report',
					context: JSON.stringify( {
						// eslint-disable-next-line camelcase
						addl_info: Boolean(
							store.inputDetails || store.inputSomethingElseDetails
						),
						// eslint-disable-next-line camelcase
						reported_user: store.inputReportedUser
					} )
				} );

				if ( store.isFormValidForSubmission() ) {
					submitReport();
				}
			}
		}

		function unacceptableBehaviorNavigateNextFromStep2() {
			const {
				showValidationError,
				displaySomethingElseDetailsEmptyError,
				missingBehaviorSelection
			} = storeToRefs( store );

			logEvent( 'click', {
				context: store.inputBehavior,
				source: 'describe_unacceptable_behavior',
				subType: 'continue'
			} );

			if ( store.noBehaviorIsSelected() ) {
				showValidationError.value = true;
				missingBehaviorSelection.value = true;
				return;
			}

			missingBehaviorSelection.value = false;

			if ( store.isSomethingElse() && !store.areSomethingElseDetailsProvided() ) {
				showValidationError.value = true;
				displaySomethingElseDetailsEmptyError.value = true;
				return;
			}

			// Call the report API for unacceptable behavior as well for server-side event logging.
			if ( store.isFormValidForSubmission() ) {
				submitReport();
			}
		}

		function navigatePrevious() {
			if ( currentStep.value === Constants.DIALOG_STEP_1 ) {
				// if on the first page, close the dialog
				wrappedOpen.value = false;

				logEvent( 'click', {
					source: 'form',
					subType: 'cancel'
				} );

				// Also clear any form data, as the user has had to
				// navigate back from the second page to the first to
				// cancel which suggests they don't want to submit this
				// report.
				store.$reset();
			} else {
				// if on the second page, navigate back to the first page
				currentStep.value = Constants.DIALOG_STEP_1;
			}
		}

		/**
		 * Record an instrumentation event when the dialog is closed via the close button.
		 *
		 * @param {boolean} isOpen The new open state of the dialog.
		 */
		function onDialogOpenStateChanged( isOpen ) {
			if ( !isOpen ) {
				const sourcesByStep = {
					[ Constants.DIALOG_STEP_1 ]: 'form',
					[ Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM ]: 'submit_report',
					[ Constants.DIALOG_STEP_SUBMIT_SUCCESS ]: 'success'
				};

				logEvent( 'click', {
					source: sourcesByStep[ currentStep.value ],
					subType: 'close'
				} );
			}
		}

		return {
			wrappedOpen,
			primaryButtonLabel,
			cancelOrBackButtonLabel,
			currentSlotName,
			navigateNext,
			navigatePrevious,
			footerHelpTextMessageHtml,
			footerErrorMessage,
			showCancelOrBackButton,
			showFooterHelpText,
			showFooterErrorText,
			formSubmissionInProgress,
			onReportSubmitFailure,
			footerIconName,
			title,
			onDialogOpenStateChanged
		};
	},
	expose: [
		// Expose internal functions called from tests in order
		// to prevent linter errors about unused properties
		'onReportSubmitFailure'
	]
};
</script>

<style lang="less">
@import ( reference ) 'mediawiki.skin.variables.less';

.ext-reportincident-dialog {
	.ext-reportincident-dialog-footer {
		float: right;
	}

	// Necessary because it isn't currently possible to have an inline CodexMessage
	// that uses a normal font weight. That will become possible with T331623.
	.ext-reportincident-dialog__footer_help span {
		font-weight: normal;
	}

	@media screen and ( max-width: @max-width-breakpoint-mobile ) {
		.ext-reportincident-dialog-footer {
			display: flex;
			flex-direction: column-reverse;
			width: 100%;

			&__back-btn {
				width: 100%;
				margin-top: @spacing-35;
			}

			&__next-btn {
				width: 100%;
				margin-top: @spacing-35;
			}
		}
	}
}
</style>
