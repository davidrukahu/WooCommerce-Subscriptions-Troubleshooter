# 🚀 Quick Start: Testing WooCommerce Subscriptions Troubleshooter

Complete guide to get your plugin testing environment up and running in 5 minutes.

## 📋 Prerequisites Checklist

Before starting, ensure you have:
- ✅ WordPress test site (staging/localhost)
- ✅ WooCommerce installed and activated
- ✅ WooCommerce Subscriptions installed and activated  
- ✅ Stripe configured in **test mode**
- ✅ Admin access to the site

## 🎯 Step-by-Step Setup

### Step 1: Install the Troubleshooter Plugin (2 minutes)
1. **Upload Plugin**:
   - Use `wc-subscriptions-troubleshooter-v2.0.0.zip`
   - Go to **Plugins → Add New → Upload Plugin**
   - Install and activate

2. **Verify Installation**:
   - Check that **WooCommerce → Subscriptions Troubleshooter** appears in menu
   - No error messages on activation

### Step 2: Generate Test Data (2 minutes)
1. **Install Generator**:
   - Upload `test-data-generator.php` and `install-test-data-generator.php` to your WordPress root
   - Visit: `yoursite.com/install-test-data-generator.php?install=1`
   - Click "Install Test Data Generator"

2. **Generate Data**:
   - Go to **WooCommerce → Test Data Generator**
   - Click **"Generate Test Subscription Data"**
   - Wait 30-60 seconds for completion

### Step 3: Start Testing (1 minute)
1. **Access Troubleshooter**:
   - Go to **WooCommerce → Subscriptions Troubleshooter**

2. **Test Basic Functionality**:
   - Search for: `customer1@test.com`
   - Select any subscription from results
   - Click **"Start Troubleshooting"**
   - Verify all 3 steps complete successfully

## 🧪 Test Scenarios to Try

### **Quick Tests (5 minutes each)**

#### **Test 1: Healthy Subscription**
```
Search: customer1@test.com
Expected: Clean analysis, no issues detected
Focus: Verify basic functionality works
```

#### **Test 2: Payment Issues**
```
Search: customer2@test.com  
Expected: Payment method warnings, failed attempts
Focus: Test issue detection accuracy
```

#### **Test 3: Timeline Problems**
```
Search: customer3@test.com
Expected: Timeline gaps, missing events
Focus: Test timeline analysis logic
```

#### **Test 4: Export Functionality**
```
Use: Any analyzed subscription
Action: Test HTML, CSV, JSON exports
Focus: Verify export completeness
```

### **Advanced Tests (10 minutes each)**

#### **Test 5: Multiple Issue Detection**
```
Search: customer4@test.com
Expected: Multiple simultaneous issues
Focus: Test complex scenario handling
```

#### **Test 6: Action Scheduler Problems**
```
Search: customer5@test.com
Expected: Failed scheduled actions
Focus: Test cron job troubleshooting
```

#### **Test 7: Status Transitions**
```
Search: Find cancelled subscriptions
Expected: Proper cancellation analysis
Focus: Test status change logic
```

## 📊 Understanding Generated Data

### **Customer Profiles Created**
| Email | Scenarios | Focus Area |
|-------|-----------|------------|
| customer1@test.com | Healthy subscriptions | Baseline testing |
| customer2@test.com | Payment failures | Payment troubleshooting |
| customer3@test.com | Timeline gaps | History analysis |
| customer4@test.com | Multiple issues | Complex scenarios |
| customer5@test.com | Action scheduler | Automation problems |

### **Subscription Products Created**
1. **Monthly Subscription** ($29.99/month) - Basic recurring
2. **Annual Subscription** ($299.99/year) - Long-term billing
3. **Premium with Trial** ($49.99/month + trial) - Complex setup

### **Issue Types Simulated**
- 💳 **Payment Failures**: Declined cards, expired methods
- ⏰ **Scheduling Issues**: Failed actions, missed renewals  
- 📊 **Timeline Gaps**: Missing activity, data inconsistencies
- 🔄 **Status Problems**: Transition failures, stuck states
- 🔄 **Switches**: Plan upgrades/downgrades

## ✅ Success Criteria

### **Basic Functionality**
- [ ] Plugin installs without errors
- [ ] Search finds test subscriptions
- [ ] All 3 analysis steps complete
- [ ] Results display properly
- [ ] Export functions work

### **Issue Detection**
- [ ] Payment problems identified correctly
- [ ] Timeline gaps detected
- [ ] Status inconsistencies flagged
- [ ] Action scheduler issues found
- [ ] Appropriate severity levels assigned

### **User Experience**
- [ ] Interface is intuitive and responsive
- [ ] Progress indicators work smoothly
- [ ] Error messages are helpful
- [ ] Export formats are useful
- [ ] Mobile interface functions properly

## 🐛 Common Issues & Solutions

### **"No subscriptions found"**
- **Cause**: Test data not generated properly
- **Solution**: Re-run the test data generator
- **Check**: WooCommerce → Subscriptions for created subscriptions

### **"Analysis fails to complete"**
- **Cause**: PHP memory limits or timeouts
- **Solution**: Increase PHP memory to 256M+, execution time to 60s
- **Check**: WordPress debug logs for specific errors

### **"Missing timeline events"**
- **Cause**: Action Scheduler not functioning
- **Solution**: Check Tools → Scheduled Actions for proper operation
- **Check**: WooCommerce logs for scheduler errors

### **"Export downloads empty files"**
- **Cause**: PHP output buffering or permissions
- **Solution**: Check file permissions, disable output buffering
- **Check**: Browser developer tools for JavaScript errors

## 🎓 Training Your Team

### **For Support Staff**
1. **Demo the Tool**: Show the 3-step process
2. **Practice Scenarios**: Work through each test case
3. **Review Outputs**: Understand what each section means
4. **Export Training**: Show how to save and share reports

### **For Developers**
1. **Code Review**: Examine the plugin architecture
2. **Extension Points**: Understand hooks and filters
3. **Custom Analyzers**: Learn to add new analysis types
4. **Performance**: Monitor resource usage patterns

## 📈 Next Steps

### **After Successful Testing**
1. **Production Deployment**: Install on live support environment
2. **Team Training**: Train support staff on using the tool
3. **Process Integration**: Update troubleshooting workflows
4. **Monitoring**: Track usage and effectiveness

### **Ongoing Maintenance**
1. **Regular Updates**: Keep plugin updated with WooCommerce
2. **Test Data Refresh**: Regenerate test data periodically
3. **Feedback Collection**: Gather team feedback for improvements
4. **Performance Monitoring**: Watch for any performance impacts

## 🔧 Customization Options

### **Adding Custom Scenarios**
Edit the test data generator to include:
- Specific payment gateway issues
- Custom subscription products
- Unique timeline patterns
- Site-specific problems

### **Extending Analysis**
Develop custom analyzers for:
- Site-specific integrations
- Custom subscription modifications
- Third-party plugin interactions
- Unique business rules

---

## 🆘 Need Help?

### **Quick Debugging**
1. Check WordPress debug logs
2. Verify all plugins are updated
3. Test with minimal plugin setup
4. Review browser console for JavaScript errors

### **Getting Support**
- GitHub Issues: [woosubs-troubleshooter/issues](https://github.com/davidrukahu/woosubs-troubleshooter/issues)
- Include: WordPress version, WooCommerce version, error messages
- Provide: Steps to reproduce, expected vs actual behavior

**Remember**: This tool should complement, not replace, your existing support processes. Use it to speed up diagnosis and provide better documentation for complex subscription issues.
