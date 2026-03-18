# Task Management System API

A RESTful API for task management built with **Laravel 12**, **Sanctum**, **MySQL 8**, **Redis**, and **Docker**.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running Tests](#running-tests)
- [API Reference](#api-reference)
- [Authorization Rules](#authorization-rules)
- [Validation Rules](#validation-rules)
- [Event System](#event-system)
- [Testing with Postman](#testing-with-postman)
- [Default Seeded Users](#default-seeded-users)
- [Docker Reference](#docker-reference)
- [Troubleshooting](#troubleshooting)

---

## Tech Stack

| Technology | Purpose |
|---|---|
| Laravel 12 | PHP framework |
| Laravel Sanctum | API token authentication |
| MySQL 8 | Primary database |
| Redis 7 | Queue backend for async notifications |
| Mailpit | Local email testing |
| Docker & Docker Compose v2 | Containerised development environment |
| PHPUnit | Automated testing |

---

## Prerequisites

### 1. Docker & Docker Compose v2

```bash
docker --version        # must be 20+
docker compose version  # must be v2 (note: no hyphen)
```

### 2. Add your user to the Docker group

Required so you can run Docker **without `sudo`**. Skip if `docker ps` already works without sudo.

```bash
sudo usermod -aG docker $USER
newgrp docker   # apply without logging out
docker ps       # confirm it works
```

### 3. Fix Docker image pulls (Linux — common in Europe)

If `docker compose up` fails with `connection reset by peer`, this is an IPv6 routing issue. Fix it once:

```bash
sudo nano /etc/docker/daemon.json
```

```json
{
  "ipv6": false,
  "dns": ["8.8.8.8", "1.1.1.1"]
}
```

```bash
sudo systemctl restart docker
```

---

## Installation

Follow these steps in order. Everything is automated — no manual database setup required.

### Step 1 — Clone the repository

```bash
git clone git@github.com:sjmuimra/tasks_management_system.git
cd tasks_management_system
```

### Step 2 — Copy environment files

```bash
cp .env.example .env
```

The `.env` file is pre-configured for Docker. Do not change `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `REDIS_HOST`, or `MAIL_HOST` — these match the Docker service names.

> **Important:** Never put `MYSQL_*` variables in `.env`. Those belong only in `docker-compose.yml` and are already set there.

### Step 3 — Build and start Docker

```bash
docker compose up -d --build
```

This starts all six containers and **automatically**:
- Creates the `tasks_manager` database
- Creates the `user` MySQL account with the correct password
- Grants the user full access to the database

Wait for all containers to be healthy:

```bash
docker compose ps
```

The `db` container must show `healthy` before proceeding. This takes ~15 seconds on first run.

### Step 4 — Install PHP dependencies

```bash
docker compose exec app composer install
```

### Step 5 — Generate application key

```bash
docker compose exec app php artisan key:generate
```

### Step 6 — Run migrations and seed

```bash
docker compose exec app php artisan migrate --seed
```

### Step 7 — Verify

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' | python3 -m json.tool
```

You should receive a JSON response containing a token. The setup is complete.

| Service | URL |
|---|---|
| API | http://localhost:8080 |
| Mailpit inbox | http://localhost:8025 |

---

## Running Tests

The test suite uses a **separate database** so it never touches your development data.

### First time only

```bash
# 1. Create the test database
docker compose exec db mysql -u root -proot \
  -e "CREATE DATABASE IF NOT EXISTS tasks_manager_test;"

# 2. Generate a key for the test environment
docker compose exec app php artisan key:generate --env=testing

# 3. Run migrations on the test database
docker compose exec app php artisan migrate --env=testing
```

### Run all tests

```bash
docker compose exec app php artisan test --env=testing
```

### Run specific groups

```bash
# Unit tests only
docker compose exec app php artisan test --env=testing --testsuite=Unit

# Feature tests only
docker compose exec app php artisan test --env=testing --testsuite=Feature

# Specific test class
docker compose exec app php artisan test --env=testing --filter=TaskControllerTest
```

### Test coverage overview

| Group | Type | What is tested |
|---|---|---|
| `Models/TaskManagement` | Unit | Task & Project models, scopes, relationships |
| `Events/TaskManagement` | Unit | TaskUpdated event structure |
| `Listeners/TaskManagement` | Unit | shouldQueue(), handle(), failed() |
| `Notifications/TaskManagement` | Unit | Mail content, channels, toArray() |
| `Controllers/Api/TaskManagement` | Feature | Full CRUD for tasks and projects |
| `Middleware/TaskManagement` | Feature | Ownership checks, overdue restrictions |
| `Requests/TaskManagement` | Feature | Validation rules for all endpoints |

---

## API Reference

All endpoints require:
```
Accept: application/json
```

Protected endpoints additionally require:
```
Authorization: Bearer {token}
```

**Base URL:** `http://localhost:8080/api/v1`

---

### Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/register` | ❌ | Register a new user |
| `POST` | `/auth/login` | ❌ | Login and receive token |
| `GET` | `/auth/me` | ✅ | Get authenticated user profile |
| `POST` | `/auth/logout` | ✅ | Revoke current token |

#### Register

```http
POST /api/v1/auth/register
```

```json
{
    "name": "Imran Khan",
    "email": "imran@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

Response `201`:
```json
{
    "message": "User registered successfully.",
    "user": { "id": 1, "name": "Imran Khan", "email": "imran@example.com" },
    "token": "1|abc123..."
}
```

#### Login

```http
POST /api/v1/auth/login
```

```json
{
    "email": "admin@example.com",
    "password": "password"
}
```

Response `200`:
```json
{
    "message": "Login successful.",
    "user": { "id": 1, "name": "Admin User", "email": "admin@example.com" },
    "token": "2|xyz789..."
}
```

---

### Tasks

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/task-management/tasks` | ✅ | List own tasks |
| `POST` | `/task-management/tasks` | ✅ | Create a task |
| `GET` | `/task-management/tasks/overdue` | ✅ | List overdue tasks |
| `GET` | `/task-management/tasks/by-user/{userId}` | ✅ | Tasks by user (admin only) |
| `GET` | `/task-management/tasks/by-project/{projectId}` | ✅ | Tasks by project |
| `GET` | `/task-management/tasks/{id}` | ✅ | Show a task |
| `PUT` | `/task-management/tasks/{id}` | ✅ | Update a task |
| `DELETE` | `/task-management/tasks/{id}` | ✅ | Delete a task |

#### Create Task

```http
POST /api/v1/task-management/tasks
```

```json
{
    "title": "Fix login bug",
    "description": "The login page crashes on mobile",
    "status": "todo",
    "deadline": "2026-12-31 00:00:00",
    "project_id": 1
}
```

Response `201`:
```json
{
    "message": "Task created successfully.",
    "data": {
        "id": 1,
        "title": "Fix login bug",
        "status": "todo",
        "deadline": "2026-12-31T00:00:00.000000Z",
        "user_id": 1,
        "project_id": 1
    }
}
```

---

### Projects

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/task-management/projects` | ✅ | List own projects |
| `POST` | `/task-management/projects` | ✅ | Create a project |
| `GET` | `/task-management/projects/{id}` | ✅ | Show project with tasks |
| `PUT` | `/task-management/projects/{id}` | ✅ | Update a project |
| `DELETE` | `/task-management/projects/{id}` | ✅ | Delete a project |

#### Create Project

```http
POST /api/v1/task-management/projects
```

```json
{
    "name": "Frontend Redesign",
    "description": "Redesign the customer dashboard"
}
```

---

## Authorization Rules

| Action | Regular User | Admin |
|---|---|---|
| Register / Login | ✅ | ✅ |
| Create, read, update, delete own tasks | ✅ | ✅ |
| Read, update, delete another user's task | ❌ | ✅ |
| Edit an overdue task | ❌ | ✅ |
| View overdue tasks | Own only | All users |
| View tasks by user ID | ❌ | ✅ |
| Create, read, update, delete own projects | ✅ | ✅ |

---

## Validation Rules

### Task

| Field | Rules |
|---|---|
| `title` | Required, string, max 255 characters |
| `description` | Required, string |
| `status` | Required, one of: `todo`, `in_progress`, `done` |
| `deadline` | Optional, valid datetime, must be in the future |
| `project_id` | Optional, integer, must exist in the projects table |

### Project

| Field | Rules |
|---|---|
| `name` | Required, string, max 255 characters |
| `description` | Optional, string |

### Register

| Field | Rules |
|---|---|
| `name` | Required, string, max 255 characters |
| `email` | Required, valid email, unique, max 255 |
| `password` | Required, min 8 characters, must be confirmed |

---

## Event System

When a task is updated, this flow runs automatically:

```
PUT /task-management/tasks/{id}
          ↓
  TaskController@update
          ↓
  TaskUpdated event dispatched
          ↓
  SendOverdueTaskNotification listener checks:
    - deadline is not null
    - deadline is in the past
    - status is not 'done'
          ↓
  Job pushed to Redis queue
          ↓
  Queue worker processes job
          ↓
  TaskDeadlineOverdue email sent → captured by Mailpit
```

View emails at **http://localhost:8025**

```bash
# Watch queue worker live
docker logs tasks_manager_queue -f

# Check failed jobs
docker compose exec app php artisan queue:failed

# Retry all failed jobs
docker compose exec app php artisan queue:retry all
```

---

## Testing with Postman

| File | Description |
|---|---|
| `TaskManagerApi.postman_collection.json` | All requests organised by domain |
| `TaskManagerApi.postman_environment.json` | Pre-configured local environment |

**Import:** Open Postman → Import → drag both files → select **Task Manager Local** environment → start with **Auth → Login**.

| Variable | Value | How it's set |
|---|---|---|
| `base_url` | `http://localhost:8080/api/v1` | Pre-configured |
| `token` | *(auto-filled after login)* | Post-request script |
| `task_id` | *(auto-filled after create)* | Post-request script |
| `project_id` | *(auto-filled after create)* | Post-request script |

---

## Default Seeded Users

| Name | Email | Password | Role |
|---|---|---|---|
| Admin User | admin@example.com | password | admin |
| Imran Khan | imran@example.com | password | user |
| *(3 random users)* | *(random)* | password | user |

---

## Docker Reference

```bash
# Start all containers
docker compose up -d

# Stop containers (keep data)
docker compose down

# Stop containers and delete all data
docker compose down -v

# Rebuild and restart
docker compose up -d --build

# View logs
docker logs tasks_manager_app -f
docker logs tasks_manager_queue -f

# Open shell in app container
docker compose exec app bash

# Run artisan commands
docker compose exec app php artisan <command>

# Fresh migration with seed
docker compose exec app php artisan migrate:fresh --seed

# Clear all caches
docker compose exec app php artisan optimize:clear
```

---

## Troubleshooting

### `permission denied` connecting to Docker socket

```bash
sudo usermod -aG docker $USER
newgrp docker
```

### `vendor/autoload.php: No such file or directory`

Dependencies not installed. Run:

```bash
docker compose exec app composer install
```

### `Access denied for user 'user'@'%'`

The MySQL volume has stale data from a previous failed setup. Wipe it and start clean:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

The `-v` flag deletes the MySQL volume. On next start, MySQL automatically recreates the database, user, and permissions from `docker-compose.yml`. No manual SQL required.

### `The "UID" variable is not set` warning

This warning is harmless after the fix applied in `docker-compose.yml` — the `user` field now uses `${UID:-1000}:${GID:-1000}` which defaults to `1000` if not set. If you want to match your exact user ID:

```bash
echo "UID=$(id -u)" >> .env
echo "GID=$(id -g)" >> .env
```

### `connection reset by peer` pulling images

See [Prerequisites — Fix Docker image pulls](#3-fix-docker-image-pulls-linux--common-in-europe).

### MySQL not ready immediately after `docker compose up`

MySQL takes ~15 seconds to initialise on first run. The `app` container is configured with `depends_on: db: condition: service_healthy` so it waits automatically. If you run commands too quickly in a separate terminal, just wait until `docker compose ps` shows `db` as `healthy`.
