<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit\Api\Rest\Handler;

use HashConfig;
use MediaWiki\Block\AbstractBlock;
use MediaWiki\Extension\ReportIncident\Api\Rest\Handler\ReportHandler;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Permissions\RateLimiter;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\Rest\Validator\UnsupportedContentTypeBodyValidator;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserNameUtils;
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
	use MockServiceDependenciesTrait;

	public function testDenyAnonymousUsers() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [ 'config' => $config ] );
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

	public function testDenyUsersWithNoEdits() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
		] );

		$userFactory = $this->createMock( UserFactory::class );
		$reportingUser = $this->createMock( User::class );
		$reportingUser->method( 'isRegistered' )->willReturn( true );
		$reportingUser->method( 'getEditCount' )->willReturn( 0 );
		$userFactory->method( 'newFromUserIdentity' )->willReturn( $reportingUser );

		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [
			'config' => $config,
			'userFactory' => $userFactory,
		] );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'apierror-permissiondenied' ), 403 )
		);
		$authority = $this->newUserAuthority( [
			'actor' => $reportingUser,
		] );
		$this->executeHandler(
			$handler,
			new RequestData( [ 'headers' => [ 'Content-Type' => 'application/json' ] ] ),
			[],
			[],
			[],
			[],
			$authority
		);
	}

	public function testDenyUserWithBlock() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
		] );

		$userFactory = $this->createMock( UserFactory::class );
		$reportingUser = $this->createMock( User::class );
		$reportingUser->method( 'isRegistered' )->willReturn( true );
		$reportingUser->method( 'getBlock' )->willReturn( $this->createMock( AbstractBlock::class ) );
		$userFactory->method( 'newFromUserIdentity' )->willReturn( $reportingUser );

		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [
			'config' => $config,
			'userFactory' => $userFactory,
		] );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'apierror-blocked' ), 403 )
		);
		$authority = $this->newUserAuthority( [
			'actor' => $reportingUser,
		] );
		$this->executeHandler(
			$handler,
			new RequestData( [ 'headers' => [ 'Content-Type' => 'application/json' ] ] ),
			[],
			[],
			[],
			[],
			$authority
		);
	}

	public function testConfigDisabled() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => false ] );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [ 'config' => $config ] );
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
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionStore->method( 'getRevisionById' )
			->with( 1 )
			->willReturn( null );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class,
			[ 'config' => $config, 'revisionStore' => $revisionStore ]
		);
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

	public function testDenyWithoutConfirmedEmail() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => false,
		] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionStore->method( 'getRevisionById' )->willReturn( null );
		$userFactory = $this->createMock( UserFactory::class );
		$user = $this->createMock( User::class );
		$user->method( 'isEmailConfirmed' )->willReturn( false );
		$userFactory->method( 'newFromUserIdentity' )->willReturn( $user );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class,
			[
				'config' => $config,
				'revisionStore' => $revisionStore,
				'userFactory' => $userFactory,
			]
		);
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'reportincident-confirmedemail-required' ), 403 )
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

	public function testAllowWithoutConfirmedEmailWhenDeveloperMode() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionStore->method( 'getRevisionById' )->willReturn( $this->createMock( RevisionRecord::class ) );
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$reportManager->method( 'record' )->willReturn( StatusValue::newGood() );
		$reportManager->method( 'notify' )->willReturn( StatusValue::newGood() );
		$userFactory = $this->createMock( UserFactory::class );
		$userIdentityLookup = $this->createMock( UserIdentityLookup::class );
		$registeredUserMock = $this->createMock( User::class );
		$registeredUserMock->method( 'isRegistered' )->willReturn( true );
		$userIdentityLookup->method( 'getUserIdentityByName' )->willReturn( $registeredUserMock );
		$user = $this->createMock( User::class );
		$user->method( 'isEmailConfirmed' )->willReturn( false );
		$userFactory->method( 'newFromUserIdentity' )->willReturn( $user );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class,
			[
				'config' => $config,
				'revisionStore' => $revisionStore,
				'userFactory' => $userFactory,
				'reportIncidentManager' => $reportManager,
				'userIdentityLookup' => $userIdentityLookup
			]
		);
		$response = $this->executeHandler(
			$handler,
			new RequestData(),
			[],
			[],
			[],
			[
				'reportedUser' => 'Foo',
				'revisionId' => 1,
				'behaviors' => [ 'something', 'something_else' ],
				'details' => 'More details'
			],
			$this->mockRegisteredUltimateAuthority()
		);
		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testGetBodyValidatorInvalidContentType() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [ 'config' => $config ] );
		$this->assertInstanceOf(
			UnsupportedContentTypeBodyValidator::class,
			$handler->getBodyValidator( 'application/text' )
		);
	}

	public function testGetBodyValidator() {
		$config = new HashConfig( [ 'ReportIncidentApiEnabled' => true ] );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [ 'config' => $config ] );
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
		// Mock the config to enable the API
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		// Mock the return value of ReportIncidentManager::record and ::notify
		$reportManager = $this->createMock( ReportIncidentManager::class );
		$reportManager->method( 'record' )->willReturn( $recordStatus );
		$reportManager->method( 'notify' )->willReturn( $notifyStatus );
		// Mock the result of RevisionStore::getRevisionById
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionRecord = $this->createMock( RevisionRecord::class );
		$revisionRecord->method( 'getId' )->willReturn( $data['revisionId'] );
		$revisionStore->method( 'getRevisionById' )
			->with( $data['revisionId'] )
			->willReturn( $revisionRecord );
		// Mock the result of UserIdentityLookup::getUserIdentityByName to always return
		// an existing user.
		$userIdentityLookup = $this->createMock( UserIdentityLookup::class );
		$userIdentity = $this->createMock( UserIdentity::class );
		$userIdentity->method( 'isRegistered' )->willReturn( true );
		$userIdentityLookup->method( 'getUserIdentityByName' )
			->with( $data['reportedUser'] )
			->willReturn( $userIdentity );
		// Disable the constructor and allow calling UserNameUtils::isIP which
		// does not rely on the object properties.
		$userNameUtils = $this->getMockBuilder( UserNameUtils::class )
			->disableOriginalConstructor()
			->onlyMethods( [] )
			->getMock();
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class,
			[
				'config' => $config,
				'reportIncidentManager' => $reportManager,
				'revisionStore' => $revisionStore,
				'userIdentityLookup' => $userIdentityLookup,
				'userNameUtils' => $userNameUtils,
			]
		);
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
			$this->mockRegisteredAuthorityWithPermissions( [ 'reportincident' ] )
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
					'reportedUser' => 'test',
					'revisionId' => 1,
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
					'reportedUser' => '1.2.3.4',
					'revisionId' => 1,
					'behaviors' => [ 'something', 'something_else' ],
				],
				StatusValue::newGood(),
				StatusValue::newGood(),
				false,
				false,
			],
			'trigger a TypeError' => [
				[
					'reportedUser' => 'testing12234',
					'revisionId' => 'foo',
					'behaviors' => 3
				],
				StatusValue::newFatal( 'rest-bad-json-body' ),
				StatusValue::newGood(),
				true,
				false,
			],
			'record fails' => [
				[
					'reportedUser' => 'Userabc',
					'revisionId' => 1,
					'behaviors' => [ 'test' ]
				],
				StatusValue::newFatal( 'rest-bad-json-body' ),
				StatusValue::newGood(),
				true,
				false,
			],
			'notify fails' => [
				[
					'reportedUser' => 'testuser',
					'revisionId' => 1,
					'behaviors' => [ 'test' ],
				],
				StatusValue::newGood(),
				StatusValue::newFatal( 'reportincident-unable-to-send' ),
				false,
				true,
			]
		];
	}

	public function testRateLimitTrip() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		$user = $this->createMock( User::class );
		$user->method( 'isRegistered' )->willReturn( true );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [ 'config' => $config ] );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'apierror-ratelimited' ), 429 )
		);
		$rateLimiter = $this->createMock( RateLimiter::class );
		$rateLimiter->method( 'limit' )->willReturn( true );
		$rateLimiter->method( 'isLimitable' )->with( 'reportincident' )->willReturn( true );
		$authority = $this->newUserAuthority( [
			'actor' => $user,
			'rateLimiter' => $rateLimiter,
			'permissions' => [ 'reportincident' ]
		] );
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

	public function testActionUnauthorized() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class, [ 'config' => $config ] );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'apierror-permissiondenied' ), 403 )
		);
		$authority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'reportincident' ] );
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

	public function testInvalidReportedUsername() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionStore->method( 'getRevisionById' )
			->willReturn( $this->createMock( RevisionRecord::class ) );
		// Mock that UserNameUtils::isIP returns false.
		$userNameUtils = $this->createMock( UserNameUtils::class );
		$userNameUtils->method( 'isIP' )->willReturn( false );
		// Mock that UserIdentityLookup::getUserIdentityByName returns null.
		$userIdentityLookup = $this->createMock( UserIdentityLookup::class );
		$userIdentityLookup->expects( $this->once() )
			->method( 'getUserIdentityByName' )
			->with( 'testing' )
			->willReturn( null );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class,
			[
				'config' => $config,
				'revisionStore' => $revisionStore,
				'userIdentityLookup' => $userIdentityLookup,
				'userNameUtils' => $userNameUtils
			]
		);
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-nonexistent-user' ), 404 )
		);
		$this->executeHandler(
			$handler,
			new RequestData( [ 'headers' => [ 'Content-Type' => 'application/json' ] ] ),
			[],
			[],
			[],
			[ 'revisionId' => 1, 'reportedUser' => 'testing' ],
			$this->mockRegisteredUltimateAuthority()
		);
	}

	public function testNonExistingUsername() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		$revisionStore = $this->createMock( RevisionStore::class );
		$revisionStore->method( 'getRevisionById' )
			->willReturn( $this->createMock( RevisionRecord::class ) );
		// Mock that UserNameUtils::isIP returns false.
		$userNameUtils = $this->createMock( UserNameUtils::class );
		$userNameUtils->method( 'isIP' )->willReturn( false );
		// Mock that UserIdentityLookup::getUserIdentityByName returns a UserIdentity for an unregistered user.
		$userIdentityLookup = $this->createMock( UserIdentityLookup::class );
		$userIdentityLookup->expects( $this->once() )
			->method( 'getUserIdentityByName' )
			->with( 'testing' )
			->willReturn( null );
		/** @var ReportHandler $handler */
		$handler = $this->newServiceInstance( ReportHandler::class,
			[
				'config' => $config,
				'revisionStore' => $revisionStore,
				'userIdentityLookup' => $userIdentityLookup,
				'userNameUtils' => $userNameUtils,
			]
		);
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-nonexistent-user' ), 404 )
		);
		$this->executeHandler(
			$handler,
			new RequestData( [ 'headers' => [ 'Content-Type' => 'application/json' ] ] ),
			[],
			[],
			[],
			[ 'revisionId' => 1, 'reportedUser' => 'testing' ],
			$this->mockRegisteredUltimateAuthority()
		);
	}

	public function testBodyFailedValidationButStillRan() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );
		$handler = $this->getMockBuilder( ReportHandler::class )
			->setConstructorArgs( [
				$config,
				$this->createMock( RevisionStore::class ),
				$this->createMock( UserNameUtils::class ),
				$this->createMock( UserIdentityLookup::class ),
				$this->createMock( ReportIncidentManager::class ),
				$this->createMock( UserFactory::class )
			] )
			->onlyMethods( [ 'getValidatedBody' ] )
			->getMock();
		$handler->method( 'getValidatedBody' )
			->willReturn( null );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-bad-json-body' ), 400 )
		);
		$this->executeHandler(
			$handler, new RequestData( [ 'headers' => [ 'Content-Type' => 'application/json' ] ] ), [], [], [],
			[ 'revisionId' => 1, 'reportedUser' => 'testing' ],
			$this->mockRegisteredUltimateAuthority()
		);
	}

	public function testBodyFailsValidationOnFormDataSubmitted() {
		$config = new HashConfig( [
			'ReportIncidentApiEnabled' => true,
			'ReportIncidentDeveloperMode' => true,
		] );

		$handler = $this->getMockBuilder( ReportHandler::class )
			->setConstructorArgs( [
				$config,
				$this->createMock( RevisionStore::class ),
				$this->createMock( UserNameUtils::class ),
				$this->createMock( UserIdentityLookup::class ),
				$this->createMock( ReportIncidentManager::class ),
				$this->createMock( UserFactory::class )
			] )
			->onlyMethods( [ 'getValidatedBody' ] )
			->getMock();
		$handler->method( 'getValidatedBody' )
			->willReturn( null );
		$this->expectExceptionObject(
			new LocalizedHttpException( new MessageValue( 'rest-unsupported-content-type' ), 415 )
		);
		$this->executeHandler(
			$handler, new RequestData( [ 'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ] ] ),
			[], [], [], [ 'revisionId' => 1, 'reportedUser' => 'testing' ],
			$this->mockRegisteredUltimateAuthority()
		);
	}
}
