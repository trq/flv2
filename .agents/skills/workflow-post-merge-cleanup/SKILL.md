---
name: workflow-post-merge-cleanup
description: >-
  Performs required post-integration cleanup: sync main, clean merged feature
  branches when PR mode is used, prune refs, and prepare for the next issue.
---

# Workflow Post-Merge Cleanup

## When to Apply

Use immediately after integration is complete (direct-to-main or merged PR) and before starting the next issue.

## Cleanup Steps

1. Sync main:
   - `git checkout main`
   - `git pull --ff-only`
2. If PR mode was used, delete merged remote branch:
   - `git push origin --delete <branch>`
3. If PR mode was used, delete merged local branch:
   - `git branch -d <branch>`
   - if constrained by environment policy, use:
     - `git update-ref -d refs/heads/<branch>`
4. Prune stale refs:
   - `git fetch --prune`
5. Verify clean state:
   - `git branch`
   - `git status --short`

## Start Next Work Item

- If next issue is high-risk or PR-requested, create a fresh issue branch:
  - `git checkout -b codex/issue-<number>-<short-slug>`
- Before any commit on the next issue, require user local pre-commit diff review in Codex Desktop.

## Guardrails

- Never force-delete unmerged branches without explicit user approval.
- Confirm merged status before deleting remote branch.
