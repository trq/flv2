# Flowly Budget AI V1 Specification

## 1. Objective

Build Flowly, an AI-first budgeting app in Laravel + Inertia React where users define cycle-based goals and record immutable allocation events through chat or UI.

The system must:

- Support onboarding through an agent workflow.
- Track Income, Expense, and Savings goals.
- Enforce funded spending from an income pool.
- Track cross-cycle savings balances through dedicated savings pools.
- Support natural-language event capture with confirmation and confidence handling.
- Provide cycle closure with strict accounting rules and rollover handling.

## 2. Product Scope (V1)

Included:

- Single-owner budgets with future-ready multi-user model.
- Multiple budgets per user.
- Pay-cycle budgeting with configurable cycle boundaries.
- Goal types:
  - Income
  - Expense (hard cap)
  - Savings recurring
  - Savings target-by-date (parent + generated child goals)
- Cross-cycle savings pools linked to savings goals.
- Immutable append-only allocation events.
- Event statuses: pending, reconciled.
- AI chat intents:
  - onboarding
  - create/update goal
  - create allocation event
  - analytics Q&A
- Merchant learning rules per user.
- In-app chat confirmation cards for all write actions.
- Background alerting via cron/jobs.

Out of scope for V1:

- Bank integrations / automatic bank sync.
- Foreign exchange and multi-currency conversions.
- Goal priorities (must-fund vs nice-to-have).
- Editing/deleting historical closed-cycle events.

## 3. Core Domain Concepts

### 3.1 Budget

- Owned by a single user.
- A user may own multiple budgets.
- Stores configuration:
  - base currency
  - locale/date format
  - timezone
  - week start
  - cycle template (for example 15th -> 14th)
- Money precision rule (V1):
  - whole-dollar amounts only (no cents)
  - stored and computed as signed integers

### 3.2 Cycle

- States: `open`, `closed`.
- Allocation events may only be added to the currently open cycle.
- Closed cycles are read-only.
- New cycle event posting is blocked until prior cycle is closed.

### 3.3 Goal

- States: `active`, `soft_deleted`.
- Goal types:
  - `income`
  - `expense`
  - `savings_recurring`
  - `savings_target_parent`
  - `savings_target_child`
- Savings goal types must link to exactly one `savings_pool_id`.
- Amounts are editable mid-cycle; progress recalculates immediately.
- Delete allowed only when cumulative goal event balance equals zero.

### 3.4 Allocation Event

- Append-only and immutable.
- Corrections are made with additional offsetting events (including negative amounts).
- Required fields:
  - budget_id
  - cycle_id
  - goal_id
  - type (income/expense/savings inferred from goal)
  - amount (signed integer whole dollars)
  - status (`pending` or `reconciled`)
  - created_at (system timestamp only; no backdating)
- Optional fields:
  - savings_pool_id (required when goal type is savings)
  - merchant
  - tags
  - note
  - metadata

### 3.5 Income Pool

- Represents funds available to allocate in the cycle.
- Pending and reconciled events both reserve/affect available funds for enforcement.
- Expense/savings allocations cannot exceed available funded pool.
- Underfunded allocations are hard-blocked and surfaced in chat.

### 3.6 Savings Pool

- Cross-cycle store of savings balances, scoped to a budget.
- Not cycle-scoped; balance carries forward indefinitely.
- A savings goal contributes to or withdraws from one linked savings pool.
- Query examples like "How much will I have in My Savings by November?" are computed from:
  - current pool balance
  - planned contributions to that date
  - planned withdrawals to that date
- Pool continuity is independent of cycle goal renames.

## 4. Accounting Rules and Invariants

1. Expense goals are hard caps.
2. Allocation that exceeds goal cap is blocked.
3. Allocation that exceeds available pool is blocked.
4. Pending events reserve funds immediately.
5. Savings allocations follow the same pool funding and cap rules as expenses.
6. Savings allocation event effects:
   - positive savings event: decrease `income_pool`, increase linked `savings_pool`
   - negative savings event: decrease linked `savings_pool`, increase `income_pool`
7. Closed cycle blocks all new allocation events.
8. Pending events must be resolved before cycle close.
9. No event can be posted to a past cycle.
10. All write actions produce a chat confirmation card.
11. Money values must remain whole-dollar signed integers end-to-end.

## 5. Savings Target-by-Date Behavior

Model:

- Parent target goal tracks total target amount and due date.
- Child goals are generated per cycle with planned contribution.

Behavior:

- Initial child plan distributes remaining target evenly across remaining cycles.
- Re-alignment occurs only during cycle close.
- Re-alignment updates future child goals, not closed-cycle history.

## 6. Cycle Close Workflow

Cycle close is a guided checklist and must complete in order:

1. Resolve all pending events (block until complete).
2. Review goal outcomes (over/under target).
3. Run adjustment sweep:
   - bring goal balances to intended target state via adjustment events
   - return released funds to income pool where applicable
4. Confirm rollover plan.
5. Create next cycle.
6. Create rollover allocation into next cycle `income_adjustment` goal.
7. Re-align target-by-date savings child goals for remaining cycles.

System-generated close actions run only after user confirmation.

## 7. AI Assistant Contract

### 7.1 Intent Classification (V1)

- `onboarding`
- `goal_management`
- `allocation_create`
- `analytics_query`

### 7.2 Write Safety Policy

- Early usage: require confirmation by default.
- Confidence-based auto-write can be enabled/configured later.
- Every write returns a confirmation card with:
  - action summary
  - entities affected
  - before/after values
  - result status

### 7.3 Merchant Learning Policy

Matching order:

1. Exact merchant rule
2. Alias/fuzzy merchant rule
3. Tag/heuristic/LLM fallback

Unknown merchant behavior:

- Ask user for goal mapping.
- Save mapping per user after confirmation.
- Allow user to inspect and adjust mappings via chat.

## 8. Analytics and Metrics

### 8.1 Required V1 Metrics

- Goal progress (actual vs target)
- Remaining funded pool balance
- Savings pool balances
- Forecasted savings pool balance by date
- Pending vs reconciled impact
- Burn rate (on-demand per goal)

### 8.2 Burn Rate Formula (Canonical for V1)

Target interpretation: below `100%` is healthy.

```php
$actualDaily = $spentAmount / max(1, $elapsedDays);
$budgetedDaily = $goalAmount / max(1, $totalCycleDays);
$burnRate = ($actualDaily / $budgetedDaily) * 100;
```

Equivalent:

```php
$burnRate = ($spentAmount * $totalCycleDays / max(1, $goalAmount * $elapsedDays)) * 100;
```

Burn-rate alerts are configurable per goal and have no forced defaults.

## 9. UI Specification (V1)

Layout:

- Centered content area, two columns.
- Left: chat interface.
- Right: stacked dashboard cards.

Cards:

1. Income Allocation
   - Stacked bar: planned allocation breakdown (expense vs savings goals)
   - Stacked bar: actual allocation breakdown (expense vs savings events + income pool balance)
   - Savings pool summary for linked goals
2. Activity Timeline
   - Bar chart of allocation events over the open cycle
3. Cycle Progress
   - `Day N of N`, progress bar, percentage

Chat rendering:

- Use structured JSON response cards for confirmations and metrics.
- Start with JSON-render style rendering; keep UI contract component-agnostic so Tool UI can be evaluated later.

## 10. Tech Stack Decisions

- Backend: Laravel 12, PHP 8.5
- Frontend: Inertia v2 + React 19
- Auth: Fortify email/password + Socialite (Google, Apple)
- DB: MongoDB
- AI orchestration: Laravel AI SDK
- Provider/model: OpenRouter + Anthropic Sonnet
- Async/background: Laravel queues/jobs + cron checks
- Realtime/alerts plumbing: Laravel broadcasting for in-app flows
- Formatting: Pint
- Testing:
  - Backend: Pest/PHPUnit
  - Frontend: Vitest + React Testing Library
  - Optional E2E: Playwright

## 11. Data Model (Mongo Collections)

Proposed collections:

- `users`
- `budgets`
- `budget_members` (future-ready; owner-only in V1)
- `budget_settings`
- `cycles`
- `goals`
- `savings_pools`
- `allocation_events`
- `merchant_rules`
- `assistant_runs`
- `alert_rules`
- `alerts`

Suggested indexes:

- `cycles`: `{ budget_id: 1, status: 1, starts_at: 1 }`
- `goals`: `{ budget_id: 1, cycle_id: 1, type: 1, status: 1 }`
- `goals`: `{ parent_goal_id: 1 }`
- `goals`: `{ budget_id: 1, savings_pool_id: 1, type: 1 }`
- `savings_pools`: `{ budget_id: 1, name: 1 }` unique
- `savings_pools`: `{ budget_id: 1, deleted_at: 1 }`
- `allocation_events`: `{ budget_id: 1, cycle_id: 1, goal_id: 1, status: 1, created_at: 1 }`
- `allocation_events`: `{ budget_id: 1, cycle_id: 1, created_at: 1 }`
- `allocation_events`: `{ budget_id: 1, savings_pool_id: 1, created_at: 1 }`
- `merchant_rules`: `{ user_id: 1, normalized_merchant: 1 }` unique
- `alerts`: `{ budget_id: 1, cycle_id: 1, resolved_at: 1 }`

## 12. Application Services (Planned)

- `CycleService`
  - open/close cycle
  - enforce close checklist
  - create rollover adjustment actions
- `GoalService`
  - create/update/delete goal with invariants
  - parent/child savings management
- `AllocationService`
  - create immutable events
  - enforce pool and goal cap checks
  - pending/reconciled transitions
- `PoolService`
  - compute available/reserved/projected balances
  - apply bidirectional income/savings pool transfer effects from savings events
- `SavingsPoolService`
  - maintain balances and forecast-by-date projections
  - enforce savings pool underflow rules for negative savings events
- `MerchantRuleService`
  - resolve merchant -> goal
  - learn/update/remove rules
- `AssistantOrchestrator`
  - intent classification
  - action proposal/execution
  - confirmation card payloads
- `AnalyticsService`
  - progress metrics
  - burn rate and projections
  - savings pool balance and forecast-by-date answers
- `AlertService`
  - scheduled checks and background notifications

## 13. Route and UI Surface (Planned)

Web/Inertia pages:

- Budget workspace (chat + dashboard cards)
- Goals management
- Cycle close review flow
- Merchant mappings management
- Settings (locale/currency/cycle/auth preferences)

API endpoints (internal UI + agent actions):

- Goal CRUD (with soft-delete constraints)
- Allocation event create/reconcile
- Cycle close workflow actions
- Analytics snapshot endpoints for card widgets
- Merchant rule CRUD
- Assistant action endpoint (intent -> proposal -> execute)

## 14. TDD-First Delivery Plan

### Milestone 1: Domain + Invariants

- Pest tests for:
  - pool underfund block
  - goal cap block
  - immutable events
  - goal delete only at net-zero
  - savings event transfer effects between income/savings pools
  - no posting to closed/past cycle

### Milestone 2: Cycle Lifecycle

- Pest tests for:
  - close blocked by pending events
  - checklist sequence
  - rollover adjustment generation after confirmation
  - next-cycle creation behavior

### Milestone 3: Savings Parent/Child

- Pest tests for:
  - savings pool linkage required for savings goals
  - child generation on creation
  - no mid-cycle regeneration
  - re-alignment on cycle close only
  - forecast-by-date calculations across multiple cycles

### Milestone 4: Assistant + Merchant Learning

- Pest tests for:
  - intent routing
  - confirmation-card payload shape
  - unknown merchant mapping prompt path
  - exact/fuzzy/heuristic resolution ordering

### Milestone 5: UI Shell + Dashboard

- Vitest/RTL tests for:
  - two-column layout
  - widget card rendering
  - confirmation card rendering in chat
  - pending vs reconciled visual distinctions

### Milestone 6: Alerts + Background Jobs

- Pest tests for:
  - scheduled checks create alerts
  - alerts only for background tasks
  - chat writes do not create background notifications

## 15. Acceptance Criteria for V1

V1 is done when:

1. User can complete onboarding via agent with minimum required setup.
2. User can create and manage goals per open cycle.
3. User can add allocation events via free-form chat and UI.
4. System blocks underfunded and over-cap allocations.
5. Cycle close workflow is enforced and creates rollover adjustments.
6. Savings target parent/child re-alignment runs on close.
7. Dashboard cards show live cycle data and savings pool summaries.
8. Confirmation cards are shown for every assistant write action.
9. Core backend and frontend behaviors are covered by automated tests.
10. User can ask projected savings-by-date questions and receive deterministic results.
