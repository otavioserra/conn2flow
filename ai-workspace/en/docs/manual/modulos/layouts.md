# 🎨 Layouts - User Manual

## What are Layouts?

**Layouts** are the master templates that define the overall structure of your pages. They contain the common elements that appear on every page - like the header, footer, and navigation. The actual page content is inserted into a special area within the layout.

---

## 🎯 Getting Started

### Accessing Layouts
1. From the Dashboard, find the **Layouts** card
2. Click to open the module
3. You'll see all available layouts

---

## 🏗️ Understanding Layouts

### How Layouts Work
```
Layout Structure:
┌─────────────────────────────┐
│         HEADER              │  ← Common to all pages
├─────────────────────────────┤
│       NAVIGATION            │  ← Common to all pages
├─────────────────────────────┤
│                             │
│    @[[pagina#corpo]]@       │  ← Page content goes here
│                             │
├─────────────────────────────┤
│         FOOTER              │  ← Common to all pages
└─────────────────────────────┘
```

The magic variable `@[[pagina#corpo]]@` is where each page's unique content appears.

---

## 📋 Layout List

### What You'll See
- **Layout name** - Identifier
- **Last modified** - When it was last changed
- **Pages using** - How many pages use this layout
- **Actions** - Edit, duplicate, delete

---

## ➕ Creating a New Layout

### Step by Step
1. Click **"Add Layout"**
2. Fill in the details:
   - **Name** - A descriptive name
   - **ID** - Unique identifier (auto-generated from name)
3. Enter the HTML structure in the code editor
4. Add CSS if needed
5. Click **"Save"**

### Basic Layout Template
```html
<!DOCTYPE html>
<html lang="@[[pagina#idioma]]@">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@[[pagina#titulo]]@</title>
    @[[pagina#head]]@
</head>
<body>
    <header>
        @[[componente#site-header]]@
    </header>
    
    <main>
        @[[pagina#corpo]]@
    </main>
    
    <footer>
        @[[componente#site-footer]]@
    </footer>
    
    @[[pagina#scripts]]@
</body>
</html>
```

---

## 🔧 Essential Variables

### Must-Have Variables
| Variable | Purpose |
|----------|---------|
| `@[[pagina#corpo]]@` | **Required!** Where page content appears |
| `@[[pagina#titulo]]@` | Page title |
| `@[[pagina#head]]@` | Additional head content |
| `@[[pagina#scripts]]@` | JavaScript at page bottom |

### Optional Variables
| Variable | Purpose |
|----------|---------|
| `@[[pagina#idioma]]@` | Current language |
| `@[[usuario#nome]]@` | Logged-in user's name |
| `@[[componente#name]]@` | Include a component |
| `@[[variavel#name]]@` | Insert a variable value |

---

## ✏️ Editing Layouts

### The Code Editor
- **HTML Tab** - Main structure
- **CSS Tab** - Layout-specific styles
- Syntax highlighting for easy editing
- Line numbers for reference

### Tips for Editing
1. Always backup before major changes
2. Test changes on a preview page first
3. Keep the `@[[pagina#corpo]]@` variable intact
4. Use components for reusable sections

---

## 🎨 CSS Frameworks

Conn2Flow supports:
- **Fomantic-UI** - Feature-rich UI framework
- **TailwindCSS** - Utility-first framework

### Selecting a Framework
1. Edit the layout
2. Choose from the **CSS Framework** dropdown
3. Save the layout
4. Framework classes are now available

---

## 📦 Using Components

Instead of repeating code, use components:

```html
<!-- Instead of repeating navigation code -->
<nav>
    <!-- lots of repeated code -->
</nav>

<!-- Use a component -->
@[[componente#main-navigation]]@
```

### Benefits
- Change once, update everywhere
- Cleaner layout code
- Easier maintenance

---

## ❓ Frequently Asked Questions

### Q: My page content isn't showing
**A:** Make sure your layout includes `@[[pagina#corpo]]@` - this is where the content appears.

### Q: Can I have different headers for different pages?
**A:** Yes! Create multiple layouts with different headers, then assign pages to the appropriate layout.

### Q: How do I add Google Analytics?
**A:** Add the tracking code before `</head>` in your layout, or use `@[[pagina#head]]@` to include it from pages.

### Q: My CSS isn't working
**A:** Check:
1. Is the CSS in the CSS tab (not HTML)?
2. Are there syntax errors?
3. Is another style overriding it?

---

## ⚠️ Important Notes

1. **Don't delete active layouts** - First reassign pages to another layout
2. **Backup before editing** - Major changes can break pages
3. **Test thoroughly** - Check all pages using the layout after changes
4. **Mobile responsive** - Always test on mobile devices

---

## 💡 Best Practices

1. **Keep layouts minimal** - Put reusable parts in components
2. **Name clearly** - "Main Site Layout" not "Layout 1"
3. **Document** - Add HTML comments explaining sections
4. **Version control** - Note what you changed and when
5. **Mobile first** - Design for mobile, enhance for desktop

---

## 🆘 Need Help?

- Check the **Components** module for reusable elements
- Check **Variables** for dynamic content
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
