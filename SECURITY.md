# Security Policy

## Supported Versions

We actively maintain and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 2.0.x   | :white_check_mark: |
| < 2.0   | :x:                |

## Reporting a Vulnerability

We take the security of our plugin seriously. If you believe you have found a security vulnerability, please report it to us responsibly.

### How to Report

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please send an email to: [your-email@domain.com]

Please include the following information in your report:

- A description of the vulnerability
- Steps to reproduce the vulnerability
- Any potential impact of the vulnerability
- Your contact information for follow-up questions

### What to Expect

After you submit a vulnerability report, we will:

1. **Acknowledge receipt** of your report within 48 hours
2. **Provide an initial assessment** within 5 business days
3. **Work with you** to understand and validate the issue
4. **Develop and test a fix** for confirmed vulnerabilities
5. **Release a security update** and publicly acknowledge your contribution (if desired)

### Security Best Practices

When using this plugin:

#### For Administrators
- Keep WordPress, WooCommerce, and all plugins updated to the latest versions
- Use strong, unique passwords for all user accounts
- Limit admin access to trusted users only
- Regularly review user permissions and access logs
- Enable two-factor authentication where possible

#### For Developers
- Follow WordPress security best practices
- Validate and sanitize all input data
- Use prepared statements for database queries
- Implement proper authentication and authorization
- Keep dependencies updated

### Plugin Security Features

This plugin implements several security measures:

#### Input Validation & Sanitization
- All user inputs are validated and sanitized using WordPress functions
- Subscription IDs are validated as positive integers
- Email addresses are validated using WordPress email validation
- Text inputs are sanitized using `sanitize_text_field()`

#### Authentication & Authorization
- All administrative functions require `manage_woocommerce` capability
- AJAX requests are protected with WordPress nonces
- Rate limiting prevents abuse of analysis functions
- User permissions are checked before any sensitive operations

#### Database Security
- All database queries use prepared statements
- No direct SQL injection points
- Database operations are limited to read-only for analysis
- Sensitive data is not stored in the database

#### Data Protection
- No sensitive customer data is logged unnecessarily
- Log retention periods are configurable and enforced
- Export functions only work for authorized users
- No external data transmission without explicit user action

### Vulnerability Disclosure Timeline

We aim to resolve security vulnerabilities promptly:

- **Critical vulnerabilities**: Patch within 7 days
- **High-severity vulnerabilities**: Patch within 14 days  
- **Medium-severity vulnerabilities**: Patch within 30 days
- **Low-severity vulnerabilities**: Address in next regular update

### Security Updates

Security updates will be:

1. Released as soon as possible after fix validation
2. Clearly marked as security releases in changelog
3. Accompanied by upgrade instructions if needed
4. Announced through appropriate channels

### Contact Information

For security-related inquiries:
- Email: [your-email@domain.com]
- GitHub: Create a security advisory (preferred for non-sensitive issues)

For general support:
- GitHub Issues: [https://github.com/davidrukahu/woosubs-troubleshooter/issues](https://github.com/davidrukahu/woosubs-troubleshooter/issues)

---

Thank you for helping keep WooCommerce Subscriptions Troubleshooter secure!
