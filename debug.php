<?php
/**
 * Script de Diagnóstico e Validação do Banco de Dados / Hostinger
 */
header("Content-Type: text/html; charset=utf-8");
@error_reporting(E_ALL);
@ini_set('display_errors', 1);

echo "<html><head><title>Diagnóstico de Banco de Dados</title>";
echo "<style>
    body { font-family: 'Inter', system-ui, sans-serif; background: #050811; color: #cbd5e1; padding: 40px; }
    h1 { color: #f8fafc; font-weight: 900; border-bottom: 1px solid #1e293b; padding-bottom: 15px; margin-bottom: 25px; }
    .card { background: #0b0f19; border: 1px solid #1e293b; padding: 25px; border-radius: 16px; margin-bottom: 20px; }
    .success { color: #4ade80; font-weight: bold; }
    .error { color: #f87171; font-weight: bold; background: #991b1b10; border: 1px solid #ef444420; padding: 15px; border-radius: 12px; margin-top: 10px; font-family: monospace; }
    .warning { color: #facc15; font-weight: bold; }
    ul { padding-left: 20px; }
    li { margin-bottom: 10px; }
    code { font-family: monospace; background: #020617; padding: 2px 6px; border-radius: 4px; color: #38bdf8; }
</style></head><body>";

echo "<h1>Diagnosticador de Sistema - PHPlayer</h1>";

echo "<div class='card'>";
echo "<h3>1. Verificação do Ambiente PHP</h3>";
echo "<ul>";
echo "<li>Versão do PHP: <strong>" . PHP_VERSION . "</strong> " . (version_compare(PHP_VERSION, '7.0.0', '>=') ? "<span class='success'>[OK]</span>" : "<span class='error'>[Desatualizada! Requer PHP >= 7.0]</span>") . "</li>";
echo "<li>Extensão PDO_MYSQL: " . (extension_loaded('pdo_mysql') ? "<span class='success'>[Ativa]</span>" : "<span class='error'>[Inativa ou Não Instalada! Active pdo_mysql no Hostinger]</span>") . "</li>";
echo "<li>Pasta de Músicas (music/): ";
$musicDir = __DIR__ . '/music/';
if (file_exists($musicDir)) {
    echo "Existe " . (is_writable($musicDir) ? "<span class='success'>[E é Gravável (0755/0777)]</span>" : "<span class='warning'>[Mas NÃO é Gravável! Defina permissões para 0755 ou 0777]</span>");
} else {
    echo "<span class='warning'>Não existe (será criada automaticamente na primeira varredura ou importação)</span>";
}
echo "</li>";

echo "<li>Pasta de Vídeos (videos/): ";
$videosDir = __DIR__ . '/videos/';
if (file_exists($videosDir)) {
    echo "Existe " . (is_writable($videosDir) ? "<span class='success'>[E é Gravável (0755/0777)]</span>" : "<span class='warning'>[Mas NÃO é Gravável! Defina permissões para 0755 ou 0777]</span>");
} else {
    echo "<span class='warning'>Não existe (será criada automaticamente ao escanear vídeos)</span>";
}
echo "</li>";

echo "<li>Pasta Geral de Uploads (uploads/): ";
$uploadsDir = __DIR__ . '/uploads/';
if (file_exists($uploadsDir)) {
    echo "Existe " . (is_writable($uploadsDir) ? "<span class='success'>[E é Gravável (0755/0777)]</span>" : "<span class='warning'>[Mas NÃO é Gravável! Defina permissões para 0755 ou 0777]</span>");
} else {
    echo "<span class='warning'>Não existe (tentará ser criada)</span>";
}
echo "</li>";

echo "<li>Subpasta de Capas de Álbuns (uploads/covers/): ";
$coversDir = __DIR__ . '/uploads/covers/';
if (file_exists($coversDir)) {
    echo "Existe " . (is_writable($coversDir) ? "<span class='success'>[E é Gravável]</span>" : "<span class='warning'>[Mas NÃO é Gravável!]</span>");
} else {
    echo "<span class='warning'>Não existe (será criada automaticamente ao alterar capas)</span>";
}
echo "</li>";

echo "<li>Subpasta de Capas de Vídeo (uploads/videos_covers/): ";
$vCoversDir = __DIR__ . '/uploads/videos_covers/';
if (file_exists($vCoversDir)) {
    echo "Existe " . (is_writable($vCoversDir) ? "<span class='success'>[E é Gravável]</span>" : "<span class='warning'>[Mas NÃO é Gravável!]</span>");
} else {
    echo "<span class='warning'>Não existe (será criada automaticamente ao escanear/carregar capas de vídeos)</span>";
}
echo "</li>";

echo "<li>Subpasta de Podcasts (uploads/podcast/): ";
$podcastDir = __DIR__ . '/uploads/podcast/';
if (file_exists($podcastDir)) {
    echo "Existe " . (is_writable($podcastDir) ? "<span class='success'>[E é Gravável]</span>" : "<span class='warning'>[Mas NÃO é Gravável!]</span>");
} else {
    echo "<span class='warning'>Não existe (será criada automaticamente na primeira sincronização de Podcast)</span>";
}
echo "</li>";
echo "</ul>";
echo "</div>";

echo "<div class='card'>";
echo "<h3>2. Tentativa de Conexão com o Arquivo config.php</h3>";
if (!file_exists('config.php')) {
    echo "<div class='error'>Erro: Arquivo 'config.php' não foi encontrado!</div>";
} else {
    if (!defined('DONT_EXIT_ON_DB_ERROR')) {
        define('DONT_EXIT_ON_DB_ERROR', true);
    }
    include 'config.php';
    echo "<p class='success'>✓ Arquivo config.php importado com sucesso!</p>";
    echo "<ul>";
    echo "<li>Host: <code>" . DB_HOST . "</code></li>";
    echo "<li>Banco: <code>" . DB_NAME . "</code></li>";
    echo "<li>Usuário: <code>" . DB_USER . "</code></li>";
    echo "</ul>";
    
    // Testar conexão
    try {
        $test_pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "<p class='success'>✓ Conexão com o banco de dados MySQL realizada com absoluto SUCESSO!</p>";
        
        echo "<h3>3. Verificação de Tabelas (Importação database.sql)</h3>";
        $tabelasRequeridas = ['users', 'songs', 'playlists', 'playlist_songs', 'favorites', 'videos', 'settings', 'artist_metadata', 'radios'];
        $tabelasFaltando = [];
        
        foreach ($tabelasRequeridas as $tab) {
            try {
                $test_pdo->query("SELECT 1 FROM $tab LIMIT 1");
                echo "<li>Tabela <code>{$tab}</code>: <span class='success'>[OK - Existente]</span></li>";
            } catch (Exception $e) {
                echo "<li>Tabela <code>{$tab}</code>: <span class='error'>[AUSENTE]</span></li>";
                $tabelasFaltando[] = $tab;
            }
        }
        
        if (!empty($tabelasFaltando)) {
            echo "<div class='error'>ALERTA DE SEGURANÇA: As tabelas (" . implode(', ', $tabelasFaltando) . ") ainda não existem no banco de dados! <br><br><strong>Solução:</strong> Acesse seu phpMyAdmin na Hostinger e execute o arquivo 'database.sql' na aba Importar.</div>";
        } else {
            $stmt = $test_pdo->query("SELECT COUNT(*) FROM users");
            $countUsers = $stmt->fetchColumn();
            echo "<li>Contagem de Usuários Registrados: <strong>{$countUsers}</strong></li>";
            if ($countUsers == 0) {
                echo "<div class='warning'>Aviso: A tabela 'users' está cadastrada, mas não possui nenhum usuário! Insira o comando SQL de INSERT contido no database.sql para criar o usuário 'admin' padrão.</div>";
            } else {
                echo "<p class='success'>✓ Usuários ativos localizados! O login 'admin' com senha 'admin' deve funcionar perfeitamente.</p>";
            }
        }

        // --- INICIO DE TESTES DE CONSULTAS SQL DIRETAS ---
        echo "<h3>4. Diagnóstico Direto de Consultas SQL (Simulando api.php)</h3>";
        echo "<ul>";
        
        // Helper interno de debug
        if (!function_exists('debug_get_songs_tables')) {
            function debug_get_songs_tables($pdo) {
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
                    return $tables;
                } catch (Exception $e) {
                    return ['songs'];
                }
            }
        }

        // 4.1 Teste de Músicas (Tracks)
        echo "<li><strong>Teste de Músicas (Rota 'tracks'):</strong><ul>";
        try {
            $tables = debug_get_songs_tables($test_pdo);
            echo "<li>Tabelas de músicas no DB: <code>" . implode(', ', $tables) . "</code></li>";
            
            $query = null;
            if (count($tables) === 1) {
                $t = $tables[0];
                $sql = "SELECT * FROM " . $t . " ORDER BY created_at DESC";
                echo "<li>SQL executado: <code>" . htmlspecialchars($sql) . "</code></li>";
                $query = $test_pdo->query($sql);
            } else {
                $parts = [];
                foreach ($tables as $t) {
                    $parts[] = "SELECT * FROM " . $t;
                }
                $sql = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS union_songs ORDER BY created_at DESC";
                echo "<li>SQL executado: <code>" . htmlspecialchars($sql) . "</code></li>";
                $query = $test_pdo->query($sql);
            }
            
            if ($query) {
                $rows = $query->fetchAll(PDO::FETCH_ASSOC);
                echo "<li>Linhas retornadas: <span class='success'>" . count($rows) . " músicas</span></li>";
                if (count($rows) > 0) {
                    echo "<li>Primeiro registro (amostra): <pre style='background: #020617; padding: 10px; color: #a5f3fc; font-size:11px;'>" . htmlspecialchars(json_encode(array_slice($rows[0], 0, 5), JSON_UNESCAPED_UNICODE)) . "</pre></li>";
                }
            } else {
                echo "<li><span class='error'>A query retornou um valor nulo ou falso!</span></li>";
            }
        } catch (Throwable $e) {
            echo "<li><span class='error'>FALHA NA EXECUÇÃO: " . htmlspecialchars($e->getMessage()) . "</span></li>";
        }
        echo "</ul></li><br>";

        // 4.2 Teste de Playlists
        echo "<li><strong>Teste de Playlists (Rota 'playlists'):</strong><ul>";
        try {
            $stmt = $test_pdo->query("SELECT * FROM playlists ORDER BY name ASC");
            if ($stmt) {
                $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<li>Total de Playlists: <span class='success'>" . count($playlists) . " playlists</span></li>";
            } else {
                echo "<li><span class='error'>Query de playlists inválida</span></li>";
            }
        } catch (Throwable $e) {
            echo "<li><span class='error'>FALHA: " . htmlspecialchars($e->getMessage()) . "</span></li>";
        }
        echo "</ul></li><br>";

        // 4.3 Teste de Favoritos
        echo "<li><strong>Teste de Favoritos (Rota 'favorites'):</strong><ul>";
        try {
            $stmt = $test_pdo->query("SELECT * FROM favorites");
            if ($stmt) {
                $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<li>Total de Favoritos salvos: <span class='success'>" . count($favorites) . " favoritos</span></li>";
            } else {
                echo "<li><span class='error'>Query de favoritos inválida</span></li>";
            }
        } catch (Throwable $e) {
            echo "<li><span class='error'>FALHA: " . htmlspecialchars($e->getMessage()) . "</span></li>";
        }
        echo "</ul></li>";
        
        echo "</ul>";
        // --- FIM DE TESTES DE CONSULTAS SQL DIRETAS ---
        
    } catch (PDOException $e) {
        echo "<div class='error'>FALHA NA CONEXÃO MYSQL:<br><br>Mensagem: " . $e->getMessage() . "<br><br><strong>Possíveis Causas se estiver na Hostinger:</strong><br>
        1. Você informou credenciais incorretas no config.php. Verifique se o nome do banco de dados e do usuário começam com o prefixo da sua conta (ex: u123196074_...).<br>
        2. A senha fornecida está incorreta.<br>
        3. O host 'localhost' pode precisar ser alterado para o endereço IP do MySQL fornecido pelo painel Hostinger.<br>
        4. O usuário criado não possui permissão de leitura/escrita no banco.</div>";
    }
}
echo "</div>";

echo "<div class='card'>";
echo "<h3>5. Registro de Erros do Servidor (api_errors.log)</h3>";
if (file_exists('api_errors.log')) {
    echo "<p class='warning'>Histórico de erros detectados recentemente:</p>";
    echo "<pre style='background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 8px; color: #ef4444; max-height: 250px; overflow-y: auto; font-size: 11px;'>" . htmlspecialchars(file_get_contents('api_errors.log')) . "</pre>";
    echo "<p><a href='?clear_logs=1' style='color: #ef4444; text-decoration: none; font-weight: bold;'>[Limpar Histórico de Logs]</a></p>";
} else {
    echo "<p class='success'>Nenhum erro registrado no arquivo api_errors.log. Isso é excelente!</p>";
}
if (isset($_GET['clear_logs'])) {
    @unlink('api_errors.log');
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
echo "</div>";

?>
<div class='card'>
<h3>6. Teste Interativo de Endpoints da API</h3>
<p>Verifique o comportamento em tempo real das rotas da API em <code>api.php</code>:</p>

<div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px;">
        <label style="display: block; font-size: 11px; color: #94a3b8; margin-bottom: 5px;">Selecione o Endpoint:</label>
        <select id="api-endpoint" onchange="onEndpointChange()" style="width: 100%; background: #020617; border: 1px solid #1e293b; color: white; padding: 8px; border-radius: 6px;">
            <option value="api.php?route=tracks">GET api.php?route=tracks (Músicas)</option>
            <option value="api.php?route=playlists">GET api.php?route=playlists (Playlists)</option>
            <option value="api.php?route=favorites">GET api.php?route=favorites (Favoritos)</option>
            <option value="api.php?route=genres">GET api.php?route=genres (Gêneros)</option>
            <option value="api.php?route=settings">GET api.php?route=settings (Configurações)</option>
            <option value="api.php?route=login">POST api.php?route=login (Autenticação)</option>
        </select>
    </div>
    <div style="width: 100px;">
        <label style="display: block; font-size: 11px; color: #94a3b8; margin-bottom: 5px;">Método:</label>
        <input type="text" id="api-method" value="GET" readonly style="width: 100%; background: #1e293b; border: 1px solid #1e293b; color: #cbd5e1; padding: 8px; border-radius: 6px; text-align: center;" />
    </div>
</div>

<div id="api-body-container" style="display: none; margin-bottom: 15px;">
    <label style="display: block; font-size: 11px; color: #94a3b8; margin-bottom: 5px;">Corpo da Requisição (JSON):</label>
    <textarea id="api-body" style="width: 100%; height: 80px; background: #020617; border: 1px solid #1e293b; color: #38bdf8; font-family: monospace; padding: 10px; border-radius: 6px; font-size: 12px; resize: vertical;">{
  "username": "admin",
  "password": "wrong_password_test"
}</textarea>
</div>

<button id='test-btn' onclick='executeInteractiveTest()' style='background: #e11d48; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;'>Executar Requisição de Teste PHP</button>

<div id='api-test-result' style='margin-top: 15px; padding: 15px; background: #020617; border: 1px solid #1e293b; border-radius: 8px; font-family: monospace; font-size: 12px; display: none; line-height: 1.6;'></div>

<script>
function onEndpointChange() {
    const endpoint = document.getElementById("api-endpoint").value;
    const methodField = document.getElementById("api-method");
    const labelContainer = document.getElementById("api-body-container");
    
    if (endpoint.includes("route=login")) {
        methodField.value = "POST";
        labelContainer.style.display = "block";
    } else {
        methodField.value = "GET";
        labelContainer.style.display = "none";
    }
}

async function executeInteractiveTest() {
    const resBox = document.getElementById("api-test-result");
    const btn = document.getElementById("test-btn");
    const url = document.getElementById("api-endpoint").value;
    const method = document.getElementById("api-method").value;
    
    resBox.style.display = "block";
    resBox.innerHTML = "<em>Enviando requisição " + method + " para " + url + "...</em>";
    btn.disabled = true;
    btn.style.opacity = "0.6";
    
    try {
        const options = {
            method: method,
            headers: {}
        };
        
        if (method === "POST") {
            options.headers["Content-Type"] = "application/json";
            options.body = document.getElementById("api-body").value;
        }
        
        const startTime = performance.now();
        const res = await fetch(url, options);
        const duration = (performance.now() - startTime).toFixed(1);
        const text = await res.text();
        
        let formattedText = text.trim() || "[CORPO EM BRANCO / SEM RETORNO]";
        let isJson = true;
        try { JSON.parse(text); } catch(e) { isJson = false; }
        
        let statusStyle = (res.status >= 200 && res.status < 300) ? "color: #4ade80;" : "color: #f87171;";
        
        resBox.innerHTML = "<strong>Código de Status HTTP:</strong> <span style='" + statusStyle + "'>" + res.status + " " + res.statusText + "</span> (" + duration + "ms)<br>" +
                           "<strong>Tipo de Conteúdo Recebido:</strong> <code>" + (res.headers.get("content-type") || "Não especificado") + "</code><br>" +
                           "<strong>Formato JSON Válido:</strong> " + (isJson ? "<span class='success'>Sim [OK]</span>" : "<span class='warning'>Não [Inválido]</span>") + "<br><br>" +
                           "<strong>Conteúdo Bruto Devolvido (Response Body):</strong><br>" +
                           "<pre style='background: #090d16; border: 1px solid #1e293b; padding: 10px; border-radius: 6px; margin: 8px 0; overflow-x: auto; max-height: 400px; color: " + (isJson ? "#38bdf8" : "#ef4444") + "'>" + 
                           (formattedText.replace(/</g, "&lt;").replace(/>/g, "&gt;")) + "</pre>";
                           
        if (res.status === 404) {
            resBox.innerHTML += "<p class='warning'>⚠️ O servidor retornou 404. Verifique se o arquivo <strong>api.php</strong> foi colocado exatamente na mesma pasta que este diagnosticador!</p>";
        } else if (text.trim() === "") {
            resBox.innerHTML += "<p class='warning'>⚠️ O endpoint devolveu uma resposta vazia. Veja no log 'api_errors.log' se houve um erro oculto ou confira se suas tabelas no banco de dados estão vazias ou ausentes.</p>";
        }
    } catch(err) {
        resBox.innerHTML = "<div class='error'>FALHA NA REQUISIÇÃO AJAX:<br><br>Mensagem: " + err.message + "</div>";
    } finally {
        btn.disabled = false;
        btn.style.opacity = "1";
    }
}
</script>
</div>
<?php

echo "<p style='text-align: center; font-size: 11px; color: #64748b;'>PHPlayer PHP Web Player • Diagnosticador Avançado de Hosting</p>";
echo "</body></html>";
