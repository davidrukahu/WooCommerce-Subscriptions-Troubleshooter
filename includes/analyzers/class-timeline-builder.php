<?php
/**
 * Timeline Builder
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Timeline_Builder {
    
    /**
     * Build timeline for subscription
     */
    public function build($subscription_id) {
        $subscription = wcs_get_subscription($subscription_id);
        
        if (!$subscription) {
            throw new Exception(__('Subscription not found.', 'wc-subscriptions-troubleshooter'));
        }
        
        $events = array();
        
        // Collect events from different sources
        $events = array_merge($events, $this->get_subscription_events($subscription));
        $events = array_merge($events, $this->get_order_events($subscription));
        $events = array_merge($events, $this->get_action_scheduler_events($subscription));
        $events = array_merge($events, $this->get_log_events($subscription));
        $events = array_merge($events, $this->get_gateway_events($subscription));
        
        // Sort events by timestamp
        usort($events, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        return array(
            'events' => $events,
            'summary' => $this->create_timeline_summary($events),
            'gaps' => $this->identify_timeline_gaps($events, $subscription)
        );
    }
    
    /**
     * Get subscription-related events
     */
    private function get_subscription_events($subscription) {
        $events = array();
        
        // Subscription creation
        $events[] = array(
            'timestamp' => $subscription->get_date('date_created'),
            'source' => 'subscription',
            'event_type' => 'subscription_created',
            'description' => sprintf(__('Subscription #%s created', 'wc-subscriptions-troubleshooter'), $subscription->get_id()),
            'metadata' => array(
                'subscription_id' => $subscription->get_id(),
                'status' => $subscription->get_status(),
                'total' => $subscription->get_total()
            ),
            'status' => 'success',
            'expected' => true
        );
        
        // Subscription notes
        $notes = wc_get_order_notes(array(
            'order_id' => $subscription->get_id(),
            'order_by' => 'date_created',
            'order' => 'ASC'
        ));
        
        foreach ($notes as $note) {
            $events[] = array(
                'timestamp' => $note->date_created,
                'source' => 'subscription_note',
                'event_type' => $this->categorize_note_type($note->content),
                'description' => $note->content,
                'metadata' => array(
                    'note_id' => $note->id,
                    'note_type' => $note->note_type,
                    'customer_note' => $note->customer_note
                ),
                'status' => $this->determine_note_status($note->content),
                'expected' => true
            );
        }
        
        // Status changes
        $status_changes = $this->get_status_change_events($subscription);
        $events = array_merge($events, $status_changes);
        
        return $events;
    }
    
    /**
     * Get order-related events
     */
    private function get_order_events($subscription) {
        $events = array();
        $related_orders = $subscription->get_related_orders();
        
        foreach ($related_orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;
            
            // Order creation
            $events[] = array(
                'timestamp' => $order->get_date_created(),
                'source' => 'order',
                'event_type' => 'order_created',
                'description' => sprintf(__('Order #%s created (%s)', 'wc-subscriptions-troubleshooter'), $order_id, $this->get_order_type($order, $subscription)),
                'metadata' => array(
                    'order_id' => $order_id,
                    'order_type' => $this->get_order_type($order, $subscription),
                    'status' => $order->get_status(),
                    'total' => $order->get_total()
                ),
                'status' => 'success',
                'expected' => true
            );
            
            // Order notes
            $order_notes = wc_get_order_notes(array(
                'order_id' => $order_id,
                'order_by' => 'date_created',
                'order' => 'ASC'
            ));
            
            foreach ($order_notes as $note) {
                $events[] = array(
                    'timestamp' => $note->date_created,
                    'source' => 'order_note',
                    'event_type' => $this->categorize_note_type($note->content),
                    'description' => sprintf(__('Order #%s: %s', 'wc-subscriptions-troubleshooter'), $order_id, $note->content),
                    'metadata' => array(
                        'order_id' => $order_id,
                        'note_id' => $note->id,
                        'note_type' => $note->note_type,
                        'customer_note' => $note->customer_note
                    ),
                    'status' => $this->determine_note_status($note->content),
                    'expected' => true
                );
            }
            
            // Payment events
            $payment_events = $this->get_payment_events($order);
            $events = array_merge($events, $payment_events);
        }
        
        return $events;
    }
    
    /**
     * Get Action Scheduler events
     */
    private function get_action_scheduler_events($subscription) {
        global $wpdb;
        
        $events = array();
        $subscription_id = $subscription->get_id();
        
        $actions_table = $wpdb->prefix . 'actionscheduler_actions';
        $groups_table = $wpdb->prefix . 'actionscheduler_groups';
        
        // Get all actions related to this subscription
        $query = $wpdb->prepare("
            SELECT a.*, ag.slug as group_slug
            FROM {$actions_table} a
            LEFT JOIN {$groups_table} ag ON a.group_id = ag.group_id
            WHERE a.args LIKE %s
            ORDER BY a.scheduled_date ASC
        ", '%' . $wpdb->esc_like($subscription_id) . '%');
        
        $actions = $wpdb->get_results($query);
        
        foreach ($actions as $action) {
            $events[] = array(
                'timestamp' => $action->scheduled_date,
                'source' => 'action_scheduler',
                'event_type' => $this->categorize_action_type($action->hook),
                'description' => sprintf(__('Scheduled action: %s', 'wc-subscriptions-troubleshooter'), $action->hook),
                'metadata' => array(
                    'action_id' => $action->action_id,
                    'hook' => $action->hook,
                    'status' => $action->status,
                    'group_slug' => $action->group_slug,
                    'args' => maybe_unserialize($action->args)
                ),
                'status' => $this->determine_action_status($action->status),
                'expected' => true
            );
        }
        
        return $events;
    }
    
    /**
     * Get log events
     */
    private function get_log_events($subscription) {
        $events = array();
        
        // Check WooCommerce logs
        $log_dir = WC_LOG_DIR;
        $log_files = glob($log_dir . '*.log');
        
        foreach ($log_files as $log_file) {
            $log_content = file_get_contents($log_file);
            $lines = explode("\n", $log_content);
            
            foreach ($lines as $line) {
                if (strpos($line, 'subscription') !== false && strpos($line, $subscription->get_id()) !== false) {
                    $events[] = array(
                        'timestamp' => $this->extract_timestamp_from_log($line),
                        'source' => 'log',
                        'event_type' => 'log_entry',
                        'description' => $line,
                        'metadata' => array(
                            'log_file' => basename($log_file),
                            'line' => $line
                        ),
                        'status' => $this->determine_log_status($line),
                        'expected' => false
                    );
                }
            }
        }
        
        return $events;
    }
    
    /**
     * Get gateway events
     */
    private function get_gateway_events($subscription) {
        $events = array();
        $payment_method = $subscription->get_payment_method();
        
        // Check for gateway-specific logs or webhooks
        switch ($payment_method) {
            case 'stripe':
                $events = array_merge($events, $this->get_stripe_events($subscription));
                break;
            case 'paypal':
                $events = array_merge($events, $this->get_paypal_events($subscription));
                break;
            // Add other gateways as needed
        }
        
        return $events;
    }
    
    /**
     * Get Stripe-specific events
     */
    private function get_stripe_events($subscription) {
        $events = array();
        
        // Check for Stripe customer ID
        $stripe_customer_id = $subscription->get_meta('_stripe_customer_id');
        
        if ($stripe_customer_id) {
            $events[] = array(
                'timestamp' => $subscription->get_date('date_created'),
                'source' => 'gateway',
                'event_type' => 'gateway_customer_created',
                'description' => sprintf(__('Stripe customer created: %s', 'wc-subscriptions-troubleshooter'), $stripe_customer_id),
                'metadata' => array(
                    'gateway' => 'stripe',
                    'customer_id' => $stripe_customer_id
                ),
                'status' => 'success',
                'expected' => true
            );
        }
        
        // Check for Stripe subscription ID
        $stripe_subscription_id = $subscription->get_meta('_stripe_subscription_id');
        
        if ($stripe_subscription_id) {
            $events[] = array(
                'timestamp' => $subscription->get_date('date_created'),
                'source' => 'gateway',
                'event_type' => 'gateway_subscription_created',
                'description' => sprintf(__('Stripe subscription created: %s', 'wc-subscriptions-troubleshooter'), $stripe_subscription_id),
                'metadata' => array(
                    'gateway' => 'stripe',
                    'subscription_id' => $stripe_subscription_id
                ),
                'status' => 'success',
                'expected' => true
            );
        }
        
        return $events;
    }
    
    /**
     * Get PayPal-specific events
     */
    private function get_paypal_events($subscription) {
        $events = array();
        
        // Check for PayPal subscription ID
        $paypal_subscription_id = $subscription->get_meta('_paypal_subscription_id');
        
        if ($paypal_subscription_id) {
            $events[] = array(
                'timestamp' => $subscription->get_date('date_created'),
                'source' => 'gateway',
                'event_type' => 'gateway_subscription_created',
                'description' => sprintf(__('PayPal subscription created: %s', 'wc-subscriptions-troubleshooter'), $paypal_subscription_id),
                'metadata' => array(
                    'gateway' => 'paypal',
                    'subscription_id' => $paypal_subscription_id
                ),
                'status' => 'success',
                'expected' => true
            );
        }
        
        return $events;
    }
    
    /**
     * Get status change events
     */
    private function get_status_change_events($subscription) {
        $events = array();
        
        // This would require tracking status changes over time
        // For now, we'll create a basic event for the current status
        $events[] = array(
            'timestamp' => $subscription->get_date('date_modified'),
            'source' => 'subscription',
            'event_type' => 'status_change',
            'description' => sprintf(__('Status changed to: %s', 'wc-subscriptions-troubleshooter'), $subscription->get_status()),
            'metadata' => array(
                'status' => $subscription->get_status()
            ),
            'status' => 'success',
            'expected' => true
        );
        
        return $events;
    }
    
    /**
     * Get payment events
     */
    private function get_payment_events($order) {
        $events = array();
        
        // Payment completion
        if ($order->is_paid()) {
            $events[] = array(
                'timestamp' => $order->get_date_paid(),
                'source' => 'order',
                'event_type' => 'payment_completed',
                'description' => sprintf(__('Payment completed for order #%s', 'wc-subscriptions-troubleshooter'), $order->get_id()),
                'metadata' => array(
                    'order_id' => $order->get_id(),
                    'payment_method' => $order->get_payment_method(),
                    'amount' => $order->get_total()
                ),
                'status' => 'success',
                'expected' => true
            );
        }
        
        // Payment failure
        if ($order->get_status() === 'failed') {
            $events[] = array(
                'timestamp' => $order->get_date_modified(),
                'source' => 'order',
                'event_type' => 'payment_failed',
                'description' => sprintf(__('Payment failed for order #%s', 'wc-subscriptions-troubleshooter'), $order->get_id()),
                'metadata' => array(
                    'order_id' => $order->get_id(),
                    'payment_method' => $order->get_payment_method(),
                    'amount' => $order->get_total()
                ),
                'status' => 'failed',
                'expected' => false
            );
        }
        
        return $events;
    }
    
    /**
     * Create timeline summary
     */
    private function create_timeline_summary($events) {
        $summary = array(
            'total_events' => count($events),
            'event_types' => array(),
            'sources' => array(),
            'statuses' => array(),
            'date_range' => array(
                'start' => null,
                'end' => null
            )
        );
        
        foreach ($events as $event) {
            // Count event types
            if (!isset($summary['event_types'][$event['event_type']])) {
                $summary['event_types'][$event['event_type']] = 0;
            }
            $summary['event_types'][$event['event_type']]++;
            
            // Count sources
            if (!isset($summary['sources'][$event['source']])) {
                $summary['sources'][$event['source']] = 0;
            }
            $summary['sources'][$event['source']]++;
            
            // Count statuses
            if (!isset($summary['statuses'][$event['status']])) {
                $summary['statuses'][$event['status']] = 0;
            }
            $summary['statuses'][$event['status']]++;
            
            // Track date range
            $timestamp = strtotime($event['timestamp']);
            if (!$summary['date_range']['start'] || $timestamp < strtotime($summary['date_range']['start'])) {
                $summary['date_range']['start'] = $event['timestamp'];
            }
            if (!$summary['date_range']['end'] || $timestamp > strtotime($summary['date_range']['end'])) {
                $summary['date_range']['end'] = $event['timestamp'];
            }
        }
        
        return $summary;
    }
    
    /**
     * Identify timeline gaps
     */
    private function identify_timeline_gaps($events, $subscription) {
        $gaps = array();
        
        // Check for missing renewal events
        $renewal_events = array_filter($events, function($event) {
            return $event['event_type'] === 'payment_completed' || $event['event_type'] === 'renewal_order_created';
        });
        
        $expected_renewals = $this->calculate_expected_renewals($subscription);
        $actual_renewals = count($renewal_events);
        
        if ($actual_renewals < $expected_renewals) {
            $gaps[] = array(
                'type' => 'missing_renewals',
                'description' => sprintf(__('Expected %d renewals, found %d', 'wc-subscriptions-troubleshooter'), $expected_renewals, $actual_renewals),
                'severity' => 'high'
            );
        }
        
        return $gaps;
    }
    
    /**
     * Helper methods
     */
    private function categorize_note_type($content) {
        $content_lower = strtolower($content);
        
        if (strpos($content_lower, 'payment') !== false) {
            return 'payment';
        } elseif (strpos($content_lower, 'status') !== false) {
            return 'status_change';
        } elseif (strpos($content_lower, 'cancelled') !== false) {
            return 'cancellation';
        } elseif (strpos($content_lower, 'failed') !== false) {
            return 'payment_failed';
        } else {
            return 'note';
        }
    }
    
    private function determine_note_status($content) {
        $content_lower = strtolower($content);
        
        if (strpos($content_lower, 'failed') !== false || strpos($content_lower, 'error') !== false) {
            return 'failed';
        } elseif (strpos($content_lower, 'success') !== false || strpos($content_lower, 'completed') !== false) {
            return 'success';
        } else {
            return 'info';
        }
    }
    
    private function categorize_action_type($hook) {
        if (strpos($hook, 'payment') !== false) {
            return 'payment';
        } elseif (strpos($hook, 'renewal') !== false) {
            return 'renewal';
        } elseif (strpos($hook, 'email') !== false) {
            return 'notification';
        } else {
            return 'action';
        }
    }
    
    private function determine_action_status($status) {
        switch ($status) {
            case 'completed':
                return 'success';
            case 'failed':
                return 'failed';
            case 'pending':
                return 'pending';
            default:
                return 'info';
        }
    }
    
    private function determine_log_status($line) {
        $line_lower = strtolower($line);
        
        if (strpos($line_lower, 'error') !== false || strpos($line_lower, 'failed') !== false) {
            return 'failed';
        } elseif (strpos($line_lower, 'success') !== false || strpos($line_lower, 'completed') !== false) {
            return 'success';
        } else {
            return 'info';
        }
    }
    
    private function get_order_type($order, $subscription) {
        if ($order->get_id() === $subscription->get_parent_id()) {
            return 'parent';
        } elseif (in_array($order->get_id(), $subscription->get_related_orders('ids', 'renewal'))) {
            return 'renewal';
        } elseif (in_array($order->get_id(), $subscription->get_related_orders('ids', 'resubscribe'))) {
            return 'resubscribe';
        } elseif (in_array($order->get_id(), $subscription->get_related_orders('ids', 'switch'))) {
            return 'switch';
        } else {
            return 'related';
        }
    }
    
    private function extract_timestamp_from_log($line) {
        // Extract timestamp from log line (format may vary)
        if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
            return $matches[1];
        }
        return current_time('mysql');
    }
    
    private function calculate_expected_renewals($subscription) {
        $start_date = $subscription->get_date('start');
        $end_date = $subscription->get_date('end');
        
        if (!$start_date) return 0;
        
        $interval = $subscription->get_billing_interval();
        $period = $subscription->get_billing_period();
        
        if ($end_date) {
            $start_timestamp = strtotime($start_date);
            $end_timestamp = strtotime($end_date);
            $duration = $end_timestamp - $start_timestamp;
            
            switch ($period) {
                case 'day':
                    return floor($duration / (DAY_IN_SECONDS * $interval));
                case 'week':
                    return floor($duration / (WEEK_IN_SECONDS * $interval));
                case 'month':
                    return floor($duration / (MONTH_IN_SECONDS * $interval));
                case 'year':
                    return floor($duration / (YEAR_IN_SECONDS * $interval));
            }
        }
        
        return 0;
    }
} 