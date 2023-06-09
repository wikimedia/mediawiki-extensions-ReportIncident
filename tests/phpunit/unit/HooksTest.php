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

	/**
	 * @covers \MediaWiki\Extension\IncidentReporting\Hooks::onBeforePageDisplay
	 */
	public function testRlModuleIsNotLoadedIfFeatureFlagIsOff() {
		$config = new HashConfig( [
			"IncidentReportingEnabled" => [ "value" => true ],
			"IncidentReportingEnabledSkins" => [ "value" => 'minerva' ],
			"IncidentReportingEnabledNamespaces" => [ "value" => NS_USER_TALK ],
		] );
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_USER_TALK );
		$outputPageMock = $this->createMock( OutputPage::class );
		$outputPageMock->method( 'getConfig' )
			->willReturn( $config );
		$outputPageMock->expects( $this->never() )
			->method( 'addModules' );
		$outputPageMock->method( 'getTitle' )->willReturn( $title );
		$skinMock = $this->createMock( Skin::class );
		( new Hooks )->onBeforePageDisplay( $outputPageMock, $skinMock );
	}

}
