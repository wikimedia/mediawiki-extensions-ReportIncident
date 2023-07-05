<template>
	<div class="ext-reportincident-dialog-step1">
		<!-- TODO: should this link be localized to the user's current wiki? -->
		<a
			target="_blank"
			href="https://en.wikipedia.org/wiki/Code_of_conduct"
		>
			<span> {{ $i18n( 'reportincident-dialog-code-of-conduct' ).text() }}</span>
		</a>
	</div>
</template>

<script>
// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogStep1'
};
</script>
