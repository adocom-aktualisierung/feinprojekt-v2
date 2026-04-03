<?php
/**
 * Kontaktformular Backend
 * Sendet Kontaktanfragen per SMTP (adomail.de) an den Organisator.
 *
 * Endpoint: POST /api/contact.php
 * Content-Type: application/json
 * Body: { "name": "...", "email": "...", "subject": "...", "message": "...", "consent": true }
 */

require_once __DIR__ . '/config.php';

// ── CORS & Security ────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();

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

// ── Rate Limiting ──────────────────────────────────────────
session_start();
$now = time();
$window = 300; // 5 Minuten
$maxRequests = 3;

if (!isset($_SESSION['contact_timestamps'])) {
    $_SESSION['contact_timestamps'] = [];
}

$_SESSION['contact_timestamps'] = array_filter(
    $_SESSION['contact_timestamps'],
    fn($ts) => ($now - $ts) < $window
);

if (count($_SESSION['contact_timestamps']) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Zu viele Anfragen. Bitte versuchen Sie es in einigen Minuten erneut.']);
    exit;
}

// ── Input lesen & validieren ────────────────────────────────
$raw = file_get_contents('php://input', false, null, 0, 16384); // Max 16 KB
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige Anfrage']);
    exit;
}

$name    = trim($data['name'] ?? '');
$email   = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');
$consent = (bool)($data['consent'] ?? false);

// Validierung
$errors = [];
if ($name === '')                                $errors[] = 'Name fehlt';
if (mb_strlen($name) > 100)                      $errors[] = 'Name zu lang (max. 100 Zeichen)';
if ($email === '')                                $errors[] = 'E-Mail-Adresse fehlt';
if (mb_strlen($email) > 254)                      $errors[] = 'E-Mail-Adresse zu lang';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'E-Mail-Adresse ungültig';
if (mb_strlen($subject) > 200)                    $errors[] = 'Betreff zu lang (max. 200 Zeichen)';
if ($message === '')                              $errors[] = 'Nachricht fehlt';
if (mb_strlen($message) > 5000)                   $errors[] = 'Nachricht zu lang (max. 5000 Zeichen)';
if (!$consent)                                    $errors[] = 'Datenschutz-Einwilligung fehlt';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// Sanitize für E-Mail-Inhalte
$name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// ── E-Mail an Organisator ───────────────────────────────────
$mailSubject = $subject !== '' ? "Kontaktanfrage: {$subject}" : 'Neue Kontaktanfrage über die Website';

$body  = "Neue Kontaktanfrage über die Website\n";
$body .= "═══════════════════════════════════\n\n";
$body .= "Von:       {$name}\n";
$body .= "E-Mail:    {$email}\n";
if ($subject !== '') {
    $body .= "Betreff:   {$subject}\n";
}
$body .= "\n───────────────────────────────────\n";
$body .= "Nachricht\n";
$body .= "───────────────────────────────────\n\n";
$body .= html_entity_decode($message, ENT_QUOTES, 'UTF-8') . "\n\n";
$body .= "───────────────────────────────────\n";
$body .= "Einwilligung:  Ja (Datenschutzerklärung akzeptiert)\n";
$body .= "Zeitpunkt:     " . date('d.m.Y H:i') . " Uhr\n";
$body .= "IP:            " . ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt') . "\n";

$mailError = '';
$mailSent = sendMail(ORGANIZER_EMAIL, $mailSubject, $body, $email, $mailError);

// Rate-Limit Timestamp setzen (nur bei Erfolg)
if ($mailSent) {
    $_SESSION['contact_timestamps'][] = $now;
}

// ── Response ────────────────────────────────────────────────
if ($mailSent) {
    echo json_encode(['ok' => true, 'message' => 'Nachricht erfolgreich gesendet']);
} else {
    error_log("Contact form mail failed: {$mailError}");
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Die Nachricht konnte leider nicht gesendet werden. Bitte versuchen Sie es telefonisch unter +49 163 7038724.'
    ]);
}

// ── Mail-Funktionen (identisch mit register.php) ────────────
function sendMail(string $to, string $subject, string $body, string $replyTo = '', string &$error = ''): bool {
    if (SMTP_PASS !== '') {
        return sendViaSMTP($to, $subject, $body, $replyTo, $error);
    }
    $error = 'SMTP_PASS is empty, mail() fallback';
    return sendViaMailFunction($to, $subject, $body, $replyTo, $error);
}

function sendViaMailFunction(string $to, string $subject, string $body, string $replyTo = '', string &$error = ''): bool {
    $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . ($replyTo ?: SMTP_FROM) . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: GemeinsamKochen/1.0\r\n";

    $result = @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);
    if (!$result) {
        $error .= ' | mail() failed';
    }
    return $result;
}

function sendViaSMTP(string $to, string $subject, string $body, string $replyTo = '', string &$error = ''): bool {
    // Connect plain on port 587, then upgrade via STARTTLS
    $ctx = stream_context_create(['ssl' => [
        'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
    ]]);
    $socket = @stream_socket_client('tcp://' . SMTP_HOST . ':587', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$socket) {
        $error = "SMTP connect " . SMTP_HOST . ":587 failed: {$errstr} ({$errno})";
        error_log($error);
        return sendViaMailFunction($to, $subject, $body, $replyTo, $error);
    }

    // Helper: read response, consume multi-line
    $readResp = function() use ($socket): string {
        $resp = '';
        do {
            $line = fgets($socket, 512);
            if ($line === false) return '';
            $resp .= $line;
        } while (isset($line[3]) && $line[3] === '-');
        return $resp;
    };

    // Greeting
    $resp = $readResp();
    if ((int)$resp >= 400) { $error = "SMTP greeting: " . trim($resp); fclose($socket); return sendViaMailFunction($to, $subject, $body, $replyTo, $error); }

    // EHLO
    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    $resp = $readResp();
    if ((int)$resp >= 400) { $error = "SMTP EHLO: " . trim($resp); fclose($socket); return sendViaMailFunction($to, $subject, $body, $replyTo, $error); }

    // STARTTLS
    fwrite($socket, "STARTTLS\r\n");
    $resp = $readResp();
    if ((int)$resp >= 400) { $error = "SMTP STARTTLS: " . trim($resp); fclose($socket); return sendViaMailFunction($to, $subject, $body, $replyTo, $error); }

    // Upgrade to TLS
    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if (!$crypto) {
        $error = "STARTTLS crypto upgrade failed";
        error_log($error);
        fclose($socket);
        return sendViaMailFunction($to, $subject, $body, $replyTo, $error);
    }

    // EHLO again after STARTTLS
    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    $resp = $readResp();

    // AUTH LOGIN
    $commands = [
        "AUTH LOGIN",
        base64_encode(SMTP_USER),
        base64_encode(SMTP_PASS),
        "MAIL FROM:<" . SMTP_FROM . ">",
        "RCPT TO:<{$to}>",
        "DATA",
    ];

    foreach ($commands as $cmd) {
        fwrite($socket, $cmd . "\r\n");
        $resp = $readResp();
        if ((int)$resp >= 400) {
            $error = "SMTP error at '{$cmd}': " . trim($resp);
            error_log($error);
            fclose($socket);
            return false; // Don't fallback to mail() after auth failure
        }
    }

    // Send mail content
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $msg  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $msg .= "To: <{$to}>\r\n";
    $msg .= "Reply-To: " . ($replyTo ?: SMTP_FROM) . "\r\n";
    $msg .= "Subject: {$encodedSubject}\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "X-Mailer: GemeinsamKochen/1.0\r\n";
    $msg .= "\r\n";
    $msg .= $body;
    $msg .= "\r\n.\r\n";

    fwrite($socket, $msg);
    $resp = $readResp();

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    if ($resp === '' || (int)$resp >= 400) {
        $error = "SMTP DATA response: " . trim($resp ?: 'no response');
        return false;
    }
    return true;
}
