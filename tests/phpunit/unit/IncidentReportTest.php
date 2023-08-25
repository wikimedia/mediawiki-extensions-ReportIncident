<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ReportIncident\IncidentReport
 */
class IncidentReportTest extends MediaWikiUnitTestCase {

	public function testConstruction() {
		$incidentReport = new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$this->createMock( RevisionRecord::class ),
			'https://foo.bar',
			[ 'foo' ],
			'Details'
		);
		$this->assertInstanceOf( IncidentReport::class, $incidentReport );
	}

	public function testNewFromRestPayload() {
		$this->assertInstanceOf( IncidentReport::class, IncidentReport::newFromRestPayload(
			new UserIdentityValue( 1, 'Reporter' ),
			[
				'reportedUser' => new UserIdentityValue( 2, 'Reported' ),
				'revision' => $this->createMock( RevisionRecord::class ),
				'link' => 'https://foo.bar',
				'behaviors' => [ 'foo' ],
				'details' => 'Details'
			]
		) );
	}
}
