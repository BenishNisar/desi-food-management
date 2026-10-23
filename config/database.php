<?php
declare(strict_types=1);

$db_config = [
    'host'     => 'localhost',
    'dbname'   => 'desi_food_db',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db_config['host'],
        $db_config['dbname'],
        $db_config['charset']
    );

    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed. Please import database.sql and check config/database.php');
}
