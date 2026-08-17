<?php
/**
 * Plugin Name: TTCTech Addons
 * Plugin URI: https://ttctech.vn
 * Description: Custom functionality and branding for TTCTech website
 * Version: 1.1.1
 * Author: TTCTech
 * Author URI: https://ttctech.vn
 * License: GPL v2 or later
 * Text Domain: ttctech-addons
 */

if (!defined('ABSPATH')) {
	exit;
}

define('TTCTECH_ADDONS_VERSION', '1.1.1');
define('TTCTECH_ADDONS_DIR', plugin_dir_path(__FILE__));
define('TTCTECH_ADDONS_URL', plugin_dir_url(__FILE__));

// Load modules
require_once TTCTECH_ADDONS_DIR . 'inc/login-customizer.php';
require_once TTCTECH_ADDONS_DIR . 'inc/admin-customizer.php';
require_once TTCTECH_ADDONS_DIR . 'inc/widgets.php';

// Site functionality must survive a theme change.
require_once TTCTECH_ADDONS_DIR . 'inc/projects.php';
require_once TTCTECH_ADDONS_DIR . 'inc/home-acf-fields.php';
require_once TTCTECH_ADDONS_DIR . 'inc/home-shortcodes.php';
