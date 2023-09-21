<template>
	<cdx-dialog
		v-model:open="wrappedOpen"
		:title="$i18n( 'reportincident-dialog-title' ).text()"
		:close-button-label="$i18n( 'reportincident-dialog-close-btn' ).text()"
		class="ext-reportincident-dialog"
	>
		<!-- dialog content-->
		<div class="ext-reportincident-dialog__content">
			<slot :name="currentSlotName"></slot>
		</div>

		<!-- dialog footer -->
		<template #footer>
			<p
				v-if="showFooterHelpText"
				v-i18n-html:reportincident-dialog-admin-review="[ adminLink ]"
				class="ext-reportincident-dialog__text-subtext">
			</p>
			<div class="ext-reportincident-dialog-footer">
				<cdx-button
					class="ext-reportincident-dialog-footer__back-btn"
					@click="navigatePrevious"
				>
					{{ defaultButtonLabel }}
				</cdx-button>
				<cdx-button
					class="ext-reportincident-dialog-footer__next-btn"
					weight="primary"
					action="progressive"
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
const { toRef, ref, computed } = require( 'vue' );
const { CdxButton, CdxDialog, useModelWrapper } = require( '@wikimedia/codex' );
const Constants = require( '../Constants.js' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialog',

	components: {
		CdxButton,
		CdxDialog
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

		const currentSlotName = computed( () => `${currentStep.value}` );
		const showFooterHelpText = computed( () => {
			return currentStep.value === Constants.DIALOG_STEP_1;
		} );

		const primaryButtonLabel = computed( () => {
			return currentStep.value === Constants.DIALOG_STEP_1 ?
				mw.msg( 'reportincident-dialog-proceed-btn' ) :
				mw.msg( 'reportincident-dialog-submit-btn' );
		} );

		const defaultButtonLabel = computed( () => {
			return currentStep.value === Constants.DIALOG_STEP_1 ?
				mw.msg( 'reportincident-dialog-first-step-cancel-btn' ) :
				mw.msg( 'reportincident-dialog-back-btn' );
		} );

		function navigateNext() {
			// if on the first page, navigate to the second page
			if ( currentStep.value === Constants.DIALOG_STEP_1 ) {
				currentStep.value = Constants.DIALOG_STEP_2;
			} else {
				// if on the second page, validate, then POST the data
				wrappedOpen.value = false;
				currentStep.value = Constants.DIALOG_STEP_1;
				const store = useFormStore();
				const restPayload = store.restPayload;
				restPayload.revisionId = mw.config.get( 'wgCurRevisionId' );
				// TODO: Validate
				new mw.Rest().post(
					'/reportincident/v0/report',
					restPayload
				);
			}
		}

		function navigatePrevious() {
			// if on the first page, close the dialog
			if ( currentStep.value === Constants.DIALOG_STEP_1 ) {
				wrappedOpen.value = false;
			} else {
				// if on the second page, navigate back to the first page
				currentStep.value = Constants.DIALOG_STEP_1;
			}
		}

		const adminLink = mw.util.getUrl( mw.config.get( 'wgReportIncidentAdministratorsPage' ) );

		return {
			wrappedOpen,
			primaryButtonLabel,
			defaultButtonLabel,
			currentSlotName,
			navigateNext,
			navigatePrevious,
			adminLink,
			showFooterHelpText
		};
	}
};
</script>

<style lang="less">
@import ( reference ) '../../../resources/lib/codex-design-tokens/theme-wikimedia-ui.less';

.ext-reportincident-dialog {
	.ext-reportincident-dialog-footer {
		float: right;
	}
	@media screen and ( max-width: @width-breakpoint-tablet ) {
		.ext-reportincident-dialog-footer {
			display: flex;
			flex-direction: column-reverse;
			width: 100%;
			&__back-btn {
				width: 100%;
				margin-top: @spacing-35;
			}

			&__next-btn {
				margin-top: @spacing-35;
				width: 100%;
			}
		}
	}
}

</style>
