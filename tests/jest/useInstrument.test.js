const useInstrument = require( '../../resources/ext.reportIncident/useInstrument.js' );

describe( 'useInstrument', () => {
	let submitInteraction;
	let newInstrument;

	beforeEach( () => {
		submitInteraction = jest.fn();
		newInstrument = jest.fn( () => ( {
			submitInteraction
		} ) );
		mw.eventLog = { newInstrument };
	} );

	afterEach( () => jest.resetAllMocks() );

	it( 'should record events if enabled', () => {
		const mwConfigGet = jest.spyOn( mw.config, 'get' ).mockImplementation( () => true );

		const logEvent = useInstrument();

		logEvent( 'view' );
		logEvent( 'click', { context: 'something' } );
		logEvent( 'click', { source: 'form', subType: 'foo', context: 'something' } );

		expect( mwConfigGet ).toHaveBeenCalledTimes( 1 );
		expect( mwConfigGet ).toHaveBeenCalledWith( 'wgReportIncidentEnableInstrumentation' );

		expect( newInstrument ).toHaveBeenCalledTimes( 1 );
		expect( newInstrument ).toHaveBeenCalledWith(
			'mediawiki.incident_reporting_system_interaction',
			'/analytics/product_metrics/web/base/1.3.0'
		);

		expect( submitInteraction ).toHaveBeenCalledTimes( 3 );
		expect( submitInteraction ).toHaveBeenNthCalledWith( 1, 'view', {} );
		expect( submitInteraction ).toHaveBeenNthCalledWith( 2, 'click', {
			// eslint-disable-next-line camelcase
			action_context: 'something'
		} );
		expect( submitInteraction ).toHaveBeenNthCalledWith( 3, 'click', {
			// eslint-disable-next-line camelcase
			action_source: 'form',
			// eslint-disable-next-line camelcase
			action_subtype: 'foo',
			// eslint-disable-next-line camelcase
			action_context: 'something'
		} );
	} );

	it( 'should not record events if enabled', () => {
		const mwConfigGet = jest.spyOn( mw.config, 'get' ).mockImplementation( () => false );

		const logEvent = useInstrument();

		logEvent( 'view' );

		expect( mwConfigGet ).toHaveBeenCalledTimes( 1 );
		expect( mwConfigGet ).toHaveBeenCalledWith( 'wgReportIncidentEnableInstrumentation' );

		expect( newInstrument ).not.toHaveBeenCalled();
		expect( submitInteraction ).not.toHaveBeenCalled();
	} );
} );
