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
		error_log( 'Weightpal block registered successfully' );
	}
}
add_action( 'init', 'weightpal_register_block' );

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
	$accent_color     = isset( $attributes['accentColor'] ) ? $attributes['accentColor'] : '#0073aa';

	// Build inline styles
	$container_style = sprintf(
		'background-color: %s; color: %s;',
		esc_attr( $background_color ),
		esc_attr( $text_color )
	);

	$button_style = sprintf(
		'background-color: %s; border-color: %s;',
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
