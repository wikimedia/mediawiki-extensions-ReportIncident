<template>
	<cdx-text-area
		ref="textAreaRef"
		v-model="computedTextContent"
		@input="updateCharacterCount"
	></cdx-text-area>
</template>

<script>
const { CdxTextArea } = require( '@wikimedia/codex' );
const { computed, onMounted, ref } = require( 'vue' );
const { codePointLength } = require( 'mediawiki.String' );

// A Codex textarea with a character limit.
// @vue/component
module.exports = exports = {
	name: 'CharacterLimitedTextArea',
	components: {
		CdxTextArea
	},
	props: {
		/**
		 * The value of this text field.
		 * Must be bound with `v-model:text-content`.
		 */
		textContent: { type: String, required: true },
		/**
		 * The number of characters this text area can still accept, as a localized numeric string.
		 * Must be bound with `v-model:remaining-characters`.
		 */
		remainingCharacters: { type: String, required: true }
	},
	emits: [
		'update:remaining-characters',
		'update:text-content'
	],
	setup( props, ctx ) {
		const codePointLimit = mw.config.get( 'wgCommentCodePointLimit' );
		const textAreaRef = ref( null );

		const computedTextContent = computed( {
			get: () => props.textContent,
			set: ( value ) => ctx.emit( 'update:text-content', value )
		} );

		const computedRemainingCharacters = computed( {
			get: () => props.remainingCharacters,
			set: ( value ) => ctx.emit( 'update:remaining-characters', value )
		} );

		/**
		 * Updates the count of remaining characters when the content changes.
		 *
		 * @param {Event} event The input event that changed the content.
		 */
		function updateCharacterCount( event ) {
			const value = event.target.value;

			let remaining = codePointLimit - codePointLength( value );
			if ( remaining > 99 ) {
				remaining = '';
			} else {
				remaining = mw.language.convertNumber( remaining );
			}

			computedRemainingCharacters.value = remaining;
		}

		onMounted( () => {
			const $textarea = $( textAreaRef.value.textarea );
			$textarea.codePointLimit( codePointLimit );
			$textarea.on( 'change', () => {
				// Needed because Vue cannot listen to JQuery events, so
				// a native JS input event is needed to cause an update.
				$textarea[ 0 ].dispatchEvent( new CustomEvent( 'input' ) );
			} );
		} );

		return {
			textAreaRef,
			computedTextContent,
			updateCharacterCount
		};
	}
};
</script>
