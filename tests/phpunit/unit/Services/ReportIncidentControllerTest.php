<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use MediaWiki\Config\HashConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\ReportIncident\Api\Rest\Handler\ReportHandler;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentController;
use MediaWiki\Output\OutputPage;
use MediaWiki\Skin\Skin;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group ReportIncident
 *
 * @covers \MediaWiki\Extension\ReportIncident\Services\ReportIncidentController
 */
class ReportIncidentControllerTest extends MediaWikiUnitTestCase {
	use MockServiceDependenciesTrait;

	private const DEFAULT_LOCAL_LINKS = [
		'ReportIncidentDisputeResolutionPage' => 'Project:Dispute resolution',
		'ReportIncidentCommunityQuestionsPage' => 'Project:Village pump',
		'ReportIncidentLocalIncidentReportPage' => 'Project:Report an incident',
	];

	private function newReportIncidentController(
		array $globalConfig,
		array $communityConfig = []
	): ReportIncidentController {
		$globalConfig += self::DEFAULT_LOCAL_LINKS;
		$globalConfig['ReportIncidentUseV2NonEmergencyFlow'] = true;
		$communityConfig += [
			'ReportIncidentLocalIncidentReportPage' => '',
			'ReportIncidentDisputeResolutionPage' => '',
			'ReportIncidentCommunityQuestionsPage' => '',
			'ReportIncidentEnabledNamespaces' => [],
			'ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL' => '',
			'ReportIncident_NonEmergency_Intimidation_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => '',
			],
			'ReportIncident_NonEmergency_Doxing_ShowWarning' => true,
			'ReportIncident_NonEmergency_Doxing_HideEditURL' => '',
			'ReportIncident_NonEmergency_Doxing_HelpMethod' => (object)[
				'WikiEmailURL' => '',
				'Email' => '',
				'OtherURL' => '',
				'EmailStewards' => false,
			],
			'ReportIncident_NonEmergency_SexualHarassment_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => '',
			],
			'ReportIncident_NonEmergency_Trolling_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => '',
			],
		];
		return new ReportIncidentController(
			new HashConfig( $globalConfig ),
			new HashConfig( $communityConfig )
		);
	}

	/** @dataProvider provideShouldShowButtonForNamespace */
	public function testShouldShowButtonForNamespace(
		$namespace,
		$supportedNamespaces,
		$communityConfigNamespaces,
		$expectedReturnValue
	) {
		$objectUnderTest = $this->newReportIncidentController(
			[ 'ReportIncidentEnabledNamespaces' => $supportedNamespaces ],
			[ 'ReportIncidentEnabledNamespaces' => $communityConfigNamespaces ]
		);
		$objectUnderTest = TestingAccessWrapper::newFromObject( $objectUnderTest );
		$this->assertSame(
			$expectedReturnValue,
			$objectUnderTest->shouldShowButtonForNamespace( $namespace ),
			'::shouldShowButtonForNamespace did not return the expected boolean.'
		);
	}

	public static function provideShouldShowButtonForNamespace() {
		return [
			'Namespace is supported' => [ 3, [ 3 ], [], true ],
			'Namespace is not supported' => [ 4, [ 3, 2 ], [], false ],
			'Namespace is set in community configuration' => [ 3, [ 4, 2 ], [ 3 ], true ],
		];
	}

	/** @dataProvider provideShouldShowButtonForUser */
	public function testShouldShowButtonForUser( $isUserNamed ) {
		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isNamed' )
			->willReturn( $isUserNamed );
		$objectUnderTest = $this->newReportIncidentController( [] );
		$objectUnderTest = TestingAccessWrapper::newFromObject( $objectUnderTest );
		$this->assertSame(
			$isUserNamed,
			$objectUnderTest->shouldShowButtonForUser( $mockUser ),
			'::shouldShowButtonForUser did not return the expected boolean.'
		);
	}

	public static function provideShouldShowButtonForUser() {
		return [
			'User is named' => [ true ],
			'User is not named' => [ false ],
		];
	}

	/** @dataProvider provideShouldAddMenuItem */
	public function testShouldAddMenuItem(
		array $config, int $namespace, ?string $skinName, bool $isUserNamed, bool $expectedReturnResult
	) {
		// Mock the IContextSource that is passed to the ::shouldAddMenuItem method
		$mockContext = $this->createMock( IContextSource::class );
		// Mock the getUser method.
		$userMock = $this->createMock( User::class );
		$userMock->method( 'isNamed' )->willReturn( $isUserNamed );
		$mockContext->method( 'getUser' )->willReturn( $userMock );
		// Mock the namespace
		$mockTitle = $this->createMock( Title::class );
		$mockTitle->method( 'getNamespace' )->willReturn( $namespace );
		$mockContext->method( 'getTitle' )->willReturn( $mockTitle );
		// Mock the skin name
		$mockSkin = $this->createMock( Skin::class );
		$mockSkin->method( 'getSkinName' )->willReturn( $skinName );
		$mockContext->method( 'getSkin' )->willReturn( $mockSkin );
		// Get the object under test
		/** @var ReportIncidentController $objectUnderTest */
		$objectUnderTest = $this->newReportIncidentController( $config );
		$this->assertSame(
			$expectedReturnResult,
			$objectUnderTest->shouldAddMenuItem( $mockContext ),
			'::shouldShowButtonForUser did not return the expected boolean.'
		);
	}

	public static function provideShouldAddMenuItem() {
		return [
			'All checks should fail' => [
				[
					'ReportIncidentReportButtonEnabled' => false,
					'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
					'ReportIncidentEnableInstrumentation' => true,
				],
				NS_TEMPLATE,
				'minerva',
				false,
				false,
			],
			'Feature flag disabled' => [
				[
					'ReportIncidentReportButtonEnabled' => false,
					'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
					'ReportIncidentEnableInstrumentation' => true,
				],
				NS_USER_TALK,
				'vector',
				true,
				false,
			],
			'Unsupported namespace' => [
				[
					'ReportIncidentReportButtonEnabled' => true,
					'ReportIncident' => [ 'vector' ],
					'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
					'ReportIncidentEnableInstrumentation' => true,
				],
				NS_TEMPLATE,
				'vector',
				true,
				false,
			],
			'User is not named' => [
				[
					'ReportIncidentReportButtonEnabled' => true,
					'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
					'ReportIncidentEnableInstrumentation' => true,
				],
				NS_USER_TALK,
				'vector',
				false,
				false,
			],
			'All checks should pass' => [
				[
					'ReportIncidentReportButtonEnabled' => true,
					'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
					'ReportIncidentEnableInstrumentation' => true,
				],
				NS_USER_TALK,
				'vector',
				true,
				true,
			],
		];
	}

	public function testAddModulesAndConfigVarsForMinerva() {
		$outputPageMock = $this->createMock( OutputPage::class );
		$userMock = $this->createMock( User::class );
		$userMock->method( 'isEmailConfirmed' )->willReturn( true );
		$outputPageMock->method( 'getUser' )->willReturn( $userMock );
		$outputPageMock->expects( $this->once() )->method( 'addJsConfigVars' )
			->with( [
				'wgReportIncidentUserHasConfirmedEmail' => true,
				'wgReportIncidentLocalLinks' => [
					'disputeResolution' => 'Project:Dispute resolution',
					'localIncidentReport' => 'Project:Report an incident',
					'askTheCommunity' => 'Project:Village pump',
				],
				'wgReportIncidentEnableInstrumentation' => true,
				'wgReportIncidentDetailsCodePointLength' => ReportHandler::MAX_DETAILS_LENGTH,
				'wgReportIncidentUserHasEmail' => false,
				'wgReportIncidentUseV2NonEmergencyFlow' => true,
				'wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity' => '',
				'wgReportIncidentNonEmergencyDoxingShowWarning' => true,
				'wgReportIncidentNonEmergencyDoxingHideEditURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodWikiEmailURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodOtherURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodEmailStewards' => false,
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity' => '',
			] );
		$outputPageMock->expects( $this->once() )->method( 'addModules' )
			->with( 'ext.reportIncident' );
		$outputPageMock->expects( $this->once() )->method( 'addModuleStyles' )
			->with( 'ext.reportIncident.menuStyles' );
		// Mock the methods used to get the current skin name to return "minerva"
		$mockSkin = $this->createMock( Skin::class );
		$mockSkin->method( 'getSkinName' )
			->willReturn( 'minerva' );
		$outputPageMock->expects( $this->once() )->method( 'getSkin' )
			->willReturn( $mockSkin );
		/** @var ReportIncidentController $objectUnderTest */
		$objectUnderTest = $this->newReportIncidentController( [
			'ReportIncidentDeveloperMode' => false,
			'ReportIncidentEnableInstrumentation' => true,
		] );
		$objectUnderTest->addModulesAndConfigVars( $outputPageMock );
	}

	/**
	 * @dataProvider provideConfigVarsData
	 */
	public function testAddModulesAndConfigVars(
		array $globalConfig,
		array $communityConfig,
		array $expectedLocalLinks
	): void {
		$outputPageMock = $this->createMock( OutputPage::class );
		$userMock = $this->createMock( User::class );
		$userMock->method( 'isEmailConfirmed' )->willReturn( true );
		$outputPageMock->method( 'getUser' )->willReturn( $userMock );
		$outputPageMock->expects( $this->once() )->method( 'addJsConfigVars' )
			->with( [
				'wgReportIncidentUserHasConfirmedEmail' => true,
				'wgReportIncidentLocalLinks' => $expectedLocalLinks,
				'wgReportIncidentEnableInstrumentation' => true,
				'wgReportIncidentDetailsCodePointLength' => ReportHandler::MAX_DETAILS_LENGTH,
				'wgReportIncidentUserHasEmail' => false,
				'wgReportIncidentUseV2NonEmergencyFlow' => true,
				'wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity' => '',
				'wgReportIncidentNonEmergencyDoxingShowWarning' => true,
				'wgReportIncidentNonEmergencyDoxingHideEditURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodWikiEmailURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodOtherURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodEmailStewards' => false,
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity' => '',
			] );
		$outputPageMock->expects( $this->once() )->method( 'addModules' )
			->with( 'ext.reportIncident' );
		$outputPageMock->expects( $this->never() )->method( 'addModuleStyles' )
			->with( 'ext.reportIncident.menuStyles' );
		// Mock the methods used to get the current skin name to return "vector"
		$mockSkin = $this->createMock( Skin::class );
		$mockSkin->method( 'getSkinName' )
			->willReturn( 'vector' );
		$outputPageMock->expects( $this->once() )->method( 'getSkin' )
			->willReturn( $mockSkin );
		$globalConfig += [
			'ReportIncidentDeveloperMode' => false,
			'ReportIncidentEnableInstrumentation' => true,
		];
		/** @var ReportIncidentController $objectUnderTest */
		$objectUnderTest = $this->newReportIncidentController( $globalConfig, $communityConfig );
		$objectUnderTest->addModulesAndConfigVars( $outputPageMock );
	}

	public static function provideConfigVarsData(): iterable {
		yield 'no customized local links in LocalSettings nor community configuration' => [
			[],
			[],
			[
				'disputeResolution' => 'Project:Dispute resolution',
				'localIncidentReport' => 'Project:Report an incident',
				'askTheCommunity' => 'Project:Village pump',
			],
		];

		yield 'customized local links in LocalSettings, nothing in community configuration' => [
			[
				'ReportIncidentLocalIncidentReportPage' => 'Project:Local incident report',
			],
			[],
			[
				'disputeResolution' => 'Project:Dispute resolution',
				'localIncidentReport' => 'Project:Local incident report',
				'askTheCommunity' => 'Project:Village pump',
			],
		];

		yield 'customized local links in LocalSettings and in community configuration' => [
			[
				'ReportIncidentLocalIncidentReportPage' => 'Project:Local incident report',
				'ReportIncidentCommunityQuestionsPage' => 'Project:Community questions',
			],
			[
				'ReportIncidentLocalIncidentReportPage' => 'Project:CommunityConfig Local incident report',
			],
			[
				'disputeResolution' => 'Project:Dispute resolution',
				'localIncidentReport' => 'Project:CommunityConfig Local incident report',
				'askTheCommunity' => 'Project:Community questions',
			],
		];
	}

	public function testAddModulesAndConfigVarsNoConfirmedEmail() {
		$outputPageMock = $this->createMock( OutputPage::class );
		$userMock = $this->createMock( User::class );
		$userMock->method( 'isEmailConfirmed' )->willReturn( false );
		$outputPageMock->method( 'getUser' )->willReturn( $userMock );
		$outputPageMock->expects( $this->once() )->method( 'addJsConfigVars' )
			->with( [
				'wgReportIncidentUserHasConfirmedEmail' => false,
				'wgReportIncidentLocalLinks' => [
					'disputeResolution' => 'Project:Dispute resolution',
					'localIncidentReport' => 'Project:Report an incident',
					'askTheCommunity' => 'Project:Village pump',
				],
				'wgReportIncidentEnableInstrumentation' => true,
				'wgReportIncidentDetailsCodePointLength' => ReportHandler::MAX_DETAILS_LENGTH,
				'wgReportIncidentUserHasEmail' => false,
				'wgReportIncidentUseV2NonEmergencyFlow' => true,
				'wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity' => '',
				'wgReportIncidentNonEmergencyDoxingShowWarning' => true,
				'wgReportIncidentNonEmergencyDoxingHideEditURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodWikiEmailURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodOtherURL' => '',
				'wgReportIncidentNonEmergencyDoxingHelpMethodEmailStewards' => false,
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodEmail' => '',
				'wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity' => '',
			] );
		$outputPageMock->expects( $this->once() )->method( 'addModules' )
			->with( 'ext.reportIncident' );
		$outputPageMock->expects( $this->never() )->method( 'addModuleStyles' )
			->with( 'ext.reportIncident.menuStyles' );
		// Mock the methods used to get the current skin name to return "vector"
		$mockSkin = $this->createMock( Skin::class );
		$mockSkin->method( 'getSkinName' )
			->willReturn( 'vector' );
		$outputPageMock->expects( $this->once() )->method( 'getSkin' )
			->willReturn( $mockSkin );
		/** @var ReportIncidentController $objectUnderTest */
		$objectUnderTest = $this->newReportIncidentController( [
			'ReportIncidentDeveloperMode' => false,
			'ReportIncidentEnableInstrumentation' => true,
		] );
		$objectUnderTest->addModulesAndConfigVars( $outputPageMock );
	}
}
