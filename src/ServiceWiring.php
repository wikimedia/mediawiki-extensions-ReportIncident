<?php

use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// See ReportIncidentServiceWiringTest.php for relevant tests.
// @codeCoverageIgnoreStart

return [
	'ReportIncidentManager' => static function (
		MediaWikiServices $services
	): ReportIncidentManager {
		return new ReportIncidentManager();
	},
];
// @codeCoverageIgnoreEnd
