<?php
/**
 * Security Utility Class
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Security {
    
    /**
     * Allowed subscription statuses
     */
    private static $allowed_subscription_statuses = array(
        'active', 'pending', 'on-hold', 'cancelled', 'expired', 'pending-cancel', 'switched', 'suspended'
    );
    
    /**
     * Allowed export formats
     */
    private static $allowed_export_formats = array('pdf', 'csv', 'html', 'json');
    
    /**
     * Allowed filter types
     */
    private static $allowed_filter_types = array('event_type', 'status', 'date_range', 'severity');
    
    /**
     * Validate and sanitize subscription ID
     */
    public static function validate_subscription_id($subscription_id) {
        // Sanitize input
        $subscription_id = sanitize_text_field($subscription_id);
        
        // Validate it's a positive integer
        if (!is_numeric($subscription_id) || $subscription_id <= 0) {
            throw new Exception(__('Invalid subscription ID provided.', 'wc-subscriptions-troubleshooter'));
        }
        
        // Convert to integer
        $subscription_id = intval($subscription_id);
        
        // Verify subscription exists and user has access
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            throw new Exception(__('Subscription not found.', 'wc-subscriptions-troubleshooter'));
        }
        
        // Check if user has permission to access this subscription
        if (!self::user_can_access_subscription($subscription_id)) {
            throw new Exception(__('You do not have permission to access this subscription.', 'wc-subscriptions-troubleshooter'));
        }
        
        return $subscription_id;
    }
    
    /**
     * Validate and sanitize search term
     */
    public static function validate_search_term($search_term) {
        // Sanitize input
        $search_term = sanitize_text_field($search_term);
        
        // Remove potentially dangerous characters
        $search_term = preg_replace('/[<>"\']/', '', $search_term);
        
        // Check minimum length
        if (strlen($search_term) < 2) {
            throw new Exception(__('Search term must be at least 2 characters long.', 'wc-subscriptions-troubleshooter'));
        }
        
        // Check maximum length
        if (strlen($search_term) > 100) {
            throw new Exception(__('Search term is too long.', 'wc-subscriptions-troubleshooter'));
        }
        
        return $search_term;
    }
    
    /**
     * Validate and sanitize filters array
     */
    public static function validate_filters($filters) {
        if (!is_array($filters)) {
            return array();
        }
        
        $validated_filters = array();
        
        foreach ($filters as $filter_type => $filter_value) {
            // Validate filter type
            if (!in_array($filter_type, self::$allowed_filter_types)) {
                continue;
            }
            
            // Sanitize filter value based on type
            switch ($filter_type) {
                case 'event_type':
                    $validated_filters[$filter_type] = self::sanitize_event_type($filter_value);
                    break;
                    
                case 'status':
                    $validated_filters[$filter_type] = self::sanitize_status($filter_value);
                    break;
                    
                case 'date_range':
                    $validated_filters[$filter_type] = self::sanitize_date_range($filter_value);
                    break;
                    
                case 'severity':
                    $validated_filters[$filter_type] = self::sanitize_severity($filter_value);
                    break;
            }
        }
        
        return $validated_filters;
    }
    
    /**
     * Validate and sanitize export format
     */
    public static function validate_export_format($format) {
        $format = sanitize_text_field($format);
        
        if (!in_array($format, self::$allowed_export_formats)) {
            throw new Exception(__('Invalid export format specified.', 'wc-subscriptions-troubleshooter'));
        }
        
        return $format;
    }
    
    /**
     * Validate and sanitize settings
     */
    public static function validate_settings($settings) {
        if (!is_array($settings)) {
            return array();
        }
        
        $validated_settings = array();
        $allowed_settings = array(
            'enable_logging' => 'boolean',
            'log_retention_days' => 'integer',
            'auto_scan_enabled' => 'boolean',
            'scan_frequency' => 'string'
        );
        
        foreach ($settings as $key => $value) {
            if (!array_key_exists($key, $allowed_settings)) {
                continue;
            }
            
            switch ($allowed_settings[$key]) {
                case 'boolean':
                    $validated_settings[$key] = (bool) $value;
                    break;
                    
                case 'integer':
                    $value = intval($value);
                    if ($key === 'log_retention_days' && ($value < 1 || $value > 365)) {
                        continue; // Skip invalid values
                    }
                    $validated_settings[$key] = $value;
                    break;
                    
                case 'string':
                    $value = sanitize_text_field($value);
                    if ($key === 'scan_frequency' && !in_array($value, array('hourly', 'daily', 'weekly'))) {
                        continue; // Skip invalid values
                    }
                    $validated_settings[$key] = $value;
                    break;
            }
        }
        
        return $validated_settings;
    }
    
    /**
     * Sanitize event type
     */
    private static function sanitize_event_type($event_type) {
        $allowed_event_types = array('payment', 'status_change', 'action', 'notification', 'gateway', 'system');
        $event_type = sanitize_text_field($event_type);
        
        return in_array($event_type, $allowed_event_types) ? $event_type : '';
    }
    
    /**
     * Sanitize status
     */
    private static function sanitize_status($status) {
        $status = sanitize_text_field($status);
        
        return in_array($status, self::$allowed_subscription_statuses) ? $status : '';
    }
    
    /**
     * Sanitize date range
     */
    private static function sanitize_date_range($date_range) {
        if (!is_array($date_range)) {
            return array();
        }
        
        $sanitized_range = array();
        
        if (isset($date_range['start'])) {
            $start_date = sanitize_text_field($date_range['start']);
            if (strtotime($start_date)) {
                $sanitized_range['start'] = $start_date;
            }
        }
        
        if (isset($date_range['end'])) {
            $end_date = sanitize_text_field($date_range['end']);
            if (strtotime($end_date)) {
                $sanitized_range['end'] = $end_date;
            }
        }
        
        return $sanitized_range;
    }
    
    /**
     * Sanitize severity
     */
    private static function sanitize_severity($severity) {
        $allowed_severities = array('critical', 'high', 'medium', 'warning', 'info');
        $severity = sanitize_text_field($severity);
        
        return in_array($severity, $allowed_severities) ? $severity : '';
    }
    
    /**
     * Check if user can access subscription
     */
    private static function user_can_access_subscription($subscription_id) {
        // Super admins and shop managers can access all subscriptions
        if (current_user_can('manage_woocommerce')) {
            return true;
        }
        
        // For other users, check if they own the subscription
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        $subscription_user_id = $subscription->get_user_id();
        
        return $current_user_id === $subscription_user_id;
    }
    
    /**
     * Escape output for HTML
     */
    public static function escape_html($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'escape_html'), $data);
        }
        
        return esc_html($data);
    }
    
    /**
     * Escape output for JavaScript
     */
    public static function escape_js($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'escape_js'), $data);
        }
        
        return esc_js($data);
    }
    
    /**
     * Escape output for attributes
     */
    public static function escape_attr($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'escape_attr'), $data);
        }
        
        return esc_attr($data);
    }
    
    /**
     * Escape output for URLs
     */
    public static function escape_url($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'escape_url'), $data);
        }
        
        return esc_url($data);
    }
    
    /**
     * Sanitize and validate email
     */
    public static function validate_email($email) {
        $email = sanitize_email($email);
        
        if (!is_email($email)) {
            throw new Exception(__('Invalid email address provided.', 'wc-subscriptions-troubleshooter'));
        }
        
        return $email;
    }
    
    /**
     * Rate limiting for AJAX requests
     */
    public static function check_rate_limit($action, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $transient_key = 'wcst_rate_limit_' . $action . '_' . $user_id;
        $current_attempts = get_transient($transient_key);
        
        if ($current_attempts === false) {
            set_transient($transient_key, 1, 60); // 1 minute window
            return true;
        }
        
        if ($current_attempts >= 10) { // Max 10 requests per minute
            throw new Exception(__('Rate limit exceeded. Please wait before making another request.', 'wc-subscriptions-troubleshooter'));
        }
        
        set_transient($transient_key, $current_attempts + 1, 60);
        return true;
    }
    
    /**
     * Log security events
     */
    public static function log_security_event($event_type, $details = array()) {
        $logger = new WCST_Logger();
        $logger->log_discrepancy(
            0, // No specific subscription for security events
            'security_' . $event_type,
            array_merge($details, array(
                'user_id' => get_current_user_id(),
                'ip_address' => self::get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'timestamp' => current_time('mysql')
            ))
        );
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Validate nonce with proper error handling
     */
    public static function verify_nonce($nonce, $action) {
        if (empty($nonce)) {
            self::log_security_event('missing_nonce', array('action' => $action));
            throw new Exception(__('Security token is missing.', 'wc-subscriptions-troubleshooter'));
        }
        
        if (!wp_verify_nonce($nonce, $action)) {
            self::log_security_event('invalid_nonce', array('action' => $action));
            throw new Exception(__('Security token is invalid or expired.', 'wc-subscriptions-troubleshooter'));
        }
        
        return true;
    }
    
    /**
     * Check user permissions with proper error handling
     */
    public static function check_permissions($capability = 'manage_woocommerce') {
        if (!current_user_can($capability)) {
            self::log_security_event('insufficient_permissions', array(
                'required_capability' => $capability,
                'user_capabilities' => wp_get_current_user()->allcaps
            ));
            throw new Exception(__('You do not have permission to perform this action.', 'wc-subscriptions-troubleshooter'));
        }
        
        return true;
    }
} 