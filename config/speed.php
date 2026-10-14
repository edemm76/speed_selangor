<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Speed Selangor Management System (SSMS) settings
    |--------------------------------------------------------------------------
    */

    // System name shown in the navbar, page titles and PDF reports
    'name' => 'Speed Selangor',
    'full_name' => 'Sistem Pengurusan Speed Selangor',
    'council' => env('SPEED_COUNCIL', 'Majlis Perbandaran Kuala Selangor'),

    // Speed Selangor processing target (days). Applications older than this
    // (since submission) and not yet finished are counted as "overdue".
    'target_days' => (int) env('SPEED_TARGET_DAYS', 14),

    // Prefix for generated reference numbers, e.g. SS-2026-0001
    'reference_prefix' => 'SS',

    // Uploaded documents
    'upload' => [
        'disk' => 'public',
        'max_kb' => 10240, // 10 MB per file
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

];