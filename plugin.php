<?php
/**
 * Plugin Name: Demo Delayed Admin Notice Plugin
 * Plugin URI: https://www.mattcromwell.com/delayed-admin-notice
 * Description: This plugin is a demo of a simple way to trigger a delayed Admin notice from your plugin.
 * Author: Matt Cromwell
 * Version: 2.0.0
 * Author URI: https://www.mattcromwell.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * INSTALLATION INSTRUCTIONS:
 *
 * 1. Include the 'admin/notice.php' file in your plugin
 * 2. Do a search/replace for 'your_prefix_' and replace it with your prefix (e.g., 'myplugin_')
 * 3. Do a search/replace for 'Your_Prefix' (PascalCase) and replace it with your prefix in PascalCase (e.g., 'MyPlugin')
 * 4. Do a search/replace for 'your-plugin-textdomain' and replace it with your plugin textdomain
 * 5. In 'admin/notice.php', update the configuration variables:
 *    - $plugin_name - Your plugin name for display
 *    - $review_url - Your WordPress.org review URL
 *    - $donate_url - Your donation URL (optional, leave empty to hide)
 * 6. Optionally adjust DELAY_DAYS constant in the class (default: 30 days)
 * 7. Register the activation and uninstall hooks (see code below)
 *
 * EXAMPLE USAGE:
 *
 * // Include the notice file
 * require_once plugin_dir_path( __FILE__ ) . 'admin/notice.php';
 *
 * // Register activation hook
 * register_activation_hook( __FILE__, array( 'Your_Prefix_Review_Notice', 'set_trigger_date' ) );
 *
 * // Register uninstall hook
 * register_uninstall_hook( __FILE__, 'your_prefix_uninstall' );
 *
 * // Initialize the notice (in your plugin init function)
 * if ( class_exists( 'Your_Prefix_Review_Notice' ) ) {
 *     $review_notice = new Your_Prefix_Review_Notice();
 *     $review_notice->init();
 * }
 *
 * // Uninstall cleanup function
 * function your_prefix_uninstall() {
 *     if ( class_exists( 'Your_Prefix_Review_Notice' ) ) {
 *         Your_Prefix_Review_Notice::cleanup();
 *     }
 * }
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the notice class
require_once dirname( __FILE__ ) . '/admin/notice.php';

// Store notice instance for activation/uninstall hooks
$your_prefix_review_notice = null;

/**
 * Activation hook callback
 */
function your_prefix_review_notice_activate() {
	if ( class_exists( 'Your_Prefix_Review_Notice' ) ) {
		$notice = new Your_Prefix_Review_Notice();
		$notice->set_trigger_date();
	}
}

/**
 * Uninstall hook callback
 */
function your_prefix_uninstall() {
	if ( class_exists( 'Your_Prefix_Review_Notice' ) ) {
		$notice = new Your_Prefix_Review_Notice();
		$notice->cleanup();
	}
}

// Register activation hook
register_activation_hook( __FILE__, 'your_prefix_review_notice_activate' );

// Register uninstall hook for cleanup
register_uninstall_hook( __FILE__, 'your_prefix_uninstall' );

// Initialize the review notice (admin only)
if ( is_admin() && class_exists( 'Your_Prefix_Review_Notice' ) ) {
	$your_prefix_review_notice = new Your_Prefix_Review_Notice();
	$your_prefix_review_notice->init();
}

// ============================================================================
// DEMO PLUGIN ONLY - DO NOT COPY THIS CODE TO YOUR PLUGIN
// ============================================================================
// The code below is ONLY for the demo plugin's settings page functionality.
// It allows testing/demoing the notice in the admin environment.
// Only copy admin/notice.php to your plugin, NOT this demo settings code.
// ============================================================================

/**
 * Register demo settings page
 * 
 * NOTE: This is FOR DEMO PLUGIN PURPOSES ONLY.
 * Do NOT copy this to your plugin.
 */
function demo_notice_add_settings_page() {
	add_options_page(
		__( 'Notice Demo and Generator', 'demo-delayed-admin-notice' ),
		__( 'Notice Demo', 'demo-delayed-admin-notice' ),
		'manage_options',
		'demo-notice-settings',
		'demo_notice_render_settings_page'
	);
}
add_action( 'admin_menu', 'demo_notice_add_settings_page' );

/**
 * Initialize demo notice instance (if active)
 * 
 * NOTE: This is FOR DEMO PLUGIN PURPOSES ONLY.
 * Do NOT copy this to your plugin.
 */
function demo_notice_init_demo_notice() {
	if ( ! is_admin() || ! class_exists( 'Your_Prefix_Review_Notice' ) ) {
		return;
	}
	
	$demo_active = get_transient( 'demo_notice_active' );
	$demo_config = get_transient( 'demo_notice_config' );
	
	if ( false === $demo_active || empty( $demo_config ) ) {
		return;
	}
	
	// DEMO MODE: Build review and donation URLs from config
	// This is demo-only logic - do NOT copy to your plugin
	$demo_review_url = '';
	$demo_donate_url = '';
	
	$demo_ctas = isset( $demo_config['ctas'] ) ? $demo_config['ctas'] : array( 'review' );
	if ( in_array( 'review', $demo_ctas, true ) && ! empty( $demo_config['plugin_slug'] ) ) {
		$demo_review_url = 'https://wordpress.org/support/plugin/' . sanitize_text_field( $demo_config['plugin_slug'] ) . '/reviews/';
	}
	if ( in_array( 'donation', $demo_ctas, true ) && ! empty( $demo_config['donation_url'] ) ) {
		$demo_donate_url = esc_url_raw( $demo_config['donation_url'] );
	}
	
	// Create demo notice instance with config from transient
	// Store globally to prevent multiple instances
	global $demo_notice_instance;
	if ( ! isset( $demo_notice_instance ) ) {
		$demo_notice_instance = new Your_Prefix_Review_Notice( array(
			'prefix' => 'demo_notice',
			'delay_days' => $demo_config['delay_days'],
			'target_screens' => array( $demo_config['page'] ),
			'position' => $demo_config['position'],
			'enable_remind_again' => $demo_config['enable_remind_again'],
			'remind_again_mode' => $demo_config['remind_again_mode'],
			'remind_again_days' => $demo_config['remind_again_days'],
			'review_url' => $demo_review_url,
			'donate_url' => $demo_donate_url,
		) );
		$demo_notice_instance->init();
		
		// DEMO MODE: Set trigger date to past so notice shows immediately (bypass delay)
		// This is demo-only logic - do NOT copy to your plugin
		$demo_option_name = 'demo_notice_review_notice_trigger_date';
		update_option( $demo_option_name, time() - DAY_IN_SECONDS, false );
	}
}
add_action( 'admin_init', 'demo_notice_init_demo_notice', 5 );

// Include demo settings page (only for demo plugin)
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/admin/demo-settings.php';
}
