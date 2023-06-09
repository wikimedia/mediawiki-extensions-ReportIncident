<template>
	<cdx-button @click="open = true">
		{{ $i18n( 'ext-incidentreporting-report-btn-label' ).text() }}
	</cdx-button>
	<cdx-dialog
		v-model:open="open"
		:title="$i18n( 'ext-incidentreporting-dialog-info-header' ).text()"
		:show-dividers="true"
		:close-button-label="$i18n( 'ext-incidentreporting-dialog-close-btn' ).text()"
		@default="open = false"
	>
	</cdx-dialog>
</template>

<script>
const { ref } = require( 'vue' ),
	{ CdxButton, CdxDialog } = require( '@wikimedia/codex' );

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
		'cdx-dialog': CdxDialog
	},
	setup() {
		const open = ref( false );
		return {
			open
		};
	}
};
</script>
