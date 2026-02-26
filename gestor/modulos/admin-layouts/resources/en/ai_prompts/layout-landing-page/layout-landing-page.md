# 🎯 Landing Page Layout - Conversion Page

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** layout, landing-page, conversion, marketing, sales

## 📋 Description
Creates a layout optimized for high-conversion landing pages focused on CTA (Call to Action).

## 🎯 Objective
Generate a complete HTML layout focused on conversion, with minimal navigation, highlight blocks and prominent calls to action.

## 📝 Input Parameters

### Required:
- **Name/Brand**: Company or product name
- **Main CTA**: Main conversion button text

### Optional:
- **Visual Style**: modern, minimalist, corporate, bold
- **Colors**: Color palette (primary and secondary)
- **Navigation**: With or without navigation menu

## 🏗️ Layout Structure

### Minimal Header
```
┌─────────────────────────────────────┐
│ Logo              CTA Button        │
└─────────────────────────────────────┘
```

### Full-Width Content
```
┌─────────────────────────────────────┐
│                                     │
│         @[[pagina#corpo]]@          │
│                                     │
└─────────────────────────────────────┘
```

### Minimal Footer
```
┌─────────────────────────────────────┐
│ © 2025 | Terms | Privacy            │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Conversion-focused design
- Minimal navigation to avoid distractions
- Large and visible CTA buttons
- Full-width layout for visual impact
- System variables: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
