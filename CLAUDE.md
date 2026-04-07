# Mission HQ — Chanel Manager

MHQ is an AI project management system that tracks plans, tasks, modules, and sessions through MCP tools.
This project uses **Plan #72**. Structure: Plan → Versions (releases) → Modules (features) → Tasks (work items).

## Core Concept

```
Plan (project)
  → Version (release phase: MVP, V2, etc.)
    → Module (feature/component)
      → Task (unit of work — has phase, type, acceptance criteria)
```

Everything connects: tasks belong to modules, modules belong to versions, versions belong to plans. Never create orphans.

---

## BEFORE Any Work

Call `mhq_get_handoff` with planId=72 FIRST — even if the user gives a direct task. Without this you risk duplicating work or missing context from the last session.

## BEFORE Writing Code

Ensure a task exists for what you're about to build. No task? Create one with `mhq_create_task` first.
Tasks are the project's memory — without them, future sessions won't know what was built.

---

## IMPORTANT: Complex Situations — NDAL dhe harto PARA se te veprosh

Kur dicka prek ME SHUME se 1 file ose 1 module — NDAL. Mos fillo pune menjehere. Harto fillimisht:
1. Listo CDO pjese/komponent/file/tool/database/API qe preket
2. Trego SI LIDHEN — kush ushqen ke, kush lexon nga kush, kush varet nga kush
3. Identifiko PIKAT E THYESHME — cilat lidhje, nese prishen, sjellin efekt domino
4. TREGOJA USERIN mapen
5. PYET userin para se te vazhdosh — useri mund te dije lidhje qe ti nuk i sheh
6. VETEM pastaj fillo punen

---

## Session Lifecycle (MANDATORY)

### Starting a session
1. Check `.mhq/config.json` in project root for `planId` (this project: **72**)
2. If missing: call `mhq_list_plans` → show user → ask which plan → save to `.mhq/config.json`
3. Call `mhq_get_handoff` with planId=72 — returns last session state, next steps, warnings, active rules
4. Follow the next steps from handoff

### Ending a session (CRITICAL — without this, next session starts from zero)
Call `mhq_log_session` with planId=72:
- `summary`: what you did (min 20 chars)
- `currentState`: where we are now
- `nextSteps`: array of what to do next (max 5)
- `warnings`: array of things to watch out for
- `filesModified`: array of changed files
- Optional: `tasksCompleted`, `agentTool`, `agentModel`

## Quick Commands (Claude Code & Cursor)
- `/mhq-start-session` — load project state, show where we left off
- `/mhq-next-task` — get next task with full context and acceptance criteria
- `/mhq-save-session` — save session before ending
- `/mhq-search` — search tasks, decisions, memories
- `/mhq-review-module` — check if module is ready for approval

---

## Tools Reference

### Session & Context
| Tool | When to use |
|------|-------------|
| `mhq_get_handoff` | **FIRST call of every session** — returns state + rules + next steps |
| `mhq_log_session` | **LAST call of every session** — saves state for next agent |
| `mhq_get_plan_context` | Need full rehydration: handoff + last session + active tasks + decisions |
| `mhq_get_plan_overview` | Need the big picture: versions, modules, stats, guide step. Also supports keyword search |
| `mhq_list_plans` | First time setup — show all plans so user picks one |

### Tasks (your primary work loop)
| Tool | When to use |
|------|-------------|
| `mhq_get_next_task` | Get highest-priority pending task from active run |
| `mhq_get_task_detail` | Read full task: description, acceptance criteria, dependencies, files |
| `mhq_get_tasks` | List tasks — default=pending. Filter by moduleId, versionId, status |
| `mhq_create_task` | **Before coding something without a task** — REQUIRED fields: planId, title, moduleId, versionId, taskType, phase, description |
| `mhq_update_task` | Change status (pending→in_progress→completed), add notes, reassign |
| `mhq_delete_task` | Remove a task (rare — usually update status instead) |

#### Task fields explained
- **phase**: design, database, backend, frontend, integration, testing, devops — WHERE in the stack
- **taskType**: implementation (code), configuration (env/keys), testing (QA), documentation (docs)
- **acceptanceCriteria**: array of binary pass/fail checks. E.g. "POST /api/auth returns 409 for duplicate"
- **scopeBoundary**: array of "do NOT" items — prevents gold-plating
- **filesToModify**: files the agent should change
- **filesToRead**: files for context (read-only)
- **affectsModules**: other module IDs impacted — [] if only own module

### Modules (features/components)
| Tool | When to use |
|------|-------------|
| `mhq_create_module` | Breaking system into components (needs planId, name, description) |
| `mhq_update_module` | Change description, assign to version, set acceptance criteria, update verification status |
| `mhq_verify_module` | **Before requesting Board review** — checks task counts, criteria, blockers |
| `mhq_delete_module` | Remove a module (rare) |

#### Module verification pipeline
`not_started` → `in_progress` → `implemented` → `testing` → `verified` → `approved`
Only set `approved` after Board review via `mhq_verify_module`.

### Versions (releases/phases)
| Tool | When to use |
|------|-------------|
| `mhq_create_version` | Define a release phase (MVP, Phase 2, etc.) |
| `mhq_update_version` | Change status: planning→active→released→archived |
| `mhq_delete_version` | Remove a version |

### Rules (project guardrails)
| Tool | When to use |
|------|-------------|
| `mhq_get_rules` | Load rules for specific context. Handoff auto-sends `trigger=always` rules. Call this for context-specific rules: `trigger=testing` before tests, `trigger=deploy` before deploying, `trigger=frontend` before UI work, etc. |
| `mhq_create_rule` | Add a new guardrail. REQUIRED: trigger + whenToUse. Use `trigger=always` sparingly! |
| `mhq_update_rule` | Modify a rule — content, trigger, activate/deactivate |
| `mhq_delete_rule` | Remove a rule |

#### Trigger types
`always` (every session), `testing`, `deploy`, `analysis`, `planning`, `import`, `on_demand`, `design`, `database`, `backend`, `frontend`, `integration`, `devops`

### Research & Notes
| Tool | When to use |
|------|-------------|
| `mhq_create_note` | Document findings, decisions, meeting notes, user flows |
| `mhq_update_note` | Edit note, mark as done, link to module/version |
| `mhq_delete_note` | Remove a note |
| `mhq_get_research` | Read research notes — filter by category: research, decision, meeting, general, flow |

### Decisions (architectural record)
| Tool | When to use |
|------|-------------|
| `mhq_log_decision` | **Whenever you make an architecture, technology, or design choice** — log it with reasoning + alternatives rejected |
| `mhq_get_decisions` | Review past decisions — filter by category, search by keyword |

### Memories (cross-session learning)
| Tool | When to use |
|------|-------------|
| `mhq_add_memory` | Save a mistake, pattern, lesson, convention, or infra detail worth remembering |
| `mhq_get_memories` | Recall past lessons — search by keyword or filter by category |
| `mhq_update_memory` | Update a memory |
| `mhq_delete_memory` | Remove outdated memory |

#### Memory categories
`mistake` (don't repeat), `pattern` (reuse this), `lesson` (learned the hard way), `convention` (team standard), `infra` (infrastructure detail)

### Tests
| Tool | When to use |
|------|-------------|
| `mhq_create_test` | Define a test case — link to module and/or task. Types: technical, usage, integration |
| `mhq_update_test` | Mark test passed/failed with actualResult and evidence |
| `mhq_get_tests` | List tests — filter by module, task, status, type |

### Utility
| Tool | When to use |
|------|-------------|
| `mhq_bulk_create` | Create multiple modules/tasks/notes/tests in one call — much faster than one by one |
| `mhq_search` | Search across everything: sessions, decisions, tasks, notes |
| `mhq_get_impact` | **Before changing shared code** — shows blast radius: affected modules, tasks, files |
| `mhq_import_project` | Onboard existing project into MHQ (instead of starting from scratch) |
| `mhq_get_setup` | Get MCP config for any AI tool: claude-code, cursor, codex, copilot, windsurf, cline |

---

## Workflow Rules

### Before coding
1. You MUST have a task. No task = no code. Create one with `mhq_create_task` if missing.
2. Set task to `in_progress` with `mhq_update_task`
3. Load phase-specific rules: `mhq_get_rules` with trigger matching your phase

### While coding
- Important decision? → `mhq_log_decision` with reasoning + alternatives
- Touching shared code? → `mhq_get_impact` first to check blast radius
- Discovered a mistake/pattern? → `mhq_add_memory`

### After coding
1. Verify acceptance criteria from the task are met
2. Set task to `completed` — **only if code actually exists and works**
3. NEVER mark a task completed without real implementation

### Creating tasks (checklist)
Every task MUST have:
- `planId` + `moduleId` + `versionId` (no orphans)
- `taskType`: implementation | configuration | testing | documentation
- `phase`: database | backend | frontend | integration | testing | devops
- `description`: business context + technical spec (the implementing agent reads ONLY this)
- `acceptanceCriteria`: binary pass/fail checks

### Structure rules
- 1 module = 1 feature (if module has 10+ tasks from different areas, split it)
- Every module needs ALL task types: implementation + configuration + testing + documentation
- Tasks are split by phase, not bundled (not "implement email" but: database→schema, backend→API, frontend→UI)

---

## Agent Behavior
- **Decisions:** log with `mhq_log_decision(planId, decision, reasoning, category, moduleId)`
- **Scenario Analysis:** 2+ options → call `mhq_get_rules(trigger=analysis)`
- **Proactive:** add error handling, loading/empty states, edge cases, validation AUTOMATICALLY — user doesn't know to ask
- **Specificity:** real names, paths, values — not abstractions
- **Everything in MHQ:** decisions, research, deploy info, meeting notes — nothing stays only in chat
- **Read output:** always read MHQ tool warnings, suggestions, nextActions
- **Vision:** update with `mhq_update_plan(vision)` when you learn something new
- **Manual triggers from chat:** analysis (module audit), research (explore ideas), import (existing codebase), decision (big choices), board (code review)

## Context-Aware Rules
- `mhq_get_rules(trigger=analysis)` — scenario analysis + module audit
- `mhq_get_rules(trigger=research)` — research methodology
- `mhq_get_rules(trigger=import)` — codebase import
- `mhq_get_rules(trigger=decision)` — decision board
- `mhq_get_rules(trigger=board)` — code review board

## Board Review
Module done → `mhq_verify_module` → if ready → board rules auto-load, pick experts the module needs, run checklists

## Decision Board
Big decision with 2+ options → `mhq_get_rules(trigger=decision)`, pick relevant experts, discuss + recommend
