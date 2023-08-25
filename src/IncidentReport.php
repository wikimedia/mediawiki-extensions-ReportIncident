<?php

namespace MediaWiki\Extension\ReportIncident;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;

/**
 * Plain value object containing incident report data.
 */
class IncidentReport {

	/**
	 * @param UserIdentity $reportingUser
	 * @param UserIdentity $reportedUser
	 * @param RevisionRecord $revisionRecord
	 * @param string $link
	 * @param array $behaviors
	 * @param string|null $details
	 */
	public function __construct(
		UserIdentity $reportingUser,
		UserIdentity $reportedUser,
		RevisionRecord $revisionRecord,
		string $link,
		array $behaviors,
		?string $details = null
	) {
	}

	public static function newFromRestPayload(
		UserIdentity $reportingUser,
		array $data
	): IncidentReport {
		return new self(
			$reportingUser,
			$data['reportedUser'],
			$data['revision'],
			$data['link'],
			$data['behaviors'],
			$data['details'] ?? null
		);
	}

}
