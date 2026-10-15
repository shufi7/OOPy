<?php

return [
    // Only the private grader service receives this token. Never embed it in Blade.
    'grader_url' => env('CODING_GRADER_URL'),
    'grader_token' => env('CODING_GRADER_TOKEN'),
    'grader_timeout' => 15,
];
