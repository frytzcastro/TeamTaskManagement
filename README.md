# MyTask

Digital product, project, task, and KPI management platform (Laravel + Vue/Inertia).

Connects strategy to delivery: **Strategic Pillar → Initiative → Delivery Project → Feature/Task → Bug/Support → Release → KPI outcome**.

Product requirements: [docs/BRD.md](docs/BRD.md).

## Stack

- PHP 8.2+ / Laravel 12
- Vue 3 + Inertia + Vite
- MySQL (external — not bundled in Docker)
- REST API + web UI

## Requirements

- PHP 8.2+, Composer, Node.js 20+, npm
- MySQL 8+ (or SQLite for a quick local try — see `.env.example`)

## Quick start (host)

```bash
cp .env.example .env
# Set DB_* for your MySQL instance
composer setup
php artisan db:seed          # optional demo data
composer dev                 # serve + queue + logs + Vite
```

App: [http://localhost:8000](http://localhost:8000)

Or step by step:

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm install && npm run build
composer dev
```

## Demo accounts

After seeding (`php artisan db:seed` / `migrate:fresh --seed`):

| Email | Role | Password |
|-------|------|----------|
| `admin@example.com` | admin | `password` |
| `exec@example.com` | executive | `password` |
| `po@example.com` | product_owner | `password` |
| `pm@example.com` | project_manager | `password` |
| `dev@example.com` | developer | `password` |
| `il@example.com` | improvement_lead | `password` |
| `support@example.com` | support | `password` |

Full walkthrough: [docs/DEMO_GUIDE.md](docs/DEMO_GUIDE.md).

## Docker (prod-like local)

MySQL stays on the host (or RDS). Storage bind-mounts `./storage`.

```bash
# .env: APP_KEY set; DB_* for host MySQL
docker compose build
docker compose up -d
```

App: [http://localhost:8080](http://localhost:8080) · Health: `/up`

Compose forces `DB_HOST=host.docker.internal` and `APP_URL=http://localhost:8080` so host MySQL and Vite assets work inside the container. Keep `DB_HOST=127.0.0.1` in `.env` for native `artisan serve`.

```bash
docker compose logs -f app
docker compose exec app php artisan migrate
docker compose down
```

## Production deploy

GitHub Actions (`.github/workflows/ci-cd.yml`): **test → deploy**.

1. Every push/PR: `php artisan test` (sqlite in-memory)
2. `main` (or manual run), only if tests pass: ECR → EC2

EFS-backed `storage/` details: [docs/deploy-docker.md](docs/deploy-docker.md).

Required GitHub secrets for **deploy** only (do **not** commit these): `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_REGION`, `ECR_REPOSITORY`, `EC2_HOST`, `EC2_USER`, `EC2_SSH_KEY`, optional `EC2_APP_DIR`.

## Tests

```bash
composer test
```

CI runs the same suite automatically; deploy will not run if tests fail.

## Security

- Never commit `.env` (contains `APP_KEY` and credentials).
- Use `.env.example` as the template only.
- Rotate any key that was ever committed by mistake.

## License

MIT — see [LICENSE](LICENSE).
