<?php
/**
 * Weightpal Block Registration
 *
 * @package Weightpal
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom block category for Weightpal
 */
function weightpal_block_categories( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'weightpal',
				'title' => __( 'Weightpal', 'weightpal' ),
				'icon'  => 'heart',
			),
		)
	);
}
add_filter( 'block_categories_all', 'weightpal_block_categories', 10, 1 );

/**
 * Register Weightpal Advisor Block
 */
function weightpal_register_block() {
	// Register block editor script
	wp_register_script(
		'weightpal-block-editor',
		WEIGHTPAL_PLUGIN_URL . 'blocks/weightpal-advisor/index.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		WEIGHTPAL_VERSION,
		true
	);

	// Register editor styles
	wp_register_style(
		'weightpal-block-editor-style',
		WEIGHTPAL_PLUGIN_URL . 'blocks/weightpal-advisor/editor.css',
		array( 'wp-edit-blocks' ),
		WEIGHTPAL_VERSION
	);

	// Register frontend styles
	wp_register_style(
		'weightpal-block-style',
		WEIGHTPAL_PLUGIN_URL . 'blocks/weightpal-advisor/style.css',
		array(),
		WEIGHTPAL_VERSION
	);

	// Register the block
	register_block_type(
		'weightpal/advisor',
		array(
			'editor_script'   => 'weightpal-block-editor',
			'editor_style'    => 'weightpal-block-editor-style',
			'style'           => 'weightpal-block-style',
			'render_callback' => 'weightpal_render_block',
			'attributes'      => array(
				'title'            => array(
					'type'    => 'string',
					'default' => __( 'Get Your Personalized Weight Loss Plan', 'weightpal' ),
				),
				'description'      => array(
					'type'    => 'string',
					'default' => __( 'Enter your information below to receive AI-powered coaching with personalized advice, meal plans, and exercise schedules.', 'weightpal' ),
				),
				'buttonText'       => array(
					'type'    => 'string',
					'default' => __( 'Get My Plan', 'weightpal' ),
				),
				'showTitle'        => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showDescription'  => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'backgroundColor'  => array(
					'type'    => 'string',
					'default' => '#f9f9f9',
				),
				'textColor'        => array(
					'type'    => 'string',
					'default' => '#333333',
				),
				'accentColor'      => array(
					'type'    => 'string',
					'default' => '#0073aa',
				),
			),
		)
	);

	// Debug: Log that block was registered
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Weightpal advisor block registered successfully' );
	}
}
add_action( 'init', 'weightpal_register_block' );

/**
 * Register Meal Planner Block
 */
function weightpal_register_meal_planner_block() {
	// Register block editor script
	wp_register_script(
		'weightpal-meal-planner-editor',
		WEIGHTPAL_PLUGIN_URL . 'blocks/meal-planner/index.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		WEIGHTPAL_VERSION,
		true
	);

	// Register editor styles
	wp_register_style(
		'weightpal-meal-planner-editor-style',
		WEIGHTPAL_PLUGIN_URL . 'blocks/meal-planner/editor.css',
		array( 'wp-edit-blocks' ),
		WEIGHTPAL_VERSION
	);

	// Register frontend styles
	wp_register_style(
		'weightpal-meal-planner-style',
		WEIGHTPAL_PLUGIN_URL . 'blocks/meal-planner/style.css',
		array(),
		WEIGHTPAL_VERSION
	);

	// Register the block
	register_block_type(
		'weightpal/meal-planner',
		array(
			'editor_script'   => 'weightpal-meal-planner-editor',
			'editor_style'    => 'weightpal-meal-planner-editor-style',
			'style'           => 'weightpal-meal-planner-style',
			'render_callback' => 'weightpal_render_meal_planner_block',
			'attributes'      => array(
				'title'            => array(
					'type'    => 'string',
					'default' => __( 'Generate Your Personalized Meal Plan', 'weightpal' ),
				),
				'description'      => array(
					'type'    => 'string',
					'default' => __( 'Get a customized meal plan tailored to your dietary preferences, allergies, and calorie goals.', 'weightpal' ),
				),
				'buttonText'       => array(
					'type'    => 'string',
					'default' => __( 'Generate Meal Plan', 'weightpal' ),
				),
				'showTitle'        => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'showDescription'  => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'backgroundColor'  => array(
					'type'    => 'string',
					'default' => '#f0f8ff',
				),
				'textColor'        => array(
					'type'    => 'string',
					'default' => '#333333',
				),
				'accentColor'      => array(
					'type'    => 'string',
					'default' => '#28a745',
				),
			),
		)
	);

	// Debug: Log that block was registered
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Weightpal meal-planner block registered successfully' );
	}
}
add_action( 'init', 'weightpal_register_meal_planner_block' );

/**
 * Render callback for the Weightpal Advisor block
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 * @return string Rendered block HTML.
 */
function weightpal_render_block( $attributes, $content ) {
	// Enqueue frontend script
	wp_enqueue_script(
		'weightpal-frontend',
		WEIGHTPAL_PLUGIN_URL . 'blocks/weightpal-advisor/frontend.js',
		array(),
		WEIGHTPAL_VERSION,
		true
	);

	// Localize script with REST API URL and nonce
	wp_localize_script(
		'weightpal-frontend',
		'weightpalData',
		array(
			'apiUrl'   => rest_url( 'weightpal/v1/advice' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'messages' => array(
				'loading'       => __( 'Getting your personalized advice...', 'weightpal' ),
				'error'         => __( 'An error occurred. Please try again.', 'weightpal' ),
				'required'      => __( 'Please fill in all fields.', 'weightpal' ),
				'invalidWeight' => __( 'Please enter a valid weight.', 'weightpal' ),
				'invalidHeight' => __( 'Please enter a valid height.', 'weightpal' ),
			),
		)
	);

	// Get attributes with defaults
	$title            = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Get Your Personalized Weight Loss Plan', 'weightpal' );
	$description      = isset( $attributes['description'] ) ? $attributes['description'] : __( 'Enter your information below to receive AI-powered coaching with personalized advice, meal plans, and exercise schedules.', 'weightpal' );
	$button_text      = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Get My Plan', 'weightpal' );
	$show_title       = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
	$show_description = isset( $attributes['showDescription'] ) ? $attributes['showDescription'] : true;
	$background_color = isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '#f9f9f9';
	$text_color       = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '#333333';
	$accent_color     = isset( $attributes['accentColor'] ) ? $attributes['accentColor'] : '#28a745';

	// Build inline styles
	$container_style = sprintf(
		'background-color: %s; color: %s;',
		esc_attr( $background_color ),
		esc_attr( $text_color )
	);

	$button_style = sprintf(
		'background-color: %s; border: 2px solid %s; color: #fff; padding: 15px 30px; font-size: 18px; font-weight: 600; border-radius: 8px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; width: 100%%;',
		esc_attr( $accent_color ),
		esc_attr( $accent_color )
	);

	// Start output buffering
	ob_start();
	?>
	<div class="weightpal-advisor-block" style="<?php echo $container_style; ?>">
		<div class="weightpal-advisor-container">
			<?php if ( $show_title ) : ?>
				<h2 class="weightpal-advisor-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( $show_description ) : ?>
				<p class="weightpal-advisor-description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<form class="weightpal-advisor-form" id="weightpal-advisor-form">
				<div class="weightpal-form-row">
					<div class="weightpal-form-field">
						<label for="weightpal-weight">
							<?php esc_html_e( 'Weight (kg)', 'weightpal' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="number"
							id="weightpal-weight"
							name="weight"
							placeholder="<?php esc_attr_e( 'e.g., 75', 'weightpal' ); ?>"
							step="0.1"
							min="1"
							required
						/>
					</div>

					<div class="weightpal-form-field">
						<label for="weightpal-height">
							<?php esc_html_e( 'Height (cm)', 'weightpal' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="number"
							id="weightpal-height"
							name="height"
							placeholder="<?php esc_attr_e( 'e.g., 175', 'weightpal' ); ?>"
							step="0.1"
							min="1"
							required
						/>
					</div>
				</div>

				<div class="weightpal-form-field">
					<label for="weightpal-routine">
						<?php esc_html_e( 'Daily Routine', 'weightpal' ); ?>
						<span class="required">*</span>
					</label>
					<textarea
						id="weightpal-routine"
						name="routine"
						rows="3"
						placeholder="<?php esc_attr_e( 'Describe your typical daily routine (work schedule, activity level, etc.)', 'weightpal' ); ?>"
						required
					></textarea>
				</div>

				<div class="weightpal-form-field">
					<label for="weightpal-query">
						<?php esc_html_e( 'Your Goal / Question', 'weightpal' ); ?>
						<span class="required">*</span>
					</label>
					<textarea
						id="weightpal-query"
						name="userQuery"
						rows="4"
						placeholder="<?php esc_attr_e( 'What would you like to achieve? Any specific questions?', 'weightpal' ); ?>"
						required
					></textarea>
				</div>

				<button
					type="submit"
					class="weightpal-submit-button"
					style="<?php echo $button_style; ?>"
				>
					<?php echo esc_html( $button_text ); ?>
				</button>
			</form>

			<div class="weightpal-loading" style="display: none;">
				<div class="weightpal-spinner"></div>
				<p><?php esc_html_e( 'Getting your personalized advice...', 'weightpal' ); ?></p>
			</div>

			<div class="weightpal-response" style="display: none;">
				<div class="weightpal-bmi-badge">
					<span class="weightpal-bmi-label"><?php esc_html_e( 'Your BMI:', 'weightpal' ); ?></span>
					<span class="weightpal-bmi-value"></span>
				</div>
				<div class="weightpal-advice-content"></div>
				<button type="button" class="weightpal-new-query-button">
					<?php esc_html_e( 'Ask Another Question', 'weightpal' ); ?>
				</button>
			</div>

			<div class="weightpal-error" style="display: none;">
				<p class="weightpal-error-message"></p>
				<button type="button" class="weightpal-try-again-button">
					<?php esc_html_e( 'Try Again', 'weightpal' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render callback for the Meal Planner block
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 * @return string Rendered block HTML.
 */
function weightpal_render_meal_planner_block( $attributes, $content ) {
	// Enqueue frontend script
	wp_enqueue_script(
		'weightpal-meal-planner-frontend',
		WEIGHTPAL_PLUGIN_URL . 'blocks/meal-planner/frontend.js',
		array(),
		WEIGHTPAL_VERSION,
		true
	);

	// Localize script with REST API URL and nonce
	wp_localize_script(
		'weightpal-meal-planner-frontend',
		'weightpalMealPlannerData',
		array(
			'apiUrl'   => rest_url( 'weightpal/v1/meal-plan' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'messages' => array(
				'loading'          => __( 'Generating your personalized meal plan...', 'weightpal' ),
				'error'            => __( 'An error occurred. Please try again.', 'weightpal' ),
				'requiredCalories' => __( 'Please enter your daily calorie target.', 'weightpal' ),
				'requiredDays'     => __( 'Please select the duration for your meal plan.', 'weightpal' ),
				'invalidCalories'  => __( 'Please enter a valid calorie target (between 1 and 10000).', 'weightpal' ),
				'invalidDays'      => __( 'Please select a valid plan duration.', 'weightpal' ),
			),
		)
	);

	// Get attributes with defaults
	$title            = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Generate Your Personalized Meal Plan', 'weightpal' );
	$description      = isset( $attributes['description'] ) ? $attributes['description'] : __( 'Get a customized meal plan tailored to your dietary preferences, allergies, and calorie goals.', 'weightpal' );
	$button_text      = isset( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Generate Meal Plan', 'weightpal' );
	$show_title       = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
	$show_description = isset( $attributes['showDescription'] ) ? $attributes['showDescription'] : true;
	$background_color = isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '#f0f8ff';
	$text_color       = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '#333333';
	$accent_color     = isset( $attributes['accentColor'] ) ? $attributes['accentColor'] : '#28a745';

	// Build inline styles
	$container_style = sprintf(
		'background-color: %s; color: %s;',
		esc_attr( $background_color ),
		esc_attr( $text_color )
	);

	$button_style = sprintf(
		'background-color: %s; border: 2px solid %s; color: #fff; padding: 15px 30px; font-size: 18px; font-weight: 600; border-radius: 8px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; width: 100%%;',
		esc_attr( $accent_color ),
		esc_attr( $accent_color )
	);

	// Start output buffering
	ob_start();
	?>
	<div class="weightpal-meal-planner-block" style="<?php echo $container_style; ?>">
		<div class="weightpal-meal-planner-container">
			<?php if ( $show_title ) : ?>
				<h2 class="weightpal-meal-planner-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( $show_description ) : ?>
				<p class="weightpal-meal-planner-description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<form class="weightpal-meal-planner-form" id="weightpal-meal-planner-form">
				<div class="weightpal-form-field">
					<label for="weightpal-dietary-preferences">
						<?php esc_html_e( 'Dietary Preferences', 'weightpal' ); ?>
					</label>
					<input
						type="text"
						id="weightpal-dietary-preferences"
						name="dietaryPreferences"
						placeholder="<?php esc_attr_e( 'e.g., Vegetarian, Vegan, Keto, Paleo, Mediterranean', 'weightpal' ); ?>"
					/>
				</div>

				<div class="weightpal-form-field">
					<label for="weightpal-allergies">
						<?php esc_html_e( 'Allergies / Foods to Avoid', 'weightpal' ); ?>
					</label>
					<input
						type="text"
						id="weightpal-allergies"
						name="allergies"
						placeholder="<?php esc_attr_e( 'e.g., Nuts, Dairy, Gluten, Shellfish', 'weightpal' ); ?>"
					/>
				</div>

				<div class="weightpal-form-row">
					<div class="weightpal-form-field">
						<label for="weightpal-calorie-target">
							<?php esc_html_e( 'Daily Calorie Target', 'weightpal' ); ?>
							<span class="required">*</span>
						</label>
						<input
							type="number"
							id="weightpal-calorie-target"
							name="calorieTarget"
							placeholder="<?php esc_attr_e( 'e.g., 2000', 'weightpal' ); ?>"
							min="1"
							max="10000"
							required
						/>
					</div>

					<div class="weightpal-form-field">
						<label for="weightpal-plan-days">
							<?php esc_html_e( 'Plan Duration', 'weightpal' ); ?>
							<span class="required">*</span>
						</label>
						<select
							id="weightpal-plan-days"
							name="planDays"
							required
						>
							<option value=""><?php esc_html_e( 'Select duration', 'weightpal' ); ?></option>
							<option value="3"><?php esc_html_e( '3 Days', 'weightpal' ); ?></option>
							<option value="7"><?php esc_html_e( '7 Days (1 Week)', 'weightpal' ); ?></option>
							<option value="14"><?php esc_html_e( '14 Days (2 Weeks)', 'weightpal' ); ?></option>
							<option value="30"><?php esc_html_e( '30 Days (1 Month)', 'weightpal' ); ?></option>
						</select>
					</div>
				</div>

				<div class="weightpal-form-field">
					<label for="weightpal-additional-preferences">
						<?php esc_html_e( 'Additional Preferences', 'weightpal' ); ?>
					</label>
					<textarea
						id="weightpal-additional-preferences"
						name="additionalPreferences"
						rows="3"
						placeholder="<?php esc_attr_e( 'Any other preferences? (e.g., budget-friendly, quick meals, specific cuisines)', 'weightpal' ); ?>"
					></textarea>
				</div>

				<button
					type="submit"
					class="weightpal-submit-button"
					style="<?php echo $button_style; ?>"
				>
					<?php echo esc_html( $button_text ); ?>
				</button>
			</form>

			<div class="weightpal-loading" style="display: none;">
				<div class="weightpal-spinner"></div>
				<p><?php esc_html_e( 'Generating your personalized meal plan...', 'weightpal' ); ?></p>
			</div>

			<div class="weightpal-response" style="display: none;">
				<div class="weightpal-meal-plan-header">
					<h3><?php esc_html_e( 'Your Personalized Meal Plan', 'weightpal' ); ?></h3>
					<button type="button" class="weightpal-print-button">
						<?php esc_html_e( '🖨️ Print Plan', 'weightpal' ); ?>
					</button>
				</div>
				<div class="weightpal-meal-plan-content"></div>
				<div class="weightpal-meal-plan-actions">
					<button type="button" class="weightpal-new-plan-button">
						<?php esc_html_e( 'Generate New Plan', 'weightpal' ); ?>
					</button>
				</div>
			</div>

			<div class="weightpal-error" style="display: none;">
				<p class="weightpal-error-message"></p>
				<button type="button" class="weightpal-try-again-button">
					<?php esc_html_e( 'Try Again', 'weightpal' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Enqueue block editor assets
 */
function weightpal_enqueue_block_editor_assets() {
	// Assets are now registered and enqueued via register_block_type
}
add_action( 'enqueue_block_editor_assets', 'weightpal_enqueue_block_editor_assets' );

/**
 * Enqueue block frontend assets
 */
function weightpal_enqueue_block_assets() {
	// Assets are now registered and enqueued via register_block_type
}
add_action( 'enqueue_block_assets', 'weightpal_enqueue_block_assets' );
