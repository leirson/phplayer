<?php
require_once 'config.php';

function get_songs_tables1($pdo) {
    $stmt = $pdo->query("SHOW TABLES LIKE 'songs_%'");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) { $tables[] = $row[0]; }
    return array_merge(["songs"], $tables);
}

$hash = $_GET['share'] ?? null;
if (!$hash) { die('Hash invalido'); }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS shares (
                    share_hash VARCHAR(100) PRIMARY KEY,
                    target_type VARCHAR(50),
                    target_id VARCHAR(500),
                    target_name VARCHAR(255),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare("SELECT * FROM shares WHERE share_hash = ?");
    $stmt->execute([$hash]);
    $share = $stmt->fetch();
    if (!$share) die("Compartilhamento expirado ou não existente.");
    
    $tracks = [];
    $art = '';
    $alb = '';
    if ($share['target_type'] === 'album') {
        $data = json_decode($share['target_id'], true);
        $art = $data['artist'] ?? '';
        $alb = $data['album'] ?? '';
        
        $tables = get_songs_tables1($pdo);
        foreach ($tables as $t) {
            $stmt1 = $pdo->prepare("SELECT * FROM `" . $t . "` WHERE artist = ? AND album = ? ORDER BY title ASC");
            $stmt1->execute([$art, $alb]);
            while ($r = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                $tracks[] = $r;
            }
        }
    }
} catch (Exception $e) {
    die('Erro de banco de dados.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ouça <?php echo htmlspecialchars($share['target_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-950 text-slate-200 antialiased font-sans flex flex-col items-center p-4 md:p-10 min-h-screen">
    
    <div class="max-w-xl w-full bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-8 pb-6 border-b border-slate-800 flex flex-col items-center text-center bg-gradient-to-b from-indigo-900/40 to-transparent">
            <div class="w-32 h-32 rounded-xl shadow-lg bg-slate-800 border border-slate-700/50 flex items-center justify-center overflow-hidden mb-5">
                <?php if (count($tracks) > 0 && !empty($tracks[0]['cover_url'])): ?>
                    <img src="<?php echo htmlspecialchars(preg_match('/^http/', $tracks[0]['over_url']) ? $tracks[0]['cover_url'] : 'music/' . $tracks[0]['cover_url']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <i data-lucide="disc-3" class="w-12 h-12 text-slate-600"></i>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1"><?php echo htmlspecialchars($alb); ?></h1>
            <p class="text-indigo-400 font-medium tracking-wide uppercase text-sm mb-4"><?php echo htmlspecialchars($art); ?></p>
        </div>

        <div class="p-6">
            <audio id="player" controls class="w-full mb-6 max-h-12"></audio>
            
            <div class="space-y-1">
                <?php foreach ($tracks as $i => $t): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-800 transition cursor-pointer" onclick="playTrack('music/<?php echo addslashes($t['file_name']); ?>', this)">
                        <div class="w-6 text-center text-xs text-slate-500 font-mono"><?php echo ($i+1); ?></div>
                        <div class="flex-1 text-sm font-medium"><?php echo htmlspecialchars($t['title']); ?></div>
                        <div class="text-xs text-slate-500 font-mono"><?php echo gmdate("i:s", $t['duration'] ?? 0); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const player = document.getElementById('player');
        let currentItem = null;
        function playTrack(url, el) {
            player.src = url;
            player.play();
            if (currentItem) currentItem.classList.remove('bg-indigo-900/40', 'border-indigo-500/50');
            el.classList.add('bg-indigo-900/40', 'border-indigo-500/50');
            currentItem = el;
        }
        <?php if(count($tracks) > 0): ?>
            player.src = 'music/<?php echo addslashes($tracks[0]['file_name']); ?>';
        <?php endif; ?>
    </script>
</body>
</html>