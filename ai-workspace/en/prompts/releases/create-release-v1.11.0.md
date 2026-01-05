```markdown
# Prompt Interactive Programming - Create Release - v1.11.0

## 📝 Guidelines for the Agent
1. Analyze the documentation to get the general context of the system currently: `ai-workspace\docs`.
2. Search for all the latest commits in the repository to understand recent changes to create the main text of this release. Versions before the last release are since v1.X.0
3. Update the main informative file of the project to see the need to update it: `README.md`.
4. Use the ready script to do the necessary operations in the repository: `ai-workspace\git\scripts\release.sh` using the following example: `bash ./gestor/utilitarios/release.sh minor "Summary for Tag" "Detailed message for Commit"`.
5. Remove all old tags from the Git repository for previous manager versions: `gestor-v*` and keep only this last one we created. Remembering that there are other types of releases that are not from the manager like `instalador-v*`, these and other types should not be deleted.
6. If you have no doubts or suggestions, you can execute the tasks above.

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Release v1.11.0 created, README updated, old tags removed keeping gestor-v1.11.0

---
**Date:** 08/18/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.11.0
```