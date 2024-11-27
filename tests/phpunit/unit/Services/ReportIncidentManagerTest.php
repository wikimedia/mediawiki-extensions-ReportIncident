<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit\Services;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\IReportIncidentNotifier;
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
		$reportIncidentManager = new ReportIncidentManager(
			$this->createMock( IReportIncidentNotifier::class ),
		);
		$this->assertStatusGood( $reportIncidentManager->record( $incidentReport ) );
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
		$notifier = $this->createMock( IReportIncidentNotifier::class );
		$notifier->expects( $this->once() )
			->method( 'notify' )
			->with( $incidentReport )
			->willReturn( StatusValue::newGood() );

		$reportIncidentManager = new ReportIncidentManager(
			$notifier
		);
		$this->assertStatusGood( $reportIncidentManager->notify( $incidentReport ) );
	}

}
