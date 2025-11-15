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
			var mealPlanData = null;

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

						if (data.success && data.data) {
							mealPlanData = data.data;
							displayMealPlanCalendar(mealPlanData);
						} else {
							showError(data.message || weightpalMealPlannerData.messages.error);
						}
					})
				.catch(function (error) {
					loadingDiv.style.display = 'none';
					var errorMsg = error.message || weightpalMealPlannerData.messages.error;
					// Add link to debug logs if available
					if (errorMsg.indexOf('JSON') !== -1 || errorMsg.indexOf('parse') !== -1) {
						errorMsg += '<br><br><a href="' + (weightpalMealPlannerData.adminUrl || '/wp-admin/') + 
							'admin.php?page=weightpal-debug" style="color: #2271b1; text-decoration: underline;">View Debug Logs</a> to see what the AI returned.';
					}
					showError(errorMsg);
				});
		});
			// Display meal plan as calendar
			function displayMealPlanCalendar(planData) {
				var mealPlanContent = container.querySelector('.weightpal-meal-plan-content');
				var html = '';

				// Plan header
				html += '<div class="meal-plan-header-info">';
				html += '<h3>' + (planData.plan_duration || 'Meal Plan') + '</h3>';
				html += '<p><strong>Daily Target:</strong> ' + (planData.daily_calorie_target || 'N/A') + ' calories</p>';
				html += '</div>';

				// Calendar grid
				html += '<div class="meal-plan-calendar">';
				
				if (planData.days && planData.days.length > 0) {
					planData.days.forEach(function (day) {
						html += '<div class="meal-day-card" data-day="' + day.day_number + '">';
						html += '<div class="day-header">';
						html += '<h4>' + day.day_name + '</h4>';
						if (day.date) {
							html += '<span class="day-date">' + formatDate(day.date) + '</span>';
						}
						html += '<span class="day-calories">' + (day.total_calories || 0) + ' cal</span>';
						html += '</div>';
						
						html += '<div class="meals-list">';
						if (day.meals && day.meals.length > 0) {
							day.meals.forEach(function (meal) {
								html += '<div class="meal-item" data-meal="' + day.day_number + '-' + meal.meal_number + '">';
								html += '<div class="meal-header">';
								html += '<strong>' + meal.meal_name + '</strong>';
								html += '<span class="meal-calories">' + (meal.calories || 0) + ' cal</span>';
								html += '</div>';
								html += '<div class="meal-dish">' + meal.dish_name + '</div>';
								html += '<button type="button" class="meal-details-btn" data-day="' + day.day_number + '" data-meal="' + meal.meal_number + '">View Details</button>';
								html += '</div>';
							});
						}
						html += '</div>';
						
						html += '</div>';
					});
				}
				
				html += '</div>';

				// Shopping list
				if (planData.shopping_list && planData.shopping_list.length > 0) {
					html += '<div class="shopping-list-section">';
					html += '<h4>Shopping List</h4>';
					html += '<ul class="shopping-list">';
					planData.shopping_list.forEach(function (item) {
						html += '<li>' + item + '</li>';
					});
					html += '</ul>';
					html += '</div>';
				}

				// Notes
				if (planData.notes) {
					html += '<div class="plan-notes">';
					html += '<p><em>' + planData.notes + '</em></p>';
					html += '</div>';
				}

				mealPlanContent.innerHTML = html;
				responseDiv.style.display = 'block';

				// Add click handlers for meal details
				attachMealDetailHandlers(planData);
			}

			// Attach click handlers to meal detail buttons
			function attachMealDetailHandlers(planData) {
				var detailButtons = container.querySelectorAll('.meal-details-btn');
				detailButtons.forEach(function (btn) {
					btn.addEventListener('click', function () {
						var dayNum = parseInt(this.getAttribute('data-day'));
						var mealNum = parseInt(this.getAttribute('data-meal'));
						showMealModal(planData, dayNum, mealNum);
					});
				});
			}

			// Show meal details in modal
			function showMealModal(planData, dayNum, mealNum) {
				var day = planData.days.find(function (d) { return d.day_number === dayNum; });
				if (!day) return;

				var meal = day.meals.find(function (m) { return m.meal_number === mealNum; });
				if (!meal) return;

				var modalHtml = '<div class="meal-modal-overlay">';
				modalHtml += '<div class="meal-modal">';
				modalHtml += '<button class="meal-modal-close">&times;</button>';
				modalHtml += '<h3>' + meal.dish_name + '</h3>';
				modalHtml += '<p class="meal-time"><strong>Time:</strong> ' + (meal.meal_time || 'Anytime') + '</p>';
				
				modalHtml += '<div class="nutrition-info">';
				modalHtml += '<span><strong>Calories:</strong> ' + (meal.calories || 0) + '</span>';
				modalHtml += '<span><strong>Protein:</strong> ' + (meal.protein || 'N/A') + '</span>';
				modalHtml += '<span><strong>Carbs:</strong> ' + (meal.carbs || 'N/A') + '</span>';
				modalHtml += '<span><strong>Fats:</strong> ' + (meal.fats || 'N/A') + '</span>';
				modalHtml += '</div>';

				if (meal.ingredients && meal.ingredients.length > 0) {
					modalHtml += '<h4>Ingredients</h4>';
					modalHtml += '<ul>';
					meal.ingredients.forEach(function (ing) {
						modalHtml += '<li>' + ing + '</li>';
					});
					modalHtml += '</ul>';
				}

				if (meal.cooking_instructions) {
					modalHtml += '<h4>Instructions</h4>';
					modalHtml += '<p>' + meal.cooking_instructions + '</p>';
				}

				if (meal.prep_time) {
					modalHtml += '<p class="prep-time"><strong>Prep Time:</strong> ' + meal.prep_time + '</p>';
				}

				modalHtml += '</div></div>';

				var modalDiv = document.createElement('div');
				modalDiv.innerHTML = modalHtml;
				document.body.appendChild(modalDiv);

				// Close modal handlers
				var closeBtn = modalDiv.querySelector('.meal-modal-close');
				var overlay = modalDiv.querySelector('.meal-modal-overlay');
				
				closeBtn.addEventListener('click', function () {
					document.body.removeChild(modalDiv);
				});
				
				overlay.addEventListener('click', function (e) {
					if (e.target === overlay) {
						document.body.removeChild(modalDiv);
					}
				});
			}

			// Format date helper
			function formatDate(dateStr) {
				var date = new Date(dateStr);
				var options = { month: 'short', day: 'numeric' };
				return date.toLocaleDateString('en-US', options);
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
					mealPlanData = null;
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

			// Download PDF button
			var downloadPdfButton = container.querySelector('.weightpal-download-pdf-button');
			if (downloadPdfButton) {
				downloadPdfButton.addEventListener('click', function () {
					if (mealPlanData) {
						generatePDF(mealPlanData);
					}
				});
			}

			// Print button
			var printButton = container.querySelector('.weightpal-print-button');
			if (printButton) {
				printButton.addEventListener('click', function () {
					window.print();
				});
			}

		// Generate PDF from meal plan data
		function generatePDF(planData) {
			// jsPDF UMD module is accessed via window.jspdf.jsPDF
			var jsPDF = window.jspdf && window.jspdf.jsPDF;
			
			if (typeof jsPDF === 'undefined') {
				alert('PDF library not loaded. Please refresh the page.');
				return;
			}

			var doc = new jsPDF();
			var yPos = 20;
			var lineHeight = 7;
			var pageHeight = doc.internal.pageSize.height;				// Title
				doc.setFontSize(18);
				doc.setFont(undefined, 'bold');
				doc.text('Your Personalized Meal Plan', 20, yPos);
				yPos += lineHeight * 2;

				// Plan info
				doc.setFontSize(12);
				doc.setFont(undefined, 'normal');
				doc.text('Duration: ' + (planData.plan_duration || 'N/A'), 20, yPos);
				yPos += lineHeight;
				doc.text('Daily Calorie Target: ' + (planData.daily_calorie_target || 'N/A') + ' calories', 20, yPos);
				yPos += lineHeight * 2;

				// Days
				if (planData.days && planData.days.length > 0) {
					planData.days.forEach(function (day) {
						// Check if need new page
						if (yPos > pageHeight - 40) {
							doc.addPage();
							yPos = 20;
						}

						doc.setFontSize(14);
						doc.setFont(undefined, 'bold');
						doc.text(day.day_name + ' - ' + (day.date || ''), 20, yPos);
						yPos += lineHeight;
						
						doc.setFontSize(10);
						doc.setFont(undefined, 'normal');
						doc.text('Total Calories: ' + (day.total_calories || 0), 20, yPos);
						yPos += lineHeight * 1.5;

						// Meals
						if (day.meals && day.meals.length > 0) {
							day.meals.forEach(function (meal) {
								if (yPos > pageHeight - 30) {
									doc.addPage();
									yPos = 20;
								}

								doc.setFont(undefined, 'bold');
								doc.text(meal.meal_name + ': ' + meal.dish_name, 25, yPos);
								yPos += lineHeight;

								doc.setFont(undefined, 'normal');
								doc.text('Calories: ' + (meal.calories || 0) + ' | Protein: ' + (meal.protein || 'N/A') + ' | Carbs: ' + (meal.carbs || 'N/A'), 25, yPos);
								yPos += lineHeight;

								if (meal.cooking_instructions) {
									var instructions = doc.splitTextToSize(meal.cooking_instructions, 160);
									doc.text(instructions, 25, yPos);
									yPos += (instructions.length * lineHeight);
								}
								yPos += lineHeight * 0.5;
							});
						}
						yPos += lineHeight;
					});
				}

				// Shopping list
				if (planData.shopping_list && planData.shopping_list.length > 0) {
					if (yPos > pageHeight - 60) {
						doc.addPage();
						yPos = 20;
					}

					doc.setFontSize(14);
					doc.setFont(undefined, 'bold');
					doc.text('Shopping List', 20, yPos);
					yPos += lineHeight * 1.5;

					doc.setFontSize(10);
					doc.setFont(undefined, 'normal');
					planData.shopping_list.forEach(function (item) {
						if (yPos > pageHeight - 20) {
							doc.addPage();
							yPos = 20;
						}
						doc.text('• ' + item, 25, yPos);
						yPos += lineHeight;
					});
				}

				// Save PDF
				doc.save('meal-plan-' + Date.now() + '.pdf');
			}
		});
	});
})();
