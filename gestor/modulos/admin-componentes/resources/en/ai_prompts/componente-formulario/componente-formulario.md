# 📝 Form Component - Contact/Registration Form

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** component, form, contact, registration

## 📋 Description
Creates a complete and functional form component for contact, registration or data collection.

## 🎯 Objective
Generate an accessible and styled HTML form component with visual validation and responsive layout.

## 📝 Input Parameters

### Required:
- **Form Type**: contact, registration, login, newsletter, survey

### Optional:
- **Fields**: List of custom fields
- **Visual Style**: modern, minimalist, card-style, floating
- **Validation**: Whether to include visual field validation
- **Submit Button**: Submit button text

## 🏗️ Component Structure

### Contact Form
```
┌─────────────────────────────────────┐
│  📝 Form Title                      │
│  Subtitle or instruction            │
│                                     │
│  ┌─────────────┐ ┌───────────────┐  │
│  │ Name         │ │ Email         │  │
│  └─────────────┘ └───────────────┘  │
│  ┌─────────────────────────────────┐│
│  │ Subject                         ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Message                         ││
│  │                                  ││
│  │                                  ││
│  └─────────────────────────────────┘│
│                                     │
│  [Send Message]                     │
└─────────────────────────────────────┘
```

### Login Form
```
┌─────────────────────────────────────┐
│  🔐 Sign In                        │
│                                     │
│  ┌─────────────────────────────────┐│
│  │ Email                           ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Password                        ││
│  └─────────────────────────────────┘│
│  □ Remember me    Forgot password  │
│                                     │
│  [Sign In]                          │
│                                     │
│  Don't have an account? Sign up     │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Floating labels or above fields
- Focus, error and success states on inputs
- Styled and prominent submit button
- Responsive layout (side-by-side fields on desktop, stacked on mobile)
- Accessibility: associated labels, aria-attributes
- If validation scripts are needed, include in ```html-extra-head ``` block or inline
