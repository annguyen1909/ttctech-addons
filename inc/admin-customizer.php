<?php
/**
 * Admin Area Customization
 *
 * Customizes WordPress admin area with TTCTech branding
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Remove WordPress logo from admin bar
 */
function ttctech_remove_wp_logo($wp_admin_bar) {
	$wp_admin_bar->remove_node('wp-logo');
}
add_action('admin_bar_menu', 'ttctech_remove_wp_logo', 999);

/**
 * Hide admin footer thank you text (keep version visible)
 */
function ttctech_remove_footer_admin() {
	return '';
}
add_filter('admin_footer_text', 'ttctech_remove_footer_admin');

/**
 * Custom admin CSS to hide thank you message
 */
function ttctech_admin_footer_styles() {
	echo '<style>
		body.wp-admin #footer-thankyou,
		body.wp-admin #wpfooter #footer-thankyou {
			display: none !important;
		}
	</style>';
}
add_action('admin_head', 'ttctech_admin_footer_styles');
