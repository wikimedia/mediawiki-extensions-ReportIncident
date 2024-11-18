'use strict';

const { mount } = require( '@vue/test-utils' );
const { mockCodePointLength } = require( '../utils.js' );

const mockMediaWikiStringCodePointLength = mockCodePointLength();

const CharacterLimitedTextArea = require( '../../../resources/ext.reportIncident/components/CharacterLimitedTextArea.vue' );

describe( 'CharacterLimitedTextArea', () => {
	let jQueryCodePointLimitMock;
	beforeEach( () => {
		// Mock the codePointLimit which is added by a plugin.
		jQueryCodePointLimitMock = jest.fn();
		global.$.prototype.codePointLimit = jQueryCodePointLimitMock;
		// Mock wgCommentCodePointLimit to the default value of 500.
		jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => {
			switch ( key ) {
				case 'wgCommentCodePointLimit':
					return 500;
			}
		} );

		mockMediaWikiStringCodePointLength.mockImplementation( ( str ) => str.length );

		const mwConvertNumber = jest.fn();
		mwConvertNumber.mockImplementation( ( number ) => String( number ) );
		mw.language.convertNumber = mwConvertNumber;
	} );

	it( 'should update content and character count', async () => {
		const wrapper = mount( CharacterLimitedTextArea, {
			props: { textContent: '', remainingCharacters: '' }
		} );

		await wrapper.find( 'textarea' ).setValue( 'test' );

		// We expect two events to have been handled ('input' and 'change')
		const emitted = wrapper.emitted();

		expect( emitted[ 'update:remaining-characters' ] ).toStrictEqual( [ [ '' ], [ '' ] ] );
		expect( emitted[ 'update:text-content' ] ).toStrictEqual( [ [ 'test' ], [ 'test' ] ] );
	} );

	it( 'should update content and character count when near limit', async () => {
		jest.spyOn( mw.config, 'get' ).mockImplementation( ( key ) => {
			switch ( key ) {
				case 'wgCommentCodePointLimit':
					return 100;
			}
		} );

		const wrapper = mount( CharacterLimitedTextArea, {
			props: { textContent: '', remainingCharacters: '' }
		} );

		await wrapper.find( 'textarea' ).setValue( 'test' );

		// We expect two events to have been handled ('input' and 'change')
		const emitted = wrapper.emitted();

		expect( emitted[ 'update:remaining-characters' ] ).toStrictEqual( [ [ '96' ], [ '96' ] ] );
		expect( emitted[ 'update:text-content' ] ).toStrictEqual( [ [ 'test' ], [ 'test' ] ] );
	} );
} );
