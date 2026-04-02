<?php
/**
 * Workshop-Anmeldung Backend
 * Sendet Anmeldedaten per SMTP (adomail.de) an den Organisator
 * und optional eine Bestätigung an den Teilnehmenden.
 *
 * Endpoint: POST /api/register.php
 * Content-Type: application/json
 */

// ── Konfiguration ───────────────────────────────────────────
define('SMTP_HOST',     'mail.adomail.de');
define('SMTP_PORT',     587);
define('SMTP_USER',     'info@mavka-berlin.de');
define('SMTP_PASS',     getenv('SMTP_PASS') ?: '');
define('SMTP_FROM',     'info@mavka-berlin.de');
define('SMTP_FROM_NAME','Gemeinsam Kochen');
define('ORGANIZER_EMAIL','info@mavka-berlin.de');

// ── CORS & Security ────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
$allowedOrigins = ['https://mavka-berlin.de', 'https://www.mavka-berlin.de'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://mavka-berlin.de');
}
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

// ── Rate Limiting (einfach, Session-basiert) ────────────────
session_start();
$now = time();
$window = 300; // 5 Minuten
$maxRequests = 5;

if (!isset($_SESSION['reg_timestamps'])) {
    $_SESSION['reg_timestamps'] = [];
}

// Alte Einträge entfernen
$_SESSION['reg_timestamps'] = array_filter(
    $_SESSION['reg_timestamps'],
    fn($ts) => ($now - $ts) < $window
);

if (count($_SESSION['reg_timestamps']) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Zu viele Anmeldungen. Bitte versuchen Sie es in einigen Minuten erneut.']);
    exit;
}

// ── Input lesen & validieren ────────────────────────────────
$raw = file_get_contents('php://input', false, null, 0, 8192); // Max 8 KB
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige Anfrage']);
    exit;
}

// Pflichtfelder
$name     = trim($data['name'] ?? '');
$phone    = trim($data['phone'] ?? '');
$consent  = (bool)($data['consent'] ?? false);
$workshop = trim($data['workshop'] ?? '');

// Optionale Felder
$email         = trim($data['email'] ?? '');
$companion     = trim($data['companion'] ?? '');
$date          = trim($data['date'] ?? '');
$time          = trim($data['time'] ?? '');
$location      = trim($data['location'] ?? '');
$photo_consent = (bool)($data['photo_consent'] ?? false);

// Validierung
$errors = [];
if ($name === '')                               $errors[] = 'Name fehlt';
if (mb_strlen($name) > 100)                     $errors[] = 'Name zu lang (max. 100 Zeichen)';
if ($email === '')                               $errors[] = 'E-Mail-Adresse fehlt';
if (mb_strlen($email) > 254)                     $errors[] = 'E-Mail-Adresse zu lang';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'E-Mail-Adresse ungültig';
if (mb_strlen($phone) > 30)                      $errors[] = 'Telefonnummer zu lang';
if (mb_strlen($companion) > 100)                 $errors[] = 'Name der Begleitperson zu lang';
if (mb_strlen($workshop) > 200)                  $errors[] = 'Workshop-Name zu lang';
if (!$consent)                                   $errors[] = 'Datenschutz-Einwilligung fehlt';
if ($workshop === '')                            $errors[] = 'Workshop-Information fehlt';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// Sanitize für E-Mail-Inhalte
$name      = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone     = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email     = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$companion = htmlspecialchars($companion, ENT_QUOTES, 'UTF-8');
$workshop  = htmlspecialchars($workshop, ENT_QUOTES, 'UTF-8');
$date      = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
$time      = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
$location  = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');

// ── E-Mail an Organisator ───────────────────────────────────
$subject = "Neue Workshop-Anmeldung: {$workshop}";

$body  = "Neue Anmeldung über die Website\n";
$body .= "═══════════════════════════════════\n\n";
$body .= "Workshop:      {$workshop}\n";
$body .= "Datum:         {$date}\n";
$body .= "Uhrzeit:       {$time}\n";
$body .= "Ort:           {$location}\n\n";
$body .= "───────────────────────────────────\n";
$body .= "Teilnehmer*in\n";
$body .= "───────────────────────────────────\n";
$body .= "Name:          {$name}\n";
$body .= "E-Mail:        {$email}\n";
if ($phone !== '') {
    $body .= "Telefon:       {$phone}\n";
}
if ($companion !== '') {
    $body .= "Begleitperson: {$companion}\n";
}
$body .= "\n───────────────────────────────────\n";
$body .= "Einwilligung:  Ja (Datenschutzerklärung akzeptiert)\n";
$body .= "Fotoerlaubnis: " . ($photo_consent ? "Ja" : "Nein") . "\n";
$body .= "Zeitpunkt:     " . date('d.m.Y H:i') . " Uhr\n";
$body .= "IP:            " . ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt') . "\n";

$mailSent = sendMail(ORGANIZER_EMAIL, $subject, $body);

// ── Bestätigungs-E-Mail an Teilnehmer*in ──────────────────
$confirmSent = false;
if ($mailSent) {
    $confirmSubject = "Ihre Anmeldung: {$workshop}";

    $confirmBody  = "Hallo {$name},\n\n";
    $confirmBody .= "vielen Dank für Ihre Anmeldung!\n\n";
    $confirmBody .= "Workshop:  {$workshop}\n";
    $confirmBody .= "Datum:     {$date}\n";
    $confirmBody .= "Uhrzeit:   {$time}\n";
    $confirmBody .= "Ort:       {$location}\n";
    if ($companion !== '') {
        $confirmBody .= "Begleitperson: {$companion}\n";
    }
    $confirmBody .= "\nWir freuen uns auf Sie!\n\n";
    $confirmBody .= "Herzliche Grüße\n";
    $confirmBody .= "Ihr Team von Gemeinsam Kochen – Gemeinsam Wachsen\n";
    $confirmBody .= "Mavka.Berlin.Volunteers e.V. (i. Gr.)\n\n";
    $confirmBody .= "───────────────────────────────────\n";
    $confirmBody .= "Bei Fragen erreichen Sie uns unter:\n";
    $confirmBody .= "Telefon: +49 163 7038724\n";
    $confirmBody .= "E-Mail:  info@mavka-berlin.de\n";

    $confirmSent = sendMail($email, $confirmSubject, $confirmBody);
}

// Rate-Limit Timestamp setzen (nur bei Erfolg)
if ($mailSent) {
    $_SESSION['reg_timestamps'][] = $now;
}

// ── Response ────────────────────────────────────────────────
if ($mailSent) {
    echo json_encode([
        'ok' => true,
        'message' => 'Anmeldung erfolgreich',
        'confirmation' => $confirmSent
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Die Anmeldung konnte leider nicht versendet werden. Bitte versuchen Sie es telefonisch unter +49 163 7038724.'
    ]);
}

// ── SMTP Mail-Funktion ──────────────────────────────────────
function sendMail(string $to, string $subject, string $body): bool {
    // Versuche zuerst SMTP, Fallback auf mail()
    if (SMTP_PASS !== '') {
        return sendViaSMTP($to, $subject, $body);
    }
    return sendViaMailFunction($to, $subject, $body);
}

function sendViaMailFunction(string $to, string $subject, string $body): bool {
    $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: GemeinsamKochen/1.0\r\n";

    return mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);
}

function sendViaSMTP(string $to, string $subject, string $body): bool {
    $socket = @fsockopen('tls://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    if (!$socket) {
        error_log("SMTP connect failed: {$errstr} ({$errno})");
        return sendViaMailFunction($to, $subject, $body);
    }

    $response = '';
    $commands = [
        null, // Read greeting
        "EHLO " . gethostname(),
        "AUTH LOGIN",
        base64_encode(SMTP_USER),
        base64_encode(SMTP_PASS),
        "MAIL FROM:<" . SMTP_FROM . ">",
        "RCPT TO:<{$to}>",
        "DATA",
    ];

    foreach ($commands as $cmd) {
        if ($cmd !== null) {
            fwrite($socket, $cmd . "\r\n");
        }
        $response = fgets($socket, 512);
        if ($response === false || (int)$response >= 400) {
            error_log("SMTP error at '{$cmd}': {$response}");
            fclose($socket);
            return sendViaMailFunction($to, $subject, $body);
        }
        // Read multi-line responses (EHLO)
        while (isset($response[3]) && $response[3] === '-') {
            $response = fgets($socket, 512);
        }
    }

    // Send mail content
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $message  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $message .= "To: <{$to}>\r\n";
    $message .= "Subject: {$encodedSubject}\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "X-Mailer: GemeinsamKochen/1.0\r\n";
    $message .= "\r\n";
    $message .= $body;
    $message .= "\r\n.\r\n";

    fwrite($socket, $message);
    $response = fgets($socket, 512);

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return $response !== false && (int)$response < 400;
}
