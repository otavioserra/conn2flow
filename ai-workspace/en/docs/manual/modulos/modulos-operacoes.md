# 🔐 Module Operations - User Manual

## What are Module Operations?

**Module Operations** define what users can DO within each module. Operations are the individual permissions like View, Create, Edit, Delete that control access at a granular level.

---

## 🎯 Getting Started

### Accessing Module Operations
1. On the Dashboard, find the **Module Operations** card
2. Click to open the module
3. You'll see all defined operations

> 🔒 This is an administrator area. You need admin permissions.

---

## 📋 Operations List

### What You'll See
For each operation:
- **Name** - Operation display name
- **ID** - Unique identifier
- **Module** - Associated module
- **Type** - Operation type
- **Actions** - Edit, delete

### Common Operations
| Operation | Meaning |
|-----------|---------|
| **view** | Can see the module |
| **create** | Can add new items |
| **edit** | Can modify existing items |
| **delete** | Can remove items |
| **export** | Can export data |
| **admin** | Full administrative access |

---

## 🔧 How Operations Work

### Permission Chain
```
User Profile → Has Operations → Determines Access

Example:
├── Admin Profile
│   ├── view ✓
│   ├── create ✓
│   ├── edit ✓
│   └── delete ✓
│
└── Editor Profile
    ├── view ✓
    ├── create ✓
    ├── edit ✓
    └── delete ✗
```

---

## ➕ Creating Operations

### How to Create
1. Click **"Add Operation"**
2. Fill in:
   - **Name** - Descriptive name
   - **ID** - Unique identifier
   - **Module** - Select module
   - **Description** - What it allows
3. Click **"Save"**

### Naming Convention
Use consistent naming:
- `module-view`
- `module-create`
- `module-edit`
- `module-delete`

---

## 🔗 Linking to Profiles

### In User Profiles
1. Go to **User Profiles**
2. Edit a profile
3. Check/uncheck operations
4. Save

### Testing Permissions
1. Login as user with that profile
2. Try accessing the module
3. Verify correct operations are available

---

## ❓ FAQ

### Q: User can see but not edit
**A:** They have `view` but not `edit` operation.

### Q: New module not showing for users
**A:** Check that users' profiles have the `view` operation for that module.

### Q: How do I restrict delete?
**A:** Remove `delete` operation from profiles that shouldn't delete.

---

## 💡 Best Practices

1. **Principle of least privilege** - Only give needed permissions
2. **Standard naming** - Use consistent operation names
3. **Document** - Describe what each operation allows
4. **Test** - Verify permissions work as expected
5. **Review regularly** - Update as roles change

---

## 🆘 Need Help?

- Check **User Profiles** for profile management
- Check **Modules** for module settings
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
