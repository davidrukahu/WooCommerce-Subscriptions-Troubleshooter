<?php
/**
 * AJAX Request Handler
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Analysis actions
        add_action('wp_ajax_wcst_analyze_subscription', array($this, 'analyze_subscription'));
        add_action('wp_ajax_wcst_get_subscription_data', array($this, 'get_subscription_data'));
        
        // Timeline actions
        add_action('wp_ajax_wcst_get_timeline_events', array($this, 'get_timeline_events'));
        add_action('wp_ajax_wcst_filter_timeline', array($this, 'filter_timeline'));
        
        // Export actions
        add_action('wp_ajax_wcst_export_report', array($this, 'export_report'));
        
        // Settings actions
        add_action('wp_ajax_wcst_save_settings', array($this, 'save_settings'));
    }
    
    /**
     * Analyze subscription
     */
    public function analyze_subscription() {
        try {
            // Security checks
            WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
            WCST_Security::check_permissions('manage_woocommerce');
            WCST_Security::check_rate_limit('analyze_subscription');
            
            // Validate and sanitize input
            $subscription_id = WCST_Security::validate_subscription_id($_POST['subscription_id']);
            
            // Initialize analyzers
            $anatomy_analyzer = new WCST_Subscription_Anatomy();
            $expected_analyzer = new WCST_Expected_Behavior();
            $timeline_builder = new WCST_Timeline_Builder();
            $discrepancy_detector = new WCST_Discrepancy_Detector();
            
            // Step 1: Analyze anatomy
            $anatomy_data = $anatomy_analyzer->analyze($subscription_id);
            
            // Step 2: Determine expected behavior
            $expected_data = $expected_analyzer->analyze($subscription_id);
            
            // Step 3: Build timeline
            $timeline_data = $timeline_builder->build($subscription_id);
            
            // Step 4: Detect discrepancies
            $discrepancies = $discrepancy_detector->analyze_discrepancies($subscription_id);
            
            // Escape output data
            $response = array(
                'success' => true,
                'data' => WCST_Security::escape_html(array(
                    'anatomy' => $anatomy_data,
                    'expected' => $expected_data,
                    'timeline' => $timeline_data,
                    'discrepancies' => $discrepancies
                ))
            );
            
            wp_send_json($response);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get subscription data for search
     */
    public function get_subscription_data() {
        try {
            // Security checks
            WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
            WCST_Security::check_permissions('manage_woocommerce');
            WCST_Security::check_rate_limit('search_subscriptions');
            
            // Validate and sanitize input
            $search_term = WCST_Security::validate_search_term($_POST['search_term']);
            
            $subscription_collector = new WCST_Subscription_Data_Collector();
            $results = $subscription_collector->search_subscriptions($search_term);
            
            // Escape output data
            wp_send_json_success(WCST_Security::escape_html($results));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get timeline events
     */
    public function get_timeline_events() {
        try {
            // Security checks
            WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
            WCST_Security::check_permissions('manage_woocommerce');
            WCST_Security::check_rate_limit('get_timeline_events');
            
            // Validate and sanitize input
            $subscription_id = WCST_Security::validate_subscription_id($_POST['subscription_id']);
            $filters = WCST_Security::validate_filters($_POST['filters'] ?? array());
            
            $timeline_builder = new WCST_Timeline_Builder();
            $events = $timeline_builder->get_filtered_events($subscription_id, $filters);
            
            // Escape output data
            wp_send_json_success(WCST_Security::escape_html($events));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Filter timeline
     */
    public function filter_timeline() {
        try {
            // Security checks
            WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
            WCST_Security::check_permissions('manage_woocommerce');
            WCST_Security::check_rate_limit('filter_timeline');
            
            // Validate and sanitize input
            $subscription_id = WCST_Security::validate_subscription_id($_POST['subscription_id']);
            $filters = WCST_Security::validate_filters($_POST['filters'] ?? array());
            
            $timeline_builder = new WCST_Timeline_Builder();
            $filtered_events = $timeline_builder->apply_filters($subscription_id, $filters);
            
            // Escape output data
            wp_send_json_success(WCST_Security::escape_html($filtered_events));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Export report
     */
    public function export_report() {
        try {
            // Security checks
            WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
            WCST_Security::check_permissions('manage_woocommerce');
            WCST_Security::check_rate_limit('export_report');
            
            // Validate and sanitize input
            $subscription_id = WCST_Security::validate_subscription_id($_POST['subscription_id']);
            $format = WCST_Security::validate_export_format($_POST['format']);
            
            $exporter = new WCST_Report_Exporter();
            $report_data = $exporter->generate_report($subscription_id, $format);
            
            // Escape output data
            wp_send_json_success(WCST_Security::escape_html($report_data));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        try {
            // Security checks
            WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
            WCST_Security::check_permissions('manage_woocommerce');
            WCST_Security::check_rate_limit('save_settings');
            
            // Validate and sanitize input
            $settings = WCST_Security::validate_settings($_POST['settings'] ?? array());
            
            // Save validated settings
            foreach ($settings as $key => $value) {
                WCST_Plugin::update_option($key, $value);
            }
            
            wp_send_json_success(__('Settings saved successfully.', 'wc-subscriptions-troubleshooter'));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    

} 