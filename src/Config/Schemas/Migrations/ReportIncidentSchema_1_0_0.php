<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ReportIncident\Config\Schemas\Migrations;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki\MediaWikiDefinitions;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class ReportIncidentSchema_1_0_0 extends JsonSchema {
	public const VERSION = '1.0.0';
	public const SCHEMA_NEXT_VERSION = '1.1.0';

	public const ReportIncidentDisputeResolutionPage = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => ''
	];

	public const ReportIncidentLocalIncidentReportPage = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => ''
	];

	public const ReportIncidentCommunityQuestionsPage = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => ''
	];

	public const ReportIncidentEnabledNamespaces = [
		self::REF => [
			'class' => MediaWikiDefinitions::class,
			'field' => 'Namespaces',
		],
	];
}
