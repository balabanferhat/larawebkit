<?php

return [

    // Uploaded files are private: stored on this disk, never under public/.
    'disk' => 'local',
    'directory' => 'form-uploads',

    // Upper limit for any file field (kilobytes); each field can set a lower one.
    'max_file_kb' => (int) env('FORMS_MAX_FILE_KB', 10240),

    // Lead magnet files attached to a form or the newsletter (Appearance-independent, private).
    'downloads_directory' => 'downloads',

];
