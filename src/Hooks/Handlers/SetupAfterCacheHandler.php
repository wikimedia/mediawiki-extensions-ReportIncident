<?php
namespace MediaWiki\Extension\ReportIncident\Hooks\Handlers;

use MediaWiki\Extension\ReportIncident\Config\ReportIncidentConfigValidator;
use MediaWiki\Extension\ReportIncident\Config\ReportIncidentSchema;
use MediaWiki\Hook\SetupAfterCacheHook;
use MediaWiki\Registration\ExtensionRegistry;

/**
 * Register our validator class for IRS community configuration if community configuration is available.
 */
class SetupAfterCacheHandler implements SetupAfterCacheHook {

	public function onSetupAfterCache() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'CommunityConfiguration' ) ) {
			return;
		}

		global $wgCommunityConfigurationValidators;

		$wgCommunityConfigurationValidators['reportincident'] = [
			'class' => ReportIncidentConfigValidator::class,
			'services' => [
				'TitleParser',
				'PageStore',
				'StatsdDataFactory'
			],
			'args' => [
				ReportIncidentSchema::class
			],
			'factory' => ReportIncidentConfigValidator::class . '::factory'
		];
	}
}
