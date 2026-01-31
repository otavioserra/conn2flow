# 🧩 Components - User Manual

## What are Components?

**Components** are reusable building blocks that you can include in layouts, pages, or other components. Think of them like LEGO pieces - you build them once and use them anywhere. Examples include headers, footers, navigation menus, call-to-action buttons, and more.

---

## 🎯 Getting Started

### Accessing Components
1. From the Dashboard, find the **Components** card
2. Click to open the module
3. You'll see all available components

---

## 🏗️ Understanding Components

### How Components Work
```
Create a component once:
┌─────────────────────────────┐
│   site-footer component     │
│   ───────────────────────   │
│   <footer>                  │
│     Contact us...           │
│     © 2024 Company          │
│   </footer>                 │
└─────────────────────────────┘

Use it anywhere:
┌─────────────────────────────┐
│   Page Layout               │
│   ...                       │
│   @[[componente#site-footer]]@
│   ...                       │
└─────────────────────────────┘
```

When you update the component, ALL places using it update automatically!

---

## 📋 Component List

### What You'll See
- **Name** - Component identifier
- **Description** - What it's for
- **Last modified** - When it was changed
- **Actions** - Edit, duplicate, delete

---

## ➕ Creating a New Component

### Step by Step
1. Click **"Add Component"**
2. Fill in details:
   - **Name** - Descriptive name (e.g., "Call to Action Button")
   - **ID** - Unique identifier (auto-generated)
3. Enter HTML in the code editor
4. Add CSS if needed
5. Click **"Save"**

### Example Component
```html
<!-- Call to Action Button Component -->
<div class="cta-container">
    <h3>Ready to get started?</h3>
    <p>Join thousands of happy customers</p>
    <a href="/signup" class="cta-button">
        Sign Up Now
    </a>
</div>
```

---

## 🔧 Using Components

### Including in a Layout or Page
```html
<!-- Include a component by its ID -->
@[[componente#component-id]]@

<!-- Examples -->
@[[componente#site-header]]@
@[[componente#newsletter-signup]]@
@[[componente#testimonials]]@
```

### Components in Components
Yes! Components can include other components:
```html
<!-- In the site-footer component -->
<footer>
    @[[componente#footer-links]]@
    @[[componente#social-icons]]@
    @[[componente#copyright]]@
</footer>
```

---

## 🔄 Dynamic Content in Components

### Using Variables
Make components dynamic with variables:
```html
<div class="company-info">
    <h2>@[[variavel#company-name]]@</h2>
    <p>@[[variavel#company-address]]@</p>
    <p>Phone: @[[variavel#company-phone]]@</p>
</div>
```

### Available Variables
| Syntax | Description |
|--------|-------------|
| `@[[variavel#name]]@` | Site variables |
| `@[[usuario#nome]]@` | Logged-in user's name |
| `@[[pagina#titulo]]@` | Current page title |
| `@[[sistema#ano-atual]]@` | Current year |

---

## ✏️ Editing Components

### The Code Editor
- **HTML Tab** - Component markup
- **CSS Tab** - Component-specific styles
- Syntax highlighting
- Live preview (if available)

### Tips
1. Keep components focused on one purpose
2. Use meaningful class names
3. Make components self-contained
4. Document with HTML comments

---

## 🎨 Styling Components

### CSS Tab
Add component-specific styles:
```css
.cta-container {
    text-align: center;
    padding: 2rem;
    background: #f5f5f5;
}

.cta-button {
    display: inline-block;
    padding: 1rem 2rem;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.cta-button:hover {
    background: #0056b3;
}
```

---

## 📦 Common Components

### Header Component
```html
<header class="site-header">
    <div class="logo">
        <a href="/">
            <img src="@[[variavel#logo-url]]@" alt="Logo">
        </a>
    </div>
    <nav class="main-nav">
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
    </nav>
</header>
```

### Footer Component
```html
<footer class="site-footer">
    <div class="footer-content">
        <p>&copy; @[[sistema#ano-atual]]@ @[[variavel#company-name]]@</p>
        <p>All rights reserved</p>
    </div>
</footer>
```

---

## ❓ Frequently Asked Questions

### Q: Where can I use components?
**A:** In layouts, pages, templates, and even inside other components.

### Q: If I update a component, do all pages update?
**A:** Yes! That's the main benefit - change once, update everywhere.

### Q: Can I use JavaScript in components?
**A:** Yes, include `<script>` tags in the HTML. Make sure scripts don't conflict.

### Q: My component isn't showing
**A:** Check:
1. Is the ID spelled correctly? (case-sensitive)
2. Is the component saved?
3. Is the component status active?

---

## 💡 Best Practices

### Organization
- **Name clearly** - "newsletter-signup" not "comp1"
- **Group related** - Use prefixes like "footer-", "header-"
- **Document** - Add comments explaining purpose

### Design
- **Single purpose** - One component, one job
- **Self-contained** - Include all needed HTML/CSS
- **Responsive** - Work on all screen sizes
- **Reusable** - Design for multiple uses

### Maintenance
- **Review regularly** - Remove unused components
- **Keep CSS scoped** - Use unique class names
- **Test changes** - Check all places using it

---

## 🆘 Need Help?

- Check the **Layouts** module to see where components are used
- Check **Variables** for dynamic content options
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
