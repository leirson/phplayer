<?php
/**
 * Arquivo de Configuração - Subsonic PHP Web Player
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'music');
define('DB_USER', 'music');
define('DB_PASS', 'pass');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('IMAGES_DIR', __DIR__ . '/images/');
define('VIDEOS_DIR', __DIR__ . '/videos/');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    if (defined('DONT_EXIT_ON_DB_ERROR')) {
        $pdo = null;
    } else {
        header('Content-Type: application/json; charset=utf-8');
        $msg = 'Falha de banco de dados: ' . $e->getMessage();
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
        // Não definimos http_response_code(500) para evitar que servidores de hospedagem alternem a response para páginas HTML genéricas de erro
        exit(json_encode(['error' => $msg]));
    }
}

if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(IMAGES_DIR)) {
    @mkdir(IMAGES_DIR, 0755, true);
}
if (!file_exists(VIDEOS_DIR)) {
    @mkdir(VIDEOS_DIR, 0755, true);
}
