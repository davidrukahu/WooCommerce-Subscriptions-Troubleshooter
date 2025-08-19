<?php
declare( strict_types=1 );
/**
 * Report Exporter Utility Class
 *
 * @package WC_Subscriptions_Troubleshooter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Report exporter utility class for generating troubleshooting reports.
 *
 * @since 2.0.0
 */
class WCST_Report_Exporter {
    
    /**
     * Export analysis data to specified format.
     *
     * @since 2.0.0
     * @param array  $analysis_data Complete analysis data.
     * @param string $format Export format (html, csv, json).
     * @return string Exported data.
     * @throws Exception If export format is unsupported.
     */
    public function export( $analysis_data, $format = 'html' ) {
        switch ( $format ) {
            case 'html':
                return $this->export_html( $analysis_data );
            case 'csv':
                return $this->export_csv( $analysis_data );
            case 'json':
                return $this->export_json( $analysis_data );
            default:
                throw new Exception( sprintf(
                    /* translators: %s: unsupported format */
                    __( 'Unsupported export format: %s', 'wc-subscriptions-troubleshooter' ),
                    $format
                ) );
        }
    }
    
    /**
     * Export data as HTML report.
     *
     * @since 2.0.0
     * @param array $analysis_data Analysis data.
     * @return string HTML report.
     */
    private function export_html( $analysis_data ) {
        $subscription_id = $analysis_data['subscription_id'];
        $timestamp = $analysis_data['timestamp'];
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Subscription Troubleshooting Report #<?php echo esc_html( $subscription_id ); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 1200px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 30px; border-radius: 5px; margin-bottom: 30px; }
                .section { background: #fff; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 25px; overflow: hidden; }
                .section-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #ddd; font-size: 18px; font-weight: bold; }
                .section-content { padding: 25px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
                th { background: #f8f9fa; font-weight: bold; }
                .status-badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
                .status-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .status-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
                .status-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
                .status-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
                .timeline-event { padding: 15px 0; border-bottom: 1px solid #eee; }
                .timeline-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
                .timeline-date { font-family: monospace; color: #666; }
                .alert { padding: 12px 15px; border-radius: 4px; margin: 15px 0; }
                .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
                .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
                .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
                .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
                .summary-card { background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; }
                .summary-value { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>WooCommerce Subscriptions Troubleshooting Report</h1>
                <p><strong>Subscription ID:</strong> <?php echo esc_html( $subscription_id ); ?></p>
                <p><strong>Generated:</strong> <?php echo esc_html( $timestamp ); ?></p>
                <p><strong>Plugin:</strong> WC Subscriptions Troubleshooter v<?php echo esc_html( WCST_PLUGIN_VERSION ); ?></p>
            </div>

            <?php echo $this->render_html_anatomy( $analysis_data['anatomy'] ); ?>
            <?php echo $this->render_html_expected( $analysis_data['expected'] ); ?>
            <?php echo $this->render_html_timeline( $analysis_data['timeline'] ); ?>
            <?php echo $this->render_html_summary( $analysis_data['summary'] ); ?>

            <footer style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
                <p>Report generated by WooCommerce Subscriptions Troubleshooter</p>
                <p>For support, visit: <a href="https://woocommerce.com/products/woocommerce-subscriptions/">WooCommerce Subscriptions</a></p>
            </footer>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Export data as CSV.
     *
     * @since 2.0.0
     * @param array $analysis_data Analysis data.
     * @return string CSV data.
     */
    private function export_csv( $analysis_data ) {
        $csv_data = array();
        
        // Header row.
        $csv_data[] = array(
            'Section',
            'Property',
            'Value',
            'Notes'
        );
        
        // Basic subscription info.
        $basic_info = $analysis_data['anatomy']['basic_info'];
        $csv_data[] = array( 'Basic Info', 'Subscription ID', $basic_info['id'], '' );
        $csv_data[] = array( 'Basic Info', 'Status', $basic_info['status'], '' );
        $csv_data[] = array( 'Basic Info', 'Customer ID', $basic_info['customer_id'], '' );
        $csv_data[] = array( 'Basic Info', 'Total', $basic_info['total'] . ' ' . $basic_info['currency'], '' );
        
        // Payment method info.
        $payment_method = $analysis_data['anatomy']['payment_method'];
        $csv_data[] = array( 'Payment Method', 'Gateway', $payment_method['title'] ?? 'N/A', '' );
        $csv_data[] = array( 'Payment Method', 'Manual Renewal', $payment_method['requires_manual'] ? 'Yes' : 'No', '' );
        $csv_data[] = array( 'Payment Method', 'Status', $payment_method['status']['is_valid'] ? 'Valid' : 'Issues Found', implode( '; ', $payment_method['status']['warnings'] ?? array() ) );
        
        // Timeline events (simplified).
        if ( isset( $analysis_data['timeline']['events'] ) ) {
            foreach ( $analysis_data['timeline']['events'] as $event ) {
                $csv_data[] = array(
                    'Timeline',
                    $event['timestamp'],
                    $event['title'],
                    $event['status']
                );
            }
        }
        
        // Issues.
        if ( isset( $analysis_data['summary']['issues'] ) ) {
            foreach ( $analysis_data['summary']['issues'] as $issue ) {
                $csv_data[] = array(
                    'Issues',
                    $issue['title'],
                    $issue['description'],
                    $issue['severity']
                );
            }
        }
        
        // Convert to CSV format.
        $output = '';
        foreach ( $csv_data as $row ) {
            $escaped_row = array_map( function( $field ) {
                return '"' . str_replace( '"', '""', $field ) . '"';
            }, $row );
            $output .= implode( ',', $escaped_row ) . "\n";
        }
        
        return $output;
    }
    
    /**
     * Export data as JSON.
     *
     * @since 2.0.0
     * @param array $analysis_data Analysis data.
     * @return string JSON data.
     */
    private function export_json( $analysis_data ) {
        return wp_json_encode( $analysis_data, JSON_PRETTY_PRINT );
    }
    
    /**
     * Render HTML anatomy section.
     *
     * @since 2.0.0
     * @param array $anatomy Anatomy data.
     * @return string HTML content.
     */
    private function render_html_anatomy( $anatomy ) {
        $basic_info = $anatomy['basic_info'];
        $payment_method = $anatomy['payment_method'];
        $billing_schedule = $anatomy['billing_schedule'];
        
        ob_start();
        ?>
        <div class="section">
            <div class="section-header">Step 1: Subscription Anatomy</div>
            <div class="section-content">
                <h3>Basic Information</h3>
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Status</h4>
                        <div class="summary-value">
                            <span class="status-badge status-<?php echo esc_attr( $this->get_status_class( $basic_info['status'] ) ); ?>">
                                <?php echo esc_html( $basic_info['status'] ); ?>
                            </span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <h4>Customer ID</h4>
                        <div class="summary-value"><?php echo esc_html( $basic_info['customer_id'] ); ?></div>
                    </div>
                    <div class="summary-card">
                        <h4>Total</h4>
                        <div class="summary-value"><?php echo esc_html( $basic_info['total'] . ' ' . $basic_info['currency'] ); ?></div>
                    </div>
                </div>

                <h3>Payment Method</h3>
                <table>
                    <tr><th>Property</th><th>Value</th></tr>
                    <tr><td>Gateway</td><td><?php echo esc_html( $payment_method['title'] ?? 'N/A' ); ?></td></tr>
                    <tr><td>Type</td><td><?php echo $payment_method['requires_manual'] ? 'Manual' : 'Automatic'; ?></td></tr>
                    <tr><td>Status</td><td>
                        <?php if ( $payment_method['status']['is_valid'] ) : ?>
                            <span class="status-badge status-success">Valid</span>
                        <?php else : ?>
                            <span class="status-badge status-error">Issues Found</span>
                        <?php endif; ?>
                    </td></tr>
                </table>

                <?php if ( ! empty( $payment_method['status']['warnings'] ) ) : ?>
                    <div class="alert alert-warning">
                        <strong>Payment Method Warnings:</strong><br>
                        <?php echo implode( '<br>', array_map( 'esc_html', $payment_method['status']['warnings'] ) ); ?>
                    </div>
                <?php endif; ?>

                <h3>Billing Schedule</h3>
                <table>
                    <tr><th>Property</th><th>Value</th></tr>
                    <tr><td>Billing Interval</td><td><?php echo esc_html( $billing_schedule['interval'] . ' ' . $billing_schedule['period'] ); ?></td></tr>
                    <tr><td>Start Date</td><td><?php echo esc_html( $this->format_date( $billing_schedule['start_date'] ) ); ?></td></tr>
                    <tr><td>Next Payment</td><td><?php echo esc_html( $this->format_date( $billing_schedule['next_payment'] ) ); ?></td></tr>
                    <tr><td>End Date</td><td><?php echo esc_html( $this->format_date( $billing_schedule['end_date'] ) ); ?></td></tr>
                    <tr><td>Editable</td><td><?php echo $billing_schedule['is_editable'] ? 'Yes' : 'No'; ?></td></tr>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render HTML expected behavior section.
     *
     * @since 2.0.0
     * @param array $expected Expected behavior data.
     * @return string HTML content.
     */
    private function render_html_expected( $expected ) {
        ob_start();
        ?>
        <div class="section">
            <div class="section-header">Step 2: Expected Behavior</div>
            <div class="section-content">
                <?php if ( isset( $expected['renewal_expectations'] ) ) : ?>
                    <h3>Renewal Process</h3>
                    <div class="alert alert-info">
                        <strong>Type:</strong> <?php echo esc_html( $expected['renewal_expectations']['type'] ); ?><br>
                        <strong>Description:</strong> <?php echo esc_html( $expected['renewal_expectations']['description'] ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render HTML timeline section.
     *
     * @since 2.0.0
     * @param array $timeline Timeline data.
     * @return string HTML content.
     */
    private function render_html_timeline( $timeline ) {
        ob_start();
        ?>
        <div class="section">
            <div class="section-header">Step 3: Timeline of Events</div>
            <div class="section-content">
                <?php if ( ! empty( $timeline['events'] ) ) : ?>
                    <?php foreach ( $timeline['events'] as $event ) : ?>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <span class="timeline-date"><?php echo esc_html( $this->format_date( $event['timestamp'] ) ); ?></span>
                                <span class="status-badge status-<?php echo esc_attr( $event['status'] ); ?>">
                                    <?php echo esc_html( $event['type'] ); ?>
                                </span>
                            </div>
                            <div><strong><?php echo esc_html( $event['title'] ); ?></strong></div>
                            <?php if ( $event['description'] !== $event['title'] ) : ?>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    <?php echo esc_html( $event['description'] ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No timeline events found.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render HTML summary section.
     *
     * @since 2.0.0
     * @param array $summary Summary data.
     * @return string HTML content.
     */
    private function render_html_summary( $summary ) {
        ob_start();
        ?>
        <div class="section">
            <div class="section-header">Summary & Next Steps</div>
            <div class="section-content">
                <?php if ( ! empty( $summary['issues'] ) ) : ?>
                    <h3>Issues Detected</h3>
                    <?php foreach ( $summary['issues'] as $issue ) : ?>
                        <?php $alert_class = 'critical' === $issue['severity'] ? 'error' : 'warning'; ?>
                        <div class="alert alert-<?php echo esc_attr( $alert_class ); ?>">
                            <strong><?php echo esc_html( $issue['title'] ); ?>:</strong>
                            <?php echo esc_html( $issue['description'] ); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="alert alert-success">
                        <strong>No issues detected!</strong> The subscription appears to be functioning normally.
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $summary['next_steps'] ) ) : ?>
                    <h3>Recommended Next Steps</h3>
                    <ul>
                        <?php foreach ( $summary['next_steps'] as $step ) : ?>
                            <li><?php echo esc_html( $step ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get CSS class for status.
     *
     * @since 2.0.0
     * @param string $status Status value.
     * @return string CSS class.
     */
    private function get_status_class( $status ) {
        $status_map = array(
            'active'         => 'success',
            'on-hold'        => 'warning',
            'cancelled'      => 'error',
            'expired'        => 'error',
            'pending-cancel' => 'warning',
            'pending'        => 'warning',
        );
        
        return $status_map[ $status ] ?? 'info';
    }
    
    /**
     * Format date for display.
     *
     * @since 2.0.0
     * @param mixed $date Date to format.
     * @return string Formatted date.
     */
    private function format_date( $date ) {
        if ( empty( $date ) || 'N/A' === $date ) {
            return 'N/A';
        }
        
        if ( is_string( $date ) ) {
            return $date;
        }
        
        if ( $date instanceof DateTime ) {
            return $date->format( 'Y-m-d H:i:s' );
        }
        
        return (string) $date;
    }
}
