<?php
/**
 * Plugin Name: Agent Ready
 * Plugin URI:  https://github.com/Open-WP-Club/agentready-wp
 * Description: Scan your WordPress site for AI agent readiness and generate missing files (llms.txt, MCP Server Card, Agent Skills, and more).
 * Version:     1.0.0
 * Author:      Open WP Club
 * License:     GPL-2.0-or-later
 * Text Domain: agentready
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGENTREADY_VERSION', '1.0.0' );
define( 'AGENTREADY_FILE', __FILE__ );
define( 'AGENTREADY_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGENTREADY_URL', plugin_dir_url( __FILE__ ) );

require_once AGENTREADY_DIR . 'includes/class-scanner.php';
require_once AGENTREADY_DIR . 'includes/class-well-known.php';
require_once AGENTREADY_DIR . 'admin/class-admin.php';

register_activation_hook( __FILE__, 'agentready_activate' );
register_deactivation_hook( __FILE__, 'agentready_deactivate' );

function agentready_activate() {
	AgentReady_Well_Known::register_rewrites();
	flush_rewrite_rules();
}

function agentready_deactivate() {
	flush_rewrite_rules();
}

add_action( 'plugins_loaded', function () {
	AgentReady_Well_Known::init();
	if ( is_admin() ) {
		AgentReady_Admin::init();
	}
} );
