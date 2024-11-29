<template>
	<cdx-text-area
		ref="textAreaRef"
		v-bind="$attrs"
		v-model="computedTextContent"
		@input="updateCharacterCount"
	></cdx-text-area>
	<span
		v-if="remainingCharacters !== ''"
		class="ext-reportincident-dialog__textarea-character-count">
		{{ remainingCharacters }}
	</span>
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
	inheritAttrs: false,
	props: {
		/**
		 * The maximum number of Unicode code points accepted by this textarea.
		 */
		codePointLimit: { type: Number, required: true },
		/**
		 * The value of this text field.
		 * Must be bound with `v-model:text-content`.
		 */
		textContent: { type: String, required: true }
	},
	emits: [
		'update:remaining-characters',
		'update:text-content'
	],
	setup( props, ctx ) {
		const codePointLimit = props.codePointLimit;
		const textAreaRef = ref( null );
		const remainingCharacters = ref( '' );

		const computedTextContent = computed( {
			get: () => props.textContent,
			set: ( value ) => ctx.emit( 'update:text-content', value )
		} );

		/**
		 * Updates the count of remaining characters when the content changes.
		 *
		 * @param {Event} event The input event that changed the content.
		 */
		function updateCharacterCount( event ) {
			const value = event.target.value;

			const remaining = codePointLimit - codePointLength( value );

			// Only show the character counter as the user is approaching the limit,
			// to avoid confusion stemming from our definition of a character not matching
			// the user's own expectations of what counts as a character.
			// This is consistent with other features such as VisualEditor.
			if ( remaining > 99 ) {
				remainingCharacters.value = '';
			} else {
				remainingCharacters.value = mw.language.convertNumber( remaining );
			}
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
			remainingCharacters,
			updateCharacterCount
		};
	}
};
</script>

<style lang="less">
@import ( reference ) 'mediawiki.skin.variables.less';

.ext-reportincident-dialog__textarea-character-count {
	color: @color-subtle;
	float: right;
}
</style>
