<?php
/**
 * Plugin Name: WCST Test Data Generator
 * Description: Generates test subscription data for troubleshooting (WC Subscriptions 7.7.0+ compatible)
 * Version: 1.0.1
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Safety check - prevent running on production
function wcst_is_production_site() {
    $url = home_url();
    $test_indicators = array(
        'localhost',
        '127.0.0.1',
        '.local',
        '.test',
        '.dev',
        'staging',
        'test',
    );
    
    foreach ( $test_indicators as $indicator ) {
        if ( strpos( $url, $indicator ) !== false ) {
            return false;
        }
    }
    
    return true; // Err on side of caution
}

if ( wcst_is_production_site() ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>WCST Test Data Generator:</strong> This plugin is disabled on production sites for safety.</p></div>';
    });
    return;
}

class WCST_Simple_Test_Generator {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'wp_ajax_wcst_simple_generate', array( $this, 'generate_data' ) );
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Simple Test Data Generator',
            'Simple Test Data',
            'manage_woocommerce',
            'wcst-simple-generator',
            array( $this, 'admin_page' )
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Simple WooCommerce Subscriptions Test Data Generator</h1>
            <div class="notice notice-warning">
                <p><strong>Test Environment Only!</strong> This creates test data for troubleshooting.</p>
            </div>
            
            <div class="card">
                <h2>Generate Basic Test Data</h2>
                <p>Creates: 2 customers, 2 products, 4 subscriptions with various scenarios</p>
                <button type="button" id="simple-generate" class="button button-primary">
                    Generate Simple Test Data
                </button>
                <div id="simple-results" style="margin-top: 15px;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#simple-generate').on('click', function() {
                const button = $(this);
                const results = $('#simple-results');
                
                button.prop('disabled', true).text('Generating...');
                results.html('<p>Creating test data...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wcst_simple_generate',
                        nonce: '<?php echo wp_create_nonce( 'wcst_simple_generate' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            results.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        } else {
                            results.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        results.html('<div class="notice notice-error"><p>Ajax request failed.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Generate Simple Test Data');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function generate_data() {
        // Security check
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wcst_simple_generate' ) ) {
            wp_send_json_error( 'Security check failed' );
        }
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }
        
        try {
            $results = $this->create_simple_test_data();
            wp_send_json_success( $results );
        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }
    
    private function create_simple_test_data() {
        // 1. Create 2 test customers
        $customers = array();
        for ( $i = 1; $i <= 2; $i++ ) {
            $email = "testcustomer{$i}@example.com";
            
            if ( ! email_exists( $email ) ) {
                $customer_id = wp_create_user( $email, 'test123', $email );
                if ( ! is_wp_error( $customer_id ) ) {
                    wp_update_user( array(
                        'ID' => $customer_id,
                        'first_name' => "Test{$i}",
                        'last_name' => 'Customer',
                        'role' => 'customer',
                    ) );
                    $customers[] = $customer_id;
                }
            } else {
                $customers[] = get_user_by( 'email', $email )->ID;
            }
        }
        
        // 2. Create 2 simple subscription products
        $products = array();
        $product_data = array(
            array( 'name' => 'Test Monthly Sub', 'price' => '19.99', 'period' => 'month', 'interval' => 1 ),
            array( 'name' => 'Test Annual Sub', 'price' => '199.99', 'period' => 'year', 'interval' => 1 ),
        );
        
        foreach ( $product_data as $data ) {
            $product = new WC_Product_Simple(); // Use simple product and add metadata
            $product->set_name( $data['name'] );
            $product->set_regular_price( $data['price'] );
            $product->set_status( 'publish' );
            $product->set_catalog_visibility( 'visible' );
            
            $product_id = $product->save();
            if ( $product_id ) {
                // Add subscription metadata manually
                update_post_meta( $product_id, '_subscription_price', $data['price'] );
                update_post_meta( $product_id, '_subscription_period', $data['period'] );
                update_post_meta( $product_id, '_subscription_period_interval', $data['interval'] );
                update_post_meta( $product_id, '_subscription_length', 0 );
                
                // Change product type to subscription
                wp_set_object_terms( $product_id, 'subscription', 'product_type' );
                
                $products[] = $product_id;
            }
        }
        
        // 3. Create basic subscriptions manually using WooCommerce order system
        $subscriptions = 0;
        
        foreach ( $customers as $customer_id ) {
            foreach ( array_slice( $products, 0, 2 ) as $product_id ) { // Each customer gets 2 subscriptions
                
                // Create a simple order first
                $order = wc_create_order( array( 'customer_id' => $customer_id ) );
                $product = wc_get_product( $product_id );
                $order->add_product( $product, 1 );
                $order->set_payment_method( 'manual' );
                $order->set_payment_method_title( 'Manual' );
                $order->calculate_totals();
                $order->set_status( 'completed' );
                $order->save();
                
                // Try to create subscription using the WooCommerce function with minimal args
                $subscription_args = array(
                    'order_id'         => $order->get_id(),
                    'customer_id'      => $customer_id,
                    'billing_period'   => get_post_meta( $product_id, '_subscription_period', true ) ?: 'month',
                    'billing_interval' => get_post_meta( $product_id, '_subscription_period_interval', true ) ?: 1,
                    'start_date'       => current_time( 'mysql' ),
                );
                
                $subscription = wcs_create_subscription( $subscription_args );
                
                if ( ! is_wp_error( $subscription ) ) {
                    $subscription->add_product( $product, 1 );
                    $subscription->set_payment_method( 'manual' );
                    $subscription->set_payment_method_title( 'Manual Payment' );
                    $subscription->calculate_totals();
                    $subscription->set_status( 'active' );
                    $subscription->save();
                    $subscriptions++;
                    
                    // Add a note
                    $subscription->add_order_note( 'Test subscription created by WCST generator.' );
                }
            }
        }
        
        return sprintf( 
            'Successfully created %d customers, %d products, and %d subscriptions. Check WooCommerce → Subscriptions to see them.',
            count( $customers ),
            count( $products ),
            $subscriptions
        );
    }
}

// Only initialize if WooCommerce and WooCommerce Subscriptions are active
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) && function_exists( 'wcs_create_subscription' ) ) {
        new WCST_Simple_Test_Generator();
    }
} );
