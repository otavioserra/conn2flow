# 📄 Page Administration - User Manual

## What is Page Administration?

The **Page Administration** (Admin Pages) module provides advanced control over all pages in your Conn2Flow system. While the Publisher is for creating content, Admin Pages is for managing page settings, metadata, paths, and technical configurations.

---

## 🎯 Getting Started

### Accessing Admin Pages
1. On the Dashboard, find the **Admin Pages** card
2. Click to open the module
3. You'll see all system pages

> 🔒 This is an administrator area. You need admin permissions.

---

## 📋 Page List

### What You'll See
For each page:
- **Title** - Page name
- **Path** - URL path
- **Layout** - Template used
- **Module** - Associated module
- **Type** - System or custom
- **Status** - Active/Inactive
- **Actions** - Edit, delete

### Filtering
- **Search** - Find by title or path
- **Type** - System, custom, etc.
- **Module** - Filter by associated module
- **Layout** - Filter by template

---

## ⚙️ Page Settings

### Basic Settings
| Setting | Description |
|---------|-------------|
| **Title** | Display name |
| **Path** | URL path (e.g., "about-us/") |
| **Layout** | Visual template |
| **Module** | Associated functionality |

### Advanced Settings
| Setting | Description |
|---------|-------------|
| **Type** | System or custom page |
| **Root** | Is this a root page? |
| **Option** | Special behaviors |
| **Version** | Page version number |

---

## 🔗 Path Management

### URL Structure
Paths define where your page lives:
- `about/` → yoursite.com/about/
- `services/web-design/` → yoursite.com/services/web-design/

### Path Rules
- Use lowercase
- Use hyphens for spaces
- End with `/`
- Keep short and descriptive
- Avoid special characters

---

## 🎨 Layout Assignment

### Changing a Page's Layout
1. Edit the page
2. Select new layout from dropdown
3. Save changes

### Layout Considerations
- Layout defines the overall structure
- Page content goes into the layout
- Test after changing layouts

---

## 📦 Module Association

### What is Module Association?
Pages can be linked to modules for functionality:
- Dashboard page → Dashboard module
- Users page → Users module
- Custom page → No module (content only)

### Changing Module
1. Edit the page
2. Select module from dropdown
3. Save changes

---

## 🔒 System vs Custom Pages

### System Pages
- Core to Conn2Flow operation
- Cannot be deleted
- Path may be restricted
- Examples: Dashboard, Login

### Custom Pages
- Created by users
- Full control over settings
- Can be deleted
- Examples: About, Contact

---

## ❓ FAQ

### Q: Can I delete a system page?
**A:** No, system pages are protected. You can only deactivate them.

### Q: How do I create a new page?
**A:** For content pages, use Publisher. Admin Pages is for managing existing pages.

### Q: My page shows 404
**A:** Check:
1. Is the path correct?
2. Is the page active?
3. Does the layout exist?

### Q: Can I have pages with the same path?
**A:** No, paths must be unique.

---

## 💡 Best Practices

1. **Plan paths carefully** - Changing URLs affects SEO
2. **Use consistent naming** - Keep paths organized
3. **Test layouts** - Check page appearance after changes
4. **Document system pages** - Know what each does
5. **Backup before changes** - Especially for system pages

---

## 🆘 Need Help?

- Check **Publisher Pages** for content management
- Check **Layouts** for template options
- Check **Modules** for functionality
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
