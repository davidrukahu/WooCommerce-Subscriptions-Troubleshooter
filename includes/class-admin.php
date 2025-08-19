<?php
declare( strict_types=1 );
/**
 * Admin Interface Controller
 *
 * @package Dr_Subs
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
            __( 'Doctor Subs', 'dr-subs' ),
            __( 'Doctor Subs', 'dr-subs' ),
            'manage_woocommerce',
            'doctor-subs',
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
        if ( 'woocommerce_page_doctor-subs' !== $hook ) {
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
                    'error'                     => __( 'An error occurred. Please try again.', 'dr-subs' ),
                    'searching'                 => __( 'Searching...', 'dr-subs' ),
                    'analyzing'                 => __( 'Analyzing subscription...', 'dr-subs' ),
                    'step1_complete'            => __( 'Step 1: Anatomy analysis complete', 'dr-subs' ),
                    'step2_complete'            => __( 'Step 2: Expected behavior analysis complete', 'dr-subs' ),
                    'step3_complete'            => __( 'Step 3: Timeline analysis complete', 'dr-subs' ),
                    'analysis_complete'         => __( 'Analysis complete!', 'dr-subs' ),
                    'no_subscription_found'     => __( 'No subscription found with that ID.', 'dr-subs' ),
                    'invalid_subscription_id'   => __( 'Please enter a valid subscription ID.', 'dr-subs' ),
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
            <h1 class="wcst-page-title">
                <span class="wcst-doctor-icon">🩺</span>
                <?php esc_html_e( 'Doctor Subs', 'dr-subs' ); ?>
            </h1>
            
            <div class="wcst-intro">
                <p><?php 
                    printf(
                        /* translators: %s: link to troubleshooting framework documentation */
                        esc_html__( 'An intuitive WooCommerce Subscriptions troubleshooting tool that implements a simple 3-step diagnostic process. %s', 'dr-subs' ),
                        '<a href="https://woocommerce.com/document/subscriptions/troubleshooting-framework/" target="_blank" rel="noopener">' . esc_html__( 'View Framework Documentation', 'dr-subs' ) . ' ↗</a>'
                    );
                ?></p>
            </div>
            
            <!-- Subscription Search -->
            <div class="wcst-search-section">
                <h2><?php esc_html_e( 'Find Subscription to Troubleshoot', 'dr-subs' ); ?></h2>
                <div class="wcst-search-container">
                    <input 
                        type="text" 
                        id="wcst-subscription-search" 
                        placeholder="<?php esc_attr_e( 'Enter subscription ID or customer email...', 'dr-subs' ); ?>"
                        class="wcst-search-input"
                    />
                    <button 
                        type="button" 
                        id="wcst-analyze-btn" 
                        class="button button-primary wcst-analyze-btn"
                    >
                        <?php esc_html_e( 'Start Troubleshooting', 'dr-subs' ); ?>
                    </button>
                </div>
                <div id="wcst-search-results" class="wcst-search-results"></div>
            </div>
            
            <!-- Progress Indicator -->
            <div id="wcst-progress" class="wcst-progress" style="display: none;">
                <h3><?php esc_html_e( '3-Step Troubleshooting Process', 'dr-subs' ); ?></h3>
                <div class="wcst-steps">
                    <div class="wcst-step" data-step="1">
                        <div class="wcst-step-number">1</div>
                        <div class="wcst-step-title"><?php esc_html_e( 'Understand the Anatomy', 'dr-subs' ); ?></div>
                        <div class="wcst-step-description"><?php esc_html_e( 'Review subscription structure and configuration', 'dr-subs' ); ?></div>
                    </div>
                    <div class="wcst-step" data-step="2">
                        <div class="wcst-step-number">2</div>
                        <div class="wcst-step-title"><?php esc_html_e( 'Determine Expected Behavior', 'dr-subs' ); ?></div>
                        <div class="wcst-step-description"><?php esc_html_e( 'Establish what should happen based on setup', 'dr-subs' ); ?></div>
                    </div>
                    <div class="wcst-step" data-step="3">
                        <div class="wcst-step-number">3</div>
                        <div class="wcst-step-title"><?php esc_html_e( 'Create Timeline', 'dr-subs' ); ?></div>
                        <div class="wcst-step-description"><?php esc_html_e( 'Document what actually occurred', 'dr-subs' ); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Results -->
            <div id="wcst-results" class="wcst-results" style="display: none;">
                
                <!-- Step 1: Subscription Anatomy -->
                <div class="wcst-section wcst-anatomy-section">
                    <h2><?php esc_html_e( 'Step 1: Subscription Anatomy', 'dr-subs' ); ?></h2>
                    <p class="wcst-section-description"><?php esc_html_e( 'Understanding how your subscription is structured and configured.', 'dr-subs' ); ?></p>
                    <div id="wcst-anatomy-content" class="wcst-content"></div>
                </div>
                
                <!-- Step 2: Expected Behavior -->
                <div class="wcst-section wcst-expected-section">
                    <h2><?php esc_html_e( 'Step 2: Expected Behavior', 'dr-subs' ); ?></h2>
                    <p class="wcst-section-description"><?php esc_html_e( 'What should happen based on your subscription configuration.', 'dr-subs' ); ?></p>
                    <div id="wcst-expected-content" class="wcst-content"></div>
                </div>
                
                <!-- Step 3: Timeline -->
                <div class="wcst-section wcst-timeline-section">
                    <h2><?php esc_html_e( 'Step 3: Timeline of Events', 'dr-subs' ); ?></h2>
                    <p class="wcst-section-description"><?php esc_html_e( 'Chronological record of what actually happened with this subscription.', 'dr-subs' ); ?></p>
                    <div id="wcst-timeline-content" class="wcst-content"></div>
                </div>
                
                <!-- Summary & Actions -->
                <div class="wcst-section wcst-summary-section">
                    <h2><?php esc_html_e( 'Summary & Next Steps', 'dr-subs' ); ?></h2>
                    <div id="wcst-summary-content" class="wcst-content"></div>
                </div>
                
            </div>
            
        </div>
        <?php
    }
}
