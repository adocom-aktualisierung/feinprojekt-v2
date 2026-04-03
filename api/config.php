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
// Auto-Deploy löscht das gesamte Repo-Verzeichnis. config.local.php
// muss daher AUSSERHALB des Repos liegen (z.B. Home-Verzeichnis).
$searchPaths = [
    __DIR__ . '/config.local.php',             // Neben config.php
    dirname(__DIR__) . '/config.local.php',     // dist/
    dirname(__DIR__, 2) . '/config.local.php',  // Projekt-Root
    dirname(__DIR__, 3) . '/config.local.php',  // Über Projekt-Root
    ($_SERVER['HOME'] ?? ($_SERVER['DOCUMENT_ROOT'] ? dirname($_SERVER['DOCUMENT_ROOT']) : '')) . '/config.local.php',
];
$configLoaded = false;
foreach ($searchPaths as $path) {
    if ($path && file_exists($path)) {
        require_once $path;
        $configLoaded = true;
        break;
    }
}
// Debug-Hilfe: zeigt wo gesucht wurde (nur im Error-Log)
if (!$configLoaded) {
    error_log('config.local.php NOT FOUND. Searched: ' . implode(', ', array_filter($searchPaths)));
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
if (!defined('SMTP_PORT'))       define('SMTP_PORT',       465);
if (!defined('SMTP_USER'))       define('SMTP_USER',       'info@mavka-berlin.de');
if (!defined('SMTP_PASS'))       define('SMTP_PASS',       env('SMTP_PASS'));
if (!defined('SMTP_FROM'))       define('SMTP_FROM',       'info@mavka-berlin.de');
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME',  'Gemeinsam Kochen');
if (!defined('ORGANIZER_EMAIL')) define('ORGANIZER_EMAIL', 'info@mavka-berlin.de');

// ── Hostinger Reach (für newsletter.php) ────────────────────
if (!defined('REACH_API_TOKEN'))    define('REACH_API_TOKEN',    env('REACH_API_TOKEN'));
if (!defined('REACH_PROFILE_UUID')) define('REACH_PROFILE_UUID', env('REACH_PROFILE_UUID'));
if (!defined('REACH_API_BASE'))     define('REACH_API_BASE',     'https://developers.hostinger.com/api/reach/v1');

// ── SQLite (für Double-Opt-in) ──────────────────────────────
if (!defined('DB_PATH'))   define('DB_PATH',   dirname(__DIR__) . '/.data/newsletter.sqlite');
if (!defined('SITE_URL'))  define('SITE_URL',  'https://mavka-berlin.de');

/**
 * Gibt eine PDO-Verbindung zur Newsletter-SQLite-Datenbank zurück.
 * Erstellt Tabelle automatisch beim ersten Zugriff.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA busy_timeout=5000');

    $pdo->exec('CREATE TABLE IF NOT EXISTS newsletter_pending (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        email      TEXT    NOT NULL,
        token      TEXT    NOT NULL UNIQUE,
        created_at INTEGER NOT NULL,
        verified_at INTEGER DEFAULT NULL,
        ip_address TEXT    DEFAULT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_nl_token ON newsletter_pending(token)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_nl_email ON newsletter_pending(email)');

    return $pdo;
}

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
