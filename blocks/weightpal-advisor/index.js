(function () {
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var ColorPicker = wp.components.ColorPicker;
	var __ = wp.i18n.__;
	var Fragment = wp.element.Fragment;

	registerBlockType('weightpal/advisor', {
		title: __('Weightpal AI Advisor', 'weightpal'),
		icon: 'heart',
		category: 'weightpal',
		attributes: {
			title: {
				type: 'string',
				default: 'Get Your Personalized Weight Loss Plan'
			},
			description: {
				type: 'string',
				default: 'Enter your information below to receive AI-powered coaching with personalized advice, meal plans, and exercise schedules.'
			},
			buttonText: {
				type: 'string',
				default: 'Get My Plan'
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
				default: '#f9f9f9'
			},
			textColor: {
				type: 'string',
				default: '#333333'
			},
			accentColor: {
				type: 'string',
				default: '#0073aa'
			}
		},

		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var blockProps = useBlockProps({
				className: 'weightpal-advisor-block',
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
								rows: 4
							}),
						el(TextControl, {
							label: __('Button Text', 'weightpal'),
							value: attributes.buttonText,
							onChange: function (value) {
								setAttributes({ buttonText: value });
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
						{ className: 'weightpal-advisor-container' },
						attributes.showTitle &&
							el('h2', { className: 'weightpal-advisor-title' }, attributes.title),
						attributes.showDescription &&
							el('p', { className: 'weightpal-advisor-description' }, attributes.description),
						el(
							'div',
							{ className: 'weightpal-advisor-form-preview' },
							el(
								'div',
								{ className: 'weightpal-form-row' },
								el(
									'div',
									{ className: 'weightpal-form-field' },
									el('label', {}, __('Weight (kg)', 'weightpal'), ' ', el('span', { className: 'required' }, '*')),
									el('input', { type: 'number', placeholder: __('e.g., 75', 'weightpal'), disabled: true })
								),
								el(
									'div',
									{ className: 'weightpal-form-field' },
									el('label', {}, __('Height (cm)', 'weightpal'), ' ', el('span', { className: 'required' }, '*')),
									el('input', { type: 'number', placeholder: __('e.g., 175', 'weightpal'), disabled: true })
								)
							),
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Daily Routine', 'weightpal'), ' ', el('span', { className: 'required' }, '*')),
								el('textarea', {
									rows: 3,
									placeholder: __('Describe your typical daily routine...', 'weightpal'),
									disabled: true
								})
							),
							el(
								'div',
								{ className: 'weightpal-form-field' },
								el('label', {}, __('Your Goal / Question', 'weightpal'), ' ', el('span', { className: 'required' }, '*')),
								el('textarea', {
									rows: 4,
									placeholder: __('What would you like to achieve?', 'weightpal'),
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
							el('em', {}, __('👆 This is a preview. The form will be interactive on the frontend.', 'weightpal'))
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
