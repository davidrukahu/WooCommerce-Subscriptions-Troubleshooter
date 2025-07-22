<?php
/**
 * Plugin Name: WooCommerce Subscriptions Troubleshooter
 * Plugin URI: https://github.com/davidrukahu/WooCommerce-Subscriptions-Troubleshooter
 * Description: A comprehensive troubleshooting tool for WooCommerce Subscriptions that guides users through a 3-step diagnostic process.
 * Version: 1.0.0
 * Author: DavidR
 * Author URI: https://github.com/davidrukahu/WooCommerce-Subscriptions-Troubleshooter
 * Text Domain: wc-subscriptions-troubleshooter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WC_Subscriptions_Troubleshooter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WCST_PLUGIN_FILE', __FILE__);
define('WCST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WCST_PLUGIN_VERSION', '1.0.0');
define('WCST_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Function to check dependencies
function wcst_check_dependencies() {
    $errors = array();
    
    // Check if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $errors[] = __('WooCommerce Subscriptions Troubleshooter requires WooCommerce to be installed and activated.', 'wc-subscriptions-troubleshooter');
    }
    
    // Check if WooCommerce Subscriptions is active
    $subscriptions_active = false;
    
    // Method 1: Check if the plugin file is active
    if (in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $subscriptions_active = true;
    }
    
    // Method 2: Check if the class exists (backup method)
    if (!$subscriptions_active && class_exists('WC_Subscriptions')) {
        $subscriptions_active = true;
    }
    
    // Method 3: Check if the function exists (another backup method)
    if (!$subscriptions_active && function_exists('wcs_get_subscription')) {
        $subscriptions_active = true;
    }
    
    if (!$subscriptions_active) {
        $errors[] = __('WooCommerce Subscriptions Troubleshooter requires WooCommerce Subscriptions to be installed and activated.', 'wc-subscriptions-troubleshooter');
    }
    
    return $errors;
}

// Check dependencies and show notices if needed
add_action('admin_notices', function() {
    $errors = wcst_check_dependencies();
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        }
    }
});

// Only proceed if dependencies are met
if (!empty(wcst_check_dependencies())) {
    return;
}

// Autoloader
spl_autoload_register(function ($class) {
    // Only handle our plugin classes
    if (strpos($class, 'WCST_') !== 0) {
        return;
    }

    // Convert class name to file path
    $class_file = str_replace('_', '-', strtolower($class));
    $class_file = str_replace('wcst-', '', $class_file);
    $file_path = WCST_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    // Double-check dependencies before initializing
    if (empty(wcst_check_dependencies())) {
        // Initialize main plugin class
        new WCST_Plugin();
    }
}, 20); // Higher priority to ensure WooCommerce and Subscriptions are loaded first

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create database tables if needed
    WCST_Plugin::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
    WCST_Plugin::deactivate();
}); 