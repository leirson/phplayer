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

            $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                share_hash VARCHAR(100) PRIMARY KEY,
                target_type VARCHAR(50),
                target_id VARCHAR(500),
                target_name VARCHAR(255),
                created_by VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
                        "album_type VARCHAR(50) DEFAULT 'album'"
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
                $decoded = trim($decoded, " .. ");
                $decoded = preg_replace('/[ --]/', '', $decoded);
                
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
                            $clean = trim($str, " .. ");
                            $clean = mb_check_encoding($clean, 'UTF-8') ? $clean : @mb_convert_encoding($clean, 'UTF-8', 'ISO-8859-1');
                            $clean = preg_replace('/[ --]/', '', $clean);
                            return trim($clean);
                        };
                        $titlev1 = $sanitizeV1(substr($tag, 3, 30));
                        $artistv1 = $sanitizeV1(substr($tag, 33, 30));
                        $albumv1 = $sanitizeV1(substr($tag, 63, 30));
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

    // Helper para verificar se o usuário atual é administrador
    if (!function_exists('is_admin_user')) {
        function is_admin_user($pdo) {
            $username = $_SERVER['HTTP_X_USERNAME'] ?? $_GET['admin_username'] ?? '';
            if (empty($username)) {
                return false;
            }
            try {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $role = $stmt->fetchColumn();
                return ($role === 'admin');
            } catch (Exception $e) {
                return false;
            }
        }
    }

    // Definição de rotas restritas para administradores
    $admin_only_routes = [
        'scan_log', 'repair_db', 'export_backup', 'import_backup', 'scan',
        'delete_track', 'music_folders', 'delete_music_folder',
        'update_track_title', 'update_album_tracks_metadata', 'update_tracks_bulk',
        'upload_album_cover', 'upload_artist_banner',
        'dlna_status', 'toggle_dlna', 'search_images', 'search_artist_logo',
        'update_album_cover_url', 'update_artist_banner_url',
        'get_settings', 'save_settings', 'lastfm_sync', 'deezer_sync',
        'google_images_sync', 'videos_scan', 'videos_upload_cover',
        'files_list', 'files_create_dir', 'files_delete', 'files_rename', 'files_upload',
        'podcasts_sync'
    ];

    if (in_array($route, $admin_only_routes)) {
        if (!is_admin_user($pdo)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: Recursos de administrador são restritos.', 'success' => false]));
        }
    }

    $current_username = $_SERVER["HTTP_X_USERNAME"] ?? $_GET["admin_username"] ?? "";
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
            $pdo->exec("ALTER TABLE shares ADD COLUMN created_by VARCHAR(50) DEFAULT NULL");
        } catch(Exception $e) {}
        try {
            $pdo->exec("ALTER TABLE shares ADD COLUMN expires_at DATETIME DEFAULT NULL");
        } catch(Exception $e) {}
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                share_hash VARCHAR(100) PRIMARY KEY,
                target_type VARCHAR(50),
                target_id VARCHAR(500),
                target_name VARCHAR(255),
                created_by VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch(Exception $e) {}
        
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
        } catch(Exception $e) {
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
                created_by VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            try { $pdo->exec("ALTER TABLE shares ADD COLUMN created_by VARCHAR(50) DEFAULT NULL"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE shares ADD COLUMN expires_at DATETIME DEFAULT NULL"); } catch(Exception $e) {}

            if (!is_admin_user($pdo)) {
                $stmt = $pdo->prepare("SELECT * FROM shares WHERE created_by = ? ORDER BY created_at DESC");
                $stmt->execute([$current_username]);
            } else {
                $stmt = $pdo->query("SELECT * FROM shares ORDER BY created_at DESC");
            }
            $shares = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            echo json_encode($shares);
        } catch(Exception $e) {
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

    

    

    

    
    case 'check_update':
        $current_version = '1.0.0';
        if (file_exists('version.php')) {
            $content = file_get_contents('version.php');
            if (preg_match("/define\\('PHPLAYER_VERSION',\\s*'([^']+)'\\)/", $content, $m)) {
                $current_version = $m[1];
            }
        }
        
        $remote_version = $current_version;
        $changelog = '';
        
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $remote_version_file = @file_get_contents('https://raw.githubusercontent.com/leirson/phplayer/main/version.php', false, $ctx);
        if ($remote_version_file && preg_match("/define\\('PHPLAYER_VERSION',\\s*'([^']+)'\\)/", $remote_version_file, $m)) {
            $remote_version = $m[1];
        }
        
        $remote_changelog_file = @file_get_contents('https://raw.githubusercontent.com/leirson/phplayer/main/changelog.php', false, $ctx);
        if ($remote_changelog_file && preg_match('/\$changelog = <<<EOT\\n(.*?)\\nEOT;/s', $remote_changelog_file, $m)) {
            $changelog = $m[1];
        } else {
            $changelog = "Não foi possível carregar o changelog do GitHub.";
        }
        
        echo json_encode([
            'current_version' => $current_version,
            'remote_version' => $remote_version,
            'has_update' => version_compare($remote_version, $current_version, '>'),
            'changelog' => $changelog
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

        $zip_url = 'https://github.com/leirson/phplayer/archive/refs/heads/main.zip';
        $temp_zip = sys_get_temp_dir() . '/phplayer_update_' . time() . '.zip';
        
        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $zip_content = @file_get_contents($zip_url, false, $ctx);
        
        if (!$zip_content) {
            exit(json_encode(['success' => false, 'error' => 'Não foi possível baixar a atualização do GitHub.']));
        }
        
        file_put_contents($temp_zip, $zip_content);
        
        $zip = new ZipArchive;
        if ($zip->open($temp_zip) === TRUE) {
            $temp_extract_dir = sys_get_temp_dir() . '/phplayer_extract_' . time();
            mkdir($temp_extract_dir);
            $zip->extractTo($temp_extract_dir);
            $zip->close();
            
            $extracted_folders = glob($temp_extract_dir . '/*', GLOB_ONLYDIR);
            $source_dir = $temp_extract_dir;
            if (count($extracted_folders) == 1) {
                $source_dir = $extracted_folders[0];
            }
            
            if (!function_exists('copy_dir_update')) {
                function copy_dir_update($src, $dst) {
                    $dir = opendir($src);
                    @mkdir($dst);
                    while (false !== ($file = readdir($dir))) {
                        if (($file != '.') && ($file != '..')) {
                            if (is_dir($src . '/' . $file)) {
                                copy_dir_update($src . '/' . $file, $dst . '/' . $file);
                            } else {
                                if ($file === 'config.php' && file_exists($dst . '/config.php')) {
                                    continue;
                                }
                                copy($src . '/' . $file, $dst . '/' . $file);
                            }
                        }
                    }
                    closedir($dir);
                }
            }
            
            copy_dir_update($source_dir, __DIR__);
            
            @unlink($temp_zip);
            if (!function_exists('delete_dir_update')) {
                function delete_dir_update($dir) {
                    if (!is_dir($dir)) {
                        if (is_file($dir)) { @unlink($dir); }
                        return;
                    }
                    $files = array_diff(scandir($dir), array('.', '..'));
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        (is_dir($path)) ? delete_dir_update($path) : @unlink($path);
                    }
                    @rmdir($dir);
                }
            }
            delete_dir_update($temp_extract_dir);
            
            echo json_encode(['success' => true]);
        } else {
            @unlink($temp_zip);
            echo json_encode(['success' => false, 'error' => 'Falha ao extrair o arquivo de atualização.']);
        }
        exit;

    case 'proxy_radio':
        $url = $_GET['url'] ?? '';
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            exit("URL inválida");
        }

        // Prevent session locking
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Set streaming headers
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: audio/mpeg");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Connection: close");

        // Set infinite execution time
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);

        // Try to open the remote stream
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Accept: */*
",
                "timeout" => 15
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

        // Output cleaning
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Read and output stream chunks
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
        write_scan_log("", true); // clear file and write header
        write_scan_log("Iniciando varredura no diretório /music...");
        
        $musicDir = __DIR__ . '/music/';
        if (!file_exists($musicDir)) {
            write_scan_log("Diretório /music não existia. Criando pasta...");
            @mkdir($musicDir, 0755, true);
        }
        
        $newTracks = 0;
        $removedTracksCount = 0;
        $coverOptions = [
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400',
            'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400',
            'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=400'
        ];

        // 1. Clean up removed files and folders from the database
        write_scan_log("Etapa 1: Limpeza de músicas cujos arquivos físicos foram removidos.");
        $allSongs = [];
        $tables = get_songs_tables($pdo);
        write_scan_log("Tabelas de músicas particionadas identificadas: " . implode(', ', $tables));
        
        foreach ($tables as $t) {
            write_scan_log("Verificando integridade dos arquivos registrados na tabela '$t'...");
            $stmtAll = $pdo->query("SELECT id, file_name FROM `" . $t . "`");
            if ($stmtAll) {
                while ($song = $stmtAll->fetch()) {
                    $song['_table'] = $t;
                    $allSongs[] = $song;
                }
            }
        }
        
        write_scan_log("Total de " . count($allSongs) . " registros encontrados no banco. Validando correspondência no disco...");
        foreach ($allSongs as $song) {
            $fName = $song['file_name'];
            if (strpos($fName, 'http') === 0 || strpos($fName, 'music_') === 0) {
                continue; // Skip seed streams and web uploads
            }
            if (!file_exists($musicDir . $fName)) {
                $t = $song['_table'];
                $pdo->prepare("DELETE FROM `" . $t . "` WHERE id = ?")->execute([$song['id']]);
                $removedTracksCount++;
                write_scan_log("Aviso: Arquivo físico '$fName' ausente. Registro excluído do BD (Tabela '$t', ID: {$song['id']}).");
            }
        }
        write_scan_log("Concluido! Músicas órfãs removidas do Banco: $removedTracksCount registros.");

        // 1.5 Pre-scan folder to identify compilation directories (where different tracks have different artists)
        write_scan_log("Etapa 1.5: Identificando pastas com múltiplos artistas (coletâneas)...");
        $folderArtistsMap = [];
        if (file_exists($musicDir)) {
            $preIt = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($musicDir));
            foreach ($preIt as $pInfo) {
                if ($pInfo->isFile()) {
                    $pExt = strtolower(pathinfo($pInfo->getFilename(), PATHINFO_EXTENSION));
                    if (in_array($pExt, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
                        $pAbs = $pInfo->getPathname();
                        $pRel = str_replace(chr(92), '/', substr($pAbs, strlen($musicDir)));
                        $dPath = dirname($pRel);
                        $pParts = explode('/', $pRel);
                        
                        // Default guess artist
                        $pArtist = count($pParts) >= 2 ? $pParts[0] : 'Artista Desconhecido';
                        
                        // Overwrite with MP3 Tag
                        if ($pExt === 'mp3') {
                            $pMeta = $getMp3Meta($pAbs);
                            if (!empty($pMeta['tag_artist'])) {
                                $pArtist = $pMeta['tag_artist'];
                            }
                        }
                        
                        if (!isset($folderArtistsMap[$dPath])) {
                            $folderArtistsMap[$dPath] = [];
                        }
                        $folderArtistsMap[$dPath][] = $pArtist;
                    }
                }
            }
        }
        write_scan_log("Etapa 1.5 Concluída! Pastas mapeadas: " . count($folderArtistsMap));

        // 2. Scan folders
        write_scan_log("Etapa 2: Analisando pasta física /music recursivamente para novas adições...");
        if (file_exists($musicDir)) {
            try {
                $directory = new RecursiveDirectoryIterator($musicDir);
                $iterator = new RecursiveIteratorIterator($directory);
                
                $totalPhysicalFiles = 0;
                $processedFiles = 0;
                
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isFile()) {
                        $totalPhysicalFiles++;
                        $absolutePath = $fileinfo->getPathname();
                        $ext = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
                        $relativePath = str_replace(chr(92), '/', substr($absolutePath, strlen($musicDir)));
                        
                        // Let's log any file that is ignored but is in /music
                        if (!in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
                            write_scan_log("Filtro: Arquivo '$relativePath' ignorado. Extensão (.$ext) não suportada.");
                            continue;
                        }
                        
                        $processedFiles++;
                        
                        // Check if file is already in BD
                        $file_exists_in_db = false;
                        $existing_table = '';
                        $existing_id = null;
                        $existing_title = '';
                        foreach ($tables as $t) {
                            $stmtCheck = $pdo->prepare("SELECT id, title FROM `" . $t . "` WHERE file_name = ? LIMIT 1");
                            $stmtCheck->execute([$relativePath]);
                            $row = $stmtCheck->fetch();
                            if ($row) {
                                $file_exists_in_db = true;
                                $existing_table = $t;
                                $existing_id = $row['id'];
                                $existing_title = $row['title'];
                                break;
                            }
                        }

                        if ($file_exists_in_db) {
                            write_scan_log("Já Registrado: '$relativePath' (Tabela: '$existing_table'). Verificando metadata para número da faixa...");
                            if ($ext === 'mp3') {
                                try {
                                    $meta = $getMp3Meta($absolutePath);
                                    if (!empty($meta['tag_track_number'])) {
                                        if (strpos(trim($existing_title), $meta['tag_track_number']) !== 0) {
                                            $new_title = $meta['tag_track_number'] . ' - ' . $existing_title;
                                            $pdo->prepare("UPDATE `" . $existing_table . "` SET title = ? WHERE id = ?")->execute([$new_title, $existing_id]);
                                            write_scan_log("- Atualizado título com número da faixa: '$new_title'");
                                        }
                                    }
                                } catch (Exception $id3Exc) {
                                    write_scan_log("- Aviso: Falha ao ler ID3 do MP3 já existente: " . $id3Exc->getMessage());
                                }
                            }
                        } else {
                            write_scan_log("Sincronizando novo arquivo: '$relativePath'");
                            $parts = explode('/', $relativePath);
                            $dPath = dirname($relativePath);
                            
                            // Check if this folder has multiple different artists
                            $isFolderMultiArtist = isset($folderArtistsMap[$dPath]) && (count(array_unique(array_map('strtolower', array_map('trim', $folderArtistsMap[$dPath])))) > 1);
                            
                            // Set artist from the main/top folder under /music
                            $artist = count($parts) >= 2 ? $parts[0] : 'Artista Desconhecido';
                            
                            // If multi-artist, set album to folder name and artist to 'Varios Artistas'
                            if ($isFolderMultiArtist) {
                                $album = ($dPath !== '.' ? basename($dPath) : 'Single');
                                $artist = 'Varios Artistas';
                            } else {
                                $album = count($parts) >= 3 ? $parts[count($parts) - 2] : 'Single';
                            }
                            
                            $lastName = basename($relativePath);
                            $title = pathinfo($lastName, PATHINFO_FILENAME);
                            $title = str_replace(['_', '-'], ' ', $title);
                            $randomCover = $coverOptions[array_rand($coverOptions)];
                            
                            $genre = 'Local Scan';
                            $duration = 210;
                            
                            write_scan_log("- Guesses iniciais pelo Caminho do Arquivo -> Artista: '$artist', Álbum: '$album', Título: '$title'");
 
                            // Apply ID3 tag reader
                            if ($ext === 'mp3') {
                                try {
                                    write_scan_log("- Lendo ID3 Metadata do arquivo MP3...");
                                    $meta = $getMp3Meta($absolutePath);
                                    
                                    if (!empty($meta['tag_genre'])) {
                                        $genre = $meta['tag_genre'];
                                    }
                                    if (!empty($meta['tag_duration'])) {
                                        $duration = $meta['tag_duration'];
                                    }
                                    if (!empty($meta['tag_title'])) {
                                        $title = $meta['tag_title'];
                                    }

                                    if (!empty($meta['tag_track_number'])) {
                                        // Apenas prependar se o título já não começar com esse número
                                        if (strpos(trim($title), $meta['tag_track_number']) !== 0) {
                                            $title = $meta['tag_track_number'] . ' - ' . $title;
                                        }
                                    }

                                    // Set artist name to the ID3 artist metadata tag if it exists so we know who sings this track!
                                    if (!$isFolderMultiArtist && !empty($meta['tag_artist'])) {
                                        $artist = $meta['tag_artist'];
                                    }
                                    
                                    // If NOT a multi-artist folder, use ID3 tag_album if possible.
                                    // Otherwise, strictly leave it as the parent directory name.
                                    if (!$isFolderMultiArtist) {
                                        if (($album === 'Single' || empty($album)) && !empty($meta['tag_album'])) {
                                            $album = $meta['tag_album'];
                                        }
                                    } else {
                                        $album = ($dPath !== '.' ? basename($dPath) : 'Single');
                                        $artist = 'Varios Artistas';
                                    }
                                    write_scan_log("- Metadata obtido -> Título: '$title', Artista: '$artist', Álbum: '$album', Gênero: '$genre', Duração: {$duration}s");
                                } catch (Exception $id3Exc) {
                                    write_scan_log("- Aviso: Falha ao ler ID3 do MP3: " . $id3Exc->getMessage());
                                }
                            }
 
                            $insertTable = get_insert_song_table($pdo);
                            write_scan_log("- Persistindo no banco de dados na tabela '$insertTable'...");
                            
                            $pdo->prepare("INSERT INTO `" . $insertTable . "` (title, artist, album, genre, file_name, file_size, duration, cover_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                                ->execute([$title, $artist, $album, $genre, $relativePath, $fileinfo->getSize(), $duration, $randomCover]);
                            $newTracks++;
                            write_scan_log("- Sucesso: Sincronizado com êxito! Registro inserido.");
                            
                            // Atualiza a lista de tabelas caso uma nova tenha sido adicionada
                            $tables = get_songs_tables($pdo);
                        }
                    }
                }
                write_scan_log("Fim da Varredura física. Arquivos físicos totais analisados: $totalPhysicalFiles, arquivos de som: $processedFiles");
            } catch (Exception $scanExc) {
                write_scan_log("ERRO FATAL NA VARREDURA FÍSICA: " . $scanExc->getMessage());
            }
        } else {
            write_scan_log("Erro: Diretório /music não pôde ser listado ou não existe.");
        }

        // Align genres within the same album (propagate the most specific ID3 genre to all tracks in each album)
        write_scan_log("Etapa 3: Alinhando gêneros de álbuns de forma inteligente para todo o catálogo...");
        try {
            $albumQueries = [];
            foreach ($tables as $t) {
                $albumQueries[] = "SELECT DISTINCT album FROM `" . $t . "` WHERE album != '' AND album IS NOT NULL";
            }
            $albumsList = $pdo->query("SELECT DISTINCT album FROM (" . implode(" UNION ALL ", $albumQueries) . ") AS u_alb")->fetchAll(PDO::FETCH_COLUMN);

            write_scan_log("Total de " . count($albumsList) . " álbuns identificados para alinhamento de gênero.");
            foreach ($albumsList as $albName) {
                $validGenre = null;
                foreach ($tables as $t) {
                    $stmtGenre = $pdo->prepare("SELECT genre FROM `" . $t . "` WHERE album = ? AND genre NOT IN ('Local Scan', 'Desconhecido', 'Single', 'Unknown', 'Upload', '') AND genre IS NOT NULL LIMIT 1");
                    $stmtGenre->execute([$albName]);
                    $genreVal = $stmtGenre->fetchColumn();
                    if ($genreVal) {
                        $validGenre = $genreVal;
                        break;
                    }
                }
                if ($validGenre) {
                    write_scan_log("- Álbum '$albName' -> Propagando gênero '$validGenre' para todas as faixas do álbum.");
                    foreach ($tables as $t) {
                        $stmtUpdate = $pdo->prepare("UPDATE `" . $t . "` SET genre = ? WHERE album = ?");
                        $stmtUpdate->execute([$validGenre, $albName]);
                    }
                }
            }
        } catch (Exception $genreExc) {
            write_scan_log("Aviso: Falha ao alinhar gêneros: " . $genreExc->getMessage());
        }

        $total = get_songs_count($pdo);
        write_scan_log("=== VARREDURA COMPLETA CONCLUÍDA COM SUCESSO! Novas faixas adicionadas: $newTracks, Removidas: $removedTracksCount, Total no banco: $total ===");
        
        echo json_encode(['success' => true, 'count' => $newTracks, 'removed' => $removedTracksCount, 'total' => intval($total)]);
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
                    "album_type VARCHAR(50) DEFAULT 'album'"
                ];
                foreach ($tables as $st) {
                    foreach ($songColsToEnsure as $colDef) {
                        try { $pdo->exec("ALTER TABLE `" . $st . "` ADD COLUMN " . $colDef); } catch (Exception $ex) {}
                    }
                }

                $cols = "id, title, artist, album, genre, file_name, file_size, duration, cover_url, created_at, play_count, last_played, album_year, album_type";
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

                echo json_encode($songs ?: []);
            } catch (Throwable $e) {
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
                    
                    if ($ext === 'mp3') {
                        $meta = $getMp3Meta(UPLOAD_DIR . $newFileName);
                        if (!empty($meta['tag_title'])) {
                            $title = $meta['tag_title'];
                        }

                        if (!empty($meta['tag_track_number'])) {
                            if (strpos(trim($title), $meta['tag_track_number']) !== 0) {
                                $title = $meta['tag_track_number'] . ' - ' . $title;
                            }
                        }

                        if (!empty($meta['tag_artist'])) {
                            $artist = $meta['tag_artist'];
                        }
                        if (!empty($meta['tag_album'])) {
                            $album = $meta['tag_album'];
                        }
                        if (!empty($meta['tag_genre']) && $meta['tag_genre'] !== 'Local Scan') {
                            $genre = $meta['tag_genre'];
                        }
                        $duration = !empty($meta['tag_duration']) ? $meta['tag_duration'] : $duration;
                    }

                    $insertTable = get_insert_song_table($pdo);
                    $pdo->prepare("INSERT INTO `" . $insertTable . "` (title, artist, album, genre, file_name, file_size, duration, cover_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$title, $artist, $album, $genre, $newFileName, $file['size'], $duration, $cover]);
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
            if (!is_dir($dirPath)) return;
            $files = array_diff(scandir($dirPath), ['.', '..']);
            foreach ($files as $file) {
                $p = $dirPath . '/' . $file;
                if (is_dir($p)) {
                    $deleteDir($p);
                } else {
                    @unlink($p);
                }
            }
            @rmdir($dirPath);
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
                $artistPhoto = $customMeta['artist_photo'] ?? '';
                $bioText = $customMeta['bio'] ?? '';
            }
        } catch (Exception $e) {
            // Safe fallback
        }
        
        if (empty($bioText) || empty($artistPhoto)) {
            $apiKey = getenv('LASTFM_API_KEY') ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';
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
                            if ($t && !strpos($t, '2a96cbd8c706c450a33e3') && !strpos($t, 'default_album')) {
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
        
        if (!$bioText) {
            $bioText = $fallback;
        }
        
        $topTracks = [];
        $apiKey = getenv('LASTFM_API_KEY') ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';
        try {
            $stmtKey = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
            $stmtKey->execute();
            $dbKey = $stmtKey->fetchColumn();
            if ($dbKey) {
                $apiKey = $dbKey;
            }
        } catch (Exception $e) {}
            
        $urlTracks = 'http://ws.audioscrobbler.com/2.0/?method=artist.gettoptracks&artist=' . urlencode($artist) . '&api_key=' . $apiKey . '&format=json&limit=50';
        $ch2 = curl_init($urlTracks);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 4);
        $res2 = curl_exec($ch2);
        $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if ($http_code2 === 200 && $res2) {
            $dataTracks = json_decode($res2, true);
            if (isset($dataTracks['toptracks']['track']) && is_array($dataTracks['toptracks']['track'])) {
                foreach ($dataTracks['toptracks']['track'] as $tt) {
                    if (!empty($tt['name'])) {
                        $topTracks[] = $tt['name'];
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
            $apiKey = $customKey ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';

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
            // Default: Google Image Search with Consent Cookie bypass and gbv=1 (forces clean basic HTML)
            $googleUrl = 'https://www.google.com/search?q=' . urlencode($query) . '&tbm=isch&safe=active&gbv=1';
            $html = '';
            
            if (function_exists('curl_init')) {
                $ch = curl_init($googleUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0');
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_COOKIE, 'CONSENT=YES+cb.20230510-17-p0.en+FX+999;');
                $html = curl_exec($ch);
                curl_close($ch);
            }
            
            if (empty($html) && ini_get('allow_url_fopen')) {
                $opts = [
                    'http' => [
                        'method' => 'GET',
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0
" .
                                    "Cookie: CONSENT=YES+cb.20230510-17-p0.en+FX+999;
"
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ];
                $context = stream_context_create($opts);
                $html = @file_get_contents($googleUrl, false, $context);
            }

            if (!empty($html)) {
                // Try loose regex first to match gstatic thumbnails without strictly requiring src= quotes
                preg_match_all('/(https?:\/\/encrypted-tbn0\.gstatic\.com\/images\?q=tbn:[a-zA-Z0-9_\-%&\+=]+)/i', $html, $matches);
                if (!empty($matches[1])) {
                    $images = array_slice(array_unique($matches[1]), 0, 16);
                }

                // Match high-resolution source images via href imgres?imgurl=
                preg_match_all('/imgurl=([^&\\x22\\x27\\s>]+)/i', $html, $imgresMatches);
                if (!empty($imgresMatches[1])) {
                    $highResUrls = [];
                    foreach ($imgresMatches[1] as $imgUrlEncoded) {
                        $u = urldecode($imgUrlEncoded);
                        if (!empty($u) && strpos($u, 'google') === false && strpos($u, 'gstatic') === false && strpos($u, 'schema.org') === false && (strpos($u, 'http://') === 0 || strpos($u, 'https://') === 0)) {
                            $highResUrls[] = $u;
                        }
                    }
                    $highResUrls = array_slice(array_unique($highResUrls), 0, 12);
                    $images = array_merge($images, $highResUrls);
                }
                
                // Also match standard images with loose quotes/delimiters
                preg_match_all('/(https?:\/\/[^\x22\x27\x5c\s>]+?\.(?:jpg|jpeg|png)(?:\?[^\x22\x27\x5c\s>]+)?)/i', $html, $urlMatches);
                if (!empty($urlMatches[1])) {
                    $rawUrls = [];
                    foreach ($urlMatches[1] as $u) {
                        if (strpos($u, 'google') === false && strpos($u, 'gstatic') === false && strpos($u, 'schema.org') === false && !in_array($u, $images)) {
                            $rawUrls[] = $u;
                        }
                    }
                    $rawUrls = array_slice(array_unique($rawUrls), 0, 8);
                    $images = array_merge($images, $rawUrls);
                }
            }

            // Fallback to Yahoo/Bing Thumbnail endpoints
            if (count($images) < 4) {
                $yahooUrl = 'https://images.search.yahoo.com/search/images?p=' . urlencode($query);
                $yHtml = '';
                
                if (function_exists('curl_init')) {
                    $ch = curl_init($yahooUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    $yHtml = curl_exec($ch);
                    curl_close($ch);
                }
                
                if (empty($yHtml) && ini_get('allow_url_fopen')) {
                    $opts = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0
"
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $yHtml = @file_get_contents($yahooUrl, false, $context);
                }

                if (!empty($yHtml)) {
                    preg_match_all('/"iurl":"([^"]+)"/i', $yHtml, $yMatches);
                    $yUrls = [];
                    if (!empty($yMatches[1])) {
                        foreach ($yMatches[1] as $yu) {
                            $yuClean = stripslashes($yu);
                            if (filter_var($yuClean, FILTER_VALIDATE_URL)) {
                                $yUrls[] = $yuClean;
                            }
                        }
                    }
                    if (empty($yUrls)) {
                        preg_match_all('/(https?:\/\/tse\d+\.mm\.bing\.net\/th\?id=[^&"]+)/i', $yHtml, $yMatches2);
                        if (!empty($yMatches2[1])) {
                            $yUrls = $yMatches2[1];
                        }
                    }
                    if (!empty($yUrls)) {
                        $yUrls = array_slice(array_unique($yUrls), 0, 15);
                        $images = array_merge($images, $yUrls);
                    }
                }
            }

            // Absolute Unsplash fallbacks
            if (empty($images)) {
                $fallbacks = [
                    'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400',
                    'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400',
                    'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=400',
                    'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=400'
                ];
                $images = array_slice($fallbacks, 0, 8);
            }
        }

        $images = array_values(array_slice(array_unique($images), 0, 24));
        echo json_encode(['success' => true, 'images' => $images]);
        break;

    case 'search_artist_logo':
        $artist = trim($_GET['artist'] ?? '');
        $source = trim($_GET['source'] ?? 'google');
        
        if (empty($artist)) {
            echo json_encode(['success' => false, 'error' => 'A busca exige o nome do artista.']);
            break;
        }
        
        $images = [];
        
        if ($source === 'lastfm') {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'lastfm_api_key'");
            $stmt->execute();
            $customKey = $stmt->fetchColumn();
            $apiKey = $customKey ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';
            
            $lastfmUrl = 'http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=' . urlencode($artist) . '&api_key=' . $apiKey . '&format=json';
            $ch = curl_init($lastfmUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);
            curl_close($ch);
            
            if ($res) {
                $data = json_decode($res, true);
                $imgs = $data['artist']['image'] ?? [];
                if (!empty($imgs)) {
                    $mega = '';
                    foreach ($imgs as $img) {
                        if ($img['size'] === 'mega') $mega = $img['#text'];
                    }
                    if (empty($mega)) {
                        foreach ($imgs as $img) {
                            if ($img['size'] === 'extralarge') $mega = $img['#text'];
                        }
                    }
                    if (empty($mega)) {
                        foreach ($imgs as $img) {
                            if ($img['size'] === 'large') $mega = $img['#text'];
                        }
                    }
                    if (!empty($mega)) {
                        $images[] = $mega;
                    }
                }
            }
        } else if ($source === 'deezer') {
            $deezerUrl = 'https://api.deezer.com/search/artist?q=' . urlencode($artist);
            $ch = curl_init($deezerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);
            curl_close($ch);
            
            if ($res) {
                $data = json_decode($res, true);
                $items = $data['data'] ?? [];
                foreach ($items as $item) {
                    $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                    if (!empty($img)) {
                        $images[] = $img;
                    }
                }
            }
        } else {
            $query = $artist;
            // Clean noise terms
            $cleanArtist = preg_replace('/\b(logo|band|png|hd|headshot|icon|avatar|music|oficial|official)\b/i', '', $query);
            $cleanArtist = trim(preg_replace('/\s+/', ' ', $cleanArtist));
            if (empty($cleanArtist)) {
                $cleanArtist = $query;
            }

            // 1. Deezer Artist Search
            $deezerUrl = 'https://api.deezer.com/search/artist?q=' . urlencode($cleanArtist);
            if (function_exists('curl_init')) {
                $ch = curl_init($deezerUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                $resDz = curl_exec($ch);
                curl_close($ch);
                if ($resDz) {
                    $dataDz = json_decode($resDz, true);
                    if (isset($dataDz['data']) && is_array($dataDz['data'])) {
                        foreach ($dataDz['data'] as $item) {
                            $pics = [
                                $item['picture_xl'] ?? '',
                                $item['picture_big'] ?? '',
                                $item['picture_medium'] ?? '',
                                $item['picture'] ?? ''
                            ];
                            foreach ($pics as $pic) {
                                if (!empty($pic) && !in_array($pic, $images)) {
                                    $images[] = $pic;
                                }
                            }
                        }
                    }
                }
            }

            // 2. iTunes Album Search
            $itunesUrl = 'https://itunes.apple.com/search?term=' . urlencode($cleanArtist) . '&entity=album&limit=15';
            if (function_exists('curl_init')) {
                $ch = curl_init($itunesUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                $resItunes = curl_exec($ch);
                curl_close($ch);
                if ($resItunes) {
                    $dataIt = json_decode($resItunes, true);
                    if (isset($dataIt['results']) && is_array($dataIt['results'])) {
                        foreach ($dataIt['results'] as $item) {
                            if (!empty($item['artworkUrl100'])) {
                                $highRes = str_replace('100x100bb.jpg', '1000x1000bb.jpg', $item['artworkUrl100']);
                                if (!in_array($highRes, $images)) {
                                    $images[] = $highRes;
                                }
                            }
                        }
                    }
                }
            }

            // 3. Google HTML fallback (only if empty)
            if (empty($images)) {
                $googleUrl = 'https://www.google.com/search?q=' . urlencode($query) . '&tbm=isch&safe=active&gbv=1';
                $html = '';
                
                if (function_exists('curl_init')) {
                    $ch = curl_init($googleUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    curl_setopt($ch, CURLOPT_COOKIE, 'CONSENT=YES+cb.20230510-17-p0.en+FX+999;');
                    $html = curl_exec($ch);
                    curl_close($ch);
                }
                
                if (empty($html) && ini_get('allow_url_fopen')) {
                    $opts = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0
" .
                                        "Cookie: CONSENT=YES+cb.20230510-17-p0.en+FX+999;
"
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $html = @file_get_contents($googleUrl, false, $context);
                }

                if (!empty($html)) {
                    preg_match_all('/(https?:\/\/encrypted-tbn0\.gstatic\.com\/images\?q=tbn:[a-zA-Z0-9_\-%&\+=]+)/i', $html, $matches);
                    if (!empty($matches[1])) {
                        $images = array_slice(array_unique($matches[1]), 0, 16);
                    }
                }
            }
        }
        
        $images = array_values(array_slice(array_unique($images), 0, 24));
        echo json_encode(['success' => true, 'images' => $images]);
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
        
        $stmt = $pdo->prepare("UPDATE songs SET cover_url = ? WHERE artist = ? AND album = ?");
        $stmt->execute([$coverUrl, $artist, $album]);
        echo json_encode(['success' => true, 'cover_url' => $coverUrl]);
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
        
        // Auto-create table
        $pdo->exec("CREATE TABLE IF NOT EXISTS artist_metadata (
            artist VARCHAR(255) PRIMARY KEY,
            artist_photo VARCHAR(1000) DEFAULT NULL,
            bio TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
        $stmt->execute([$artist, $artistPhoto]);
        echo json_encode(['success' => true, 'artist_photo' => $artistPhoto]);
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

    case 'lastfm_sync':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        
        $apiKey = getenv('LASTFM_API_KEY') ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';
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

        // Get count of unique albums that have no cover or generic unsplash cover
        $unionAlbumsParts = [];
        foreach ($tables as $t) {
            $unionAlbumsParts[] = "SELECT artist, album, cover_url FROM `" . $t . "`";
        }
        $unionAlbumsSql = "SELECT DISTINCT artist, album 
            FROM (" . implode(" UNION ALL ", $unionAlbumsParts) . ") AS u 
            WHERE album IS NOT NULL AND album != '' AND (cover_url IS NULL OR cover_url = '' OR cover_url LIKE '%unsplash.com%' OR cover_url LIKE '%images.unsplash%')";
        
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
                        if ($t && !strpos($t, '2a96cbd8c706c450a33e3') && !strpos($t, 'default_album')) {
                            $weight = $sizePriority[$size] ?? -1;
                            if ($weight > $maxWeight) {
                                $maxWeight = $weight;
                                $bestImg = $t;
                            }
                        }
                    }
                    if ($bestImg) {
                        $photoUrl = $bestImg;
                        $artistUpdated++;
                    }
                }
                if (isset($data['artist']['bio']['summary'])) {
                    $bioText = $data['artist']['bio']['summary'];
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
            $art = $alb['artist'];
            $title = $alb['album'];
            
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
                        $coverUrl = $bestImg;
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
        
        // 1. Sync artist photos via Deezer
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
            $deezerUrl = 'https://api.deezer.com/search/artist?q=' . urlencode($art);
            $ch = curl_init($deezerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $photoUrl = 'no_photo';
            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    $item = $data['data'][0];
                    $img = $item['picture_xl'] ?? $item['picture_big'] ?? $item['picture_medium'] ?? $item['picture'] ?? '';
                    if (!empty($img)) {
                        $photoUrl = $img;
                        $artistUpdated++;
                    }
                }
            }
            
            $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
            $up->execute([$art, $photoUrl]);
        }
        
        // 2. Sync album covers via Deezer
        $unionAlbumsParts = [];
        foreach ($tables as $t) {
            $unionAlbumsParts[] = "SELECT artist, album, cover_url FROM `" . $t . "`";
        }
        $unionAlbumsSql = "SELECT DISTINCT artist, album 
            FROM (" . implode(" UNION ALL ", $unionAlbumsParts) . ") AS u 
            WHERE album IS NOT NULL AND album != '' AND (cover_url IS NULL OR cover_url = '' OR cover_url LIKE '%unsplash.com%' OR cover_url LIKE '%images.unsplash%')";
        
        $stmtTotalAlbumsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPending = intval($stmtTotalAlbumsPending->fetchColumn());
        
        // Select up to 15 albums to process in this batch
        $stmt = $pdo->query("SELECT artist, album FROM (" . $unionAlbumsSql . ") AS t LIMIT 15");
        $albums = $stmt->fetchAll();
        
        $albumUpdated = 0;
        foreach ($albums as $alb) {
            $art = trim($alb['artist']);
            $title = trim($alb['album']);
            
            if (!$art || !$title || $art === 'Artista Desconhecido' || $title === 'Single' || $title === 'Álbum Desconhecido') {
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

    case 'google_images_sync':
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

        // 1. Sync artist photos via Google Images
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
            $query = $art . ' logo band png hd';
            $googleUrl = 'https://www.google.com/search?q=' . urlencode($query) . '&tbm=isch&safe=active';
            $ch = curl_init($googleUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_COOKIE, 'CONSENT=YES+cb.20230510-17-p0.en+FX+999;');
            $html = curl_exec($ch);
            curl_close($ch);
            
            $photoUrl = 'no_photo';
            if ($html) {
                $cleanHtml = str_replace('\/', '/', $html);
                $rawUrls = [];
                preg_match_all('/(https?:\/\/[a-zA-Z0-9_\-\.\/:]+\.(?:jpg|jpeg|png|webp))/i', $cleanHtml, $urlMatches);
                if (!empty($urlMatches[1])) {
                    foreach ($urlMatches[1] as $u) {
                        if (strpos($u, 'google') === false && 
                            strpos($u, 'gstatic') === false && 
                            strpos($u, 'schema.org') === false && 
                            strpos($u, 'facebook.com') === false && 
                            strpos($u, 'twitter.com') === false &&
                            strpos($u, 'wikipedia.org') === false &&
                            strpos($u, 'icon') === false) {
                            $rawUrls[] = $u;
                        }
                    }
                }
                
                if (!empty($rawUrls)) {
                    $photoUrl = $rawUrls[0];
                    $artistUpdated++;
                } else {
                    preg_match_all('/(https?:\/\/encrypted-tbn0\.gstatic\.com\/images\?q=tbn:[a-zA-Z0-9_\-]+)/i', $cleanHtml, $matches);
                    if (!empty($matches[1])) {
                        $photoUrl = $matches[1][0];
                        $artistUpdated++;
                    }
                }
            }
            
            $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
            $up->execute([$art, $photoUrl]);
        }

        // 2. Sync album covers via Google Images
        // Build union query for albums lacking cover
        $unionAlbumsParts = [];
        foreach ($tables as $t) {
            $unionAlbumsParts[] = "SELECT artist, album, cover_url FROM `" . $t . "`";
        }
        $unionAlbumsSql = "SELECT DISTINCT artist, album 
            FROM (" . implode(" UNION ALL ", $unionAlbumsParts) . ") AS u 
            WHERE album IS NOT NULL AND album != '' AND (cover_url IS NULL OR cover_url = '' OR cover_url LIKE '%unsplash.com%' OR cover_url LIKE '%images.unsplash%')";
        
        $stmtTotalAlbumsPending = $pdo->query("SELECT COUNT(*) FROM (" . $unionAlbumsSql . ") AS t");
        $albumsPending = intval($stmtTotalAlbumsPending->fetchColumn());
        
        // Select up to 10 albums to process in this Google batch to preserve bandwidth and stability
        $stmt = $pdo->query("SELECT artist, album FROM (" . $unionAlbumsSql . ") AS t LIMIT 10");
        $albums = $stmt->fetchAll();
        
        $albumUpdated = 0;
        foreach ($albums as $alb) {
            $art = trim($alb['artist']);
            $title = trim($alb['album']);
            
            if (!$art || !$title || $art === 'Artista Desconhecido' || $title === 'Single' || $title === 'Álbum Desconhecido') {
                continue;
            }
            
            // Search album on Google Images - Using clean Artist and Album Name
            $query = $art . ' ' . $title;
            $googleUrl = 'https://www.google.com/search?q=' . urlencode($query) . '&tbm=isch&safe=active';
            $ch = curl_init($googleUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_COOKIE, 'CONSENT=YES+cb.20230510-17-p0.en+FX+999;');
            $html = curl_exec($ch);
            curl_close($ch);
            
            $coverUrl = '';
            if ($html) {
                // Pre-clean escaped slashes in JSON blocks so standard regex works flawlessly
                $cleanHtml = str_replace('\/', '/', $html);
                
                // Matches high quality raw image URLs using clean alphanumeric character class
                $rawUrls = [];
                preg_match_all('/(https?:\/\/[a-zA-Z0-9_\-\.\/:]+\.(?:jpg|jpeg|png|webp))/i', $cleanHtml, $urlMatches);
                if (!empty($urlMatches[1])) {
                    foreach ($urlMatches[1] as $u) {
                        if (strpos($u, 'google') === false && 
                            strpos($u, 'gstatic') === false && 
                            strpos($u, 'schema.org') === false && 
                            strpos($u, 'facebook.com') === false && 
                            strpos($u, 'twitter.com') === false &&
                            strpos($u, 'wikipedia.org') === false &&
                            strpos($u, 'icon') === false) {
                            $rawUrls[] = $u;
                        }
                    }
                }
                
                if (!empty($rawUrls)) {
                    $coverUrl = $rawUrls[0];
                } else {
                    // Fallback to Google CDN encrypted tbn thumbnail with precise alphanumeric class
                    preg_match_all('/(https?:\/\/encrypted-tbn0\.gstatic\.com\/images\?q=tbn:[a-zA-Z0-9_\-]+)/i', $cleanHtml, $matches);
                    if (!empty($matches[1])) {
                        $coverUrl = $matches[1][0];
                    }
                }
            }
            
            // Fallback to Yahoo/Bing image search if Google returned empty matches or blocked
            if (empty($coverUrl)) {
                $yahooUrl = 'https://images.search.yahoo.com/search/images?p=' . urlencode($query . ' album cover');
                $ch = curl_init($yahooUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $yHtml = curl_exec($ch);
                curl_close($ch);
                
                if ($yHtml) {
                    preg_match_all('/"iurl":"([^"]+)"/i', $yHtml, $yMatches);
                    if (!empty($yMatches[1])) {
                        foreach ($yMatches[1] as $yu) {
                            $yuClean = stripslashes($yu);
                            if (filter_var($yuClean, FILTER_VALIDATE_URL)) {
                                $coverUrl = $yuClean;
                                break;
                            }
                        }
                    }
                }
            }
            
            // Ultra-stable third level fallback: iTunes Search API (100% legal, non-authenticated, square art)
            if (empty($coverUrl)) {
                $itunesUrl = 'https://itunes.apple.com/search?term=' . urlencode($art . ' ' . $title) . '&entity=album&limit=1';
                $ch = curl_init($itunesUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                curl_setopt($ch, CURLOPT_USERAGENT, 'SubsonicPHPPlayer/1.0');
                $itunesRes = curl_exec($ch);
                curl_close($ch);
                if ($itunesRes) {
                    $itunesData = json_decode($itunesRes, true);
                    if (isset($itunesData['results'][0]['artworkUrl100'])) {
                        $itunesCover = $itunesData['results'][0]['artworkUrl100'];
                        // Convert 100x100 to 600x600 for absolute beautiful high quality definition
                        $coverUrl = str_replace('100x100bb', '600x600bb', $itunesCover);
                    }
                }
            }
            
            if ($coverUrl) {
                foreach ($tables as $t) {
                    $up = $pdo->prepare("UPDATE `" . $t . "` SET cover_url = ? WHERE artist = ? AND album = ?");
                    $up->execute([$coverUrl, $art, $title]);
                }
                $albumUpdated++;
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
            'message' => "Lote Google Images concluído! Atualizados {$artistUpdated} artistas e {$albumUpdated} capas de álbuns. Pendentes: " . ($artistsPendingFinal + $albumsPendingFinal)
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
            $apiKey = getenv('LASTFM_API_KEY') ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';
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
        $filePath = UPLOAD_DIR . $song['file_name'];
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . '/music/' . $song['file_name'];
        }
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit("Sem arquivo");
        }
        
        // Registrar reprodução de forma leve e estatística no banco de dados automaticamente
        try {
            $pdo->prepare("UPDATE `" . $table . "` SET play_count = play_count + 1, last_played = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
        } catch (Exception $log_ex) {}

        if (ob_get_level()) {
            ob_end_clean();
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

    case 'stream_video':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400);
            exit("ID do vídeo inválido");
        }
        $stmt = $pdo->prepare("SELECT file_name FROM videos WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        if (!$video) {
            http_response_code(404);
            exit("Vídeo não encontrado no catálogo");
        }
        $filePath = VIDEOS_DIR . $video['file_name'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit("Arquivo de vídeo físico não encontrado");
        }

        // Detectar o Content-Type ideal baseado na extensão do arquivo de vídeo
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'mkv'  => 'video/x-matroska',
            'mov'  => 'video/quicktime',
            'avi'  => 'video/x-msvideo',
            'ogv'  => 'video/ogg'
        ];
        $contentType = $mimeTypes[$ext] ?? 'video/mp4';

        // Desativar qualquer buffer de saída do PHP para economizar memória RAM e começar o envio de bytes na hora
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Tentar desativar compressão extra Gzip (evita carregar vídeo todo na memória antes do chunk)
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        $size = filesize($filePath);
        $fp = fopen($filePath, 'rb');
        if (!$fp) {
            http_response_code(500);
            exit("Erro crítico ao abrir arquivo de vídeo");
        }

        $start = 0;
        $end = $size - 1;

        // Gerenciar requisições HTTP parciais (Bytes Ranges) - Essencial para buscar tempo (seeking) e reproduzir sem travar
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            if (preg_match('/bytes=h*(d+)-(d*)[D.*]?/i', $range, $matches)) {
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

        // Enviar os cabeçalhos de controle ideais de cache e mídia
        header("Content-Type: $contentType");
        header("Accept-Ranges: bytes");
        header("Cache-Control: public, max-age=604800, no-transform"); // Cacheia por 1 semana no navegador/dispositivo
        header("Connection: keep-alive");

        // Loop de transmissão em blocos (buffers) de alto rendimento de 128KB
        $chunkSize = 131072; // 128KB por bloco
        while (!feof($fp) && ($start <= $end)) {
            $chunk = min($chunkSize, $end - $start + 1);
            echo fread($fp, $chunk);
            flush();
            $start += $chunk;
        }
        fclose($fp);
        exit;

    case 'videos':
        if ($method === 'GET') {
            if (!file_exists(VIDEOS_DIR)) {
                @mkdir(VIDEOS_DIR, 0755, true);
            }
            $stmt = $pdo->query("SELECT * FROM videos");
            $knownVideos = [];
            foreach ($stmt->fetchAll() as $row) {
                $knownVideos[$row['id']] = $row;
            }
            
            $results = [];
            if (file_exists(VIDEOS_DIR)) {
                $directory = new RecursiveDirectoryIterator(VIDEOS_DIR);
                $iterator = new RecursiveIteratorIterator($directory);
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isFile()) {
                        $ext = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
                        if (in_array($ext, ['mp4', 'webm', 'mkv', 'mov', 'avi'])) {
                            $absolutePath = $fileinfo->getPathname();
                            $relativePath = str_replace(chr(92), '/', substr($absolutePath, strlen(VIDEOS_DIR)));
                            $id = 'vid-' . md5($relativePath);
                            $title = pathinfo($fileinfo->getFilename(), PATHINFO_FILENAME);
                            $title = str_replace(['_', '-'], ' ', $title);
                            $fileSize = $fileinfo->getSize();
                            
                            $coverUrl = isset($knownVideos[$id]) ? $knownVideos[$id]['cover_url'] : null;
                            $createdAt = date('Y-m-d H:i:s', $fileinfo->getMTime());
                            
                            if (!isset($knownVideos[$id])) {
                                $stmtInsert = $pdo->prepare("INSERT INTO videos (id, title, file_name, file_size, cover_url, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmtInsert->execute([$id, $title, $relativePath, $fileSize, $coverUrl, $createdAt]);
                            }
                            
                            $results[] = [
                                'id' => $id,
                                'title' => $title,
                                'fileName' => $relativePath,
                                'fileSize' => intval($fileSize),
                                'coverUrl' => $coverUrl,
                                'createdAt' => $createdAt
                            ];
                        }
                    }
                }
            }
            echo json_encode($results);
        }
        break;

    case 'videos_scan':
        $videosDir = VIDEOS_DIR;
        if (!file_exists($videosDir)) {
            @mkdir($videosDir, 0755, true);
        }
        $stmt = $pdo->query("SELECT * FROM videos");
        $knownVideos = [];
        foreach ($stmt->fetchAll() as $row) {
            $knownVideos[$row['id']] = $row;
        }
        $newVideos = 0;
        $totalVideos = 0;
        if (file_exists($videosDir)) {
            $directory = new RecursiveDirectoryIterator($videosDir);
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $ext = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
                    if (in_array($ext, ['mp4', 'webm', 'mkv', 'mov', 'avi'])) {
                        $absolutePath = $fileinfo->getPathname();
                        $relativePath = str_replace(chr(92), '/', substr($absolutePath, strlen($videosDir)));
                        $id = 'vid-' . md5($relativePath);
                        $totalVideos++;
                        if (!isset($knownVideos[$id])) {
                            $title = pathinfo($fileinfo->getFilename(), PATHINFO_FILENAME);
                            $title = str_replace(['_', '-'], ' ', $title);
                            $fileSize = $fileinfo->getSize();
                            $createdAt = date('Y-m-d H:i:s', $fileinfo->getMTime());
                            $stmtInsert = $pdo->prepare("INSERT INTO videos (id, title, file_name, file_size, cover_url, created_at) VALUES (?, ?, ?, ?, NULL, ?)");
                            $stmtInsert->execute([$id, $title, $relativePath, $fileSize, $createdAt]);
                            $newVideos++;
                        }
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'count' => $newVideos, 'total' => $totalVideos]);
        break;

    case 'videos_upload_cover':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        if (!isset($_FILES['cover'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Arquivo de imagem não fornecido.']));
        }
        $id = trim($_GET['id'] ?? '');
        if (empty($id)) {
            http_response_code(400);
            exit(json_encode(['error' => 'ID do vídeo não especificado.']));
        }
        
        $file = $_FILES['cover'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Formato não suportado. Use PNG, JPG, JPEG, WEBP ou GIF.']));
        }
        
        $newFileName = 'cover_' . md5($id) . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], IMAGES_DIR . $newFileName)) {
            $coverUrl = 'images/' . $newFileName;
            $stmt = $pdo->prepare("UPDATE videos SET cover_url = ? WHERE id = ?");
            $stmt->execute([$coverUrl, $id]);
            echo json_encode(['success' => true, 'cover_url' => $coverUrl]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível mover a imagem para a pasta /images. Verifique permissões de escrita.']);
        }
        break;

    case 'files_list':
        $subpath = isset($_GET['path']) ? $_GET['path'] : '';
        $realBase = realpath(__DIR__);
        
        $is_valid_file_path = function($subpath, $realBase) {
            $subpath = trim(trim($subpath), '/\\');
            if (empty($subpath) || $subpath === '.') {
                return 'root';
            }
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) {
                @mkdir($musicDir, 0777, true);
            }
            $videosDir = $realBase . '/videos';
            if (!file_exists($videosDir)) {
                @mkdir($videosDir, 0777, true);
            }
            
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) {
                return false;
            }
            
            $allowedMusic = realpath($musicDir);
            $allowedVideos = realpath($videosDir);
            
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            if ($allowedVideos !== false && ($targetPath === $allowedVideos || strpos($targetPath, $allowedVideos . '/') === 0 || strpos($targetPath, $allowedVideos . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolved = $is_valid_file_path($subpath, $realBase);
        if ($resolved === false) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado ou diretório fora do escopo (/music ou /videos).']));
        }
        
        if ($resolved === 'root') {
            $musicDir = $realBase . '/music';
            $videosDir = $realBase . '/videos';
            if (!file_exists($musicDir)) { @mkdir($musicDir, 0777, true); }
            if (!file_exists($videosDir)) { @mkdir($videosDir, 0777, true); }
            
            $items = [
                [
                    'name' => 'music',
                    'path' => 'music',
                    'is_dir' => true,
                    'size' => 0,
                    'mtime' => @filemtime($musicDir) ?: time()
                ],
                [
                    'name' => 'videos',
                    'path' => 'videos',
                    'is_dir' => true,
                    'size' => 0,
                    'mtime' => @filemtime($videosDir) ?: time()
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
                $size = $isDir ? 0 : @filesize($fullPath);
                $mtime = @filemtime($fullPath);
                $items[] = [
                    'name' => $entry,
                    'path' => $relativeItem,
                    'is_dir' => $isDir,
                    'size' => $size ? $size : 0,
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
        $subpath = isset($_POST['path']) ? $_POST['path'] : '';
        $realBase = realpath(__DIR__);
        
        $is_valid_file_path = function($subpath, $realBase) {
            $subpath = trim(trim($subpath), '/\\');
            if (empty($subpath) || $subpath === '.') {
                return 'root';
            }
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) {
                @mkdir($musicDir, 0777, true);
            }
            $videosDir = $realBase . '/videos';
            if (!file_exists($videosDir)) {
                @mkdir($videosDir, 0777, true);
            }
            
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) {
                return false;
            }
            
            $allowedMusic = realpath($musicDir);
            $allowedVideos = realpath($videosDir);
            
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            if ($allowedVideos !== false && ($targetPath === $allowedVideos || strpos($targetPath, $allowedVideos . '/') === 0 || strpos($targetPath, $allowedVideos . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolved = $is_valid_file_path($subpath, $realBase);
        if ($resolved === false || $resolved === 'root') {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: não é permitido criar pastas na raiz virtual.']));
        }
        $parentDir = $resolved;
        $newDirName = isset($_POST['name']) ? trim($_POST['name']) : '';
        $newDirName = preg_replace('/[^a-zA-Z0-9_\-\s\.]/', '', $newDirName);
        if (empty($newDirName)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Nome da pasta inválido.']));
        }
        $newDirPath = $parentDir . '/' . $newDirName;
        if (file_exists($newDirPath)) {
            http_response_code(400);
            exit(json_encode(['error' => 'Uma pasta ou arquivo com este nome já existe.']));
        }
        if (mkdir($newDirPath, 0777, true)) {
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
            if (!file_exists($musicDir)) {
                @mkdir($musicDir, 0777, true);
            }
            $videosDir = $realBase . '/videos';
            if (!file_exists($videosDir)) {
                @mkdir($videosDir, 0777, true);
            }
            
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) {
                return false;
            }
            
            $allowedMusic = realpath($musicDir);
            $allowedVideos = realpath($videosDir);
            
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            if ($allowedVideos !== false && ($targetPath === $allowedVideos || strpos($targetPath, $allowedVideos . '/') === 0 || strpos($targetPath, $allowedVideos . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolved = $is_valid_file_path($subpath, $realBase);
        if ($resolved === false || $resolved === 'root' || $resolved === realpath($realBase . '/music') || $resolved === realpath($realBase . '/videos')) {
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
            return $success ? @rmdir($dir) : false;
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
            if (!file_exists($musicDir)) {
                @mkdir($musicDir, 0777, true);
            }
            $videosDir = $realBase . '/videos';
            if (!file_exists($videosDir)) {
                @mkdir($videosDir, 0777, true);
            }
            
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) {
                return false;
            }
            
            $allowedMusic = realpath($musicDir);
            $allowedVideos = realpath($videosDir);
            
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            if ($allowedVideos !== false && ($targetPath === $allowedVideos || strpos($targetPath, $allowedVideos . '/') === 0 || strpos($targetPath, $allowedVideos . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolvedOld = $is_valid_file_path($oldSubpath, $realBase);
        if ($resolvedOld === false || $resolvedOld === 'root' || $resolvedOld === realpath($realBase . '/music') || $resolvedOld === realpath($realBase . '/videos')) {
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
            exit(json_encode(['error' => 'Destino inválido ou fora dos limites (/music ou /videos).']));
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
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            exit(json_encode(['error' => 'Falha ao renomear arquivo ou pasta.']));
        }
        break;

    case 'files_upload':
        $subpath = isset($_POST['path']) ? trim($_POST['path']) : '';
        $realBase = realpath(__DIR__);
        
        $is_valid_file_path = function($subpath, $realBase) {
            $subpath = trim(trim($subpath), '/\\');
            if (empty($subpath) || $subpath === '.') {
                return 'root';
            }
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) {
                @mkdir($musicDir, 0777, true);
            }
            $videosDir = $realBase . '/videos';
            if (!file_exists($videosDir)) {
                @mkdir($videosDir, 0777, true);
            }
            
            $targetPath = realpath($realBase . '/' . $subpath);
            if ($targetPath === false) {
                return false;
            }
            
            $allowedMusic = realpath($musicDir);
            $allowedVideos = realpath($videosDir);
            
            if ($allowedMusic !== false && ($targetPath === $allowedMusic || strpos($targetPath, $allowedMusic . '/') === 0 || strpos($targetPath, $allowedMusic . '\\') === 0)) {
                return $targetPath;
            }
            if ($allowedVideos !== false && ($targetPath === $allowedVideos || strpos($targetPath, $allowedVideos . '/') === 0 || strpos($targetPath, $allowedVideos . '\\') === 0)) {
                return $targetPath;
            }
            return false;
        };

        $resolved = $is_valid_file_path($subpath, $realBase);
        if ($resolved === 'root' || $resolved === false) {
            $musicDir = $realBase . '/music';
            if (!file_exists($musicDir)) {
                @mkdir($musicDir, 0777, true);
            }
            $resolved = realpath($musicDir);
        }
        if ($resolved === false) {
            http_response_code(403);
            exit(json_encode(['error' => 'Acesso negado: pasta de destino inválida.']));
        }
        $targetDir = $resolved;
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            exit(json_encode(['error' => 'Nenhum arquivo enviado.']));
        }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            exit(json_encode(['error' => 'Erro no upload do arquivo (Código: ' . $file['error'] . ').']));
        }
        $uploadedName = basename($file['name']);
        $uploadedName = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $uploadedName);
        $destPath = $targetDir . '/' . $uploadedName;
        $protected = ['index.php', 'api.php', 'config.php', 'mobile.php', 'debug.php', 'package.json', 'server.ts', 'tsconfig.json', '.env', '.env.example'];
        if (in_array(strtolower($uploadedName), $protected)) {
            http_response_code(403);
            exit(json_encode(['error' => 'Upload negado: impossível sobrescrever arquivo protegido do sistema.']));
        }
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['success' => true, 'filename' => $uploadedName]);
        } else {
            http_response_code(500);
            exit(json_encode(['error' => 'Falha ao mover arquivo enviado para o destino. Verifique permissões.']));
        }
        break;

    case 'podcasts':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM songs WHERE genre = 'Podcast' ORDER BY created_at DESC");
            $podcastTracks = $stmt->fetchAll();
            $podcastsMap = [];

            foreach ($podcastTracks as $t) {
                $podName = $t['album'];
                if (!isset($podcastsMap[$podName])) {
                    $feedUrlFromDb = '';
                    $limitFromDb = 5;
                    try {
                        $stmtUrl = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $stmtUrl->execute(["podcast_feed_" . $podName]);
                        $feedUrlFromDb = $stmtUrl->fetchColumn() ?: '';
                        
                        $stmtLimit = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $stmtLimit->execute(["podcast_limit_" . $podName]);
                        $limitVal = $stmtLimit->fetchColumn();
                        if ($limitVal !== false && $limitVal !== null) {
                            $limitFromDb = intval($limitVal);
                        }
                    } catch (Exception $e) {}

                    $podcastsMap[$podName] = [
                        'name' => $podName,
                        'artist' => $t['artist'],
                        'coverUrl' => $t['cover_url'] ?? 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150',
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
            echo json_encode(array_values($podcastsMap));
        }
        break;

    case 'podcasts_sync':
        if ($method !== 'POST') {
            http_response_code(405);
            exit(json_encode(['error' => 'Apenas requisições POST são permitidas.']));
        }
        $rawInput = file_get_contents('php://input');
        $dataDecoded = json_decode($rawInput, true);
        $feedUrl = trim($dataDecoded['feedUrl'] ?? '');
        $maxEpisodes = intval($dataDecoded['maxEpisodes'] ?? 5);
        if ($maxEpisodes < 1) {
            $maxEpisodes = 5;
        }
        if (empty($feedUrl)) {
            http_response_code(400);
            exit(json_encode(['error' => 'O endereço feedUrl é obrigatório.']));
        }

        try {
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
"
                ]
            ];
            $context = stream_context_create($opts);
            $xml = @file_get_contents($feedUrl, false, $context);
            if ($xml === false) {
                http_response_code(400);
                exit(json_encode(['error' => 'Falha ao conectar e ler o feed RSS do Podcast no PHP.']));
            }

            // 1. Extract Podcast Name
            $podcastName = "Podcast Desconhecido";
            if (preg_match('/<channel>([\s\S]*?)<\/channel>/i', $xml, $cMatch)) {
                $channelXml = $cMatch[1];
            } else {
                $channelXml = $xml;
            }
            if (preg_match('/<title>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/title>/i', $channelXml, $tMatch)) {
                $podcastName = preg_replace('/\s+/', ' ', trim($tMatch[1]));
            }

            $safePodcastName = preg_replace('/[\/\\\?%*:|"<>]/', '-', $podcastName);
            $safePodcastName = trim($safePodcastName);

            // Save feedUrl and limit to Settings
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(100) PRIMARY KEY,
                    setting_value TEXT DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $stmtSaveFeed = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmtSaveFeed->execute(["podcast_feed_" . $podcastName, $feedUrl]);
                
                $stmtSaveLimit = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmtSaveLimit->execute(["podcast_limit_" . $podcastName, strval($maxEpisodes)]);
            } catch (Exception $e) {}

            // 2. Extract Cover
            $coverUrl = 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=450';
            if (preg_match('/<itunes:image\s+[^>]*href=["\']([^"\']+)["\']/i', $channelXml, $imgMatch)) {
                $coverUrl = $imgMatch[1];
            } else {
                if (preg_match('/<image>([\s\S]*?)<\/image>/i', $channelXml, $imgBlock)) {
                    if (preg_match('/<url>([\s\S]*?)<\/url>/i', $imgBlock[1], $urlMatch)) {
                        $coverUrl = trim($urlMatch[1]);
                    }
                }
            }

            // Folder
            $podcastDir = UPLOAD_DIR . '/podcast/' . $safePodcastName;
            if (!file_exists($podcastDir)) {
                @mkdir($podcastDir, 0755, true);
            }

            // 3. Extract items
            preg_match_all('/<item>([\s\S]*?)<\/item>/i', $xml, $itemMatches);
            $items = $itemMatches[1] ?? [];
            $itemsToProcess = array_slice($items, 0, $maxEpisodes);
            $downloadedCount = 0;

            foreach ($itemsToProcess as $itemXml) {
                $epTitleRaw = 'Episódio Desconhecido';
                if (preg_match('/<title>(?:<!\[CDATA\[)?([\s\S]*?)(?:\]\]>)?<\/title>/i', $itemXml, $epTitleMatch)) {
                    $epTitleRaw = preg_replace('/\s+/', ' ', trim($epTitleMatch[1]));
                }
                $cleanEpTitle = preg_replace('/[\/\\\?%*:|"<>]/', '-', $epTitleRaw);
                $cleanEpTitle = trim($cleanEpTitle);

                $mediaUrl = '';
                if (preg_match('/<enclosure[^>]+url=["\']([^"\']+)["\']/i', $itemXml, $encMatch)) {
                    $mediaUrl = $encMatch[1];
                } else {
                    if (preg_match('/<enclosure[\s\S]*?url=["\']([^"\']+)["\']/i', $itemXml, $fallbackEncMatch)) {
                        $mediaUrl = $fallbackEncMatch[1];
                    }
                }

                if (empty($mediaUrl)) continue;

                $episodeFileName = $cleanEpTitle . '.mp3';
                $filePath = $podcastDir . '/' . $episodeFileName;
                $fileSize = 0;

                if (!file_exists($filePath)) {
                    $ch = curl_init($mediaUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'PHPlayerPodcastDownloader/1.0 PHP');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    $fileData = curl_exec($ch);
                    curl_close($ch);

                    if ($fileData !== false) {
                        @file_put_contents($filePath, $fileData);
                        $fileSize = strlen($fileData);
                        $downloadedCount++;
                    } else {
                        continue;
                    }
                } else {
                    $fileSize = filesize($filePath);
                }

                $durationInSeconds = 1800;
                if (preg_match('/<itunes:duration>([\s\S]*?)<\/itunes:duration>/i', $itemXml, $durMatch)) {
                    $durStr = trim($durMatch[1]);
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

                // Check if already in songs table
                $stmtCheck = $pdo->prepare("SELECT id FROM songs WHERE file_name = ?");
                $stmtCheck->execute([$relativeFileName]);
                $exists = $stmtCheck->fetchColumn();

                if ($exists) {
                    $stmtUpd = $pdo->prepare("UPDATE songs SET title = ?, artist = ?, album = ?, duration = ?, file_size = ?, cover_url = ? WHERE id = ?");
                    $stmtUpd->execute([$epTitleRaw, $podcastName, $podcastName, $durationInSeconds, $fileSize, $coverUrl, $exists]);
                } else {
                    $stmtIns = $pdo->prepare("INSERT INTO songs (title, artist, album, genre, file_name, file_size, duration, cover_url) VALUES (?, ?, ?, 'Podcast', ?, ?, ?, ?)");
                    $stmtIns->execute([$epTitleRaw, $podcastName, $podcastName, $relativeFileName, $fileSize, $durationInSeconds, $coverUrl]);
                }
            }

            // Excluir episódios excedentes (mais antigos) se o total passar de $maxEpisodes
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
                        
                        $fullPathToDelete = UPLOAD_DIR . '/' . $epFileName;
                        if (!empty($epFileName) && file_exists($fullPathToDelete)) {
                            @unlink($fullPathToDelete);
                        }
                        
                        $stmtDel = $pdo->prepare("DELETE FROM songs WHERE id = ?");
                        $stmtDel->execute([$epId]);
                    }
                }
            } catch (Exception $delErr) {}

            echo json_encode(['success' => true, 'podcastName' => $podcastName, 'episodeCount' => $downloadedCount]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao sincronizar podcast: ' . $e->getMessage()]);
        }
        break;

    case 'radios':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM radios ORDER BY created_at DESC");
            $radios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($radios);
        } else if ($method === 'POST') {
            // Require admin role
            $usernameHeader = $_SERVER['HTTP_X_USERNAME'] ?? '';
            $stmtUser = $pdo->prepare("SELECT role FROM users WHERE username = ?");
            $stmtUser->execute([$usernameHeader]);
            $role = $stmtUser->fetchColumn();
            if ($role !== 'admin') {
                http_response_code(403);
                exit(json_encode(['error' => 'Apenas administradores podem cadastrar rádios.']));
            }

            $rawInput = file_get_contents('php://input');
            $dataDecoded = json_decode($rawInput, true);
            $name = trim($dataDecoded['name'] ?? '');
            $url = trim($dataDecoded['url'] ?? '');

            if (empty($name) || empty($url)) {
                http_response_code(400);
                exit(json_encode(['error' => 'Nome e endereço são obrigatórios.']));
            }

            // Resolve playlist stream URL (.m3u, .pls, .asx) if needed
            $resolvedUrl = $url;
            try {
                $opts = [
                    "http" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
",
                        "timeout" => 5
                    ]
                ];
                $context = stream_context_create($opts);
                $lowerUrl = strtolower($url);

                if (strpos($lowerUrl, '.m3u') !== false || strpos($lowerUrl, '.pls') !== false || strpos($lowerUrl, '.asx') !== false) {
                    $text = @file_get_contents($url, false, $context);
                    if ($text !== false) {
                        if (strpos($lowerUrl, '.m3u') !== false || strpos($text, '#EXTM3U') !== false || strpos($text, '#EXTINF') !== false) {
                            // Parse M3U
                            $lines = explode("
", $text);
                            foreach ($lines as $line) {
                                $trimmed = trim($line);
                                if ($trimmed && strpos($trimmed, '#') !== 0) {
                                    if (strpos($trimmed, 'http://') === 0 || strpos($trimmed, 'https://') === 0) {
                                        $resolvedUrl = $trimmed;
                                        break;
                                    }
                                }
                            }
                        } else if (strpos($lowerUrl, '.pls') !== false || strpos(strtolower($text), '[playlist]') !== false) {
                            // Parse PLS
                            $lines = explode("
", $text);
                            foreach ($lines as $line) {
                                $trimmed = trim($line);
                                if (strpos(strtolower($trimmed), 'file') === 0) {
                                    $parts = explode('=', $trimmed, 2);
                                    if (count($parts) > 1) {
                                        $streamUrl = trim($parts[1]);
                                        if (strpos($streamUrl, 'http://') === 0 || strpos($streamUrl, 'https://') === 0) {
                                            $resolvedUrl = $streamUrl;
                                            break;
                                        }
                                    }
                                }
                            }
                        } else if (strpos($lowerUrl, '.asx') !== false || strpos(strtolower($text), '<asx') !== false || strpos(strtolower($text), '<entry>') !== false) {
                            // Parse ASX (XML-like)
                            if (preg_match('/<ref\s+href\s*=\s*["\']([^"\']+)["\']/i', $text, $matches)) {
                                $resolvedUrl = trim($matches[1]);
                            } else if (preg_match('/href\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i', $text, $matches)) {
                                $resolvedUrl = trim($matches[1]);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignore resolve errors, fallback to original URL
            }

            $id = 'radio-' . round(microtime(true) * 1000);
            $stmtIns = $pdo->prepare("INSERT INTO radios (id, name, url, resolved_url, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmtIns->execute([$id, $name, $url, $resolvedUrl, date('Y-m-d H:i:s')]);

            echo json_encode([
                'success' => true,
                'radio' => [
                    'id' => $id,
                    'name' => $name,
                    'url' => $url,
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
        // Require admin role
        $usernameHeader = $_SERVER['HTTP_X_USERNAME'] ?? '';
        $stmtUser = $pdo->prepare("SELECT role FROM users WHERE username = ?");
        $stmtUser->execute([$usernameHeader]);
        $role = $stmtUser->fetchColumn();
        if ($role !== 'admin') {
            http_response_code(403);
            exit(json_encode(['error' => 'Apenas administradores podem remover rádios.']));
        }

        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            $rawInput = file_get_contents('php://input');
            $dataDecoded = json_decode($rawInput, true);
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
