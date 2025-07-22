<?php
/**
 * Subscription Data Collector
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Subscription_Data_Collector {
    
    /**
     * Get subscription data
     */
    public function get_subscription_data($subscription_id) {
        $subscription = wcs_get_subscription($subscription_id);
        
        if (!$subscription) {
            throw new Exception(__('Subscription not found.', 'wc-subscriptions-troubleshooter'));
        }
        
        return array(
            'basic_info' => $this->get_basic_info($subscription),
            'customer_info' => $this->get_customer_info($subscription),
            'payment_method' => $this->get_payment_method($subscription),
            'billing_schedule' => $this->get_billing_schedule($subscription),
            'related_orders' => $this->get_related_orders($subscription),
            'subscription_notes' => $this->get_subscription_notes($subscription),
            'scheduled_actions' => $this->get_scheduled_actions($subscription),
            'meta_data' => $this->get_meta_data($subscription)
        );
    }
    
    /**
     * Get basic subscription information
     */
    private function get_basic_info($subscription) {
        return array(
            'id' => $subscription->get_id(),
            'status' => $subscription->get_status(),
            'date_created' => $subscription->get_date('date_created'),
            'date_modified' => $subscription->get_date('date_modified'),
            'total' => $subscription->get_total(),
            'currency' => $subscription->get_currency(),
            'product_ids' => $subscription->get_items() ? array_keys($subscription->get_items()) : array()
        );
    }
    
    /**
     * Get customer information
     */
    private function get_customer_info($subscription) {
        $customer = $subscription->get_customer();
        
        return array(
            'id' => $customer ? $customer->get_id() : 0,
            'email' => $subscription->get_billing_email(),
            'first_name' => $subscription->get_billing_first_name(),
            'last_name' => $subscription->get_billing_last_name(),
            'full_name' => $subscription->get_formatted_billing_full_name(),
            'phone' => $subscription->get_billing_phone()
        );
    }
    
    /**
     * Get payment method information
     */
    private function get_payment_method($subscription) {
        $payment_method = $subscription->get_payment_method();
        $payment_method_title = $subscription->get_payment_method_title();
        
        return array(
            'gateway_id' => $payment_method,
            'title' => $payment_method_title,
            'token_id' => $subscription->get_meta('_payment_token_id'),
            'last_4' => $this->get_last_4_digits($subscription),
            'expiry' => $this->get_expiry_date($subscription),
            'is_tokenized' => !empty($subscription->get_meta('_payment_token_id'))
        );
    }
    
    /**
     * Get billing schedule information
     */
    private function get_billing_schedule($subscription) {
        return array(
            'period' => $subscription->get_billing_period(),
            'interval' => $subscription->get_billing_interval(),
            'start_date' => $subscription->get_date('start'),
            'next_payment' => $subscription->get_date('next_payment'),
            'end_date' => $subscription->get_date('end'),
            'trial_end' => $subscription->get_date('trial_end'),
            'last_payment' => $subscription->get_date('last_payment'),
            'last_order_date_created' => $subscription->get_date('last_order_date_created')
        );
    }
    
    /**
     * Get related orders
     */
    private function get_related_orders($subscription) {
        return array(
            'parent_order_id' => $subscription->get_parent_id(),
            'renewal_orders' => $subscription->get_related_orders('ids', 'renewal'),
            'resubscribe_orders' => $subscription->get_related_orders('ids', 'resubscribe'),
            'switch_orders' => $subscription->get_related_orders('ids', 'switch'),
            'all_orders' => $subscription->get_related_orders('ids')
        );
    }
    
    /**
     * Get subscription notes
     */
    private function get_subscription_notes($subscription) {
        $notes = wc_get_order_notes(array(
            'order_id' => $subscription->get_id(),
            'order_by' => 'date_created',
            'order' => 'DESC'
        ));
        
        $formatted_notes = array();
        foreach ($notes as $note) {
            $formatted_notes[] = array(
                'id' => $note->id,
                'content' => $note->content,
                'date_created' => $note->date_created,
                'date_created_gmt' => $note->date_created_gmt,
                'note_type' => $note->note_type,
                'customer_note' => $note->customer_note
            );
        }
        
        return $formatted_notes;
    }
    
    /**
     * Get scheduled actions
     */
    private function get_scheduled_actions($subscription) {
        global $wpdb;
        
        $actions_table = $wpdb->prefix . 'actionscheduler_actions';
        $groups_table = $wpdb->prefix . 'actionscheduler_groups';
        
        $subscription_id = $subscription->get_id();
        
        // Get all actions related to this subscription
        $query = $wpdb->prepare("
            SELECT a.*, ag.slug as group_slug
            FROM {$actions_table} a
            LEFT JOIN {$groups_table} ag ON a.group_id = ag.group_id
            WHERE a.args LIKE %s
            ORDER BY a.scheduled_date ASC
        ", '%' . $wpdb->esc_like($subscription_id) . '%');
        
        $actions = $wpdb->get_results($query);
        
        $formatted_actions = array(
            'pending' => array(),
            'completed' => array(),
            'failed' => array(),
            'cancelled' => array()
        );
        
        foreach ($actions as $action) {
            $status = $action->status;
            $formatted_actions[$status][] = array(
                'id' => $action->action_id,
                'hook' => $action->hook,
                'status' => $action->status,
                'scheduled_date' => $action->scheduled_date,
                'args' => maybe_unserialize($action->args),
                'group_slug' => $action->group_slug
            );
        }
        
        return $formatted_actions;
    }
    
    /**
     * Get meta data
     */
    private function get_meta_data($subscription) {
        $meta_data = $subscription->get_meta_data();
        $formatted_meta = array();
        
        foreach ($meta_data as $meta) {
            $formatted_meta[$meta->key] = $meta->value;
        }
        
        return $formatted_meta;
    }
    
    /**
     * Get last 4 digits of payment method
     */
    private function get_last_4_digits($subscription) {
        $last_4 = $subscription->get_meta('_payment_token_last4');
        
        if (!$last_4) {
            // Try to get from parent order
            $parent_order = $subscription->get_parent();
            if ($parent_order) {
                $last_4 = $parent_order->get_meta('_payment_token_last4');
            }
        }
        
        return $last_4;
    }
    
    /**
     * Get expiry date of payment method
     */
    private function get_expiry_date($subscription) {
        $expiry = $subscription->get_meta('_payment_token_expiry');
        
        if (!$expiry) {
            // Try to get from parent order
            $parent_order = $subscription->get_parent();
            if ($parent_order) {
                $expiry = $parent_order->get_meta('_payment_token_expiry');
            }
        }
        
        return $expiry;
    }
    
    /**
     * Search subscriptions
     */
    public function search_subscriptions($search_term) {
        global $wpdb;
        
        $results = array();
        
        // Search by subscription ID
        if (is_numeric($search_term)) {
            $subscription = wcs_get_subscription($search_term);
            if ($subscription) {
                $results[] = array(
                    'id' => $subscription->get_id(),
                    'title' => sprintf(__('Subscription #%s', 'wc-subscriptions-troubleshooter'), $subscription->get_id()),
                    'status' => $subscription->get_status(),
                    'customer' => $subscription->get_formatted_billing_full_name(),
                    'email' => $subscription->get_billing_email()
                );
            }
        }
        
        // Search by customer email
        $email_results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, pm.meta_value as email
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_subscription'
            AND pm.meta_key = '_billing_email'
            AND pm.meta_value LIKE %s
            LIMIT 10
        ", '%' . $wpdb->esc_like($search_term) . '%'));
        
        foreach ($email_results as $result) {
            $subscription = wcs_get_subscription($result->ID);
            if ($subscription) {
                $results[] = array(
                    'id' => $subscription->get_id(),
                    'title' => sprintf(__('Subscription #%s', 'wc-subscriptions-troubleshooter'), $subscription->get_id()),
                    'status' => $subscription->get_status(),
                    'customer' => $subscription->get_formatted_billing_full_name(),
                    'email' => $subscription->get_billing_email()
                );
            }
        }
        
        return $results;
    }
} 