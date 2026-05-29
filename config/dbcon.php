<?php
// Aligne TP Module 2 - Connexion PDO try/catch & ERRMODE_EXCEPTION

$servername = 'localhost';
$username = 'root';
$password = '';
$database = 'emsp_digital';

try {
    $conn = new PDO(
        "mysql:host={$servername};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Echec de connexion a la base de donnees.');
}
