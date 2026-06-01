<?php
declare( strict_types=1 );

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ReportIncident\Services\DirectReportIncidentNotifier;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentNotifier;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentRecorder;
use MediaWiki\Extension\ReportIncident\Services\MetricsPlatformIncidentRecorder;
use MediaWiki\Extension\ReportIncident\Services\NullReportIncidentNotifier;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentController;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Extension\ReportIncident\Services\ZendeskClient;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// See ReportIncidentServiceWiringTest.php for relevant tests.
// @codeCoverageIgnoreStart

return [
	'ReportIncidentManager' => static function (
		MediaWikiServices $services
	): ReportIncidentManager {
		return new ReportIncidentManager(
			$services->getService( 'ReportIncidentNotifier' ),
			$services->getService( 'ReportIncidentRecorder' ),
			$services->getService( 'DirectReportIncidentNotifier' )
		);
	},
	'ReportIncidentNotifier' => static function ( MediaWikiServices $services ): IReportIncidentNotifier {
		if ( !$services->getMainConfig()->get( 'ReportIncidentZendeskUrl' ) ) {
			return new NullReportIncidentNotifier();
		}

		return new ZendeskClient(
			$services->getHttpRequestFactory(),
			$services->getMessageFormatterFactory()->getTextFormatter( 'en' ),
			$services->getUrlUtils(),
			$services->getUserFactory(),
			$services->getTitleFactory(),
			LoggerFactory::getInstance( 'ReportIncident' ),
			new ServiceOptions( ZendeskClient::CONSTRUCTOR_OPTIONS, $services->getMainConfig() )
		);
	},
	'DirectReportIncidentNotifier' => static function ( MediaWikiServices $services ): IReportIncidentNotifier {
		// Direct reports are only possible if an email is configured in CommunityConfiguration.
		// No-op if it's not available.
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'CommunityConfiguration' ) ) {
			return new NullReportIncidentNotifier();
		}

		return new DirectReportIncidentNotifier(
			$services->getMainConfig(),
			$services->getService( 'CommunityConfiguration.MediaWikiConfigReader' ),
			new ServiceOptions( DirectReportIncidentNotifier::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			LoggerFactory::getInstance( 'ReportIncident' ),
			$services->getEmailer(),
			$services->getMessageFormatterFactory()->getTextFormatter(
				$services->getContentLanguageCode()->toString()
			),
			$services->getUrlUtils(),
			$services->getTitleFactory(),
			$services->getUserFactory()
		);
	},
	'ReportIncidentRecorder' => static function ( MediaWikiServices $services ): IReportIncidentRecorder {
		return new MetricsPlatformIncidentRecorder(
			$services->getService( 'EventLogging.MetricsClientFactory' ),
			$services->getTitleFactory(),
			$services->getUserFactory()
		);
	},
	'ReportIncidentController' => static function (
		MediaWikiServices $services
	): ReportIncidentController {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'CommunityConfiguration' ) ) {
			$localLinksConfig = $services->getService( 'CommunityConfiguration.MediaWikiConfigReader' );
		} else {
			$localLinksConfig = $services->getMainConfig();
		}
		$experimentManager = ExtensionRegistry::getInstance()->isLoaded( 'TestKitchen' ) ?
			MediaWikiServices::getInstance()->getService( 'TestKitchen.ExperimentManager' ) :
			null;
		return new ReportIncidentController( $services->getMainConfig(), $localLinksConfig, $experimentManager );
	}
];
// @codeCoverageIgnoreEnd
