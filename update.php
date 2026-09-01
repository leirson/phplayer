<?php
/**
 * PHPlayer - Script de Atualização e Reparo de Emergência
 * Permite que administradores atualizem o sistema e reparem arquivos danificados
 */
@session_start();
@set_time_limit(300);
@ini_set('memory_limit', '256M');

// Carregar versão local
$local_version = '1.0.0';
if (file_exists(__DIR__ . '/version.php')) {
    include_once __DIR__ . '/version.php';
    if (defined('PHPLAYER_VERSION')) {
        $local_version = PHPLAYER_VERSION;
    }
}

// Carregar configurações de banco se existirem
$db_connected = false;
$db_error = '';
$pdo = null;

if (file_exists(__DIR__ . '/config.php')) {
    try {
        require_once __DIR__ . '/config.php';
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $db_connected = true;
        }
    } catch (Throwable $e) {
        $db_error = $e->getMessage();
    }
}

// Processar Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['phplayer_admin_logged']);
    unset($_SESSION['phplayer_admin_user']);
    header("Location: update.php");
    exit;
}

// Processar Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $login_error = 'Informe o usuário e a senha.';
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password'])) {
                    if (($user['role'] ?? '') === 'admin') {
                        $_SESSION['phplayer_admin_logged'] = true;
                        $_SESSION['phplayer_admin_user'] = $user['username'];
                        header("Location: update.php");
                        exit;
                    } else {
                        $login_error = 'Apenas usuários administradores podem acessar a ferramenta de atualização.';
                    }
                } else {
                    $login_error = 'Usuário ou senha incorretos.';
                }
            } catch (Throwable $e) {
                $login_error = 'Erro ao consultar banco de dados: ' . $e->getMessage();
            }
        } else {
            $login_error = 'Não foi possível conectar ao banco de dados para validar o login.';
        }
    }
}

$is_admin = !empty($_SESSION['phplayer_admin_logged']) && $_SESSION['phplayer_admin_logged'] === true;

// Endpoint AJAX: Verificar Versão Remota no GitHub
if (isset($_GET['action']) && $_GET['action'] === 'check_version') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso não autorizado']);
        exit;
    }

    $remote_version = $local_version;
    $changelog = 'Não foi possível obter o changelog remoto.';
    
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'user_agent' => 'PHPlayer-Updater/' . $local_version
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $remote_ver_raw = @file_get_contents('https://raw.githubusercontent.com/leirson/phplayer/main/version.php', false, $ctx);
    if ($remote_ver_raw && preg_match("/define\\('PHPLAYER_VERSION',\\s*'([^']+)'\\)/", $remote_ver_raw, $m)) {
        $remote_version = $m[1];
    }

    $remote_chg_raw = @file_get_contents('https://raw.githubusercontent.com/leirson/phplayer/main/changelog.php', false, $ctx);
    if ($remote_chg_raw && preg_match('/\$changelog = <<<EOT\\n(.*?)\\nEOT;/s', $remote_chg_raw, $m)) {
        $changelog = trim($m[1]);
    }

    echo json_encode([
        'success' => true,
        'local_version' => $local_version,
        'remote_version' => $remote_version,
        'has_update' => version_compare($remote_version, $local_version, '>'),
        'changelog' => $changelog
    ]);
    exit;
}

// Endpoint AJAX: Executar Atualização / Reparo
if (isset($_GET['action']) && $_GET['action'] === 'run_update') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acesso não autorizado. Faça login como administrador.']);
        exit;
    }

    if (!class_exists('ZipArchive')) {
        echo json_encode(['success' => false, 'error' => 'A extensão ZipArchive do PHP não está ativada no servidor. Ative-a no painel da sua hospedagem.']);
        exit;
    }

    if (!is_writable(__DIR__)) {
        echo json_encode(['success' => false, 'error' => 'O diretório raiz (' . __DIR__ . ') não possui permissão de escrita. Defina permissões 0755 ou 0777.']);
        exit;
    }

    $zip_url = 'https://github.com/leirson/phplayer/archive/refs/heads/main.zip';
    $temp_zip = sys_get_temp_dir() . '/phplayer_update_' . time() . '.zip';

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 45,
            'user_agent' => 'PHPlayer-Updater/' . $local_version
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $zip_content = @file_get_contents($zip_url, false, $ctx);
    if (!$zip_content && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zip_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $zip_content = curl_exec($ch);
        curl_close($ch);
    }

    if (!$zip_content || strlen($zip_content) < 1000) {
        echo json_encode(['success' => false, 'error' => 'Não foi possível baixar o pacote de atualização do GitHub. Verifique a conexão com a internet ou o limite de requisições.']);
        exit;
    }

    if (file_put_contents($temp_zip, $zip_content) === false) {
        echo json_encode(['success' => false, 'error' => 'Não foi possível salvar o arquivo compactado temporário em ' . $temp_zip]);
        exit;
    }

    $zip = new ZipArchive();
    if ($zip->open($temp_zip) === TRUE) {
        $temp_extract_dir = sys_get_temp_dir() . '/phplayer_extract_' . time() . '_' . mt_rand(1000, 9999);
        @mkdir($temp_extract_dir, 0777, true);
        $zip->extractTo($temp_extract_dir);
        $zip->close();

        $extracted_folders = glob($temp_extract_dir . '/*', GLOB_ONLYDIR);
        $source_dir = $temp_extract_dir;
        if (count($extracted_folders) === 1) {
            $source_dir = $extracted_folders[0];
        }

        // Função segura para copiar recursivamente preservando dados do usuário
        $copied_files = 0;
        $ignored_files = 0;

        $copy_recursive = function($src, $dst) use (&$copy_recursive, &$copied_files, &$ignored_files) {
            if (!file_exists($src)) return;
            $dir = opendir($src);
            if (!file_exists($dst)) {
                @mkdir($dst, 0755, true);
            }
            while (false !== ($file = readdir($dir))) {
                if ($file === '.' || $file === '..') continue;
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;

                // Proteger pastas e arquivos do usuário
                if ($file === 'config.php' && file_exists($dstFile)) {
                    $ignored_files++;
                    continue;
                }
                if (in_array($file, ['uploads', 'images', 'videos', 'movies', 'series', 'podcast', 'music', 'covers', 'videos_covers', '.git'])) {
                    if (file_exists($dstFile)) {
                        $ignored_files++;
                        continue;
                    }
                }

                if (is_dir($srcFile)) {
                    $copy_recursive($srcFile, $dstFile);
                } else {
                    if (@copy($srcFile, $dstFile)) {
                        $copied_files++;
                    }
                }
            }
            closedir($dir);
        };

        $copy_recursive($source_dir, __DIR__);

        // Limpeza dos arquivos temporários
        @unlink($temp_zip);
        $clean_temp = function($dir) use (&$clean_temp) {
            if (empty($dir) || !file_exists($dir)) return;
            if (!is_dir($dir)) { @unlink($dir); return; }
            $files = @scandir($dir);
            if ($files === false) return;
            foreach (array_diff($files, ['.', '..']) as $f) {
                $p = $dir . '/' . $f;
                is_dir($p) ? $clean_temp($p) : @unlink($p);
            }
            if (file_exists($dir) && is_dir($dir)) {
                @rmdir($dir);
            }
        };
        $clean_temp($temp_extract_dir);

        // Executar auto-migrações no banco de dados se conectado
        $db_migration_status = "Não aplicável";
        if ($pdo) {
            try {
                // Tabela shares
                $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                    share_hash VARCHAR(100) PRIMARY KEY,
                    target_type VARCHAR(50),
                    target_id VARCHAR(500),
                    target_name VARCHAR(255),
                    created_by VARCHAR(50) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `expires_at` DATETIME DEFAULT NULL"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE `shares` ADD COLUMN `target_name` VARCHAR(255) DEFAULT NULL"); } catch (Throwable $e) {}

                // Tabela settings
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(100) PRIMARY KEY,
                    setting_value TEXT DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Coluna theme em users
                try { $pdo->exec("ALTER TABLE users ADD COLUMN theme VARCHAR(50) DEFAULT 'default'"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE users ADD COLUMN sidebarBg VARCHAR(100) DEFAULT ''"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE users ADD COLUMN footerBg VARCHAR(100) DEFAULT ''"); } catch (Throwable $e) {}
                try { $pdo->exec("ALTER TABLE users ADD COLUMN topBg VARCHAR(100) DEFAULT ''"); } catch (Throwable $e) {}

                $db_migration_status = "Tabelas e colunas sincronizadas com sucesso!";
            } catch (Throwable $e) {
                $db_migration_status = "Aviso na migração: " . $e->getMessage();
            }
        }

        // Ler nova versão atualizada
        $new_version = $local_version;
        if (file_exists(__DIR__ . '/version.php')) {
            $v_content = file_get_contents(__DIR__ . '/version.php');
            if (preg_match("/define\\('PHPLAYER_VERSION',\\s*'([^']+)'\\)/", $v_content, $vm)) {
                $new_version = $vm[1];
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Sistema atualizado e reparado com sucesso!',
            'copied_files' => $copied_files,
            'old_version' => $local_version,
            'new_version' => $new_version,
            'db_status' => $db_migration_status
        ]);
    } else {
        @unlink($temp_zip);
        echo json_encode(['success' => false, 'error' => 'Falha ao descompactar arquivo de atualização (.zip).']);
    }
    exit;
}

// Obter diagnósticos do servidor
$php_version_ok = version_compare(PHP_VERSION, '7.4.0', '>=');
$zip_ok = class_exists('ZipArchive');
$curl_ok = function_exists('curl_init');
$dir_writable = is_writable(__DIR__);
$pdo_ok = extension_loaded('pdo_mysql');
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPlayer - Painel de Atualização e Reparo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfeff',
                            100: '#cffafe',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2',
                            900: '#164e63',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #030712;
            background-image: radial-gradient(circle at 50% 0%, rgba(6, 182, 212, 0.08) 0%, transparent 60%);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #0b0f19;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
    </style>
</head>
<body class="text-slate-200 min-h-screen flex flex-col justify-between selection:bg-cyan-500 selection:text-black">

    <!-- Header / Navbar -->
    <header class="border-b border-slate-800/80 bg-slate-950/70 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 text-white font-bold text-lg">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <h1 class="font-bold text-base tracking-tight text-white flex items-center gap-2">
                        PHPlayer <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-400 border border-cyan-800">Updater</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">Ferramenta de Atualização e Reparo do Sistema</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400 font-mono hidden sm:inline-block">v<?= htmlspecialchars($local_version) ?></span>
                <?php if ($is_admin): ?>
                    <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="font-semibold text-white"><?= htmlspecialchars($_SESSION['phplayer_admin_user'] ?? 'Admin') ?></span>
                    </div>
                    <a href="update.php?action=logout" class="text-xs text-rose-400 hover:text-rose-300 bg-rose-950/40 hover:bg-rose-950/80 border border-rose-900/60 px-3 py-1.5 rounded-lg transition font-medium">
                        Sair
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 flex-1">

        <?php if (!$is_admin): ?>
            <!-- Login Card for Administrator -->
            <div class="max-w-md mx-auto my-12">
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl shadow-black/60 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-cyan-500 to-blue-600"></div>

                    <div class="text-center mb-6">
                        <div class="inline-flex p-3 rounded-2xl bg-cyan-950/60 border border-cyan-800/60 text-cyan-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-white tracking-tight">Autenticação Administrativa</h2>
                        <p class="text-xs text-slate-400 mt-1">Faça login com sua conta de administrador para atualizar ou reparar o PHPlayer.</p>
                    </div>

                    <?php if (!empty($login_error)): ?>
                        <div class="mb-4 p-3.5 rounded-xl bg-rose-950/50 border border-rose-800/80 text-rose-300 text-xs flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= htmlspecialchars($login_error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="update.php" class="space-y-4">
                        <input type="hidden" name="login_action" value="1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Usuário Administrador</label>
                            <input type="text" name="username" required autofocus placeholder="admin" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-600 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Senha</label>
                            <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-600 outline-none transition">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-bold rounded-xl text-sm transition duration-200 shadow-lg shadow-cyan-500/25 flex items-center justify-center gap-2 cursor-pointer mt-2">
                            <span>Acessar Painel de Atualização</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </form>

                    <div class="mt-6 pt-5 border-t border-slate-800 flex justify-between items-center text-xs text-slate-400">
                        <a href="index.php" class="hover:text-cyan-400 transition flex items-center gap-1">
                            &larr; Voltar ao Web Player
                        </a>
                        <a href="debug.php" class="hover:text-cyan-400 transition">
                            Abrir Diagnóstico
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Admin Dashboard Panel -->
            <div class="space-y-6">

                <!-- Status Banner & Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Version Card -->
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Versão Instalada</span>
                                <div class="text-2xl font-bold font-mono text-white mt-1">v<span id="label-local-ver"><?= htmlspecialchars($local_version) ?></span></div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-800/80 text-cyan-400 border border-slate-700/60">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-slate-400">Repositório:</span>
                            <span class="text-cyan-400 font-mono" id="label-remote-ver">Verificando...</span>
                        </div>
                    </div>

                    <!-- Environment Health Card -->
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Ambiente PHP</span>
                                <div class="text-xl font-bold text-white mt-1">PHP <?= PHP_VERSION ?></div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-800/80 <?= ($php_version_ok && $zip_ok && $dir_writable) ? 'text-emerald-400' : 'text-amber-400' ?> border border-slate-700/60">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-slate-400">ZipArchive / Escrita:</span>
                            <span class="<?= ($zip_ok && $dir_writable) ? 'text-emerald-400' : 'text-rose-400' ?> font-semibold">
                                <?= ($zip_ok && $dir_writable) ? 'Pronto para Atualizar' : 'Permissão Restrita' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Database Status Card -->
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Banco de Dados</span>
                                <div class="text-lg font-bold <?= $db_connected ? 'text-emerald-400' : 'text-rose-400' ?> mt-1">
                                    <?= $db_connected ? 'Conectado (MySQL)' : 'Desconectado' ?>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-800/80 <?= $db_connected ? 'text-emerald-400' : 'text-rose-400' ?> border border-slate-700/60">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-slate-400">Base:</span>
                            <span class="text-slate-300 font-mono truncate max-w-[150px]"><?= defined('DB_NAME') ? htmlspecialchars(DB_NAME) : 'Não config.' ?></span>
                        </div>
                    </div>
                </div>

                <!-- Update Action Box -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                                <span>Centro de Atualizações e Reparo de Sistema</span>
                                <span id="badge-update-status" class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700">Verificando...</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-1">Baixa a versão estável mais recente do GitHub oficial, atualiza os scripts e preserva automaticamente seu arquivo <code class="text-cyan-300 bg-slate-950 px-1 py-0.5 rounded">config.php</code> e suas pastas de mídia.</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button id="btn-check-update" onclick="checkUpdate()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-semibold transition border border-slate-700 flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Verificar Agora</span>
                            </button>

                            <button id="btn-run-update" onclick="runUpdate()" class="px-5 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-bold rounded-xl text-xs transition duration-200 shadow-lg shadow-cyan-500/20 flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span id="label-update-btn">Atualizar / Reparar Sistema</span>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar during Update -->
                    <div id="progress-container" class="hidden my-4">
                        <div class="flex justify-between items-center text-xs font-semibold mb-1.5">
                            <span id="progress-status-text" class="text-cyan-400">Processando atualização...</span>
                            <span id="progress-percent" class="text-slate-400">0%</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-800">
                            <div id="progress-bar" class="bg-gradient-to-r from-cyan-500 to-blue-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Log Output Terminal -->
                    <div class="mt-5">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                                Terminal de Execução
                            </span>
                            <button onclick="clearLogs()" class="text-[11px] text-slate-400 hover:text-slate-200">Limpar Log</button>
                        </div>
                        <div id="terminal-logs" class="bg-slate-950 border border-slate-800 rounded-xl p-4 font-mono text-[11px] leading-relaxed text-slate-300 h-52 overflow-y-auto custom-scrollbar space-y-1">
                            <div class="text-slate-400">[<?= date('H:i:s') ?>] Pronto. Painel de Atualização iniciado para <?= htmlspecialchars($_SESSION['phplayer_admin_user'] ?? 'Admin') ?>.</div>
                        </div>
                    </div>

                    <!-- Changelog Section -->
                    <div class="mt-5 pt-5 border-t border-slate-800">
                        <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Notas de Lançamento / Changelog Remoto
                        </h3>
                        <div id="changelog-box" class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 text-xs font-mono text-slate-300 max-h-40 overflow-y-auto custom-scrollbar whitespace-pre-wrap">
Carregando histórico de alterações do GitHub...
                        </div>
                    </div>
                </div>

                <!-- Server Diagnostics Table -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-sm font-bold text-white tracking-tight mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                        Diagnósticos Técnicos do Servidor
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-slate-950/80 border border-slate-800/80 rounded-xl flex items-center justify-between">
                            <span class="text-slate-400">Versão PHP:</span>
                            <span class="<?= $php_version_ok ? 'text-emerald-400' : 'text-rose-400' ?> font-semibold"><?= PHP_VERSION ?> <?= $php_version_ok ? '(OK)' : '(Mínimo 7.4)' ?></span>
                        </div>

                        <div class="p-3 bg-slate-950/80 border border-slate-800/80 rounded-xl flex items-center justify-between">
                            <span class="text-slate-400">Extensão ZipArchive:</span>
                            <span class="<?= $zip_ok ? 'text-emerald-400' : 'text-rose-400' ?> font-semibold"><?= $zip_ok ? 'Ativa (OK)' : 'Inativa (Necessária)' ?></span>
                        </div>

                        <div class="p-3 bg-slate-950/80 border border-slate-800/80 rounded-xl flex items-center justify-between">
                            <span class="text-slate-400">Permissão de Escrita:</span>
                            <span class="<?= $dir_writable ? 'text-emerald-400' : 'text-rose-400' ?> font-semibold"><?= $dir_writable ? 'Permitida (0755/0777)' : 'Somente Leitura' ?></span>
                        </div>

                        <div class="p-3 bg-slate-950/80 border border-slate-800/80 rounded-xl flex items-center justify-between">
                            <span class="text-slate-400">Driver PDO MySQL:</span>
                            <span class="<?= $pdo_ok ? 'text-emerald-400' : 'text-rose-400' ?> font-semibold"><?= $pdo_ok ? 'Disponível' : 'Indisponível' ?></span>
                        </div>

                        <div class="p-3 bg-slate-950/80 border border-slate-800/80 rounded-xl flex items-center justify-between">
                            <span class="text-slate-400">Módulo cURL:</span>
                            <span class="<?= $curl_ok ? 'text-emerald-400' : 'text-amber-400' ?> font-semibold"><?= $curl_ok ? 'Disponível' : 'Indisponível (usando streams)' ?></span>
                        </div>

                        <div class="p-3 bg-slate-950/80 border border-slate-800/80 rounded-xl flex items-center justify-between">
                            <span class="text-slate-400">Memória / Limite de Tempo:</span>
                            <span class="text-slate-300 font-mono"><?= ini_get('memory_limit') ?> / <?= ini_get('max_execution_time') ?>s</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Quick Navigation -->
                <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                    <div class="flex items-center gap-3">
                        <a href="index.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 border border-slate-700">
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Abrir PHPlayer Web
                        </a>
                        <a href="mobile.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 border border-slate-700">
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Abrir PHPlayer Mobile
                        </a>
                    </div>

                    <a href="debug.php" class="text-xs text-slate-400 hover:text-cyan-400 transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        Abrir Ferramenta de Diagnóstico Completo (debug.php)
                    </a>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950/60 py-5 text-center text-xs text-slate-400">
        <div class="max-w-5xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2">
            <p>PHPlayer - Player de Mídia & Streaming Autônomo</p>
            <p class="font-mono text-slate-400">Versão do Sistema: v<?= htmlspecialchars($local_version) ?></p>
        </div>
    </footer>

    <!-- Client-side Scripts -->
    <script>
        function log(msg, type = 'info') {
            const term = document.getElementById('terminal-logs');
            if (!term) return;
            const time = new Date().toLocaleTimeString();
            const div = document.createElement('div');
            let colorClass = 'text-slate-300';
            let prefix = '[INFO]';
            if (type === 'success') { colorClass = 'text-emerald-400 font-semibold'; prefix = '[SUCESSO]'; }
            if (type === 'error') { colorClass = 'text-rose-400 font-semibold'; prefix = '[ERRO]'; }
            if (type === 'warn') { colorClass = 'text-amber-400 font-semibold'; prefix = '[AVISO]'; }
            div.className = colorClass;
            div.textContent = `[${time}] ${prefix} ${msg}`;
            term.appendChild(div);
            term.scrollTop = term.scrollHeight;
        }

        function clearLogs() {
            const term = document.getElementById('terminal-logs');
            if (term) term.innerHTML = '';
        }

        async function checkUpdate() {
            const btn = document.getElementById('btn-check-update');
            const badge = document.getElementById('badge-update-status');
            const remoteLabel = document.getElementById('label-remote-ver');
            const changelogBox = document.getElementById('changelog-box');

            if (btn) btn.disabled = true;
            if (badge) {
                badge.className = 'text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-cyan-950 text-cyan-400 border border-cyan-800 animate-pulse';
                badge.textContent = 'Verificando GitHub...';
            }
            log('Consultando repositório oficial no GitHub por novas versões...');

            try {
                const res = await fetch('update.php?action=check_version');
                const data = await res.json();

                if (data.success) {
                    if (remoteLabel) remoteLabel.textContent = 'v' + data.remote_version;
                    if (changelogBox) changelogBox.textContent = data.changelog || 'Nenhum changelog disponível.';

                    if (data.has_update) {
                        log(`Nova versão disponível: v${data.remote_version} (Instalada: v${data.local_version})`, 'warn');
                        if (badge) {
                            badge.className = 'text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-950 text-amber-400 border border-amber-800';
                            badge.textContent = `Nova Versão Disponível: v${data.remote_version}`;
                        }
                    } else {
                        log(`Seu sistema já está na versão mais recente (v${data.local_version}).`, 'success');
                        if (badge) {
                            badge.className = 'text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800';
                            badge.textContent = 'Sistema Atualizado';
                        }
                    }
                } else {
                    log('Não foi possível verificar a versão remota: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (err) {
                log('Falha na requisição de checagem: ' + err.message, 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function runUpdate() {
            if (!confirm('Deseja iniciar o processo de atualização e reparo agora?\nSeu arquivo config.php e suas pastas de mídia serão preservados.')) {
                return;
            }

            const btn = document.getElementById('btn-run-update');
            const progressCont = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressStatus = document.getElementById('progress-status-text');
            const progressPercent = document.getElementById('progress-percent');

            if (btn) btn.disabled = true;
            if (progressCont) progressCont.classList.remove('hidden');

            const setProgress = (p, text) => {
                if (progressBar) progressBar.style.width = p + '%';
                if (progressPercent) progressPercent.textContent = p + '%';
                if (progressStatus) progressStatus.textContent = text;
            };

            setProgress(15, 'Conectando ao GitHub e baixando pacote compactado...');
            log('Iniciando download do pacote mais recente do repositório principal...');

            try {
                setProgress(35, 'Baixando e descompactando arquivos...');
                const res = await fetch('update.php?action=run_update');
                const data = await res.json();

                if (data.success) {
                    setProgress(100, 'Atualização concluída com sucesso!');
                    log(`Sucesso: ${data.message}`, 'success');
                    log(`Total de arquivos de código atualizados: ${data.copied_files}`, 'info');
                    log(`Migrações de banco: ${data.db_status}`, 'info');
                    log(`Versão final do sistema: v${data.new_version}`, 'success');

                    const localVerEl = document.getElementById('label-local-ver');
                    if (localVerEl) localVerEl.textContent = data.new_version;

                    alert('Parabéns! O PHPlayer foi atualizado e reparado com sucesso.\nVersão: v' + data.new_version);
                } else {
                    setProgress(0, 'Erro na atualização');
                    log('Falha ao atualizar: ' + (data.error || 'Erro desconhecido'), 'error');
                    alert('Erro na atualização: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (err) {
                setProgress(0, 'Erro de conexão');
                log('Erro inesperado: ' + err.message, 'error');
                alert('Erro durante a execução: ' + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        // Auto-executar checagem inicial se estiver logado
        <?php if ($is_admin): ?>
            document.addEventListener('DOMContentLoaded', () => {
                checkUpdate();
            });
        <?php endif; ?>
    </script>
</body>
</html>
