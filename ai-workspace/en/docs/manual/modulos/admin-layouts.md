# 🎨 Layouts Administration - User Manual

## What is Layouts Administration?

The **Layouts Admin** module provides advanced management of page layouts. While the Layouts module is for creating templates, Admin Layouts handles technical settings, system layouts, and configuration.

---

## 🎯 Getting Started

### Accessing Admin Layouts
1. On the Dashboard, find the **Admin Layouts** card
2. Click to open the module
3. You'll see all system layouts

> 🔒 This is an administrator area. You need admin permissions.

---

## 📋 Layout List

### What You'll See
For each layout:
- **ID** - Unique identifier
- **Name** - Display name
- **Type** - System or custom
- **Framework** - CSS framework used
- **Pages** - Number of pages using it
- **Actions** - Edit, delete

---

## 🔧 Layout Management

### System Layouts
- Core layouts for system pages
- Cannot be deleted
- Can be customized

### Custom Layouts
- Created by users
- Full control
- Can be deleted (if no pages use it)

---

## ⚙️ Technical Settings

### Layout Configuration
- **ID** - Must be unique
- **HTML Template** - Full page structure
- **CSS Framework** - Fomantic-UI, TailwindCSS, etc.
- **Head Content** - Meta tags, links
- **Script Content** - JavaScript files

### Required Variable
Every layout MUST include:
```html
@[[pagina#corpo]]@
```
This is where page content appears.

---

## ❓ FAQ

### Q: Can I delete a layout used by pages?
**A:** No, reassign pages first.

### Q: What's the difference from regular Layouts?
**A:** Admin Layouts is for technical management and system layouts.

### Q: How do I change a page's layout?
**A:** Use Admin Pages or the page editor.

---

## 💡 Best Practices

1. **Never remove @[[pagina#corpo]]@** - Pages won't display
2. **Test on all devices** - Ensure responsive design
3. **Backup before editing** - Especially system layouts
4. **Use consistent naming** - Easy to identify

---

## 🆘 Need Help?

- Check **Layouts** for creating layouts
- Check **Components** for reusable elements
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
