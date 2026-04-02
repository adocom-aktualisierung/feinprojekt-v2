<?php
/**
 * Newsletter-Anmeldung Backend
 * Leitet E-Mail-Adressen an Hostinger Reach weiter (Public API).
 *
 * Endpoint: POST /api/newsletter.php
 * Content-Type: application/json
 * Body: { "email": "...", "consent": true }
 */

// ── Konfiguration (aus Umgebungsvariablen) ──────────────────
define('REACH_API_TOKEN',    getenv('REACH_API_TOKEN')    ?: '');
define('REACH_PROFILE_UUID', getenv('REACH_PROFILE_UUID') ?: '');
define('REACH_API_BASE',     'https://developers.hostinger.com/api/reach/v1');

// ── CORS & Security ────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Nur POST erlaubt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

// ── Konfiguration prüfen ───────────────────────────────────
if (REACH_API_TOKEN === '' || REACH_PROFILE_UUID === '') {
    error_log('Newsletter: REACH_API_TOKEN oder REACH_PROFILE_UUID nicht konfiguriert');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Newsletter-Dienst ist noch nicht eingerichtet. Bitte versuchen Sie es später erneut.']);
    exit;
}

// ── Rate Limiting ──────────────────────────────────────────
session_start();
$now = time();
$window = 300; // 5 Minuten
$maxRequests = 3;

if (!isset($_SESSION['nl_timestamps'])) {
    $_SESSION['nl_timestamps'] = [];
}

$_SESSION['nl_timestamps'] = array_filter(
    $_SESSION['nl_timestamps'],
    fn($ts) => ($now - $ts) < $window
);

if (count($_SESSION['nl_timestamps']) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Zu viele Anmeldungen. Bitte versuchen Sie es in einigen Minuten erneut.']);
    exit;
}

// ── Input lesen & validieren ────────────────────────────────
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige Anfrage']);
    exit;
}

$email   = trim($data['email'] ?? '');
$consent = (bool)($data['consent'] ?? false);

$errors = [];
if ($email === '')                              $errors[] = 'E-Mail-Adresse fehlt';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-Mail-Adresse ungültig';
if (!$consent)                                  $errors[] = 'Datenschutz-Einwilligung fehlt';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// ── Hostinger Reach API aufrufen ────────────────────────────
$url = REACH_API_BASE . '/profiles/' . REACH_PROFILE_UUID . '/contacts';

$payload = json_encode([
    'email' => $email,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . REACH_API_TOKEN,
    ],
]);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

// ── Antwort auswerten ──────────────────────────────────────
if ($curlError !== '') {
    error_log("Newsletter Reach API cURL error: {$curlError}");
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Der Newsletter-Dienst ist vorübergehend nicht erreichbar. Bitte versuchen Sie es später erneut.']);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    // Erfolg — Rate-Limit Timestamp setzen
    $_SESSION['nl_timestamps'][] = $now;
    echo json_encode(['ok' => true, 'message' => 'Anmeldung erfolgreich']);
} else {
    // Reach API hat einen Fehler zurückgegeben
    $body = json_decode($response, true);
    $reachError = $body['message'] ?? $body['error'] ?? 'Unbekannter Fehler';
    error_log("Newsletter Reach API error (HTTP {$httpCode}): {$reachError} — Response: {$response}");

    // Benutzerfreundliche Fehlermeldungen
    if ($httpCode === 409 || stripos($reachError, 'already exists') !== false) {
        echo json_encode(['ok' => true, 'message' => 'Sie sind bereits für den Newsletter angemeldet.']);
    } elseif ($httpCode === 422) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Die E-Mail-Adresse konnte nicht verarbeitet werden. Bitte prüfen Sie Ihre Eingabe.']);
    } else {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'Die Anmeldung konnte leider nicht durchgeführt werden. Bitte versuchen Sie es später erneut.']);
    }
}
