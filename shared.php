<?php
require_once 'config.php';

$hash = $_GET['share'] ?? null;
if (!$hash) { die('Hash invalido'); }

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

    $stmt = $pdo->prepare("SELECT * FROM shares WHERE share_hash = ?");
    $stmt->execute([$hash]);
    $share = $stmt->fetch();
    if (!$share) die("Compartilhamento não existente.");
    if ($share['expires_at'] && strtotime($share['expires_at']) < time()) {
        die("Compartilhamento expirado.");
    }
    
    $tracks = [];
    $art = '';
    $alb = $share['target_name']; 
    
    // Polyfill start
    function get_songs_tables2($pdo) {
        $stmt = $pdo->query("SHOW TABLES LIKE 'songs_%'");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) { $tables[] = $row[0]; }
        return array_merge(["songs"], $tables);
    }

    if ($share['target_type'] === 'album') {
        $data = json_decode($share['target_id'], true);
        $art = $data['artist'] ?? '';
        $albName = $data['album'] ?? '';
        $alb = $albName;
        
        $tables = get_songs_tables2($pdo);
        foreach ($tables as $t) {
            $stmt1 = $pdo->prepare("SELECT * FROM `" . $t . "` WHERE artist = ? AND album = ? ORDER BY title ASC");
            $stmt1->execute([$art, $albName]);
            while ($r = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                $tracks[] = $r;
            }
        }
    } else if ($share['target_type'] === 'playlist') {
        // target_id contains the playlist ID
        $plId = intval($share['target_id']);
        $stmtPl = $pdo->prepare("SELECT * FROM playlists WHERE id = ?");
        $stmtPl->execute([$plId]);
        $playlistInfo = $stmtPl->fetch();
        if ($playlistInfo) {
            $art = "Criador: " . $playlistInfo['username'];
            
            // fetch songs from playlist_songs
            $stmtPs = $pdo->prepare("SELECT * FROM playlist_songs WHERE playlist_id = ? ORDER BY position ASC");
            $stmtPs->execute([$plId]);
            $associations = $stmtPs->fetchAll();
            
            foreach ($associations as $assoc) {
                // assume songs table for playlist for now, since it originally maps to it
                $sId = $assoc['song_id'];
                $sStmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
                $sStmt->execute([$sId]);
                $songInfo = $sStmt->fetch();
                if ($songInfo) {
                    $tracks[] = $songInfo;
                }
            }
        }
    }
} catch (Exception $e) {
    die('Erro de banco de dados: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPlayer - <?php echo htmlspecialchars($share['target_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-950 text-slate-200 antialiased font-sans flex flex-col items-center p-4 md:p-10 min-h-screen">
    
    <div class="max-w-xl w-full bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-8 pb-6 border-b border-slate-800 flex flex-col items-center text-center bg-gradient-to-b from-sky-900/40 to-transparent">
            <div class="w-32 h-32 rounded-xl shadow-lg bg-slate-800 border border-slate-700/50 flex items-center justify-center overflow-hidden mb-5">
                <?php if (count($tracks) > 0 && !empty($tracks[0]['cover_url'])): ?>
                    <img src="<?php echo htmlspecialchars(preg_match('/^http/', $tracks[0]['cover_url']) ? $tracks[0]['cover_url'] : 'music/' . $tracks[0]['cover_url']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <i data-lucide="<?php echo $share['target_type'] === 'playlist' ? 'list-music' : 'disc-3' ?>" class="w-12 h-12 text-slate-600"></i>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1"><?php echo htmlspecialchars($alb); ?></h1>
            <p class="text-sky-400 font-medium tracking-wide uppercase text-sm mb-4"><?php echo htmlspecialchars($art); ?></p>
        </div>

        <div class="p-6">
            <audio id="player" controls class="w-full mb-6 max-h-12 bg-slate-950 rounded-xl" style="color-scheme: dark;"></audio>
            
            <div class="space-y-1">
                <?php if(empty($tracks)): ?>
                    <div class="text-center text-slate-500 text-sm py-4">Nenhuma música encontrada neste compartilhamento.</div>
                <?php endif; ?>
                <?php foreach ($tracks as $i => $t): ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-800 transition cursor-pointer track-row" data-url="music/<?php echo addslashes($t['file_name']); ?>">
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
        
        document.querySelectorAll('.track-row').forEach(row => {
            row.addEventListener('click', function() {
                playTrack(this.dataset.url, this);
            });
        });

        function playTrack(url, el) {
            player.src = url;
            player.play();
            if (currentItem) currentItem.classList.remove('bg-sky-900/40', 'border-sky-500/50', 'border');
            el.classList.add('bg-sky-900/40', 'border-sky-500/50', 'border');
            currentItem = el;
        }
        
        // Autoplay logic or select first track
        <?php if(count($tracks) > 0): ?>
            const firstTrack = document.querySelector('.track-row');
            if (firstTrack) {
                player.src = firstTrack.dataset.url;
                firstTrack.classList.add('bg-sky-900/40', 'border-sky-500/50', 'border');
                currentItem = firstTrack;
            }
        <?php endif; ?>
        
        player.addEventListener('ended', () => {
            if (currentItem && currentItem.nextElementSibling && currentItem.nextElementSibling.classList.contains('track-row')) {
                currentItem.nextElementSibling.click();
            }
        });
    </script>
</body>
</html>
