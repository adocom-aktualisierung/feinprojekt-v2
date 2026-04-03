<?php
/**
 * Newsletter-Anmeldung Backend — Double-Opt-in
 *
 * Speichert Anmeldung als "pending" in SQLite und sendet Bestätigungs-E-Mail.
 * Erst nach Klick auf den Bestätigungslink (verify-newsletter.php)
 * wird die E-Mail-Adresse an Hostinger Reach weitergeleitet.
 *
 * Endpoint: POST /api/newsletter.php
 * Content-Type: application/json
 * Body: { "email": "...", "consent": true }
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

// ── Konfiguration prüfen ───────────────────────────────────
if (SMTP_PASS === '') {
    error_log('Newsletter: SMTP_PASS nicht konfiguriert');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Der Newsletter-Dienst ist noch nicht eingerichtet. Bitte versuchen Sie es später erneut.']);
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
$raw = file_get_contents('php://input', false, null, 0, 2048); // Max 2 KB
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
if (mb_strlen($email) > 254)                    $errors[] = 'E-Mail-Adresse zu lang';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-Mail-Adresse ungültig';
if (!$consent)                                  $errors[] = 'Datenschutz-Einwilligung fehlt';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// ── Double-Opt-in Logik ────────────────────────────────────
$db = getDB();

// Alte unbestätigte Einträge aufräumen (> 7 Tage)
$db->prepare('DELETE FROM newsletter_pending WHERE verified_at IS NULL AND created_at < :cutoff')
   ->execute([':cutoff' => $now - 604800]);

// Prüfe ob E-Mail schon existiert
$existing = $db->prepare('SELECT verified_at, created_at FROM newsletter_pending WHERE email = :email ORDER BY created_at DESC LIMIT 1');
$existing->execute([':email' => $email]);
$row = $existing->fetch();

if ($row) {
    if ($row['verified_at'] !== null) {
        // Bereits verifiziert
        echo json_encode(['ok' => true, 'message' => 'Sie sind bereits für den Newsletter angemeldet.']);
        exit;
    }
    if (($now - $row['created_at']) < 172800) {
        // Pending und < 48h alt → nicht erneut senden
        echo json_encode(['ok' => true, 'message' => 'Wir haben Ihnen bereits eine Bestätigungs-E-Mail gesendet. Bitte prüfen Sie Ihr Postfach (auch den Spam-Ordner).']);
        exit;
    }
    // Pending aber abgelaufen → alten Eintrag löschen
    $db->prepare('DELETE FROM newsletter_pending WHERE email = :email AND verified_at IS NULL')
       ->execute([':email' => $email]);
}

// ── Token generieren & speichern ────────────────────────────
$token = bin2hex(random_bytes(32));
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$stmt = $db->prepare('INSERT INTO newsletter_pending (email, token, created_at, ip_address) VALUES (:email, :token, :created_at, :ip)');
$stmt->execute([
    ':email'      => $email,
    ':token'      => $token,
    ':created_at' => $now,
    ':ip'         => $ip,
]);

// ── Bestätigungs-E-Mail senden ──────────────────────────────
$verifyUrl = SITE_URL . '/api/verify-newsletter.php?token=' . $token;

$subject = 'Newsletter-Anmeldung bestätigen';
$body = "Hallo,\n\n"
    . "vielen Dank für Ihr Interesse am Newsletter von „Gemeinsam Kochen – Gemeinsam Wachsen"!\n\n"
    . "Bitte bestätigen Sie Ihre Anmeldung, indem Sie auf den folgenden Link klicken:\n\n"
    . $verifyUrl . "\n\n"
    . "Dieser Link ist 48 Stunden gültig.\n\n"
    . "Falls Sie sich nicht angemeldet haben, können Sie diese E-Mail einfach ignorieren.\n\n"
    . "Herzliche Grüße\n"
    . "Ihr Team von Gemeinsam Kochen – Gemeinsam Wachsen\n"
    . "Mavka.Berlin.Volunteers\n";

$mailError = '';
$sent = sendMail($email, $subject, $body, '', $mailError);

if ($sent) {
    $_SESSION['nl_timestamps'][] = $now;
    echo json_encode(['ok' => true, 'message' => 'Bitte prüfen Sie Ihr Postfach – wir haben Ihnen eine Bestätigungs-E-Mail gesendet.']);
} else {
    // Mail fehlgeschlagen → pending Record entfernen
    $db->prepare('DELETE FROM newsletter_pending WHERE token = :token')
       ->execute([':token' => $token]);
    error_log("Newsletter verification mail failed: {$mailError}");
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.']);
}

// ── Mail-Funktionen ─────────────────────────────────────────
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
    $ctx = stream_context_create(['ssl' => [
        'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
    ]]);
    $socket = @stream_socket_client('tcp://' . SMTP_HOST . ':587', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$socket) {
        $error = "SMTP connect " . SMTP_HOST . ":587 failed: {$errstr} ({$errno})";
        error_log($error);
        return sendViaMailFunction($to, $subject, $body, $replyTo, $error);
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
    if ((int)$resp >= 400) { $error = "SMTP greeting: " . trim($resp); fclose($socket); return sendViaMailFunction($to, $subject, $body, $replyTo, $error); }

    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    $resp = $readResp();
    if ((int)$resp >= 400) { $error = "SMTP EHLO: " . trim($resp); fclose($socket); return sendViaMailFunction($to, $subject, $body, $replyTo, $error); }

    fwrite($socket, "STARTTLS\r\n");
    $resp = $readResp();
    if ((int)$resp >= 400) { $error = "SMTP STARTTLS: " . trim($resp); fclose($socket); return sendViaMailFunction($to, $subject, $body, $replyTo, $error); }

    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if (!$crypto) {
        $error = "STARTTLS crypto upgrade failed";
        error_log($error);
        fclose($socket);
        return sendViaMailFunction($to, $subject, $body, $replyTo, $error);
    }

    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    $resp = $readResp();

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
            return false;
        }
    }

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
