<?php
$env = file_exists(__DIR__ . '/.env') ? parse_ini_file(__DIR__ . '/.env') : [];

$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? 'student_queue';

   $conn = new mysqli("127.0.0.1", "root", "", "student_queue");

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error . '<br>Please import database.sql and check your database settings.');
}
$conn->set_charset('utf8mb4');
?>
