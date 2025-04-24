<?php
namespace MediaWiki\Extension\ReportIncident\Config;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki\MediaWikiDefinitions;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

/**
 * JSON schema for community configuration used by the Incident Reporting System.
 */
class ReportIncidentSchema extends JsonSchema {

	public const VERSION = '1.0.0';

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
