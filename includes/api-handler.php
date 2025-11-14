<?php
/**
 * Weightpal REST API Handler
 *
 * @package Weightpal
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API routes
 */
function weightpal_register_rest_routes() {
	register_rest_route(
		'weightpal/v1',
		'/advice',
		array(
			'methods'             => 'POST',
			'callback'            => 'weightpal_get_ai_advice',
			'permission_callback' => '__return_true',
			'args'                => array(
				'weight'    => array(
					'required'          => true,
					'type'              => 'number',
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
					'sanitize_callback' => function( $param ) {
						return floatval( $param );
					},
				),
				'height'    => array(
					'required'          => true,
					'type'              => 'number',
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
					'sanitize_callback' => function( $param ) {
						return floatval( $param );
					},
				),
				'routine'   => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'userQuery' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'weightpal_register_rest_routes' );

/**
 * Get AI advice callback
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response|WP_Error Response or error.
 */
function weightpal_get_ai_advice( $request ) {
	try {
		// Get POST data (already validated and sanitized by REST API)
		$weight     = $request->get_param( 'weight' );
		$height     = $request->get_param( 'height' );
		$routine    = $request->get_param( 'routine' );
		$user_query = $request->get_param( 'userQuery' );

	// Calculate BMI
	// BMI = weight (kg) / (height (m))^2
	// Convert height from cm to meters
	$height_in_meters = $height / 100;
	$bmi              = $weight / ( $height_in_meters * $height_in_meters );
	$bmi              = round( $bmi, 2 );

	// Get plugin settings
	$options = get_option( 'weightpal_options' );

	// Check if API key exists
	if ( empty( $options['gemini_api_key'] ) ) {
		return new WP_Error(
			'missing_api_key',
			__( 'Gemini API key is not configured. Please contact the site administrator to configure the Weightpal plugin.', 'weightpal' ),
			array( 'status' => 500 )
		);
	}

	$api_key           = $options['gemini_api_key'];
	$gemini_model      = ! empty( $options['gemini_model'] ) ? $options['gemini_model'] : 'gemini-1.5-flash';
	$system_prompt     = ! empty( $options['system_prompt'] ) ? $options['system_prompt'] : weightpal_get_default_system_prompt();
	$max_output_tokens = ! empty( $options['max_output_tokens'] ) ? $options['max_output_tokens'] : 2048;

	// Construct user prompt
	$user_prompt = sprintf(
		"User Data:\n* Weight: %s kg\n* Height: %s cm\n* Calculated BMI: %s\n* Daily Routine: %s\n\nUser's Question: %s",
		$weight,
		$height,
		$bmi,
		$routine,
		$user_query
	);

	// Call Gemini API
	$ai_response = weightpal_call_gemini_api( $api_key, $gemini_model, $system_prompt, $user_prompt, $max_output_tokens );

	// Check for errors
	if ( is_wp_error( $ai_response ) ) {
		return $ai_response;
	}

	// Return successful response
	return new WP_REST_Response(
		array(
			'success' => true,
			'data'    => $ai_response,
			'bmi'     => $bmi,
		),
		200
	);
	} catch ( Exception $e ) {
		// Log the error
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Weightpal API Error: ' . $e->getMessage() );
		}

		return new WP_Error(
			'api_exception',
			sprintf(
				/* translators: %s: error message */
				__( 'An error occurred: %s', 'weightpal' ),
				$e->getMessage()
			),
			array( 'status' => 500 )
		);
	}
}

/**
 * Call Gemini API
 *
 * @param string $api_key          The Gemini API key.
 * @param string $model            The Gemini model to use.
 * @param string $system_prompt    The system prompt.
 * @param string $user_prompt      The user prompt.
 * @param int    $max_output_tokens Maximum output tokens.
 * @return string|WP_Error AI response text or error.
 */
function weightpal_call_gemini_api( $api_key, $model, $system_prompt, $user_prompt, $max_output_tokens ) {
	// Increase PHP execution time limit if needed
	$original_time_limit = ini_get( 'max_execution_time' );
	if ( $original_time_limit > 0 && $original_time_limit < 180 ) {
		@set_time_limit( 180 );
	}

	// Gemini API endpoint - Using v1 API with selected model
	$api_url = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent?key=' . $api_key;

	// Retry configuration
	$max_retries = 3;
	$retry_delay = 2; // seconds

	for ( $attempt = 1; $attempt <= $max_retries; $attempt++ ) {
		$response = weightpal_make_gemini_request( $api_url, $system_prompt, $user_prompt, $max_output_tokens );

		// If not an error, return the response
		if ( ! is_wp_error( $response ) ) {
			return $response;
		}

		// Check if it's a retryable error (503 or timeout)
		$error_data = $response->get_error_data();
		$is_retryable = isset( $error_data['status'] ) && ( $error_data['status'] === 503 || $error_data['status'] === 504 );

		// If it's the last attempt or not retryable, return the error
		if ( $attempt === $max_retries || ! $is_retryable ) {
			return $response;
		}

		// Wait before retrying (exponential backoff)
		sleep( $retry_delay * $attempt );
	}

	return $response;
}

/**
 * Make a single request to Gemini API
 *
 * @param string $api_url          The API URL.
 * @param string $system_prompt    The system prompt.
 * @param string $user_prompt      The user prompt.
 * @param int    $max_output_tokens Maximum output tokens.
 * @return string|WP_Error AI response text or error.
 */
function weightpal_make_gemini_request( $api_url, $system_prompt, $user_prompt, $max_output_tokens ) {

	// Prepare request body
	$body = array(
		'contents'         => array(
			array(
				'parts' => array(
					array(
						'text' => $system_prompt . "\n\n" . $user_prompt,
					),
				),
			),
		),
		'generationConfig' => array(
			'maxOutputTokens' => $max_output_tokens,
			'temperature'     => 0.7,
			'topP'            => 0.8,
			'topK'            => 40,
		),
		'safetySettings'   => array(
			array(
				'category'  => 'HARM_CATEGORY_HARASSMENT',
				'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
			),
			array(
				'category'  => 'HARM_CATEGORY_HATE_SPEECH',
				'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
			),
			array(
				'category'  => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
				'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
			),
			array(
				'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
				'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
			),
		),
	);

	// Make the API request
	$response = wp_remote_post(
		$api_url,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 120, // Increased to 120 seconds for longer responses
		)
	);

	// Check for request errors
	if ( is_wp_error( $response ) ) {
		// Log error for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Weightpal API Request Error: ' . $response->get_error_message() );
		}

		return new WP_Error(
			'api_request_failed',
			sprintf(
				/* translators: %s: error message */
				__( 'Failed to connect to Gemini API: %s', 'weightpal' ),
				$response->get_error_message()
			),
			array( 'status' => 503 )
		);
	}

	// Get response code
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	// Check for HTTP errors
	if ( $response_code !== 200 ) {
		$error_data = json_decode( $response_body, true );
		$error_message = isset( $error_data['error']['message'] ) 
			? $error_data['error']['message'] 
			: __( 'Unknown API error', 'weightpal' );

		// Log error for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Weightpal Gemini API Error %d: %s', $response_code, $error_message ) );
		}

		// Provide user-friendly messages for common errors
		if ( $response_code === 503 ) {
			$error_message = __( 'The AI service is currently experiencing high demand. Please try again in a few moments.', 'weightpal' );
		} elseif ( $response_code === 429 ) {
			$error_message = __( 'API rate limit exceeded. Please wait a moment and try again.', 'weightpal' );
		} elseif ( $response_code === 401 || $response_code === 403 ) {
			$error_message = __( 'API authentication failed. Please check your API key in the plugin settings.', 'weightpal' );
		}

		return new WP_Error(
			'api_error',
			$error_message,
			array( 'status' => $response_code )
		);
	}

	// Parse JSON response
	$data = json_decode( $response_body, true );

	// Extract AI text from response
	if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
		return $data['candidates'][0]['content']['parts'][0]['text'];
	}

	// Check if content was blocked by safety filters
	if ( isset( $data['candidates'][0]['finishReason'] ) && $data['candidates'][0]['finishReason'] === 'SAFETY' ) {
		return new WP_Error(
			'content_blocked',
			__( 'The request was blocked by safety filters. Please try rephrasing your query.', 'weightpal' ),
			array( 'status' => 400 )
		);
	}

	// If we can't find the text, return a generic error
	return new WP_Error(
		'invalid_response',
		__( 'Received an invalid response from Gemini API. Please try again.', 'weightpal' ),
		array( 'status' => 500 )
	);
}

/**
 * Add CORS headers for REST API
 */
function weightpal_add_cors_headers() {
	// Allow CORS for the REST API endpoint
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
	header( 'Access-Control-Max-Age: 86400' );
}
add_action( 'rest_api_init', 'weightpal_add_cors_headers' );
