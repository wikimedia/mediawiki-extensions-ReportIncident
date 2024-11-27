<?php

namespace MediaWiki\Extension\ReportIncident;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;

/**
 * Plain value object containing incident report data.
 */
class IncidentReport {
	public const THREAT_TYPE_IMMEDIATE = 'immediate-threat-physical-harm';
	public const THREAT_TYPE_UNACCEPTABLE_BEHAVIOR = 'unacceptable-user-behavior';

	private UserIdentity $reportingUser;
	private UserIdentity $reportedUser;
	private RevisionRecord $revisionRecord;
	private ?string $behaviorType;
	private ?string $physicalHarmType;
	private ?string $somethingElseDetails;
	private ?string $details;
	private ?string $threadId;
	private string $incidentType;

	public function __construct(
		UserIdentity $reportingUser,
		UserIdentity $reportedUser,
		RevisionRecord $revisionRecord,
		string $incidentType,
		?string $behaviorType,
		?string $physicalHarmType,
		?string $somethingElseDetails = null,
		?string $details = null,
		?string $threadId = null
	) {
		$this->reportingUser = $reportingUser;
		$this->reportedUser = $reportedUser;
		$this->revisionRecord = $revisionRecord;
		$this->incidentType = $incidentType;
		$this->behaviorType = $behaviorType;
		$this->somethingElseDetails = $somethingElseDetails;
		$this->details = $details;
		$this->threadId = $threadId;
		$this->physicalHarmType = $physicalHarmType;
	}

	/**
	 * Known values for the 'behaviorType' field.
	 * This should match the list of allowed values for "unacceptable behavior" reports on the frontend.
	 *
	 * @return string[]
	 */
	public static function behaviorTypes(): array {
		return [
			'doxing',
			'hate-speech-or-discrimination',
			'intimidation',
			'sexual-harassment',
			'spam',
			'trolling',
			'something-else'
		];
	}

	public static function newFromRestPayload(
		UserIdentity $reportingUser,
		array $data
	): IncidentReport {
		return new self(
			$reportingUser,
			$data['reportedUser'],
			$data['revision'],
			$data['incidentType'],
			$data['behaviorType'] ?? null,
			$data['physicalHarmType'] ?? null,
			$data['somethingElseDetails'] ?? null,
			$data['details'] ?? null,
			$data['threadId'] ?? null
		);
	}

	public function getReportingUser(): UserIdentity {
		return $this->reportingUser;
	}

	public function getIncidentType(): string {
		return $this->incidentType;
	}

	public function getBehaviorType(): ?string {
		return $this->behaviorType;
	}

	public function getPhysicalHarmType(): ?string {
		return $this->physicalHarmType;
	}

	public function getDetails(): ?string {
		return $this->details;
	}

	public function getRevisionRecord(): RevisionRecord {
		return $this->revisionRecord;
	}

	public function getReportedUser(): UserIdentity {
		return $this->reportedUser;
	}

	public function getSomethingElseDetails(): ?string {
		return $this->somethingElseDetails;
	}

	public function getThreadId(): ?string {
		return $this->threadId;
	}
}
