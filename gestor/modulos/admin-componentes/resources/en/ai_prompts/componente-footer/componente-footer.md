# 🔻 Footer Component - Complete Footer

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** component, footer, links, contact

## 📋 Description
Creates a complete footer component with information columns, links and contact data.

## 🎯 Objective
Generate a footer component organized in columns with useful links, contact information and social media.

## 📝 Input Parameters

### Required:
- **Company Name**: Company name or brand

### Optional:
- **Columns**: Number of content columns (2-4)
- **Links**: List of links organized by category
- **Social Media**: Social media links
- **Colors**: Background and text colors
- **Copyright**: Custom copyright text

## 🏗️ Component Structure

### Desktop Footer
```
┌─────────────────────────────────────┐
│ About     Links      Links   Contact│
│                                     │
│ Company   Link 1     Link 1  Email  │
│ description Link 2   Link 2  Phone  │
│            Link 3    Link 3  Addr   │
├─────────────────────────────────────┤
│ © 2025 Company | 🔗 🔗 🔗          │
└─────────────────────────────────────┘
```

### Mobile Footer
```
┌─────────────────────────────────────┐
│ About                               │
│ Company description                 │
│                                     │
│ Links                               │
│ Link 1, Link 2, Link 3             │
│                                     │
│ Contact                             │
│ Email, Phone, Addr                  │
├─────────────────────────────────────┤
│ © 2025 Company | 🔗 🔗 🔗          │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Responsive column layout
- Bottom section with copyright and social media
- Social media icons (inline SVG or Font Awesome via html-extra-head)
- Generally dark or contrasting colors
- Links with defined hover state
