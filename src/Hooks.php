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

use MediaWiki\Config\Config;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Output\OutputPage;

class Hooks implements
	BeforePageDisplayHook,
	SidebarBeforeOutputHook,
	SkinTemplateNavigation__UniversalHook
{

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		// If the button is disabled, don't do anything.
		// FIXME: Replace this hook implementation with DiscussionTools integration (T340137)
		$config = $out->getConfig();
		if ( !$config->get( 'ReportIncidentReportButtonEnabled' ) ) {
			return;
		}

		// Only add button if in configured namespace and skin
		if ( in_array( $out->getTitle()->getNamespace(),
				$config->get( 'ReportIncidentEnabledNamespaces' ) ) &&
			in_array( $skin->getSkinName(), $config->get( 'ReportIncidentEnabledSkins' ) ) ) {
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
			!in_array( $skin->getTitle()->getNamespace(), $config->get( 'ReportIncidentEnabledNamespaces' ) ) ||
			$skin->getSkinName() !== 'minerva' ||
			!in_array( $skin->getSkinName(), $config->get( 'ReportIncidentEnabledSkins' ) ) ||
			!$skin->getUser()->isNamed()
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
			!in_array( $sktemplate->getTitle()->getNamespace(), $config->get( 'ReportIncidentEnabledNamespaces' ) ) ||
			!in_array( $sktemplate->getSkinName(), $config->get( 'ReportIncidentEnabledSkins' ) ) ||
			!$sktemplate->getUser()->isNamed()
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
			// If the current skin is minerva, then load the minerva icons for use
			// in the overflow menu.
			$output->addModuleStyles( 'ext.reportIncident.minervaicons' );
		}
	}
}
