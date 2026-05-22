<template>
	<cdx-message type="success">
		{{ $i18n( 'reportincident-nonemergency-directreport-submitsuccess-banner' ).text() }}
	</cdx-message>
	<h3 class="ext-reportincident-dialog__submit-success-section-header">
		{{
			$i18n( 'reportincident-nonemergency-directreport-submitsuccess-next-header' ).text()
		}}
	</h3>
	<!-- eslint-disable vue/no-v-html-->
	<p
		v-for="( paragraph, key ) in nextInfoMsg"
		:key="key"
		v-html="paragraph"
	></p>
</template>

<script>
const { defineComponent, onMounted } = require( 'vue' );
const { CdxMessage } = require( '@wikimedia/codex' );
const useInstrument = require( '../composables/useInstrument.js' );

module.exports = exports = defineComponent( {
	name: 'NonEmergencyPostSubmitSuccessStep',
	components: { CdxMessage },
	setup() {
		const logEvent = useInstrument();

		onMounted( () => {
			logEvent( 'view', { source: 'direct_reporting_confirmation' } );
		} );
		let nextInfoMsg = mw
			.message( 'reportincident-nonemergency-directreport-submitsuccess-next-info' )
			.parse();
		nextInfoMsg = nextInfoMsg.split( '\n\n' );
		return {
			nextInfoMsg
		};
	}
} );
</script>
