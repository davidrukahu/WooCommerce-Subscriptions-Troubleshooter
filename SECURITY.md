# Security Documentation

## Overview

The WooCommerce Subscriptions Troubleshooter plugin implements comprehensive security measures following WordPress security best practices. This document outlines the security features and measures in place.

## Security Principles

Our security implementation follows these core principles:

1. **Never trust user input** - All input is validated and sanitized
2. **Escape as late as possible** - Output is escaped just before display
3. **Escape everything from untrusted sources** - Database data, user input, third-party APIs
4. **Never assume anything** - Always validate and verify
5. **Sanitation is okay, but validation/rejection is better** - We prefer to reject invalid data

## Security Features

### 1. Input Validation and Sanitization

#### Subscription ID Validation
- Validates that subscription ID is a positive integer
- Verifies subscription exists in database
- Checks user permissions to access the subscription
- Sanitizes input using `sanitize_text_field()`

```php
$subscription_id = WCST_Security::validate_subscription_id($_POST['subscription_id']);
```

#### Search Term Validation
- Minimum length validation (2 characters)
- Maximum length validation (100 characters)
- Removes potentially dangerous characters (`<>"'`)
- Sanitizes input using `sanitize_text_field()`

```php
$search_term = WCST_Security::validate_search_term($_POST['search_term']);
```

#### Filter Validation
- Validates filter types against allowed list
- Sanitizes filter values based on type
- Validates date ranges and status values

```php
$filters = WCST_Security::validate_filters($_POST['filters']);
```

#### Settings Validation
- Validates settings against allowed options
- Type checking for boolean, integer, and string values
- Range validation for numeric values

```php
$settings = WCST_Security::validate_settings($_POST['settings']);
```

### 2. Output Escaping

All output is properly escaped using WordPress escaping functions:

#### HTML Escaping
```php
$data = WCST_Security::escape_html($data);
```

#### JavaScript Escaping
```php
$data = WCST_Security::escape_js($data);
```

#### Attribute Escaping
```php
$data = WCST_Security::escape_attr($data);
```

#### URL Escaping
```php
$data = WCST_Security::escape_url($data);
```

### 3. Authentication and Authorization

#### Nonce Verification
- All AJAX requests require valid nonces
- Nonces are verified using `wp_verify_nonce()`
- Security events are logged for failed nonce attempts

```php
WCST_Security::verify_nonce($_POST['nonce'], 'wcst_nonce');
```

#### Permission Checks
- All operations require `manage_woocommerce` capability
- Subscription access is restricted to owners or administrators
- Security events are logged for permission violations

```php
WCST_Security::check_permissions('manage_woocommerce');
```

### 4. Rate Limiting

- Maximum 10 requests per minute per user per action
- Uses WordPress transients for rate limiting
- Prevents abuse and DoS attacks

```php
WCST_Security::check_rate_limit('analyze_subscription');
```

### 5. Security Logging

All security events are logged for monitoring and analysis:

- Missing or invalid nonces
- Permission violations
- Rate limit violations
- Invalid input attempts

```php
WCST_Security::log_security_event('invalid_nonce', array('action' => $action));
```

### 6. File Security

#### Log File Protection
- Log files are protected with `.htaccess` rules
- Direct access to log files is denied
- Logs are stored outside web-accessible directories when possible

#### Plugin File Protection
- All PHP files include `ABSPATH` checks
- Direct access to plugin files is prevented

```php
if (!defined('ABSPATH')) {
    exit;
}
```

### 7. Database Security

#### Prepared Statements
- All database queries use prepared statements
- SQL injection is prevented through proper escaping
- User input is never directly inserted into queries

#### Data Validation
- All data is validated before database operations
- Invalid data is rejected rather than sanitized
- Database operations are wrapped in try-catch blocks

### 8. AJAX Security

#### Request Validation
- All AJAX requests are validated
- Input is sanitized and validated
- Output is properly escaped

#### Error Handling
- Generic error messages prevent information disclosure
- Detailed errors are logged but not shown to users
- Security events are tracked and monitored

## Security Checklist

### Input Validation
- [x] All user input is validated
- [x] Input length limits are enforced
- [x] Input type validation is implemented
- [x] Dangerous characters are filtered

### Output Escaping
- [x] All output is escaped
- [x] Context-appropriate escaping is used
- [x] Data is escaped as late as possible

### Authentication
- [x] Nonces are used for all forms
- [x] Nonces are verified on all requests
- [x] User permissions are checked

### Authorization
- [x] Capability checks are implemented
- [x] Resource access is restricted
- [x] Subscription ownership is verified

### Rate Limiting
- [x] AJAX requests are rate limited
- [x] Rate limits are per-user and per-action
- [x] Rate limit violations are logged

### Logging
- [x] Security events are logged
- [x] Logs are protected from direct access
- [x] Log rotation is implemented

### File Security
- [x] Direct file access is prevented
- [x] Log files are protected
- [x] Plugin files include security checks

### Database Security
- [x] Prepared statements are used
- [x] SQL injection is prevented
- [x] Data validation is implemented

## Security Best Practices

### For Developers

1. **Always validate input** - Never trust user data
2. **Escape output** - Use appropriate escaping functions
3. **Check permissions** - Verify user capabilities
4. **Use nonces** - Protect against CSRF attacks
5. **Log security events** - Monitor for suspicious activity
6. **Rate limit requests** - Prevent abuse
7. **Keep dependencies updated** - Patch security vulnerabilities

### For Administrators

1. **Keep WordPress updated** - Install security patches
2. **Use strong passwords** - Implement password policies
3. **Limit user permissions** - Follow principle of least privilege
4. **Monitor logs** - Review security event logs regularly
5. **Backup regularly** - Maintain secure backups
6. **Use HTTPS** - Encrypt all communications

## Security Monitoring

### Log Analysis

The plugin logs security events that should be monitored:

- **Missing nonces** - May indicate CSRF attempts
- **Invalid nonces** - May indicate session issues or attacks
- **Permission violations** - May indicate privilege escalation attempts
- **Rate limit violations** - May indicate automated attacks
- **Invalid input** - May indicate injection attempts

### Recommended Monitoring

1. **Daily log review** - Check for unusual patterns
2. **Failed authentication attempts** - Monitor for brute force attacks
3. **Permission violations** - Check for unauthorized access attempts
4. **Rate limit violations** - Monitor for automated abuse
5. **Input validation failures** - Check for injection attempts

## Reporting Security Issues

If you discover a security vulnerability in this plugin:

1. **Do not disclose publicly** - Report privately first
2. **Contact the developer** - Use secure communication
3. **Provide details** - Include steps to reproduce
4. **Allow time for fix** - Give reasonable time for response
5. **Coordinate disclosure** - Work together on public disclosure

## Security Updates

This plugin follows WordPress security update practices:

- **Regular security reviews** - Code is reviewed for vulnerabilities
- **Dependency updates** - Third-party libraries are kept updated
- **Security patches** - Critical issues are patched immediately
- **Version numbering** - Security updates increment version numbers appropriately

## Compliance

This plugin is designed to comply with:

- **WordPress Coding Standards** - Follows WordPress security guidelines
- **OWASP Top 10** - Addresses common web application vulnerabilities
- **GDPR Requirements** - Protects user data and privacy
- **PCI DSS** - Secure handling of payment-related data

## Conclusion

Security is a continuous process, not a one-time implementation. This plugin implements comprehensive security measures but should be used as part of a broader security strategy that includes:

- Regular security audits
- User training and awareness
- System monitoring and alerting
- Incident response planning
- Regular backups and recovery testing

For additional security information, refer to:
- [WordPress Security Documentation](https://wordpress.org/support/article/hardening-wordpress/)
- [OWASP Security Guidelines](https://owasp.org/www-project-top-ten/)
- [WooCommerce Security Best Practices](https://docs.woocommerce.com/document/security-best-practices/) 