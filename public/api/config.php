<?php
/**
 * Zentrale Konfiguration für alle API-Endpoints.
 * Liest Credentials aus Umgebungsvariablen (hPanel, .htaccess, $_ENV, $_SERVER).
 *
 * Eingebunden via: require_once __DIR__ . '/config.php';
 */

/**
 * Liest eine Umgebungsvariable aus allen verfügbaren Quellen.
 * Hostinger hPanel setzt Vars je nach PHP-FPM-Konfiguration
 * in unterschiedlichen Superglobals.
 */
function env(string $key, string $default = ''): string {
    // 1. getenv() — Standard-PHP
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;

    // 2. $_ENV — PHP Superglobal
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];

    // 3. $_SERVER — FastCGI/FPM schreibt oft hierhin
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];

    return $default;
}

// ── SMTP (für register.php + contact.php) ───────────────────
define('SMTP_HOST',       'mail.adomail.de');
define('SMTP_PORT',       587);
define('SMTP_USER',       'info@mavka-berlin.de');
define('SMTP_PASS',       env('SMTP_PASS'));
define('SMTP_FROM',       'info@mavka-berlin.de');
define('SMTP_FROM_NAME',  'Gemeinsam Kochen');
define('ORGANIZER_EMAIL', 'info@mavka-berlin.de');

// ── Hostinger Reach (für newsletter.php) ────────────────────
define('REACH_API_TOKEN',    env('REACH_API_TOKEN'));
define('REACH_PROFILE_UUID', env('REACH_PROFILE_UUID'));
define('REACH_API_BASE',     'https://developers.hostinger.com/api/reach/v1');

// ── CORS ────────────────────────────────────────────────────
define('ALLOWED_ORIGINS', ['https://mavka-berlin.de', 'https://www.mavka-berlin.de']);

/**
 * Setzt CORS-Header für die erlaubten Origins.
 */
function setCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, ALLOWED_ORIGINS, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: https://mavka-berlin.de');
    }
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
