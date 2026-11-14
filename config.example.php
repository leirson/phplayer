<?php
/**
 * Arquivo de Configuração - Subsonic PHP Web Player
 * Compatível com Hospedagem Compartilhada (Hostinger/cPanel) e Containers Docker (via variáveis de ambiente)
 */
define('DB_HOST', getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? getenv('DB_HOST') : 'localhost');
define('DB_PORT', getenv('DB_PORT') !== false && getenv('DB_PORT') !== '' ? getenv('DB_PORT') : '3306');
define('DB_NAME', getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? getenv('DB_NAME') : '');
define('DB_USER', getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? getenv('DB_USER') : '');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('IMAGES_DIR', __DIR__ . '/images/');
define('MUSIC_DIR', __DIR__ . '/music/');
define('PODCAST_DIR', __DIR__ . '/podcast/');

if (!function_exists('exitWithJsonError')) {
    function exitWithJsonError($msg, $statusCode = 200) {
        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        // Mantemos 200 por padrão para evitar que servidores Nginx/Apache/cPanel descartem o corpo JSON em favor de tela de erro genérica HTML vazia
        if ($statusCode !== 200 && $statusCode !== 500) {
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
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    if (defined('DONT_EXIT_ON_DB_ERROR')) {
        $pdo = null;
    } else {
        $msg = 'Falha de conexão com o banco de dados MySQL: ' . $e->getMessage() . '. Verifique as credenciais no arquivo config.php (Host: ' . DB_HOST . ', Usuário: ' . DB_USER . ', Banco: ' . DB_NAME . ')';
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
        exitWithJsonError($msg, 200);
    }
}

if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(IMAGES_DIR)) {
    @mkdir(IMAGES_DIR, 0755, true);
}
if (!file_exists(PODCAST_DIR)) {
    @mkdir(PODCAST_DIR, 0755, true);
}
