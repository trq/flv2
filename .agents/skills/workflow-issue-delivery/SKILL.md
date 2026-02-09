---
name: workflow-issue-delivery
description: >-
  Executes this repository's issue-first delivery workflow: one issue at a time,
  TDD-first, local pre-commit review, then integrate via direct-to-main for
  low-risk work or branch+PR for high-risk work.
  Use when starting or progressing milestone work in GitHub issues.
---

# Workflow Issue Delivery

## When to Apply

Use this skill when implementing milestone issues in this repository.

## Required Order

1. Sync and cleanup:
   - `git checkout main`
   - `git pull --ff-only`
   - prune stale refs and remove merged branches.
2. Select the next issue in milestone order (unless the user reprioritizes).
3. Decide integration mode:
   - `direct-to-main` (default for low-risk tasks)
   - `branch+PR` (required for high-risk changes)
4. If using `branch+PR`, create branch:
   - `codex/issue-<number>-<short-slug>`
5. Implement with TDD:
   - write/update failing tests first
   - implement minimal code
   - make tests pass
6. Validate:
   - run minimal affected tests with `php artisan test --compact ...`
   - run `vendor/bin/pint --dirty --format agent`
7. Pause for local review before commit:
   - present uncommitted diff/status for user review in Codex Desktop
   - wait for explicit user approval
8. Integrate:
   - `direct-to-main`: commit on `main`, push
   - `branch+PR`: commit, push branch, open one PR linked to one issue (`Closes #<number>`), merge, cleanup branches
9. Stop and wait for explicit user review/approval before next issue.

## PR Mode Checklist

- Scope matches issue acceptance criteria.
- Includes test commands actually run.
- Mentions any deliberate out-of-scope changes.
- Uses one PR per issue.

## Guardrails

- Do not commit before user-approved local pre-commit review.
- Do not start the next issue before current issue integration is complete.
- High-risk changes must use `branch+PR`:
  - schema/migration/index or persistence model changes
  - authentication/authorization/security changes
  - AI write-action/orchestration behavior changes
  - broad cross-cutting refactors across multiple domains
  - any user-requested PR path
- Do not batch multiple issues in one integration unit unless user requests it.
- Keep implementation aligned with `/Users/trq/src/trq/flv2/BUDGET_AI_V1_SPEC.md`.
- Enforce Flowly money modeling: whole-dollar signed integers only.
