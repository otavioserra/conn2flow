# 📊 Dashboard Layout - Admin Panel

**Version:** 1.0.0
**Date:** 2025-10-28
**Author:** Conn2Flow System
**Tags:** layout, dashboard, admin, panel, management

## 📋 Description
Creates a layout for an admin panel (dashboard) with sidebar navigation and main content area.

## 🎯 Objective
Generate a complete HTML layout for an admin panel with fixed sidebar, top bar and dynamic content area.

## 📝 Input Parameters

### Required:
- **System Name**: Name of the system or admin panel

### Optional:
- **Visual Style**: modern, corporate, minimalist, dark
- **Colors**: Color palette (primary and secondary)
- **Sidebar**: Fixed or collapsible
- **Menu Items**: List of main navigation items

## 🏗️ Layout Structure

### Top Bar
```
┌─────────────────────────────────────┐
│ ☰ System Name       🔔 👤 Admin    │
└─────────────────────────────────────┘
```

### Sidebar + Content
```
┌──────┬──────────────────────────────┐
│      │                              │
│ Menu │    @[[pagina#corpo]]@        │
│      │                              │
│ Item1│                              │
│ Item2│                              │
│ Item3│                              │
│      │                              │
└──────┴──────────────────────────────┘
```

## 🎨 Expected Style
- Fixed sidebar on the left with main navigation
- Top bar with user information and notifications
- Flexible content area to receive the page body
- Responsive design with collapsible sidebar on mobile
- System variables: <!-- pagina#titulo -->, <!-- pagina#css -->, <!-- pagina#js -->, @[[pagina#corpo]]@
