# Mission HQ — planId: 1

Session start → call `mhq_get_handoff(planId=1)`. Session end → call `mhq_log_session(planId=1)`.

## THE ONE RULE — no code without a task
NEVER write or edit code unless an in-progress task exists for it. If the user asks for something and no task covers it: STOP → `mhq_create_task` → `mhq_get_task_detail` → THEN build. **No task = no code.**
ONE task per edit: finish the current task and `mhq_update_task(status=completed)` BEFORE you open the next task's files. Never batch-implement several tasks and mark them all at the end — if the session dies, untracked work is invisible to the next agent.

## WORK THE WHOLE WORKSTREAM (don't leave tasks silently undone)
When you're handed a batch of related tasks (a WORKSTREAM), LIST every task up front, then work through ALL of them in order, one at a time. Do NOT stop at a "clean unit" and leave the rest. At the END of your reply, show the full workstream as a checklist with every task marked **✅**. If you genuinely had to leave anything undone, **DECLARE it explicitly** (which tasks, and why) — never stop silently. A workstream is not done until every task is ✅ or openly accounted for.

## HOW YOU TALK TO THE USER (plain language — they own the vision, not the jargon)
Explain everything as a concrete STORY — before → after, "what you'll now see" — not abstract tech. DEFINE every technical term the first time you use it, right there in the sentence (the user owns the vision but may not be deep-technical, and forgets things built weeks ago). Address them by name. A correct answer they can't follow is a FAILED answer — when you ship something, say plainly what changed for THEM, where to see it, and what to do next.

## How MHQ works
`Plan → Version (release phase) → Module (feature) → Task (unit of work)`. A Phase = a Version. A task's `phase` field is its work DISCIPLINE (database/backend/frontend/…), NOT a project phase. Tasks carry taskType + acceptanceCriteria. Knowledge survives in MEMORIES (auto-loaded), things you can't decide now become PROPOSALS, and SKILLS give you the right professional lens.

## Session start — obey the handoff MODE
`mhq_get_handoff(planId=1)` returns ONE mode. Obey it — do not assume:
- **CONFIG** — your local setup is broken → fix config first (run setup), nothing else.
- **PROJECT_SETUP** — brand-new project → create structure (version → modules → tasks); do NOT jump into research/code.
- **IMPORT** — an existing codebase to onboard → map it into modules/tasks (brownfield); do NOT ask "what do you want to build?".
- **normal** — work the guide; `guide.currentStep` tells you where you are.

### The guide (project lifecycle)
The guide walks a project from discovery → research → planning → design → implementation → review/launch, one step at a time. The handoff names your CURRENT step and its job — **follow it; don't skip ahead or assume**. Honor every BLOCK the tools return, and the module-approval **GATE** (needs a recorded BOARD-VERDICT memory).

### First contact — brand-new empty project
No modules/tasks yet → handoff sends you to PROJECT_SETUP or an early guide step. Follow it: establish the vision, then create version → modules → tasks (load `mhq_get_skills(planId=1, trigger="planning")`). Structure first — never improvise code before a task exists.

## Implementation flow (every task)
1. `mhq_get_tasks(moduleId)` — see what's pending.
2. `mhq_get_task_detail(taskId)` — **MANDATORY before coding.** It is the ONLY point that auto-loads the module's architecture + convention memories, the phase skill, and the acceptance criteria. Skip it and you code blind — wrong patterns, missed requirements, re-solving fixed bugs.
3. Implement following that context. Touch ONLY what the task requires — no drive-by refactoring or cleaning up code you didn't change. Touching shared code → `mhq_get_impact` first.
4. `mhq_update_task(status=completed)` — immediately. Completion is BLOCKED until the module has an architecture memory + a convention memory; if blocked, create them, then mark complete.

## Memories — knowledge that survives sessions
Without memories every session restarts from zero. They auto-load ONLY via `mhq_get_task_detail`. Create with `mhq_add_memory(relatedModules=[…])`:
- **architecture** — after building/changing a module: files, flow, connections.
- **convention** — after establishing patterns: stack, naming, imports.
- **lesson / mistake** — when something broke or surprised you: Problem → Solution → WHY. The next agent WILL hit it.

After a module, record its data contracts: `mhq_update_module(moduleId, dataContracts={outputs:[…], consumes:[…]})` — board review verifies cross-module connections.

## Skills — load the lens before working
`mhq_get_skills(planId=1, trigger=X)`: pm · planning · backend · frontend · database · testing · devops · design · research · integration · analysis. (`get_task_detail` auto-loads the phase skill; `get_proposals` auto-loads pm.)

### Board + Jury — the quality ritual
A dynamic panel of task-relevant experts critiques, then a jury VOTES — never ship the first idea. Run it: choosing an approach / weighing options · the user says "board" or is unhappy · after a substantive implementation (review to max) · stuck debugging. Skip trivial 1-file reversible changes. To run: `mhq_get_skills(planId=1, trigger="on_demand")` loads **#92** (and **#93** for high-stakes/irreversible), READ it fully, THEN run — or invoke `/board-mhq <directive>`. MHQ auto-surfaces a REVIEW reminder when you complete a substantive task, a DECIDE-testing reminder when you create test tasks, and the module-approval GATE. The USER is the final authority.

## Proposals — deferred work & decisions
Decide now → just do it (save a convention memory if it matters). Can't decide now (trade-offs, "maybe later", features beyond the MVP) → ASK the user, then `mhq_create_proposal`. Every feature the user names beyond the MVP becomes a proposal or it's forgotten. Never create one silently. When the handoff says "X open proposals", review with `mhq_get_proposals(status="open")` (load trigger=pm first).

## Testing
Every module needs testing tasks — module approval is GATE-blocked without them (and a DECIDE-testing board reminder fires when you create them). Tests verify the acceptance criteria; failing tests block approval.

## Complex / cross-cutting changes
When a change touches MORE than 1 file or module — STOP. List what's affected, show who depends on whom, name the fragile points, tell the user the map, ASK before proceeding.

## ALWAYS REPORT FRICTION (real-time retrospective — every session)
At session end (and as you go), tell the user the PROBLEMS you hit along the way — anything that ate time, confused you, a gate that mis-fired, a doc/memory gap, a trap you had to re-derive — even when the work went fine. Route each one: a real technical lesson/bug → `mhq_add_memory(category=lesson|mistake, phase, relatedModules)`; an MHQ-system rough edge (a mis-firing gate, a confusing tool, a missing hint) → `mhq_create_proposal(source=agent_finding)`; a workflow/communication rule → flag it for this CLAUDE.md. WHY: every session's friction is the system's NEXT improvement, and repeated friction becomes an automatic fix. Never skip this — silent friction is lost improvement.

## Always
- Read MHQ tool output — BLOCKs (can't proceed), memory hints, flow reminders, quality warnings are guidance, not noise.
- Before `git push`, scan for leaked secrets (`sk-ant-*`, `ghp_*`, `github_pat_*`, private keys, passwords in connection strings) and remove them.

## Tools (index)
- **Navigation:** `mhq_get_handoff` · `mhq_get_plan_overview` · `mhq_search`
- **Work:** `mhq_get_tasks` · `mhq_get_task_detail` · `mhq_update_task`
- **Knowledge:** `mhq_add_memory` · `mhq_get_memories` · `mhq_create_proposal` · `mhq_get_proposals`
- **Structure:** `mhq_create_module` · `mhq_create_version` · `mhq_create_task` · `mhq_verify_module` · `mhq_update_module` · `mhq_get_impact`

---
*Example (illustrative — NOT your project): a module "Orders" outputs `["orders table","/api/orders endpoint"]` and consumes `["users table"]`. A module that holds several independent integrations → split each into its own sub-module via `parentModuleId`, so each gets its own architecture memory, tests, and board review.*

<!-- mhq-manual: v3 — managed by MHQ; refresh via Check Config → Repair, do not hand-edit -->
