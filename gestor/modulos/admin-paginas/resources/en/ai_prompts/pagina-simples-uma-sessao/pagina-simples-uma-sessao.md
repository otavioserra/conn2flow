# 🎯 Simple Page - One Complete Session

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** page, simple, hero, highlight

## 📋 Description
Creates a page with a single hero session containing title, description and highlight image.

## 🎯 Objective
Generate a simple and impactful page with a main session that presents content clearly and visually attractive.

## 📝 Input Parameters

### Required:
- **Page Title**: Main title of the session (max. 60 characters)
- **Description**: Descriptive text of the session (max. 200 characters)
- **Highlight Image**: URL or path of the main image

### Optional:
- **Background Color**: Hexadecimal color for the session background
- **Text Alignment**: left, center, right
- **Session Height**: small, medium, large, full

## 🏗️ Page Structure

### Main Session (Hero)
```
┌─────────────────────────────────────┐
│        [HIGHLIGHT IMAGE]            │
│                                     │
│        [MAIN TITLE]                 │
│                                     │
│     [DETAILED DESCRIPTION]          │
│                                     │
│        [ACTION BUTTON]              │
└─────────────────────────────────────┘
```

## 📋 Creation Instructions

1. **HTML Structure**: Use classes from the active CSS framework
2. **Responsiveness**: Ensure functionality on mobile and desktop
3. **Accessibility**: Include alt texts and semantic structure
4. **Performance**: Optimize images and loading

## 🎨 Practical Example

**Input:**
- Title: "Welcome to Our Company"
- Description: "We are specialists in innovative digital solutions"
- Image: "/assets/images/hero-company.jpg"

**Expected Result:**
Page with a centered hero session, background image, highlighted title and clear description.

## ⚙️ Technical Metadata

- **CSS Framework**: Compatible with Fomantic-UI and TailwindCSS
- **Dependencies**: Conn2Flow assets system
- **Limitations**: Maximum 1 session per page
- **Compatibility**: Modern browsers

---

*Prompt optimized for quick creation of simple landing pages*