<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\ReportIncident\Services;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use StatusValue;

/**
 * Manage IncidentReport objects and do things:
 * - Create notifications for emergency reports
 */
class ReportIncidentManager {
	public function __construct(
		private readonly IReportIncidentNotifier $notifier,
		private readonly IReportIncidentRecorder $recorder,
		private readonly IReportIncidentNotifier $directReportNotifier,
	) {
	}

	/**
	 * @param IncidentReport $incidentReport
	 * @return StatusValue
	 */
	public function record( IncidentReport $incidentReport ): StatusValue {
		return $this->recorder->record( $incidentReport );
	}

	public function notify( IncidentReport $incidentReport ): StatusValue {
		return $this->notifier->notify( $incidentReport );
	}

	public function sendDirectReport( IncidentReport $incidentReport ): StatusValue {
		return $this->directReportNotifier->notify( $incidentReport );
	}

	/**
	 * For direct reports, expose the send to email for use in error messages
	 *
	 * @param IncidentReport $incidentReport
	 * @return string
	 */
	public function getDirectReportSendToEmail( IncidentReport $incidentReport ): string {
		return $this->directReportNotifier->getSendToEmail( $incidentReport );
	}

}
