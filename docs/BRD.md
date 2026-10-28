# BUSINESS REQUIREMENTS DOCUMENT

## Digital Product, Project, Task & KPI Management Platform

**Document Version:** 1.0
**Date:** October 2026
**Status:** Proposed
**Source:** 3-Year Digital Product Roadmap

---

# 1. Executive Summary

The organization currently uses a spreadsheet-based roadmap to manage strategic initiatives, features, KPIs, timelines, and business goals.

The proposed system will transform this roadmap into a centralized web-based **Digital Product & Project Management Platform** that allows management, product owners, project managers, programmers, QA, support teams, and other stakeholders to manage the complete lifecycle of work.

The platform will connect:

**Business Goal → Strategic Initiative → Project → Feature → Task → Bug/Support Ticket → Delivery → KPI → Business Outcome**

The system will not simply measure whether programmers completed tasks. It will measure whether delivered work contributes to business objectives while also providing engineering delivery, quality, support, and operational metrics.

The platform will support:

* 3-year strategic roadmap management
* Strategic initiative management
* Project management
* Feature/product backlog
* Task and subtask management
* Programmer work management
* Sprint management
* Bug reporting
* Support ticket management
* QA/testing workflow
* KPI tracking
* Project success measurement
* Programmer/team performance metrics
* Management dashboards
* Automated notifications
* Audit trail
* Reporting and analytics

---

# 2. Business Problem

The existing Excel roadmap provides strategic visibility but does not provide an operational system for executing the roadmap.

The spreadsheet can answer:

* What are we planning?
* When should it happen?
* What is the business goal?
* What KPI should improve?
* What features are planned?

However, it does not sufficiently answer:

* Who is currently working on the initiative?
* What tasks are being worked on?
* What is blocked?
* Which programmer owns a task?
* How much work is completed?
* What bugs were discovered?
* How many bugs escaped to production?
* Which projects are delayed?
* Why is a project delayed?
* How much support work is consuming development capacity?
* Are delivered features actually improving the target KPI?
* Which initiatives are generating measurable business value?
* What is the current workload of each team?
* Which issues require management attention?

The proposed application will close this gap.

---

# 3. Business Objective

The primary objective is to create a single system for managing and measuring the organization's digital product development lifecycle.

### Primary objective

> Convert the strategic roadmap into measurable, executable work while providing management with real-time visibility into delivery, quality, productivity, support workload, and business outcomes.

---

# 4. Strategic Alignment

The system must preserve the structure of the existing roadmap.

### Existing roadmap hierarchy

**Strategic Pillar** (theme / portfolio bucket)

↓

**Strategic Initiative** (business bet + success criteria) — declares **Systems touched** (M2M).

↓

**Delivery Project** (time-bound execution — not the same as an app/system)

- **Types:** `standard` (normal delivery) or `dmaic` (structured continuous-improvement project using the DMAIC method). Additional types may be added later without a parallel project table.
- DMAIC projects have a linked **DMAIC profile** (phases, gates, RCA, improvements, control plans).
- Projects **assign Features** from initiative Systems into delivery; they do not own the product backlog.

↓

**Business Problem / Opportunity** (optional formal problem statement linked to an initiative; often the Define input for a DMAIC project)

**System** (Reservation App, Seller Portal, etc.) — catalog of apps/services/platforms. Owns the **product backlog**:

**System → Epic → Feature** (Feature may be unassigned / backlog, or scheduled into a Delivery Project)

↓ (once Feature is assigned to a project)

**Task / Subtask**

↓

**Bug / Support Ticket**

↓

**Release**

**KPI Outcome** — linked to initiatives and delivery projects; measures business result (not a strict child of tasks).

Example:

**Cancellation Prevention**

→ Cancellation Risk Indicators

→ Cancellation Risk Project

→ Risk Rules

→ API development

→ Frontend development

→ QA

→ Bug fixes

→ Production release

→ Cancellation Rate

This relationship is fundamental to the proposed system.

---

# 5. North Star

The application should ultimately help management answer:

> **Are we delivering the right work, on time, with acceptable quality, and is that work producing the expected business result?**

The platform therefore has four major measurement dimensions:

### 5.1 Business Outcome

Are business KPIs improving?

### 5.2 Delivery

Are projects and initiatives being delivered according to plan?

### 5.3 Engineering

Is development work being completed efficiently and predictably?

### 5.4 Quality & Support

Are defects and support issues being controlled?

### Measurement layers (do not conflate)

The platform tracks **three conceptual KPI / metric layers** on one `kpis` table via `metric_layer` (plus computed Team Delivery metrics):

1. **Business** (`metric_layer=business`) — scorecards (baseline / target / current) linked to initiatives and delivery projects. Completing tasks does **not** auto-update these.
2. **Process / engineering** — Team Delivery dashboard metrics are **computed** from tasks/bugs (not always stored as KPI rows). Stored KPIs may also use `metric_layer=process` when manually tracked.
3. **Six Sigma** (`metric_layer=six_sigma`) — DPU/DPMO/capability-style metrics where appropriate on DMAIC control plans. **Not** forced onto every software task.

**DMAIC** is a first-class extension of delivery projects (`type=dmaic`): DEFINE → MEASURE → ANALYZE → IMPROVE → CONTROL → CLOSED, with phase gates.

**Support tickets**, formal **Releases/Deployments**, **Sprints**, Feature **DoD checklists**, in-app **notification center**, and **Team Delivery charts** are in scope for the current ops build (Increments 6–8). Still deferred: attachment libraries, knowledge-base articles, full `test_cases`/`test_runs`, time tracking, and AI assistants.

### Technical identity & codes

* **Primary keys:** Application entities (including `users` and domain tables) use **UUID** primary keys and UUID foreign keys. Exceptions that keep integer surrogates: Spatie `roles`/`permissions` ids, `activity_log.id`, `personal_access_tokens.id`, queue/cache tables, and pure pivot row ids (`team_user`, `*_kpi`, member/system pivots, etc.). Morph columns that reference app models store UUID strings.
* **Business codes** (`code` on initiatives, delivery projects, systems, KPIs): **system-generated only** (`GeneratesCode` trait; prefixes `INIT`, `PRJ`, `SYS`, `KPI`). Users must not enter or edit codes in create/edit forms; UI may show the assigned code read-only.

---

# 6. Scope

## 6.1 In Scope

### Strategic Planning

* Strategic focus management
* 3-year roadmap
* Strategic initiatives
* Initiative objectives
* Initiative KPIs
* Initiative success criteria
* Dependencies
* Priority
* Status

### Project Management

* Projects
* Project owners
* Project timelines
* Milestones
* Project health
* Project risks
* Project dependencies
* Project budget/capacity
* Project success metrics

### System catalog & product backlog

* Systems (apps, services, platforms) — shared catalog
* Initiative ↔ System (systems this bet touches)
* System owns Epics and Features (product backlog / roadmap)
* Features are scheduled into Delivery Projects when committed for build
* Owners, status; derived links to projects via assigned features

### Product Management

* System-scoped Epics and Features (backlog)
* Assign Features into Delivery Project Plan
* User stories
* Acceptance criteria
* Product priorities
* Release planning

### Task Management

* Tasks
* Subtasks
* Assignments
* Due dates
* Estimates
* Actual effort
* Status
* Priority
* Dependencies
* Attachments
* Comments
* Activity history

### Engineering Management

* Developer assignment
* Sprint planning
* Workload management
* Development status
* Code/repository reference
* Pull request reference
* Deployment reference
* Developer KPIs
* Team KPIs

### QA

* Test cases
* Test execution
* QA status
* Bugs
* Regression testing
* Release approval

### Bug Management

* Bug reporting
* Severity
* Priority
* Environment
* Reproduction steps
* Screenshots/videos
* Assigned developer
* QA verification
* Resolution
* Production escape tracking

### Support

* Customer/internal support tickets
* Ticket categorization
* SLA
* Priority
* Assignment
* Escalation
* Resolution
* Root cause
* Conversion of support ticket to development task/bug

### KPI Management

* KPI definitions
* Baseline
* Target
* Current value
* Trend
* Owner
* Reporting period
* Success status
* Initiative association
* Project association

### Reporting

* Executive dashboard
* Project dashboard
* Developer dashboard
* QA dashboard
* Support dashboard
* KPI dashboard
* Roadmap dashboard
* Delivery analytics

---

# 7. Out of Scope — Initial Version

The following may be considered future enhancements:

* Full HR performance appraisal
* Payroll
* Employee attendance
* Financial accounting
* Full CRM
* Full customer relationship management
* Advanced AI coding assistant
* Automated code generation
* Source-code hosting

Integrations may be added later.

---

# 8. User Roles

## 8.1 Executive / Management

Can:

* View strategic roadmap
* View KPI dashboards
* View project health
* View initiative performance
* View delivery performance
* View support trends
* View business outcomes
* Drill down from KPI → initiative → project → task

Management should generally not need to manage individual development tasks.

---

## 8.2 Product Owner

Can:

* Create initiatives
* Define product objectives
* Define success measures
* Manage backlog
* Prioritize features
* Define acceptance criteria
* Track product KPIs
* Approve completed features

---

## 8.3 Project Manager

Can:

* Create projects
* Expectations / milestones (auto from epics & releases; optional manual custom)
* Assign tasks
* Manage schedules
* Monitor dependencies
* Track risks
* Monitor project health
* Generate reports

---

## 8.4 Programmer / Developer

Can:

* View assigned work
* Update tasks
* Log effort
* Add comments
* Attach technical references
* Submit work for QA
* Report technical blockers
* Create bugs
* Link commits/PRs
* View personal metrics

---

## 8.5 QA

Can:

* View development tasks
* Create test cases
* Execute tests
* Report bugs
* Verify fixes
* Reject incomplete work
* Approve releases

---

## 8.6 Support

Can:

* Create support tickets
* Categorize tickets
* Assign tickets
* Track SLA
* Escalate issues
* Convert tickets to bugs/tasks
* Communicate resolution

---

## 8.7 System Administrator

Can:

* Manage users
* Manage roles
* Manage permissions
* Configure workflows
* Configure statuses
* Configure KPI definitions
* Configure notification rules
* View audit logs

---

## 8.8 Improvement Lead

Leads structured improvement cycles on `type=dmaic` delivery projects (DMAIC method under the hood).

**Spatie role slug:** `improvement_lead` (formerly `six_sigma_leader`; rename is cosmetic — same permissions).

Can:

* View strategy (pillars, initiatives, problems) and delivery context
* Manage improvement cycles (bootstrap, phases, RCA, improvements, control plans)
* Approve phase gates
* Manage KPI outcomes (including `metric_layer=six_sigma` capability metrics where used)
* View Team Delivery and Improvement overview dashboards

UI copy should prefer “Improvement Lead” / “improvement cycle” over “Six Sigma Leader” jargon. The KPI metric layer value `six_sigma` remains a technical enum for capability-style metrics.

---

## 8.9 Hub-first UX (project-first)

Primary workplace is the **Delivery Project hub** (show page), not a global mega-menu of every entity. The product is **project-first**: day-to-day delivery and improvement execution happen on a project. **Strategy** (Pillar → Initiative → Portfolio) and **improvement entry** (Business Problems, KPI Outcomes, Improvement overview) are layers that stay one click away — not equal primary-nav weight for every role.

Hub tabs (lifecycle stages, not presentation modes): **Overview · Plan · Build · Schedule · Quality · Ship · Improve** (Improve only when `type=dmaic`).

**Presentation modes inside stages:** Build may offer **List | Board** (same tasks); Schedule uses the project Gantt. Do **not** rename or replace lifecycle tabs with Board / List / Table / Timeline as peer global views.

**Improvement streamlines from** an at-risk KPI and/or Business Problem **into** a `type=dmaic` delivery project. Stage work (checklist, RCA, gates, control) runs on that project’s **Improve** tab. Fixes still ship via Plan → Build → Ship on the same project. There is no parallel “improvement app”; `dmaic.show` deep-links into the hub Improve tab. Hub Overview surfaces linked problem/KPIs for dmaic projects so improvement context is visible without leaving the hub.

**App shell:** **Slim left sidebar** + **compact top bar**.

* **Sidebar** — role-primary links (few, **role home first**), a collapsible **More** section grouped as Work items / Quality / Strategy for secondary lists (Epics, Features, Tasks, Sprints, Releases, Pillars, Initiatives, etc.), and a flat **My projects** list (up to ~8 recent delivery projects where the user is owner or member — **not** a Pillar → Initiative folder tree).
* **Top bar** — notifications (bell → inbox) and account menu (log out) only. **No** global “+ Create” that changes by context. Profile opens from the sidebar footer identity control — not duplicated in the top-bar menu.
* **Mobile** — sidebar collapses into a drawer; same link sets.

**Navigation:** Prefer creating work from the hub **section actions** (e.g. Plan → Add feature) with `delivery_project_id` prefilled — not a global header Add that changes by tab.

**Hub Add create:** opens a project-scoped form (`Projects / {Project} / New {Entity}`); Save and Cancel return to the originating hub tab (`return_tab`). Standalone create from global indexes stays flat (`Features / New` → entity show).

**Role primary map** (keep ≤5 links):

* **Developer / QA** — My Work, Projects, Bugs
* **Support** — Support, Projects
* **Project Manager** — Team, Projects, Schedule, Quality
* **Improvement Lead** — Improve (overview), Projects, Schedule, Problems, KPI Outcomes
* **Product Owner / Executive / Admin** — Portfolio, Projects, Schedule, Dashboards (+ Admin when permitted)

Strategy entities remain required in the data model (`initiative_id` on delivery projects). Delivery roles reach Portfolio/Pillars/Initiatives via **More → Strategy**; PO/exec use **Portfolio** as primary.

**Shell non-goals:** generic PM “workspace / studio” hierarchy in the sidebar; Board/List/Table/Timeline as replacements for Plan/Build/Quality/Ship/Improve; global search in this pass.

**Breadcrumbs:** follow the **list root of the current workspace**, not the full strategy hierarchy (e.g. Delivery Project hub → `Projects / {Project}`). Strategy parentage (initiative, pillar) lives in page meta or body links, not as middle trail segments that feel unvisited after list → detail navigation.

**Expectations (milestones):** Hub Overview **Expectations** are sparse checkpoints for stakeholder commitments and project movement—not a second Schedule/Gantt. They are **auto-synced** from dated **System Epics** that have at least one Feature **assigned to this project** (Plan commitments: epic `due_date`) and from **Releases** (Ship commitments: release `target_date`). Features and Tasks do **not** each spawn a milestone; they feed **progress** under the parent epic checkpoint (using only Features of that Epic assigned to this project). Each expectation shows expected date, scope progress (%), and health (`on_track` | `at_risk` | `slipped` | `done`) vs the commitment. Project `target_completion` remains the final project-level commitment (Glance + Expectations movement strip). Rare non-work dates may still be added as **manual** custom expectations; auto rows are edited by changing the epic or release, not by free-text milestone forms. Do not place milestone diamonds on the Gantt in this pass.

**Plan tab:** lists System Epics that have Features assigned to the project, with those Features nested underneath. Primary actions: **Assign from backlog** (Features of initiative Systems not yet on this project) and create Epic/Feature on the System then assign. Do not create project-owned Epics.

**Role homes** (login `/dashboard` redirect):

* **Executive** / **admin** → Executive dashboard
* **Product Owner** (not also executive/admin) → Portfolio
* **Project Manager** (not also executive/admin) → Team Delivery
* **Improvement Lead** (not also executive/admin/PM) → Improvement overview
* **Developer** / **QA** → My Work
* **data_analyst** → Team Delivery
* **process_owner** → Improvement overview
* **Support** → Delivery Projects index (no dedicated dashboard)

---

# 9. Core Application Modules

## Module 1 — Executive Dashboard

The executive dashboard provides a high-level view of the entire digital organization.

### Dashboard sections

**Strategic Roadmap**

* Current year
* Current half-year
* Initiatives
* Projects
* Status
* Delayed initiatives

**Business KPIs**

* Cancellation Rate
* Reservation → Take-out Rate
* Financing Approval Rate
* Average Reservation → Take-out Days
* Take-outs per Seller
* Referral-generated Sales %

**Delivery KPIs**

* Projects on schedule
* Projects delayed
* Features completed
* Tasks completed
* Blocked tasks

**Quality**

* Open bugs
* Critical bugs
* Production bugs
* Bug resolution time

**Support**

* Open tickets
* SLA breaches
* Average resolution time
* Recurring issues

---

# 10. Roadmap Module

The Roadmap module replaces the Executive Roadmap Excel sheet.

Each roadmap item contains:

* Strategic Focus
* Initiative
* Year
* Period
* Business Goal
* Problem/Opportunity
* Expected Outcome
* KPI
* Baseline
* Target
* Owner
* Priority
* Status
* Dependencies
* Projects

### Example

**Strategic Focus:** Cancellation Prevention

**Initiative:** Cancellation Risk Indicators

**Goal:** Reduce preventable cancellations

**KPI:** Cancellation Rate

**Baseline:** 10%

**Target:** 8.5%

**Expected Impact:** Revenue retention

**Projects:** Cancellation Risk Engine

---

# 11. Initiative Module

Each strategic initiative becomes a manageable entity.

### Initiative fields

* Initiative ID
* Name
* Strategic Focus
* Business Goal
* Problem
* Opportunity
* Owner
* Start Date
* Target Date
* Priority
* Status
* KPI
* Baseline
* Target
* Expected Business Impact
* Dependencies
* Risks
* Projects
* Success Criteria

### Initiative success

An initiative should not be considered successful simply because its features were released.

Example:

**Feature delivered:** Yes

**KPI improved:** No

Result:

> Initiative delivered technically but business outcome not achieved.

This distinction is important.

---

# 12. Project Module

Projects represent actual execution.

### Project fields

* Project ID
* Project name
* Initiative
* Project owner
* Product owner
* Start date
* Target completion
* Actual completion
* Status
* Health
* Priority
* Team
* Description
* Success criteria
* KPI
* Risks
* Dependencies

### Project health

The system should calculate:

**Green**

On track.

**Amber**

At risk.

**Red**

Delayed or major issue.

Health can consider:

* Schedule
* Task completion
* Blocked work
* Bugs
* Dependencies
* Scope changes

---

# 13. Task Management

Tasks are the primary execution unit. Prefer linking each task to a **Feature** (`feature_id`) so Plan → Build stays clear. Create from Feature **Build**, Plan feature row **Add task**, or Build **Add task** with Feature select.

### Task fields

* Task ID
* Project
* Feature (preferred; optional for ad-hoc work)
* Epic
* Title
* Description
* Assignee
* Reviewer
* QA
* Priority
* Status
* Start date
* Due date
* Estimate
* Actual effort
* Completion %
* Dependencies
* Blockers
* Bug links
* Git/PR reference
* Comments
* Attachments

### Suggested workflow

**Backlog**

→ Ready

→ In Progress

→ Code Review

→ QA

→ QA Failed

→ Ready for Release

→ Released

→ Done

---

# 14. Programmer Work Management

The system should provide developers with a personal workspace.

### "My Work"

Displays:

* Today's tasks
* Overdue tasks
* Current sprint
* Blocked tasks
* Bugs assigned
* Support escalations
* Code review requests
* Upcoming deadlines
* **Personal Kanban board** (default view) of assigned tasks — see below

### Personal Kanban (My Work)

Flow view of the developer’s **assigned** tasks (not a team/project board).

Columns (left → right), mapped from task statuses:

| Column | Statuses |
| ------ | -------- |
| Backlog | `backlog` |
| Ready | `ready` |
| In progress | `in_progress` |
| Review | `code_review` |
| QA | `qa`, `qa_failed` |
| Blocked | `blocked` |
| Done | `ready_for_release`, `done`, `released` (last 14 days only) |

* Drag card between columns to change status.
* Commitment **estimate** still required when entering `in_progress` (card snaps back with error if missing).
* Soft WIP warning when more than **2** cards are In progress (guidance only — not a hard block).
* List view remains available; process KPI cards stay above the board.
* Bugs remain in the separate open-bugs list (not on the board in this pass).

**Deferred (personal board):** swimlanes, hard WIP limits, bug cards on the board, within-column reorder.

### Team Kanban (Team Delivery)

For leads with `dashboards.team`: a **shared** board of team (or filtered) work so leaders can see what people are doing, what is Ready next, and what recently finished.

* Same columns as Personal Kanban.
* Cards show **assignee** (+ project, due, estimate).
* Filters: team, user, delivery project.
* Soft WIP warning when **any assignee** has more than **2** cards In progress.
* **Attention** strip: overdue, blocked, active work missing estimate.
* Metrics / charts remain on a Metrics tab (or toggle) on the same Team Delivery page.
* **View vs edit:** anyone with `dashboards.team` can **see** the board; dragging/updating status requires `tasks.update` or `tasks.manage` (typically project managers and assignees on their own cards). Executives / POs / Improvement Leads are view-only on the team board unless they also hold task update rights.
* Drag status updates use the same estimate gate as My Work (managers with `tasks.manage` can move any card).

**Deferred (team board):** assignee swimlanes, hard WIP enforcement, auto-assignment from the board.

---

### Developer daily view

Example:

| Work             | Status      | Priority |
| ---------------- | ----------- | -------- |
| Payment API      | In Progress | P0       |
| Login Bug        | QA          | P1       |
| Customer Profile | Code Review | P0       |
| Support #1023    | Blocked     | P1       |

---

# 15. Programmer KPI Framework

The system should avoid measuring programmers primarily through simplistic metrics such as lines of code.

Instead, programmer performance should be measured using multiple dimensions.

**These are process / engineering metrics — not initiative Business KPI Outcomes.** Completing tasks does not auto-update business KPIs (e.g. Cancellation Rate).

## Current developer process KPIs (My Work)

Scored for the **assigned developer’s owned work** (not whole-project blame). Cap early finish at **120%**. Unscored gaps (missing estimate / due date) are shown separately.

### 1. Owned-work delivery score

Baseline **100%** when the task is completed on its committed `due_date`.

* Early: linear bonus up to **120%**.
* Late: linear deduction to **0%** at **26 weeks (~6 months)** past due (~3.846 pts/week).
* Incomplete overdue items use delay from today.
* Aggregate = average of scored items in the period (optionally weighted by estimate days).

### 2. Commitment / estimate score

Task `estimate` is a **commitment in working days**. Required before moving a task to `in_progress`.

* Actual duration = working days from `started_at` → `completed_at` (fallback: `actual_effort`).
* Score = `min(120, 100 × estimate / actual)` when actual > 0.
* Missing estimate → excluded from score (gap).

### 3. Quality score

For bugs assigned to the developer in the period:

* `100 × (1 − reopened_resolved / resolved)` with floor **0** (uses `reopened_count` on resolved/closed bugs).

## Supporting delivery metrics (also available)

### Task Completion Rate

Completed tasks ÷ assigned tasks

### Cycle Time

Time from In Progress → Done (`started_at` → `completed_at`)

### Planned vs Actual Effort

Actual effort ÷ estimated effort (same family as commitment score)

---

## Quality (broader / future)

### Defect Rate

Bugs attributable to delivered work ÷ completed development items

### Production Escape Rate

Production bugs ÷ total bugs

### First-Time QA Pass Rate

Items passing QA on first submission ÷ items submitted to QA

---

## Schedule envelope roll-up

Leaf commitment window = Task `start_date` … `due_date`.

* **Feature / Epic / Project** expose a **rolled schedule envelope** = min(child starts) … max(child ends).
* Planned dates may still be set on Feature/Epic/Project (`start_date` / `due_date` or project `target_completion`); UI warns when the rolled envelope exceeds the planned end.

---

## Schedule Gantt (delivery timeline)

Read-only hierarchical **Gantt** for delivery schedule visibility (process/delivery view — **not** initiative business KPIs).

### Surfaces

* **Portfolio Schedule** (`/schedule`) — all delivery projects on one timeline; expand one project for Epic → Feature → Task detail.
* **Project hub Schedule tab** — full tree for that delivery project.

### Hierarchy (v1 rows)

Delivery Project → Epic → Feature → Task. Subtasks are out of scope for Gantt rows.

### Bar semantics

* **Planned / commitment** bar from planned dates (project `start_date`…`target_completion`; epic/feature `start_date`…`due_date`; task commitment window).
* **Rolled envelope** when rolled dates differ from planned (from schedule roll-up).
* Undated work (no planned and no rolled dates) appears in an **Undated work** list — not as fabricated bars.

### Conflict signals (v1)

1. **Schedule slip** — parent rolled end after planned end; open task overdue; or completed after due date.
2. **Resource overlap** — two open top-level tasks with the same assignee and overlapping date windows.

### Permissions

* Data: `delivery_projects.view`.
* Portfolio Schedule nav: users with `dashboards.team` or `dashboards.executive` (or admin). Developers keep My Work; no separate `schedule.view` permission in this pass.

### Deferred

* Drag-and-drop reschedule / write-back from the chart
* Dependency arrows / critical path
* Sprint swimlanes / personal day calendar
* Multi-assignee split credit
* Business-KPI overlay on the chart

---

## Reliability

### Blocked Time

Total time tasks remain blocked.

### SLA Compliance

Tasks completed within agreed SLA ÷ total SLA-controlled tasks

---

## Engineering Contribution

Possible future metrics:

* Code review participation
* Documentation contribution
* Technical debt reduction
* Automated test contribution
* Deployment reliability
* Incident resolution contribution

These should be interpreted as team/productivity indicators rather than used as a single numerical employee ranking.

---

# 16. Team Delivery Dashboard

Management should be able to see **computed** delivery and quality metrics (separate from Business KPI Outcomes), plus a **Team Kanban** for live flow (see §14 Team Kanban).

### Delivery (current build)

* Tasks completed
* Tasks overdue
* Completion rate
* On-time completion rate
* Average cycle time

*(Deferred: sprint completion, planned vs completed work)*

### Quality (current build)

* Bugs created
* Bugs resolved
* Open critical bugs
* Reopen rate
* Production bugs

### Support (current build)

* Support tickets open / breached SLA
* Average resolution time
* Converted to development work

### Reliability

* Blocked work
* Dependency delays
* Failed deployments
* Rollbacks

---

# 17. Bug Reporting Module

Any authorized user should be able to report a bug.

### Bug form

**Title**

Short description.

**Description**

Detailed explanation.

**Steps to reproduce**

1. Login
2. Open payment screen
3. Select payment method
4. Submit payment

**Expected Result**

Payment should be processed.

**Actual Result**

Payment remains pending.

**Environment**

* Production
* Staging
* Development
* Mobile
* Web

**Severity**

* Critical
* High
* Medium
* Low

**Attachments**

* Screenshot
* Video
* Logs
* Files

**Related Project**

Project association.

**Related Feature**

Feature association.

---

# 18. Bug Workflow

Recommended workflow:

**Reported**

↓

**Triaged**

↓

**Assigned**

↓

**In Development**

↓

**Ready for QA**

↓

**QA Testing**

↓

**Resolved**

↓

**Closed**

If the issue remains:

**Reopened**

↓

**In Development**

---

# 19. Support Ticket Module

Support tickets should be separate from bugs because not every support issue is a software defect.

### Ticket categories

* User Assistance
* Account Issue
* Payment Issue
* Data Issue
* Access Issue
* System Issue
* Bug
* Feature Request
* Other

A support ticket can be converted into:

* Bug
* Task
* Feature request
* Knowledge-base article

---

# 20. Support SLA

Each ticket can have:

* Priority
* SLA response time
* SLA resolution time
* Assigned team
* Assigned user
* Escalation level

Example:

| Priority |       Response | Resolution |
| -------- | -------------: | ---------: |
| Critical |         15 min |      4 hrs |
| High     |           1 hr |      8 hrs |
| Medium   |          4 hrs |     2 days |
| Low      | 1 business day |     5 days |

These values should be configurable.

---

# 21. KPI Management Module

The KPI module converts the Excel KPI Scorecard into a live system.

Each KPI should contain:

* KPI ID
* KPI name
* Definition
* Formula
* Owner
* Data source
* Baseline
* Target
* Current value
* Reporting period
* Direction
* Status
* Trend
* Related initiative
* Related project

### Example

**KPI:** Cancellation Rate

**Formula:**

Cancelled Reservations ÷ Total Reservations

**Direction:**

Lower is better

**Baseline:**

10%

**Target:**

8.5%

**Current:**

9.1%

**Status:**

Improving

---

# 22. KPI Status

The system can calculate status based on target progress.

### Example

**Green**

Target achieved/on track.

**Amber**

Progress below expected trajectory.

**Red**

Significantly below target.

**No Data**

Insufficient data.

The thresholds should be configurable per KPI.

---

# 23. Success Measurement

Every project must define success before development begins.

A project should have:

### Business Objective

What business problem are we solving?

### Output

What are we building?

### Outcome

What should change after release?

### KPI

How will we measure the change?

### Baseline

Where are we today?

### Target

Where do we want to be?

### Measurement Period

When will success be evaluated?

---

# 24. Example: Cancellation Project

### Output

Cancellation Risk Indicators.

### Outcome

Earlier identification of customers likely to cancel.

### Baseline

Cancellation rate: 10%.

### Target

Cancellation rate: 8.5%.

### Delivery KPI

Feature released by target date.

### Business KPI

Cancellation rate decreases.

### Success determination

The system should report both:

**Delivery Status:** Complete

**Business Outcome:** Target achieved / partially achieved / not achieved

This prevents the organization from confusing "we built it" with "it worked."

---

# 25. Project Success Scorecard

Every project should have a scorecard.

| Dimension    | Measure                  |
| ------------ | ------------------------ |
| Schedule     | Delivered on time        |
| Scope        | Required scope delivered |
| Quality      | Defect rate              |
| Adoption     | User adoption            |
| Business KPI | Target improvement       |
| Support      | Support impact           |
| ROI          | Business value           |

---

# 26. Traceability

One of the most important system capabilities should be full traceability.

Example:

**Cancellation Rate**

↓

**Cancellation Prevention**

↓

**Cancellation Risk Indicators**

↓

**Cancellation Risk Project**

↓

**Risk Engine Feature**

↓

**Developer Tasks**

↓

**Pull Request**

↓

**QA Test**

↓

**Bug**

↓

**Release**

↓

**KPI Measurement**

This allows management to drill down from business results to actual engineering work.

---

# 27. Dashboard Structure

## Executive Dashboard

### Business

* North Star KPIs
* KPI trends
* Target vs actual

### Roadmap

* Initiatives by year
* On-track initiatives
* At-risk initiatives

### Delivery

* Projects
* Completion
* Delays

### Engineering

* Team workload
* Cycle time
* Quality

### Support

* Ticket volume
* SLA
* Critical issues

---

# 28. Project Dashboard

Displays:

* Project progress
* Milestones
* Task completion
* Sprint progress
* Bugs
* Blockers
* Dependencies
* Team workload
* Timeline — see **Schedule Gantt** (hub Schedule tab)
* KPI outcome

---

# 29. Developer Dashboard

Displays:

* Assigned tasks (List + **personal Kanban** — see §14 Personal Kanban)
* Process KPI cards (owned-work delivery, commitment, quality)
* Current sprint
* Overdue tasks
* Blocked tasks
* Bugs
* Code reviews
* QA failures
* Cycle time
* Workload

---

# 30. Support Dashboard

Displays:

* Open tickets
* New tickets
* SLA status
* Critical tickets
* Average resolution time
* Ticket categories
* Escalations
* Bugs generated from support

---

# 31. Notifications

The system should provide notifications for:

### Tasks

* Assignment
* Due date approaching
* Overdue
* Blocked

### Bugs

* New assignment
* Severity escalation
* QA rejection
* Reopened bug

### Support

* New ticket
* SLA approaching
* SLA breached
* Escalation

### Projects

* Milestone approaching
* Project at risk
* Dependency blocked

### KPIs

* KPI target missed
* KPI trend deterioration
* KPI target achieved

---

# 32. Audit Trail

All significant changes should be recorded.

Examples:

* Status changed
* Assignment changed
* Priority changed
* Due date changed
* Estimate changed
* KPI target changed
* Bug severity changed
* Ticket escalated

Audit records should contain:

* User
* Action
* Date/time
* Previous value
* New value

---

# 33. Search and Filtering

Global search should support:

* Project
* Initiative
* Feature
* Task
* Bug
* Support ticket
* User
* KPI

Filters should include:

* Status
* Priority
* Owner
* Project
* Date
* Team
* Severity
* Strategic focus

---

# 34. Reporting

The system should generate:

### Management Reports

* Roadmap status
* KPI scorecard
* Project status
* Business outcome report

### IT Reports

* Development productivity
* Bug report
* QA report
* Support report
* Sprint report

### Project Reports

* Timeline
* Task completion
* Risks
* Issues
* Dependencies
* KPI

---

# 35. Suggested Database Architecture

Core entities:

```text
users
roles
teams

strategic_pillars
initiatives
initiative_kpi

delivery_projects
delivery_project_milestones
delivery_project_members
initiative_system

systems

epics
features
user_stories

tasks
task_dependencies
task_comments
task_attachments
task_time_logs

sprints

bugs
# (deferred) bug_attachments, bug_history, support_attachments
# current: support_tickets, support_comments, support_slas
# current: releases, release_items, deployments
# current: sprints (+ tasks.sprint_id)
# (deferred) test_cases, test_runs

test_cases
test_runs

releases
deployments

kpis
kpi_measurements
kpi_targets

notifications
audit_logs
```

---

# 36. Important Relationships

```text
Strategic Pillar
      │
      ▼
Initiative ──────── KPI Outcome
      │  └──── System (systems touched, M2M)
      ▼
Delivery Project ─── KPI Outcome
      │
      │  (assigns Features from initiative Systems)
      │
System (catalog)
      │
      ▼
Epic
      │
      ▼
Feature ······· scheduled into Delivery Project
      │
      ▼
Task
      │
      ├──── Bug
      │
      └──── QA Test
              │
              ▼
           Release
              │
              ▼
        KPI Measurement
```

---

# 37. Integrations

The platform should be designed for integration.

Potential integrations:

### Git

* GitHub
* GitLab
* Bitbucket

Purpose:

* Commit references
* Pull requests
* Branches
* Deployment status

### Communication

* Email
* Microsoft Teams
* Slack
* Viber where applicable

### Existing Business Systems

* Seller's Reservation App
* Homeowners App
* Financing systems
* Payment systems
* CRM
* Internal APIs

### Analytics

* Google Analytics
* Application analytics
* Database reporting
* BI tools

---

# 38. Recommended Technical Approach

Given the organization's existing development environment, the application can be implemented as a web application using:

**Backend**

Laravel

**Frontend**

Vue.js + Vite

**Database**

MySQL

**Authentication**

Laravel authentication / SSO where available

**API**

REST API

**Real-time**

WebSockets / notifications where required

**Deployment**

AWS

The application should be API-first enough that future mobile/PWA clients can consume the same platform.

---

# 39. MVP

The first release should focus on replacing the operational gap between the roadmap and actual development work.

## MVP Phase 1

### 1. Authentication & Roles

* Login
* Roles
* Permissions

### 2. Roadmap

* Strategic focus
* Initiatives
* KPI
* Targets
* Timeline — delivery Schedule Gantt (portfolio + project hub); see § Schedule Gantt

### 3. Projects

* Projects
* Milestones
* Project status
* Project health

### 4. Tasks

* Tasks
* Subtasks
* Assignment
* Due dates
* Dependencies
* Comments

### 5. Bugs (current build)

* Bug reporting
* Assignment
* Severity / environment
* Workflow (reported → closed, including reopen)
* Links to delivery project, feature, system, task

### 6. Support (current build)

* Support ticket
* Assignment
* Configurable SLA matrix
* Comments
* Convert → Bug / Task / Feature

> Attachments and KB articles remain deferred. Defect workflow for engineering remains the Bug module.

### 7. KPI Outcomes (business)

* KPI definition
* Baseline
* Target
* Current
* Trend
* Manual measurements

### 8. Dashboards

* Executive
* Developer (My Work)
* **Team Delivery** (computed delivery + quality metrics + charts — current build)
* DMAIC Overview
* Support ticket list (with Support module)

### Team Delivery metrics (current build — computed)

| Metric | Source |
|--------|--------|
| Tasks completed | tasks status done/released |
| Tasks overdue | due date past and not done/released |
| Completion rate | completed ÷ assigned (user/team scope) |
| On-time rate | completed on or before due date ÷ completed with due date |
| Avg cycle time | avg(completed_at − started_at) |
| Bugs created / resolved | bugs counts |
| Open critical | severity critical and not closed |
| Reopen rate | bugs with reopened_count > 0 ÷ resolved/closed |
| Production bugs | environment = production |

---

# 40. Phase 2 (ops increments 6–8 — current build)

In scope now:

* Support tickets + SLA + convert to development work
* Release management + deployment tracking (task `released` sync)
* Sprint management (task `sprint_id`)
* Feature Definition of Done checklist
* In-app notification center (database channel)
* Team Delivery charts

Still deferred after this pass:

* Time tracking
* Full QA test cases / test runs
* Git hosting integration
* Attachments / KB articles
* Automated KPI calculations beyond current measurements
* Advanced reports / burndown polish
* Mail / WebSockets

---

# 41. Phase 3

Add:

* AI project assistant
* AI task summarization
* AI bug classification
* AI support-ticket classification
* Predictive project risk
* Predictive KPI risk
* Intelligent developer workload planning
* Automated management summaries

---

# 42. Business KPIs for the Platform Itself

The application should have KPIs measuring whether the management platform is successful.

### Adoption

**Active User Rate**

Active users ÷ registered users

### Task Management

**Task Completion Rate**

Completed tasks ÷ assigned tasks

### Delivery

**On-Time Delivery Rate**

Projects delivered on time ÷ completed projects

### Quality

**Production Escape Rate**

Production bugs ÷ total bugs

### Support

**SLA Compliance**

Tickets resolved within SLA ÷ resolved tickets

### Business Outcome

**Initiative Success Rate**

Initiatives achieving defined business targets ÷ completed initiatives

---

# 43. Proposed Programmer KPI Scorecard

The system should provide a balanced engineering scorecard rather than a single "developer score."

| Category    | KPI                             |
| ----------- | ------------------------------- |
| Delivery    | Task completion                 |
| Delivery    | On-time completion              |
| Delivery    | Cycle time                      |
| Estimation  | Planned vs actual effort        |
| Quality     | Defect rate                     |
| Quality     | Reopen rate                     |
| Quality     | Production escape rate          |
| QA          | First-pass QA rate              |
| Reliability | Blocked time                    |
| Support     | Support resolution contribution |
| Engineering | Code review participation       |
| Engineering | Documentation                   |
| Engineering | Technical debt contribution     |

Individual metrics should be interpreted within team/project context.

---

# 44. Definition of Done

A task should not be considered "Done" simply because the programmer finished coding.

Suggested definition:

1. Development completed
2. Code reviewed
3. Automated tests completed where applicable
4. QA completed
5. Bugs resolved
6. Acceptance criteria satisfied
7. Documentation updated where required
8. Released where applicable
9. Product owner accepted

---

# 45. Definition of Project Success

A project is successful when:

### Delivery

Required scope is delivered.

### Quality

Acceptable defect levels are maintained.

### Adoption

Target users actually use the capability where applicable.

### Business Outcome

The defined KPI moves toward or reaches its target.

### Sustainability

The solution does not create unacceptable support or operational burden.

---

# 46. Example End-to-End Workflow

A management team creates:

**INIT-004 — Cancellation Risk Indicators**

Business goal:

Reduce cancellations.

KPI:

Cancellation Rate.

Target:

15% relative reduction.

↓

Project manager creates:

**Cancellation Risk Engine Project**

↓

Product owner creates:

**Cancellation Risk Feature**

↓

Project manager creates tasks:

* Database analysis
* Risk rules API
* Frontend indicators
* Notification integration
* Unit tests
* QA testing

↓

Developer completes API task.

↓

Code review.

↓

QA.

↓

QA discovers bug.

↓

Bug automatically links to the task.

↓

Developer fixes bug.

↓

QA passes.

↓

Feature released.

↓

System begins measuring:

Cancellation Rate.

↓

After the defined measurement period:

**Baseline:** 10%

**Current:** 8.7%

↓

Management dashboard shows:

**Delivery:** Complete

**Business Outcome:** Target progress

This provides the complete chain from strategy to business result.

---

# 47. Functional Requirements Priority

## P0 — Critical

* Authentication
* User/role management
* Roadmap
* Initiative management
* Project management
* Task management
* Bug reporting
* Support tickets
* KPI management
* Executive dashboard
* Developer dashboard
* Audit trail
* Notifications

## P1 — High

* Sprint management
* QA management
* Time tracking
* Release management
* Advanced reporting
* Git integration
* Automated KPI calculations

## P2 — Future

* AI assistant
* Predictive analytics
* Automated risk detection
* Advanced resource planning
* AI support classification

---

# 48. Non-Functional Requirements

### Performance

Standard pages should load within an acceptable response time under normal organizational load.

### Security

* Role-based access control
* Secure authentication
* HTTPS
* Audit logging
* Secure file uploads
* API authentication
* Sensitive-data protection

### Availability

The platform should target high availability appropriate for an internal business-critical system.

### Scalability

The architecture should support increasing:

* Users
* Projects
* Tasks
* Tickets
* KPI measurements
* Attachments
* Historical records

### Maintainability

The system should use modular architecture so future modules can be added without redesigning the core platform.

---

# 49. Acceptance Criteria

The system will be considered ready for initial production use when:

1. Management can create and monitor a roadmap.
2. Initiatives can be linked to KPIs.
3. Initiatives can contain projects.
4. Projects can contain features and tasks.
5. Tasks can be assigned to programmers.
6. Programmers can update task status.
7. Bugs can be created and linked to development work.
8. Support tickets can be created and escalated.
9. Support tickets can be converted into development work.
10. QA can verify development work.
11. Projects display current health.
12. KPIs display baseline, target, and actual performance.
13. Management can drill down from KPI to initiative and project.
14. User activity is auditable.
15. Notifications are generated for important workflow events.
16. Role-based permissions prevent unauthorized access.

---

# 50. Final Product Vision

The application should evolve beyond being a task management system.

The long-term vision is:

> **A single digital operating system for planning, executing, measuring, and improving technology-driven business initiatives.**

Instead of having separate spreadsheets for:

* Roadmap
* Projects
* Tasks
* Bugs
* Support
* KPIs
* Programmer performance
* Management reporting

the organization will have one connected system.

The ultimate information flow becomes:

**WHY**

Business Strategy

↓

**WHAT**

Initiatives & Projects

↓

**HOW**

Features & Tasks

↓

**WHO**

Teams & Programmers

↓

**QUALITY**

QA & Bugs

↓

**OPERATIONS**

Support & Releases

↓

**RESULT**

KPIs & Business Outcomes

↓

**IMPROVEMENT**

Management decisions and next roadmap cycle.

This creates a closed-loop system where the organization can continuously answer:

**"What are we trying to achieve?"**

**"What are we doing to achieve it?"**

**"Who is working on it?"**

**"Are we delivering it effectively?"**

**"Is the software working reliably?"**

**"Are users experiencing problems?"**

**"Did the project actually improve the business KPI?"**

**"What should we do next?"**
