# Local development

This checkout runs locally with Docker Desktop. The stack is PHP 8.3 with
Apache, MySQL 8.4, Moodle's `public/` directory as the web root, and a
background container that runs Moodle cron every minute.

## Start

```sh
docker compose up --build -d
```

The database uses the imported `elearning_db` backup and its `el_` table
prefix. Open <http://localhost:8080> and sign in with a user from that backup.
For a completely empty MySQL volume, the local fallback administrator is:

- Username: `admin`
- Password: `Admin123!`

## Operate

```sh
docker compose ps
docker compose logs -f web
docker compose down
```

Application code is bind-mounted from this checkout. MySQL data,
Moodle-uploaded data, and Composer dependencies live in named Docker volumes.
Running `docker compose down` preserves them.

## ISP platform configuration

After importing or recreating the database, apply the idempotent ISP feature
configuration and run its read-only verification:

```sh
docker compose exec -T web php /var/www/moodle/docker/configure-learning-platform.php
docker compose exec -T web php /var/www/moodle/docker/verify-learning-platform.php
```

The configuration enables announcements, completion, learning plans, badges,
certificates and analytics. It also creates one Attendance register per course
and the reports **Analytique ISP — progression et résultats** and
**Présences — détail des séances**.

Administrators can open the consolidated dashboard at:

<http://localhost:8080/report/isplearninganalytics/index.php>

Active time is an estimate derived from Moodle's standard event log. Events
are grouped into sessions; a gap longer than 30 minutes begins a new session,
and every observed session is credited with at least one minute. The dashboard
supports 30-day, 90-day, 12-month and all-history periods and CSV export.

The Attendance activity is a local Moodle 5.2 compatibility port of the
official `MOODLE_501_STABLE` branch. Its exact upstream commit and local support
declaration are documented in `public/mod/attendance/README.md`.

The earlier PostgreSQL Docker volume is not deleted when switching this stack
to MySQL, so it remains available as a rollback copy.
