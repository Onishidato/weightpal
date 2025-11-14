(function () {
	'use strict';

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function () {
		const forms = document.querySelectorAll('#weightpal-advisor-form');

		forms.forEach(function (form) {
			const container = form.closest('.weightpal-advisor-block');
			const loadingDiv = container.querySelector('.weightpal-loading');
			const responseDiv = container.querySelector('.weightpal-response');
			const errorDiv = container.querySelector('.weightpal-error');
			const errorMessage = container.querySelector('.weightpal-error-message');

			form.addEventListener('submit', function (e) {
				e.preventDefault();

				// Get form data
				const weight = parseFloat(form.querySelector('[name="weight"]').value);
				const height = parseFloat(form.querySelector('[name="height"]').value);
				const routine = form.querySelector('[name="routine"]').value.trim();
				const userQuery = form.querySelector('[name="userQuery"]').value.trim();

				// Validate inputs
				if (!weight || weight <= 0) {
					showError(weightpalData.messages.invalidWeight);
					return;
				}

				if (!height || height <= 0) {
					showError(weightpalData.messages.invalidHeight);
					return;
				}

				if (!routine || !userQuery) {
					showError(weightpalData.messages.required);
					return;
				}

				// Hide all sections
				form.style.display = 'none';
				errorDiv.style.display = 'none';
				responseDiv.style.display = 'none';
				loadingDiv.style.display = 'block';

				// Prepare request data
				const requestData = {
					weight: weight,
					height: height,
					routine: routine,
					userQuery: userQuery,
				};

				// Make API request
				fetch(weightpalData.apiUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': weightpalData.nonce,
					},
					body: JSON.stringify(requestData),
				})
					.then(function (response) {
						if (!response.ok) {
							return response.json().then(function (error) {
								throw new Error(error.message || weightpalData.messages.error);
							});
						}
						return response.json();
					})
					.then(function (data) {
						loadingDiv.style.display = 'none';

						if (data.success) {
							displayResponse(data.data, data.bmi);
						} else {
							showError(data.message || weightpalData.messages.error);
						}
					})
					.catch(function (error) {
						loadingDiv.style.display = 'none';
						showError(error.message || weightpalData.messages.error);
					});
			});

			// Display response
			function displayResponse(advice, bmi) {
				const bmiValue = container.querySelector('.weightpal-bmi-value');
				const adviceContent = container.querySelector('.weightpal-advice-content');

				// Set BMI
				bmiValue.textContent = bmi;

				// Add BMI category class
				bmiValue.className = 'weightpal-bmi-value';
				if (bmi < 18.5) {
					bmiValue.classList.add('underweight');
				} else if (bmi >= 18.5 && bmi < 25) {
					bmiValue.classList.add('normal');
				} else if (bmi >= 25 && bmi < 30) {
					bmiValue.classList.add('overweight');
				} else {
					bmiValue.classList.add('obese');
				}

				// Convert markdown-like formatting to HTML
				let formattedAdvice = advice
					.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
					.replace(/\n\n/g, '</p><p>')
					.replace(/\n/g, '<br>');

				adviceContent.innerHTML = '<p>' + formattedAdvice + '</p>';

				responseDiv.style.display = 'block';
			}

			// Show error
			function showError(message) {
				errorMessage.textContent = message;
				errorDiv.style.display = 'block';
			}

			// New query button
			const newQueryButton = container.querySelector('.weightpal-new-query-button');
			if (newQueryButton) {
				newQueryButton.addEventListener('click', function () {
					form.reset();
					responseDiv.style.display = 'none';
					form.style.display = 'block';
				});
			}

			// Try again button
			const tryAgainButton = container.querySelector('.weightpal-try-again-button');
			if (tryAgainButton) {
				tryAgainButton.addEventListener('click', function () {
					errorDiv.style.display = 'none';
					form.style.display = 'block';
				});
			}
		});
	});
})();
