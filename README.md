# Doctor Subs - Subscription Troubleshooter

An intuitive WordPress plugin that implements the official WooCommerce Subscriptions troubleshooting framework, providing a systematic 3-step approach to diagnosing subscription issues: https://woocommerce.com/document/subscriptions/troubleshooting-framework/

## Installation

1. Upload the plugin files to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **WooCommerce > Subscriptions Troubleshooter**

## Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **WooCommerce**: 9.8.5+
- **WooCommerce Subscriptions**: Latest version recommended

## Usage

### Quick Start
1. Go to **WooCommerce > Subscriptions Troubleshooter**
2. Enter a subscription ID or customer email in the search box
3. Click **"Start Troubleshooting"**
4. Review the automated 3-step analysis

### Search Options
- **By Subscription ID**: Enter the numeric subscription ID (e.g., 1234)
- **By Customer Email**: Enter customer email to search their subscriptions

### The 3-Step Process

1. **Understand the Anatomy** - Review subscription structure and configuration
2. **Determine Expected Behavior** - Establish what should happen based on setup  
3. **Create a Timeline** - Document what actually occurred to identify problems

### Common Issues Detected

- Payment method configuration problems
- Failed scheduled actions
- Missing renewal orders
- Timeline discrepancies
- Status inconsistencies
- Gateway communication issues

## Technical Details

### Plugin Architecture

- **Main Plugin Class**: `WCST_Plugin` - Core plugin initialization and management
- **Admin Interface**: `WCST_Admin` - WordPress admin integration and UI
- **AJAX Handler**: `WCST_Ajax_Handler` - Handles all AJAX requests securely
- **Security Layer**: `WCST_Security` - Input validation and permission checking
- **Logging System**: `WCST_Logger` - Comprehensive activity logging

### Analyzer Classes

- **Subscription Anatomy**: `WCST_Subscription_Anatomy` - Step 1 analysis
- **Expected Behavior**: `WCST_Expected_Behavior` - Step 2 analysis  
- **Timeline Builder**: `WCST_Timeline_Builder` - Step 3 analysis
- **Data Collector**: `WCST_Subscription_Data` - Data collection utilities

### Data Sources

- Subscription meta data and notes
- Order information and notes
- Action Scheduler events
- Payment gateway logs
- WooCommerce system logs
- Switch operation logs

## Security & Performance

- **Nonce Verification**: All AJAX requests are nonce-protected
- **Permission Checking**: Requires `manage_woocommerce` capability
- **Rate Limiting**: Built-in rate limiting for analysis requests
- **Input Sanitization**: All inputs are properly sanitized
- **SQL Injection Protection**: All database queries use prepared statements
- **Efficient Queries**: Optimized database queries with result limiting

## Contributing

This plugin follows WordPress and WooCommerce coding standards. Contributions are welcome!

## License

GPL v2 or later. See LICENSE file for details.