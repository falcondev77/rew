<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'buonafo1_amz');
define('DB_USER', 'buonafo1_amz1');
define('DB_PASS', 'Pianeta123!');
define('SITE_NAME', 'AffiliateRewards');
define('SITE_URL', 'https://buonafortuna.world');
define('DEFAULT_TRACKING_SUFFIX', '-21');
define('DEFAULT_SHARE_PERCENT', 30);
define('BONUS_SHARE_PERCENT', 10);
define('NEXT_REWARD_TARGET', 500);
define('NEXT_REWARD_LABEL', 'Gift Card Amazon 10€');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}
