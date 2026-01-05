```markdown
# Prompt Interactive Programming - Create Release - vVERSION

## 📝 Guidelines for the Agent
1. Analyze the documentation so you have the general context of the system currently: `ai-workspace\docs`.
2. Search for all the latest commits in the repository to understand the recent changes to create the main text of this release. Versions before the last release are since v1.X.0
3. Update the main informative file of the project to see the need to update it: `README.md`.
4. Use the ready script to do the necessary operations in the repository: `ai-workspace\git\scripts\release.sh` using the following example: `bash ./gestor/utilitarios/release.sh minor "Summary for the Tag" "Detailed message for the Commit"`.
5. Remove all old tags from the Git repository for previous manager versions: `gestor-v*` and keep only this last one we created. Remembering that there are other types of releases that are not from the manager like `instalador-v*`, these and other types should not be deleted.

## 🤔 Questions and 📝 Suggestions

## ✅ Implementation Progress
- [] progress item

---
**Date:** currentDate()
**Developer:** Otavio Serra
**Project:** Conn2Flow version()
```