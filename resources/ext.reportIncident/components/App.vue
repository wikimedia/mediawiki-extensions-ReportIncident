<template>
	<cdx-button
		id="ext-reportincident-dialog-button"
		@click="open = true"
	>
		{{ $i18n( 'reportincident-report-btn-label' ).text() }}
	</cdx-button>
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
const { ref } = require( 'vue' ),
	{ CdxButton } = require( '@wikimedia/codex' ),
	ReportIncidentDialog = require( './ReportIncidentDialog.vue' ),
	ReportIncidentDialogStep1 = require( './ReportIncidentDialogStep1.vue' ),
	ReportIncidentDialogStep2 = require( './ReportIncidentDialogStep2.vue' );

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
		'cdx-button': CdxButton,
		'report-incident-dialog': ReportIncidentDialog,
		'report-incident-dialog-step-1': ReportIncidentDialogStep1,
		'report-incident-dialog-step-2': ReportIncidentDialogStep2
	},
	setup() {
		const open = ref( false );
		return {
			open
		};
	}
};
</script>
