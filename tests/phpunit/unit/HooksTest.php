<?php

namespace MediaWiki\Extension\IncidentReporting\Tests\Unit;

use HashConfig;
use MediaWiki\Extension\IncidentReporting\Hooks;
use OutputPage;
use Skin;
use Title;

/**
 * @covers \MediaWiki\Extension\IncidentReporting\Hooks
 */
class HooksTest extends \MediaWikiUnitTestCase {

	public function testFeatureFlagDisabled() {
		$config = new HashConfig( [
			'IncidentReportingReportButtonEnabled' => false,
			'IncidentReportingEnabledSkins' => [ 'minerva' ],
			'IncidentReportingEnabledNamespaces' => [ NS_USER_TALK ],
		] );
		$outputPageMock = $this->createMock( OutputPage::class );
		$outputPageMock->method( 'getConfig' )->willReturn( $config );
		$skinMock = $this->createMock( Skin::class );
		$outputPageMock->expects( $this->never() )->method( 'addModules' );
		( new Hooks() )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

	public function testConfigEnabledCorrectNamespaceAndSkin() {
		$config = new HashConfig( [
			'IncidentReportingReportButtonEnabled' => true,
			'IncidentReportingEnabledSkins' => [ 'minerva' ],
			'IncidentReportingEnabledNamespaces' => [ NS_USER_TALK ],
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
			'IncidentReportingReportButtonEnabled' => true,
			'IncidentReportingEnabledSkins' => [ 'minerva' ],
			'IncidentReportingEnabledNamespaces' => [ NS_PROJECT_TALK ],
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
			'IncidentReportingReportButtonEnabled' => true,
			'IncidentReportingEnabledSkins' => [ 'minerva' ],
			'IncidentReportingEnabledNamespaces' => [ NS_USER_TALK ],
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
