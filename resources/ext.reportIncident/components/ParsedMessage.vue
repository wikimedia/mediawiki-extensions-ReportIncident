<template>
	<!-- eslint-disable-next-line vue/no-v-html -->
	<p v-html="content"></p>
</template>

<script>
const { defineComponent, computed } = require( 'vue' );

// A rendered MediaWiki message, with any links within set to open in a new tab.
module.exports = exports = defineComponent( {
	name: 'ParsedMessage',
	props: {
		/**
		 * A MediaWiki message instance as returned by mw.message().
		 */
		message: { type: Object, required: true }
	},
	setup( props ) {
		const content = computed( () => {
			if ( !props.message ) {
				return '';
			}

			// Update the "target" attribute of anchors within the rendered
			// message content to ensure they all open in a new tab.
			const $parsedMsg = $( `<div>${ props.message.parse() }</div>` );
			$parsedMsg.find( 'a' ).attr( 'target', '_blank' );
			return $parsedMsg.html();
		} );

		return { content };
	}
} );
</script>
