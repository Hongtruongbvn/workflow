# WorkFlow Pro

**WorkFlow Pro** is a project and task management application built with Laravel. It helps teams manage workspaces, projects, tasks, deadlines, members, and project progress in one place.

## Features

* User authentication
* Workspace management
* Member management and roles
* Project management
* Task management
* Kanban Board
* Task assignment and deadlines
* Milestones
* Comments and file attachments
* Notifications
* Project progress tracking and reports

## Tech Stack

* Laravel
* PHP
* SQLite
* Blade
* Laravel Authentication

## Requirements

* PHP
* Composer
* Node.js & npm
* SQLite

## Installation

Clone the repository:

```bash
git clone YOUR_REPOSITORY_URL
cd YOUR_PROJECT_FOLDER
```

Install dependencies:

```bash
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Start the development server:

```bash
php artisan serve
```

For frontend assets:

```bash
npm run dev
```

The application will be available at:

```text
http://localhost:8000
```

## Environment Configuration

Create a `.env` file based on `.env.example`.

```env
APP_NAME=YOUR_APP_NAME
APP_ENV=YOUR_APP_ENV
APP_KEY=YOUR_APP_KEY
APP_DEBUG=YOUR_APP_DEBUG
APP_URL=YOUR_APP_URL

APP_LOCALE=YOUR_APP_LOCALE
APP_FALLBACK_LOCALE=YOUR_APP_FALLBACK_LOCALE
APP_FAKER_LOCALE=YOUR_APP_FAKER_LOCALE

APP_MAINTENANCE_DRIVER=YOUR_APP_MAINTENANCE_DRIVER

BCRYPT_ROUNDS=YOUR_BCRYPT_ROUNDS

LOG_CHANNEL=YOUR_LOG_CHANNEL
LOG_STACK=YOUR_LOG_STACK
LOG_DEPRECATIONS_CHANNEL=YOUR_LOG_DEPRECATIONS_CHANNEL
LOG_LEVEL=YOUR_LOG_LEVEL

DB_CONNECTION=YOUR_DB_CONNECTION
DB_HOST=YOUR_DB_HOST
DB_PORT=YOUR_DB_PORT
DB_DATABASE=YOUR_DB_DATABASE
DB_USERNAME=YOUR_DB_USERNAME
DB_PASSWORD=YOUR_DB_PASSWORD

SESSION_DRIVER=YOUR_SESSION_DRIVER
SESSION_LIFETIME=YOUR_SESSION_LIFETIME
SESSION_ENCRYPT=YOUR_SESSION_ENCRYPT
SESSION_PATH=YOUR_SESSION_PATH
SESSION_DOMAIN=YOUR_SESSION_DOMAIN

BROADCAST_CONNECTION=YOUR_BROADCAST_CONNECTION
FILESYSTEM_DISK=YOUR_FILESYSTEM_DISK
QUEUE_CONNECTION=YOUR_QUEUE_CONNECTION

CACHE_STORE=YOUR_CACHE_STORE

MEMCACHED_HOST=YOUR_MEMCACHED_HOST

REDIS_CLIENT=YOUR_REDIS_CLIENT
REDIS_HOST=YOUR_REDIS_HOST
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PORT=YOUR_REDIS_PORT

MAIL_MAILER=YOUR_MAIL_MAILER
MAIL_SCHEME=YOUR_MAIL_SCHEME
MAIL_HOST=YOUR_MAIL_HOST
MAIL_PORT=YOUR_MAIL_PORT
MAIL_USERNAME=YOUR_MAIL_USERNAME
MAIL_PASSWORD=YOUR_MAIL_PASSWORD
MAIL_FROM_ADDRESS=YOUR_MAIL_FROM_ADDRESS
MAIL_FROM_NAME=YOUR_MAIL_FROM_NAME

AWS_ACCESS_KEY_ID=YOUR_AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY=YOUR_AWS_SECRET_ACCESS_KEY
AWS_DEFAULT_REGION=YOUR_AWS_DEFAULT_REGION
AWS_BUCKET=YOUR_AWS_BUCKET
AWS_USE_PATH_STYLE_ENDPOINT=YOUR_AWS_USE_PATH_STYLE_ENDPOINT

VITE_APP_NAME=YOUR_VITE_APP_NAME
```

> **Note:** Never commit your `.env` file or sensitive credentials to the repository. Use `.env.example` for sharing environment configuration.

## License

This project is for personal portfolio and learning purposes.
