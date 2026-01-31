# 📝 Variables - User Manual

## What are Variables?

**Variables** are reusable pieces of content that you can use throughout your site. Instead of typing the same information in multiple places (like your company name or phone number), you define it once as a variable and reference it everywhere. When you update the variable, all places using it update automatically!

---

## 🎯 Getting Started

### Accessing Variables
1. From the Dashboard, find the **Variables** card
2. Click to open the module
3. You'll see all existing variables

---

## 🏗️ Understanding Variables

### How Variables Work
```
Define once:
┌─────────────────────────────┐
│  Variable: company-phone    │
│  Value: (555) 123-4567      │
└─────────────────────────────┘

Use anywhere with:
@[[variavel#company-phone]]@

Result:
(555) 123-4567
```

---

## 📋 Variable List

### What You'll See
- **Name/ID** - Variable identifier
- **Value** - Current value (preview)
- **Category** - Grouping
- **Type** - Text, HTML, etc.
- **Actions** - Edit, delete

### Filtering
- **Search** - Find by name or value
- **Category** - Filter by group
- **Type** - Filter by variable type

---

## ➕ Creating a New Variable

### Step by Step
1. Click **"Add Variable"**
2. Fill in the details:
   - **Name/ID** - Unique identifier (lowercase, dashes)
   - **Value** - The content
   - **Type** - Text, HTML, JSON, etc.
   - **Category** - Grouping (optional)
   - **Description** - What it's for (optional)
3. Click **"Save"**

### Naming Tips
- Use lowercase with dashes: `company-name`
- Be descriptive: `contact-email` not `email1`
- Use prefixes: `social-facebook`, `social-twitter`

---

## 🔧 Variable Types

| Type | Best For | Example |
|------|----------|---------|
| **Text** | Simple strings | Company name, phone |
| **HTML** | Formatted content | Address with line breaks |
| **JSON** | Structured data | Configuration settings |
| **Number** | Numeric values | Prices, counts |

---

## 📦 Using Variables

### Basic Usage
```html
<!-- In any page, layout, or component -->
<p>Contact us at @[[variavel#company-email]]@</p>
<p>Call: @[[variavel#company-phone]]@</p>
```

### In Different Contexts

**In Text:**
```html
<p>© 2024 @[[variavel#company-name]]@. All rights reserved.</p>
```

**In Attributes:**
```html
<a href="mailto:@[[variavel#contact-email]]@">Email Us</a>
<img src="@[[variavel#logo-url]]@" alt="Logo">
```

**In JavaScript:**
```html
<script>
    var companyName = "@[[variavel#company-name]]@";
</script>
```

---

## 🌐 System Variables

Some variables are provided automatically:

| Variable | Description |
|----------|-------------|
| `@[[sistema#versao]]@` | Conn2Flow version |
| `@[[sistema#ano-atual]]@` | Current year |
| `@[[sistema#data-atual]]@` | Today's date |
| `@[[usuario#nome]]@` | Logged-in user's name |
| `@[[pagina#titulo]]@` | Current page title |

---

## 📂 Common Variable Categories

### Contact Information
```
contact-email
contact-phone
contact-address
contact-hours
```

### Company Info
```
company-name
company-slogan
company-cnpj
company-registration
```

### Social Media
```
social-facebook
social-instagram
social-linkedin
social-twitter
social-youtube
```

### SEO Defaults
```
seo-default-title
seo-default-description
seo-og-image
```

---

## ✏️ Editing Variables

### How to Edit
1. Find the variable in the list
2. Click **Edit** (pencil icon)
3. Change the value
4. Click **Save**

> 💡 **Changes take effect immediately** on all pages using the variable!

---

## 🌍 Multi-Language Variables

### Creating Language Versions
1. Create a variable for each language
2. Use language prefix or suffix:
   - `welcome-message-en`
   - `welcome-message-pt`
3. Or use the language selector when editing

### Automatic Language Detection
The system can automatically use the correct language version based on the current page language.

---

## ❓ Frequently Asked Questions

### Q: My variable isn't showing
**A:** Check:
1. Is the ID spelled correctly? (case-sensitive)
2. Is the syntax correct? `@[[variavel#id]]@`
3. Is the variable saved and active?

### Q: Can I use HTML in a text variable?
**A:** It's better to use the HTML type if you need formatting. Text type may escape HTML characters.

### Q: How do I delete a variable?
**A:** First check where it's used! Deleting a variable used in pages will leave the placeholder visible.

### Q: Are there any limits?
**A:** Variable names should be under 255 characters. Values can be much longer.

---

## 💡 Best Practices

### Organization
1. **Consistent naming** - Use prefixes to group related variables
2. **Document usage** - Fill in the description field
3. **Categorize** - Group variables logically
4. **Review regularly** - Remove unused variables

### Content
1. **Keep values simple** - Complex content = components
2. **No sensitive data** - Don't store passwords or secrets
3. **Update carefully** - Remember changes affect all uses
4. **Backup values** - Before major changes

---

## 🆘 Need Help?

- Check **Components** for more complex reusable content
- Check **Layouts** and **Pages** to see variable usage
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
