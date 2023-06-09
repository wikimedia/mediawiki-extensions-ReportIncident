/* eslint-disable no-undef */
// Assign things to "global" here if you want them to be globally available during tests
global.$ = require( 'jquery' );
// mediawiki
const mockMediaWiki = require( '@wikimedia/mw-node-qunit/src/mockMediaWiki.js' );
global.mw = mockMediaWiki();
