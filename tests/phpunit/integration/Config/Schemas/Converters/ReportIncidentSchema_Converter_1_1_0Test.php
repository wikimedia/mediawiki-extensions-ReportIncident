<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ReportIncident\Tests\Integration\Config\Schemas\Converters;

use MediaWiki\Extension\CommunityConfiguration\Maintenance\MigrateConfig;
use MediaWiki\Extension\ReportIncident\Config\Schemas\ReportIncidentSchema;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\Title;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
/**
 * @covers MediaWiki\Extension\ReportIncident\Config\Schemas\Converters\ReportIncidentSchema_Converter_1_1_0
 * @group Database
 */
class ReportIncidentSchema_Converter_1_1_0Test extends MaintenanceBaseTestCase {
	private const PROVIDER_ID = 'ReportIncident';
	private const CONFIG_PAGE_TITLE = 'MediaWiki:IncidentReportingConfig.json';

	protected function getMaintenanceClass(): string {
		return MigrateConfig::class;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'CommunityConfigurationProviders', [
			self::PROVIDER_ID => [
				'store' => [
					'type' => 'wikipage',
					'args' => [ self::CONFIG_PAGE_TITLE ],
				],
				'validator' => [
					'type' => 'jsonschema',
					'args' => [ ReportIncidentSchema::class ],
				],
				'type' => 'mw-config',
			]
		] );
	}

	public function testUpgrade(): void {
		$pageStatus = $this->editPage( 'MediaWiki:IncidentReportingConfig.json', '{
	"$version": "1.0.0",
	"ReportIncidentEnabledNamespaces": [1, 3, 7, 9]
	}' );

		$this->assertStatusGood( $pageStatus );
		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'version' => '1.1.0' ],
			[ 'ReportIncident' ]
		);

		$result = $this->maintenance->execute();
		$this->expectOutputString( "All done!  Migrated from 1.0.0 to 1.1.0" . PHP_EOL );
		$this->assertTrue( $result );

		$expectedConfig = (object)[
			'$version' => '1.1.0',
			'ReportIncidentE2ETesterUsers' => [],
			'ReportIncidentEnabledNamespaces' => [ 1, 3, 7, 9 ],
			'ReportIncident_NonEmergency_DisruptiveEditing' => (object)[],
			'ReportIncident_NonEmergency_DisruptiveEditing_HelpMethod' => (object)[],
			'ReportIncident_NonEmergency_Doxing' => (object)[],
			'ReportIncident_NonEmergency_Doxing_HelpMethod' => (object)[],
			'ReportIncident_NonEmergency_Doxing_HideEditURL' => '',
			'ReportIncident_NonEmergency_Doxing_ShowWarning' => (bool)[],
			'ReportIncident_NonEmergency_HateSpeech' => (object)[],
			'ReportIncident_NonEmergency_HateSpeech_HelpMethod' => (object)[],
			'ReportIncident_NonEmergency_Intimidation' => (object)[],
			'ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL' => '',
			'ReportIncident_NonEmergency_Intimidation_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			],
			'ReportIncident_NonEmergency_Other' => (object)[],
			'ReportIncident_NonEmergency_Other_DisputeResolutionURL' => '',
			'ReportIncident_NonEmergency_Other_HelpMethod' => (object)[],
			'ReportIncident_NonEmergency_SexualHarassment' => (object)[],
			'ReportIncident_NonEmergency_SexualHarassment_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			],
			'ReportIncident_NonEmergency_Sockpuppetry' => (object)[],
			'ReportIncident_NonEmergency_Sockpuppetry_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			],
			'ReportIncident_NonEmergency_SomethingElse' => (object)[],
			'ReportIncident_NonEmergency_SomethingElse_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			],
			'ReportIncident_NonEmergency_Spam' => (object)[],
			'ReportIncident_NonEmergency_Spam_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
			],
			'ReportIncident_NonEmergency_Spam_SpamContentURL' => '',
			'ReportIncident_NonEmergency_Trolling' => (object)[],
			'ReportIncident_NonEmergency_Trolling_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			],
			'ReportIncident_NonEmergency_UserDispute' => (object)[],
			'ReportIncident_NonEmergency_UserDispute_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			],
			'ReportIncident_NonEmergency_Vandalism' => (object)[],
			'ReportIncident_NonEmergency_Vandalism_HelpMethod' => (object)[
				'ContactAdmin' => '',
				'Email' => '',
				'ContactCommunity' => ''
			]
		];

		/** @var WikiPage $wikipage */
		$wikipage = $this->getServiceContainer()->get( 'WikiPageFactory' )->newFromTitle(
			Title::newFromText( 'MediaWiki:IncidentReportingConfig.json' )
		);
		$actualDataStatus = $wikipage->getContent()->getData();
		$this->assertStatusGood( $actualDataStatus );
		$this->assertEquals( $expectedConfig, $actualDataStatus->getValue() );
	}

	public function testDowngrade(): void {
		$pageStatus = $this->editPage( 'MediaWiki:IncidentReportingConfig.json', '{
	"$version": "1.1.0",
	"ReportIncidentEnabledNamespaces": [1, 3, 7, 9],
	"ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL": "www.i-hear-you-got-a-dispute.org"
	}' );

		$this->assertStatusGood( $pageStatus );
		$this->maintenance->loadParamsAndArgs(
			null,
			[ 'version' => '1.0.0' ],
			[ 'ReportIncident' ]
		);

		$result = $this->maintenance->execute();
		$this->expectOutputString( "All done!  Migrated from 1.1.0 to 1.0.0" . PHP_EOL );
		$this->assertTrue( $result );

		$expectedConfig = (object)[
			'$version' => '1.0.0',
			'ReportIncidentEnabledNamespaces' => [ 1, 3, 7, 9 ],
		];

		/** @var WikiPage $wikipage */
		$wikipage = $this->getServiceContainer()->get( 'WikiPageFactory' )->newFromTitle(
			Title::newFromText( 'MediaWiki:IncidentReportingConfig.json' )
		);
		$actualDataStatus = $wikipage->getContent()->getData();
		$this->assertStatusGood( $actualDataStatus );
		$this->assertEquals( $expectedConfig, $actualDataStatus->getValue() );
	}
}
