# Housekeeping — Cleaning Checklist + Inspection Tracking

**Date:** 2026-07-05 · **Plan:** MissionHQ planId=1, module 5 (Housekeeping) · **Tasks:** 205–208

## Goal
Two improvements to the housekeeping board:
1. When a housekeeper presses **Fillo**, open a **full-screen, phone-first "cleaning mode"** with a live **timer** and a **checklist** of what to do in the room (sheets, towels, bathroom, amenities…). **Perfundo** is blocked until every item is checked.
2. **Inspekto** stays a single click but now **records who inspected and when** (audit trail shown on the card).

## Decisions (from Marjus)
- Inspection: single click + tracking (who/when). **No availability gating** — the room still frees at `completed`, as today.
- Perfundo: **blocked until all checklist items done** (enforced server-side, not just the button).
- Templates: **one checklist per task type** (checkout / stayover / deep).
- Managed in **Settings → Housekeeping** (JSON setting, like `task_types`).

## Data model
- `Setting housekeeping.checklists` (json): `{ "<type>": ["label", …] }`. Built-in defaults per type when unset.
- `cleaning_tasks` new columns (all nullable):
  - `checklist` (json): per-task **snapshot** `[{label, done, done_at}]` — copied from the template at start so editing the template later never corrupts an in-flight task.
  - `started_by`, `inspected_by` (FK users, nullOnDelete), `inspected_at` (timestamp).
  - `started_at` already exists but was never written — now populated at Fillo.

## Flow
- **Fillo** (`pending→in_progress`): snapshot template into `checklist`, set `started_at`+`started_by`, then navigate to the full-screen clean view.
- **Clean view** (`GET /housekeeping/{task}/clean`): live timer from `started_at`; tap to toggle items → `PATCH /housekeeping/{task}/checklist`; Perfundo disabled until 100%.
- **Perfundo** (`in_progress→completed`): server rejects if any item undone; frees room (null-guarded).
- **Inspekto** (`completed→inspected`): sets `inspected_by`+`inspected_at`; shown on card.

## Fragile points
1. Template edit must not alter started tasks → **snapshot at start**.
2. Enforce-complete on the **server**, not only the disabled button.
3. Timer derived from **server `started_at`** → correct across refresh/reopen.
4. **Ownership/IDOR:** clean page + checklist PATCH deny a non-assigned caller lacking a manage permission.
5. `$task->room` **null-guarded** (mistake #93). No `FIELD()` on SQLite (mistake #58).
6. Legacy rows: `checklist=null` treated as "no list" → not blocked, no backfill.

## Files
- **DB:** new migration on `cleaning_tasks`.
- **Backend:** `CleaningTask.php`, `CleaningTaskController.php` (+`updateChecklist`, +`clean`), `SettingsController.php`, `routes/web.php`.
- **Frontend:** `Housekeeping/Clean.vue` (new), `Housekeeping/Index.vue`, `Settings/Tabs/HousekeepingTab.vue`.
- **Tests:** `tests/Feature/CleaningChecklistTest.php`.

## Out of scope
Room-availability gating on inspection; separate checklist-items table; drag-and-drop; new permissions.
