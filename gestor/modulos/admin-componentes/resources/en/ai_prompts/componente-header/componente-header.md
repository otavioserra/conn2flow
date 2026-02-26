# 🔝 Header Component - Navigation Header

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** component, header, navigation, menu

## 📋 Description
Creates a header component with logo and responsive navigation menu.

## 🎯 Objective
Generate a complete header component with logo, navigation menu and mobile version with hamburger menu.

## 📝 Input Parameters

### Required:
- **Name/Logo**: Brand name or logo text

### Optional:
- **Menu Items**: List of navigation links
- **Visual Style**: transparent, solid, with shadow, fixed on top
- **Colors**: Background and text colors
- **CTA**: Highlight button in header (e.g.: "Contact", "Get Started")

## 🏗️ Component Structure

### Desktop Header
```
┌─────────────────────────────────────┐
│ Logo    Menu1 Menu2 Menu3   [CTA]   │
└─────────────────────────────────────┘
```

### Mobile Header
```
┌─────────────────────────────────────┐
│ Logo                          ☰     │
├─────────────────────────────────────┤
│ Menu1                               │
│ Menu2                               │
│ Menu3                               │
│ [CTA]                               │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Responsive navigation with hamburger menu for mobile
- Logo on the left, navigation on the right
- Optional sticky menu support
- CSS with specific classes to avoid conflicts
- If using JavaScript for menu toggle, include it in ```html-extra-head ``` block or inline
