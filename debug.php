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
echo "<li>Pasta de Uploads (uploads/): ";
$uploadsDir = __DIR__ . '/uploads/';
if (file_exists($uploadsDir)) {
    echo "Existe " . (is_writable($uploadsDir) ? "<span class='success'>[E é Gravável (0755/0777)]</span>" : "<span class='warning'>[Mas NÃO é Gravável! Defina permissões para 0755 ou 0777]</span>");
} else {
    echo "<span class='warning'>Não existe (tentará ser criada)</span>";
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
        $tabelasRequeridas = ['users', 'songs', 'playlists', 'playlist_songs', 'favorites'];
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
echo "<h3>4. Registro de Erros do Servidor (api_errors.log)</h3>";
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
<h3>5. Teste Interativo de Endpoint da API</h3>
<p>Clique no botão abaixo para simular uma requisição JavaScript de login para o seu arquivo <code>api.php</code> e diagnosticar respostas em branco:</p>
<button id='test-btn' onclick='testApiLogin()' style='background: #e11d48; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background 0.2s;'>Simular Requisição de Login (POST)</button>
<div id='api-test-result' style='margin-top: 15px; padding: 15px; background: #020617; border: 1px solid #1e293b; border-radius: 8px; font-family: monospace; font-size: 12px; display: none; line-height: 1.6;'></div>

<script>
async function testApiLogin() {
    const resBox = document.getElementById("api-test-result");
    const btn = document.getElementById("test-btn");
    resBox.style.display = "block";
    resBox.innerHTML = "<em>Enviando requisição POST para api.php?route=login...</em>";
    btn.disabled = true;
    btn.style.opacity = "0.6";
    try {
        const res = await fetch("api.php?route=login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username: "admin", password: "wrong_password_test" })
        });
        const text = await res.text();
        let formattedText = text.trim() || "[CORPO EM BRANCO / SEM RETORNO]";
        let isJson = true;
        try { JSON.parse(text); } catch(e) { isJson = false; }
        
        let statusStyle = (res.status >= 200 && res.status < 300) ? "color: #4ade80;" : (res.status === 401 ? "color: #facc15;" : "color: #f87171;");
        
        resBox.innerHTML = "<strong>Código de Status HTTP:</strong> <span style='" + statusStyle + "'>" + res.status + " " + res.statusText + "</span><br>" +
                           "<strong>Tipo de Conteúdo Recebido:</strong> <code>" + (res.headers.get("content-type") || "Não especificado") + "</code><br>" +
                           "<strong>Formato JSON Válido:</strong> " + (isJson ? "<span class='success'>Sim [OK]</span>" : "<span class='warning'>Não [Inválido]</span>") + "<br><br>" +
                           "<strong>Conteúdo Bruto Devolvido (Response Body):</strong><br>" +
                           "<pre style='background: #090d16; border: 1px solid #1e293b; padding: 10px; border-radius: 6px; margin: 8px 0; overflow-x: auto; color: " + (isJson ? "#38bdf8" : "#ef4444") + "'>" + 
                           (formattedText.replace(/</g, "&lt;").replace(/>/g, "&gt;")) + "</pre>";
                           
        if (res.status === 404) {
            resBox.innerHTML += "<p class='warning'>⚠️ O servidor retornou 404. Verifique se o arquivo <strong>api.php</strong> foi colocado exatamente na mesma pasta que este arquivo de de diagnóstico!</p>";
        } else if (text.trim() === "") {
            resBox.innerHTML += "<p class='warning'>⚠️ O arquivo <strong>api.php</strong> existe, mas devolveu uma resposta 100% vazia. Isso pode indicar uma restrição do servidor ao ler JSON brutos no PHP, ou erro fatal oculto que não pôde ser gravado nos logs.</p>";
        }
    } catch(err) {
        resBox.innerHTML = "<span class='error'>Erro de rede ao disparar a requisição: " + err.message + "</span>";
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
