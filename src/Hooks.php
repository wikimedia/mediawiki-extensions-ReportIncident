<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\ReportIncident;

use IContextSource;
use MediaWiki\Config\Config;
use MediaWiki\Extension\DiscussionTools\Hooks\DiscussionToolsAddOverflowMenuItemsHook;
use MediaWiki\Extension\DiscussionTools\OverflowMenuItem;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\User\User;

class Hooks implements
	BeforePageDisplayHook,
	SidebarBeforeOutputHook,
	SkinTemplateNavigation__UniversalHook,
	DiscussionToolsAddOverflowMenuItemsHook
{

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		// If the button feature flag is disabled, don't do anything.
		$config = $out->getConfig();
		if ( !$config->get( 'ReportIncidentReportButtonEnabled' ) ) {
			return;
		}

		// Only add HTML if in configured namespace and skin.
		// Named user check happens in DiscussionTools and menu tools hooks.
		if ( $this->shouldShowButtonForNamespace( $out->getTitle()->getNamespace(), $config ) &&
			$this->shouldShowButtonForSkin( $skin->getSkinName(), $config ) ) {
			$out->addHtml( '<div id="ext-reportincident-app"></div>' );
		}
	}

	/** @inheritDoc */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		$config = $skin->getConfig();
		// Don't show the link to report in minerva overflow menu if any of the following apply:
		// * The link is disabled
		// * The current title is not in a supported namespace
		// * The skin being used by the user is not minerva
		// * The minerva skin is not supported
		// * The user is not named
		if (
			!$config->get( 'ReportIncidentReportButtonEnabled' ) ||
			!$this->shouldShowButtonForNamespace( $skin->getTitle()->getNamespace(), $config ) ||
			$skin->getSkinName() !== 'minerva' ||
			!$this->shouldShowButtonForSkin( $skin->getSkinName(), $config ) ||
			!$this->shouldShowButtonForUser( $skin->getUser() )
		) {
			return;
		}

		$this->addModulesAndConfigVars( $skin->getOutput(), $config, $skin->getSkinName() );
		$sidebar['TOOLBOX']['reportincident'] = [
			'class' => 'ext-reportincident-link',
			'text' => $skin->msg( 'reportincident-report-btn-label' )->text(),
			'href' => '#',
			'icon' => 'flag',
		];
	}

	/**
	 * @inheritDoc
	 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$config = $sktemplate->getConfig();
		// Don't show the link to report in the tools menu if any of the following apply:
		// * The link is disabled
		// * The current title is not in a supported namespace
		// * The skin being used by the user is not supported
		// * The user is not named.
		if (
			!$config->get( 'ReportIncidentReportButtonEnabled' ) ||
			!$this->shouldShowButtonForNamespace( $sktemplate->getTitle()->getNamespace(), $config ) ||
			!$this->shouldShowButtonForSkin( $sktemplate->getSkinName(), $config ) ||
			!$this->shouldShowButtonForUser( $sktemplate->getUser() )
		) {
			return;
		}

		$this->addModulesAndConfigVars( $sktemplate->getOutput(), $config, $sktemplate->getSkinName() );
		$links['actions']['reportincident'] = [
			'class' => 'ext-reportincident-link',
			'text' => $sktemplate->msg( 'reportincident-report-btn-label' )->text(),
			'href' => '#',
			'icon' => 'flag',
		];
	}

	/**
	 * Load the modules and associated JS configuration variables
	 * to allow use of the ReportIncident dialog.
	 *
	 * @param OutputPage $output
	 * @param Config $config
	 * @param string|null $skinName The name of the skin being used, which can be got from Skin::getSkinName.
	 * @return void
	 */
	protected function addModulesAndConfigVars( OutputPage $output, Config $config, ?string $skinName ): void {
		// This method is protected to allow mocking this method in the tests.
		//
		// Add the link to the administrators page for use by the dialog.
		$output->addJsConfigVars( [
			'wgReportIncidentAdministratorsPage' => $config->get( 'ReportIncidentAdministratorsPage' )
		] );
		// Add the ReportIncident module, including the JS and Vue code for the dialog.
		$output->addModules( 'ext.reportIncident' );
		if ( $skinName === 'minerva' ) {
			// Load custom menu styles for Minerva; see the 'skinStyles' property of the RL module
			// loaded below.
			$output->addModuleStyles( 'ext.reportIncident.menuStyles' );
		}
	}

	/** @inheritDoc */
	public function onDiscussionToolsAddOverflowMenuItems(
		array &$overflowMenuItems,
		array &$resourceLoaderModules,
		bool $sectionHeadingIsEditable,
		array $threadItemData,
		IContextSource $contextSource
	) {
		$config = $contextSource->getConfig();
		$title = $contextSource->getTitle();
		$skin = $contextSource->getSkin();
		$user = $contextSource->getUser();
		if ( !$config->get( 'ReportIncidentReportButtonEnabled' ) ) {
			return;
		}
		if ( $this->shouldShowButtonForNamespace( $title->getNamespace(), $config ) &&
			$this->shouldShowButtonForSkin( $skin->getSkinName(), $config ) &&
			$this->shouldShowButtonForUser( $user ) ) {
			$overflowMenuItems[] = new OverflowMenuItem(
				'reportincident',
				'flag',
				$contextSource->msg( 'reportincident-report-btn-label' ),
				0,
				[ 'thread-id' => $threadItemData['id'] ]
			);
			$resourceLoaderModules[] = 'ext.reportIncident';
		}
	}

	private function shouldShowButtonForNamespace( int $namespace, Config $config ): bool {
		return in_array( $namespace, $config->get( 'ReportIncidentEnabledNamespaces' ) );
	}

	private function shouldShowButtonForSkin( string $skin, Config $config ): bool {
		return in_array( $skin, $config->get( 'ReportIncidentEnabledSkins' ) );
	}

	private function shouldShowButtonForUser( User $user ): bool {
		return $user->isNamed();
	}
}
