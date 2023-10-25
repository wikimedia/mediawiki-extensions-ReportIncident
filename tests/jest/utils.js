'use strict';

/**
 * Mocks mw.Api().get() and returns a jest.fn()
 * that is used as the get() method. This can
 * be used to expect that the get() method is
 * called with the correct arguments.
 *
 * @param {*} returnValue
 * @return {jest.fn}
 */
function mockApiGet( returnValue ) {
	const apiGet = jest.fn();
	apiGet.mockImplementation( () => {
		return returnValue;
	} );
	jest.spyOn( mw, 'Api' ).mockImplementation( () => {
		return {
			get: apiGet
		};
	} );
	return apiGet;
}

module.exports = {
	mockApiGet: mockApiGet
};
