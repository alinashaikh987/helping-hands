<?php
// Example database configuration for Helping Hands.
// Copy this file to db.php and update the values for your local setup.

$host = 'localhost';
$dbname = 'helping_hands';
$username = 'root';
$password = ''; // Set your local MySQL password here.

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed.');
    die('Unable to connect to the database. Please check your configuration.');
}
