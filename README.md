# PHP Newsletter

PHP Newsletter is a self-hosted email marketing and subscriber management system built on Laravel 13. It helps you manage subscriber lists, create templates, schedule campaigns, track opens and clicks, and keep all campaign data on your own server.

This README is based on `manual_en.html` and summarizes the project for installation, launch, and day-to-day use.

## Overview

PHP Newsletter is suitable for:

- newsletters and editorial digests;
- marketing campaigns and promotional mailings;
- service notifications and website announcements;
- internal or member-only mailing lists.

It allows you to:

- manage subscribers and categories;
- create HTML and plain text campaigns;
- personalize messages with macros;
- attach files to templates;
- schedule campaigns for future delivery;
- collect open and click statistics;
- run the whole system on your own infrastructure.

## Key Features

### Email Delivery

- SMTP, `mail()`, and `sendmail` support
- scheduled and manual mailing
- resend flow for unsent messages
- attachments support
- custom email headers

### Templates and Personalization

- WYSIWYG editor
- HTML and plain text messages
- macros for subscriber personalization
- UTF-8 email subjects, content and attachment names

All outgoing messages use UTF-8, including manual mailings, test messages,
scheduled mailings and retries. The former outgoing charset setting is no longer
used or seeded. CSV and TXT subscriber imports accept UTF-8 only,
with or without a BOM; no charset selection or conversion is performed.

Manual, scheduled and retry mailings process recipients in subscriber ID order.
Subjects and message bodies retain their Cyrillic characters; the former
random-order and character-substitution settings are no longer supported.

### Subscriber Management

- categories and segmentation
- manual subscriber creation
- import and export tools
- subscription confirmation and unsubscribe flow

### Projects and user roles

Templates, schedules and delivery/click statistics belong to a project.
Subscriber categories are global and are not owned by a project.
Subscribers have one record per email address and can belong to multiple projects
or none. Name, email, category memberships, active status, unsubscribe token and last-send time are shared
across projects; unsubscribing stops all mailings for that subscriber.
Manual campaigns, scheduled delivery, retries and exports keep project data separate.

- **Administrator** has full access and manages users, global settings, SMTP,
  macros and subscriber categories. Only administrators can change a project owner.
- **Project administrator** creates projects and manages owned or assigned projects,
  their templates, subscribers, schedules and reports.
- **Moderator** manages subscribers (including import, export and deletion) and
  views/downloads reports for assigned projects. A moderator cannot send campaigns
  or clear reports. Project ownership retains management access to that project.

Create users with the appropriate role in **Users**, then assign them in
**Projects → Edit**. Administrators and the project owner can assign project
administrators; assigned project administrators can also assign moderators.
Changing a user's global role clears their assignments, so assign their new role
to the relevant projects afterwards. Dashboard cards and navigation follow access.

Inactive projects retain their data, but do not send campaigns or accept public
subscriptions. Deleting a project after confirmation also deletes its templates,
attachments, scheduled mailings and statistics. Subscriber records, categories and
their subscription links are preserved, including contacts left without projects.
Deleting or deactivating a project does not change the global category list. Shared
mailing logs are retained while they contain results from another project.
The default project has ID `0` and is available to every admin-panel user within
their role permissions, without an explicit membership. It is virtual: the
`projects` table contains only ordinary projects. Its name follows the interface
language; its status is always active. It has no owner and cannot be edited or
deleted, including by administrators.
Choose zero or more projects when creating or importing subscribers. An empty
selection adds the contact to the default project. Clearing all memberships when
editing still leaves an unassigned contact. Only administrators can view contacts
without projects and permanently delete contact records. Project administrators and moderators see contacts assigned
to their projects; removing a contact removes only their accessible project memberships
and preserves global category subscriptions. Editing memberships or importing cannot
remove links to projects the current user cannot access.
Subscriber creation, editing, import and export always show the same global category
list, including when no projects are selected. Changing project selection does not
clear categories. Editing categories replaces the contact’s global category selection.
Imports reuse an existing email without overwriting its name or active status, and
add selected categories without deleting existing category memberships.
Exports combine the selected projects without duplicate contacts; an empty project
selection exports unassigned contacts for administrators. Templates use the default
project when no project is submitted. The default project is selected first in the
form, and the project link cannot be changed after template creation.
Generate subscription embed code separately for each project in **Subscription form**.
The public form and subscription endpoints use the default project (`0`) when
`project_id` is omitted. The category list is global in every project. Embed code
for the default project loads `/categories` without a query parameter. Forms for
other projects must pass their explicit `project_id` to select the subscription membership.

### Analytics and Reporting

- sent and failed delivery history
- HTML open tracking
- link click tracking
- downloadable spreadsheet reports

### Admin interface

The admin panel uses AdminLTE 4.1 and Bootstrap 5.3, following the implementation
in [janickiy/phpnewsletter_8](https://github.com/janickiy/phpnewsletter_8).
Navigation, forms, settings tabs, DataTables, mailing dialogs, Summernote and the
FullCalendar schedule use the updated components. Assets are bundled locally;
Docker does not require an npm build. See [admin UI assets](docs/admin-ui.md)
for versions and maintenance details.

## Quick Start

1. Install the application and complete the setup wizard.
2. Log in to the admin panel as the administrator.
3. Configure the delivery method: SMTP, `mail()`, or `sendmail`.
4. Create a project and the global subscriber categories.
5. Add or import subscribers.
6. Create a template and define macros if needed.
7. Send a test email.
8. Create a schedule entry and configure cron.
9. Review logs and reports after the first live mailing.

## System Requirements

- PHP 8.4.1 or newer
- Laravel 13
- MySQL 5.6 or newer
- Apache 2+ with `mod_rewrite`, or Nginx pointing to `public/index.php`

### Required PHP Extensions

- `mbstring`
- `zip`
- `curl`
- `iconv`
- `gd`
- `fileinfo`

## Installation

### Local Docker environment

Run commands from the project root. The Compose project is named `phpnewsletter_8_1`,
so its containers and database volume are separate from older PHP Newsletter installations.
Published ports are bound to localhost.

For an already installed checkout with `.env` and database data:

```bash
docker compose -f docker/docker-compose.yml up -d --build
```

- Application: <http://localhost:8081>
- Mailpit inbox: <http://localhost:8025>
- MySQL from the host: `127.0.0.1:3307`
- SMTP from the host: `127.0.0.1:1025`; from the app container: `mailpit:1025`

For a fresh checkout, start the web application and its dependencies first:

```bash
docker compose -f docker/docker-compose.yml up -d --build app mysql mailpit
docker compose -f docker/docker-compose.yml logs -f app
```

Wait for Composer installation and Apache startup, then open <http://localhost:8081/install>.
Do not create `.env` before using the web installer: this application uses its presence
as the installation flag. Use database host `mysql`, database/user `phpnewsletter`,
and password `secret` with the default Compose settings. Choose administrator credentials
in the installer. After installation, start the scheduler:

```bash
docker compose -f docker/docker-compose.yml up -d --build scheduler
```

The scheduler runs Laravel's `schedule:work` as `www-data`, including scheduled mailings
and retries. It waits for a healthy application and database before starting.

For local email delivery, select SMTP in application Settings and add an active SMTP
profile: host `mailpit`, port `1025`, username/password `mailpit`, authentication `plain`,
encryption `no`, and sender `newsletter@example.test`. Mailpit captures these messages
for inspection in its inbox. The `MAIL_*` variables configure Laravel mail separately;
newsletter delivery uses the SMTP profile stored in the database.

Useful commands:

```bash
docker compose -f docker/docker-compose.yml ps
docker compose -f docker/docker-compose.yml logs --tail=100 app scheduler
docker compose -f docker/docker-compose.yml stop
docker compose -f docker/docker-compose.yml up -d
```

MySQL and Mailpit data persist in named Docker volumes. Keep `.env` when restarting;
it contains the application encryption key and database configuration. Avoid `down -v`
unless you intend to erase this environment's data. Ports can be overridden using
`APP_PORT`, `MYSQL_PORT`, `MAILPIT_UI_PORT`, and `MAILPIT_SMTP_PORT` in the root `.env`;
update `APP_URL` when changing the application port.

### Upgrading an existing Docker installation

`database/migrations` contains consolidated creation migrations for each table.
They include the current columns, indexes and foreign keys and create
the complete schema for new installations. They do not reapply changes to tables
whose creation migrations have already run: older installations must first have
nullable `ready_sent.schedule_id` and `log_id` with `ON DELETE SET NULL`, and the
`subscribers` indexes on `name` and `created_at`. Retired charset/randomization
settings and the `charsets` table are not part of this baseline.
Installations from before project support also require a separate schema and data
migration to associate their existing records with projects.

The category and redirect creation migrations already contain their final schemas:
neither table has `project_id` or `project_reference_id`, and `redirect` has nullable
`template_id` and `template` snapshot fields. The three September 24 upgrade migrations
have been consolidated into these creation migrations. Running `migrate` on an older
database does not alter tables whose creation migrations have already run; such a
database needs a separate schema conversion that preserves its existing records.

Each new tracked link identifies its template; click history
retains the template ID and name even if that template is later renamed or deleted.
Existing click records and old links have no reliable template attribution, so their
newsletter fields remain empty. Link reports and Excel exports separate newsletters
that use the same URL. Administrators can see all retained click history; project
roles see clicks for templates in their accessible projects.

For virtual default-project support, `templates`,
`schedule`, `project_subscriber` and `ready_sent` use a stored generated column
`project_reference_id = NULLIF(project_id, 0)` with a restrictive foreign key to
`projects.id`. This permits the virtual ID `0` while rejecting missing ordinary
projects. Replace the former direct project foreign keys with these generated-column
foreign keys before removing the old `projects.id = 0` row and its redundant
`project_user` assignments. Preserve every other reference with `project_id = 0`.
`project_user` continues to reference ordinary projects directly. Take a backup and
stop the application and scheduler while converting an existing database; the
consolidated creation migrations do not perform this conversion.

Installations with the former `subscribers.project_id` column also need a data
conversion before using this version: copy memberships to `project_subscriber`,
reconcile duplicate email records and their categories/statistics, then replace
the project/email unique index with a global unique email index and remove the
old column. Preserve the global subscriber status, token and delivery timestamps.
Running `migrate` alone only creates the membership table; it does not convert an
existing subscribers table. Perform this conversion with the application and
scheduler stopped, after taking a database backup.

The application runs on Laravel 13 and PHP 8.4. Keep a backup of the database,
`.env`, and uploaded files before upgrading. Keep the existing `.env` and database
volume so accounts, encryption keys, and campaign data remain available.

```bash
docker compose -f docker/docker-compose.yml stop app scheduler
docker compose -f docker/docker-compose.yml build app scheduler
docker compose -f docker/docker-compose.yml run --rm --no-deps --user www-data --entrypoint composer app install --no-interaction --prefer-dist
docker compose -f docker/docker-compose.yml run --rm --no-deps --user www-data --entrypoint php app artisan optimize:clear
docker compose -f docker/docker-compose.yml run --rm --no-deps --user www-data --entrypoint php app artisan migrate --force
docker compose -f docker/docker-compose.yml up -d --wait
```

Template attachments are stored privately. Downloads are served through an
authenticated endpoint that checks the template's project.
Apache denies the old public attachment paths via `public/.htaccess`; on Nginx,
also deny requests to `/storage/attach/` in the server configuration.

`composer.lock` is included in version control to keep installations reproducible.
The web container synchronizes dependencies with this lock file at startup,
including when an existing `vendor` directory is present.

### Running tests

The test profile runs PHPUnit 13 with PHP 8.4 and a separate MySQL 8.4 database.
The test database and test storage are temporary; the application's MySQL volume
and uploaded files are not used. MySQL is required because delivery reports use
MySQL-specific date functions.

```bash
docker compose -f docker/docker-compose.yml --profile test run --build --rm tests
docker compose -f docker/docker-compose.yml --profile test rm -sf mysql-test
```

PHPUnit forces the dedicated test database credentials and refuses to run with a
cached application configuration. Run `php artisan config:clear` in the app
container first if configuration has been cached.

### Manual installation

1. Upload or extract the project into your website directory.
2. Configure the virtual host or domain to point to the application.
3. Make sure the writable directories are writable by PHP.
4. Open the installer in the browser:

```text
http://your-domain/install/
```

5. Complete the setup wizard:

- system requirement check
- permission check
- database configuration
- administrator creation
- installation completion

The installer creates `.env`, writes database credentials, runs migrations and seeders, generates the application key, and creates the first administrator account.

### Writable Directories

The application typically needs write access to:

- `storage/app`
- `storage/framework/cache`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`

## First Login Checklist

After installation:

1. Open `/login` and sign in.
2. Go to `Settings` and configure:
- sender email address
- sender name
- content type
- character set
- subscription confirmation behavior
3. Go to `SMTP` and add at least one valid SMTP profile if SMTP will be used.
4. Verify the site URL and unsubscribe links.
5. Create a test template and send a test email.

## Admin Panel Modules

| Module | Purpose |
| --- | --- |
| Templates | Create and edit email templates, add attachments, and start manual sends |
| Category | Create subscriber categories and audience segments |
| Subscribers | Manage subscribers, import/export data, activate, deactivate, and remove entries |
| Macros | Define reusable placeholders for personalization |
| Schedule | Plan campaign delivery by date, time, and category |
| Log | Review send history and download reports |
| Redirect | Track link clicks recorded through internal redirects |
| SMTP | Manage SMTP servers and related settings |
| Settings | Control global behavior, delivery, content, and intervals |
| Users | Manage admin panel users and roles |
| Pages | Utility pages such as PHP info, subscription form, and cron help |

## Subscribers

### Import

Supported import formats:

- TXT
- CSV
- XLS
- XLSX
- ODS

Before import, it is recommended to:

- remove duplicates;
- validate email addresses;
- prepare destination categories in advance;
- save CSV/TXT files in UTF-8 (with or without a BOM).

### Export

Supported export formats:

- TXT
- XLSX

### Public Subscription Endpoints

The application includes built-in public endpoints:

- `/form` - subscription form
- `/categories` - category list for the public form
- `/add-sub` - add a new subscriber
- `/unsubscribe/{subscriber}/{token}` - unsubscribe link
- `/subscribe/{subscriber}/{token}` - subscription confirmation link

## Templates, Macros, and Attachments

Templates can contain:

- message subject or name;
- HTML or plain text body;
- delivery priority;
- optional attachments.

Macros are placeholders used to personalize outgoing mail. Typical use cases include subscriber name, email address, service links, and other custom values.

Attached files are linked to a template and added automatically during delivery.

## Delivery Methods

| Mode | Recommended Use |
| --- | --- |
| SMTP | Recommended for production and reliable delivery |
| `mail()` | Suitable only for simple or temporary setups |
| `sendmail` | Use when a working sendmail binary is available |

Key settings to review:

- sender email address and sender name;
- content type: HTML or plain text;
- character set;
- sendmail path if this mode is used;
- send limits per run;
- sleep interval between messages;
- resend interval for the same subscriber.

## Tracking and Reports

### Open Tracking

For HTML email, the system injects a `1x1` tracking image into the message body. When the image is loaded, the message is marked as opened.

Notes:

- available only for HTML emails;
- depends on external image loading in the recipient mail client;
- useful for analytics, but never perfectly accurate.

### Click Tracking

Links can be routed through an internal redirect endpoint so the system records click statistics before sending the user to the final destination.

### Reports

The system stores:

- send history;
- success and failure statuses;
- HTML open data;
- click-through records;
- downloadable spreadsheet reports.

## Scheduler and Cron

The project uses three console commands:

- `php artisan emails:send` - processes scheduled delivery
- `php artisan emails:unsent` - retries unsent messages
- `php artisan emails:remove-unconfirmed-subscriber` - removes expired unconfirmed subscriptions when enabled

Both mailing commands process active schedules within their start/end window. For the
default project, recipients must be active members of that project and belong to a
selected global category; a schedule with no
categories sends nothing. Other projects target all their active subscribers, without
category filtering. Retries process failed deliveries only and skip subscribers already
successfully sent that schedule.

Multiple category memberships do not duplicate a recipient. A plain console invocation
also prevents another instance of the same mailing command from starting, and overlapping
manual delivery requests for the same log are rejected. Successful retries are saved after
each message so later interruptions do not repeat completed deliveries.

The Laravel scheduler is currently configured to run:

- `emails:send` every minute
- `emails:unsent` every ten minutes
- `emails:remove-unconfirmed-subscriber` every ten minutes

The schedules are defined in `routes/console.php`. Command classes in
`app/Console/Commands` are discovered automatically by Laravel.

Example direct cron commands shown by the project:

```text
/usr/bin/php -q /path/to/artisan emails:send
/usr/bin/php -q /path/to/artisan emails:unsent
```

If cron is not configured, scheduled campaigns will not start automatically.

## Roles and Security

Main roles:

- `admin` - full access to all modules, including SMTP, settings, and users
- `project_admin` - manages owned or assigned projects, templates, subscribers,
  schedules and reports
- `moderator` - manages subscribers and views/downloads reports in assigned projects;
  project owners retain management access to their projects

Recommendations:

- use a strong administrator password;
- restrict admin panel access by IP if possible;
- create database backups before upgrades;
- do not use production SMTP credentials in development or test environments;
- review send logs and application logs regularly.

## Deliverability Recommendations

- prefer SMTP over `mail()` for production campaigns;
- configure SPF, DKIM, and ideally DMARC for the sender domain;
- avoid sudden high-volume mailing without warming up the domain and IP;
- make sure unsubscribe links are valid and publicly reachable;
- keep attachments and HTML complexity under control;
- send test campaigns to several mailbox providers before a large mailing.

## Troubleshooting

### Emails Are Not Sent

- verify SMTP host, port, username, and password;
- make sure the firewall allows outbound SMTP connections;
- check send logs and application logs;
- confirm that cron is active.

### Subscriptions Do Not Arrive

- check that the public subscription form is available and `/add-sub` works;
- make sure the email address is not already subscribed to the selected project;
  an existing contact from another project is reused with its current global status;
- verify whether confirmation is enabled and whether confirmation emails are delivered.

### Open Tracking Does Not Work

- make sure the campaign is sent as HTML;
- remember that many mail clients block external images;
- confirm that the application URL is correct and the tracking pixel is publicly reachable.

### Scheduled Campaigns Do Not Start

- check cron configuration and `php artisan schedule:run` usage if you rely on Laravel scheduler;
- make sure the schedule entry falls into the correct time window;
- verify the server time zone.

## Project Structure

- `app/` - controllers, models, services, DTOs, middleware, helpers, and console commands
- `bootstrap/app.php` - application setup, routes, middleware, redirects, and exception handling
- `bootstrap/providers.php` - application service providers; package providers use Composer discovery
- `app/Http/Middleware/` - application-specific installation, locale, permission, and subscriber middleware
- `routes/` - web and API routes, console commands, and scheduled tasks
- `lang/` - interface translations
- `resources/views/` - admin templates, public subscription pages, and installer screens
- `database/migrations/` - database schema definitions
- `storage/` - logs, cache, temporary files, and stored attachments
- `public/` - public assets and entry point

The application uses the Laravel 13 bootstrap structure. Framework middleware,
HTTP and console kernels, and the default exception handler are provided by Laravel;
their standard classes are not duplicated under `app/`. Custom rate limiting and
helper aliases are registered in `AppServiceProvider`.

## Summary

PHP Newsletter is a complete self-hosted mailing solution with subscriber management, segmentation, templates, analytics, and scheduling. For stable production use, pay special attention to SMTP configuration, cron, writable directories, and sender-domain DNS records.
