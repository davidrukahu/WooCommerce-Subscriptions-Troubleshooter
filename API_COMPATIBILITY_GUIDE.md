# WooCommerce Subscriptions 7.7.0+ API Compatibility Guide

## The Problem
WooCommerce Subscriptions 7.7.0 changed/removed many API methods that were used in older versions. This causes "Call to undefined method" errors when using deprecated methods.

## Common Broken Methods in 7.7.0+

### ❌ **Deprecated Methods (Don't Exist in 7.7.0+)**
```php
// Product methods that DON'T work:
$product->get_subscription_period()           // ❌ Doesn't exist
$product->get_subscription_period_interval()  // ❌ Doesn't exist
$product->set_subscription_price()            // ❌ Doesn't exist
$product->set_subscription_period()           // ❌ Doesn't exist
$product->set_subscription_period_interval()  // ❌ Doesn't exist
$product->get_subscription_trial_length()     // ❌ Doesn't exist

// Subscription methods that DON'T work:
$subscription->set_date( 'start', $date )     // ❌ Doesn't exist
$subscription->set_date( 'next_payment', $date ) // ❌ Doesn't exist
$subscription->set_date( 'trial_end', $date ) // ❌ Doesn't exist
$subscription->set_date( 'last_payment', $date ) // ❌ Doesn't exist
```

### ✅ **Correct Methods for 7.7.0+**
```php
// Product metadata methods:
$product->get_meta( '_subscription_period' )          // ✅ Works
$product->get_meta( '_subscription_period_interval' ) // ✅ Works
$product->update_meta_data( '_subscription_price', $price )    // ✅ Works
$product->update_meta_data( '_subscription_period', $period )  // ✅ Works
$product->update_meta_data( '_subscription_period_interval', $interval ) // ✅ Works
$product->get_meta( '_subscription_trial_length' )    // ✅ Works

// Subscription date methods:
$subscription->update_dates( array(                    // ✅ Works
    'start' => 'Y-m-d H:i:s',
    'next_payment' => 'Y-m-d H:i:s',
    'trial_end' => 'Y-m-d H:i:s',
    'last_payment' => 'Y-m-d H:i:s'
) );
```

## Fixed Test Data Generator

I've created multiple fixed versions:

### **Version 1: Full Featured (test-data-generator-fixed-v2.php)**
- Complete generator with all scenarios
- Uses correct WC Subscriptions 7.7.0+ API
- Creates 15-20 subscriptions with various issues

### **Version 2: Simple Plugin (wcst-test-data-generator-plugin.php)**
- Minimal, bulletproof approach
- Creates just basic test data
- Less likely to break with API changes
- Good for initial testing

## Installation Options

### **Option 1: Use Simple Plugin (Recommended for Testing)**
1. Upload `wcst-test-data-generator-plugin.php` to `/wp-content/plugins/`
2. Activate in **Plugins** menu
3. Go to **WooCommerce → Simple Test Data**
4. Generate basic test data

### **Option 2: Use Fixed Full Generator**
1. Upload `test-data-generator-fixed-v2.php` to `/wp-content/mu-plugins/`
2. Rename to `wcst-test-data-generator.php`
3. Go to **WooCommerce → Test Data Generator**
4. Generate comprehensive test data

## Key API Changes in WC Subscriptions 7.7.0+

### **Product Creation**
```php
// OLD (Broken):
$product = new WC_Product_Subscription();
$product->set_subscription_price( '29.99' );

// NEW (Working):
$product = new WC_Product_Subscription();
$product->update_meta_data( '_subscription_price', '29.99' );
```

### **Subscription Creation**
```php
// OLD (Broken):
wcs_create_subscription( array(
    'billing_period' => $product->get_subscription_period(),
    'billing_interval' => $product->get_subscription_period_interval(),
) );

// NEW (Working):
wcs_create_subscription( array(
    'billing_period' => $product->get_meta( '_subscription_period' ) ?: 'month',
    'billing_interval' => $product->get_meta( '_subscription_period_interval' ) ?: 1,
) );
```

### **Metadata Keys Reference**
```php
// Core subscription product metadata:
'_subscription_price'            // Recurring price
'_subscription_period'           // month, year, week, day
'_subscription_period_interval'  // 1, 2, 3, etc.
'_subscription_length'           // 0 = never expires
'_subscription_trial_length'     // Trial duration
'_subscription_trial_period'     // Trial period (day, week, month)
'_subscription_sign_up_fee'      // One-time setup fee
```

## Alternative: Manual Test Data Creation

If the generators still fail, you can create test data manually:

### **Manual Subscription Product**
1. Go to **Products → Add New**
2. Set product type to **"Subscription"**
3. Set price and billing schedule
4. Publish

### **Manual Test Subscription**
1. Go to **WooCommerce → Subscriptions → Add Subscription**
2. Select customer and product
3. Set dates and payment method
4. Save

## Debugging API Issues

### **Check WooCommerce Subscriptions Version**
```php
if ( class_exists( 'WC_Subscriptions' ) ) {
    echo WC_Subscriptions::$version;
}
```

### **Test Available Methods**
```php
$product = new WC_Product_Subscription();
if ( method_exists( $product, 'get_subscription_period' ) ) {
    echo "Old API available";
} else {
    echo "Use metadata API";
}
```

### **Enable WordPress Debug**
Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Next Steps

1. **Try Simple Plugin First**: Use `wcst-test-data-generator-plugin.php` for basic test data
2. **Test Troubleshooter**: Use generated subscriptions to test the troubleshooter plugin
3. **Report Issues**: If problems persist, check the error logs for specific method names

The key takeaway is that WooCommerce Subscriptions 7.7.0+ uses a metadata-based API instead of dedicated getter/setter methods for subscription properties.
