# 📦 Modules - User Manual

## What is Module Management?

The **Modules** module lets administrators control which features appear in the sidebar menu and how they're organized. You can show/hide modules, reorder them, and group them into logical categories.

---

## 🎯 Getting Started

### Accessing Module Management
1. From the Dashboard, find the **Modules** card
2. Click to open the module
3. You'll see all system modules and their groups

> ⚠️ **Note:** This is an admin-level feature.

---

## 📋 Understanding Modules

### What Are Modules?
Modules are the individual features/sections of Conn2Flow:
- Users
- Pages
- Layouts
- Components
- etc.

Each item in the sidebar menu represents a module.

---

## 🗂️ Module Organization

### View Options
- **List view** - All modules in a table
- **Group view** - Modules organized by groups

### Module Information
| Field | Description |
|-------|-------------|
| **Name** | Display name in menu |
| **ID** | Unique identifier |
| **Group** | Category it belongs to |
| **Order** | Position in menu |
| **Visible** | Show/hide in menu |
| **Icon** | Menu icon |

---

## 👁️ Showing/Hiding Modules

### Hide a Module
1. Find the module in the list
2. Toggle the **Visible** switch to off
3. Module disappears from sidebar

### Show a Module
1. Find the module in the list
2. Toggle the **Visible** switch to on
3. Module appears in sidebar

> 💡 **Tip:** Hiding doesn't delete - users with direct links can still access if they have permission.

---

## ↕️ Reordering Modules

### Drag and Drop
1. Click and hold a module row
2. Drag to new position
3. Release to drop
4. Order is saved automatically

### Using Order Number
1. Click **Edit** on a module
2. Change the **Order** number
3. Save - lower numbers appear first

---

## 📂 Module Groups

### What Are Groups?
Groups organize related modules together:
```
📂 Administration
  ├── Users
  ├── Profiles
  └── Updates

📂 Content
  ├── Pages
  ├── Layouts
  └── Components
```

### Creating a Group
1. Go to **Module Groups**
2. Click **"Add Group"**
3. Enter:
   - Name
   - Icon
   - Order
4. Save

### Assigning Modules to Groups
1. Edit a module
2. Select the **Group** from dropdown
3. Save

---

## 🎨 Customizing Icons

### Changing a Module Icon
1. Edit the module
2. Click the **Icon** selector
3. Browse or search icons
4. Click to select
5. Save

### Available Icons
Conn2Flow uses Fomantic-UI icons. Examples:
- `users` - People icon
- `file` - Document icon
- `cog` - Settings gear
- `paint brush` - Design icon

---

## ✏️ Editing Module Details

### What You Can Change
1. **Name** - Display name (shown in menu)
2. **Icon** - Visual identifier
3. **Group** - Which category
4. **Order** - Position in menu
5. **Visible** - Show/hide

### What You Cannot Change
- **ID** - Fixed identifier
- **Core functionality** - System modules can't be deleted

---

## ❓ Frequently Asked Questions

### Q: A module disappeared from my menu
**A:** Possible causes:
1. It was hidden (check Visible toggle)
2. You don't have permission (check your profile)
3. It was moved to a different group

### Q: Can I delete a module?
**A:** Core modules cannot be deleted. Plugin modules can be removed by uninstalling the plugin.

### Q: Can I rename a module?
**A:** Yes, change the **Name** field. This only affects the display name.

### Q: How do I reset to default order?
**A:** There's no automatic reset. You'll need to manually reorder, or restore from a backup.

### Q: Hidden modules still accessible?
**A:** Yes, hiding only removes from menu. Users with permissions and direct URLs can still access. Use profiles to restrict access completely.

---

## 💡 Best Practices

### Organization
1. **Group logically** - Related modules together
2. **Most used first** - Frequently accessed at top
3. **Hide unused** - Clean up the menu
4. **Clear names** - Rename if default isn't clear

### For Different Users
Consider creating different views:
- **Editors** - Only see content modules
- **Admins** - See all modules
- **Managers** - See reports and users

### Maintenance
1. **Review periodically** - Is organization still logical?
2. **Remove clutter** - Hide modules no one uses
3. **Update groups** - As your needs change
4. **Document** - Note why things are organized this way

---

## 🆘 Need Help?

- Check **User Profiles** for permission-based access control
- Check **Module Groups** for group management
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
