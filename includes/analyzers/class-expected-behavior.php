<?php
/**
 * Expected Behavior Analyzer
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Expected_Behavior {
    
    /**
     * Analyze expected behavior
     */
    public function analyze($subscription_id) {
        $subscription = wcs_get_subscription($subscription_id);
        
        if (!$subscription) {
            throw new Exception(__('Subscription not found.', 'wc-subscriptions-troubleshooter'));
        }
        
        return array(
            'product_config' => $this->analyze_product_configuration($subscription),
            'gateway_behavior' => $this->analyze_gateway_behavior($subscription),
            'active_plugins_impact' => $this->analyze_active_plugins(),
            'expected_events' => $this->calculate_expected_events($subscription)
        );
    }
    
    /**
     * Analyze product configuration
     */
    private function analyze_product_configuration($subscription) {
        $items = $subscription->get_items();
        $product_config = array();
        
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product && $product->is_type('subscription')) {
                $product_config = array(
                    'subscription_price' => $product->get_price(),
                    'billing_period' => $product->get_meta('_subscription_period'),
                    'billing_interval' => $product->get_meta('_subscription_period_interval'),
                    'trial_length' => $product->get_meta('_subscription_trial_length'),
                    'trial_period' => $product->get_meta('_subscription_trial_period'),
                    'sign_up_fee' => $product->get_meta('_subscription_sign_up_fee'),
                    'free_trial' => $product->get_meta('_subscription_trial_length') > 0,
                    'product_id' => $product->get_id(),
                    'product_name' => $product->get_name()
                );
                break; // Assuming single subscription product
            }
        }
        
        return $product_config;
    }
    
    /**
     * Analyze gateway behavior
     */
    private function analyze_gateway_behavior($subscription) {
        $payment_method = $subscription->get_payment_method();
        $gateway = WC()->payment_gateways()->payment_gateways();
        
        $gateway_behavior = array(
            'supports_subscriptions' => false,
            'supports_subscription_cancellation' => false,
            'supports_subscription_suspension' => false,
            'supports_subscription_reactivation' => false,
            'supports_subscription_amount_changes' => false,
            'supports_subscription_date_changes' => false,
            'requires_manual_renewal' => false,
            'webhook_configured' => false,
            'gateway_name' => '',
            'gateway_description' => ''
        );
        
        if (isset($gateway[$payment_method])) {
            $gateway_instance = $gateway[$payment_method];
            $gateway_behavior['gateway_name'] = $gateway_instance->get_title();
            $gateway_behavior['gateway_description'] = $gateway_instance->get_description();
            
            // Check gateway capabilities
            $gateway_behavior['supports_subscriptions'] = $gateway_instance->supports('subscriptions');
            $gateway_behavior['supports_subscription_cancellation'] = $gateway_instance->supports('subscription_cancellation');
            $gateway_behavior['supports_subscription_suspension'] = $gateway_instance->supports('subscription_suspension');
            $gateway_behavior['supports_subscription_reactivation'] = $gateway_instance->supports('subscription_reactivation');
            $gateway_behavior['supports_subscription_amount_changes'] = $gateway_instance->supports('subscription_amount_changes');
            $gateway_behavior['supports_subscription_date_changes'] = $gateway_instance->supports('subscription_date_changes');
            
            // Check for manual renewal requirement
            $gateway_behavior['requires_manual_renewal'] = $this->check_manual_renewal_requirement($payment_method);
            
            // Check webhook configuration
            $gateway_behavior['webhook_configured'] = $this->check_webhook_configuration($payment_method);
        }
        
        return $gateway_behavior;
    }
    
    /**
     * Analyze active plugins impact
     */
    private function analyze_active_plugins() {
        $active_plugins = get_option('active_plugins');
        $plugin_impact = array(
            'membership_plugins' => array(),
            'payment_retry_plugins' => array(),
            'email_plugins' => array(),
            'automation_plugins' => array(),
            'conflict_plugins' => array()
        );
        
        foreach ($active_plugins as $plugin) {
            $plugin_name = basename($plugin, '.php');
            
            // Membership plugins
            if (strpos($plugin, 'woocommerce-memberships') !== false) {
                $plugin_impact['membership_plugins'][] = array(
                    'name' => 'WooCommerce Memberships',
                    'impact' => 'May affect subscription access and billing'
                );
            }
            
            // Payment retry plugins
            if (strpos($plugin, 'woocommerce-subscription-payment-retry') !== false) {
                $plugin_impact['payment_retry_plugins'][] = array(
                    'name' => 'WooCommerce Subscription Payment Retry',
                    'impact' => 'Adds automatic payment retry functionality'
                );
            }
            
            // Email plugins
            if (strpos($plugin, 'woocommerce-email-customizer') !== false || 
                strpos($plugin, 'woocommerce-advanced-notifications') !== false) {
                $plugin_impact['email_plugins'][] = array(
                    'name' => 'Email Customization Plugin',
                    'impact' => 'May affect subscription notification emails'
                );
            }
            
            // Automation plugins
            if (strpos($plugin, 'woocommerce-automation') !== false || 
                strpos($plugin, 'woocommerce-workflows') !== false) {
                $plugin_impact['automation_plugins'][] = array(
                    'name' => 'Automation Plugin',
                    'impact' => 'May add custom subscription workflows'
                );
            }
            
            // Potential conflict plugins
            if (strpos($plugin, 'woocommerce-subscription') !== false && 
                strpos($plugin, 'woocommerce-subscriptions') === false) {
                $plugin_impact['conflict_plugins'][] = array(
                    'name' => $plugin_name,
                    'impact' => 'Potential subscription plugin conflict'
                );
            }
        }
        
        return $plugin_impact;
    }
    
    /**
     * Calculate expected events
     */
    private function calculate_expected_events($subscription) {
        $expected_events = array(
            'renewal_dates' => array(),
            'payment_retry_schedule' => array(),
            'email_notifications' => array(),
            'status_transitions' => array()
        );
        
        // Calculate expected renewal dates
        $expected_events['renewal_dates'] = $this->calculate_renewal_dates($subscription);
        
        // Calculate payment retry schedule
        $expected_events['payment_retry_schedule'] = $this->calculate_payment_retry_schedule($subscription);
        
        // Calculate expected email notifications
        $expected_events['email_notifications'] = $this->calculate_email_notifications($subscription);
        
        // Calculate expected status transitions
        $expected_events['status_transitions'] = $this->calculate_status_transitions($subscription);
        
        return $expected_events;
    }
    
    /**
     * Calculate expected renewal dates
     */
    private function calculate_renewal_dates($subscription) {
        $renewal_dates = array();
        $start_date = $subscription->get_date('start');
        $next_payment = $subscription->get_date('next_payment');
        $end_date = $subscription->get_date('end');
        
        if ($start_date && $next_payment) {
            $current_date = $start_date;
            $interval = $subscription->get_billing_interval();
            $period = $subscription->get_billing_period();
            
            // Calculate next 12 renewal dates
            for ($i = 0; $i < 12; $i++) {
                $renewal_date = $this->add_time_to_date($current_date, $interval, $period);
                
                if ($end_date && $renewal_date > $end_date) {
                    break;
                }
                
                $renewal_dates[] = array(
                    'date' => $renewal_date,
                    'number' => $i + 1,
                    'is_past' => strtotime($renewal_date) < current_time('timestamp')
                );
                
                $current_date = $renewal_date;
            }
        }
        
        return $renewal_dates;
    }
    
    /**
     * Calculate payment retry schedule
     */
    private function calculate_payment_retry_schedule($subscription) {
        $retry_schedule = array();
        
        // Check if payment retry is enabled
        $retry_enabled = get_option('woocommerce_subscription_payment_retry_enabled', 'no');
        
        if ($retry_enabled === 'yes') {
            $retry_rules = get_option('woocommerce_subscription_payment_retry_rules', array());
            
            foreach ($retry_rules as $rule) {
                $retry_schedule[] = array(
                    'attempt' => $rule['retry_number'],
                    'delay' => $rule['retry_delay'],
                    'delay_unit' => $rule['retry_delay_unit']
                );
            }
        }
        
        return $retry_schedule;
    }
    
    /**
     * Calculate expected email notifications
     */
    private function calculate_email_notifications($subscription) {
        $notifications = array();
        
        // Get email settings
        $email_settings = get_option('woocommerce_email_settings', array());
        
        // Subscription renewal emails
        if (isset($email_settings['woocommerce_subscription_renewal_reminder_enabled']) && 
            $email_settings['woocommerce_subscription_renewal_reminder_enabled'] === 'yes') {
            $notifications[] = array(
                'type' => 'renewal_reminder',
                'timing' => $email_settings['woocommerce_subscription_renewal_reminder_days'] ?? 7,
                'enabled' => true
            );
        }
        
        // Payment failed emails
        if (isset($email_settings['woocommerce_subscription_payment_failed_enabled']) && 
            $email_settings['woocommerce_subscription_payment_failed_enabled'] === 'yes') {
            $notifications[] = array(
                'type' => 'payment_failed',
                'timing' => 'immediate',
                'enabled' => true
            );
        }
        
        // Subscription cancelled emails
        if (isset($email_settings['woocommerce_subscription_cancelled_enabled']) && 
            $email_settings['woocommerce_subscription_cancelled_enabled'] === 'yes') {
            $notifications[] = array(
                'type' => 'subscription_cancelled',
                'timing' => 'immediate',
                'enabled' => true
            );
        }
        
        return $notifications;
    }
    
    /**
     * Calculate expected status transitions
     */
    private function calculate_status_transitions($subscription) {
        $transitions = array();
        $current_status = $subscription->get_status();
        
        // Define expected transitions based on current status
        switch ($current_status) {
            case 'active':
                $transitions[] = array(
                    'from' => 'active',
                    'to' => 'on-hold',
                    'trigger' => 'payment_failed',
                    'expected' => true
                );
                $transitions[] = array(
                    'from' => 'active',
                    'to' => 'cancelled',
                    'trigger' => 'customer_cancellation',
                    'expected' => true
                );
                $transitions[] = array(
                    'from' => 'active',
                    'to' => 'expired',
                    'trigger' => 'subscription_end_date_reached',
                    'expected' => true
                );
                break;
                
            case 'on-hold':
                $transitions[] = array(
                    'from' => 'on-hold',
                    'to' => 'active',
                    'trigger' => 'payment_completed',
                    'expected' => true
                );
                $transitions[] = array(
                    'from' => 'on-hold',
                    'to' => 'cancelled',
                    'trigger' => 'customer_cancellation',
                    'expected' => true
                );
                break;
                
            case 'pending':
                $transitions[] = array(
                    'from' => 'pending',
                    'to' => 'active',
                    'trigger' => 'payment_completed',
                    'expected' => true
                );
                $transitions[] = array(
                    'from' => 'pending',
                    'to' => 'cancelled',
                    'trigger' => 'payment_failed',
                    'expected' => true
                );
                break;
        }
        
        return $transitions;
    }
    
    /**
     * Helper methods
     */
    private function check_manual_renewal_requirement($gateway_id) {
        // Check if gateway requires manual renewal
        $manual_renewal_gateways = array(
            'cheque',
            'bacs',
            'cod'
        );
        
        return in_array($gateway_id, $manual_renewal_gateways);
    }
    
    private function check_webhook_configuration($gateway_id) {
        // Check if webhook is configured for the gateway
        $webhook_urls = get_option('woocommerce_' . $gateway_id . '_webhook_urls', array());
        
        return !empty($webhook_urls);
    }
    
    private function add_time_to_date($date, $interval, $period) {
        $timestamp = strtotime($date);
        
        switch ($period) {
            case 'day':
                return date('Y-m-d H:i:s', strtotime('+' . $interval . ' days', $timestamp));
            case 'week':
                return date('Y-m-d H:i:s', strtotime('+' . $interval . ' weeks', $timestamp));
            case 'month':
                return date('Y-m-d H:i:s', strtotime('+' . $interval . ' months', $timestamp));
            case 'year':
                return date('Y-m-d H:i:s', strtotime('+' . $interval . ' years', $timestamp));
            default:
                return $date;
        }
    }
} 