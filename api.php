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

set_exception_handler(function ($exception) {
    header('Content-Type: application/json; charset=utf-8');
    $msg = 'Exceção não tratada no servidor PHP: ' . $exception->getMessage() . ' (' . basename($exception->getFile()) . ':' . $exception->getLine() . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
    echo json_encode([
        'error' => $msg,
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    header('Content-Type: application/json; charset=utf-8');
    $msg = 'Erro/Aviso de execução no PHP: ' . $message . ' (' . basename($file) . ':' . $line . ')';
    @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
    echo json_encode([
        'error' => $msg,
        'file' => basename($file),
        'line' => $line
    ]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json; charset=utf-8');
        $msg = 'ERRO FATAL PHP: ' . $error['message'] . ' (' . basename($error['file']) . ':' . $error['line'] . ')';
        @file_put_contents('api_errors.log', date('[Y-m-d H:i:s] ') . $msg . "
", FILE_APPEND);
        echo json_encode([
            'error' => $msg,
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
        exit;
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

            // Garantir coluna theme
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN theme VARCHAR(30) DEFAULT 'default'");
            } catch (Exception $theme_ex) {
                // Ignora se já existir
            }

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
                $clean = trim($frameContent, "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F");
                return trim($clean);
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
                        $titlev1 = trim(substr($tag, 3, 30), "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F");
                        $artistv1 = trim(substr($tag, 33, 30), "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F");
                        $albumv1 = trim(substr($tag, 63, 30), "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F");
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
                    }
                }
                fclose($handle_v1);
            }
        }
        return $meta;
    };

    switch ($route) {
    case 'login':
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(['success' => true, 'username' => $user['username'], 'role' => $user['role'], 'theme' => $user['theme'] ?? 'default']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário ou senha incorretos']);
        }
        break;

    case 'users':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT id, username, role, theme FROM users ORDER BY username ASC");
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

    case 'repair_db':
        try {
            // Remove NULL characters (CHAR(0))
            $pdo->query("UPDATE songs SET title = REPLACE(title, CHAR(0), '') WHERE title LIKE '%\0%'");
            $pdo->query("UPDATE songs SET genre = REPLACE(genre, CHAR(0), '') WHERE genre LIKE '%\0%'");
            $pdo->query("UPDATE songs SET artist = REPLACE(artist, CHAR(0), '') WHERE artist LIKE '%\0%'");
            $pdo->query("UPDATE songs SET album = REPLACE(album, CHAR(0), '') WHERE album LIKE '%\0%'");

            // Remove interrogaçoes
            $pdo->query("UPDATE songs SET title = REPLACE(title, '?', '') WHERE title LIKE '%?%'");
            $pdo->query("UPDATE songs SET genre = REPLACE(genre, '?', '') WHERE genre LIKE '%?%'");
            $pdo->query("UPDATE songs SET artist = REPLACE(artist, '?', '') WHERE artist LIKE '%?%'");
            $pdo->query("UPDATE songs SET album = REPLACE(album, '?', '') WHERE album LIKE '%?%'");
            
            // Corrige generos em branco
            $pdo->query("UPDATE songs SET genre = 'Desconhecido' WHERE genre IS NULL OR TRIM(genre) = ''");

            echo json_encode(['status' => 'ok', 'message' => 'Reparação realizada com sucesso! Limpeza concluída de caracteres nulos e falhas de encoding.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao realizar a reparação: ' . $e->getMessage()]);
        }
        break;

    case 'scan':
        $musicDir = __DIR__ . '/music/';
        if (!file_exists($musicDir)) {
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
        $stmtAll = $pdo->query("SELECT id, file_name FROM songs");
        $allSongs = $stmtAll->fetchAll();
        foreach ($allSongs as $song) {
            $fName = $song['file_name'];
            if (strpos($fName, 'http') === 0 || strpos($fName, 'music_') === 0) {
                continue; // Skip seed streams and web uploads
            }
            if (!file_exists($musicDir . $fName)) {
                $pdo->prepare("DELETE FROM songs WHERE id = ?")->execute([$song['id']]);
                $removedTracksCount++;
            }
        }

        if (file_exists($musicDir)) {
            $directory = new RecursiveDirectoryIterator($musicDir);
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $ext = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
                    if (in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
                        $absolutePath = $fileinfo->getPathname();
                        $relativePath = str_replace(chr(92), '/', substr($absolutePath, strlen($musicDir)));
                        
                        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM songs WHERE file_name = ?");
                        $stmtCheck->execute([$relativePath]);
                        if ($stmtCheck->fetchColumn() == 0) {
                            $parts = explode('/', $relativePath);
                            // Set artist from the main/top folder under /music
                            $artist = count($parts) >= 2 ? $parts[0] : 'Artista Desconhecido';
                            // Set album from the folder containing the file itself
                            $album = count($parts) >= 3 ? $parts[count($parts) - 2] : 'Single';
                            $lastName = basename($relativePath);
                            $title = pathinfo($lastName, PATHINFO_FILENAME);
                            $title = str_replace(['_', '-'], ' ', $title);
                            $randomCover = $coverOptions[array_rand($coverOptions)];
                            
                            $genre = 'Local Scan';
                            $duration = 210;
 
                            // Apply ID3 tag reader
                            if ($ext === 'mp3') {
                                $meta = $getMp3Meta($absolutePath);
                                $genre = $meta['tag_genre'];
                                $duration = $meta['tag_duration'];
                                if (!empty($meta['tag_title'])) $title = $meta['tag_title'];
                                
                                // Only use metadata tags if the folder layout didn't provide a folder name
                                if ($artist === 'Artista Desconhecido' && !empty($meta['tag_artist'])) {
                                    $artist = $meta['tag_artist'];
                                }
                                if ($album === 'Single' && !empty($meta['tag_album'])) {
                                    $album = $meta['tag_album'];
                                }
                            }
 
                            $pdo->prepare("INSERT INTO songs (title, artist, album, genre, file_name, file_size, duration, cover_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                                ->execute([$title, $artist, $album, $genre, $relativePath, $fileinfo->getSize(), $duration, $randomCover]);
                            $newTracks++;
                        }
                    }
                }
            }
        }

        // Align genres within the same album (propagate the most specific ID3 genre to all tracks in each album)
        $stmtAlbums = $pdo->query("SELECT DISTINCT album FROM songs WHERE album != '' AND album IS NOT NULL");
        $albumsList = $stmtAlbums->fetchAll(PDO::FETCH_COLUMN);
        foreach ($albumsList as $albName) {
            $stmtGenre = $pdo->prepare("SELECT genre FROM songs WHERE album = ? AND genre NOT IN ('Local Scan', 'Desconhecido', 'Single', 'Unknown', 'Upload', '') AND genre IS NOT NULL LIMIT 1");
            $stmtGenre->execute([$albName]);
            $validGenre = $stmtGenre->fetchColumn();
            if ($validGenre) {
                $stmtUpdate = $pdo->prepare("UPDATE songs SET genre = ? WHERE album = ?");
                $stmtUpdate->execute([$validGenre, $albName]);
            }
        }

        $total = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
        echo json_encode(['success' => true, 'count' => $newTracks, 'removed' => $removedTracksCount, 'total' => intval($total)]);
        break;

    case 'tracks':
        if ($method === 'GET') {
            try {
                if (!isset($pdo) || !$pdo) {
                    throw new Exception("Falha de infraestrutura: Conexão PDO com o Banco de Dados não foi iniciada.");
                }
                $query = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
                if ($query === false) {
                    throw new Exception("Erro de Consulta: Falha ao consultar a tabela 'songs'. Se o banco de dados for novo, verifique se as tabelas foram criadas corretamente.");
                }
                $songs = $query->fetchAll();
                echo json_encode($songs ?: []);
            } catch (Exception $e) {
                http_response_code(500);
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

                    $pdo->prepare("INSERT INTO songs (title, artist, album, genre, file_name, file_size, duration, cover_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
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
        $song = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
        $song->execute([$id]);
        $track = $song->fetch();
        if ($track) {
            $filePath = UPLOAD_DIR . $track['file_name'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $pdo->prepare("DELETE FROM songs WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'update_track_title':
        if ($method !== 'POST' && $method !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        if (!$id || empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Id e título são obrigatórios.']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE songs SET title = ? WHERE id = ?");
        $stmt->execute([$title, $id]);
        echo json_encode(['success' => true]);
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
            $stmt = $pdo->prepare("UPDATE songs SET cover_url = ? WHERE artist = ? AND album = ?");
            $stmt->execute([$coverUrl, $artist, $album]);
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
        
        echo json_encode([
            'bio' => $bioText,
            'artist_photo' => $artistPhoto ?: null
        ]);
        break;

    case 'search_images':
        $query = trim($_GET['q'] ?? '');
        if (empty($query)) {
            echo json_encode(['success' => false, 'error' => 'Query is required']);
            break;
        }

        $images = [];

        // 1. Google Image Search with Consent Cookie bypass
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

        if ($html) {
            preg_match_all('/src="(https:\/\/encrypted-tbn0\.gstatic\.com\/images\?q=tbn:[^"]+)"/i', $html, $matches);
            if (!empty($matches[1])) {
                $images = array_slice(array_unique($matches[1]), 0, 15);
            }
            preg_match_all('/"(https?:\/\/[^"]+\.(?:jpg|jpeg|png))"/i', $html, $urlMatches);
            if (!empty($urlMatches[1])) {
                $rawUrls = [];
                foreach ($urlMatches[1] as $u) {
                    if (strpos($u, 'google') === false && strpos($u, 'gstatic') === false && strpos($u, 'schema.org') === false) {
                        $rawUrls[] = $u;
                    }
                }
                $rawUrls = array_slice(array_unique($rawUrls), 0, 8);
                $images = array_merge($images, $rawUrls);
            }
        }

        // 2. Fallback to Yahoo/Bing Thumbnail endpoints (resilient on datacenter IPs)
        if (count($images) < 4) {
            $yahooUrl = 'https://images.search.yahoo.com/search/images?p=' . urlencode($query);
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

        // 3. Absolute Unsplash fallbacks
        if (empty($images)) {
            $fallbacks = [
                'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400',
                'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400',
                'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=400',
                'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=400'
            ];
            $images = array_slice($fallbacks, 0, 8);
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
        
        // Find all unique artists in songs
        $stmt = $pdo->query("SELECT DISTINCT artist FROM songs WHERE artist IS NOT NULL AND artist != ''");
        $artists = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $artistUpdated = 0;
        foreach ($artists as $art) {
            // Check if we already have an artist photo
            $check = $pdo->prepare("SELECT artist_photo FROM artist_metadata WHERE artist = ?");
            $check->execute([$art]);
            $currentPhoto = $check->fetchColumn();
            
            if (!$currentPhoto) {
                // Fetch from Last.fm
                $url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=' . urlencode($art) . '&api_key=' . $apiKey . '&format=json&lang=pt';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $res = curl_exec($ch);
                curl_close($ch);
                
                if ($res) {
                    $data = json_decode($res, true);
                    $photoUrl = '';
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
                        }
                    }
                    if ($photoUrl) {
                        $up = $pdo->prepare("INSERT INTO artist_metadata (artist, artist_photo) VALUES (?, ?) ON DUPLICATE KEY UPDATE artist_photo = VALUES(artist_photo)");
                        $up->execute([$art, $photoUrl]);
                        $artistUpdated++;
                    }
                }
            }
        }
        
        // Find all unique (artist, album) that have generic or missing cover
        $stmt = $pdo->query("SELECT DISTINCT artist, album FROM songs WHERE album IS NOT NULL AND album != ''");
        $albums = $stmt->fetchAll();
        
        $albumUpdated = 0;
        foreach ($albums as $alb) {
            $art = $alb['artist'];
            $title = $alb['album'];
            
            // Query one song to see if the cover is missing or generic (using unsplash or empty)
            $check = $pdo->prepare("SELECT cover_url FROM songs WHERE artist = ? AND album = ? LIMIT 1");
            $check->execute([$art, $title]);
            $cover = $check->fetchColumn();
            
            if (empty($cover) || strpos($cover, 'unsplash.com') !== false || strpos($cover, 'images.unsplash') !== false) {
                // Fetch cover from Last.fm
                $url = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&artist=' . urlencode($art) . '&album=' . urlencode($title) . '&api_key=' . $apiKey . '&format=json';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $res = curl_exec($ch);
                curl_close($ch);
                
                if ($res) {
                    $data = json_decode($res, true);
                    $coverUrl = '';
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
                    if ($coverUrl) {
                        $up = $pdo->prepare("UPDATE songs SET cover_url = ? WHERE artist = ? AND album = ?");
                        $up->execute([$coverUrl, $art, $title]);
                        $albumUpdated++;
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'artists_updated' => $artistUpdated,
            'albums_updated' => $albumUpdated,
            'message' => "Sincronização com Last.fm concluída! Atualizados {$artistUpdated} artistas e {$albumUpdated} capas de álbuns."
        ]);
        break;

    case 'album_cover':
        $artist = trim($_GET['artist'] ?? '');
        $album = trim($_GET['album'] ?? '');
        if (!$artist || !$album) {
            echo json_encode(['success' => false, 'error' => 'Required parameters missing']);
            break;
        }
        
        $apiKey = getenv('LASTFM_API_KEY') ?: '4cb074e4b8ec4ee9b6eb6caae250ec4b';
        $url = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&artist=' . urlencode($artist) . '&album=' . urlencode($album) . '&api_key=' . $apiKey . '&format=json';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $coverUrl = '';
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
        
        if ($coverUrl) {
            $stmt = $pdo->prepare("UPDATE songs SET cover_url = ? WHERE artist = ? AND album = ? AND (cover_url IS NULL OR cover_url LIKE '%unsplash.com%')");
            $stmt->execute([$coverUrl, $artist, $album]);
            echo json_encode(['success' => true, 'cover_url' => $coverUrl]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'stream':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT file_name FROM songs WHERE id = ?");
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
        $size = filesize($filePath);
        $fp = fopen($filePath, 'rb');
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
                            $id = 'vid-' . substr(bin2hex($relativePath), 0, 16);
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
                        $id = 'vid-' . substr(bin2hex($relativePath), 0, 16);
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
