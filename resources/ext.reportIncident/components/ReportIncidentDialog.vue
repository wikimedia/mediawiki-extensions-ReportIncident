<template>
	<cdx-dialog
		v-model:open="wrappedOpen"
		:title="stepTitleText"
		:use-close-button="true"
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
				v-if="footerHelpText.msg"
				:icon="footerHelpText.icon"
				:inline="true">
				<parsed-message
					class="ext-reportincident-dialog-footer-help"
					:message="footerHelpText.msg">
				</parsed-message>
			</cdx-message>
			<cdx-message
				v-if="footerErrorMessage"
				type="error"
				inline
				class="ext-reportincident-dialog__form-error-text">
				{{ footerErrorMessage }}
			</cdx-message>
			<div class="ext-reportincident-dialog-footer">
				<cdx-button
					v-if="secondaryButtonText"
					class="ext-reportincident-dialog-footer__back-btn"
					:disabled="formSubmissionInProgress || null"
					@click="navigatePrevious"
				>
					{{ secondaryButtonText }}
				</cdx-button>
				<cdx-button
					class="ext-reportincident-dialog-footer__next-btn"
					weight="primary"
					action="progressive"
					:disabled="formSubmissionInProgress || null"
					@click="navigateNext"
				>
					{{ primaryButtonText }}
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
const ParsedMessage = require( './ParsedMessage.vue' );
const icons = require( '../components/icons.json' );
const Constants = require( '../Constants.js' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialog',
	components: {
		CdxButton,
		CdxDialog,
		CdxMessage,
		ParsedMessage
	},
	props: {
		initialStep: {
			type: String,
			default: Constants.DIALOG_STEP_1
		}
	},
	emits: [ 'update:open' ],

	setup( props, { emit } ) {
		// Initialize global refs
		const store = useFormStore();
		const logEvent = useInstrument();

		// Initialize dialog-level state refs
		const wrappedOpen = useModelWrapper( toRef( props, 'open' ), emit, 'update:open' );
		const footerErrorMessage = ref( '' );
		const formSubmissionInProgress = ref( false );

		// Initialize all the refs needed to manage step state
		const currentStep = ref( '' );
		const isSuccessStep = ref( false );
		const stepTitleText = ref( '' );
		const primaryButtonText = ref( '' );
		const secondaryButtonText = ref( '' );

		/**
		 * Change what step is being shown. This should contain all components the
		 * step affects like the title, button texts, and whether or not the workflow
		 * has ended (isSuccessStep).
		 *
		 * @param {string} stepName
		 */
		function setStep( stepName ) {
			currentStep.value = stepName;

			switch ( stepName ) {
				case Constants.DIALOG_STEP_1:
					isSuccessStep.value = false;
					stepTitleText.value = mw.msg( 'reportincident-dialog-main-title' );
					primaryButtonText.value = mw.msg( 'reportincident-dialog-continue' );
					secondaryButtonText.value = mw.msg( 'reportincident-dialog-cancel' );
					return;

				// Non-emergency workflow
				case Constants.DIALOG_STEP_REPORT_BEHAVIOR_TYPES:
					isSuccessStep.value = false;
					stepTitleText.value = mw.msg( 'reportincident-dialog-describe-the-incident-title' );
					primaryButtonText.value = mw.msg( 'reportincident-dialog-continue' );
					secondaryButtonText.value = mw.msg( 'reportincident-dialog-back-btn' );
					return;

				case Constants.DIALOG_STEP_NONEMERGENCY_SUBMIT_SUCCESS:
					isSuccessStep.value = true;
					stepTitleText.value = mw.msg( 'reportincident-nonemergency-submitsuccess-title' );
					primaryButtonText.value = mw.msg( 'reportincident-submit-back-to-page' );
					secondaryButtonText.value = '';
					return;

				// Emergency workflow
				case Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM:
					isSuccessStep.value = false;
					stepTitleText.value = mw.msg( 'reportincident-dialog-report-immediate-harm-title' );
					primaryButtonText.value = mw.msg( 'reportincident-dialog-submit-btn' );
					secondaryButtonText.value = mw.msg( 'reportincident-dialog-back-btn' );
					return;

				case Constants.DIALOG_STEP_EMERGENCY_SUBMIT_SUCCESS:
					isSuccessStep.value = true;
					stepTitleText.value = mw.msg( 'reportincident-submit-emergency-dialog-title' );
					primaryButtonText.value = mw.msg( 'reportincident-submit-back-to-page' );
					secondaryButtonText.value = '';
					return;

				// This should never be hit so make it clear something has gone wrong
				default:
					isSuccessStep.value = false;
					stepTitleText.value = '';
					primaryButtonText.value = '';
					secondaryButtonText.value = '';
			}
		}

		// Initialize on load after all necessary variables and functions have been declared
		currentStep.value = props.initialStep;
		setStep( currentStep.value );
		const currentSlotName = computed( () => `${ currentStep.value }` );

		// Footer text state is calculated separately from the other step-dependent
		// components because it is actually incident type-dependent and can change
		// even when the step hasn't.
		const footerHelpText = computed( () => {
			if ( isSuccessStep.value ) {
				return {
					icon: null,
					msg: null
				};
			}

			switch ( store.incidentType ) {
				case Constants.typeOfIncident.unacceptableUserBehavior:
					return {
						icon: icons.cdxIconUserGroup,
						msg: mw.message( 'reportincident-unacceptable-behavior-footer' )
					};

				case Constants.typeOfIncident.immediateThreatPhysicalHarm:
					return {
						icon: icons.cdxIconLock,
						msg: mw.message( 'reportincident-physical-harm-footer' )
					};

				default:
					return {
						icon: null,
						msg: null
					};
			}
		} );

		/**
		 * Function called when the POST request to the
		 * ReportIncident reporting REST API succeeds.
		 */
		function onReportSubmitSuccess() {
			formSubmissionInProgress.value = false;
			footerErrorMessage.value = '';
		}

		/**
		 * Function called when the POST request to the
		 * ReportIncident reporting REST API fails.
		 *
		 * @param {Object} errObject
		 */
		function onReportSubmitFailure( errObject ) {
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

			if ( errorKey && errorText ) {
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

		/**
		 * POST request to the ReportIncident reporting REST API.
		 * Validation should be done before this is called.
		 *
		 * @return {Promise}
		 */
		async function submitReportPromise() {
			// The user filing the report is set as an e2e tester in CommunityConfiguration,
			// don't post the report and instead consider the submission as having succeeded
			if ( mw.config.get( 'wgReportIncidentE2ETesterUsers' ).includes( mw.user.getName() ) ) {
				return;
			}

			const restPayload = store.restPayload;
			restPayload.revisionId = mw.config.get( 'wgCurRevisionId' );
			// TODO: Simulate mw.Api.postWithToken() by re-trying if the REST API call fails
			// because the CSRF token does not match.

			restPayload.token = mw.user.tokens.get( 'csrfToken' );

			restPayload.page = mw.config.get( 'wgPageName' );

			formSubmissionInProgress.value = true;
			return new mw.Rest().post(
				'/reportincident/v0/report',
				restPayload
			).catch(
				( errorType, errObject ) => {
					const error = new Error( errorType );
					error.object = errObject;
					throw error;
				}
			);
		}

		/**
		 * Convenience function to close the dialog
		 */
		function closeDialog() {
			wrappedOpen.value = false;
			store.$reset();
		}

		function navigateNext() {
			const { showValidationError } = storeToRefs( store );
			footerErrorMessage.value = '';

			// If on any success/final step, close and reset
			if ( isSuccessStep.value ) {
				closeDialog();
				setStep( Constants.DIALOG_STEP_1 );
				return;
			}

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
				// Validation passed so we can proceed to either workflow.
				if ( store.incidentType === Constants.typeOfIncident.immediateThreatPhysicalHarm ) {
					setStep( Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM );
				} else {
					setStep( Constants.DIALOG_STEP_REPORT_BEHAVIOR_TYPES );
				}

				logEvent( 'click', {
					// eslint-disable-next-line camelcase
					context: JSON.stringify( { harm_option: store.physicalHarmTypeContext } ),
					source: 'form',
					subType: 'continue'
				} );

				return;
			}

			// Non-emergency workflow
			// If navigating from DIALOG_STEP_REPORT_BEHAVIOR_TYPES, validate that a behavior
			// type was selected and if one is, submit the form and navigate to success page.
			if ( currentStep.value === Constants.DIALOG_STEP_REPORT_BEHAVIOR_TYPES ) {
				const {
					displaySomethingElseDetailsEmptyError,
					missingBehaviorSelection
				} = storeToRefs( store );

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

				logEvent( 'click', {
					context: store.inputBehavior,
					source: 'describe_unacceptable_behavior',
					subType: 'continue'
				} );

				// Even though no actual report will be submitted,
				// call the server anyway for logging purposes
				if ( store.isFormValidForSubmission() ) {
					submitReportPromise()
						.then( () => {
							onReportSubmitSuccess();
							setStep( Constants.DIALOG_STEP_NONEMERGENCY_SUBMIT_SUCCESS );
						} )
						.catch( ( err ) => {
							onReportSubmitFailure( err.object );
						} );
				}

				return;
			}

			// Emergency workflow
			// If navigating from DIALOG_STEP_REPORT_IMMEDIATE_HARM, validate that
			// the report is filled out, POST it, and navigate to the success page
			if ( currentStep.value === Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM ) {
				if ( !store.isFormValidForSubmission() ) {
					return;
				}
				logEvent( 'click', {
					subType: 'submit_report',
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
				submitReportPromise()
					.then( () => {
						onReportSubmitSuccess();
						setStep( Constants.DIALOG_STEP_EMERGENCY_SUBMIT_SUCCESS );
					} )
					.catch( ( err ) => {
						onReportSubmitFailure( err.object );
					} );
			}
		}

		function navigatePrevious() {
			footerErrorMessage.value = '';

			if ( currentStep.value === Constants.DIALOG_STEP_1 ) {
				// if on the first page, close the dialog and clear
				// any form data, as the user has had to navigate back
				// from the second page to the first to cancel which
				// suggests they don't want to submit this report.
				logEvent( 'click', {
					source: 'form',
					subType: 'cancel'
				} );
				closeDialog();
			} else {
				// if on the second page, navigate back to the first page
				let source;
				if ( currentStep.value === Constants.DIALOG_STEP_REPORT_BEHAVIOR_TYPES ) {
					source = 'describe_unacceptable_behavior';
				} else if ( currentStep.value === Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM ) {
					source = 'submit_report';
				} else {
					source = 'form';
				}
				setStep( Constants.DIALOG_STEP_1 );
				logEvent( 'click', {
					source: source,
					subType: 'back'
				} );
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
					[ Constants.DIALOG_STEP_REPORT_BEHAVIOR_TYPES ]: 'describe_unacceptable_behavior',
					[ Constants.DIALOG_STEP_REPORT_IMMEDIATE_HARM ]: 'submit_report',
					[ Constants.DIALOG_STEP_EMERGENCY_SUBMIT_SUCCESS ]: 'submitted',
					[ Constants.DIALOG_STEP_NONEMERGENCY_SUBMIT_SUCCESS ]: `get_support_${ store.inputBehavior }`
				};

				logEvent( 'click', {
					source: sourcesByStep[ currentStep.value ],
					subType: 'close'
				} );
			}
		}

		return {
			wrappedOpen,
			currentSlotName,
			navigateNext,
			navigatePrevious,
			footerErrorMessage,
			formSubmissionInProgress,
			onReportSubmitFailure,
			onDialogOpenStateChanged,
			stepTitleText,
			primaryButtonText,
			secondaryButtonText,
			footerHelpText
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
		margin-top: @spacing-50;

		.ext-reportincident-dialog-footer__back-btn {
			margin-right: @spacing-50;
		}
	}

	.ext-reportincident-dialog-footer-help {
		color: @color-subtle;
		// Necessary because it isn't currently possible to have an inline CodexMessage
		// that uses a normal font weight. That will become possible with T331623.
		font-weight: normal;
		hyphens: manual;
		-ms-hyphens: manual;
		-webkit-hyphens: manual;
	}

	@media screen and ( max-width: @max-width-breakpoint-mobile ) {
		// NOTE: Add extra cascade due to conflicting MobileFrontend styling.
		p.ext-reportincident-dialog-footer-help {
			font-size: @font-size-small;
		}

		.ext-reportincident-dialog-footer {
			margin-top: @spacing-75;
		}
	}
}
</style>
