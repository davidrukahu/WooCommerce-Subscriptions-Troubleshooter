# WooCommerce Subscriptions Troubleshooter

A comprehensive troubleshooting tool for WooCommerce Subscriptions that guides users through a 3-step diagnostic process to identify and resolve subscription issues.

## Features

### 🔍 **3-Step Diagnostic Process**

1. **Subscription Anatomy** - Comprehensive analysis of subscription structure
   - Payment method details and validation
   - Billing schedule and timing analysis
   - Related orders and their statuses
   - Scheduled actions and their execution status
   - Subscription notes and metadata

2. **Expected Behavior** - What should happen based on configuration
   - Product configuration analysis
   - Gateway behavior and capabilities
   - Active plugin impact assessment
   - Expected event calculations
   - Configuration validation

3. **Event Timeline** - Chronological log of actual events
   - Subscription and order notes
   - Action Scheduler events
   - Payment gateway communications
   - System logs and errors
   - Filterable and searchable timeline

### 🎯 **Key Features**

- **Clean Admin Interface** - Modern, responsive design with collapsible sections
- **Real-time Analysis** - AJAX-powered analysis with progress indicators
- **Issue Detection** - Automatic identification of common subscription problems
- **Severity Scoring** - Prioritized issues by impact level (Critical, High, Medium, Warning, Info)
- **Export Capabilities** - Export reports in HTML, CSV, and PDF formats
- **Comprehensive Logging** - Detailed logging system for tracking and debugging
- **Gateway Support** - Specialized analysis for Stripe, PayPal, and other gateways
- **Mobile Responsive** - Works seamlessly on all device sizes

### 🚨 **Issue Detection**

The plugin automatically detects and flags:

- **Payment Issues**
  - Overdue payments
  - Failed payment attempts
  - Expired payment methods
  - Missing payment tokens

- **Scheduler Issues**
  - Missing renewal actions
  - Failed scheduled actions
  - Irregular payment intervals

- **Status Issues**
  - Unexpected subscription statuses
  - Stuck status transitions
  - Configuration problems

- **Gateway Issues**
  - Communication failures
  - Webhook problems
  - Token validation errors

- **Notification Issues**
  - Missing email notifications
  - Disabled reminder emails

## Installation

### Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- WooCommerce Subscriptions plugin
- PHP 7.4 or higher

### Installation Steps

1. **Download the Plugin**
   ```bash
   git clone https://github.com/your-username/wc-subscriptions-troubleshooter.git
   ```

2. **Upload to WordPress**
   - Upload the `wc-subscriptions-troubleshooter` folder to `/wp-content/plugins/`
   - Or zip the folder and upload via WordPress admin

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "WooCommerce Subscriptions Troubleshooter"
   - Click "Activate"

4. **Access the Tool**
   - Navigate to WooCommerce → Subscriptions Troubleshooter
   - Start analyzing subscriptions

## Usage

### Basic Usage

1. **Enter Subscription ID or Email**
   - Use the search box to find subscriptions by ID or customer email
   - The tool will auto-suggest matching subscriptions

2. **Run Analysis**
   - Click "Analyze Subscription" to start the 3-step process
   - Watch the progress indicator as each step completes

3. **Review Results**
   - **Anatomy**: Check subscription structure and payment method status
   - **Expected Behavior**: Review configuration and gateway capabilities
   - **Timeline**: Examine chronological events and identify gaps
   - **Summary**: View prioritized issues and recommendations

### Advanced Features

#### Filtering Timeline Events
- Filter by event type (Payment, Status Change, Action, Notification)
- Filter by status (Success, Failed, Pending)
- Search within event descriptions

#### Export Reports
- Export analysis results in multiple formats
- Include all sections or specific data
- Share reports with support teams

#### Issue Tracking
- View historical issues for subscriptions
- Mark issues as resolved
- Track issue patterns across multiple subscriptions

## Configuration

### Plugin Settings

The plugin includes several configurable options:

```php
// Enable/disable logging
WCST_Plugin::update_option('enable_logging', true);

// Set log retention period (days)
WCST_Plugin::update_option('log_retention_days', 30);

// Enable automatic scanning
WCST_Plugin::update_option('auto_scan_enabled', false);

// Set scan frequency
WCST_Plugin::update_option('scan_frequency', 'daily');
```

### Database Tables

The plugin creates a custom table for issue tracking:

```sql
CREATE TABLE wp_wcs_troubleshooter_issues (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    subscription_id bigint(20) NOT NULL,
    issue_type varchar(50) NOT NULL,
    issue_category varchar(50) NOT NULL,
    severity varchar(20) NOT NULL,
    details longtext,
    detected_at datetime NOT NULL,
    resolved_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY subscription_id (subscription_id),
    KEY issue_type (issue_type),
    KEY severity (severity),
    KEY detected_at (detected_at)
);
```

## Development

### File Structure

```
wc-subscriptions-troubleshooter/
├── wc-subscriptions-troubleshooter.php    # Main plugin file
├── includes/
│   ├── class-plugin.php                   # Main plugin class
│   ├── class-admin.php                    # Admin interface controller
│   ├── class-ajax-handler.php             # AJAX request handling
│   ├── analyzers/
│   │   ├── class-subscription-anatomy.php # Step 1: Anatomy analyzer
│   │   ├── class-expected-behavior.php    # Step 2: Expected behavior
│   │   ├── class-timeline-builder.php     # Step 3: Timeline creator
│   │   └── class-discrepancy-detector.php # Compares expected vs actual
│   ├── collectors/
│   │   └── class-subscription-data.php    # Subscription data collector
│   └── utilities/
│       └── class-logger.php               # Custom logging functionality
├── admin/
│   ├── css/
│   │   └── admin-styles.css               # Admin styling
│   └── js/
│       └── admin-scripts.js               # Admin JavaScript
└── logs/
    └── troubleshooter.log                 # Plugin-specific logs
```

### Extending the Plugin

#### Adding New Analyzers

Create a new analyzer class in `includes/analyzers/`:

```php
class WCST_Custom_Analyzer {
    public function analyze($subscription_id) {
        // Your analysis logic here
        return array(
            'custom_data' => $data,
            'issues' => $issues
        );
    }
}
```

#### Adding New Data Collectors

Create a new collector class in `includes/collectors/`:

```php
class WCST_Custom_Data_Collector {
    public function collect_data($subscription_id) {
        // Your data collection logic here
        return $data;
    }
}
```

#### Customizing the Interface

Modify the admin interface by editing:
- `admin/css/admin-styles.css` for styling
- `admin/js/admin-scripts.js` for JavaScript functionality
- `includes/class-admin.php` for PHP logic

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Customize analysis data
add_filter('wcst_analysis_data', function($data, $subscription_id) {
    // Modify analysis data
    return $data;
}, 10, 2);

// Add custom discrepancy checks
add_action('wcst_analyze_discrepancies', function($subscription_id) {
    // Your custom discrepancy logic
});

// Customize export format
add_filter('wcst_export_format', function($format, $data) {
    // Custom export logic
    return $formatted_data;
}, 10, 2);
```

## Troubleshooting

### Common Issues

1. **Plugin Not Loading**
   - Check WooCommerce and WooCommerce Subscriptions are active
   - Verify PHP version compatibility
   - Check for plugin conflicts

2. **Analysis Fails**
   - Ensure subscription ID is valid
   - Check database permissions
   - Review error logs

3. **Missing Data**
   - Verify Action Scheduler is working
   - Check gateway integration
   - Review subscription configuration

### Debug Mode

Enable debug logging:

```php
// Add to wp-config.php
define('WCST_DEBUG', true);
```

### Support

For support and bug reports:
- Create an issue on GitHub
- Include subscription ID and error details
- Provide system information (WordPress, WooCommerce versions)

## Changelog

### Version 1.0.0
- Initial release
- 3-step diagnostic process
- Basic issue detection
- Export functionality
- Admin interface

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Credits

Developed for Woo HE's to simplify troubleshooting and reduce support time.

---

**Note**: This plugin is designed to work with WooCommerce Subscriptions and may require adjustments for custom subscription implementations. 