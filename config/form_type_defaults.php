<?php

/**
 * Type-specific default fields for form views
 * 
 * These defaults are used when no user preference exists.
 * They provide optimal field visibility for each form type.
 */
return [
    'registration' => [
        'list_view' => ['fname', 'lname', 'email', 'message_long'],
        'detail_view' => ['fname', 'lname', 'email', 'message_long', 'phone'],
    ],
    'survey' => [
        'list_view' => ['name', 'email', 'dob', 'city'],
        'detail_view' => ['name', 'email', 'dob', 'city', 'country', 'age'],
    ],
    'contest' => [
        'list_view' => ['name', 'email', 'age', 'city'],
        'detail_view' => ['name', 'email', 'age', 'city', 'phone'],
    ],
    // Common defaults (fallback for unknown types)
    'default' => [
        'list_view' => ['fname', 'lname', 'email', 'message_long'],
        'detail_view' => ['fname', 'lname', 'email', 'message_long'],
    ],
];

