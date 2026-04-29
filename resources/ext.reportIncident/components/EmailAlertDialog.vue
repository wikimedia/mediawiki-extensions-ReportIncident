<template>
	<cdx-dialog
		v-model:open="wrappedOpen"
		class="ext-reportincident-emaildialog"
		:title="$i18n( 'reportincident-emaildialog-title' ).text()"
		:use-close-button="true"
		:close-button-label="$i18n( 'reportincident-emaildialog-close-button' ).text()"
		:primary-action="primaryAction"
		:default-action="defaultAction"
		@primary="onPrimaryAction"
		@default="onDefaultAction"
		@update:open="onDialogUpdateOpen"
	>
		<p>{{ $i18n( 'reportincident-emaildialog-content' ).text() }}</p>
	</cdx-dialog>
</template>

<script>

const { toRef } = require( 'vue' );
const { CdxDialog, useModelWrapper } = require( '@wikimedia/codex' );

// @vue/component
module.exports = exports = {
	name: 'EmailAlertDialog',
	components: { CdxDialog },
	emits: [ 'update:open' ],
	setup( props, { emit } ) {
		const wrappedOpen = useModelWrapper( toRef( props, 'open' ), emit, 'update:open' );

		const primaryAction = {
			label: mw.msg( 'reportincident-emaildialog-primary' ),
			actionType: 'progressive'
		};

		const defaultAction = {
			label: mw.msg( 'reportincident-emaildialog-close-button' )
		};

		function onDialogUpdateOpen( isOpen ) {
			if ( !isOpen ) {
				mw.hook( 'reportincident.logEvent' ).fire( 'click', {
					source: 'email_verification',
					subType: 'close'
				} );
			}
		}

		function onPrimaryAction() {
			wrappedOpen.value = false;

			mw.hook( 'reportincident.logEvent' ).fire( 'click', {
				source: 'email_verification',
				subType: 'go_to_settings'
			} );

			if ( mw.config.get( 'wgReportIncidentUserHasEmail' ) ) {
				window.location.assign(
					mw.Title.newFromText( 'Special:ConfirmEmail' ).getUrl()
				);

				return;
			}

			window.location.assign(
				mw.Title.newFromText( 'Special:Preferences' ).getUrl() +
					'#mw-prefsection-personal-email'
			);
		}

		function onDefaultAction() {
			wrappedOpen.value = false;

			mw.hook( 'reportincident.logEvent' ).fire( 'click', {
				source: 'email_verification',
				subType: 'cancel'
			} );
		}

		return {
			wrappedOpen,
			primaryAction,
			defaultAction,
			onPrimaryAction,
			onDefaultAction,
			onDialogUpdateOpen
		};
	}
};

</script>
