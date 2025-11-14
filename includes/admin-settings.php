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
				'gemini_model'      => 'gemini-1.5-flash',
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

	// Add Gemini Model field
	add_settings_field(
		'weightpal_gemini_model',                     // Field ID
		__( 'Gemini Model', 'weightpal' ),            // Field title
		'weightpal_gemini_model_render',              // Callback function
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

	if ( isset( $input['gemini_model'] ) ) {
		$sanitized['gemini_model'] = sanitize_text_field( $input['gemini_model'] );
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
 * Fetch available Gemini models from API
 *
 * @param string $api_key The Gemini API key.
 * @return array Array of available models or empty array on error.
 */
function weightpal_fetch_gemini_models( $api_key ) {
	if ( empty( $api_key ) ) {
		return array();
	}

	// Check for cached models (cache for 1 hour)
	$cache_key = 'weightpal_gemini_models_' . md5( $api_key );
	$cached_models = get_transient( $cache_key );

	if ( false !== $cached_models ) {
		return $cached_models;
	}

	// Fetch models from API
	$api_url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
	$response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );

	if ( is_wp_error( $response ) ) {
		return array();
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! isset( $data['models'] ) || ! is_array( $data['models'] ) ) {
		return array();
	}

	$models = array();
	foreach ( $data['models'] as $model ) {
		// Only include models that support generateContent
		if ( isset( $model['name'] ) && isset( $model['supportedGenerationMethods'] ) ) {
			if ( in_array( 'generateContent', $model['supportedGenerationMethods'], true ) ) {
				// Extract model name (remove 'models/' prefix)
				$model_name = str_replace( 'models/', '', $model['name'] );
				$display_name = isset( $model['displayName'] ) ? $model['displayName'] : $model_name;
				$models[ $model_name ] = $display_name;
			}
		}
	}

	// Cache the results for 1 hour
	set_transient( $cache_key, $models, HOUR_IN_SECONDS );

	return $models;
}

/**
 * Render Gemini Model field
 */
function weightpal_gemini_model_render() {
	$options = get_option( 'weightpal_options' );
	$api_key = isset( $options['gemini_api_key'] ) ? $options['gemini_api_key'] : '';
	$selected_model = isset( $options['gemini_model'] ) ? $options['gemini_model'] : 'gemini-1.5-flash';

	// Fetch available models
	$models = weightpal_fetch_gemini_models( $api_key );

	// Default models if API fetch fails
	$default_models = array(
		'gemini-1.5-flash'   => 'Gemini 1.5 Flash',
		'gemini-1.5-pro'     => 'Gemini 1.5 Pro',
		'gemini-1.0-pro'     => 'Gemini 1.0 Pro',
	);

	// Use fetched models or fall back to defaults
	$available_models = ! empty( $models ) ? $models : $default_models;
	?>
	<select 
		name="weightpal_options[gemini_model]" 
		id="weightpal_gemini_model"
		class="regular-text"
	>
		<?php foreach ( $available_models as $model_id => $model_name ) : ?>
			<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $selected_model, $model_id ); ?>>
				<?php echo esc_html( $model_name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	
	<?php if ( ! empty( $api_key ) && ! empty( $models ) ) : ?>
		<p class="description" style="color: #46b450;">
			<?php
			printf(
				/* translators: %d: number of models */
				esc_html( _n( '%d model available', '%d models available', count( $models ), 'weightpal' ) ),
				count( $models )
			);
			?>
		</p>
	<?php elseif ( ! empty( $api_key ) ) : ?>
		<p class="description" style="color: #dc3232;">
			<?php esc_html_e( 'Unable to fetch models from API. Using default models.', 'weightpal' ); ?>
		</p>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'Enter your API key above and save to fetch available models.', 'weightpal' ); ?>
		</p>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Select which Gemini model to use for generating responses.', 'weightpal' ); ?>
		<button 
			type="button" 
			class="button button-small" 
			id="weightpal-refresh-models"
			style="margin-left: 10px;"
			<?php echo empty( $api_key ) ? 'disabled' : ''; ?>
		>
			<?php esc_html_e( 'Refresh Models', 'weightpal' ); ?>
		</button>
	</p>
	
	<script>
	jQuery(document).ready(function($) {
		$('#weightpal-refresh-models').on('click', function(e) {
			e.preventDefault();
			var button = $(this);
			button.prop('disabled', true).text('<?php esc_html_e( 'Refreshing...', 'weightpal' ); ?>');
			
			// Clear the cache by saving settings with a special flag
			$.post(ajaxurl, {
				action: 'weightpal_refresh_models',
				nonce: '<?php echo wp_create_nonce( 'weightpal_refresh_models' ); ?>'
			}, function(response) {
				if (response.success) {
					location.reload();
				} else {
					button.prop('disabled', false).text('<?php esc_html_e( 'Refresh Models', 'weightpal' ); ?>');
					alert('<?php esc_html_e( 'Failed to refresh models. Please try again.', 'weightpal' ); ?>');
				}
			});
		});
	});
	</script>
	<?php
}

/**
 * AJAX handler to refresh models
 */
function weightpal_ajax_refresh_models() {
	check_ajax_referer( 'weightpal_refresh_models', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	$options = get_option( 'weightpal_options' );
	$api_key = isset( $options['gemini_api_key'] ) ? $options['gemini_api_key'] : '';

	// Clear the cache
	$cache_key = 'weightpal_gemini_models_' . md5( $api_key );
	delete_transient( $cache_key );

	wp_send_json_success();
}
add_action( 'wp_ajax_weightpal_refresh_models', 'weightpal_ajax_refresh_models' );

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
		max="1,048,576"
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
		max="1,048,576"
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
