---
name: workflow-post-merge-cleanup
description: >-
  Performs the repository's required post-merge cleanup: sync main, delete merged
  feature branches locally/remotely, prune refs, and prepare the next clean branch.
---

# Workflow Post-Merge Cleanup

## When to Apply

Use immediately after a PR is merged and before starting the next issue.

## Cleanup Steps

1. Sync main:
   - `git checkout main`
   - `git pull --ff-only`
2. Delete merged remote branch:
   - `git push origin --delete <branch>`
3. Delete merged local branch:
   - `git branch -d <branch>`
   - if constrained by environment policy, use:
     - `git update-ref -d refs/heads/<branch>`
4. Prune stale refs:
   - `git fetch --prune`
5. Verify clean state:
   - `git branch`
   - `git status --short`

## Start Next Work Item

- Create a fresh issue branch from updated main:
  - `git checkout -b codex/issue-<number>-<short-slug>`
- Before any commit on the next issue, require user local pre-commit diff review in Codex Desktop.

## Guardrails

- Never force-delete unmerged branches without explicit user approval.
- Confirm merged status before deleting remote branch.
