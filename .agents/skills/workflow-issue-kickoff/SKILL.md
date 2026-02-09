---
name: workflow-issue-kickoff
description: >-
  Starts an issue implementation with the right context: read the GitHub issue,
  map acceptance criteria to tests, align with BUDGET_AI_V1_SPEC.md, and define
  a minimal implementation slice.
---

# Workflow Issue Kickoff

## When to Apply

Use before writing code for a new issue.

## Kickoff Steps

1. Read issue details:
   - `gh issue view <number> --json title,body,url,milestone`
2. Extract acceptance criteria as explicit test targets.
3. Cross-check against `/Users/trq/src/trq/flv2/BUDGET_AI_V1_SPEC.md`.
4. Decide test scope first:
   - unit vs feature
   - minimum affected files
5. Classify integration risk and choose delivery path:
   - low-risk: `direct-to-main`
   - high-risk: `branch+PR`
   - high-risk includes schema/auth/security/AI-write-orchestration/cross-domain refactors
6. Apply Flowly money rule to scope:
   - all money values are signed whole-dollar integers
   - reject decimal/floating-point money handling in new code/tests
7. Capture commit convention for delivery:
   - commit messages reference active issue number
   - closing commit includes `Closes #<number>` in body
8. Define implementation slice:
   - smallest set of classes/functions needed to pass tests.

## Deliverable for Kickoff

- A short checklist:
  - tests to add/update
  - core files to touch
  - invariants/edge cases to enforce

## Guardrails

- Pause and ask user if issue scope conflicts with spec.
- Avoid speculative architecture before first failing tests exist.
- Default to `direct-to-main` unless change is high-risk or user requests PR.
