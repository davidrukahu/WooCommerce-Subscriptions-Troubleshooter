# Test Data Generator API Fix

## Problem
The original test data generator was using deprecated/non-existent WooCommerce Subscriptions API methods:
- `set_subscription_price()` - doesn't exist in WC Subscriptions 7.7.0
- `set_subscription_period()` - doesn't exist in WC Subscriptions 7.7.0  
- `set_subscription_period_interval()` - doesn't exist in WC Subscriptions 7.7.0
- Date formatting issues

## Solution
Updated the generator to use the correct WooCommerce Subscriptions 7.7.0+ API:

### ✅ **Fixed Product Creation**
```php
// OLD (broken):
$product->set_subscription_price( $data['price'] );
$product->set_subscription_period( $data['period'] );
$product->set_subscription_period_interval( $data['interval'] );

// NEW (working):
$product->update_meta_data( '_subscription_price', $data['price'] );
$product->update_meta_data( '_subscription_period', $data['period'] );
$product->update_meta_data( '_subscription_period_interval', $data['interval'] );
```

### ✅ **Fixed Date Formatting**
```php
// OLD (incorrect):
$subscription->set_date( 'start', $start_date );

// NEW (correct):
$subscription->set_date( 'start', date( 'Y-m-d H:i:s', $start_date ) );
```

### ✅ **Fixed Trial Handling**
```php
// OLD (deprecated methods):
$product->get_subscription_trial_length()

// NEW (correct metadata access):
$product->get_meta( '_subscription_trial_length' )
```

### ✅ **Enhanced Error Handling**
- Added try/catch blocks around each major operation
- Better error messages for debugging
- Validation that each step creates data successfully

## Installation of Fixed Version

### Method 1: Replace Existing File
1. Download the fixed `test-data-generator-fixed.php`
2. Replace your existing file in `/wp-content/mu-plugins/`
3. Rename to `wcst-test-data-generator.php`

### Method 2: Re-run Installer
1. Delete existing file from mu-plugins
2. Upload both `test-data-generator.php` and `install-test-data-generator.php` to WordPress root
3. Visit: `yoursite.com/install-test-data-generator.php?install=1`
4. Re-install the generator

## Testing the Fix

After updating:
1. Go to **WooCommerce → Test Data Generator**
2. Click **"Generate Test Subscription Data"**
3. Should complete without the `set_subscription_price()` error
4. Check **WooCommerce → Subscriptions** for created test subscriptions

## What the Fixed Version Creates

The corrected generator will create:
- ✅ **5 test customers** (customer1@test.com through customer5@test.com)
- ✅ **3 subscription products** with proper metadata
- ✅ **15-20 test subscriptions** with various scenarios
- ✅ **Realistic subscription issues** for troubleshooting testing

## API Compatibility Notes

This fix ensures compatibility with:
- **WooCommerce Subscriptions 7.7.0+**
- **High Performance Order Storage (HPOS)**
- **WooCommerce 9.8.5+**
- **Modern WordPress/WooCommerce CRUD methods**

## Future-Proofing

The updated code uses:
- Standard WooCommerce meta data methods
- Proper date formatting for MySQL
- Error handling for API changes
- Validation of created objects

This should work with future versions of WooCommerce Subscriptions as it uses the stable metadata API rather than potentially deprecated setter methods.

---

**Quick Fix Summary**: The issue was using non-existent API methods. The fix switches to using `update_meta_data()` with the correct meta keys that WooCommerce Subscriptions actually uses internally.
