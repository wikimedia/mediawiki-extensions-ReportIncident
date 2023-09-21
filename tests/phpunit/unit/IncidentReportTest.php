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
			'SomethingElse',
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
				'somethingElseDetails' => 'blah',
				'details' => 'Details'
			]
		) );
	}

	public function testGetters() {
		$reportingUser = new UserIdentityValue( 1, 'Reporter' );
		$reportedUser = new UserIdentityValue( 2, 'Reported' );
		$revisionRecord = $this->createMock( RevisionRecord::class );
		$link = 'https://foo.bar';
		$behaviors = [ 'foo' ];
		$somethingElseDetails = 'Something else';
		$details = 'Details';
		$incidentReport = new IncidentReport(
			$reportingUser,
			$reportedUser,
			$revisionRecord,
			$link,
			$behaviors,
			$somethingElseDetails,
			$details
		);
		$this->assertSame( $reportingUser, $incidentReport->getReportingUser() );
		$this->assertSame( $reportedUser, $incidentReport->getReportedUser() );
		$this->assertSame( $revisionRecord, $incidentReport->getRevisionRecord() );
		$this->assertSame( $link, $incidentReport->getLink() );
		$this->assertSame( $behaviors, $incidentReport->getBehaviors() );
		$this->assertSame( $somethingElseDetails, $incidentReport->getSomethingElseDetails() );
		$this->assertSame( $details, $incidentReport->getDetails() );
	}
}
