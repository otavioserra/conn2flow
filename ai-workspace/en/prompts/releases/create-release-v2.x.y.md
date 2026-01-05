```markdown
# Prompt Interactive Programming - Create Release - v2.4.0

## 📝 Guidelines for the Agent
1. Analyze the documentation to get the general context of the system currently: `ai-workspace\docs`.
2. Search for all the latest commits in the repository to understand recent changes to create the main text of this release, as well as its change history. Versions before the last release are since v2.3.1 until the current version which will be v2.4.0.
3. Update the main informative file of the project to see the need to update it: EN - `README.md`, PT-BR - `README.pt-br.md`.
4. Update the main changelog files of the project: Standard - `CHANGELOG.md` and more detailed history - `ai-workspace\docs\CONN2FLOW-CHANGELOG-HISTORY.md`.
5. Update the `body` field information of the GitHub Workflow release file: `.github\workflows\release-gestor.yml`. Keep the existing formatting standard and add the new release information.
6. Create a summarized tag message and a detailed commit message for the release to include in the next step.
7. Use the ready script to do the necessary operations in the GIT repository: `ai-workspace\scripts\releases\release.sh` using the following example: `bash ./ai-workspace/scripts/releases/release.sh minor "Summary for Tag" "Detailed message for Commit"`.
8. If you have no doubts or suggestions, you can execute the tasks above. Otherwise include your considerations right in the next session below.

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Analyze documentation in ai-workspace/docs for general system context
- [x] Search commits since v2.3.0 until today to understand changes
- [x] Update README.md and README-PT-BR.md with v2.4.0 information
- [x] Update CHANGELOG.md with v2.4.0 entry
- [x] Update CONN2FLOW-CHANGELOG-HISTORY.md with detailed history
- [x] Update body field of .github/workflows/release-gestor.yml
- [x] Create tag and commit messages for the release
- [x] Execute release script with created messages
- [x] **RELEASE v2.4.0 COMPLETED SUCCESSFULLY!** 🎉

---
**Date:** 11/06/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v2.4.0
```