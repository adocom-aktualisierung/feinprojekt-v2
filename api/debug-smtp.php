<?php
/**
 * SMTP Debug — NACH DEM TEST LÖSCHEN!
 * Testet die SMTP-Verbindung und zeigt Fehler an.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "SMTP Debug\n==========\n\n";
echo "Host:  " . SMTP_HOST . "\n";
echo "Port:  " . SMTP_PORT . "\n";
echo "User:  " . SMTP_USER . "\n";
echo "Pass:  " . (SMTP_PASS !== '' ? str_repeat('*', strlen(SMTP_PASS)) : '*** EMPTY ***') . "\n";
echo "From:  " . SMTP_FROM . "\n";
echo "To:    " . ORGANIZER_EMAIL . "\n\n";

echo "Connecting to tls://" . SMTP_HOST . ":" . SMTP_PORT . " ...\n";
$socket = @fsockopen('tls://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);

if (!$socket) {
    echo "FAILED: {$errstr} ({$errno})\n\n";
    echo "Trying ssl:// on port 465 ...\n";
    $socket = @fsockopen('ssl://' . SMTP_HOST, 465, $errno2, $errstr2, 10);
    if (!$socket) {
        echo "FAILED: {$errstr2} ({$errno2})\n\n";
        echo "Trying plain on port 587 (STARTTLS) ...\n";
        $socket = @fsockopen(SMTP_HOST, 587, $errno3, $errstr3, 10);
        if (!$socket) {
            echo "FAILED: {$errstr3} ({$errno3})\n";
            echo "\nAll connection methods failed.\n";
            exit;
        } else {
            echo "OK (plain:587 — needs STARTTLS)\n";
        }
    } else {
        echo "OK (ssl:465)\n";
    }
} else {
    echo "OK\n";
}

$greeting = fgets($socket, 512);
echo "Greeting: {$greeting}";

fwrite($socket, "EHLO " . gethostname() . "\r\n");
$resp = '';
do {
    $line = fgets($socket, 512);
    $resp .= $line;
} while (isset($line[3]) && $line[3] === '-');
echo "EHLO: {$resp}";

fwrite($socket, "AUTH LOGIN\r\n");
echo "AUTH LOGIN: " . fgets($socket, 512);

fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
echo "User: " . fgets($socket, 512);

fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
$authResp = fgets($socket, 512);
echo "Pass: {$authResp}";

if ((int)$authResp >= 400) {
    echo "\nAuthentication FAILED\n";
} else {
    echo "\nAuthentication OK\n";
}

fwrite($socket, "QUIT\r\n");
fclose($socket);
