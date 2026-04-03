<?php
/** Zeigt die Suchpfade für config.local.php — NACH DEM TEST LÖSCHEN! */
header('Content-Type: text/plain; charset=utf-8');

echo "config.local.php Suchpfade\n===========================\n\n";

$paths = [
    '__DIR__'              => __DIR__ . '/config.local.php',
    'dirname(1) = dist/'   => dirname(__DIR__) . '/config.local.php',
    'dirname(2) = root'    => dirname(__DIR__, 2) . '/config.local.php',
    'dirname(3) = above'   => dirname(__DIR__, 3) . '/config.local.php',
    'HOME'                 => ($_SERVER['HOME'] ?? 'N/A') . '/config.local.php',
    'DOCUMENT_ROOT parent' => (isset($_SERVER['DOCUMENT_ROOT']) ? dirname($_SERVER['DOCUMENT_ROOT']) : 'N/A') . '/config.local.php',
];

foreach ($paths as $label => $path) {
    $exists = (strpos($path, 'N/A') === false && file_exists($path)) ? 'FOUND' : 'not found';
    echo str_pad($label, 25) . " => {$path}  [{$exists}]\n";
}

echo "\n--- Server Info ---\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "HOME: " . ($_SERVER['HOME'] ?? 'N/A') . "\n";
