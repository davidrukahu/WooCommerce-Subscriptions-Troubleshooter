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
        $this->verify_nonce();
        $this->check_permissions();
        
        $subscription_id = sanitize_text_field($_POST['subscription_id']);
        
        if (empty($subscription_id)) {
            wp_send_json_error(__('Subscription ID is required.', 'wc-subscriptions-troubleshooter'));
        }
        
        try {
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
            
            $response = array(
                'success' => true,
                'data' => array(
                    'anatomy' => $anatomy_data,
                    'expected' => $expected_data,
                    'timeline' => $timeline_data,
                    'discrepancies' => $discrepancies
                )
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
        $this->verify_nonce();
        $this->check_permissions();
        
        $search_term = sanitize_text_field($_POST['search_term']);
        
        if (empty($search_term)) {
            wp_send_json_error(__('Search term is required.', 'wc-subscriptions-troubleshooter'));
        }
        
        try {
            $subscription_collector = new WCST_Subscription_Data_Collector();
            $results = $subscription_collector->search_subscriptions($search_term);
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get timeline events
     */
    public function get_timeline_events() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $subscription_id = sanitize_text_field($_POST['subscription_id']);
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        if (empty($subscription_id)) {
            wp_send_json_error(__('Subscription ID is required.', 'wc-subscriptions-troubleshooter'));
        }
        
        try {
            $timeline_builder = new WCST_Timeline_Builder();
            $events = $timeline_builder->get_filtered_events($subscription_id, $filters);
            
            wp_send_json_success($events);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Filter timeline
     */
    public function filter_timeline() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $subscription_id = sanitize_text_field($_POST['subscription_id']);
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        if (empty($subscription_id)) {
            wp_send_json_error(__('Subscription ID is required.', 'wc-subscriptions-troubleshooter'));
        }
        
        try {
            $timeline_builder = new WCST_Timeline_Builder();
            $filtered_events = $timeline_builder->apply_filters($subscription_id, $filters);
            
            wp_send_json_success($filtered_events);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Export report
     */
    public function export_report() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $subscription_id = sanitize_text_field($_POST['subscription_id']);
        $format = sanitize_text_field($_POST['format']); // 'pdf', 'csv', 'html'
        
        if (empty($subscription_id)) {
            wp_send_json_error(__('Subscription ID is required.', 'wc-subscriptions-troubleshooter'));
        }
        
        try {
            $exporter = new WCST_Report_Exporter();
            $report_data = $exporter->generate_report($subscription_id, $format);
            
            wp_send_json_success($report_data);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        try {
            foreach ($settings as $key => $value) {
                WCST_Plugin::update_option($key, sanitize_text_field($value));
            }
            
            wp_send_json_success(__('Settings saved successfully.', 'wc-subscriptions-troubleshooter'));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Verify nonce
     */
    private function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'], 'wcst_nonce')) {
            wp_die(__('Security check failed.', 'wc-subscriptions-troubleshooter'));
        }
    }
    
    /**
     * Check user permissions
     */
    private function check_permissions() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to perform this action.', 'wc-subscriptions-troubleshooter'));
        }
    }
} 