# Backups and restore

## What a backup contains

`php artisan larawebkit:backup` writes one zip to `storage/app/private/backups/` (never web-accessible):

```
site-2026-10-29-030000.zip
├── database.sqlite   or   database.sql      SQLite copy, or a plain SQL dump for MySQL / MariaDB
├── files/public/...                         storage/app/public: media library (WebP variants)
├── files/local/...                          storage/app/private: original uploads, form files, downloads
└── manifest.json                            date, URL, database driver, file count
```

The SQL dump is written in PHP, so it works on hosts without `mysqldump`. It is **not** in the archive:
`.env`. Keep a copy of it (above all `APP_KEY`) in a password manager. Encrypted fields (2FA secrets, event
links, AI keys entered in the admin) cannot be read without the same `APP_KEY`.

Options: `--no-files` (database only), `--no-upload` (skip the remote copy), `--list`.

## Schedule and retention

**Settings → Backups**: off / daily / weekly (Sunday), the time (server time, usually UTC) and how many
archives to keep (default 7). The existing cron entry (`schedule:run` every minute) runs it, nothing else to
set up. `larawebkit:deploy-check` warns when the last successful backup is older than expected.

**System → Backups** (super-admins only) lists the archives with their size, downloads or deletes them and
starts a backup in the background ("Back up now" runs on the queue, so a large site does not hit the web
timeout).

## Copy to another server

A backup on the same server does not help when the server is lost. Set a remote disk in `.env`:

| Target | `.env` | Package |
|---|---|---|
| FTP / FTPS | `BACKUP_REMOTE_DISK=backup-ftp`, `BACKUP_FTP_HOST`, `_USERNAME`, `_PASSWORD`, `_ROOT` | `composer require league/flysystem-ftp` |
| SFTP | `BACKUP_REMOTE_DISK=backup-sftp`, `BACKUP_SFTP_HOST`, `_USERNAME`, `_PASSWORD` or `_PRIVATE_KEY` | `composer require league/flysystem-sftp-v3` |
| S3-compatible (AWS, Cloudflare R2, Backblaze B2, Wasabi...) | `BACKUP_REMOTE_DISK=backup-s3`, `BACKUP_S3_KEY`, `_SECRET`, `_BUCKET`, `_ENDPOINT`, `_REGION` | `composer require league/flysystem-aws-s3-v3` |

Archives go to `BACKUP_REMOTE_DIRECTORY` (default `larawebkit-backups`); the same retention applies there. If
the copy fails, the local backup is still kept and the error shows on System → Backups and in the log.

Set `BACKUP_PASSWORD` to encrypt the zip entries with AES-256 (recommended for remote copies). Open them
with 7-Zip, Keka, `7z x` or any unzip tool that supports AES; `unzip` from Info-ZIP does not.

## Restore

Test this once on a copy before you need it.

1. Put the code in place (git clone or upload) and run `composer install --no-dev`, then restore `.env`
   with the **same `APP_KEY`**.
2. Unzip the archive somewhere outside the web root: `unzip site-2026-10-29-030000.zip -d /tmp/restore`.
3. Database:
   - **SQLite**: `cp /tmp/restore/database.sqlite database/database.sqlite`
   - **MySQL / MariaDB**: create an empty database (cPanel → MySQL Databases), then
     `mysql -u USER -p DATABASE < /tmp/restore/database.sql` or import the file in phpMyAdmin (Import tab;
     split it or ask the host to raise the upload limit if it is large).
4. Files: copy `files/public/` to `storage/app/public/` and `files/local/` to `storage/app/private/`.
5. Run `php artisan migrate --force` (in case the code is newer than the backup), `php artisan storage:link`,
   `php artisan larawebkit:themes:publish` and `php artisan optimize:clear`.
6. Check with `php artisan larawebkit:deploy-check` and log in to the admin.
