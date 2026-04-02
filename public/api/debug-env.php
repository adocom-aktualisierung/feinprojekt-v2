<?php
/**
 * Temporärer Debug-Endpoint — zeigt, ob Env-Vars ankommen.
 * NACH DEM TESTEN SOFORT LÖSCHEN!
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'smtp_pass_length'        => strlen(SMTP_PASS),
    'smtp_pass_set'           => SMTP_PASS !== '',
    'reach_token_length'      => strlen(REACH_API_TOKEN),
    'reach_token_set'         => REACH_API_TOKEN !== '',
    'reach_uuid_length'       => strlen(REACH_PROFILE_UUID),
    'reach_uuid_set'          => REACH_PROFILE_UUID !== '',
    'env_source_smtp'         => getenv('SMTP_PASS') !== false ? 'getenv' : (isset($_ENV['SMTP_PASS']) ? '$_ENV' : (isset($_SERVER['SMTP_PASS']) ? '$_SERVER' : 'NONE')),
    'env_source_reach_token'  => getenv('REACH_API_TOKEN') !== false ? 'getenv' : (isset($_ENV['REACH_API_TOKEN']) ? '$_ENV' : (isset($_SERVER['REACH_API_TOKEN']) ? '$_SERVER' : 'NONE')),
], JSON_PRETTY_PRINT);
