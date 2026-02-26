# 🧩 Components - Component Generation

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** component, reusable, modular, html

## 📋 Description
Default prompt for creating reusable HTML components.

## 🎯 Objective
Generate a modular and reusable HTML component that can be included in any page or layout in the system.

## 📝 Input Parameters

### Required:
- **Component Type**: Type of component to create (e.g.: card, form, section, navigation)

### Optional:
- **Visual Style**: modern, minimalist, corporate
- **Colors**: Component color palette
- **Responsive**: Whether it should adapt to different screens

## 🏗️ Component Structure

### Basic Component
```
┌─────────────────────────────────────┐
│                                     │
│          HTML Content               │
│                                     │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Encapsulated and independent component
- Semantic and accessible HTML
- Scoped CSS (use specific classes)
- If head resources are needed, use ```html-extra-head ``` block
- Focus on reusability and modularity
