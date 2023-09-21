<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentMailer;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;

/**
 * @covers MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager
 */
class ReportIncidentManagerTest extends MediaWikiUnitTestCase {

	public function testRecord() {
		$incidentReport = new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$this->createMock( RevisionRecord::class ),
			'https://foo.bar',
			[ 'foo' ],
			'Details'
		);
		$reportIncidentManager = new ReportIncidentManager(
			$this->createMock( ReportIncidentMailer::class ),
		);
		$this->assertStatusGood( $reportIncidentManager->record( $incidentReport ) );
	}

	public function testNotify() {
		$incidentReport = new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$this->createMock( RevisionRecord::class ),
			'https://foo.bar',
			[ 'foo' ],
			'Details'
		);
		$reportIncidentMailer = $this->createMock( ReportIncidentMailer::class );
		$reportIncidentMailer->method( 'sendEmail' )->willReturn( \StatusValue::newGood() );
		$reportIncidentManager = new ReportIncidentManager(
			$reportIncidentMailer
		);
		$this->assertStatusGood( $reportIncidentManager->notify( $incidentReport ) );
	}

}
