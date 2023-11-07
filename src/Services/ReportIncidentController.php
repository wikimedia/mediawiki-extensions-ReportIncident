<?php

namespace MediaWiki\Extension\ReportIncident\Services;

use IContextSource;
use MediaWiki\Config\Config;
use MediaWiki\Output\OutputPage;
use MediaWiki\User\User;

/**
 * Controls whether the reporting links and dialog should be shown.
 */
class ReportIncidentController {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Should the reporting link / button be shown in the current namespace
	 *
	 * @param int $namespace
	 * @return bool
	 */
	private function shouldShowButtonForNamespace( int $namespace ): bool {
		return in_array( $namespace, $this->config->get( 'ReportIncidentEnabledNamespaces' ) );
	}

	/**
	 * Should the reporting link / button be shown for the current skin.
	 * The name of the skin can be got from Skin::getSkinName.
	 *
	 * @param ?string $skin Supports null to be compatible with Skin::getSkinName that could
	 *   return null. On null, this method always returns false.
	 * @return bool
	 */
	private function shouldShowButtonForSkin( ?string $skin ): bool {
		$enabledSkins = $this->config->get( 'ReportIncidentEnabledSkins' );
		if ( in_array( '*', $enabledSkins ) ) {
			return true;
		}
		if ( $skin === null ) {
			return false;
		}
		return in_array( $skin, $enabledSkins );
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
	 * namespace, skin and user.
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
			$this->shouldShowButtonForSkin( $context->getSkin()->getSkinName() ) &&
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
		$output->addJsConfigVars( [
			// Add the link to the administrators page for use by the dialog.
			'wgReportIncidentAdministratorsPage' => $this->config->get( 'ReportIncidentAdministratorsPage' ),
			// If in developer mode, pretend the user has a confirmed email if the query parameter is set to
			// 'withconfirmedemail=1', otherwise use DB value.
			'wgReportIncidentUserHasConfirmedEmail' => $pretendUserHasConfirmedEmail ?: $user->isEmailConfirmed(),
		] );
		// Add the ReportIncident module, including the JS and Vue code for the dialog.
		$output->addModules( 'ext.reportIncident' );
		if ( $output->getSkin()->getSkinName() === 'minerva' ) {
			// Load custom menu styles for Minerva; see the 'skinStyles' property of the RL module
			// loaded below.
			$output->addModuleStyles( 'ext.reportIncident.menuStyles' );
		}
	}
}
