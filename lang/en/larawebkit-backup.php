<?php

return [
    'title' => 'Backups',
    'now' => 'Back up now',
    'queued' => 'Backup started',
    'queued_body' => 'It runs in the background (cron) and appears here in a minute or two.',
    'download' => 'Download',
    'missing' => 'That backup no longer exists.',
    'delete_confirm' => 'Delete :name? This cannot be undone.',
    'deleted' => 'Backup deleted',
    'status' => 'Status',
    'last_ok' => 'Last backup :date.',
    'last_failed' => 'The last backup failed (:date):',
    'remote_copy' => 'copied to :target',
    'never' => 'No backup has run yet.',
    'schedule_hint' => 'Schedule: :frequency, keeping the newest :keep (Settings → Backups). Archives hold every record and file: keep downloaded copies somewhere safe.',
    'archives' => 'Archives',
    'empty' => 'No backups yet.',
    'file' => 'File',
    'size' => 'Size',
    'date' => 'Date',
    'restore_hint' => 'Restoring is described in docs/backups.md. Keep your .env (APP_KEY) as well: encrypted fields cannot be read without it.',
];
