<?php
/**
 * SMTP Debug mit STARTTLS — NACH DEM TEST LÖSCHEN!
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "SMTP Debug (STARTTLS)\n=====================\n\n";
echo "Host:  " . SMTP_HOST . "\n";
echo "Port:  587 (STARTTLS)\n";
echo "User:  " . SMTP_USER . "\n";
echo "Pass:  " . (SMTP_PASS !== '' ? str_repeat('*', strlen(SMTP_PASS)) : '*** EMPTY ***') . "\n";
echo "From:  " . SMTP_FROM . "\n";
echo "To:    " . ORGANIZER_EMAIL . "\n\n";

echo "1. Connecting to " . SMTP_HOST . ":587 ...\n";
$socket = @fsockopen(SMTP_HOST, 587, $errno, $errstr, 10);

if (!$socket) {
    echo "FAILED: {$errstr} ({$errno})\n";
    exit;
}
echo "OK\n";

$greeting = fgets($socket, 512);
echo "2. Greeting: {$greeting}";

fwrite($socket, "EHLO test\r\n");
$resp = '';
do { $line = fgets($socket, 512); $resp .= $line; } while (isset($line[3]) && $line[3] === '-');
echo "3. EHLO: {$resp}";

fwrite($socket, "STARTTLS\r\n");
$resp = fgets($socket, 512);
echo "4. STARTTLS: {$resp}";

if ((int)$resp >= 400) {
    echo "\nSTARTTLS rejected\n";
    fclose($socket);
    exit;
}

echo "5. Upgrading to TLS ... ";
$crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
if (!$crypto) {
    echo "FAILED\n";
    fclose($socket);
    exit;
}
echo "OK\n";

fwrite($socket, "EHLO test\r\n");
$resp = '';
do { $line = fgets($socket, 512); $resp .= $line; } while (isset($line[3]) && $line[3] === '-');
echo "6. EHLO (post-TLS): {$resp}";

fwrite($socket, "AUTH LOGIN\r\n");
echo "7. AUTH LOGIN: " . fgets($socket, 512);

fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
echo "8. User: " . fgets($socket, 512);

fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
$authResp = fgets($socket, 512);
echo "9. Pass: {$authResp}";

if ((int)$authResp >= 400) {
    echo "\nAuthentication FAILED\n";
} else {
    echo "\nAuthentication OK!\n";
}

fwrite($socket, "QUIT\r\n");
fclose($socket);
