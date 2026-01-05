```markdown
# Prompt Interactive Programming - NAME

## 🎯 Initial Context
- FUNDAMENTAL: Analyze the previous context before following the instructions below, which was recorded in the file: `ai-workspace\prompts\architecture\update-resource-data-v3.md`.

## 📝 Instructions for the Agent
1. I found a problem in generating versions and checksums for resources: `pages`, `layouts`, and `components`. The same problem that occurred in the previous case of global resources is happening in the case of module resources.
2. Analyze what happened in the global case here `ai-workspace\prompts\architecture\update-resource-data-v3.md`, as the same is happening in the case of modules, make the necessary corrections.
4. Execute the script to see if it fixed it.
3. Generate a detailed message, replace "DetailedMessageHere" from the script, and execute the following GIT script: `./ai-workspace/git/scripts/commit.sh "DetailedMessageHere"`

## 🤔 Doubts and 📝 Suggestions

## ✅ Implementation Progress
- [x] Correction applied: versioning and checksums now update also for modules and plugins; script executed without orphans and commit performed (gestor-v1.10.20)

---
**Date:** 08/18/2025
**Developer:** Otavio Serra
**Project:** Conn2Flow v1.10.20
```