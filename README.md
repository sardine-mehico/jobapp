# JobApp

Self-hosted job application system for Office PC Cleaning with a Laravel API, Nuxt frontend, PostgreSQL, and Redis.

## Stack

- Laravel API in [api](/home/reallybasic/Vscode/jobapp/plan/api)
- Nuxt app in [frontend](/home/reallybasic/Vscode/jobapp/plan/frontend)
- Local verification compose in [docker-compose.yml](/home/reallybasic/Vscode/jobapp/plan/docker-compose.yml)

## Local Run

1. Copy `.env.example` to `.env` at the repo root and set `APP_KEY`, `DB_PASSWORD`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD`.
2. Copy [api/.env.example](/home/reallybasic/Vscode/jobapp/plan/api/.env.example) to `api/.env` if you want to run the API outside Docker.
3. Run `docker compose up -d --build`.
4. The API container will automatically wait for PostgreSQL, run `php artisan migrate --force`, and then run `php artisan db:seed --force` on startup.
5. Open `http://localhost:3000/employer/login` and sign in with the `ADMIN_EMAIL` / `ADMIN_PASSWORD` values from the root `.env`.

Optional analytics:
- Set `NUXT_PUBLIC_GA4_ID=G-XXXXXXXXXX` in the root `.env` to enable GA4 in the frontend.
- GA4 is only active in production builds and only tracks the public applicant flow (`/:code` and `/thank-you`).
- Employer login and all `/admin` routes are excluded from tracking.

## Production Notes

- Deploy the compose stack to `/opt/stacks/jobapp/docker-compose.yml`.
- Keep persistent data under `/srv/docker/jobapp/`.
- In Nginx Proxy Manager, point:
  - `jobs.officepc.online` to `http://jobapp-nuxt:3000`
  - `admin.officepc.online` to `http://jobapp-nuxt:3000`
  - preferred: `api.officepc.online` to `http://jobapp-api:80`
- Update `NUXT_PUBLIC_API_BASE` to `https://api.officepc.online/api` in production.
- If using GA4, set `NUXT_PUBLIC_GA4_ID` in the production environment and recreate the `jobapp-nuxt` container.
