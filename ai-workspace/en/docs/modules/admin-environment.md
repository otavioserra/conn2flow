# Module: admin-environment

## 📋 General Information

| Field | Value |
|-------|-------|
| **Module ID** | `admin-environment` |
| **Name** | Environment Administration |
| **Version** | `1.0.0` |
| **Category** | Administrative Module |
| **Complexity** | 🟡 Medium |
| **Status** | ✅ Active |
| **Dependencies** | `interface`, `html` |

## 🎯 Purpose

The **admin-environment** module provides **system testing and debugging tools** for Conn2Flow administrators. It includes utilities for testing email configurations, reCAPTCHA settings, and other environment-specific features.

## 🏗️ Main Features

### 🔧 **Environment Testing**
- **Email tests**: Verify SMTP configuration
- **reCAPTCHA tests**: Validate captcha settings
- **Debug mode**: Enable/disable debug output
- **System info**: View environment details

### 📧 **Email Testing**
- Send test emails
- Verify SMTP settings
- Check email delivery
- Debug email errors

### 🛡️ **reCAPTCHA Testing**
- Test v3 reCAPTCHA integration
- Verify site and secret keys
- Check score thresholds
- Debug captcha responses

## 📁 File Structure

```
gestor/modulos/admin-environment/
├── admin-environment.php        # Main module controller
├── admin-environment.js         # Client-side functionality
├── admin-environment.json       # Module configuration
└── resources/
    ├── en/
    │   └── pages/
    │       └── admin-environment/
    └── pt-br/
        └── pages/
            └── admin-environment/
```

## 🔧 Testing Features

### Email Test
```php
// Test email configuration
$result = testEmailConfiguration([
    'to' => 'test@example.com',
    'subject' => 'Test Email',
    'message' => 'This is a test email.'
]);

// Returns success/error with details
```

### reCAPTCHA Test
```php
// Test reCAPTCHA configuration
$result = testRecaptcha($token);

// Returns score and validation status
```

## 🎨 User Interface

### Environment Dashboard
- System information display
- Email test form
- reCAPTCHA test button
- Debug toggle
- Configuration status indicators

### Test Results
- Success/error messages
- Detailed debug output
- Score displays (for reCAPTCHA)
- Error explanations

## 🔐 Security

- Only accessible to host administrators
- Debug mode should be disabled in production
- Sensitive info is masked in output
- Test results are not logged

## ⚙️ Configuration Variables

| Variable | Description |
|----------|-------------|
| `email-tests-success-msg` | Success message for email test |
| `email-tests-error-msg` | Error message for email test |
| `recaptcha-test-success` | reCAPTCHA success message |
| `recaptcha-test-low-score` | Low score warning |
| `recaptcha-error-*` | Various error messages |

## 💡 Use Cases

### Troubleshooting Email
1. Access admin-environment
2. Enter test email address
3. Click "Send Test Email"
4. Check results for errors
5. Verify email in inbox

### Validating reCAPTCHA
1. Access admin-environment
2. Click "Test reCAPTCHA"
3. Check returned score
4. Verify configuration if score is low

## 🔗 Related Modules
- `perfil-usuario`: User authentication testing
- `admin-plugins`: Plugin environment tests
