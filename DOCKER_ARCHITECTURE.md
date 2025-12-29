# Docker Architecture

## Container Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                       Docker Environment                          │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  App Container (PHP 8.3 + Nginx + Supervisor)             │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │ │
│  │  │   Nginx      │  │  PHP-FPM     │  │  Supervisor  │    │ │
│  │  │   :80        │  │   :9000      │  │              │    │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │ │
│  │                                                            │ │
│  │  Laravel Application                                       │ │
│  │  /var/www/html                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                    │
│                              ▼                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  PostgreSQL 16                                             │ │
│  │  Port: 5432                                                │ │
│  │  Database: personal_finance                                │ │
│  │  User: sail                                                │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Redis                                                     │ │
│  │  Port: 6379                                                │ │
│  │  Purpose: Cache & Sessions                                 │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Mailpit                                                   │ │
│  │  SMTP: 1025                                                │ │
│  │  Web UI: 8025                                              │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │   Host Machine   │
                    │                  │
                    │  localhost:80    │
                    │  localhost:8025  │
                    │  localhost:5432  │
                    │  localhost:6379  │
                    └──────────────────┘
```

## Port Mappings

| Service    | Container Port | Host Port | Purpose              |
|------------|----------------|-----------|----------------------|
| App        | 80             | 80        | Web Application      |
| PostgreSQL | 5432           | 5432      | Database             |
| Redis      | 6379           | 6379      | Cache & Sessions     |
| Mailpit    | 1025           | 1025      | SMTP                 |
| Mailpit UI | 8025           | 8025      | Email Testing UI     |
| Vite       | 5173           | 5173      | Frontend Dev Server  |

## Data Persistence

Volumes are used to persist data:
- `personal-finance-pgsql` - PostgreSQL data
- `personal-finance-redis` - Redis data
- `./` mounted to `/var/www/html` - Application code

## Network

All containers communicate via the `personal-finance` bridge network.

## Supervisor Configuration

The app container runs two processes via Supervisor:
1. **PHP-FPM** - PHP FastCGI Process Manager
2. **Nginx** - Web server serving the Laravel application

## Development Workflow

```
Developer
    │
    ▼
Edit Code
    │
    ▼
Code Changes (Auto-synced via volume mount)
    │
    ▼
Nginx → PHP-FPM → Laravel App
    │
    ├─→ PostgreSQL (Database queries)
    ├─→ Redis (Cache/Sessions)
    └─→ Mailpit (Email testing)
```

## Environment Variables

Key environment variables in `.env`:
- `DB_HOST=pgsql` - PostgreSQL container name
- `REDIS_HOST=redis` - Redis container name
- `MAIL_HOST=mailpit` - Mailpit container name
- `APP_PORT=80` - Application port

## Health Checks

Both PostgreSQL and Redis include health checks:
- **PostgreSQL**: `pg_isready` command
- **Redis**: `redis-cli ping` command

## Security Notes

- Default passwords are used for development
- Change `DB_PASSWORD` in production
- Container runs as non-root user `sail`
- Nginx serves only public directory
- Hidden files (`.env`, etc.) not accessible via web
