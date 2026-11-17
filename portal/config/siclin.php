<?php

return [
    'solver_seconds' => (int) env('OPTIMIZER_SECONDS', 10),
    'shift_search_nodes' => (int) env('SHIFT_SEARCH_NODES', 100000),
    'otp_secret' => env('OTP_SECRET'),
    'otp_ttl' => (int) env('OTP_TTL_SECONDS', 600),
    'otp_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'otp_cooldown' => (int) env('OTP_COOLDOWN_SECONDS', 60),
    'otp_account_requests' => (int) env('OTP_ACCOUNT_REQUESTS', 3),
    'otp_ip_requests' => (int) env('OTP_IP_REQUESTS', 20),
    'otp_window' => (int) env('OTP_WINDOW_SECONDS', 900),
    'otp_grant_ttl' => (int) env('OTP_GRANT_SECONDS', 300),
    'mail_mode' => env('MAIL_MODE', 'local'),
    'mail_queue' => 'mail-'.env('MAIL_MODE', 'local'),
    'allowed_domains' => array_filter(array_map('trim', explode(',', strtolower(env('ALLOWED_EMAIL_DOMAINS', ''))))),
    'mail_recipients' => array_filter(array_map('trim', explode(',', strtolower(env('SMTP_ALLOWED_RECIPIENTS', '').','.env('TEST_RECIPIENT_EMAIL', ''))))),
];
