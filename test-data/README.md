# 🧪 Test Data Generator

This folder contains tools to generate test subscription data for thoroughly testing the Dr Subs.

## 📁 Files

### `wcst-test-data-generator-plugin.php`
A simple, reliable test data generator that creates basic subscription test scenarios.

**Features:**
- ✅ Creates test customers with billing information
- ✅ Creates subscription products (monthly, yearly)
- ✅ Creates active subscriptions with different statuses
- ✅ Uses only stable WooCommerce core methods
- ✅ Compatible with WooCommerce Subscriptions 7.7.0+
- ✅ HPOS compatible

## 🚀 Installation & Usage

### Method 1: Plugin Installation (Recommended)
1. Upload `wcst-test-data-generator-plugin.php` to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Go to **Tools** → **WC Subscriptions Test Data**
4. Click **"Generate Test Data"**
5. Wait for completion message

### Method 2: Must-Use Plugin
1. Copy `wcst-test-data-generator-plugin.php` to `/wp-content/mu-plugins/`
2. Go to WordPress admin → **Tools** → **WC Subscriptions Test Data**
3. Click **"Generate Test Data"**

## 🎯 What Gets Created

**Test Customers (5):**
- John Doe, Jane Smith, Bob Johnson, Alice Brown, Charlie Wilson
- Complete billing information for each
- Mix of different email domains

**Subscription Products (3):**
- Monthly Newsletter ($9.99/month)
- Premium Support ($29.99/year) 
- Basic Service ($4.99/month)

**Test Subscriptions (5):**
- Various statuses: Active, Pending, On-Hold
- Different billing cycles and amounts
- Realistic customer assignments

## 🔧 Testing with Troubleshooter

After generating test data:

1. **Activate** the WC Subscriptions Troubleshooter
2. **Navigate** to WooCommerce → Subscriptions Troubleshooter
3. **Test subscription IDs** (typically starts from the highest ID)
4. **Analyze results** for each test scenario

## 🧹 Cleanup

To remove all test data:
1. Delete test subscriptions from WooCommerce → Subscriptions
2. Delete test products from Products → All Products  
3. Delete test customers from Users (if desired)

## ⚠️ Important Notes

- **Use only on test/development sites**
- **Backup your database before generating test data**
- **Test data uses Stripe in test mode by default**
- **Compatible with HPOS (High-Performance Order Storage)**

## 🆘 Troubleshooting

If you encounter any issues:
1. Check that WooCommerce Subscriptions is active
2. Ensure you have admin permissions
3. Check WordPress error logs for details
4. Verify WooCommerce Subscriptions is version 7.7.0+
