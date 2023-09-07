<template>
	<div class="ext-reportincident-dialog-step1">
		<!-- Harassment explainer -->
		<div>
			<span>
				{{ $i18n( 'reportincident-dialog-harassment-intro-text' ).text() }}
				{{ $i18n(
					'reportincident-dialog-harassment-lead-in-to-harassment-examples'
				).text() }}
			</span>
			<ul>
				<li>
					{{ $i18n( 'reportincident-dialog-harassment-example-insults' ).text() }}
				</li>
				<li>
					{{
						$i18n( 'reportincident-dialog-harassment-example-sexual-harassment' ).text()
					}}
				</li>
				<li>
					{{ $i18n( 'reportincident-dialog-harassment-example-threats' ).text() }}
				</li>
				<li>
					{{ $i18n(
						'reportincident-dialog-harassment-example-encouraging-harm'
					).text() }}
				</li>
				<li>
					{{ $i18n( 'reportincident-dialog-harassment-example-doxing' ).text() }}
				</li>
				<li>
					{{ $i18n( 'reportincident-dialog-harassment-example-hounding' ).text() }}
				</li>
				<li>
					{{ $i18n( 'reportincident-dialog-harassment-example-trolling' ).text() }}
				</li>
			</ul>
		</div>

		<!-- Universal code of conduct -->
		<div class="ext-reportincident-dialog-step1__text-block">
			<span
				v-i18n-html:reportincident-dialog-code-of-conduct-link="[ codeOfConductLink ]">
			</span>
		</div>

		<!-- How to engage in dialog section -->
		<div class="ext-reportincident-dialog-step1__text-block">
			<div class="ext-reportincident-dialog__text-header">
				{{ $i18n( 'reportincident-dialog-engage-header' ).text() }}
			</div>
			<span> {{ $i18n( 'reportincident-dialog-engage-text' ).text() }}</span>
		</div>
		<div class="ext-reportincident-dialog-step1__text-block">
			<span
				v-i18n-html:reportincident-dialog-admin-review="[ adminLink ]"
				class="ext-reportincident-dialog__text-subtext">
			</span>
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogStep1',
	setup() {

		const adminLink = mw.util.getUrl( mw.config.get( 'wgReportIncidentAdministratorsPage' ) );
		const codeOfConductLink = 'https://foundation.wikimedia.org/wiki/Special:MyLanguage/Universal_Code_of_Conduct';

		return {
			adminLink,
			codeOfConductLink
		};
	}
};
</script>

<style lang="less">
@import ( reference ) '../../../resources/lib/codex-design-tokens/theme-wikimedia-ui.less';

.ext-reportincident-dialog-step1 {
	&__text-block {
		margin-top: @spacing-125;
	}
	ul {
		&, li {
			margin-top: @spacing-12;
			margin-bottom: @spacing-12;
		}
	}
}
</style>
