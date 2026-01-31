# 🔄 System Updates - User Manual

## What are System Updates?

The **Updates** module keeps your Conn2Flow installation current with the latest features, security patches, and bug fixes. Regular updates ensure your system runs smoothly and securely.

---

## 🎯 Getting Started

### Accessing Updates
1. From the Dashboard, find the **Updates** card
2. Click to open the module
3. You'll see your current version and available updates

> ⚠️ **Note:** This module is typically only available to Super Administrators.

---

## 📊 Understanding the Update Screen

### Information Displayed
- **Current Version** - Your installed Conn2Flow version
- **Latest Version** - Newest available version
- **Update Available** - Yes/No indicator
- **Update History** - Previous update attempts

---

## 🔄 Checking for Updates

### Automatic Check
The system automatically checks for updates periodically. You'll see a notification on the Dashboard when an update is available.

### Manual Check
1. Open the Updates module
2. Click **"Check for Updates"**
3. Wait for the check to complete
4. See if an update is available

---

## ⬆️ Applying an Update

### Before You Begin
1. **Backup your data** - Always backup before updating
2. **Check requirements** - Ensure your server meets requirements
3. **Plan timing** - Update during low-traffic periods
4. **Notify users** - Inform team members of potential downtime

### Step by Step
1. Open the Updates module
2. Review the update details (what's new, what's fixed)
3. Click **"Update Now"**
4. Wait for the process to complete
5. Review the update log
6. Verify the system works correctly

### What Happens During Update
1. System enters maintenance mode
2. New files are downloaded
3. Database migrations run
4. Caches are cleared
5. System exits maintenance mode
6. Update log is recorded

---

## 📋 Update History

### Viewing Past Updates
The module shows all previous updates:
- **Version** - From → To
- **Date** - When it was applied
- **Status** - Success or Failed
- **Log** - Click to view details

### Using the Log
If something goes wrong, the log helps diagnose:
- What step failed
- Error messages
- Actions to take

---

## 🚨 Troubleshooting

### Update Failed

**1. Download Failed**
- Check internet connection
- Verify server can reach GitHub
- Try again later (may be temporary)

**2. Extraction Failed**
- Check disk space
- Verify write permissions
- Check PHP memory limit

**3. Migration Failed**
- Check database connection
- Review error message
- May need manual database fix

**4. Files Not Updating**
- Clear browser cache
- Check file permissions
- Verify .htaccess configuration

### Recovering from Failed Update

1. **Don't panic** - Most issues are recoverable
2. **Check the log** - Understand what failed
3. **Restore backup** - If you made one (you did, right?)
4. **Contact support** - If you're stuck

---

## ❓ Frequently Asked Questions

### Q: How often should I update?
**A:** Apply updates as soon as possible, especially security updates. Check at least weekly.

### Q: Will updates break my customizations?
**A:** Core updates shouldn't affect your layouts, pages, or components. Custom modifications to core files might need reapplication.

### Q: Can I skip versions?
**A:** Generally yes, the system handles this. But major version jumps may require sequential updates.

### Q: What if I'm on a slow connection?
**A:** The download might timeout. Try during off-peak hours or ask your host about better connectivity.

### Q: Do I need to backup before every update?
**A:** Yes! Always. Even small updates can occasionally cause issues.

---

## 💡 Best Practices

### Pre-Update
1. **Always backup** - Database and files
2. **Test first** - If you have a staging environment
3. **Read notes** - Review what's changing
4. **Plan timing** - Low-traffic periods

### Post-Update
1. **Clear caches** - Browser and server
2. **Test features** - Check critical functionality
3. **Monitor logs** - Watch for errors
4. **Notify team** - Confirm update complete

### Regular Maintenance
1. **Check weekly** - Look for new updates
2. **Don't delay** - Especially security updates
3. **Keep backups** - Regular, automated backups
4. **Stay informed** - Follow Conn2Flow announcements

---

## 🆘 Need Help?

- Check the update log for specific errors
- Review the system requirements
- Contact your system administrator
- Visit our documentation at [conn2flow.com/docs](https://conn2flow.com/docs)
