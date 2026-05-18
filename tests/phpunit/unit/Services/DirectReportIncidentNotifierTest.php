<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ReportIncident\Tests\Unit\Services;

use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\DirectReportIncidentNotifier;
use MediaWiki\Mail\IEmailer;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\Utils\UrlUtils;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use StatusValue;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\Message\MessageSpecifier;

/**
 * @covers \MediaWiki\Extension\ReportIncident\Services\DirectReportIncidentNotifier
 */
class DirectReportIncidentNotifierTest extends MediaWikiUnitTestCase {
	private ITextFormatter $textFormatter;
	private UrlUtils $urlUtils;
	private TitleFactory $titleFactory;
	private LoggerInterface $logger;
	private MediaWikiConfigReader $communityConfigReader;
	private IEmailer $emailer;

	private DirectReportIncidentNotifier $directReportIncidentNotifier;

	protected function setUp(): void {
		$this->textFormatter = $this->createMock( ITextFormatter::class );
		$this->urlUtils = $this->createMock( UrlUtils::class );
		$this->logger = $this->createMock( LoggerInterface::class );
		$this->emailer = $this->createMock( IEmailer::class );

		$this->communityConfigReader = $this->createMock( MediaWikiConfigReader::class );
		$this->communityConfigReader->method( 'get' )
		->willReturnMap( [
			[ 'ReportIncident_NonEmergency_Spam_HelpMethod', (object)[ 'Email' => '' ] ],
			[ 'ReportIncident_NonEmergency_Doxing_HelpMethod', (object)[ 'Email' => 'bar@example.com' ] ]
		] );

		$this->titleFactory = $this->createMock( TitleFactory::class );
		$specialEmailUserTitle = $this->createMock( Title::class );
		$specialEmailUserTitle->method( 'getFullURL' )
			->willReturn( 'Special:EmailUser?wpTarget=Reporter' );
		$this->titleFactory->method( 'newFromTextThrow' )
			->with( 'EmailUser', NS_SPECIAL )
			->willReturn( $specialEmailUserTitle );

		$formatter = new class implements ITextFormatter {
			public function getLangCode(): string {
				return 'qqx';
			}

			public function format( MessageSpecifier $message ): string {
				return $message->dump();
			}
		};

		$this->directReportIncidentNotifier = new DirectReportIncidentNotifier(
			new HashConfig( [
				'PasswordSender' => 'foo@example.com'
			] ),
			$this->communityConfigReader,
			new ServiceOptions( DirectReportIncidentNotifier::CONSTRUCTOR_OPTIONS, [
				'Script' => '/index.php'
			] ),
			$this->logger,
			$this->emailer,
			$formatter,
			$this->urlUtils,
			$this->titleFactory

		);
	}

	public function testNotify() {
		$incidentReport = $this->newIncidentReport();

		$this->emailer->expects( $this->once() )
			->method( 'send' )
			->willReturnCallback( function ( $sendTo, $sender, $subject, $body ) {
				$this->assertSame( 'bar@example.com', $sendTo->address );
				$this->assertStringContainsString( 'foo@example.com', $sender->toString() );
				$expectedBody = '<message key="reportincident-directreport-email-body">' .
				'<text>Reporter</text><text>Reported</text>' .
				'<text><message key="reportincident-notification-link-to-page-prefix"></message></text>' .
				'<text></text>' .
				'<text><message key="reportincident-dialog-harassment-type-doxing"></message></text>' .
				'<text>Direct report</text><text>Special:EmailUser?wpTarget=Reporter</text>' .
				'</message>';

				$this->assertSame( '<message key="reportincident-directreport-email-subject"></message>', $subject );
				$this->assertSame( $expectedBody, $body );
				return StatusValue::newGood();
			} );
		$this->logger->expects( $this->once() )
			->method( 'info' )
			->with( 'Direct report sent' );
		$status = $this->directReportIncidentNotifier->notify( $incidentReport );
		$this->assertStatusGood( $status );
	}

	public function testNotifyNoSendToEmailConfigured() {
		$incidentReport = $this->newIncidentReport( [
			'behaviorType' => 'spam'
		] );

		$this->logger->expects( $this->once() )
			->method( 'error' )
			->with( 'Direct report failed to send, no email configured' );
		$status = $this->directReportIncidentNotifier->notify( $incidentReport );
		$this->assertStatusNotGood( $status );
	}

	public function testNotifyFailedSend() {
		$incidentReport = $this->newIncidentReport( [
			'behaviorType' => 'doxing'
		] );

		$this->emailer->method( 'send' )
			->willReturn( StatusValue::newFatal( 'foo' ) );

		$this->logger->expects( $this->once() )
			->method( 'error' )
			->with( 'Direct report failed to send, mailer failed' );
		$status = $this->directReportIncidentNotifier->notify( $incidentReport );
		$this->assertStatusNotGood( $status );
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
			$overrides['incidentType'] ?? IncidentReport::THREAT_TYPE_UNACCEPTABLE_BEHAVIOR,
			$overrides['behaviorType'] ?? 'doxing',
			$overrides['physicalHarmType'] ?? null,
			$overrides['somethingElseDetails'] ?? null,
			$overrides['details'] ?? null,
			$overrides['threadId'] ?? null,
			$overrides['directReport'] ?? 'Direct report'
		);
	}
}
