<?php
/**
 * Admin Interface Controller
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wcst_analyze_subscription', array($this, 'ajax_analyze_subscription'));
        add_action('wp_ajax_wcst_get_subscription_data', array($this, 'ajax_get_subscription_data'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Subscriptions Troubleshooter', 'wc-subscriptions-troubleshooter'),
            __('Subscriptions Troubleshooter', 'wc-subscriptions-troubleshooter'),
            'manage_woocommerce',
            'wc-subscriptions-troubleshooter',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_wc-subscriptions-troubleshooter' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'wcst-admin-styles',
            WCST_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            WCST_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'wcst-admin-scripts',
            WCST_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery', 'wp-util'),
            WCST_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('wcst-admin-scripts', 'wcst_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcst_nonce'),
            'strings' => array(
                'analyzing' => __('Analyzing subscription...', 'wc-subscriptions-troubleshooter'),
                'error' => __('An error occurred. Please try again.', 'wc-subscriptions-troubleshooter'),
                'no_subscription' => __('No subscription found with the provided ID.', 'wc-subscriptions-troubleshooter')
            )
        ));
    }
    
    /**
     * Render main admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap wcst-admin">
            <h1><?php _e('WooCommerce Subscriptions Troubleshooter', 'wc-subscriptions-troubleshooter'); ?></h1>
            
            <div class="wcst-container">
                <!-- Subscription Selector -->
                <div class="wcst-subscription-selector">
                    <div class="wcst-search-box">
                        <input type="text" id="wcst-subscription-search" 
                               placeholder="<?php _e('Enter subscription ID or customer email', 'wc-subscriptions-troubleshooter'); ?>"
                               class="regular-text">
                        <button type="button" id="wcst-analyze-btn" class="button button-primary">
                            <?php _e('Analyze Subscription', 'wc-subscriptions-troubleshooter'); ?>
                        </button>
                    </div>
                    <div id="wcst-search-results" class="wcst-search-results" style="display: none;"></div>
                </div>
                
                <!-- Progress Indicator -->
                <div class="wcst-progress" id="wcst-progress" style="display: none;">
                    <div class="wcst-progress-steps">
                        <div class="wcst-step" data-step="1">
                            <span class="wcst-step-number">1</span>
                            <span class="wcst-step-title"><?php _e('Anatomy', 'wc-subscriptions-troubleshooter'); ?></span>
                        </div>
                        <div class="wcst-step" data-step="2">
                            <span class="wcst-step-number">2</span>
                            <span class="wcst-step-title"><?php _e('Expected Behavior', 'wc-subscriptions-troubleshooter'); ?></span>
                        </div>
                        <div class="wcst-step" data-step="3">
                            <span class="wcst-step-number">3</span>
                            <span class="wcst-step-title"><?php _e('Timeline', 'wc-subscriptions-troubleshooter'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Results Container -->
                <div id="wcst-results" class="wcst-results" style="display: none;">
                    <!-- Step 1: Anatomy -->
                    <div class="wcst-section" id="wcst-anatomy-section">
                        <div class="wcst-section-header">
                            <h2><?php _e('Step 1: Subscription Anatomy', 'wc-subscriptions-troubleshooter'); ?></h2>
                            <button type="button" class="wcst-toggle-section" data-section="anatomy">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                        <div class="wcst-section-content" id="wcst-anatomy-content">
                            <div class="wcst-loading"><?php _e('Loading subscription anatomy...', 'wc-subscriptions-troubleshooter'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Expected Behavior -->
                    <div class="wcst-section" id="wcst-expected-section">
                        <div class="wcst-section-header">
                            <h2><?php _e('Step 2: Expected Behavior', 'wc-subscriptions-troubleshooter'); ?></h2>
                            <button type="button" class="wcst-toggle-section" data-section="expected">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                        <div class="wcst-section-content" id="wcst-expected-content">
                            <div class="wcst-loading"><?php _e('Analyzing expected behavior...', 'wc-subscriptions-troubleshooter'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Timeline -->
                    <div class="wcst-section" id="wcst-timeline-section">
                        <div class="wcst-section-header">
                            <h2><?php _e('Step 3: Event Timeline', 'wc-subscriptions-troubleshooter'); ?></h2>
                            <button type="button" class="wcst-toggle-section" data-section="timeline">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                        <div class="wcst-section-content" id="wcst-timeline-content">
                            <div class="wcst-loading"><?php _e('Building timeline...', 'wc-subscriptions-troubleshooter'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Summary and Issues -->
                    <div class="wcst-section" id="wcst-summary-section">
                        <div class="wcst-section-header">
                            <h2><?php _e('Summary & Issues', 'wc-subscriptions-troubleshooter'); ?></h2>
                            <button type="button" class="wcst-toggle-section" data-section="summary">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                        <div class="wcst-section-content" id="wcst-summary-content">
                            <div class="wcst-loading"><?php _e('Generating summary...', 'wc-subscriptions-troubleshooter'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for subscription analysis
     */
    public function ajax_analyze_subscription() {
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
     * AJAX handler for getting subscription data
     */
    public function ajax_get_subscription_data() {
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
} 