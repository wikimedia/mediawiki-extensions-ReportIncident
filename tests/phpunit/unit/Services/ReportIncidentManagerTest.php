<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\ReportIncident\Tests\Unit\Services;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentNotifier;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentRecorder;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Page\PageIdentityValue;
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
	private IReportIncidentNotifier $directReportNotifier;

	private ReportIncidentManager $reportIncidentManager;

	protected function setUp(): void {
		parent::setUp();

		$this->notifier = $this->createMock( IReportIncidentNotifier::class );
		$this->recorder = $this->createMock( IReportIncidentRecorder::class );
		$this->directReportNotifier = $this->createMock( IReportIncidentNotifier::class );

		$this->reportIncidentManager = new ReportIncidentManager(
			$this->notifier,
			$this->recorder,
			$this->directReportNotifier
		);
	}

	public function testRecord() {
		$incidentReport = $this->newIncidentReport();

		$this->recorder->expects( $this->once() )
			->method( 'record' )
			->with( $incidentReport )
			->willReturn( StatusValue::newGood() );

		$this->assertStatusGood( $this->reportIncidentManager->record( $incidentReport ) );
	}

	public function testNotify() {
		$incidentReport = $this->newIncidentReport();

		$this->notifier->expects( $this->once() )
			->method( 'notify' )
			->with( $incidentReport )
			->willReturn( StatusValue::newGood() );

		$this->assertStatusGood( $this->reportIncidentManager->notify( $incidentReport ) );
	}

	public function testDirectReport() {
		$incidentReport = $this->newIncidentReport( [
			'incidentType' => IncidentReport::THREAT_TYPE_UNACCEPTABLE_BEHAVIOR,
			'behaviorType' => 'doxing',
			'physicalHarmType' => null,
			'details' => null,
			'directReport' => 'Dirct report'
		] );

		$this->directReportNotifier->expects( $this->once() )
			->method( 'notify' )
			->with( $incidentReport )
			->willReturn( StatusValue::newGood() );

		$this->assertStatusGood( $this->reportIncidentManager->sendDirectReport( $incidentReport ) );
	}

	private function newIncidentReport( array $overrides = [] ): IncidentReport {
		$page = new PageIdentityValue( 1, NS_TALK, 'TestPage', PageIdentityValue::LOCAL );

		$revRecord = $this->createMock( RevisionRecord::class );
		$revRecord->method( 'getPage' )
			->willReturn( $page );

		return new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$revRecord,
			$page,
			$overrides['incidentType'] ?? IncidentReport::THREAT_TYPE_IMMEDIATE,
			$overrides['behaviorType'] ?? null,
			$overrides['physicalHarmType'] ?? 'threats-physical-harm',
			null,
			$overrides['details'] ?? 'Details',
			null,
			$overrides['directReport'] ?? null
		);
	}

}
