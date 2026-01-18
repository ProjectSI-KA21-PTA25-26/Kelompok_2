<?php
// SMTP/email configuration for SSkIN
// This file provides defaults but environment variables take precedence.

// Load from .env if available
$enabled = getenv('SMTP_ENABLED');
$enabled = $enabled !== false ? filter_var($enabled, FILTER_VALIDATE_BOOLEAN) : false;

return [
    'enabled' => $enabled,
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
        'port' => getenv('SMTP_PORT') ? intval(getenv('SMTP_PORT')) : 587,
        'username' => getenv('SMTP_USERNAME') ?: '',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'secure' => getenv('SMTP_SECURE') ?: 'tls',
        'auth' => getenv('SMTP_AUTH') ? in_array(strtolower(getenv('SMTP_AUTH')), ['1','true','yes'], true) : true,
    ],
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'no-reply@sskin.com',
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'ShaSkin Beauty Care',
    'is_html' => getenv('SMTP_IS_HTML') ? in_array(strtolower(getenv('SMTP_IS_HTML')), ['1','true','yes'], true) : false,
];

