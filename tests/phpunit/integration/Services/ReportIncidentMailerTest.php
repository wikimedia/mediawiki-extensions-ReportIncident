<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Integration\Services;

use MailAddress;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentMailer;
use MediaWiki\Mail\IEmailer;
use MediaWiki\Message\TextFormatter;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\Utils\UrlUtils;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
use StatusValue;
use Wikimedia\Message\MessageValue;
use Wikimedia\Message\ParamType;
use Wikimedia\Message\ScalarParam;

/**
 * @group ReportIncident
 *
 * @covers \MediaWiki\Extension\ReportIncident\Services\ReportIncidentMailer
 */
class ReportIncidentMailerTest extends MediaWikiIntegrationTestCase {
	private function getReportIncidentMailer(
		array $options,
		?UrlUtils $urlUtils = null,
		?IEmailer $emailer = null,
		?TitleFactory $titleFactory = null,
		?TextFormatter $textFormatter = null,
		?LoggerInterface $logger = null
	) {
		return new ReportIncidentMailer(
			new ServiceOptions( ReportIncidentMailer::CONSTRUCTOR_OPTIONS, $options ),
			$urlUtils ?? $this->createMock( UrlUtils::class ),
			$titleFactory ?? $this->createMock( TitleFactory::class ),
			$textFormatter ?? $this->createMock( TextFormatter::class ),
			$emailer ?? $this->createMock( IEmailer::class ),
			$logger ?? $this->createMock( LoggerInterface::class )
		);
	}

	public function testSuccessfulEmail() {
		// Make a mock RevisionRecord with ID 1.
		$mockRevisionRecord = $this->createMock( RevisionRecord::class );
		$mockRevisionRecord->method( 'getId' )
			->willReturn( 1 );
		$incidentReport = new IncidentReport(
			UserIdentityValue::newRegistered( 1, 'Test' ),
			UserIdentityValue::newRegistered( 2, 'Violator' ),
			$mockRevisionRecord,
			[ 'something-else', 'foo' ],
			'something-else-details',
			'details'
		);
		// Mock the Title for the reporting user and reported user.
		$mockReportingUserPageTitle = $this->createMock( Title::class );
		$mockReportingUserPageTitle->method( 'getPrefixedDBkey' )
			->willReturn( 'User_talk:' . strtr( $incidentReport->getReportingUser()->getName(), ' ', '_' ) );
		$mockReportingUserPageTitle->method( 'getDBkey' )
			->willReturn( strtr( $incidentReport->getReportingUser()->getName(), ' ', '_' ) );
		$mockReportedUserPageTitle = $this->createMock( Title::class );
		$mockReportedUserPageTitle->method( 'getPrefixedDBkey' )
			->willReturn( 'User_talk:' . strtr( $incidentReport->getReportedUser()->getName(), ' ', '_' ) );
		$mockReportedUserPageTitle->method( 'getDBkey' )
			->willReturn( strtr( $incidentReport->getReportedUser()->getName(), ' ', '_' ) );
		// Define the expected message key parameters
		$messageKeyToExpectedParameters = [
			'emailsender' => [],
			'reportincident-email-subject' => [
				new ScalarParam( ParamType::TEXT, $mockReportingUserPageTitle->getPrefixedDBkey() )
			],
			'reportincident-email-something-else' => [
				new ScalarParam( ParamType::TEXT, $incidentReport->getSomethingElseDetails() )
			],
			'reportincident-email-body' => [
				new ScalarParam( ParamType::TEXT, $mockReportingUserPageTitle->getDBkey() ),
				new ScalarParam( ParamType::TEXT, $mockReportedUserPageTitle->getDBkey() ),
				new ScalarParam( ParamType::TEXT, 'revision-link?oldid=1' ),
				new ScalarParam( ParamType::TEXT, implode( ', ', [ 'reportincident-email-something-else', 'foo' ] ) ),
				new ScalarParam( ParamType::TEXT, $incidentReport->getDetails() ),
				new ScalarParam( ParamType::TEXT, 'special-email-link' ),
			],
		];
		// Mock the TextFormatter to define ::format
		$textFormatter = $this->createMock( TextFormatter::class );
		$textFormatter->method( 'format' )
			->willReturnCallback( function ( MessageValue $messageValue ) use ( $messageKeyToExpectedParameters ) {
				// Validate the parameters
				$this->assertArrayEquals(
					$messageKeyToExpectedParameters[$messageValue->getKey()],
					$messageValue->getParams(),
					true,
					true,
					"Parameters for message {$messageValue->getKey()} not as expected."
				);
				// Mock formatted text value by returning the key
				// as this will identify the string by it's key which
				// should be unique to all other strings in the response.
				return $messageValue->getKey();
			} );
		$titleFactory = $this->createMock( TitleFactory::class );
		// Mock the Special:Email Title object
		$mockSpecialEmailTitle = $this->createMock( Title::class );
		$mockSpecialEmailTitle
			->expects( $this->once() )
			->method( 'getSubpage' )
			->willReturnSelf();
		$mockSpecialEmailTitle
			->expects( $this->once() )
			->method( 'getFullURL' )
			->willReturn( 'special-email-link' );
		// Mock the TitleFactory::newFromText method to return the associated
		// title objects in the order they are called.
		$titleFactory->method( 'newFromText' )
			->withConsecutive(
				[ $incidentReport->getReportingUser()->getName(), NS_USER ],
				[ $incidentReport->getReportedUser()->getName(), NS_USER ],
				[ 'Special:EmailUser' ]
			)
			->willReturnOnConsecutiveCalls(
				$mockReportingUserPageTitle, $mockReportedUserPageTitle, $mockSpecialEmailTitle
			);
		// Mock the UrlUtils::expand method to return a pre-defined string for ease of testing.
		$urlUtils = $this->createMock( UrlUtils::class );
		$urlUtils->method( 'expand' )
			->with( wfScript() )
			->willReturn( 'revision-link' );
		$emailer = $this->createMock( IEmailer::class );
		$emailer->method( 'send' )
			->with(
				// The values passed here will be the message keys
				// as the mock implementation of TextFormatter::format
				// returns the message key.
				[ new MailAddress( 'a@b.com' ) ],
				new MailAddress( 'test@test.com', 'emailsender' ),
				'reportincident-email-subject',
				'reportincident-email-body'
			)->willReturn( StatusValue::newGood( 'test-value' ) );
		$reportIncidentMailer = $this->getReportIncidentMailer(
			[
				'ReportIncidentRecipientEmails' => [ 'a@b.com' ],
				'ReportIncidentEmailFromAddress' => 'test@test.com',
			],
			$urlUtils,
			$emailer,
			$titleFactory,
			$textFormatter
		);
		// ::sendEmail returns a ReportIncidentEmailStatus which should
		// have been merged with the status returned by IEmailer::send.
		$sendEmailStatus = $reportIncidentMailer->sendEmail( $incidentReport );
		$this->assertStatusGood( $sendEmailStatus, '::sendEmail should return a good status' );
		$this->assertStatusValue(
			'test-value', $sendEmailStatus,
			'::sendEmail should return a status that has the value in the status returned from IEmailer::send.'
		);
		$this->assertArrayEquals(
			[
				'to' => [ new MailAddress( 'a@b.com' ) ],
				'from' => new MailAddress( 'test@test.com', 'emailsender' ),
				'subject' => 'reportincident-email-subject',
				'body' => 'reportincident-email-body'
			],
			$sendEmailStatus->getEmailContents(),
			true,
			true,
			'The status returned by ::sendEmail was not as expected.'
		);
	}
}
