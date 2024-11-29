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

		mockMediaWikiStringCodePointLength.mockImplementation( ( str ) => str.length );

		const mwConvertNumber = jest.fn();
		mwConvertNumber.mockImplementation( ( number ) => String( number ) );
		mw.language.convertNumber = mwConvertNumber;
	} );

	it( 'should update content and not show character count when far from the limit', async () => {
		const wrapper = mount( CharacterLimitedTextArea, {
			props: { codePointLimit: 600, textContent: '' }
		} );

		const initialCharacterCount = wrapper.find( '.ext-reportincident-dialog__textarea-character-count' );

		await wrapper.find( 'textarea' ).setValue( 'test' );

		const newCharacterCount = wrapper.find( '.ext-reportincident-dialog__textarea-character-count' );

		// We expect two events to have been handled ('input' and 'change')
		const emitted = wrapper.emitted();

		expect( emitted[ 'update:text-content' ] ).toStrictEqual( [ [ 'test' ], [ 'test' ] ] );
		expect( initialCharacterCount.exists() ).toBe( false );
		expect( newCharacterCount.exists() ).toBe( false );
	} );

	it( 'shows character count when near the limit', async () => {
		const wrapper = mount( CharacterLimitedTextArea, {
			props: { codePointLimit: 100, textContent: '' }
		} );

		const initialCharacterCount = wrapper.find( '.ext-reportincident-dialog__textarea-character-count' );

		await wrapper.find( 'textarea' ).setValue( 'test' );

		const newCharacterCount = wrapper.find( '.ext-reportincident-dialog__textarea-character-count' );

		expect( initialCharacterCount.exists() ).toBe( false );
		expect( newCharacterCount.text() ).toBe( '96' );
	} );

	it( 'does not show character count without input even if initial limit is small', async () => {
		const wrapper = mount( CharacterLimitedTextArea, {
			props: { codePointLimit: 50, textContent: '' }
		} );

		const initialCharacterCount = wrapper.find( '.ext-reportincident-dialog__textarea-character-count' );

		expect( initialCharacterCount.exists() ).toBe( false );
	} );

	it( 'should forward other props to textarea wrapper component', () => {
		const wrapper = mount( CharacterLimitedTextArea, {
			props: { codePointLimit: 600, textContent: '', class: 'foo' }
		} );

		expect( wrapper.find( '.cdx-text-area' ).classes() ).toContain( 'foo' );
	} );
} );
