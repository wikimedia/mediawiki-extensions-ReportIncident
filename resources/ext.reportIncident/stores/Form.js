'use strict';

const Pinia = require( 'pinia' );
const { ref, computed } = require( 'vue' );

const useFormStore = Pinia.defineStore( 'form', () => {
	const inputHarassments = ref( [ ] );

	const inputViolator = ref( '' );
	const inputEvidence = ref( '' );
	const inputDetails = ref( '' );
	const inputSomethingElseDetails = ref( '' );
	const inputEmail = ref( '' );

	const isFormValid = computed( () => {
		// every form item must be filled out except for additional details, which is optional
		// TODO (T338818): additional validation needed
		let isValid = true;

		isValid = inputHarassments.value.length > 0;
		isValid = isValid && inputViolator.value !== '';
		isValid = isValid && inputEvidence.value !== '';
		isValid = isValid && inputEmail.value !== '';

		return isValid;
	} );

	return {
		inputHarassments,
		inputViolator,
		inputDetails,
		inputSomethingElseDetails,
		inputEvidence,
		inputEmail,
		isFormValid
	};
} );

module.exports = useFormStore;
