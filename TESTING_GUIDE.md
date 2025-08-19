# Testing Guide for WooCommerce Subscriptions Troubleshooter v2.0.0

## Pre-Installation Checklist

### Requirements
- ✅ **WordPress**: 5.0 or higher
- ✅ **PHP**: 7.4 or higher  
- ✅ **WooCommerce**: 9.8.5 or higher
- ✅ **WooCommerce Subscriptions**: Latest version (7.7.0+ recommended)

### Test Environment Setup
1. **Use a staging site** - Never test on production
2. **Backup your site** before installation
3. **Have test subscriptions ready** - Create a few test subscriptions with different:
   - Payment methods (manual, Stripe, PayPal, etc.)
   - Statuses (active, on-hold, cancelled)
   - Billing schedules (monthly, yearly, weekly)
   - Some with renewal history, some new

## Installation Steps

### Method 1: WordPress Admin Upload
1. Go to **Plugins > Add New > Upload Plugin**
2. Choose `wc-subscriptions-troubleshooter-v2.0.0.zip`
3. Click **Install Now**
4. **Activate** the plugin

### Method 2: Manual Installation
1. Extract the zip file
2. Upload `wc-subscriptions-troubleshooter` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. **Activate** "WooCommerce Subscriptions Troubleshooter"

## Testing Steps

### 1. Initial Verification
- [ ] Plugin activates without errors
- [ ] No PHP errors in debug log
- [ ] Menu appears under **WooCommerce > Subscriptions Troubleshooter**

### 2. Basic Functionality Tests

#### Test Search Functionality
- [ ] **Search by Subscription ID**: Enter a valid subscription ID
- [ ] **Search by Email**: Enter customer email address
- [ ] **Invalid searches**: Try non-existent IDs, invalid emails
- [ ] **Auto-suggestions**: Type partial email and check suggestions appear

#### Test Analysis Process
- [ ] **Step 1 - Anatomy**: Verify subscription details load correctly
  - Payment method information
  - Billing schedule details  
  - Subscription notes
  - Related orders
  - Scheduled actions
- [ ] **Step 2 - Expected Behavior**: Check behavioral analysis
  - Payment gateway capabilities
  - Renewal expectations
  - Lifecycle expectations
- [ ] **Step 3 - Timeline**: Validate timeline creation
  - Chronological events display
  - Event filtering works
  - No missing critical events

#### Test Different Subscription Types
- [ ] **Manual subscriptions**: Test manual renewal subscriptions
- [ ] **Automatic subscriptions**: Test gateway-controlled subscriptions  
- [ ] **Action Scheduler subscriptions**: Test WP cron-based renewals
- [ ] **Failed payment subscriptions**: Test subscriptions with payment issues
- [ ] **Switched subscriptions**: Test subscriptions that have been upgraded/downgraded

### 3. Export Functionality
- [ ] **HTML Export**: Generate and download HTML report
- [ ] **CSV Export**: Generate and download CSV data
- [ ] **JSON Export**: Generate and download JSON data
- [ ] **Report completeness**: Verify all sections included in exports

### 4. Issue Detection Testing
- [ ] **Payment method issues**: Test with expired/invalid payment methods
- [ ] **Failed scheduled actions**: Test subscriptions with failed renewal actions
- [ ] **Timeline discrepancies**: Test subscriptions with gaps in renewal history
- [ ] **Status inconsistencies**: Test subscriptions with unusual status transitions

### 5. Security Testing
- [ ] **Non-admin access**: Verify non-WooCommerce admins cannot access
- [ ] **CSRF protection**: Check nonces are working (inspect AJAX requests)
- [ ] **Input validation**: Try malicious inputs (SQL injection attempts, XSS)
- [ ] **Rate limiting**: Make rapid successive requests to test limits

### 6. Performance Testing
- [ ] **Large subscriptions**: Test with subscriptions having many related orders
- [ ] **Heavy timeline**: Test subscriptions with extensive note history
- [ ] **Multiple simultaneous analyses**: Test concurrent usage
- [ ] **Memory usage**: Monitor PHP memory consumption

### 7. Compatibility Testing
- [ ] **Different payment gateways**: Test with various payment methods
- [ ] **Plugin conflicts**: Test with other common WooCommerce plugins
- [ ] **Theme compatibility**: Test with different WordPress themes
- [ ] **Mobile responsiveness**: Test admin interface on mobile devices

## Common Test Scenarios

### Scenario 1: Healthy Active Subscription
- Create an active subscription with successful renewals
- Verify analysis shows "no issues detected"
- Check all timeline events are properly recorded

### Scenario 2: Subscription with Payment Issues
- Create subscription with expired payment method
- Verify issue detection flags payment problems
- Check timeline shows failed payment attempts

### Scenario 3: On-Hold Subscription
- Put a subscription on hold
- Verify expected behavior explains suspension
- Check timeline records status change

### Scenario 4: Cancelled Subscription
- Cancel an active subscription
- Verify no future renewals are expected
- Check cancellation is properly recorded

### Scenario 5: Switched Subscription
- Perform a subscription upgrade/downgrade
- Verify switch analysis detects changes
- Check switch logs are referenced

## Debugging Common Issues

### Plugin Won't Activate
```
Check WordPress debug log for errors:
- PHP version compatibility
- Missing WooCommerce/Subscriptions
- Plugin conflicts
```

### Analysis Fails
```
Check for:
- Valid subscription ID
- Database connectivity
- Action Scheduler functioning
- PHP memory limits
```

### Missing Timeline Events
```
Verify:
- WooCommerce logging is enabled
- Action Scheduler is working
- Subscription has activity history
```

### Export Problems
```
Check:
- PHP max_execution_time
- Memory limits
- File permissions
- Browser popup blockers
```

## Expected Results

### Successful Installation
- No PHP errors in logs
- Plugin appears in admin menu
- Search functionality works
- Basic analysis completes

### Successful Analysis
- All 3 steps complete without errors
- Relevant data appears in each section
- Issues are detected where appropriate
- Export functions work correctly

## Reporting Issues

If you encounter problems during testing:

1. **Gather Information**:
   - WordPress version
   - WooCommerce version
   - WooCommerce Subscriptions version
   - PHP version
   - Error messages (exact text)
   - Steps to reproduce

2. **Check Debug Logs**:
   - WordPress debug log
   - WooCommerce logs
   - Server error logs

3. **Report the Issue**:
   - GitHub: [https://github.com/davidrukahu/woosubs-troubleshooter/issues](https://github.com/davidrukahu/woosubs-troubleshooter/issues)
   - Include all gathered information
   - Specify if this affects core functionality

## Success Criteria

The plugin passes testing if:
- ✅ Installs and activates without errors
- ✅ Basic analysis workflow completes successfully
- ✅ Issues are detected appropriately
- ✅ Export functionality works
- ✅ No security vulnerabilities found
- ✅ Performance is acceptable
- ✅ Compatible with target WooCommerce versions

## Next Steps After Testing

1. **Production Deployment**: If tests pass, deploy to production
2. **User Training**: Train support team on using the tool
3. **Documentation**: Update any internal troubleshooting procedures
4. **Monitoring**: Monitor plugin performance in production
5. **Feedback Collection**: Gather feedback from support team usage

---

**Remember**: This is a troubleshooting tool designed to help diagnose subscription issues. It should complement, not replace, your existing support processes.
