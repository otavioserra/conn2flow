# 🎯 Blog Layout - Content with Sidebar

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** layout, blog, sidebar, articles, content

## 📋 Description
Creates a blog layout with header, main content area, lateral sidebar and footer.

## 🎯 Objective
Generate a complete HTML layout optimized for blogs and content portals, with sidebar for widgets, categories and complementary information.

## 📝 Input Parameters

### Required:
- **Blog Name**: Name that will appear in the header
- **Menu Items**: List of navigation items

### Optional:
- **Sidebar Position**: left, right (default: right)
- **Sidebar Widgets**: search, categories, recent posts, tags
- **Sidebar Width**: narrow, medium, wide

## 🏗️ Layout Structure

### Header
```
┌─────────────────────────────────────┐
│ Blog Logo     Navigation Menu       │
└─────────────────────────────────────┘
```

### Main Area + Sidebar
```
┌──────────────────────┬──────────────┐
│                      │   Sidebar    │
│  @[[pagina#corpo]]@  │  - Search    │
│                      │  - Categories│
│                      │  - Recent    │
└──────────────────────┴──────────────┘
```

### Footer
```
┌─────────────────────────────────────┐
│ About | Contact | Social Media      │
└─────────────────────────────────────┘
```

## 🎨 Expected Style
- Design optimized for reading
- Sidebar with functional widgets
- Clear and readable typography
- Responsive layout (sidebar collapses on mobile)
- System variables: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
