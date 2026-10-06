# Laravel Docker — Setup Guide

A Laravel 13 starter (Livewire 4, Flux UI, Fortify auth, Tailwind 4) that runs entirely in Docker. You do **not** need PHP, Composer, or Node installed on your machine — only Docker.

## Stack

| Service   | Image / Version                          | Port on host |
|-----------|------------------------------------------|--------------|
| `app`     | `serversideup/php:8.5-fpm-nginx` + Node 26 | `8000`       |
| `mariadb` | `mariadb:11.4`                           | `3306`       |

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose v2)
- Git

Check that Docker is running:

```bash
docker --version
docker compose version
```

## 1. Clone the project

```bash
git clone <repository-url> laravel-docker-peserta
cd laravel-docker-peserta
```

## 2. Create the `.env` file

```bash
cp .env.example .env
```

The default database is **SQLite**, which needs no extra setup. To use **MariaDB** instead, see [Using MariaDB](#using-mariadb).

## 3. Build and start the containers

```bash
docker compose up -d --build
```

This single command:

1. Builds the `app` image: installs PHP extensions and Node.js, runs `composer install`, `npm ci`, and `npm run build`.
2. Starts MariaDB and waits until it is healthy.
3. Starts the `app` container. On startup, if `vendor/` is missing in your project folder, it runs `composer install` automatically (see `docker/entrypoint.d/20-composer-install.sh`).

The first build takes a few minutes. Check that both containers are up:

```bash
docker compose ps
```

## 4. Generate the app key and run migrations

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

With SQLite, `migrate` will offer to create `database/database.sqlite` if it doesn't exist — answer **yes**.

## 5. Open the app

Visit **http://localhost:8000**

Health check: http://localhost:8000/up should return `200`.

Register a new account to reach the dashboard.

---

## Daily usage

| Task                         | Command                                          |
|------------------------------|--------------------------------------------------|
| Start                        | `docker compose up -d`                           |
| Stop                         | `docker compose down`                            |
| View logs                    | `docker compose logs -f app`                     |
| Shell inside the container   | `docker compose exec app bash`                   |
| Artisan                      | `docker compose exec app php artisan <command>`  |
| Composer                     | `docker compose exec app composer <command>`     |
| npm                          | `docker compose exec app npm <command>`          |
| Rebuild front-end assets     | `docker compose exec app npm run build`          |
| Vite dev server (hot reload) | `docker compose exec app npm run dev`            |
| Run tests, lint, PHPStan     | `docker compose exec app composer test`          |

Always run PHP, Composer, and npm **inside the container** so you use the same PHP 8.5 / Node 26 versions as everyone else.

## Using MariaDB

1. In `.env`, comment out the SQLite line and enable the MariaDB block:

   ```dotenv
   # DB_CONNECTION=sqlite

   DB_CONNECTION=mysql
   DB_HOST=mariadb
   DB_PORT=3306
   DB_DATABASE=laravel_intermediate
   DB_USERNAME=laravel
   DB_PASSWORD=secret
   ```

   > Use a `DB_USERNAME` **other than `root`**. `docker-compose.yml` passes it to `MARIADB_USER`, and MariaDB refuses to create a second user named `root` — the container will exit with `CREATE USER failed for 'root'@'%'`.

2. Recreate the database container so it picks up the new credentials, then migrate:

   ```bash
   docker compose down -v      # -v deletes the old database volume
   docker compose up -d
   docker compose exec app php artisan migrate
   ```

To connect with a GUI client (TablePlus, DBeaver, etc.), use host `127.0.0.1`, port `3306`, and the credentials from `.env`. If port 3306 is already taken on your machine, set `FORWARD_DB_PORT=3307` in `.env`.

## How the Docker setup works

```
docker-compose.yml          # services: app + mariadb
docker/
├── Dockerfile              # app image (PHP 8.5, nginx, Node 26, deps baked in)
├── entrypoint.d/
│   └── 20-composer-install.sh   # installs vendor/ on startup if missing
├── php/custom.ini          # memory_limit, upload size, timezone
└── nginx/custom.conf       # client_max_body_size
.dockerignore               # keeps vendor/, node_modules/, .git out of the build
```

Volumes on the `app` service:

- `.:/var/www/html` — your project folder is mounted live, so code changes show up immediately.
- `app_node_modules` → `node_modules/` and `app_public_build` → `public/build/` — named volumes, so these live **inside Docker only** and are not visible in your project folder.
- `vendor/` is in your project folder (installed by the entrypoint script), so your editor can autocomplete Laravel classes.

The `Dockerfile` matches `www-data` to UID/GID `1000` so files created in the container are writable on the host. If your user's UID is different (`id -u`), build with:

```bash
docker compose build --build-arg USER_ID=$(id -u) --build-arg GROUP_ID=$(id -g)
```

## Troubleshooting

**`Failed opening required '.../vendor/autoload.php'`**
`vendor/` is missing. Restart the container (the entrypoint reinstalls it) or install manually:

```bash
docker compose restart app
# or
docker compose exec app composer install
```

**`Vite manifest not found` / styles missing**

```bash
docker compose exec app npm run build
```

**`package.json` changed but new packages aren't picked up**
`node_modules` lives in a named volume that is only populated the first time it is created. Reinstall inside the container:

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

**MariaDB container keeps exiting**
Check `docker compose logs mariadb`. The usual cause is `DB_USERNAME=root` (see [Using MariaDB](#using-mariadb)). Fix `.env`, then `docker compose down -v && docker compose up -d`.

**Port 8000 or 3306 already in use**
Stop the other service using it, change `"8000:8080"` in `docker-compose.yml`, or set `FORWARD_DB_PORT` in `.env`.

**Start completely fresh** (deletes the database and installed packages):

```bash
docker compose down -v
docker compose up -d --build
```
