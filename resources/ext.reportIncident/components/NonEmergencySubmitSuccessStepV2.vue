<template>
	<template v-if="page">
		<cdx-message>
			<p><strong>{{ page.description.header }}</strong></p>
			<p>{{ page.description.text }}</p>
		</cdx-message>
		<cdx-message v-if="page.notice.shouldDisplay" type="warning">
			<p>{{ page.notice.text }}</p>
		</cdx-message>
		<h3>{{ $i18n( 'reportincident-nonemergency-nextsteps-header' ).text() }}</h3>
		<h3>{{ $i18n( 'reportincident-nonemergency-requesthelp-header' ).text() }}</h3>
		<h3>{{ $i18n( 'reportincident-nonemergency-other-header' ).text() }}</h3>
	</template>
</template>

<script>

const useFormStore = require( '../stores/Form.js' );
const { CdxMessage } = require( '@wikimedia/codex' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogNonEmergencySupport',
	components: { CdxMessage },
	setup() {
		const store = useFormStore();
		const behavior = store.inputBehavior;

		const pages = {};

		const page = pages[ behavior ];

		return {
			page
		};
	}
};
</script>
