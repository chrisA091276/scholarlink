<?php

return [
    'show_demo_credentials' => env('SHOW_DEMO_CREDENTIALS', false),
    'verify_email' => env('REQUIRE_EMAIL_VERIFICATION', false),
    'email_notifications' => env('EMAIL_NOTIFICATIONS', false),
    'document_storage' => env('DOCUMENT_STORAGE', 'local'),
    'max_upload_kb' => (int) env('MAX_UPLOAD_KB', 5120),
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'bucket' => env('SUPABASE_STORAGE_BUCKET', 'scholarlink-documents'),
    ],
];
