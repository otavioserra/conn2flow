# 👥 User Profiles - User Manual

## What are User Profiles?

**User Profiles** are permission templates that define what users can do in the system. Instead of setting permissions for each user individually, you create profiles and assign them to users.

---

## 🎯 Getting Started

### Accessing User Profiles
1. From the Dashboard, find the **User Profiles** card
2. Click to open the module
3. You'll see all existing profiles

---

## 📋 Understanding Profiles

### How Profiles Work
```
Profile (e.g., "Editor")
    └── Has permissions for:
        ├── Pages Module (view, add, edit)
        ├── Media Module (view, upload)
        └── Publisher Module (view, add, edit, delete)
            
User "John" 
    └── Assigned Profile: "Editor"
        └── John can do everything the Editor profile allows
```

---

## 📦 Default Profiles

Conn2Flow comes with these built-in profiles:

| Profile | Description | Typical Use |
|---------|-------------|-------------|
| **Super Admin** | Full access to all modules and operations | System owner, IT administrator |
| **Admin** | Most features, some restrictions | Department managers |
| **Editor** | Content creation and management | Writers, content managers |
| **User** | Basic access, mostly view-only | General staff, viewers |

---

## ➕ Creating a New Profile

### Step by Step
1. Click **"Add Profile"**
2. Fill in basic information:
   - **Name** - Descriptive name (e.g., "Marketing Team")
   - **Description** - What this profile is for
   - **Level** - Hierarchy number (higher = more authority)
3. Set permissions using the **Permission Matrix**
4. Click **"Save"**

---

## 🎛️ The Permission Matrix

The permission matrix is a grid showing:
- **Rows** = Modules
- **Columns** = Operations (View, Add, Edit, Delete, etc.)

### How to Set Permissions
1. Find the module row
2. Check the boxes for allowed operations:
   - ☑️ **View** - Can see the module and its content
   - ☑️ **Add** - Can create new items
   - ☑️ **Edit** - Can modify existing items
   - ☑️ **Delete** - Can remove items

### Quick Selection
- **Check row header** - Select all operations for that module
- **Check column header** - Select that operation for all modules

---

## ✏️ Editing a Profile

### What You Can Change
1. Find the profile in the list
2. Click **Edit**
3. Modify:
   - Name and description
   - Permission checkboxes
4. Click **"Save"**

> ⚠️ **Warning:** Changes affect ALL users with this profile immediately!

---

## 🔗 Profile Inheritance

You can create profiles that inherit from other profiles:

### How It Works
1. Create a base profile (e.g., "Staff - Basic")
2. Create a child profile (e.g., "Staff - Advanced")
3. Set "Staff - Basic" as the parent
4. The child gets all parent permissions PLUS its own

### Benefits
- Less work maintaining permissions
- Consistent base permissions
- Easy to create variations

---

## 📊 Profile Levels

Levels determine hierarchy:

| Level | Example Profile | Can Manage |
|-------|-----------------|------------|
| 100 | Super Admin | Everyone |
| 80 | Admin | Levels below 80 |
| 50 | Editor | Levels below 50 |
| 20 | User | Only themselves |

> 💡 **Rule:** Users can only manage users with lower-level profiles.

---

## ❓ Frequently Asked Questions

### Q: Can I delete a profile with assigned users?
**A:** No. First reassign users to another profile, then delete.

### Q: What happens if I change permissions?
**A:** All users with that profile immediately get the new permissions (on their next page load).

### Q: Can a user have multiple profiles?
**A:** No. Each user has one profile. Create a new combined profile if needed.

### Q: How do I see which users have a profile?
**A:** Go to **Users** and filter by profile.

---

## 💡 Best Practices

### Creating Profiles
1. **Name clearly** - "Marketing Editor" is better than "Profile 3"
2. **Start minimal** - Add permissions as needed, not all at once
3. **Document purpose** - Use the description field
4. **Test** - Create a test user with the profile to verify

### Security
1. **Limit admins** - Not everyone needs admin access
2. **Regular audits** - Review profiles quarterly
3. **Remove unused** - Delete profiles no one uses
4. **Separate duties** - Different tasks = different profiles

---

## 🆘 Need Help?

- Check the **Users** module to assign profiles
- Check **Module Operations** to understand available operations
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
