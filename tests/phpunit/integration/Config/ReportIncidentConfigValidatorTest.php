<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Integration\Config;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\ReportIncident\Config\ReportIncidentConfigValidator;
use MediaWiki\Extension\ReportIncident\Config\ReportIncidentSchema;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ReportIncident\Config\ReportIncidentConfigValidator
 * @covers \MediaWiki\Extension\ReportIncident\Config\ReportIncidentSchema
 * @group Database
 */
class ReportIncidentConfigValidatorTest extends MediaWikiIntegrationTestCase {
	private const TEST_EXISTING_PAGE = 'ReportIncidentExistingPage';

	private ReportIncidentConfigValidator $validator;

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'CommunityConfiguration' );

		$context = new RequestContext();
		$context->setLanguage( 'qqx' );

		$this->validator = ReportIncidentConfigValidator::factory(
			$this->getServiceContainer()->getTitleParser(),
			$this->getServiceContainer()->getPageStore(),
			CommunityConfigurationServices::wrap( $this->getServiceContainer() )->getValidatorFactory(),
			ReportIncidentSchema::class,
			$context
		);
	}

	public function addDBDataOnce() {
		$this->getExistingTestPage( self::TEST_EXISTING_PAGE );
	}

	/**
	 * @dataProvider provideValidationErrors
	 */
	public function testPermissiveValidationFails( array $config, array $expectedErrors ) {
		$status = $this->validator->validatePermissively( (object)$config );
		$errors = $status->getValidationErrorsData();

		$this->assertStatusNotGood( $status );
		$this->assertSame( $expectedErrors, $errors );
	}

	/**
	 * @dataProvider provideValidationErrors
	 */
	public function testStrictValidationFails( array $config, array $expectedErrors ) {
		$status = $this->validator->validateStrictly( (object)$config );
		$errors = $status->getValidationErrorsData();

		$this->assertStatusNotGood( $status );
		$this->assertSame( $expectedErrors, $errors );
	}

	public static function provideValidationErrors(): iterable {
		$baseConfig = [
			'ReportIncidentDisputeResolutionPage' => '',
			'ReportIncidentLocalIncidentReportPage' => '',
			'ReportIncidentCommunityQuestionsPage' => '',
			'ReportIncidentEnabledNamespaces' => [],
		];

		$titleProps = [
			'ReportIncidentDisputeResolutionPage',
			'ReportIncidentLocalIncidentReportPage',
			'ReportIncidentCommunityQuestionsPage',
		];

		foreach ( $titleProps as $name ) {
			yield "invalid title for config option \"$name\"" => [
				array_merge( $baseConfig, [ $name => ':' ] ),
				[
					[
						'property' => $name,
						'pointer' => "/$name",
						'messageLiteral' => '(title-invalid-empty: &#58;)',
						'additionalData' => [],
					],
				],
			];

			yield "missing title for config option \"$name\"" => [
				array_merge( $baseConfig, [ $name => 'ReportIncidentNoSuchPageExists' ] ),
				[
					[
						'property' => $name,
						'pointer' => "/$name",
						'messageLiteral' => '(communityconfiguration-reportincident-invalid-title)',
						'additionalData' => [],
					],
				],
			];

			yield "special page for config option \"$name\"" => [
				array_merge( $baseConfig, [ $name => 'Special:Version' ] ),
				[
					[
						'property' => $name,
						'pointer' => "/$name",
						'messageLiteral' => '(communityconfiguration-reportincident-invalid-title)',
						'additionalData' => [],
					],
				],
			];
		}

		yield 'config not matching schema' => [
			[
				'ReportIncidentDisputeResolutionPage' => 5,
				'ReportIncidentLocalIncidentReportPage' => '',
				'ReportIncidentCommunityQuestionsPage' => '',
			],
			[
				[
					'property' => 'ReportIncidentDisputeResolutionPage',
					'pointer' => '/ReportIncidentDisputeResolutionPage',
					'messageLiteral' => 'Integer value found, but a string is required',
					'additionalData' => [ 'constraint' => 'type' ],
				]
			]
		];
	}

	/**
	 * @dataProvider provideValidConfig
	 */
	public function testPermissiveValidationSuccess( array $config ): void {
		$status = $this->validator->validatePermissively( (object)$config );

		$this->assertStatusOK( $status );
	}

	/**
	 * @dataProvider provideValidConfig
	 */
	public function testStrictValidationSuccess( array $config ): void {
		$status = $this->validator->validateStrictly( (object)$config );

		$this->assertStatusOK( $status );
	}

	public static function provideValidConfig(): iterable {
		yield 'no pages set' => [
			[
				'ReportIncidentDisputeResolutionPage' => '',
				'ReportIncidentLocalIncidentReportPage' => '',
				'ReportIncidentCommunityQuestionsPage' => '',
			],
		];

		yield 'some pages set' => [
			[
				'ReportIncidentDisputeResolutionPage' => self::TEST_EXISTING_PAGE,
				'ReportIncidentLocalIncidentReportPage' => '',
				'ReportIncidentCommunityQuestionsPage' => '',
			],
		];

		yield 'some pages and namespaces set' => [
			[
				'ReportIncidentDisputeResolutionPage' => self::TEST_EXISTING_PAGE,
				'ReportIncidentLocalIncidentReportPage' => '',
				'ReportIncidentCommunityQuestionsPage' => '',
				'ReportIncidentEnabledNamespaces' => [ NS_PROJECT ],
			],
		];

		yield 'all pages set' => [
			[
				'ReportIncidentDisputeResolutionPage' => self::TEST_EXISTING_PAGE,
				'ReportIncidentLocalIncidentReportPage' => self::TEST_EXISTING_PAGE,
				'ReportIncidentCommunityQuestionsPage' => self::TEST_EXISTING_PAGE,
			],
		];
	}
}
