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
- multi-encoding support

### Subscriber Management

- categories and segmentation
- manual subscriber creation
- import and export tools
- subscription confirmation and unsubscribe flow

### Analytics and Reporting

- sent and failed delivery history
- HTML open tracking
- link click tracking
- downloadable spreadsheet reports

## Quick Start

1. Install the application and complete the setup wizard.
2. Log in to the admin panel as the administrator.
3. Configure the delivery method: SMTP, `mail()`, or `sendmail`.
4. Create subscriber categories.
5. Add or import subscribers.
6. Create a template and define macros if needed.
7. Send a test email.
8. Create a schedule entry and configure cron.
9. Review logs and reports after the first live mailing.

## System Requirements

- PHP 8.4 or newer
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

`composer.lock` is included in version control to keep installations reproducible.
The web container synchronizes dependencies with this lock file at startup,
including when an existing `vendor` directory is present.

### Running tests

The test profile runs PHPUnit 12 with PHP 8.4 and a separate MySQL 8.4 database.
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
- check file encoding.

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
- `moderator` - access to categories, subscribers, and macros

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
- make sure the email address is not already in the database;
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
