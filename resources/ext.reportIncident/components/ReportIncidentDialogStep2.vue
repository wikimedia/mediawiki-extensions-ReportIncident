<template>
	<form id="reportincident-form" class="ext-reportincident-dialog-step2">
		<!-- type of harassment -->
		<cdx-field
			:is-fieldset="true"
			:status="harassmentStatus"
			:messages="formErrorMessages.inputBehaviors"
			class="ext-reportincident-dialog-step2__harassment-options">
			<template #label>
				{{ $i18n( 'reportincident-dialog-harassment-type-label' ).text() }}
			</template>
			<cdx-checkbox
				v-for="checkbox in harassmentOptions"
				:id="'ext-reportincident-dialog-option__' + checkbox.value"
				:key="'checkbox-' + checkbox.value"
				v-model="inputBehaviors"
				:input-value="checkbox.value">
				{{ checkbox.label }}
			</cdx-checkbox>
			<cdx-text-area
				v-if="collectSomethingElseDetails"
				v-model="inputSomethingElseDetails"
				class="ext-reportincident-dialog-step2__something-else-textarea"
				:placeholder="$i18n(
					'reportincident-dialog-something-else-input-placeholder'
				).text()"
				@focusout="displaySomethingElseTextboxRequiredError = true"
			></cdx-text-area>
		</cdx-field>

		<!-- who is violating behavior guidelines -->
		<cdx-field
			class="ext-reportincident-dialog-step2__form-item
							ext-reportincident-dialog-step2__violator-name"
			:status="reportedUserStatus"
			:messages="formErrorMessages.inputReportedUser"
			@focusout="displayReportedUserRequiredError = true"
		>
			<template #label>
				{{ $i18n( 'reportincident-dialog-violator-label' ).text() }}
			</template>
			<cdx-lookup
				v-model:selected="inputReportedUserSelection"
				:placeholder="$i18n( 'reportincident-dialog-violator-placeholder-text' ).text()"
				:menu-items="inputReportedUserMenuItems"
				:menu-config="reportedUserLookupMenuConfig"
				@input="onReportedUserInput"
				@update:selected="onReportedUserSelected"
			>
			</cdx-lookup>
		</cdx-field>

		<!-- Additional details -->
		<cdx-field
			:optional-flag="$i18n( 'reportincident-dialog-optional-label' ).text()"
			class="ext-reportincident-dialog-step2__form-item
							ext-reportincident-dialog-step2__additional-details">
			<template #label>
				{{ $i18n( 'reportincident-dialog-additional-details-input-label' ).text() }}
			</template>
			<cdx-text-area
				v-model="inputDetails"
				:placeholder="$i18n(
					'reportincident-dialog-additional-details-input-placeholder'
				).text()">
			</cdx-text-area>
		</cdx-field>
	</form>
</template>

<script>

const Constants = require( '../Constants.js' );
const useFormStore = require( '../stores/Form.js' );
const { CdxCheckbox, CdxField, CdxLookup, CdxTextArea } = require( '@wikimedia/codex' );
const { storeToRefs } = require( 'pinia' );
const { computed, ref, onMounted, onUnmounted } = require( 'vue' );

// @vue/component
module.exports = exports = {
	name: 'ReportIncidentDialogStep2',
	components: {
		CdxCheckbox,
		CdxField,
		CdxLookup,
		CdxTextArea
	},
	setup() {
		const store = useFormStore();

		const {
			inputBehaviors,
			inputReportedUser,
			displayReportedUserRequiredError,
			inputSomethingElseDetails,
			displaySomethingElseTextboxRequiredError,
			inputDetails
		} = storeToRefs( store );

		const inputReportedUserSelection = ref( '' );
		const windowHeight = ref( window.innerHeight );
		const suggestedUsernames = ref( [] );

		let debounce = null;

		/**
		 * Called when the browser window is resized.
		 *
		 * This function updates the reference containing
		 * the current height of the window to adjust the
		 * number of menu items shown.
		 */
		function onWindowResize() {
			windowHeight.value = window.innerHeight;
		}

		// Call the onWindowResize on the "resize" event once the
		// second step is mounted.
		onMounted( () => {
			window.addEventListener( 'resize', onWindowResize );
		} );

		// Stop calling the onWindowResize method when the component
		// is unmounted (after successful submission, closing the dialog,
		// or navigating back to the first step).
		onUnmounted( () => {
			window.removeEventListener( 'resize', onWindowResize );
		} );

		const harassmentOptions = [
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-hate-speech-or-discrimination' ),
				value: Constants.harassmentTypes.HATE_SPEECH
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-sexual-harassment' ),
				value: Constants.harassmentTypes.SEXUAL_HARASSMENT
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-threats-of-violence' ),
				value: Constants.harassmentTypes.THREATS_OR_VIOLENCE
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-intimidation' ),
				value: Constants.harassmentTypes.INTIMIDATION_AGGRESSION
			},
			{
				label: mw.msg( 'reportincident-dialog-harassment-type-something-else' ),
				value: Constants.harassmentTypes.OTHER
			}
		];

		const harassmentStatus = computed( () => {
			return store.formErrorMessages.inputBehaviors ? 'error' : 'default';
		} );

		const reportedUserStatus = computed( () => {
			return store.formErrorMessages.inputReportedUser ? 'error' : 'default';
		} );

		const formErrorMessages = computed( () => {
			return store.formErrorMessages;
		} );

		/**
		 * Whether the "Something else" textbox value should be sent
		 * in the request body to the REST endpoint on form submission.
		 */
		const collectSomethingElseDetails = computed( () => {
			return inputBehaviors.value.filter(
				( input ) => input === Constants.harassmentTypes.OTHER
			).length > 0;
		} );

		/**
		 * The menu items for the reported user Codex Lookup component.
		 */
		const inputReportedUserMenuItems = computed( () => {
			return suggestedUsernames.value.map( ( user ) => {
				return { value: user.name };
			} );
		} );

		/**
		 * The configuration settings for the Codex Lookup reported username
		 * component.
		 *
		 * This sets the visibleItemLimit to a proportion of the height such
		 * that the dropdown menu should not overflow the bottom of the dialog.
		 */
		const reportedUserLookupMenuConfig = computed( () => {
			return {
				visibleItemLimit: Math.min(
					Math.max(
						Math.floor( windowHeight.value / 150 ),
						2
					),
					5
				)
			};
		} );

		/**
		 * Load username suggestions for the username lookup component
		 * using the 'allusers' query API. The results are set as the
		 * suggestedUsernames reference for further use.
		 *
		 * Calling this method repeatedly is safe as the API call is
		 * debounced using a 100ms delay.
		 */
		function loadSuggestedUsernames() {
			// Clear any other yet to be run API calls to get the suggested usernames.
			clearTimeout( debounce );
			const currentValue = inputReportedUser.value;
			// Do nothing if we have no input.
			if ( !currentValue ) {
				suggestedUsernames.value = [];
				return;
			}

			// Debounce the API calls using a 100ms delay.
			debounce = setTimeout( () => {
				new mw.Api().get( {
					action: 'query',
					list: 'allusers',
					auprefix: currentValue,
					limit: '10'
				} ).then( function ( data ) {
					// If the current value of inputReportedUser
					// has been changed since the API call was first
					// made then ignore this response.
					if ( inputReportedUser.value !== currentValue ) {
						return;
					}

					// If the API errors or returns an unexpected structure,
					// then just display no suggestions.
					if (
						!data ||
						!data.query ||
						!data.query.allusers ||
						!Array.isArray( data.query.allusers ) ||
						data.query.allusers.length === 0
					) {
						suggestedUsernames.value = [];
						return;
					}

					suggestedUsernames.value = data.query.allusers;
				} ).catch( function ( error ) {
					suggestedUsernames.value = [];
					mw.log.error( error );
				} );
			}, 100 );
		}

		/**
		 * Called when the reported username component has an "input" event fired.
		 * This function updates the form state and asks for username suggestions
		 * for the lookup dropdown.
		 *
		 * @param {string} value The new value of the reported username component.
		 */
		function onReportedUserInput( value ) {
			// Keep a track of the actual text in the input for the form store.
			inputReportedUser.value = value;
			// Load suggestions based on the input already entered.
			loadSuggestedUsernames();
		}

		/**
		 * Called when a suggested username is selected by the user
		 * from the items in the dropdown menu.
		 *
		 * This disables the required field error and updates the
		 * form state.
		 */
		function onReportedUserSelected() {
			displayReportedUserRequiredError.value = false;
			inputReportedUser.value = inputReportedUserSelection.value;
		}

		return {
			harassmentOptions,
			inputBehaviors,
			inputDetails,
			inputSomethingElseDetails,
			inputReportedUserSelection,
			inputReportedUserMenuItems,
			reportedUserLookupMenuConfig,
			displaySomethingElseTextboxRequiredError,
			displayReportedUserRequiredError,
			collectSomethingElseDetails,
			formErrorMessages,
			harassmentStatus,
			reportedUserStatus,
			onReportedUserInput,
			onReportedUserSelected,
			// Used in tests, so needs to be passed out here.
			/* eslint-disable vue/no-unused-properties */
			windowHeight,
			suggestedUsernames
			/* eslint-enable vue/no-unused-properties */
		};
	}
};
</script>

<style lang="less">
@import ( reference ) 'mediawiki.skin.variables.less';

.ext-reportincident-dialog-step2 {
	&__form-item {
		margin-top: @spacing-125;
		margin-bottom: @spacing-125;
	}
}
</style>
