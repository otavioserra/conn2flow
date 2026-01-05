```markdown
# Prompt Interactive Programming - Debug Mode

## 🎯 Initial Context
- The goal is to create a debug mode for the Conn2Flow installer, allowing local tests without manually filling out the form and without downloading gestor.zip from the repository every time.
- The installer is in the `gestor-instalador` folder.

## 📝 Guidelines for the Agent

- [x] 1. Define the format and location of the debug configuration file (`.env.debug` in the project root).
- [x] 2. List all mandatory and optional installer fields for auto-filling:
    - db_host, db_name, db_user, db_pass, domain, install_path, admin_name, admin_email, admin_pass, admin_pass_confirm, ssl_enabled, clean_install, lang.
- [x] 3. Plan how the installer will detect and use this file to automatically fill in the data (automatic detection of `.env.debug` in development environment).
- [x] 4. Add option to skip gestor.zip download and use local files (SKIP_DOWNLOAD variable in `.env.debug`).
- [x] 5. Document how to enable/disable debug mode and how to customize installation data.
    - Debug mode is automatically activated if the `.env.debug` file exists in the project root.
    - To disable, simply remove or rename the `.env.debug` file.
    - In the release process, the workflow automatically removes `.env.debug` before creating `instalador.zip`, ensuring it will never be distributed in production.
- [ ] 6. Suggest improvements for logs and error messages in debug mode (display extra details, stacktrace, environment variables).
- [ ] 7. Validate if there is a security impact by exposing sensitive data in debug mode and suggest protections.
- [ ] 8. Implement automated tests to validate debug mode and ensure the installer works correctly with pre-filled data.

## 🤔 Doubts and 📝 Suggestions
- What format do you prefer for the configuration file? (JSON, .env, PHP array)
.env.debug in the root is the best option. And create an option in index.php so that if this file exists, it will be used instead of the normal mode. When creating the release using GitHub workflow, this file must be removed: `.github\workflows\release-instalador.yml`
- Should debug mode be activated by environment variable, GET/POST parameter or automatic detection?
As stated above, automatic detection in development environment is the best approach.
- Any restrictions regarding the synchronization of manager files for test environment?
I will use a script I made for this. For installer data I use this to synchronize: `docker\utils\sincroniza-gestor-instalador.sh`, for manager data I use: `docker\utils\sincroniza-gestor.sh`. So when modifying or making any correction in the manager or installer, just run the script in each case, which will get the latest copy.
- Any preference for detailed logs (e.g. save to separate file, display on screen)?
Actually there are already all log outputs.

## ✅ Implementation Progress
- [ ] progress item

---

**Date:** 08/19/2025  
**Developer:** Otavio Serra  
**Project:** Conn2Flow v1.2.0
```