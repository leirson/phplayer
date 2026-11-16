<?php
/**
 * REST API Principal - Subsonic PHP Web Player
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Range, X-Username");
header("Access-Control-Expose-Headers: Content-Range, Content-Length, Accept-Ranges");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Iniciar buffer de saída para evitar que qualquer whitespace, aviso de extensão ou erro PHP corrompa as respostas JSON
if (!ob_get_level()) {
    @ob_start();
}

// Habilitar relato de erros para poder tratá-los no callback global e transparente
@error_reporting(E_ALL);
@ini_set('display_errors', 0); // Não imprimir avisos crus para evitar corromper o JSON
@set_time_limit(0);
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '512M');

if (!function_exists('exitWithJsonError')) {
    function exitWithJsonError($msg, $statusCode = 200) {
        // Limpar qualquer saída anterior que possa ter vazado para o buffer
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

// Polyfills de funções do PHP 8 e mbstring para compatibilidade com servidores PHP antigos ou sem ext-mbstring
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($str, $encoding = null) {
        return strtolower((string)$str);
    }
}
if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding($var = null, $encoding = null) {
        if (!is_string($var)) return true;
        return preg_match('//u', $var) === 1;
    }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($string, $to_encoding, $from_encoding = null) {
        $s = (string)$string;
        if (function_exists('iconv')) {
            $from = is_string($from_encoding) && $from_encoding !== '' ? $from_encoding : 'UTF-8';
            $res = @iconv($from, $to_encoding . '//IGNORE', $s);
            if ($res !== false) return $res;
        }
        return $s;
    }
}
if (!function_exists('phplayer_lower')) {
    function phplayer_lower($str) {
        $s = trim((string)$str);
        return function_exists('mb_strtolower') ? @mb_strtolower($s, 'UTF-8') : strtolower($s);
    }
}
if (!function_exists('phplayer_safe_db_str')) {
    function phplayer_safe_db_str($str, $maxBytes = 250) {
        $s = str_replace("\0", '', (string)$str);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        if (function_exists('iconv')) {
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
            if ($clean !== false) $s = $clean;
        } elseif (function_exists('mb_convert_encoding')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'UTF-8');
        }
        $s = trim($s);
        if ($maxBytes > 0 && strlen($s) > $maxBytes) {
            $s = function_exists('mb_strcut') ? @mb_strcut($s, 0, $maxBytes, 'UTF-8') : substr($s, 0, $maxBytes);
        }
        return $s;
    }
}

set_exception_handler(function ($exception) {
    $msg = 'Exceção não tratada no servidor PHP: ' . $exception->getMessage() . ' (' . basename($exception->getFile()) . ':' . $exception->getLine() . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
    exitWithJsonError($msg);
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    // Não interromper a execução para avisos, notices ou deprecações
    if (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_WARNING, E_USER_WARNING])) {
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . "Aviso PHP ($severity): $message (" . basename($file) . ":$line)\n", FILE_APPEND);
        return false;
    }
    $msg = 'Erro de execução no PHP: ' . $message . ' (' . basename($file) . ':' . $line . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
    exitWithJsonError($msg);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = 'ERRO FATAL PHP: ' . $error['message'] . ' (' . basename($error['file']) . ':' . $error['line'] . ')';
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
        exitWithJsonError($msg);
    }
});

require_once 'config.php';
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads/');
if (!defined('IMAGES_DIR')) define('IMAGES_DIR', __DIR__ . '/images/');

foreach ([UPLOAD_DIR, IMAGES_DIR] as $dirPathCheck) {
    if (!file_exists($dirPathCheck)) {
        @mkdir($dirPathCheck, 0755, true);
    }
}
header('Content-Type: application/json; charset=utf-8');

try {
    // Garantir a estrutura das tabelas do banco de dados (Auto-Migration completa)
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            // Criar tabelas se não existirem
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
              id int(11) NOT NULL AUTO_INCREMENT,
              username varchar(50) NOT NULL UNIQUE,
              password varchar(255) NOT NULL,
              role varchar(20) DEFAULT 'ouvinte',
              theme varchar(30) DEFAULT 'default',
              created_at datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            try { @$pdo->exec("ALTER TABLE users ADD COLUMN sidebarBg varchar(100) DEFAULT ''"); } catch(Exception $e) {}
            try { @$pdo->exec("ALTER TABLE users ADD COLUMN footerBg varchar(100) DEFAULT ''"); } catch(Exception $e) {}
            try { @$pdo->exec("ALTER TABLE users ADD COLUMN topBg varchar(100) DEFAULT ''"); } catch(Exception $e) {}
            try { @$pdo->exec("ALTER TABLE users ADD COLUMN can_download tinyint(1) DEFAULT 1"); } catch(Exception $e) {}
            try { @$pdo->exec("ALTER TABLE users ADD COLUMN can_share tinyint(1) DEFAULT 1"); } catch(Exception $e) {}
                try { @$pdo->exec("ALTER TABLE users ADD COLUMN dashboardLimit int(11) DEFAULT 50"); } catch(Exception $e) {}

            $pdo->exec("CREATE TABLE IF NOT EXISTS songs (
              id int(11) NOT NULL AUTO_INCREMENT,
              title varchar(255) NOT NULL,
              artist varchar(255) DEFAULT 'Artista Desconhecido',
              album varchar(255) DEFAULT 'Álbum Desconhecido',
              genre varchar(100) DEFAULT 'Desconhecido',
              file_name varchar(255) NOT NULL,
              file_size bigint(20) NOT NULL,
              duration int(11) DEFAULT 180,
              cover_url varchar(500) DEFAULT NULL,
              created_at datetime DEFAULT CURRENT_TIMESTAMP,
              play_count int(11) DEFAULT 0,
              last_played datetime DEFAULT NULL,
              album_year varchar(10) DEFAULT NULL,
              album_type varchar(50) DEFAULT 'album',
              track_number int(11) DEFAULT NULL,
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS playlists (
              id int(11) NOT NULL AUTO_INCREMENT,
              name varchar(100) NOT NULL,
              username varchar(50) NOT NULL,
              created_at datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS playlist_songs (
              playlist_id int(11) NOT NULL,
              song_id int(11) NOT NULL,
              position int(11) NOT NULL,
              PRIMARY KEY (playlist_id,song_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
              username varchar(50) NOT NULL,
              song_id int(11) NOT NULL,
              PRIMARY KEY (username,song_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS play_logs (
              id int(11) NOT NULL AUTO_INCREMENT,
              username varchar(50) NOT NULL,
              song_id int(11) NOT NULL,
              played_at datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
              setting_key varchar(100) NOT NULL,
              setting_value text DEFAULT NULL,
              PRIMARY KEY (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
              artist varchar(255) NOT NULL,
              artist_photo varchar(1000) DEFAULT NULL,
              bio text DEFAULT NULL,
              PRIMARY KEY (artist)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS radios (
              id varchar(50) NOT NULL,
              name varchar(255) NOT NULL,
              url varchar(500) NOT NULL,
              resolved_url varchar(500) DEFAULT NULL,
              created_at datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                share_hash VARCHAR(100) PRIMARY KEY,
                target_type VARCHAR(50),
                target_id VARCHAR(500),
                target_name VARCHAR(255),
                created_by VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Garantir colunas em shares caso a tabela já existisse de versões anteriores
            try {
                $shareCols = $pdo->query("SHOW COLUMNS FROM `shares`");
                $existingCols = $shareCols ? $shareCols->fetchAll(PDO::FETCH_COLUMN) : [];
                if (!empty($existingCols)) {
                    if (!in_array('created_by', $existingCols)) {
                        $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL");
                    }
                    if (!in_array('expires_at', $existingCols)) {
                        $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL");
                    }
                    if (!in_array('target_name', $existingCols)) {
                        $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL");
                    }
                }
            } catch (Throwable $th) {
                try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL"); } catch (Throwable $e) {}
            }

            // Garantir coluna theme
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN theme VARCHAR(30) DEFAULT 'default'");
            } catch (Exception $theme_ex) {
                // Ignora se já existir
            }

            // Garantir colunas de estatísticas e metadados adicionais em TODAS as tabelas de músicas
            try {
                if (function_exists('get_songs_tables')) {
                    $songColsToEnsure = [
                        "play_count INT DEFAULT 0",
                        "last_played DATETIME DEFAULT NULL",
                        "album_year VARCHAR(10) DEFAULT NULL",
                        "album_type VARCHAR(50) DEFAULT 'album'",
                        "track_number INT DEFAULT NULL"
                    ];
                    foreach (get_songs_tables($pdo) as $st) {
                        foreach ($songColsToEnsure as $colDef) {
                            try { $pdo->exec("ALTER TABLE `" . $st . "` ADD COLUMN " . $colDef); } catch (Exception $ex) {}
                        }
                    }
                }
            } catch (Exception $col_ex) {}

            // Inserir usuários padrão se tabela users estiver vazia
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ($userCount == 0) {
                $pdo->exec("INSERT INTO users (id, username, password, role) VALUES
                (1, 'admin', '\$2y\$10\$m.1eNSRiMtmn.9RvSqJL/.sRfFdcFlgv36RrpGkNfzR5F7LaA1C42', 'admin'),
                (2, 'ouvinte', '\$2y\$10\$X10Q4Ac4vmEgRpyWM2ok1./0gKGk6d3QXpeHE4c0YcD1rZ/VxGEKe', 'ouvinte')
                ON DUPLICATE KEY UPDATE username = username;");
            }
        }
    } catch (Exception $e) {
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . "Erro Auto-Migration: " . $e->getMessage() . "
", FILE_APPEND);
    }

    // Helper para log de sincronização de musicas
    if (!function_exists('write_scan_log')) {
        function write_scan_log($message, $clear = false) {
            $logFile = __DIR__ . '/music_scan.log';
            if ($clear) {
                @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "=== INICIALIZANDO VARREDURA COMPLETA ===
");
            } else {
                @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . "
", FILE_APPEND);
            }
        }
    }

    // Helper para buscar tabelas de musicas particionadas (songs, songs_1, songs_2, etc)
    if (!function_exists('get_songs_tables')) {
        function get_songs_tables($pdo) {
            try {
                $stmt = $pdo->query("SHOW TABLES");
                $tables = [];
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                        $tblName = $row[0];
                        if ($tblName === 'songs' || preg_match('/^songs_([0-9]+)$/', $tblName)) {
                            $tables[] = $tblName;
                        }
                    }
                }
                if (empty($tables)) {
                    $tables[] = 'songs';
                }
                usort($tables, function($a, $b) {
                    if ($a === 'songs') return -1;
                    if ($b === 'songs') return 1;
                    $numA = intval(substr($a, 6));
                    $numB = intval(substr($b, 6));
                    return $numA <=> $numB;
                });
                return $tables;
            } catch (Exception $e) {
                return ['songs'];
            }
        }
    }

    if (!function_exists('get_song_table_by_id')) {
        function get_song_table_by_id($pdo, $id) {
            $tables = get_songs_tables($pdo);
            foreach ($tables as $t) {
                try {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM `" . $t . "` WHERE id = ?");
                    $chk->execute([$id]);
                    if (intval($chk->fetchColumn()) > 0) {
                        return $t;
                    }
                } catch (Exception $e) {}
            }
            return 'songs';
        }
    }

    if (!function_exists('get_songs_count')) {
        function get_songs_count($pdo, $where = null, $params = []) {
            $tables = get_songs_tables($pdo);
            $total = 0;
            foreach ($tables as $t) {
                try {
                    $sql = "SELECT COUNT(*) FROM `" . $t . "`";
                    if ($where) {
                        $sql .= " WHERE " . $where;
                    }
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $total += intval($stmt->fetchColumn());
                } catch (Exception $e) {}
            }
            return $total;
        }
    }

    if (!function_exists('get_insert_song_table')) {
        function get_insert_song_table($pdo) {
            $tables = get_songs_tables($pdo);
            $activeTable = end($tables);
            if (!$activeTable) {
                $activeTable = 'songs';
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `" . $activeTable . "`");
                $count = intval($stmt->fetchColumn());
            } catch (Exception $e) {
                $count = 0;
            }
            
            if ($count >= 1000) {
                if ($activeTable === 'songs') {
                    $nextIndex = 1;
                } else {
                    preg_match('/^songs_([0-9]+)$/', $activeTable, $matches);
                    $nextIndex = intval($matches[1] ?? 0) + 1;
                }
                $newTable = "songs_" . $nextIndex;
                
                // Calcula o próximo AUTO_INCREMENT global
                $maxId = 0;
                foreach ($tables as $t) {
                    try {
                        $mVal = intval($pdo->query("SELECT COALESCE(MAX(id), 0) FROM `" . $t . "`")->fetchColumn());
                        if ($mVal > $maxId) {
                            $maxId = $mVal;
                        }
                    } catch (Exception $e) {}
                }
                $nextAutoInc = $maxId + 1;
                
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `" . $newTable . "` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `title` varchar(255) NOT NULL,
                      `artist` varchar(255) DEFAULT 'Artista Desconhecido',
                      `album` varchar(255) DEFAULT 'Álbum Desconhecido',
                      `genre` varchar(100) DEFAULT 'Desconhecido',
                      `file_name` varchar(255) NOT NULL,
                      `file_size` bigint(20) NOT NULL,
                      `duration` int(11) DEFAULT 180,
                      `cover_url` varchar(500) DEFAULT NULL,
                      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                      `play_count` INT DEFAULT 0,
                      `last_played` DATETIME DEFAULT NULL,
                      `album_year` VARCHAR(10) DEFAULT NULL,
                      `album_type` VARCHAR(50) DEFAULT 'album',
                      `track_number` INT DEFAULT NULL,
                      PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=" . $nextAutoInc . ";");
                    
                    return $newTable;
                } catch (Exception $e) {
                    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . "Erro ao criar tabela particionada " . $newTable . ": " . $e->getMessage() . "
", FILE_APPEND);
                }
            }
            
            return $activeTable;
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $route = isset($_GET['route']) ? $_GET['route'] : '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!is_array($input)) {
        $input = [];
    }

    $current_username = '';
    if (!empty($_SERVER['HTTP_X_USERNAME'])) {
        $current_username = trim($_SERVER['HTTP_X_USERNAME']);
    } elseif (!empty($_POST['username'])) {
        $current_username = trim($_POST['username']);
    } elseif (!empty($_POST['admin_username'])) {
        $current_username = trim($_POST['admin_username']);
    } elseif (!empty($_GET['admin_username'])) {
        $current_username = trim($_GET['admin_username']);
    } elseif (!empty($_GET['username'])) {
        $current_username = trim($_GET['username']);
    } elseif (!empty($input['username'])) {
        $current_username = trim($input['username']);
    } elseif (!empty($input['admin_username'])) {
        $current_username = trim($input['admin_username']);
    } elseif (function_exists('getallheaders')) {
        $allHeaders = getallheaders();
        foreach ($allHeaders as $hKey => $hVal) {
            if (strtolower($hKey) === 'x-username' && !empty($hVal)) {
                $current_username = trim($hVal);
                break;
            }
        }
    }
    if (empty($current_username) && function_exists('apache_request_headers')) {
        $apacheHeaders = apache_request_headers();
        foreach ($apacheHeaders as $hKey => $hVal) {
            if (strtolower($hKey) === 'x-username' && !empty($hVal)) {
                $current_username = trim($hVal);
                break;
            }
        }
    }

    // Lightweight pure PHP ID3 tag parser helper
    $getMp3Meta = function($filename) {
        $meta = [
            'tag_genre' => 'Local Scan',
            'tag_title' => '',
            'tag_artist' => '',
            'tag_album' => '',
            'tag_duration' => 210
        ];
        if (!file_exists($filename) || !is_readable($filename)) {
            return $meta;
        }
        $handle = @fopen($filename, 'rb');
        if (!$handle) {
            return $meta;
        }
        $data = @fread($handle, 32768);
        @fclose($handle);

        if (substr($data, 0, 3) === 'ID3') {
            $genres_map = [
                0 => 'Blues', 1 => 'Classic Rock', 2 => 'Country', 3 => 'Dance', 4 => 'Disco', 5 => 'Funk', 
                6 => 'Grunge', 7 => 'Hip-Hop', 8 => 'Jazz', 9 => 'Metal', 10 => 'New Age', 11 => 'Oldies', 
                12 => 'Other', 13 => 'Pop', 14 => 'R&B', 15 => 'Rap', 16 => 'Reggae', 17 => 'Rock', 
                18 => 'Techno', 19 => 'Industrial', 20 => 'Alternative', 21 => 'Ska', 22 => 'Death Metal', 
                23 => 'Pranks', 24 => 'Soundtrack', 25 => 'Euro-Techno', 26 => 'Ambient', 27 => 'Trip-Hop', 
                28 => 'Vocal', 29 => 'Jazz+Funk', 30 => 'Fusion', 31 => 'Trance', 32 => 'Classical', 
                33 => 'Instrumental', 34 => 'Acid', 35 => 'House', 36 => 'Game', 37 => 'Sound Clip', 
                38 => 'Gospel', 39 => 'Noise', 40 => 'Alt Rock', 41 => 'Bass', 42 => 'Soul', 43 => 'Punk', 
                44 => 'Space', 45 => 'Meditative', 46 => 'Instrumental Pop', 47 => 'Instrumental Rock', 
                48 => 'Ethnic', 49 => 'Gothic', 50 => 'Darkwave', 51 => 'Techno-Industrial', 52 => 'Electronic', 
                53 => 'Pop-Folk', 54 => 'Eurodance', 55 => 'Dream', 56 => 'Southern Rock', 57 => 'Comedy', 
                58 => 'Cult', 59 => 'Gangsta', 60 => 'Top 40', 61 => 'Christian Rap', 62 => 'Pop/Funk', 
                63 => 'Jungle', 64 => 'Native American', 65 => 'Cabaret', 66 => 'New Wave', 67 => 'Psychadelic', 
                68 => 'Rave', 69 => 'Showtunes', 70 => 'Trailer', 71 => 'Lo-Fi', 72 => 'Tribal', 73 => 'Acid Punk', 
                74 => 'Acid Jazz', 75 => 'Polka', 76 => 'Retro', 77 => 'Musical', 78 => 'Rock & Roll', 79 => 'Hard Rock'
            ];

            $findFrame = function($data, $frameId) {
                $pos = strpos($data, $frameId);
                if ($pos === false) return '';
                $sizeBytes = substr($data, $pos + 4, 4);
                if (strlen($sizeBytes) < 4) return '';
                $size = (ord($sizeBytes[0]) << 24) | (ord($sizeBytes[1]) << 16) | (ord($sizeBytes[2]) << 8) | ord($sizeBytes[3]);
                if ($size <= 0) return '';
                if ($size > 512) $size = 64;
                $frameContent = substr($data, $pos + 10, $size);
                
                if (strlen($frameContent) < 1) return '';
                
                $encoding = ord($frameContent[0]);
                $text = substr($frameContent, 1);
                
                if ($encoding === 0) {
                    $decoded = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
                } elseif ($encoding === 1) {
                    if (substr($text, 0, 2) === "ÿþ" || (strlen($text) >= 2 && ord($text[0]) === 255 && ord($text[1]) === 254)) {
                        $decoded = @mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16LE');
                    } elseif (substr($text, 0, 2) === "þÿ" || (strlen($text) >= 2 && ord($text[0]) === 254 && ord($text[1]) === 255)) {
                        $decoded = @mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16BE');
                    } else {
                        $decoded = @mb_convert_encoding($text, 'UTF-8', 'UTF-16');
                    }
                } elseif ($encoding === 2) {
                    $decoded = @mb_convert_encoding($text, 'UTF-8', 'UTF-16BE');
                } elseif ($encoding === 3) {
                    $decoded = $text;
                } else {
                    // Fallback guess
                    if (substr($frameContent, 0, 2) === "ÿþ") {
                        $decoded = @mb_convert_encoding(substr($frameContent, 2), 'UTF-8', 'UTF-16LE');
                    } elseif (substr($frameContent, 0, 2) === "þÿ") {
                        $decoded = @mb_convert_encoding(substr($frameContent, 2), 'UTF-8', 'UTF-16BE');
                    } else {
                        $decoded = mb_check_encoding($frameContent, 'UTF-8') ? $frameContent : @mb_convert_encoding($frameContent, 'UTF-8', 'ISO-8859-1');
                    }
                }
                
                // Remove potential remnants of BOMs and control characters
                $decoded = str_replace(["ï»¿", "ÿþ", "þÿ"], '', $decoded);
                $decoded = trim($decoded, "\x00..\x1f ");
                $decoded = preg_replace("/[\x00-\x08\x0b\x0c\x0e-\x1f]/", "", $decoded);
                
                // Fallback validation to guarantee absolute conformance to utf8mb4
                $decoded = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
                return trim($decoded);
            };

            $genreRaw = $findFrame($data, 'TCON');
            if (!empty($genreRaw)) {
                if (preg_match('/^\((\d+)\)(.*)$/', $genreRaw, $matches)) {
                    $id = intval($matches[1]);
                    if (isset($genres_map[$id])) {
                        $meta['tag_genre'] = $genres_map[$id];
                    }
                    if (!empty($matches[2])) {
                        $meta['tag_genre'] = trim($matches[2]);
                    }
                } elseif (is_numeric($genreRaw)) {
                    $id = intval($genreRaw);
                    if (isset($genres_map[$id])) {
                        $meta['tag_genre'] = $genres_map[$id];
                    }
                } else {
                    $meta['tag_genre'] = $genreRaw;
                }
            }

            $titleRaw = $findFrame($data, 'TIT2');
            if (!empty($titleRaw)) $meta['tag_title'] = $titleRaw;

            $artistRaw = $findFrame($data, 'TPE1');
            if (!empty($artistRaw)) $meta['tag_artist'] = $artistRaw;

            $albumRaw = $findFrame($data, 'TALB');
            if (!empty($albumRaw)) $meta['tag_album'] = $albumRaw;

            $trackRaw = $findFrame($data, 'TRCK');
            if (!empty($trackRaw)) {
                if (preg_match('/^(\d+)/', $trackRaw, $m)) {
                    $meta['tag_track_number'] = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                } else {
                    $meta['tag_track_number'] = $trackRaw;
                }
            }

            $yearRaw = $findFrame($data, 'TYER');
            if (empty($yearRaw)) {
                $yearRaw = $findFrame($data, 'TDRC');
            }
            if (!empty($yearRaw)) {
                if (preg_match('/^(\d{4})/', $yearRaw, $m)) {
                    $meta['tag_year'] = $m[1];
                }
            }

            $durationRaw = $findFrame($data, 'TLEN');
            if (!empty($durationRaw) && is_numeric($durationRaw)) {
                $ms = intval($durationRaw);
                if ($ms > 0) {
                    $meta['tag_duration'] = round($ms / 1000);
                }
            }
        } else {
            $handle_v1 = @fopen($filename, 'rb');
            if ($handle_v1) {
                if (@fseek($handle_v1, -128, SEEK_END) === 0) {
                    $tag = fread($handle_v1, 128);
                    if (substr($tag, 0, 3) === 'TAG') {
                        $sanitizeV1 = function($str) {
                            $clean = trim($str, "\x00..\x1f ");
                            $clean = mb_check_encoding($clean, "UTF-8") ? $clean : @mb_convert_encoding($clean, "UTF-8", "ISO-8859-1");
                            $clean = preg_replace("/[\x00-\x08\x0b\x0c\x0e-\x1f]/", "", $clean);
                            return trim($clean);
                        };
                        $titlev1 = $sanitizeV1(substr($tag, 3, 30));
                        $artistv1 = $sanitizeV1(substr($tag, 33, 30));
                        $albumv1 = $sanitizeV1(substr($tag, 63, 30));
                        $yearv1 = $sanitizeV1(substr($tag, 93, 4));
                        $genre_byte = ord(substr($tag, 127, 1));
                        $genres_map = [
                            0 => 'Blues', 1 => 'Classic Rock', 2 => 'Country', 3 => 'Dance', 4 => 'Disco', 5 => 'Funk', 
                            6 => 'Grunge', 7 => 'Hip-Hop', 8 => 'Jazz', 9 => 'Metal', 10 => 'New Age', 11 => 'Oldies', 
                            12 => 'Other', 13 => 'Pop', 14 => 'R&B', 15 => 'Rap', 16 => 'Reggae', 17 => 'Rock', 
                            18 => 'Techno', 19 => 'Industrial', 20 => 'Alternative', 21 => 'Ska', 22 => 'Death Metal', 
                            23 => 'Pranks', 24 => 'Soundtrack', 25 => 'Euro-Techno', 26 => 'Ambient', 27 => 'Trip-Hop', 
                            28 => 'Vocal', 29 => 'Jazz+Funk', 30 => 'Fusion', 31 => 'Trance', 32 => 'Classical', 
                            33 => 'Instrumental', 34 => 'Acid', 35 => 'House', 36 => 'Game', 37 => 'Sound Clip', 
                            38 => 'Gospel', 39 => 'Noise', 40 => 'Alt Rock', 41 => 'Bass', 42 => 'Soul', 43 => 'Punk', 
                            44 => 'Space', 45 => 'Meditative', 46 => 'Instrumental Pop', 47 => 'Instrumental Rock', 
                            48 => 'Ethnic', 49 => 'Gothic', 50 => 'Darkwave', 51 => 'Techno-Industrial', 52 => 'Electronic', 
                            53 => 'Pop-Folk', 54 => 'Eurodance', 55 => 'Dream', 56 => 'Southern Rock', 57 => 'Comedy', 
                            58 => 'Cult', 59 => 'Gangsta', 60 => 'Top 40', 61 => 'Christian Rap', 62 => 'Pop/Funk', 
                            63 => 'Jungle', 64 => 'Native American', 65 => 'Cabaret', 66 => 'New Wave', 67 => 'Psychadelic', 
                            68 => 'Rave', 69 => 'Showtunes', 70 => 'Trailer', 71 => 'Lo-Fi', 72 => 'Tribal', 73 => 'Acid Punk', 
                            74 => 'Acid Jazz', 75 => 'Polka', 76 => 'Retro', 77 => 'Musical', 78 => 'Rock & Roll', 79 => 'Hard Rock'
                        ];
                        if (!empty($titlev1)) $meta['tag_title'] = $titlev1;
                        if (!empty($artistv1)) $meta['tag_artist'] = $artistv1;
                        if (!empty($albumv1)) $meta['tag_album'] = $albumv1;
                        if (!empty($yearv1)) $meta['tag_year'] = $yearv1;
                        if (isset($genres_map[$genre_byte])) $meta['tag_genre'] = $genres_map[$genre_byte];

                        $zeroByte = ord(substr($tag, 125, 1));
                        $trackByte = ord(substr($tag, 126, 1));
                        if ($zeroByte === 0 && $trackByte > 0) {
                            $meta['tag_track_number'] = str_pad($trackByte, 2, '0', STR_PAD_LEFT);
                        }
                    }
                }
                fclose($handle_v1);
            }
        }
        return $meta;
    };

    if (!function_exists('phplayer_is_unknown_artist')) {
        function phplayer_is_unknown_artist($name) {
            $clean = phplayer_lower($name);
            if ($clean === '') return true;
            $placeholders = [
                'artista desconhecido',
                'desconhecido',
                'unknown artist',
                'unknown',
                'sem artista',
                'no artist',
                'artist',
                'artista',
                'n/a',
                'na',
                '-',
                '--',
                '?',
                'none',
                'null'
            ];
            if (in_array($clean, $placeholders, true)) return true;
            if (preg_replace('/[\s\.\-\_\?\!]+/u', '', $clean) === '') return true;
            return false;
        }
    }

    if (!function_exists('phplayer_is_various_artists')) {
        function phplayer_is_various_artists($name) {
            $clean = phplayer_lower($name);
            if ($clean === '') return false;
            $various = [
                'varios artistas',
                'vários artistas',
                'various artists',
                'various artist',
                'various',
                'va',
                'v.a.',
                'v.a',
                'v/a',
                'multiplos artistas',
                'múltiplos artistas',
                'diversos artistas',
                'diverzos artistas',
                'coletanea',
                'coletânea',
                'compilation'
            ];
            return in_array($clean, $various, true);
        }
    }

    if (!function_exists('phplayer_is_various_artists_folder')) {
        function phplayer_is_various_artists_folder($filePathOrArtist) {
            $norm = phplayer_lower(str_replace('\\', '/', (string)$filePathOrArtist));
            if ($norm === '') return false;
            $norm = ltrim($norm, '/');
            if (stripos($norm, 'music/') === 0) {
                $norm = substr($norm, 6);
            }
            $segments = array_values(array_filter(explode('/', $norm), function($s) { return $s !== ''; }));
            foreach ($segments as $seg) {
                if (phplayer_is_various_artists($seg)) {
                    return true;
                }
            }
            return false;
        }
    }

    if (!function_exists('phplayer_album_cover_exclude_where')) {
        function phplayer_album_cover_exclude_where() {
            return "album IS NOT NULL AND album != '' AND album != 'Single' AND album != 'Álbum Desconhecido' "
                . "AND artist IS NOT NULL AND artist != '' AND artist != 'Artista Desconhecido' "
                . "AND LOWER(TRIM(artist)) NOT IN ('varios artistas', 'vários artistas', 'various artists', 'various artist', 'various', 'va', 'v.a.', 'v.a', 'v/a', 'diversos artistas', 'multiplos artistas', 'múltiplos artistas', 'coletanea', 'coletânea', 'compilation') "
                . "AND LOWER(REPLACE(COALESCE(file_name, ''), '\\\\', '/')) NOT LIKE 'varios artistas/%' "
                . "AND LOWER(REPLACE(COALESCE(file_name, ''), '\\\\', '/')) NOT LIKE 'vários artistas/%' "
                . "AND LOWER(REPLACE(COALESCE(file_name, ''), '\\\\', '/')) NOT LIKE '%/varios artistas/%' "
                . "AND LOWER(REPLACE(COALESCE(file_name, ''), '\\\\', '/')) NOT LIKE '%/vários artistas/%' "
                . "AND LOWER(REPLACE(COALESCE(file_name, ''), '\\\\', '/')) NOT LIKE 'various artists/%' "
                . "AND LOWER(REPLACE(COALESCE(file_name, ''), '\\\\', '/')) NOT LIKE '%/various artists/%' "
                . "AND (genre IS NULL OR LOWER(genre) NOT LIKE '%podcast%') "
                . "AND (cover_url IS NULL OR cover_url = '' OR cover_url LIKE '%unsplash.com%' OR cover_url LIKE '%images.unsplash%')";
        }
    }

    if (!function_exists('phplayer_is_unknown_album')) {
        function phplayer_is_unknown_album($name) {
            $clean = phplayer_lower($name);
            if ($clean === '') return true;
            $placeholders = [
                'álbum desconhecido',
                'album desconhecido',
                'desconhecido',
                'unknown album',
                'unknown',
                'sem álbum',
                'sem album',
                'no album',
                'album',
                'álbum',
                'n/a',
                'na',
                '-',
                '--',
                '?',
                'none',
                'null'
            ];
            if (in_array($clean, $placeholders, true)) return true;
            if (preg_replace('/[\s\.\-\_\?\!]+/u', '', $clean) === '') return true;
            return false;
        }
    }

    if (!function_exists('phplayer_extract_folder_meta')) {
        function phplayer_extract_folder_meta($relativePath) {
            $norm = trim(str_replace('\\', '/', (string)$relativePath), '/');
            if (stripos($norm, 'music/') === 0) {
                $norm = substr($norm, 6);
            }
            $parts = array_values(array_filter(explode('/', $norm), function($p) {
                return $p !== '' && $p !== '.';
            }));

            $artistFolder = null;
            $albumFolder = 'Single';

            if (count($parts) >= 3) {
                // Exemplo: /music/ARTISTA/ALBUM/musica.mp3
                $artistFolder = trim($parts[0]);
                $candidateAlbum = trim($parts[count($parts) - 2]);
                if (count($parts) >= 4 && preg_match('/^(cd|disc|disco|volume|vol\.?)\s*\d+$/i', $candidateAlbum)) {
                    $albumFolder = trim($parts[count($parts) - 3]);
                } else {
                    $albumFolder = $candidateAlbum;
                }
            } elseif (count($parts) === 2) {
                // Exemplo: /music/ARTISTA/musica.mp3
                $artistFolder = trim($parts[0]);
                $albumFolder = 'Single';
            }

            return [
                'artist' => ($artistFolder !== null && $artistFolder !== '') ? $artistFolder : 'Artista Desconhecido',
                'album' => ($albumFolder !== '') ? $albumFolder : 'Single',
                'has_artist_folder' => ($artistFolder !== null && $artistFolder !== '' && !phplayer_is_unknown_artist($artistFolder)),
                'parts_count' => count($parts)
            ];
        }
    }

    // Helper para verificar se o usuário atual é administrador
    if (!function_exists('is_admin_user')) {
        function is_admin_user($pdo) {
            global $current_username;
            $username = !empty($current_username) ? $current_username : (
                $_SERVER['HTTP_X_USERNAME'] ??
                $_POST['username'] ??
                $_POST['admin_username'] ??
                $_GET['admin_username'] ??
                $_GET['username'] ??
                ''
            );
            if (empty($username) && function_exists('getallheaders')) {
                $allHeaders = getallheaders();
                foreach ($allHeaders as $hKey => $hVal) {
                    if (strtolower($hKey) === 'x-username' && !empty($hVal)) {
                        $username = trim($hVal);
                        break;
                    }
                }
            }
            if (empty($username) && function_exists('apache_request_headers')) {
                $apacheHeaders = apache_request_headers();
                foreach ($apacheHeaders as $hKey => $hVal) {
                    if (strtolower($hKey) === 'x-username' && !empty($hVal)) {
                        $username = trim($hVal);
                        break;
                    }
                }
            }
            if (empty($username)) {
                $rawBody = @file_get_contents('php://input');
                if (!empty($rawBody)) {
                    $jsonParsed = @json_decode($rawBody, true);
                    if (is_array($jsonParsed)) {
                        $username = trim((string)($jsonParsed['admin_username'] ?? $jsonParsed['username'] ?? ''));
                    }
                }
            }
            if (empty($username)) {
                return false;
            }
            try {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE LOWER(username) = LOWER(?)");
                $stmt->execute([$username]);
                $role = $stmt->fetchColumn();
                if ($role !== false && strtolower((string)$role) === 'admin') {
                    return true;
                }
                if ($role === false && strtolower($username) === 'admin') {
                    return true;
                }
                return false;
            } catch (Throwable $e) {
                return strtolower($username) === 'admin';
            }
        }
    }

    // Definição de rotas restritas para administradores
    $admin_only_routes = [
        'scan_log', 'repair_db', 'export_backup', 'import_backup', 'scan',
        'delete_track', 'music_folders', 'delete_music_folder',
        'update_track_title', 'update_album_tracks_metadata', 'update_tracks_bulk',
        'upload_album_cover', 'upload_artist_banner',
        'dlna_status', 'toggle_dlna',
        'update_album_cover_url', 'update_artist_banner_url', 'clear_album_covers',
        'get_settings', 'save_settings', 'lastfm_sync', 'deezer_sync',
        'wikipedia_sync', 'google_images_sync', 'artist_covers_sync',
        'files_list', 'files_create_dir', 'files_delete', 'files_rename', 'files_upload',
        'podcasts_sync', 'podcasts_auto_sync', 'podcast_config', 'podcast_update_limit', 'podcast_delete'
    ];

    if (in_array($route, $admin_only_routes)) {
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
        }
    }

    if (!function_exists('phplayer_is_valid_remote_artist_image')) {
        function phplayer_is_valid_remote_artist_image($url) {
            $url = trim((string)$url);
            if ($url === '' || $url === 'no_photo' || $url === 'no_cover') return false;
            if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) return false;
            // Rejeitar placeholders vazios do Deezer, Last.fm e arquivos não-imagem
            if (
                strpos($url, '/images/artist//') !== false ||
                strpos($url, 'd41d8cd98f00b204e9800998ecf8427e') !== false ||
                strpos($url, '00000000000000000000000000000000') !== false ||
                strpos($url, '2a96cbd8b46e442fc41c2b86b821562f') !== false ||
                strpos($url, '2a96cbd8c706c450a33e3') !== false ||
                strpos($url, 'default_artist') !== false ||
                strpos($url, 'default_album') !== false ||
                preg_match('/\.(pdf|djvu|ogg|oga|flac|wav|webm|ogv|mid)(\?|$)/i', $url)
            ) {
                return false;
            }
            return true;
        }
    }

    if (!function_exists('phplayer_download_and_save_image')) {
        function phplayer_download_and_save_image($remoteUrl, $prefix = 'artist', $identifier = '') {
            $remoteUrl = trim((string)$remoteUrl);
            if ($remoteUrl === '' || $remoteUrl === 'no_photo' || $remoteUrl === 'no_cover') {
                return $remoteUrl;
            }
            // Se já for um arquivo local salvo no servidor, retorna direto
            if (strpos($remoteUrl, 'http://') !== 0 && strpos($remoteUrl, 'https://') !== 0) {
                return $remoteUrl;
            }

            if (!defined('IMAGES_DIR')) {
                define('IMAGES_DIR', __DIR__ . '/images/');
            }
            if (!is_dir(IMAGES_DIR)) {
                @mkdir(IMAGES_DIR, 0755, true);
            }

            $isWiki = (
                stripos($remoteUrl, 'wikipedia.org') !== false ||
                stripos($remoteUrl, 'wikidata.org') !== false ||
                stripos($remoteUrl, 'wikimedia.org') !== false
            );
            $ua = $isWiki
                ? ('PHPlayerMusicSystem/1.4.7 (https://github.com/leirson/phplayer; contact@phplayer.org) PHP/' . PHP_VERSION)
                : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

            $imgData = '';
            $contentType = '';

            if (function_exists('curl_init')) {
                $ch = @curl_init($remoteUrl);
                if ($ch) {
                    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    @curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    @curl_setopt($ch, CURLOPT_TIMEOUT, 12);
                    @curl_setopt($ch, CURLOPT_USERAGENT, $ua);
                    $res = @curl_exec($ch);
                    $httpCode = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $contentType = strtolower((string)@curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
                    @curl_close($ch);
                    if ($httpCode >= 200 && $httpCode < 300 && is_string($res) && strlen($res) > 200) {
                        $imgData = $res;
                    }
                }
            }

            if ($imgData === '' && ini_get('allow_url_fopen')) {
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => "User-Agent: {$ua}\r\n",
                        'timeout' => 12,
                        'follow_location' => 1
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                $res = @file_get_contents($remoteUrl, false, $ctx);
                if (is_string($res) && strlen($res) > 200) {
                    $imgData = $res;
                }
            }

            if ($imgData === '' || strlen($imgData) <= 200) {
                return '';
            }

            // Rejeitar páginas de erro HTML disfarçadas
            $headSample = strtolower(substr(ltrim($imgData), 0, 120));
            if (strpos($headSample, '<!doctype html') === 0 || strpos($headSample, '<html') === 0) {
                return '';
            }

            // Detectar extensão pelos magic bytes ou Content-Type
            $ext = 'jpg';
            if (strncmp($imgData, "\xFF\xD8\xFF", 3) === 0) {
                $ext = 'jpg';
            } elseif (strncmp($imgData, "\x89PNG\x0D\x0A\x1A\x0A", 8) === 0) {
                $ext = 'png';
            } elseif (strncmp($imgData, "RIFF", 4) === 0 && substr($imgData, 8, 4) === "WEBP") {
                $ext = 'webp';
            } elseif (strncmp($imgData, "GIF87a", 6) === 0 || strncmp($imgData, "GIF89a", 6) === 0) {
                $ext = 'gif';
            } elseif (strpos($headSample, '<svg') !== false || strpos($contentType, 'image/svg') !== false) {
                $ext = 'svg';
            } elseif (strpos($contentType, 'image/png') !== false) {
                $ext = 'png';
            } elseif (strpos($contentType, 'image/webp') !== false) {
                $ext = 'webp';
            } elseif (strpos($contentType, 'image/gif') !== false) {
                $ext = 'gif';
            }

            $hashKey = md5(mb_strtolower(trim($identifier !== '' ? $identifier : $remoteUrl)));
            // Remover arquivos antigos do mesmo artista/álbum para não acumular duplicatas em /images
            $oldMatches = @glob(IMAGES_DIR . $prefix . '_' . $hashKey . '_*.*');
            if (is_array($oldMatches)) {
                foreach ($oldMatches as $oldFile) {
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
            }

            $newFileName = $prefix . '_' . $hashKey . '_' . substr(md5($remoteUrl . uniqid('', true)), 0, 8) . '.' . $ext;
            $fullDest = IMAGES_DIR . $newFileName;
            if (@file_put_contents($fullDest, $imgData) !== false && file_exists($fullDest) && filesize($fullDest) > 200) {
                return 'images/' . $newFileName;
            }

            return '';
        }
    }

    if (!function_exists('phplayer_find_artist_cover_deezer_or_spotify')) {
        function phplayer_find_artist_cover_deezer_or_spotify($artist, $pdo = null) {
            $artist = trim((string)$artist);
            if ($artist === '') {
                return ['url' => '', 'source' => 'none'];
            }

            $httpGet = function($url, $headers = [], $timeout = 5) {
                $isWikiOrMb = (
                    stripos($url, 'wikipedia.org') !== false ||
                    stripos($url, 'wikidata.org') !== false ||
                    stripos($url, 'wikimedia.org') !== false ||
                    stripos($url, 'musicbrainz.org') !== false
                );
                $ua = $isWikiOrMb
                    ? ('PHPlayerMusicSystem/1.4.7 (https://github.com/leirson/phplayer; contact@phplayer.org) PHP/' . PHP_VERSION)
                    : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

                if (function_exists('curl_init')) {
                    $ch = @curl_init($url);
                    if ($ch) {
                        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                        @curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                        @curl_setopt($ch, CURLOPT_USERAGENT, $ua);
                        if (!empty($headers)) {
                            @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        }
                        $res = @curl_exec($ch);
                        $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        @curl_close($ch);
                        if ($code >= 200 && $code < 300 && !empty($res)) {
                            return (string)$res;
                        }
                    }
                }
                if (ini_get('allow_url_fopen')) {
                    $hdrStr = "User-Agent: {$ua}\r\n";
                    foreach ($headers as $h) {
                        $hdrStr .= $h . "\r\n";
                    }
                    $ctx = stream_context_create([
                        'http' => ['method' => 'GET', 'header' => $hdrStr, 'timeout' => $timeout, 'follow_location' => 1],
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                    ]);
                    $res = @file_get_contents($url, false, $ctx);
                    if (!empty($res)) return (string)$res;
                }
                return '';
            };

            // 1. PRIMEIRA OPÇÃO: DEEZER (primeira imagem de ARTISTA localizada no Deezer)
            $deezerQueries = [$artist];
            $cleanedArtist = trim((string)preg_replace('/\s+(?:feat\.?|ft\.?|part\.?|participação|with)\s+.*/iu', '', $artist));
            if ($cleanedArtist !== '' && mb_strtolower($cleanedArtist) !== mb_strtolower($artist)) {
                $deezerQueries[] = $cleanedArtist;
            }

            foreach ($deezerQueries as $dzQuery) {
                $deezerUrl = 'https://api.deezer.com/search/artist?q=' . urlencode($dzQuery) . '&limit=10';
                $resDz = $httpGet($deezerUrl, [], 5);
                if ($resDz !== '') {
                    $dataDz = @json_decode($resDz, true);
                    if (!empty($dataDz['data']) && is_array($dataDz['data'])) {
                        $normTarget = mb_strtolower($dzQuery);
                        // Priorizar correspondência exata de nome se houver, senão pegar a 1ª capa de artista válida da lista
                        foreach ($dataDz['data'] as $item) {
                            if (isset($item['type']) && $item['type'] !== 'artist') continue;
                            $itemName = mb_strtolower(trim((string)($item['name'] ?? '')));
                            $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                            if ($itemName === $normTarget && strpos($img, '/images/cover/') === false && phplayer_is_valid_remote_artist_image($img)) {
                                return ['url' => $img, 'source' => 'Deezer'];
                            }
                        }
                        foreach ($dataDz['data'] as $item) {
                            if (isset($item['type']) && $item['type'] !== 'artist') continue;
                            $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                            if (strpos($img, '/images/cover/') === false && phplayer_is_valid_remote_artist_image($img)) {
                                return ['url' => $img, 'source' => 'Deezer'];
                            }
                        }
                    }
                }
            }

            // 2. SEGUNDA OPÇÃO AUTOMÁTICA: SPOTIFY (se não localizar no Deezer, pegar a 1ª capa de ARTISTA do Spotify)
            // 2a) Tentar via Spotify Web API oficial caso credenciais estejam configuradas
            if ($pdo) {
                try {
                    $stmtSp = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('spotify_client_id', 'spotify_client_secret')");
                    $spCfg = $stmtSp ? $stmtSp->fetchAll(PDO::FETCH_KEY_PAIR) : [];
                    $spClientId = trim($spCfg['spotify_client_id'] ?? '');
                    $spClientSecret = trim($spCfg['spotify_client_secret'] ?? '');
                    if ($spClientId !== '' && $spClientSecret !== '' && function_exists('curl_init')) {
                        $chTok = @curl_init('https://accounts.spotify.com/api/token');
                        @curl_setopt($chTok, CURLOPT_RETURNTRANSFER, true);
                        @curl_setopt($chTok, CURLOPT_POST, true);
                        @curl_setopt($chTok, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
                        @curl_setopt($chTok, CURLOPT_SSL_VERIFYPEER, false);
                        @curl_setopt($chTok, CURLOPT_HTTPHEADER, [
                            'Authorization: Basic ' . base64_encode($spClientId . ':' . $spClientSecret),
                            'Content-Type: application/x-www-form-urlencoded'
                        ]);
                        @curl_setopt($chTok, CURLOPT_TIMEOUT, 4);
                        $tokRes = @json_decode(@curl_exec($chTok), true);
                        @curl_close($chTok);
                        if (!empty($tokRes['access_token'])) {
                            $spApiRaw = $httpGet(
                                'https://api.spotify.com/v1/search?q=' . urlencode($artist) . '&type=artist&limit=5',
                                ['Authorization: Bearer ' . $tokRes['access_token']],
                                5
                            );
                            if ($spApiRaw !== '') {
                                $spApiData = @json_decode($spApiRaw, true);
                                foreach (($spApiData['artists']['items'] ?? []) as $spArt) {
                                    foreach (($spArt['images'] ?? []) as $spImg) {
                                        if (!empty($spImg['url']) && phplayer_is_valid_remote_artist_image($spImg['url'])) {
                                            return ['url' => $spImg['url'], 'source' => 'Spotify'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Throwable $eSp) {}
            }

            $spotifyIds = [];

            // 2b) Busca direta por perfil de artista no Spotify (open.spotify.com/artist/{id}) via DuckDuckGo HTML
            $ddgUrl = 'https://html.duckduckgo.com/html/?q=' . urlencode('site:open.spotify.com/artist "' . $artist . '"');
            $ddgRaw = $httpGet($ddgUrl, ['Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8'], 4);
            if ($ddgRaw !== '') {
                $decodedDdg = rawurldecode($ddgRaw);
                if (preg_match_all('#open\.spotify\.com/(?:intl-[a-zA-Z-]+/)?artist/([a-zA-Z0-9]{22})#', $decodedDdg, $ddgMatches)) {
                    foreach ($ddgMatches[1] as $foundSpId) {
                        if (!in_array($foundSpId, $spotifyIds)) {
                            $spotifyIds[] = $foundSpId;
                        }
                        if (count($spotifyIds) >= 3) break;
                    }
                }
            }

            // 2c) Buscar Spotify Artist ID via Wikidata (P1902 = Spotify artist ID) e Wikipedia PT/EN
            if (empty($spotifyIds)) {
                $wdIds = [];
                foreach (['pt', 'en'] as $lang) {
                    $wdSearchUrl = 'https://www.wikidata.org/w/api.php?action=wbsearchentities&search=' . urlencode($artist) . '&language=' . $lang . '&format=json&limit=4';
                    $wdRaw = $httpGet($wdSearchUrl, [], 4);
                    if ($wdRaw !== '') {
                        $wdData = @json_decode($wdRaw, true);
                        foreach (($wdData['search'] ?? []) as $sItem) {
                            $qid = $sItem['id'] ?? '';
                            if ($qid !== '' && !in_array($qid, $wdIds)) {
                                $wdIds[] = $qid;
                            }
                        }
                    }
                    if (count($wdIds) >= 4) break;
                }

                if (empty($wdIds)) {
                    foreach (['pt', 'en'] as $wpLang) {
                        $wpUrl = 'https://' . $wpLang . '.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($artist) . '&gsrlimit=4&prop=pageprops&format=json';
                        $wpRaw = $httpGet($wpUrl, [], 4);
                        if ($wpRaw !== '') {
                            $wpData = @json_decode($wpRaw, true);
                            foreach (($wpData['query']['pages'] ?? []) as $pg) {
                                $qid = $pg['pageprops']['wikibase_item'] ?? '';
                                if ($qid !== '' && !in_array($qid, $wdIds)) {
                                    $wdIds[] = $qid;
                                }
                            }
                        }
                        if (!empty($wdIds)) break;
                    }
                }

                if (!empty($wdIds)) {
                    $wdEntUrl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&ids=' . implode('|', array_slice($wdIds, 0, 6)) . '&props=claims&format=json';
                    $wdEntRaw = $httpGet($wdEntUrl, [], 4);
                    if ($wdEntRaw !== '') {
                        $wdEntData = @json_decode($wdEntRaw, true);
                        foreach ($wdIds as $eid) {
                            $claims = $wdEntData['entities'][$eid]['claims'] ?? [];
                            foreach (($claims['P1902'] ?? []) as $c) {
                                $spId = $c['mainsnak']['datavalue']['value'] ?? '';
                                if ($spId !== '' && !in_array($spId, $spotifyIds)) {
                                    $spotifyIds[] = $spId;
                                }
                            }
                        }
                    }
                }
            }

            // 2d) Fallback adicional para descobrir o Spotify Artist ID via MusicBrainz URL relations
            if (empty($spotifyIds)) {
                $mbSearchUrl = 'https://musicbrainz.org/ws/2/artist/?query=artist:' . urlencode('"' . $artist . '"') . '&limit=2&fmt=json';
                $mbRaw = $httpGet($mbSearchUrl, [], 4);
                if ($mbRaw !== '') {
                    $mbData = @json_decode($mbRaw, true);
                    foreach (($mbData['artists'] ?? []) as $mbArt) {
                        $mbid = $mbArt['id'] ?? '';
                        if ($mbid === '') continue;
                        $mbRelRaw = $httpGet('https://musicbrainz.org/ws/2/artist/' . urlencode($mbid) . '?inc=url-rels&fmt=json', [], 4);
                        if ($mbRelRaw !== '') {
                            $mbRelData = @json_decode($mbRelRaw, true);
                            foreach (($mbRelData['relations'] ?? []) as $rel) {
                                $resUrl = $rel['url']['resource'] ?? '';
                                if (preg_match('#open\.spotify\.com/artist/([a-zA-Z0-9]+)#', $resUrl, $spm)) {
                                    if (!in_array($spm[1], $spotifyIds)) {
                                        $spotifyIds[] = $spm[1];
                                    }
                                }
                            }
                        }
                        if (!empty($spotifyIds)) break;
                    }
                }
            }

            // Obter a primeira imagem de ARTISTA do Spotify via endpoint oficial oEmbed
            foreach ($spotifyIds as $spId) {
                $oeRaw = $httpGet('https://open.spotify.com/oembed?url=https://open.spotify.com/artist/' . rawurlencode($spId), [], 4);
                if ($oeRaw !== '') {
                    $oeData = @json_decode($oeRaw, true);
                    $thumbUrl = $oeData['thumbnail_url'] ?? '';
                    if (phplayer_is_valid_remote_artist_image($thumbUrl)) {
                        return ['url' => $thumbUrl, 'source' => 'Spotify'];
                    }
                }
            }

            return ['url' => '', 'source' => 'none'];
        }
    }

    if (!function_exists('resolve_radio_stream_url')) {
        function resolve_radio_stream_url($url, $stationName = '') {
            $url = trim((string)$url);
            if (empty($url)) return '';

            // Mapa direto para os presets conhecidos do radios.com.br (que bloqueiam via Cloudflare 403)
            $knownMap = [
                '26885' => 'https://playerservices.streamtheworld.com/api/livestream-redirect/RADIO_KISSFM_ADP.aac',
                '93525' => 'https://playerservices.streamtheworld.com/api/livestream-redirect/RADIO_89FM_ADP.aac',
                '8800'  => 'https://stream.zeno.fm/vlcraijc6yiuv',
                '11927' => 'https://antenaone.crossradio.com.br/stream/1'
            ];
            foreach ($knownMap as $plId => $directStream) {
                if (strpos($url, '/' . $plId . '/') !== false) {
                    return $directStream;
                }
            }

            $lowerUrl = strtolower($url);
            $isPlaylistUrl = (strpos($lowerUrl, '.m3u') !== false && strpos($lowerUrl, '.m3u8') === false)
                || strpos($lowerUrl, '.pls') !== false
                || strpos($lowerUrl, '.asx') !== false
                || strpos($lowerUrl, 'radios.com.br') !== false;

            if ($isPlaylistUrl) {
                $text = false;
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
                    $text = @curl_exec($ch);
                    $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                    @curl_close($ch);
                    if ($httpCode >= 400) {
                        $text = false;
                    }
                }
                if ($text !== false && !empty($text)) {
                    $trimmedBody = ltrim($text);
                    // Ignorar páginas HTML de bloqueio (ex: Cloudflare)
                    if (stripos($trimmedBody, '<!doctype') !== 0 && stripos($trimmedBody, '<html') !== 0 && stripos($trimmedBody, '<head') !== 0) {
                        if (preg_match('/<ref\s+href\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i', $text, $m)) {
                            return trim($m[1]);
                        }
                        $lines = preg_split('/\r\n|\r|\n/', $text);
                        foreach ($lines as $line) {
                            $t = trim($line);
                            if ($t === '' || strpos($t, '#') === 0) continue;
                            if (stripos($t, 'file') === 0 && strpos($t, '=') !== false) {
                                $parts = explode('=', $t, 2);
                                $cand = trim($parts[1] ?? '');
                                if (strpos($cand, 'http://') === 0 || strpos($cand, 'https://') === 0) {
                                    return $cand;
                                }
                            } elseif (strpos($t, 'http://') === 0 || strpos($t, 'https://') === 0) {
                                return $t;
                            }
                        }
                    }
                }

                // Se for link do radios.com.br ou playlist bloqueada e tivermos o nome da rádio, consultar Radio Browser API
                $searchQuery = trim($stationName);
                if (empty($searchQuery)) {
                    if (stripos($url, 'kiss') !== false) $searchQuery = 'Kiss FM';
                    elseif (stripos($url, 'jovem') !== false) $searchQuery = 'Jovem Pan News';
                    elseif (stripos($url, 'antena') !== false) $searchQuery = 'Antena 1';
                    elseif (stripos($url, 'rock') !== false) $searchQuery = '89 FM';
                }
                if (!empty($searchQuery) && function_exists('curl_init')) {
                    $rbUrl = 'https://de1.api.radio-browser.info/json/stations/search?name=' . rawurlencode($searchQuery) . '&hidebroken=true&limit=3';
                    $chRb = curl_init($rbUrl);
                    curl_setopt($chRb, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chRb, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($chRb, CURLOPT_TIMEOUT, 5);
                    curl_setopt($chRb, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($chRb, CURLOPT_USERAGENT, 'PHPlayer/1.4');
                    $rbRaw = @curl_exec($chRb);
                    @curl_close($chRb);
                    if (!empty($rbRaw)) {
                        $rbArr = @json_decode($rbRaw, true);
                        if (is_array($rbArr) && !empty($rbArr[0])) {
                            $resolvedCandidate = trim($rbArr[0]['url_resolved'] ?? $rbArr[0]['url'] ?? '');
                            if (!empty($resolvedCandidate)) {
                                return $resolvedCandidate;
                            }
                        }
                    }
                }
            }

            return $url;
        }
    }

    if (!function_exists('clean_rss_text')) {
        function clean_rss_text($str) {
            if (empty($str)) return '';
            $s = str_replace(['<![CDATA[', ']]>'], '', (string)$str);
            $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s);
        }
    }

    if (!function_exists('syncSinglePodcastFeed')) {
        function syncSinglePodcastFeed($pdo, $feedUrl, $maxEpisodesRaw, $podcastNameOverride = '') {
            $feedUrl = trim((string)$feedUrl);
            if (empty($feedUrl)) {
                return ['error' => 'O endereço feedUrl é obrigatório.', 'success' => false];
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            @ignore_user_abort(true);

            $isAll = ($maxEpisodesRaw === 'all' || $maxEpisodesRaw === 0 || $maxEpisodesRaw === '0');
            $maxEpisodes = $isAll ? 0 : intval($maxEpisodesRaw);
            if (!$isAll && !in_array($maxEpisodes, [5, 10, 15])) {
                if ($maxEpisodes <= 5) $maxEpisodes = 5;
                else if ($maxEpisodes <= 10) $maxEpisodes = 10;
                else $maxEpisodes = 15;
            }

            try {
                @ini_set('max_execution_time', '300');
                @set_time_limit(300);
                @ini_set('memory_limit', '512M');
                @ini_set('pcre.backtrack_limit', '20000000');
                @ini_set('pcre.recursion_limit', '20000000');

                $syncStartTime = microtime(true);

                $xml = false;
                if (function_exists('curl_init')) {
                    $ch = curl_init($feedUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
                    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
                    curl_setopt($ch, CURLOPT_ENCODING, ""); // Descompacta gzip/deflate automaticamente
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    $xml = @curl_exec($ch);
                    @curl_close($ch);
                }

                if (($xml === false || empty($xml)) && ini_get('allow_url_fopen')) {
                    $opts = [
                        "http" => [
                            "method" => "GET",
                            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nAccept: application/rss+xml, application/xml, text/xml, */*\r\n",
                            "timeout" => 25,
                            "follow_location" => 1
                        ],
                        "ssl" => [
                            "verify_peer" => false,
                            "verify_peer_name" => false
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $xml = @file_get_contents($feedUrl, false, $context);
                }

                if (!empty($xml) && substr($xml, 0, 2) === "\x1f\x8b" && function_exists('gzdecode')) {
                    $xml = @gzdecode($xml);
                }

                if ($xml === false || empty($xml)) {
                    return ['error' => 'Falha ao conectar e ler o feed RSS do Podcast no PHP. Verifique se a URL está acessível.', 'success' => false];
                }

                // Extrair cabeçalho do canal apenas antes do primeiro <item> (evita erro de PCRE backtrack em XMLs de 5MB+)
                $firstItemPos = stripos($xml, '<item');
                if ($firstItemPos === false) {
                    $firstItemPos = stripos($xml, '<entry');
                }
                $channelHeaderXml = ($firstItemPos !== false) ? substr($xml, 0, $firstItemPos) : substr($xml, 0, 65536);

                $podcastName = trim((string)$podcastNameOverride);
                if (empty($podcastName) && preg_match('/<title[^>]*>([\s\S]*?)<\/title>/i', $channelHeaderXml, $tMatch)) {
                    $extracted = clean_rss_text($tMatch[1]);
                    if (!empty($extracted)) {
                        $podcastName = $extracted;
                    }
                }

                if (empty($podcastName)) {
                    $host = parse_url($feedUrl, PHP_URL_HOST);
                    $podcastName = $host ? "Podcast " . $host : "Podcast Desconhecido";
                }

                $safePodcastName = preg_replace('/[\/\\\?%*:|"<>]/', '-', $podcastName);
                $safePodcastName = trim($safePodcastName, " .-\t\n\r\0\x0B");
                if (empty($safePodcastName)) {
                    $safePodcastName = 'Podcast-' . substr(md5($feedUrl), 0, 8);
                }

                // Capa do canal (decodificando &amp; em atributos XML)
                $coverUrl = 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=450';
                if (preg_match('/<itunes:image\s+[^>]*href=["\']([^"\']+)["\']/i', $channelHeaderXml, $imgMatch)) {
                    $coverUrl = html_entity_decode(trim($imgMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                } else if (preg_match('/<image[^>]*>([\s\S]*?)<\/image>/i', $channelHeaderXml, $imgBlock)) {
                    if (preg_match('/<url[^>]*>([\s\S]*?)<\/url>/i', $imgBlock[1], $urlMatch)) {
                        $coverUrl = clean_rss_text($urlMatch[1]);
                    }
                } else if (preg_match('/<googleplay:image\s+[^>]*href=["\']([^"\']+)["\']/i', $channelHeaderXml, $gpMatch)) {
                    $coverUrl = html_entity_decode(trim($gpMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                // Autor / Artista do canal
                $authorName = $podcastName;
                if (preg_match('/<itunes:author[^>]*>([\s\S]*?)<\/itunes:author>/i', $channelHeaderXml, $authMatch)) {
                    $cleanedAuth = clean_rss_text($authMatch[1]);
                    if (!empty($cleanedAuth)) $authorName = $cleanedAuth;
                } else if (preg_match('/<author[^>]*>([\s\S]*?)<\/author>/i', $channelHeaderXml, $authMatch2)) {
                    $cleanedAuth2 = clean_rss_text($authMatch2[1]);
                    if (!empty($cleanedAuth2)) $authorName = $cleanedAuth2;
                }

                // Salvar feedUrl, limite, capa e artista nas configurações para nunca sumir
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        setting_key VARCHAR(191) UNIQUE,
                        setting_value LONGTEXT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $stmtSaveFeed = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmtSaveFeed->execute(["podcast_feed_" . $podcastName, $feedUrl]);
                    
                    $limitStr = $maxEpisodes === 0 ? "all" : strval($maxEpisodes);
                    $stmtSaveLimit = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmtSaveLimit->execute(["podcast_limit_" . $podcastName, $limitStr]);

                    $stmtSaveCover = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmtSaveCover->execute(["podcast_cover_" . $podcastName, $coverUrl]);

                    $stmtSaveArt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmtSaveArt->execute(["podcast_artist_" . $podcastName, $authorName]);
                } catch (Exception $e) {}

                // Diretório de armazenamento físico do podcast
                $podcastDir = (defined('PODCAST_DIR') && is_dir(PODCAST_DIR)) ? rtrim(PODCAST_DIR, '/\\') . '/' . $safePodcastName : rtrim(UPLOAD_DIR, '/\\') . '/podcast/' . $safePodcastName;
                if (!file_exists($podcastDir)) {
                    @mkdir($podcastDir, 0755, true);
                }

                // Extrair itens do RSS com stripos/substr (imune ao limite de 1MB do preg_match_all em feeds grandes)
                $targetCount = $maxEpisodes > 0 ? $maxEpisodes : 50;
                $itemsToProcess = [];
                $searchOffset = ($firstItemPos !== false) ? $firstItemPos : 0;
                $xmlLen = strlen($xml);

                while (count($itemsToProcess) < $targetCount && $searchOffset < $xmlLen) {
                    $itemStart = stripos($xml, '<item', $searchOffset);
                    $closeTag = '</item>';
                    if ($itemStart === false) {
                        $itemStart = stripos($xml, '<entry', $searchOffset);
                        $closeTag = '</entry>';
                    }
                    if ($itemStart === false) break;

                    // Garantir que é a tag <item> ou <item ...> e não <itunes:...>
                    $nextChar = substr($xml, $itemStart + 5, 1);
                    if ($closeTag === '</item>' && $nextChar !== '>' && $nextChar !== ' ' && $nextChar !== "\t" && $nextChar !== "\r" && $nextChar !== "\n") {
                        $searchOffset = $itemStart + 5;
                        continue;
                    }

                    $itemEnd = stripos($xml, $closeTag, $itemStart);
                    if ($itemEnd === false) break;

                    $itemsToProcess[] = substr($xml, $itemStart, $itemEnd - $itemStart);
                    $searchOffset = $itemEnd + strlen($closeTag);
                }

                $syncedEpisodesCount = 0;
                $downloadedFilesCount = 0;

                foreach ($itemsToProcess as $itemXml) {
                    $epTitleRaw = 'Episódio Desconhecido';
                    if (preg_match('/<title[^>]*>([\s\S]*?)<\/title>/i', $itemXml, $epTitleMatch)) {
                        $cleanedEpTitle = clean_rss_text($epTitleMatch[1]);
                        if (!empty($cleanedEpTitle)) {
                            $epTitleRaw = $cleanedEpTitle;
                        }
                    }
                    $cleanEpTitle = preg_replace('/[\/\\\?%*:|"<>]/', '-', $epTitleRaw);
                    $cleanEpTitle = trim($cleanEpTitle, " .-\t\n\r\0\x0B");
                    if (empty($cleanEpTitle)) {
                        $cleanEpTitle = 'episodio-' . substr(md5($itemXml), 0, 8);
                    }

                    $mediaUrl = '';
                    $enclosureLength = 0;
                    if (preg_match('/<enclosure\s+[^>]*url=["\']([^"\']+)["\'][^>]*>/i', $itemXml, $encMatch)) {
                        $mediaUrl = html_entity_decode(trim($encMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if (preg_match('/length=["\'](\d+)["\']/i', $encMatch[0], $lenMatch)) {
                            $enclosureLength = intval($lenMatch[1]);
                        }
                    } else if (preg_match('/<enclosure[\s\S]*?url=["\']([^"\']+)["\']/i', $itemXml, $fallbackEncMatch)) {
                        $mediaUrl = html_entity_decode(trim($fallbackEncMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    } else if (preg_match('/<media:content\s+[^>]*url=["\']([^"\']+)["\']/i', $itemXml, $mediaMatch)) {
                        $mediaUrl = html_entity_decode(trim($mediaMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }

                    if (empty($mediaUrl)) continue;

                    // Capa individual do episódio (se houver)
                    $epCoverUrl = $coverUrl;
                    if (preg_match('/<itunes:image\s+[^>]*href=["\']([^"\']+)["\']/i', $itemXml, $epImgMatch)) {
                        $candImg = html_entity_decode(trim($epImgMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if (!empty($candImg)) $epCoverUrl = $candImg;
                    }

                    $episodeFileName = substr($cleanEpTitle, 0, 180) . '.mp3';
                    $filePath = $podcastDir . '/' . $episodeFileName;
                    $altFilePath = rtrim(UPLOAD_DIR, '/\\') . '/podcast/' . $safePodcastName . '/' . $episodeFileName;

                    // Sempre salvar arquivo sidecar .url para permitir streaming imediato mesmo se o MP3 for grande demais para baixar de uma vez
                    @file_put_contents($filePath . '.url', $mediaUrl);

                    $fileSize = $enclosureLength > 0 ? $enclosureLength : 15728640;

                    if (file_exists($filePath) && filesize($filePath) > 1000) {
                        $fileSize = filesize($filePath);
                    } elseif (file_exists($altFilePath) && filesize($altFilePath) > 1000) {
                        $fileSize = filesize($altFilePath);
                    } else {
                        // Baixar fisicamente dentro da janela segura de tempo da requisição HTTP (máx 10s totais) para evitar 504 Gateway Timeout
                        $elapsed = microtime(true) - $syncStartTime;
                        $shouldAttemptDownload = ($elapsed < 10.0) && ($enclosureLength === 0 || $enclosureLength <= 25 * 1024 * 1024);
                        if ($shouldAttemptDownload && function_exists('curl_init')) {
                            $fp = @fopen($filePath, 'w+b');
                            if ($fp) {
                                $remainingTime = max(4, intval(12 - $elapsed));
                                $ch = curl_init($mediaUrl);
                                curl_setopt($ch, CURLOPT_FILE, $fp);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
                                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
                                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
                                curl_setopt($ch, CURLOPT_TIMEOUT, $remainingTime);
                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                $resExec = @curl_exec($ch);
                                $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                                @curl_close($ch);
                                @fclose($fp);

                                if ($resExec && $httpCode >= 200 && $httpCode < 400 && file_exists($filePath) && filesize($filePath) > 1000) {
                                    $fileSize = filesize($filePath);
                                    $downloadedFilesCount++;
                                } else {
                                    @unlink($filePath);
                                }
                            }
                        }
                    }

                    $durationInSeconds = 1800;
                    if (preg_match('/<itunes:duration[^>]*>([\s\S]*?)<\/itunes:duration>/i', $itemXml, $durMatch)) {
                        $durStr = clean_rss_text($durMatch[1]);
                        if (strpos($durStr, ':') !== false) {
                            $pts = explode(':', $durStr);
                            if (count($pts) === 3) {
                                $durationInSeconds = intval($pts[0]) * 3600 + intval($pts[1]) * 60 + intval($pts[2]);
                            } else if (count($pts) === 2) {
                                $durationInSeconds = intval($pts[0]) * 60 + intval($pts[1]);
                            }
                        } else {
                            $val = intval($durStr);
                            if ($val > 0) $durationInSeconds = $val;
                        }
                    }

                    $relativeFileName = 'podcast/' . $safePodcastName . '/' . $episodeFileName;

                    $stmtCheck = $pdo->prepare("SELECT id FROM songs WHERE file_name = ? OR (genre = 'Podcast' AND album = ? AND title = ?) LIMIT 1");
                    $stmtCheck->execute([$relativeFileName, $podcastName, $epTitleRaw]);
                    $exists = $stmtCheck->fetchColumn();

                    if ($exists) {
                        $stmtUpd = $pdo->prepare("UPDATE songs SET title = ?, artist = ?, album = ?, genre = 'Podcast', file_name = ?, duration = ?, file_size = ?, cover_url = ? WHERE id = ?");
                        $stmtUpd->execute([$epTitleRaw, $authorName ?: $podcastName, $podcastName, $relativeFileName, $durationInSeconds, $fileSize, $epCoverUrl, $exists]);
                    } else {
                        $stmtIns = $pdo->prepare("INSERT INTO songs (title, artist, album, genre, file_name, file_size, duration, cover_url) VALUES (?, ?, ?, 'Podcast', ?, ?, ?, ?)");
                        $stmtIns->execute([$epTitleRaw, $authorName ?: $podcastName, $podcastName, $relativeFileName, $fileSize, $durationInSeconds, $epCoverUrl]);
                    }
                    $syncedEpisodesCount++;
                }

                // Excluir episódios excedentes apenas se o limite for numérico e finito (> 0)
                if ($maxEpisodes > 0) {
                    try {
                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM songs WHERE genre = 'Podcast' AND album = ?");
                        $stmtCount->execute([$podcastName]);
                        $totalEpisodes = intval($stmtCount->fetchColumn());

                        if ($totalEpisodes > $maxEpisodes) {
                            $stmtAll = $pdo->prepare("SELECT id, file_name FROM songs WHERE genre = 'Podcast' AND album = ? ORDER BY created_at DESC, id DESC");
                            $stmtAll->execute([$podcastName]);
                            $allEps = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

                            $epsToDelete = array_slice($allEps, $maxEpisodes);
                            foreach ($epsToDelete as $epToDelete) {
                                $epId = $epToDelete['id'];
                                $epFileName = $epToDelete['file_name'];

                                $pathsToDelete = [
                                    UPLOAD_DIR . '/' . $epFileName,
                                    UPLOAD_DIR . $epFileName,
                                    __DIR__ . '/' . $epFileName,
                                    (defined('PODCAST_DIR') ? PODCAST_DIR . preg_replace('#^podcast/#', '', $epFileName) : '')
                                ];
                                foreach ($pathsToDelete as $pDel) {
                                    if (!empty($pDel)) {
                                        if (file_exists($pDel) && is_file($pDel)) @unlink($pDel);
                                        if (file_exists($pDel . '.url') && is_file($pDel . '.url')) @unlink($pDel . '.url');
                                    }
                                }

                                $stmtDel = $pdo->prepare("DELETE FROM songs WHERE id = ?");
                                $stmtDel->execute([$epId]);
                            }
                        }
                    } catch (Exception $delErr) {}
                }

                return [
                    'success' => true,
                    'podcastName' => $podcastName,
                    'downloaded' => $syncedEpisodesCount,
                    'downloadedFiles' => $downloadedFilesCount,
                    'episodeCount' => $syncedEpisodesCount,
                    'feedUrl' => $feedUrl,
                    'coverUrl' => $coverUrl
                ];
            } catch (Throwable $e) {
                return ['error' => 'Erro ao sincronizar podcast: ' . $e->getMessage(), 'success' => false];
            }
        }
    }

    if (!function_exists('get_dir_size_and_subdirs')) {
        function get_dir_size_and_subdirs($dirPath) {
            $totalSize = 0;
            $directSubdirs = 0;
            $totalSubdirs = 0;
            $files = 0;

            if (!is_dir($dirPath) || !is_readable($dirPath)) {
                return ['size' => 0, 'direct_subdirs' => 0, 'subdirs' => 0, 'total_subdirs' => 0, 'files' => 0];
            }

            // Contagem de subpastas diretas (nível imediato)
            $immediate = @scandir($dirPath);
            if ($immediate !== false) {
                foreach ($immediate as $item) {
                    if ($item === '.' || $item === '..') continue;
                    if (is_dir($dirPath . '/' . $item)) {
                        $directSubdirs++;
                    }
                }
            }

            // Cálculo recursivo do tamanho total e subpastas aninhadas
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isDir()) {
                        $totalSubdirs++;
                    } else if ($fileInfo->isFile()) {
                        $files++;
                        $totalSize += $fileInfo->getSize();
                    }
                }
            } catch (Throwable $e) {
                $totalSubdirs = $directSubdirs;
            }

            return [
                'size' => $totalSize,
                'direct_subdirs' => $directSubdirs,
                'subdirs' => $directSubdirs,
                'total_subdirs' => $totalSubdirs,
                'files' => $files
            ];
        }
    }

    switch ($route) {
    
        case 'list_users':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado para listar usuários']);
            exit;
        }
        $stmt = $pdo->query("SELECT username, role, can_download, can_share, dashboardLimit FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
        break;

    case 'create_user':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'ouvinte';
        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome e senha obrigatórios']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Erro ao criar usuário']);
        }
        break;

    case 'delete_user':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }
        $username = $_GET['username'] ?? '';
        if (strtolower($username) === 'admin') {
            http_response_code(400);
            echo json_encode(['error' => 'Admin não pode ser deletado']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$username]);
        echo json_encode(['success' => true]);
        break;
        
    case 'update_user':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            exit;
        }
        $username = $_GET['username'] ?? '';
        $can_download = $input['can_download'] ?? null;
        
        if ($can_download !== null) {
            $val = $can_download ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE users SET can_download = ? WHERE username = ?");
            $stmt->execute([$val, $username]);
        }
        
        $can_share = $input['can_share'] ?? null;
        if ($can_share !== null) {
            $val = $can_share ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE users SET can_share = ? WHERE username = ?");
            $stmt->execute([$val, $username]);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    case 'create_share':
        if (!is_admin_user($pdo)) {
            $stmt = $pdo->prepare("SELECT can_share FROM users WHERE username = ?");
            $stmt->execute([$current_username]);
            $u = $stmt->fetch();
            if (!$u || $u['can_share'] == 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Você não tem permissão para compartilhar.']);
                exit;
            }
        }
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                share_hash VARCHAR(100) PRIMARY KEY,
                target_type VARCHAR(50),
                target_id VARCHAR(500),
                target_name VARCHAR(255),
                created_by VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $shareCols = $pdo->query("SHOW COLUMNS FROM `shares`");
            $existingCols = $shareCols ? $shareCols->fetchAll(PDO::FETCH_COLUMN) : [];
            if (!empty($existingCols)) {
                if (!in_array('created_by', $existingCols)) {
                    $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL");
                }
                if (!in_array('expires_at', $existingCols)) {
                    $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL");
                }
                if (!in_array('target_name', $existingCols)) {
                    $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL");
                }
            }
        } catch (Throwable $e) {
            try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL"); } catch(Throwable $te) {}
            try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL"); } catch(Throwable $te) {}
            try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL"); } catch(Throwable $te) {}
        }
        
        $hash = substr(md5(uniqid(rand(), true)), 0, 12);
        $type = $input['type'] ?? 'album';
        $id = $input['id'] ?? '';
        $name = $input['name'] ?? 'Compartilhamento';
        $expires = $input['expires_days'] ?? 7; // default 7 days
        
        $expires_at = null;
        if ($expires > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires days"));
        }
        
        $stmt = $pdo->prepare("INSERT INTO shares (share_hash, target_type, target_id, target_name, created_by, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$hash, $type, $id, $name, $current_username, $expires_at]);
        } catch (Throwable $e) {
            exit(json_encode(['error' => $e->getMessage()]));
        }
        
        echo json_encode(['success' => true, 'hash' => $hash, 'url' => '?share=' . $hash]);
        break;

    case 'list_shares':
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                share_hash VARCHAR(100) PRIMARY KEY,
                target_type VARCHAR(50),
                target_id VARCHAR(500),
                target_name VARCHAR(255),
                created_by VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $shareCols = $pdo->query("SHOW COLUMNS FROM `shares`");
            $existingCols = $shareCols ? $shareCols->fetchAll(PDO::FETCH_COLUMN) : [];
            if (!empty($existingCols)) {
                if (!in_array('created_by', $existingCols)) {
                    $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL");
                }
                if (!in_array('expires_at', $existingCols)) {
                    $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL");
                }
                if (!in_array('target_name', $existingCols)) {
                    $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL");
                }
            }
        } catch (Throwable $e) {
            try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL"); } catch(Throwable $te) {}
            try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL"); } catch(Throwable $te) {}
            try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL"); } catch(Throwable $te) {}
        }

        try {
            if (!is_admin_user($pdo)) {
                $stmt = $pdo->prepare("SELECT * FROM shares WHERE created_by = ? ORDER BY created_at DESC");
                $stmt->execute([$current_username]);
            } else {
                $stmt = $pdo->query("SELECT * FROM shares ORDER BY created_at DESC");
            }
            $shares = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            echo json_encode($shares);
        } catch (Throwable $e) {
            echo json_encode([]);
        }
        break;

    case 'delete_share':
        $hash = $_GET['hash'] ?? '';
        if (!is_admin_user($pdo)) {
            $stmt = $pdo->prepare("DELETE FROM shares WHERE share_hash = ? AND created_by = ?");
            $stmt->execute([$hash, $current_username]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM shares WHERE share_hash = ?");
            $stmt->execute([$hash]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'update_share':
        $hash = $_GET['hash'] ?? '';
        $expires = $input['expires_days'] ?? 7;
        
        $expires_at = null;
        if ($expires > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires days"));
        }
        
        if (!is_admin_user($pdo)) {
            $stmt = $pdo->prepare("UPDATE shares SET expires_at = ? WHERE share_hash = ? AND created_by = ?");
            $stmt->execute([$expires_at, $hash, $current_username]);
        } else {
            $stmt = $pdo->prepare("UPDATE shares SET expires_at = ? WHERE share_hash = ?");
            $stmt->execute([$expires_at, $hash]);
        }
        echo json_encode(['success' => true]);
        break;

    

    

    

    
    case 'save_github_config':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $repo = trim($input['github_repo'] ?? 'leirson/phplayer');
        $branch = trim($input['github_branch'] ?? 'main');
        $token = trim($input['github_token'] ?? '');
        if ($repo === '') $repo = 'leirson/phplayer';
        if ($branch === '') $branch = 'main';
        // Remove full URL prefix if user pasted https://github.com/owner/repo
        $repo = preg_replace('#^https?://(www\.)?github\.com/#i', '', $repo);
        $repo = trim(preg_replace('#\.git$#i', '', $repo), '/');

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['github_repo', $repo]);
            $stmt->execute(['github_branch', $branch]);
            if ($token !== '__KEEP__') {
                $stmt->execute(['github_token', $token]);
            }
            echo json_encode(['success' => true, 'github_repo' => $repo, 'github_branch' => $branch, 'has_token' => ($token !== '' && $token !== '__KEEP__')]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    case 'check_update':
    case 'github_status':
    case 'check_github':
        if (!function_exists('phplayer_get_local_version')) {
            function phplayer_get_local_version() {
                $verFile = __DIR__ . '/version.php';
                if (file_exists($verFile)) {
                    $content = @file_get_contents($verFile);
                    if ($content && preg_match("/define\\(\\s*'PHPLAYER_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $content, $m)) {
                        return trim($m[1]);
                    }
                }
                return defined('PHPLAYER_VERSION') ? PHPLAYER_VERSION : '1.0.0';
            }
        }

        if (!function_exists('phplayer_get_github_cfg')) {
            function phplayer_get_github_cfg($pdo) {
                $cfg = [
                    'repo' => 'leirson/phplayer',
                    'branch' => 'main',
                    'token' => ''
                ];
                try {
                    if ($pdo) {
                        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('github_repo', 'github_branch', 'github_token')");
                        if ($stmt) {
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if ($row['setting_key'] === 'github_repo' && !empty(trim($row['setting_value']))) {
                                    $cfg['repo'] = trim($row['setting_value']);
                                } elseif ($row['setting_key'] === 'github_branch' && !empty(trim($row['setting_value']))) {
                                    $cfg['branch'] = trim($row['setting_value']);
                                } elseif ($row['setting_key'] === 'github_token' && !empty(trim($row['setting_value']))) {
                                    $cfg['token'] = trim($row['setting_value']);
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {}

                if (!empty($_GET['repo'])) {
                    $r = preg_replace('#^https?://(www\.)?github\.com/#i', '', trim($_GET['repo']));
                    $cfg['repo'] = trim(preg_replace('#\.git$#i', '', $r), '/');
                }
                if (!empty($_GET['branch'])) {
                    $cfg['branch'] = trim($_GET['branch']);
                }
                return $cfg;
            }
        }

        if (!function_exists('phplayer_github_http')) {
            function phplayer_github_http($url, $timeout = 10, $token = '', $headOrRange = false) {
                $t0 = microtime(true);
                $ua = 'PHPlayer-Updater/' . phplayer_get_local_version() . ' (PHP/' . PHP_VERSION . ')';
                $headers = [
                    'User-Agent: ' . $ua,
                    'Accept: application/vnd.github+json, text/plain, */*',
                    'Cache-Control: no-cache'
                ];
                if (!empty($token)) {
                    $headers[] = 'Authorization: Bearer ' . $token;
                }
                if ($headOrRange) {
                    $headers[] = 'Range: bytes=0-1023';
                }

                $result = [
                    'ok' => false,
                    'http_code' => 0,
                    'body' => '',
                    'latency_ms' => 0,
                    'method_used' => 'none',
                    'error' => '',
                    'rate_limit_remaining' => null,
                    'rate_limit_limit' => null
                ];

                // 1) Tentar via cURL (recomendado na maioria das hospedagens PHP)
                if (function_exists('curl_init')) {
                    $respHeaders = [];
                    $ch = @curl_init($url);
                    if ($ch) {
                        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        @curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                        @curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(8, $timeout));
                        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        @curl_setopt($ch, CURLOPT_USERAGENT, $ua);
                        @curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $headerLine) use (&$respHeaders) {
                            $len = strlen($headerLine);
                            $parts = explode(':', $headerLine, 2);
                            if (count($parts) === 2) {
                                $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                            }
                            return $len;
                        });

                        $body = @curl_exec($ch);
                        $httpCode = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $curlErr = @curl_error($ch);
                        @curl_close($ch);

                        $result['latency_ms'] = (int)round((microtime(true) - $t0) * 1000);
                        $result['method_used'] = 'curl';
                        $result['http_code'] = $httpCode;
                        if (isset($respHeaders['x-ratelimit-remaining'])) {
                            $result['rate_limit_remaining'] = (int)$respHeaders['x-ratelimit-remaining'];
                        }
                        if (isset($respHeaders['x-ratelimit-limit'])) {
                            $result['rate_limit_limit'] = (int)$respHeaders['x-ratelimit-limit'];
                        }

                        if ($body !== false && $httpCode >= 200 && $httpCode < 400) {
                            $result['ok'] = true;
                            $result['body'] = (string)$body;
                            return $result;
                        } else {
                            $result['body'] = is_string($body) ? $body : '';
                            $result['error'] = $curlErr ?: ('HTTP ' . $httpCode);
                            // Se recebeu resposta HTTP do GitHub (ex: 404, 403), não precisa repetir via stream
                            if ($httpCode >= 400) {
                                return $result;
                            }
                        }
                    }
                }

                // 2) Fallback para PHP Stream (file_get_contents) se allow_url_fopen estiver ativo
                if (ini_get('allow_url_fopen')) {
                    $t1 = microtime(true);
                    $ctx = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => implode("\r\n", $headers) . "\r\n",
                            'timeout' => $timeout,
                            'follow_location' => 1,
                            'max_redirects' => 5,
                            'ignore_errors' => true
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ]);

                    $body = @file_get_contents($url, false, $ctx);
                    $httpCode = 0;
                    if (isset($http_response_header) && is_array($http_response_header)) {
                        foreach ($http_response_header as $hdr) {
                            if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $hdr, $m)) {
                                $httpCode = (int)$m[1];
                            } elseif (stripos($hdr, 'x-ratelimit-remaining:') === 0) {
                                $result['rate_limit_remaining'] = (int)trim(substr($hdr, strlen('x-ratelimit-remaining:')));
                            } elseif (stripos($hdr, 'x-ratelimit-limit:') === 0) {
                                $result['rate_limit_limit'] = (int)trim(substr($hdr, strlen('x-ratelimit-limit:')));
                            }
                        }
                    }

                    $result['latency_ms'] = (int)round((microtime(true) - $t1) * 1000);
                    $result['method_used'] = 'stream';
                    $result['http_code'] = $httpCode;

                    if ($body !== false && ($httpCode === 0 || ($httpCode >= 200 && $httpCode < 400))) {
                        $result['ok'] = true;
                        $result['body'] = (string)$body;
                        $result['error'] = '';
                        return $result;
                    } else {
                        $result['body'] = is_string($body) ? $body : '';
                        if (empty($result['error'])) {
                            $result['error'] = $httpCode > 0 ? ('HTTP ' . $httpCode) : 'Falha na conexão via stream (verifique DNS/Firewall/SSL)';
                        }
                    }
                } elseif ($result['method_used'] === 'none') {
                    $result['error'] = 'Nem cURL nem allow_url_fopen estão habilitados neste servidor PHP.';
                }

                return $result;
            }
        }

        if (!function_exists('phplayer_parse_changelog')) {
            function phplayer_parse_changelog($rawContent) {
                if (!is_string($rawContent) || trim($rawContent) === '') {
                    return '';
                }
                // Suporta LF (\n) e CRLF (\r\n)
                if (preg_match('/\$changelog\s*=\s*<<<[\'"]?EOT[\'"]?\r?\n(.*?)\r?\nEOT;/s', $rawContent, $m)) {
                    return trim($m[1]);
                }
                // Fallback caso o arquivo seja texto puro
                $cleaned = preg_replace('/^<\?php\s*/i', '', $rawContent);
                return trim($cleaned);
            }
        }

        $current_version = phplayer_get_local_version();
        $ghCfg = phplayer_get_github_cfg($pdo);
        $repo = $ghCfg['repo'];
        $branch = $ghCfg['branch'];
        $token = $ghCfg['token'];
        $cacheBust = time();

        // 1. Buscar version.php remoto (Raw -> Fallback GitHub API Contents)
        $verUrl = "https://raw.githubusercontent.com/{$repo}/{$branch}/version.php?t={$cacheBust}";
        $verReq = phplayer_github_http($verUrl, 10, $token);
        $remote_version = $current_version;
        $version_found = false;

        if ($verReq['ok'] && preg_match("/define\\(\\s*'PHPLAYER_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $verReq['body'], $m)) {
            $remote_version = trim($m[1]);
            $version_found = true;
        } else {
            // Fallback via GitHub API Contents caso raw.githubusercontent.com seja bloqueado
            $apiVerUrl = "https://api.github.com/repos/{$repo}/contents/version.php?ref=" . rawurlencode($branch);
            $apiVerReq = phplayer_github_http($apiVerUrl, 10, $token);
            if ($apiVerReq['ok']) {
                $jsonVer = @json_decode($apiVerReq['body'], true);
                if (!empty($jsonVer['content'])) {
                    $decodedVer = base64_decode($jsonVer['content']);
                    if ($decodedVer && preg_match("/define\\(\\s*'PHPLAYER_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $decodedVer, $m)) {
                        $remote_version = trim($m[1]);
                        $version_found = true;
                        $verReq = $apiVerReq;
                    }
                }
            }
        }

        // 2. Buscar changelog.php remoto (Raw -> Fallback GitHub API Contents)
        $chgUrl = "https://raw.githubusercontent.com/{$repo}/{$branch}/changelog.php?t={$cacheBust}";
        $chgReq = phplayer_github_http($chgUrl, 10, $token);
        $changelog = '';
        $changelog_found = false;

        if ($chgReq['ok']) {
            $parsedChg = phplayer_parse_changelog($chgReq['body']);
            if ($parsedChg !== '') {
                $changelog = $parsedChg;
                $changelog_found = true;
            }
        }
        if (!$changelog_found) {
            $apiChgUrl = "https://api.github.com/repos/{$repo}/contents/changelog.php?ref=" . rawurlencode($branch);
            $apiChgReq = phplayer_github_http($apiChgUrl, 10, $token);
            if ($apiChgReq['ok']) {
                $jsonChg = @json_decode($apiChgReq['body'], true);
                if (!empty($jsonChg['content'])) {
                    $decodedChg = base64_decode($jsonChg['content']);
                    $parsedChg = phplayer_parse_changelog($decodedChg);
                    if ($parsedChg !== '') {
                        $changelog = $parsedChg;
                        $changelog_found = true;
                        $chgReq = $apiChgReq;
                    }
                }
            }
        }

        if (!$changelog_found) {
            // Fallback para changelog local se existir, avisando motivo
            if (file_exists(__DIR__ . '/changelog.php')) {
                $localChgRaw = @file_get_contents(__DIR__ . '/changelog.php');
                $localChg = phplayer_parse_changelog($localChgRaw);
                $changelog = "[Aviso: Exibindo changelog local — GitHub retornou " . ($chgReq['error'] ?: 'erro') . "]\n\n" . $localChg;
            } else {
                $changelog = "Não foi possível carregar o changelog do GitHub (" . ($chgReq['error'] ?: 'Sem resposta') . ").";
            }
        }

        // Se a rota for check_update simples, já retorna os dados com telemetria de conexão
        if ($route === 'check_update') {
            echo json_encode([
                'success' => $version_found,
                'github_connected' => $version_found,
                'current_version' => $current_version,
                'remote_version' => $remote_version,
                'has_update' => version_compare($remote_version, $current_version, '>'),
                'changelog' => $changelog,
                'repo' => $repo,
                'branch' => $branch,
                'http_code' => $verReq['http_code'],
                'latency_ms' => $verReq['latency_ms'],
                'method_used' => $verReq['method_used'],
                'error' => $version_found ? null : ($verReq['error'] ?: 'Não foi possível ler version.php no repositório GitHub.')
            ]);
            exit;
        }

        // Rota completa de diagnóstico: github_status / check_github
        $apiRepoUrl = "https://api.github.com/repos/{$repo}";
        $repoReq = phplayer_github_http($apiRepoUrl, 10, $token);
        $repoInfo = $repoReq['ok'] ? (@json_decode($repoReq['body'], true) ?: []) : [];

        $apiCommitUrl = "https://api.github.com/repos/{$repo}/commits/" . rawurlencode($branch);
        $commitReq = phplayer_github_http($apiCommitUrl, 10, $token);
        $commitData = $commitReq['ok'] ? (@json_decode($commitReq['body'], true) ?: []) : [];
        $latestCommit = null;
        if (!empty($commitData['sha'])) {
            $latestCommit = [
                'sha' => substr($commitData['sha'], 0, 7),
                'full_sha' => $commitData['sha'],
                'message' => trim( strtok($commitData['commit']['message'] ?? 'Sem mensagem', "\n") ),
                'author' => $commitData['commit']['author']['name'] ?? ($commitData['author']['login'] ?? 'Desconhecido'),
                'date' => $commitData['commit']['author']['date'] ?? null,
                'html_url' => $commitData['html_url'] ?? "https://github.com/{$repo}/commit/{$commitData['sha']}"
            ];
        }

        $zipUrl = "https://github.com/{$repo}/archive/refs/heads/{$branch}.zip";
        $zipReq = phplayer_github_http($zipUrl, 12, $token, true);

        $envInfo = [
            'php_version' => PHP_VERSION,
            'php_ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'curl_enabled' => function_exists('curl_init'),
            'allow_url_fopen' => (bool)ini_get('allow_url_fopen'),
            'openssl_enabled' => extension_loaded('openssl'),
            'zip_enabled' => class_exists('ZipArchive'),
            'dir_writable' => is_writable(__DIR__),
            'temp_writable' => is_writable(sys_get_temp_dir())
        ];

        $checks = [
            'github_api' => [
                'name' => 'GitHub REST API (api.github.com)',
                'url' => $apiRepoUrl,
                'ok' => $repoReq['ok'],
                'http_code' => $repoReq['http_code'],
                'latency_ms' => $repoReq['latency_ms'],
                'method' => $repoReq['method_used'],
                'error' => $repoReq['error'],
                'details' => $repoReq['ok']
                    ? ('Repositório ' . (($repoInfo['private'] ?? false) ? 'Privado' : 'Público') . ' • Branch padrão: ' . ($repoInfo['default_branch'] ?? $branch))
                    : ('Falha: ' . ($repoReq['error'] ?: 'Inacessível'))
            ],
            'github_raw_version' => [
                'name' => 'Versão Remota (raw.githubusercontent.com)',
                'url' => "https://raw.githubusercontent.com/{$repo}/{$branch}/version.php",
                'ok' => $version_found,
                'http_code' => $verReq['http_code'],
                'latency_ms' => $verReq['latency_ms'],
                'method' => $verReq['method_used'],
                'error' => $verReq['error'],
                'details' => $version_found ? ("Versão detectada: v{$remote_version}") : ('Erro: ' . ($verReq['error'] ?: 'version.php inválido'))
            ],
            'github_raw_changelog' => [
                'name' => 'Changelog Remoto (changelog.php)',
                'url' => "https://raw.githubusercontent.com/{$repo}/{$branch}/changelog.php",
                'ok' => $changelog_found,
                'http_code' => $chgReq['http_code'],
                'latency_ms' => $chgReq['latency_ms'],
                'method' => $chgReq['method_used'],
                'error' => $chgReq['error'],
                'details' => $changelog_found ? ('Carregado (' . strlen($changelog) . ' bytes)') : ('Erro: ' . ($chgReq['error'] ?: 'changelog.php inacessível'))
            ],
            'github_commits' => [
                'name' => 'Histórico de Commits (' . $branch . ')',
                'url' => $apiCommitUrl,
                'ok' => ($latestCommit !== null),
                'http_code' => $commitReq['http_code'],
                'latency_ms' => $commitReq['latency_ms'],
                'method' => $commitReq['method_used'],
                'error' => $commitReq['error'],
                'details' => $latestCommit ? ('Commit #' . $latestCommit['sha'] . ' por ' . $latestCommit['author']) : ('Erro: ' . ($commitReq['error'] ?: 'Sem dados de commit'))
            ],
            'github_zip_archive' => [
                'name' => 'Pacote de Atualização (.zip)',
                'url' => $zipUrl,
                'ok' => $zipReq['ok'],
                'http_code' => $zipReq['http_code'],
                'latency_ms' => $zipReq['latency_ms'],
                'method' => $zipReq['method_used'],
                'error' => $zipReq['error'],
                'details' => $zipReq['ok'] ? 'Download ZIP acessível (Codeload GitHub OK)' : ('Erro: ' . ($zipReq['error'] ?: 'Falha ao testar pacote ZIP'))
            ]
        ];

        $allGithubOk = $version_found && ($repoReq['ok'] || $changelog_found);
        $avgLatency = (int)round(($verReq['latency_ms'] + $chgReq['latency_ms'] + $repoReq['latency_ms']) / 3);

        echo json_encode([
            'success' => $allGithubOk,
            'overall_status' => $allGithubOk ? 'connected' : ($version_found || $repoReq['ok'] ? 'partial' : 'offline'),
            'repo' => $repo,
            'branch' => $branch,
            'has_token' => !empty($token),
            'current_version' => $current_version,
            'remote_version' => $remote_version,
            'has_update' => version_compare($remote_version, $current_version, '>'),
            'changelog' => $changelog,
            'latest_commit' => $latestCommit,
            'rate_limit' => [
                'remaining' => $repoReq['rate_limit_remaining'] ?? $commitReq['rate_limit_remaining'],
                'limit' => $repoReq['rate_limit_limit'] ?? $commitReq['rate_limit_limit']
            ],
            'avg_latency_ms' => $avgLatency,
            'method_used' => $verReq['method_used'],
            'checks' => $checks,
            'environment' => $envInfo,
            'checked_at' => date('Y-m-d H:i:s')
        ]);
        exit;

    case 'do_update':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
        }
        if (!class_exists('ZipArchive')) {
            exit(json_encode(['success' => false, 'error' => 'A extensão ZipArchive não está habilitada no PHP.']));
        }

        $repo = 'leirson/phplayer';
        $branch = 'main';
        $token = '';
        try {
            if ($pdo) {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('github_repo', 'github_branch', 'github_token')");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if ($row['setting_key'] === 'github_repo' && !empty(trim($row['setting_value']))) $repo = trim($row['setting_value']);
                        if ($row['setting_key'] === 'github_branch' && !empty(trim($row['setting_value']))) $branch = trim($row['setting_value']);
                        if ($row['setting_key'] === 'github_token' && !empty(trim($row['setting_value']))) $token = trim($row['setting_value']);
                    }
                }
            }
        } catch (Throwable $e) {}

        $zip_url = "https://github.com/{$repo}/archive/refs/heads/{$branch}.zip";
        $temp_zip = sys_get_temp_dir() . '/phplayer_update_' . time() . '.zip';

        $zip_content = false;
        $download_err = '';
        if (function_exists('curl_init')) {
            $ch = @curl_init($zip_url);
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            @curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $hdrs = ['User-Agent: PHPlayer-Updater/1.0'];
            if (!empty($token)) $hdrs[] = 'Authorization: Bearer ' . $token;
            @curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
            $zip_content = @curl_exec($ch);
            $http_code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $download_err = @curl_error($ch);
            @curl_close($ch);
            if ($http_code < 200 || $http_code >= 400) {
                $zip_content = false;
                if (!$download_err) $download_err = "HTTP {$http_code}";
            }
        }

        if (!$zip_content && ini_get('allow_url_fopen')) {
            $hdrStr = "User-Agent: PHPlayer-Updater/1.0\r\n";
            if (!empty($token)) $hdrStr .= "Authorization: Bearer {$token}\r\n";
            $ctx = stream_context_create([
                'http' => ['timeout' => 60, 'header' => $hdrStr, 'follow_location' => 1],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            $zip_content = @file_get_contents($zip_url, false, $ctx);
        }

        if (!$zip_content || strlen($zip_content) < 1000) {
            exit(json_encode([
                'success' => false,
                'error' => 'Não foi possível baixar o pacote de atualização do GitHub (' . ($download_err ?: 'pacote vazio ou inacessível') . ').'
            ]));
        }

        @file_put_contents($temp_zip, $zip_content);

        $zip = new ZipArchive;
        if ($zip->open($temp_zip) === TRUE) {
            $temp_extract_dir = sys_get_temp_dir() . '/phplayer_extract_' . time();
            @mkdir($temp_extract_dir, 0755, true);
            $zip->extractTo($temp_extract_dir);
            $zip->close();

            $extracted_folders = glob($temp_extract_dir . '/*', GLOB_ONLYDIR);
            $source_dir = $temp_extract_dir;
            if (is_array($extracted_folders) && count($extracted_folders) === 1) {
                $source_dir = $extracted_folders[0];
            }

            if (!function_exists('copy_dir_update')) {
                function copy_dir_update($src, $dst, $baseSrc = null) {
                    if ($baseSrc === null) $baseSrc = $src;
                    $protected_dirs = ['music', 'podcast', 'covers', 'uploads', 'images', '.git'];
                    $dir = @opendir($src);
                    if (!$dir) return;
                    if (!is_dir($dst)) @mkdir($dst, 0755, true);
                    while (false !== ($file = readdir($dir))) {
                        if ($file === '.' || $file === '..') continue;
                        $srcFile = $src . '/' . $file;
                        $dstFile = $dst . '/' . $file;
                        $relPath = ltrim(str_replace($baseSrc, '', $srcFile), '/\\');

                        if ($relPath === 'config.php' && file_exists($dstFile)) {
                            continue;
                        }
                        $skip = false;
                        foreach ($protected_dirs as $pdir) {
                            if ($relPath === $pdir || strpos($relPath, $pdir . '/') === 0 || strpos($relPath, $pdir . '\\') === 0) {
                                if (file_exists($dstFile)) {
                                    $skip = true;
                                    break;
                                }
                            }
                        }
                        if ($skip) continue;

                        if (is_dir($srcFile)) {
                            copy_dir_update($srcFile, $dstFile, $baseSrc);
                        } else {
                            @copy($srcFile, $dstFile);
                        }
                    }
                    closedir($dir);
                }
            }

            copy_dir_update($source_dir, __DIR__);

            @unlink($temp_zip);
            if (!function_exists('delete_dir_update')) {
                function delete_dir_update($dir) {
                    if (empty($dir) || !file_exists($dir)) return;
                    if (!is_dir($dir)) { @unlink($dir); return; }
                    $files = @scandir($dir);
                    if ($files === false) return;
                    foreach (array_diff($files, ['.', '..']) as $file) {
                        $path = $dir . '/' . $file;
                        is_dir($path) ? delete_dir_update($path) : @unlink($path);
                    }
                    if (file_exists($dir) && is_dir($dir)) {
                        @rmdir($dir);
                    }
                }
            }
            delete_dir_update($temp_extract_dir);

            $new_ver = '1.0.0';
            if (file_exists(__DIR__ . '/version.php')) {
                $vc = @file_get_contents(__DIR__ . '/version.php');
                if ($vc && preg_match("/define\\(\\s*'PHPLAYER_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $vc, $vm)) {
                    $new_ver = trim($vm[1]);
                }
            }

            echo json_encode(['success' => true, 'new_version' => $new_ver]);
        } else {
            @unlink($temp_zip);
            echo json_encode(['success' => false, 'error' => 'Falha ao extrair o arquivo de atualização (.zip).']);
        }
        exit;

    case 'proxy_radio':
        $url = trim($_GET['url'] ?? '');
        $stationName = trim($_GET['name'] ?? '');
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            exit("URL inválida");
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        // Resolver links de playlists (.m3u, .pls, .asx) e links do radios.com.br
        $url = resolve_radio_stream_url($url, $stationName);

        while (ob_get_level()) {
            @ob_end_clean();
        }

        @ini_set('zlib.output_compression', 'Off');
        @ini_set('output_buffering', 'Off');
        @ini_set('implicit_flush', 1);
        @ob_implicit_flush(true);
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);

        header("Access-Control-Allow-Origin: *");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("X-Accel-Buffering: no");
        header("Connection: close");

        if (function_exists('curl_init')) {
            $headersSent = false;
            $detectedContentType = 'audio/mpeg';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_HTTP200ALIASES, ['ICY 200 OK', 'ICY 400 Server Full']);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: */*',
                'Icy-MetaData: 0'
            ]);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $headerLine) use (&$detectedContentType) {
                $len = strlen($headerLine);
                if (stripos($headerLine, 'content-type:') === 0) {
                    $ctVal = trim(substr($headerLine, 13));
                    if (stripos($ctVal, 'audio/') !== false || stripos($ctVal, 'application/ogg') !== false || stripos($ctVal, 'video/mp2t') !== false) {
                        $detectedContentType = $ctVal;
                    }
                }
                return $len;
            });
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$headersSent, &$detectedContentType) {
                if (connection_aborted()) {
                    return 0;
                }
                $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                // Ignorar corpos HTML/texto de redirecionamentos (301/302/307/308) para não corromper o cabeçalho do áudio
                if ($httpCode >= 300 && $httpCode < 400) {
                    return strlen($data);
                }
                if ($httpCode >= 400) {
                    return 0;
                }
                if (!$headersSent) {
                    $headersSent = true;
                    header("Content-Type: " . $detectedContentType);
                }
                echo $data;
                @flush();
                return strlen($data);
            });
            @curl_exec($ch);
            @curl_close($ch);
            exit;
        }

        header("Content-Type: audio/mpeg");
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nAccept: */*\r\nIcy-MetaData: 0\r\n",
                "timeout" => 15,
                "follow_location" => 1
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ];
        
        $context = stream_context_create($opts);
        $fp = @fopen($url, 'rb', false, $context);
        if (!$fp) {
            http_response_code(502);
            exit("Falha ao abrir stream de rádio");
        }

        while (!feof($fp) && !connection_aborted()) {
            $buffer = fread($fp, 8192);
            if ($buffer === false) {
                break;
            }
            echo $buffer;
            flush();
        }
        fclose($fp);
        exit;

    case 'login':
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            echo json_encode([
                'success' => true,
                'username' => $user['username'],
                'role' => $user['role'],
                'theme' => $user['theme'] ?? 'default',
                'sidebarBg' => $user['sidebarBg'] ?? '',
                'footerBg' => $user['footerBg'] ?? '',
                'topBg' => $user['topBg'] ?? ''
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário ou senha incorretos']);
        }
        break;

    case 'users':
        $requestUser = $_SERVER['HTTP_X_USERNAME'] ?? $_GET['admin_username'] ?? '';
        if ($method === 'GET' || $method === 'POST' || $method === 'DELETE') {
            if (!is_admin_user($pdo)) {
                http_response_code(403);
                exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
            }
        }

        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT id, username, role, theme, sidebarBg, footerBg, topBg, can_download, dashboardLimit FROM users ORDER BY username ASC");
            echo json_encode($stmt->fetchAll());
        } elseif ($method === 'POST') {
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $role = trim($input['role'] ?? 'ouvinte');
            $theme = trim($input['theme'] ?? 'default');
            if (empty($username) || empty($password)) {
                http_response_code(400);
                exit(json_encode(['error' => 'Nome e senha vazios']));
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, theme) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $theme]);
            echo json_encode(['success' => true]);
        } elseif ($method === 'PUT') {
            $username = trim($_GET['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $role = trim($input['role'] ?? '');
            $theme = trim($input['theme'] ?? '');

            // Verificação de autorização para edição de perfis
            $isEditingSelf = (strtolower($username) === strtolower($requestUser));
            $isAdmin = is_admin_user($pdo);

            if (!$isEditingSelf && !$isAdmin) {
                http_response_code(403);
                exit(json_encode(['error' => 'Acesso negado: Você não pode editar dados de outro usuário.', 'success' => false]));
            }

            if (!empty($role) && !$isAdmin) {
                http_response_code(403);
                exit(json_encode(['error' => 'Acesso negado: Apenas administradores podem modificar cargos de usuários.', 'success' => false]));
            }

            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE username = ?")->execute([$hash, $username]);
            }
            if ($role) {
                $pdo->prepare("UPDATE users SET role = ? WHERE username = ?")->execute([$role, $username]);
            }
            if ($theme) {
                $pdo->prepare("UPDATE users SET theme = ? WHERE username = ?")->execute([$theme, $username]);
            }
            if (isset($input['can_download']) && $isAdmin) {
                $can_download = $input['can_download'] ? 1 : 0;
                $pdo->prepare("UPDATE users SET can_download = ? WHERE username = ?")->execute([$can_download, $username]);
            }
            if (isset($input['dashboardLimit'])) {
                $limit = (int)$input['dashboardLimit'];
                $pdo->prepare("UPDATE users SET dashboardLimit = ? WHERE username = ?")->execute([$limit, $username]);
            }
            if (isset($input['sidebarBg'])) {
                $sidebarBg = trim($input['sidebarBg']);
                $pdo->prepare("UPDATE users SET sidebarBg = ?, footerBg = ?, topBg = ? WHERE username = ?")->execute([$sidebarBg, $sidebarBg, $sidebarBg, $username]);
            }
            echo json_encode(['success' => true]);
        } elseif ($method === 'DELETE') {
            $username = trim($_GET['username'] ?? '');
            if ($username === 'admin') {
                http_response_code(400);
                exit(json_encode(['error' => 'O administrador master não pode ser excluído']));
            }
            $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);
            echo json_encode(['success' => true]);
        }
        break;

    case 'favorites':
        $username = trim($_GET['username'] ?? '');
        $stmt = $pdo->prepare("SELECT song_id FROM favorites WHERE username = ?");
        $stmt->execute([$username]);
        echo json_encode(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        break;

    case 'favorites_toggle':
        $username = trim($input['username'] ?? '');
        $trackId = intval($input['trackId'] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE username = ? AND song_id = ?");
        $stmt->execute([$username, $trackId]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->prepare("DELETE FROM favorites WHERE username = ? AND song_id = ?")->execute([$username, $trackId]);
        } else {
            $pdo->prepare("INSERT INTO favorites (username, song_id) VALUES (?, ?)")->execute([$username, $trackId]);
        }
        $stmt = $pdo->prepare("SELECT song_id FROM favorites WHERE username = ?");
        $stmt->execute([$username]);
        echo json_encode(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        break;

    case 'scan_log':
        $logFile = __DIR__ . '/music_scan.log';
        if ($method === 'DELETE') {
            @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "=== LOG REINICIADO PELO ADMINISTRADOR ===
");
            echo json_encode(['success' => true, 'message' => 'Log limpo com sucesso.']);
        } else {
            $content = '';
            if (file_exists($logFile)) {
                $content = @file_get_contents($logFile);
            }
            if (empty($content)) {
                $content = "Nenhum log de varredura gerado ainda.";
            }
            echo json_encode([
                'success' => true,
                'content' => $content,
                'last_modified' => file_exists($logFile) ? date('Y-m-d H:i:s', filemtime($logFile)) : null
            ]);
        }
        break;

    case 'repair_db':
        try {
            $tables = get_songs_tables($pdo);
            foreach ($tables as $t) {
                // Remove NULL characters (CHAR(0))
                $pdo->query("UPDATE `" . $t . "` SET title = REPLACE(title, CHAR(0), '') WHERE title LIKE '%\0%'");
                $pdo->query("UPDATE `" . $t . "` SET genre = REPLACE(genre, CHAR(0), '') WHERE genre LIKE '%\0%'");
                $pdo->query("UPDATE `" . $t . "` SET artist = REPLACE(artist, CHAR(0), '') WHERE artist LIKE '%\0%'");
                $pdo->query("UPDATE `" . $t . "` SET album = REPLACE(album, CHAR(0), '') WHERE album LIKE '%\0%'");

                // Remove interrogaçoes
                $pdo->query("UPDATE `" . $t . "` SET title = REPLACE(title, '?', '') WHERE title LIKE '%?%'");
                $pdo->query("UPDATE `" . $t . "` SET genre = REPLACE(genre, '?', '') WHERE genre LIKE '%?%'");
                $pdo->query("UPDATE `" . $t . "` SET artist = REPLACE(artist, '?', '') WHERE artist LIKE '%?%'");
                $pdo->query("UPDATE `" . $t . "` SET album = REPLACE(album, '?', '') WHERE album LIKE '%?%'");
                
                // Corrige generos em branco
                $pdo->query("UPDATE `" . $t . "` SET genre = 'Desconhecido' WHERE genre IS NULL OR TRIM(genre) = ''");

                // Limpa 'no_cover' das URLs de capa
                $pdo->query("UPDATE `" . $t . "` SET cover_url = REPLACE(cover_url, 'no_cover', '')");

                // Recupera nome do artista (e álbum) pela pasta /music/ARTISTA/ALBUM quando não identificado
                $stmtUnk = $pdo->query("SELECT id, artist, album, file_name, genre FROM `" . $t . "`");
                if ($stmtUnk) {
                    while ($rowUnk = $stmtUnk->fetch(PDO::FETCH_ASSOC)) {
                        $fNameUnk = ltrim(str_replace('\\', '/', trim($rowUnk['file_name'] ?? '')), '/');
                        $gUnk = strtolower(trim($rowUnk['genre'] ?? ''));
                        if ($fNameUnk === '' || strpos($fNameUnk, 'http') === 0 || strpos($fNameUnk, 'music_') === 0 || stripos($fNameUnk, 'podcast/') === 0 || strpos($gUnk, 'podcast') !== false) {
                            continue;
                        }
                        $fMetaUnk = phplayer_extract_folder_meta($fNameUnk);
                        if ($fMetaUnk['has_artist_folder']) {
                            $upFields = [];
                            $upVals = [];
                            if ((phplayer_is_unknown_artist($rowUnk['artist'] ?? '') || phplayer_is_various_artists($rowUnk['artist'] ?? '')) && ($rowUnk['artist'] ?? '') !== $fMetaUnk['artist']) {
                                $upFields[] = "artist = ?";
                                $upVals[] = $fMetaUnk['artist'];
                            }
                            if ((phplayer_is_unknown_album($rowUnk['album'] ?? '') || ($rowUnk['album'] ?? '') === 'Single') && $fMetaUnk['album'] !== 'Single') {
                                $upFields[] = "album = ?";
                                $upVals[] = $fMetaUnk['album'];
                            }
                            if (!empty($upFields)) {
                                $upVals[] = $rowUnk['id'];
                                $pdo->prepare("UPDATE `" . $t . "` SET " . implode(', ', $upFields) . " WHERE id = ?")->execute($upVals);
                            }
                        }
                    }
                }
            }

            echo json_encode(['status' => 'ok', 'message' => 'Reparação realizada com sucesso! Limpeza concluída de caracteres nulos e falhas de encoding.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao realizar a reparação: ' . $e->getMessage()]);
        }
        break;

    case 'register_play':
        $id = intval($input['id'] ?? $_GET['id'] ?? 0);
        if ($id > 0) {
            $table = get_song_table_by_id($pdo, $id);
            $stmt = $pdo->prepare("UPDATE `" . $table . "` SET play_count = play_count + 1, last_played = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
        }
        break;

    case 'export_backup':
        $backup = [
            'playlists' => $pdo->query("SELECT * FROM playlists")->fetchAll(PDO::FETCH_ASSOC),
            'playlist_songs' => $pdo->query("SELECT * FROM playlist_songs")->fetchAll(PDO::FETCH_ASSOC),
            'favorites' => $pdo->query("SELECT * FROM favorites")->fetchAll(PDO::FETCH_ASSOC),
            'settings' => $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC)
        ];
        header('Content-disposition: attachment; filename=phplayer_backup_' . date('Y-m-d') . '.json');
        header('Content-type: application/json');
        echo json_encode($backup, JSON_PRETTY_PRINT);
        exit;

    case 'import_backup':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Método POST requerido']));
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || (!isset($data['playlists']) && !isset($data['favorites']))) {
            http_response_code(400);
            exit(json_encode(['error' => 'Dados de backup inválidos ou vazios']));
        }
        
        $pdo->beginTransaction();
        try {
            if (isset($data['playlists']) && is_array($data['playlists'])) {
                // Remove playlists antigas para reimportar limpo
                $pdo->exec("DELETE FROM playlist_songs");
                $pdo->exec("DELETE FROM playlists");
                
                foreach ($data['playlists'] as $pl) {
                    $stmt = $pdo->prepare("INSERT INTO playlists (id, name, username, created_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$pl['id'], $pl['name'], $pl['username'], $pl['created_at'] ?? date('Y-m-d H:i:s')]);
                }
            }
            if (isset($data['playlist_songs']) && is_array($data['playlist_songs'])) {
                foreach ($data['playlist_songs'] as $ps) {
                    // Verifica integridade da referência
                    $table = get_song_table_by_id($pdo, $ps['song_id']);
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM `" . $table . "` WHERE id = ?");
                    $chk->execute([$ps['song_id']]);
                    if ($chk->fetchColumn() > 0) {
                        $stmt = $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_id, position) VALUES (?, ?, ?)");
                        $stmt->execute([$ps['playlist_id'], $ps['song_id'], $ps['position']]);
                    }
                }
            }
            if (isset($data['favorites']) && is_array($data['favorites'])) {
                $pdo->exec("DELETE FROM favorites");
                foreach ($data['favorites'] as $fav) {
                    $table = get_song_table_by_id($pdo, $fav['song_id']);
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM `" . $table . "` WHERE id = ?");
                    $chk->execute([$fav['song_id']]);
                    if ($chk->fetchColumn() > 0) {
                        $stmt = $pdo->prepare("INSERT INTO favorites (username, song_id) VALUES (?, ?)");
                        $stmt->execute([$fav['username'], $fav['song_id']]);
                    }
                }
            }
            if (isset($data['settings']) && is_array($data['settings'])) {
                foreach ($data['settings'] as $st) {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->execute([$st['setting_key'], $st['setting_value']]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Backup restaurado com sucesso!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao restaurar backup: ' . $e->getMessage()]);
        }
        break;

    case 'scan':
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @ignore_user_abort(true);

        $reqSubpath = isset($_REQUEST['path']) ? trim($_REQUEST['path']) : (isset($_REQUEST['folder']) ? trim($_REQUEST['folder']) : '');
        $reqArtist = isset($_REQUEST['artist']) ? trim($_REQUEST['artist']) : '';
        
        $baseMusicDir = realpath(__DIR__ . '/music');
        if (!$baseMusicDir) {
            @mkdir(__DIR__ . '/music', 0755, true);
            $baseMusicDir = realpath(__DIR__ . '/music');
        }
        if (!$baseMusicDir || !is_dir($baseMusicDir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Diretório /music não existe ou não pôde ser criado no servidor.']);
            break;
        }
        
        $targetScanDir = $baseMusicDir;
        $isSelectiveScan = false;
        $scanTargetDisplay = '/music';

        if (!empty($reqSubpath) && $reqSubpath !== '.' && $reqSubpath !== 'root' && $reqSubpath !== 'music') {
            $cleanSub = trim($reqSubpath, '/\\');
            if (strpos($cleanSub, 'music/') === 0) {
                $cleanSub = substr($cleanSub, 6);
            } elseif ($cleanSub === 'music') {
                $cleanSub = '';
            }

            if (!empty($cleanSub)) {
                $resolved = realpath($baseMusicDir . '/' . $cleanSub);
                if ($resolved !== false && (strpos($resolved, $baseMusicDir . DIRECTORY_SEPARATOR) === 0 || strpos($resolved, $baseMusicDir . '/') === 0 || $resolved === $baseMusicDir)) {
                    $targetScanDir = $resolved;
                    $isSelectiveScan = true;
                    $scanTargetDisplay = '/music/' . $cleanSub;
                }
            }
        } elseif (!empty($reqArtist)) {
            $resolvedArtist = realpath($baseMusicDir . '/' . trim($reqArtist, '/\\'));
            if ($resolvedArtist !== false && (strpos($resolvedArtist, $baseMusicDir . DIRECTORY_SEPARATOR) === 0 || strpos($resolvedArtist, $baseMusicDir . '/') === 0)) {
                $targetScanDir = $resolvedArtist;
                $isSelectiveScan = true;
                $scanTargetDisplay = '/music/' . trim($reqArtist, '/\\');
            }
        }

        $musicDir = rtrim(str_replace('\\', '/', $baseMusicDir), '/') . '/';
        
        write_scan_log("", !$isSelectiveScan);
        write_scan_log("Iniciando varredura " . ($isSelectiveScan ? "específica na pasta '$scanTargetDisplay'..." : "global no diretório /music..."));
        
        $newTracks = 0;
        $removedTracksCount = 0;
        $fileErrorsCount = 0;
        $coverOptions = [
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400',
            'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400',
            'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=400'
        ];

        // 1. Clean up removed files and folders from the database + index existing rows in memory (evita N+1 SELECTs)
        write_scan_log("Etapa 1: Limpeza de músicas cujos arquivos físicos foram removidos (" . ($isSelectiveScan ? "somente para o escopo '$scanTargetDisplay'" : "todo o catálogo") . ").");
        $allSongs = [];
        $existingSongsByFile = [];
        $tables = get_songs_tables($pdo);
        write_scan_log("Tabelas de músicas particionadas identificadas: " . implode(', ', $tables));
        
        $selectivePrefix = $isSelectiveScan ? trim(str_replace(DIRECTORY_SEPARATOR, '/', substr($targetScanDir, strlen($baseMusicDir))), '/') : '';
        
        foreach ($tables as $t) {
            try {
                $stmtAll = $pdo->query("SELECT id, title, artist, album, album_year, genre, track_number, file_name FROM `" . $t . "`");
                if ($stmtAll) {
                    while ($song = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
                        $song['_table'] = $t;
                        $allSongs[] = $song;
                        $normKey = ltrim(str_replace('\\', '/', (string)$song['file_name']), '/');
                        if ($normKey !== '' && !isset($existingSongsByFile[$normKey])) {
                            $existingSongsByFile[$normKey] = $song;
                        }
                    }
                }
            } catch (Throwable $tblExc) {
                write_scan_log("Aviso ao consultar tabela '$t': " . $tblExc->getMessage());
            }
        }
        
        write_scan_log("Total de " . count($allSongs) . " registros encontrados no banco. Validando correspondência no disco...");
        foreach ($allSongs as $song) {
            $fName = (string)($song['file_name'] ?? '');
            $isPodcast = (isset($song['genre']) && strcasecmp((string)$song['genre'], 'Podcast') === 0) || stripos($fName, 'podcast/') === 0;
            if ($fName === '' || strpos($fName, 'http') === 0 || strpos($fName, 'music_') === 0 || $isPodcast) {
                continue;
            }

            if ($isSelectiveScan && !empty($selectivePrefix)) {
                $fNameNorm = trim(str_replace('\\', '/', $fName), '/');
                if ($fNameNorm !== $selectivePrefix && strpos($fNameNorm, $selectivePrefix . '/') !== 0) {
                    continue;
                }
            }

            $diskPath = $musicDir . ltrim(str_replace('\\', '/', $fName), '/');
            if (!@file_exists($diskPath)) {
                try {
                    $t = $song['_table'];
                    $pdo->prepare("DELETE FROM `" . $t . "` WHERE id = ?")->execute([$song['id']]);
                    $removedTracksCount++;
                    $normKey = ltrim(str_replace('\\', '/', $fName), '/');
                    unset($existingSongsByFile[$normKey]);
                    write_scan_log("Aviso: Arquivo físico '$fName' ausente. Registro excluído do BD (Tabela '$t', ID: {$song['id']}).");
                } catch (Throwable $delExc) {
                    write_scan_log("Aviso ao remover registro órfão ID {$song['id']}: " . $delExc->getMessage());
                }
            }
        }
        write_scan_log("Concluido! Músicas órfãs removidas do Banco: $removedTracksCount registros.");

        // 1.5 Passagem única recursiva no disco (evita percorrer /music duas vezes e cacheia ID3 em memória)
        write_scan_log("Etapa 1.5: Analisando árvore física '" . ($isSelectiveScan ? $scanTargetDisplay : "/music") . "' e identificando pastas com múltiplos artistas...");
        $folderArtistsMap = [];
        $scannedAudioFiles = [];
        $mp3MetaCache = [];
        $totalPhysicalFiles = 0;
        $processedFiles = 0;
        $ignoredExtSampleCount = 0;

        if (@file_exists($targetScanDir) && @is_dir($targetScanDir)) {
            try {
                $dirFlags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;
                $dirIter = new RecursiveDirectoryIterator($targetScanDir, $dirFlags);
                $filterIter = class_exists('RecursiveCallbackFilterIterator')
                    ? new RecursiveCallbackFilterIterator($dirIter, function($current) {
                        $bName = $current->getFilename();
                        if ($bName === '.' || $bName === '..' || $bName === '@eaDir' || $bName === '#recycle' || $bName === 'lost+found' || $bName === '.DS_Store') {
                            return false;
                        }
                        if (stripos($bName, '.Trash') === 0 || (substr($bName, 0, 2) === '._')) {
                            return false;
                        }
                        return true;
                    })
                    : $dirIter;
                $preIt = new RecursiveIteratorIterator($filterIter, RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);

                foreach ($preIt as $pInfo) {
                    try {
                        if (!$pInfo->isFile()) {
                            continue;
                        }
                        $pName = $pInfo->getFilename();
                        if (substr($pName, 0, 2) === '._' || $pName === '.DS_Store' || $pName === 'Thumbs.db') {
                            continue;
                        }
                        $pAbs = str_replace('\\', '/', $pInfo->getPathname());
                        if (strpos($pAbs, '/@eaDir/') !== false || strpos($pAbs, '/#recycle/') !== false) {
                            continue;
                        }
                        $totalPhysicalFiles++;

                        $pRel = (strpos($pAbs, $musicDir) === 0)
                            ? ltrim(substr($pAbs, strlen($musicDir)), '/')
                            : ltrim(str_replace('\\', '/', substr($pAbs, strlen($baseMusicDir))), '/');
                        if ($pRel === '' || stripos($pRel, 'podcast/') === 0) {
                            continue;
                        }

                        $pExt = strtolower(pathinfo($pName, PATHINFO_EXTENSION));
                        if (!in_array($pExt, ['mp3', 'wav', 'ogg', 'aac', 'm4a'], true)) {
                            if ($ignoredExtSampleCount < 30) {
                                write_scan_log("Filtro: Arquivo '$pRel' ignorado. Extensão (.$pExt) não suportada.");
                                $ignoredExtSampleCount++;
                            }
                            continue;
                        }

                        $processedFiles++;
                        $dPath = dirname($pRel);
                        $pFolderMeta = phplayer_extract_folder_meta($pRel);
                        $pArtist = $pFolderMeta['artist'];

                        $pMeta = null;
                        if ($pExt === 'mp3') {
                            try {
                                $pMeta = $getMp3Meta($pAbs);
                                $mp3MetaCache[$pAbs] = $pMeta;
                                if (!empty($pMeta['tag_artist']) && !phplayer_is_unknown_artist($pMeta['tag_artist']) && !phplayer_is_various_artists($pMeta['tag_artist'])) {
                                    $pArtist = $pMeta['tag_artist'];
                                }
                            } catch (Throwable $id3PreExc) {
                                $mp3MetaCache[$pAbs] = [];
                            }
                        }

                        if (!isset($folderArtistsMap[$dPath])) {
                            $folderArtistsMap[$dPath] = [];
                        }
                        $folderArtistsMap[$dPath][] = $pArtist;

                        $fileSizeSafe = 0;
                        try { $fileSizeSafe = (int)$pInfo->getSize(); } catch (Throwable $szExc) { $fileSizeSafe = @filesize($pAbs) ?: 0; }

                        $scannedAudioFiles[] = [
                            'abs' => $pAbs,
                            'rel' => $pRel,
                            'ext' => $pExt,
                            'dir' => $dPath,
                            'size' => $fileSizeSafe,
                            'folder_meta' => $pFolderMeta
                        ];
                    } catch (Throwable $itemExc) {
                        $fileErrorsCount++;
                    }
                }
            } catch (Throwable $preScanExc) {
                write_scan_log("ERRO AO PERCORRER DIRETÓRIO FÍSICO: " . $preScanExc->getMessage());
            }
        } else {
            write_scan_log("Erro: Diretório '$scanTargetDisplay' não pôde ser listado ou não existe.");
        }
        write_scan_log("Etapa 1.5 Concluída! Pastas mapeadas: " . count($folderArtistsMap) . ", arquivos de áudio válidos: $processedFiles (de $totalPhysicalFiles arquivos físicos).");

        // Pré-calcular mapa de pastas com múltiplos artistas
        $folderMultiArtistMap = [];
        foreach ($folderArtistsMap as $dPathKey => $artistsArr) {
            $uniqueFolderArtists = array_values(array_unique(array_filter(array_map(function($a) {
                return phplayer_lower($a);
            }, $artistsArr), function($v) { return $v !== ''; })));
            $isMulti = false;
            if (count($uniqueFolderArtists) > 1) {
                $sampleRel = $dPathKey !== '.' ? ($dPathKey . '/track.mp3') : 'track.mp3';
                $sampleMeta = phplayer_extract_folder_meta($sampleRel);
                $folderArtistLower = phplayer_lower($sampleMeta['artist']);
                $allMatch = $sampleMeta['has_artist_folder'] && $folderArtistLower !== '' && count(array_filter($uniqueFolderArtists, function($ua) use ($folderArtistLower) {
                    return $ua !== '' && (strpos($ua, $folderArtistLower) !== false || strpos($folderArtistLower, $ua) !== false);
                })) === count($uniqueFolderArtists);
                if (!$allMatch) {
                    $isMulti = true;
                }
            }
            $folderMultiArtistMap[$dPathKey] = $isMulti;
        }

        // 2. Processar arquivos encontrados na passagem única
        write_scan_log("Etapa 2: Sincronizando $processedFiles arquivos de áudio com o banco de dados...");
        $insertTable = get_insert_song_table($pdo);
        $insertTableCount = 0;
        try {
            $insertTableCount = intval($pdo->query("SELECT COUNT(*) FROM `" . $insertTable . "`")->fetchColumn());
        } catch (Throwable $cntExc) {}

        foreach ($scannedAudioFiles as $fileEntry) {
            $absolutePath = $fileEntry['abs'];
            $relativePath = $fileEntry['rel'];
            $ext = $fileEntry['ext'];
            $dPath = $fileEntry['dir'];
            $folderMeta = $fileEntry['folder_meta'];
            $isFolderMultiArtist = !empty($folderMultiArtistMap[$dPath]);

            $existingRow = $existingSongsByFile[$relativePath] ?? null;
            $file_exists_in_db = ($existingRow !== null);

            if ($file_exists_in_db) {
                try {
                    $existing_table = $existingRow['_table'];
                    $existing_id = $existingRow['id'];
                    $existing_title = (string)($existingRow['title'] ?? '');
                    $existing_artist = (string)($existingRow['artist'] ?? '');
                    $existing_album = (string)($existingRow['album'] ?? '');
                    $existing_year = $existingRow['album_year'] ?? null;
                    $existing_genre = $existingRow['genre'] ?? null;
                    $existing_track_number = $existingRow['track_number'] ?? null;

                    $updateClauses = [];
                    $updateParams = [];
                    $meta = ($ext === 'mp3' && isset($mp3MetaCache[$absolutePath])) ? $mp3MetaCache[$absolutePath] : null;

                    if ($ext === 'mp3' && is_array($meta) && !empty($meta)) {
                        if (!empty($meta['tag_track_number'])) {
                            $meta_track_num = (int)$meta['tag_track_number'];
                            $title_needs_update = false;
                            $new_title = trim($existing_title);

                            if (empty($existing_track_number) && $meta_track_num > 0) {
                                $updateClauses[] = "track_number = ?";
                                $updateParams[] = $meta_track_num;
                            }

                            $pattern1 = '/^' . preg_quote(str_pad($meta_track_num, 2, '0', STR_PAD_LEFT), '/') . '\s*-\s*/';
                            $pattern2 = '/^' . preg_quote((string)$meta_track_num, '/') . '\s*-\s*/';

                            if (preg_match($pattern1, $new_title)) {
                                $new_title = preg_replace($pattern1, '', $new_title);
                                $title_needs_update = true;
                            } elseif (preg_match($pattern2, $new_title)) {
                                $new_title = preg_replace($pattern2, '', $new_title);
                                $title_needs_update = true;
                            }

                            if ($title_needs_update && trim($new_title) !== '') {
                                $updateClauses[] = "title = ?";
                                $updateParams[] = phplayer_safe_db_str($new_title, 250);
                            }
                        }

                        if (empty($existing_year) && !empty($meta['tag_year'])) {
                            $updateClauses[] = "album_year = ?";
                            $updateParams[] = phplayer_safe_db_str($meta['tag_year'], 10);
                        }

                        if ((empty($existing_genre) || $existing_genre === 'Local Scan') && !empty($meta['tag_genre']) && $meta['tag_genre'] !== 'Local Scan') {
                            $updateClauses[] = "genre = ?";
                            $updateParams[] = phplayer_safe_db_str($meta['tag_genre'], 95);
                        }
                    }

                    if (phplayer_is_unknown_artist($existing_artist) || phplayer_is_various_artists($existing_artist) || ($isFolderMultiArtist && $folderMeta['has_artist_folder'] && $existing_artist !== $folderMeta['artist'])) {
                        $resolvedArtist = '';
                        if ($folderMeta['has_artist_folder'] && ($isFolderMultiArtist || phplayer_is_various_artists($existing_artist))) {
                            $resolvedArtist = $folderMeta['artist'];
                        } elseif ($meta && !empty($meta['tag_artist']) && !phplayer_is_unknown_artist($meta['tag_artist']) && !phplayer_is_various_artists($meta['tag_artist'])) {
                            $resolvedArtist = $meta['tag_artist'];
                        } elseif ($folderMeta['has_artist_folder']) {
                            $resolvedArtist = $folderMeta['artist'];
                        }
                        $resolvedArtistSafe = phplayer_safe_db_str($resolvedArtist, 250);
                        if ($resolvedArtistSafe !== '' && !phplayer_is_unknown_artist($resolvedArtistSafe) && $resolvedArtistSafe !== $existing_artist) {
                            $updateClauses[] = "artist = ?";
                            $updateParams[] = $resolvedArtistSafe;
                        }
                    }

                    if (phplayer_is_unknown_album($existing_album) || ($existing_album === 'Single' && $folderMeta['album'] !== 'Single')) {
                        $resolvedAlbum = '';
                        if ($folderMeta['album'] !== 'Single') {
                            $resolvedAlbum = $folderMeta['album'];
                        } elseif ($meta && !empty($meta['tag_album']) && !phplayer_is_unknown_album($meta['tag_album'])) {
                            $resolvedAlbum = $meta['tag_album'];
                        }
                        $resolvedAlbumSafe = phplayer_safe_db_str($resolvedAlbum, 250);
                        if ($resolvedAlbumSafe !== '' && !phplayer_is_unknown_album($resolvedAlbumSafe) && $resolvedAlbumSafe !== $existing_album) {
                            $updateClauses[] = "album = ?";
                            $updateParams[] = $resolvedAlbumSafe;
                        }
                    }

                    if (count($updateClauses) > 0) {
                        $updateParams[] = $existing_id;
                        $pdo->prepare("UPDATE `" . $existing_table . "` SET " . implode(", ", $updateClauses) . " WHERE id = ?")->execute($updateParams);
                        write_scan_log("Atualizado '$relativePath' (Tabela: '$existing_table').");
                    }
                } catch (Throwable $updExc) {
                    $fileErrorsCount++;
                    write_scan_log("Aviso ao atualizar '$relativePath': " . $updExc->getMessage());
                }
            } else {
                try {
                    $artist = $folderMeta['artist'];
                    if ($isFolderMultiArtist) {
                        $album = ($folderMeta['album'] !== 'Single') ? $folderMeta['album'] : ($dPath !== '.' ? basename($dPath) : 'Single');
                        $artist = $folderMeta['has_artist_folder'] ? $folderMeta['artist'] : 'Varios Artistas';
                    } else {
                        $album = $folderMeta['album'];
                    }

                    $lastName = basename($relativePath);
                    $title = pathinfo($lastName, PATHINFO_FILENAME);
                    $title = str_replace(['_', '-'], ' ', $title);
                    $randomCover = $coverOptions[array_rand($coverOptions)];

                    $genre = 'Local Scan';
                    $duration = 210;
                    $meta = ($ext === 'mp3' && isset($mp3MetaCache[$absolutePath])) ? $mp3MetaCache[$absolutePath] : [];
                    $albumYear = null;

                    if ($ext === 'mp3' && is_array($meta) && !empty($meta)) {
                        if (!empty($meta['tag_genre'])) {
                            $genre = $meta['tag_genre'];
                        }
                        if (!empty($meta['tag_duration'])) {
                            $duration = (int)$meta['tag_duration'];
                        }
                        if (!empty($meta['tag_title'])) {
                            $title = $meta['tag_title'];
                        }

                        if (!empty($meta['tag_track_number'])) {
                            $meta_track_num = (int)$meta['tag_track_number'];
                            $pattern1 = '/^' . preg_quote(str_pad($meta_track_num, 2, '0', STR_PAD_LEFT), '/') . '\s*-\s*/';
                            $pattern2 = '/^' . preg_quote((string)$meta_track_num, '/') . '\s*-\s*/';
                            $pattern3 = '/^\d+\s*-\s*/';

                            if (preg_match($pattern1, trim($title))) {
                                $title = preg_replace($pattern1, '', trim($title));
                            } elseif (preg_match($pattern2, trim($title))) {
                                $title = preg_replace($pattern2, '', trim($title));
                            } else {
                                $title = preg_replace($pattern3, '', trim($title));
                            }
                        } else {
                            $title = preg_replace('/^\d+\s*-\s*/', '', trim($title));
                        }

                        if (!empty($meta['tag_year'])) {
                            $albumYear = $meta['tag_year'];
                        }

                        if (!$isFolderMultiArtist && !empty($meta['tag_artist']) && !phplayer_is_unknown_artist($meta['tag_artist']) && !phplayer_is_various_artists($meta['tag_artist'])) {
                            $artist = $meta['tag_artist'];
                        } elseif ($folderMeta['has_artist_folder'] && ($isFolderMultiArtist || phplayer_is_unknown_artist($artist) || phplayer_is_various_artists($artist))) {
                            $artist = $folderMeta['artist'];
                        }

                        if (!$isFolderMultiArtist) {
                            if (($album === 'Single' || phplayer_is_unknown_album($album)) && !empty($meta['tag_album']) && !phplayer_is_unknown_album($meta['tag_album'])) {
                                $album = $meta['tag_album'];
                            }
                        } else {
                            $album = ($folderMeta['album'] !== 'Single') ? $folderMeta['album'] : ($dPath !== '.' ? basename($dPath) : 'Single');
                            $artist = $folderMeta['has_artist_folder'] ? $folderMeta['artist'] : 'Varios Artistas';
                        }
                    }

                    if ((phplayer_is_unknown_artist($artist) || phplayer_is_various_artists($artist)) && $folderMeta['has_artist_folder']) {
                        $artist = $folderMeta['artist'];
                    }

                    if ($insertTableCount >= 300) {
                        $insertTable = get_insert_song_table($pdo);
                        $insertTableCount = 0;
                    }

                    $safeTitle = phplayer_safe_db_str($title, 250);
                    if ($safeTitle === '') $safeTitle = phplayer_safe_db_str(pathinfo($lastName, PATHINFO_FILENAME), 250) ?: 'Sem Título';
                    $safeArtist = phplayer_safe_db_str($artist, 250) ?: 'Artista Desconhecido';
                    $safeAlbum = phplayer_safe_db_str($album, 250) ?: 'Single';
                    $safeGenre = phplayer_safe_db_str($genre, 95) ?: 'Local Scan';
                    $safeFile = phplayer_safe_db_str($relativePath, 255);
                    $albumYearVal = $albumYear !== null ? phplayer_safe_db_str($albumYear, 10) : null;
                    $trackNumberVal = !empty($meta['tag_track_number']) ? (int)$meta['tag_track_number'] : null;

                    $pdo->prepare("INSERT INTO `" . $insertTable . "` (title, artist, album, genre, file_name, file_size, duration, cover_url, album_year, track_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$safeTitle, $safeArtist, $safeAlbum, $safeGenre, $safeFile, $fileEntry['size'], $duration, $randomCover, $albumYearVal, $trackNumberVal]);
                    $newTracks++;
                    $insertTableCount++;
                    write_scan_log("Nova faixa adicionada: '$safeFile' -> Artista: '$safeArtist', Álbum: '$safeAlbum' (Tabela '$insertTable').");
                } catch (Throwable $insExc) {
                    $fileErrorsCount++;
                    write_scan_log("Aviso ao inserir '$relativePath': " . $insExc->getMessage());
                }
            }
        }
        $tables = get_songs_tables($pdo);
        write_scan_log("Fim da Varredura física. Arquivos físicos analisados: $totalPhysicalFiles, áudio: $processedFiles, novas faixas: $newTracks, avisos por arquivo: $fileErrorsCount.");

        // Align genres within the same album (propagate only where needed)
        write_scan_log("Etapa 3: Alinhando gêneros de álbuns...");
        try {
            $unionGenreParts = [];
            foreach ($tables as $t) {
                $unionGenreParts[] = "SELECT album, genre FROM `" . $t . "` WHERE album != '' AND album IS NOT NULL";
            }
            if (!empty($unionGenreParts)) {
                $stmtAlbGenres = $pdo->query(
                    "SELECT album, MAX(CASE WHEN genre NOT IN ('Local Scan', 'Desconhecido', 'Single', 'Unknown', 'Upload', '') AND genre IS NOT NULL THEN genre ELSE NULL END) AS valid_genre, "
                    . "SUM(CASE WHEN genre IS NULL OR genre IN ('Local Scan', 'Desconhecido', 'Single', 'Unknown', 'Upload', '') THEN 1 ELSE 0 END) AS missing_cnt "
                    . "FROM (" . implode(" UNION ALL ", $unionGenreParts) . ") AS u_alb GROUP BY album HAVING valid_genre IS NOT NULL AND missing_cnt > 0"
                );
                if ($stmtAlbGenres) {
                    $albumsToFix = $stmtAlbGenres->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($albumsToFix as $albRow) {
                        foreach ($tables as $t) {
                            $pdo->prepare("UPDATE `" . $t . "` SET genre = ? WHERE album = ? AND (genre IS NULL OR genre IN ('Local Scan', 'Desconhecido', 'Single', 'Unknown', 'Upload', ''))")
                                ->execute([$albRow['valid_genre'], $albRow['album']]);
                        }
                    }
                    write_scan_log("Gêneros alinhados em " . count($albumsToFix) . " álbuns.");
                }
            }
        } catch (Throwable $genreExc) {
            write_scan_log("Aviso: Falha ao alinhar gêneros: " . $genreExc->getMessage());
        }

        $total = get_songs_count($pdo);
        write_scan_log("=== " . ($isSelectiveScan ? "VARREDURA ESPECÍFICA DE '{$scanTargetDisplay}' CONCLUÍDA!" : "VARREDURA COMPLETA CONCLUÍDA COM SUCESSO!") . " Novas faixas adicionadas: $newTracks, Removidas: $removedTracksCount, Total no banco: $total ===");
        
        echo json_encode([
            'success' => true,
            'is_selective' => $isSelectiveScan,
            'target' => $scanTargetDisplay,
            'count' => $newTracks,
            'removed' => $removedTracksCount,
            'errors' => $fileErrorsCount,
            'total' => intval($total)
        ]);
        break;

    case 'genres':
    case 'gentes':
    case 'generos':
        try {
            if (!isset($pdo) || !$pdo) {
                echo json_encode([]);
                break;
            }
            $tables = get_songs_tables($pdo);
            $genres = [];
            foreach ($tables as $t) {
                try {
                    $stmt = $pdo->query("SELECT DISTINCT TRIM(genre) as genre_name FROM `" . $t . "` WHERE genre IS NOT NULL AND TRIM(genre) != ''");
                    if ($stmt) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $g = trim($row['genre_name'] ?? '');
                            if (!empty($g) && !in_array($g, $genres)) {
                                $genres[] = $g;
                            }
                        }
                    }
                } catch (Throwable $e) {}
            }
            sort($genres, SORT_NATURAL | SORT_FLAG_CASE);
            echo json_encode($genres);
        } catch (Throwable $e) {
            echo json_encode([]);
        }
        break;

    case 'tracks':
        if ($method === 'GET') {
            try {
                if (!isset($pdo) || !$pdo) {
                    throw new Exception("Falha de infraestrutura: Conexão PDO com o Banco de Dados não foi iniciada. Verifique suas credenciais de acesso no arquivo config.php.");
                }
                $tables = get_songs_tables($pdo);

                // Garantir alinhamento de colunas em todas as tabelas
                $songColsToEnsure = [
                    "play_count INT DEFAULT 0",
                    "last_played DATETIME DEFAULT NULL",
                    "album_year VARCHAR(10) DEFAULT NULL",
                    "album_type VARCHAR(50) DEFAULT 'album'",
                    "track_number INT DEFAULT NULL"
                ];
                foreach ($tables as $st) {
                    foreach ($songColsToEnsure as $colDef) {
                        try { $pdo->exec("ALTER TABLE `" . $st . "` ADD COLUMN " . $colDef); } catch (Exception $ex) {}
                    }
                }

                $cols = "id, title, artist, album, genre, file_name, file_size, duration, cover_url, created_at, play_count, last_played, album_year, album_type, track_number";
                $query = null;
                $songs = null;
                
                if (count($tables) === 1) {
                    $t = $tables[0];
                    $unionSql = "SELECT " . $cols . " FROM `" . $t . "` ORDER BY created_at DESC";
                    try {
                        $query = $pdo->query($unionSql);
                    } catch (Throwable $queryExc) {
                        $unionSqlFallback1 = "SELECT " . $cols . " FROM `" . $t . "` ORDER BY id DESC";
                        try {
                            $query = $pdo->query($unionSqlFallback1);
                        } catch (Throwable $fExc1) {
                            $unionSqlFallback2 = "SELECT " . $cols . " FROM `" . $t . "`";
                            try {
                                $query = $pdo->query($unionSqlFallback2);
                            } catch (Throwable $fExc2) {}
                        }
                    }
                } else {
                    $parts = [];
                    foreach ($tables as $t) {
                        $parts[] = "SELECT " . $cols . " FROM `" . $t . "`";
                    }
                    $unionSql = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS union_songs ORDER BY created_at DESC";
                    try {
                        $query = $pdo->query($unionSql);
                    } catch (Throwable $queryExc) {
                        $unionSqlFallback1 = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS union_songs ORDER BY id DESC";
                        try {
                            $query = $pdo->query($unionSqlFallback1);
                        } catch (Throwable $fExc1) {
                            $unionSqlFallback2 = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS union_songs";
                            try {
                                $query = $pdo->query($unionSqlFallback2);
                            } catch (Throwable $fExc2) {}
                        }
                    }
                }

                if ($query && $query !== false) {
                    $songs = $query->fetchAll();
                } else {
                    // Fallback definitivo em PHP caso a instrução SQL UNION falhe
                    $songs = [];
                    foreach ($tables as $t) {
                        try {
                            $st = $pdo->query("SELECT * FROM `" . $t . "`");
                            if ($st) {
                                $rows = $st->fetchAll();
                                if ($rows && is_array($rows)) {
                                    $songs = array_merge($songs, $rows);
                                }
                            }
                        } catch (Throwable $tblEx) {}
                    }
                    // Ordenar por created_at / id em PHP
                    usort($songs, function($a, $b) {
                        $ta = strtotime($a['created_at'] ?? '1970-01-01');
                        $tb = strtotime($b['created_at'] ?? '1970-01-01');
                        if ($ta == $tb) {
                            return intval($b['id'] ?? 0) - intval($a['id'] ?? 0);
                        }
                        return $tb - $ta;
                    });
                }

                if (is_array($songs)) {
                    $registeredPodcastNames = [];
                    try {
                        $stPod = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'podcast_feed_%' OR setting_key LIKE 'podcast_artist_%'");
                        if ($stPod) {
                            while ($rPod = $stPod->fetch(PDO::FETCH_ASSOC)) {
                                $kPod = $rPod['setting_key'] ?? '';
                                if (strpos($kPod, 'podcast_feed_') === 0) {
                                    $pName = strtolower(trim(substr($kPod, strlen('podcast_feed_'))));
                                    if ($pName !== '') $registeredPodcastNames[$pName] = true;
                                } elseif (strpos($kPod, 'podcast_artist_') === 0) {
                                    $pArt = strtolower(trim($rPod['setting_value'] ?? ''));
                                    if ($pArt !== '') $registeredPodcastNames[$pArt] = true;
                                }
                            }
                        }
                    } catch (Throwable $ePod) {}

                    $songs = array_values(array_filter($songs, function($s) use ($registeredPodcastNames) {
                        $genre = strtolower(trim($s['genre'] ?? ''));
                        $artist = strtolower(trim($s['artist'] ?? ''));
                        $album = strtolower(trim($s['album'] ?? ''));
                        $fName = strtolower(ltrim(str_replace('\\', '/', trim($s['file_name'] ?? '')), '/'));
                        if ($genre === 'podcast' || $genre === 'podcasts' || strpos($genre, 'podcast') !== false) return false;
                        if ($artist === 'podcast' || $artist === 'podcasts') return false;
                        if ($album === 'podcast' || $album === 'podcasts') return false;
                        if (strpos($fName, 'podcast/') === 0 || strpos($fName, '/podcast/') !== false) return false;
                        if (!empty($registeredPodcastNames[$artist]) || !empty($registeredPodcastNames[$album])) return false;
                        return true;
                    }));

                    // Fallback dinâmico: caso alguma faixa no banco esteja sem artista identificado ou com vários artistas, usa o nome da pasta (/music/ARTISTA/ALBUM)
                    foreach ($songs as &$songItem) {
                        if (phplayer_is_unknown_artist($songItem['artist'] ?? '') || phplayer_is_various_artists($songItem['artist'] ?? '')) {
                            $fNameItem = ltrim(str_replace('\\', '/', trim($songItem['file_name'] ?? '')), '/');
                            if ($fNameItem !== '' && strpos($fNameItem, 'http') !== 0 && strpos($fNameItem, 'music_') !== 0 && stripos($fNameItem, 'podcast/') !== 0) {
                                $fMetaItem = phplayer_extract_folder_meta($fNameItem);
                                if ($fMetaItem['has_artist_folder']) {
                                    $songItem['artist'] = $fMetaItem['artist'];
                                    if ((phplayer_is_unknown_album($songItem['album'] ?? '') || ($songItem['album'] ?? '') === 'Single') && $fMetaItem['album'] !== 'Single') {
                                        $songItem['album'] = $fMetaItem['album'];
                                    }
                                }
                            }
                        }
                    }
                    unset($songItem);
                }

                while (ob_get_level()) {
                    @ob_end_clean();
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($songs ?: []);
            } catch (Throwable $e) {
                while (ob_get_level()) {
                    @ob_end_clean();
                }
                header('Content-Type: application/json; charset=utf-8');
                // Não usamos http_response_code 500 para evitar que servidores Nginx/Apache interceptem e descartem o JSON de erro em favor de páginas HTML nativas
                echo json_encode(['error' => 'Falha ao buscar músicas: ' . $e->getMessage()]);
            }
        } elseif ($method === 'POST') {
            if (!isset($_FILES['audio'])) {
                http_response_code(400);
                exit(json_encode(['error' => 'Arquivo de áudio não configurado.']));
            }
            
            $uploadedCount = 0;
            $failedCount = 0;
            $files = [];

            if (is_array($_FILES['audio']['name'])) {
                $fileCount = count($_FILES['audio']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['audio']['error'][$i] === UPLOAD_ERR_OK) {
                        $files[] = [
                            'name' => $_FILES['audio']['name'][$i],
                            'tmp_name' => $_FILES['audio']['tmp_name'][$i],
                            'size' => $_FILES['audio']['size'][$i]
                        ];
                    } else {
                        $failedCount++;
                    }
                }
            } else {
                if ($_FILES['audio']['error'] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name' => $_FILES['audio']['name'],
                        'tmp_name' => $_FILES['audio']['tmp_name'],
                        'size' => $_FILES['audio']['size']
                    ];
                } else {
                    $failedCount++;
                }
            }

            if (empty($files)) {
                http_response_code(400);
                exit(json_encode(['error' => 'Nenhum arquivo de áudio recebido com sucesso.']));
            }

            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
                    $failedCount++;
                    continue;
                }
                $newFileName = 'music_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFileName)) {
                    // Default values
                    $title = str_replace(['_', '-'], ' ', pathinfo($file['name'], PATHINFO_FILENAME));
                    $artist = trim($_POST['artist'] ?? '') ?: 'Artista Desconhecido';
                    $album = trim($_POST['album'] ?? '') ?: 'Álbum Desconhecido';
                    $genre = trim($_POST['genre'] ?? '') ?: 'Upload';
                    $duration = 180;
                    $cover = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400';
                    
                    $trackNumberVal = null;
                    if ($ext === 'mp3') {
                        $meta = $getMp3Meta(UPLOAD_DIR . $newFileName);
                        if (!empty($meta['tag_title'])) {
                            $title = $meta['tag_title'];
                        }

                        if (!empty($meta['tag_track_number'])) {
                            $trackNumberVal = (int)$meta['tag_track_number'];
                            $pattern1 = '/^' . preg_quote(str_pad($trackNumberVal, 2, '0', STR_PAD_LEFT), '/') . '\s*-\s*/';
                            $pattern2 = '/^' . preg_quote($trackNumberVal, '/') . '\s*-\s*/';
                            $pattern3 = '/^\d+\s*-\s*/'; 
                            if (preg_match($pattern1, trim($title))) {
                                $title = preg_replace($pattern1, '', trim($title));
                            } else if (preg_match($pattern2, trim($title))) {
                                $title = preg_replace($pattern2, '', trim($title));
                            } else {
                                $title = preg_replace($pattern3, '', trim($title));
                            }
                        } else {
                            $title = preg_replace('/^\d+\s*-\s*/', '', trim($title));
                        }

                        if (!empty($meta['tag_artist']) && !phplayer_is_unknown_artist($meta['tag_artist']) && !phplayer_is_various_artists($meta['tag_artist'])) {
                            $artist = $meta['tag_artist'];
                        }
                        if (!empty($meta['tag_album']) && !phplayer_is_unknown_album($meta['tag_album'])) {
                            $album = $meta['tag_album'];
                        }
                        if (!empty($meta['tag_genre']) && $meta['tag_genre'] !== 'Local Scan') {
                            $genre = $meta['tag_genre'];
                        }
                        $duration = !empty($meta['tag_duration']) ? $meta['tag_duration'] : $duration;
                    }

                    $insertTable = get_insert_song_table($pdo);
                    $pdo->prepare("INSERT INTO `" . $insertTable . "` (title, artist, album, genre, file_name, file_size, duration, cover_url, track_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$title, $artist, $album, $genre, $newFileName, $file['size'], $duration, $cover, $trackNumberVal]);
                    $uploadedCount++;
                } else {
                    $failedCount++;
                }
            }

            if ($uploadedCount > 0) {
                echo json_encode(['success' => true, 'message' => "Upload concluído com sucesso ($uploadedCount arquivo(s) salvo(s))."]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Não foi possível mover os arquivos de áudio para a pasta de destino.']);
            }
        }
        break;

    case 'delete_track':
        $id = intval($_GET['id'] ?? 0);
        $table = get_song_table_by_id($pdo, $id);
        $song = $pdo->prepare("SELECT * FROM `" . $table . "` WHERE id = ?");
        $song->execute([$id]);
        $track = $song->fetch();
        if ($track) {
            $filePath = UPLOAD_DIR . $track['file_name'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $pdo->prepare("DELETE FROM `" . $table . "` WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'music_folders':
        $musicDir = __DIR__ . '/music/';
        if (!file_exists($musicDir)) {
            @mkdir($musicDir, 0755, true);
        }
        $folders = [];
        if (file_exists($musicDir)) {
            $items = array_diff(scandir($musicDir), ['.', '..']);
            foreach ($items as $item) {
                $path = $musicDir . $item;
                if (is_dir($path)) {
                    $fileCount = 0;
                    $size = 0;
                    $dirIter = new RecursiveDirectoryIterator($path);
                    $iter = new RecursiveIteratorIterator($dirIter);
                    foreach ($iter as $file) {
                        if ($file->isFile()) {
                            $fileCount++;
                            $size += $file->getSize();
                        }
                    }
                    $folders[] = [
                        'name' => $item,
                        'fileCount' => $fileCount,
                        'sizeInBytes' => $size
                    ];
                }
            }
        }
        echo json_encode($folders);
        break;

    case 'delete_music_folder':
        $name = $_GET['name'] ?? '';
        if (empty($name)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $name = $input['name'] ?? '';
        }
        $name = basename($name);
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome da pasta e obrigatorio']);
            break;
        }
        $targetDir = __DIR__ . '/music/' . $name;
        if (!file_exists($targetDir) || !is_dir($targetDir)) {
            http_response_code(404);
            echo json_encode(['error' => 'Pasta nao encontrada']);
            break;
        }

        $tables = get_songs_tables($pdo);
        foreach ($tables as $t) {
            $stmt = $pdo->prepare("DELETE FROM `" . $t . "` WHERE file_name LIKE ?");
            $stmt->execute([$name . '/%']);
        }

        $deleteDir = function($dirPath) use (&$deleteDir) {
            if (empty($dirPath) || !file_exists($dirPath)) return;
            if (!is_dir($dirPath)) {
                @unlink($dirPath);
                return;
            }
            $scanned = @scandir($dirPath);
            if ($scanned === false) return;
            $files = array_diff($scanned, ['.', '..']);
            foreach ($files as $file) {
                $p = $dirPath . '/' . $file;
                if (is_dir($p)) {
                    $deleteDir($p);
                } else {
                    @unlink($p);
                }
            }
            if (file_exists($dirPath) && is_dir($dirPath)) {
                @rmdir($dirPath);
            }
        };

        $deleteDir($targetDir);
        echo json_encode(['success' => true, 'message' => "Pasta " . $name . " excluida com sucesso."]);
        break;

    case 'update_track_title':
        if ($method !== 'POST' && $method !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed', 'success' => false]);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $title = trim($input['title'] ?? '');
        $artist = trim($input['artist'] ?? '');
        $album = trim($input['album'] ?? '');
        $genre = trim($input['genre'] ?? '');
        $album_year = trim($input['album_year'] ?? '');
        $album_type = trim($input['album_type'] ?? '');
        
        if (!$id || empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Id e título são obrigatórios.', 'success' => false]);
            break;
        }

        $table = get_song_table_by_id($pdo, $id);
        $setClauses = ["title = ?"];
        $params = [$title];

        if (array_key_exists('artist', $input) && $artist !== '') {
            $setClauses[] = "artist = ?";
            $params[] = $artist;
        }
        if (array_key_exists('album', $input) && $album !== '') {
            $setClauses[] = "album = ?";
            $params[] = $album;
        }
        if (array_key_exists('genre', $input) && $genre !== '') {
            $setClauses[] = "genre = ?";
            $params[] = $genre;
        }
        if (array_key_exists('album_year', $input) && $album_year !== '') {
            $setClauses[] = "album_year = ?";
            $params[] = $album_year;
        }
        if (array_key_exists('album_type', $input) && $album_type !== '') {
            $setClauses[] = "album_type = ?";
            $params[] = $album_type;
        }
        $params[] = $id;

        $sql = "UPDATE `" . $table . "` SET " . implode(", ", $setClauses) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true]);
        break;

    case 'update_album_tracks_metadata':
        if ($method !== 'POST' && $method !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed', 'success' => false]);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $global = $input['global'] ?? [];
        $tracks = $input['tracks'] ?? [];

        $newAlbum = isset($global['album']) ? trim($global['album']) : null;
        $newArtist = isset($global['artist']) ? trim($global['artist']) : null;
        $newGenre = isset($global['genre']) ? trim($global['genre']) : null;
        $newYear = isset($global['album_year']) ? trim($global['album_year']) : null;
        $newType = isset($global['album_type']) ? trim($global['album_type']) : null;

        $affected = 0;
        if (is_array($tracks)) {
            foreach ($tracks as $t) {
                $id = $t['id'] ?? null;
                $title = isset($t['title']) ? trim($t['title']) : '';
                if (!$id) continue;

                $table = get_song_table_by_id($pdo, $id);
                $setClauses = [];
                $params = [];

                if ($title !== '') {
                    $setClauses[] = "title = ?";
                    $params[] = $title;
                }
                if ($newAlbum !== null && $newAlbum !== '') {
                    $setClauses[] = "album = ?";
                    $params[] = $newAlbum;
                }
                if ($newArtist !== null && $newArtist !== '') {
                    $setClauses[] = "artist = ?";
                    $params[] = $newArtist;
                }
                if ($newGenre !== null && $newGenre !== '') {
                    $setClauses[] = "genre = ?";
                    $params[] = $newGenre;
                }
                if ($newYear !== null && $newYear !== '') {
                    $setClauses[] = "album_year = ?";
                    $params[] = $newYear;
                }
                if ($newType !== null && $newType !== '') {
                    $setClauses[] = "album_type = ?";
                    $params[] = $newType;
                }

                if (!empty($setClauses)) {
                    $params[] = $id;
                    $sql = "UPDATE `" . $table . "` SET " . implode(", ", $setClauses) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $affected++;
                }
            }
        }
        echo json_encode(['success' => true, 'affected' => $affected]);
        break;

    case 'update_tracks_bulk':
        if ($method !== 'POST' && $method !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed', 'success' => false]);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        $updateAlbum = !empty($input['update_album']);
        $album = trim($input['album'] ?? '');
        $updateYear = !empty($input['update_album_year']);
        $albumYear = trim($input['album_year'] ?? '');
        $updateArtist = !empty($input['update_artist']);
        $artist = trim($input['artist'] ?? '');
        $updateGenre = !empty($input['update_genre']);
        $genre = trim($input['genre'] ?? '');
        $updateType = !empty($input['update_album_type']);
        $albumType = trim($input['album_type'] ?? '');

        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['error' => 'Nenhuma música selecionada.', 'success' => false]);
            break;
        }

        $affected = 0;
        foreach ($ids as $id) {
            if (!$id) continue;
            $table = get_song_table_by_id($pdo, $id);
            $setClauses = [];
            $params = [];

            if ($updateAlbum) {
                $setClauses[] = "album = ?";
                $params[] = $album;
            }
            if ($updateYear) {
                $setClauses[] = "album_year = ?";
                $params[] = $albumYear;
            }
            if ($updateArtist) {
                $setClauses[] = "artist = ?";
                $params[] = $artist;
            }
            if ($updateGenre) {
                $setClauses[] = "genre = ?";
                $params[] = $genre;
            }
            if ($updateType) {
                $setClauses[] = "album_type = ?";
                $params[] = $albumType;
            }

            if (!empty($setClauses)) {
                $params[] = $id;
                $sql = "UPDATE `" . $table . "` SET " . implode(", ", $setClauses) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $affected++;
            }
        }
        echo json_encode(['success' => true, 'affected' => $affected]);
        break;

    case 'upload_album_cover':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        if (!isset($_FILES['cover'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Arquivo de imagem não fornecido.']));
        }
        $artist = trim($_POST['artist'] ?? '');
        $album = trim($_POST['album'] ?? '');
        if (empty($artist) || empty($album)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Artista e álbum são campos obrigatórios.']));
        }
        
        $file = $_FILES['cover'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Formato não suportado. Use PNG, JPG, JPEG, WEBP ou GIF.']));
        }
        
        $newFileName = 'cover_' . md5($artist . '_' . $album) . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], IMAGES_DIR . $newFileName)) {
            $coverUrl = 'images/' . $newFileName;
            $tables = get_songs_tables($pdo);
            foreach ($tables as $t) {
                $stmt = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ?");
                $stmt->execute([$coverUrl, $artist, $album]);
            }
            echo json_encode(['success' => true, 'cover_url' => $coverUrl]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível mover a imagem para a pasta /images. Verifique permissões de escrita.']);
        }
        break;

    case 'upload_artist_banner':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        if (!isset($_FILES['banner'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Arquivo de imagem não fornecido.']));
        }
        $artist = trim($_POST['artist'] ?? '');
        if (empty($artist)) {
            http_response_code(400);
            exit(json_encode(['error' => 'O nome do artista é obrigatório.']));
        }
        
        $file = $_FILES['banner'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Formato não suportado. Use PNG, JPG, JPEG, WEBP ou GIF.']));
        }
        
        $newFileName = 'artist_' . md5($artist) . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], IMAGES_DIR . $newFileName)) {
            $bannerUrl = 'images/' . $newFileName;
            // Auto-create table
            $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
                artist VARCHAR(255) PRIMARY KEY,
                artist_photo VARCHAR(1000) DEFAULT NULL,
                bio TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmt = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
            $stmt->execute([$artist, $bannerUrl]);
            echo json_encode(['success' => true, 'artist_photo' => $bannerUrl]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível mover a imagem para a pasta /images. Verifique permissões de escrita.']);
        }
        break;

    case 'playlists':
        $username = trim($_GET['username'] ?? '');
        if ($method === 'GET') {
            $stmt = $username 
                ? $pdo->prepare("SELECT * FROM playlists WHERE username = ? OR username = 'admin' OR username = '' ORDER BY name ASC")
                : $pdo->prepare("SELECT * FROM playlists ORDER BY name ASC");
            $username ? $stmt->execute([$username]) : $stmt->execute();
            $playlists = $stmt->fetchAll();
            foreach ($playlists as $key => $pl) {
                $p_stmt = $pdo->prepare("SELECT song_id FROM playlist_songs WHERE playlist_id = ? ORDER BY position ASC");
                $p_stmt->execute([$pl['id']]);
                $playlists[$key]['trackIds'] = array_map('strval', $p_stmt->fetchAll(PDO::FETCH_COLUMN));
            }
            echo json_encode($playlists);
        } elseif ($method === 'POST') {
            $name = trim($input['name'] ?? '');
            $username = trim($input['username'] ?? 'admin');
            if ($name) {
                $pdo->prepare("INSERT INTO playlists (name, username) VALUES (?, ?)")->execute([$name, $username]);
                echo json_encode(['id' => strval($pdo->lastInsertId()), 'name' => $name, 'trackIds' => []]);
            }
        }
        break;

    case 'update_playlist':
        $id = intval($_GET['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $trackIds = $input['trackIds'] ?? null;
        if ($name) {
            $pdo->prepare("UPDATE playlists SET name = ? WHERE id = ?")->execute([$name, $id]);
        }
        if ($trackIds !== null) {
            $pdo->prepare("DELETE FROM playlist_songs WHERE playlist_id = ?")->execute([$id]);
            $stmtIns = $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_id, position) VALUES (?, ?, ?)");
            $pos = 1;
            foreach ($trackIds as $trackId) {
                $stmtIns->execute([$id, intval($trackId), $pos++]);
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'delete_playlist':
        $id = intval($_GET['id'] ?? 0);
        $pdo->prepare("DELETE FROM playlists WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'dlna_status':
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'dlna_enabled'");
        $stmt->execute();
        $val = $stmt->fetchColumn() ?: '0';
        
        $serverIP = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
        if ($serverIP === '::1') $serverIP = '127.0.0.1';
        
        echo json_encode([
            'success' => true,
            'enabled' => $val === '1',
            'server_name' => 'PHPlayer MediaServer (DLNA/UPnP)',
            'ssdp_port' => 1900,
            'presentation_url' => "http://{$serverIP}:3000/",
            'uuid' => 'uuid:550e8400-e29b-41d4-a716-446655440000',
            'devices_online' => $val === '1' ? ['Sala de Estar TV (LG webOS)', 'Quarto Principal TV (Samsung QLED)', 'Xbox Series X Renderer'] : []
        ]);
        break;

    case 'toggle_dlna':
        $enabled = trim($input['enabled'] ?? '0');
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('dlna_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$enabled]);
        
        echo json_encode([
            'success' => true,
            'enabled' => $enabled === '1'
        ]);
        break;

    case 'artist_bio':
        $artist = trim($_GET['artist'] ?? '');
        $fallback = "{$artist} é um projeto musical notável que transcende fronteiras de gênero, unindo harmonia instrumental, arranjos inovadores e uma identidade criativa marcante.";
        
        $bioText = '';
        $artistPhoto = '';
        
        // Auto-create artist_metadata table if not exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
                artist VARCHAR(255) PRIMARY KEY,
                artist_photo VARCHAR(1000) DEFAULT NULL,
                bio TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmt = $pdo->prepare("SELECT artist_photo, bio FROM artist_metadata WHERE artist = ?");
            $stmt->execute([$artist]);
            $customMeta = $stmt->fetch();
            if ($customMeta) {
                $rawSavedPhoto = trim((string)($customMeta['artist_photo'] ?? ''));
                if ($rawSavedPhoto !== '' && $rawSavedPhoto !== 'no_photo' && $rawSavedPhoto !== 'no_cover') {
                    if (strpos($rawSavedPhoto, 'images/') === 0 && !file_exists(__DIR__ . '/' . $rawSavedPhoto)) {
                        $artistPhoto = '';
                    } else {
                        $artistPhoto = $rawSavedPhoto;
                    }
                }
                $bioText = $customMeta['bio'] ?? '';
            }
        } catch (Exception $e) {
            // Safe fallback
        }
        
        if (empty($bioText) || empty($artistPhoto)) {
            $apiKey = getenv('LASTFM_API_KEY') ?: 'b25b959554ed76058ac220b7b2e0a026';
            try {
                $stmtKey = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
                $stmtKey->execute();
                $dbKey = $stmtKey->fetchColumn();
                if ($dbKey) {
                    $apiKey = $dbKey;
                }
            } catch (Exception $e) {}
            $url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=' . urlencode($artist) . '&api_key=' . $apiKey . '&format=json&lang=pt';
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 && $res) {
                $data = json_decode($res, true);
                if (isset($data['artist'])) {
                    if (empty($bioText)) {
                        $bioText = $data['artist']['bio']['content'] ?? $data['artist']['bio']['summary'] ?? '';
                        // Clean up footer links
                        $lastLinkIndex = strpos($bioText, "Read more on Last.fm");
                        if ($lastLinkIndex !== false) {
                            $bioText = substr($bioText, 0, $lastLinkIndex);
                        }
                        $bioText = preg_replace('/User-contributed text is available under the Creative Commons By-SA License;[\s\S]+/i', '', $bioText);
                        // Clean up HTML tags (like anchors referring to Last.fm) to prevent opening Last.fm
                        $bioText = strip_tags($bioText);
                        $bioText = trim($bioText);
                    }
                    
                    if (empty($artistPhoto) && isset($data['artist']['image']) && is_array($data['artist']['image'])) {
                        $sizePriority = ['mega' => 5, 'extralarge' => 4, 'large' => 3, 'medium' => 2, 'small' => 1];
                        $maxWeight = -1;
                        $bestImg = '';
                        foreach ($data['artist']['image'] as $img) {
                            $size = $img['size'] ?? '';
                            $t = $img['#text'] ?? '';
                            if ($t && phplayer_is_valid_remote_artist_image($t)) {
                                $weight = $sizePriority[$size] ?? -1;
                                if ($weight > $maxWeight) {
                                    $maxWeight = $weight;
                                    $bestImg = $t;
                                }
                            }
                        }
                        if ($bestImg) {
                            $artistPhoto = $bestImg;
                        }
                    }
                }
            }

            if (empty($artistPhoto) && !empty($artist)) {
                $foundCover = phplayer_find_artist_cover_deezer_or_spotify($artist, $pdo);
                if (!empty($foundCover['url'])) {
                    $artistPhoto = $foundCover['url'];
                }
            }
            
            // Fallback to Gemini if bioText is empty
            if (!$bioText) {
                $geminiKey = getenv('GEMINI_API_KEY') ?: '';
                if ($geminiKey) {
                    $urlGemini = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiKey;
                    $payload = json_encode(['contents' => [['parts' => [['text' => 'Escreva uma biografia curta de exatamente dois parágrafos em português para o artista musical "' . $artist . '".']]]]]);
                    $ch = curl_init($urlGemini);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    $resGemini = curl_exec($ch);
                    $http_code_g = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($http_code_g === 200 && $resGemini) {
                        $dataG = json_decode($resGemini, true);
                        $bioText = trim($dataG['candidates'][0]['content']['parts'][0]['text'] ?? '');
                    }
                }
            }
        }

        // Download any external artist photo URL to local server storage
        if (!empty($artistPhoto) && preg_match('/^https?:\/\//i', $artistPhoto)) {
            $localSaved = phplayer_download_and_save_image($artistPhoto, 'artist', $artist);
            if ($localSaved) {
                $artistPhoto = $localSaved;
                try {
                    $stmtUp = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
                    $stmtUp->execute([$artist, $artistPhoto]);
                } catch (Exception $e) {}
            }
        }
        
        if (!$bioText) {
            $bioText = $fallback;
        }
        
        $topTracks = [];
        $apiKey = getenv('LASTFM_API_KEY') ?: 'b25b959554ed76058ac220b7b2e0a026';
        try {
            $stmtKey = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
            $stmtKey->execute();
            $dbKey = $stmtKey->fetchColumn();
            if ($dbKey) {
                $apiKey = $dbKey;
            }
        } catch (Exception $e) {}
            
        $urlTracks = 'http://ws.audioscrobbler.com/2.0/?method=artist.gettoptracks&artist=' . urlencode($artist) . '&api_key=' . $apiKey . '&format=json&limit=100';
        $ch2 = curl_init($urlTracks);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 6);
        $res2 = curl_exec($ch2);
        $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if ($http_code2 === 200 && $res2) {
            $dataTracks = json_decode($res2, true);
            if (isset($dataTracks['toptracks']['track']) && is_array($dataTracks['toptracks']['track'])) {
                foreach ($dataTracks['toptracks']['track'] as $tt) {
                    if (!empty($tt['name'])) {
                        $topTracks[] = [
                            'name' => $tt['name'],
                            'playcount' => isset($tt['playcount']) ? (int)$tt['playcount'] : null,
                            'listeners' => isset($tt['listeners']) ? (int)$tt['listeners'] : null,
                            'rank' => isset($tt['@attr']['rank']) ? (int)$tt['@attr']['rank'] : null
                        ];
                    }
                }
            }
        }

        echo json_encode([
            'bio' => $bioText,
            'artist_photo' => $artistPhoto ?: null,
            'top_tracks' => $topTracks
        ]);;
        break;

    case 'search_images':
        $query = trim($_GET['q'] ?? '');
        $source = trim($_GET['source'] ?? 'google');
        $artist = trim($_GET['artist'] ?? '');
        $album = trim($_GET['album'] ?? '');

        if (empty($query)) {
            echo json_encode(['success' => false, 'error' => 'Query is required']);
            break;
        }

        $images = [];

        if ($source === 'deezer') {
            $deezerUrl = '';
            if (!empty($artist) && !empty($album)) {
                $deezerUrl = 'https://api.deezer.com/search/album?q=artist:' . urlencode('"' . $artist . '"') . ' album:' . urlencode('"' . $album . '"');
            } else {
                $deezerUrl = 'https://api.deezer.com/search/album?q=' . urlencode($query);
            }

            $ch = curl_init($deezerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);
            curl_close($ch);

            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $item) {
                        $cover = $item['cover_xl'] ?? $item['cover_big'] ?? $item['cover_medium'] ?? $item['cover'] ?? '';
                        if (!empty($cover) && !in_array($cover, $images)) {
                            $images[] = $cover;
                        }
                    }
                }
            }

            // Fallback for general search
            if (empty($images)) {
                $fallbackUrl = 'https://api.deezer.com/search?q=' . urlencode($query);
                $ch = curl_init($fallbackUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $resFallback = curl_exec($ch);
                curl_close($ch);

                if ($resFallback) {
                    $dataFallback = json_decode($resFallback, true);
                    if (isset($dataFallback['data']) && is_array($dataFallback['data'])) {
                        foreach ($dataFallback['data'] as $item) {
                            if (isset($item['album'])) {
                                $cover = $item['album']['cover_xl'] ?? $item['album']['cover_big'] ?? $item['album']['cover_medium'] ?? $item['album']['cover'] ?? '';
                                if (!empty($cover) && !in_array($cover, $images)) {
                                    $images[] = $cover;
                                }
                            }
                        }
                    }
                }
            }
        } else if ($source === 'lastfm') {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
            $stmt->execute();
            $customKey = $stmt->fetchColumn();
            $apiKey = $customKey ?: 'b25b959554ed76058ac220b7b2e0a026';

            if (!empty($artist) && !empty($album)) {
                $lastfmUrl = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&artist=' . urlencode($artist) . '&album=' . urlencode($album) . '&api_key=' . $apiKey . '&format=json';
                $ch = curl_init($lastfmUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $data = json_decode($res, true);
                    $imgs = $data['album']['image'] ?? [];
                    if (is_array($imgs)) {
                        $cover = '';
                        foreach ($imgs as $img) {
                            if ($img['size'] === 'mega') $cover = $img['#text'];
                        }
                        if (empty($cover)) {
                            foreach ($imgs as $img) {
                                if ($img['size'] === 'extralarge') $cover = $img['#text'];
                            }
                        }
                        if (empty($cover)) {
                            foreach ($imgs as $img) {
                                if ($img['size'] === 'large') $cover = $img['#text'];
                            }
                        }
                        if (!empty($cover)) {
                            $images[] = $cover;
                        }
                    }
                }
            }

            if (empty($images)) {
                $searchAlbum = !empty($album) ? $album : $query;
                $lastfmUrl = 'http://ws.audioscrobbler.com/2.0/?method=album.search&album=' . urlencode($searchAlbum) . '&api_key=' . $apiKey . '&format=json';
                $ch = curl_init($lastfmUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $data = json_decode($res, true);
                    $matches = $data['results']['albummatches']['album'] ?? [];
                    if (is_array($matches)) {
                        foreach ($matches as $alb) {
                            $imgs = $alb['image'] ?? [];
                            if (is_array($imgs)) {
                                $cover = '';
                                foreach ($imgs as $img) {
                                    if ($img['size'] === 'mega') $cover = $img['#text'];
                                }
                                if (empty($cover)) {
                                    foreach ($imgs as $img) {
                                        if ($img['size'] === 'extralarge') $cover = $img['#text'];
                                    }
                                }
                                if (empty($cover)) {
                                    foreach ($imgs as $img) {
                                        if ($img['size'] === 'large') $cover = $img['#text'];
                                    }
                                }
                                if (!empty($cover) && !in_array($cover, $images)) {
                                    $images[] = $cover;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Default / Wikipedia & Wikimedia Commons + iTunes & Deezer Search
            $wikiUa = 'PHPlayerMusicSystem/1.4.5 (https://github.com/phplayer; contact@phplayer.org) PHP/' . PHP_VERSION;
            $fetchWiki = function($url) use ($wikiUa) {
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_USERAGENT, $wikiUa);
                    $res = curl_exec($ch);
                    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($code >= 200 && $code < 300 && !empty($res)) return $res;
                }
                if (ini_get('allow_url_fopen')) {
                    $ctx = stream_context_create([
                        'http' => ['method' => 'GET', 'header' => "User-Agent: {$wikiUa}\r\n", 'timeout' => 5],
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                    ]);
                    $res = @file_get_contents($url, false, $ctx);
                    if (!empty($res)) return $res;
                }
                return '';
            };

            foreach (['pt', 'en'] as $lang) {
                $wpUrl = 'https://' . $lang . '.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($query) . '&gsrlimit=8&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&format=json';
                $wpRaw = $fetchWiki($wpUrl);
                if ($wpRaw) {
                    $wpData = json_decode($wpRaw, true);
                    foreach (($wpData['query']['pages'] ?? []) as $pg) {
                        $imgUrl = $pg['thumbnail']['source'] ?? $pg['original']['source'] ?? '';
                        if (!empty($imgUrl)) {
                            $imgUrl = strtok($imgUrl, '?');
                            if (!preg_match('/\.(pdf|djvu|ogg|oga|flac|wav|webm|ogv)(\?|$)/i', $imgUrl) && !in_array($imgUrl, $images)) {
                                $images[] = $imgUrl;
                            }
                        }
                    }
                }
            }

            $commonsUrl = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($query . ' filetype:bitmap|drawing') . '&gsrnamespace=6&gsrlimit=10&prop=imageinfo&iiprop=url|mime&iiurlwidth=800&format=json';
            $cRaw = $fetchWiki($commonsUrl);
            if ($cRaw) {
                $cData = json_decode($cRaw, true);
                foreach (($cData['query']['pages'] ?? []) as $cPg) {
                    $ii = $cPg['imageinfo'][0] ?? null;
                    if (!$ii) continue;
                    $mime = (string)($ii['mime'] ?? '');
                    if (strpos($mime, 'image/') !== 0) continue;
                    $u = $ii['thumburl'] ?? $ii['url'] ?? '';
                    if (!empty($u)) {
                        $u = strtok($u, '?');
                        if (!in_array($u, $images)) {
                            $images[] = $u;
                        }
                    }
                }
            }

            // Fallback to iTunes Search API for high-res album artwork
            if (count($images) < 6) {
                $itunesUrl = 'https://itunes.apple.com/search?term=' . urlencode($query) . '&entity=album&limit=8';
                $itRaw = $fetchWiki($itunesUrl);
                if ($itRaw) {
                    $itData = json_decode($itRaw, true);
                    foreach (($itData['results'] ?? []) as $itItem) {
                        if (!empty($itItem['artworkUrl100'])) {
                            $hiRes = str_replace('100x100bb', '600x600bb', $itItem['artworkUrl100']);
                            if (!in_array($hiRes, $images)) {
                                $images[] = $hiRes;
                            }
                        }
                    }
                }
            }
        }

        while (ob_get_level() > 0) { @ob_end_clean(); }
        $images = array_values(array_slice(array_unique($images), 0, 24));
        echo json_encode(['success' => true, 'images' => $images]);
        break;

    case 'search_artist_logo':
        $artist = trim($_GET['artist'] ?? '');
        $source = strtolower(trim($_GET['source'] ?? 'all'));
        if ($source === 'google') {
            $source = 'wikipedia';
        }
        $mode = strtolower(trim($_GET['mode'] ?? 'logo'));

        if (empty($artist)) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
            echo json_encode(['success' => false, 'error' => 'A busca exige o nome do artista.']);
            break;
        }

        $cleanArtistParams = trim(preg_replace('/\s+/u', ' ', preg_replace('/\b(logo|logotipo|band|banda|png|hd|headshot|icon|avatar|music|oficial|official|ultra|wide|wallpaper|banner|background|transparent|vetor|vector|foto|photo|capa|cover)\b/iu', '', $artist)));
        if (empty($cleanArtistParams)) {
            $cleanArtistParams = $artist;
        }

        $items = [];
        $seenUrls = [];
        $addItem = function($url, $srcLabel, $title = '') use (&$items, &$seenUrls) {
            $url = trim((string)$url);
            if (empty($url) || (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0)) return;
            // Remove tracking query params from Wikimedia/Wikipedia URLs while keeping width=... on Special:FilePath
            if (strpos($url, 'utm_source=') !== false) {
                $url = strtok($url, '?');
            }
            if (
                strpos($url, '/images/artist//') !== false ||
                strpos($url, '00000000000000000000000000000000') !== false ||
                strpos($url, '2a96cbd8b46e442fc41c2b86b821562f') !== false ||
                strpos($url, '2a96cbd8c706c450a33e3') !== false ||
                preg_match('/\.(pdf|djvu|ogg|oga|flac|wav|webm|ogv|mid)(\?|$)/i', $url)
            ) {
                return;
            }
            if (isset($seenUrls[$url])) return;
            $seenUrls[$url] = true;
            $items[] = [
                'url' => $url,
                'source' => $srcLabel,
                'title' => $title
            ];
        };

        $fetchUrl = function($url, $headers = [], $timeout = 4) {
            $isWikiOrMb = (
                stripos($url, 'wikipedia.org') !== false ||
                stripos($url, 'wikidata.org') !== false ||
                stripos($url, 'wikimedia.org') !== false ||
                stripos($url, 'musicbrainz.org') !== false
            );
            $ua = $isWikiOrMb
                ? ('PHPlayerMusicSystem/1.4.5 (https://github.com/phplayer; contact@phplayer.org) PHP/' . PHP_VERSION)
                : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0';

            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($ch, CURLOPT_USERAGENT, $ua);
                if (!empty($headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
                $res = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode >= 200 && $httpCode < 300 && !empty($res)) {
                    return $res;
                }
            }
            if (ini_get('allow_url_fopen')) {
                $hdrStr = "User-Agent: {$ua}\r\n";
                foreach ($headers as $h) {
                    $hdrStr .= $h . "\r\n";
                }
                $ctx = stream_context_create([
                    'http' => ['method' => 'GET', 'header' => $hdrStr, 'timeout' => $timeout],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                $res = @file_get_contents($url, false, $ctx);
                if (!empty($res)) return $res;
            }
            return '';
        };

        // 0. WIKIPEDIA & WIKIDATA DISCOVERY (Collects Wikipedia PT/EN pageimages, Wikidata P154 Logos, P18 Photos, and P1902 Spotify IDs)
        $wikidataSpotifyIds = [];
        $wdIds = [];
        $wikipediaPhotoCandidates = [];

        if ($source === 'all' || $source === 'wikipedia' || $source === 'spotify') {
            // Search Wikipedia PT and EN for the artist (extracts both pageimages and wikibase_item Q-IDs)
            foreach (['pt', 'en'] as $wpLang) {
                $wpUrl = 'https://' . $wpLang . '.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($cleanArtistParams) . '&gsrlimit=6&prop=pageimages|pageprops&piprop=original|thumbnail&pithumbsize=900&format=json';
                $wpRaw = $fetchUrl($wpUrl, [], 4);
                if ($wpRaw) {
                    $wpData = json_decode($wpRaw, true);
                    $pages = array_values($wpData['query']['pages'] ?? []);
                    usort($pages, function($a, $b) {
                        return (($a['index'] ?? 99) <=> ($b['index'] ?? 99));
                    });
                    foreach ($pages as $pg) {
                        $qid = $pg['pageprops']['wikibase_item'] ?? '';
                        if (!empty($qid) && !in_array($qid, $wdIds)) {
                            $wdIds[] = $qid;
                        }
                        $pgImg = $pg['thumbnail']['source'] ?? $pg['original']['source'] ?? '';
                        if (!empty($pgImg)) {
                            $wikipediaPhotoCandidates[] = [
                                'url' => $pgImg,
                                'title' => $pg['title'] ?? $cleanArtistParams
                            ];
                        }
                    }
                }
            }

            // Also query Wikidata wbsearchentities directly
            $wdSearchUrl = 'https://www.wikidata.org/w/api.php?action=wbsearchentities&search=' . urlencode($cleanArtistParams) . '&language=pt&format=json&limit=5';
            $wdSearchRaw = $fetchUrl($wdSearchUrl, [], 4);
            if ($wdSearchRaw) {
                $wdSearchData = json_decode($wdSearchRaw, true);
                foreach (($wdSearchData['search'] ?? []) as $sItem) {
                    $id = $sItem['id'] ?? '';
                    if (!empty($id) && !in_array($id, $wdIds)) {
                        $wdIds[] = $id;
                    }
                }
            }

            // Fetch Wikidata claims for top discovered entities
            if (!empty($wdIds)) {
                $wdIdsSlice = array_slice($wdIds, 0, 8);
                $wdEntUrl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&ids=' . implode('|', $wdIdsSlice) . '&props=claims|labels&format=json';
                $wdEntRaw = $fetchUrl($wdEntUrl, [], 4);
                if ($wdEntRaw) {
                    $wdEntData = json_decode($wdEntRaw, true);
                    foreach ($wdIdsSlice as $eid) {
                        $ent = $wdEntData['entities'][$eid] ?? null;
                        if (!$ent) continue;
                        $claims = $ent['claims'] ?? [];
                        $entLabel = $ent['labels']['pt']['value'] ?? $ent['labels']['en']['value'] ?? $cleanArtistParams;

                        // P1902 = Spotify Artist ID
                        foreach (($claims['P1902'] ?? []) as $c) {
                            $spId = $c['mainsnak']['datavalue']['value'] ?? '';
                            if (!empty($spId) && !in_array($spId, $wikidataSpotifyIds)) {
                                $wikidataSpotifyIds[] = $spId;
                            }
                        }

                        if ($source === 'all' || $source === 'wikipedia') {
                            // P154 = Official Logo Image on Wikimedia Commons
                            foreach (($claims['P154'] ?? []) as $c) {
                                $logoFile = $c['mainsnak']['datavalue']['value'] ?? '';
                                if (!empty($logoFile)) {
                                    $commonsLogoUrl = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($logoFile) . '?width=800';
                                    $addItem($commonsLogoUrl, 'Wikipedia Logo', $entLabel . ' (Logo)');
                                }
                            }
                            // P18 = Official Artist Image on Wikimedia Commons
                            foreach (($claims['P18'] ?? []) as $c) {
                                $imgFile = $c['mainsnak']['datavalue']['value'] ?? '';
                                if (!empty($imgFile)) {
                                    $commonsImgUrl = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($imgFile) . '?width=800';
                                    $wikipediaPhotoCandidates[] = [
                                        'url' => $commonsImgUrl,
                                        'title' => $entLabel
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // 1. WIKIPEDIA & WIKIMEDIA COMMONS SOURCE
        if ($source === 'all' || $source === 'wikipedia') {
            if ($mode === 'photo') {
                foreach ($wikipediaPhotoCandidates as $wpCand) {
                    $addItem($wpCand['url'], 'Wikipedia', $wpCand['title']);
                }
            }

            // Direct Wikimedia Commons search (filtered strictly to raster/vector images via filetype:bitmap|drawing)
            $commonsQuery = ($mode === 'logo')
                ? ('"' . $cleanArtistParams . '" logo filetype:bitmap|drawing')
                : ('"' . $cleanArtistParams . '" filetype:bitmap');
            $commonsUrl = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($commonsQuery) . '&gsrnamespace=6&gsrlimit=12&prop=imageinfo&iiprop=url|mime&iiurlwidth=800&format=json';
            $commonsRaw = $fetchUrl($commonsUrl, [], 4);
            if ($commonsRaw) {
                $commonsData = json_decode($commonsRaw, true);
                $cPages = array_values($commonsData['query']['pages'] ?? []);
                usort($cPages, function($a, $b) {
                    return (($a['index'] ?? 99) <=> ($b['index'] ?? 99));
                });
                foreach ($cPages as $cPg) {
                    $ii = $cPg['imageinfo'][0] ?? null;
                    if (!$ii) continue;
                    $mime = strtolower((string)($ii['mime'] ?? ''));
                    if (strpos($mime, 'image/') !== 0) continue;
                    $imgUrl = $ii['thumburl'] ?? $ii['url'] ?? '';
                    if (!empty($imgUrl)) {
                        $pgTitle = preg_replace('/^File:/i', '', (string)($cPg['title'] ?? $cleanArtistParams));
                        $badge = ($mode === 'logo' || stripos($pgTitle, 'logo') !== false || strpos($mime, 'svg') !== false)
                            ? 'Wikipedia Logo'
                            : 'Wikipedia';
                        $addItem($imgUrl, $badge, $pgTitle);
                    }
                }
            }

            if ($mode !== 'photo') {
                foreach ($wikipediaPhotoCandidates as $wpCand) {
                    $addItem($wpCand['url'], 'Wikipedia', $wpCand['title']);
                }
            }
        }

        // 2. SPOTIFY SOURCE
        if ($source === 'all' || $source === 'spotify') {
            $spotifyIds = $wikidataSpotifyIds;

            // Optional: if admin configured Spotify API credentials in settings
            try {
                $stmtSp = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('spotify_client_id', 'spotify_client_secret')");
                $spCfg = $stmtSp ? $stmtSp->fetchAll(PDO::FETCH_KEY_PAIR) : [];
                $spClientId = trim($spCfg['spotify_client_id'] ?? '');
                $spClientSecret = trim($spCfg['spotify_client_secret'] ?? '');
                if (!empty($spClientId) && !empty($spClientSecret) && function_exists('curl_init')) {
                    $chTok = curl_init('https://accounts.spotify.com/api/token');
                    curl_setopt($chTok, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chTok, CURLOPT_POST, true);
                    curl_setopt($chTok, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
                    curl_setopt($chTok, CURLOPT_HTTPHEADER, [
                        'Authorization: Basic ' . base64_encode($spClientId . ':' . $spClientSecret),
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                    curl_setopt($chTok, CURLOPT_TIMEOUT, 4);
                    $tokRes = json_decode(curl_exec($chTok), true);
                    curl_close($chTok);
                    if (!empty($tokRes['access_token'])) {
                        $spApiRaw = $fetchUrl(
                            'https://api.spotify.com/v1/search?q=' . urlencode($cleanArtistParams) . '&type=artist&limit=8',
                            ['Authorization: Bearer ' . $tokRes['access_token']],
                            4
                        );
                        $spApiData = json_decode($spApiRaw, true);
                        foreach (($spApiData['artists']['items'] ?? []) as $spArt) {
                            foreach (($spArt['images'] ?? []) as $spImg) {
                                if (!empty($spImg['url'])) {
                                    $addItem($spImg['url'], 'Spotify', $spArt['name'] ?? $cleanArtistParams);
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (Throwable $eSp) {}

            // Fetch official Spotify artist thumbnails via public Spotify oEmbed endpoint
            foreach (array_slice($spotifyIds, 0, 4) as $spId) {
                $oembedRaw = $fetchUrl('https://open.spotify.com/oembed?url=https://open.spotify.com/artist/' . urlencode($spId), [], 3);
                if (!empty($oembedRaw)) {
                    $oeData = json_decode($oembedRaw, true);
                    if (!empty($oeData['thumbnail_url'])) {
                        $addItem($oeData['thumbnail_url'], 'Spotify', $oeData['title'] ?? $cleanArtistParams);
                    }
                }
            }
        }

        // 3. DEEZER SOURCE
        if ($source === 'all' || $source === 'deezer') {
            $deezerUrl = 'https://api.deezer.com/search/artist?q=' . urlencode($cleanArtistParams) . '&limit=12';
            $resDz = $fetchUrl($deezerUrl, [], 4);
            if ($resDz) {
                $dataDz = json_decode($resDz, true);
                foreach (($dataDz['data'] ?? []) as $item) {
                    $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                    if (!empty($img)) {
                        $addItem($img, 'Deezer', $item['name'] ?? $cleanArtistParams);
                    }
                }
            }
        }

        // 4. LAST.FM SOURCE
        if ($source === 'lastfm') {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
            $stmt->execute();
            $customKey = $stmt->fetchColumn();
            $apiKey = $customKey ?: 'b25b959554ed76058ac220b7b2e0a026';

            $lastfmUrl = 'http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=' . urlencode($cleanArtistParams) . '&api_key=' . $apiKey . '&format=json';
            $resLfm = $fetchUrl($lastfmUrl, [], 4);
            if ($resLfm) {
                $data = json_decode($resLfm, true);
                $imgs = $data['artist']['image'] ?? [];
                foreach (['mega', 'extralarge', 'large'] as $sz) {
                    foreach ($imgs as $img) {
                        if (($img['size'] ?? '') === $sz && !empty($img['#text'])) {
                            $addItem($img['#text'], 'Last.fm', $cleanArtistParams);
                        }
                    }
                }
            }
        }

        while (ob_get_level() > 0) { @ob_end_clean(); }
        $items = array_values(array_slice($items, 0, 32));
        $images = array_values(array_map(function($it) { return $it['url']; }, $items));
        echo json_encode([
            'success' => true,
            'images' => $images,
            'items' => $items
        ]);
        break;

    case 'update_album_cover_url':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        $artist = trim($input['artist'] ?? '');
        $album = trim($input['album'] ?? '');
        $coverUrl = trim($input['cover_url'] ?? '');
        
        if (empty($artist) || empty($album) || empty($coverUrl)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Campos obrigatórios ausentes.']));
        }

        // Baixar a imagem para o servidor (/images)
        $localCover = phplayer_download_and_save_image($coverUrl, 'cover', $artist . '_' . $album);
        if (!empty($localCover)) {
            $coverUrl = $localCover;
        }
        
        $tables = get_songs_tables($pdo);
        foreach ($tables as $t) {
            $stmt = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ?");
            $stmt->execute([$coverUrl, $artist, $album]);
        }
        echo json_encode(['success' => true, 'cover_url' => $coverUrl, 'downloaded_to_server' => (strpos($coverUrl, 'images/') === 0)]);
        break;

    case 'artist_photos':
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->query("SELECT artist, artist_photo FROM artist_metadata WHERE artist_photo IS NOT NULL AND artist_photo != '' AND artist_photo != 'no_photo' AND artist_photo != 'no_cover' AND artist_photo NOT LIKE 'images/cover_%'");
        $photos = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
        echo json_encode(['success' => true, 'photos' => $photos ?: new stdClass()]);
        break;

    case 'update_artist_banner_url':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        $artist = trim($input['artist'] ?? '');
        $artistPhoto = trim($input['artist_photo'] ?? $input['artistPhoto'] ?? '');
        
        if (empty($artist) || empty($artistPhoto)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Campos obrigatórios ausentes.']));
        }

        // Sempre baixar a imagem atribuída para o servidor (/images)
        $localPhoto = phplayer_download_and_save_image($artistPhoto, 'artist', $artist);
        if (!empty($localPhoto)) {
            $artistPhoto = $localPhoto;
        }
        
        // Auto-create table
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
        $stmt->execute([$artist, $artistPhoto]);
        echo json_encode(['success' => true, 'artist_photo' => $artistPhoto, 'downloaded_to_server' => (strpos($artistPhoto, 'images/') === 0)]);
        break;

    case 'get_settings':
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['success' => true, 'settings' => $rows]);
        break;

    case 'save_settings':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        $key = trim($input['setting_key'] ?? $input['key'] ?? '');
        $val = trim($input['setting_value'] ?? $input['value'] ?? '');
        if (empty($key)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Chave de configuração vazia.']));
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $val]);
        echo json_encode(['success' => true]);
        break;

    case 'artist_covers_sync':
        if ($method !== 'POST' && $method !== 'GET') {
            http_response_code(405);
            exit(json_encode(['error' => 'Método não permitido.']));
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        @set_time_limit(0);
        @ignore_user_abort(true);

        $syncStartTime = microtime(true);
        $tables = get_songs_tables($pdo);
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // No início da sincronização (reset=1), limpamos 'no_photo'/'no_cover', capas de álbuns atribuídas por engano (images/cover_%)
        // e também registros cujo arquivo local em /images tenha sido apagado do servidor
        $resetFlag = !empty($input['reset']) || !empty($_GET['reset']);
        if ($resetFlag) {
            try {
                $pdo->exec("UPDATE artist_metadata SET artist_photo = '' WHERE artist_photo IN ('no_photo', 'no_cover') OR artist_photo LIKE 'images/cover_%'");
                $stmtCheckLocal = $pdo->query("SELECT artist, artist_photo FROM artist_metadata WHERE artist_photo IS NOT NULL AND artist_photo != ''");
                if ($stmtCheckLocal) {
                    $updMissing = $pdo->prepare("UPDATE artist_metadata SET artist_photo = '' WHERE artist = ?");
                    while ($rowChk = $stmtCheckLocal->fetch(PDO::FETCH_ASSOC)) {
                        $p = trim((string)($rowChk['artist_photo'] ?? ''));
                        if ($p !== '' && strpos($p, 'http://') !== 0 && strpos($p, 'https://') !== 0) {
                            $diskPath = __DIR__ . '/' . ltrim($p, '/');
                            if (!file_exists($diskPath) || filesize($diskPath) <= 200) {
                                $updMissing->execute([$rowChk['artist']]);
                            }
                        }
                    }
                }
            } catch (Throwable $eReset) {}
        }

        // Selecionar TODOS os artistas únicos do catálogo (excluindo podcasts e desconhecidos)
        // que estão sem capa de artista OU cuja imagem é capa de álbum (images/cover_%) OU ainda é URL externa
        $unionParts = [];
        foreach ($tables as $t) {
            $unionParts[] = "SELECT TRIM(artist) AS artist, genre FROM `" . $t . "`";
        }
        $unionArtistsSql = "SELECT DISTINCT s.artist, am.artist_photo AS existing_photo
            FROM (" . implode(" UNION ALL ", $unionParts) . ") s
            LEFT JOIN artist_metadata am ON s.artist = am.artist
            WHERE s.artist IS NOT NULL
              AND s.artist != ''
              AND LOWER(s.artist) NOT IN ('artista desconhecido', 'unknown artist', 'various artists', 'varios artistas', 'vários artistas', 'podcast', 'podcasts', 'rádio on-line')
              AND (s.genre IS NULL OR LOWER(TRIM(s.genre)) NOT IN ('podcast', 'podcasts'))
              AND (
                  am.artist IS NULL
                  OR am.artist_photo IS NULL
                  OR am.artist_photo = ''
                  OR am.artist_photo LIKE 'images/cover_%'
                  OR am.artist_photo LIKE 'http://%'
                  OR am.artist_photo LIKE 'https://%'
              )";

        $stmtAllPending = $pdo->query("SELECT artist, existing_photo FROM (" . $unionArtistsSql . ") AS t");
        $allPendingRows = $stmtAllPending ? $stmtAllPending->fetchAll(PDO::FETCH_ASSOC) : [];
        $artistsPendingInitial = count($allPendingRows);

        $artistUpdated = 0;
        $deezerCount = 0;
        $spotifyCount = 0;
        $downloadedCount = 0;
        $notFoundCount = 0;
        $processedCount = 0;
        $processedItems = [];

        // Processar TODOS os artistas em ondas paralelas via curl_multi_init (25 por onda para máxima velocidade)
        $chunks = array_chunk($allPendingRows, 25);
        foreach ($chunks as $chunk) {
            // Pré-buscar no Deezer em paralelo para todos os artistas da onda que não têm URL válida prévia
            $deezerResultsMap = [];
            if (function_exists('curl_multi_init') && function_exists('curl_init')) {
                $mh = @curl_multi_init();
                $handles = [];
                if ($mh) {
                    foreach ($chunk as $idx => $row) {
                        $art = trim((string)($row['artist'] ?? ''));
                        $existingPhoto = trim((string)($row['existing_photo'] ?? ''));
                        if ($art === '') continue;
                        if ((strpos($existingPhoto, 'http://') === 0 || strpos($existingPhoto, 'https://') === 0) && phplayer_is_valid_remote_artist_image($existingPhoto)) {
                            continue;
                        }
                        $dzUrl = 'https://api.deezer.com/search/artist?q=' . urlencode($art) . '&limit=10';
                        $ch = @curl_init($dzUrl);
                        if ($ch) {
                            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                            @curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                            @curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0 Safari/537.36');
                            @curl_multi_add_handle($mh, $ch);
                            $handles[$idx] = ['ch' => $ch, 'artist' => $art];
                        }
                    }

                    if (!empty($handles)) {
                        $running = null;
                        do {
                            $status = @curl_multi_exec($mh, $running);
                            if ($running > 0) {
                                @curl_multi_select($mh, 0.2);
                            }
                        } while ($running > 0 && $status === CURLM_OK);

                        foreach ($handles as $idx => $hInfo) {
                            $ch = $hInfo['ch'];
                            $art = $hInfo['artist'];
                            $rawDz = @curl_multi_getcontent($ch);
                            $httpCode = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            @curl_multi_remove_handle($mh, $ch);
                            @curl_close($ch);

                            if ($httpCode >= 200 && $httpCode < 300 && !empty($rawDz)) {
                                $dataDz = @json_decode($rawDz, true);
                                if (!empty($dataDz['data']) && is_array($dataDz['data'])) {
                                    $normTarget = mb_strtolower($art);
                                    $pickedDz = '';
                                    foreach ($dataDz['data'] as $item) {
                                        if (isset($item['type']) && $item['type'] !== 'artist') continue;
                                        $itemName = mb_strtolower(trim((string)($item['name'] ?? '')));
                                        $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                                        if ($itemName === $normTarget && strpos($img, '/images/cover/') === false && phplayer_is_valid_remote_artist_image($img)) {
                                            $pickedDz = $img;
                                            break;
                                        }
                                    }
                                    if ($pickedDz === '') {
                                        foreach ($dataDz['data'] as $item) {
                                            if (isset($item['type']) && $item['type'] !== 'artist') continue;
                                            $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                                            if (strpos($img, '/images/cover/') === false && phplayer_is_valid_remote_artist_image($img)) {
                                                $pickedDz = $img;
                                                break;
                                            }
                                        }
                                    }
                                    if ($pickedDz !== '') {
                                        $deezerResultsMap[$idx] = $pickedDz;
                                    }
                                }
                            }
                        }
                    }
                    @curl_multi_close($mh);
                }
            }

            foreach ($chunk as $idx => $row) {
                $art = trim((string)($row['artist'] ?? ''));
                $existingPhoto = trim((string)($row['existing_photo'] ?? ''));
                if ($art === '') continue;

                $processedCount++;
                $savedLocalPath = '';
                $usedSource = 'none';

                // 1) Se já tinha URL externa de artista atribuída, baixá-la para o servidor (/images/artist_...)
                if ((strpos($existingPhoto, 'http://') === 0 || strpos($existingPhoto, 'https://') === 0) && strpos($existingPhoto, '/images/cover/') === false && phplayer_is_valid_remote_artist_image($existingPhoto)) {
                    $savedLocalPath = phplayer_download_and_save_image($existingPhoto, 'artist', $art);
                    if ($savedLocalPath !== '') {
                        $usedSource = (stripos($existingPhoto, 'dzcdn.net') !== false || stripos($existingPhoto, 'deezer.com') !== false)
                            ? 'Deezer'
                            : ((stripos($existingPhoto, 'scdn.co') !== false || stripos($existingPhoto, 'spotify') !== false) ? 'Spotify' : 'Download Local');
                    }
                }

                // 2) Se encontrou no resultado paralelo do Deezer (1ª opção)
                if ($savedLocalPath === '' && !empty($deezerResultsMap[$idx])) {
                    $savedLocalPath = phplayer_download_and_save_image($deezerResultsMap[$idx], 'artist', $art);
                    if ($savedLocalPath !== '') {
                        $usedSource = 'Deezer';
                    }
                }

                // 3) Se não achou no pré-fetch ou se falhou o download, usar busca completa (Deezer -> Spotify automático)
                if ($savedLocalPath === '') {
                    $found = phplayer_find_artist_cover_deezer_or_spotify($art, $pdo);
                    if (!empty($found['url'])) {
                        $savedLocalPath = phplayer_download_and_save_image($found['url'], 'artist', $art);
                        if ($savedLocalPath !== '') {
                            $usedSource = $found['source'];
                        }
                    }
                }

                if ($savedLocalPath !== '') {
                    $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
                    $up->execute([$art, $savedLocalPath]);
                    $artistUpdated++;
                    $downloadedCount++;
                    if (strcasecmp($usedSource, 'Deezer') === 0) $deezerCount++;
                    if (strcasecmp($usedSource, 'Spotify') === 0) $spotifyCount++;
                    $processedItems[] = [
                        'artist' => $art,
                        'source' => $usedSource,
                        'status' => 'saved',
                        'local_path' => $savedLocalPath,
                        'photo' => $savedLocalPath,
                        'ok' => true
                    ];
                } else {
                    $notFoundCount++;
                    $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, 'no_photo') ON DUPLICATE KEY UPDATE artist_photo = 'no_photo'");
                    $up->execute([$art]);
                    $processedItems[] = [
                        'artist' => $art,
                        'source' => 'Não localizado',
                        'status' => 'not_found',
                        'local_path' => null,
                        'photo' => null,
                        'ok' => false
                    ];
                }
            }

            // Janela segura de 18s por requisição HTTP para evitar 504 Gateway Timeout em hospedagens com milhares de artistas;
            // se ainda restarem artistas após 18s, o frontend continua imediatamente na próxima requisição até zerar.
            if ((microtime(true) - $syncStartTime) >= 18.0) {
                break;
            }
        }

        $stmtTotalPendingAfter = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPendingFinal = intval($stmtTotalPendingAfter ? $stmtTotalPendingAfter->fetchColumn() : 0);

        echo json_encode([
            'success' => true,
            'batch_checked' => $processedCount,
            'artists_processed' => $processedCount,
            'artists_initial' => $artistsPendingInitial,
            'artists_updated' => $artistUpdated,
            'deezer_count' => $deezerCount,
            'spotify_count' => $spotifyCount,
            'downloaded_count' => $downloadedCount,
            'not_found_count' => $notFoundCount,
            'artists_pending' => $artistsPendingFinal,
            'processed_items' => $processedItems,
            'message' => "Sincronização de capas de artistas: {$artistUpdated} baixadas para o servidor (Deezer: {$deezerCount}, Spotify: {$spotifyCount}). Restantes: {$artistsPendingFinal}"
        ]);
        break;

    case 'clear_album_covers':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        try {
            $tables = get_songs_tables($pdo);
            $totalCleared = 0;
            foreach ($tables as $t) {
                $stmt = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = NULL WHERE (genre IS NULL OR LOWER(genre) NOT LIKE '%podcast%') AND (file_name IS NULL OR (LOWER(REPLACE(file_name, '\\\\', '/')) NOT LIKE 'podcast/%' AND LOWER(REPLACE(file_name, '\\\\', '/')) NOT LIKE '%/podcast/%'))");
                $stmt->execute();
                $totalCleared += $stmt->rowCount();
            }
            echo json_encode([
                'success' => true,
                'cleared_rows' => $totalCleared,
                'message' => "Todas as imagens de álbuns foram apagadas do banco de dados ({$totalCleared} faixas atualizadas)."
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao apagar imagens dos álbuns: ' . $e->getMessage()]);
        }
        break;

    case 'lastfm_sync':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        
        $apiKey = getenv('LASTFM_API_KEY') ?: 'b25b959554ed76058ac220b7b2e0a026';
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmtKey = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
            $stmtKey->execute();
            $dbKey = $stmtKey->fetchColumn();
            if ($dbKey) {
                $apiKey = $dbKey;
            }
        } catch (Exception $e) {}
        
        // Ensure artist_metadata exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Get list of overall unique artists that lack a photo/banner in metadata
        $tables = get_songs_tables($pdo);
        $unionParts = [];
        foreach ($tables as $t) {
            $unionParts[] = "SELECT artist FROM `" . $t . "`";
        }
        $unionArtistsSql = "SELECT DISTINCT s.artist 
            FROM (" . implode(" UNION ALL ", $unionParts) . ") s 
            LEFT JOIN artist_metadata am ON s.artist = am.artist 
            WHERE s.artist IS NOT NULL AND s.artist != '' AND (am.artist IS NULL OR am.artist_photo IS NULL OR am.artist_photo = '' OR am.artist_photo = 'no_photo')";
        
        $stmtTotalArtistsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPending = intval($stmtTotalArtistsPending->fetchColumn());

        // Get count of unique albums that have no cover or generic unsplash cover (ignoring Vários Artistas folder)
        $unionAlbumsParts = [];
        foreach ($tables as $t) {
            $unionAlbumsParts[] = "SELECT artist, album, cover_url, file_name, genre FROM `" . $t . "`";
        }
        $unionAlbumsSql = "SELECT DISTINCT artist, album 
            FROM (" . implode(" UNION ALL ", $unionAlbumsParts) . ") AS u 
            WHERE " . phplayer_album_cover_exclude_where();
        
        $stmtTotalAlbumsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPending = intval($stmtTotalAlbumsPending->fetchColumn());

        // Select up to 15 artists to fetch in this batch
        $stmt = $pdo->query("SELECT artist FROM (" . $unionArtistsSql . ") AS t LIMIT 15");
        $artists = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $artistUpdated = 0;
        foreach ($artists as $art) {
            // Fetch from Last.fm
            $url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=' . urlencode($art) . '&api_key=' . $apiKey . '&format=json&lang=pt';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $photoUrl = 'no_photo';
            $bioText = '';
            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['artist']['image']) && is_array($data['artist']['image'])) {
                    $sizePriority = ['mega' => 5, 'extralarge' => 4, 'large' => 3, 'medium' => 2, 'small' => 1];
                    $maxWeight = -1;
                    $bestImg = '';
                    foreach ($data['artist']['image'] as $img) {
                        $size = $img['size'] ?? '';
                        $t = $img['#text'] ?? '';
                        if ($t && !strpos($t, '2a96cbd8c706c450a33e3') && !strpos($t, '2a96cbd8b46e442fc41c2b86b821562f') && !strpos($t, 'default_album')) {
                            $weight = $sizePriority[$size] ?? -1;
                            if ($weight > $maxWeight) {
                                $maxWeight = $weight;
                                $bestImg = $t;
                            }
                        }
                    }
                    if ($bestImg) {
                        $localPhoto = phplayer_download_and_save_image($bestImg, 'artist', $art);
                        if ($localPhoto !== '') {
                            $photoUrl = $localPhoto;
                            $artistUpdated++;
                        }
                    }
                }
                if (isset($data['artist']['bio']['summary'])) {
                    $bioText = $data['artist']['bio']['summary'];
                }
            }
            // Se o Last.fm não retornou imagem (bloqueio padrão da API do Last.fm para fotos de artistas), usar fallback Deezer -> Spotify e baixar para o servidor
            if ($photoUrl === 'no_photo') {
                $found = phplayer_find_artist_cover_deezer_or_spotify($art, $pdo);
                if (!empty($found['url'])) {
                    $localPhoto = phplayer_download_and_save_image($found['url'], 'artist', $art);
                    if ($localPhoto !== '') {
                        $photoUrl = $localPhoto;
                        $artistUpdated++;
                    }
                }
            }
            
            $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo, bio) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo), bio = VALUES(bio)");
            $up->execute([$art, $photoUrl, $bioText]);
        }
        
        // Select up to 15 albums to process in this batch
        $stmt = $pdo->query("SELECT artist, album FROM (" . $unionAlbumsSql . ") AS t LIMIT 15");
        $albums = $stmt->fetchAll();
        
        $albumUpdated = 0;
        foreach ($albums as $alb) {
            $art = trim($alb['artist']);
            $title = trim($alb['album']);
            if (!$art || !$title || phplayer_is_unknown_artist($art) || phplayer_is_various_artists($art) || phplayer_is_various_artists_folder($art)) {
                continue;
            }
            
            // Fetch cover from Last.fm
            $url = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&artist=' . urlencode($art) . '&album=' . urlencode($title) . '&api_key=' . $apiKey . '&format=json';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $coverUrl = 'no_cover';
            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['album']['image']) && is_array($data['album']['image'])) {
                    $sizePriority = ['mega' => 5, 'extralarge' => 4, 'large' => 3, 'medium' => 2, 'small' => 1];
                    $maxWeight = -1;
                    $bestImg = '';
                    foreach ($data['album']['image'] as $img) {
                        $size = $img['size'] ?? '';
                        $t = $img['#text'] ?? '';
                        if ($t && !strpos($t, '2a96cbd8c706c450a33e3') && !strpos($t, 'default_album')) {
                            $weight = $sizePriority[$size] ?? -1;
                            if ($weight > $maxWeight) {
                                $maxWeight = $weight;
                                $bestImg = $t;
                            }
                        }
                    }
                    if ($bestImg) {
                        $localCover = phplayer_download_and_save_image($bestImg, 'cover', $art . '_' . $title);
                        $coverUrl = $localCover !== '' ? $localCover : $bestImg;
                        $albumUpdated++;
                    }
                }
            }
            
            foreach ($tables as $t) {
                $up = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ?");
                $up->execute([$coverUrl, $art, $title]);
            }
        }
        
        // Count again after database updates to return correct remaining count
        $stmtTotalArtistsPending2 = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPendingFinal = intval($stmtTotalArtistsPending2->fetchColumn());

        $stmtTotalAlbumsPending2 = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPendingFinal = intval($stmtTotalAlbumsPending2->fetchColumn());
        
        echo json_encode([
            'success' => true,
            'artists_updated' => $artistUpdated,
            'albums_updated' => $albumUpdated,
            'artists_pending' => $artistsPendingFinal,
            'albums_pending' => $albumsPendingFinal,
            'message' => "Lote de sincronização concluído! Atualizados {$artistUpdated} artistas e {$albumUpdated} capas. Itens pendentes: " . ($artistsPendingFinal + $albumsPendingFinal)
        ]);
        break;

    case 'deezer_sync':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        
        $tables = get_songs_tables($pdo);
        
        // Ensure artist_metadata exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 1. Sync artist photos via Deezer (fallback Spotify) e baixar para o servidor
        $unionParts = [];
        foreach ($tables as $t) {
            $unionParts[] = "SELECT artist FROM `" . $t . "`";
        }
        $unionArtistsSql = "SELECT DISTINCT s.artist 
            FROM (" . implode(" UNION ALL ", $unionParts) . ") s 
            LEFT JOIN artist_metadata am ON s.artist = am.artist 
            WHERE s.artist IS NOT NULL AND s.artist != '' AND (am.artist IS NULL OR am.artist_photo IS NULL OR am.artist_photo = '' OR am.artist_photo = 'no_photo')";
        
        $stmtTotalArtistsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPending = intval($stmtTotalArtistsPending->fetchColumn());
        
        // Select up to 15 artists to fetch in this batch
        $stmt = $pdo->query("SELECT artist FROM (" . $unionArtistsSql . ") AS t LIMIT 15");
        $artists = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $artistUpdated = 0;
        foreach ($artists as $art) {
            $photoUrl = 'no_photo';
            $found = phplayer_find_artist_cover_deezer_or_spotify($art, $pdo);
            if (!empty($found['url'])) {
                $localPhoto = phplayer_download_and_save_image($found['url'], 'artist', $art);
                $photoUrl = $localPhoto !== '' ? $localPhoto : $found['url'];
                $artistUpdated++;
            }
            
            $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
            $up->execute([$art, $photoUrl]);
        }
        
        // 2. Sync album covers via Deezer (ignoring Vários Artistas folder)
        $unionAlbumsParts = [];
        foreach ($tables as $t) {
            $unionAlbumsParts[] = "SELECT artist, album, cover_url, file_name, genre FROM `" . $t . "`";
        }
        $unionAlbumsSql = "SELECT DISTINCT artist, album 
            FROM (" . implode(" UNION ALL ", $unionAlbumsParts) . ") AS u 
            WHERE " . phplayer_album_cover_exclude_where();
        
        $stmtTotalAlbumsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPending = intval($stmtTotalAlbumsPending->fetchColumn());
        
        // Select up to 15 albums to process in this batch
        $stmt = $pdo->query("SELECT artist, album FROM (" . $unionAlbumsSql . ") AS t LIMIT 15");
        $albums = $stmt->fetchAll();
        
        $albumUpdated = 0;
        foreach ($albums as $alb) {
            $art = trim($alb['artist']);
            $title = trim($alb['album']);
            
            if (!$art || !$title || phplayer_is_unknown_artist($art) || phplayer_is_various_artists($art) || phplayer_is_various_artists_folder($art) || $title === 'Single' || $title === 'Álbum Desconhecido') {
                continue;
            }
            
            // Search album on Deezer
            // First attempt: search specifically by artist and album query format
            $url = 'https://api.deezer.com/search/album?q=artist:"' . urlencode($art) . '" album:"' . urlencode($title) . '"';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SubsonicPHPPlayer/1.0');
            $res = curl_exec($ch);
            curl_close($ch);
            
            $coverUrl = '';
            
            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    $firstMatch = $data['data'][0];
                    $coverUrl = $firstMatch['cover_xl'] ?? $firstMatch['cover_big'] ?? $firstMatch['cover_medium'] ?? $firstMatch['cover'] ?? '';
                }
            }
            
            // Second attempt: fallback to broader search if strict artist/album returned nothing
            if (empty($coverUrl)) {
                $url = 'https://api.deezer.com/search/album?q=' . urlencode($art . ' ' . $title);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'SubsonicPHPPlayer/1.0');
                $res = curl_exec($ch);
                curl_close($ch);
                
                if ($res) {
                    $data = json_decode($res, true);
                    if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                        $firstMatch = $data['data'][0];
                        $coverUrl = $firstMatch['cover_xl'] ?? $firstMatch['cover_big'] ?? $firstMatch['cover_medium'] ?? $firstMatch['cover'] ?? '';
                    }
                }
            }
            
            if ($coverUrl) {
                // Filter out default deezer cover images if any
                if (strpos($coverUrl, 'default_album') === false && strpos($coverUrl, 'images/cover') !== false) {
                    $localCover = phplayer_download_and_save_image($coverUrl, 'cover', $art . '_' . $title);
                    if ($localCover !== '') {
                        $coverUrl = $localCover;
                    }
                    foreach ($tables as $t) {
                        $up = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ?");
                        $up->execute([$coverUrl, $art, $title]);
                    }
                    $albumUpdated++;
                }
            }
        }
        
        // Count again after database updates to return correct remaining count
        $stmtTotalArtistsPending2 = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPendingFinal = intval($stmtTotalArtistsPending2->fetchColumn());
        
        $stmtTotalAlbumsPending2 = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPendingFinal = intval($stmtTotalAlbumsPending2->fetchColumn());
        
        echo json_encode([
            'success' => true,
            'artists_updated' => $artistUpdated,
            'albums_updated' => $albumUpdated,
            'artists_pending' => $artistsPendingFinal,
            'albums_pending' => $albumsPendingFinal,
            'message' => "Lote Deezer concluído! Atualizados {$artistUpdated} artistas e {$albumUpdated} capas de álbuns. Pendentes: " . ($artistsPendingFinal + $albumsPendingFinal)
        ]);
        break;

    case 'wikipedia_sync':
    case 'google_images_sync':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }

        $tables = get_songs_tables($pdo);
        $wikiUa = 'PHPlayerMusicSystem/1.4.7 (https://github.com/phplayer; contact@phplayer.org) PHP/' . PHP_VERSION;

        // Ensure artist_metadata exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 1. Sync artist photos/logos via Wikipedia & Wikidata
        $unionParts = [];
        foreach ($tables as $t) {
            $unionParts[] = "SELECT artist FROM `" . $t . "`";
        }
        $unionArtistsSql = "SELECT DISTINCT s.artist 
            FROM (" . implode(" UNION ALL ", $unionParts) . ") s 
            LEFT JOIN artist_metadata am ON s.artist = am.artist 
            WHERE s.artist IS NOT NULL AND s.artist != '' AND (am.artist IS NULL OR am.artist_photo IS NULL OR am.artist_photo = '' OR am.artist_photo = 'no_photo')";

        $stmtTotalArtistsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPending = intval($stmtTotalArtistsPending->fetchColumn());

        // Select up to 10 artists to fetch in this batch
        $stmt = $pdo->query("SELECT artist FROM (" . $unionArtistsSql . ") AS t LIMIT 10");
        $artists = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $artistUpdated = 0;
        foreach ($artists as $art) {
            $photoUrl = 'no_photo';
            foreach (['pt', 'en'] as $wpLang) {
                $wpUrl = 'https://' . $wpLang . '.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($art) . '&gsrlimit=3&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&format=json';
                $ch = curl_init($wpUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, $wikiUa);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                $wpRes = curl_exec($ch);
                curl_close($ch);
                if ($wpRes) {
                    $wpData = json_decode($wpRes, true);
                    $pages = array_values($wpData['query']['pages'] ?? []);
                    usort($pages, function($a, $b) {
                        return (($a['index'] ?? 99) <=> ($b['index'] ?? 99));
                    });
                    foreach ($pages as $pg) {
                        $cand = $pg['thumbnail']['source'] ?? $pg['original']['source'] ?? '';
                        if (!empty($cand) && !preg_match('/\.(pdf|djvu|ogg|oga|flac|wav|webm|ogv)(\?|$)/i', $cand)) {
                            $rawCand = strtok($cand, '?');
                            $localPhoto = phplayer_download_and_save_image($rawCand, 'artist', $art);
                            $photoUrl = $localPhoto !== '' ? $localPhoto : $rawCand;
                            $artistUpdated++;
                            break 2;
                        }
                    }
                }
            }

            $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
            $up->execute([$art, $photoUrl]);
        }

        // 2. Sync album covers via Wikipedia & iTunes (ignoring Vários Artistas folder)
        $unionAlbumsParts = [];
        foreach ($tables as $t) {
            $unionAlbumsParts[] = "SELECT artist, album, cover_url, file_name, genre FROM `" . $t . "`";
        }
        $unionAlbumsSql = "SELECT DISTINCT artist, album 
            FROM (" . implode(" UNION ALL ", $unionAlbumsParts) . ") AS u 
            WHERE " . phplayer_album_cover_exclude_where();

        $stmtTotalAlbumsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPending = intval($stmtTotalAlbumsPending->fetchColumn());

        $stmt = $pdo->query("SELECT artist, album FROM (" . $unionAlbumsSql . ") AS t LIMIT 10");
        $albums = $stmt->fetchAll();

        $albumUpdated = 0;
        foreach ($albums as $alb) {
            $art = trim($alb['artist']);
            $title = trim($alb['album']);

            if (!$art || !$title || phplayer_is_unknown_artist($art) || phplayer_is_various_artists($art) || phplayer_is_various_artists_folder($art) || $title === 'Single' || $title === 'Álbum Desconhecido') {
                continue;
            }

            $coverUrl = '';
            $query = $art . ' ' . $title;
            foreach (['pt', 'en'] as $wpLang) {
                $wpUrl = 'https://' . $wpLang . '.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=' . urlencode($query) . '&gsrlimit=3&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&format=json';
                $ch = curl_init($wpUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, $wikiUa);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                $wpRes = curl_exec($ch);
                curl_close($ch);
                if ($wpRes) {
                    $wpData = json_decode($wpRes, true);
                    $pages = array_values($wpData['query']['pages'] ?? []);
                    usort($pages, function($a, $b) {
                        return (($a['index'] ?? 99) <=> ($b['index'] ?? 99));
                    });
                    foreach ($pages as $pg) {
                        $cand = $pg['thumbnail']['source'] ?? $pg['original']['source'] ?? '';
                        if (!empty($cand) && !preg_match('/\.(pdf|djvu|ogg|oga|flac|wav|webm|ogv)(\?|$)/i', $cand)) {
                            $coverUrl = strtok($cand, '?');
                            break 2;
                        }
                    }
                }
            }

            // Fallback: iTunes Search API
            if (empty($coverUrl)) {
                $itunesUrl = 'https://itunes.apple.com/search?term=' . urlencode($art . ' ' . $title) . '&entity=album&limit=1';
                $ch = curl_init($itunesUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                curl_setopt($ch, CURLOPT_USERAGENT, $wikiUa);
                $itunesRes = curl_exec($ch);
                curl_close($ch);
                if ($itunesRes) {
                    $itunesData = json_decode($itunesRes, true);
                    if (isset($itunesData['results'][0]['artworkUrl100'])) {
                        $itunesCover = $itunesData['results'][0]['artworkUrl100'];
                        $coverUrl = str_replace('100x100bb', '600x600bb', $itunesCover);
                    }
                }
            }

            if ($coverUrl) {
                $localCover = phplayer_download_and_save_image($coverUrl, 'cover', $art . '_' . $title);
                if ($localCover !== '') {
                    $coverUrl = $localCover;
                }
                foreach ($tables as $t) {
                    $up = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ?");
                    $up->execute([$coverUrl, $art, $title]);
                }
                $albumUpdated++;
            }
        }

        $stmtTotalArtistsPending2 = $pdo->query("SELECT COUNT(*) FROM (" . $unionArtistsSql . ") AS t");
        $artistsPendingFinal = intval($stmtTotalArtistsPending2->fetchColumn());

        $stmtTotalAlbumsPending2 = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPendingFinal = intval($stmtTotalAlbumsPending2->fetchColumn());

        echo json_encode([
            'success' => true,
            'artists_updated' => $artistUpdated,
            'albums_updated' => $albumUpdated,
            'artists_pending' => $artistsPendingFinal,
            'albums_pending' => $albumsPendingFinal,
            'message' => "Lote Wikipedia concluído! Atualizados {$artistUpdated} artistas e {$albumUpdated} capas de álbuns. Pendentes: " . ($artistsPendingFinal + $albumsPendingFinal)
        ]);
        break;

    case 'album_cover':
        $artist = trim($_GET['artist'] ?? '');
        $album = trim($_GET['album'] ?? '');
        $source = trim($_GET['source'] ?? 'deezer'); // default to deezer for reliability
        if (!$artist || !$album) {
            echo json_encode(['success' => false, 'error' => 'Required parameters missing']);
            break;
        }
        if (phplayer_is_various_artists($artist) || phplayer_is_various_artists_folder($artist)) {
            echo json_encode(['success' => false, 'skipped' => true]);
            break;
        }
        try {
            $tablesChk = get_songs_tables($pdo);
            foreach ($tablesChk as $tc) {
                $stFn = $pdo->prepare("SELECT file_name FROM `" . $tc . "` WHERE artist = ? AND album = ? LIMIT 10");
                $stFn->execute([$artist, $album]);
                while ($fnVal = $stFn->fetchColumn()) {
                    if (phplayer_is_various_artists_folder($fnVal)) {
                        echo json_encode(['success' => false, 'skipped' => true]);
                        break 3;
                    }
                }
            }
        } catch (Throwable $eSkip) {}
        
        $coverUrl = '';
        if ($source === 'deezer') {
            // Fetch from Deezer API
            $url = 'https://api.deezer.com/search/album?q=artist:"' . urlencode($artist) . '" album:"' . urlencode($album) . '"';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SubsonicPHPPlayer/1.0');
            $res = curl_exec($ch);
            curl_close($ch);
            
            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    $firstMatch = $data['data'][0];
                    $coverUrl = $firstMatch['cover_xl'] ?? $firstMatch['cover_big'] ?? $firstMatch['cover_medium'] ?? $firstMatch['cover'] ?? '';
                }
            }
            
            // Fallback broader search
            if (empty($coverUrl)) {
                $url = 'https://api.deezer.com/search/album?q=' . urlencode($artist . ' ' . $album);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'SubsonicPHPPlayer/1.0');
                $res = curl_exec($ch);
                curl_close($ch);
                
                if ($res) {
                    $data = json_decode($res, true);
                    if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                        $firstMatch = $data['data'][0];
                        $coverUrl = $firstMatch['cover_xl'] ?? $firstMatch['cover_big'] ?? $firstMatch['cover_medium'] ?? $firstMatch['cover'] ?? '';
                    }
                }
            }
        } else {
            // Fetch cover from Last.fm
            $apiKey = getenv('LASTFM_API_KEY') ?: 'b25b959554ed76058ac220b7b2e0a026';
            try {
                $stmtKey = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
                $stmtKey->execute();
                $dbKey = $stmtKey->fetchColumn();
                if ($dbKey) {
                    $apiKey = $dbKey;
                }
            } catch (Exception $e) {}
            
            $url = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&artist=' . urlencode($artist) . '&album=' . urlencode($album) . '&api_key=' . $apiKey . '&format=json';
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 && $res) {
                $data = json_decode($res, true);
                if (isset($data['album']['image']) && is_array($data['album']['image'])) {
                    $sizePriority = ['mega' => 5, 'extralarge' => 4, 'large' => 3, 'medium' => 2, 'small' => 1];
                    $maxWeight = -1;
                    $bestImg = '';
                    foreach ($data['album']['image'] as $img) {
                        $size = $img['size'] ?? '';
                        $t = $img['#text'] ?? '';
                        if ($t && !strpos($t, '2a96cbd8c706c450a33e3') && !strpos($t, 'default_album')) {
                            $weight = $sizePriority[$size] ?? -1;
                            if ($weight > $maxWeight) {
                                $maxWeight = $weight;
                                $bestImg = $t;
                            }
                        }
                    }
                    if ($bestImg) {
                        $coverUrl = $bestImg;
                    }
                }
            }
        }
        
        if ($coverUrl) {
            $localCover = phplayer_download_and_save_image($coverUrl, 'cover', $artist . '_' . $album);
            if ($localCover !== '') {
                $coverUrl = $localCover;
            }
            $tables = get_songs_tables($pdo);
            foreach ($tables as $t) {
                $stmt = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ? AND (cover_url IS NULL OR cover_url LIKE '%unsplash.com%' OR cover_url LIKE '%images.unsplash%')");
                $stmt->execute([$coverUrl, $artist, $album]);
            }
            echo json_encode(['success' => true, 'cover_url' => $coverUrl]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'stream':
        $id = intval($_GET['id'] ?? 0);
        $table = get_song_table_by_id($pdo, $id);
        $stmt = $pdo->prepare("SELECT file_name FROM `" . $table . "` WHERE id = ?");
        $stmt->execute([$id]);
        $song = $stmt->fetch();
        if (!$song) {
            http_response_code(404);
            exit("Não encontrado");
        }
        $possiblePaths = [
            UPLOAD_DIR . $song['file_name'],
            UPLOAD_DIR . '/' . $song['file_name'],
            __DIR__ . '/' . $song['file_name'],
            (defined('PODCAST_DIR') ? PODCAST_DIR . preg_replace('#^podcast/#', '', $song['file_name']) : ''),
            (defined('PODCAST_DIR') ? PODCAST_DIR . $song['file_name'] : ''),
            __DIR__ . '/music/' . $song['file_name']
        ];
        $filePath = null;
        $remoteUrlSidecar = null;
        foreach ($possiblePaths as $cand) {
            if (!empty($cand) && file_exists($cand) && is_file($cand) && filesize($cand) > 1000) {
                $filePath = $cand;
                break;
            }
            if (!empty($cand) && file_exists($cand . '.url') && is_file($cand . '.url')) {
                $u = trim((string)@file_get_contents($cand . '.url'));
                if (!empty($u) && (strpos($u, 'http://') === 0 || strpos($u, 'https://') === 0)) {
                    $remoteUrlSidecar = $u;
                }
            }
        }
        if (!$filePath && (strpos($song['file_name'], 'http://') === 0 || strpos($song['file_name'], 'https://') === 0)) {
            $remoteUrlSidecar = $song['file_name'];
        }

        // Registrar reprodução de forma leve e estatística no banco de dados automaticamente
        try {
            $pdo->prepare("UPDATE `" . $table . "` SET play_count = play_count + 1, last_played = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
        } catch (Exception $log_ex) {}

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        while (ob_get_level()) {
            @ob_end_clean();
        }

        // Se for um episódio de podcast ainda não baixado por completo no disco (possui sidecar .url), fazer stream transparente via cURL
        if (!$filePath && !empty($remoteUrlSidecar) && function_exists('curl_init')) {
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            @set_time_limit(0);
            header("Access-Control-Allow-Origin: *");
            header("Accept-Ranges: bytes");
            header("X-Accel-Buffering: no");

            $reqHeaders = ['Accept: */*'];
            if (isset($_SERVER['HTTP_RANGE'])) {
                $reqHeaders[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
            }

            $headersForwarded = false;
            $statusCodeToSend = 200;
            $respContentType = 'audio/mpeg';
            $respContentLength = '';
            $respContentRange = '';

            $ch = curl_init($remoteUrlSidecar);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $headerLine) use (&$statusCodeToSend, &$respContentType, &$respContentLength, &$respContentRange) {
                $len = strlen($headerLine);
                if (preg_match('/^HTTP\/[0-9.]+\s+(\d+)/i', $headerLine, $m)) {
                    $code = intval($m[1]);
                    if ($code === 200 || $code === 206) {
                        $statusCodeToSend = $code;
                        $respContentLength = '';
                        $respContentRange = '';
                    }
                } elseif (stripos($headerLine, 'content-type:') === 0) {
                    $ct = trim(substr($headerLine, 13));
                    if (stripos($ct, 'audio/') !== false || stripos($ct, 'application/octet-stream') !== false) {
                        $respContentType = 'audio/mpeg';
                    }
                } elseif (stripos($headerLine, 'content-length:') === 0) {
                    $respContentLength = trim(substr($headerLine, 15));
                } elseif (stripos($headerLine, 'content-range:') === 0) {
                    $respContentRange = trim(substr($headerLine, 14));
                }
                return $len;
            });
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$headersForwarded, &$statusCodeToSend, &$respContentType, &$respContentLength, &$respContentRange) {
                if (connection_aborted()) return 0;
                $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                if ($httpCode >= 300 && $httpCode < 400) {
                    return strlen($data);
                }
                if ($httpCode >= 400) {
                    return 0;
                }
                if (!$headersForwarded) {
                    $headersForwarded = true;
                    if ($statusCodeToSend === 206) {
                        header('HTTP/1.1 206 Partial Content');
                    } else {
                        header('HTTP/1.1 200 OK');
                    }
                    header('Content-Type: ' . $respContentType);
                    if (!empty($respContentRange)) header('Content-Range: ' . $respContentRange);
                    if (!empty($respContentLength)) header('Content-Length: ' . $respContentLength);
                }
                echo $data;
                @flush();
                return strlen($data);
            });
            @curl_exec($ch);
            @curl_close($ch);
            exit;
        }

        if (!$filePath) {
            http_response_code(404);
            exit("Sem arquivo");
        }
        $size = filesize($filePath);
        $fp = fopen($filePath, 'rb');
        
        // Caching headers de Alta Performance e compatibilidade com ETag
        $mtime = filemtime($filePath);
        $etag = md5($filePath . $mtime . $size);
        header('Cache-Control: public, max-age=86400');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('ETag: "' . $etag . '"');
        
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
            fclose($fp);
            http_response_code(304);
            exit;
        }

        $start = 0;
        $end = $size - 1;
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                $start = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: " . ($end - $start + 1));
            fseek($fp, $start);
        } else {
            header('HTTP/1.1 200 OK');
            header("Content-Length: $size");
        }
        header("Content-Type: audio/mpeg");
        header("Accept-Ranges: bytes");
        while (!feof($fp) && ($start <= $end)) {
            $chunk = min(8192, $end - $start + 1);
            echo fread($fp, $chunk);
            flush();
            $start += $chunk;
        }
        fclose($fp);
        exit;

    case 'download_track':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
        }
        $id = intval($_GET['id'] ?? 0);
        $table = get_song_table_by_id($pdo, $id);
        $stmt = $pdo->prepare("SELECT file_name, title FROM `" . $table . "` WHERE id = ?");
        $stmt->execute([$id]);
        $song = $stmt->fetch();
        if (!$song) {
            http_response_code(404);
            exit("Não encontrado");
        }
        $filePath = UPLOAD_DIR . $song['file_name'];
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . '/music/' . $song['file_name'];
        }
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit("Sem arquivo");
        }
        if (ob_get_level()) {
            ob_end_clean();
        }
        $filename = str_replace('"', '_', $song['title']) . '.mp3';
        $fallbackFilename = preg_replace('/[^a-zA-Z0-9_\\-\\.]/', '_', $song['title']) . '.mp3';
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fallbackFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;

    case 'download_album':
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
        }
        $albumName = $_GET['album'] ?? '';
        if (empty($albumName)) {
            http_response_code(400);
            exit("Álbum não especificado");
        }
        $tables = get_songs_tables($pdo);
        $songs = [];
        foreach ($tables as $t) {
            $stmt = $pdo->prepare("SELECT id, file_name, title FROM `" . $t . "` WHERE album = ?");
            $stmt->execute([$albumName]);
            $songs = array_merge($songs, $stmt->fetchAll());
        }
        if (empty($songs)) {
            http_response_code(404);
            exit("Álbum vazio ou não encontrado");
        }
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zipFile = tempnam(sys_get_temp_dir(), 'album_zip');
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($songs as $song) {
                    $filePath = UPLOAD_DIR . $song['file_name'];
                    if (!file_exists($filePath)) {
                        $filePath = __DIR__ . '/music/' . $song['file_name'];
                    }
                    if (file_exists($filePath)) {
                        $safeTitle = preg_replace('/[^a-zA-Z0-9_\\-\\.\\s]/i', '', $song['title']);
                        if (empty($safeTitle)) {
                            $safeTitle = "track_" . $song['id'];
                        }
                        $zip->addFile($filePath, $safeTitle . '.mp3');
                    }
                }
                $zip->close();
                if (ob_get_level()) {
                    ob_end_clean();
                }
                $filename = str_replace('"', '_', $albumName) . '.zip';
                $fallbackFilename = preg_replace('/[^a-zA-Z0-9_\\-\\.]/', '_', $albumName) . '.zip';
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $fallbackFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
                unlink($zipFile);
                exit;
            } else {
                http_response_code(500);
                exit("Erro ao criar arquivo ZIP");
            }
        } else {
            http_response_code(500);
            exit("A extensão ZipArchive do PHP não está instalada neste servidor.");
        }

    

    case 'files_list':
        $subpath = isset($_GET['path']) ? $_GET['path'] : '';
        $realBase = realpath(__DIR__);
        
        $is_valid_file_path = function($subpath, $realBase) {
            $subpath = trim(trim($subpath), '/\\');
            if (empty($subpath) || $subpath === '.') {
                return 'root';
            }
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) { @mkdir($musicDir, 0777, true); }
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) { return false; }
            $allowedMusic = realpath($musicDir);
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolved = $is_valid_file_path($subpath, $realBase);
        if ($resolved === false) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado ou diretório fora do escopo (/music).']));
        }
        
        if ($resolved === 'root') {
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) { @mkdir($musicDir, 0777, true); }
            
            $musicStats = get_dir_size_and_subdirs($musicDir);
            $items = [
                [
                    'name' => 'music',
                    'path' => 'music',
                    'is_dir' => true,
                    'size' => $musicStats['size'],
                    'direct_subdirs' => $musicStats['direct_subdirs'],
                    'subdirs_count' => $musicStats['direct_subdirs'],
                    'total_subdirs' => $musicStats['total_subdirs'],
                    'files_count' => $musicStats['files'],
                    'mtime' => @filemtime($musicDir) ?: time()
                ]
            ];
            echo json_encode([
                'success' => true,
                'current_path' => '',
                'is_root' => true,
                'items' => $items
            ]);
            break;
        }
        
        $targetDir = $resolved;
        $items = [];
        if ($handle = opendir($targetDir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $fullPath = $targetDir . '/' . $entry;
                $relativeDir = trim(str_replace($realBase, '', $targetDir), '/\\');
                $relativeItem = $relativeDir ? $relativeDir . '/' . $entry : $entry;
                $isDir = is_dir($fullPath);
                
                $dirStats = null;
                if ($isDir) {
                    $dirStats = get_dir_size_and_subdirs($fullPath);
                    $size = $dirStats['size'];
                } else {
                    $size = @filesize($fullPath);
                }
                $mtime = @filemtime($fullPath);

                $items[] = [
                    'name' => $entry,
                    'path' => $relativeItem,
                    'is_dir' => $isDir,
                    'size' => $size ? (int)$size : 0,
                    'direct_subdirs' => $isDir ? $dirStats['direct_subdirs'] : 0,
                    'subdirs_count' => $isDir ? $dirStats['direct_subdirs'] : 0,
                    'total_subdirs' => $isDir ? $dirStats['total_subdirs'] : 0,
                    'files_count' => $isDir ? $dirStats['files'] : 0,
                    'mtime' => $mtime ? $mtime : time()
                ];
            }
            closedir($handle);
        }
        usort($items, function($a, $b) {
            if ($a['is_dir'] && !$b['is_dir']) return -1;
            if (!$a['is_dir'] && $b['is_dir']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });
        echo json_encode([
            'success' => true,
            'current_path' => trim(str_replace($realBase, '', $targetDir), '/\\'),
            'is_root' => false,
            'items' => $items
        ]);
        break;

    case 'files_create_dir':
        $subpath = isset($_POST['path']) ? trim($_POST['path']) : '';
        $realBase = realpath(__DIR__);
        
        $musicDir = $realBase . '/music';
        if (!file_exists($musicDir)) {
            @mkdir($musicDir, 0777, true);
            @chmod($musicDir, 0777);
        }
        $allowedMusic = realpath($musicDir);
        
        $subpathClean = trim($subpath, '/\\');
        if (empty($subpathClean) || $subpathClean === '.' || $subpathClean === 'root') {
            $parentDir = $allowedMusic;
        } else {
            $targetPath = realpath($realBase . '/' . $subpathClean);
            if ($targetPath === false || $allowedMusic === false || ($targetPath !== $allowedMusic && strpos($targetPath, $allowedMusic . DIRECTORY_SEPARATOR) !== 0 && strpos($targetPath, $allowedMusic . '/') !== 0)) {
                http_response_code(403);
                exit(json_encode(['error' => 'Acesso negado: destino inválido ou fora dos limites (/music).']));
            }
            $parentDir = $targetPath;
        }

        $newDirName = isset($_POST['name']) ? trim($_POST['name']) : '';
        $newDirName = preg_replace('/[^\p{L}\p{N}_\-\s\.]/u', '', $newDirName);
        $newDirName = trim($newDirName);
        if (empty($newDirName)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Nome da pasta inválido.']));
        }
        $newDirPath = $parentDir . '/' . $newDirName;
        if (file_exists($newDirPath)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Uma pasta ou arquivo com este nome já existe.']));
        }
        if (@mkdir($newDirPath, 0777, true)) {
            @chmod($newDirPath, 0777);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            exit(json_encode(['error' => 'Falha ao criar pasta. Verifique permissões de gravação.']));
        }
        break;

    case 'files_delete':
        $subpath = isset($_POST['path']) ? trim($_POST['path']) : '';
        $realBase = realpath(__DIR__);
        
        $is_valid_file_path = function($subpath, $realBase) {
            $subpath = trim(trim($subpath), '/\\');
            if (empty($subpath) || $subpath === '.') {
                return 'root';
            }
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) { @mkdir($musicDir, 0777, true); }
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) { return false; }
            $allowedMusic = realpath($musicDir);
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolved = $is_valid_file_path($subpath, $realBase);
        if ($resolved === false || $resolved === 'root' || $resolved === realpath($realBase . '/music')) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: pasta raiz do sistema ou protegida.']));
        }
        $targetPath = $resolved;
        $protected = ['index.php', 'api.php', 'config.php', 'mobile.php', 'debug.php', 'package.json', 'server.ts', 'tsconfig.json', '.env', '.env.example', 'node_modules', 'src', 'dist', 'api_errors.log'];
        $targetName = basename($targetPath);
        if (in_array(strtolower($targetName), $protected)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Protegido do sistema.']));
        }
        function rmdir_recursive_php($dir) {
            if (empty($dir) || !file_exists($dir)) {
                return true;
            }
            @chmod($dir, 0777);
            if (!is_dir($dir)) {
                return @unlink($dir);
            }
            $scan = @scandir($dir);
            if ($scan === false) {
                return false;
            }
            $items = array_diff($scan, ['.', '..']);
            $success = true;
            foreach ($items as $item) {
                $path = $dir . '/' . $item;
                if (is_dir($path)) {
                    if (!rmdir_recursive_php($path)) {
                        $success = false;
                    }
                } else {
                    @chmod($path, 0777);
                    if (!@unlink($path)) {
                        $success = false;
                    }
                }
            }
            if (file_exists($dir) && is_dir($dir)) {
                return $success ? @rmdir($dir) : false;
            }
            return true;
        }
        if (rmdir_recursive_php($targetPath)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            exit(json_encode(['error' => 'Falha ao excluir arquivo ou pasta. Verifique as permissões de gravação do servidor no diretório /music.']));
        }
        break;

    case 'files_rename':
        $oldSubpath = isset($_POST['old_path']) ? trim($_POST['old_path']) : '';
        $newSubpath = isset($_POST['new_path']) ? trim($_POST['new_path']) : '';
        if (empty($oldSubpath) || empty($newSubpath)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Caminhos vazios.']));
        }
        $realBase = realpath(__DIR__);
        
        $is_valid_file_path = function($subpath, $realBase) {
            $subpath = trim(trim($subpath), '/\\');
            if (empty($subpath) || $subpath === '.') {
                return 'root';
            }
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) { @mkdir($musicDir, 0777, true); }
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) { return false; }
            $allowedMusic = realpath($musicDir);
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolvedOld = $is_valid_file_path($oldSubpath, $realBase);
        if ($resolvedOld === false || $resolvedOld === 'root' || $resolvedOld === realpath($realBase . '/music')) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado à pasta de origem ou protegida.']));
        }
        
        $newParentSub = dirname($newSubpath);
        if ($newParentSub === '.' || $newParentSub === '/' || empty($newParentSub)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: não é permitido colocar itens no topo do diretório.']));
        }
        $resolvedNewParent = $is_valid_file_path($newParentSub, $realBase);
        if ($resolvedNewParent === false || $resolvedNewParent === 'root') {
            http_response_code(403);
            exit(json_encode(['error' => 'Destino inválido ou fora dos limites (/music).']));
        }
        
        $oldPath = $resolvedOld;
        $newPath = $resolvedNewParent . '/' . basename($newSubpath);
        
        $protected = ['index.php', 'api.php', 'config.php', 'mobile.php', 'debug.php', 'package.json', 'server.ts', 'tsconfig.json', '.env', '.env.example', 'node_modules', 'src', 'dist', 'api_errors.log'];
        $oldName = basename($oldPath);
        if (in_array(strtolower($oldName), $protected)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Este arquivo ou pasta é protegido do sistema e não pode ser renomeado.']));
        }
        if (file_exists($newPath)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Um arquivo ou pasta com este nome de destino já existe.']));
        }
        if (rename($oldPath, $newPath)) {
            // Update database records if renaming music or video files/folders
            try {
                $oldRelMusic = preg_replace('#^music/#i', '', $oldSubpath);
                $newRelMusic = preg_replace('#^music/#i', '', $newSubpath);
                if ($oldSubpath !== $oldRelMusic) {
                    if (function_exists('get_songs_tables')) {
                        $tables = get_songs_tables($pdo);
                        foreach ($tables as $t) {
                            $stmt = $pdo->prepare("UPDATE `$t` SET file_name = REPLACE(file_name, ?, ?) WHERE file_name = ? OR file_name LIKE ?");
                            $stmt->execute([$oldRelMusic, $newRelMusic, $oldRelMusic, $oldRelMusic . '/%']);
                        }
                    }
                }

            } catch (Exception $dbErr) {
                // Ignore DB update errors if tables do not exist
            }
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            exit(json_encode(['error' => 'Falha ao renomear arquivo ou pasta.']));
        }
        break;

    case 'files_upload':
        $subpath = isset($_POST['path']) ? trim($_POST['path']) : '';
        $relativePath = isset($_POST['relative_path']) ? trim($_POST['relative_path']) : '';
        $realBase = realpath(__DIR__);
        
        $musicDir = $realBase . '/music';
        if (!file_exists($musicDir)) {
            @mkdir($musicDir, 0777, true);
            @chmod($musicDir, 0777);
        }
        $allowedMusic = realpath($musicDir);
        
        // Default to 'music' if subpath is empty or virtual root
        $subpathClean = trim($subpath, '/\\');
        if (empty($subpathClean) || $subpathClean === '.' || $subpathClean === 'root') {
            $targetDir = $allowedMusic;
        } else {
            $targetPath = realpath($realBase . '/' . $subpathClean);
            if ($targetPath === false && strpos($subpathClean, '..') === false) {
                $candidate = $realBase . '/' . $subpathClean;
                @mkdir($candidate, 0777, true);
                @chmod($candidate, 0777);
                $targetPath = realpath($candidate);
            }
            if ($targetPath === false || $allowedMusic === false || ($targetPath !== $allowedMusic && strpos($targetPath, $allowedMusic . DIRECTORY_SEPARATOR) !== 0 && strpos($targetPath, $allowedMusic . '/') !== 0)) {
                http_response_code(403);
                exit(json_encode(['error' => 'Acesso negado: pasta de destino fora dos limites permitidos (/music).']));
            }
            $targetDir = $targetPath;
        }

        if (!isset($_FILES['file'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Nenhum arquivo enviado.']));
        }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            exit(json_encode(['error' => 'Erro no upload do arquivo (Código: ' . $file['error'] . ').']));
        }

        $destDir = $targetDir;
        $origName = basename($file['name']);
        $origExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $uploadedName = $origName;

        // Process nested subdirectories from relative_path (e.g. ArtistName/AlbumName/song.mp3)
        if (!empty($relativePath)) {
            $relativePath = str_replace('\\', '/', $relativePath);
            $rawSegments = explode('/', $relativePath);
            $cleanSegments = [];
            foreach ($rawSegments as $seg) {
                $seg = trim($seg);
                $clean = preg_replace('/[^\p{L}\p{N}_\-\.\s\(\)\[\]]/u', '', $seg);
                $clean = trim($clean);
                if ($clean !== '' && $clean !== '.' && $clean !== '..') {
                    $cleanSegments[] = $clean;
                }
            }
            if (count($cleanSegments) > 0) {
                $leafName = array_pop($cleanSegments);
                if (!empty($leafName)) {
                    $uploadedName = $leafName;
                }
                if (count($cleanSegments) > 0) {
                    $subDirRel = implode('/', $cleanSegments);
                    $destDir = $targetDir . '/' . $subDirRel;
                    if (!file_exists($destDir)) {
                        @mkdir($destDir, 0777, true);
                        @chmod($destDir, 0777);
                    }
                }
            }
        }

        // Sanitize file name
        $cleanBaseName = preg_replace('/[^\p{L}\p{N}_\-\.\s\(\)\[\]]/u', '', $uploadedName);
        $cleanBaseName = trim($cleanBaseName);
        if (empty($cleanBaseName)) {
            $uploadedName = 'upload_' . uniqid() . ($origExt ? '.' . $origExt : '');
        } else {
            $uploadedName = $cleanBaseName;
        }

        // Security: Prevent uploading dangerous executable scripts
        $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));
        $disallowedExts = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'cgi', 'sh', 'bash', 'exe', 'bat', 'cmd', 'py', 'pl', 'htaccess'];
        if (in_array($ext, $disallowedExts)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Upload negado: tipo de arquivo não permitido por segurança.']));
        }

        $destPath = $destDir . '/' . $uploadedName;
        $protected = ['index.php', 'api.php', 'config.php', 'mobile.php', 'debug.php', 'package.json', 'server.ts', 'tsconfig.json', '.env', '.env.example', 'version.php', 'changelog.php', 'shared.php'];
        if (in_array(strtolower($uploadedName), $protected)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Upload negado: impossível sobrescrever arquivo protegido do sistema.']));
        }

        if (!file_exists($destDir)) {
            @mkdir($destDir, 0777, true);
            @chmod($destDir, 0777);
        }

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            @chmod($destPath, 0666);
            echo json_encode([
                'success' => true,
                'filename' => $uploadedName,
                'dest' => str_replace($realBase, '', $destPath),
                'relative' => trim(str_replace($realBase, '', $destPath), '/\\')
            ]);
        } else {
            http_response_code(500);
            exit(json_encode(['error' => 'Falha ao mover arquivo enviado para o destino. Verifique permissões de gravação na pasta do servidor.']));
        }
        break;

    case 'podcasts':
        if ($method === 'GET') {
            $podcastTracks = [];
            try {
                $tables = function_exists('get_songs_tables') ? get_songs_tables($pdo) : ['songs'];
                foreach ($tables as $tbl) {
                    $q = $pdo->query("SELECT * FROM `" . $tbl . "` WHERE LOWER(genre) LIKE '%podcast%' OR LOWER(file_name) LIKE 'podcast/%' OR LOWER(file_name) LIKE '%/podcast/%' OR LOWER(artist) = 'podcast' OR LOWER(album) = 'podcast'");
                    if ($q) {
                        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
                        if (is_array($rows)) {
                            $podcastTracks = array_merge($podcastTracks, $rows);
                        }
                    }
                }
                usort($podcastTracks, function($a, $b) {
                    $ta = strtotime($a['created_at'] ?? '1970-01-01');
                    $tb = strtotime($b['created_at'] ?? '1970-01-01');
                    if ($ta == $tb) return intval($b['id'] ?? 0) - intval($a['id'] ?? 0);
                    return $tb - $ta;
                });
            } catch (Throwable $podEx) {
                $stmt = $pdo->query("SELECT * FROM songs WHERE genre = 'Podcast'");
                $podcastTracks = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            }
            $podcastsMap = [];

            foreach ($podcastTracks as $t) {
                $podName = $t['album'];
                if (!isset($podcastsMap[$podName])) {
                    $feedUrlFromDb = '';
                    $limitFromDb = 5;
                    $coverFromDb = $t['cover_url'] ?? '';
                    $artistFromDb = $t['artist'] ?? $podName;

                    try {
                        $stmtUrl = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $stmtUrl->execute(["podcast_feed_" . $podName]);
                        $feedUrlFromDb = $stmtUrl->fetchColumn() ?: '';
                        
                        $stmtLimit = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $stmtLimit->execute(["podcast_limit_" . $podName]);
                        $limitVal = $stmtLimit->fetchColumn();
                        if ($limitVal !== false && $limitVal !== null) {
                            $limitFromDb = ($limitVal === 'all' || $limitVal === '0') ? 'all' : intval($limitVal);
                        }

                        $stmtCov = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $stmtCov->execute(["podcast_cover_" . $podName]);
                        $covVal = $stmtCov->fetchColumn();
                        if (!empty($covVal)) {
                            $coverFromDb = $covVal;
                        }

                        $stmtArt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $stmtArt->execute(["podcast_artist_" . $podName]);
                        $artVal = $stmtArt->fetchColumn();
                        if (!empty($artVal)) {
                            $artistFromDb = $artVal;
                        }
                    } catch (Exception $e) {}

                    $podcastsMap[$podName] = [
                        'name' => $podName,
                        'artist' => $artistFromDb,
                        'coverUrl' => $coverFromDb ?: 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150',
                        'feedUrl' => $feedUrlFromDb,
                        'limit' => $limitFromDb,
                        'episodes' => []
                    ];
                }
                $podcastsMap[$podName]['episodes'][] = [
                    'id' => strval($t['id']),
                    'title' => $t['title'],
                    'artist' => $t['artist'],
                    'album' => $t['album'],
                    'duration' => intval($t['duration']),
                    'fileName' => $t['file_name'],
                    'fileSize' => intval($t['file_size']),
                    'coverUrl' => $t['cover_url'],
                    'genre' => $t['genre'],
                    'createdAt' => $t['created_at']
                ];
            }

            // Preservar e incluir todos os canais de podcasts registrados em `settings`
            try {
                $stmtFeeds = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'podcast_feed_%'");
                $savedFeeds = $stmtFeeds ? $stmtFeeds->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($savedFeeds as $sf) {
                    $podName = substr($sf['setting_key'], strlen('podcast_feed_'));
                    if (empty($podName)) continue;
                    $feedUrl = trim($sf['setting_value'] ?? '');

                    if (!isset($podcastsMap[$podName])) {
                        $limitVal = 5;
                        try {
                            $stLimit = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                            $stLimit->execute(["podcast_limit_" . $podName]);
                            $lRaw = $stLimit->fetchColumn();
                            if ($lRaw !== false && $lRaw !== null) {
                                $limitVal = ($lRaw === 'all' || $lRaw === '0') ? 'all' : intval($lRaw);
                            }
                        } catch (Exception $e) {}

                        $coverUrl = 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150';
                        try {
                            $stCov = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                            $stCov->execute(["podcast_cover_" . $podName]);
                            $cRaw = $stCov->fetchColumn();
                            if (!empty($cRaw)) {
                                $coverUrl = $cRaw;
                            }
                        } catch (Exception $e) {}

                        $artistName = $podName;
                        try {
                            $stArt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                            $stArt->execute(["podcast_artist_" . $podName]);
                            $aRaw = $stArt->fetchColumn();
                            if (!empty($aRaw)) {
                                $artistName = $aRaw;
                            }
                        } catch (Exception $e) {}

                        $podcastsMap[$podName] = [
                            'name' => $podName,
                            'artist' => $artistName,
                            'coverUrl' => $coverUrl,
                            'feedUrl' => $feedUrl,
                            'limit' => $limitVal,
                            'episodes' => []
                        ];
                    } else if (empty($podcastsMap[$podName]['feedUrl'])) {
                        $podcastsMap[$podName]['feedUrl'] = $feedUrl;
                    }
                }
            } catch (Exception $e) {}

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array_values($podcastsMap));
        }
        break;

    case 'podcasts_sync':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.', 'success' => false], JSON_UNESCAPED_UNICODE));
        }
        $rawInput = file_get_contents('php://input');
        $dataDecoded = !empty($rawInput) ? json_decode($rawInput, true) : [];
        if (!is_array($dataDecoded) || empty($dataDecoded)) {
            $dataDecoded = is_array($input) ? $input : [];
        }

        $feedUrl = trim($dataDecoded['feedUrl'] ?? $_POST['feedUrl'] ?? $_GET['feedUrl'] ?? '');
        $maxEpisodesRaw = $dataDecoded['maxEpisodes'] ?? $_POST['maxEpisodes'] ?? $_GET['maxEpisodes'] ?? 5;
        
        if (empty($feedUrl)) {
            http_response_code(400);
            exit(json_encode(['error' => 'O endereço feedUrl é obrigatório.', 'success' => false], JSON_UNESCAPED_UNICODE));
        }

        $res = syncSinglePodcastFeed($pdo, $feedUrl, $maxEpisodesRaw);
        if (!empty($res['error'])) {
            http_response_code(400);
            $res['success'] = false;
        } else {
            $res['success'] = true;
        }
        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        break;

    case 'podcasts_auto_sync':
        try {
            $stmtFeeds = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'podcast_feed_%'");
            $savedFeeds = $stmtFeeds ? $stmtFeeds->fetchAll(PDO::FETCH_ASSOC) : [];

            $synced = [];
            $totalDownloaded = 0;

            foreach ($savedFeeds as $sf) {
                $feedUrl = trim($sf['setting_value'] ?? '');
                if (empty($feedUrl)) continue;

                $podName = str_replace('podcast_feed_', '', $sf['setting_key']);

                $limitKey = 'podcast_limit_' . $podName;
                $stmtLimit = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $stmtLimit->execute([$limitKey]);
                $limitVal = $stmtLimit->fetchColumn();
                if ($limitVal === false || $limitVal === null) {
                    $limitVal = '5';
                }

                $res = syncSinglePodcastFeed($pdo, $feedUrl, $limitVal, $podName);
                if (!empty($res['success'])) {
                    $synced[] = [
                        'podcast' => $res['podcastName'],
                        'downloaded' => $res['episodeCount'] ?? 0
                    ];
                    $totalDownloaded += ($res['episodeCount'] ?? 0);
                }
            }

            $now = date('Y-m-d H:i:s');
            $stmtLast = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('podcast_last_auto_sync', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmtLast->execute([$now]);

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'syncedCount' => count($synced),
                'downloadedTotal' => $totalDownloaded,
                'lastSync' => strtotime($now),
                'lastSyncDate' => $now,
                'details' => $synced
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha na sincronização automática: ' . $e->getMessage()]);
        }
        break;

    case 'podcast_config':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('podcast_auto_sync_enabled', 'podcast_auto_sync_interval', 'podcast_default_retention', 'podcast_last_auto_sync')");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
            $retentionVal = $rows['podcast_default_retention'] ?? '5';
            $lastSyncRaw = $rows['podcast_last_auto_sync'] ?? null;
            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'enabled' => ($rows['podcast_auto_sync_enabled'] ?? '1') === '1',
                'interval' => intval($rows['podcast_auto_sync_interval'] ?? 3600),
                'retention' => $retentionVal,
                'defaultRetention' => $retentionVal,
                'lastSync' => $lastSyncRaw ? strtotime($lastSyncRaw) : null,
                'lastSyncDate' => $lastSyncRaw
            ]);
        } else if ($method === 'POST') {
            $rawInput = file_get_contents('php://input');
            $data = !empty($rawInput) ? json_decode($rawInput, true) : [];
            if (!is_array($data) || empty($data)) {
                $data = is_array($input) ? $input : [];
            }
            $enabled = isset($data['enabled']) && ($data['enabled'] === true || $data['enabled'] === '1' || $data['enabled'] === 1) ? '1' : '0';
            $interval = intval($data['interval'] ?? 3600);
            if ($interval < 15) $interval = 3600;
            $retention = $data['retention'] ?? $data['defaultRetention'] ?? '5';
            if (!in_array($retention, ['5', '10', '15', 'all'])) {
                $retention = '5';
            }

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['podcast_auto_sync_enabled', $enabled]);
            $stmt->execute(['podcast_auto_sync_interval', strval($interval)]);
            $stmt->execute(['podcast_default_retention', $retention]);

            $stmtLast = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'podcast_last_auto_sync'");
            $lastSyncRaw = $stmtLast ? $stmtLast->fetchColumn() : null;

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'enabled' => $enabled === '1',
                'interval' => $interval,
                'retention' => $retention,
                'defaultRetention' => $retention,
                'lastSync' => $lastSyncRaw ? strtotime($lastSyncRaw) : null,
                'lastSyncDate' => $lastSyncRaw
            ]);
        }
        break;

    case 'podcast_update_limit':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        $rawInput = file_get_contents('php://input');
        $data = !empty($rawInput) ? json_decode($rawInput, true) : [];
        if (!is_array($data) || empty($data)) {
            $data = is_array($input) ? $input : [];
        }
        $podcastName = trim($data['podcastName'] ?? $_POST['podcastName'] ?? '');
        $limitRaw = $data['limit'] ?? $_POST['limit'] ?? 5;

        if (empty($podcastName)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Nome do podcast é obrigatório.']));
        }

        $isAll = ($limitRaw === 'all' || $limitRaw === 0 || $limitRaw === '0');
        $limitVal = $isAll ? 'all' : strval(intval($limitRaw));
        $maxNum = $isAll ? 0 : intval($limitRaw);

        try {
            $stmtSaveLimit = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmtSaveLimit->execute(["podcast_limit_" . $podcastName, $limitVal]);

            // Se o limite for numérico e finito, excluir excedentes
            if ($maxNum > 0) {
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM songs WHERE genre = 'Podcast' AND album = ?");
                $stmtCount->execute([$podcastName]);
                $totalEpisodes = intval($stmtCount->fetchColumn());

                if ($totalEpisodes > $maxNum) {
                    $stmtAll = $pdo->prepare("SELECT id, file_name FROM songs WHERE genre = 'Podcast' AND album = ? ORDER BY created_at DESC, id DESC");
                    $stmtAll->execute([$podcastName]);
                    $allEps = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

                    $epsToDelete = array_slice($allEps, $maxNum);
                    foreach ($epsToDelete as $epToDelete) {
                        $epId = $epToDelete['id'];
                        $epFileName = $epToDelete['file_name'];
                        $pathsToDelete = [
                            UPLOAD_DIR . '/' . $epFileName,
                            UPLOAD_DIR . $epFileName,
                            __DIR__ . '/' . $epFileName,
                            (defined('PODCAST_DIR') ? PODCAST_DIR . preg_replace('#^podcast/#', '', $epFileName) : '')
                        ];
                        foreach ($pathsToDelete as $pDel) {
                            if (!empty($pDel)) {
                                if (file_exists($pDel) && is_file($pDel)) @unlink($pDel);
                                if (file_exists($pDel . '.url') && is_file($pDel . '.url')) @unlink($pDel . '.url');
                            }
                        }
                        $stmtDel = $pdo->prepare("DELETE FROM songs WHERE id = ?");
                        $stmtDel->execute([$epId]);
                    }
                }
            }

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'podcastName' => $podcastName, 'limit' => $limitVal]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar limite: ' . $e->getMessage()]);
        }
        break;

    case 'podcast_delete':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        $rawInput = file_get_contents('php://input');
        $data = !empty($rawInput) ? json_decode($rawInput, true) : [];
        if (!is_array($data) || empty($data)) {
            $data = is_array($input) ? $input : [];
        }
        $podcastName = trim($data['podcastName'] ?? $_POST['podcastName'] ?? '');
        if (empty($podcastName)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Nome do podcast é obrigatório.']));
        }

        try {
            // 1. Remover configurações do podcast
            $stmtDelSet = $pdo->prepare("DELETE FROM settings WHERE setting_key IN (?, ?, ?, ?)");
            $stmtDelSet->execute([
                "podcast_feed_" . $podcastName,
                "podcast_limit_" . $podcastName,
                "podcast_cover_" . $podcastName,
                "podcast_artist_" . $podcastName
            ]);

            // 2. Encontrar todos os episódios registrados e excluir arquivos
            $stmtEps = $pdo->prepare("SELECT id, file_name FROM songs WHERE genre = 'Podcast' AND album = ?");
            $stmtEps->execute([$podcastName]);
            $eps = $stmtEps->fetchAll(PDO::FETCH_ASSOC);

            foreach ($eps as $ep) {
                $fName = $ep['file_name'];
                $possibleFiles = [
                    UPLOAD_DIR . '/' . $fName,
                    UPLOAD_DIR . $fName,
                    __DIR__ . '/' . $fName,
                    (defined('PODCAST_DIR') ? PODCAST_DIR . preg_replace('#^podcast/#', '', $fName) : '')
                ];
                foreach ($possibleFiles as $pf) {
                    if (!empty($pf)) {
                        if (file_exists($pf) && is_file($pf)) @unlink($pf);
                        if (file_exists($pf . '.url') && is_file($pf . '.url')) @unlink($pf . '.url');
                    }
                }
            }

            // 3. Excluir registros do banco
            $stmtDelSongs = $pdo->prepare("DELETE FROM songs WHERE genre = 'Podcast' AND album = ?");
            $stmtDelSongs->execute([$podcastName]);

            // 4. Remover pasta do podcast se existir
            $safePodcastName = preg_replace('/[\/\\\?%*:|"<>]/', '-', $podcastName);
            $dirsToRemove = [
                UPLOAD_DIR . '/podcast/' . $safePodcastName,
                (defined('PODCAST_DIR') ? PODCAST_DIR . $safePodcastName : '')
            ];
            foreach ($dirsToRemove as $dr) {
                if (!empty($dr) && file_exists($dr) && is_dir($dr)) {
                    $files = @scandir($dr);
                    if ($files !== false) {
                        foreach (array_diff($files, ['.', '..']) as $f) {
                            @unlink($dr . '/' . $f);
                        }
                    }
                    @rmdir($dr);
                }
            }

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => "Podcast '{$podcastName}' e seus episódios foram removidos com sucesso."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao excluir podcast: ' . $e->getMessage()]);
        }
        break;

    case 'radios':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM radios ORDER BY created_at DESC");
            $radios = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            // Auto-atualizar rádios já cadastradas que ainda apontam para links bloqueados do radios.com.br ou sem resolved_url
            foreach ($radios as &$rRow) {
                $origU = trim($rRow['url'] ?? '');
                $resU = trim($rRow['resolved_url'] ?? '');
                $rName = trim($rRow['name'] ?? '');
                if (empty($resU) || stripos($resU, 'radios.com.br') !== false || (stripos($resU, '.m3u') !== false && stripos($resU, '.m3u8') === false) || stripos($resU, '.pls') !== false) {
                    $betterUrl = resolve_radio_stream_url($origU ?: $resU, $rName);
                    if (!empty($betterUrl) && $betterUrl !== $resU) {
                        $rRow['resolved_url'] = $betterUrl;
                        try {
                            $stUpdR = $pdo->prepare("UPDATE radios SET resolved_url = ? WHERE id = ?");
                            $stUpdR->execute([$betterUrl, $rRow['id']]);
                        } catch (Exception $e) {}
                    }
                }
            }
            unset($rRow);

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($radios);
        } else if ($method === 'POST') {
            if (!is_admin_user($pdo)) {
                http_response_code(403);
                exit(json_encode(['error' => 'Apenas administradores podem cadastrar rádios.']));
            }

            $rawInput = file_get_contents('php://input');
            $dataDecoded = !empty($rawInput) ? json_decode($rawInput, true) : [];
            if (!is_array($dataDecoded) || empty($dataDecoded)) {
                $dataDecoded = is_array($input) ? $input : [];
            }
            $name = trim($dataDecoded['name'] ?? $_POST['name'] ?? '');
            $url = trim($dataDecoded['url'] ?? $_POST['url'] ?? '');

            if (empty($name) || empty($url)) {
                http_response_code(400);
                exit(json_encode(['error' => 'Nome e endereço são obrigatórios.']));
            }

            $resolvedUrl = resolve_radio_stream_url($url, $name);

            $id = 'radio-' . round(microtime(true) * 1000);
            $stmtIns = $pdo->prepare("INSERT INTO radios (id, name, url, resolved_url, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmtIns->execute([$id, $name, $url, $resolvedUrl, date('Y-m-d H:i:s')]);

            while (ob_get_level()) {
                @ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'radio' => [
                    'id' => $id,
                    'name' => $name,
                    'url' => $url,
                    'resolved_url' => $resolvedUrl,
                    'resolvedUrl' => $resolvedUrl,
                    'createdAt' => date('Y-m-d H:i:s')
                ]
            ]);
        }
        break;

    case 'radios_delete':
        if ($method !== 'DELETE' && $method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas DELETE e POST são permitidos.']));
        }
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Apenas administradores podem remover rádios.']));
        }

        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            $rawInput = file_get_contents('php://input');
            $dataDecoded = !empty($rawInput) ? json_decode($rawInput, true) : [];
            if (!is_array($dataDecoded) || empty($dataDecoded)) {
                $dataDecoded = is_array($input) ? $input : [];
            }
            $id = trim($dataDecoded['id'] ?? '');
        }

        if (empty($id)) {
            http_response_code(400);
            exit(json_encode(['error' => 'O ID da rádio é obrigatório.']));
        }

        $stmtDel = $pdo->prepare("DELETE FROM radios WHERE id = ?");
        $stmtDel->execute([$id]);

        echo json_encode(['success' => true]);
        break;

    case 'lyrics':
        $title = trim($_GET['title'] ?? '');
        $artist = trim($_GET['artist'] ?? '');
        if (empty($title)) {
            http_response_code(400);
            exit(json_encode(['error' => 'O título da música é obrigatório.']));
        }
        
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: PHPlayerMusicBrowser/1.0 PHP
"
            ]
        ];
        $context = stream_context_create($opts);

        // Pesquisar apenas no Lyrics.ovh
        $url = 'https://api.lyrics.ovh/v1/' . rawurlencode($artist) . '/' . rawurlencode($title);
        $res = @file_get_contents($url, false, $context);
        
        if ($res !== false) {
            $data = json_decode($res, true);
            if (!empty($data['lyrics'])) {
                echo json_encode([
                    'success' => true, 
                    'lyrics' => $data['lyrics'],
                    'source' => 'Lyrics.ovh'
                ]);
                exit(0);
            }
        }
        
        echo json_encode([
            'success' => false, 
            'lyrics' => "Letras não encontradas nos servidores (Lyrics.ovh) para:
\"" . htmlspecialchars($title) . "\" - \"" . htmlspecialchars($artist) . "\""
        ]);
        break;

    case 'log_play':
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $username = trim($input['username'] ?? '');
        $song_id = intval($input['song_id'] ?? 0);
        
        if (empty($username)) {
            $username = !empty($current_username) ? $current_username : ($_SERVER['HTTP_X_USERNAME'] ?? '');
        }
        if (empty($username) || empty($song_id)) {
            echo json_encode(['error' => 'Parâmetros inválidos']);
            break;
        }

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS play_logs (
                id int(11) NOT NULL AUTO_INCREMENT,
                username varchar(100) NOT NULL,
                song_id int(11) NOT NULL,
                played_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $stmt = $pdo->prepare("INSERT INTO play_logs (username, song_id) VALUES (?, ?)");
            $stmt->execute([$username, $song_id]);

            // Atualiza contador de reprodução na tabela songs
            if (function_exists('get_songs_tables')) {
                $tables = get_songs_tables($pdo);
                foreach ($tables as $t) {
                    $stmtUpdate = $pdo->prepare("UPDATE `$t` SET play_count = play_count + 1, last_played = NOW() WHERE id = ?");
                    $stmtUpdate->execute([$song_id]);
                }
            } else {
                $pdo->prepare("UPDATE songs SET play_count = play_count + 1, last_played = NOW() WHERE id = ?")->execute([$song_id]);
            }
        } catch (Exception $e) {}
        
        echo json_encode(['success' => true]);
        break;

    case 'get_play_stats':
        $username = trim($_GET['username'] ?? '');
        if (empty($username)) {
            $username = !empty($current_username) ? $current_username : ($_SERVER['HTTP_X_USERNAME'] ?? '');
        }

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS play_logs (
                id int(11) NOT NULL AUTO_INCREMENT,
                username varchar(100) NOT NULL,
                song_id int(11) NOT NULL,
                played_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (Exception $e) {}

        $topSongs = [];
        $totalSeconds = 0;

        try {
            if (!empty($username) && $username !== 'all') {
                $stmtTop = $pdo->prepare("
                    SELECT s.title, s.artist, s.cover_url, COUNT(p.id) as play_count 
                    FROM play_logs p 
                    JOIN songs s ON p.song_id = s.id 
                    WHERE p.username = ? 
                    GROUP BY p.song_id, s.title, s.artist, s.cover_url 
                    ORDER BY play_count DESC 
                    LIMIT 5
                ");
                $stmtTop->execute([$username]);
                $topSongs = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

                $stmtTime = $pdo->prepare("
                    SELECT COALESCE(SUM(s.duration), 0) as total_seconds 
                    FROM play_logs p 
                    JOIN songs s ON p.song_id = s.id 
                    WHERE p.username = ?
                ");
                $stmtTime->execute([$username]);
                $totalSeconds = (int) $stmtTime->fetchColumn();
            } else {
                $stmtTop = $pdo->query("
                    SELECT s.title, s.artist, s.cover_url, COUNT(p.id) as play_count 
                    FROM play_logs p 
                    JOIN songs s ON p.song_id = s.id 
                    GROUP BY p.song_id, s.title, s.artist, s.cover_url 
                    ORDER BY play_count DESC 
                    LIMIT 5
                ");
                $topSongs = $stmtTop ? $stmtTop->fetchAll(PDO::FETCH_ASSOC) : [];

                $stmtTime = $pdo->query("
                    SELECT COALESCE(SUM(s.duration), 0) as total_seconds 
                    FROM play_logs p 
                    JOIN songs s ON p.song_id = s.id
                ");
                $totalSeconds = (int) ($stmtTime ? $stmtTime->fetchColumn() : 0);
            }

            // Fallback: se play_logs estiver vazio, verificar campo play_count diretamente na tabela songs
            if (empty($topSongs)) {
                $stmtFallback = $pdo->query("
                    SELECT title, artist, cover_url, play_count 
                    FROM songs 
                    WHERE play_count > 0 
                    ORDER BY play_count DESC 
                    LIMIT 5
                ");
                if ($stmtFallback) {
                    $topSongs = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
                }
                if ($totalSeconds === 0 && !empty($topSongs)) {
                    $stmtTimeFallback = $pdo->query("
                        SELECT COALESCE(SUM(duration * play_count), 0) 
                        FROM songs 
                        WHERE play_count > 0
                    ");
                    if ($stmtTimeFallback) {
                        $totalSeconds = (int) $stmtTimeFallback->fetchColumn();
                    }
                }
            }
        } catch (Exception $e) {
            $topSongs = [];
            $totalSeconds = 0;
        }

        echo json_encode([
            'success' => true,
            'topSongs' => $topSongs,
            'totalSeconds' => $totalSeconds
        ]);
        break;

    case 'get_recommendations':
        $username = trim($_GET['username'] ?? '');
        if (empty($username)) {
            echo json_encode(['error' => 'Parâmetro username ausente']);
            break;
        }
        
        // Obter os 5 artistas mais tocados
        $stmtTopArtists = $pdo->prepare("
            SELECT s.artist, COUNT(p.id) as play_count 
            FROM play_logs p 
            JOIN songs s ON p.song_id = s.id 
            WHERE p.username = ? 
            GROUP BY s.artist 
            ORDER BY play_count DESC 
            LIMIT 5
        ");
        $stmtTopArtists->execute([$username]);
        $topArtists = $stmtTopArtists->fetchAll(PDO::FETCH_COLUMN);
        
        // Obter os 5 gêneros mais tocados
        $stmtTopGenres = $pdo->prepare("
            SELECT s.genre, COUNT(p.id) as play_count 
            FROM play_logs p 
            JOIN songs s ON p.song_id = s.id 
            WHERE p.username = ? AND s.genre != '' AND s.genre != 'Desconhecido'
            GROUP BY s.genre 
            ORDER BY play_count DESC 
            LIMIT 5
        ");
        $stmtTopGenres->execute([$username]);
        $topGenres = $stmtTopGenres->fetchAll(PDO::FETCH_COLUMN);
        
        // Encontrar músicas recomendadas baseadas nos artistas e gêneros, que o usuário ainda não tocou (ou tocou pouco), ordenado aleatoriamente
        // Se não houver top artistas/gêneros, retorna algo aleatório.
        
        $recommendations = [];
        if (!empty($topArtists) || !empty($topGenres)) {
            $artistPlaceholders = implode(',', array_fill(0, count($topArtists), '?'));
            $genrePlaceholders = implode(',', array_fill(0, count($topGenres), '?'));
            
            $query = "SELECT * FROM songs WHERE 1=0 ";
            $params = [];
            
            if (!empty($topArtists)) {
                $query .= " OR artist IN ($artistPlaceholders) ";
                $params = array_merge($params, $topArtists);
            }
            if (!empty($topGenres)) {
                $query .= " OR genre IN ($genrePlaceholders) ";
                $params = array_merge($params, $topGenres);
            }
            
            // Exclude already played heavily? Let's just fetch random from those
            $query .= " ORDER BY RAND() LIMIT 10";
            
            $stmtRec = $pdo->prepare($query);
            $stmtRec->execute($params);
            $recommendations = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Random tracks if no history
            $stmtRec = $pdo->query("SELECT * FROM songs ORDER BY RAND() LIMIT 10");
            $recommendations = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'success' => true,
            'recommendations' => $recommendations
        ]);
        break;

    case 'android_ping':
        $totalSongs = 0;
        $totalArtists = 0;
        $totalAlbums = 0;
        try {
            $totalSongs = (int)$pdo->query("SELECT COUNT(*) FROM songs WHERE LOWER(COALESCE(genre,'')) != 'podcast'")->fetchColumn();
            $totalArtists = (int)$pdo->query("SELECT COUNT(DISTINCT artist) FROM songs WHERE LOWER(COALESCE(genre,'')) != 'podcast' AND artist IS NOT NULL AND artist != ''")->fetchColumn();
            $totalAlbums = (int)$pdo->query("SELECT COUNT(DISTINCT album) FROM songs WHERE LOWER(COALESCE(genre,'')) != 'podcast' AND album IS NOT NULL AND album != ''")->fetchColumn();
        } catch (Throwable $ePing) {}

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api.php')), '/');
        $baseServerUrl = $proto . '://' . $host . ($dir ? $dir : '');

        echo json_encode([
            'success' => true,
            'status' => 'online',
            'version' => defined('PHPLAYER_VERSION') ? PHPLAYER_VERSION : '1.3.8',
            'server_url' => $baseServerUrl,
            'api_url' => $baseServerUrl . '/api.php',
            'stats' => [
                'tracks' => $totalSongs,
                'artists' => $totalArtists,
                'albums' => $totalAlbums
            ],
            'endpoints' => [
                'login' => 'POST api.php?route=login',
                'tracks' => 'GET api.php?route=tracks',
                'stream' => 'GET api.php?route=stream&id={id}',
                'artist_photos' => 'GET api.php?route=artist_photos',
                'favorites' => 'GET api.php?route=favorites&username={user}',
                'favorites_toggle' => 'POST api.php?route=favorites_toggle',
                'playlists' => 'GET api.php?route=playlists&username={user}',
                'radios' => 'GET api.php?route=radios',
                'podcasts' => 'GET api.php?route=podcasts',
                'lyrics' => 'GET api.php?route=lyrics&artist={artist}&title={title}'
            ]
        ]);
        break;

    case 'download_android_app':
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo json_encode(['error' => 'A extensão ZipArchive do PHP não está habilitada neste servidor.']);
            break;
        }

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api.php')), '/');
        $detectedUrl = $proto . '://' . $host . ($dir ? $dir : '');
        $customServerUrl = trim($_GET['server_url'] ?? $detectedUrl);
        if (empty($customServerUrl)) $customServerUrl = $detectedUrl;
        $customServerUrl = rtrim(preg_replace('#/(api|index|mobile)\.php$#i', '', $customServerUrl), '/');

        $tmpZip = tempnam(sys_get_temp_dir(), 'phplayer_android_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível criar o arquivo ZIP temporário.']);
            break;
        }

        $androidDir = __DIR__ . '/android';
        if (is_dir($androidDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($androidDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $fileInfo) {
                $fullPath = $fileInfo->getRealPath();
                $relPath = 'PHPlayerAndroid/' . ltrim(str_replace('\\', '/', substr($fullPath, strlen($androidDir))), '/');
                if (strpos($relPath, '/build/') !== false || strpos($relPath, '/.gradle/') !== false || strpos($relPath, '/.idea/') !== false) {
                    continue;
                }
                if ($fileInfo->isDir()) {
                    $zip->addEmptyDir($relPath);
                } else {
                    $content = @file_get_contents($fullPath);
                    if (basename($fullPath) === 'MainActivity.kt' && $content !== false) {
                        $content = preg_replace(
                            '/private const val DEFAULT_SERVER_URL = "[^"]*"/',
                            'private const val DEFAULT_SERVER_URL = "' . addslashes($customServerUrl) . '"',
                            $content
                        );
                    }
                    $zip->addFromString($relPath, $content !== false ? $content : '');
                }
            }
        }

        $readmeContent = "# PHPlayer - Aplicativo Android Nativo (Conexão via API)\n\n"
            . "Este projeto Android (Kotlin) conecta-se diretamente ao seu servidor de música PHP via `api.php`.\n\n"
            . "## URL Pré-Configurada\n"
            . "- Servidor: `{$customServerUrl}`\n"
            . "- Endpoint da API: `{$customServerUrl}/api.php`\n\n"
            . "## Recursos Integrados via API\n"
            . "- Autenticação de usuário (`POST api.php?route=login`)\n"
            . "- Sincronização de Álbuns, Artistas (com foto/logo oficial e sem podcasts), Músicas e Favoritos (`GET api.php?route=tracks`, `artist_photos`, `favorites`)\n"
            . "- Streaming nativo de áudio com cache offline automático (`GET api.php?route=stream&id=...`)\n"
            . "- Equalizador de áudio nativo de 5 bandas do Android e visualizador de letras (`GET api.php?route=lyrics`)\n"
            . "- Reprodução de Playlists, Rádios On-line e Podcasts do servidor\n\n"
            . "## Como gerar o arquivo instalável (.APK)\n"
            . "1. Abra o **Android Studio** e clique em **Open**.\n"
            . "2. Selecione a pasta `PHPlayerAndroid` extraída deste arquivo ZIP.\n"
            . "3. Aguarde o Gradle sincronizar e clique em **Build > Build Bundle(s) / APK(s) > Build APK(s)**.\n"
            . "4. Instale o arquivo `app-debug.apk` gerado no seu celular Android!\n";
        $zip->addFromString('PHPlayerAndroid/README_ANDROID_API.md', $readmeContent);
        $zip->close();

        if (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="PHPlayer_Android_API_Project.zip"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'API Endpoint Inválido']);
        break;
}
} catch (Throwable $exception) {
    header('Content-Type: application/json; charset=utf-8');
    $msg = 'Erro interno no servidor PHP (Throwable): ' . $exception->getMessage() . ' (' . basename($exception->getFile()) . ':' . $exception->getLine() . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
    echo json_encode([
        'error' => $msg,
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit(0);
}
