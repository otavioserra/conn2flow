# 🎯 Services Page - Interactive Cards

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** page, services, cards, interactive, portfolio

## 📋 Description
Creates a services page with interactive cards presenting different solutions or products offered.

## 🎯 Objective
Generate a dynamic page that presents services attractively, with cards that can have hover effects, animations and calls to action.

## 📝 Input Parameters

### Required:
- **Page Title**: Main title of the services section
- **Services**: List of 3-6 services with title, description and icon/image

### Optional:
- **Layout**: grid, masonry, carousel
- **Animations**: fade, slide, scale, rotate
- **Filters**: Categories to filter services
- **Prices**: Values or "Quote" for each service

## 🏗️ Page Structure

### Header + Services Grid
```
┌─────────────────────────────────────┐
│      [SERVICES TITLE]               │
│                                     │
│   [INTRODUCTION DESCRIPTION]        │
└─────────────────────────────────────┘

┌─────────────┬─────────────┬─────────────┐
│   SERVICE   │   SERVICE   │   SERVICE   │
│      1      │      2      │      3      │
│             │             │             │
│ [ICON]      │ [ICON]      │ [ICON]      │
│             │             │             │
│ [TITLE]     │ [TITLE]     │ [TITLE]     │
│             │             │             │
│ [DESCRIPTION] │ [DESCRIPTION] │ [DESCRIPTION] │
│             │             │             │
│ [PRICE]     │ [PRICE]     │ [PRICE]     │
│             │             │             │
│ [BUTTON]    │ [BUTTON]    │ [BUTTON]    │
└─────────────┴─────────────┴─────────────┘
```

## 📋 Creation Instructions

1. **Consistent Cards**: Same size and style for all
2. **Interactivity**: Hover effects and smooth transitions
3. **Responsiveness**: Adaptable grid (1-2-3 columns)
4. **Performance**: Lazy loading for images and animations

## 🎨 Practical Example

**Development Services:**
1. **Web Design** - "Modern and responsive interfaces"
2. **Development** - "Robust and scalable applications"
3. **Consulting** - "Optimization and digital strategy"
4. **Maintenance** - "Continuous support and updates"

**Expected Result:**
Attractive page with animated cards that highlight each offered service.

## ⚙️ Technical Metadata

- **CSS Framework**: Card components and grid system
- **Dependencies**: Conn2Flow animation system
- **Limitations**: Maximum 12 services per page
- **Compatibility**: CSS animations and transitions

---

*Ideal prompt for portfolios and service catalog presentation*