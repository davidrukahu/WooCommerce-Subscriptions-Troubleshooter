<?php
/**
 * Logger Utility
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Logger {
    
    /**
     * Log file path
     */
    private $log_file;
    
    /**
     * Common issue patterns
     */
    private $common_issues = array(
        'failed_renewal' => array(
            'pattern' => '/payment.*failed|declined|error/',
            'category' => 'payment_failure',
            'severity' => 'critical'
        ),
        'missed_action' => array(
            'pattern' => '/action.*missed|skipped|timeout/',
            'category' => 'scheduler_issue',
            'severity' => 'high'
        ),
        'gateway_timeout' => array(
            'pattern' => '/gateway.*timeout|unreachable/',
            'category' => 'gateway_communication',
            'severity' => 'high'
        ),
        'token_expired' => array(
            'pattern' => '/token.*expired|invalid/',
            'category' => 'payment_method',
            'severity' => 'critical'
        ),
        'webhook_failure' => array(
            'pattern' => '/webhook.*failed|error/',
            'category' => 'gateway_communication',
            'severity' => 'high'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log_file = WCST_PLUGIN_DIR . 'logs/troubleshooter.log';
        $this->ensure_log_directory();
    }
    
    /**
     * Log discrepancy
     */
    public function log_discrepancy($subscription_id, $type, $details) {
        $entry = array(
            'timestamp' => current_time('mysql'),
            'subscription_id' => $subscription_id,
            'type' => $type,
            'details' => $details,
            'category' => $this->categorize_issue($type, $details),
            'severity' => $this->assess_severity($type, $details)
        );
        
        // Log to file
        $this->write_log($entry);
        
        // Store in database for aggregation
        $this->store_in_db($entry);
        
        // Trigger alerts if critical
        if ($entry['severity'] === 'critical') {
            $this->trigger_alert($entry);
        }
    }
    
    /**
     * Log analysis result
     */
    public function log_analysis($subscription_id, $analysis_data) {
        $entry = array(
            'timestamp' => current_time('mysql'),
            'subscription_id' => $subscription_id,
            'type' => 'analysis_completed',
            'details' => array(
                'anatomy_issues' => count($analysis_data['anatomy']['meta_analysis']['warnings'] ?? array()),
                'expected_issues' => count($analysis_data['expected']['active_plugins_impact']['conflict_plugins'] ?? array()),
                'timeline_events' => count($analysis_data['timeline']['events'] ?? array()),
                'discrepancies' => count($analysis_data['discrepancies'] ?? array())
            ),
            'category' => 'analysis',
            'severity' => 'info'
        );
        
        $this->write_log($entry);
    }
    
    /**
     * Get common issues report
     */
    public function get_common_issues_report($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT issue_type, issue_category, severity, COUNT(*) as count
            FROM {$table_name}
            WHERE detected_at >= %s
            GROUP BY issue_type, issue_category, severity
            ORDER BY count DESC
        ", $cutoff_date));
        
        return $results;
    }
    
    /**
     * Get subscription issues
     */
    public function get_subscription_issues($subscription_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM {$table_name}
            WHERE subscription_id = %d
            ORDER BY detected_at DESC
        ", $subscription_id));
        
        return $results;
    }
    
    /**
     * Get recent issues
     */
    public function get_recent_issues($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM {$table_name}
            ORDER BY detected_at DESC
            LIMIT %d
        ", $limit));
        
        return $results;
    }
    
    /**
     * Mark issue as resolved
     */
    public function mark_issue_resolved($issue_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        
        $result = $wpdb->update(
            $table_name,
            array('resolved_at' => current_time('mysql')),
            array('id' => $issue_id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Clean old logs
     */
    public function clean_old_logs($days = 90) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $result = $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_name}
            WHERE detected_at < %s
            AND resolved_at IS NOT NULL
        ", $cutoff_date));
        
        return $result;
    }
    
    /**
     * Export issues report
     */
    public function export_issues_report($format = 'csv', $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($filters['severity'])) {
            $where_clauses[] = 'severity = %s';
            $where_values[] = $filters['severity'];
        }
        
        if (!empty($filters['category'])) {
            $where_clauses[] = 'issue_category = %s';
            $where_values[] = $filters['category'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'detected_at >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'detected_at <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $query = "SELECT * FROM {$table_name} {$where_sql} ORDER BY detected_at DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query);
        
        return $this->format_export_data($results, $format);
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensure_log_directory() {
        $log_dir = dirname($this->log_file);
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Create .htaccess to protect logs
        $htaccess_file = $log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Order deny,allow\nDeny from all\n");
        }
    }
    
    /**
     * Write log entry
     */
    private function write_log($entry) {
        $log_line = sprintf(
            "[%s] Subscription #%s - %s (%s): %s\n",
            $entry['timestamp'],
            $entry['subscription_id'],
            $entry['type'],
            $entry['severity'],
            json_encode($entry['details'])
        );
        
        file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Store in database
     */
    private function store_in_db($entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wcs_troubleshooter_issues';
        
        $wpdb->insert(
            $table_name,
            array(
                'subscription_id' => $entry['subscription_id'],
                'issue_type' => $entry['type'],
                'issue_category' => $entry['category'],
                'severity' => $entry['severity'],
                'details' => json_encode($entry['details']),
                'detected_at' => $entry['timestamp']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Categorize issue
     */
    private function categorize_issue($type, $details) {
        $details_string = is_array($details) ? json_encode($details) : $details;
        
        foreach ($this->common_issues as $pattern_name => $pattern_data) {
            if (preg_match($pattern_data['pattern'], strtolower($details_string))) {
                return $pattern_data['category'];
            }
        }
        
        return 'general';
    }
    
    /**
     * Assess severity
     */
    private function assess_severity($type, $details) {
        $details_string = is_array($details) ? json_encode($details) : $details;
        
        foreach ($this->common_issues as $pattern_name => $pattern_data) {
            if (preg_match($pattern_data['pattern'], strtolower($details_string))) {
                return $pattern_data['severity'];
            }
        }
        
        // Default severity based on type
        $type_severities = array(
            'payment_failed' => 'critical',
            'renewal_missed' => 'high',
            'gateway_error' => 'high',
            'configuration_error' => 'medium',
            'warning' => 'warning',
            'info' => 'info'
        );
        
        return isset($type_severities[$type]) ? $type_severities[$type] : 'medium';
    }
    
    /**
     * Trigger alert
     */
    private function trigger_alert($entry) {
        // Send admin notification
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('Critical Subscription Issue - #%s', 'wc-subscriptions-troubleshooter'), $entry['subscription_id']);
        
        $message = sprintf(
            __("A critical issue has been detected with subscription #%s:\n\nType: %s\nCategory: %s\nDetails: %s\n\nPlease review immediately.", 'wc-subscriptions-troubleshooter'),
            $entry['subscription_id'],
            $entry['type'],
            $entry['category'],
            json_encode($entry['details'])
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Log alert
        $this->write_log(array(
            'timestamp' => current_time('mysql'),
            'subscription_id' => $entry['subscription_id'],
            'type' => 'alert_sent',
            'details' => array('alert_type' => 'critical_issue'),
            'category' => 'alert',
            'severity' => 'info'
        ));
    }
    
    /**
     * Format export data
     */
    private function format_export_data($data, $format) {
        switch ($format) {
            case 'csv':
                return $this->format_csv($data);
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'html':
                return $this->format_html($data);
            default:
                return $data;
        }
    }
    
    /**
     * Format CSV
     */
    private function format_csv($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, array('ID', 'Subscription ID', 'Issue Type', 'Category', 'Severity', 'Details', 'Detected At', 'Resolved At'));
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, array(
                $row->id,
                $row->subscription_id,
                $row->issue_type,
                $row->issue_category,
                $row->severity,
                $row->details,
                $row->detected_at,
                $row->resolved_at
            ));
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Format HTML
     */
    private function format_html($data) {
        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        $html .= '<tr><th>ID</th><th>Subscription ID</th><th>Issue Type</th><th>Category</th><th>Severity</th><th>Details</th><th>Detected At</th><th>Resolved At</th></tr>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($row->id) . '</td>';
            $html .= '<td>' . esc_html($row->subscription_id) . '</td>';
            $html .= '<td>' . esc_html($row->issue_type) . '</td>';
            $html .= '<td>' . esc_html($row->issue_category) . '</td>';
            $html .= '<td>' . esc_html($row->severity) . '</td>';
            $html .= '<td>' . esc_html($row->details) . '</td>';
            $html .= '<td>' . esc_html($row->detected_at) . '</td>';
            $html .= '<td>' . esc_html($row->resolved_at) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        return $html;
    }
} 