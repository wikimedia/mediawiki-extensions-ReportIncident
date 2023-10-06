<template>
	<email-alert-dialog v-model:open="emailAlertOpen">
	</email-alert-dialog>
	<report-incident-dialog v-model:open="open">
		<template #dialog_step_1>
			<report-incident-dialog-step-1></report-incident-dialog-step-1>
		</template>
		<template #dialog_step_2>
			<report-incident-dialog-step-2></report-incident-dialog-step-2>
		</template>
	</report-incident-dialog>
</template>

<script>
const { ref, onMounted } = require( 'vue' ),
	EmailAlertDialog = require( './EmailAlertDialog.vue' ),
	ReportIncidentDialog = require( './ReportIncidentDialog.vue' ),
	ReportIncidentDialogStep1 = require( './ReportIncidentDialogStep1.vue' ),
	ReportIncidentDialogStep2 = require( './ReportIncidentDialogStep2.vue' ),
	useFormStore = require( '../stores/Form.js' );

// @vue/component
module.exports = exports = {
	name: 'App',
	compatConfig: {
		MODE: 3
	},
	compilerOptions: {
		whitespace: 'condense'
	},
	components: {
		'email-alert-dialog': EmailAlertDialog,
		'report-incident-dialog': ReportIncidentDialog,
		'report-incident-dialog-step-1': ReportIncidentDialogStep1,
		'report-incident-dialog-step-2': ReportIncidentDialogStep2
	},
	setup() {

		const emailAlertOpen = ref( false );
		const open = ref( false );

		/**
		 * Open the main dialog if user has confirmed email, otherwise show email alert dialog.
		 */
		function showDialogDependingOnEmailConfirmationStatus() {
			if ( mw.config.get( 'wgReportIncidentUserHasConfirmedEmail' ) ) {
				open.value = true;
			} else {
				emailAlertOpen.value = true;
			}
		}

		// Open the dialog if the link is clicked on.
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '.ext-reportincident-link' ).on( 'click', ( event ) => {
			// TODO: Add instrumentation.
			event.preventDefault();
			showDialogDependingOnEmailConfirmationStatus();
		} );

		onMounted( () => {
			const store = useFormStore();
			mw.hook( 'discussionToolsOverflowMenuOnChoose' ).add( function ( id, menuItem ) {
				if ( id === 'reportincident' ) {
					store.overflowMenuData = menuItem.getData();
					showDialogDependingOnEmailConfirmationStatus();
				}
			} );
		} );
		return {
			open,
			emailAlertOpen
		};
	}
};
</script>
