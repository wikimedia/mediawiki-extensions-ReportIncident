<?php
namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ZendeskClient;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\Utils\UrlUtils;
use MediaWikiUnitTestCase;
use MockHttpTrait;
use MWHttpRequest;
use Psr\Log\LoggerInterface;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\Message\MessageSpecifier;

/**
 * @covers \MediaWiki\Extension\ReportIncident\Services\ZendeskClient
 */
class ZendeskClientTest extends MediaWikiUnitTestCase {
	use MockHttpTrait;

	private HttpRequestFactory $httpRequestFactory;
	private ITextFormatter $textFormatter;
	private UrlUtils $urlUtils;
	private UserFactory $userFactory;
	private LoggerInterface $logger;

	private ZendeskClient $zendeskClient;

	protected function setUp(): void {
		$this->httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$this->textFormatter = $this->createMock( ITextFormatter::class );
		$this->urlUtils = $this->createMock( UrlUtils::class );
		$this->userFactory = $this->createMock( UserFactory::class );
		$this->logger = $this->createMock( LoggerInterface::class );

		$formatter = new class implements ITextFormatter {
			public function getLangCode(): string {
				return 'qqx';
			}

			public function format( MessageSpecifier $message ): string {
				return $message->dump();
			}
		};

		$this->zendeskClient = new ZendeskClient(
			$this->httpRequestFactory,
			$formatter,
			$this->urlUtils,
			$this->userFactory,
			$this->logger,
			new ServiceOptions( ZendeskClient::CONSTRUCTOR_OPTIONS, [
				'ReportIncidentZendeskHTTPProxy' => 'foo',
				'ReportIncidentZendeskUrl' => 'https://zendesk.example.com',
				'ReportIncidentZendeskSubjectLine' => 'Test subject line',
				'Script' => '/index.php'
			] )
		);
	}

	/**
	 * @dataProvider provideReportData
	 */
	public function testShouldCreateRequest( ?string $threadId, string $expectedPageLink ): void {
		$mockRevisionRecord = $this->createMock( RevisionRecord::class );
		$mockRevisionRecord->method( 'getId' )
			->willReturn( 1 );

		$incidentReport = new IncidentReport(
			new UserIdentityValue( 1, 'Reporter' ),
			new UserIdentityValue( 2, 'Reported' ),
			$mockRevisionRecord,
			IncidentReport::THREAT_TYPE_IMMEDIATE,
			null,
			'threats-physical-harm',
			null,
			'Details',
			$threadId
		);

		$fullReportingUser = $this->createMock( User::class );
		$fullReportingUser->method( 'getEmail' )
			->willReturn( 'reportinguser@example.com' );

		$this->userFactory->method( 'newFromUserIdentity' )
			->with( $incidentReport->getReportingUser() )
			->willReturn( $fullReportingUser );

		$this->urlUtils->method( 'expand' )
			->with( '/index.php' )
			->willReturn( 'page-link' );

		$expectedPayload = json_encode( [
			'request' => [
				'requester' => [
					'name' => 'Reporter',
					'email' => 'reportinguser@example.com',
				],

				'subject' => 'Test subject line',
				'comment' => [
					'body' => '<message key="reportincident-notification-message-body"><text>Reporter</text>' .
						'<text>Reported</text>' . $expectedPageLink .
						'<text><message key="reportincident-threats-physical-harm"></message></text>' .
						'<text>Details</text></message>',
				],
			],
		] );

		$this->httpRequestFactory->method( 'create' )
			->willReturnCallback( function ( string $url, array $options ) use ( $expectedPayload ): MWHttpRequest {
				$this->assertSame( 'https://zendesk.example.com/api/v2/requests.json', $url );
				$this->assertSame( 'POST', $options['method'] );
				$this->assertSame( 'foo', $options['proxy'] );
				$this->assertJsonStringEqualsJsonString( $expectedPayload, $options['postData'] );

				return $this->makeFakeHttpRequest();
			} );

		$this->logger->expects( $this->once() )
			->method( 'info' )
			->with( 'Zendesk request created' );

		$status = $this->zendeskClient->notify( $incidentReport );

		$this->assertStatusGood( $status );
	}

	public static function provideReportData(): iterable {
		yield 'no thread ID' => [
			null,
			'<text><message key="reportincident-notification-link-to-page-prefix"></message></text>' .
			'<text>page-link?oldid=1</text>'
		];

		yield 'thread ID for comment' => [
			'c-testuser-20230504030201',
			'<text><message key="reportincident-notification-link-to-comment-prefix"></message></text>' .
			'<text>page-link?oldid=1#c-testuser-20230504030201</text>'
		];

		yield 'thread ID for topic' => [
			'h-testuser-20230504030201',
			'<text><message key="reportincident-notification-link-to-topic-prefix"></message></text>' .
			'<text>page-link?oldid=1#h-testuser-20230504030201</text>'
		];
	}

	public function testShouldLogUnknownZendeskError(): void {
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

		$fullReportingUser = $this->createMock( User::class );
		$fullReportingUser->method( 'getEmail' )
			->willReturn( 'reportinguser@example.com' );

		$this->userFactory->method( 'newFromUserIdentity' )
			->with( $incidentReport->getReportingUser() )
			->willReturn( $fullReportingUser );

		$this->urlUtils->method( 'expand' )
			->with( '/index.php' )
			->willReturn( 'page-link' );

		$this->httpRequestFactory->method( 'create' )
			->willReturn( $this->makeFakeHttpRequest( 'some error', 500 ) );

		$this->logger->expects( $this->once() )
			->method( 'error' )
			->with( 'Unknown Zendesk error while creating request', [ 'status' => 500 ] );

		$status = $this->zendeskClient->notify( $incidentReport );

		$this->assertStatusNotGood( $status );
	}

	public function testShouldLogFormattedZendeskError(): void {
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

		$fullReportingUser = $this->createMock( User::class );
		$fullReportingUser->method( 'getEmail' )
			->willReturn( 'reportinguser@example.com' );

		$this->userFactory->method( 'newFromUserIdentity' )
			->with( $incidentReport->getReportingUser() )
			->willReturn( $fullReportingUser );

		$this->urlUtils->method( 'expand' )
			->with( '/index.php' )
			->willReturn( 'page-link' );

		$zdError = json_encode( [
			'details' => [],
			'description' => 'RecordValidation errors',
			'error' => 'RecordInvalid'
		] );

		$this->httpRequestFactory->method( 'create' )
			->willReturn(
				$this->makeFakeHttpRequest( $zdError, 400, [
					'Content-Type' => 'application/json'
				] )
			);

		$this->logger->expects( $this->once() )
			->method( 'error' )
			->with(
				'Zendesk error while creating request: "RecordInvalid" (RecordValidation errors)',
				[ 'status' => 400 ]
			);

		$status = $this->zendeskClient->notify( $incidentReport );

		$this->assertStatusNotGood( $status );
	}
}
