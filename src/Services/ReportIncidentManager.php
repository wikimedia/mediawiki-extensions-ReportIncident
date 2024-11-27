<?php

namespace MediaWiki\Extension\ReportIncident\Services;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use StatusValue;

/**
 * Manage IncidentReport objects and do things:
 * - Create notifications for emergency reports
 */
class ReportIncidentManager {
	private IReportIncidentNotifier $notifier;

	public function __construct( IReportIncidentNotifier $notifier ) {
		$this->notifier = $notifier;
	}

	/**
	 * @param IncidentReport $incidentReport
	 * @return StatusValue
	 */
	public function record( IncidentReport $incidentReport ): StatusValue {
		// For now, this is a no-op.
		// Eventually we would store the reports in DB (T345246)
		return StatusValue::newGood();
	}

	public function notify( IncidentReport $incidentReport ): StatusValue {
		return $this->notifier->notify( $incidentReport );
	}

}
