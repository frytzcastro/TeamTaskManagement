# Demo Guide

Local demo accounts and sample data created by `Database\Seeders\DemoDataSeeder`.

**Password for all accounts:** `password`

## Demo logins

| Email | Name | Role |
|-------|------|------|
| `admin@example.com` | System Admin | admin |
| `exec@example.com` | Eden Executive | executive |
| `po@example.com` | Perry Owner | product_owner |
| `pm@example.com` | Pat Manager | project_manager |
| `dev@example.com` | Dana Developer | developer |
| `il@example.com` | Ira Lead | improvement_lead |
| `support@example.com` | Susie Support | support |

## What each role is good for

- **admin** — full access, including **Users** role assignment
- **executive** — lands on Executive dashboard; primary **Portfolio**, Projects, Schedule, Dashboards
- **product_owner** — lands on **Portfolio**; Projects + Schedule; Pillars/Initiatives/Problems/KPIs via Portfolio or **More → Strategy**
- **project_manager** — lands on **Team**; Projects, Schedule, Quality; strategy via **More → Strategy**
- **improvement_lead** — lands on **Improve** overview; Projects, Schedule, Problems, KPI Outcomes; DMAIC on project hub Improve tab
- **support** — lands on Projects; primary Support tickets (create/update/convert)
- **developer** — lands on **My Work**; Projects, Bugs; personal Kanban + process KPIs

## Seed / reset demo data

```bash
php artisan migrate:fresh --seed
```

## Sample data included

- **Strategic pillar:** Cancellation Prevention
- **Initiative:** Cancellation Risk Indicators (code auto-generated, e.g. `INIT-…`)
- **Business problem:** Late cancellation risk visibility
- **Delivery projects:** Cancellation Risk Engine (`standard` / Delivery) + Cancellation DMAIC (`dmaic` / Improvement)
- **Systems:** Reservation App, Seller Portal (linked on the initiative; own epics/features)
- **KPI:** Cancellation Rate (`metric_layer=business`)
- **Epics / Features** on systems, features assigned into delivery projects; Tasks / Bugs as before
- **Improvement cycle** on the `dmaic` project (stages live on the project hub Improve tab)
- **Support tickets** + SLA matrix + sample open ticket
- **Release** sample under Cancellation Risk Engine
- **Sprint** sample with tasks linked

## Project-first navigation

Top bar is **role-primary** (home first, few links) + **More** grouped as Work items / Quality / Strategy:

- **My Work / Team / Portfolio / Improve / Support** — role home
- **Projects** — Delivery Project hubs (primary workplace)
- **Schedule** — portfolio Gantt when permitted
- **Problems / KPI Outcomes** — Improvement Lead primaries
- **Quality / Dashboards / Admin** — as role allows
- **More → Strategy** — Portfolio (for delivery roles), Pillars, Initiatives, Problems/KPIs when not primary
- Bell — notifications

Day-to-day: open a **Delivery Project** → tabs → **section Add** (e.g. Plan → Add feature) + **Next steps**. Strategy parentage appears on hub meta (Pillar / Initiative). From Portfolio, open projects under each initiative. From a Business Problem, open linked DMAIC Improve.

Section create opens a project-scoped form; Save/Cancel return to that project tab. Prefer **Plan → feature → Add task** (or Feature page **Build**) so tasks stay linked to features; hub **Build** lists show the parent feature.

**Overview → Expectations:** checkpoints auto-appear when an epic has a due date (Plan) or a release has a target date (Ship). Each row shows progress and on-track / at risk / slipped health — use this for stakeholder expectations and project movement, not as a second Gantt. Custom (manual) expectations are optional for non-work dates.

## First 30 minutes (Product Owner)

1. Login `po@example.com` → lands on **Portfolio**
2. Open a delivery project link under an initiative (or Pillars via More → Strategy)
3. On the hub → Plan / Features; set DoD as needed
4. Use **Schedule** or Dashboards when you need delivery visibility (view)

## First 30 minutes (Improvement Lead)

1. Login `il@example.com` → lands on **Improve** overview
2. Open **Problems** → sample problem → **Open Improve** on the linked DMAIC project
3. Or **Projects** → **Cancellation DMAIC** → Overview shows problem context → **Open Improve**
4. Use **Plan / Build / Ship** on the same project for fixes
5. Check **KPI Outcomes** for Cancellation Rate

## First 30 minutes (Project Manager)

1. Login `pm@example.com` → **Team** (Board by default) — see who is In progress / Ready / Blocked; filter by user or project
2. Open **Cancellation Risk Engine** hub → **Overview → Expectations** (epic/release checkpoints + movement); then **Attention** / **Metrics** as needed
3. Open **Schedule** — portfolio Gantt; expand a project or filter by assignee to spot slip / overlap
4. Open **Cancellation Risk Engine** hub → **Schedule** tab for Epic → Feature → Task bars
5. Use section **Add** on Plan (features) then **Add task** on a feature row or Feature Build; follow **Next steps**

## First 30 minutes (Developer)

1. Login `dev@example.com` → **My Work** (Board view by default)
2. See three process KPI cards: Owned-work delivery, Commitment accuracy, Quality
3. Drag a Ready card into **In progress** — set **commitment estimate (working days)** if prompted; due dates feed delay signals
4. Use List toggle for the classic table; clear Overdue / Due today / Blocked chips first
5. Complete work; scores update from due-date adherence, estimate vs actual, and bug reopen quality
6. On the project hub, Feature/Epic/Project show **rolled schedule** from child task dates (planned vs envelope)

## Notes

- Local development only.
- Still deferred: attachments, KB articles, full test-case suites, time tracking, AI.
