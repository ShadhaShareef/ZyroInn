<?php
// Production: Set ENCRYPTION_KEY via environment variable.
//   Apache: SetEnv ENCRYPTION_KEY "your-64-hex-char-key"
//   .env:   ENCRYPTION_KEY="your-64-hex-char-key"
//
// The key below is for local dev only — never commit the real key.
$envKey = getenv('ENCRYPTION_KEY') ?: 'change-this-to-a-random-64-hex-char-key-in-production';

return [
    'dev_mode'         => true,
    'app_url'          => 'http://localhost/ZyroInn',
    'app_name'         => 'ZyroInn',
    'session_lifetime' => 86400,
    'session_idle'     => 1800,

    'encryption_key'   => $envKey,
];
