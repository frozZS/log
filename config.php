<?php
// config.php

// Configuración de la base de datos para XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'underground_cinema');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP por defecto no tiene contraseña


// Función para conectar a la base de datos
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
        exit;
    }
}

// Función para generar nombres únicos de archivos
function generateUniqueFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid('uc_', true) . '.' . $extension;
}

// Función para validar tipo de archivo
function validateFileType($file, $allowedTypes) {
    $fileType = $file['type'] ?? '';
    return in_array($fileType, $allowedTypes, true);
}

// Headers CORS para desarrollo local\header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
