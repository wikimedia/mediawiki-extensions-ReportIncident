<?php
namespace MediaWiki\Extension\ReportIncident\Config;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki\MediaWikiDefinitions;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

/**
 * JSON schema for community configuration used by the Incident Reporting System.
 */
class ReportIncidentSchema extends JsonSchema {

	public const VERSION = '1.1.0';

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

	// This is a hack to add a sub-header denoting the section
	public const ReportIncident_NonEmergency_Intimidation = [
		self::TYPE => self::TYPE_OBJECT,
	];

	public const ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => ''
	];

	public const ReportIncident_NonEmergency_Intimidation_HelpMethod = [
		self::TYPE => self::TYPE_OBJECT,
		self::PROPERTIES => [
			'ContactAdmin' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'Email' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'ContactCommunity' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
		],
	];

	// sub-header hack
	public const ReportIncident_NonEmergency_Doxing = [
		self::TYPE => self::TYPE_OBJECT,
	];

	public const ReportIncident_NonEmergency_Doxing_ShowWarning = [
		self::TYPE => self::TYPE_BOOLEAN,
		self::DEFAULT => true
	];

	public const ReportIncident_NonEmergency_Doxing_HideEditURL = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => ''
	];

	public const ReportIncident_NonEmergency_Doxing_HelpMethod = [
		self::TYPE => self::TYPE_OBJECT,
		self::PROPERTIES => [
			'WikiEmailURL' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'Email' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'OtherURL' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'EmailStewards' => [
				self::TYPE => self::TYPE_BOOLEAN,
				self::DEFAULT => false
			],
		],
	];

	// sub-header hack
	public const ReportIncident_NonEmergency_SexualHarassment = [
		self::TYPE => self::TYPE_OBJECT,
	];

	public const ReportIncident_NonEmergency_SexualHarassment_HelpMethod = [
		self::TYPE => self::TYPE_OBJECT,
		self::PROPERTIES => [
			'ContactAdmin' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'Email' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
			'ContactCommunity' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => ''
			],
		],
	];
}
