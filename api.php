<?php
/**
 * REST API Principal - Subsonic PHP Web Player
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Range");
header("Access-Control-Expose-Headers: Content-Range, Content-Length, Accept-Ranges");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Habilitar relato de erros para poder tratá-los no callback global e transparente
@error_reporting(E_ALL);
@ini_set('display_errors', 0); // Não imprimir avisos crus para evitar corromper o JSON
@set_time_limit(0);
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '512M');

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

if (!function_exists('safe_utf8_encode')) {
    function safe_utf8_encode($data) {
        if (is_string($data)) {
            if (!preg_match('//u', $data)) {
                if (function_exists('mb_convert_encoding')) {
                    return mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
                } elseif (function_exists('iconv')) {
                    return iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $data);
                } elseif (function_exists('utf8_encode')) {
                    return utf8_encode($data);
                } else {
                    return preg_replace('/[\x80-\xFF]/', '?', $data);
                }
            }
            return $data;
        } elseif (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = safe_utf8_encode($v);
            }
        } elseif (is_object($data)) {
            $vars = get_object_vars($data);
            foreach ($vars as $k => $v) {
                $data->$k = safe_utf8_encode($v);
            }
        }
        return $data;
    }
}

if (!function_exists('echo_json')) {
    function echo_json($data, $statusCode = 200) {
        if ($statusCode !== 200) {
            http_response_code($statusCode);
        }
        header('Content-Type: application/json; charset=utf-8');
        $safeData = safe_utf8_encode($data);
        $json = json_encode($safeData, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = json_encode(['error' => 'Falha de codificacao JSON no servidor', 'code' => json_last_error()]);
        }
        echo $json;
        exit(0);
    }
}

// Polyfills de funções do PHP 8 para compatibilidade com servidores locais de PHP antigos (PHP 7.4 ou anterior)
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

set_exception_handler(function ($exception) {
    $msg = 'Exceção não tratada no servidor PHP: ' . $exception->getMessage() . ' (' . basename($exception->getFile()) . ':' . $exception->getLine() . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
    exitWithJsonError($msg);
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    $msg = 'Erro/Aviso de execução no PHP: ' . $message . ' (' . basename($file) . ':' . $line . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
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
header('Content-Type: application/json; charset=utf-8');

// Helper para log de sincronização de musicas
if (!function_exists('write_scan_log')) {
    function write_scan_log($message, $clear = false) {
        $logFile = __DIR__ . '/music_scan.log';
        if ($clear) {
            @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "=== INICIALIZANDO VARREDURA COMPLETA ===\n");
        } else {
            @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
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
                return ($numA < $numB) ? -1 : (($numA > $numB) ? 1 : 0);
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
                  `album_year` int(11) DEFAULT NULL,
                  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                  `play_count` INT DEFAULT 0,
                  `last_played` DATETIME DEFAULT NULL,
                  PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=" . $nextAutoInc . ";");
                
                return $newTable;
            } catch (Exception $e) {
                @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . "Erro ao criar tabela particionada " . $newTable . ": " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
        
        return $activeTable;
    }
}

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
              album_year int(11) DEFAULT NULL,
              created_at datetime DEFAULT CURRENT_TIMESTAMP,
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

            $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
              id varchar(50) NOT NULL,
              title varchar(255) NOT NULL,
              file_name varchar(255) NOT NULL,
              file_size bigint(20) NOT NULL,
              cover_url varchar(500) DEFAULT NULL,
              created_at datetime DEFAULT CURRENT_TIMESTAMP,
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

            // Garantir coluna theme
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN theme VARCHAR(30) DEFAULT 'default'");
            } catch (Exception $theme_ex) {
                // Ignora se já existir
            }

            // Garantir colunas de estatísticas (Reproduções)
            try {
                $pdo->exec("ALTER TABLE songs ADD COLUMN play_count INT DEFAULT 0");
            } catch (Exception $col_ex) {}
            try {
                $pdo->exec("ALTER TABLE songs ADD COLUMN last_played DATETIME DEFAULT NULL");
            } catch (Exception $col_ex) {}

            // Garantir coluna album_year em todas as tabelas particionadas
            try {
                $chk_tables = get_songs_tables($pdo);
                foreach ($chk_tables as $stb) {
                    try {
                        $pdo->exec("ALTER TABLE `" . $stb . "` ADD COLUMN album_year INT DEFAULT NULL");
                    } catch (Exception $col_ex) {}
                }
            } catch (Exception $e) {}

            // Inserir usuários padrão se tabela users estiver vazia
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ($userCount == 0) {
                $pdo->exec("INSERT INTO users (id, username, password, role) VALUES
                (1, 'admin', '\$2y\$10\$m.1eNSRiMtmn.9RvSqJL/.sRfFdcFlgv36RrpGkNfzR5F7LaA1C42', 'admin'),
                (2, 'ouvinte', '\$2y\$10\$X10Q4Ac4vmEgRpyWM2ok1./0gKGk6d3QXpeHE4c0YcD1rZ/VxGEKe', 'ouvinte')
                ON DUPLICATE KEY UPDATE username = username;");
            }

            // Inserir configurações padrão se tabela settings estiver vazia para habilitar o DLNA (UPnP) por padrão
            $settingCount = $pdo->query("SELECT COUNT(*) FROM settings WHERE setting_key = 'dlna_enabled'")->fetchColumn();
            if ($settingCount == 0) {
                $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('dlna_enabled', '1')");
            }
        }
    } catch (Exception $e) {
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . "Erro Auto-Migration: " . $e->getMessage() . "
", FILE_APPEND);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $route = isset($_GET['route']) ? $_GET['route'] : '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

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
                if ($size <= 0 || $size > 512) $size = 64;
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
                $decoded = trim($decoded, "
