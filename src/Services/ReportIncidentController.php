<?php

namespace MediaWiki\Extension\ReportIncident\Services;

use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\ReportIncident\Api\Rest\Handler\ReportHandler;
use MediaWiki\Output\OutputPage;
use MediaWiki\User\User;

/**
 * Controls whether the reporting links and dialog should be shown.
 */
class ReportIncidentController {

	public function __construct(
		private readonly Config $config,
		private readonly Config $localConfig,
	) {
	}

	/**
	 * Should the reporting link / button be shown in the current namespace
	 *
	 * @param int $namespace
	 * @return bool
	 */
	private function shouldShowButtonForNamespace( int $namespace ): bool {
		return in_array( $namespace, $this->getLocalConfig( 'ReportIncidentEnabledNamespaces' ) );
	}

	/**
	 * Should the reporting link / button be shown for the current user
	 *
	 * @param User $user
	 * @return bool
	 */
	private function shouldShowButtonForUser( User $user ): bool {
		return $user->isNamed();
	}

	/**
	 * Is the reporting button / link (and reporting dialog) enabled.
	 *
	 * @return bool
	 */
	private function isButtonEnabled(): bool {
		return $this->config->get( 'ReportIncidentReportButtonEnabled' );
	}

	/**
	 * Should the reporting link / button be shown for the current
	 * namespace and user.
	 *
	 * This can also be used to determine whether to add the HTML
	 * for the reporting dialog in a given request.
	 *
	 * @param IContextSource $context The context associated with the current request.
	 * @return bool Whether the button / link should be shown.
	 */
	public function shouldAddMenuItem( IContextSource $context ): bool {
		return $this->isButtonEnabled() &&
			$this->shouldShowButtonForNamespace( $context->getTitle()->getNamespace() ) &&
			$this->shouldShowButtonForUser( $context->getUser() );
	}

	/**
	 * Load the modules and associated JS configuration variables
	 * to allow use of the ReportIncident dialog.
	 *
	 * Should only be called after the other methods in this
	 * service to check if the button should be shown are
	 * consulted.
	 *
	 * @param OutputPage $output The OutputPage object associated with the current response
	 * @return void
	 */
	public function addModulesAndConfigVars( OutputPage $output ): void {
		$user = $output->getUser();
		$isDeveloperMode = $this->config->get( 'ReportIncidentDeveloperMode' );
		$pretendUserHasConfirmedEmail = $isDeveloperMode && $output->getRequest()->getBool( 'withconfirmedemail' );
		$pretendUserHasEmail = $isDeveloperMode && $output->getRequest()->getBool( 'withemail' );

		$communityConfigIntimidationHelpMethods = $this->localConfig
			->get( 'ReportIncident_NonEmergency_Intimidation_HelpMethod' );
		$communityConfigDoxingHelpMethods = $this->localConfig
			->get( 'ReportIncident_NonEmergency_Doxing_HelpMethod' );
		$communityConfigSexualHarassmentHelpMethods = $this->localConfig
			->get( 'ReportIncident_NonEmergency_SexualHarassment_HelpMethod' );
		$communityConfigTrollingHelpMethods = $this->localConfig
			->get( 'ReportIncident_NonEmergency_Trolling_HelpMethod' );
		$output->addJsConfigVars( [
			// If in developer mode, pretend the user has a confirmed email if the query parameter is set to
			// 'withconfirmedemail=1', otherwise use DB value.
			'wgReportIncidentUserHasConfirmedEmail' => $pretendUserHasConfirmedEmail ?: $user->isEmailConfirmed(),
			// If in developer mode, pretend the user has an email set if the query parameter is set to
			// 'withemail=1', otherwise use DB value.
			'wgReportIncidentUserHasEmail' => $pretendUserHasEmail ?: $user->getEmail() !== '',
			// Add wiki-specific links used by the submit success step (T379242).
			'wgReportIncidentLocalLinks' => [
				'disputeResolution' => $this->getLocalConfig( 'ReportIncidentDisputeResolutionPage' ),
				'localIncidentReport' => $this->getLocalConfig( 'ReportIncidentLocalIncidentReportPage' ),
				'askTheCommunity' => $this->getLocalConfig( 'ReportIncidentCommunityQuestionsPage' )
			],
			// Control whether instrumentation is enabled pending approval (T372823).
			'wgReportIncidentEnableInstrumentation' => $this->config->get( 'ReportIncidentEnableInstrumentation' ),
			'wgReportIncidentDetailsCodePointLength' => ReportHandler::MAX_DETAILS_LENGTH,
			'wgReportIncidentUseV2NonEmergencyFlow' => $this->config->get( 'ReportIncidentUseV2NonEmergencyFlow' ),
			// Non-Emergency Intimidation CommunityConfig values
			'wgReportIncidentNonEmergencyIntimidationDisputeResolutionURL' =>
				$this->localConfig->get( 'ReportIncident_NonEmergency_Intimidation_DisputeResolutionURL' ),
			// Non-Emergency help methods
			'wgReportIncidentNonEmergencyIntimidationHelpMethodContactAdmin' =>
				$communityConfigIntimidationHelpMethods->ContactAdmin,
			'wgReportIncidentNonEmergencyIntimidationHelpMethodEmail' =>
				$communityConfigIntimidationHelpMethods->Email,
			'wgReportIncidentNonEmergencyIntimidationHelpMethodContactCommunity' =>
				$communityConfigIntimidationHelpMethods->ContactCommunity,
			'wgReportIncidentNonEmergencyDoxingShowWarning' =>
				$this->localConfig->get( 'ReportIncident_NonEmergency_Doxing_ShowWarning' ),
			'wgReportIncidentNonEmergencyDoxingHideEditURL' =>
				$this->localConfig->get( 'ReportIncident_NonEmergency_Doxing_HideEditURL' ),
			// For doxing help methods, disable all other configured methods if stewards email is enabled
			'wgReportIncidentNonEmergencyDoxingHelpMethodWikiEmailURL' =>
				$communityConfigDoxingHelpMethods->EmailStewards ? '' : $communityConfigDoxingHelpMethods->WikiEmailURL,
			'wgReportIncidentNonEmergencyDoxingHelpMethodEmail' =>
				$communityConfigDoxingHelpMethods->EmailStewards ? '' : $communityConfigDoxingHelpMethods->Email,
			'wgReportIncidentNonEmergencyDoxingHelpMethodOtherURL' =>
				$communityConfigDoxingHelpMethods->EmailStewards ? '' : $communityConfigDoxingHelpMethods->OtherURL,
			'wgReportIncidentNonEmergencyDoxingHelpMethodEmailStewards' =>
				$communityConfigDoxingHelpMethods->EmailStewards,
			'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactAdmin' =>
				$communityConfigSexualHarassmentHelpMethods->ContactAdmin,
			'wgReportIncidentNonEmergencySexualHarassmentHelpMethodEmail' =>
				$communityConfigSexualHarassmentHelpMethods->Email,
			'wgReportIncidentNonEmergencySexualHarassmentHelpMethodContactCommunity' =>
				$communityConfigSexualHarassmentHelpMethods->ContactCommunity,
			'wgReportIncidentNonEmergencyTrollingHelpMethodContactAdmin' =>
				$communityConfigTrollingHelpMethods->ContactAdmin,
			'wgReportIncidentNonEmergencyTrollingHelpMethodEmail' =>
				$communityConfigTrollingHelpMethods->Email,
			'wgReportIncidentNonEmergencyTrollingHelpMethodContactCommunity' =>
				$communityConfigTrollingHelpMethods->ContactCommunity,
		] );
		// Add the ReportIncident module, including the JS and Vue code for the dialog.
		$output->addModules( 'ext.reportIncident' );
		if ( $output->getSkin()->getSkinName() === 'minerva' ) {
			// Load custom menu styles for Minerva; see the 'skinStyles' property of the RL module
			// loaded below.
			$output->addModuleStyles( 'ext.reportIncident.menuStyles' );
		}
	}

	/**
	 * Get a config option from IRS's community configuration,
	 * falling back to the global config if no value was set.
	 *
	 * @param string $key The key to look up in the configuration.
	 * @return mixed
	 */
	private function getLocalConfig( string $key ) {
		$localValue = $this->localConfig->get( $key );
		return $localValue ?: $this->config->get( $key );
	}
}
