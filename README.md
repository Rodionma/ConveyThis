# File Storage — temporary PDF/DOCX storage

A Laravel 12 (PHP 8.2+) web app for storing **PDF** and **DOCX** files for a limited time.

- Asynchronous drag & drop uploader (jQuery AJAX + progress bars, several files at once)
- 10 MB per-file limit, checked in the browser and on the server (the real MIME type is checked too, not just the extension)
- File metadata is stored in MySQL
- A separate management page to list, download and delete files
- Files are **deleted automatically 24 hours** after upload
- Every deletion, **manual or automatic**, publishes a message to **RabbitMQ**. A queue worker consumes it and sends an e-mail to `FILES_NOTIFICATION_EMAIL`
- Docker Compose setup: nginx, php-fpm, queue worker, scheduler, MySQL, RabbitMQ and Mailpit

**Stack:** Laravel 12 · PHP 8.3 (Docker) · MySQL 8.4 · RabbitMQ 3.13 · Bootstrap 5 · jQuery 3.7

---

## Quick start (Docker)

Requirements: Docker + Docker Compose v2.

```bash
git clone <repo-url> file-storage && cd file-storage
cp .env.example .env
docker compose up -d --build
```

On first start the `app` container runs `composer install`, generates `APP_KEY` and runs the migrations. This takes about a minute. Then open:

| Service                    | URL                                              |
|----------------------------|--------------------------------------------------|
| Application (upload page)  | http://localhost:8080                            |
| File management page       | http://localhost:8080/files                      |
| RabbitMQ management UI     | http://localhost:15672 (guest / guest)           |
| Mailpit (sent e-mails)     | http://localhost:8025                            |

Containers:

| Container   | Purpose                                                                              |
|-------------|--------------------------------------------------------------------------------------|
| `app`       | php-fpm; also runs setup (composer, key, migrations) on start                        |
| `nginx`     | web server                                                                           |
| `worker`    | `php artisan queue:work rabbitmq --queue=file-notifications`: consumes notifications and sends the mail |
| `scheduler` | `php artisan schedule:work`: runs `files:purge-expired` every minute                 |
| `mysql`     | database                                                                             |
| `rabbitmq`  | message broker (with management plugin)                                              |
| `mailpit`   | SMTP catcher, so you can see the notification e-mails (optional)                     |

Useful commands:

```bash
docker compose logs -f worker scheduler          # watch notifications / purge runs
docker compose exec app php artisan test         # run the test suite
docker compose exec app php artisan files:purge-expired   # purge manually
docker compose down        # stop (add -v to also drop the MySQL volume)
```

If a host port is already in use, override it in `.env` or the shell:
`APP_PORT`, `FORWARD_DB_PORT` (33060), `FORWARD_RABBITMQ_PORT` (5672), `FORWARD_RABBITMQ_UI_PORT` (15672), `FORWARD_MAILPIT_UI_PORT` (8025).
For example: `FORWARD_RABBITMQ_PORT=5673 FORWARD_RABBITMQ_UI_PORT=15673 docker compose up -d`.

On Linux, pass your user id so files created in the bind mount belong to you:
`HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose up -d --build`.

## Running without Docker

Requirements: PHP 8.2+ (ext: pdo_mysql, sockets, zip, fileinfo, mbstring), Composer, MySQL and RabbitMQ.

```bash
composer install
cp .env.example .env          # set DB_* / RABBITMQ_* to your servers
php artisan key:generate
php artisan migrate

php artisan serve                                                     # http://127.0.0.1:8000
php artisan queue:work rabbitmq --queue=file-notifications            # separate terminal
php artisan schedule:work                                             # separate terminal
```

Without an SMTP server, set `MAIL_MAILER=log`. The e-mails are then written to `storage/logs/laravel.log`.

## Configuration (`.env`)

| Variable                        | Default              | Description                                          |
|---------------------------------|----------------------|------------------------------------------------------|
| `FILES_NOTIFICATION_EMAIL`      | `admin@example.com`  | Recipient of the deletion notifications              |
| `FILES_TTL_MINUTES`             | `1440`               | Retention period in minutes (1440 = 24 h; e.g. `10` for testing) |
| `FILES_MAX_SIZE_KB`             | `10240`              | Max upload size (10 MB)                              |
| `FILES_NOTIFICATION_QUEUE`      | `file-notifications` | RabbitMQ queue name                                  |
| `RABBITMQ_HOST/PORT/USER/PASSWORD/VHOST` |             | RabbitMQ connection                                  |
| `MAIL_MAILER`                   | `smtp` (Mailpit)     | `log` writes the e-mails to the log instead          |

## How it works

```
Browser ──AJAX──► FileController ──► FileStorageService ──► storage/app/private/uploads + MySQL (stored_files)
                                            │
   manual DELETE /files/{id} ───────────────┤
   scheduler: files:purge-expired (every minute, expires_at <= now) ──┘
                                            │ delete()
                                            ▼
                      SendFileDeletedNotification job ──► RabbitMQ queue "file-notifications"
                                                               │
                                     queue worker ◄────────────┘
                                            │
                                            ▼
                         Mail::to(FILES_NOTIFICATION_EMAIL) → FileDeletedMail
```

- `FileStorageService::delete()` is the single deletion path for both manual and automatic deletes. It deletes the DB row and publishes the message to RabbitMQ **inside one DB transaction**. If RabbitMQ is unavailable, the row is restored, so no deletion goes unnotified, and the scheduler retries expired files on the next run. The physical file is removed after the commit.
- The message carries the file data (name, size, upload/delete time, reason `manual`/`expired`), so the worker doesn't need the deleted DB row.
- Upload validation (`App\Http\Requests\UploadFileRequest` + `App\Rules\AllowedDocument`) checks the size, the extension and the real MIME type of the content. DOCX files that libmagic reports as plain ZIP are recognised by their internal structure.
- PHP/nginx limits (`upload_max_filesize=12M`, `client_max_body_size 13M`) are set slightly above the app limit, so users see a friendly validation message instead of a server error.

### Routes

| Method | URI                     | Description                       |
|--------|-------------------------|-----------------------------------|
| GET    | `/`                     | Upload page                       |
| GET    | `/files`                | Management page                   |
| GET    | `/files/list`           | JSON list of active files         |
| POST   | `/files`                | Upload (multipart, field `file`)  |
| GET    | `/files/{uuid}/download`| Download                          |
| DELETE | `/files/{uuid}`         | Delete (+ RabbitMQ notification)  |

### Key files

```
app/Http/Controllers/FileController.php
app/Http/Requests/UploadFileRequest.php
app/Rules/AllowedDocument.php
app/Services/FileStorageService.php
app/Jobs/SendFileDeletedNotification.php
app/Mail/FileDeletedMail.php
app/Console/Commands/PurgeExpiredFiles.php
routes/console.php                  # schedule
config/files.php, config/queue.php  # 'rabbitmq' connection
resources/views/files/*.blade.php
public/js/{common,upload,files}.js
docker-compose.yml, Dockerfile, docker/
```

## Tests

```bash
php artisan test     # or: docker compose exec app php artisan test
```

The tests cover uploads (PDF/DOCX, wrong types, fake extensions, size limit), the list, downloads, manual deletion, the expiry purge (including the 24 h boundary), the schedule registration, and the notification job and mail.
