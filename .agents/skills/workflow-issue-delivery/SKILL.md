---
name: workflow-issue-delivery
description: >-
  Executes this repository's issue-first delivery workflow: one issue at a time,
  one branch, one PR, TDD-first, then pause for user review before continuing.
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
3. Create branch:
   - `codex/issue-<number>-<short-slug>`
4. Implement with TDD:
   - write/update failing tests first
   - implement minimal code
   - make tests pass
5. Validate:
   - run minimal affected tests with `php artisan test --compact ...`
   - run `vendor/bin/pint --dirty --format agent`
6. Pause for local review before commit:
   - present uncommitted diff/status for user review in Codex Desktop
   - wait for explicit user approval
7. Commit and open one PR linked to one issue (`Closes #<number>`).
8. Stop and wait for explicit user review/approval before next issue.

## PR Template Checklist

- Scope matches issue acceptance criteria.
- Includes test commands actually run.
- Mentions any deliberate out-of-scope changes.
- Uses one PR per issue.

## Guardrails

- Do not commit before user-approved local pre-commit review.
- Do not start the next issue before the current PR is reviewed/merged.
- Do not batch multiple issues in one PR unless user requests it.
- Keep implementation aligned with `/Users/trq/src/trq/flv2/BUDGET_AI_V1_SPEC.md`.
- Enforce Flowly money modeling: whole-dollar signed integers only.
