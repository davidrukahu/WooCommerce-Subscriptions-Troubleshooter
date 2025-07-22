<?php
/**
 * Main Plugin Class
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * AJAX handler instance
     */
    public $ajax_handler;
    
    /**
     * Logger instance
     */
    public $logger;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . WCST_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load admin class
        if (is_admin()) {
            $this->admin = new WCST_Admin();
        }
        
        // Load AJAX handler
        $this->ajax_handler = new WCST_Ajax_Handler();
        
        // Load logger
        $this->logger = new WCST_Logger();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wc-subscriptions-troubleshooter', false, dirname(WCST_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        do_action('wcst_init');
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Admin-specific initialization
        do_action('wcst_admin_init');
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-subscriptions-troubleshooter') . '">' . 
                         __('Settings', 'wc-subscriptions-troubleshooter') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Issues tracking table
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) NOT NULL,
            issue_type varchar(50) NOT NULL,
            issue_category varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            details longtext,
            detected_at datetime NOT NULL,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY subscription_id (subscription_id),
            KEY issue_type (issue_type),
            KEY severity (severity),
            KEY detected_at (detected_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_options = array(
            'enable_logging' => true,
            'log_retention_days' => 30,
            'auto_scan_enabled' => false,
            'scan_frequency' => 'daily'
        );
        
        add_option('wcst_settings', $default_options);
    }
    
    /**
     * Get plugin option
     */
    public static function get_option($key, $default = null) {
        $options = get_option('wcst_settings', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update plugin option
     */
    public static function update_option($key, $value) {
        $options = get_option('wcst_settings', array());
        $options[$key] = $value;
        update_option('wcst_settings', $options);
    }
} 