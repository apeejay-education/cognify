# Cognify — Developer Guideline & Implementation Plan

Version: 1.0  
Date: 2025-10-28

This document converts the PRD, technical architecture, epics, and front-end spec into a single developer playbook: environment setup, priorities, engineering standards, sprint roadmap, testing & CI, API conventions, and immediate next tasks.

---

## Quick task receipt and plan

- What I'm doing: capturing implementation guidance and an actionable plan so engineers can start building the Cognify platform consistently and safely.  
- Outcome: `docs/developer-guideline.md` (this file) with onboarding steps, phased roadmap, dev standards, and immediate choices for what to scaffold next.

---

## Checklist (requirements coverage)

1. Local development environment & Docker recipe.  
2. Multi-tenant foundation + TenantResolver and tenant-aware models.  
3. Modular package approach for each major domain.  
4. Front-end engineering plan (tokens, Blade components, Alpine, Storybook/gallery).  
5. API contracts, auth (Sanctum), webhooks, and payment patterns.  
6. Testing strategy (unit, integration, accessibility, performance).  
7. CI/CD pipeline, deployments, monitoring, and backups.  
8. Security & PCI guidance.  

---

## 1 — Developer Onboarding (fast start)

Prerequisites (recommended):
- macOS/Linux with Docker Desktop or Docker Engine
- PHP 8.2, Composer
- Node 18+, npm (or pnpm), and Vite (or Mix)
- MySQL 8+, Redis 7+

Quickstart (example)
```bash
git clone <repo-url>
cd cognify
git checkout -b feat/dev-setup

# copy env and configure DB/Redis credentials (use docker or local)
cp .env.example .env
composer install
npm install
npm run dev

# optional: start docker services if docker-compose provided
docker compose up --build -d

# run migrations for superadmin (local tenant migrations later)
php artisan migrate --path=database/migrations/superadmin
php artisan key:generate
```

Notes:
- Place credentials for production in a secrets manager (AWS Secrets Manager / Vault).  
- Use `app()` container to expose current tenant (per `TenantResolver`).

---

## 2 — Architecture & Development Patterns

- Multi-tenant approach: Database-per-tenant with shared services. Implement `TenantResolver` middleware and a `TenantAwareModel` base class to auto-scope queries.  
- Modular packages: develop each major domain (CRM, SIS, LMS, Payments, Gamification, Analytics) as Laravel packages under `packages/` or `app/Modules/` with ServiceProviders, migrations, views and routes.  
- API-first: RESTful API resources using Laravel Resource classes and consistent JSON envelopes. Use Sanctum for SPA auth.  
- Real-time: Laravel Broadcasting and private channels for per-user / per-institute updates.  

Code structure (recommended):
```
app/
  Http/
    Controllers/
      SuperAdmin/
      Client/
      API/
    Middleware/
  Models/
  Services/
packages/
  Gamification/
  Payments/
  ...
resources/views/components/   # Blade components
routes/
  superadmin.php
  client.php
  api.php
```

---

## 3 — Phased Roadmap (deliverable plan)

Phase 1 — Foundation & MVP (6–8 weeks)
- Tenant onboarding & SuperAdmin (tenant creation, DB wiring).  
- Auth + RBAC (basic roles & permissions).  
- Core models: users, students, courses, batches.  
- Basic LMS: course CRUD, course listing, basic lesson player.  
- Basic e-commerce flow stub (cart, checkout stub).  
- Deliver: working dev environment, tenant creation, dashboard, components gallery.

Phase 2 — Features & UX polish (6–8 weeks)
- Enrollment & payment integration (Stripe + Razorpay).  
- Gamification basics (badges, points, streaks).  
- Assessments (autosave, timed tests).  
- Component library + Storybook / gallery.  

Phase 3 — Integrations & AI (6–8 weeks)
- AI features (voice assessment integration), search (Meilisearch), real-time, third-party integrations (Zoom, Twilio).  

Phase 4 — Scale, Ops & Compliance (4–6 weeks)
- Financial reporting, reconciliation, PCI DSS path, monitoring, backups, production hardening.  

Sprint cadence: 2-week sprints. Use the epics in `docs/stories/` to break down stories into sprint backlog.

---

## 4 — Frontend Engineering Plan

Stack: Laravel Blade views for server-rendered pages + Alpine.js for local interactivity, Tailwind CSS for utilities, optional Storybook for component preview.

Delivery items:
- Design tokens: add `resources/css/_tokens.css` with CSS variables and update `tailwind.config.js` with `cognify` color theme.  
- Blade component library: implement components listed in `docs/front-end-spec.md` under `resources/views/components/`. Keep components small, prop-driven, and testable.  
- Component gallery: route `/docs/ui-components` that shows all components with states & knobs (use simple Blade page or Storybook).  
- Player & PWA: implement manifest generator and service worker skeleton per architecture doc.

Performance & Accessibility:
- Keep JS minimal; lazy load non-critical assets.  
- Respect `prefers-reduced-motion`.  
- Ensure contrast and ARIA attributes as in front-end spec.  

---

## 5 — API Contracts & Events

Authentication
- Use Laravel Sanctum, issue tokens on login. Use CSRF cookies for session-based flows.

Key endpoints (frontend contract):
- GET /api/dashboard  
- GET /api/courses?query=&filters=  
- GET /api/courses/{id}  
- POST /api/cart  
- POST /api/checkout  
- POST /api/webhooks/{gateway}  
- GET /api/lessons/{id}/progress  
- POST /api/lessons/{id}/progress

Webhooks & payments
- Implement idempotency for webhooks (store webhook events with unique IDs).  
- Use gateway tokens; do not persist card numbers. Keep payment work off the main request thread (queue jobs for reconciliation and enrollment updates).

Event architecture
- Publish domain events for UI (BadgeEarned, AssessmentGraded, EnrollmentCompleted) via broadcast channels (private/institute-specific). Validate channel auth.

---

## 6 — Testing & QA

Unit & Integration
- PHP: PHPUnit/Pest for services, repositories, and resource transformers.  
- Blade: snapshot testing for components (Pest or phpunit with blade test harness).  
- JS: small unit tests for Alpine behaviors where practical.

E2E
- Laravel Dusk or Playwright: smoke flows (login, enroll, checkout sandbox, lesson playback, assessment).  

Accessibility
- axe-core in CI for main pages.  

Performance
- Lighthouse CI for dashboard and checkout.  

CI checklist for PRs
- Lint (phpcs, eslint, stylelint), unit tests, accessibility scan, lightweight E2E smoke tests.

---

## 7 — CI/CD, Deployments & Rollback

CI pipeline recommendations (GitHub Actions sample):
- `test` job: checkout -> composer install -> phpunit -> eslint/stylelint -> axe accessibility.  
- `deploy` job (after test): run migrations (tenant-aware), cache clear, queue:restart, health check.  

Staging & production
- Deploy `beta` branch to staging automatically. Require manual promoting to `main` for production. Use blue/green or rolling deployments where possible.  

Rollback
- Keep DB migrations reversible; have rollback instructions for critical migrations.

---

## 8 — Observability & Operations

- Metrics: Prometheus + Grafana for service health, queue depth, job durations.  
- Logs: centralize to ELK/CloudWatch.  
- Alerts: Slack/PagerDuty for critical errors (payment failures, queue backlog).  
- Backups: daily DB backups + weekly offsite replication; test restores quarterly.  

---

## 9 — Security & Compliance

- Tenant Isolation: strict middleware checks to prevent cross-tenant queries. Use DB-level separation and application scoping.  
- Payments: do not store raw payment data. Use PCI-compliant gateway flows; plan for audit if needed.  
- Secrets: store in a vault (AWS Secrets Manager / HashiCorp Vault).  
- Input validation and file checks (voice uploads, etc.) per `VoiceFileValidationRule` pattern.  

---

## 10 — Development Standards & Playbook

Code style
- PHP: PSR-12, use PHPStan / Psalm at level 5+ in CI.  
- JS/CSS: ESLint + Prettier; Tailwind best practices.  

PR process
- Small PRs, link to epics/stories, include tests, pass CI, get 1–2 reviewers.  

Database changes
- Migrations under package boundaries where possible; tenant migrations handled by a tenant migration command.  

Documentation
- Update `docs/` whenever API or data model changes. Keep `docs/front-end-spec.md` and `docs/developer-guideline.md` in sync.

---

## 11 — Acceptance Criteria (examples)

Enrollment flow (MVP)
- Add course to cart -> checkout -> payment sandbox -> webhook confirms -> enrollment created and visible in user's dashboard.  
- Performance: checkout page <3s on 3G simulated mobile.  

Assessment flow
- Autosave works reliably (every 10s) and resumes when network returns.  

Multi-tenant
- SuperAdmin creates tenant -> tenant DB created/migrated -> tenant portal accessible at `tenant.example.com`.

---

## 12 — Immediate next deliverables (pick one)

1. Scaffold Blade component stubs and a component gallery page (`/docs/ui-components`).  
2. Scaffold Storybook configuration and sample stories for Button, Card, Sidebar.  
3. Create `DEVELOPER.md` and `docs/dev-setup.md` with exact environment setup and docker-compose improvements.  
4. Parse `docs/stories/*.md` to generate a prioritized GitHub issue backlog for the first 3 sprints.

Pick one (1–4) and I will execute it now. If you prefer a different deliverable, tell me which and I’ll proceed.

---

## Appendix: Useful commands

Build & dev
```bash
composer install
npm install
npm run dev
docker compose up --build -d
php artisan migrate
```

Run tests
```bash
php artisan test
# or phpunit/pest
npm run test # frontend tests if configured
```

CI tips
- Run axe accessibility and Lighthouse in CI for critical pages.

---

File created by automation: `docs/developer-guideline.md`. Keep this document current as the source-of-truth for implementation decisions.
