<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use HashConfig;
use MediaWiki\Extension\ReportIncident\Api\Rest\Handler\ReportHandler;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\Rest\Validator\UnsupportedContentTypeBodyValidator;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;
use StatusValue;
use Wikimedia\Message\MessageValue;

/**
 * @group ReportIncident
 *
 * @covers MediaWiki\Extension\ReportIncident\Api\Rest\Handler\ReportHandler
 */
class ReportHandlerTest extends MediaWikiUnitTestCase {

	use MockAuthorityTrait;
	use HandlerTestTrait;

	public function testDenyAnonymousUsers() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$userFactory = $this->createMock( UserFactory::class );
		$handler = new ReportHandler( $config, $revisionStore, $userFactory, $reportManager );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-permission-denied-anon' ), 401 )
		);
		$authority = $this->mockAnonUltimateAuthority();
		$this->executeHandler(
			$handler,
			new RequestData( [ 'headers' => [ 'Content-Type' => 'application/json' ] ] ),
			[],
			[],
			[],
			[ 'revisionId' => 1 ],
			$authority
		);
	}

	public function testConfigDisabled() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => false ] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$userFactory = $this->createMock( UserFactory::class );
		$handler = new ReportHandler( $config, $revisionStore, $userFactory, $reportManager );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-no-match' ), 404 )
		);
		$this->executeHandler(
			$handler,
			new RequestData(),
			[],
			[],
			[ 'revisionId' => 1 ],
		);
	}

	public function testRevisionDoesntExist() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionStore->method( 'getRevisionById' )->willReturn( null );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$userFactory = $this->createMock( UserFactory::class );
		$handler = new ReportHandler( $config, $revisionStore, $userFactory, $reportManager );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-nonexistent-revision' ), 404 )
		);
		$this->executeHandler(
			$handler,
			new RequestData(),
			[],
			[],
			[],
			[ 'revisionId' => 1 ],
			$this->mockRegisteredUltimateAuthority()
		);
	}

	public function testGetBodyValidatorInvalidContentType() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$revisionStore = $this->createMock( RevisionStore::class );
		$userFactory = $this->createMock( UserFactory::class );
		$handler = new ReportHandler( $config, $revisionStore, $userFactory, $reportManager );
		$this->assertInstanceOf(
			UnsupportedContentTypeBodyValidator::class,
			$handler->getBodyValidator( 'application/text' )
		);
	}

	public function testGetBodyValidator() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$revisionStore = $this->createMock( RevisionStore::class );
		$userFactory = $this->createMock( UserFactory::class );
		$handler = new ReportHandler( $config, $revisionStore, $userFactory, $reportManager );
		$validator = $handler->getBodyValidator( 'application/json' );
		$this->assertInstanceOf( JsonBodyValidator::class, $validator );
	}

	/**
	 * @dataProvider provideTestRestPayload
	 */
	public function testRestPayload(
		array $data,
		StatusValue $recordStatus,
		StatusValue $notifyStatus,
		bool $expectRecordException,
		bool $expectNotifyException
	) {
		if ( is_int( $data['revisionId'] ) ) {
			$revisionRecord = $this->createMock( RevisionRecord::class );
			$revisionRecord->method( 'getId' )->willReturn( $data['revisionId'] );
			$data['revision'] = $revisionRecord;
		}
		if ( is_int( $data['reportedUserId'] ) ) {
			$userIdentityValue = $this->createMock( UserIdentityValue::class );
			$userIdentityValue->method( 'getId' )->willReturn( $data['reportedUserId'] );
			$data['reportedUser'] = $userIdentityValue;
		}

		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$reportManager->method( 'record' )->willReturn( $recordStatus );
		$reportManager->method( 'notify' )->willReturn( $notifyStatus );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionRecord = $this->createMock( RevisionRecord::class );
		$revisionStore->method( 'getRevisionById' )->willReturn( $revisionRecord );
		$userFactory = $this->createMock( UserFactory::class );
		$handler = new ReportHandler( $config, $revisionStore, $userFactory, $reportManager );
		if ( $expectRecordException ) {
			$this->expectExceptionObject(
				new LocalizedHttpException( new MessageValue( $recordStatus->getErrors()[0]['message'] ), 400 )
			);
		} elseif ( $expectNotifyException ) {
			$this->expectExceptionObject(
				new LocalizedHttpException( new MessageValue( $notifyStatus->getErrors()[0]['message'] ), 500 )
			);
		}
		$response = $this->executeHandler(
			$handler,
			new RequestData( [] ),
			[],
			[],
			[],
			$data,
			$this->mockRegisteredUltimateAuthority()
		);
		$this->assertSame( 204, $response->getStatusCode() );
	}

	/**
	 * Data provider for ::testRestPayload
	 */
	public static function provideTestRestPayload(): array {
		return [
			'correct values' => [
				[
					'reportedUserId' => 2,
					'revisionId' => 1,
					'link' => 'https://foo.bar',
					'behaviors' => [ 'something', 'something_else' ],
					'details' => 'More details'
				],
				StatusValue::newGood(),
				StatusValue::newGood(),
				false,
				false,
			],
			'correct values, empty details' => [
				[
					'reportedUserId' => 2,
					'revisionId' => 1,
					'link' => 'https://foo.bar',
					'behaviors' => [ 'something', 'something_else' ],
				],
				StatusValue::newGood(),
				StatusValue::newGood(),
				false,
				false,
			],
			'trigger a TypeError' => [
				[
					'reportedUserId' => 1,
					'revisionId' => 'foo',
					'link' => 1,
					'behaviors' => 3
				],
				StatusValue::newFatal( 'rest-bad-json-body' ),
				StatusValue::newGood(),
				true,
				false,
			],
			'record fails' => [
				[
					'reportedUserId' => 1,
					'revisionId' => 1,
					'link' => 'https://foo.bar',
					'behaviors' => [ 'test' ]
				],
				StatusValue::newFatal( 'rest-bad-json-body' ),
				StatusValue::newGood(),
				true,
				false,
			],
			'notify fails' => [
				[
					'reportedUserId' => 1,
					'revisionId' => 1,
					'link' => 'https://foo.bar',
					'behaviors' => [ 'test' ],
				],
				StatusValue::newGood(),
				StatusValue::newFatal( 'reportincident-unable-to-send' ),
				false,
				true,
			]
		];
	}
}
