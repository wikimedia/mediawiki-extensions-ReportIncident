<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use HashConfig;
use MediaWiki\Extension\ReportIncident\Hooks;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use Message;
use OutputPage;
use Skin;
use Title;
use Wikimedia\TestingAccessWrapper;

/**
 * @group ReportIncident
 *
 * @covers \MediaWiki\Extension\ReportIncident\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {

	public function testFeatureFlagDisabled() {
		$config = new HashConfig( [
			'ReportIncidentReportButtonEnabled' => false,
			'ReportIncidentEnabledSkins' => [ 'minerva' ],
			'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
			'ReportIncidentAdministratorsPage' => 'Main_Page'
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$outputPageMock->method( 'getConfig' )->willReturn( $config );
		$skinMock = $this->createMock( Skin::class );
		$outputPageMock->expects( $this->never() )->method( 'addHTML' );
		( new Hooks() )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

	public function testConfigEnabledCorrectNamespaceAndSkin() {
		$config = new HashConfig( [
			'ReportIncidentReportButtonEnabled' => true,
			'ReportIncidentEnabledSkins' => [ 'minerva' ],
			'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
			'ReportIncidentAdministratorsPage' => 'Main_Page',
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_USER_TALK );
		$outputPageMock->method( 'getConfig' )->willReturn( $config );
		$outputPageMock->method( 'getTitle' )->willReturn( $title );
		$skinMock = $this->createMock( Skin::class );
		$skinMock->method( 'getSkinName' )->willReturn( 'minerva' );
		$outputPageMock->expects( $this->once() )->method( 'addHTML' );
		( new Hooks() )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

	public function testConfigEnabledIncorrectNamespaceCorrectSkin() {
		$config = new HashConfig( [
			'ReportIncidentReportButtonEnabled' => true,
			'ReportIncidentEnabledSkins' => [ 'minerva' ],
			'ReportIncidentEnabledNamespaces' => [ NS_PROJECT_TALK ],
			'ReportIncidentAdministratorsPage' => 'Main_Page',
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_USER_TALK );
		$outputPageMock->method( 'getConfig' )->willReturn( $config );
		$outputPageMock->method( 'getTitle' )->willReturn( $title );
		$skinMock = $this->createMock( Skin::class );
		$skinMock->method( 'getSkinName' )->willReturn( 'minerva' );
		$outputPageMock->expects( $this->never() )->method( 'addHTML' );
		( new Hooks() )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

	public function testConfigEnabledCorrectNamespaceIncorrectSkin() {
		$config = new HashConfig( [
			'ReportIncidentReportButtonEnabled' => true,
			'ReportIncidentEnabledSkins' => [ 'minerva' ],
			'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
			'ReportIncidentAdministratorsPage' => 'Main_Page',
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_USER_TALK );
		$outputPageMock->method( 'getConfig' )->willReturn( $config );
		$outputPageMock->method( 'getTitle' )->willReturn( $title );
		$outputPageMock->expects( $this->never() )->method( 'addHTML' );
		$skinMock = $this->createMock( Skin::class );
		$skinMock->method( 'getSkinName' )->willReturn( 'vector' );
		( new Hooks() )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

	public function testAddModulesAndConfigVarsForMinerva() {
		$config = new HashConfig( [
			'ReportIncidentAdministratorsPage' => 'Main_Page',
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$outputPageMock->expects( $this->once() )->method( 'addJsConfigVars' )
			->with( [
				'wgReportIncidentAdministratorsPage' => 'Main_Page',
			] );
		$outputPageMock->expects( $this->once() )->method( 'addModules' )
			->with( 'ext.reportIncident' );
		$outputPageMock->expects( $this->once() )->method( 'addModuleStyles' )
			->with( 'ext.reportIncident.minervaicons' );
		TestingAccessWrapper::newFromObject( new Hooks() )
			->addModulesAndConfigVars( $outputPageMock, $config, 'minerva' );
	}

	public function testAddModulesAndConfigVars() {
		$config = new HashConfig( [
			'ReportIncidentAdministratorsPage' => 'Main_Page',
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$outputPageMock->expects( $this->once() )->method( 'addJsConfigVars' )
			->with( [
				'wgReportIncidentAdministratorsPage' => 'Main_Page',
			] );
		$outputPageMock->expects( $this->once() )->method( 'addModules' )
			->with( 'ext.reportIncident' );
		$outputPageMock->expects( $this->never() )->method( 'addModuleStyles' );
		TestingAccessWrapper::newFromObject( new Hooks() )
			->addModulesAndConfigVars( $outputPageMock, $config, 'vector' );
	}

	private function commonTestToolLinksNotAdded(
		$config, $titleNamespace, $skinName, $userIsNamed, $method, $failureMessage
	) {
		$mockTitle = $this->createMock( Title::class );
		$mockTitle->method( 'getNamespace' )->willReturn( $titleNamespace );
		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isNamed' )->willReturn( $userIsNamed );
		$mockSkin = $this->createMock( Skin::class );
		$mockSkin->method( 'getSkinName' )->willReturn( $skinName );
		$mockSkin->method( 'getConfig' )->willReturn( $config );
		$mockSkin->method( 'getTitle' )->willReturn( $mockTitle );
		$mockSkin->method( 'getUser' )->willReturn( $mockUser );
		$objectUnderTest = $this->getMockBuilder( Hooks::class )
			->onlyMethods( [ 'addModulesAndConfigVars' ] )
			->getMock();
		$objectUnderTest->expects( $this->never() )->method( 'addModulesAndConfigVars' );
		$sidebar = [];
		$objectUnderTest->$method( $mockSkin, $sidebar );
		$this->assertArrayEquals(
			[],
			$sidebar,
			true,
			true,
			$failureMessage
		);
	}

	/** @dataProvider provideToolLinksMethodNames */
	public function testToolLinksNotAddedWhenReportButtonDisabled( $method ) {
		$this->commonTestToolLinksNotAdded(
			new HashConfig( [
				'ReportIncidentReportButtonEnabled' => false,
			] ),
			NS_USER_TALK,
			'vector',
			true,
			$method,
			"The Hooks::$method method should not modify the sidebar array if " .
			'the button is disabled.'
		);
	}

	/** @dataProvider provideToolLinksMethodNames */
	public function testToolLinksNotAddedWhenCorrectNamespaceButIncorrectSkin( $method ) {
		$this->commonTestToolLinksNotAdded(
			new HashConfig( [
				'ReportIncidentReportButtonEnabled' => true,
				'ReportIncidentEnabledSkins' => [ 'vector' ],
				'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
				'ReportIncidentAdministratorsPage' => 'Main_Page',
			] ),
			NS_USER_TALK,
			'minerva',
			true,
			$method,
			"The Hooks::$method method should not modify the sidebar array if " .
			'if the skin used is not supported.'
		);
	}

	/** @dataProvider provideToolLinksMethodNames */
	public function testToolLinksNotAddedWhenIncorrectNamespaceButCorrectSkin( $method ) {
		$this->commonTestToolLinksNotAdded(
			new HashConfig( [
				'ReportIncidentReportButtonEnabled' => true,
				'ReportIncidentEnabledSkins' => [ 'minerva' ],
				'ReportIncidentEnabledNamespaces' => [ NS_PROJECT_TALK ],
				'ReportIncidentAdministratorsPage' => 'Main_Page',
			] ),
			NS_USER_TALK,
			'minerva',
			true,
			$method,
			"The Hooks::$method method should not modify the sidebar array if " .
			'the namespace is not supported.'
		);
	}

	/** @dataProvider provideToolLinksMethodNames */
	public function testToolLinksNotAddedWhenUserIsNotNamed( $method ) {
		$this->commonTestToolLinksNotAdded(
			new HashConfig( [
				'ReportIncidentReportButtonEnabled' => true,
				'ReportIncidentEnabledSkins' => [ 'minerva' ],
				'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
				'ReportIncidentAdministratorsPage' => 'Main_Page',
			] ),
			NS_USER_TALK,
			'minerva',
			false,
			$method,
			"The Hooks::$method method should not modify the sidebar array if " .
			'the user is not named.'
		);
	}

	public static function provideToolLinksMethodNames() {
		return [
			'Hooks::onSkinTemplateNavigation__Universal' => [ 'onSkinTemplateNavigation__Universal' ],
			'Hooks::onSidebarBeforeOutput' => [ 'onSidebarBeforeOutput' ],
		];
	}

	/** @dataProvider provideNonMinervaSkinNames */
	public function testOnSidebarBeforeOutputOnlyUsedForMinervaSkin( $skinName ) {
		$this->commonTestToolLinksNotAdded(
			new HashConfig( [
				'ReportIncidentReportButtonEnabled' => true,
				'ReportIncidentEnabledSkins' => [ 'vector', 'timeless' ],
				'ReportIncidentEnabledNamespaces' => [ NS_PROJECT_TALK ],
				'ReportIncidentAdministratorsPage' => 'Main_Page',
			] ),
			NS_USER_TALK,
			$skinName,
			true,
			'onSidebarBeforeOutput',
			"The Hooks::onSidebarBeforeOutput method should not modify the sidebar array if " .
			'the skin is not minerva.'
		);
	}

	public static function provideNonMinervaSkinNames() {
		return [
			'Vector' => [ 'vector' ],
			'Timeless' => [ 'timeless' ],
			'Random string' => [ 'testing1234' ],
		];
	}

	/** @dataProvider provideToolLinksAddedWhenCorrectNamespaceAndSkin */
	public function testToolLinksAddedWhenCorrectNamespaceAndSkin( $method, $expectedSidebar ) {
		$config = new HashConfig( [
			'ReportIncidentReportButtonEnabled' => true,
			'ReportIncidentEnabledSkins' => [ 'minerva' ],
			'ReportIncidentEnabledNamespaces' => [ NS_USER_TALK ],
			'ReportIncidentAdministratorsPage' => 'Main_Page',
		] );
		$mockTitle = $this->createMock( Title::class );
		$mockTitle->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_USER_TALK );
		$mockOutput = $this->createMock( OutputPage::class );
		$mockMessage = $this->createMock( Message::class );
		$mockMessage->method( 'text' )->willReturn( 'test link text' );
		// Mock the user to be named.
		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isNamed' )->willReturn( true );
		$mockSkin = $this->createMock( Skin::class );
		$mockSkin->method( 'getSkinName' )->willReturn( 'minerva' );
		$mockSkin->method( 'getConfig' )->willReturn( $config );
		$mockSkin->method( 'getTitle' )->willReturn( $mockTitle );
		$mockSkin->method( 'getOutput' )->willReturn( $mockOutput );
		$mockSkin->method( 'getUser' )->willReturn( $mockUser );
		$mockSkin->method( 'msg' )
			->with( 'reportincident-report-btn-label' )
			->willReturn( $mockMessage );
		$objectUnderTest = $this->getMockBuilder( Hooks::class )
			->onlyMethods( [ 'addModulesAndConfigVars' ] )
			->getMock();
		$objectUnderTest->expects( $this->once() )
			->method( 'addModulesAndConfigVars' )
			->with( $mockOutput, $config, 'minerva' );
		$sidebar = [];
		$objectUnderTest->$method( $mockSkin, $sidebar );
		$this->assertArrayEquals(
			$expectedSidebar,
			$sidebar,
			false,
			true,
			"The Hooks::$method method should modify the sidebar array."
		);
	}

	public static function provideToolLinksAddedWhenCorrectNamespaceAndSkin() {
		return [
			'Hooks::onSkinTemplateNavigation__Universal' => [
				'onSkinTemplateNavigation__Universal',
				[
					'actions' => [
						'reportincident' => [
							'class' => 'ext-reportincident-link',
							'text' => 'test link text',
							'href' => '#',
							'icon' => 'flag',
						]
					]
				]
			],
			'Hooks::onSidebarBeforeOutput' => [
				'onSidebarBeforeOutput',
				[
					'TOOLBOX' => [
						'reportincident' => [
							'class' => 'ext-reportincident-link',
							'text' => 'test link text',
							'href' => '#',
							'icon' => 'flag',
						]
					]
				]
			],
		];
	}
}
