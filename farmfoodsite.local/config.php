<?php
declare(strict_types=1);

/*
 * Настройки подключения к MySQL для Open Server Panel.
 * Обычно на локальном OpenServer: host=localhost, port=3306.
 * Если в панели указан другой порт MySQL — поменяйте DB_PORT.
 */
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'farm_food';
const DB_USER = 'root';
const DB_PASS = ''; // Укажите пароль MySQL, если он задан в OpenServer.

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
