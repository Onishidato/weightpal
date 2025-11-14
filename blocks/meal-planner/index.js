(function () {
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var ColorPicker = wp.components.ColorPicker;
	var __ = wp.i18n.__;
	var Fragment = wp.element.Fragment;

	registerBlockType('weightpal/meal-planner', {
		title: __('Weightpal Meal Planner', 'weightpal'),
		icon: 'food',
		category: 'weightpal',
		attributes: {
			title: {
				type: 'string',
				default: 'Personalized Meal Plan Generator'
			},
			description: {
				type: 'string',
				default: 'Get a customized meal plan based on your dietary preferences, goals, and lifestyle.'
			},
			buttonText: {
				type: 'string',
				default: 'Generate My Meal Plan'
			},
			showTitle: {
				type: 'boolean',
				default: true
			},
			showDescription: {
				type: 'boolean',
				default: true
			},
			backgroundColor: {
				type: 'string',
				default: '#f0f9ff'
			},
			textColor: {
				type: 'string',
				default: '#1e293b'
			},
			accentColor: {
				type: 'string',
				default: '#28a745'
			},
			planDuration: {
				type: 'string',
				default: '7'
			}
		},

		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var blockProps = useBlockProps({
				className: 'weightpal-meal-planner-block',
				style: {
					backgroundColor: attributes.backgroundColor,
					color: attributes.textColor
				}
			});

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Content Settings', 'weightpal'), initialOpen: true },
						el(ToggleControl, {
							label: __('Show Title', 'weightpal'),
							checked: attributes.showTitle,
							onChange: function (value) {
								setAttributes({ showTitle: value });
							}
						}),
						attributes.showTitle &&
							el(TextControl, {
								label: __('Title', 'weightpal'),
								value: attributes.title,
								onChange: function (value) {
									setAttributes({ title: value });
								}
							}),
						el(ToggleControl, {
							label: __('Show Description', 'weightpal'),
							checked: attributes.showDescription,
							onChange: function (value) {
								setAttributes({ showDescription: value });
							}
						}),
						attributes.showDescription &&
							el(TextareaControl, {
								label: __('Description', 'weightpal'),
								value: attributes.description,
								onChange: function (value) {
									setAttributes({ description: value });
								},
								rows: 3
							}),
						el(TextControl, {
							label: __('Button Text', 'weightpal'),
							value: attributes.buttonText,
							onChange: function (value) {
								setAttributes({ buttonText: value });
							}
						}),
						el(SelectControl, {
							label: __('Default Plan Duration', 'weightpal'),
							value: attributes.planDuration,
							options: [
								{ label: __('3 Days', 'weightpal'), value: '3' },
								{ label: __('7 Days', 'weightpal'), value: '7' },
								{ label: __('14 Days', 'weightpal'), value: '14' },
								{ label: __('30 Days', 'weightpal'), value: '30' }
							],
							onChange: function (value) {
								setAttributes({ planDuration: value });
							}
						})
					),
					el(
						PanelBody,
						{ title: __('Color Settings', 'weightpal'), initialOpen: false },
						el('p', {}, el('strong', {}, __('Background Color', 'weightpal'))),
						el(ColorPicker, {
							color: attributes.backgroundColor,
							onChangeComplete: function (color) {
								setAttributes({ backgroundColor: color.hex });
							},
							disableAlpha: true
						}),
						el('p', { style: { marginTop: '20px' } }, el('strong', {}, __('Text Color', 'weightpal'))),
						el(ColorPicker, {
							color: attributes.textColor,
							onChangeComplete: function (color) {
								setAttributes({ textColor: color.hex });
							},
							disableAlpha: true
						}),
						el(
							'p',
							{ style: { marginTop: '20px' } },
							el('strong', {}, __('Accent Color (Button)', 'weightpal'))
						),
						el(ColorPicker, {
							color: attributes.accentColor,
							onChangeComplete: function (color) {
								setAttributes({ accentColor: color.hex });
							},
							disableAlpha: true
						})
					)
				),
				el(
					'div',
					blockProps,
					el(
						'div',
						{ className: 'weightpal-meal-planner-container' },
						attributes.showTitle &&
							el('h2', { className: 'weightpal-meal-planner-title' }, attributes.title),
						attributes.showDescription &&
							el('p', { className: 'weightpal-meal-planner-description' }, attributes.description),
						el(
							'div',
							{ className: 'weightpal-meal-planner-form-preview' },
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Dietary Preferences', 'weightpal')),
								el('input', { type: 'text', placeholder: __('e.g., Vegetarian, Vegan, Keto', 'weightpal'), disabled: true })
							),
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Food Allergies / Restrictions', 'weightpal')),
								el('input', { type: 'text', placeholder: __('e.g., Nuts, Dairy, Gluten', 'weightpal'), disabled: true })
							),
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Daily Calorie Target', 'weightpal')),
								el('input', { type: 'number', placeholder: __('e.g., 2000', 'weightpal'), disabled: true })
							),
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Number of Days', 'weightpal')),
								el('select', { disabled: true }, 
									el('option', {}, attributes.planDuration + ' Days')
								)
							),
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Additional Preferences', 'weightpal')),
								el('textarea', {
									rows: 3,
									placeholder: __('e.g., Quick meals, meal prep friendly, budget-friendly...', 'weightpal'),
									disabled: true
								})
							),
							el(
								'button',
								{
									type: 'button',
									className: 'weightpal-submit-button',
									style: {
										backgroundColor: attributes.accentColor,
										borderColor: attributes.accentColor
									},
									disabled: true
								},
								attributes.buttonText
							)
						),
						el(
							'p',
							{ className: 'weightpal-editor-notice' },
							el('em', {}, __('👆 This is a preview. The meal planner will be interactive on the frontend.', 'weightpal'))
						)
					)
				)
			);
		},

		save: function () {
			return null;
		}
	});
})();
