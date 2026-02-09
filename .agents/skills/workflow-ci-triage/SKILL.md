---
name: workflow-ci-triage
description: >-
  Diagnoses and fixes failed GitHub PR checks for this repository using gh CLI,
  local reproduction, minimal patches, and revalidation before rerunning checks.
---

# Workflow CI Triage

## When to Apply

Use when a PR has failing CI/lint/test checks.

## Triage Procedure

1. Inspect check status:
   - `gh pr checks <pr-number>`
2. Pull failing logs:
   - `gh run view <run-id> --job <job-id> --log`
3. Classify failure:
   - dependency/platform mismatch
   - lint/format issue
   - test failure/regression
   - workflow misconfiguration
4. Reproduce locally with the narrowest command possible.
5. Apply minimal fix; avoid unrelated refactors.
6. Re-run targeted tests and `vendor/bin/pint --dirty --format agent`.
7. Push and re-check:
   - `gh pr checks <pr-number> --watch --interval 10`

## Output Expectations

- Report root cause clearly.
- List exact files changed and why.
- List exact validation commands run.

## Guardrails

- Prefer fixing lockfile/config incompatibilities over weakening CI.
- Avoid broad workflow changes unless required by root cause.
- Keep fixes scoped to the active PR/issue.
