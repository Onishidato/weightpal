<?php
/**
 * Weightpal Admin Settings Page
 *
 * @package Weightpal
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add admin menu page
 */
function weightpal_add_admin_menu() {
	add_menu_page(
		__( 'Weightpal AI', 'weightpal' ),           // Page title
		__( 'Weightpal AI', 'weightpal' ),           // Menu title
		'manage_options',                             // Capability
		'weightpal',                                  // Menu slug
		'weightpal_settings_page',                    // Callback function
		'dashicons-heart',                            // Icon
		30                                            // Position
	);
}
add_action( 'admin_menu', 'weightpal_add_admin_menu' );

/**
 * Initialize settings
 */
function weightpal_settings_init() {
	// Register setting
	register_setting(
		'weightpal',                    // Option group
		'weightpal_options',            // Option name
		array(
			'sanitize_callback' => 'weightpal_sanitize_options',
			'default'           => array(
				'gemini_api_key'    => '',
				'system_prompt'     => weightpal_get_default_system_prompt(),
				'max_output_tokens' => 2048,
				'max_input_tokens'  => 8192,
			),
		)
	);

	// Add settings section
	add_settings_section(
		'weightpal_section',                          // Section ID
		__( 'Weightpal AI Configuration', 'weightpal' ),  // Section title
		'weightpal_section_callback',                     // Callback function
		'weightpal'                                       // Page slug
	);

	// Add Gemini API Key field
	add_settings_field(
		'weightpal_gemini_api_key',                   // Field ID
		__( 'Gemini API Key', 'weightpal' ),          // Field title
		'weightpal_api_key_render',                   // Callback function
		'weightpal',                                  // Page slug
		'weightpal_section'                           // Section ID
	);

	// Add System Prompt field
	add_settings_field(
		'weightpal_system_prompt',                    // Field ID
		__( 'System Prompt', 'weightpal' ),           // Field title
		'weightpal_system_prompt_render',             // Callback function
		'weightpal',                                  // Page slug
		'weightpal_section'                           // Section ID
	);

	// Add Max Output Tokens field
	add_settings_field(
		'weightpal_max_output_tokens',                // Field ID
		__( 'Maximum Output Tokens', 'weightpal' ),   // Field title
		'weightpal_max_output_tokens_render',         // Callback function
		'weightpal',                                  // Page slug
		'weightpal_section'                           // Section ID
	);

	// Add Max Input Tokens field
	add_settings_field(
		'weightpal_max_input_tokens',                 // Field ID
		__( 'Maximum Input Tokens', 'weightpal' ),    // Field title
		'weightpal_max_input_tokens_render',          // Callback function
		'weightpal',                                  // Page slug
		'weightpal_section'                           // Section ID
	);
}
add_action( 'admin_init', 'weightpal_settings_init' );

/**
 * Sanitize options
 *
 * @param array $input The input array from the form.
 * @return array Sanitized options array.
 */
function weightpal_sanitize_options( $input ) {
	$sanitized = array();

	if ( isset( $input['gemini_api_key'] ) ) {
		$sanitized['gemini_api_key'] = sanitize_text_field( $input['gemini_api_key'] );
	}

	if ( isset( $input['system_prompt'] ) ) {
		$sanitized['system_prompt'] = sanitize_textarea_field( $input['system_prompt'] );
	}

	if ( isset( $input['max_output_tokens'] ) ) {
		$sanitized['max_output_tokens'] = absint( $input['max_output_tokens'] );
		if ( $sanitized['max_output_tokens'] < 1 ) {
			$sanitized['max_output_tokens'] = 2048;
		}
	}

	if ( isset( $input['max_input_tokens'] ) ) {
		$sanitized['max_input_tokens'] = absint( $input['max_input_tokens'] );
		if ( $sanitized['max_input_tokens'] < 1 ) {
			$sanitized['max_input_tokens'] = 8192;
		}
	}

	return $sanitized;
}

/**
 * Section callback
 */
function weightpal_section_callback() {
	echo '<p>' . esc_html__( 'Configure your Weightpal AI settings below. You will need a Google Gemini API key to use this plugin.', 'weightpal' ) . '</p>';
	echo '<p>' . sprintf(
		wp_kses(
			/* translators: %s: URL to Google AI Studio */
			__( 'Get your API key from <a href="%s" target="_blank" rel="noopener noreferrer">Google AI Studio</a>.', 'weightpal' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
			)
		),
		esc_url( 'https://makersuite.google.com/app/apikey' )
	) . '</p>';
}

/**
 * Render API Key field
 */
function weightpal_api_key_render() {
	$options = get_option( 'weightpal_options' );
	$api_key = isset( $options['gemini_api_key'] ) ? $options['gemini_api_key'] : '';
	?>
	<input 
		type="password" 
		name="weightpal_options[gemini_api_key]" 
		id="weightpal_gemini_api_key"
		value="<?php echo esc_attr( $api_key ); ?>" 
		class="regular-text"
		placeholder="<?php esc_attr_e( 'Enter your Gemini API key', 'weightpal' ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Your Google Gemini API key. This will be stored securely in your database.', 'weightpal' ); ?>
	</p>
	<?php
}

/**
 * Render System Prompt field
 */
function weightpal_system_prompt_render() {
	$options       = get_option( 'weightpal_options' );
	$system_prompt = isset( $options['system_prompt'] ) ? $options['system_prompt'] : weightpal_get_default_system_prompt();
	?>
	<textarea 
		name="weightpal_options[system_prompt]" 
		id="weightpal_system_prompt"
		rows="12" 
		class="large-text code"
		placeholder="<?php esc_attr_e( 'Enter the system prompt for the AI', 'weightpal' ); ?>"
	><?php echo esc_textarea( $system_prompt ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'This is the system prompt that will be sent to the Gemini API. It defines how the AI should respond to user queries.', 'weightpal' ); ?>
	</p>
	<p>
		<button type="button" class="button" onclick="document.getElementById('weightpal_system_prompt').value = '<?php echo esc_js( weightpal_get_default_system_prompt() ); ?>';">
			<?php esc_html_e( 'Reset to Default', 'weightpal' ); ?>
		</button>
	</p>
	<?php
}

/**
 * Render Max Output Tokens field
 */
function weightpal_max_output_tokens_render() {
	$options           = get_option( 'weightpal_options' );
	$max_output_tokens = isset( $options['max_output_tokens'] ) ? $options['max_output_tokens'] : 2048;
	?>
	<input 
		type="number" 
		name="weightpal_options[max_output_tokens]" 
		id="weightpal_max_output_tokens"
		value="<?php echo esc_attr( $max_output_tokens ); ?>" 
		class="regular-text"
		min="1"
		max="32768"
		step="1"
	/>
	<p class="description">
		<?php esc_html_e( 'Maximum number of tokens to generate in the response. Default: 2048', 'weightpal' ); ?>
	</p>
	<?php
}

/**
 * Render Max Input Tokens field
 */
function weightpal_max_input_tokens_render() {
	$options          = get_option( 'weightpal_options' );
	$max_input_tokens = isset( $options['max_input_tokens'] ) ? $options['max_input_tokens'] : 8192;
	?>
	<input 
		type="number" 
		name="weightpal_options[max_input_tokens]" 
		id="weightpal_max_input_tokens"
		value="<?php echo esc_attr( $max_input_tokens ); ?>" 
		class="regular-text"
		min="1"
		max="32768"
		step="1"
	/>
	<p class="description">
		<?php esc_html_e( 'Maximum number of tokens to accept in the input. Default: 8192', 'weightpal' ); ?>
	</p>
	<?php
}

/**
 * Render settings page
 */
function weightpal_settings_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Show success message if settings were saved
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error(
			'weightpal_messages',
			'weightpal_message',
			__( 'Settings saved successfully!', 'weightpal' ),
			'success'
		);
	}

	// Show error/success messages
	settings_errors( 'weightpal_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<form action="options.php" method="post">
			<?php
			// Output security fields for the registered setting "weightpal"
			settings_fields( 'weightpal' );
			
			// Output setting sections and their fields
			do_settings_sections( 'weightpal' );
			
			// Output save settings button
			submit_button( __( 'Save Settings', 'weightpal' ) );
			?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'About Weightpal', 'weightpal' ); ?></h2>
		<p><?php esc_html_e( 'Weightpal is an AI-powered weight loss coaching plugin that uses Google Gemini to provide personalized advice, meal plans, and exercise schedules.', 'weightpal' ); ?></p>
		
		<h3><?php esc_html_e( 'API Endpoint', 'weightpal' ); ?></h3>
		<p>
			<code><?php echo esc_html( rest_url( 'weightpal/v1/advice' ) ); ?></code>
		</p>
		<p><?php esc_html_e( 'Use this endpoint to make POST requests with user data (weight, height, routine, userQuery).', 'weightpal' ); ?></p>
	</div>
	<?php
}
