<?php

namespace MediaWiki\Extension\ReportIncident\Services;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use StatusValue;

/**
 * Manage IncidentReport objects and do something with them.
 */
class ReportIncidentManager {

	/**
	 * @param IncidentReport $incidentReport
	 * @return StatusValue
	 */
	public function record( IncidentReport $incidentReport ): StatusValue {
		// For now, this is a no-op.
		// Eventually we would store the reports in DB (T345246)
		return StatusValue::newGood();
	}
}
