<?php
/**
 * Plugin Name: Weightpal
 * Plugin URI: https://example.com/weightpal
 * Description: AI-powered weight loss coaching using Google Gemini API. Provides personalized advice, meal plans, and exercise schedules.
 * Version: 1.0.6
 * Author: Ken Vu
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
define( 'WEIGHTPAL_VERSION', '1.0.6' );
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
		'gemini_model'        => 'gemini-1.5-flash',
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
 * Get default system prompt for AI Advisor
 *
 * @return string Default system prompt for Gemini AI Advisor
 */
function weightpal_get_default_system_prompt() {
	return "You are 'Weightpal,' an expert AI health and fitness coach. Your goal is to help users lose weight safely and effectively. You must be encouraging, positive, and base your advice on science.\n\nWhen a user provides their weight, height, BMI, daily routine, and a goal, you must generate a response that includes:\n1. **Personalized Advice:** Direct, actionable advice based on their query.\n2. **Sample Meal Plan:** A one-day sample meal plan (breakfast, lunch, dinner, snacks) appropriate for their goals.\n3. **Exercise Schedule:** A simple exercise schedule (e.g., 'Monday: 30-min brisk walk', 'Tuesday: Bodyweight circuit') that fits their stated routine.\n\nDo not provide any medical diagnoses. Always encourage the user to consult a doctor before starting a new diet or exercise program.";
}

/**
 * Get default system prompt for Meal Planner
 *
 * @return string Default system prompt for Meal Planner
 */
function weightpal_get_default_meal_planner_prompt() {
	return "!!!CRITICAL INSTRUCTION!!! OUTPUT MUST BE VALID JSON ONLY. NO INTRODUCTIONS. NO EXPLANATIONS. NO TEXT BEFORE OR AFTER JSON.\n\nIf you output anything other than valid JSON starting with { and ending with }, you have FAILED.\n\nGenerate a meal plan using this exact JSON structure:\n\n{\n  \"plan_duration\": \"7 days\",\n  \"daily_calorie_target\": 2000,\n  \"days\": [\n    {\n      \"day_number\": 1,\n      \"day_name\": \"Day 1\",\n      \"date\": \"2025-11-15\",\n      \"total_calories\": 2000,\n      \"meals\": [\n        {\n          \"meal_number\": 1,\n          \"meal_name\": \"Breakfast\",\n          \"meal_time\": \"8:00 AM\",\n          \"dish_name\": \"Oatmeal with Berries\",\n          \"ingredients\": [\"1 cup oats\", \"1/2 cup blueberries\", \"1 tbsp honey\"],\n          \"calories\": 350,\n          \"protein\": \"12g\",\n          \"carbs\": \"60g\",\n          \"fats\": \"8g\",\n          \"cooking_instructions\": \"Cook oats with water for 5 minutes. Top with berries and honey.\",\n          \"prep_time\": \"10 minutes\"\n        }\n      ]\n    }\n  ],\n  \"shopping_list\": [\"oats\", \"blueberries\", \"honey\"],\n  \"notes\": \"Stay hydrated. Adjust portions as needed.\"\n}\n\nRULES YOU MUST FOLLOW:\n1. Output ONLY the JSON object - no markdown, no code blocks, no explanations\n2. Start your response with { and end with }\n3. Include breakfast, lunch, dinner, and at least one snack for each day\n4. Each day must have an array of meals with all required fields\n5. Respect all dietary restrictions and preferences mentioned by the user\n6. Keep cooking instructions practical and concise\n7. Calculate accurate calorie counts for each meal\n8. Ensure nutritional balance across all meals\n9. Use realistic ingredient portions\n10. Generate dates sequentially starting from today\n\nDo NOT:\n- Add any text before the JSON\n- Add any text after the JSON\n- Use markdown code blocks like ```json\n- Add comments or explanations\n- Omit any required fields\n\nYour entire response should be parseable by JSON.parse() immediately.";
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

// Gutenberg Block Registration
require_once WEIGHTPAL_PLUGIN_DIR . 'includes/block-register.php';

/**
 * Load plugin text domain for translations
 */
function weightpal_load_textdomain() {
	load_plugin_textdomain( 'weightpal', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'weightpal_load_textdomain' );
