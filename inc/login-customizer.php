<?php
/**
 * Login Page Customization
 *
 * Customizes WordPress login page with TTCTech branding
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Enqueue login page styles
 */
function ttctech_login_enqueue_scripts() {
	wp_enqueue_style(
		'ttctech-login',
		TTCTECH_ADDONS_URL . 'assets/css/login.css',
		[],
		TTCTECH_ADDONS_VERSION
	);
}
add_action('login_enqueue_scripts', 'ttctech_login_enqueue_scripts');

/**
 * Change login logo URL to site home
 */
function ttctech_login_logo_url() {
	return home_url();
}
add_filter('login_headerurl', 'ttctech_login_logo_url');

/**
 * Change login logo title attribute
 */
function ttctech_login_logo_title() {
	return get_bloginfo('name');
}
add_filter('login_headertext', 'ttctech_login_logo_title');
