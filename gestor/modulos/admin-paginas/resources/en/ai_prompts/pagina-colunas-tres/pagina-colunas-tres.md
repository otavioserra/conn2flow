# 🎯 Page with Columns - Grid Layout

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** page, columns, grid, cards, highlight

## 📋 Description
Creates a page with a single session containing 3 columns, where each column presents title, description and highlight image.

## 🎯 Objective
Generate a page organized in grid format that presents multiple information or services in a visually attractive and comparable way.

## 📝 Input Parameters

### Required:
- **Session Title**: Main title of the page (max. 60 characters)
- **Column 1**: Title, description and image
- **Column 2**: Title, description and image
- **Column 3**: Title, description and image

### Optional:
- **Column Height**: auto, equal, custom
- **Spacing**: small, medium, large
- **Alignment**: top, center, bottom
- **Hover Effects**: Visual effects when hovering mouse

## 🏗️ Page Structure

### Main Session with 3 Columns
```
┌─────────────────────────────────────┐
│        [SESSION TITLE]              │
└─────────────────────────────────────┘
┌─────────────┬─────────────┬─────────────┐
│   COLUMN 1  │   COLUMN 2  │   COLUMN 3  │
│             │             │             │
│ [IMAGE]     │ [IMAGE]     │ [IMAGE]     │
│             │             │             │
│ [TITLE]     │ [TITLE]     │ [TITLE]     │
│             │             │             │
│ [DESCRIPTION] │ [DESCRIPTION] │ [DESCRIPTION] │
│             │             │             │
│ [BUTTON]    │ [BUTTON]    │ [BUTTON]    │
└─────────────┴─────────────┴─────────────┘
```

## 📋 Creation Instructions

1. **Responsive Layout**: Columns adapt on mobile (1 column) and desktop (3 columns)
2. **Visual Consistency**: Same proportions and styles for all columns
3. **Information Hierarchy**: Titles > Images > Descriptions > Actions
4. **Accessibility**: Semantic structure and keyboard navigation

## 🎨 Practical Example

**Scenario: Services Page**
- **Column 1**: Web Development - "We create modern websites" + code image
- **Column 2**: UX/UI Design - "Intuitive interfaces" + mockup image
- **Column 3**: Consulting - "Process optimization" + meeting image

**Expected Result:**
Three side-by-side cards presenting services in an organized and visual way.

## ⚙️ Technical Metadata

- **CSS Framework**: Grid system of Fomantic-UI or TailwindCSS
- **Dependencies**: Conn2Flow images and buttons system
- **Limitations**: Fixed at 3 columns per session
- **Compatibility**: Responsive layout for all devices

---

*Perfect prompt for presentation of services, products or resources*