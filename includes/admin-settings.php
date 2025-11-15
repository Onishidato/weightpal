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
	// Main menu page
	add_menu_page(
		__( 'Weightpal AI', 'weightpal' ),           // Page title
		__( 'Weightpal AI', 'weightpal' ),           // Menu title
		'manage_options',                             // Capability
		'weightpal',                                  // Menu slug
		'weightpal_general_settings_page',            // Callback function
		'dashicons-heart',                            // Icon
		30                                            // Position
	);

	// General Settings submenu
	add_submenu_page(
		'weightpal',                                  // Parent slug
		__( 'General Settings', 'weightpal' ),        // Page title
		__( 'General Settings', 'weightpal' ),        // Menu title
		'manage_options',                             // Capability
		'weightpal',                                  // Menu slug (same as parent to replace main menu)
		'weightpal_general_settings_page'             // Callback function
	);

	// AI Advisor submenu
	add_submenu_page(
		'weightpal',                                  // Parent slug
		__( 'AI Advisor Settings', 'weightpal' ),     // Page title
		__( 'AI Advisor', 'weightpal' ),              // Menu title
		'manage_options',                             // Capability
		'weightpal-advisor',                          // Menu slug
		'weightpal_advisor_settings_page'             // Callback function
	);

	// Meal Planner submenu
	add_submenu_page(
		'weightpal',                                  // Parent slug
		__( 'Meal Planner Settings', 'weightpal' ),   // Page title
		__( 'Meal Planner', 'weightpal' ),            // Menu title
		'manage_options',                             // Capability
		'weightpal-meal-planner',                     // Menu slug
		'weightpal_meal_planner_settings_page'        // Callback function
	);

	// Debug Logs submenu
	add_submenu_page(
		'weightpal',                                  // Parent slug
		__( 'Debug Logs', 'weightpal' ),              // Page title
		__( 'Debug Logs', 'weightpal' ),              // Menu title
		'manage_options',                             // Capability
		'weightpal-debug',                            // Menu slug
		'weightpal_debug_logs_page'                   // Callback function
	);
}
add_action( 'admin_menu', 'weightpal_add_admin_menu' );

/**
 * Initialize settings
 */
function weightpal_settings_init() {
	// Register setting
	register_setting(
		'weightpal_general',                // Option group
		'weightpal_options',            // Option name
		array(
			'sanitize_callback' => 'weightpal_sanitize_options',
			'default'           => array(
				'gemini_api_key'          => '',
				'gemini_model'            => 'gemini-1.5-flash',
				'max_output_tokens'       => 4096,
				'max_input_tokens'        => 8192,
				'advisor_system_prompt'   => weightpal_get_default_system_prompt(),
				'meal_planner_system_prompt' => weightpal_get_default_meal_planner_prompt(),
			),
		)
	);

	// Add General Settings section
	add_settings_section(
		'weightpal_general_section',                      // Section ID
		__( 'API Configuration', 'weightpal' ),           // Section title
		'weightpal_general_section_callback',             // Callback function
		'weightpal'                                       // Page slug
	);

	// Add AI Advisor Settings section
	add_settings_section(
		'weightpal_advisor_section',                      // Section ID
		__( 'System Prompt Configuration', 'weightpal' ), // Section title
		'weightpal_advisor_section_callback',             // Callback function
		'weightpal-advisor'                               // Page slug
	);

	// Add Meal Planner Settings section
	add_settings_section(
		'weightpal_meal_planner_section',                 // Section ID
		__( 'System Prompt Configuration', 'weightpal' ), // Section title
		'weightpal_meal_planner_section_callback',        // Callback function
		'weightpal-meal-planner'                          // Page slug
	);

	// Add Gemini API Key field
	add_settings_field(
		'weightpal_gemini_api_key',                   // Field ID
		__( 'Gemini API Key', 'weightpal' ),          // Field title
		'weightpal_api_key_render',                   // Callback function
		'weightpal',                                  // Page slug
		'weightpal_general_section'                   // Section ID
	);

	// Add Gemini Model field
	add_settings_field(
		'weightpal_gemini_model',                     // Field ID
		__( 'Gemini Model', 'weightpal' ),            // Field title
		'weightpal_gemini_model_render',              // Callback function
		'weightpal',                                  // Page slug
		'weightpal_general_section'                   // Section ID
	);

	// Add Max Output Tokens field
	add_settings_field(
		'weightpal_max_output_tokens',                // Field ID
		__( 'Maximum Output Tokens', 'weightpal' ),   // Field title
		'weightpal_max_output_tokens_render',         // Callback function
		'weightpal',                                  // Page slug
		'weightpal_general_section'                   // Section ID
	);

	// Add Max Input Tokens field
	add_settings_field(
		'weightpal_max_input_tokens',                 // Field ID
		__( 'Maximum Input Tokens', 'weightpal' ),    // Field title
		'weightpal_max_input_tokens_render',          // Callback function
		'weightpal',                                  // Page slug
		'weightpal_general_section'                   // Section ID
	);

	// Add AI Advisor System Prompt field
	add_settings_field(
		'weightpal_advisor_system_prompt',            // Field ID
		__( 'AI Advisor System Prompt', 'weightpal' ), // Field title
		'weightpal_advisor_system_prompt_render',     // Callback function
		'weightpal-advisor',                          // Page slug
		'weightpal_advisor_section'                   // Section ID
	);

	// Add Meal Planner System Prompt field
	add_settings_field(
		'weightpal_meal_planner_system_prompt',       // Field ID
		__( 'Meal Planner System Prompt', 'weightpal' ), // Field title
		'weightpal_meal_planner_system_prompt_render', // Callback function
		'weightpal-meal-planner',                     // Page slug
		'weightpal_meal_planner_section'              // Section ID
	);
}
add_action( 'admin_init', 'weightpal_settings_init' );

/**
 * Render Debug Logs page
 */
function weightpal_debug_logs_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle clear logs action
	if ( isset( $_POST['weightpal_clear_logs'] ) && check_admin_referer( 'weightpal_clear_logs' ) ) {
		delete_option( 'weightpal_api_logs' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Logs cleared successfully.', 'weightpal' ) . '</p></div>';
	}

	// Get logs
	$logs = get_option( 'weightpal_api_logs', array() );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'View the last 10 AI API responses for debugging purposes. This helps diagnose issues with JSON parsing and AI responses.', 'weightpal' ); ?></p>

		<form method="post" style="margin-bottom: 20px;">
			<?php wp_nonce_field( 'weightpal_clear_logs' ); ?>
			<input type="hidden" name="weightpal_clear_logs" value="1">
			<?php submit_button( __( 'Clear All Logs', 'weightpal' ), 'delete', 'submit', false ); ?>
		</form>

		<?php if ( empty( $logs ) ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No API logs yet. Logs will appear here after you generate meal plans or get AI advice.', 'weightpal' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( array_reverse( $logs ) as $index => $log ) : ?>
				<div class="card" style="margin-bottom: 20px; max-width: 100%;">
					<h2 style="margin-top: 0; padding: 15px; background: #f0f0f1; border-bottom: 1px solid #dcdcde;">
						<?php echo esc_html( $log['type'] === 'meal_plan' ? 'Meal Plan Request' : 'AI Advisor Request' ); ?>
						- <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log['timestamp'] ) ); ?>
						<span style="float: right; color: <?php echo $log['success'] ? '#46b450' : '#dc3232'; ?>;">
							<?php echo $log['success'] ? '✓ Success' : '✗ Failed'; ?>
						</span>
					</h2>
					<div style="padding: 15px;">
						<h3><?php esc_html_e( 'User Input:', 'weightpal' ); ?></h3>
						<pre style="background: #f6f7f7; padding: 10px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html( $log['user_input'] ); ?></pre>

						<h3><?php esc_html_e( 'AI Raw Response:', 'weightpal' ); ?></h3>
						<pre style="background: #f6f7f7; padding: 10px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto;"><?php echo esc_html( $log['ai_response'] ); ?></pre>

						<?php if ( ! $log['success'] && ! empty( $log['error'] ) ) : ?>
							<h3 style="color: #dc3232;"><?php esc_html_e( 'Error:', 'weightpal' ); ?></h3>
							<pre style="background: #fef7f1; padding: 10px; color: #dc3232; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html( $log['error'] ); ?></pre>
						<?php endif; ?>

						<?php if ( $log['success'] && ! empty( $log['parsed_json'] ) ) : ?>
							<h3 style="color: #46b450;"><?php esc_html_e( 'Parsed JSON (Valid):', 'weightpal' ); ?></h3>
							<pre style="background: #f0f6f0; padding: 10px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto;"><?php echo esc_html( json_encode( json_decode( $log['parsed_json'] ), JSON_PRETTY_PRINT ) ); ?></pre>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Sanitize settings
 *
 * @param array $input Raw input from form.
 * @return array Sanitized input
 */
function weightpal_sanitize_options( $input ) {
	$sanitized = array();

	if ( isset( $input['gemini_api_key'] ) ) {
		$sanitized['gemini_api_key'] = sanitize_text_field( $input['gemini_api_key'] );
	}

	if ( isset( $input['gemini_model'] ) ) {
		$sanitized['gemini_model'] = sanitize_text_field( $input['gemini_model'] );
	}

	if ( isset( $input['advisor_system_prompt'] ) ) {
		$sanitized['advisor_system_prompt'] = sanitize_textarea_field( $input['advisor_system_prompt'] );
	}

	if ( isset( $input['meal_planner_system_prompt'] ) ) {
		$sanitized['meal_planner_system_prompt'] = sanitize_textarea_field( $input['meal_planner_system_prompt'] );
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
 * Section callback - General Settings
 */
function weightpal_general_section_callback() {
	echo '<p>' . esc_html__( 'Configure your API settings and token limits. These settings apply to all Weightpal features.', 'weightpal' ) . '</p>';
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
 * Section callback - AI Advisor Settings
 */
function weightpal_advisor_section_callback() {
	echo '<p>' . esc_html__( 'Customize the AI advisor behavior and system prompt for personalized weight loss coaching.', 'weightpal' ) . '</p>';
}

/**
 * Section callback - Meal Planner Settings
 */
function weightpal_meal_planner_section_callback() {
	echo '<p>' . esc_html__( 'Customize the meal planner behavior and system prompt for personalized meal planning.', 'weightpal' ) . '</p>';
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
/**
 * Render AI Advisor System Prompt field
 */
function weightpal_advisor_system_prompt_render() {
	$options       = get_option( 'weightpal_options' );
	$system_prompt = isset( $options['advisor_system_prompt'] ) ? $options['advisor_system_prompt'] : weightpal_get_default_system_prompt();
	?>
	<textarea 
		name="weightpal_options[advisor_system_prompt]" 
		id="weightpal_advisor_system_prompt"
		rows="12" 
		class="large-text code"
		placeholder="<?php esc_attr_e( 'Enter the system prompt for the AI Advisor', 'weightpal' ); ?>"
	><?php echo esc_textarea( $system_prompt ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'This system prompt defines how the AI should respond to weight loss coaching queries.', 'weightpal' ); ?>
	</p>
	<p>
		<button type="button" class="button" onclick="document.getElementById('weightpal_advisor_system_prompt').value = '<?php echo esc_js( weightpal_get_default_system_prompt() ); ?>';">
			<?php esc_html_e( 'Reset to Default', 'weightpal' ); ?>
		</button>
	</p>
	<?php
}

/**
 * Render Meal Planner System Prompt field
 */
function weightpal_meal_planner_system_prompt_render() {
	$options       = get_option( 'weightpal_options' );
	$system_prompt = isset( $options['meal_planner_system_prompt'] ) ? $options['meal_planner_system_prompt'] : weightpal_get_default_meal_planner_prompt();
	
	// Check if the prompt contains JSON instructions
	$has_json_instructions = ( stripos( $system_prompt, 'JSON' ) !== false && stripos( $system_prompt, 'days' ) !== false );
	
	if ( ! $has_json_instructions ) {
		echo '<div class="notice notice-warning inline" style="margin: 10px 0; padding: 10px;">';
		echo '<p><strong>' . esc_html__( 'Warning:', 'weightpal' ) . '</strong> ';
		echo esc_html__( 'Your system prompt does not appear to include JSON formatting instructions. Click "Reset to Default" below to use the updated prompt that ensures proper JSON output.', 'weightpal' );
		echo '</p></div>';
	}
	?>
	<textarea 
		name="weightpal_options[meal_planner_system_prompt]" 
		id="weightpal_meal_planner_system_prompt"
		rows="12" 
		class="large-text code"
		placeholder="<?php esc_attr_e( 'Enter the system prompt for the Meal Planner', 'weightpal' ); ?>"
	><?php echo esc_textarea( $system_prompt ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'This system prompt defines how the AI should respond to meal planning requests. The prompt MUST instruct the AI to return ONLY JSON format.', 'weightpal' ); ?>
	</p>
	<p>
		<button type="button" class="button button-secondary" onclick="if(confirm('<?php esc_attr_e( 'This will replace your current prompt with the default JSON-focused prompt. Continue?', 'weightpal' ); ?>')) { document.getElementById('weightpal_meal_planner_system_prompt').value = '<?php echo esc_js( weightpal_get_default_meal_planner_prompt() ); ?>'; }"><?php esc_html_e( 'Reset to Default', 'weightpal' ); ?></button>
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
 * Render General Settings page
 */
function weightpal_general_settings_page() {
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
		<h1><?php esc_html_e( 'Weightpal AI - General Settings', 'weightpal' ); ?></h1>
		
		<form action="options.php" method="post">
			<?php
			// Output security fields for the registered setting
			settings_fields( 'weightpal_general' );
			
			// Output setting sections and their fields
			do_settings_sections( 'weightpal' );
			
			// Output save settings button
			submit_button( __( 'Save Settings', 'weightpal' ) );
			?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'About Weightpal', 'weightpal' ); ?></h2>
		<p><?php esc_html_e( 'Weightpal is an AI-powered weight loss coaching plugin that uses Google Gemini to provide personalized advice, meal plans, and exercise schedules.', 'weightpal' ); ?></p>
		
		<h3><?php esc_html_e( 'API Endpoints', 'weightpal' ); ?></h3>
		<p>
			<strong><?php esc_html_e( 'AI Advisor:', 'weightpal' ); ?></strong><br>
			<code><?php echo esc_html( rest_url( 'weightpal/v1/advice' ) ); ?></code>
		</p>
		<p>
			<strong><?php esc_html_e( 'Meal Planner:', 'weightpal' ); ?></strong><br>
			<code><?php echo esc_html( rest_url( 'weightpal/v1/meal-plan' ) ); ?></code>
		</p>
	</div>
	<?php
}

/**
 * Render AI Advisor Settings page
 */
function weightpal_advisor_settings_page() {
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
		<h1><?php esc_html_e( 'Weightpal AI - Advisor Settings', 'weightpal' ); ?></h1>
		<p><?php esc_html_e( 'Configure the AI advisor system prompt to customize how the AI responds to weight loss coaching queries.', 'weightpal' ); ?></p>
		
		<form action="options.php" method="post">
			<?php
			// Output security fields
			settings_fields( 'weightpal_general' );
			
			// Output setting sections and their fields
			do_settings_sections( 'weightpal-advisor' );
			
			// Output save settings button
			submit_button( __( 'Save Settings', 'weightpal' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Render Meal Planner Settings page
 */
function weightpal_meal_planner_settings_page() {
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
		<h1><?php esc_html_e( 'Weightpal AI - Meal Planner Settings', 'weightpal' ); ?></h1>
		<p><?php esc_html_e( 'Configure the meal planner system prompt to customize how the AI generates personalized meal plans.', 'weightpal' ); ?></p>
		
		<form action="options.php" method="post">
			<?php
			// Output security fields
			settings_fields( 'weightpal_general' );
			
			// Output setting sections and their fields
			do_settings_sections( 'weightpal-meal-planner' );
			
			// Output save settings button
			submit_button( __( 'Save Settings', 'weightpal' ) );
			?>
		</form>
	</div>
	<?php
}
