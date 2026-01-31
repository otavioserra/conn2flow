# 📄 Templates - User Manual

## What are Templates?

**Templates** are pre-designed content structures that help you create pages faster. Think of them as starting points - instead of creating pages from scratch, you choose a template that already has the layout and sections you need.

---

## 🎯 Getting Started

### Accessing Templates
1. From the Dashboard, find the **Templates** card
2. Click to open the module
3. You'll see all available templates

---

## 🏗️ Understanding Templates

### Templates vs Layouts vs Components

| Element | Purpose | Example |
|---------|---------|---------|
| **Layout** | Page structure (header, footer) | Admin layout, Public layout |
| **Template** | Pre-filled content structure | Blog post, Product page |
| **Component** | Reusable UI element | Navigation menu, Footer |

Templates are content blueprints. When you create a page from a template, you get a head start with predefined sections.

---

## 📋 Template List

### What You'll See
- **Template name** - Identifier
- **Description** - What it's for
- **Preview** - Visual preview (if available)
- **Actions** - Use, edit, duplicate

---

## 🎨 Using Templates

### Creating a Page from Template
1. Go to **Pages** → **Add Page**
2. Look for **"Choose Template"** option
3. Browse available templates
4. Select one that matches your needs
5. Click **"Use Template"**
6. The new page opens with template content pre-filled
7. Customize the content
8. Save or publish

### What Gets Copied
- HTML structure
- Placeholder text
- Default sections
- Styling classes

### What You Change
- Replace placeholder text with your content
- Update images
- Modify sections as needed
- Add or remove elements

---

## ➕ Creating Templates (Admin)

### Step by Step
1. Click **"Add Template"**
2. Enter basic info:
   - **Name** - Descriptive name
   - **Description** - When to use this template
3. Design the content structure:
   - Add sections and elements
   - Use placeholder text
   - Include helpful comments
4. Set CSS framework if needed
5. Save

### Template Best Practices
```html
<!-- Template: Product Page -->
<section class="hero">
    <h1>[Product Name]</h1>
    <p class="tagline">[Brief product tagline]</p>
</section>

<section class="features">
    <h2>Key Features</h2>
    <!-- Add 3-4 feature cards -->
    <div class="feature">
        <h3>[Feature 1]</h3>
        <p>[Description]</p>
    </div>
</section>

<section class="cta">
    <h2>Ready to Buy?</h2>
    <a href="#" class="button">[Button Text]</a>
</section>
```

Use placeholders like `[Product Name]` to indicate where users should add content.

---

## ✏️ Editing Templates

### Making Changes
1. Find the template in the list
2. Click **Edit**
3. Modify the structure, text, or styling
4. Save

> ⚠️ **Note:** Changes don't affect pages already created from the template.

---

## 📦 Common Template Types

### Marketing Pages
- **Landing Page** - Single CTA, hero section
- **Product Page** - Features, pricing, testimonials
- **About Page** - Company story, team, values

### Blog & Content
- **Blog Post** - Article structure with image, text, author
- **News Article** - Date-focused, quick read format
- **Tutorial** - Step-by-step with code blocks

### Business
- **Contact Page** - Form, map, contact info
- **Services Page** - Service cards, descriptions
- **Portfolio** - Project gallery layout

---

## 🔧 Template Variables

### Using Dynamic Content
Templates can include variables:
```html
<footer>
    <p>© @[[sistema#ano-atual]]@ @[[variavel#company-name]]@</p>
</footer>
```

This automatically inserts:
- Current year
- Your company name (from variables)

---

## ❓ Frequently Asked Questions

### Q: Can I modify a page after using a template?
**A:** Yes! The template is just a starting point. Once you create the page, you can change anything.

### Q: If I update a template, do existing pages update?
**A:** No. Pages created from templates are independent. Template changes only affect new pages.

### Q: Can I save my page as a new template?
**A:** This depends on your system. Some setups allow "Save as Template" functionality.

### Q: What if I don't see any templates?
**A:** Templates may not be created yet. Ask your administrator to add some, or create pages manually.

---

## 💡 Best Practices

### Creating Templates
1. **Purpose-driven** - One template, one purpose
2. **Clear placeholders** - Use [brackets] for replaceable text
3. **Comments** - Add HTML comments explaining sections
4. **Complete** - Include all standard sections
5. **Flexible** - Easy to add/remove sections

### Using Templates
1. **Choose wisely** - Pick the closest match
2. **Customize fully** - Don't leave placeholder text
3. **Check responsiveness** - Test on mobile
4. **Personalize** - Add your unique content

---

## 🆘 Need Help?

- Check **Layouts** for overall page structure
- Check **Components** for reusable elements
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
