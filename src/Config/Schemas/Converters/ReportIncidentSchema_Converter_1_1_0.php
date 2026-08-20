<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\ReportIncident\Config\Schemas\Converters;

use MediaWiki\Extension\CommunityConfiguration\Schema\ISchemaConverter;
use stdClass;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class ReportIncidentSchema_Converter_1_1_0 implements ISchemaConverter {

	public function upgradeFromOlder( stdClass $data ): stdClass {
		unset( $data->ReportIncidentCommunityQuestionsPage );
		unset( $data->ReportIncidentDisputeResolutionPage );
		unset( $data->ReportIncidentLocalIncidentReportPage );

		$data->ReportIncidentE2ETesterUsers = [];
		$data->ReportIncident_NonEmergency_DisruptiveEditing = (object)[];
		$data->ReportIncident_NonEmergency_DisruptiveEditing_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Doxing = (object)[];
		$data->ReportIncident_NonEmergency_Doxing_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Doxing_HideEditURL = '';
		$data->ReportIncident_NonEmergency_Doxing_ShowWarning = false;
		$data->ReportIncident_NonEmergency_HateSpeech = (object)[];
		$data->ReportIncident_NonEmergency_HateSpeech_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_SexualHarassment = (object)[];

		$data->ReportIncident_NonEmergency_SexualHarassment_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_SexualHarassment_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_SexualHarassment_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_SexualHarassment_HelpMethod->{'ContactCommunity'} = '';
		$data->ReportIncident_NonEmergency_Sockpuppetry = (object)[];

		$data->ReportIncident_NonEmergency_Sockpuppetry_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Sockpuppetry_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_Sockpuppetry_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_Sockpuppetry_HelpMethod->{'ContactCommunity'} = '';
		$data->ReportIncident_NonEmergency_SomethingElse = (object)[];
		$data->ReportIncident_NonEmergency_Spam = (object)[];

		$data->ReportIncident_NonEmergency_Spam_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Spam_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_Spam_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_Spam_SpamContentURL = '';
		$data->ReportIncident_NonEmergency_Other = (object)[];
		$data->ReportIncident_NonEmergency_Other_DisputeResolutionURL = '';
		$data->ReportIncident_NonEmergency_Other_HelpMethod = (object)[];

		$data->ReportIncident_NonEmergency_SomethingElse_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_SomethingElse_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_SomethingElse_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_SomethingElse_HelpMethod->{'ContactCommunity'} = '';
		$data->ReportIncident_NonEmergency_Trolling = (object)[];

		$data->ReportIncident_NonEmergency_Trolling_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Trolling_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_Trolling_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_Trolling_HelpMethod->{'ContactCommunity'} = '';
		$data->ReportIncident_NonEmergency_UserDispute = (object)[];

		$data->ReportIncident_NonEmergency_UserDispute_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_UserDispute_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_UserDispute_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_UserDispute_HelpMethod->{'ContactCommunity'} = '';
		$data->ReportIncident_NonEmergency_Vandalism = (object)[];

		$data->ReportIncident_NonEmergency_Vandalism_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Vandalism_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_Vandalism_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_Vandalism_HelpMethod->{'ContactCommunity'} = '';
		$data->ReportIncident_NonEmergency_Intimidation = (object)[];
		$data->ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL = '';

		$data->ReportIncident_NonEmergency_Intimidation_HelpMethod = (object)[];
		$data->ReportIncident_NonEmergency_Intimidation_HelpMethod->{'ContactAdmin'} = '';
		$data->ReportIncident_NonEmergency_Intimidation_HelpMethod->{'Email'} = '';
		$data->ReportIncident_NonEmergency_Intimidation_HelpMethod->{'ContactCommunity'} = '';
		return $data;
	}

	public function downgradeToPrevious( stdClass $data ): stdClass {
		unset( $data->ReportIncidentE2ETesterUsers );
		unset( $data->ReportIncident_NonEmergency_DisruptiveEditing );
		unset( $data->ReportIncident_NonEmergency_DisruptiveEditing_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Doxing );
		unset( $data->ReportIncident_NonEmergency_Doxing_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Doxing_HideEditURL );
		unset( $data->ReportIncident_NonEmergency_Doxing_ShowWarning );
		unset( $data->ReportIncident_NonEmergency_HateSpeech );
		unset( $data->ReportIncident_NonEmergency_HateSpeech_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_SexualHarassment );
		unset( $data->ReportIncident_NonEmergency_SexualHarassment_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Sockpuppetry );
		unset( $data->ReportIncident_NonEmergency_Sockpuppetry_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_SomethingElse );
		unset( $data->ReportIncident_NonEmergency_Spam );
		unset( $data->ReportIncident_NonEmergency_Spam_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Spam_SpamContentURL );
		unset( $data->ReportIncident_NonEmergency_Other );
		unset( $data->ReportIncident_NonEmergency_Other_DisputeResolutionURL );
		unset( $data->ReportIncident_NonEmergency_Other_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_SomethingElse_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Trolling );
		unset( $data->ReportIncident_NonEmergency_Trolling_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_UserDispute );
		unset( $data->ReportIncident_NonEmergency_UserDispute_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Vandalism );
		unset( $data->ReportIncident_NonEmergency_Vandalism_HelpMethod );
		unset( $data->ReportIncident_NonEmergency_Intimidation );
		unset( $data->ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL );
		unset( $data->ReportIncident_NonEmergency_Intimidation_HelpMethod );
		return $data;
	}
}
