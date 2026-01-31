# 📋 Templates Administration - User Manual

## What is Templates Administration?

The **Templates Admin** module provides advanced management of content templates. While Templates is for using templates, Admin Templates handles creation, versions, and technical settings.

---

## 🎯 Getting Started

### Accessing Admin Templates
1. On the Dashboard, find the **Admin Templates** card
2. Click to open the module
3. You'll see all system templates

> 🔒 This is an administrator area. You need admin permissions.

---

## 📋 Template List

### What You'll See
For each template:
- **ID** - Unique identifier
- **Name** - Display name
- **Category** - Template type
- **Version** - Template version
- **Status** - Active/Inactive
- **Actions** - Edit, duplicate, delete

---

## 🔧 Template Types

| Type | Purpose |
|------|---------|
| **Page** | Full page templates |
| **Section** | Content blocks |
| **Email** | Email templates |
| **Component** | Reusable elements |

---

## ➕ Creating Templates

### How to Create
1. Click **"Add Template"**
2. Fill in:
   - **Name** - Descriptive name
   - **ID** - Unique identifier
   - **Category** - Template type
   - **Content** - HTML structure
   - **Styles** - CSS (optional)
3. Click **"Save"**

### Template Structure
```html
<!-- Good template example -->
<section class="hero-section">
    <h1>{{title}}</h1>
    <p>{{description}}</p>
    <a href="{{cta_link}}" class="btn">{{cta_text}}</a>
</section>
```

---

## 🔤 Placeholders

### Using Placeholders
Mark editable areas:
- `{{placeholder_name}}` - Text content
- `<!-- editable -->` - Editable blocks

### Example
```html
<div class="feature-card">
    <h3>{{feature_title}}</h3>
    <p>{{feature_description}}</p>
</div>
```

---

## ⚙️ Version Management

### Versioning
- Templates can have versions
- Revert to previous versions
- Track changes over time

### Best Practice
- Update version when making significant changes
- Document what changed in each version

---

## ❓ FAQ

### Q: Difference from regular Templates?
**A:** Admin Templates is for creating/managing; Templates is for using them.

### Q: Can I import templates?
**A:** Check for import functionality or manually add via this module.

### Q: Template not appearing?
**A:** Check:
1. Status is Active
2. Correct category selected
3. No syntax errors

---

## 💡 Best Practices

1. **Use clear placeholders** - Descriptive names
2. **Include preview** - Help users visualize
3. **Document usage** - Add description
4. **Test thoroughly** - Try on different content
5. **Keep organized** - Use categories

---

## 🆘 Need Help?

- Check **Templates** for using templates
- Check **Layouts** for page structures
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
