/* eslint-disable no-undef */
const { config } = require( '@vue/test-utils' );
// Mock Vue plugins in test suites
config.global.mocks = {
	$i18n: ( str ) => {
		return {
			text: () => str,
			parse: () => str
		};
	}
};
config.global.directives = {
	'i18n-html': ( el, binding ) => {
		el.innerHTML = `${binding.arg} (${binding.value})`;
	}
};
// Assign things to "global" here if you want them to be globally available during tests
global.$ = require( 'jquery' );
