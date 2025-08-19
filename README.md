# Dr Subs - Subscription Troubleshooter

An intuitive WordPress plugin that implements the official WooCommerce Subscriptions troubleshooting framework, providing a systematic 3-step approach to diagnosing subscription issues: https://woocommerce.com/document/subscriptions/troubleshooting-framework/

## Features

### 🔍 **Step 1: Subscription Anatomy**
- **Payment Method Analysis**: Detailed analysis of payment gateway configuration and status
- **Billing Schedule Review**: Complete billing schedule information and editability status
- **Subscription Notes**: Comprehensive review of all subscription activity notes
- **Related Orders**: Full tracking of parent, renewal, switch, and resubscribe orders
- **Scheduled Actions**: Action Scheduler integration for automated renewal tracking
- **Subscription Type Detection**: Automatic identification of Action Scheduler vs Gateway-controlled subscriptions

### 🎯 **Step 2: Expected Behavior Analysis**
- **Product Configuration**: Analysis of subscription product settings and implications
- **Payment Gateway Capabilities**: Detailed review of what your payment gateway supports
- **Renewal Process Expectations**: Clear explanation of how renewals should work
- **Lifecycle Expectations**: Status transitions and expected subscription behavior
- **Creation Process Validation**: Verification of proper subscription creation from parent orders
- **Switching Analysis**: Detection and analysis of subscription switches with log references

### 📅 **Step 3: Timeline Builder**
- **Chronological Event Timeline**: Complete timeline of all subscription events
- **Multi-Source Data Collection**: Events from subscription notes, orders, scheduled actions, and logs
- **Discrepancy Detection**: Automatic identification of timeline gaps and inconsistencies
- **Pattern Analysis**: Analysis of renewal patterns, payment patterns, and error patterns
- **Visual Timeline**: Easy-to-read timeline with status indicators and event categories

### 📊 **Comprehensive Reporting**
- **Interactive Admin Interface**: Clean, intuitive interface following WordPress design standards
- **Export Capabilities**: HTML, CSV, and JSON export formats
- **Issue Detection**: Automatic identification of common subscription problems
- **Next Steps Recommendations**: Actionable recommendations based on findings
- **Summary Statistics**: Quick overview of subscription health

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
5. Export the report for documentation or support

### Search Options
- **By Subscription ID**: Enter the numeric subscription ID (e.g., 1234)
- **By Customer Email**: Enter customer email to search their subscriptions

### Understanding Results

#### **Subscription Anatomy**
- Review payment method status and warnings
- Check billing schedule configuration
- Examine subscription notes for historical context
- Verify related orders and their statuses

#### **Expected Behavior**
- Understand what should happen with your subscription
- Review payment gateway capabilities
- Learn about renewal process expectations
- Identify any configuration mismatches

#### **Timeline Analysis**
- Follow chronological sequence of events
- Identify gaps or inconsistencies
- Spot patterns in renewals or failures
- Locate specific points where issues occurred

## Troubleshooting Framework

This plugin implements the official WooCommerce Subscriptions troubleshooting framework as documented at [woocommerce.com](https://woocommerce.com/document/subscriptions/troubleshooting-framework/).

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
- **Report Exporter**: `WCST_Report_Exporter` - Export functionality

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