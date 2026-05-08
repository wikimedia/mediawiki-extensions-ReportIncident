<template>
	<div v-if="page" ref="pageContent">
		<cdx-message v-if="isTestReport" type="warning">
			{{ $i18n( 'reportincident-e2e-tester-notice' ).text() }}
		</cdx-message>
		<cdx-message :icon="icons.cdxIconUserGroup">
			<p v-if="page.description.header">
				<strong>{{ page.description.header }}</strong>
			</p>
			<p>{{ page.description.text }}</p>
		</cdx-message>
		<cdx-message v-if="page.notice && page.notice.shouldDisplay()" type="warning">
			<p>{{ page.notice.text }}</p>
		</cdx-message>
		<h3 class="ext-reportincident-dialog__submit-success-section-header">
			{{ $i18n( 'reportincident-nonemergency-nextsteps-header' ).text() }}
		</h3>
		<!-- eslint-disable vue/no-v-html-->
		<p
			v-for="( paragraph, key ) in nextStepMsg"
			:key="key"
			v-html="paragraph"
		></p>
		<!-- eslint-enable vue/no-v-html-->
		<h3 class="ext-reportincident-dialog__submit-success-section-header">
			{{ $i18n( 'reportincident-nonemergency-requesthelp-header' ).text() }}
		</h3>
		<ul>
			<!-- eslint-disable vue/no-v-html-->
			<li
				v-for="( helpMethod, key ) in helpMethods"
				:key="key"
				v-html="helpMethod"
			></li>
			<!-- eslint-enable vue/no-v-html-->
		</ul>
		<h3 class="ext-reportincident-dialog__submit-success-section-header">
			{{ $i18n( 'reportincident-nonemergency-other-header' ).text() }}
		</h3>
		<p v-i18n-html:reportincident-nonemergency-generic-nextstep-otheraction>
		</p>
	</div>
</template>

<script>

const { onMounted, ref } = require( 'vue' );
const useFormStore = require( '../stores/Form.js' );
const Constants = require( '../Constants.js' );
const { CdxMessage } = require( '@wikimedia/codex' );
const useInstrument = require( '../composables/useInstrument.js' );
const icons = require( '../components/icons.json' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogNonEmergencySupport',
	components: { CdxMessage },
	setup() {
		const logEvent = useInstrument();
		const pageContent = ref( null );
		const store = useFormStore();
		const behavior = store.inputBehavior;

		onMounted( () => {
			logEvent( 'view', { source: `get_support_${ behavior }` } );

			$( pageContent.value ).find( 'a' ).on( 'click', function () {
				logEvent( 'click', {
					context: $( this ).attr( 'href' ),
					source: `get_support_${ behavior }`
				} );
			} );
		} );

		const page = Object.values( Constants.harassmentTypesV2 )
			.find( ( obj ) => obj.id === behavior );

		// A message is considered valid if the requiredParams are found via mw.config.get()
		// If no requiredParams are found, the message is considered valid by default.
		function getValidMsgParams( msg ) {
			// No required params, message is automatically considered valid
			if ( !msg.requiredParams ) {
				return [];
			}

			// Check if all required parameters exist. No other validation is done here.
			// If all required parameters exist, message is considered valid.
			const msgParams = [];
			for ( const param of msg.requiredParams ) {
				const paramValue = mw.config.get( param );
				if ( !paramValue ) {
					// Found an invalid value, break as the message is considered invalid now
					break;
				}
				msgParams.push( paramValue );
			}

			// If all required parameters weren't found, message is invalid
			if ( msgParams.length !== msg.requiredParams.length ) {
				return false;
			}
			return msgParams;
		}

		// Cycle through all the next step messages, returning the first valid one
		let nextStepMsg = '';
		for ( const msg of page.nextSteps ) {
			const msgParams = getValidMsgParams( msg );
			if ( msgParams !== false ) {
				// * reportincident-nonemergency-intimidation-nextstep-configured
				// * reportincident-nonemergency-intimidation-nextstep-default
				nextStepMsg = mw.message( msg.msgKey, msgParams ).parse();
				break;
			}
		}
		nextStepMsg = nextStepMsg.split( '\n\n' );

		// Cycle through all help method messages and return all valid ones
		const helpMethods = [];
		for ( const msg of page.helpMethods ) {
			const msgParams = getValidMsgParams( msg );
			if ( msgParams !== false ) {
				// * reportincident-nonemergency-helpmethod-contactadmin
				// * reportincident-nonemergency-helpmethod-email
				// * reportincident-nonemergency-helpmethod-contactcommunity
				helpMethods.push( mw.message( msg.msgKey, msgParams ).parse() );
			}
		}

		// If no valid (configured) help methods were found, return the default fallback
		if ( !helpMethods.length ) {
			// * reportincident-nonemergency-helpmethod-default
			// * HACK: linter wants at least 2 messages, remove when second one added
			helpMethods.push( mw.message( page.helpMethodDefault.msgKey ).parse() );
		}

		const testers = mw.config.get( 'wgReportIncidentE2ETesterUsers' ) || [];
		const isTestReport = testers.includes( mw.user.getName() );

		return {
			page,
			pageContent,
			nextStepMsg,
			helpMethods,
			icons,
			isTestReport
		};
	}
};
</script>

<style lang="less">
.ext-reportincident-dialog__submit-success-section-header {
	font-size: 100%;
	padding: 0;
	margin: 1em 0 0 0;
}
</style>
