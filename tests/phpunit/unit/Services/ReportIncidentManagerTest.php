<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit\Services;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentNotifier;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentRecorder;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;

/**
 * @group ReportIncident
 *
 * @covers MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager
 */
class ReportIncidentManagerTest extends MediaWikiUnitTestCase {

	private IReportIncidentNotifier $notifier;
	private IReportIncidentRecorder $recorder;

	private ReportIncidentManager $reportIncidentManager;

	protected function setUp(): void {
		parent::setUp();

		$this->notifier = $this->createMock( IReportIncidentNotifier::class );
		$this->recorder = $this->createMock( IReportIncidentRecorder::class );

		$this->reportIncidentManager = new ReportIncidentManager(
			$this->notifier,
			$this->recorder
		);
	}

	public function testRecord() {
		$incidentReport = new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$this->createMock( RevisionRecord::class ),
			IncidentReport::THREAT_TYPE_IMMEDIATE,
			null,
			'threats-physical-harm',
			null,
			'Details'
		);

		$this->recorder->expects( $this->once() )
			->method( 'record' )
			->with( $incidentReport )
			->willReturn( StatusValue::newGood() );

		$this->assertStatusGood( $this->reportIncidentManager->record( $incidentReport ) );
	}

	public function testNotify() {
		$incidentReport = new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$this->createMock( RevisionRecord::class ),
			IncidentReport::THREAT_TYPE_IMMEDIATE,
			null,
			'threats-physical-harm',
			null,
			'Details'
		);

		$this->notifier->expects( $this->once() )
			->method( 'notify' )
			->with( $incidentReport )
			->willReturn( StatusValue::newGood() );

		$this->assertStatusGood( $this->reportIncidentManager->notify( $incidentReport ) );
	}

}
