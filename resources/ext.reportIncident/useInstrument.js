'use strict';

/**
 * Additional context for an instrumentation event.
 *
 * @typedef {Object} InteractionData
 * @property {string} [subType]
 * @property {string} [source]
 * @property {string} [context]
 */

/**
 * @callback LogEvent Log an event to the IRS event stream.
 *
 * @param {string} action
 * @param {InteractionData} [data]
 */

/**
 * Create an event logging function configured to log events to the IRS event stream.
 *
 * @return {LogEvent}
 */
const useInstrument = () => {
	// Disable instrumentation by default pending approval (T372823).
	if ( !mw.config.get( 'wgReportIncidentEnableInstrumentation' ) ) {
		return () => {};
	}

	const instrument = mw.eventLog.newInstrument(
		'mediawiki.incident_reporting_system_interaction',
		'/analytics/product_metrics/web/base/1.3.0'
	);

	return ( action, data = {} ) => {
		const interactionData = {};

		if ( data.subType ) {
			// eslint-disable-next-line camelcase
			interactionData.action_subtype = data.subType;
		}

		if ( data.source ) {
			// eslint-disable-next-line camelcase
			interactionData.action_source = data.source;
		}

		if ( data.context ) {
			// eslint-disable-next-line camelcase
			interactionData.action_context = data.context;
		}

		instrument.submitInteraction( action, interactionData );
	};
};

module.exports = useInstrument;
