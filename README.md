# HealMe

HealMe is an AI-powered telehealth platform that connects patients with medical specialists through symptom triaging, smart doctor matching, and streamlined online appointment workflows.

## Tech stack

- Laravel 13 + PHP 8.3+
- React 19 + TypeScript + Vite
- MySQL 8, Redis, Mailpit
- Google Gemini API integration
- Laravel Sail, Pest, Pint, and PHPStan/Larastan

## Implemented foundation

- DDD-style backend folders for AI, doctors, patients, and appointments
- `POST /api/v1/triage/analyze` for structured symptom summaries
- `POST /api/v1/doctors/match` for top doctor recommendations
- seeders for a large doctor dataset with near-term availability slots
- React frontend shell for the landing experience

## Quick start

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Open [http://localhost](http://localhost).

## Quality checks

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pest --parallel
./vendor/bin/sail bin pint --test
./vendor/bin/sail php ./vendor/bin/phpstan analyse --memory-limit=1G
npm run lint
npm run typecheck
```

Frontend tooling: TypeScript everywhere (`resources/js`, `vite.config.ts`, `eslint.config.ts`), ESLint via `npm run lint`.
## Google OAuth & Meet

- Patient/doctor Google sign-in uses `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`.
- Open Meet links are created with the shared app account via `GOOGLE_MEET_DRIVER=google` and `GOOGLE_MEET_REFRESH_TOKEN`.
- Use `GOOGLE_MEET_DRIVER=fake` for UI-only local work without Meet credentials.
