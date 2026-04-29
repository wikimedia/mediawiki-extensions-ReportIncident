<template>
	<form id="reportincident-form" ref="pageContent">
		<cdx-message :type="messageType">
			<parsed-message class="ext-reportincident-dialog__message" :message="banner">
			</parsed-message>
		</cdx-message>
		<template v-for="section in sections" :key="section.title.key">
			<h3 class="ext-reportincident-dialog__submit-success-section-header">
				{{ section.title.text() }}
			</h3>
			<parsed-message
				v-for="item in section.paragraphs"
				:key="item.key"
				:message="item">
			</parsed-message>
			<ul v-if="section.listItems">
				<li v-for="item in section.listItems" :key="item.key">
					<parsed-message :message="item"></parsed-message>
				</li>
			</ul>
		</template>
	</form>
</template>

<script>
const { defineComponent, onMounted, ref, watch } = require( 'vue' );
const { CdxMessage } = require( '@wikimedia/codex' );
const ParsedMessage = require( './ParsedMessage.vue' );
const useInstrument = require( '../composables/useInstrument.js' );

module.exports = exports = defineComponent( {
	name: 'EmergencySubmitSuccessStep',
	components: {
		CdxMessage,
		ParsedMessage
	},
	setup() {
		const logEvent = useInstrument();
		const pageContent = ref( null );

		watch( () => pageContent.value, async () => {
			$( pageContent.value ).find( 'a' ).off( 'click' );

			$( pageContent.value ).find( 'a' ).on( 'click', function () {
				logEvent( 'click', {
					context: $( this ).attr( 'href' ),
					source: 'submitted'
				} );
			} );
		} );

		onMounted( () => {
			logEvent( 'view', { source: 'submitted' } );
		} );

		const banner = mw.message( 'reportincident-submit-emergency-success' );
		const messageType = 'success';
		const sections = [
			{
				title: mw.message( 'reportincident-submit-emergency-section-important-title' ),
				listItems: [
					mw.message( 'reportincident-submit-emergency-section-important-item-services' ),
					mw.message( 'reportincident-submit-emergency-section-important-item-resources' )
				]
			},
			{
				title: mw.message( 'reportincident-submit-emergency-section-next-title' ),
				paragraphs: [
					mw.message( 'reportincident-submit-emergency-section-next-item-team' ),
					mw.message( 'reportincident-submit-emergency-section-next-item-review' ),
					mw.message( 'reportincident-submit-emergency-section-next-item-email' )
				]
			}
		];

		return {
			pageContent,
			banner,
			sections,
			messageType
		};
	}
} );
</script>

<style lang="less">
.ext-reportincident-dialog__submit-success-section-header {
	font-size: 100%;
}
</style>
