(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var forms = document.querySelectorAll('#weightpal-meal-planner-form');

		forms.forEach(function (form) {
			var container = form.closest('.weightpal-meal-planner-block');
			var loadingDiv = container.querySelector('.weightpal-loading');
			var responseDiv = container.querySelector('.weightpal-response');
			var errorDiv = container.querySelector('.weightpal-error');
			var errorMessage = container.querySelector('.weightpal-error-message');

			form.addEventListener('submit', function (e) {
				e.preventDefault();

				// Get form data
				var dietaryPreferences = form.querySelector('[name="dietaryPreferences"]').value.trim();
				var allergies = form.querySelector('[name="allergies"]').value.trim();
				var calorieTarget = parseInt(form.querySelector('[name="calorieTarget"]').value);
				var planDays = parseInt(form.querySelector('[name="planDays"]').value);
				var additionalPreferences = form.querySelector('[name="additionalPreferences"]').value.trim();

				// Validate inputs
				if (!calorieTarget || calorieTarget <= 0) {
					showError(weightpalMealPlannerData.messages.invalidCalories);
					return;
				}

				if (!planDays || planDays <= 0) {
					showError(weightpalMealPlannerData.messages.invalidDays);
					return;
				}

				// Hide all sections
				form.style.display = 'none';
				errorDiv.style.display = 'none';
				responseDiv.style.display = 'none';
				loadingDiv.style.display = 'block';

				// Prepare request data
				var requestData = {
					dietaryPreferences: dietaryPreferences,
					allergies: allergies,
					calorieTarget: calorieTarget,
					planDays: planDays,
					additionalPreferences: additionalPreferences
				};

				// Make API request
				fetch(weightpalMealPlannerData.apiUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': weightpalMealPlannerData.nonce
					},
					body: JSON.stringify(requestData)
				})
					.then(function (response) {
						if (!response.ok) {
							return response.json().then(function (error) {
								throw new Error(error.message || weightpalMealPlannerData.messages.error);
							});
						}
						return response.json();
					})
					.then(function (data) {
						loadingDiv.style.display = 'none';

						if (data.success) {
							displayMealPlan(data.data);
						} else {
							showError(data.message || weightpalMealPlannerData.messages.error);
						}
					})
					.catch(function (error) {
						loadingDiv.style.display = 'none';
						showError(error.message || weightpalMealPlannerData.messages.error);
					});
			});

			// Display meal plan
			function displayMealPlan(mealPlan) {
				var mealPlanContent = container.querySelector('.weightpal-meal-plan-content');

				// Convert markdown-like formatting to HTML
				var formattedPlan = mealPlan
					.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
					.replace(/\n\n/g, '</p><p>')
					.replace(/\n/g, '<br>');

				mealPlanContent.innerHTML = '<p>' + formattedPlan + '</p>';

				responseDiv.style.display = 'block';
			}

			// Show error
			function showError(message) {
				errorMessage.textContent = message;
				errorDiv.style.display = 'block';
			}

			// New plan button
			var newPlanButton = container.querySelector('.weightpal-new-plan-button');
			if (newPlanButton) {
				newPlanButton.addEventListener('click', function () {
					form.reset();
					responseDiv.style.display = 'none';
					form.style.display = 'block';
				});
			}

			// Try again button
			var tryAgainButton = container.querySelector('.weightpal-try-again-button');
			if (tryAgainButton) {
				tryAgainButton.addEventListener('click', function () {
					errorDiv.style.display = 'none';
					form.style.display = 'block';
				});
			}

			// Print button
			var printButton = container.querySelector('.weightpal-print-button');
			if (printButton) {
				printButton.addEventListener('click', function () {
					window.print();
				});
			}
		});
	});
})();
