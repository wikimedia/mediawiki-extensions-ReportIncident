'use strict';

const Pinia = require( 'pinia' );
const { ref, computed } = require( 'vue' );

const useFormStore = Pinia.defineStore( 'form', () => {
	const inputBehaviors = ref( [ ] );
	const inputReportedUser = ref( '' );
	const inputLink = ref( '' );
	const inputDetails = ref( '' );
	const inputSomethingElseDetails = ref( '' );

	const isFormValid = computed( () => {
		// every form item must be filled out except for additional details, which is optional
		// TODO (T338818): additional validation needed
		let isValid = true;

		isValid = inputBehaviors.value.length > 0;
		isValid = isValid && inputReportedUser.value !== '';
		isValid = isValid && inputLink.value !== '';

		return isValid;
	} );

	// Build an object that we can pass to the REST endpoint.
	const restPayload = computed( () => {
		const restData = {
			reportedUserId: inputReportedUser.value,
			link: inputLink.value,
			details: inputDetails.value,
			behaviors: inputBehaviors.value
		};
		if ( inputBehaviors.value.indexOf( 'something-else' ) !== -1 ) {
			restData.somethingElseDetails = inputSomethingElseDetails.value;
		}
		return restData;
	} );

	return {
		inputBehaviors,
		inputReportedUser,
		inputDetails,
		inputSomethingElseDetails,
		inputLink,
		isFormValid,
		restPayload
	};
} );

module.exports = useFormStore;
