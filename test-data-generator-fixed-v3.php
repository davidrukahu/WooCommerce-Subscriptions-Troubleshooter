<?php
/**
 * WooCommerce Subscriptions Test Data Generator
 * 
 * This script creates realistic subscription test data including:
 * - Various subscription statuses
 * - Different payment methods (including Stripe test mode)
 * - Failed payments and renewals
 * - Subscription switches
 * - Timeline gaps and issues
 * 
 * Usage: Upload to your test site and run via WordPress admin or WP-CLI
 * 
 * IMPORTANT: Only use on test/staging sites - never on production!
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCST_Test_Data_Generator {
	
	/**
	 * Initialize the test data generator.
	 */
	public function __construct() {
		// Safety check - prevent running on production
		if ( $this->is_production_site() ) {
			wp_die( 'This script cannot be run on production sites for safety reasons.' );
		}
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_ajax_wcst_generate_test_data', array( $this, 'generate_test_data' ) );
	}
	
	/**
	 * Check if this is a production site.
	 */
	private function is_production_site() {
		$url = home_url();
		$production_indicators = array(
			'.com',
			'.org',
			'.net',
			'.co.uk',
			'.ca',
			// Add your production domains here
		);
		
		// Allow if it's clearly a test environment
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
	
	/**
	 * Add admin menu for the generator.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			'Subscription Test Data Generator',
			'Test Data Generator',
			'manage_woocommerce',
			'wcst-test-data',
			array( $this, 'admin_page' )
		);
	}
	
	/**
	 * Render admin page.
	 */
	public function admin_page() {
		?>
		<div class="wrap">
			<h1>WooCommerce Subscriptions Test Data Generator</h1>
			
			<div class="notice notice-warning">
				<p><strong>Warning:</strong> This will create test data in your database. Only use on test/staging sites!</p>
			</div>
			
			<div class="card">
				<h2>What This Creates</h2>
				<ul>
					<li>✅ <strong>5 Test Customers</strong> with different profiles</li>
					<li>✅ <strong>3 Subscription Products</strong> (monthly, yearly, with trial)</li>
					<li>✅ <strong>15-20 Test Subscriptions</strong> with various statuses</li>
					<li>✅ <strong>Realistic Timeline Issues</strong>:
						<ul>
							<li>Failed payment attempts</li>
							<li>Expired payment methods</li>
							<li>Missed renewal actions</li>
							<li>Status transition problems</li>
							<li>Subscription switches</li>
						</ul>
					</li>
					<li>✅ <strong>Stripe Test Mode Integration</strong></li>
				</ul>
			</div>
			
			<div class="card">
				<h2>Generate Test Data</h2>
				<p>Click the button below to generate comprehensive test data for subscription troubleshooting.</p>
				
				<button type="button" id="generate-test-data" class="button button-primary button-large">
					Generate Test Subscription Data
				</button>
				
				<div id="generation-progress" style="display: none; margin-top: 20px;">
					<p>Generating test data... <span id="progress-text"></span></p>
					<div style="background: #f1f1f1; height: 20px; border-radius: 10px; overflow: hidden;">
						<div id="progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;"></div>
					</div>
				</div>
				
				<div id="generation-results" style="display: none; margin-top: 20px;"></div>
			</div>
			
			<div class="card">
				<h2>After Generation</h2>
				<p>Once the test data is created, you can:</p>
				<ol>
					<li>Go to <strong>WooCommerce > Subscriptions</strong> to see the created subscriptions</li>
					<li>Test the <strong>Subscriptions Troubleshooter</strong> with various subscription IDs</li>
					<li>Search by customer emails: customer1@test.com, customer2@test.com, etc.</li>
					<li>Look for subscriptions with different statuses and issues</li>
				</ol>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			$('#generate-test-data').on('click', function() {
				const button = $(this);
				const progress = $('#generation-progress');
				const results = $('#generation-results');
				const progressBar = $('#progress-bar');
				const progressText = $('#progress-text');
				
				button.prop('disabled', true).text('Generating...');
				progress.show();
				results.hide();
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wcst_generate_test_data',
						nonce: '<?php echo wp_create_nonce( 'wcst_generate_test_data' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							progressBar.css('width', '100%');
							progressText.text('Complete!');
							results.html('<div class="notice notice-success"><p><strong>Success!</strong> ' + response.data.message + '</p></div>').show();
						} else {
							results.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + response.data + '</p></div>').show();
						}
					},
					error: function() {
						results.html('<div class="notice notice-error"><p><strong>Error:</strong> Failed to generate test data.</p></div>').show();
					},
					complete: function() {
						button.prop('disabled', false).text('Generate Test Subscription Data');
						setTimeout(() => progress.hide(), 2000);
					}
				});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Generate test data via AJAX.
	 */
	public function generate_test_data() {
		// Security check
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wcst_generate_test_data' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		
		try {
			$results = $this->create_test_data();
			wp_send_json_success( $results );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}
	
	/**
	 * Create all test data.
	 */
	private function create_test_data() {
		$results = array(
			'customers' => 0,
			'products' => 0,
			'subscriptions' => 0,
			'issues_created' => 0,
		);
		
		try {
			// 1. Create test customers
			$customers = $this->create_test_customers();
			$results['customers'] = count( $customers );
			
			if ( empty( $customers ) ) {
				throw new Exception( 'Failed to create test customers.' );
			}
			
			// 2. Create subscription products
			$products = $this->create_subscription_products();
			$results['products'] = count( $products );
			
			if ( empty( $products ) ) {
				throw new Exception( 'Failed to create subscription products.' );
			}
			
			// 3. Create subscriptions with various scenarios
			$subscriptions = $this->create_test_subscriptions( $customers, $products );
			$results['subscriptions'] = count( $subscriptions );
			
			if ( empty( $subscriptions ) ) {
				throw new Exception( 'Failed to create test subscriptions.' );
			}
			
			// 4. Create realistic issues
			$issues = $this->create_subscription_issues( $subscriptions );
			$results['issues_created'] = $issues;
			
			$results['message'] = sprintf(
				'Created %d customers, %d products, %d subscriptions with %d simulated issues.',
				$results['customers'],
				$results['products'],
				$results['subscriptions'],
				$results['issues_created']
			);
			
		} catch ( Exception $e ) {
			// Log the error and re-throw with more context
			error_log( 'WCST Test Data Generator Error: ' . $e->getMessage() );
			throw new Exception( 'Test data generation failed: ' . $e->getMessage() );
		}
		
		return $results;
	}
	
	/**
	 * Create test customers.
	 */
	private function create_test_customers() {
		$customers = array();
		
		$customer_data = array(
			array(
				'email' => 'customer1@test.com',
				'first_name' => 'John',
				'last_name' => 'Smith',
				'role' => 'customer',
			),
			array(
				'email' => 'customer2@test.com',
				'first_name' => 'Sarah',
				'last_name' => 'Johnson',
				'role' => 'customer',
			),
			array(
				'email' => 'customer3@test.com',
				'first_name' => 'Mike',
				'last_name' => 'Williams',
				'role' => 'customer',
			),
			array(
				'email' => 'customer4@test.com',
				'first_name' => 'Emma',
				'last_name' => 'Brown',
				'role' => 'customer',
			),
			array(
				'email' => 'customer5@test.com',
				'first_name' => 'David',
				'last_name' => 'Davis',
				'role' => 'customer',
			),
		);
		
		foreach ( $customer_data as $data ) {
			// Check if customer already exists
			if ( email_exists( $data['email'] ) ) {
				$customers[] = get_user_by( 'email', $data['email'] )->ID;
				continue;
			}
			
			$customer_id = wp_create_user( $data['email'], 'test123', $data['email'] );
			
			if ( ! is_wp_error( $customer_id ) ) {
				wp_update_user( array(
					'ID' => $customer_id,
					'first_name' => $data['first_name'],
					'last_name' => $data['last_name'],
					'role' => $data['role'],
				) );
				
				$customers[] = $customer_id;
			}
		}
		
		return $customers;
	}
	
	/**
	 * Create subscription products.
	 */
	private function create_subscription_products() {
		$products = array();
		
		$product_data = array(
			array(
				'name' => 'Monthly Subscription',
				'price' => '29.99',
				'period' => 'month',
				'interval' => 1,
				'trial_length' => 0,
				'sign_up_fee' => '0',
			),
			array(
				'name' => 'Annual Subscription',
				'price' => '299.99',
				'period' => 'year',
				'interval' => 1,
				'trial_length' => 0,
				'sign_up_fee' => '0',
			),
			array(
				'name' => 'Premium Plan with Trial',
				'price' => '49.99',
				'period' => 'month',
				'interval' => 1,
				'trial_length' => 14,
				'sign_up_fee' => '9.99',
			),
		);
		
		foreach ( $product_data as $data ) {
			$product = new WC_Product_Subscription();
			$product->set_name( $data['name'] );
			$product->set_regular_price( $data['price'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			
			// Set subscription metadata using the correct meta keys
			$product->update_meta_data( '_subscription_price', $data['price'] );
			$product->update_meta_data( '_subscription_period', $data['period'] );
			$product->update_meta_data( '_subscription_period_interval', $data['interval'] );
			
			if ( $data['trial_length'] > 0 ) {
				$product->update_meta_data( '_subscription_trial_length', $data['trial_length'] );
				$product->update_meta_data( '_subscription_trial_period', 'day' );
			}
			
			if ( $data['sign_up_fee'] > 0 ) {
				$product->update_meta_data( '_subscription_sign_up_fee', $data['sign_up_fee'] );
			}
			
			// Set subscription length (0 = never expires)
			$product->update_meta_data( '_subscription_length', 0 );
			
			$product_id = $product->save();
			if ( $product_id ) {
				$products[] = $product_id;
			}
		}
		
		return $products;
	}
	
	/**
	 * Create test subscriptions with various scenarios.
	 */
	private function create_test_subscriptions( $customers, $products ) {
		$subscriptions = array();
		
		$scenarios = array(
			// Healthy subscriptions
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array() ),
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array() ),
			
			// Payment issues
			array( 'status' => 'on-hold', 'payment_method' => 'stripe', 'issues' => array( 'failed_payment', 'expired_card' ) ),
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array( 'payment_retry' ) ),
			
			// Status issues
			array( 'status' => 'pending-cancel', 'payment_method' => 'stripe', 'issues' => array() ),
			array( 'status' => 'cancelled', 'payment_method' => 'stripe', 'issues' => array( 'early_cancellation' ) ),
			
			// Manual renewals
			array( 'status' => 'active', 'payment_method' => 'manual', 'issues' => array() ),
			array( 'status' => 'on-hold', 'payment_method' => 'manual', 'issues' => array( 'overdue_payment' ) ),
			
			// Action scheduler issues
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array( 'failed_action' ) ),
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array( 'missing_action' ) ),
			
			// Switching scenarios
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array( 'recent_switch' ) ),
			
			// Timeline gaps
			array( 'status' => 'active', 'payment_method' => 'stripe', 'issues' => array( 'timeline_gap' ) ),
			
			// Mixed issues
			array( 'status' => 'on-hold', 'payment_method' => 'stripe', 'issues' => array( 'failed_payment', 'failed_action' ) ),
		);
		
		foreach ( $scenarios as $index => $scenario ) {
			$customer_id = $customers[ $index % count( $customers ) ];
			$product_id = $products[ $index % count( $products ) ];
			
			$subscription = $this->create_single_subscription( $customer_id, $product_id, $scenario );
			
			if ( $subscription ) {
				$subscriptions[] = $subscription;
			}
		}
		
		return $subscriptions;
	}
	
	/**
	 * Create a single subscription.
	 */
	private function create_single_subscription( $customer_id, $product_id, $scenario ) {
		$product = wc_get_product( $product_id );
		
		// Create parent order first
		$order = wc_create_order( array( 'customer_id' => $customer_id ) );
		$order->add_product( $product, 1 );
		$order->set_payment_method( $scenario['payment_method'] );
		$order->set_payment_method_title( ucfirst( $scenario['payment_method'] ) );
		$order->calculate_totals();
		
		// Add Stripe test payment method details
		if ( 'stripe' === $scenario['payment_method'] ) {
			$order->update_meta_data( '_stripe_customer_id', 'cus_test_' . wp_rand( 100000, 999999 ) );
			$order->update_meta_data( '_stripe_source_id', 'card_test_' . wp_rand( 100000, 999999 ) );
		}
		
		$order->set_status( 'completed' );
		$order->save();
		
		// Create subscription
		$subscription = wcs_create_subscription( array(
			'order_id' => $order->get_id(),
			'billing_period' => $product->get_meta( '_subscription_period' ) ?: 'month',
			'billing_interval' => $product->get_meta( '_subscription_period_interval' ) ?: 1,
		) );
		
		if ( is_wp_error( $subscription ) ) {
			return false;
		}
		
		$subscription->add_product( $product, 1 );
		$subscription->set_payment_method( $scenario['payment_method'] );
		$subscription->set_payment_method_title( ucfirst( $scenario['payment_method'] ) );
		
		// Set subscription dates using the correct 7.7.0+ API
		$start_date = current_time( 'timestamp' ) - wp_rand( 30, 180 ) * DAY_IN_SECONDS;
		
		// Prepare dates array for update_dates method
		$dates_to_update = array(
			'start' => date( 'Y-m-d H:i:s', $start_date ),
		);
		
		// Set trial end if applicable
		$trial_length = $product->get_meta( '_subscription_trial_length' );
		if ( $trial_length > 0 ) {
			$trial_end = $start_date + ( intval( $trial_length ) * DAY_IN_SECONDS );
			$dates_to_update['trial_end'] = date( 'Y-m-d H:i:s', $trial_end );
		}
		
		// Set next payment date
		if ( 'cancelled' !== $scenario['status'] ) {
			$next_payment = $this->calculate_next_payment_date( $subscription, $start_date );
			$dates_to_update['next_payment'] = date( 'Y-m-d H:i:s', $next_payment );
		}
		
		// Update all dates at once using the correct API
		$subscription->update_dates( $dates_to_update );
		
		$subscription->calculate_totals();
		$subscription->set_status( $scenario['status'] );
		$subscription->save();
		
		// Add payment method metadata for Stripe
		if ( 'stripe' === $scenario['payment_method'] ) {
			$subscription->update_meta_data( '_stripe_customer_id', $order->get_meta( '_stripe_customer_id' ) );
			$subscription->update_meta_data( '_stripe_source_id', $order->get_meta( '_stripe_source_id' ) );
			$subscription->save();
		}
		
		return $subscription->get_id();
	}
	
	/**
	 * Calculate next payment date based on subscription settings.
	 */
	private function calculate_next_payment_date( $subscription, $start_timestamp = null ) {
		if ( null === $start_timestamp ) {
			$start_date = $subscription->get_date( 'start' );
			$start_timestamp = $start_date ? $start_date->getTimestamp() : current_time( 'timestamp' );
		}
		
		$trial_end = $subscription->get_date( 'trial_end' );
		$trial_timestamp = $trial_end ? $trial_end->getTimestamp() : null;
		
		// Get period and interval from product metadata
		$product = $subscription->get_items();
		$product = reset( $product );
		if ( $product ) {
			$product_obj = $product->get_product();
			$period = $product_obj->get_meta( '_subscription_period' );
			$interval = $product_obj->get_meta( '_subscription_period_interval' );
		}
		
		// Fallback values
		$period = $period ?: 'month';
		$interval = $interval ?: 1;
		
		$base_date = $trial_timestamp ?: $start_timestamp;
		
		switch ( $period ) {
			case 'day':
				return $base_date + ( $interval * DAY_IN_SECONDS );
			case 'week':
				return $base_date + ( $interval * WEEK_IN_SECONDS );
			case 'month':
				return strtotime( "+{$interval} months", $base_date );
			case 'year':
				return strtotime( "+{$interval} years", $base_date );
			default:
				return strtotime( '+1 month', $base_date );
		}
	}
	
	/**
	 * Create realistic subscription issues.
	 */
	private function create_subscription_issues( $subscription_ids ) {
		$issues_created = 0;
		
		foreach ( $subscription_ids as $subscription_id ) {
			$subscription = wcs_get_subscription( $subscription_id );
			if ( ! $subscription ) {
				continue;
			}
			
			// Randomly apply issues based on subscription scenario
			$rand = wp_rand( 1, 10 );
			
			if ( $rand <= 3 ) {
				$this->create_failed_payment_scenario( $subscription );
				$issues_created++;
			} elseif ( $rand <= 5 ) {
				$this->create_action_scheduler_issue( $subscription );
				$issues_created++;
			} elseif ( $rand <= 7 ) {
				$this->create_timeline_gap( $subscription );
				$issues_created++;
			} elseif ( $rand <= 8 ) {
				$this->create_payment_method_issue( $subscription );
				$issues_created++;
			}
		}
		
		return $issues_created;
	}
	
	/**
	 * Create failed payment scenario.
	 */
	private function create_failed_payment_scenario( $subscription ) {
		// Add failed payment note
		$subscription->add_order_note(
			'Payment failed. Reason: Your card was declined. (Stripe test mode)',
			0,
			false
		);
		
		// Create a failed renewal order
		$renewal_order = wcs_create_renewal_order( $subscription );
		if ( $renewal_order && ! is_wp_error( $renewal_order ) ) {
			$renewal_order->set_status( 'failed' );
			$renewal_order->add_order_note( 'Payment failed - card declined (Test mode)' );
			$renewal_order->save();
		}
		
		// Update last payment date
		$last_payment = current_time( 'timestamp' ) - wp_rand( 7, 30 ) * DAY_IN_SECONDS;
		$subscription->update_dates( array( 'last_payment' => date( 'Y-m-d H:i:s', $last_payment ) ) );
		$subscription->save();
	}
	
	/**
	 * Create Action Scheduler issue.
	 */
	private function create_action_scheduler_issue( $subscription ) {
		// Add note about failed scheduled action
		$subscription->add_order_note(
			'Scheduled renewal payment action failed to execute properly.',
			0,
			false
		);
		
		// Simulate missed renewal window
		$expected_renewal = current_time( 'timestamp' ) - wp_rand( 1, 7 ) * DAY_IN_SECONDS;
		$subscription->add_order_note(
			sprintf( 'Renewal payment was scheduled for %s but did not execute.', 
				date( 'Y-m-d H:i:s', $expected_renewal ) ),
			0,
			false
		);
	}
	
	/**
	 * Create timeline gap.
	 */
	private function create_timeline_gap( $subscription ) {
		// Add notes indicating a gap in activity
		$gap_start = current_time( 'timestamp' ) - wp_rand( 60, 120 ) * DAY_IN_SECONDS;
		$gap_end = $gap_start + wp_rand( 7, 21 ) * DAY_IN_SECONDS;
		
		$subscription->add_order_note(
			sprintf( 'Note: No activity recorded between %s and %s - possible data gap.',
				date( 'Y-m-d', $gap_start ),
				date( 'Y-m-d', $gap_end ) ),
			0,
			false
		);
	}
	
	/**
	 * Create payment method issue.
	 */
	private function create_payment_method_issue( $subscription ) {
		if ( 'stripe' === $subscription->get_payment_method() ) {
			// Simulate expired card
			$subscription->add_order_note(
				'Warning: Payment method will expire soon (12/24). Customer should update payment details.',
				0,
				false
			);
			
			// Add metadata indicating expired card
			$subscription->update_meta_data( '_stripe_card_expiry_notified', 'yes' );
			$subscription->save();
		}
	}
}

// Initialize if we're in WordPress admin or running via WP-CLI
if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	new WCST_Test_Data_Generator();
}

// WP-CLI command support
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'wcst generate-test-data', function() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce is not active.' );
		}
		
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			WP_CLI::error( 'WooCommerce Subscriptions is not active.' );
		}
		
		$generator = new WCST_Test_Data_Generator();
		
		try {
			$results = $generator->create_test_data();
			WP_CLI::success( $results['message'] );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	} );
}
