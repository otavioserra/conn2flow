# 🎯 Basic Site Layout - Institutional Structure

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** layout, site, institutional, basic, header, footer

## 📋 Description
Creates a complete layout for an institutional website with navigation header, main content area and footer.

## 🎯 Objective
Generate a complete HTML layout with a professional structure for institutional websites, including responsive navigation, dynamic content area and informative footer.

## 📝 Input Parameters

### Required:
- **Site Name**: Name that will appear in the header (max. 40 characters)
- **Menu Items**: List of navigation items (3 to 6 items)

### Optional:
- **Logo**: URL or logo text
- **Primary Color**: Main site color
- **Footer**: Additional footer information
- **Social Media**: Links to profiles

## 🏗️ Layout Structure

### Header (Navigation)
```
┌─────────────────────────────────────┐
│ Logo    Menu Item 1 | Item 2 | ...  │
└─────────────────────────────────────┘
```

### Main Content
```
┌─────────────────────────────────────┐
│                                     │
│        @[[pagina#corpo]]@           │
│                                     │
└─────────────────────────────────────┘
```

### Footer
```
┌─────────────────────────────────────┐
│ © 2025 Site Name. All rights...     │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Clean and professional design
- Responsive navigation with hamburger menu on mobile
- Footer with contact information and copyright
- System variables: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
