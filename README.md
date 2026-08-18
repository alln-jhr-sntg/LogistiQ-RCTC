# MoveOps

Web-based vehicle reservation and fleet management system with a rule-based
vehicle recommendation engine, built for **Remix Construction and Trading
Corporation (RCTC)** and its sister companies. Formerly developed under the
working title "LogistiQ"; the product and database now use the **MoveOps**
name (`system_settings.system_name`). The repository folder stays `lvms`

A companion native Android app, **MoveOps Driver**, gives drivers a
mobile client for their assigned trips and reports GPS location back to the
web system's live map. It is a separate project and is not part of this
repository; a build of it is served for download from inside the web app at
`public/downloads/moveops-driver.apk`.

## What it does

- **Reservations → Gatepasses → Trips.** An employee requests a vehicle;
  the request is scored by the recommendation engine, approved, issued a
  gatepass, and only then becomes a trip.
- **Rule-based vehicle recommendation.** Hard disqualifiers (passenger
  capacity, cargo capacity, schedule conflict) followed by six weighted
  scoring criteria, including a Philippine LTO truck-ban ("weight coding")
  model that penalizes over-GVWR vehicles only when the trip window actually
  overlaps a ban period.
- **Live GPS tracking.** The driver app posts location during a trip; the
  admin live map polls and renders it, with a health status (live / delayed
  / stale) derived from ping age.
- **Fleet upkeep.** Maintenance interval tracking with automatic
  notifications when a vehicle crosses its service threshold.
- **Multi-company scope.** Three seeded companies — REMIX, IDEAL, TenBuild —
  share one fleet of vehicles and drivers. Viewing spans companies for
  admin-and-above roles; acting on a record is scoped to the record's own
  company (drivers are exempt from company scoping entirely).
- **Reporting.** CSV export for trip history, maintenance history, and
  vehicle utilization.

## Roles

| Role          | Scope                                                                   |
| ------------- | ----------------------------------------------------------------------- |
| `super_admin` | Full system access; the only role that can approve gatepasses.          |
| `fleet_admin` | Runs the shared fleet across all three companies; also the REMIX admin. |
| `admin`       | Own-company only.                                                       |
| `employee`    | Requests reservations, tracks their own trips/projects.                 |
| `driver`      | Executes trips via the Android app; exempt from company scope.          |

## Tech stack

- PHP 8.3, no framework — routing is a flat array matched against
  `index.php?url=...` (no `.htaccess`, no clean URLs)
- MySQL via PDO, prepared statements throughout
- Vanilla JS (no build step) — `public/js/`
- Hand-rolled CSS with a `:root` design-token block — `public/css/app.css`
- No Composer, no Bootstrap
- REST API under `api/` (bearer-token auth) for the Android driver app
- Deployed on Hostinger shared hosting; developed locally on XAMPP

## Setup

1. Clone or extract this repository into `xampp/htdocs/`, named `lvms`.
2. Copy `config/secrets.example.php` to `config/secrets.php` (gitignored)
   and fill in:
   - `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` for your local MySQL
   - `APP_BASE` — `''` for document root, or `/lvms` on XAMPP
   - `DEBUG` — `true` locally
   - `GOOGLE_MAPS_API_KEY` — required for the location picker and live map
3. Create the database and import the schema, then apply every file in
   `database/migrations/` in date order:
   ```
   mysql -u root -p your_db_name < database/lvms_schema_final_v2.sql
   ```
4. Start Apache and MySQL in XAMPP.
5. The schema seeds `system_settings` and the three companies, but no user
   accounts — there is no public registration route. Insert an initial
   `super_admin` row into `users` directly (with a `password_hash` from
   PHP's `password_hash()`) to get your first login.
6. Visit `http://localhost/lvms/` or `http://localhost/lvms/index.php?url=login`.

## Project layout

```
index.php          web router (query-string routes, CSRF-checked POSTs)
api/                REST API for the Android driver app (bearer-token auth)
controllers/        one controller per resource
models/             PDO data access, extends models/BaseModel.php
services/           domain logic: recommendation engine, weight coding,
                    maintenance checks, code generation, trip limits
core/               Auth, CSRF, and view helpers
config/             app bootstrap, constants, DB config, secrets (gitignored)
views/              server-rendered PHP templates
public/             app.css, JS, uploads, and the driver APK download
database/           schema (lvms_schema_final_v2.sql), DBML reference, migrations
```
