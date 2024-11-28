<?php

namespace MediaWiki\Extension\ReportIncident\Services;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\EventLogging\MetricsPlatform\MetricsClientFactory;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use StatusValue;

/**
 * Records non-emergency reports to the Metrics Platform schema used for IRS interaction data.
 */
class MetricsPlatformIncidentRecorder implements IReportIncidentRecorder {

	private MetricsClientFactory $metricsClientFactory;
	private TitleFactory $titleFactory;
	private UserFactory $userFactory;

	public function __construct(
		MetricsClientFactory $metricsClientFactory,
		TitleFactory $titleFactory,
		UserFactory $userFactory
	) {
		$this->metricsClientFactory = $metricsClientFactory;
		$this->titleFactory = $titleFactory;
		$this->userFactory = $userFactory;
	}

	public function record( IncidentReport $incidentReport ): StatusValue {
		// Don't record emergency reports in this implementation.
		// Eventually, this will be replaced by database storage,
		// which will equally record both kinds of reports (T345246).
		if ( $incidentReport->getIncidentType() === IncidentReport::THREAT_TYPE_IMMEDIATE ) {
			return StatusValue::newGood();
		}

		$reportedUser = $incidentReport->getReportedUser();

		$page = $incidentReport->getRevisionRecord()->getPage();

		$context = new DerivativeContext( $this->getContext() );
		$context->setTitle( $this->titleFactory->newFromPageIdentity( $page ) );
		$context->setUser( $this->userFactory->newFromUserIdentity( $incidentReport->getReportingUser() ) );

		$client = $this->metricsClientFactory->newMetricsClient( $context );

		$client->submitInteraction(
			'mediawiki.product_metrics.incident_reporting_system_interaction',
			'/analytics/product_metrics/web/base/1.3.0',
			'submit',
			[
				'action_source' => 'api',
				'action_context' => json_encode( [
					'type' => $incidentReport->getBehaviorType(),
					'reportedUserId' => $reportedUser ? $reportedUser->getId() : null,
				] ),
			]
		);

		return StatusValue::newGood();
	}

	/**
	 * Get the current global context. Useful for testing.
	 * @return IContextSource
	 */
	protected function getContext(): IContextSource {
		return RequestContext::getMain();
	}
}
