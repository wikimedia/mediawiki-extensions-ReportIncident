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

use MediaWiki\Hook\BeforePageDisplayHook;
use OutputPage;
use Skin;

class Hooks implements BeforePageDisplayHook {

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * Hook: BeforePageDisplay
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		// If the button is disabled, don't do anything.
		// FIXME: Replace this hook implementation with DiscussionTools integration (T340137)
		if ( !$out->getConfig()->get( 'ReportIncidentReportButtonEnabled' ) ) {
			return;
		}

		// Only add button if in configured namespace and skin
		if ( in_array( $out->getTitle()->getNamespace(),
				$out->getConfig()->get( 'ReportIncidentEnabledNamespaces' ) ) &&
			in_array( $skin->getSkinName(), $out->getConfig()->get( 'ReportIncidentEnabledSkins' ) ) ) {
			$out->addModules( [ 'ext.reportIncident' ] );
			$out->addHtml( '<div id="ext-reportincident-app"></div>' );
		}
	}
}
