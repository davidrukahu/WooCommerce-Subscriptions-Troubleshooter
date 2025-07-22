<?php
/**
 * Report Exporter Class
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCST_Report_Exporter {
    
    /**
     * Generate report
     */
    public function generate_report($subscription_id, $format) {
        // Validate subscription ID
        $subscription_id = WCST_Security::validate_subscription_id($subscription_id);
        
        // Get subscription data
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            throw new Exception(__('Subscription not found.', 'wc-subscriptions-troubleshooter'));
        }
        
        // Collect report data
        $report_data = $this->collect_report_data($subscription_id);
        
        // Format report based on requested format
        switch ($format) {
            case 'pdf':
                return $this->generate_pdf_report($report_data);
            case 'csv':
                return $this->generate_csv_report($report_data);
            case 'html':
                return $this->generate_html_report($report_data);
            case 'json':
                return $this->generate_json_report($report_data);
            default:
                throw new Exception(__('Unsupported export format.', 'wc-subscriptions-troubleshooter'));
        }
    }
    
    /**
     * Collect report data
     */
    private function collect_report_data($subscription_id) {
        // Initialize analyzers
        $anatomy_analyzer = new WCST_Subscription_Anatomy();
        $expected_analyzer = new WCST_Expected_Behavior();
        $timeline_builder = new WCST_Timeline_Builder();
        $discrepancy_detector = new WCST_Discrepancy_Detector();
        
        return array(
            'subscription_id' => $subscription_id,
            'generated_at' => current_time('mysql'),
            'anatomy' => $anatomy_analyzer->analyze($subscription_id),
            'expected' => $expected_analyzer->analyze($subscription_id),
            'timeline' => $timeline_builder->build($subscription_id),
            'discrepancies' => $discrepancy_detector->analyze_discrepancies($subscription_id)
        );
    }
    
    /**
     * Generate PDF report
     */
    private function generate_pdf_report($data) {
        // Basic PDF generation - in a real implementation, you'd use a library like TCPDF or mPDF
        $html = $this->generate_html_report($data);
        
        return array(
            'format' => 'pdf',
            'filename' => 'subscription-report-' . $data['subscription_id'] . '.pdf',
            'content' => $html, // For now, return HTML content
            'mime_type' => 'application/pdf'
        );
    }
    
    /**
     * Generate CSV report
     */
    private function generate_csv_report($data) {
        $csv_data = array();
        
        // Add header
        $csv_data[] = array('Subscription Report', 'Generated: ' . $data['generated_at']);
        $csv_data[] = array();
        
        // Add subscription info
        $csv_data[] = array('Subscription ID', $data['subscription_id']);
        $csv_data[] = array();
        
        // Add discrepancies
        $csv_data[] = array('Discrepancies Found', count($data['discrepancies']));
        foreach ($data['discrepancies'] as $discrepancy) {
            $csv_data[] = array(
                $discrepancy['type'],
                $discrepancy['severity'],
                $discrepancy['description']
            );
        }
        
        // Convert to CSV string
        $csv_content = '';
        foreach ($csv_data as $row) {
            $csv_content .= '"' . implode('","', array_map('addslashes', $row)) . '"' . "\n";
        }
        
        return array(
            'format' => 'csv',
            'filename' => 'subscription-report-' . $data['subscription_id'] . '.csv',
            'content' => $csv_content,
            'mime_type' => 'text/csv'
        );
    }
    
    /**
     * Generate HTML report
     */
    private function generate_html_report($data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Subscription Report #<?php echo esc_html($data['subscription_id']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f0f0f0; padding: 20px; margin-bottom: 20px; }
                .section { margin-bottom: 30px; }
                .discrepancy { border: 1px solid #ddd; padding: 10px; margin: 10px 0; }
                .critical { border-left: 5px solid #dc3545; }
                .high { border-left: 5px solid #fd7e14; }
                .medium { border-left: 5px solid #ffc107; }
                .warning { border-left: 5px solid #17a2b8; }
                .info { border-left: 5px solid #6c757d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Subscription Troubleshooter Report</h1>
                <p><strong>Subscription ID:</strong> <?php echo esc_html($data['subscription_id']); ?></p>
                <p><strong>Generated:</strong> <?php echo esc_html($data['generated_at']); ?></p>
            </div>
            
            <div class="section">
                <h2>Summary</h2>
                <p><strong>Total Discrepancies:</strong> <?php echo count($data['discrepancies']); ?></p>
                <p><strong>Timeline Events:</strong> <?php echo count($data['timeline']['events'] ?? array()); ?></p>
            </div>
            
            <div class="section">
                <h2>Discrepancies</h2>
                <?php if (!empty($data['discrepancies'])): ?>
                    <?php foreach ($data['discrepancies'] as $discrepancy): ?>
                        <div class="discrepancy <?php echo esc_attr($discrepancy['severity']); ?>">
                            <h3><?php echo esc_html($discrepancy['type']); ?></h3>
                            <p><strong>Severity:</strong> <?php echo esc_html($discrepancy['severity']); ?></p>
                            <p><strong>Description:</strong> <?php echo esc_html($discrepancy['description']); ?></p>
                            <?php if (!empty($discrepancy['recommendation'])): ?>
                                <p><strong>Recommendation:</strong> <?php echo esc_html($discrepancy['recommendation']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No discrepancies found.</p>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        
        return array(
            'format' => 'html',
            'filename' => 'subscription-report-' . $data['subscription_id'] . '.html',
            'content' => $html,
            'mime_type' => 'text/html'
        );
    }
    
    /**
     * Generate JSON report
     */
    private function generate_json_report($data) {
        return array(
            'format' => 'json',
            'filename' => 'subscription-report-' . $data['subscription_id'] . '.json',
            'content' => json_encode($data, JSON_PRETTY_PRINT),
            'mime_type' => 'application/json'
        );
    }
} 