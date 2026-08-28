# Software Development Life Cycle (SDLC) — HealMe

## 1. SDLC Methodology: Feature-Driven Agile & TDD
HealMe adheres to an iterative, test-driven development workflow structured around 2-week sprints.

## 2. Code Quality & Formatting Enforcement
- **Laravel Pint** is configured as the mandatory PHP code style fixer.
- All PHP code must pass `pint --test` (or `composer format:test`) prior to PR creation.
- **ESLint** + **TypeScript** (`npm run lint`, `npm run typecheck`) are required for frontend TS/TSX before PR creation.
- Project docs (`README.md`, `AGENTS.md`, `SAD.md`, `SDLC.md`, `TESTING.md`) must be updated immediately after a change when setup, architecture, agents, testing, or workflow is affected — not deferred until push time.

## 3. Git Branching & Workflow Rules (GitHub Flow)
- `main`: Production-ready code only. Fully tested and tagged.
- `develop`: Integration branch for active sprint development.
- `feature/feature-name`: Isolated branches created from `develop`.
- `fix/bug-description`: Hotfix/bugfix branches.

### Pull Request (PR) Requirements
- All PRs must pass automated CI pipeline checks (Tests, Pint, PHPStan, ESLint, typecheck, build).
- Docs already updated with the change when setup, architecture, agents, testing, or workflow was affected.
- Minimum 1 code review approval required before merging.

## 4. CI/CD Pipeline Architecture (GitHub Actions)
Each commit and PR triggers the automated workflow inside Docker:
1. **PHP Linting & Formatting:** `./vendor/bin/pint --test`
2. **Static Analysis:** `./vendor/bin/phpstan analyse --level=8`
3. **Automated Testing:** `./vendor/bin/pest --parallel`
4. **Frontend lint & types:** `npm run lint` and `npm run typecheck`
5. **Build Check:** `npm run build`

## 5. Environment Lifecycle
- **Local Environment:** Docker Compose / Laravel Sail (PHP 8.3, MySQL 8, Redis, Mailpit).
- **Staging:** Hosted on Render (Auto-deploys from `develop` branch).
- **Production:** Hosted on Render/Fly.io + Vercel (Auto-deploys on `main` tag release).
