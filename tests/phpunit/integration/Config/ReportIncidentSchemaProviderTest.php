<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\ReportIncident\Tests\Integration\Config;

use MediaWiki\Extension\CommunityConfiguration\Tests\SchemaProviderTestCase;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;

/**
 * @coversNothing
 * @group Database
 */
class ReportIncidentSchemaProviderTest extends SchemaProviderTestCase {
	use MockAuthorityTrait;

	protected function getExtensionName(): string {
		return 'ReportIncident';
	}

	protected function getProviderId(): string {
		return 'ReportIncident';
	}

	public function testConvertFromEarlierVersionToLaterVersionDoesNotBreak(): void {
		$pageStatus = $this->editPage( 'MediaWiki:ReportIncident.json', '{
			"$version": "1.0.0",
			"ReportIncidentEnabledNamespaces": [],
			"ReportIncidentE2ETesterUsers": []
		}' );
		$this->assertStatusGood( $pageStatus );
		$configurationStatus = $this->getProvider()->loadValidConfigurationConvertedToLatest();
		$this->assertStatusGood( $configurationStatus );
	}
}
