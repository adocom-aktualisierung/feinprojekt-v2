<?php
/**
 * Newsletter Double-Opt-in — Bestätigungslink
 *
 * GET /api/verify-newsletter.php?token=...
 *
 * Validiert den Token, markiert als verifiziert und leitet
 * die E-Mail-Adresse an Hostinger Reach weiter.
 */

require_once __DIR__ . '/config.php';

// ── Token aus URL lesen ─────────────────────────────────────
$token = $_GET['token'] ?? '';

// Token muss exakt 64 Hex-Zeichen sein
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    renderPage('Ungültiger Link', 'Der Bestätigungslink ist ungültig. Bitte melden Sie sich erneut für den Newsletter an.', true);
    exit;
}

// ── Datenbank prüfen ────────────────────────────────────────
$db = getDB();
$now = time();

$stmt = $db->prepare('SELECT id, email, created_at, verified_at FROM newsletter_pending WHERE token = :token LIMIT 1');
$stmt->execute([':token' => $token]);
$row = $stmt->fetch();

if (!$row) {
    renderPage('Link nicht gefunden', 'Dieser Bestätigungslink ist nicht (mehr) gültig. Bitte melden Sie sich erneut für den Newsletter an.', true);
    exit;
}

if ($row['verified_at'] !== null) {
    renderPage('Bereits bestätigt', 'Ihre E-Mail-Adresse wurde bereits bestätigt. Sie erhalten unseren Newsletter.', false);
    exit;
}

// Token älter als 48 Stunden?
if (($now - $row['created_at']) > 172800) {
    renderPage('Link abgelaufen', 'Dieser Bestätigungslink ist leider abgelaufen. Bitte melden Sie sich erneut für den Newsletter an.', true);
    exit;
}

// ── An Hostinger Reach API weiterleiten ─────────────────────
$reachOk = false;
$reachError = '';

if (REACH_API_TOKEN !== '' && REACH_PROFILE_UUID !== '') {
    $url = REACH_API_BASE . '/profiles/' . REACH_PROFILE_UUID . '/contacts';

    $payload = json_encode(['email' => $row['email']]);

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

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        $reachError = "Reach API cURL error: {$curlError}";
        error_log("Newsletter verify: {$reachError}");
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        $reachOk = true;
    } elseif ($httpCode === 409) {
        // Kontakt existiert bereits bei Reach — das ist OK
        $reachOk = true;
    } else {
        $body = json_decode($response, true);
        $reachError = "Reach API HTTP {$httpCode}: " . ($body['message'] ?? $body['error'] ?? $response);
        error_log("Newsletter verify: {$reachError}");
    }
} else {
    // Reach nicht konfiguriert — trotzdem als verifiziert markieren
    error_log('Newsletter verify: REACH_API_TOKEN oder REACH_PROFILE_UUID nicht konfiguriert, überspringe Reach-API');
    $reachOk = true;
}

if ($reachOk) {
    // Als verifiziert markieren
    $update = $db->prepare('UPDATE newsletter_pending SET verified_at = :now WHERE id = :id');
    $update->execute([':now' => $now, ':id' => $row['id']]);

    renderPage('Anmeldung bestätigt!',
        'Vielen Dank! Ihre E-Mail-Adresse wurde erfolgreich bestätigt. Sie erhalten ab jetzt unseren Newsletter.',
        false
    );
} else {
    renderPage('Fehler bei der Bestätigung',
        'Die Bestätigung konnte leider nicht abgeschlossen werden. Bitte versuchen Sie es in einigen Minuten erneut, indem Sie den Link in Ihrer E-Mail noch einmal anklicken.',
        true
    );
}

// ── HTML-Seite rendern ──────────────────────────────────────
function renderPage(string $title, string $message, bool $isError): void {
    $bgColor   = '#FAFAF5';
    $cardBg    = '#FFFDF5';
    $textColor = '#4A4A4A';
    $accent    = $isError ? '#C43333' : '#1B5E20';
    $icon      = $isError ? '&#10007;' : '&#10003;';

    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} – Gemeinsam Kochen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Nunito', system-ui, sans-serif;
            background: {$bgColor};
            color: {$textColor};
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: {$cardBg};
            border-radius: 16px;
            padding: 48px 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: {$accent};
            color: #fff;
            font-size: 32px;
            line-height: 64px;
            margin: 0 auto 24px;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: {$accent};
            margin-bottom: 16px;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.65;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background: #1B5E20;
            color: #fff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover { background: #2E6F40; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{$icon}</div>
        <h1>{$title}</h1>
        <p>{$message}</p>
        <a href="/" class="btn">Zur Startseite</a>
    </div>
</body>
</html>
HTML;
}
