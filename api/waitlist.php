<?php
/**
 * Workshop-Warteliste Backend
 * Nimmt Warteliste-Einträge entgegen und sendet sie per SMTP an den Organisator.
 * Der Teilnehmer:in erhält eine kurze Bestätigung.
 *
 * Endpoint: POST /api/waitlist.php
 * Content-Type: application/json
 *
 * Felder: name (required), email (required), workshop (required slug),
 *         phone (optional), consent (required bool).
 *
 * Hinweis: Die SMTP-/Mail-Hilfsfunktionen duplizieren register.php —
 * Refactor in gemeinsames api/_mail.php ist P3 Tech-Debt.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

// ── Rate Limiting ────────────────────────────────────────────
session_start();
$now = time();
$window = 300;
$maxRequests = 5;

if (!isset($_SESSION['waitlist_timestamps'])) {
    $_SESSION['waitlist_timestamps'] = [];
}
$_SESSION['waitlist_timestamps'] = array_filter(
    $_SESSION['waitlist_timestamps'],
    fn($ts) => ($now - $ts) < $window
);
if (count($_SESSION['waitlist_timestamps']) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Zu viele Anfragen. Bitte versuchen Sie es in einigen Minuten erneut.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────
$raw = file_get_contents('php://input', false, null, 0, 8192);
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige Anfrage']);
    exit;
}

$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$phone    = trim($data['phone'] ?? '');
$workshop = trim($data['workshop'] ?? '');
$consent  = (bool)($data['consent'] ?? false);

// ── Validierung ─────────────────────────────────────────────
$errors = [];
if ($name === '')                                $errors[] = 'Name fehlt';
if (mb_strlen($name) > 100)                      $errors[] = 'Name zu lang';
if ($email === '')                               $errors[] = 'E-Mail-Adresse fehlt';
if (mb_strlen($email) > 254)                     $errors[] = 'E-Mail-Adresse zu lang';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'E-Mail-Adresse ungültig';
if (mb_strlen($phone) > 30)                      $errors[] = 'Telefonnummer zu lang';
if ($workshop === '')                            $errors[] = 'Workshop-Kennung fehlt';
if (mb_strlen($workshop) > 200)                  $errors[] = 'Workshop-Kennung zu lang';
if (!$consent)                                   $errors[] = 'Datenschutz-Einwilligung fehlt';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// Sanitize
$name     = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$phone    = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$workshop = htmlspecialchars($workshop, ENT_QUOTES, 'UTF-8');

// ── Mail an Organisator ─────────────────────────────────────
$subject = "Warteliste: {$workshop}";
$body  = "Neue Warteliste-Anfrage über die Website\n";
$body .= "═══════════════════════════════════════\n\n";
$body .= "Workshop:   {$workshop}\n\n";
$body .= "Name:       {$name}\n";
$body .= "E-Mail:     {$email}\n";
if ($phone !== '') {
    $body .= "Telefon:    {$phone}\n";
}
$body .= "\nZeitpunkt:  " . date('d.m.Y H:i') . " Uhr\n";
$body .= "IP:         " . ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt') . "\n";

$mailSent = wlSendMail(ORGANIZER_EMAIL, $subject, $body);

// ── Bestätigung an Teilnehmer:in ────────────────────────────
$confirmSent = false;
if ($mailSent) {
    $confirmSubject = "Ihre Anmeldung zur Warteliste: {$workshop}";
    $confirmBody  = "Hallo {$name},\n\n";
    $confirmBody .= "vielen Dank — wir haben Sie auf die Warteliste für den Workshop\n";
    $confirmBody .= "„{$workshop}\" aufgenommen.\n\n";
    $confirmBody .= "Sobald ein Platz frei wird oder ein Zusatztermin feststeht,\n";
    $confirmBody .= "melden wir uns per E-Mail bei Ihnen.\n\n";
    $confirmBody .= "Herzliche Grüße\n";
    $confirmBody .= "Ihr Team von Gemeinsam Kochen – Gemeinsam Wachsen\n";
    $confirmBody .= "Mavka.Berlin.Volunteers e.V. (i. Gr.)\n\n";
    $confirmBody .= "───────────────────────────────────\n";
    $confirmBody .= "Kontakt: +49 163 7038724 · info@mavka-berlin.de\n";
    $confirmSent = wlSendMail($email, $confirmSubject, $confirmBody);
}

if ($mailSent) {
    $_SESSION['waitlist_timestamps'][] = $now;
    echo json_encode([
        'ok' => true,
        'message' => 'Warteliste-Eintrag erfolgreich',
        'confirmation' => $confirmSent,
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Ihre Anfrage konnte leider nicht übermittelt werden. Bitte versuchen Sie es telefonisch unter +49 163 7038724.',
    ]);
}

// ── Mail-Hilfsfunktionen (dupliziert aus register.php, P3 Tech-Debt) ──
function wlSendMail(string $to, string $subject, string $body): bool {
    if (SMTP_PASS !== '') {
        return wlSendViaSMTP($to, $subject, $body);
    }
    return wlSendViaMailFunction($to, $subject, $body);
}

function wlSendViaMailFunction(string $to, string $subject, string $body): bool {
    $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: GemeinsamKochen/1.0\r\n";
    return @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);
}

function wlSendViaSMTP(string $to, string $subject, string $body): bool {
    $ctx = stream_context_create(['ssl' => [
        'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
    ]]);
    $socket = @stream_socket_client('tcp://' . SMTP_HOST . ':587', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$socket) {
        error_log("SMTP connect " . SMTP_HOST . ":587 failed: {$errstr} ({$errno})");
        return wlSendViaMailFunction($to, $subject, $body);
    }

    $readResp = function() use ($socket): string {
        $resp = '';
        do {
            $line = fgets($socket, 512);
            if ($line === false) return '';
            $resp .= $line;
        } while (isset($line[3]) && $line[3] === '-');
        return $resp;
    };

    $resp = $readResp();
    if ((int)$resp >= 400) { error_log("SMTP greeting: " . trim($resp)); fclose($socket); return wlSendViaMailFunction($to, $subject, $body); }
    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    $resp = $readResp();
    if ((int)$resp >= 400) { fclose($socket); return wlSendViaMailFunction($to, $subject, $body); }
    fwrite($socket, "STARTTLS\r\n");
    $resp = $readResp();
    if ((int)$resp >= 400) { fclose($socket); return wlSendViaMailFunction($to, $subject, $body); }
    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if (!$crypto) { error_log("STARTTLS crypto upgrade failed"); fclose($socket); return wlSendViaMailFunction($to, $subject, $body); }
    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    $readResp();

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
            error_log("SMTP error at '{$cmd}': " . trim($resp));
            fclose($socket);
            return false;
        }
    }

    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $msg  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $msg .= "To: <{$to}>\r\n";
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
    return $resp !== '' && (int)$resp < 400;
}
