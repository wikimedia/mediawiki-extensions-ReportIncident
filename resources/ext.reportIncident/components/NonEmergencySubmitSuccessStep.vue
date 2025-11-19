<template>
	<form id="reportincident-form" ref="form">
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
const { defineComponent, onMounted, ref } = require( 'vue' );
const { CdxMessage } = require( '@wikimedia/codex' );
const ParsedMessage = require( './ParsedMessage.vue' );
const useInstrument = require( '../composables/useInstrument.js' );

module.exports = exports = defineComponent( {
	name: 'NonEmergencySubmitSuccessStep',
	components: {
		CdxMessage,
		ParsedMessage
	},
	props: {
		links: { type: Object, required: true }
	},
	setup( props ) {
		const { links } = props;
		const logEvent = useInstrument();
		const form = ref( null );

		onMounted( () => {
			logEvent( 'view', { source: 'get_support' } );

			$( form.value ).find( 'a' ).on( 'click', function () {
				logEvent( 'click', {
					context: $( this ).attr( 'href' ),
					source: 'get_support'
				} );
			} );
		} );

		const banner = mw.message( 'reportincident-submit-behavior-notice', links.localIncidentReport );
		const messageType = 'notice';
		const sections = [
			{
				title: mw.message( 'reportincident-submit-behavior-section-support-title' ),
				paragraphs: [
					mw.message( 'reportincident-submit-behavior-section-support-item-behavior' )
				],
				listItems: [
					mw.message( 'reportincident-submit-behavior-section-support-item-guidelines' ),
					mw.message(
						'reportincident-submit-behavior-section-support-item-dispute-resolution',
						links.disputeResolution
					),
					mw.message(
						'reportincident-submit-behavior-section-support-item-admins',
						links.localIncidentReport
					),
					mw.message( 'reportincident-submit-behavior-section-support-item-mentors' ),
					mw.message( 'reportincident-submit-behavior-section-support-item-info-is-public' )
				]
			},
			{
				title: mw.message( 'reportincident-submit-behavior-section-other-options-title' ),
				paragraphs: [
					mw.message(
						'reportincident-submit-behavior-section-other-options-item-ask',
						links.askTheCommunity
					),
					mw.message( 'reportincident-submit-behavior-section-other-options-item-contact-host' )
				]
			}
		];

		return {
			form,
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
