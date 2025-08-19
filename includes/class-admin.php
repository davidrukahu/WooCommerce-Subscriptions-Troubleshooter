<?php
declare( strict_types=1 );
/**
 * Admin Interface Controller
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin interface controller class.
 *
 * @since 2.0.0
 */
class WCST_Admin {
    
    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * Add admin menu.
     *
     * @since 2.0.0
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Subscriptions Troubleshooter', 'wc-subscriptions-troubleshooter' ),
            __( 'Subscriptions Troubleshooter', 'wc-subscriptions-troubleshooter' ),
            'manage_woocommerce',
            'wc-subscriptions-troubleshooter',
            array( $this, 'render_admin_page' )
        );
    }
    
    /**
     * Enqueue admin scripts and styles.
     *
     * @since 2.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'woocommerce_page_wc-subscriptions-troubleshooter' !== $hook ) {
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
            array( 'jquery' ),
            WCST_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX.
        wp_localize_script(
            'wcst-admin-scripts',
            'wcst_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wcst_nonce' ),
                'strings'  => array(
                    'error'                     => __( 'An error occurred. Please try again.', 'wc-subscriptions-troubleshooter' ),
                    'searching'                 => __( 'Searching...', 'wc-subscriptions-troubleshooter' ),
                    'analyzing'                 => __( 'Analyzing subscription...', 'wc-subscriptions-troubleshooter' ),
                    'step1_complete'            => __( 'Step 1: Anatomy analysis complete', 'wc-subscriptions-troubleshooter' ),
                    'step2_complete'            => __( 'Step 2: Expected behavior analysis complete', 'wc-subscriptions-troubleshooter' ),
                    'step3_complete'            => __( 'Step 3: Timeline analysis complete', 'wc-subscriptions-troubleshooter' ),
                    'analysis_complete'         => __( 'Analysis complete!', 'wc-subscriptions-troubleshooter' ),
                    'no_subscription_found'     => __( 'No subscription found with that ID.', 'wc-subscriptions-troubleshooter' ),
                    'invalid_subscription_id'   => __( 'Please enter a valid subscription ID.', 'wc-subscriptions-troubleshooter' ),
                ),
            )
        );
    }
    
    /**
     * Render the main admin page.
     *
     * @since 2.0.0
     */
    public function render_admin_page() {
        ?>
        <div class="wrap wcst-admin-wrap">
            <h1><?php esc_html_e( 'WooCommerce Subscriptions Troubleshooter', 'wc-subscriptions-troubleshooter' ); ?></h1>
            
            <div class="wcst-intro">
                <p><?php esc_html_e( 'Follow the official WooCommerce Subscriptions troubleshooting framework to diagnose subscription issues systematically.', 'wc-subscriptions-troubleshooter' ); ?></p>
            </div>
            
            <!-- Subscription Search -->
            <div class="wcst-search-section">
                <h2><?php esc_html_e( 'Find Subscription to Troubleshoot', 'wc-subscriptions-troubleshooter' ); ?></h2>
                <div class="wcst-search-container">
                    <input 
                        type="text" 
                        id="wcst-subscription-search" 
                        placeholder="<?php esc_attr_e( 'Enter subscription ID or customer email...', 'wc-subscriptions-troubleshooter' ); ?>"
                        class="wcst-search-input"
                    />
                    <button 
                        type="button" 
                        id="wcst-analyze-btn" 
                        class="button button-primary wcst-analyze-btn"
                    >
                        <?php esc_html_e( 'Start Troubleshooting', 'wc-subscriptions-troubleshooter' ); ?>
                    </button>
                </div>
                <div id="wcst-search-results" class="wcst-search-results"></div>
            </div>
            
            <!-- Progress Indicator -->
            <div id="wcst-progress" class="wcst-progress" style="display: none;">
                <h3><?php esc_html_e( '3-Step Troubleshooting Process', 'wc-subscriptions-troubleshooter' ); ?></h3>
                <div class="wcst-steps">
                    <div class="wcst-step" data-step="1">
                        <div class="wcst-step-number">1</div>
                        <div class="wcst-step-title"><?php esc_html_e( 'Understand the Anatomy', 'wc-subscriptions-troubleshooter' ); ?></div>
                        <div class="wcst-step-description"><?php esc_html_e( 'Review subscription structure and configuration', 'wc-subscriptions-troubleshooter' ); ?></div>
                    </div>
                    <div class="wcst-step" data-step="2">
                        <div class="wcst-step-number">2</div>
                        <div class="wcst-step-title"><?php esc_html_e( 'Determine Expected Behavior', 'wc-subscriptions-troubleshooter' ); ?></div>
                        <div class="wcst-step-description"><?php esc_html_e( 'Establish what should happen based on setup', 'wc-subscriptions-troubleshooter' ); ?></div>
                    </div>
                    <div class="wcst-step" data-step="3">
                        <div class="wcst-step-number">3</div>
                        <div class="wcst-step-title"><?php esc_html_e( 'Create Timeline', 'wc-subscriptions-troubleshooter' ); ?></div>
                        <div class="wcst-step-description"><?php esc_html_e( 'Document what actually occurred', 'wc-subscriptions-troubleshooter' ); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Results -->
            <div id="wcst-results" class="wcst-results" style="display: none;">
                
                <!-- Step 1: Subscription Anatomy -->
                <div class="wcst-section wcst-anatomy-section">
                    <h2><?php esc_html_e( 'Step 1: Subscription Anatomy', 'wc-subscriptions-troubleshooter' ); ?></h2>
                    <p class="wcst-section-description"><?php esc_html_e( 'Understanding how your subscription is structured and configured.', 'wc-subscriptions-troubleshooter' ); ?></p>
                    <div id="wcst-anatomy-content" class="wcst-content"></div>
                </div>
                
                <!-- Step 2: Expected Behavior -->
                <div class="wcst-section wcst-expected-section">
                    <h2><?php esc_html_e( 'Step 2: Expected Behavior', 'wc-subscriptions-troubleshooter' ); ?></h2>
                    <p class="wcst-section-description"><?php esc_html_e( 'What should happen based on your subscription configuration.', 'wc-subscriptions-troubleshooter' ); ?></p>
                    <div id="wcst-expected-content" class="wcst-content"></div>
                </div>
                
                <!-- Step 3: Timeline -->
                <div class="wcst-section wcst-timeline-section">
                    <h2><?php esc_html_e( 'Step 3: Timeline of Events', 'wc-subscriptions-troubleshooter' ); ?></h2>
                    <p class="wcst-section-description"><?php esc_html_e( 'Chronological record of what actually happened with this subscription.', 'wc-subscriptions-troubleshooter' ); ?></p>
                    <div id="wcst-timeline-content" class="wcst-content"></div>
                </div>
                
                <!-- Summary & Actions -->
                <div class="wcst-section wcst-summary-section">
                    <h2><?php esc_html_e( 'Summary & Next Steps', 'wc-subscriptions-troubleshooter' ); ?></h2>
                    <div id="wcst-summary-content" class="wcst-content"></div>
                </div>
                
            </div>
            
        </div>
        <?php
    }
}
