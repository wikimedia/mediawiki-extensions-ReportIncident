<template>
	<form id="reportincident-form" ref="form">
		<cdx-message :type="messageType">
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p class="ext-reportincident-dialog__message" v-html="banner.parse()"></p>
		</cdx-message>
		<template v-for="section in sections" :key="section.title.key">
			<h3 class="ext-reportincident-dialog__submit-success-section-header">
				{{ section.title.text() }}
			</h3>
			<!-- eslint-disable vue/no-v-html -->
			<p
				v-for="item in section.paragraphs"
				:key="item.key"
				v-html="item.parse()">
			</p>
			<ul v-if="section.listItems">
				<li
					v-for="item in section.listItems"
					:key="item.key"
					v-html="item.parse()">
				</li>
			</ul>
		</template>
	</form>
</template>

<script>
const { computed, defineComponent, onMounted, ref } = require( 'vue' );
const { CdxMessage } = require( '@wikimedia/codex' );
const Constants = require( '../Constants.js' );
const useFormStore = require( '../stores/Form.js' );
const useInstrument = require( '../composables/useInstrument.js' );

module.exports = exports = defineComponent( {
	name: 'SubmitSuccessStep',
	components: {
		CdxMessage
	},
	props: {
		links: { type: Object, required: true }
	},
	setup( props ) {
		const { links } = props;
		const store = useFormStore();
		const logEvent = useInstrument();

		const isEmergency = computed(
			() => store.incidentType === Constants.typeOfIncident.immediateThreatPhysicalHarm
		);

		const form = ref( null );

		onMounted( () => {
			if ( isEmergency.value ) {
				logEvent( 'view', { source: 'submitted' } );
				return;
			}

			logEvent( 'view', { source: 'get_support' } );

			$( form.value ).find( 'a' ).on( 'click', function () {
				logEvent( 'click', {
					context: $( this ).attr( 'href' ),
					source: 'get_support'
				} );
			} );
		} );

		const banner = computed(
			() => isEmergency.value ? mw.message( 'reportincident-submit-emergency-success' ) :
				mw.message( 'reportincident-submit-behavior-notice', links.localIncidentReport )
		);

		const messageType = computed( () => isEmergency.value ? 'success' : 'notice' );

		const sections = computed( () => {
			if ( isEmergency.value ) {
				return [
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
			}

			return [
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
		} );

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
