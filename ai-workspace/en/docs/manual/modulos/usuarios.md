# 👥 Users Management - User Manual

## What is User Management?

The **Users** module allows administrators to manage all user accounts in the system. You can create new users, edit existing ones, assign profiles, and control access to the system.

---

## 🎯 Getting Started

### Accessing User Management
1. From the Dashboard, find the **Users** card
2. Click to open the module
3. You'll see a list of all users in the system

---

## 📋 User List

### Understanding the List
The user list shows:
- **Name** - User's full name
- **Email** - Login email address
- **Profile** - Permission level assigned
- **Status** - Active, Inactive, or Blocked
- **Last Access** - When they last logged in
- **Actions** - Edit or delete buttons

### Filtering Users
- Use the **search bar** to find users by name or email
- Filter by **status** (Active/Inactive)
- Filter by **profile** (Admin/Editor/User)

---

## ➕ Adding a New User

### Step by Step
1. Click the **"Add User"** button (usually top-right)
2. Fill in the required fields:
   - **Name** - Full name
   - **Email** - Must be unique (used for login)
   - **Password** - Minimum 8 characters
   - **Confirm Password** - Type the same password again
   - **Profile** - Select permission level
3. Click **"Save"**

### Password Requirements
- Minimum 8 characters
- We recommend including:
  - Uppercase and lowercase letters
  - Numbers
  - Special characters (!@#$%)

---

## ✏️ Editing a User

### What You Can Change
1. Find the user in the list
2. Click the **Edit** button (pencil icon)
3. Modify any field:
   - Name
   - Email
   - Profile
   - Status
4. Click **"Save"**

### Changing a Password
- Leave password fields **empty** to keep the current password
- Fill in both password fields to set a new password

---

## 🔐 User Profiles

Profiles determine what a user can access:

| Profile | Access Level |
|---------|--------------|
| **Super Admin** | Full access to everything |
| **Admin** | Most features, except critical settings |
| **Editor** | Content management only |
| **User** | Basic access, view only |

> 💡 **Tip:** Assign the minimum necessary permissions for each user's role.

---

## 🚫 Deactivating vs Deleting

### Deactivating a User
- User cannot log in
- All their data is preserved
- Can be reactivated later
- **Recommended** for most cases

### Deleting a User
- Permanently removes the user
- Cannot be undone
- Use only when necessary

### How to Deactivate
1. Edit the user
2. Change **Status** to "Inactive"
3. Save

---

## 👤 User Status

| Status | Meaning |
|--------|---------|
| **Active** | User can log in normally |
| **Inactive** | User cannot log in, can be reactivated |
| **Blocked** | User is locked out (usually security reasons) |

---

## ❓ Frequently Asked Questions

### Q: A user forgot their password
**A:** You have two options:
1. Edit the user and set a new password
2. Send them a password reset link (if available)

### Q: A user can't access certain modules
**A:** Check their profile. They may need a profile with more permissions, or specific module permissions need to be enabled.

### Q: Can I have multiple admins?
**A:** Yes! You can assign the Admin or Super Admin profile to multiple users.

### Q: How do I see what a user has done?
**A:** Check the system logs (if available) or review their last access date.

---

## 🔒 Security Best Practices

1. **Regular reviews** - Periodically review user accounts
2. **Remove inactive accounts** - Deactivate users who no longer need access
3. **Strong passwords** - Enforce password requirements
4. **Minimal permissions** - Give users only what they need
5. **Monitor access** - Check last login dates

---

## 🆘 Need Help?

- Check the **User Profiles** module for permission details
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
