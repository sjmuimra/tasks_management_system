# Task Management System API

A high performance RESTful API for task management built with **Laravel 12**, **Sanctum**, **MySQL**, **Redis**, and **Docker**. The API supports full task and project management with role based access control, deadline notifications, and a fully containerised development environment.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running Tests](#running-tests)
- [API Reference](#api-reference)
- [Authorization Rules](#authorization-rules)
- [Validation Rules](#validation-rules)
- [Event System](#event-system)
- [Testing with Postman](#testing-with-postman)
- [Default Seeded Users](#default-seeded-users)

---

## Tech Stack

| Technology          | Purpose |
|---------------------|---|
| **Laravel 12**      | PHP framework |
| **Laravel Sanctum** | API token authentication |
| **MySQL 8**         | Primary database |
| **Redis 7**         | Queue backend for async notifications |
| **Mailpit**         | Local email testing |
| **Docker**          | Containerised development environment |
| **PHPUnit**         | Automated testing |

---

## Architecture

The project follows standard **Laravel MVC** architecture with clean separation of concerns:

```
app/
├── Events/TaskManagement/         # Domain events
├── Http/
│   ├── Controllers/Api/
│   │   └── TaskManagement/        # Authentication, Task & Project controllers
│   ├── Middleware/
│   │   └── TaskManagement/        # Custom middleware
│   └── Requests/
│       └── TaskManagement/        # Auth, Task & Project form requests
├── Listeners/
│       └── TaskManagement/        # Event listeners
├── Models/
│   ├── TaskManagement/            # Task & Project models
│   └── User.php                   # User model
└── Notifications/
    └── TaskManagement/            # Email notifications

tests/
├── Feature/
│   ├── Controllers/
│   │   └── TaskManagement/       # Controller tests
│   ├── Listeners/
│   │   └── TaskManagement/       # Listener tests
│   ├── Middleware/               
│   │   └── TaskManagement/       # Middleware tests
│   ├── Models/               
│   │   └── TaskManagement/       # Model tests
│   ├── Requests/                  
│   │   └── TaskManagement/       # Form request validation tests
│   │
└── Unit/
    ├── Events/
    │   └── TaskManagement/        # Event unit tests
    ├── Listeners/
    │   └── TaskManagement/        # Listener unit tests
    ├── Models/
    │   └── TaskManagement/        # Model unit tests
    └── Notifications/
        └── TaskManagement/        # Notification unit tests
```

---

## Prerequisites

- **Docker** & **Docker Compose** v2+
- **Git**
- **Postman** (optional, for manual API testing)

---

## Installation

### 1. Clone the repository

```bash
git clone git@github.com:sjmuimra/tasks_management_system.git
cd tasks-management-system
```

### 2. Copy the environment file

```bash
cp .env.example .env
```

Update `.env` with the following Docker values:

```env
APP_NAME=TaskManagementSystem
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=tasks_manager
DB_USERNAME=user
DB_PASSWORD=user

QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@taskmanager.local"
MAIL_FROM_NAME="${APP_NAME}"
```

### 3. Build and start Docker

```bash
docker compose up -d --build
```

This starts the following containers:

| Container | Service | Port |
|---|---|---|
| `tasks_manager_app` | PHP 8.3 + FPM | 9000 |
| `tasks_manager_nginx` | Nginx | 8080 |
| `tasks_manager_db` | MySQL 8 | 3306 |
| `tasks_manager_redis` | Redis 7 | 6379 |
| `tasks_manager_mailpit` | Mailpit | 1025 / 8025 |
| `tasks_manager_queue` | Queue Worker | — |

### 4. Install dependencies

```bash
docker compose exec app composer install
```

### 5. Generate application key

```bash
docker compose exec app php artisan key:generate
```

### 6. Run migrations and seed the database

```bash
docker compose exec app php artisan migrate --seed
```

### 7. Verify the setup

| Service | URL |
|---|---|
| API | http://localhost:8080 |
| Mailpit Inbox | http://localhost:8025 |

---

## Running Tests

### Create the test database (first time only)

The test suite uses a **separate database** to avoid overwriting your local development data.
```bash
# Create the test database
docker compose exec db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS tasks_manager_test;"

# Generate a key for the test environment
docker compose exec app php artisan key:generate --env=testing

# Run migrations on the test database
docker compose exec app php artisan migrate --env=testing
```

Make sure your `.env.testing` file has the correct database name and credentials:
```env
DB_DATABASE=tasks_manager_test
DB_USERNAME=root
DB_PASSWORD=root
```

### Run all tests

```bash
docker compose exec app php artisan test --env=testing
```

### Run specific test groups

```bash
# Unit tests only
docker compose exec app php artisan test --env=testing --testsuite=Unit

# Feature tests only
docker compose exec app php artisan test --env=testing --testsuite=Feature

# Specific test class
docker compose exec app php artisan test --env=testing --filter=TaskControllerTest

# Specific test group
docker compose exec app php artisan test --env=testing --filter=Models
```

### Test coverage overview

| Group                          | Type | What is tested                                                     |
|--------------------------------|---|--------------------------------------------------------------------|
| `Models/TaskManagement`        | Unit | Task & Project models, scopes, relationships                       |
| `Events/TaskManagement`        | Unit | TaskUpdated event structure                                        |
| `Listeners/TaskManagement`     | Unit | shouldQueue() logic, handle(), failed()                            |
| `Notifications/TaskManagement` | Unit | Mail content, channels, toArray()                                  |
| `Controllers/Api/TaskManagement`  | Feature | Full CRUD for tasks and projects                                   |
| `Middleware/TaskManagement`    | Feature | Ownership checks, overdue restrictions                             |
| `Requests/TaskManagement`      | Feature | Login & register validation rules, Task & project validation rules |
| `TaskManagement`               | Feature | Event dispatch and notification integration                        |

---

## API Reference

All endpoints require the header:
```
Accept: application/json
```

Protected endpoints additionally require:
```
Authorization: Bearer {{token}}
```

### Base URL

```
http://localhost:8080/api/v1
```

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
    "email": "imran@gmail.com",
    "password": "password",
    "password_confirmation": "password"
}
```

Response `201`:
```json
{
    "message": "User registered successfully.",
    "user": { "id": 1, "name": "Imran Khan", "email": "imran@gmail.com" },
    "token": "1|abc123..."
}
```

#### Login

```http
POST /api/v1/auth/login
```

```json
{
    "email": "imran@gmail.com",
    "password": "password"
}
```

Response `200`:
```json
{
    "message": "Login successful.",
    "user": { "id": 1, "name": "Imran Khan", "email": "imran@gmail.com" },
    "token": "2|xyz789..."
}
```

---

### Tasks

| Method | Endpoint | Auth | Middleware | Description |
|---|---|---|---|---|
| `GET` | `/task-management/tasks` | ✅ | — | List own tasks |
| `POST` | `/task-management/tasks` | ✅ | — | Create a task |
| `GET` | `/task-management/tasks/overdue` | ✅ | — | List overdue tasks |
| `GET` | `/task-management/tasks/by-user/{userId}` | ✅ | — | Tasks by user |
| `GET` | `/task-management/tasks/by-project/{projectId}` | ✅ | — | Tasks by project |
| `GET` | `/task-management/tasks/{id}` | ✅ | owner | Show a task |
| `PUT/PATCH` | `/task-management/tasks/{id}` | ✅ | owner, overdue | Update a task |
| `DELETE` | `/task-management/tasks/{id}` | ✅ | owner | Delete a task |

#### List Tasks

```http
GET /api/v1/task-management/tasks
GET /api/v1/task-management/tasks?project_id=1
```

#### Create Task

```http
POST /api/v1/task-management/tasks
```

```json
{
    "title": "My Task",
    "description": "Task description",
    "status": "todo",
    "deadline": "2026-12-31 00:00:00",
    "project_id": 1
}
```

Response `201`:
```json
{
    "message": "Task created successfully.",
    "task": {
        "id": 1,
        "title": "My Task",
        "description": "Task description",
        "status": "todo",
        "deadline": "2026-12-31T00:00:00.000000Z",
        "user_id": 1,
        "project_id": 1
    }
}
```

#### Update Task

```http
PUT /api/v1/task-management/tasks/{id}
```

```json
{
    "status": "in_progress"
}
```

#### Overdue Tasks

```http
GET /api/v1/task-management/tasks/overdue
```

> Regular users see only their own overdue tasks. Admins see all overdue tasks across all users.

---

### Projects

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/task-management/projects` | ✅ | List own projects |
| `POST` | `/task-management/projects` | ✅ | Create a project |
| `GET` | `/task-management/projects/{id}` | ✅ | Show project with tasks |
| `PUT/PATCH` | `/task-management/projects/{id}` | ✅ | Update a project |
| `DELETE` | `/task-management/projects/{id}` | ✅ | Delete a project |

#### Create Project

```http
POST /api/v1/task-management/projects
```

```json
{
    "name": "My Project",
    "description": "Project description"
}
```

Response `201`:
```json
{
    "message": "Project created successfully.",
    "project": {
        "id": 1,
        "name": "My Project",
        "description": "Project description",
        "user_id": 1
    }
}
```

---

## Authorization Rules

| Action | Regular User | Admin |
|---|---|---|
| Register / Login | ✅ | ✅ |
| CRUD own tasks | ✅ | ✅ |
| View another user's task | ❌ | ✅ |
| Update another user's task | ❌ | ✅ |
| Delete another user's task | ❌ | ✅ |
| Edit overdue task | ❌ | ✅ |
| View overdue tasks | Own only | All users |
| View tasks by user | ❌ | ✅ |
| CRUD own projects | ✅ | ✅ |

---

## Validation Rules

### Task

| Field | Rules |
|---|---|
| `title` | Required, string, max 255 characters |
| `description` | Required, string |
| `status` | Required, one of: `todo`, `in_progress`, `done` |
| `deadline` | Optional, valid date, must be in the future |
| `project_id` | Optional, integer, must exist in projects table |

### Project

| Field | Rules |
|---|---|
| `name` | Required, string, max 255 characters |
| `description` | Optional, string |

### Register

| Field | Rules |
|---|---|
| `name` | Required, string, max 255 characters |
| `email` | Required, valid email, unique, max 255 characters |
| `password` | Required, string, min 8 characters, confirmed |

---

## Event System

When a task is updated via the API, the following flow is triggered automatically:

```
PUT /api/v1/task-management/tasks/{id}
            ↓
    TaskController@update
            ↓
    TaskUpdated event dispatched
            ↓
    SendOverdueTaskNotification listener
            ↓
    shouldQueue() checks:
      - deadline is not null
      - deadline is in the past
      - status is not 'done'
            ↓
    Job pushed to Redis queue
            ↓
    Queue worker processes the job
            ↓
    TaskDeadlineOverdue notification sent
            ↓
    Email delivered via Mailpit (local)
```

### Viewing notifications locally

Open **Mailpit** in your browser:

```
http://localhost:8025
```

All outgoing emails are captured here during local development.

### Monitoring the queue worker

```bash
# Watch queue worker logs in real time
docker logs tasks_manager_queue -f

# Check failed jobs
docker compose exec app php artisan queue:failed

# Retry failed jobs
docker compose exec app php artisan queue:retry all
```

---

## Testing with Postman

A Postman collection and environment are included in the repository root:

| File                                      | Description |
|-------------------------------------------|---|
| `TaskManagerApi.postman_collection.json`  | All API requests organised by domain |
| `TaskManagerApi.postman_environment.json` | Environment variables for local testing |

### Import into Postman

1. Open Postman
2. Click **Import** in the top left
3. Drag and drop both JSON files
4. Select the **Task Manager Local** environment from the top right dropdown
5. Start with **Auth → Register** or **Auth → Login**

### Environment variables

| Variable | Value | Set by |
|---|---|---|
| `base_url` | `http://localhost:8080/api/v1` | Manual |
| `token` | *(auto-filled)* | Login/Register script |
| `task_id` | *(auto-filled)* | Create Task script |
| `project_id` | *(auto-filled)* | Create Project script |

---

## Default Seeded Users

After running `php artisan migrate --seed`:

| Name               | Email               | Password | Role |
|--------------------|---------------------|---|---|
| Admin User         | admin@example.com   | password | admin |
| Imran Khan         | imran@gmail.com.com | password | user |
| *(3 random users)* | *(random)*          | password | user |

Each user has projects and tasks assigned, including overdue tasks for testing the notification system.

---

## Docker Services

### Useful commands

```bash
# Start all containers
docker compose up -d

# Stop all containers
docker compose down

# Rebuild containers
docker compose up -d --build

# View logs
docker logs tasks_manager_app -f
docker logs tasks_manager_queue -f

# Access the app container
docker compose exec app bash

# Run artisan commands
docker compose exec app php artisan <command>

# Fresh migration with seed
docker compose exec app php artisan migrate:fresh --seed

# Clear all caches
docker compose exec app php artisan optimize:clear
```

---
