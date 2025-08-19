# 🧪 WooCommerce Subscriptions Test Data Generator

A comprehensive tool for creating realistic subscription test data with various issues and scenarios. Perfect for testing the Subscriptions Troubleshooter plugin and training support teams.

## 🎯 What This Creates

### 👥 **5 Test Customers**
- `customer1@test.com` through `customer5@test.com`
- All passwords: `test123`
- Realistic names and profiles
- Ready for subscription testing

### 📦 **3 Subscription Products**
1. **Monthly Subscription** - $29.99/month
2. **Annual Subscription** - $299.99/year  
3. **Premium Plan with Trial** - $49.99/month + $9.99 setup fee + 14-day trial

### 🔄 **15-20 Test Subscriptions** with Realistic Issues

#### **Healthy Subscriptions** (30%)
- Active subscriptions with clean payment history
- Regular renewal patterns
- No timeline gaps or issues

#### **Payment Issues** (25%)
- Failed payment attempts with Stripe test mode
- Expired credit cards
- Payment retry scenarios
- Declined transactions

#### **Action Scheduler Problems** (20%)
- Failed scheduled renewal actions
- Missing renewal events
- Timing discrepancies
- Cron job failures

#### **Status Transition Issues** (15%)
- Subscriptions stuck in transitional states
- Unexpected status changes
- On-hold scenarios
- Cancellation edge cases

#### **Timeline Gaps** (10%)
- Missing activity periods
- Data inconsistencies
- Unexplained timeline jumps
- Historical data problems

## 🔧 Installation Methods

### Method 1: Easy Installer (Recommended)
1. Upload both `test-data-generator.php` and `install-test-data-generator.php` to your WordPress root
2. Visit: `yoursite.com/install-test-data-generator.php?install=1`
3. Follow the web interface instructions
4. Generator will be installed as a mu-plugin

### Method 2: Manual mu-plugin Installation
1. Upload `test-data-generator.php` to `/wp-content/mu-plugins/`
2. Rename to `wcst-test-data-generator.php`
3. It becomes automatically active

### Method 3: WP-CLI
```bash
# Upload the file first, then:
wp eval-file test-data-generator.php
wp wcst generate-test-data
```

### Method 4: Temporary functions.php
1. Copy contents of `test-data-generator.php`
2. Paste into your theme's `functions.php`
3. Use and remove when done

## 🚀 Usage

### WordPress Admin
1. Go to **WooCommerce → Test Data Generator**
2. Click **"Generate Test Subscription Data"**
3. Wait for completion (30-60 seconds)
4. Start testing with generated data

### WP-CLI
```bash
wp wcst generate-test-data
```

## 💳 Stripe Test Mode Integration

The generator creates subscriptions specifically designed for Stripe test mode:

### **Test Payment Methods**
- Generates Stripe test customer IDs: `cus_test_123456`
- Creates test payment sources: `card_test_789012`
- Compatible with Stripe webhook testing
- Simulates real payment scenarios safely

### **Test Card Numbers for Stripe**
Use these in your Stripe test environment:
- **Successful payments**: `4242424242424242`
- **Declined card**: `4000000000000002`
- **Insufficient funds**: `4000000000009995`
- **Expired card**: `4000000000000069`
- **Processing error**: `4000000000000119`

## 📊 Testing Scenarios Generated

### **Scenario 1: Healthy Active Subscription**
- **Status**: Active
- **Payment Method**: Stripe (test mode)
- **Issues**: None
- **Timeline**: Clean renewal history
- **Use Case**: Baseline for comparison

### **Scenario 2: Failed Payment Recovery**
- **Status**: On-hold → Active
- **Payment Method**: Stripe (test mode)
- **Issues**: Card declined, then updated
- **Timeline**: Shows payment failure and recovery
- **Use Case**: Test payment retry logic

### **Scenario 3: Action Scheduler Failure**
- **Status**: Active
- **Payment Method**: Stripe (test mode)
- **Issues**: Scheduled action failed to execute
- **Timeline**: Missing renewal event
- **Use Case**: Test cron job troubleshooting

### **Scenario 4: Expired Payment Method**
- **Status**: On-hold
- **Payment Method**: Stripe (test mode)
- **Issues**: Card expired 12/24
- **Timeline**: Shows expiry warning
- **Use Case**: Test payment method updates

### **Scenario 5: Timeline Gap**
- **Status**: Active
- **Payment Method**: Stripe (test mode)
- **Issues**: 2-week gap in recorded activity
- **Timeline**: Missing events period
- **Use Case**: Test gap detection logic

### **Scenario 6: Manual Renewal**
- **Status**: Active
- **Payment Method**: Manual
- **Issues**: Overdue payment
- **Timeline**: Manual payment reminders
- **Use Case**: Test manual renewal workflows

### **Scenario 7: Subscription Switch**
- **Status**: Active
- **Payment Method**: Stripe (test mode)
- **Issues**: Recent plan upgrade
- **Timeline**: Shows switch transaction
- **Use Case**: Test switching scenarios

### **Scenario 8: Pending Cancellation**
- **Status**: Pending-cancel
- **Payment Method**: Stripe (test mode)
- **Issues**: Customer requested cancellation
- **Timeline**: Shows cancellation request
- **Use Case**: Test end-of-period cancellations

## 🔍 Using Generated Data with Troubleshooter

### **Quick Tests**
1. **Search by Email**: Use `customer1@test.com` through `customer5@test.com`
2. **Search by ID**: Use any generated subscription ID
3. **Filter by Status**: Look for active, on-hold, cancelled subscriptions

### **Specific Issue Testing**
1. **Payment Problems**: Look for subscriptions with Stripe test failures
2. **Action Scheduler**: Find subscriptions with failed scheduled actions
3. **Timeline Analysis**: Examine subscriptions with activity gaps
4. **Status Transitions**: Review subscriptions with unusual status changes

### **Export Testing**
1. Run analysis on any generated subscription
2. Test HTML, CSV, and JSON exports
3. Verify all data appears correctly in exports
4. Check that issue summaries are accurate

## ⚠️ Safety Features

### **Production Protection**
- Automatically detects production environments
- Refuses to run on non-test domains
- Includes multiple safety checks
- Clear warning messages

### **Test Environment Detection**
✅ **Allowed environments**:
- `localhost`
- `127.0.0.1`
- `*.local`, `*.test`, `*.dev`
- `staging.*`, `test.*`

❌ **Blocked environments**:
- `*.com`, `*.org`, `*.net` (unless clearly test)
- Any production-looking domain

### **Data Safety**
- Only creates test data, never modifies existing
- Uses clearly marked test email addresses
- Creates obvious test customer names
- All Stripe data uses test mode identifiers

## 🧹 Cleanup

### **Removing Test Data**
To clean up generated test data:

```sql
-- Remove test customers (BE CAREFUL!)
DELETE FROM wp_users WHERE user_email LIKE 'customer%@test.com';

-- Remove test subscriptions
DELETE FROM wp_posts WHERE post_type = 'shop_subscription' 
AND post_author IN (SELECT ID FROM wp_users WHERE user_email LIKE '%@test.com');

-- Remove test orders
DELETE FROM wp_posts WHERE post_type = 'shop_order' 
AND post_author IN (SELECT ID FROM wp_users WHERE user_email LIKE '%@test.com');
```

### **Removing Generator**
- Delete from `mu-plugins` folder
- Or remove from `functions.php`
- Delete installer files from site root

## 🔧 Customization

### **Adding More Scenarios**
Edit the `$scenarios` array in `create_test_subscriptions()`:

```php
$scenarios[] = array(
    'status' => 'your_status',
    'payment_method' => 'your_gateway',
    'issues' => array( 'your_custom_issue' )
);
```

### **Custom Issue Types**
Add new issue creators in `create_subscription_issues()`:

```php
private function create_your_custom_issue( $subscription ) {
    // Your custom issue logic
    $subscription->add_order_note( 'Your custom issue description' );
}
```

### **Different Payment Gateways**
Modify payment method assignments:

```php
// Add PayPal, manual, or other gateways
array( 'status' => 'active', 'payment_method' => 'paypal', 'issues' => array() )
```

## 📈 Performance Notes

### **Generation Time**
- **5 customers**: ~2 seconds
- **3 products**: ~3 seconds  
- **15-20 subscriptions**: ~30-45 seconds
- **Issue simulation**: ~10-15 seconds
- **Total**: 45-60 seconds

### **Database Impact**
- Creates ~50-75 database records
- Minimal storage footprint
- No performance impact on existing data
- Safe for staging environments

## 🐛 Troubleshooting Generator Issues

### **"Requirements Not Met"**
- Verify WooCommerce is active
- Verify WooCommerce Subscriptions is active
- Check user permissions (need admin access)

### **"Cannot Run on Production"**
- Generator detected non-test environment
- Use on localhost, staging, or .test domains only
- Override not recommended for safety

### **Generation Fails**
- Check PHP memory limits (increase to 256M+)
- Verify database write permissions
- Check for plugin conflicts
- Review WordPress debug logs

### **Missing Subscriptions**
- Check WooCommerce → Subscriptions admin
- Verify customer accounts were created
- Check subscription status filters
- Look for error messages in logs

## 📞 Support

### **For Generator Issues**
- Check WordPress debug logs
- Verify all requirements are met
- Test with minimal plugin setup
- Review database permissions

### **For Troubleshooter Integration**
- Generated data should work immediately
- All subscriptions have realistic scenarios
- Use customer emails for easy searching
- Check that test data matches real patterns

---

**Remember**: This tool is for testing environments only. Always test the troubleshooter thoroughly before deploying to production support workflows.
