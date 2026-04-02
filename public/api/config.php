<?php
/**
 * Zentrale Konfiguration für alle API-Endpoints.
 *
 * Credentials werden geladen aus (Priorität):
 *   1. config.local.php  — Datei nur auf dem Server, nicht im Git
 *   2. Umgebungsvariablen — getenv() / $_ENV / $_SERVER
 *
 * Eingebunden via: require_once __DIR__ . '/config.php';
 */

// ── Lokale Credentials laden (falls vorhanden) ─────────────
// config.local.php liegt NUR auf dem Server und definiert die
// Secrets per define(). Wird von .gitignore ausgeschlossen.
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

/**
 * Liest eine Umgebungsvariable aus allen verfügbaren Quellen.
 */
function env(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    return $default;
}

// ── SMTP (für register.php + contact.php) ───────────────────
// config.local.php kann diese bereits definiert haben → nur setzen wenn noch nicht definiert
if (!defined('SMTP_HOST'))       define('SMTP_HOST',       'mail.adomail.de');
if (!defined('SMTP_PORT'))       define('SMTP_PORT',       587);
if (!defined('SMTP_USER'))       define('SMTP_USER',       'info@mavka-berlin.de');
if (!defined('SMTP_PASS'))       define('SMTP_PASS',       env('SMTP_PASS'));
if (!defined('SMTP_FROM'))       define('SMTP_FROM',       'info@mavka-berlin.de');
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME',  'Gemeinsam Kochen');
if (!defined('ORGANIZER_EMAIL')) define('ORGANIZER_EMAIL', 'info@mavka-berlin.de');

// ── Hostinger Reach (für newsletter.php) ────────────────────
if (!defined('REACH_API_TOKEN'))    define('REACH_API_TOKEN',    env('REACH_API_TOKEN'));
if (!defined('REACH_PROFILE_UUID')) define('REACH_PROFILE_UUID', env('REACH_PROFILE_UUID'));
if (!defined('REACH_API_BASE'))     define('REACH_API_BASE',     'https://developers.hostinger.com/api/reach/v1');

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
