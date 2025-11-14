<?php
/**
 * Plugin Name: Weightpal
 * Plugin URI: https://example.com/weightpal
 * Description: AI-powered weight loss coaching using Google Gemini API. Provides personalized advice, meal plans, and exercise schedules.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: weightpal
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'WEIGHTPAL_VERSION', '1.0.0' );
define( 'WEIGHTPAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEIGHTPAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WEIGHTPAL_PLUGIN_FILE', __FILE__ );

/**
 * Plugin activation hook
 */
function weightpal_activate() {
	// Set default options on activation
	$default_options = array(
		'gemini_api_key'      => '',
		'system_prompt'       => weightpal_get_default_system_prompt(),
		'max_output_tokens'   => 2048,
		'max_input_tokens'    => 8192,
	);

	// Only add defaults if options don't exist
	if ( false === get_option( 'weightpal_options' ) ) {
		add_option( 'weightpal_options', $default_options );
	}

	// Flush rewrite rules for REST API
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'weightpal_activate' );

/**
 * Plugin deactivation hook
 */
function weightpal_deactivate() {
	// Flush rewrite rules
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'weightpal_deactivate' );

/**
 * Get default system prompt
 *
 * @return string Default system prompt for Gemini AI
 */
function weightpal_get_default_system_prompt() {
	return "You are 'Weightpal,' an expert AI health and fitness coach. Your goal is to help users lose weight safely and effectively. You must be encouraging, positive, and base your advice on science.\n\nWhen a user provides their weight, height, BMI, daily routine, and a goal, you must generate a response that includes:\n1. **Personalized Advice:** Direct, actionable advice based on their query.\n2. **Sample Meal Plan:** A one-day sample meal plan (breakfast, lunch, dinner, snacks) appropriate for their goals.\n3. **Exercise Schedule:** A simple exercise schedule (e.g., 'Monday: 30-min brisk walk', 'Tuesday: Bodyweight circuit') that fits their stated routine.\n\nDo not provide any medical diagnoses. Always encourage the user to consult a doctor before starting a new diet or exercise program.";
}

/**
 * Include required files
 */
// Admin Settings Page
if ( is_admin() ) {
	require_once WEIGHTPAL_PLUGIN_DIR . 'includes/admin-settings.php';
}

// REST API Handler
require_once WEIGHTPAL_PLUGIN_DIR . 'includes/api-handler.php';

/**
 * Load plugin text domain for translations
 */
function weightpal_load_textdomain() {
	load_plugin_textdomain( 'weightpal', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'weightpal_load_textdomain' );
