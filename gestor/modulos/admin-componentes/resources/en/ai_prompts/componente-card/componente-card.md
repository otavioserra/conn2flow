# 🃏 Card Component - Content Card

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** component, card, content, product

## 📋 Description
Creates a card component for displaying content such as products, services or articles.

## 🎯 Objective
Generate a modular card component with image, title, description and action, reusable in different contexts.

## 📝 Input Parameters

### Required:
- **Card Type**: product, service, article, profile, testimonial

### Optional:
- **With Image**: Whether to include a featured image
- **Visual Style**: elevated (shadow), flat, with border
- **Action**: Action button or link (e.g.: "See more", "Buy")
- **Badge/Tag**: Highlight label (e.g.: "New", "Sale")

## 🏗️ Component Structure

### Card with Image
```
┌─────────────────────────────────────┐
│          [Image/Thumb]              │
│  Badge                              │
├─────────────────────────────────────┤
│  Card Title                         │
│  Brief description of the card      │
│  content with summary text.         │
│                                     │
│  [Action/Button]                    │
└─────────────────────────────────────┘
```

### Card without Image
```
┌─────────────────────────────────────┐
│  🎯 Icon                           │
│  Card Title                         │
│  Brief description of the card      │
│  content with summary text.         │
│                                     │
│  [Action/Button]                    │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Rounded corners
- Subtle shadow for elevation effect
- Hover with smooth transition
- Image with consistent aspect ratio
- Action button aligned to card footer
- Responsive (stackable in grids)
