<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit;

use HashConfig;
use MediaWiki\Extension\ReportIncident\Hooks;
use OutputPage;
use Skin;
use Title;

/**
 * @covers \MediaWiki\Extension\ReportIncident\Hooks
 */
class HooksTest extends \MediaWikiUnitTestCase {

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
		$outputPageMock->expects( $this->never() )->method( 'addModules' );
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
		$outputPageMock->expects( $this->once() )->method( 'addModules' );
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
		$outputPageMock->expects( $this->never() )->method( 'addModules' );
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
		$skinMock = $this->createMock( Skin::class );
		$skinMock->method( 'getSkinName' )->willReturn( 'vector' );
		$outputPageMock->expects( $this->never() )->method( 'addModules' );
		( new Hooks() )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

}
