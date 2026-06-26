<?php
/**
 * Arquivo de Configuração - Subsonic PHP Web Player
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123196074_music');
define('DB_USER', 'u123196074_music');
define('DB_PASS', 'WinIpCfg95@260213');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('IMAGES_DIR', __DIR__ . '/images/');
define('VIDEOS_DIR', __DIR__ . '/videos/');

if (!function_exists('exitWithJsonError')) {
    function exitWithJsonError($msg, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        if ($statusCode !== 200) {
            http_response_code($statusCode);
        }
        $payload = ['error' => $msg, 'success' => false];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $clean_msg = preg_replace('/[^\x20-\x7E\s]/', '?', $msg);
            $json = '{"error":"' . addslashes($clean_msg) . '","success":false}';
        }
        exit($json);
    }
}

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
        $msg = 'Falha de banco de dados: ' . $e->getMessage();
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
        exitWithJsonError($msg);
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
