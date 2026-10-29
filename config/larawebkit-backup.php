<?php

return [
    // Archives are written to this (local, private) disk and directory.
    'disk' => 'local',
    'directory' => 'backups',

    // Files that go into the archive, per disk; paths starting with an excluded prefix are left out.
    'include_disks' => ['public', 'local'],
    'exclude' => ['backups', 'livewire-tmp', 'framework'],

    // Optional copy to another disk (see config/filesystems.php: backup-ftp, backup-sftp, backup-s3).
    'remote_disk' => env('BACKUP_REMOTE_DISK'),
    'remote_directory' => env('BACKUP_REMOTE_DIRECTORY', 'larawebkit-backups'),

    // Optional AES-256 password for the zip entries (recommended with a remote copy).
    'password' => env('BACKUP_PASSWORD'),

    // Rows per INSERT statement in the MySQL / MariaDB dump.
    'dump_chunk' => 500,
];
