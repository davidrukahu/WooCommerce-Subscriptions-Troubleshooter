<?php
/**
 * Subscription Anatomy Analyzer
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Subscription_Anatomy {
    
    /**
     * Analyze subscription anatomy
     */
    public function analyze($subscription_id) {
        $collector = new WCST_Subscription_Data_Collector();
        $raw_data = $collector->get_subscription_data($subscription_id);
        
        return array(
            'summary' => $this->create_summary($raw_data),
            'billing_info' => $this->analyze_billing_info($raw_data),
            'payment_method' => $this->analyze_payment_method($raw_data),
            'related_orders' => $this->analyze_related_orders($raw_data),
            'scheduled_actions' => $this->analyze_scheduled_actions($raw_data),
            'subscription_notes' => $this->analyze_subscription_notes($raw_data),
            'meta_analysis' => $this->analyze_meta_data($raw_data)
        );
    }
    
    /**
     * Create summary panel
     */
    private function create_summary($data) {
        $basic_info = $data['basic_info'];
        $customer_info = $data['customer_info'];
        $billing_schedule = $data['billing_schedule'];
        
        return array(
            'subscription_id' => $basic_info['id'],
            'status' => array(
                'value' => $basic_info['status'],
                'label' => $this->get_status_label($basic_info['status']),
                'class' => $this->get_status_class($basic_info['status'])
            ),
            'customer' => array(
                'name' => $customer_info['full_name'],
                'email' => $customer_info['email'],
                'id' => $customer_info['id']
            ),
            'billing_schedule' => array(
                'period' => $billing_schedule['period'],
                'interval' => $billing_schedule['interval'],
                'next_payment' => $billing_schedule['next_payment'],
                'total' => $basic_info['total'],
                'currency' => $basic_info['currency']
            ),
            'created_date' => $basic_info['date_created'],
            'last_modified' => $basic_info['date_modified']
        );
    }
    
    /**
     * Analyze billing information
     */
    private function analyze_billing_info($data) {
        $billing_schedule = $data['billing_schedule'];
        
        $next_payment = $billing_schedule['next_payment'];
        $now = current_time('timestamp');
        $next_payment_timestamp = $next_payment ? strtotime($next_payment) : 0;
        
        $days_until_next = $next_payment_timestamp ? ceil(($next_payment_timestamp - $now) / DAY_IN_SECONDS) : 0;
        
        return array(
            'schedule' => array(
                'period' => $billing_schedule['period'],
                'interval' => $billing_schedule['interval'],
                'start_date' => $billing_schedule['start_date'],
                'next_payment' => $next_payment,
                'end_date' => $billing_schedule['end_date'],
                'trial_end' => $billing_schedule['trial_end']
            ),
            'timing' => array(
                'days_until_next_payment' => $days_until_next,
                'is_overdue' => $days_until_next < 0,
                'is_due_soon' => $days_until_next >= 0 && $days_until_next <= 7,
                'last_payment' => $billing_schedule['last_payment']
            ),
            'trial_info' => array(
                'has_trial' => !empty($billing_schedule['trial_end']),
                'trial_end' => $billing_schedule['trial_end'],
                'is_in_trial' => $this->is_in_trial($billing_schedule)
            )
        );
    }
    
    /**
     * Analyze payment method
     */
    private function analyze_payment_method($data) {
        $payment_method = $data['payment_method'];
        
        $is_expired = false;
        $days_until_expiry = 0;
        
        if (!empty($payment_method['expiry'])) {
            $expiry_timestamp = strtotime($payment_method['expiry']);
            $now = current_time('timestamp');
            $days_until_expiry = ceil(($expiry_timestamp - $now) / DAY_IN_SECONDS);
            $is_expired = $days_until_expiry < 0;
        }
        
        return array(
            'gateway' => array(
                'id' => $payment_method['gateway_id'],
                'title' => $payment_method['title'],
                'is_available' => $this->is_gateway_available($payment_method['gateway_id'])
            ),
            'token' => array(
                'id' => $payment_method['token_id'],
                'last_4' => $payment_method['last_4'],
                'is_tokenized' => $payment_method['is_tokenized']
            ),
            'expiry' => array(
                'date' => $payment_method['expiry'],
                'is_expired' => $is_expired,
                'days_until_expiry' => $days_until_expiry,
                'is_expiring_soon' => $days_until_expiry >= 0 && $days_until_expiry <= 30
            ),
            'status' => array(
                'is_valid' => !$is_expired && $payment_method['is_tokenized'],
                'warnings' => $this->get_payment_warnings($payment_method, $is_expired, $days_until_expiry)
            )
        );
    }
    
    /**
     * Analyze related orders
     */
    private function analyze_related_orders($data) {
        $related_orders = $data['related_orders'];
        
        $all_orders = array();
        $order_statuses = array();
        
        // Get parent order
        if ($related_orders['parent_order_id']) {
            $parent_order = wc_get_order($related_orders['parent_order_id']);
            if ($parent_order) {
                $all_orders[] = array(
                    'id' => $parent_order->get_id(),
                    'type' => 'parent',
                    'status' => $parent_order->get_status(),
                    'total' => $parent_order->get_total(),
                    'date' => $parent_order->get_date_created()
                );
                $order_statuses[] = $parent_order->get_status();
            }
        }
        
        // Get renewal orders
        foreach ($related_orders['renewal_orders'] as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $all_orders[] = array(
                    'id' => $order->get_id(),
                    'type' => 'renewal',
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'date' => $order->get_date_created()
                );
                $order_statuses[] = $order->get_status();
            }
        }
        
        return array(
            'orders' => $all_orders,
            'counts' => array(
                'total' => count($all_orders),
                'parent' => $related_orders['parent_order_id'] ? 1 : 0,
                'renewals' => count($related_orders['renewal_orders']),
                'resubscribes' => count($related_orders['resubscribe_orders']),
                'switches' => count($related_orders['switch_orders'])
            ),
            'status_summary' => array_count_values($order_statuses),
            'latest_order' => !empty($all_orders) ? end($all_orders) : null
        );
    }
    
    /**
     * Analyze scheduled actions
     */
    private function analyze_scheduled_actions($data) {
        $scheduled_actions = $data['scheduled_actions'];
        
        $total_actions = 0;
        $action_types = array();
        
        foreach ($scheduled_actions as $status => $actions) {
            $total_actions += count($actions);
            foreach ($actions as $action) {
                $hook = $action['hook'];
                if (!isset($action_types[$hook])) {
                    $action_types[$hook] = 0;
                }
                $action_types[$hook]++;
            }
        }
        
        return array(
            'summary' => array(
                'total' => $total_actions,
                'pending' => count($scheduled_actions['pending']),
                'completed' => count($scheduled_actions['completed']),
                'failed' => count($scheduled_actions['failed']),
                'cancelled' => count($scheduled_actions['cancelled'])
            ),
            'action_types' => $action_types,
            'next_action' => $this->get_next_action($scheduled_actions['pending']),
            'recent_failures' => array_slice($scheduled_actions['failed'], 0, 5),
            'all_actions' => $scheduled_actions
        );
    }
    
    /**
     * Analyze subscription notes
     */
    private function analyze_subscription_notes($data) {
        $notes = $data['subscription_notes'];
        
        $note_types = array();
        $recent_notes = array_slice($notes, 0, 10);
        
        foreach ($notes as $note) {
            $type = $note['note_type'];
            if (!isset($note_types[$type])) {
                $note_types[$type] = 0;
            }
            $note_types[$type]++;
        }
        
        return array(
            'total_notes' => count($notes),
            'note_types' => $note_types,
            'recent_notes' => $recent_notes,
            'customer_notes' => array_filter($notes, function($note) {
                return $note['customer_note'];
            })
        );
    }
    
    /**
     * Analyze meta data
     */
    private function analyze_meta_data($data) {
        $meta_data = $data['meta_data'];
        
        $important_meta = array();
        $warnings = array();
        
        // Check for important meta fields
        $important_keys = array(
            '_schedule_start',
            '_schedule_next_payment',
            '_requires_manual_renewal',
            '_payment_retry_count',
            '_stripe_customer_id',
            '_paypal_subscription_id'
        );
        
        foreach ($important_keys as $key) {
            if (isset($meta_data[$key])) {
                $important_meta[$key] = $meta_data[$key];
            }
        }
        
        // Check for potential issues
        if (isset($meta_data['_payment_retry_count']) && $meta_data['_payment_retry_count'] > 3) {
            $warnings[] = __('High payment retry count detected.', 'wc-subscriptions-troubleshooter');
        }
        
        if (isset($meta_data['_requires_manual_renewal']) && $meta_data['_requires_manual_renewal']) {
            $warnings[] = __('Subscription requires manual renewal.', 'wc-subscriptions-troubleshooter');
        }
        
        return array(
            'important_fields' => $important_meta,
            'warnings' => $warnings,
            'total_meta_fields' => count($meta_data)
        );
    }
    
    /**
     * Helper methods
     */
    private function get_status_label($status) {
        $status_labels = array(
            'active' => __('Active', 'wc-subscriptions-troubleshooter'),
            'pending' => __('Pending', 'wc-subscriptions-troubleshooter'),
            'on-hold' => __('On Hold', 'wc-subscriptions-troubleshooter'),
            'cancelled' => __('Cancelled', 'wc-subscriptions-troubleshooter'),
            'expired' => __('Expired', 'wc-subscriptions-troubleshooter'),
            'pending-cancel' => __('Pending Cancel', 'wc-subscriptions-troubleshooter')
        );
        
        return isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
    }
    
    private function get_status_class($status) {
        $status_classes = array(
            'active' => 'success',
            'pending' => 'warning',
            'on-hold' => 'warning',
            'cancelled' => 'error',
            'expired' => 'error',
            'pending-cancel' => 'warning'
        );
        
        return isset($status_classes[$status]) ? $status_classes[$status] : 'default';
    }
    
    private function is_in_trial($billing_schedule) {
        if (empty($billing_schedule['trial_end'])) {
            return false;
        }
        
        $trial_end = strtotime($billing_schedule['trial_end']);
        $now = current_time('timestamp');
        
        return $now < $trial_end;
    }
    
    private function is_gateway_available($gateway_id) {
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        return isset($available_gateways[$gateway_id]);
    }
    
    private function get_payment_warnings($payment_method, $is_expired, $days_until_expiry) {
        $warnings = array();
        
        if ($is_expired) {
            $warnings[] = __('Payment method has expired.', 'wc-subscriptions-troubleshooter');
        } elseif ($days_until_expiry >= 0 && $days_until_expiry <= 30) {
            $warnings[] = sprintf(__('Payment method expires in %d days.', 'wc-subscriptions-troubleshooter'), $days_until_expiry);
        }
        
        if (!$payment_method['is_tokenized']) {
            $warnings[] = __('Payment method is not tokenized.', 'wc-subscriptions-troubleshooter');
        }
        
        return $warnings;
    }
    
    private function get_next_action($pending_actions) {
        if (empty($pending_actions)) {
            return null;
        }
        
        // Sort by scheduled date
        usort($pending_actions, function($a, $b) {
            return strtotime($a['scheduled_date']) - strtotime($b['scheduled_date']);
        });
        
        return $pending_actions[0];
    }
} 