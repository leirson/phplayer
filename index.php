<?php
if (isset($_GET['share'])) {
    require 'shared.php';
    exit;
}
?>
<?php
// Detecta se o acesso é por dispositivo móvel
function isMobile() {
    return preg_match(
        '/(android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile)/i',
        $_SERVER['HTTP_USER_AGENT']
    );
}

// Se for mobile, redireciona
if (isMobile()) {
    header("Location: mobile.php");
    exit;
}

@error_reporting(E_ALL);
@ini_set('display_errors', 1);
if (!file_exists('config.php')) {
    die("config.php missing");
}
define('DONT_EXIT_ON_DB_ERROR', true);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPlayer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    zIndex: {
                        '55': '55',
                        '56': '56',
                        '57': '57',
                        '60': '60'
                    },
                    colors: {
                        sky: {
                            300: 'var(--theme-sky-300)',
                            400: 'var(--theme-sky-400)',
                            450: 'var(--theme-sky-450)',
                            500: 'var(--theme-sky-500)',
                            600: 'var(--theme-sky-600)',
                        },
                        indigo: {
                            500: 'var(--theme-indigo-500)',
                            600: 'var(--theme-indigo-600)',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap');
        
        :root {
            --theme-sky-300: #7dd3fc;
            --theme-sky-400: #38bdf8;
            --theme-sky-450: #38bdf8;
            --theme-sky-500: #0ea5e9;
            --theme-sky-600: #0284c7;
            --theme-indigo-500: #6366f1;
            --theme-indigo-600: #4f46e5;
            --theme-bg: #070b13;
        }
        :root[data-theme="emerald"] {
            --theme-sky-300: #6ee7b7;
            --theme-sky-400: #34d399;
            --theme-sky-450: #34d399;
            --theme-sky-500: #10b981;
            --theme-sky-600: #059669;
            --theme-indigo-500: #10b981;
            --theme-indigo-600: #047857;
            --theme-bg: #02140e;
        }
        :root[data-theme="rose"] {
            --theme-sky-300: #fca5a5;
            --theme-sky-400: #fb7185;
            --theme-sky-450: #fb7185;
            --theme-sky-500: #f43f5e;
            --theme-sky-600: #e11d48;
            --theme-indigo-500: #ec4899;
            --theme-indigo-600: #db2777;
            --theme-bg: #140307;
        }
        :root[data-theme="amber"] {
            --theme-sky-300: #fde047;
            --theme-sky-400: #fbbf24;
            --theme-sky-450: #fbbf24;
            --theme-sky-500: #f59e0b;
            --theme-sky-600: #d97706;
            --theme-indigo-500: #f97316;
            --theme-indigo-600: #ea580c;
            --theme-bg: #110902;
        }
        :root[data-theme="violet"] {
            --theme-sky-300: #c084fc;
            --theme-sky-400: #a78bfa;
            --theme-sky-450: #a78bfa;
            --theme-sky-500: #8b5cf6;
            --theme-sky-600: #7c3aed;
            --theme-indigo-500: #6366f1;
            --theme-indigo-600: #4f46e5;
            --theme-bg: #0a0414;
        }
        :root[data-theme="crimson"] {
            --theme-sky-300: #fca5a5;
            --theme-sky-400: #f87171;
            --theme-sky-450: #f87171;
            --theme-sky-500: #ef4444;
            --theme-sky-600: #dc2626;
            --theme-indigo-500: #b91c1c;
            --theme-indigo-600: #991b1b;
            --theme-bg: #140203;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--theme-bg, #070b13); color: #e2e8f0; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #020617; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }

        /* Evitar que preenchimentos automáticos do navegador usem fundos claros em inputs escuros */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0px 1000px #050914 inset !important;
            -webkit-text-fill-color: #38bdf8 !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Estilos do Player de Vídeo sem Travamentos (Pseudo-Fullscreen) */
        #video-modal.pseudo-fullscreen-active {
            padding: 0 !important;
            background-color: #000000 !important;
        }
        #video-modal.pseudo-fullscreen-active #video-modal-container {
            max-width: 100% !important;
            width: 100vw !important;
            height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
        }
        #video-modal.pseudo-fullscreen-active #video-viewport-container {
            aspect-ratio: auto !important;
            flex-grow: 1 !important;
            height: calc(100vh - 53px) !important;
        }
        video::-webkit-media-controls-fullscreen-button {
            display: none !important;
        }
        video::-picture-in-picture-button {
            display: none !important;
        }
        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spinSlow 20s linear infinite;
        }
        .paused-animation {
            animation-play-state: paused !important;
        }
    
    .no-download [onclick*="startDownload"] { display: none !important; }
    .no-download [onclick*="route=download"] { display: none !important; }
    .no-download button[title*="Download"] { display: none !important; }
    .no-download button[title*="Baixar"] { display: none !important; }
    .no-download a[data-download-bot] { display: none !important; }
    .no-download [data-lucide="download"] { display: none !important; }
    .no-download .only-downloaders { display: none !important; }
</style>
</head>
<body class="h-screen overflow-hidden flex flex-col antialiased">

    <!-- DIAGNOSTIC ERROR MODAL -->
    <div id="error-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md hidden">
        <div class="w-full max-w-2xl bg-slate-950 border border-slate-900 rounded-3xl p-8 shadow-2xl space-y-6 text-left">
            <div class="flex items-center gap-4 border-b border-slate-900 pb-5">
                <div class="p-3 bg-rose-500/10 rounded-2xl text-rose-450">
                    <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                </div>
                <div>
                    <h1 class="text-base font-black text-white leading-tight">Falha de Instalação / MySQL</h1>
                    <p class="text-[11px] text-slate-400">Ocorreu um erro operacional de conexão ou autenticação no banco de dados.</p>
                </div>
            </div>
            
            <div class="bg-rose-550/5 border border-rose-500/10 text-rose-300 rounded-2xl p-5 space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-rose-400">Retorno Técnico do Servidor PHP:</h2>
                <pre id="error-modal-details" class="text-[11px] font-mono bg-slate-905 p-4 rounded-xl text-rose-200 border border-slate-900 overflow-x-auto whitespace-pre-wrap max-h-48"></pre>
            </div>

            <div class="space-y-3 text-xs text-slate-400">
                <h3 class="font-bold text-slate-200 uppercase tracking-widest text-[10px]">Guia de Correção Rápidos (Hostinger):</h3>
                <ul class="list-decimal pl-5 space-y-2 leading-relaxed">
                    <li>
                        <strong class="text-white font-semibold">Use o Diagnosticador Automático (debug.php):</strong> Crie um arquivo no servidor chamado <strong class="text-rose-400">debug.php</strong> com o código disponível na aba de Diagnóstico do seu Hub de Exportação e acesse <code class="bg-slate-900 px-1 py-0.5 rounded text-white font-mono">seudominio.com/debug.php</code>. Ele testará sua conexão e listará exatamente quais tabelas estão faltando!
                    </li>
                    <li>
                        <strong class="text-white">Verifique as Credenciais do Banco:</strong> No gerenciador de arquivos da Hostinger, abra o arquivo <strong class="text-sky-400">config.php</strong> e verifique se as constantes <code class="bg-slate-900 px-1 py-0.5 rounded text-white font-mono">DB_HOST</code>, <code class="bg-slate-900 px-1 py-0.5 rounded text-white font-mono">DB_NAME</code>, <code class="bg-slate-900 px-1 py-0.5 rounded text-white font-mono">DB_USER</code> e <code class="bg-slate-900 px-1 py-0.5 rounded text-white font-mono">DB_PASS</code> batem exatamente com as configurações do banco criado no painel da Hostinger.
                    </li>
                    <li>
                        <strong class="text-white">Importe o arquivo database.sql:</strong> Certifique-se de acessar o <strong class="text-indigo-400">phpMyAdmin</strong> correspondente na Hostinger e importar as tabelas executando o arquivo <code class="bg-slate-900 px-1 py-0.5 rounded font-mono text-emerald-400">database.sql</code> presente no seu Hub de Exportação.
                    </li>
                    <li>
                        <strong class="text-white">Exclua o index.html default:</strong> Lembre-se de apagar o arquivo <code class="bg-slate-900 px-1.5 py-0.5 rounded font-mono text-amber-500">index.html</code> default criado pela Hostinger na pasta <strong class="text-white">public_html</strong>, para o Apache dar preferência ao <strong class="text-sky-400">index.php</strong>.
                    </li>
                </ul>
            </div>

            <div class="pt-4 border-t border-slate-900 flex justify-end gap-3">
                <button onclick="closeErrorModal()" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-850 border border-slate-800 rounded-xl text-xs font-bold text-slate-350 transition">
                    Fechar Aviso
                </button>
                <button onclick="window.location.reload()" class="px-5 py-2.5 bg-sky-500 hover:bg-sky-450 rounded-xl text-xs font-black text-white transition inline-flex items-center gap-1.5">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Recarregar Página
                </button>
            </div>
        </div>
    </div>

    <!-- SCAN LOG MODAL -->
    <div id="scan-log-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md hidden animate-fade-in">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-3xl w-full p-6 shadow-2xl space-y-4 flex flex-col justify-between max-h-[85vh]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 shrink-0">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-sky-400"></i>
                    <div>
                        <h2 class="text-sm font-black text-white uppercase tracking-wider">Log de Sincronização</h2>
                        <p class="text-[10px] text-slate-400">Relatório da última varredura detalhada de diretórios de músicas.</p>
                    </div>
                </div>
                <button onclick="closeScanLogModal()" class="p-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            
            <div class="space-y-1.5 flex-1 flex flex-col min-h-0 overflow-hidden">
                <div class="flex items-center justify-between pb-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-sans">Histórico de Eventos Encontrados:</span>
                    <button onclick="refreshMusicScanLog()" class="px-2 py-1 text-[10px] bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 font-bold rounded flex items-center gap-1 transition">
                        <i data-lucide="rotate-cw" class="w-3 h-3"></i> Atualizar
                    </button>
                </div>
                <div id="scan-log-content" class="text-[11px] font-mono bg-slate-950 p-4 rounded-xl text-slate-300 border border-slate-900 overflow-y-auto flex-1 whitespace-pre-wrap leading-relaxed select-text">
                    Carregando logs...
                </div>
            </div>
            
            <div class="flex items-center justify-between pt-1 border-t border-slate-800 shrink-0">
                <span id="scan-log-time" class="text-[9px] font-mono text-slate-500">Última atualização: -</span>
                <div class="flex gap-2 font-sans">
                    <button onclick="clearMusicScanLog()" class="px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10 font-bold rounded-lg border border-rose-500/20 transition cursor-pointer">
                        Limpar Log
                    </button>
                    <button onclick="closeScanLogModal()" class="px-4 py-1.5 text-xs bg-slate-800 hover:bg-slate-700 text-white font-black rounded-lg transition cursor-pointer">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- GOOGLE IMAGES SEARCH MODAL REMOVED -->
    
    <!-- PLAYLIST SELECTOR MODAL -->
    <div id="playlist-selector-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md hidden animate-fade-in">
        <div class="bg-slate-950 border border-slate-900 rounded-3xl w-full max-w-sm overflow-hidden flex flex-col shadow-2xl">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-900 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-black uppercase text-white tracking-wider flex items-center gap-1.5">
                        <i data-lucide="list-plus" class="w-4 h-4 text-sky-400"></i>
                        Adicionar à Playlist
                    </h3>
                    <p class="text-[10px] text-slate-500 font-mono mt-0.5 uppercase tracking-wider">
                        Selecione a playlist de destino
                    </p>
                </div>
                <button onclick="closePlaylistSelectorModal()" class="p-1 px-2.5 bg-slate-900 border border-slate-800 hover:bg-slate-850 text-slate-400 hover:text-white rounded-lg text-xs cursor-pointer font-bold transition">
                    Fechar
                </button>
            </div>

            <!-- List of Playlists -->
            <div id="playlist-selector-list" class="p-5 space-y-2 max-h-[300px] overflow-y-auto custom-scroll">
                <!-- Playlists will be dynamically inserted here -->
            </div>
        </div>
    </div>

    <!-- LOGIN PANEL -->
    <div id="login-panel" class="flex-1 flex items-center justify-center p-4 hidden" style="background-color: var(--theme-bg, #070b13);">
        <div class="w-full max-w-md p-8 bg-slate-950/60 border border-slate-900 rounded-3xl shadow-2xl backdrop-blur-xl text-center space-y-6">
            <div class="inline-flex p-4 bg-sky-500/10 rounded-2xl text-sky-400">
                <i data-lucide="music" class="w-8 h-8 animate-pulse"></i>
            </div>
            <div>
                <h2 class="text-xl font-black text-white tracking-tight">PHPlayer PHP Engine</h2>
                <p class="text-xs text-slate-500 mt-1">Insira suas credenciais para acessar sua biblioteca digital</p>
            </div>
            <form onsubmit="handleLoginSubmit(event)" class="space-y-4">
                <input id="login-username" type="text" placeholder="Nome de Usuário" required class="w-full bg-slate-900 border border-slate-800 focus:border-sky-500 text-white rounded-xl px-4 py-3 text-xs outline-none transition-all font-semibold">
                <input id="login-password" type="password" placeholder="Senha da Conta" required class="w-full bg-slate-900 border border-slate-800 focus:border-sky-500 text-white rounded-xl px-4 py-3 text-xs outline-none transition-all font-semibold">
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-sky-500 to-indigo-600 hover:opacity-90 active:scale-[0.98] text-white rounded-xl text-xs font-black transition-all">
                    Acessar Player
                </button>
            </form>
            <p class="text-[10px] text-slate-600">Teste padrão: admin / admin ou ouvinte / ouvinte</p>
        </div>
    </div>

    <!-- WORKSPACE PANEL -->
    <!-- WORKSPACE PANEL (Public Shared Player) -->
    <div id="public-shared-player" class="flex-1 flex overflow-hidden hidden bg-slate-950 flex-col items-center">
    </div>

    <!-- WORKSPACE PANEL (Logged In) -->
    <div id="workspace-panel" class="flex-1 flex overflow-hidden hidden">
        
        <!-- SIDEBAR -->
        <aside class="w-64 bg-slate-950 border-r border-slate-900 p-4 flex flex-col justify-between shrink-0 h-full">
            <div class="space-y-6 overflow-y-auto pr-1">
                <div class="flex items-center gap-3 px-2 py-1">
                    <div class="p-2 bg-gradient-to-tr from-sky-500 to-indigo-600 rounded-xl text-white shadow-lg shadow-sky-500/10">
                        <i data-lucide="music-4" class="w-5 h-5 animate-pulse"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-white tracking-tight leading-none">PHPlayer</h1>
                        <span class="text-[9px] text-sky-450 font-mono tracking-wider">PLAYER WEB</span>
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-500 pl-2" data-i18n="sidebar-title">Biblioteca</p>
                    <button id="tab-btn-dashboard" onclick="setTab('dashboard')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-sky-400 bg-sky-550/10 border border-sky-500/20" data-i18n="sidebar-dashboard">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                    </button>
                    <button id="tab-btn-tracks" onclick="setTab('tracks')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition" data-i18n="sidebar-songs">
                        <i data-lucide="music" class="w-4 h-4"></i> Minhas Músicas
                    </button>
                    <button id="tab-btn-favorites" onclick="setTab('favorites')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition" data-i18n="sidebar-favorites">
                        <i data-lucide="heart" class="w-4 h-4 text-rose-500"></i> Favoritos
                    </button>
                    <button id="tab-btn-playlists" onclick="setTab('playlists')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition" data-i18n="sidebar-playlists">
                        <i data-lucide="list-music" class="w-4 h-4 text-emerald-450"></i> Playlists
                    </button>
                    <button id="tab-btn-videos" onclick="setTab('videos')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition" data-i18n="sidebar-videos">
                        <i data-lucide="film" class="w-4 h-4 text-sky-450"></i> Galeria de Vídeos
                    </button>
                    <button id="tab-btn-podcast" onclick="setTab('podcast')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition">
                        <i data-lucide="podcast" class="w-4 h-4 text-orange-400"></i> Podcast
                    </button>
                    <button id="tab-btn-radios" onclick="setTab('radios')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition">
                        <i data-lucide="radio" class="w-4 h-4 text-emerald-400"></i> Rádios
                    </button>
                    <button id="tab-btn-reprodutor" onclick="setTab('reprodutor')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition">
                        <i data-lucide="disc" class="w-4 h-4 text-sky-450 animate-spin-slow"></i> Reprodutor
                    </button>
                    
                    <button id="tab-btn-config" onclick="setTab('config')" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition" data-i18n="sidebar-settings">
                        <i data-lucide="settings" class="w-4 h-4 text-sky-400"></i> Configurações
                    </button>
                </div>

                <!-- ARTISTAS -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between pl-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500" data-i18n="sidebar-artists">Artistas</span>
                        <button id="clear-artist-filter" onclick="filterByArtist('')" class="text-[9px] text-sky-400 font-bold hidden cursor-pointer" data-i18n="sidebar-clear-filter">Limpar</button>
                    </div>
                    <div id="artist-sidebar-list" class="space-y-0.5 max-h-[300px] overflow-y-auto pr-1"></div>
                </div>

                <!-- LISTA DE REPRODUÇÃO ATUAL (QUEUE) -->
                <div id="player-mini-queue-wrapper" class="space-y-2 hidden">
                    <div class="flex items-center justify-between pl-2 pt-2 border-t border-slate-900/40">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-500 flex items-center gap-1.5">
                            <i data-lucide="play-circle" class="w-3.5 h-3.5"></i> Lista de Reprodução
                        </span>
                        <button onclick="clearCurrentQueue()" class="text-[9px] text-slate-500 hover:text-red-400 font-bold transition cursor-pointer">Limpar</button>
                    </div>
                    <div id="player-mini-queue-list" class="space-y-0.5 max-h-48 overflow-y-auto pr-1 custom-scroll text-left"></div>
                </div>
            </div>

            <!-- FOOTER PROFILE -->
            <div class="border-t border-slate-900 pt-3 flex items-center justify-between">
                <div class="flex items-center gap-2 truncate">
                    <div id="user-avatar" class="w-7 h-7 rounded-lg bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white text-[10px] font-black">US</div>
                    <div class="truncate">
                        <p id="profile-name" class="text-xs font-bold text-slate-200 truncate leading-none">Usuário</p>
                        <span id="profile-role" class="text-[9px] text-slate-500 font-bold uppercase tracking-wide">Ouvinte</span>
                    </div>
                </div>
                <button onclick="handleLogout()" class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition" title="Sair da Conta">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </div>
        </aside>

        <!-- MAIN WINDOW -->
        <main class="flex-1 overflow-y-auto p-6 flex flex-col" style="background-color: var(--theme-bg, #070b13);">
            
            <!-- VIEW: DASHBOARD -->
            <section id="pane-dashboard" class="space-y-6 flex-1">
                <div class="flex justify-between items-center bg-slate-950/40 border border-slate-900 p-6 rounded-2xl">
                    <div>
                        <h2 id="dashboard-welcome-title" class="text-xl font-black text-white" data-i18n="welcome-title">Bem-vindo de volta!</h2>
                        <p id="dashboard-welcome-sub" class="text-xs text-slate-400 mt-1" data-i18n="welcome-sub">Servidor PHPlayer</p>
                    </div>
                    <div class="text-right">
                        <span id="clock" class="text-xl font-black text-sky-400 font-mono">00:00</span>
                        <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5" data-i18n="clock-sync">Sincronizado</p>
                    </div>
                </div>

                <!-- STATS -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="p-4 bg-slate-950/60 border border-slate-900 rounded-2xl">
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest" data-i18n="stat-collection">Coleção</span>
                        <h3 id="stat-songs" class="text-2xl font-black text-white mt-1">0</h3>
                    </div>
                    <div class="p-4 bg-slate-950/60 border border-slate-900 rounded-2xl">
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest" data-i18n="stat-albums">Álbuns únicos</span>
                        <h3 id="stat-albums" class="text-2xl font-black text-white mt-1">0</h3>
                    </div>
                    <div class="p-4 bg-slate-950/60 border border-slate-900 rounded-2xl">
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest" data-i18n="stat-artists">Artistas únicos</span>
                        <h3 id="stat-artists" class="text-2xl font-black text-white mt-1">0</h3>
                    </div>
                    <div class="p-4 bg-slate-950/60 border border-slate-900 rounded-2xl">
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest" data-i18n="stat-favorites">Favoritos</span>
                        <h3 id="stat-favs" class="text-2xl font-black text-white mt-1">0</h3>
                    </div>
                </div>

                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 border-b border-slate-900 pb-3">
                        <h3 class="text-xs font-black text-slate-400 tracking-wider uppercase" data-i18n="album-collection-title">Coleção de Álbuns</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Search Input for Dashboard -->
                            <div class="relative w-36 md:w-40 shrink-0">
                                <input id="php-dashboard-search" type="text" oninput="onPhpDashboardSearchInput(this.value)" data-i18n-placeholder="dashboard-search-placeholder" placeholder="Buscar álbuns..." class="w-full bg-[#0a111e] border border-slate-800 text-slate-300 hover:text-white text-[11px] rounded-xl pl-8 pr-3 py-2 focus:border-sky-500 outline-none font-medium transition shadow-inner" />
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-500">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>

                            <!-- Gênero Filter Dropdown -->
                            <div class="relative w-36 md:w-40 shrink-0">
                                <select id="php-dashboard-genre-filter" onchange="onPhpDashboardGenreChange(this.value)" class="w-full bg-[#0a111e] border border-slate-800 text-slate-300 hover:text-white text-[11px] rounded-xl pl-3 pr-8 py-2 focus:border-sky-500 outline-none font-bold cursor-pointer appearance-none transition shadow-inner">
                                    <option value="all">Todos Gêneros</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            
                            <!-- Sort Filter Dropdown -->
                            <div class="relative w-36 md:w-40 shrink-0">
                                <select id="php-dashboard-sort-filter" onchange="onPhpDashboardSortChange(this.value)" class="w-full bg-[#0a111e] border border-slate-800 text-slate-300 hover:text-white text-[11px] rounded-xl pl-3 pr-8 py-2 focus:border-sky-500 outline-none font-bold cursor-pointer appearance-none transition shadow-inner">
                                    <option value="random" selected>Aleatórios</option>
                                    <option value="alphabetical">Ordem Alfabética</option>
                                    <option value="recent">Mais Recentes</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>

                            <!-- play random album button -->
                            <button onclick="playRandomAlbum()" class="px-3.5 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-[11px] font-black uppercase tracking-wider transition active:scale-95 flex items-center gap-1.5 shadow-lg shadow-sky-500/10 cursor-pointer shrink-0" data-i18n="btn-random-album">
                                <i data-lucide="shuffle" class="w-3.5 h-3.5"></i> Tocar Álbum Aleatório
                            </button>
                        </div>
                    </div>
                    <div id="album-grid-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>
                </div>
            </section>

            <!-- VIEW: MUSIC TABLE & PLAYLIST -->
            <section id="pane-tracks" class="space-y-6 flex-1 hidden">
                <div id="tracks-header-block" class="flex justify-between items-center gap-4">
                    <div>
                        <h2 id="table-view-title" class="text-xl font-black text-white" data-i18n="title-library">Minha Biblioteca</h2>
                        <p id="table-view-count" class="text-xs text-slate-400 mt-0.5">0 músicas encontradas</p>
                        <div id="favorites-actions-block" class="mt-2.5 flex items-center gap-2 hidden"></div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <!-- Artist Select Filter Dropdown -->
                        <div class="relative w-48">
                            <select id="artist-filter-dropdown" onchange="filterTracksByArtistDropdown(this.value)" class="w-full bg-[#0d121f] border border-slate-800 text-slate-200 text-xs rounded-xl pl-3.5 pr-8 py-2.5 focus:border-sky-500 outline-none font-semibold select-all cursor-pointer appearance-none">
                                <option value="">Todos Artistas</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <input id="search-input" oninput="renderTracksTable()" type="text" data-i18n-placeholder="search-placeholder" placeholder="Pesquisar título, artista, álbum..." class="w-64 bg-slate-900 border border-slate-800 focus:border-sky-500 rounded-xl px-4 py-2.5 text-xs select-all text-white outline-none font-semibold">
                    </div>
                </div>

                <div id="tracks-grid-layout" class="flex flex-col lg:flex-row gap-6">
                    <!-- Left Sidebar Panels for Artistas and Álbuns -->
                    <div class="w-full lg:w-64 shrink-0 space-y-4">
                        <!-- Artists Sidebar Card -->
                        <div class="p-4 bg-slate-900/30 border border-slate-900 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <i data-lucide="smile" class="w-3.5 h-3.5 text-sky-400"></i> Artistas
                                </span>
                                <button onclick="clearSidebarFilters()" id="reset-filter-pills" class="text-[9px] text-sky-400 hover:text-sky-300 font-semibold cursor-pointer hidden">
                                    Limpar
                                </button>
                            </div>
                            <div id="sidebar-artists-list" class="flex flex-col gap-1.5 max-h-64 lg:max-h-[320px] overflow-y-auto pr-1 small-scroll custom-scroll w-full">
                            </div>
                        </div>

                        <!-- Albums Sidebar Card -->
                        <div class="p-4 bg-slate-900/30 border border-slate-900 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <i data-lucide="folder-heart" class="w-3.5 h-3.5 text-violet-400"></i> Álbuns
                                </span>
                            </div>
                            <div id="sidebar-albums-list" class="flex flex-col gap-1.5 max-h-64 lg:max-h-[320px] overflow-y-auto pr-1 small-scroll custom-scroll w-full">
                            </div>
                        </div>
                    </div>

                    <!-- Right Area: Table wrapper -->
                    <div id="tracks-table-wrapper" class="flex-1 bg-slate-950 border border-slate-900 rounded-2xl overflow-hidden min-h-[300px]">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#101522] text-slate-550 uppercase font-mono tracking-wider text-[9px] border-b border-slate-900 select-none">
                                <tr>
                                    <th class="py-3 px-4 w-12 text-center" data-i18n="col-idx">#</th>
                                    <th class="py-3 px-4 cursor-pointer hover:bg-slate-900/40 transition" onclick="sortTracksPhp('title')" data-i18n="col-track">Faixa <span id="sort-icon-title" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                    <th class="py-3 px-4 cursor-pointer hover:bg-slate-900/40 transition" onclick="sortTracksPhp('artist')" data-i18n="col-artist">Artista <span id="sort-icon-artist" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                    <th class="py-3 px-4 cursor-pointer hover:bg-slate-900/40 transition" onclick="sortTracksPhp('album')" data-i18n="col-album">Álbum <span id="sort-icon-album" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                    <th class="py-3 px-4 cursor-pointer hover:bg-slate-900/40 transition hidden sm:table-cell" onclick="sortTracksPhp('genre')">Gênero <span id="sort-icon-genre" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                    <th class="py-3 px-4 cursor-pointer hover:bg-slate-900/40 transition text-center w-20 hidden sm:table-cell" onclick="sortTracksPhp('duration')">
                                        <div class="flex items-center justify-center">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-500 mr-1"></i>
                                            Duração <span id="sort-icon-duration" class="text-slate-600 opacity-40"> &updownarrow;</span>
                                        </div>
                                    </th>
                                    <th class="py-3 px-4 text-right w-28 font-semibold" data-i18n="col-operations">Operações</th>
                                </tr>
                            </thead>
                            <tbody id="tracks-table-body" class="divide-y divide-slate-900/40 text-slate-300"></tbody>
                        </table>
                        <div id="tracks-pagination-wrapper"></div>
                    </div>
                </div>

                <!-- ARTIST DISC COMPONENT VIEW -->
                <div id="artist-albums-view" class="hidden space-y-6"></div>
            </section>

            <!-- VIEW: CONFIGURATION PANEL -->
            <section id="pane-config" class="space-y-6 flex-1 hidden">
                <div class="border-b border-slate-900 pb-5">
                    <h2 class="text-xl font-black text-white flex items-center gap-2">
                        <i data-lucide="settings" class="text-sky-400 w-5 h-5"></i> <span data-i18n="config-panel-title">Painel de Configurações</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium" data-i18n="config-panel-sub">Personalize as cores do site e gerencie a biblioteca local de músicas e vídeos.</p>
                </div>
 
                <!-- SUBTABS NAVIGATION -->
                <div class="flex border-b border-slate-900/60 pb-1 gap-4" id="config-subtabs-nav">
                    <button onclick="setConfigSubTab('theme')" id="subtab-btn-theme" class="pb-2 text-xs font-bold border-b-2 border-sky-500 text-white cursor-pointer select-none" data-i18n="subnav-themes">
                        Coloração & Temas
                    </button>
                    <button onclick="setConfigSubTab('media')" id="subtab-btn-media" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only" data-i18n="subnav-sync">
                        Sincronização e Mídia
                    </button>
                    <!-- end current -->

                    <button onclick="setConfigSubTab('updates')" id="subtab-btn-updates" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only">
                        Atualização
                    </button>

                    <button onclick="setConfigSubTab('shares')" id="subtab-btn-shares" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only">
                        Compartilhamentos
                    </button>
                    <button onclick="setConfigSubTab('dashboard_cfg')" id="subtab-btn-dashboard_cfg" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only">
                        Dashboard
                    </button>
<button onclick="setConfigSubTab('users')" id="subtab-btn-users" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only" data-i18n="subnav-users">
                        Editar Usuários
                    </button>
                    <button onclick="setConfigSubTab('password')" id="subtab-btn-password" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none" data-i18n="subnav-password">
                        Alterar Senha
                    </button>
                    <button onclick="setConfigSubTab('shortcuts')" id="subtab-btn-shortcuts" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none">
                        Atalhos de Teclado
                    </button>
                    <button onclick="setConfigSubTab('files')" id="subtab-btn-files" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only" data-i18n="subnav-files">
                        Arquivos
                    </button>
                    <button onclick="setConfigSubTab('id3')" id="subtab-btn-id3" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only">
                        Editor ID3
                    </button>
                </div>
 
                <!-- SUBTAB 1: COLOR THEMES -->
                <div id="subtab-pane-theme" class="space-y-6">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="space-y-1">
                            <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5">
                                <i data-lucide="sparkles" class="w-4 h-4 text-sky-400"></i> <span data-i18n="theme-choose-title">Escolha sua cor de realce e fundo</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 leading-normal" data-i18n="theme-choose-desc">Selecione abaixo o seu tema de cores. Ele mudará os ícones, botões de reprodução, barras de progresso e a cor de fundo do site.</p>
                        </div>
                        <button onclick="saveDesktopTheme()" class="px-5 py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl flex items-center gap-2 transition select-none cursor-pointer shadow-lg shadow-sky-500/10" data-i18n="theme-apply-btn">
                            <i data-lucide="check" class="w-4 h-4"></i> Aplicar Tema
                        </button>
                    </div>
 
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button onclick="selectDesktopTheme('default')" class="theme-card relative p-4 rounded-2xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="w-8 h-8 rounded-lg bg-sky-500 shrink-0 flex items-center justify-center text-white" id="indicator-theme-default"></div>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-0.5" data-i18n="theme-default-title">Azul Celeste</h4>
                                <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-default-desc">O visual clássico do player com tons azuis elegantes.</p>
                            </div>
                        </button>
                        <button onclick="selectDesktopTheme('emerald')" class="theme-card relative p-4 rounded-2xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500 shrink-0 flex items-center justify-center text-white" id="indicator-theme-emerald"></div>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-0.5" data-i18n="theme-emerald-title">Verde Esmeralda</h4>
                                <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-emerald-desc">Visual inspirado em florestas com tons verdes vibrantes.</p>
                            </div>
                        </button>
                        <button onclick="selectDesktopTheme('rose')" class="theme-card relative p-4 rounded-2xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="w-8 h-8 rounded-lg bg-rose-500 shrink-0 flex items-center justify-center text-white" id="indicator-theme-rose"></div>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-0.5" data-i18n="theme-rose-title">Rosa Sunset</h4>
                                <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-rose-desc">Sensações acolhedoras de fim de tarde e tons quentes de rosa.</p>
                            </div>
                        </button>
                        <button onclick="selectDesktopTheme('amber')" class="theme-card relative p-4 rounded-2xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="w-8 h-8 rounded-lg bg-amber-500 shrink-0 flex items-center justify-center text-white" id="indicator-theme-amber"></div>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-0.5" data-i18n="theme-amber-title">Nascer do Sol</h4>
                                <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-amber-desc">Aparência ensolarada e aconchegante em ouro vibrante.</p>
                            </div>
                        </button>
                        <button onclick="selectDesktopTheme('violet')" class="theme-card relative p-4 rounded-2xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="w-8 h-8 rounded-lg bg-violet-500 shrink-0 flex items-center justify-center text-white" id="indicator-theme-violet"></div>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-0.5" data-i18n="theme-violet-title">Roxo Ametista</h4>
                                <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-violet-desc">Clima místico e tecnológico em violeta profundo e rico.</p>
                            </div>
                        </button>
                        <button onclick="selectDesktopTheme('crimson')" class="theme-card relative p-4 rounded-2xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="w-8 h-8 rounded-lg bg-red-600 shrink-0 flex items-center justify-center text-white" id="indicator-theme-crimson"></div>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-0.5" data-i18n="theme-crimson-title">Vermelho Carmesim</h4>
                                <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-crimson-desc">Contrastes intensos e fortes com tonalidades rubi profundas.</p>
                            </div>
                        </button>
                        <!-- Special Custom Theme Card for PHP -->
                        <div id="php-custom-theme-card" class="theme-card relative p-4 rounded-2xl border text-left flex flex-col gap-3 justify-between bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none">
                            <div class="flex gap-4 items-start w-full">
                                <div id="indicator-theme-custom" onclick="document.getElementById('php-custom-color-picker').click()" class="w-8 h-8 rounded-lg bg-sky-500 shrink-0 flex items-center justify-center text-white cursor-pointer border border-white/10 relative">
                                    <i data-lucide="palette" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-xs font-bold text-white mb-0.5 flex items-center gap-1.5" data-i18n="theme-custom-title">
                                        Tema Customizado <span id="php-custom-badge" class="hidden text-[8px] bg-sky-500/15 text-sky-400 px-1.5 py-0.2 rounded font-black uppercase tracking-wider">Ativo</span>
                                    </h4>
                                    <p class="text-[10px] text-slate-400 leading-normal" data-i18n="theme-custom-desc">Selecione uma cor customizada para todo o sistema utilizando o seletor circular.</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center w-full mt-1">
                                <input type="color" id="php-custom-color-picker" oninput="onPhpCustomColorChange(this.value)" onchange="onPhpCustomColorChange(this.value)" value="#0ea5e9" class="w-10 h-7 rounded bg-transparent border-0 cursor-pointer shrink-0" />
                                <button onclick="selectDesktopTheme('custom:' + document.getElementById('php-custom-color-picker').value)" class="flex-1 py-1.5 px-3 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white rounded-lg text-[9px] font-bold uppercase tracking-wider transition cursor-pointer text-center" data-i18n="theme-custom-select-btn">
                                    Selecionar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOM LAYOUT COLOR FOR PHP -->
                    <div class="bg-slate-950/60 border border-slate-900 rounded-2xl p-6 text-left space-y-6 mt-6">
                        <div class="space-y-1.5 border-b border-slate-900 pb-4">
                            <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5">
                                <i data-lucide="sliders" class="w-4 h-4 text-sky-400"></i> Personalizar Cor do Layout
                            </h3>
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Ajuste a cor de fundo das barras do player (Barra Lateral, Barra Superior e Barra de Reprodução). Mudar a cor aplicará o mesmo tom em todo o layout.
                            </p>
                        </div>

                        <!-- Quick Presets -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-300">Esquemas Rápidos de Cores</h4>
                            <div class="flex flex-wrap gap-2.5">
                                <button
                                    type="button"
                                    onclick="applyPhpLayoutColor('#020617')"
                                    class="px-3 py-1.5 text-[10px] uppercase tracking-wider font-bold rounded-xl border border-slate-900 bg-slate-100/5 hover:bg-slate-900 text-slate-300 cursor-pointer transition select-none"
                                >
                                    Padrão (Slate)
                                </button>
                                <button
                                    type="button"
                                    onclick="applyPhpLayoutColor('#000000')"
                                    class="px-3 py-1.5 text-[10px] uppercase tracking-wider font-bold rounded-xl border border-slate-900 bg-black hover:bg-zinc-950 text-slate-300 cursor-pointer transition select-none"
                                >
                                    Preto Absoluto
                                </button>
                                <button
                                    type="button"
                                    onclick="applyPhpLayoutColor('#09101d')"
                                    class="px-3 py-1.5 text-[10px] uppercase tracking-wider font-bold rounded-xl border border-slate-100/5 bg-[#09101d] hover:bg-[#0d1c33] text-slate-300 cursor-pointer transition select-none"
                                >
                                    Azul Naval
                                </button>
                                <button
                                    type="button"
                                    onclick="applyPhpLayoutColor('#0c0517')"
                                    class="px-3 py-1.5 text-[10px] uppercase tracking-wider font-bold rounded-xl border border-slate-100/5 bg-[#0c0517] hover:bg-[#13072b] text-slate-300 cursor-pointer transition select-none"
                                >
                                    Roxo Místico
                                </button>
                            </div>
                        </div>

                        <!-- Custom Picker -->
                        <div class="bg-slate-950/40 border border-slate-900/60 p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">Escolher Cor Customizada</h4>
                                <p class="text-[10px] text-slate-400">Arraste para selecionar a cor e clique em Salvar para aplicar permanentemente ao layout.</p>
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <input
                                    type="color"
                                    id="php-layout-bg-picker"
                                    oninput="onPhpLayoutBgLiveChange(this.value)"
                                    class="w-10 h-7 rounded bg-transparent border-0 cursor-pointer shrink-0"
                                />
                                <input
                                    type="text"
                                    id="php-layout-bg-text"
                                    value="#020617"
                                    oninput="onPhpLayoutBgLiveChange(this.value)"
                                    class="flex-1 sm:w-28 bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-center text-xs font-mono text-white"
                                />
                                <button
                                    onclick="savePhpLayoutColor()"
                                    class="py-1.5 px-4 bg-sky-500 hover:bg-sky-600 text-white rounded-lg text-xs font-black uppercase tracking-wider transition cursor-pointer select-none"
                                >
                                    Salvar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SEÇÃO DE IDIOMA / LANGUAGE SECTION -->
                    <div class="mt-8 bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="space-y-1">
                            <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5">
                                <i data-lucide="globe" class="w-4 h-4 text-sky-400"></i> <span data-i18n="lang-choose-title">Escolha o idioma do sistema</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 leading-normal" data-i18n="lang-choose-desc">Escolha entre Português, Inglês ou Espanhol para traduzir a interface do player.</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button onclick="selectDesktopLang('pt')" class="lang-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-center bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none w-full">
                            <span class="text-2xl">🇧🇷</span>
                            <div class="text-left">
                                <h4 class="text-xs font-bold text-white">Português</h4>
                                <span class="text-[9px] text-slate-400">Idioma nativo (PT-BR)</span>
                            </div>
                            <div class="ml-auto w-5 h-5 rounded-full bg-sky-500 flex items-center justify-center text-white hidden" id="indicator-lang-pt">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </div>
                        </button>
                        <button onclick="selectDesktopLang('en')" class="lang-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-center bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none w-full">
                            <span class="text-2xl">🇺🇸</span>
                            <div class="text-left">
                                <h4 class="text-xs font-bold text-white">English</h4>
                                <span class="text-[9px] text-slate-400">English version</span>
                            </div>
                            <div class="ml-auto w-5 h-5 rounded-full bg-sky-500 flex items-center justify-center text-white hidden" id="indicator-lang-en">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </div>
                        </button>
                        <button onclick="selectDesktopLang('es')" class="lang-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-center bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none w-full">
                            <span class="text-2xl">🇪🇸</span>
                            <div class="text-left">
                                <h4 class="text-xs font-bold text-white">Español</h4>
                                <span class="text-[9px] text-slate-400">Versión en español</span>
                            </div>
                            <div class="ml-auto w-5 h-5 rounded-full bg-sky-500 flex items-center justify-center text-white hidden" id="indicator-lang-es">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- SUBTAB 2: SYNCHRONIZATION AND MEDIA (Admin Only) -->
                <div id="subtab-pane-media" class="space-y-6 hidden admin-only">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Music scan -->
                        <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col justify-between gap-4 text-left">
                            <div class="space-y-1.5 font-sans">
                                <span class="bg-sky-500/10 text-sky-400 border border-sky-500/25 text-[9px] font-black uppercase px-2 py-0.5 rounded-full inline-block">Áudio Library</span>
                                <h3 class="text-sm font-bold text-white">Sincronizar Pasta /music</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Varre recursivamente o diretório no servidor para incluir novos áudios no seu catálogo.</p>
                            </div>
                            <div class="space-y-2 font-sans w-full">
                                <button onclick="runMusicDirectoryScan(this)" class="w-full py-2.5 bg-sky-500 hover:bg-sky-600 font-extrabold text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Sincronizar Músicas
                                </button>
                                <button onclick="openMusicScanLog()" class="w-full py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 font-bold text-slate-350 hover:text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Ver Log de Sincronização
                                </button>
                            </div>
                        </div>

                        <!-- Video scan -->
                        <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col justify-between gap-5 text-left">
                            <div class="space-y-1.5">
                                <span class="bg-violet-500/10 text-violet-400 border border-violet-500/25 text-[9px] font-black uppercase px-2 py-0.5 rounded-full inline-block">Vídeo Library</span>
                                <h3 class="text-sm font-bold text-white">Sincronizar Pasta /videos</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Varre recursivamente o diretório de vídeos no disco do servidor para listar arquivos .mp4 ou .mkv.</p>
                            </div>
                            <button onclick="runVideoDirectoryScan(this)" class="w-full py-2.5 bg-violet-500 hover:bg-violet-600 font-extrabold text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Sincronizar Vídeos
                            </button>
                        </div>

                        <!-- Last.fm Sync Card -->
                        <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col justify-between gap-5 text-left">
                            <div class="space-y-1.5 font-sans">
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 text-[9px] font-black uppercase px-2 py-0.5 rounded-full inline-block">Metadados Last.fm</span>
                                <h3 class="text-sm font-bold text-white">Sincronizar com Last.fm</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">
                                    Sincroniza automaticamente imagens de capa de álbuns genéricas/em branco e baixa os melhores banners de artistas da enciclopédia Last.fm global para o catálogo.
                                </p>
                            </div>
                            <button onclick="runLastfmSync(this)" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 font-extrabold text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Sincronizar Capas e Banners
                            </button>
                        </div>

                        <!-- Deezer Sync Card -->
                        <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col justify-between gap-5 text-left font-sans">
                            <div class="space-y-1.5">
                                <span class="bg-sky-500/10 text-sky-400 border border-sky-500/25 text-[9px] font-black uppercase px-2 py-0.5 rounded-full inline-block">Deezer API</span>
                                <h3 class="text-sm font-bold text-white">Sincronizar com Deezer</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">
                                    Busca e atualiza capas automaticamente em alta resolução de álbuns que estão sem capa ou têm capas genéricas no banco de dados global do Deezer. Não necessita de chave de API.
                                </p>
                            </div>
                            <button onclick="runDeezerSync(this)" class="w-full py-2.5 bg-sky-500 hover:bg-sky-600 font-extrabold text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer">
                                <i data-lucide="music" class="w-3.5 h-3.5"></i> Sincronizar pelo Deezer
                            </button>
                        </div>

                        <!-- Google Images Sync Card -->
                        <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col justify-between gap-5 text-left font-sans">
                            <div class="space-y-1.5">
                                <span class="bg-blue-500/10 text-blue-400 border border-blue-500/25 text-[9px] font-black uppercase px-2 py-0.5 rounded-full inline-block">Google Images</span>
                                <h3 class="text-sm font-bold text-white">Sincronizar com Google</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">
                                    Busca e atualiza capas de álbuns pendentes de forma inteligente diretamente no Google Images por meio de técnicas avançadas de indexação. Ideal para álbuns raros ou nacionais.
                                </p>
                            </div>
                            <button onclick="runGoogleSync(this)" class="w-full py-2.5 bg-blue-500 hover:bg-blue-600 font-extrabold text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer">
                                <i data-lucide="image" class="w-3.5 h-3.5"></i> Sincronizar pelo Google Images
                            </button>
                        </div>

                        <!-- Repair DB Card -->
                        <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col justify-between gap-5 text-left">
                            <div class="space-y-1.5">
                                <span class="bg-rose-500/10 text-rose-400 border border-rose-500/25 text-[9px] font-black uppercase px-2 py-0.5 rounded-full inline-block">Ferramenta</span>
                                <h3 class="text-sm font-bold text-white">Reparar Banco de Dados</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Corrige automaticamente problemas com nomes de arquivos ou gêneros lidos incorretamente com interrogações ou tags nulas binárias no Windows/Linux.</p>
                            </div>
                            <button onclick="runDatabaseRepair(this)" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 font-extrabold text-white rounded-xl text-xs flex items-center justify-center gap-1.5 transition">
                                <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Reparar Banco
                            </button>
                        </div>
                    </div>

                    <!-- Last.fm API Key Integration Card inside PHP config -->
                    <div class="bg-slate-900/10 border border-slate-900 rounded-2xl p-6 space-y-4 text-left mt-6 animate-fade-in">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                                <i data-lucide="sparkles" class="w-4 h-4 text-emerald-400"></i> API Key Last.fm Personalizada
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Configure uma chave de API para obter biografias e buscar capas de álbuns a partir da enciclopédia musical global Last.fm, tanto localmente quanto na hospedagem PHP MySQL.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 max-w-2xl">
                            <input
                                id="lastfm-api-key-input"
                                type="text"
                                placeholder="Insira sua Last.fm API Key (ex: 4cb074e...)"
                                class="flex-1 bg-slate-950 border border-slate-900 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 transition font-sans"
                            />
                            <button
                                onclick="saveLastfmApiKey()"
                                id="save-lastfm-key-btn"
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-650 text-white font-bold text-xs uppercase tracking-wider rounded-xl flex items-center justify-center gap-1.5 transition cursor-pointer shrink-0"
                            >
                                Salvar Chave
                            </button>
                        </div>
                    </div>

                    

                    <!-- Upload Form -->
                    <div class="bg-slate-950/50 border border-slate-900 p-6 rounded-2xl space-y-4 text-left">
                        <div class="border-b border-slate-900 pb-3">
                            <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5">
                                <i data-lucide="upload" class="w-4 h-4 text-sky-400"></i> Enviar Músicas ao Servidor
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-0.5">Envie e cadastre canções únicas instantaneamente.</p>
                        </div>
                        <form onsubmit="handleMusicUpload(event)" id="music-upload-form" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Título da Faixa</label>
                                    <input name="title" placeholder="Nome da música (opcional se possuir ID3)" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Artista Padrão</label>
                                    <input name="artist" placeholder="Ex: SoundHelix (usado como backup se sem ID3)" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Álbum Padrão</label>
                                    <input name="album" placeholder="Ex: Cosmic Volume I (usado como backup se sem ID3)" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase">Gênero Padrão</label>
                                    <input name="genre" placeholder="Ex: Instrumental (usado como backup se sem ID3)" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold text-slate-400 uppercase">Selecione Múltiplos Áudios (MP3, WAV, OGG, AAC)</label>
                                <input type="file" name="audio[]" accept=".mp3,.wav,.ogg,.aac" multiple required class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-500 file:text-white hover:file:bg-sky-600 pointer-events-auto cursor-pointer">
                                <p class="text-[10px] text-slate-500 mt-1">💡 Dica: Ao escolher múltiplos arquivos, as informações de cada música (título, artista, álbum, etc.) serão lidas automaticamente a partir de suas tags ID3 integradas.</p>
                            </div>
                            <button type="submit" id="uploader-submit-btn" class="w-full py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white text-xs font-black rounded-xl">Iniciar Upload</button>
                        </form>
                    </div>


                </div>

                <!-- SUBTAB: EDITOR DE TAGS ID3 (Admin Only) -->
                <div id="subtab-pane-id3" class="space-y-6 hidden admin-only">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-left font-sans">
                        <div class="space-y-1">
                            <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-4 h-4 text-sky-400"></i> Editor de Metadados / ID3 do Acervo
                            </h3>
                            <p class="text-[11px] text-slate-400 leading-normal">Selecione e edite as tags ID3 básicas (Título, Artista, Álbum e Gênero) de qualquer música. As edições são aplicadas diretamente no banco de dados e sincronizadas em tempo real no player.</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-slate-500"></i>
                            <input type="text" id="id3-search-input" oninput="filterId3Songs()" placeholder="Pesquisar por título, artista ou álbum..." class="w-full pl-9 pr-3 py-2 text-xs bg-slate-900 border border-slate-800 text-white placeholder-slate-500 rounded-xl outline-none focus:border-sky-500/50 transition">
                        </div>
                        <div class="px-4 py-2 bg-slate-950 border border-slate-900 rounded-xl flex items-center justify-center font-mono text-[10px] text-slate-400 tracking-wider">
                            <span id="id3-songs-count">0</span>&nbsp;Músicas encontradas
                        </div>
                    </div>

                    <!-- BULK ACTION BAR -->
                    <div id="id3-bulk-bar" class="hidden animate-fade-in bg-slate-950/80 border border-sky-500/20 p-4 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-square" class="w-4 h-4 text-sky-450"></i>
                            <span class="text-xs font-bold text-slate-300">
                                <span id="id3-selected-count-badge" class="bg-sky-500/15 text-sky-450 px-2 py-0.5 rounded font-black">0</span> 
                                músicas selecionadas
                            </span>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto font-sans">
                            <button onclick="openId3BulkModal()" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-sky-500 to-indigo-650 text-[10px] text-white font-black px-4 py-2 rounded-xl transition shadow-lg shadow-sky-500/10 cursor-pointer hover:brightness-110 active:scale-95 select-none">
                                <i data-lucide="layers" class="w-3.5 h-3.5"></i> Alteração em Massa
                            </button>
                            <button onclick="clearId3Selection()" class="flex-1 sm:flex-none text-[11px] font-bold text-slate-400 hover:text-white px-3 py-2 rounded-xl transition cursor-pointer select-none">
                                Limpar
                            </button>
                        </div>
                    </div>

                    <!-- ID3 Songs List scroll container -->
                    <div class="bg-slate-950/40 border border-slate-900/60 rounded-2xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-900/80 text-[10px] uppercase font-black tracking-widest text-slate-500 select-none bg-[#0a111e]/15">
                                        <th class="py-3 px-4 w-10 text-center">
                                            <input type="checkbox" id="id3-select-all" onchange="toggleSelectAllId3(this)" class="w-3.5 h-3.5 text-sky-500 bg-slate-900 border-slate-800 rounded focus:ring-sky-500 cursor-pointer">
                                        </th>
                                        <th class="py-3 px-4">#</th>
                                        <th class="py-3 px-4">Título</th>
                                        <th class="py-3 px-4">Artista</th>
                                        <th class="py-3 px-4">Álbum</th>
                                        <th class="py-3 px-4">Ano</th>
                                        <th class="py-3 px-4">Gênero</th>
                                        <th class="py-3 px-4 text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="id3-songs-table-body" class="divide-y divide-slate-900/40">
                                    <tr>
                                        <td colspan="8" class="py-8 text-center text-slate-500 italic">Carregando acervo musical...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 3: USER MANAGEMENT (Admin Only) -->
                <div id="subtab-pane-users" class="grid grid-cols-1 lg:grid-cols-3 gap-6 hidden admin-only">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl space-y-4 text-left">
                        <h3 class="text-xs font-black uppercase text-white">Criar Novo Usuário</h3>
                        <form onsubmit="handleCreateUser(event)" class="space-y-3">
                            <input id="new-user-name" placeholder="Nome de login" required class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none">
                            <input id="new-user-pass" type="password" placeholder="Senha secreta" required class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none">
                            <select id="new-user-role" class="w-full bg-slate-900 border border-slate-800 text-slate-300 p-2.5 text-xs rounded-xl outline-none">
                                <option value="ouvinte">Ouvinte (Apenas escuta)</option>
                                <option value="admin">Administrador (Total)</option>
                            </select>
                            <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white rounded-xl text-xs font-extrabold cursor-pointer select-none">Criar Usuário</button>
                        </form>
                    </div>

                    <div class="lg:col-span-2 bg-slate-950/50 border border-slate-900 rounded-2xl overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-900/50 text-slate-500 border-b border-slate-900 uppercase font-mono tracking-widest text-[9px]">
                                <tr>
                                    <th class="py-3 px-4">Usuário</th>
                                    <th class="py-3 px-4">Nível</th>
                                    <th class="py-3 px-4 text-right w-20">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body" class="divide-y divide-slate-900/50"></tbody>
                        </table>
                    </div>
                </div>

                
                <!-- SUBTAB 6: ATUALIZAÇÕES -->
                <div id="subtab-pane-updates" class="space-y-6 hidden admin-only font-sans">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl max-w-2xl text-left">
                        <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5 mb-1">
                            <i data-lucide="refresh-cw" class="w-4 h-4 text-sky-400"></i> Atualização do Sistema
                        </h3>
                        <p class="text-xs text-slate-400 leading-normal mb-5">Verifique se existe uma nova versão do PHPlayer disponível no GitHub.</p>
                        
                        <div class="bg-slate-900/40 border border-slate-800 rounded-xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block mb-1">Status Atual</span>
                                    <div id="update-status-badge" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 border border-slate-800 text-slate-300 rounded-lg text-[11px] font-bold">
                                        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i> Desconhecido
                                    </div>
                                </div>
                                <button onclick="checkPhpUpdates()" id="btn-check-updates" class="bg-gradient-to-r from-sky-500 to-indigo-600 text-white hover:opacity-90 font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer shadow-lg shadow-sky-500/10 flex items-center gap-1.5">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i> Verificar Atualização
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 border-t border-slate-900/60 pt-4">
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-slate-500 tracking-wider block mb-1">Sua Versão</span>
                                    <div id="current-version-label" class="text-xl font-black text-white font-mono">--</div>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase font-bold text-slate-500 tracking-wider block mb-1">Versão Disponível</span>
                                    <div id="remote-version-label" class="text-xl font-black text-sky-400 font-mono">--</div>
                                </div>
                            </div>
                        </div>

                        <div id="changelog-container" class="mt-5 hidden">
                            <h4 class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-2 flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-400"></i> O que há de novo (Changelog)
                            </h4>
                            <pre id="changelog-content" class="bg-slate-950 border border-slate-900 p-4 rounded-xl text-[11px] text-slate-300 font-mono whitespace-pre-wrap leading-relaxed max-h-64 overflow-y-auto custom-scroll shadow-inner">
                            </pre>
                            
                            <div id="download-update-wrapper" class="mt-4 hidden">
                                <a href="https://github.com/leirson/phplayer" target="_blank" class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-black rounded-xl text-xs uppercase tracking-wider transition shadow-lg shadow-emerald-500/20">
                                    <i data-lucide="download-cloud" class="w-4 h-4"></i> Baixar Nova Versão do GitHub
                                </a>
                                <p class="text-[10px] text-center text-slate-500 mt-2 font-bold uppercase tracking-wider">Lembre-se de fazer backup antes de atualizar.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 4: CHANGE MY PASSWORD (Visible to all users) -->
                <div 
                <!-- SUBTAB 5: SHARES & DASHBOARD (Admin only) -->
                <div id="subtab-pane-dashboard_cfg" class="space-y-6 hidden">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl max-w-md text-left">
                        <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5 mb-4">
                            <i data-lucide="layout-grid" class="w-4 h-4 text-sky-400"></i>
                            Configuração do Dashboard
                        </h3>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Qtd. de Álbuns Aleatórios/Recentes</label>
                        <div class="flex gap-2 mb-3">
                           <input type="number" id="dashboard-albums-count" placeholder="12" class="w-full bg-slate-900 border border-slate-800 text-white text-sm rounded-lg py-2 px-3 focus:outline-none focus:border-sky-500 transition">
                        </div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Tempo de Atualização (Segundos)</label>
                        <div class="flex gap-2">
                           <input type="number" id="dashboard-rotate-time" placeholder="8" class="w-full bg-slate-900 border border-slate-800 text-white text-sm rounded-lg py-2 px-3 focus:outline-none focus:border-sky-500 transition">
                           <button onclick="saveDashboardSettings()" class="bg-sky-500 hover:bg-sky-600 focus:scale-95 text-white px-4 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap">Salvar Todos</button>
                        </div>
                    </div>
                </div>
<div id="subtab-pane-shares" class="space-y-6 hidden">
                    <div class="bg-slate-950/50 border border-slate-900 overflow-hidden rounded-2xl text-left">
                        <h3 class="p-4 text-xs font-black uppercase text-white bg-slate-900 flex items-center gap-1.5">
                            <i data-lucide="share-2" class="w-4 h-4 text-sky-400"></i>
                            Links Compartilhados
                        </h3>
                        <div class="w-full overflow-x-auto min-h-[100px] custom-scroll">
                            <table class="w-full text-left text-xs text-slate-300 whitespace-nowrap">
                                <thead>
                                    <tr class="border-b border-slate-900/60 text-slate-500 font-mono tracking-wider text-[9px] uppercase">
                                        <th class="py-2 px-4">Alvo</th>
                                        <th class="py-2 px-4">Link</th>
                                        <th class="py-2 px-4 text-right w-12">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="shares-table-body" class="divide-y divide-slate-900/40">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

<div id="subtab-pane-password" class="space-y-6 hidden">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl max-w-md text-left">
                        <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5 mb-1.5">
                            <i data-lucide="lock" class="w-4 h-4 text-sky-400"></i> Alterar Minha Senha de Acesso
                        </h3>
                        <p class="text-xs text-slate-400 leading-normal mb-4">Insira abaixo a sua nova senha. Ela será atualizada instantaneamente e salva de forma criptografada no banco de dados.</p>
                        
                        <form onsubmit="handleMyPasswordChange(event)" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Nova Senha Secreta</label>
                                <input id="my-new-password" type="password" required placeholder="Digite a nova senha de acesso" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none focus:border-sky-500">
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white rounded-xl text-xs font-bold cursor-pointer select-none active:scale-95 transition">
                                Salvar Nova Senha
                            </button>
                        </form>
                    </div>
                </div>

                <!-- SUBTAB: KEYBOARD SHORTCUTS (Visible to all users) -->
                <div id="subtab-pane-shortcuts" class="space-y-6 hidden text-left">
                    <div class="bg-slate-950/60 border border-slate-900 rounded-2xl p-5 space-y-2">
                        <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                            <i data-lucide="keyboard" class="w-4 h-4 text-sky-400"></i> Atalhos de Teclado
                        </h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            O PHPlayer oferece atalhos de teclado globais para gerenciar sua reprodução de forma rápida, eficiente e sem tirar as mãos do teclado. Os atalhos funcionam de qualquer view da aplicação (exceto quando você estiver digitando em campos de texto).
                        </p>
                    </div>

                    <div class="bg-slate-950/20 border border-slate-900 rounded-2xl p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <!-- Shortcut 1 -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/40 border border-slate-900 hover:border-slate-800/80 transition-all">
                                <div class="space-y-1 pr-4">
                                    <span class="text-xs font-bold text-slate-200 block">Pesquisa Rápida</span>
                                    <span class="text-[11px] text-slate-400 block leading-relaxed">
                                        Foca instantaneamente a barra de busca de áudio ou vídeo. Se não estiver em uma aba compatível, o PHPlayer navega automaticamente para a biblioteca de músicas para efetuar a pesquisa.
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40">Ctrl</kbd>
                                    <span class="text-xs text-slate-500 font-bold font-sans">+</span>
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">F</kbd>
                                    <span class="text-xs text-slate-500 font-bold font-sans">ou</span>
                                    <kbd class="px-2.5 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">/</kbd>
                                </div>
                            </div>

                            <!-- Play/Pause Space shortcut -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/40 border border-slate-900 hover:border-slate-800/80 transition-all">
                                <div class="space-y-1 pr-4">
                                    <span class="text-xs font-bold text-slate-200 block">Reproduzir / Pausar</span>
                                    <span class="text-[11px] text-slate-400 block leading-relaxed">
                                        Alterna instantaneamente o estado de reprodução (reproduzindo ou pausado) do áudio ou música ativa.
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <kbd class="px-4 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">Espaço</kbd>
                                </div>
                            </div>

                            <!-- Shortcut 2 -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/40 border border-slate-900 hover:border-slate-800/80 transition-all">
                                <div class="space-y-1 pr-4">
                                    <span class="text-xs font-bold text-slate-200 block">Próxima Música</span>
                                    <span class="text-[11px] text-slate-400 block leading-relaxed">
                                        Avança para a próxima faixa na fila de escuta ativa.
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">Seta Direita</kbd>
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10.5px] font-bold shadow-md shadow-black/40 font-semibold">&rarr;</kbd>
                                </div>
                            </div>

                            <!-- Shortcut 3 -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/40 border border-slate-900 hover:border-slate-800/80 transition-all">
                                <div class="space-y-1 pr-4">
                                    <span class="text-xs font-bold text-slate-200 block">Música Anterior</span>
                                    <span class="text-[11px] text-slate-400 block leading-relaxed">
                                        Retorna ao início da música atual ou reproduz a faixa anterior na fila de escuta ativa.
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">Seta Esquerda</kbd>
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10.5px] font-bold shadow-md shadow-black/40 font-semibold">&larr;</kbd>
                                </div>
                            </div>

                            <!-- Shortcut 4 -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/40 border border-slate-900 hover:border-slate-800/80 transition-all">
                                <div class="space-y-1 pr-4">
                                    <span class="text-xs font-bold text-slate-200 block">Aumentar Volume</span>
                                    <span class="text-[11px] text-slate-400 block leading-relaxed">
                                        Aumenta o volume atual do reprodutor de mídia em 5% a cada acionamento.
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">+</kbd>
                                    <span class="text-xs text-slate-500 font-bold font-sans">ou</span>
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">=</kbd>
                                </div>
                            </div>

                            <!-- Shortcut 5 -->
                            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/40 border border-slate-900 hover:border-slate-800/80 transition-all">
                                <div class="space-y-1 pr-4">
                                    <span class="text-xs font-bold text-slate-200 block">Diminuir Volume</span>
                                    <span class="text-[11px] text-slate-400 block leading-relaxed">
                                        Diminui o volume atual do reprodutor em 5% e entra em modo mudo automaticamente se chegar a 0%.
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">-</kbd>
                                    <span class="text-xs text-slate-500 font-bold font-sans">ou</span>
                                    <kbd class="px-2 py-1 bg-slate-950 text-slate-300 border border-slate-800 rounded-md font-mono text-[10px] font-bold shadow-md shadow-black/40 font-semibold">_</kbd>
                                </div>
                            </div>

                        </div>

                        <!-- Note alert -->
                        <div class="p-4 bg-slate-900/25 border border-slate-900 rounded-xl space-y-1 text-slate-400 text-xs text-left">
                            <span class="text-slate-300 font-bold font-sans block flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 bg-sky-500 rounded-full"></span> Nota de Uso do Teclado:
                            </span>
                            <p class="leading-relaxed text-[11px]">
                                Os atalhos de teclado foram desenvolvidos de forma inteligente para que não interfiram nas suas atividades comuns. Sempre que você estiver focado em um campo de cadastro, enviando um arquivo, comentando ou configurando parâmetros nos menus, as funções do player são pausadas temporariamente para evitar ações acidentais.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 5: FILE MANAGER (Admin Only) -->
                <div id="subtab-pane-files" class="space-y-6 hidden admin-only">
                    <div class="bg-slate-950/50 border border-slate-900 p-5 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="space-y-1">
                            <h3 class="text-xs font-black uppercase text-white flex items-center gap-1.5">
                                <i data-lucide="folder-open" class="w-4 h-4 text-sky-450"></i> Gerenciador de Arquivos do Servidor
                            </h3>
                            <p class="text-[11px] text-slate-400 leading-normal">
                                Navegue pelas pastas do player, crie diretórios, exclua e envie arquivos de música (<span class="font-mono text-sky-400">.mp3</span>) ou vídeo (<span class="font-mono text-sky-400">.mp4</span>) diretamente sob o servidor.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="file-manager-btn-new-folder" onclick="openNewFolderModal()" class="px-4 py-2 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white font-extrabold text-xs rounded-xl flex items-center gap-1.5 transition select-none cursor-pointer">
                                <i data-lucide="folder-plus" class="w-4 h-4 text-emerald-400"></i> Nova Pasta
                            </button>
                            <button id="file-manager-btn-upload" onclick="document.getElementById('file-manager-upload-input').click()" class="px-4 py-2 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-extrabold text-xs rounded-xl flex items-center gap-1.5 transition select-none cursor-pointer shadow-lg shadow-sky-500/10">
                                <i data-lucide="upload" class="w-4 h-4"></i> Enviar Arquivos
                            </button>
                            <input type="file" id="file-manager-upload-input" class="hidden" multiple onchange="handleFileManagerUpload(this.files)">
                        </div>
                    </div>

                    <!-- Breadcrumbs & Info -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-slate-950/20 border border-slate-900/60 p-3 rounded-xl">
                        <div id="file-manager-breadcrumbs" class="flex items-center gap-1.5 text-xs text-slate-450 font-medium overflow-x-auto py-1"></div>
                        <div class="text-[10px] font-mono text-slate-500 text-right" id="file-manager-info"></div>
                    </div>

                    <!-- Drag & Drop Upload Zone -->
                    <div id="file-manager-dragzone" class="relative border-2 border-dashed border-slate-900 hover:border-sky-500/45 bg-slate-950/15 rounded-2xl min-h-[300px] transition duration-200" ondragover="handleFileDragOver(event)" ondragleave="handleFileDragLeave(event)" ondrop="handleFileDrop(event)">
                        <!-- Upload Progress Panel -->
                        <div id="file-manager-upload-progress" class="hidden p-4 border-b border-slate-900 bg-slate-950/60 rounded-t-2xl space-y-2">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-300 font-bold" id="upload-progress-text">Enviando arquivos...</span>
                                <span class="font-mono text-sky-400" id="upload-progress-percent">0%</span>
                            </div>
                            <div class="w-full bg-slate-900 h-1.5 rounded-full overflow-hidden">
                                <div id="upload-progress-bar" class="bg-gradient-to-r from-sky-400 to-indigo-500 h-full w-0 transition-all duration-150"></div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-900 text-[10px] font-black uppercase text-slate-500 tracking-wider">
                                        <th class="p-4 pl-5">Nome</th>
                                        <th class="p-4 hidden sm:table-cell">Tamanho</th>
                                        <th class="p-4 hidden md:table-cell">Modificado</th>
                                        <th class="p-4 text-right pr-5">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="file-manager-table-body" class="divide-y divide-slate-900/40 text-xs text-slate-350"></tbody>
                            </table>
                        </div>

                        <!-- Drag overlay hint -->
                        <div id="file-manager-drag-overlay" class="absolute inset-0 bg-slate-950/90 backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center gap-3 pointer-events-none opacity-0 transition duration-200">
                            <i data-lucide="cloud-upload" class="w-12 h-12 text-sky-400 animate-bounce"></i>
                            <p class="text-xs font-bold text-white">Solte os arquivos para enviá-los aqui</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- VIEW: VIDEO GALLERY -->
            <section id="pane-videos" class="space-y-6 flex-1 hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-900 pb-5">
                    <div>
                        <h2 class="text-xl font-black text-white flex items-center gap-2">
                            <i data-lucide="film" class="text-sky-400 w-5 h-5"></i> Galeria de Vídeos
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Coleção de arquivos de vídeo localizados sob /videos
                        </p>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-72">
                        <input id="video-search-input" oninput="renderVideoGallery()" type="text" placeholder="Pesquisar vídeos..." class="w-full bg-slate-900 border border-slate-800 focus:border-sky-500 rounded-xl pl-9 pr-4 py-2.5 text-xs text-white outline-none font-semibold">
                        <i data-lucide="search" class="absolute left-3 top-3 w-4 h-4 text-slate-500 flex items-center justify-center pointer-events-none"></i>
                    </div>
                </div>

                <div id="video-loading" class="py-24 text-center text-xs text-slate-500 flex flex-col items-center gap-2">
                    <i data-lucide="loader" class="w-6 h-6 animate-spin text-sky-450"></i>
                    Escaneando pasta /videos no servidor de mídia...
                </div>

                <div id="video-empty" class="py-24 text-center text-xs text-slate-500 italic bg-slate-950/20 border border-slate-900 border-dashed rounded-3xl hidden">
                    Nenhum arquivo de vídeo encontrado. Toque alguns vídeos (.mp4, .webm, .mkv, .mov, .avi) na pasta /videos do sistema para listá-los.
                </div>

                <div id="video-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"></div>
            </section>

            <!-- VIEW: PLAYLISTS -->
            <section id="pane-playlists" class="space-y-6 flex-1 hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-900 pb-5">
                    <div>
                        <h2 class="text-xl font-black text-white flex items-center gap-2">
                            <i data-lucide="list-music" class="text-emerald-400 w-5 h-5"></i> Minhas Playlists
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Gerencie e ouça suas seleções musicais personalizadas
                        </p>
                    </div>

                    <button onclick="createPlay()" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-black uppercase tracking-wider transition active:scale-95 flex items-center gap-1.5 shadow-lg shadow-emerald-500/10 cursor-pointer">
                        <i data-lucide="plus" class="w-4 h-4"></i> Criar Playlist
                    </button>
                </div>

                <div id="playlists-empty" class="py-24 text-center text-xs text-slate-500 italic bg-slate-950/20 border border-slate-900 border-dashed rounded-3xl hidden">
                    Nenhuma playlist criada ainda. Clique no botão acima para criar sua primeira playlist!
                </div>

                <div id="playlists-grid" class="grid grid-cols-1 md:grid-cols-3 gap-6"></div>
            </section>

            <!-- VIEW: PODCASTS -->
            <section id="pane-podcast" class="space-y-6 flex-1 hidden">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gradient-to-r from-slate-950 via-[#0b101b] to-slate-950 p-6 rounded-3xl border border-slate-900/50 shadow-xl">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-orange-500/10 rounded-2xl text-orange-400">
                            <i data-lucide="radio" class="w-8 h-8 animate-pulse"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                                Sincronizador de Podcasts
                            </h2>
                            <p class="text-xs text-slate-500 mt-1 max-w-md">
                                Insira o endereço RSS para sincronizar e baixar os últimos 5 episódios automaticamente. Cada podcast será organizado como um Álbum na sua área.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-950/40 p-6 rounded-3xl border border-slate-900 shadow-md">
                    <h3 class="text-xs font-black text-white tracking-widest uppercase mb-4 text-orange-400">Sincronizar Nova Fonte</h3>
                    
                    <div class="admin-only space-y-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input
                                id="podcast-feed-input"
                                type="text"
                                placeholder="https://exemplo.com/podcast/rss"
                                class="flex-1 bg-slate-900 border border-slate-800 focus:border-orange-500 rounded-xl px-4 py-3 text-xs outline-none transition font-semibold text-white placeholder-slate-500"
                            />
                            <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 shrink-0">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-wider whitespace-nowrap">Máx. episódios:</span>
                                <select id="podcast-max-episodes" class="bg-transparent text-xs font-bold text-white outline-none">
                                    <option value="3" class="bg-slate-950 text-white">3</option>
                                    <option value="5" selected class="bg-slate-950 text-white">5</option>
                                    <option value="10" class="bg-slate-950 text-white">10</option>
                                    <option value="20" class="bg-slate-950 text-white">20</option>
                                    <option value="50" class="bg-slate-950 text-white">50</option>
                                </select>
                            </div>
                            <button
                                id="btn-sync-podcast"
                                onclick="runPodcastSync()"
                                class="bg-gradient-to-r from-orange-500 to-amber-600 font-black hover:opacity-90 text-white rounded-xl px-6 py-3 text-xs tracking-wide transition flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-orange-500/5 shrink-0"
                            >
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sincronizar Feed
                            </button>
                        </div>

                        <!-- Presets -->
                        <div class="flex flex-wrap items-center gap-2 pt-1 text-xs" id="desktop-podcast-suggestions">
                            <span class="text-slate-505 font-bold text-[11px] text-slate-400">Sugestões (Podcast Addict):</span>
                        </div>
                    </div>

                    <div id="podcast-status-msg" class="mt-4 p-4 rounded-xl text-xs flex items-start gap-2.5 transition border hidden"></div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-black text-white tracking-widest uppercase text-slate-400 pl-1">Biblioteca de Podcasts</h3>
                    <div id="podcasts-loading" class="py-12 text-center text-xs text-slate-500 flex flex-col items-center gap-2">
                        <i data-lucide="loader" class="w-6 h-6 animate-spin text-orange-500"></i>
                        Carregando biblioteca...
                    </div>
                    <div id="podcasts-empty" class="text-center py-12 bg-slate-950/20 border border-slate-900 border-dashed rounded-3xl space-y-3 hidden">
                        <i data-lucide="headphones" class="w-12 h-12 text-slate-700 mx-auto"></i>
                        <p class="text-xs text-slate-500">Nenhum canal de Podcast sincronizado ainda.</p>
                    </div>
                    <div id="podcasts-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
                </div>

                <div id="podcast-details" class="bg-slate-950/65 p-6 rounded-3xl border border-slate-900 shadow-xl space-y-6 hidden">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-900 pb-5">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-slate-950 overflow-hidden shrink-0 border border-slate-900">
                                <img id="pod-detail-img" src="" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <span class="text-[9px] bg-orange-500/15 text-orange-400 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Podcast Ativo</span>
                                <h3 id="pod-detail-name" class="text-lg font-black text-white mt-1">Podcast</h3>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="admin-only flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 shrink-0">
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-wider whitespace-nowrap">Máx. episódios:</span>
                                <select id="pod-detail-limit" class="bg-transparent text-xs font-bold text-white outline-none">
                                    <option value="3" class="bg-slate-950 text-white">3</option>
                                    <option value="5" class="bg-slate-950 text-white">5</option>
                                    <option value="10" class="bg-slate-950 text-white">10</option>
                                    <option value="20" class="bg-slate-950 text-white">20</option>
                                    <option value="50" class="bg-slate-950 text-white">50</option>
                                </select>
                            </div>
                            <button id="pod-detail-update-btn" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-orange-400 hover:text-orange-300 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Atualizar
                            </button>
                            <button id="pod-play-all-btn" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-black text-xs rounded-xl transition flex items-center gap-2 cursor-pointer shadow-lg shadow-orange-500/10">
                                <i data-lucide="play" class="w-4 h-4 fill-white text-white"></i> Tocar em Ordem
                            </button>
                        </div>
                    </div>
                    <div id="pod-episodes-list" class="space-y-2.5"></div>
                </div>
            </section>

            <!-- VIEW: RADIOS -->
            <section id="pane-radios" class="space-y-6 flex-1 hidden">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gradient-to-r from-slate-950 via-[#0b101b] to-slate-950 p-6 rounded-3xl border border-slate-900/50 shadow-xl">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-500/10 rounded-2xl text-emerald-400">
                            <i data-lucide="radio" class="w-8 h-8 animate-pulse"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                                Estações de Rádio On-line
                            </h2>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm sm:max-w-md">
                                Configure e ouça suas transmissões de rádio favoritas. Aceitamos URLs de playlists como <span class="font-mono text-slate-400">.m3u</span>, <span class="font-mono text-slate-400 font-bold">.pls</span> e <span class="font-mono text-slate-400">.asx</span> que resolveremos de forma transparente.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="admin-only bg-slate-950/40 p-6 rounded-3xl border border-slate-900 shadow-md">
                    <h3 class="text-xs font-black text-white tracking-widest uppercase mb-4 text-emerald-400 flex items-center gap-1.5">
                        <i data-lucide="plus" class="w-4 h-4"></i> Cadastrar Nova Emissora
                    </h3>
                    
                    <form onsubmit="handleAddRadioPhp(event)" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block pl-1">Nome da Rádio</label>
                                <input
                                    id="radio-name-input"
                                    type="text"
                                    required
                                    placeholder="Ex: Antena 1, Jovem Pan..."
                                    class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl px-4 py-3 text-xs outline-none transition font-semibold text-white placeholder-slate-500"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block pl-1">Endereço (M3U, PLS, ASX ou Stream Direto)</label>
                                <input
                                    id="radio-url-input"
                                    type="text"
                                    required
                                    placeholder="https://exemplo.com/fluxo.m3u"
                                    class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl px-4 py-3 text-xs outline-none transition font-semibold text-white placeholder-slate-500"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-2">
                            <!-- Presets -->
                            <div class="flex flex-wrap items-center gap-2 text-xs" id="desktop-radio-suggestions">
                                <span class="font-bold text-[11px] text-slate-400">Sugestões (radios.com.br):</span>
                            </div>
                            <button
                                type="submit"
                                id="btn-add-radio"
                                class="bg-gradient-to-r from-emerald-500 to-teal-600 font-extrabold hover:opacity-90 text-white rounded-xl px-6 py-3 text-xs tracking-wide transition flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-emerald-500/5 border border-transparent shrink-0"
                            >
                                <i data-lucide="check" class="w-4 h-4"></i> Cadastrar Rádio
                            </button>
                        </div>
                    </form>

                    <div id="radio-status-msg" class="mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 transition border hidden"></div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-black text-white tracking-widest uppercase text-slate-400 pl-1">Emissoras Disponíveis</h3>
                    
                    <div id="radios-loading" class="py-12 text-center text-xs text-slate-500 flex flex-col items-center gap-2">
                        <i data-lucide="loader" class="w-6 h-6 animate-spin text-emerald-500"></i>
                        Carregando emissoras...
                    </div>
                    
                    <div id="radios-empty" class="text-center py-12 bg-slate-950/20 border border-slate-900 border-dashed rounded-3xl space-y-3 hidden">
                        <i data-lucide="radio" class="w-12 h-12 text-slate-700 mx-auto"></i>
                        <p class="text-xs text-slate-500">Nenhuma rádio sintonizada ainda pela administração.</p>
                    </div>
                    
                    <div id="radios-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
                </div>
            </section>

            <!-- VIEW: REPRODUTOR -->
            <section id="pane-reprodutor" class="space-y-6 flex-1 hidden text-left font-sans">
                <!-- EMPTY STATE (WHEN NO MUSIC RUNNING) -->
                <div id="reprodutor-empty-state" class="flex flex-col items-center justify-center py-20 bg-slate-950/20 border border-slate-900 rounded-3xl p-10 text-center space-y-4 max-w-lg mx-auto">
                    <div class="w-16 h-16 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400">
                        <i data-lucide="music" class="w-8 h-8 animate-bounce text-sky-450"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-white">Nenhuma música em execução</h3>
                    <p class="text-xs text-slate-500 max-w-sm">
                        Selecione uma faixa da sua biblioteca de áudio ou do painel de controle e clique em reproduzir para carregar o reprodutor móvel.
                    </p>
                </div>

                <!-- ACTIVE REPRODUCING STATE -->
                <div id="reprodutor-active-state" class="w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 items-start text-left hidden">
                    <!-- COLUMN 1: VINYL AND PROGRESS -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="bg-[#0c1322]/80 border border-slate-900/80 p-6 md:p-8 rounded-3xl flex flex-col items-center justify-between shadow-2xl relative overflow-hidden">
                            <!-- Top Info Header Row -->
                            <div class="flex items-center justify-between w-full pb-4 border-b border-slate-900">
                                <div>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase bg-emerald-500/10 text-emerald-400 px-2.5 py-1 rounded-full border border-emerald-500/10">
                                        <i data-lucide="check" class="w-2.5 h-2.5"></i> Salvo Local
                                    </span>
                                </div>
                                <div class="text-center">
                                    <p class="text-[9px] text-slate-505 uppercase tracking-widest font-black">REPRODUTOR PHPLAYER</p>
                                    <p id="reprodutor-album" class="text-[10px] text-sky-450 font-bold max-w-[150px] truncate mt-0.5">Álbum</p>
                                </div>
                                <button id="reprodutor-fav-btn" onclick="toggleReprodutorFavorite(event)" class="p-2 bg-slate-900 hover:bg-slate-850 rounded-xl transition cursor-pointer border border-slate-850 text-slate-400 hover:text-white">
                                    <i data-lucide="heart" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Big Rotating Vinyl Area -->
                            <div class="flex flex-col items-center justify-center py-6">
                                <div class="relative w-56 h-56 sm:w-64 sm:h-64 aspect-square rounded-full bg-slate-950 flex items-center justify-center shadow-2xl border-4 border-slate-900 overflow-hidden group select-none">
                                    <div class="absolute inset-1 rounded-full border border-slate-800/10"></div>
                                    <div class="absolute inset-4 rounded-full border border-slate-800/20"></div>
                                    <div class="absolute inset-8 rounded-full border border-slate-800/30"></div>
                                    <div class="absolute inset-12 rounded-full border border-slate-800/40"></div>
                                    <div class="absolute inset-16 rounded-full border border-slate-800/50"></div>
                                    
                                    <div class="w-[50%] h-[50%] rounded-full overflow-hidden z-10 border-2 border-slate-950 shadow-md">
                                        <img id="reprodutor-cover" src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=300" class="w-full h-full object-cover rounded-full" referrerPolicy="no-referrer" />
                                    </div>

                                    <div class="absolute w-4 h-4 bg-slate-900 border-2 border-slate-950 rounded-full z-20"></div>
                                </div>

                                <div class="text-center mt-6 w-full max-w-sm space-y-1">
                                    <h3 id="reprodutor-title" class="text-base font-extrabold text-white truncate max-w-xs mx-auto">Título</h3>
                                    <p id="reprodutor-artist" class="text-xs text-sky-400 font-bold truncate max-w-xs mx-auto">Artista</p>
                                    <div class="flex items-center justify-center pt-2">
                                        <span id="reprodutor-genre" class="text-[8px] font-black tracking-widest bg-slate-900/40 border border-slate-850 px-2.5 py-0.5 rounded-full text-slate-500 uppercase">GÊNERO</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Playback progress bar -->
                            <div class="w-full space-y-2 mt-4">
                                <input id="reprodutor-seek" oninput="seek(this.value)" type="range" min="0" value="0" step="0.5" class="w-full accent-sky-400 bg-slate-900 border border-slate-850 h-1.5 rounded-lg outline-none cursor-pointer">
                                <div class="flex items-center justify-between text-[10px] text-slate-500 font-mono">
                                    <span id="reprodutor-current-time">0:00</span>
                                    <span id="reprodutor-duration">0:00</span>
                                </div>
                            </div>

                            <!-- Buttons Panel (Shuffle, Skip, Play, Next, Repeat) -->
                            <div class="w-full flex items-center justify-between px-1 md:px-4 mt-6">
                                <button id="reprodutor-shuffle-btn" onclick="toggleShuffle()" class="p-2.5 rounded-xl border transition cursor-pointer bg-slate-950 border-transparent text-slate-500 hover:text-slate-350">
                                    <i data-lucide="shuffle" class="w-4 h-4"></i>
                                </button>

                                <button onclick="prev()" class="p-3 bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-300 hover:text-white rounded-2xl transition cursor-pointer">
                                    <i data-lucide="skip-back" class="w-5 h-5 fill-current"></i>
                                </button>

                                <button id="reprodutor-master-play-btn" onclick="togglePlay()" class="p-5.5 bg-gradient-to-tr from-sky-500 to-indigo-605 text-white rounded-full transition shadow-xl shadow-sky-500/10 transform active:scale-95 cursor-pointer">
                                    <i data-lucide="play" class="w-6 h-6 fill-current ml-0.5"></i>
                                </button>

                                <button onclick="next()" class="p-3 bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-300 hover:text-white rounded-2xl transition cursor-pointer">
                                    <i data-lucide="skip-forward" class="w-5 h-5 fill-current"></i>
                                </button>

                                <button id="reprodutor-loop-btn" onclick="toggleLoop()" class="p-2.5 rounded-xl border transition cursor-pointer bg-slate-950 border-transparent text-slate-500 hover:text-slate-350">
                                    <i data-lucide="repeat" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Party Mode Switch -->
                            <div class="flex items-center justify-between bg-slate-900/30 border border-slate-900 rounded-2xl p-4 mt-4 w-full">
                                <div class="space-y-0.5 text-left">
                                    <span class="text-xs font-extrabold text-white flex items-center gap-1.5 leading-none">
                                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-pink-400"></i> Modo Festa (Party Mode)
                                    </span>
                                    <p class="text-[10px] text-slate-500 leading-normal">Desativa a pausa manual e os controles normais de navegação!</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer select-none shrink-0 ml-3">
                                    <input id="reprodutor-party-checkbox" type="checkbox" onchange="togglePartyModeFromReprodutor(this)" class="sr-only peer" />
                                    <div class="w-9 h-5 bg-slate-950 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-550 after:border-slate-500 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-pink-500 peer-checked:after:bg-white border border-slate-850"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Queue Panel below -->
                        <div class="bg-[#090e18] border border-slate-900 p-5 rounded-2xl text-left space-y-3 flex flex-col h-72">
                            <div class="flex items-center justify-between border-b border-slate-900 pb-2 shrink-0">
                                <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Fila de Reprodução</h4>
                                <span id="reprodutor-queue-count" class="text-[10px] text-sky-400 font-bold tracking-tight">0 de 0 fatias</span>
                            </div>
                            <div id="reprodutor-queue-list" class="flex-1 overflow-y-auto pr-1 space-y-1.5 custom-scroll text-slate-300">
                                <!-- Queued songs render here dynamically -->
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN 2: AJUSTES ADICIONAIS, EQUALIZER AND VISUALIZER -->
                    <div class="lg:col-span-4 space-y-4">
                        <!-- TIMERS AND TRANSITIONS (Ajustes Adicionais) -->
                        <div class="bg-[#090e18] border border-slate-900 p-5 rounded-2xl text-left space-y-4">
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Ajustes Adicionais</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <!-- Sleep Timer select inside php -->
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-sky-400"></i> Temporizador (Sleep Timer)
                                    </label>
                                    <select id="reprodutor-sleep-timer-select" onchange="changeReprodutorSleepTimer(this.value)" class="w-full bg-slate-950 border border-slate-900 rounded-xl py-2 px-3 text-xs text-white outline-none cursor-pointer">
                                        <option value="0">Desativado</option>
                                        <option value="5">5 minutos</option>
                                        <option value="15">15 minutos</option>
                                        <option value="30">30 minutos</option>
                                        <option value="60">1 hora</option>
                                    </select>
                                </div>

                                <!-- Crossfade Duration selector -->
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                        <i data-lucide="layers" class="w-3.5 h-3.5 text-sky-450"></i> Transição Suave (Crossfade)
                                    </label>
                                    <select id="reprodutor-crossfade-select" onchange="changeReprodutorCrossfade(this.value)" class="w-full bg-slate-950 border border-slate-900 rounded-xl py-2 px-3 text-xs text-white outline-none cursor-pointer">
                                        <option value="0">Sem transição (Corte seco)</option>
                                        <option value="2">2 segundos</option>
                                        <option value="4">4 segundos</option>
                                        <option value="7">7 segundos</option>
                                        <option value="10">10 segundos</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        
                </div>
            </section>
        </main>
    </div>

    <!-- AUDIO PLAYER CONTROLLER -->
    <footer id="player-toolbar" class="h-20 bg-slate-950 border-t border-slate-900 px-6 flex items-center justify-between shrink-0 select-none z-55 hidden">
        <div class="flex items-center gap-3 w-1/4">
            <img id="track-cover" src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100" class="w-10 h-10 rounded-lg object-cover bg-slate-900 border border-slate-800 shrink-0">
            <div class="min-w-0 hidden md:block">
                <p id="track-title" class="text-xs font-bold text-white truncate">Nenhuma música</p>
                <p id="track-artist" class="text-[10px] text-slate-400 truncate mt-0.5">Selecione para ouvir</p>
            </div>
        </div>

        <div class="flex flex-col items-center gap-1.5 flex-1 max-w-lg">
            <div class="flex items-center gap-4">
                <button onclick="toggleShuffle()" id="player-shuffle" class="text-slate-500 hover:text-white transition cursor-pointer"><i data-lucide="shuffle" class="w-4 h-4"></i></button>
                <button onclick="prev()" class="text-slate-400 hover:text-white transition cursor-pointer"><i data-lucide="skip-back" class="w-4 h-4"></i></button>
                <button onclick="togglePlay()" id="player-play-btn" class="p-2 bg-white text-slate-950 rounded-full hover:scale-105 active:scale-95 transition cursor-pointer"><i data-lucide="play" class="w-4 h-4 fill-current"></i></button>
                <button onclick="next()" class="text-slate-400 hover:text-white transition cursor-pointer"><i data-lucide="skip-forward" class="w-4 h-4"></i></button>
                <button onclick="toggleLoop()" id="player-loop" class="text-slate-500 hover:text-white transition cursor-pointer"><i data-lucide="repeat" class="w-4 h-4"></i></button>
                <button onclick="togglePlayerCurrentFav(event)" id="player-favorite-heart-btn" class="text-slate-500 hover:text-rose-550 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0 hidden" title="Adicionar aos Favoritos">
                    <i data-lucide="heart" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="w-full flex items-center gap-2">
                <span id="player-current-time" class="text-[9px] font-mono text-slate-500 w-8 text-right">0:00</span>
                <input id="player-seek" oninput="seek(this.value)" type="range" min="0" value="0" step="0.5" class="flex-1 h-1 bg-slate-800 accent-sky-500 rounded-lg cursor-pointer">
                <span id="player-duration" class="text-[9px] font-mono text-slate-500 w-8">0:00</span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 w-1/3">
            <button onclick="showLyricsPhp()" class="text-slate-400 hover:text-sky-400 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0" title="Mostrar Letras">
                <i data-lucide="file-text" class="w-4 h-4"></i>
            </button>
            <button onclick="showEqualizerPhp()" class="text-slate-400 hover:text-sky-400 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0" title="Equalizador">
                <i data-lucide="sliders" class="w-4 h-4"></i>
            </button>
            <button onclick="showVisualizerPhp()" class="text-slate-400 hover:text-sky-400 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0" title="Visualizador (Estilo WMP)">
                <i data-lucide="activity" class="w-4 h-4"></i>
            </button>
            <button onclick="togglePartyModePhp()" id="player-party-mode-btn" class="text-slate-400 hover:text-pink-400 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0 flex items-center gap-1" title="Modo Festa (Desativa Pausa e Bloqueia Navegação)">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
            </button>
            <div id="php-sleep-container" class="relative group shrink-0">
                <button id="php-sleep-btn" class="p-1.5 text-slate-400 hover:text-white transition cursor-pointer border border-transparent rounded-lg flex items-center gap-1 font-mono text-[9px]" title="Sleep Timer">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </button>
                <div class="absolute bottom-full right-0 pb-2 pointer-events-none opacity-0 group-hover:pointer-events-auto group-hover:opacity-100 transition-all duration-150 z-50">
                    <div class="w-32 bg-slate-950 border border-slate-900 rounded-xl shadow-2xl py-1 overflow-hidden">
                        <div class="px-2 py-0.5 border-b border-slate-900 mb-1">
                            <span class="text-[8px] font-bold text-slate-500 uppercase tracking-wider block">Desligar em:</span>
                        </div>
                        <button onclick="setSleepTimerPhp(5)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">5 minutos</button>
                        <button onclick="setSleepTimerPhp(15)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">15 minutos</button>
                        <button onclick="setSleepTimerPhp(30)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">30 minutos</button>
                        <button onclick="setSleepTimerPhp(45)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">45 minutos</button>
                        <button onclick="setSleepTimerPhp(60)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">60 minutos</button>
                        <button onclick="setSleepTimerPhp(null)" class="w-full text-left px-2.5 py-1 text-[10px] text-rose-450 hover:text-rose-350 hover:bg-rose-950/20 border-t border-slate-900 mt-1 transition font-bold text-rose-400">Desativar</button>
                    </div>
                </div>
            </div>
            <div id="php-crossfade-container" class="relative group shrink-0">
                <button id="php-crossfade-btn" class="p-1.5 text-slate-400 hover:text-white transition cursor-pointer border border-transparent rounded-lg flex items-center gap-1 font-mono text-[9px]" title="Efeito Crossfade (Transição)">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </button>
                <div class="absolute bottom-full right-0 pb-2 pointer-events-none opacity-0 group-hover:pointer-events-auto group-hover:opacity-100 transition-all duration-150 z-50">
                    <div class="w-32 bg-slate-950 border border-slate-900 rounded-xl shadow-2xl py-1 overflow-hidden">
                        <div class="px-2 py-0.5 border-b border-slate-900 mb-1">
                            <span class="text-[8px] font-bold text-slate-500 uppercase tracking-wider block">Crossfade (Transição):</span>
                        </div>
                        <button onclick="setCrossfadePhp(2)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">2 segundos</button>
                        <button onclick="setCrossfadePhp(4)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">4 segundos</button>
                        <button onclick="setCrossfadePhp(7)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-slate-900 transition font-medium">7 segundos</button>
                        <button onclick="setCrossfadePhp(10)" class="w-full text-left px-2.5 py-1 text-[11px] text-slate-350 hover:text-white hover:bg-[#0a111e] transition font-medium">10 segundos</button>
                        <button onclick="setCrossfadePhp(0)" class="w-full text-left px-2.5 py-1 text-[10px] text-rose-450 hover:text-rose-350 hover:bg-rose-950/20 border-t border-slate-900 mt-1 transition font-bold text-rose-400">Desativar</button>
                    </div>
                </div>
            </div>
            <button onclick="mute()" id="player-mute" class="text-slate-400 hover:text-white transition cursor-pointer shrink-0"><i data-lucide="volume-2" class="w-4 h-4"></i></button>
            <input id="player-volume" oninput="volume(this.value)" type="range" min="0" max="1" step="0.05" value="0.7" class="w-16 h-1 bg-slate-800 accent-sky-500 rounded-lg cursor-pointer shrink-0">
        </div>
    </footer>

    <audio id="real-audio" class="hidden" preload="auto"></audio>

    <!-- VIDEO PLAYER MODAL -->
    <div id="video-modal" class="fixed inset-0 bg-black/95 flex items-center justify-center z-50 p-4 hidden">
        <div id="video-modal-container" class="relative w-full max-w-4xl bg-slate-950 border border-slate-900 rounded-3xl overflow-hidden shadow-2xl flex flex-col font-sans">
            <!-- Modal Title bar -->
            <div class="flex items-center justify-between p-4 border-b border-slate-900 bg-[#0d131f]/15">
                <div class="flex items-center gap-2 min-w-0">
                    <i data-lucide="film" class="w-4 h-4 text-sky-450 shrink-0"></i>
                    <span id="video-modal-title" class="text-xs font-bold text-white max-w-sm sm:max-w-md truncate">Video</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="video-maximize-btn" onclick="toggleVideoMaximize()" class="p-1 text-slate-500 hover:text-white hover:bg-slate-900 rounded-lg transition" title="Tela Cheia">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                    </button>
                    <button onclick="closeVideoModal()" class="p-1 text-slate-500 hover:text-white hover:bg-slate-900 rounded-lg transition">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Video core viewport -->
            <div id="video-viewport-container" class="aspect-video bg-black flex items-center justify-center relative">
                <video id="modal-video-player" controls class="w-full h-full object-contain" style="will-change: transform; transform: translate3d(0,0,0);"></video>
            </div>
        </div>
    </div>

    <!-- IMAGE SEARCH MODAL -->
    <div id="image-search-modal" class="fixed inset-0 bg-black/75 flex items-center justify-center z-50 p-4 backdrop-blur-sm hidden">
        <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-xl w-full flex flex-col max-h-[85vh] overflow-hidden shadow-2xl">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-900 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider">Buscar Logo / Foto da Banda</h3>
                    <p id="image-search-modal-sub" class="text-[10px] font-semibold text-slate-400 mt-1"></p>
                </div>
                <button onclick="closeImageSearchModal()" class="p-1.5 hover:bg-slate-900 text-slate-400 hover:text-white rounded-lg transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Search Controls -->
            <div class="p-5 bg-slate-950/40 border-b border-slate-900 space-y-3.5">
                <div class="flex gap-2">
                    <input type="text" id="image-search-query" placeholder="Pesquisar por artista..." class="flex-1 bg-slate-900 border border-slate-800/80 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 font-semibold focus:outline-none focus:border-sky-500 transition" onkeydown="if(event.key === 'Enter') executeImageSearch()">
                    <button onclick="executeImageSearch()" class="px-5 bg-sky-500 hover:bg-sky-600 font-bold text-xs uppercase tracking-wider text-white rounded-xl transition cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        Buscar
                    </button>
                </div>

                <!-- Source Select Tabs -->
                <div id="image-search-tabs-container" class="flex bg-[#070a12] p-1 rounded-xl border border-slate-900/40">
                    <input type="hidden" id="image-search-source" value="google">
                    <button id="src-tab-google" onclick="setImageSearchSource('google')" class="flex-1 text-center py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition bg-slate-900 text-white shadow-sm border border-slate-800/30">
                        Google
                    </button>
                    <button id="src-tab-deezer" onclick="setImageSearchSource('deezer')" class="flex-1 text-center py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition text-slate-400 hover:text-white font-black">
                        Deezer
                    </button>
                    <button id="src-tab-lastfm" onclick="setImageSearchSource('lastfm')" class="flex-1 text-center py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition text-slate-400 hover:text-white font-black">
                        Last.fm
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div id="image-search-results-container" class="flex-1 overflow-y-auto p-5 custom-scroll min-h-[220px]">
                <div class="h-44 flex flex-col items-center justify-center gap-2 text-center text-slate-500">
                    <i data-lucide="image" class="w-7 h-7 text-slate-700"></i>
                    <p class="text-xs font-bold text-slate-400">Nenhum resultado de imagem</p>
                </div>
            </div>
        </div>
    </div>
            
              
    <!-- EQUALIZER MODAL -->
    <div id="equalizer-modal" class="fixed inset-0 bg-black/85 flex flex-col items-center justify-center z-[60] p-4 backdrop-blur-sm hidden font-sans">
        <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-2xl w-full text-left shadow-2xl relative flex flex-col overflow-hidden animate-fade-in">
            <!-- Header -->
            <div class="flex items-center justify-between p-5 border-b border-slate-900">
                <div class="flex items-center gap-2">
                    <i data-lucide="sliders" class="w-5 h-5 text-sky-455"></i>
                    <h3 class="text-sm font-black uppercase text-white tracking-wider">Equalizador de Áudio</h3>
                </div>
                <button onclick="closeEqualizerModal()" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-900 rounded-lg transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto w-full">
                <!-- Presets -->
                <div class="mb-6">
                    <label class="text-[9px] uppercase font-bold text-slate-500 tracking-wider block mb-3">Predefinições (Presets)</label>
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                        <button onclick="setEqPresetPhp('flat')" id="preset-php-flat" class="preset-btn px-2 py-1.5 bg-sky-500/10 border border-sky-500 text-sky-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-bold shadow-lg shadow-sky-500/5 cursor-pointer">Flat</button>
                        <button onclick="setEqPresetPhp('bass')" id="preset-php-bass" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Grav</button>
                        <button onclick="setEqPresetPhp('pop')" id="preset-php-pop" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Pop</button>
                        <button onclick="setEqPresetPhp('rock')" id="preset-php-rock" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Rock</button>
                        <button onclick="setEqPresetPhp('vocal')" id="preset-php-vocal" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Voz</button>
                        <button onclick="setEqPresetPhp('electronic')" id="preset-php-electronic" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Eletr</button>
                        <button onclick="setEqPresetPhp('suave')" id="preset-php-suave" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Suav</button>
                        <button onclick="setEqPresetPhp('classical')" id="preset-php-classical" class="preset-btn px-2 py-1.5 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium cursor-pointer">Clás</button>
                    </div>
                </div>

                <!-- Sliders -->
                <div class="p-6 flex justify-around items-center h-56 bg-slate-900/40 border border-slate-800/60 rounded-xl">
                    <div class="flex flex-col items-center h-full gap-2">
                        <span class="text-[9px] text-slate-500 font-mono" id="php-eq-gain-val-0">0dB</span>
                        <div class="flex-1 w-8 relative flex justify-center py-2">
                            <input type="range" min="-12" max="12" value="0" id="php-eq-slider-0" oninput="onEqSliderChangePhp(0, this.value)" class="h-full cursor-pointer accent-sky-400" orient="vertical" style="-webkit-appearance: slider-vertical; width: 6px; height: 100%;">
                        </div>
                        <span class="text-[9px] font-bold text-white">60Hz</span>
                        <span class="text-[8px] text-sky-455 uppercase font-black tracking-wider">Graves</span>
                    </div>
                    <div class="flex flex-col items-center h-full gap-2">
                        <span class="text-[9px] text-slate-500 font-mono" id="php-eq-gain-val-1">0dB</span>
                        <div class="flex-1 w-8 relative flex justify-center py-2">
                            <input type="range" min="-12" max="12" value="0" id="php-eq-slider-1" oninput="onEqSliderChangePhp(1, this.value)" class="h-full cursor-pointer accent-sky-400" orient="vertical" style="-webkit-appearance: slider-vertical; width: 6px; height: 100%;">
                        </div>
                        <span class="text-[9px] font-bold text-white">230Hz</span>
                        <span class="text-[8px] text-slate-500 uppercase font-bold tracking-wider">MGrav</span>
                    </div>
                    <div class="flex flex-col items-center h-full gap-2">
                        <span class="text-[9px] text-slate-500 font-mono" id="php-eq-gain-val-2">0dB</span>
                        <div class="flex-1 w-8 relative flex justify-center py-2">
                            <input type="range" min="-12" max="12" value="0" id="php-eq-slider-2" oninput="onEqSliderChangePhp(2, this.value)" class="h-full cursor-pointer accent-sky-400" orient="vertical" style="-webkit-appearance: slider-vertical; width: 6px; height: 100%;">
                        </div>
                        <span class="text-[9px] font-bold text-white">910Hz</span>
                        <span class="text-[8px] text-slate-500 uppercase font-bold tracking-wider">Médio</span>
                    </div>
                    <div class="flex flex-col items-center h-full gap-2">
                        <span class="text-[9px] text-slate-500 font-mono" id="php-eq-gain-val-3">0dB</span>
                        <div class="flex-1 w-8 relative flex justify-center py-2">
                            <input type="range" min="-12" max="12" value="0" id="php-eq-slider-3" oninput="onEqSliderChangePhp(3, this.value)" class="h-full cursor-pointer accent-sky-400" orient="vertical" style="-webkit-appearance: slider-vertical; width: 6px; height: 100%;">
                        </div>
                        <span class="text-[9px] font-bold text-white">4kHz</span>
                        <span class="text-[8px] text-slate-500 uppercase font-bold tracking-wider">Pres</span>
                    </div>
                    <div class="flex flex-col items-center h-full gap-2">
                        <span class="text-[9px] text-slate-500 font-mono" id="php-eq-gain-val-4">0dB</span>
                        <div class="flex-1 w-8 relative flex justify-center py-2">
                            <input type="range" min="-12" max="12" value="0" id="php-eq-slider-4" oninput="onEqSliderChangePhp(4, this.value)" class="h-full cursor-pointer accent-sky-400" orient="vertical" style="-webkit-appearance: slider-vertical; width: 6px; height: 100%;">
                        </div>
                        <span class="text-[9px] font-bold text-white">14kHz</span>
                        <span class="text-[8px] text-sky-455 uppercase font-black tracking-wider">Agudo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- VISUALIZER MODAL -->
    <div id="visualizer-modal" class="fixed inset-0 bg-black flex items-center justify-center z-[60] p-4 hidden font-sans">
        <div id="visualizer-container" class="bg-[#090e18] border border-slate-800 rounded-3xl w-full max-w-4xl shadow-2xl flex flex-col overflow-hidden animate-fade-in relative">
            <!-- Toolbar (hidable in fullscreen) -->
            <div id="visualizer-toolbar" class="flex flex-col shrink-0">
                <div class="flex items-center justify-between p-4 border-b border-slate-900 bg-[#0d131f]/50">
                    <div class="flex items-center gap-3">
                        <i data-lucide="activity" class="w-5 h-5 text-sky-455 animate-pulse"></i>
                        <span class="text-sm font-black text-white uppercase tracking-widest">Visualizador</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="toggleVisualizerFullscreen()" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition cursor-pointer" title="Tela Cheia">
                            <i data-lucide="maximize" class="w-4 h-4"></i>
                        </button>
                        <button onclick="closeVisualizerModal()" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition cursor-pointer" title="Fechar">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <div class="p-3 border-b border-slate-900 bg-slate-900/20 flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-500 uppercase mr-1">Estilos:</span>
                        <button onclick="setVisualizerStylePhp('bars')" id="v-style-bars" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition bg-sky-500/10 text-sky-400 cursor-pointer border border-sky-500/30">Barras</button>
                        <button onclick="setVisualizerStylePhp('scope')" id="v-style-scope" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent cursor-pointer">Onda</button>
                        <button onclick="setVisualizerStylePhp('beat')" id="v-style-beat" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent cursor-pointer">Batida</button>
                        <button onclick="setVisualizerStylePhp('circle')" id="v-style-circle" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent cursor-pointer">Círculo</button>
                        <button onclick="setVisualizerStylePhp('particles')" id="v-style-particles" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent cursor-pointer">Partículas</button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-slate-500 uppercase mr-1">Cores:</span>
                        <button onclick="setVisualizerColorPhp('wmp')" id="v-color-wmp" class="px-2.5 py-1 rounded-md text-[10px] uppercase font-black transition bg-cyan-500/10 text-cyan-405 border border-cyan-500/30 cursor-pointer">WMP</button>
                        <button onclick="setVisualizerColorPhp('neon')" id="v-color-neon" class="px-2.5 py-1 rounded-md text-[10px] uppercase font-black transition text-purple-400/50 hover:text-purple-400 cursor-pointer">Neon</button>
                        <button onclick="setVisualizerColorPhp('fire')" id="v-color-fire" class="px-2.5 py-1 rounded-md text-[10px] uppercase font-black transition text-orange-400/50 hover:text-orange-400 cursor-pointer">Fire</button>
                        <button onclick="setVisualizerColorPhp('cyber')" id="v-color-cyber" class="px-2.5 py-1 rounded-md text-[10px] uppercase font-black transition text-green-400/50 hover:text-green-400 cursor-pointer">Cyber</button>
                    </div>
                </div>
            </div>
            
            <!-- Canvas -->
            <div id="visualizer-canvas-wrapper" class="w-full relative bg-[#040810] flex items-center justify-center overflow-hidden h-72 sm:h-96" style="min-height: 250px; transform: translateZ(0); will-change: transform;">
                <canvas id="php-visualizer-canvas" class="w-full h-full block" style="will-change: transform;"></canvas>
            </div>
        </div>
    </div>


    <!-- LYRICS MODAL -->
    <div id="lyrics-modal" class="fixed inset-0 bg-black/85 flex items-center justify-center z-[60] p-4 backdrop-blur-sm hidden font-sans text-left">
        <div class="bg-[#0b0f19] border border-slate-800 rounded-3xl max-w-2xl w-full flex flex-col shadow-2xl overflow-hidden animate-fade-in relative max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-5 border-b border-slate-900 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-sky-400 shrink-0">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 id="lyrics-title" class="text-sm font-bold text-white truncate max-w-[200px] sm:max-w-xs">Título</h3>
                        <p id="lyrics-artist" class="text-[10px] text-sky-450 truncate mt-0.5 font-semibold">Artista</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="flex bg-slate-900/60 p-1 border border-slate-800 rounded-lg gap-1 select-none">
                        <button id="btn-lyrics-karaoke" onclick="setLyricsMode('karaoke')" class="px-2.5 py-1 font-bold uppercase tracking-wider text-[9px] rounded cursor-pointer transition-all duration-200">
                            Karaoke
                        </button>
                        <button id="btn-lyrics-standard" onclick="setLyricsMode('standard')" class="px-2.5 py-1 font-bold uppercase tracking-wider text-[9px] rounded cursor-pointer transition-all duration-200">
                            Leitura
                        </button>
                    </div>
                    <button onclick="closeLyricsModal()" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-900 rounded-xl transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Search controls -->
            <div class="px-5 py-3 bg-[#0a0f18] border-b border-slate-900 flex flex-col sm:flex-row gap-3 shrink-0">
                <input type="text" id="lyrics-search-artist" placeholder="Artista" class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-sky-500 transition" onkeydown="if(event.key === 'Enter') searchLyricsCustom()">
                <input type="text" id="lyrics-search-title" placeholder="Música" class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-sky-500 transition" onkeydown="if(event.key === 'Enter') searchLyricsCustom()">
                <button onclick="searchLyricsCustom()" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 font-bold text-[10px] uppercase tracking-wider text-white rounded-xl transition cursor-pointer sm:w-auto w-full">
                    Buscar Letras
                </button>
            </div>

            <!-- Content -->
            <div id="lyrics-content" class="flex-1 overflow-y-auto p-6 text-center whitespace-pre-line custom-scroll text-sm leading-relaxed text-slate-350">
                <!-- Loaded on demand -->
            </div>
        </div>
    </div>


    <!-- ID3 TAGS EDITOR MODAL (Admin Only) -->
    <div id="id3-edit-modal" class="fixed inset-0 bg-black/85 flex items-center justify-center z-[60] p-4 backdrop-blur-sm hidden">
        <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-md w-full flex flex-col overflow-hidden shadow-2xl font-sans animate-fade-in">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-900 flex items-center justify-between col-span-2">
                <div class="flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-4 h-4 text-sky-400"></i>
                    <h3 class="text-xs font-black uppercase text-white tracking-wider">Editar Tags ID3 / Metadados</h3>
                </div>
                <button onclick="closeId3EditModal()" class="p-1.5 hover:bg-slate-900 text-slate-400 hover:text-white rounded-lg transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Content (Forms) -->
            <form id="id3-edit-form" onsubmit="saveId3Tags(event)" class="p-5 space-y-4 text-left">
                <input type="hidden" id="id3-edit-song-id" name="id">
                
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Título da Música</label>
                    <input type="text" id="id3-edit-title" name="title" required class="w-full bg-slate-900 border border-slate-800 text-sky-400 font-semibold p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                </div>

                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Artista</label>
                    <input type="text" id="id3-edit-artist" name="artist" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                </div>

                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Álbum</label>
                    <input type="text" id="id3-edit-album" name="album" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Gênero</label>
                        <input type="text" id="id3-edit-genre" name="genre" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Ano do Álbum</label>
                        <input type="number" id="id3-edit-year" name="album_year" placeholder="Ex: 2024" min="1900" max="2100" class="w-full bg-slate-900 border border-slate-800 text-white p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                    </div>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="button" onclick="closeId3EditModal()" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-850 hover:text-white text-slate-400 border border-slate-800/80 rounded-xl text-xs font-bold transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white rounded-xl text-xs font-black transition shadow-lg shadow-sky-500/10 hover:shadow-sky-500/20 cursor-pointer">
                        Salvar Tags
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ID3 TAGS BULK EDITOR MODAL (Admin Only) -->
    <div id="id3-bulk-modal" class="fixed inset-0 bg-black/85 flex items-center justify-center z-[60] p-4 backdrop-blur-sm hidden font-sans">
        <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-md w-full flex flex-col overflow-hidden shadow-2xl font-sans animate-fade-in text-left">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-900 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4 text-sky-400"></i>
                    <div>
                        <h3 class="text-xs font-black uppercase text-white tracking-wider">Alteração em Massa</h3>
                        <p class="text-[10px] text-slate-450 font-medium">Editar <span id="id3-bulk-selected-count" class="font-bold text-sky-400">0</span> músicas selecionadas</p>
                    </div>
                </div>
                <button onclick="closeId3BulkModal()" class="p-1.5 hover:bg-slate-900 text-slate-400 hover:text-white rounded-lg transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Content (Forms) -->
            <form id="id3-bulk-form" onsubmit="saveBulkId3Tags(event)" class="p-5 space-y-4 font-sans">
                <p class="text-[10px] text-slate-400 leading-normal bg-[#040812]/80 p-3 rounded-xl border border-slate-900 flex gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-sky-400 shrink-0 mt-0.5"></i>
                    <span>Selecione a caixa de seleção ao lado de cada campo para ativar o preenchimento e aplicar nos arquivos selecionados.</span>
                </p>

                <!-- Album field -->
                <div class="space-y-1.5 pt-1">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="bulk-use-album" onchange="toggleBulkField('album')" class="w-3.5 h-3.5 text-sky-500 bg-slate-900 border-slate-800 rounded focus:ring-sky-500 cursor-pointer">
                        <label for="bulk-use-album" class="text-[9px] font-bold text-slate-300 uppercase tracking-wider cursor-pointer flex items-center gap-1 select-none">
                            Alterar Álbum
                        </label>
                    </div>
                    <input type="text" id="id3-bulk-album" placeholder="Ex: Cosmic Hits" disabled class="w-full bg-slate-900/40 border border-slate-900 text-slate-500 p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition opacity-40">
                </div>

                <!-- Year field -->
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="bulk-use-year" onchange="toggleBulkField('year')" class="w-3.5 h-3.5 text-sky-500 bg-slate-900 border-slate-800 rounded focus:ring-sky-500 cursor-pointer">
                        <label for="bulk-use-year" class="text-[9px] font-bold text-slate-300 uppercase tracking-wider cursor-pointer flex items-center gap-1 select-none">
                            Alterar Ano do Álbum
                        </label>
                    </div>
                    <input type="number" id="id3-bulk-year" placeholder="Ex: 2024" min="1900" max="2100" disabled class="w-full bg-slate-900/40 border border-slate-900 text-slate-500 p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition opacity-40">
                </div>

                <!-- Artist field -->
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="bulk-use-artist" onchange="toggleBulkField('artist')" class="w-3.5 h-3.5 text-sky-500 bg-slate-900 border-slate-800 rounded focus:ring-sky-500 cursor-pointer">
                        <label for="bulk-use-artist" class="text-[9px] font-bold text-slate-300 uppercase tracking-wider cursor-pointer flex items-center gap-1 select-none">
                            Alterar Artista
                        </label>
                    </div>
                    <input type="text" id="id3-bulk-artist" placeholder="Ex: SoundHelix" disabled class="w-full bg-slate-900/40 border border-slate-900 text-slate-500 p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition opacity-40">
                </div>

                <!-- Genre field -->
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="bulk-use-genre" onchange="toggleBulkField('genre')" class="w-3.5 h-3.5 text-sky-500 bg-slate-900 border-slate-800 rounded focus:ring-sky-500 cursor-pointer">
                        <label for="bulk-use-genre" class="text-[9px] font-bold text-slate-300 uppercase tracking-wider cursor-pointer flex items-center gap-1 select-none">
                            Alterar Gênero
                        </label>
                    </div>
                    <input type="text" id="id3-bulk-genre" placeholder="Ex: Synthwave" disabled class="w-full bg-slate-900/40 border border-slate-900 text-slate-500 p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition opacity-40">
                </div>

                <div class="pt-4 flex gap-3 text-xs font-bold font-sans">
                    <button type="button" onclick="closeId3BulkModal()" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-850 hover:text-white text-slate-350 border border-slate-800/80 rounded-xl transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white rounded-xl transition shadow-lg shadow-sky-500/10 hover:shadow-sky-500/20 cursor-pointer font-black">
                        Aplicar em Massa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ID3 ALBUM BULK EDITOR MODAL (Admin Only) -->
    <div id="id3-album-modal" class="fixed inset-0 bg-black/85 flex items-center justify-center z-[60] p-4 backdrop-blur-sm hidden font-sans">
        <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-lg w-full flex flex-col overflow-hidden shadow-2xl font-sans animate-fade-in text-left max-h-[90vh]">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-900 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <i data-lucide="disc" class="w-4 h-4 text-sky-450 animate-spin-slow"></i>
                    <div>
                        <h3 class="text-xs font-black uppercase text-white tracking-wider">Editar Álbum Completo</h3>
                        <p class="text-[10px] text-slate-450 font-medium">Você está editando o álbum: <span id="id3-album-title-text" class="font-bold text-sky-400"></span></p>
                    </div>
                </div>
                <button onclick="closeId3AlbumModal()" class="p-1.5 hover:bg-slate-900 text-slate-400 hover:text-white rounded-lg transition cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Content (Forms) -->
            <form id="id3-album-form" onsubmit="saveAlbumId3Tags(event)" class="p-5 space-y-4 font-sans overflow-y-auto flex-1 custom-scroll">
                <!-- Warning/Explanation -->
                <p class="text-[10px] text-slate-400 leading-normal bg-[#040812]/80 p-3 rounded-xl border border-slate-900 flex gap-2">
                    <i data-lucide="info" class="w-4.5 h-4.5 text-sky-400 shrink-0 mt-0.5"></i>
                    <span>Este editor altera os metadados do álbum e o título de cada música individualmente. Clique em "Salvar Álbum" no final da página para gravar permanentemente no banco de dados.</span>
                </p>

                <!-- Target Hidden Values -->
                <input type="hidden" id="id3-album-original-name">

                <!-- New Album Field -->
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Novo Nome do Álbum</label>
                    <input type="text" id="id3-album-name" required autocomplete="off" class="w-full bg-[#050914] border border-slate-800/90 text-sky-400 font-semibold p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                </div>

                <!-- Artist field -->
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Artista</label>
                    <input type="text" id="id3-album-artist" autocomplete="off" class="w-full bg-[#050914] border border-slate-800/90 text-sky-400 font-semibold p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- Genre field -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Gênero</label>
                        <input type="text" id="id3-album-genre" autocomplete="off" class="w-full bg-[#050914] border border-slate-800/90 text-sky-400 font-semibold p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                    </div>

                    <!-- Year field -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Ano</label>
                        <input type="number" id="id3-album-year" min="1900" max="2100" placeholder="Ex: 2024" autocomplete="off" class="w-full bg-[#050914] border border-slate-800/90 text-sky-400 font-semibold p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition">
                    </div>
                </div>

                <!-- Song list preview -->
                <div class="space-y-2 bg-slate-950/40 p-4 rounded-xl border border-slate-900">
                    <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Títulos das Músicas deste Álbum (<span id="id3-album-songs-count">0</span>)</label>
                    <div id="id3-album-songs-list" class="max-h-56 overflow-y-auto pr-1 text-[11px] text-slate-400 space-y-2 custom-scroll">
                        <!-- Filled dynamically with input fields -->
                    </div>
                </div>

                <div class="pt-2 flex gap-3 text-xs font-bold font-sans shrink-0">
                    <button type="button" onclick="closeId3AlbumModal()" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-850 hover:text-white text-slate-350 border border-slate-800/80 rounded-xl transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white rounded-xl transition shadow-lg shadow-sky-500/10 hover:shadow-sky-500/20 cursor-pointer font-black">
                        Salvar Álbum
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Garantir que chamadas ao lucide não quebrem a aplicação caso o CDN falhe ou atrase
        if (typeof window.lucide === 'undefined' || !window.lucide) {
            window.lucide = {
                createIcons: function() {
                    console.warn("Biblioteca Lucide de ícones não foi carregada.");
                }
            };
        }

        const API = 'api.php';

        // Interceptador global do fetch no index.php para propagar o cabeçalho X-Username
        const origFetch = window.fetch;
        try {
            Object.defineProperty(window, 'fetch', {
                value: async function(...args) {
                    let [resource, config] = args;
                    if (typeof resource === 'string' && (resource.includes('api.php') || resource.startsWith('api.php'))) {
                        config = config || {};
                        config.headers = config.headers || {};
                        if (currentUser && currentUser.username) {
                            config.headers['X-Username'] = currentUser.username;
                        }
                    }
                    return origFetch(resource, config);
                },
                writable: true,
                configurable: true,
                enumerable: true
            });
        } catch (e) {
            console.error("Erro ao definir interceptor fetch:", e);
        }

        let globalSettings = {};
let currentUser = null;
        let activeTab = 'dashboard';
        
        let allTracks = [];
        let allPlaylists = [];
        let allFavorites = [];
        let filteredTracks = [];
        let allVideos = [];
        let uploadingVideoId = null;
        
        let selectedArtist = '';
        let phpSelectedArtist = '';
        let phpSelectedAlbum = '';
        let artistBioText = '';
        let artistPhotoUrl = '';
        let artistTopTracks = [];
        let loadingArtistBio = false;
        let loadedCoversCache = {};
        let isBioExpanded = false;
        
        let selectedPlaylistId = '';
        let activePlaylistAlbum = ''; // for album detail views

        // Player engine variables
        let isPlaying = false;
        let isShuffle = false;
        let isLoop = false;
        let isPartyMode = false;
        let activeQueue = [];
        let activeQueueIdx = -1;
        const audio = document.getElementById('real-audio');

        // Pagination & Sorting state for tracks table in PHP
        let phpSortField = null;
        let phpSortOrder = 'asc';
        let phpCurrentPage = 1;
        const phpPageSize = 55;
        let dashboardAlbumPage = 1;
        const dashboardAlbumPageSize = 60;
        let lastSearchQuery = '';
        let randomDashboardAlbums = [];
        let dashboardRandomInterval = null;

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            let shareHash = urlParams.get('share');
            if (!shareHash && window.location.hash.length > 1) {
                shareHash = window.location.hash.substring(1);
            }
            if (shareHash) {
                const lp = document.getElementById('login-panel');
                if (lp) lp.classList.add('hidden');
                const wp = document.getElementById('workspace-panel');
                if (wp) wp.classList.add('hidden');
                return bootPublicSharedPlayer(shareHash);
            }
            const savedLang = localStorage.getItem('phplayer_lang') || 'pt';
            if (window.applySystemLanguage) {
                window.applySystemLanguage(savedLang);
            }
            
            const stored = localStorage.getItem('phplayer_user');
            if (stored) {
                try {
                    currentUser = JSON.parse(stored);
                    if (currentUser && currentUser.username) {
                        bootPlayer();
                    } else {
                        const lp = document.getElementById('login-panel');
                        if (lp) lp.classList.remove('hidden');
                    }
                } catch (e) {
                    localStorage.removeItem('phplayer_user');
                    const lp = document.getElementById('login-panel');
                    if (lp) lp.classList.remove('hidden');
                }
            } else {
                const lp = document.getElementById('login-panel');
                if (lp) lp.classList.remove('hidden');
            }
            lucide.createIcons();
            
            setInterval(() => {
                const now = new Date();
                const clock = document.getElementById('clock');
                if (clock) {
                    clock.textContent = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                }
            }, 1000);

            // Audio element listeners
            if (audio) {
                audio.addEventListener('timeupdate', () => {
                    const seek = document.getElementById('player-seek');
                    const curTime = document.getElementById('player-current-time');
                    if (seek) seek.value = audio.currentTime;
                    if (curTime) curTime.textContent = formatSecs(audio.currentTime);

                    // Sync reprodutor seek slider and current time
                    const repSeek = document.getElementById('reprodutor-seek');
                    const repCurTime = document.getElementById('reprodutor-current-time');
                    if (repSeek && document.activeElement !== repSeek) repSeek.value = audio.currentTime;
                    if (repCurTime) repCurTime.textContent = formatSecs(audio.currentTime);

                    // Real-time Karaoke dynamic highlight syncing
                    const lyricsModal = document.getElementById('lyrics-modal');
                    if (lyricsModal && !lyricsModal.classList.contains('hidden') && typeof window.updateLyricsKaraoke === 'function') {
                        window.updateLyricsKaraoke(audio.currentTime);
                    }

                    // Dynamic Crossfade / Volume transition logic in PHP
                    if (window.phpCrossfadeDuration > 0 && audio.duration) {
                        const timeLeft = audio.duration - audio.currentTime;
                        const targetUserVolume = (typeof window.phpUserVolume !== 'undefined') ? window.phpUserVolume : 0.7;

                        if (timeLeft <= window.phpCrossfadeDuration && timeLeft > 0) {
                            // Fade out near end
                            const mult = Math.max(0, timeLeft / window.phpCrossfadeDuration);
                            audio.volume = targetUserVolume * mult;
                        } else if (audio.currentTime <= window.phpCrossfadeDuration && audio.currentTime >= 0) {
                            // Fade in near beginning
                            const mult = Math.min(1, audio.currentTime / window.phpCrossfadeDuration);
                            audio.volume = targetUserVolume * mult;
                        } else {
                            audio.volume = targetUserVolume;
                        }
                    }
                });
                audio.addEventListener('loadedmetadata', () => {
                    const seek = document.getElementById('player-seek');
                    const dur = document.getElementById('player-duration');
                    if (seek) seek.max = audio.duration || 180;
                    if (dur) dur.textContent = formatSecs(audio.duration || 180);

                    // Sync reprodutor duration
                    const repSeek = document.getElementById('reprodutor-seek');
                    const repDur = document.getElementById('reprodutor-duration');
                    if (repSeek) repSeek.max = audio.duration || 180;
                    if (repDur) repDur.textContent = formatSecs(audio.duration || 180);
                });
                audio.addEventListener('ended', () => {
                    if (isLoop) {
                        audio.currentTime = 0;
                        audio.play();
                    } else {
                        next();
                    }
                });
            }

            // Global Keyboard Shortcuts
            window.addEventListener('keydown', (e) => {
                const target = e.target;
                if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
                    return;
                }

                const isCtrlF = (e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'F');
                const isSlash = e.key === '/';
                if (isCtrlF || isSlash) {
                    e.preventDefault();
                    if (typeof activeTab !== 'undefined' && activeTab === 'videos') {
                        const vSearch = document.getElementById('video-search-input');
                        if (vSearch) {
                            vSearch.focus();
                            vSearch.select();
                        }
                    } else {
                        setTab('tracks');
                        setTimeout(() => {
                            const mSearch = document.getElementById('search-input');
                            if (mSearch) {
                                mSearch.focus();
                                mSearch.select();
                            }
                        }, 80);
                    }
                    return;
                }

                if (e.key === ' ' || e.key === 'Spacebar') {
                    e.preventDefault();
                    if (typeof togglePlay === 'function') {
                        togglePlay();
                    }
                    return;
                }

                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    next();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    prev();
                } else if (e.key === '+' || e.key === '=') {
                    e.preventDefault();
                    const volSlider = document.getElementById('player-volume');
                    if (audio) {
                        let newVol = Math.min(1, audio.volume + 0.05);
                        audio.volume = newVol;
                        if (volSlider) volSlider.value = newVol;
                    }
                } else if (e.key === '-' || e.key === '_') {
                    e.preventDefault();
                    const volSlider = document.getElementById('player-volume');
                    if (audio) {
                        let newVol = Math.max(0, audio.volume - 0.05);
                        audio.volume = newVol;
                        if (volSlider) volSlider.value = newVol;
                    }
                } else if (e.key === 'Escape') {
                    const videoModal = document.getElementById('video-modal');
                    if (videoModal && !videoModal.classList.contains('hidden')) {
                        e.preventDefault();
                        if (videoModal.classList.contains('pseudo-fullscreen-active')) {
                            toggleVideoMaximize();
                        } else {
                            closeVideoModal();
                        }
                    }
                }
            });
        };

        function applyUserLayoutBg() {
            if (!currentUser) return;
            const sidebarBg = currentUser.sidebarBg || '';
            const footerBg = currentUser.footerBg || '';
            const topBg = currentUser.topBg || '';

            const sidebarEl = document.querySelector('aside');
            if (sidebarEl) {
                if (sidebarBg) {
                    sidebarEl.style.backgroundColor = sidebarBg;
                    sidebarEl.style.backgroundImage = 'none';
                } else {
                    sidebarEl.style.backgroundColor = '';
                    sidebarEl.style.backgroundImage = '';
                }
            }

            const footerEl = document.getElementById('player-toolbar');
            if (footerEl) {
                if (footerBg) {
                    footerEl.style.backgroundColor = footerBg;
                    footerEl.style.backgroundImage = 'none';
                } else {
                    footerEl.style.backgroundColor = '';
                    footerEl.style.backgroundImage = '';
                }
            }

            const headerEl = document.querySelector('header');
            if (headerEl) {
                if (topBg) {
                    headerEl.style.backgroundColor = topBg;
                    headerEl.style.backgroundImage = 'none';
                } else {
                    headerEl.style.backgroundColor = '';
                    headerEl.style.backgroundImage = '';
                }
            }

            const miniPlayer = document.getElementById('mini-player');
            if (miniPlayer) {
                if (footerBg) {
                    miniPlayer.style.backgroundColor = footerBg;
                    miniPlayer.style.backgroundImage = 'none';
                } else {
                    miniPlayer.style.backgroundColor = '';
                    miniPlayer.style.backgroundImage = '';
                }
            }

            const expPlayer = document.getElementById('expanded-player');
            if (expPlayer) {
                if (sidebarBg) {
                    expPlayer.style.backgroundColor = sidebarBg;
                    expPlayer.style.backgroundImage = 'none';
                } else {
                    expPlayer.style.backgroundColor = '';
                    expPlayer.style.backgroundImage = '';
                }
            }
        }

        async function bootPlayer() {
                try {
                    const resSet = await fetch(API + '?route=get_settings');
                    if (resSet.ok) {
                        const datSet = await resSet.json();
                        if (datSet && datSet.settings) {
                            globalSettings = datSet.settings;
                        }
                    }
                } catch (e) {}

            if (currentUser && currentUser.can_download !=='' && parseInt(currentUser.can_download) === 0) {
                document.body.classList.add('no-download');
            } else {
                document.body.classList.remove('no-download');
            }
            if (!currentUser || !currentUser.username) {
                const lp = document.getElementById('login-panel');
                if (lp) lp.classList.remove('hidden');
                return;
            }
            
            const loginPanel = document.getElementById('login-panel');
            const workspacePanel = document.getElementById('workspace-panel');
            const playerToolbar = document.getElementById('player-toolbar');
            
            if (loginPanel) loginPanel.classList.add('hidden');
            if (workspacePanel) workspacePanel.classList.remove('hidden');
            if (playerToolbar) playerToolbar.classList.remove('hidden');
            
            const pName = document.getElementById('profile-name');
            const pRole = document.getElementById('profile-role');
            const pAvatar = document.getElementById('user-avatar');
            
            if (pName) pName.textContent = currentUser.username;
            if (pRole) pRole.textContent = currentUser.role === 'admin' ? 'Administrador' : 'Ouvinte';
            if (pAvatar) pAvatar.textContent = currentUser.username.substring(0, 2).toUpperCase();
            
            const pWelcome = document.getElementById('dashboard-welcome-title');
            if (pWelcome) pWelcome.innerHTML = `Bem-vindo de volta <span class="text-sky-400 font-extrabold">${currentUser.username}</span>!`;
            const pWelcomeSub = document.getElementById('dashboard-welcome-sub');
            if (pWelcomeSub) pWelcomeSub.textContent = "Servidor PHPlayer";
            
            selectedDesktopTheme = currentUser.theme || 'default';
            applyUserTheme(selectedDesktopTheme);
            applyUserLayoutBg();
            updateThemeCardsUI();

            const savedLang = localStorage.getItem('phplayer_lang') || 'pt';
            if (window.applySystemLanguage) {
                window.applySystemLanguage(savedLang);
            }
            
            const adminRoutes = document.getElementById('admin-routes');
            if (adminRoutes) {
                if (currentUser.role === 'admin') {
                    adminRoutes.classList.remove('hidden');
                } else {
                    adminRoutes.classList.add('hidden');
                }
            }

            await loadData();
            updateRandomDashboardAlbums();
            setTab('dashboard');

            setupDashboardInterval();
        }

        async function loadData() {
            try {
                const r1 = await fetch(API + '?route=tracks');
                const t1 = await r1.text();
                let parsedTracks;
                try {
                    parsedTracks = JSON.parse(t1);
                } catch(e) {
                    throw new Error("Erro de dados de músicas: resposta inválida do servidor (não-JSON). Detalhes:\n" + t1.substring(0, 150));
                }
                if (parsedTracks && parsedTracks.error) {
                    throw new Error(parsedTracks.error);
                }
                if (!Array.isArray(parsedTracks)) {
                    throw new Error("Dados de músicas recebidos do servidor em formato inválido.");
                }
                allTracks = parsedTracks;
                
                if (window.updateDashboardGenresDropdown) {
                    window.updateDashboardGenresDropdown();
                } else {
                    updateDashboardGenresDropdown();
                }
                
                const r2 = await fetch(API + '?route=playlists&username=' + encodeURIComponent(currentUser.username));
                const t2 = await r2.text();
                let parsedPlaylists;
                try {
                    parsedPlaylists = JSON.parse(t2);
                } catch(e) {
                    throw new Error("Erro de dados de playlists: resposta inválida do servidor (não-JSON). Detalhes:\n" + t2.substring(0, 150));
                }
                if (parsedPlaylists && parsedPlaylists.error) {
                    throw new Error(parsedPlaylists.error);
                }
                if (!Array.isArray(parsedPlaylists)) {
                    throw new Error("Dados de playlists recebidos do servidor em formato inválido.");
                }
                allPlaylists = parsedPlaylists;

                const r3 = await fetch(API + '?route=favorites&username=' + encodeURIComponent(currentUser.username));
                const t3 = await r3.text();
                let parsedFavorites;
                try {
                    parsedFavorites = JSON.parse(t3);
                } catch(e) {
                    throw new Error("Erro de dados de favoritos: resposta inválida do servidor (não-JSON). Detalhes:\n" + t3.substring(0, 150));
                }
                if (parsedFavorites && parsedFavorites.error) {
                    throw new Error(parsedFavorites.error);
                }
                if (!Array.isArray(parsedFavorites)) {
                    throw new Error("Dados de favoritos recebidos do servidor em formato inválido.");
                }
                allFavorites = parsedFavorites;

                if (activeTab === 'playlists' && window.renderPlaylistsGrid) {
                    window.renderPlaylistsGrid();
                }
            } catch (err) {
                console.error(err);
                showErrorModal("Erro de conexão com o banco de dados PHP: " + err.message);
            }
        }

        function selectArtist(art) {
            if (isPartyMode) {
                alert("O Modo Festa está ativo! A navegação está bloqueada para manter a diversão focada no player.");
                return;
            }
            selectedArtist = art;
            selectedPlaylistId = '';
            activePlaylistAlbum = '';
            artistBioText = '';
            artistPhotoUrl = '';
            loadingArtistBio = true;
            isBioExpanded = false;

            activeTab = 'tracks';
            const paneDash = document.getElementById('pane-dashboard');
            if (paneDash) paneDash.classList.add('hidden');
            const paneTracks = document.getElementById('pane-tracks');
            if (paneTracks) paneTracks.classList.remove('hidden');
            if (document.getElementById('pane-playlists')) document.getElementById('pane-playlists').classList.add('hidden');
            if (document.getElementById('pane-config')) document.getElementById('pane-config').classList.add('hidden');
            const paneVideos = document.getElementById('pane-videos');
            if (paneVideos) paneVideos.classList.add('hidden');

            // Highlight track tab correctly inside sidebar
            const btns = ['dashboard', 'tracks', 'favorites', 'config', 'videos', 'playlists', 'podcast', 'radios'];
            btns.forEach(b => {
                const el = document.getElementById('tab-btn-' + b);
                if (el) {
                    if (b === 'tracks') {
                        el.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-sky-400 bg-sky-500/10 border border-sky-500/20";
                    } else {
                        el.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition";
                    }
                }
            });

            const tableTitle = document.getElementById('table-view-title');
            if (tableTitle) tableTitle.textContent = "Artista: " + art;
            const clearFilter = document.getElementById('clear-artist-filter');
            if (clearFilter) clearFilter.classList.remove('hidden');

            renderLeftSidebar();
            renderTracksTable();
            
            fetch(API + '?route=artist_bio&artist=' + encodeURIComponent(art))
                .then(res => res.json())
                .then(data => {
                    artistBioText = (data.bio || 'Sem biografia disponível.').replace(/<[^>]*>/g, '').trim();
                    artistPhotoUrl = data.artist_photo || '';
                    artistTopTracks = data.top_tracks || [];
                    loadingArtistBio = false;
                    renderLeftSidebar();
                    renderTracksTable();
                    
                    fetchAlbumCoversForArtist(art);
                })
                .catch(err => {
                    console.error("Erro ao carregar biografia:", err);
                    artistBioText = 'Erro ao carregar biografia do artista.';
                    loadingArtistBio = false;
                    renderLeftSidebar();
                    renderTracksTable();
                });
        }

        function fetchAlbumCoversForArtist(art) {
            const artistTracks = allTracks.filter(t => t.artist === art);
            const albums = {};
            artistTracks.forEach(t => {
                const alb = t.album || 'Single';
                if (!albums[alb]) {
                    albums[alb] = t.coverUrl || t.cover_url || '';
                }
            });
            
            Object.keys(albums).forEach(albStr => {
                const currentCover = albums[albStr];
                if (!currentCover || currentCover.includes('unsplash.com')) {
                    fetch(API + '?route=album_cover&artist=' + encodeURIComponent(art) + '&album=' + encodeURIComponent(albStr))
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.cover_url) {
                                loadedCoversCache[albStr] = data.cover_url;
                                allTracks.forEach(track => {
                                    if (track.artist === art && (track.album || 'Single') === albStr) {
                                        track.cover_url = data.cover_url;
                                        track.coverUrl = data.cover_url;
                                    }
                                });
                                renderTracksTable();
                            }
                        })
                        .catch(err => console.error("Erro carregando capa:", err));
                }
            });
        }

        function formatSecs(seconds) {
            if (isNaN(seconds)) return "0:00";
            const min = Math.floor(seconds / 60);
            const r = Math.floor(seconds % 60);
            return min + ":" + (r < 10 ? '0' : '') + r;
        }

        let configActiveSubTab = 'theme';
        function setConfigSubTab(subTabName) {
            const adminTabs = ['media', 'users', 'files', 'id3', 'shares', 'dashboard_cfg', 'updates'];
            if (adminTabs.includes(subTabName) && (!currentUser || currentUser.role !== 'admin')) {
                subTabName = 'theme';
            }
            configActiveSubTab = subTabName;
            
            // Hide all subtab panes
            document.getElementById('subtab-pane-theme').classList.add('hidden');
            document.getElementById('subtab-pane-media').classList.add('hidden');
            document.getElementById('subtab-pane-users').classList.add('hidden');
            const pwdPane = document.getElementById('subtab-pane-password');
            const sharesPane = document.getElementById('subtab-pane-shares');
            if (sharesPane) sharesPane.classList.add('hidden');
const updPane = document.getElementById('subtab-pane-updates');
            if (updPane) updPane.classList.add('hidden');
            const pshares = document.getElementById('subtab-pane-shares'); if(pshares) pshares.classList.add('hidden');
            if (pwdPane) pwdPane.classList.add('hidden');
            const filesPane = document.getElementById('subtab-pane-files');
            if (filesPane) filesPane.classList.add('hidden');
            const shortcutsPane = document.getElementById('subtab-pane-shortcuts');
            if (shortcutsPane) shortcutsPane.classList.add('hidden');
            const id3Pane = document.getElementById('subtab-pane-id3');
            if (id3Pane) id3Pane.classList.add('hidden');
            
            // Hide all nav button markers
            const subBtns = ['theme', 'media', 'dashboard_cfg', 'shares', 'users', 'password', 'files', 'shortcuts', 'id3', 'updates'];
            subBtns.forEach(sb => {
                const el = document.getElementById('subtab-btn-' + sb);
                if (el) {
                    el.className = "pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none";
                }
            });
            
            // Show select one
            const curPane = document.getElementById('subtab-pane-' + subTabName);
            if (curPane) curPane.classList.remove('hidden');
            const activeSubBtn = document.getElementById('subtab-btn-' + subTabName);
            if (activeSubBtn) {
                activeSubBtn.className = "pb-2 text-xs font-bold border-b-2 border-sky-500 text-white cursor-pointer select-none";
            }
            
            if (subTabName === 'users') {
                renderUsersTable();
            }
            if (subTabName === 'media') {
                if (typeof loadMusicFolders === 'function') {
                    loadMusicFolders();
                }
            }
            if (subTabName === 'files') {
                loadFileManager(fileManagerCurrentPath || '');
            }
            if (subTabName === 'id3') { renderId3SongsList(); } if (subTabName === 'shares') { renderSharesTable(); } if (subTabName === 'dashboard_cfg') { loadDashSettings(); }
            lucide.createIcons();
        }

        let id3SearchQuery = '';
        let selectedId3SongIds = [];

        function filterId3Songs() {
            id3SearchQuery = document.getElementById('id3-search-input').value.toLowerCase();
            renderId3SongsList();
        }

        function toggleSelectAllId3(masterCheckbox) {
            const tbody = document.getElementById('id3-songs-table-body');
            if (!tbody) return;
            
            const filtered = allTracks.filter(t => {
                const titleStr = (t.title || '').toLowerCase();
                const artistStr = (t.artist || '').toLowerCase();
                const albumStr = (t.album || '').toLowerCase();
                const genreStr = (t.genre || '').toLowerCase();
                return titleStr.includes(id3SearchQuery) || 
                       artistStr.includes(id3SearchQuery) || 
                       albumStr.includes(id3SearchQuery) ||
                       genreStr.includes(id3SearchQuery);
            });

            if (masterCheckbox.checked) {
                filtered.forEach(t => {
                    const id = String(t.id);
                    if (!selectedId3SongIds.includes(id)) {
                        selectedId3SongIds.push(id);
                    }
                });
            } else {
                filtered.forEach(t => {
                    const id = String(t.id);
                    const idx = selectedId3SongIds.indexOf(id);
                    if (idx > -1) {
                        selectedId3SongIds.splice(idx, 1);
                    }
                });
            }

            const checkboxes = tbody.querySelectorAll('.id3-song-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = masterCheckbox.checked;
            });

            updateId3SelectionCount();
        }

        function updateRowCheckbox(checkboxEl, songId) {
            const id = String(songId);
            if (checkboxEl.checked) {
                if (!selectedId3SongIds.includes(id)) {
                    selectedId3SongIds.push(id);
                }
            } else {
                const idx = selectedId3SongIds.indexOf(id);
                if (idx > -1) {
                    selectedId3SongIds.splice(idx, 1);
                }
            }
            
            updateSelectAllCheckboxState();
            updateId3SelectionCount();
        }

        function updateSelectAllCheckboxState() {
            const master = document.getElementById('id3-select-all');
            if (!master) return;
            
            const tbody = document.getElementById('id3-songs-table-body');
            if (!tbody) return;
            
            const checkboxes = tbody.querySelectorAll('.id3-song-checkbox');
            if (checkboxes.length === 0) {
                master.checked = false;
                return;
            }
            
            let allChecked = true;
            checkboxes.forEach(cb => {
                if (!cb.checked) allChecked = false;
            });
            master.checked = allChecked;
        }

        function updateId3SelectionCount() {
            const count = selectedId3SongIds.length;
            
            const badge = document.getElementById('id3-selected-count-badge');
            if (badge) badge.textContent = count;
            
            const bar = document.getElementById('id3-bulk-bar');
            if (bar) {
                if (count > 0) {
                    bar.classList.remove('hidden');
                } else {
                    bar.classList.add('hidden');
                }
            }

            const modalCount = document.getElementById('id3-bulk-selected-count');
            if (modalCount) modalCount.textContent = count;
        }

        function clearId3Selection() {
            selectedId3SongIds = [];
            
            const master = document.getElementById('id3-select-all');
            if (master) master.checked = false;
            
            const tbody = document.getElementById('id3-songs-table-body');
            if (tbody) {
                const checkboxes = tbody.querySelectorAll('.id3-song-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
            }
            
            updateId3SelectionCount();
        }

        function toggleBulkField(field) {
            const useCheckbox = document.getElementById('bulk-use-' + field);
            const inputField = document.getElementById('id3-bulk-' + field);
            if (!useCheckbox || !inputField) return;
            
            if (useCheckbox.checked) {
                inputField.removeAttribute('disabled');
                inputField.classList.remove('bg-slate-900/40', 'text-slate-500', 'opacity-40');
                inputField.classList.add('bg-slate-900', 'text-white');
                inputField.focus();
            } else {
                inputField.setAttribute('disabled', 'true');
                inputField.classList.add('bg-slate-900/40', 'text-slate-500', 'opacity-40');
                inputField.classList.remove('bg-slate-900', 'text-white');
                inputField.value = '';
            }
        }

        function openId3BulkModal() {
            if (selectedId3SongIds.length === 0) {
                alert('Selecione ao menos uma música primeiro.');
                return;
            }
            
            const fields = ['album', 'year', 'artist', 'genre'];
            fields.forEach(f => {
                const cb = document.getElementById('bulk-use-' + f);
                if (cb) cb.checked = false;
                toggleBulkField(f);
            });
            
            document.getElementById('id3-bulk-selected-count').textContent = selectedId3SongIds.length;
            const bulkModal = document.getElementById('id3-bulk-modal');
            bulkModal.classList.remove('hidden');
            bulkModal.style.zIndex = '999999';
            bulkModal.style.display = 'flex';
            if(window.lucide) window.lucide.createIcons();
        }

        function closeId3BulkModal() {
            const m = document.getElementById('id3-bulk-modal');
            if(m) {
                m.classList.add('hidden');
                m.style.display = 'none';
            }
        }

        async function saveBulkId3Tags(event) {
            event.preventDefault();
            
            if (selectedId3SongIds.length === 0) {
                alert('Nenhuma música selecionada.');
                return;
            }
            
            const useAlbum = document.getElementById('bulk-use-album').checked;
            const useYear = document.getElementById('bulk-use-year').checked;
            const useArtist = document.getElementById('bulk-use-artist').checked;
            const useGenre = document.getElementById('bulk-use-genre').checked;
            
            if (!useAlbum && !useYear && !useArtist && !useGenre) {
                alert('Selecione e ative ao menos um campo para realizar a alteração em massa.');
                return;
            }
            
            const payload = {
                ids: selectedId3SongIds,
                update_album: useAlbum,
                album: useAlbum ? document.getElementById('id3-bulk-album').value.trim() : '',
                update_album_year: useYear,
                album_year: useYear ? document.getElementById('id3-bulk-year').value.trim() : '',
                update_artist: useArtist,
                artist: useArtist ? document.getElementById('id3-bulk-artist').value.trim() : '',
                update_genre: useGenre,
                genre: useGenre ? document.getElementById('id3-bulk-genre').value.trim() : ''
            };
            
            try {
                const response = await fetch(API + '?route=update_tracks_bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Username': currentUser.username
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    closeId3BulkModal();
                    clearId3Selection();
                    
                    await loadData();
                    renderId3SongsList();
                    
                    if (typeof selectArtist === 'function' && selectedArtist) {
                        selectArtist(selectedArtist);
                    }
                    
                    alert('Alteração em massa aplicada com sucesso em Músicas.');
                } else {
                    alert('Erro ao atualizar em massa: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (err) {
                console.error(err);
                alert('Erro de conexão ao salvar alterações em massa.');
            }
        }

        function renderId3SongsList() {
            const tbody = document.getElementById('id3-songs-table-body');
            const countEl = document.getElementById('id3-songs-count');
            if (!tbody) return;

            const filtered = allTracks.filter(t => {
                const titleStr = (t.title || '').toLowerCase();
                const artistStr = (t.artist || '').toLowerCase();
                const albumStr = (t.album || '').toLowerCase();
                const genreStr = (t.genre || '').toLowerCase();
                return titleStr.includes(id3SearchQuery) || 
                       artistStr.includes(id3SearchQuery) || 
                       albumStr.includes(id3SearchQuery) ||
                       genreStr.includes(id3SearchQuery);
            });

            const displayed = filtered.slice(0, 50);

            if (countEl) {
                if (filtered.length > 50) {
                    countEl.innerHTML = `Exibindo <span class="font-bold text-sky-400 mx-1">50</span> de <span class="font-bold text-white mx-1">${filtered.length}</span> encontradas`;
                } else {
                    countEl.innerHTML = `<span class="font-bold text-white mx-1">${filtered.length}</span> encontradas`;
                }
            }

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500 italic">Nenhuma música encontrada para a sua busca.</td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = '';
            displayed.forEach((track, index) => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-slate-900/40 border-b border-slate-900/30 transition duration-150 align-middle text-slate-300";
                
                const title = track.title || 'Sem Título';
                const artist = track.artist || 'Artista Desconhecido';
                const album = track.album || 'Álbum Desconhecido';
                const year = track.album_year || '-';
                const genre = track.genre || 'Desconhecido';
                const isChecked = selectedId3SongIds.includes(String(track.id));

                tr.innerHTML = `
                    <td class="py-3 px-4 text-center">
                        <input type="checkbox" class="id3-song-checkbox cursor-pointer w-3.5 h-3.5 text-sky-500 bg-slate-900 border-slate-800 rounded focus:ring-sky-500" value="${track.id}" ${isChecked ? 'checked' : ''} onchange="updateRowCheckbox(this, '${track.id}')">
                    </td>
                    <td class="py-3 px-4 font-mono text-[10px] text-slate-500 select-none">${index + 1}</td>
                    <td class="py-3 px-4 font-bold text-sky-400 max-w-[180px] truncate" title="${title}">${title}</td>
                    <td class="py-3 px-4 text-slate-300 max-w-[140px] truncate" title="${artist}">${artist}</td>
                    <td class="py-3 px-4 text-slate-400 max-w-[140px] truncate" title="${album}">${album}</td>
                    <td class="py-3 px-4 font-mono text-[10px] text-slate-400" title="${year}">${year}</td>
                    <td class="py-3 px-4 text-slate-500 max-w-[100px] truncate" title="${genre}">${genre}</td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <button onclick="openId3EditModal('${track.id}')" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-sky-500 border border-slate-800 text-[10px] text-white font-heavy py-1 px-2.5 rounded-lg transition shrink-0 cursor-pointer hover:border-transparent select-none active:scale-95" title="Editar Tags desta música">
                                <i data-lucide="music" class="w-3 h-3 text-sky-400"></i> Música
                            </button>
                            <button onclick="openAlbumBulkEdit('${track.id}')" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-indigo-650 border border-slate-800 text-[10px] text-white font-heavy py-1 px-2.5 rounded-lg transition shrink-0 cursor-pointer hover:border-transparent select-none active:scale-95" title="Editar Álbum Completo">
                                <i data-lucide="disc" class="w-3 h-3 text-indigo-400"></i> Álbum
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            updateSelectAllCheckboxState();
            lucide.createIcons();
        }

        function openAlbumBulkEdit(trackId) {
            try {
                const baseTrack = allTracks.find(t => String(t.id) === String(trackId));
                if (!baseTrack) {
                    alert('Música base não encontrada para edição.');
                    return;
                }
                openAlbumBulkEditByName(baseTrack.album || 'Álbum Desconhecido');
            } catch (err) {
                console.error("Erro em openAlbumBulkEdit:", err);
                alert("Ocorreu um erro ao abrir: " + err.message);
            }
        }

        window.openAlbumBulkEditByElement = function(element, event) {
            if (event) {
                try {
                    event.preventDefault();
                    event.stopPropagation();
                } catch(e) {}
            }
            if (!element) return;
            
            try {
                const btn = element.closest('button') || element;
                const trackIdsStr = btn.getAttribute('data-track-ids');
                const albumName = btn.getAttribute('data-album-name') || 'Álbum Desconhecido';
                
                if (!trackIdsStr) {
                    openAlbumBulkEditByName(albumName);
                    return;
                }
                const ids = trackIdsStr.split(',').map(id => id.trim().toLowerCase());
                const albumTracks = allTracks.filter(t => t && t.id && ids.includes(String(t.id).trim().toLowerCase()));
                
                if (albumTracks.length === 0) {
                    openAlbumBulkEditByName(albumName);
                    return;
                }
                
                populateAndShowId3AlbumModal(albumTracks, albumName);
            } catch (err) {
                console.error("Erro em openAlbumBulkEditByElement:", err);
            }
        };

        window.openAlbumBulkEditByName = function(albumName, artistName = '') {
            if (!albumName) return;
            try {
                const artistFilter = artistName || selectedArtist || '';
                const lowerAlbumName = albumName.toLowerCase();
                const isEmptyAlbum = lowerAlbumName === 'single' || lowerAlbumName === 'álbum desconhecido' || lowerAlbumName === 'album desconhecido';
                
                let albumTracks = allTracks.filter(t => {
                    const tAlbum = t.album || '';
                    const isAlbumMatch = (isEmptyAlbum && !tAlbum) || (!isEmptyAlbum && tAlbum.toLowerCase() === lowerAlbumName);
                    if (artistFilter) {
                        const tArtist = t.artist || '';
                        const isArtistMatch = (!tArtist && artistFilter.toLowerCase() === 'artista desconhecido') || 
                                              (tArtist && tArtist.toLowerCase() === artistFilter.toLowerCase());
                        return isAlbumMatch && isArtistMatch;
                    }
                    return isAlbumMatch;
                });
                
                if (albumTracks.length === 0 && artistFilter) {
                    albumTracks = allTracks.filter(t => {
                        const tAlbum = t.album || '';
                        return (isEmptyAlbum && !tAlbum) || (!isEmptyAlbum && tAlbum.toLowerCase() === lowerAlbumName);
                    });
                }
                
                if (albumTracks.length === 0) {
                    alert('Nenhuma música encontrada para este álbum: ' + albumName);
                    return;
                }
                
                populateAndShowId3AlbumModal(albumTracks, albumName);
            } catch (err) {
                console.error("Erro em openAlbumBulkEditByName:", err);
            }
        };

        function populateAndShowId3AlbumModal(albumTracks, albumName) {
            try {
                const baseTrack = albumTracks[0] || {};
                
                const titleTextEl = document.getElementById('id3-album-title-text');
                const origNameInput = document.getElementById('id3-album-original-name');
                const nameInput = document.getElementById('id3-album-name');
                const artistInput = document.getElementById('id3-album-artist');
                const genreInput = document.getElementById('id3-album-genre');
                const yearInput = document.getElementById('id3-album-year');
                
                if (titleTextEl) titleTextEl.textContent = albumName;
                if (origNameInput) origNameInput.value = albumName;
                if (nameInput) nameInput.value = albumName;
                if (artistInput) artistInput.value = baseTrack.artist || 'Artista Desconhecido';
                if (genreInput) genreInput.value = baseTrack.genre || 'Desconhecido';
                if (yearInput) yearInput.value = baseTrack.album_year || '';

                const listEl = document.getElementById('id3-album-songs-list');
                const countEl = document.getElementById('id3-album-songs-count');
                if (countEl) countEl.textContent = albumTracks.length;
                if (listEl) {
                    listEl.innerHTML = '';
                    albumTracks.forEach((t, i) => {
                        const div = document.createElement('div');
                        div.className = "py-3 flex flex-col gap-1.5 border-b border-slate-900/40 last:border-0";
                        div.innerHTML = `
                            <div class="flex items-center justify-between text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                <span>Música #${i+1}</span>
                                ${t.duration ? `<span class="font-mono text-[9px]">${formatSecs(t.duration)}</span>` : ''}
                            </div>
                            <input type="text" data-track-id="${t.id}" autocomplete="off" class="id3-album-track-title-input w-full bg-slate-950 border border-slate-900 text-sky-400 p-2.5 text-xs rounded-xl outline-none focus:border-sky-500/50 transition font-semibold" value="${String(t.title || '').replace(/"/g, '&quot;')}">
                        `;
                        listEl.appendChild(div);
                    });
                }

                const modalEl = document.getElementById('id3-album-modal');
                if (modalEl) {
                    modalEl.classList.remove('hidden');
                    // Ensure absolute top zindex
                    modalEl.style.zIndex = '999999';
                    modalEl.style.display = 'flex';
                }
                
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                } else if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            } catch (err) {
                console.error("Erro interno modal id3:", err);
            }
        }

        function closeId3AlbumModal() {
            const m = document.getElementById('id3-album-modal');
            if(m) {
                m.classList.add('hidden');
                m.style.display = 'none';
            }
        }

        async function saveAlbumId3Tags(event) {
            event.preventDefault();

            const oldAlbumName = document.getElementById('id3-album-original-name').value;
            const newAlbumName = document.getElementById('id3-album-name').value.trim();
            const newArtist = document.getElementById('id3-album-artist').value.trim();
            const newGenre = document.getElementById('id3-album-genre').value.trim();
            const newYear = document.getElementById('id3-album-year').value.trim();

            if (!newAlbumName) {
                alert('O Nome do Álbum é obrigatório.');
                return;
            }

            // Find all track inputs inside the modal
            const inputs = document.querySelectorAll('.id3-album-track-title-input');
            const tracksData = [];
            let hasEmptyTitle = false;

            inputs.forEach(input => {
                const id = input.getAttribute('data-track-id'); // Ensure UUIDs string works
                const title = input.value.trim();
                if (!title) {
                    hasEmptyTitle = true;
                }
                tracksData.push({ id, title });
            });

            if (hasEmptyTitle) {
                alert('O título de todas as músicas deve ser preenchido.');
                return;
            }

            const payload = {
                global: {
                    album: newAlbumName,
                    artist: newArtist,
                    genre: newGenre,
                    album_year: newYear
                },
                tracks: tracksData
            };

            try {
                const response = await fetch(API + '?route=update_album_tracks_metadata', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Username': currentUser.username
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    closeId3AlbumModal();
                    
                    // Reload data
                    await loadData();
                    
                    // Refresh current view (supports ID3 editor tab or general artist view)
                    if (typeof renderId3SongsList === 'function') {
                        renderId3SongsList();
                    }
                    
                    if (typeof selectArtist === 'function' && selectedArtist) {
                        selectArtist(selectedArtist);
                    }
                    
                    alert('Álbum e músicas alterados com sucesso (' + result.affected + ' músicas atualizadas).');
                } else {
                    alert('Erro ao atualizar o álbum: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (err) {
                console.error(err);
                alert('Erro de conexão ao salvar alterações do álbum.');
            }
        }

        function openId3EditModal(songId) {
            const track = allTracks.find(t => t.id == songId);
            if (!track) return;

            document.getElementById('id3-edit-song-id').value = track.id;
            document.getElementById('id3-edit-title').value = track.title || '';
            document.getElementById('id3-edit-artist').value = track.artist || '';
            document.getElementById('id3-edit-album').value = track.album || '';
            document.getElementById('id3-edit-genre').value = track.genre || '';
            document.getElementById('id3-edit-year').value = track.album_year || '';

            const modal = document.getElementById('id3-edit-modal');
            modal.classList.remove('hidden');
            modal.style.zIndex = '99999';
            modal.style.display = 'flex';
            if(window.lucide) window.modal.style.display = 'flex';
            if(window.lucide) window.lucide.createIcons();
        }

        function closeId3EditModal() {
            const m = document.getElementById('id3-edit-modal');
            if(m) {
                m.classList.add('hidden');
                m.style.display = 'none';
            }
        }

        async function saveId3Tags(event) {
            event.preventDefault();
            const id = document.getElementById('id3-edit-song-id').value;
            const title = document.getElementById('id3-edit-title').value.trim();
            const artist = document.getElementById('id3-edit-artist').value.trim();
            const album = document.getElementById('id3-edit-album').value.trim();
            const genre = document.getElementById('id3-edit-genre').value.trim();
            const album_year = document.getElementById('id3-edit-year').value.trim();

            if (!id || !title) {
                alert('O ID e o Título são obrigatórios.');
                return;
            }

            try {
                const response = await fetch(API + '?route=update_track_title', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Username': currentUser.username
                    },
                    body: JSON.stringify({ id, title, artist, album, genre, album_year })
                });
                const result = await response.json();
                if (result.success) {
                    closeId3EditModal();
                    
                    // Reload data from api
                    await loadData();
                    
                    // Refresh the ID3 list
                    renderId3SongsList();
                    
                    // Refresh the active list view in player dashboard
                    if (typeof selectArtist === 'function' && selectedArtist) {
                        selectArtist(selectedArtist);
                    }
                } else {
                    alert('Erro ao atualizar ID3: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (err) {
                console.error(err);
                alert('Erro de conexão ao salvar tags ID3.');
            }
        }

        let fileManagerCurrentPath = '';
        let fileUploadQueue = [];
        let currentlyUploading = false;

        async function loadFileManager(path = '') {
            try {
                const res = await fetch(`api.php?route=files_list&path=${encodeURIComponent(path)}`);
                const data = await res.json();
                if (data.success) {
                    fileManagerCurrentPath = data.current_path;
                    renderFileManager(data);
                } else {
                    alert(data.error || 'Erro ao carregar arquivos');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao carregar o gerenciador de arquivos.');
            }
        }

        function renderFileManager(data) {
            const tbody = document.getElementById('file-manager-table-body');
            if (!tbody) return;
            tbody.innerHTML = '';

            // Toggle new folder & upload buttons visibility at virtual root
            const btnNewFolder = document.getElementById('file-manager-btn-new-folder');
            const btnUpload = document.getElementById('file-manager-btn-upload');
            if (btnNewFolder) {
                if (data.is_root) {
                    btnNewFolder.classList.add('hidden');
                } else {
                    btnNewFolder.classList.remove('hidden');
                }
            }
            if (btnUpload) {
                if (data.is_root) {
                    btnUpload.classList.add('hidden');
                } else {
                    btnUpload.classList.remove('hidden');
                }
            }

            // Breadcrumbs rendering
            const crumbsEl = document.getElementById('file-manager-breadcrumbs');
            if (crumbsEl) {
                crumbsEl.innerHTML = '';
                
                const rootBtn = document.createElement('button');
                rootBtn.className = "hover:text-white font-bold flex items-center gap-1 cursor-pointer transition text-slate-400";
                rootBtn.innerHTML = '<i data-lucide="hard-drive" class="w-3.5 h-3.5"></i> Raiz';
                rootBtn.onclick = () => loadFileManager('');
                crumbsEl.appendChild(rootBtn);

                if (data.current_path) {
                    const parts = data.current_path.split('/');
                    let accumulated = '';
                    parts.forEach((p, index) => {
                        accumulated = accumulated ? accumulated + '/' + p : p;
                        
                        const separator = document.createElement('span');
                        separator.textContent = ' / ';
                        separator.className = 'text-slate-600 select-none mx-0.5';
                        crumbsEl.appendChild(separator);
                        
                        const partBtn = document.createElement('button');
                        partBtn.className = (index === parts.length - 1)
                            ? "text-sky-400 font-extrabold"
                            : "hover:text-white cursor-pointer transition text-slate-350";
                        partBtn.textContent = p;
                        const targetLoc = accumulated;
                        partBtn.onclick = () => loadFileManager(targetLoc);
                        crumbsEl.appendChild(partBtn);
                    });
                }
            }

            // Folder details count
            const infoEl = document.getElementById('file-manager-info');
            if (infoEl) {
                const dirsCount = data.items.filter(i => i.is_dir).length;
                const filesCount = data.items.filter(i => !i.is_dir).length;
                infoEl.textContent = `${dirsCount} pastas, ${filesCount} arquivos`;
            }

            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="p-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="folder-open" class="w-8 h-8 text-slate-700"></i>
                                <span>Esta pasta está vazia de momento.</span>
                            </div>
                        </td>
                    </tr>
                `;
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
                return;
            }

            data.items.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = "border-b border-slate-900/30 hover:bg-slate-950/25 transition-all text-slate-300";

                const iconHtml = getFileIconHtml(item.name, item.is_dir);
                let onclickAttr = '';
                let cursorClass = '';
                if (item.is_dir) {
                    onclickAttr = `onclick="loadFileManager('${item.path.replace(/'/g, "\'")}')"`;
                    cursorClass = 'cursor-pointer select-none group';
                }

                const dateStr = new Date(item.mtime * 1000).toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const sizeStr = item.is_dir ? '-' : formatBytes(item.size);

                const actButtons = data.is_root ? '' : `
                    <div class="flex items-center justify-end gap-1">
                        <button onclick="handleRenameFileManagerItem('${item.path.replace(/'/g, "\'")}', '${item.name.replace(/'/g, "\'")}')" class="p-1.5 text-slate-500 hover:text-sky-400 transition cursor-pointer" title="Renomear">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        </button>
                        <button onclick="handleDeleteFileManagerItem('${item.path.replace(/'/g, "\'")}', ${item.is_dir})" class="p-1.5 text-slate-500 hover:text-rose-400 transition cursor-pointer" title="Excluir">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                `;
                                tr.innerHTML = `
                    <td class="p-4 pl-5 ${cursorClass}" ${onclickAttr}>
                        <div class="flex items-center gap-2.5 min-w-0 max-w-sm md:max-w-md">
                            ${iconHtml}
                            <span class="truncate font-medium text-slate-205 group-hover:text-sky-400 transition" title="${item.name}">${item.name}</span>
                        </div>
                    </td>
                    <td class="p-4 hidden sm:table-cell text-slate-500 font-mono text-[11px]">${sizeStr}</td>
                    <td class="p-4 hidden md:table-cell text-slate-500 font-mono text-[11px]">${dateStr}</td>
                    <td class="p-4 text-right pr-5">${actButtons}</td>
                `;

                tbody.appendChild(tr);
            });

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                lucide.createIcons();
            }
        }

        function getFileIconHtml(filename, isDir) {
            if (isDir) {
                return '<i data-lucide="folder" class="w-4 h-4 text-sky-400 shrink-0"></i>';
            }
            const ext = filename.split('.').pop().toLowerCase();
            if (['mp3', 'm4a', 'wav', 'ogg', 'flac'].includes(ext)) {
                return '<i data-lucide="music-4" class="w-4 h-4 text-emerald-400 shrink-0"></i>';
            }
            if (['mp4', 'mkv', 'avi', 'mov', 'webm'].includes(ext)) {
                return '<i data-lucide="video" class="w-4 h-4 text-purple-400 shrink-0"></i>';
            }
            if (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'].includes(ext)) {
                return '<i data-lucide="image" class="w-4 h-4 text-amber-400 shrink-0"></i>';
            }
            if (['db', 'sqlite', 'sql'].includes(ext)) {
                return '<i data-lucide="database" class="w-4 h-4 text-rose-500 shrink-0"></i>';
            }
            if (['php', 'ts', 'js', 'html', 'css', 'json'].includes(ext)) {
                return '<i data-lucide="code" class="w-4 h-4 text-sky-500 shrink-0"></i>';
            }
            return '<i data-lucide="file-text" class="w-4 h-4 text-slate-400 shrink-0"></i>';
        }

        function formatBytes(bytes, decimals = 1) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function openNewFolderModal() {
            const name = prompt("Insira o nome da nova pasta:");
            if (!name) return;
            createNewFolder(name);
        }

        async function createNewFolder(name) {
            try {
                const formData = new FormData();
                formData.append('path', fileManagerCurrentPath);
                formData.append('name', name);

                const res = await fetch('api.php?route=files_create_dir', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    loadFileManager(fileManagerCurrentPath);
                } else {
                    alert(data.error || "Erro ao criar pasta");
                }
            } catch (err) {
                console.error(err);
                alert("Erro de rede ao criar pasta.");
            }
        }

        function handleRenameFileManagerItem(itemPath, oldName) {
            const newName = prompt("Renomear arquivo ou pasta para:", oldName);
            if (!newName || newName === oldName) return;

            const parts = itemPath.split('/');
            parts[parts.length - 1] = newName;
            const newPath = parts.join('/');

            renameItem(itemPath, newPath);
        }

        async function renameItem(oldPath, newPath) {
            try {
                const formData = new FormData();
                formData.append('old_path', oldPath);
                formData.append('new_path', newPath);

                const res = await fetch('api.php?route=files_rename', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    loadFileManager(fileManagerCurrentPath);
                } else {
                    alert(data.error || "Erro ao renomear");
                }
            } catch (err) {
                console.error(err);
                alert("Erro de rede ao renomear.");
            }
        }

        async function handleDeleteFileManagerItem(itemPath, isDir) {
            const msg = isDir
                ? "Tem certeza que deseja excluir esta pasta e TODO o seu conteúdo de forma definitiva e irreversível?"
                : "Tem certeza que deseja excluir este arquivo?";
            if (!confirm(msg)) return;

            try {
                const formData = new FormData();
                formData.append('path', itemPath);

                const res = await fetch('api.php?route=files_delete', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    loadFileManager(fileManagerCurrentPath);
                } else {
                    alert(data.error || "Erro ao excluir");
                }
            } catch (err) {
                console.error(err);
                alert("Erro de rede ao excluir.");
            }
        }

        function handleFileManagerUpload(files) {
            if (fileManagerCurrentPath === '') {
                alert('Não é permitido enviar arquivos diretamente na pasta raiz virtual. Entre em /music ou /videos primeiro.');
                return;
            }
            if (!files || files.length === 0) return;
            for (let i = 0; i < files.length; i++) {
                fileUploadQueue.push(files[i]);
            }
            processNextUpload();
        }

        async function processNextUpload() {
            if (currentlyUploading || fileUploadQueue.length === 0) {
                if (fileUploadQueue.length === 0 && !currentlyUploading) {
                    setTimeout(() => {
                        const progressEl = document.getElementById('file-manager-upload-progress');
                        if (progressEl) progressEl.classList.add('hidden');
                    }, 1000);
                }
                return;
            }

            currentlyUploading = true;
            const file = fileUploadQueue.shift();

            const progressEl = document.getElementById('file-manager-upload-progress');
            if (progressEl) progressEl.classList.remove('hidden');

            const pText = document.getElementById('upload-progress-text');
            if (pText) pText.textContent = `Enviando: ${file.name} (${formatBytes(file.size)}) ... [Pendentes: ${fileUploadQueue.length}]`;

            try {
                const formData = new FormData();
                formData.append('path', fileManagerCurrentPath);
                formData.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'api.php?route=files_upload', true);

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        const bar = document.getElementById('upload-progress-bar');
                        if (bar) bar.style.width = percent + '%';
                        const pctText = document.getElementById('upload-progress-percent');
                        if (pctText) pctText.textContent = percent + '%';
                    }
                };

                xhr.onload = function() {
                    currentlyUploading = false;
                    const bar = document.getElementById('upload-progress-bar');
                    if (bar) bar.style.width = '0%';

                    if (xhr.status === 200) {
                        try {
                            const resObj = JSON.parse(xhr.responseText);
                            if (!resObj.success) {
                                alert(`Falha ao enviar ${file.name}: ` + (resObj.error || 'Erro desconhecido'));
                            }
                        } catch(e) {
                            console.error(e);
                        }
                    } else {
                        alert(`Erro no servidor (${xhr.status}) ao enviar ${file.name}`);
                    }

                    loadFileManager(fileManagerCurrentPath);
                    processNextUpload();
                };

                xhr.onerror = function() {
                    currentlyUploading = false;
                    alert(`Falha na rede para ${file.name}`);
                    processNextUpload();
                };

                xhr.send(formData);

            } catch (err) {
                currentlyUploading = false;
                console.error(err);
                processNextUpload();
            }
        }

        function handleFileDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            const overlay = document.getElementById('file-manager-drag-overlay');
            if (overlay) {
                overlay.style.opacity = '1';
                overlay.style.pointerEvents = 'auto';
            }
        }

        function handleFileDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            const overlay = document.getElementById('file-manager-drag-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
            }
        }

        function handleFileDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            const overlay = document.getElementById('file-manager-drag-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
            }

            const files = e.dataTransfer.files;
            handleFileManagerUpload(files);
        }

        async function loadMusicFolders() {
            const tbody = document.getElementById('music-folders-table-body');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-slate-500"><i class="animate-spin inline-block w-3 h-3 mr-1" data-lucide="refresh-cw"></i> Carregando pastas...</td></tr>';
            lucide.createIcons();
            try {
                const res = await fetch('api.php?route=music_folders');
                if (res.ok) {
                    const folders = await res.json();
                    if (!folders || folders.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-slate-500">Nenhuma pasta detectada sob o diretório /music/.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = '';
                    folders.forEach(function(f) {
                        let sizeStr = '0 B';
                        const bytes = f.sizeInBytes || 0;
                        if (bytes > 0) {
                            const k = 1024;
                            const sizes = ['B', 'KB', 'MB', 'GB'];
                            const i = Math.floor(Math.log(bytes) / Math.log(k));
                            sizeStr = parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                        }

                        tbody.innerHTML += '<tr class="hover:bg-slate-900/20">' +
                            '<td class="py-3 font-bold text-white flex items-center gap-1.5"><i data-lucide="folder" class="w-4 h-4 text-sky-400"></i> ' + f.name + '</td>' +
                            '<td class="py-3 text-slate-400 font-mono">' + f.fileCount + ' música(s)</td>' +
                            '<td class="py-3 text-slate-400 font-mono">' + sizeStr + '</td>' +
                            '<td class="py-3 text-right">' +
                                '<button onclick="deleteMusicFolder(' + "'" + encodeURIComponent(f.name) + "'" + ')" class="px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 border border-red-500/20 active:scale-95 text-[10px] font-bold rounded-lg transition cursor-pointer">' +
                                    '<i data-lucide="trash-2" class="w-3 h-3 inline"></i> Excluir' +
                                '</button>' +
                            '</td>' +
                        '</tr>';
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-red-550">Falha ao se conectar com api.php?route=music_folders.</td></tr>';
                }
            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-red-550">Erro de rede ao carregar pastas.</td></tr>';
            }
            lucide.createIcons();
        }

        async function deleteMusicFolder(folderName) {
            const decName = decodeURIComponent(folderName);
            if (!confirm('Tem certeza absoluta de que deseja excluir a pasta "' + decName + '" no servidor? Isso apagará todos os arquivos contidos nela de forma irreversível.')) {
                return;
            }
            try {
                const res = await fetch('api.php?route=delete_music_folder&name=' + folderName, { method: 'POST' });
                const data = await res.json();
                if (res.ok) {
                    alert(data.message || 'Pasta excluída com sucesso!');
                    await loadData();
                    await loadMusicFolders();
                } else {
                    alert(data.error || 'Erro ao excluir pasta.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro na requisição ao api.php?route=delete_music_folder.');
            }
        }

        window.handleMyPasswordChange = async function(e) {
            e.preventDefault();
            const passInput = document.getElementById('my-new-password');
            const passVal = passInput.value.trim();
            if (!passVal) {
                alert('A nova senha não pode ser vazia.');
                return;
            }
            try {
                const res = await fetch(API + '?route=users&username=' + encodeURIComponent(currentUser.username), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: passVal })
                });
                if (res.ok) {
                    alert('Sua senha foi atualizada com sucesso!');
                    passInput.value = '';
                } else {
                    alert('Erro ao atualizar sua senha no servidor.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro na conexão com o servidor ao alterar senha.');
            }
        };

        window.runDatabaseRepair = async function(btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Reparando...';
            lucide.createIcons();
            try {
                const res = await fetch(API + '?route=repair_db');
                const data = await res.json();
                if (data.status === 'ok') {
                    alert(data.message);
                    await loadData(); // Reload catalog to reflect changes!
                    renderAlbumGrid();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('Erro na conexão para reparação do banco.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        };
 
        window.translations = {
            pt: {
                "sidebar-title": "Biblioteca",
                "sidebar-dashboard": "Dashboard",
                "sidebar-songs": "Minhas Músicas",
                "sidebar-favorites": "Favoritos",
                "sidebar-videos": "Galeria de Vídeos",
                "sidebar-settings": "Configurações",
                "sidebar-artists": "Artistas",
                "sidebar-playlists": "Playlists",
                "sidebar-clear-filter": "Limpar",
                
                "welcome-title": "Bem-vindo de volta!",
                "welcome-sub": "Servidor PHPlayer",
                "clock-sync": "Sincronizado",
                
                "stat-collection": "Coleção",
                "stat-albums": "Álbuns únicos",
                "stat-artists": "Artistas únicos",
                "stat-favorites": "Favoritos",
                "album-collection-title": "Coleção de Álbuns",
                "btn-random-album": "Tocar Álbum Aleatório",
                "dashboard-search-placeholder": "Buscar álbuns...",
                
                "search-placeholder": "Pesquisar título, artista, álbum...",
                "title-library": "Minha Biblioteca",
                
                "col-idx": "#",
                "col-track": "Faixa",
                "col-artist": "Artista",
                "col-album": "Álbum",
                "col-operations": "Operações",
                
                "config-panel-title": "Painel de Configurações",
                "config-panel-sub": "Personalize as cores do site e gerencie a biblioteca local de músicas e vídeos.",
                
                "subnav-themes": "Coloração & Temas",
                "subnav-sync": "Sincronização e Mídia",
                "subnav-users": "Editar Usuários",
                "subnav-password": "Alterar Senha",
                "subnav-files": "Arquivos",
                
                "theme-choose-title": "Escolha sua cor de realce e fundo",
                "theme-choose-desc": "Selecione abaixo o seu tema de cores. Ele mudará os ícones, botões de reprodução, barras de progresso e a cor de fundo do site.",
                "theme-apply-btn": "Aplicar Tema",
                
                "lang-choose-title": "Escolha o idioma do sistema",
                "lang-choose-desc": "Escolha entre Português, Inglês ou Espanhol para traduzir a interface do player.",
                
                "theme-default-title": "Azul Celeste",
                "theme-default-desc": "O visual clássico do player com tons azuis elegantes.",
                "theme-emerald-title": "Verde Esmeralda",
                "theme-emerald-desc": "Visual inspirado em florestas com tons verdes vibrantes.",
                "theme-rose-title": "Rosa Sunset",
                "theme-rose-desc": "Sensações acolhedoras de fim de tarde e tons quentes de rosa.",
                "theme-amber-title": "Nascer do Sol",
                "theme-amber-desc": "Aparência ensolarada e aconchegante em ouro vibrante.",
                "theme-violet-title": "Roxo Ametista",
                "theme-violet-desc": "Clima místico e tecnológico em violeta profundo e rico.",
                "theme-crimson-title": "Vermelho Carmesim",
                "theme-crimson-desc": "Contrastes intensos e fortes com tonalidades rubi profundas."
            },
            en: {
                "sidebar-title": "Library",
                "sidebar-dashboard": "Dashboard",
                "sidebar-songs": "My Music",
                "sidebar-favorites": "Favorites",
                "sidebar-videos": "Video Gallery",
                "sidebar-settings": "Settings",
                "sidebar-artists": "Artists",
                "sidebar-playlists": "Playlists",
                "sidebar-clear-filter": "Clear",
                
                "welcome-title": "Welcome back!",
                "welcome-sub": "PHPlayer Server",
                "clock-sync": "Synchronized",
                
                "stat-collection": "Collection",
                "stat-albums": "Unique Albums",
                "stat-artists": "Unique Artists",
                "stat-favorites": "Favorites",
                "album-collection-title": "Album Collection",
                "btn-random-album": "Play Random Album",
                "dashboard-search-placeholder": "Search albums...",
                
                "search-placeholder": "Search title, artist, album...",
                "title-library": "My Library",
                
                "col-idx": "#",
                "col-track": "Track",
                "col-artist": "Artist",
                "col-album": "Album",
                "col-operations": "Operations",
                
                "config-panel-title": "Settings Panel",
                "config-panel-sub": "Customize site colors and manage the local music and video library.",
                
                "subnav-themes": "Colors & Themes",
                "subnav-sync": "Sync & Media",
                "subnav-users": "Edit Users",
                "subnav-password": "Change Password",
                "subnav-files": "Files",
                
                "theme-choose-title": "Choose your highlight & background",
                "theme-choose-desc": "Select your color theme below. It will change icons, play buttons, progress bars and background color.",
                "theme-apply-btn": "Apply Theme",
                
                "lang-choose-title": "Choose system language",
                "lang-choose-desc": "Choose between Portuguese, English or Spanish to translate the player interface.",
                
                "theme-default-title": "Sky Blue",
                "theme-default-desc": "The classic player look with elegant blue tones.",
                "theme-emerald-title": "Emerald Green",
                "theme-emerald-desc": "Forest-inspired look with vibrant green tones.",
                "theme-rose-title": "Sunset Pink",
                "theme-rose-desc": "Cozy late afternoon feelings and warm pink tones.",
                "theme-amber-title": "Sunrise Gold",
                "theme-amber-desc": "Sunny and cozy look in vibrant gold.",
                "theme-violet-title": "Amethyst Purple",
                "theme-violet-desc": "Mystical and techy vibe in deep, rich violet.",
                "theme-crimson-title": "Crimson Red",
                "theme-crimson-desc": "Intense and strong contrasts with deep ruby shades."
            },
            es: {
                "sidebar-title": "Biblioteca",
                "sidebar-dashboard": "Dashboard",
                "sidebar-songs": "Mis Canciones",
                "sidebar-favorites": "Favoritos",
                "sidebar-videos": "Galería de Videos",
                "sidebar-settings": "Configuraciones",
                "sidebar-artists": "Artistas",
                "sidebar-playlists": "Listas de reproducción",
                "sidebar-clear-filter": "Limpiar",
                
                "welcome-title": "¡Bienvenido de nuevo!",
                "welcome-sub": "Servidor PHPlayer",
                "clock-sync": "Sincronizado",
                
                "stat-collection": "Colección",
                "stat-albums": "Álbumes únicos",
                "stat-artists": "Artistas únicos",
                "stat-favorites": "Favoritos",
                "album-collection-title": "Colección de Álbumes",
                "btn-random-album": "Tocar Álbum Aleatorio",
                "dashboard-search-placeholder": "Buscar álbumes...",
                
                "search-placeholder": "Buscar título, artista, álbum...",
                "title-library": "Mi Biblioteca",
                
                "col-idx": "#",
                "col-track": "Pista",
                "col-artist": "Artista",
                "col-album": "Álbum",
                "col-operations": "Operaciones",
                
                "config-panel-title": "Panel de Configuraciones",
                "config-panel-sub": "Personaliza los colores del sitio y gestiona la biblioteca local de música y videos.",
                
                "subnav-themes": "Colores y Temas",
                "subnav-sync": "Sincronización y Medios",
                "subnav-users": "Editar Usuarios",
                "subnav-password": "Cambiar Contraseña",
                "subnav-files": "Archivos",
                
                "theme-choose-title": "Elija su color de realce y fondo",
                "theme-choose-desc": "Seleccione abajo su tema de colores. Cambiará los iconos, botones de reproducción, barra de progreso y fondo.",
                "theme-apply-btn": "Aplicar Tema",
                
                "lang-choose-title": "Elija el idioma del sistema",
                "lang-choose-desc": "Elija entre portugués, inglés o español para traducir la interfaz del reproductor.",
                
                "theme-default-title": "Azul Celeste",
                "theme-default-desc": "Aspecto clásico del reproductor con elegantes tonos azules.",
                "theme-emerald-title": "Verde Esmeralda",
                "theme-emerald-desc": "Aspecto inspirado en bosques con tonos verdes vibrantes.",
                "theme-rose-title": "Rosa Sunset",
                "theme-rose-desc": "Sensaciones cálidas de atardecer y tonos rosados cálidos.",
                "theme-amber-title": "Amanecer Dorado",
                "theme-amber-desc": "Aspecto sobrecogedor en oro vibrante.",
                "theme-violet-title": "Amatista Púrpura",
                "theme-violet-desc": "Clima místico y tecnológico en violeta profundo y rico.",
                "theme-crimson-title": "Rojo Carmesín",
                "theme-crimson-desc": "Contrastes intensos y fortes con tonalidades rubí profundas."
            }
        };

        window.selectDesktopLang = function(lang) {
            applySystemLanguage(lang);
        };

        window.applySystemLanguage = function(lang) {
            localStorage.setItem('phplayer_lang', lang);
            const trans = window.translations[lang] || window.translations['pt'];
            
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (trans[key]) {
                    const icon = el.querySelector('i, svg');
                    const sortingSpan = el.querySelector('span[id^="sort-icon"]');
                    let iconHtml = icon ? icon.outerHTML + ' ' : '';
                    let sortHtml = sortingSpan ? ' ' + sortingSpan.outerHTML : '';
                    if (icon || sortingSpan) {
                        el.innerHTML = iconHtml + trans[key] + sortHtml;
                    } else {
                        el.textContent = trans[key];
                    }
                }
            });

            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                if (trans[key]) {
                    el.placeholder = trans[key];
                }
            });

            const pWelcome = document.getElementById('dashboard-welcome-title');
            if (pWelcome && currentUser) {
                if (lang === 'en') {
                    pWelcome.innerHTML = `Welcome back <span class="text-sky-400 font-extrabold">${currentUser.username}</span>!`;
                } else if (lang === 'es') {
                    pWelcome.innerHTML = `¡Bienvenido de nuevo <span class="text-sky-400 font-extrabold">${currentUser.username}</span>!`;
                } else {
                    pWelcome.innerHTML = `Bem-vindo de volta <span class="text-sky-400 font-extrabold">${currentUser.username}</span>!`;
                }
            }

            const langs = ['pt', 'en', 'es'];
            langs.forEach(l => {
                const ind = document.getElementById('indicator-lang-' + l);
                const card = ind ? ind.parentElement : null;
                if (ind && card) {
                    if (l === lang) {
                        ind.classList.remove('hidden');
                        card.className = "lang-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-center bg-slate-900 border-sky-500 shadow-md transition select-none w-full";
                    } else {
                        ind.classList.add('hidden');
                        card.className = "lang-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-center bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none w-full";
                    }
                }
            });

            // Translate the static Sort options
            const sortSelect = document.getElementById('php-dashboard-sort-filter');
            if (sortSelect) {
                const optRandom = sortSelect.querySelector('option[value="random"]');
                const optAlpha = sortSelect.querySelector('option[value="alphabetical"]');
                const optRecent = sortSelect.querySelector('option[value="recent"]');
                if (lang === 'en') {
                    if (optRandom) optRandom.textContent = 'Random';
                    if (optAlpha) optAlpha.textContent = 'Alphabetical';
                    if (optRecent) optRecent.textContent = 'Most Recent';
                } else if (lang === 'es') {
                    if (optRandom) optRandom.textContent = 'Aleatorios';
                    if (optAlpha) optAlpha.textContent = 'Orden Alfabético';
                    if (optRecent) optRecent.textContent = 'Más Recientes';
                } else {
                    if (optRandom) optRandom.textContent = 'Aleatórios';
                    if (optAlpha) optAlpha.textContent = 'Ordem Alfabética';
                    if (optRecent) optRecent.textContent = 'Mais Recentes';
                }
            }

            // Also update the Genres select dropdown with translated placeholder
            updateDashboardGenresDropdown();

            lucide.createIcons();
        };

        let selectedDesktopTheme = 'default';

        function selectDesktopTheme(themeName) {
            selectedDesktopTheme = themeName;
            applyUserTheme(themeName);
            updateThemeCardsUI();
        }

        async function saveDesktopTheme() {
            try {
                const res = await fetch('api.php?route=users&username=' + encodeURIComponent(currentUser.username), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: selectedDesktopTheme })
                });
                if (res.ok) {
                    currentUser.theme = selectedDesktopTheme;
                    localStorage.setItem('phplayer_user', JSON.stringify(currentUser));
                    window.location.reload();
                } else {
                    alert('Erro ao atualizar seu tema de cores no banco de dados.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro na conexão para trocar de tema.');
            }
        }

        function applyUserTheme(themeName) {
            const active = themeName || 'default';
            document.documentElement.setAttribute('data-theme', active);

            let styleTag = document.getElementById('custom-theme-style');
            if (active.indexOf('custom:') === 0) {
                const hexColor = active.replace('custom:', '');
                if (/^#[0-9a-f]{6}$/i.test(hexColor)) {
                    const r = parseInt(hexColor.slice(1, 3), 16);
                    const g = parseInt(hexColor.slice(3, 5), 16);
                    const b = parseInt(hexColor.slice(5, 7), 16);
                    
                    const adjust = (r, g, b, pct) => {
                        const nr = Math.min(255, Math.max(0, Math.round(r + (255 - r) * pct)));
                        const ng = Math.min(255, Math.max(0, Math.round(g + (255 - g) * pct)));
                        const nb = Math.min(255, Math.max(0, Math.round(b + (255 - b) * pct)));
                        return "#" + nr.toString(16).padStart(2, '0') + ng.toString(16).padStart(2, '0') + nb.toString(16).padStart(2, '0');
                    };
                    const darken = (r, g, b, pct) => {
                        const nr = Math.min(255, Math.max(0, Math.round(r * (1 - pct))));
                        const ng = Math.min(255, Math.max(0, Math.round(g * (1 - pct))));
                        const nb = Math.min(255, Math.max(0, Math.round(b * (1 - pct))));
                        return "#" + nr.toString(16).padStart(2, '0') + ng.toString(16).padStart(2, '0') + nb.toString(16).padStart(2, '0');
                    };
                    
                    const sky300 = adjust(r, g, b, 0.4);
                    const sky400 = adjust(r, g, b, 0.2);
                    const sky550 = adjust(r, g, b, 0.1);
                    const sky500 = hexColor;
                    const sky600 = darken(r, g, b, 0.2);
                    const indigo500 = hexColor;
                    const indigo600 = darken(r, g, b, 0.35);
                    
                    const css = ':root, :root[data-theme^="custom:"] { ' +
                        '--theme-sky-300: ' + sky300 + ' !important; ' +
                        '--theme-sky-400: ' + sky400 + ' !important; ' +
                        '--theme-sky-450: ' + sky550 + ' !important; ' +
                        '--theme-sky-500: ' + sky500 + ' !important; ' +
                        '--theme-sky-600: ' + sky600 + ' !important; ' +
                        '--theme-indigo-500: ' + indigo500 + ' !important; ' +
                        '--theme-indigo-600: ' + indigo600 + ' !important; ' +
                    '}';
                    if (!styleTag) {
                        styleTag = document.createElement('style');
                        styleTag.id = 'custom-theme-style';
                        document.head.appendChild(styleTag);
                    }
                    styleTag.textContent = css;
                }
            } else {
                if (styleTag) styleTag.textContent = '';
            }
        }

        window.onPhpCustomColorChange = function(val) {
            applyUserTheme('custom:' + val);
            const ind = document.getElementById('indicator-theme-custom');
            if (ind) {
                ind.style.backgroundColor = val;
            }
        };

        window.onPhpLayoutBgLiveChange = function(val) {
            const hexPattern = /^#[0-9a-f]{6}$/i;
            if (hexPattern.test(val)) {
                const picker = document.getElementById('php-layout-bg-picker');
                const text = document.getElementById('php-layout-bg-text');
                if (picker && picker.value !== val) picker.value = val;
                if (text && text.value !== val) text.value = val;

                if (currentUser) {
                    currentUser.sidebarBg = val;
                    currentUser.footerBg = val;
                    currentUser.topBg = val;
                    applyUserLayoutBg();
                }
            }
        };

        window.applyPhpLayoutColor = async function(val) {
            window.onPhpLayoutBgLiveChange(val);
            await window.savePhpLayoutColor();
        };

        window.savePhpLayoutColor = async function() {
            if (!currentUser) return;
            const chosenBg = currentUser.sidebarBg || '#020617';
            try {
                const response = await fetch('api.php?route=users&username=' + encodeURIComponent(currentUser.username), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sidebarBg: chosenBg })
                });
                const res = await response.json();
                if (res.success) {
                    localStorage.setItem('phplayer_user', JSON.stringify(currentUser));
                    alert('Cor do layout salva com sucesso!');
                } else {
                    alert('Erro ao salvar cor do layout.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de conexão ao salvar cor.');
            }
        };

        function updateThemeCardsUI() {
            const activeShow = selectedDesktopTheme || (currentUser ? (currentUser.theme || 'default') : 'default');
            const themes = ['default', 'emerald', 'rose', 'amber', 'violet', 'crimson'];
            themes.forEach(th => {
                const ind = document.getElementById('indicator-theme-' + th);
                if (ind) {
                    if (activeShow === th) {
                        ind.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-white"></i>';
                        ind.parentElement.className = 'theme-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-start bg-slate-900 border-sky-500 shadow-md transition select-none';
                    } else {
                        ind.innerHTML = '';
                        ind.parentElement.className = 'theme-card relative p-4 rounded-xl border text-left cursor-pointer flex gap-4 items-start bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none';
                    }
                }
            });

            // Special handling for Custom Theme card inside updateThemeCardsUI
            const customInd = document.getElementById('indicator-theme-custom');
            const customCard = document.getElementById('php-custom-theme-card');
            const customBadge = document.getElementById('php-custom-badge');
            const customPicker = document.getElementById('php-custom-color-picker');

            if (customCard && customInd) {
                if (activeShow.indexOf('custom:') === 0) {
                    const customHex = activeShow.replace('custom:', '');
                    customInd.style.backgroundColor = customHex;
                    customInd.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-white"></i>';
                    customCard.className = 'theme-card relative p-4 rounded-2xl border text-left flex flex-col gap-3 justify-between bg-slate-900 border-sky-500 shadow-md transition select-none';
                    if (customBadge) customBadge.classList.remove('hidden');
                    if (customBadge) customBadge.style.display = 'inline-block';
                    if (customPicker) customPicker.value = customHex;
                } else {
                    customInd.innerHTML = '<i data-lucide="palette" class="w-4 h-4 text-white"></i>';
                    if (currentUser && currentUser.theme && currentUser.theme.indexOf('custom:') === 0) {
                        customInd.style.backgroundColor = currentUser.theme.replace('custom:', '');
                    } else {
                        customInd.style.backgroundColor = '#0ea5e9';
                    }
                    customCard.className = 'theme-card relative p-4 rounded-2xl border text-left flex flex-col gap-3 justify-between bg-slate-950/40 border-slate-900/60 hover:border-slate-800 transition select-none';
                    if (customBadge) customBadge.classList.add('hidden');
                    if (customBadge) customBadge.style.display = 'none';
                }
            }
            lucide.createIcons();
        }

        async function runMusicDirectoryScan(btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Escaneando...';
            lucide.createIcons();
            try {
                const res = await fetch('api.php?route=scan', { method: 'POST' });
                if (res.ok) {
                    alert('Sincronização de músicas concluída com sucesso!');
                    await loadData();
                    if (typeof loadMusicFolders === 'function') {
                        await loadMusicFolders();
                    }
                } else {
                    alert('Falha na varredura recursiva de músicas.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao escanear pasta /music.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        }

        async function openMusicScanLog() {
            document.getElementById('scan-log-modal').classList.remove('hidden');
            await refreshMusicScanLog();
        }
        window.openMusicScanLog = openMusicScanLog;

        function closeScanLogModal() {
            document.getElementById('scan-log-modal').classList.add('hidden');
        }
        window.closeScanLogModal = closeScanLogModal;

        async function refreshMusicScanLog() {
            const contentDiv = document.getElementById('scan-log-content');
            const timeSpan = document.getElementById('scan-log-time');
            contentDiv.textContent = "Carregando histórico do log...";
            try {
                const res = await fetch('api.php?route=scan_log');
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        contentDiv.textContent = data.content;
                        timeSpan.textContent = data.last_modified ? "Última varredura: " + data.last_modified : "Última varredura: -";
                        // Scroll to bottom so latest events are visible
                        contentDiv.scrollTop = contentDiv.scrollHeight;
                    } else {
                        contentDiv.textContent = "Erro na resposta do servidor: " + JSON.stringify(data);
                    }
                } else {
                    contentDiv.textContent = "Servidor respondeu com código de status: " + res.status;
                }
            } catch (err) {
                contentDiv.textContent = "Falha de rede ao conectar com a API: " + err.message;
            }
        }
        window.refreshMusicScanLog = refreshMusicScanLog;

        async function clearMusicScanLog() {
            if (!confirm("Deseja realmente limpar todos os logs de sincronização?")) return;
            const contentDiv = document.getElementById('scan-log-content');
            contentDiv.textContent = "Limpando logs...";
            try {
                const res = await fetch('api.php?route=scan_log', { method: 'DELETE' });
                if (res.ok) {
                    await refreshMusicScanLog();
                } else {
                    alert("Falha ao limpar logs no servidor.");
                }
            } catch (err) {
                alert("Erro ao enviar solicitação: " + err.message);
            }
        }
        window.clearMusicScanLog = clearMusicScanLog;

        async function runVideoDirectoryScan(btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando...';
            lucide.createIcons();
            try {
                const res = await fetch('api.php?route=videos_scan', { method: 'POST' });
                const data = await res.json();
                if (res.ok) {
                    alert('Sincronização de vídeos concluída com sucesso! Encontrados ' + (data.count || 0) + ' novos vídeos ou capas sincronizados.');
                } else {
                    alert('Erro ao varrer diretório de vídeos.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao escanear pasta /videos.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        }

        async function runLastfmSync(btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando Lote 1...';
            lucide.createIcons();
            
            let totalProcessed = 0;
            let currentBatch = 1;
            
            try {
                while (true) {
                    const res = await fetch('api.php?route=lastfm_sync', { method: 'POST' });
                    const data = await res.json();
                    
                    if (!res.ok || data.error) {
                        alert(data.error || 'Erro ao sincronizar lote com o Last.fm.');
                        break;
                    }
                    
                    const updatedCount = (data.artists_updated || 0) + (data.albums_updated || 0);
                    const pendingCount = (data.artists_pending || 0) + (data.albums_pending || 0);
                    
                    totalProcessed += updatedCount;
                    
                    if (pendingCount === 0 || updatedCount === 0) {
                        break;
                    }
                    
                    currentBatch++;
                    btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando Lote ' + currentBatch + ' (Faltam ' + pendingCount + ')...';
                    lucide.createIcons();
                    
                    // Delay de 300ms para evitar sobrecarga
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                alert('Sincronização com Last.fm efetuada com sucesso! Catálogo 100% atualizado.');
                await loadData();
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao conectar com a API de sincronização.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        }
        window.runLastfmSync = runLastfmSync;

        async function runDeezerSync(btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando Lote 1...';
            lucide.createIcons();
            
            let totalProcessed = 0;
            let currentBatch = 1;
            
            try {
                while (true) {
                    const res = await fetch('api.php?route=deezer_sync', { method: 'POST' });
                    const data = await res.json();
                    
                    if (!res.ok || data.error) {
                        alert(data.error || 'Erro ao sincronizar lote com o Deezer.');
                        break;
                    }
                    
                    const updatedCount = (data.albums_updated || 0) + (data.artists_updated || 0);
                    const pendingCount = (data.albums_pending || 0) + (data.artists_pending || 0);
                    
                    totalProcessed += updatedCount;
                    
                    if (pendingCount === 0 || updatedCount === 0) {
                        break;
                    }
                    
                    currentBatch++;
                    btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando Lote ' + currentBatch + ' (Faltam ' + pendingCount + ')...';
                    lucide.createIcons();
                    
                    // Delay de 300ms para evitar sobrecarga
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
                
                alert('Sincronização com Deezer concluída com sucesso! Capas de álbuns e logos de artistas atualizados.');
                await loadData();
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao conectar com a API de sincronização do Deezer.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        }
        window.runDeezerSync = runDeezerSync;

        async function runGoogleSync(btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando Lote 1...';
            lucide.createIcons();
            
            let totalProcessed = 0;
            let currentBatch = 1;
            
            try {
                while (true) {
                    const res = await fetch('api.php?route=google_images_sync', { method: 'POST' });
                    const data = await res.json();
                    
                    if (!res.ok || data.error) {
                        alert(data.error || 'Erro ao sincronizar lote com o Google Images.');
                        break;
                    }
                    
                    const updatedCount = (data.albums_updated || 0) + (data.artists_updated || 0);
                    const pendingCount = (data.albums_pending || 0) + (data.artists_pending || 0);
                    
                    totalProcessed += updatedCount;
                    
                    if (pendingCount === 0 || updatedCount === 0) {
                        break;
                    }
                    
                    currentBatch++;
                    btn.innerHTML = '<i data-lucide="refresh-cw" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando Lote ' + currentBatch + ' (Faltam ' + pendingCount + ')...';
                    lucide.createIcons();
                    
                    // Delay de 500ms para respeitar os limites do Google Images
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
                
                alert('Sincronização com Google Images concluída com sucesso! Capas de álbuns e logos de artistas atualizados.');
                await loadData();
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao conectar com a API de sincronização do Google Images.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        }
        window.runGoogleSync = runGoogleSync;

        function setTab(tabName) {
            if (isPartyMode) {
                alert("O Modo Festa está ativo! A navegação está bloqueada para manter a diversão focada no player.");
                return;
            }
            activeTab = tabName;
            document.getElementById('pane-dashboard').classList.add('hidden');
            document.getElementById('pane-tracks').classList.add('hidden');
            if (document.getElementById('pane-config')) document.getElementById('pane-config').classList.add('hidden');
            document.getElementById('pane-videos').classList.add('hidden');
            if (document.getElementById('pane-playlists')) document.getElementById('pane-playlists').classList.add('hidden');
            if (document.getElementById('pane-podcast')) document.getElementById('pane-podcast').classList.add('hidden');
            if (document.getElementById('pane-radios')) document.getElementById('pane-radios').classList.add('hidden');
            if (document.getElementById('pane-reprodutor')) document.getElementById('pane-reprodutor').classList.add('hidden');
            
            // Clear navigation classes
            const btns = ['dashboard', 'tracks', 'favorites', 'config', 'videos', 'playlists', 'podcast', 'radios', 'reprodutor'];
            btns.forEach(b => {
                const el = document.getElementById('tab-btn-' + b);
                if (el) {
                    el.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition";
                }
            });

            const activeBtn = document.getElementById('tab-btn-' + tabName);
            if (activeBtn) {
                if (tabName === 'favorites') {
                    activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#f43f5e] bg-rose-500/10 border border-rose-500/20";
                } else if (tabName === 'playlists') {
                    activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20";
                } else if (tabName === 'podcast') {
                    activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-orange-400 bg-orange-500/10 border border-orange-500/20";
                } else if (tabName === 'radios') {
                    activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20";
                } else if (tabName === 'reprodutor') {
                    activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-sky-400 bg-sky-500/10 border border-sky-500/20";
                } else {
                    activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-sky-400 bg-sky-500/10 border border-sky-500/20";
                }
            }

            if (tabName === 'dashboard') {
                renderDashboard();
                document.getElementById('pane-dashboard').classList.remove('hidden');
            } else if (tabName === 'playlists') {
                renderPlaylistsGrid();
                document.getElementById('pane-playlists').classList.remove('hidden');
            } else if (tabName === 'tracks') {
                selectedArtist = '';
                selectedPlaylistId = '';
                activePlaylistAlbum = '';
                document.getElementById('table-view-title').textContent = "Minha Biblioteca";
                renderTracksTable();
                document.getElementById('pane-tracks').classList.remove('hidden');
            } else if (tabName === 'favorites') {
                selectedArtist = '';
                selectedPlaylistId = '';
                activePlaylistAlbum = '';
                document.getElementById('table-view-title').textContent = "Músicas Favoritas";
                renderTracksTable();
                document.getElementById('pane-tracks').classList.remove('hidden');
            } else if (tabName === 'config') {
                document.getElementById('pane-config').classList.remove('hidden');
                if (currentUser.role === 'admin') {
                    document.querySelectorAll('.admin-only').forEach(el => el.classList.remove('hidden'));
                } else {
                    document.querySelectorAll('.admin-only').forEach(el => el.classList.add('hidden'));
                }
                setConfigSubTab('theme');
                updateThemeCardsUI();
                if (currentUser) {
                    const currentBg = currentUser.sidebarBg || '#020617';
                    const picker = document.getElementById('php-layout-bg-picker');
                    const text = document.getElementById('php-layout-bg-text');
                    if (picker) picker.value = currentBg;
                    if (text) text.value = currentBg;
                }
                if (window.loadLastfmKeyForUI) window.loadLastfmKeyForUI();
            } else if (tabName === 'videos') {
                renderVideoGallery();
                document.getElementById('pane-videos').classList.remove('hidden');
            } else if (tabName === 'podcast') {
                loadPodcastsPhp();
                document.getElementById('pane-podcast').classList.remove('hidden');
            } else if (tabName === 'radios') {
                loadRadiosPhp();
                document.getElementById('pane-radios').classList.remove('hidden');
            } else if (tabName === 'reprodutor') {
                if (typeof updateReprodutorTab === 'function') updateReprodutorTab();
                document.getElementById('pane-reprodutor').classList.remove('hidden');
            }
            lucide.createIcons();
        }

        function renderDashboard() {
            const statSongs = document.getElementById('stat-songs');
            if (statSongs) statSongs.textContent = allTracks.length;
            
            const uniqueAlbs = new Set(allTracks.map(t => t.album));
            const statAlbums = document.getElementById('stat-albums');
            if (statAlbums) statAlbums.textContent = uniqueAlbs.size;

            const uniqueArts = new Set(allTracks.map(t => t.artist || ''));
            uniqueArts.delete('Artista Desconhecido');
            uniqueArts.delete('');
            const statArtists = document.getElementById('stat-artists');
            if (statArtists) statArtists.textContent = uniqueArts.size;
            
            const statFavs = document.getElementById('stat-favs');
            if (statFavs) statFavs.textContent = allFavorites.length;

            renderAlbumGrid();
            renderLeftSidebar();
        }

        function renderLeftSidebar() {
            // Sidebar Artists list
            const arts = Array.from(new Set(allTracks.map(t => t.artist))).filter(Boolean).sort();
            const artEl = document.getElementById('artist-sidebar-list');
            if (artEl) {
                artEl.innerHTML = '';
                arts.forEach(art => {
                    const active = selectedArtist === art;
                    const btn = document.createElement('button');
                    btn.className = active 
                        ? "w-full flex items-center gap-2 px-3 py-1 bg-sky-500/15 text-sky-400 border border-sky-500/10 rounded-lg text-left text-xs truncate"
                        : "w-full flex items-center gap-2 px-3 py-1 text-slate-400 hover:text-white hover:bg-slate-900 rounded-lg text-left text-xs truncate transition";
                    btn.innerHTML = `<i data-lucide="user" class="w-3.5 h-3.5 shrink-0 text-slate-500"></i> <span class="truncate">${art}</span>`;
                    btn.onclick = () => {
                        selectedPlaylistId = '';
                        activePlaylistAlbum = '';
                        const titleEl = document.getElementById('table-view-title');
                        if (titleEl) titleEl.textContent = "Artista: " + art;
                        const clearFilterEl = document.getElementById('clear-artist-filter');
                        if (clearFilterEl) clearFilterEl.classList.remove('hidden');
                        renderLeftSidebar();
                        activeTab = 'tracks';
                        const paneDash = document.getElementById('pane-dashboard');
                        if (paneDash) paneDash.classList.add('hidden');
                        const paneTracks = document.getElementById('pane-tracks');
                        if (paneTracks) paneTracks.classList.remove('hidden');
                        selectArtist(art);
                    };
                    artEl.appendChild(btn);
                });
            }

            // Sidebar Playlists list
            const plEl = document.getElementById('playlist-sidebar-list');
            if (plEl) {
                plEl.innerHTML = '';
                allPlaylists.forEach(pl => {
                    const active = selectedPlaylistId == pl.id;
                    const btn = document.createElement('button');
                    btn.className = active
                        ? "w-full flex items-center justify-between px-3 py-1 bg-indigo-500/15 text-indigo-400 border border-indigo-500/10 rounded-lg text-left text-xs"
                        : "w-full flex items-center justify-between px-3 py-1 text-slate-400 hover:text-white hover:bg-slate-900 rounded-lg text-left text-xs transition";
                    btn.innerHTML = `<span class="flex items-center gap-2 truncate"><i data-lucide="list-music" class="w-3.5 h-3.5 shrink-0"></i> <span class="truncate">${pl.name}</span></span> <span class="text-[9px] text-slate-500">${pl.trackIds.length}</span>`;
                    btn.onclick = () => {
                        selectedPlaylistId = pl.id;
                        selectedArtist = '';
                        activePlaylistAlbum = '';
                        const titleEl = document.getElementById('table-view-title');
                        if (titleEl) titleEl.textContent = "Playlist: " + pl.name;
                        renderLeftSidebar();
                        renderTracksTable();
                        activeTab = 'tracks';
                        const paneDash = document.getElementById('pane-dashboard');
                        if (paneDash) paneDash.classList.add('hidden');
                        const paneTracks = document.getElementById('pane-tracks');
                        if (paneTracks) paneTracks.classList.remove('hidden');
                    };
                    plEl.appendChild(btn);
                });
            }

            // Update artist select filter dropdown
            const artistFilterSelect = document.getElementById('artist-filter-dropdown');
            if (artistFilterSelect) {
                artistFilterSelect.innerHTML = '<option value="">Todos Artistas</option>';
                arts.forEach(art => {
                    const opt = document.createElement('option');
                    opt.value = art;
                    opt.textContent = art + ' (' + allTracks.filter(t => t.artist === art).length + ')';
                    if (selectedArtist === art) {
                        opt.selected = true;
                    }
                    artistFilterSelect.appendChild(opt);
                });
                artistFilterSelect.value = selectedArtist;
            }

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                lucide.createIcons();
            }
        }

        function filterTracksByArtistDropdown(val) {
            if (!val) {
                selectedArtist = '';
                const clearFilterEl = document.getElementById('clear-artist-filter');
                if (clearFilterEl) clearFilterEl.classList.add('hidden');
                const titleEl = document.getElementById('table-view-title');
                if (titleEl) titleEl.textContent = "Minha Biblioteca";
            } else {
                selectedArtist = val;
                selectedPlaylistId = '';
                activePlaylistAlbum = '';
                const titleEl = document.getElementById('table-view-title');
                if (titleEl) titleEl.textContent = "Artista: " + val;
                const clearFilterEl = document.getElementById('clear-artist-filter');
                if (clearFilterEl) clearFilterEl.classList.remove('hidden');
            }
            activeTab = 'tracks';
            const paneDash = document.getElementById('pane-dashboard');
            if (paneDash) paneDash.classList.add('hidden');
            const paneTracks = document.getElementById('pane-tracks');
            if (paneTracks) paneTracks.classList.remove('hidden');
            if (document.getElementById('pane-playlists')) document.getElementById('pane-playlists').classList.add('hidden');
            if (document.getElementById('pane-config')) document.getElementById('pane-config').classList.add('hidden');
            const paneVideos = document.getElementById('pane-videos');
            if (paneVideos) paneVideos.classList.add('hidden');

            renderLeftSidebar();
            renderTracksTable();
        }

        function filterByArtist(art) {
            if (!art) {
                selectedArtist = '';
                const clearFilterEl = document.getElementById('clear-artist-filter');
                if (clearFilterEl) clearFilterEl.classList.add('hidden');
                setTab('tracks');
            } else {
                selectArtist(art);
            }
        }

        window.phpDashboardGenre = 'all';
        
        window.phpDashboardSort = 'random';
        window.phpDashboardQuery = '';

        window.onPhpDashboardSearchInput = function(val) {
            window.phpDashboardQuery = val;
            updateRandomDashboardAlbums();
            renderAlbumGrid();
        };

        function updateDashboardGenresDropdown() {
            const dropdown = document.getElementById('php-dashboard-genre-filter');
            if (!dropdown) return;
            
            // Get unique genres
            const genresSet = new Set();
            allTracks.forEach(t => {
                if (t.genre && t.genre.trim() !== '') {
                    genresSet.add(t.genre.trim());
                }
            });
            const genresArray = Array.from(genresSet).sort();
            
            // Re-render select options
            const currentLang = localStorage.getItem('phplayer_lang') || 'pt';
            let allGenresText = 'Todos os Gêneros';
            if (currentLang === 'en') {
                allGenresText = 'All Genres';
            } else if (currentLang === 'es') {
                allGenresText = 'Todos los Géneros';
            }
            
            dropdown.innerHTML = `<option value="all">${allGenresText}</option>`;
            genresArray.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g;
                opt.textContent = g;
                if (window.phpDashboardGenre === g) {
                    opt.selected = true;
                }
                dropdown.appendChild(opt);
            });
        }

        window.onPhpDashboardGenreChange = function(val) {
            window.phpDashboardGenre = val;
            updateRandomDashboardAlbums();
            renderAlbumGrid();
        };

        window.onPhpDashboardSortChange = function(val) {
            window.phpDashboardSort = val;
            updateRandomDashboardAlbums();
            renderAlbumGrid();
        };

        

        

        window.setupDashboardInterval = function() {
            if (dashboardRandomInterval) {
                clearInterval(dashboardRandomInterval);
                dashboardRandomInterval = null;
            }
            let timeStr = globalSettings.dashboard_rotate_time;
            let timeSecs = timeStr !== undefined && timeStr !== "" ? parseInt(timeStr) : 8;
            if (timeSecs > 0) {
                dashboardRandomInterval = setInterval(() => {
                    const pane = document.getElementById('pane-dashboard');
                    if (pane && !pane.classList.contains('hidden')) {
                        updateRandomDashboardAlbums();
                        renderAlbumGrid();
                    }
                }, timeSecs * 1000);
            }
        };

        function updateRandomDashboardAlbums() {
            if (!allTracks || allTracks.length === 0) {
                randomDashboardAlbums = [];
                return;
            }
            // Group by albums
            const albsMap = {};
            allTracks.forEach(t => {
                const key = t.album || 'Single';
                if (!albsMap[key]) {
                    albsMap[key] = { 
                        name: key, 
                        artist: t.artist, 
                        genre: t.genre || 'Desconhecido', 
                        cover: t.cover_url || t.coverUrl, 
                        tracks: [],
                        maxDate: t.createdAt || ''
                    };
                } else {
                    if (t.genre && (!albsMap[key].genre || albsMap[key].genre === 'DESCONHECIDO' || albsMap[key].genre === 'Local Scan' || albsMap[key].genre === 'Desconhecido')) {
                        albsMap[key].genre = t.genre;
                    }
                    if (t.createdAt && (!albsMap[key].maxDate || t.createdAt > albsMap[key].maxDate)) {
                        albsMap[key].maxDate = t.createdAt;
                    }
                }
                albsMap[key].tracks.push(t);
            });

            let albumsArray = Object.values(albsMap);
            if (albumsArray.length === 0) {
                randomDashboardAlbums = [];
                return;
            }

            // Filter by search query if any
            const query = (window.phpDashboardQuery || '').toLowerCase().trim();
            if (query) {
                albumsArray = albumsArray.filter(alb => {
                    return alb.name.toLowerCase().includes(query) || 
                           alb.artist.toLowerCase().includes(query);
                });
            }

            // 1. Filter by Genre if selected
            const selectedGenre = window.phpDashboardGenre || 'all';
            if (selectedGenre !== 'all') {
                albumsArray = albumsArray.filter(alb => {
                    return (alb.genre && alb.genre.toLowerCase() === selectedGenre.toLowerCase()) ||
                           alb.tracks.some(t => t.genre && t.genre.toLowerCase() === selectedGenre.toLowerCase());
                });
            }

            // 2. Sort albums based on window.phpDashboardSort
            const currentSort = window.phpDashboardSort || 'random';
            if (currentSort === 'alphabetical') {
                albumsArray.sort((a, b) => a.name.localeCompare(b.name));
                randomDashboardAlbums = albumsArray; // show all
            } else if (currentSort === 'recent') {
                albumsArray.sort((a, b) => {
                    const de = new Date(b.maxDate).getTime() || 0;
                    const da = new Date(a.maxDate).getTime() || 0;
                    return de - da;
                });
                randomDashboardAlbums = albumsArray; // show all
            } else {
                // 'random' (Auto-rotated 12 random albums)
                const shuffled = [...albumsArray].sort(() => Math.random() - 0.5);
                randomDashboardAlbums = shuffled.slice(0, globalSettings.dashboard_albums_count ? parseInt(globalSettings.dashboard_albums_count) : 12);
            }
        }

        window.playRandomAlbum = function() {
            if (!allTracks || allTracks.length === 0) return;
            const albsMap = {};
            allTracks.forEach(t => {
                const key = t.album || 'Single';
                if (!albsMap[key]) {
                    albsMap[key] = { name: key, artist: t.artist, genre: t.genre || 'Desconhecido', cover: t.cover_url || t.coverUrl, tracks: [] };
                } else {
                    if (t.genre && (!albsMap[key].genre || albsMap[key].genre === 'DESCONHECIDO' || albsMap[key].genre === 'Local Scan' || albsMap[key].genre === 'Desconhecido')) {
                        albsMap[key].genre = t.genre;
                    }
                }
                albsMap[key].tracks.push(t);
            });
            let albumsArray = Object.values(albsMap);
            if (albumsArray.length === 0) return;

            // Filter by search query if any
            const query = (window.phpDashboardQuery || '').toLowerCase().trim();
            if (query) {
                albumsArray = albumsArray.filter(alb => {
                    return alb.name.toLowerCase().includes(query) || 
                           alb.artist.toLowerCase().includes(query);
                });
            }

            // Filter by Genre if selected
            const selectedGenre = window.phpDashboardGenre || 'all';
            if (selectedGenre !== 'all') {
                albumsArray = albumsArray.filter(alb => {
                    return (alb.genre && alb.genre.toLowerCase() === selectedGenre.toLowerCase()) ||
                           alb.tracks.some(t => t.genre && t.genre.toLowerCase() === selectedGenre.toLowerCase());
                });
            }

            if (albumsArray.length === 0) return;
            const randomAlb = albumsArray[Math.floor(Math.random() * albumsArray.length)];
            if (randomAlb.tracks.length > 0) {
                activeQueue = randomAlb.tracks;
                activeQueueIdx = 0;
                loadTrack(activeQueue[0]);
            }
        };

        function renderAlbumGrid() {
            const grid = document.getElementById('album-grid-container');
            if (!grid) return;
            grid.innerHTML = '';
            
            if (!randomDashboardAlbums || randomDashboardAlbums.length === 0) {
                updateRandomDashboardAlbums();
            }

            const albsMap = {};
            allTracks.forEach(t => {
                const key = t.album || 'Single';
                if (!albsMap[key]) {
                    albsMap[key] = true;
                }
            });
            const totalAlbumsCount = Object.keys(albsMap).length;

            randomDashboardAlbums.forEach(alb => {
                const cell = document.createElement('div');
                cell.className = "p-3 bg-slate-950/60 border border-slate-900/40 rounded-2xl flex flex-col group hover:border-sky-500/40 cursor-pointer transition duration-350";
                cell.innerHTML = `
                    <div class="relative rounded-xl overflow-hidden aspect-square bg-slate-900/40 flex items-center justify-center border border-slate-905/60 shadow-inner">
                        <img src="${alb.cover || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=300'}" class="w-full h-full object-cover shadow-md border border-slate-800/40 group-hover:scale-105 duration-300 transition" referrerpolicy="no-referrer">
                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition duration-300">
                            <button onclick="playAlbumByName(event, '${alb.name.replace(new RegExp("'", "g"), "\\'")}')" class="p-2.5 bg-sky-500 hover:bg-sky-600 text-white rounded-full shadow-lg cursor-pointer transform hover:scale-105 transition" title="Reproduzir Álbum em Ordem">
                                <i data-lucide="play" class="w-3.5 h-3.5 fill-current text-white"></i>
                            </button>
                            <button onclick="playAlbumQueueShuffled(event, ${JSON.stringify(alb.tracks).replace(/"/g, '&quot;')})" class="p-2.5 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white rounded-full shadow-lg border border-slate-800 cursor-pointer transform hover:scale-105 transition" title="Reproduzir em Modo Aleatório">
                                <i data-lucide="shuffle" class="w-3.5 h-3.5 text-slate-300"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 text-left">
                        <p class="text-xs font-bold text-slate-200 truncate">${alb.name}</p>
                        <span class="text-[10px] text-slate-500 truncate block mt-0.5">${alb.genre || 'Desconhecido'}</span>
                    </div>
                `;
                cell.onclick = () => {
                    activePlaylistAlbum = alb.name;
                    selectedArtist = '';
                    selectedPlaylistId = '';
                    document.getElementById('table-view-title').textContent = "Álbum: " + alb.name;
                    renderTracksTable();
                    activeTab = 'tracks';
                    document.getElementById('pane-dashboard').classList.add('hidden');
                    document.getElementById('pane-tracks').classList.remove('hidden');
                };
                grid.appendChild(cell);
            });

            // Handle Pagination Display below albums grid
            let pagDiv = document.getElementById('dashboard-albums-pagination');
            if (!pagDiv) {
                pagDiv = document.createElement('div');
                pagDiv.id = 'dashboard-albums-pagination';
                grid.parentNode.insertBefore(pagDiv, grid.nextSibling);
            }

            const currentLang = localStorage.getItem('phplayer_lang') || 'pt';
            const isRandom = (window.phpDashboardSort === 'random');
            let countLabel = "";
            let rotateBtnText = "";
            
            const currentCount = randomDashboardAlbums ? randomDashboardAlbums.length : 0;
            const limitStr = globalSettings.dashboard_albums_count ? parseInt(globalSettings.dashboard_albums_count) : 12;
            let rTime = globalSettings.dashboard_rotate_time;
            const timeStr = rTime === '0' ? 'Desativada' : (rTime ? rTime + 's' : '8s');
            
            if (isRandom) {
                if (currentLang === 'en') {
                    countLabel = `Showing ${Math.min(limitStr, currentCount)} of ${currentCount} random albums (Rotate: ${timeStr})`;
                    rotateBtnText = "Next Albums ⟳";
                } else if (currentLang === 'es') {
                    countLabel = `Mostrando ${Math.min(limitStr, currentCount)} de ${currentCount} álbumes aleatorios (Rotación: ${timeStr})`;
                    rotateBtnText = "Siguientes álbumes ⟳";
                } else {
                    countLabel = `Mostrando ${Math.min(limitStr, currentCount)} de ${currentCount} álbuns aleatórios (Rotação: ${timeStr})`;
                    rotateBtnText = "Próximos Álbuns ⟳";
                }
            } else {
                if (currentLang === 'en') {
                    countLabel = `Showing ${currentCount} albums sorted`;
                } else if (currentLang === 'es') {
                    countLabel = `Mostrando ${currentCount} álbumes ordenados`;
                } else {
                    countLabel = `Mostrando ${currentCount} álbuns ordenados`;
                }
            }

            if (isRandom && currentCount > limitStr) {
                pagDiv.innerHTML = `
                    <div class="flex items-center justify-between gap-4 py-3 mt-4 text-xs font-medium border-t border-slate-900/40 pt-4">
                        <div class="text-slate-500 font-semibold flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            ${countLabel}
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="changeDashboardAlbumPage(1)" class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sky-400 font-extrabold hover:text-white hover:border-sky-500/30 transition cursor-pointer select-none active:scale-95">
                                ${rotateBtnText}
                            </button>
                        </div>
                    </div>
                `;
            } else {
                pagDiv.innerHTML = `
                    <div class="flex items-center justify-between gap-4 py-3 mt-4 text-xs font-medium border-t border-slate-900/40 pt-4">
                        <div class="text-slate-500 font-semibold flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            ${countLabel}
                        </div>
                    </div>
                `;
            }
            lucide.createIcons();
        }

        window.changeDashboardAlbumPage = function(page) {
            updateRandomDashboardAlbums();
            renderAlbumGrid();
        };

        function playAlbumQueue(e, trackList) {
            e.stopPropagation();
            if (trackList.length === 0) return;
            activeQueue = trackList;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        }

        function playAlbumQueueShuffled(e, trackList) {
            e.stopPropagation();
            if (trackList.length === 0) return;
            const shuffled = [...trackList].sort(() => Math.random() - 0.5);
            activeQueue = shuffled;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        }

        let sleepTimerSecs = null;
        let sleepTimerInterval = null;

        function setSleepTimerPhp(minutes) {
            if (sleepTimerInterval) clearInterval(sleepTimerInterval);
            if (minutes === null) {
                sleepTimerSecs = null;
                updateSleepTimerButton();
                return;
            }
            sleepTimerSecs = minutes * 60;
            updateSleepTimerButton();
            
            sleepTimerInterval = setInterval(() => {
                if (sleepTimerSecs === null) {
                    clearInterval(sleepTimerInterval);
                    return;
                }
                if (sleepTimerSecs <= 1) {
                    if (isPlaying) {
                        togglePlay();
                    }
                    sleepTimerSecs = null;
                    clearInterval(sleepTimerInterval);
                    updateSleepTimerButton();
                } else {
                    sleepTimerSecs--;
                    updateSleepTimerButton();
                }
            }, 1000);
        }

        function updateSleepTimerButton() {
            const btn = document.getElementById('php-sleep-btn');
            if (!btn) return;
            if (sleepTimerSecs === null) {
                btn.innerHTML = `<i data-lucide="clock" class="w-4 h-4"></i>`;
                btn.className = 'p-1.5 text-slate-400 hover:text-white transition cursor-pointer border border-transparent rounded-lg flex items-center gap-1 font-mono text-[9px]';
            } else {
                const mins = Math.floor(sleepTimerSecs / 60);
                const secs = String(sleepTimerSecs % 60).padStart(2, '0');
                btn.innerHTML = `<i data-lucide="clock" class="w-3.5 h-3.5 text-sky-400 mr-1 animate-pulse"></i><span class="text-[10px] text-sky-400 font-mono">${mins}:${secs}</span>`;
                btn.className = 'p-1.5 border border-sky-950 bg-sky-950/40 text-sky-400 rounded-lg flex items-center gap-1 font-mono text-[9px] cursor-pointer';
            }
            if (window.lucide) window.lucide.createIcons();

            // Sync reprodutor sleep select
            const repSelect = document.getElementById('reprodutor-sleep-timer-select');
            if (repSelect) {
                if (sleepTimerSecs === null) {
                    repSelect.value = "0";
                } else {
                    const mins = Math.ceil(sleepTimerSecs / 60);
                    if ([5, 15, 30, 60].includes(mins)) {
                        repSelect.value = String(mins);
                    }
                }
            }
        }

        window.phpCrossfadeDuration = 0;
        window.setCrossfadePhp = function(secs) {
            window.phpCrossfadeDuration = parseInt(secs) || 0;
            const btn = document.getElementById('php-crossfade-btn');
            if (btn) {
                if (window.phpCrossfadeDuration === 0) {
                    btn.innerHTML = `<i data-lucide="layers" class="w-4 h-4"></i>`;
                    btn.className = 'p-1.5 text-slate-400 hover:text-white transition cursor-pointer border border-transparent rounded-lg flex items-center gap-1 font-mono text-[9px]';
                } else {
                    btn.innerHTML = `<i data-lucide="layers" class="w-3.5 h-3.5 text-sky-450 mr-1 animate-pulse"></i><span class="text-[10px] text-sky-450 font-mono">${window.phpCrossfadeDuration}s</span>`;
                    btn.className = 'p-1.5 border border-sky-950 bg-sky-950/40 text-sky-450 rounded-lg flex items-center gap-1 font-mono text-[9px] cursor-pointer';
                }
            }
            if (window.lucide) window.lucide.createIcons();
            
            // Sync with mobile status display
            const mobStatus = document.getElementById('mobile-crossfade-status');
            if (mobStatus) {
                mobStatus.textContent = window.phpCrossfadeDuration === 0 ? 'Desativado' : window.phpCrossfadeDuration + 's';
                mobStatus.className = window.phpCrossfadeDuration === 0 ? 'text-[9px] text-slate-500 font-bold uppercase tracking-wider' : 'text-[9px] text-sky-455 font-bold uppercase tracking-wider animate-pulse';
            }

            // Sync reprodutor crossfade select
            const crossSelect = document.getElementById('reprodutor-crossfade-select');
            if (crossSelect) {
                crossSelect.value = String(window.phpCrossfadeDuration);
            }
        };

        window.setMobileCrossfade = function(secs) {
            window.setCrossfadePhp(secs);
        };

        let phpLyricsMode = localStorage.getItem('php_lyrics_mode') || 'karaoke';
        let phpLyricsData = [];
        let currentActiveLineIdx = -1;

        window.updateLyricsButtonsState = function() {
            const mode = phpLyricsMode;
            const btnKaraoke = document.getElementById('btn-lyrics-karaoke');
            const btnStandard = document.getElementById('btn-lyrics-standard');
            
            if (mode === 'karaoke') {
                if (btnKaraoke) btnKaraoke.className = "px-2.5 py-1 font-black uppercase tracking-wider text-[9px] rounded-lg cursor-pointer flex items-center gap-1 transition-all duration-200 bg-sky-500/10 text-sky-400 border border-sky-550/20";
                if (btnStandard) btnStandard.className = "px-2.5 py-1 font-bold uppercase tracking-wider text-[9px] rounded-lg cursor-pointer flex items-center gap-1 transition-all duration-200 text-slate-500 hover:text-slate-350";
            } else {
                if (btnKaraoke) btnKaraoke.className = "px-2.5 py-1 font-bold uppercase tracking-wider text-[9px] rounded-lg cursor-pointer flex items-center gap-1 transition-all duration-200 text-slate-500 hover:text-slate-350";
                if (btnStandard) btnStandard.className = "px-2.5 py-1 font-black uppercase tracking-wider text-[9px] rounded-lg cursor-pointer flex items-center gap-1 transition-all duration-200 bg-slate-900 text-slate-300 border border-slate-800/80";
            }
        };

        window.setLyricsMode = function(mode) {
            phpLyricsMode = mode;
            localStorage.setItem('php_lyrics_mode', mode);
            window.updateLyricsButtonsState();
            if (window.phpCurrentTrackLyricsRaw) {
                window.renderLyricsContent();
            }
            if (window.lucide) window.lucide.createIcons();
        };

        window.parseLrc = function(lyricsText) {
            const lines = lyricsText.split('\n');
            const lrcData = [];
            const timestampRegex = /\[(\d{2}):(\d{2})(?:\.(\d{2,3}))?\]/g;
            let hasTimestamps = false;

            for (let line of lines) {
                line = line.trim();
                if (!line) continue;
                
                timestampRegex.lastIndex = 0;
                const matches = [...line.matchAll(timestampRegex)];
                if (matches.length > 0) {
                    hasTimestamps = true;
                    const cleanText = line.replace(timestampRegex, '').trim();
                    for (const match of matches) {
                        const m = parseInt(match[1], 10);
                        const s = parseInt(match[2], 10);
                        const msStr = match[3] || '0';
                        const ms = parseFloat('0.' + msStr);
                        const time = m * 60 + s + ms;
                        lrcData.push({ time, text: cleanText });
                    }
                } else {
                    if (line.startsWith('[') && line.includes(':') && line.endsWith(']')) {
                        continue;
                    }
                    lrcData.push({ time: -1, text: line });
                }
            }

            if (!hasTimestamps) {
                const cleanLines = lines
                    .map(l => l.trim())
                    .filter(l => !l.startsWith('[') || !l.endsWith(']'));
                
                const aud = document.getElementById('real-audio');
                const duration = (aud && aud.duration) ? aud.duration : (window.phpCurrentTrack ? window.phpCurrentTrack.duration || 180 : 180);
                const lineTime = duration / Math.max(1, cleanLines.length);
                
                return cleanLines.map((text, idx) => ({
                    time: idx * lineTime,
                    text: text
                }));
            }

            const finalData = lrcData.filter(item => item.time >= 0);
            finalData.sort((a, b) => a.time - b.time);
            return finalData;
        };

        window.renderLyricsContent = function() {
            const contentEl = document.getElementById('lyrics-content');
            if (!contentEl) return;
            
            const rawLyrics = window.phpCurrentTrackLyricsRaw;
            if (!rawLyrics) return;
            
            if (phpLyricsMode === 'karaoke') {
                phpLyricsData = window.parseLrc(rawLyrics);
                currentActiveLineIdx = -1;
                
                if (phpLyricsData.length === 0) {
                    contentEl.innerHTML = `<p class="text-sm text-slate-500">Nenhuma linha parseável de letras.</p>`;
                    return;
                }
                
                let html = `<div class="space-y-5 py-12 max-w-lg mx-auto flex flex-col items-center">`;
                phpLyricsData.forEach((item, index) => {
                    const cleanTxt = item.text || '...';
                    html += `
                        <div id="karaoke-line-${index}" onclick="window.seekAudioTo(${item.time})" class="karaoke-line text-slate-400 font-medium text-base sm:text-lg opacity-60 transition-all duration-300 transform scale-95 origin-center cursor-pointer py-1 hover:text-white hover:scale-100" title="Clique para ir a este trecho">
                            ${cleanTxt}
                        </div>
                    `;
                });
                html += `</div>`;
                contentEl.innerHTML = html;
                
                const aud = document.getElementById('real-audio');
                if (aud) {
                    window.updateLyricsKaraoke(aud.currentTime);
                }
            } else {
                const display = rawLyrics.replace(/\[\d{2}:\d{2}(?:\.\d{2,3})?\]/g, '').trim();
                contentEl.innerHTML = `<div class="whitespace-pre-line leading-relaxed text-slate-350 hover:text-white text-sm md:text-base font-medium max-w-lg mx-auto py-6" style="text-shadow: 0 1px 3px rgba(0,0,0,0.5)">${display}</div>`;
            }
        };

        window.seekAudioTo = function(seconds) {
            const aud = document.getElementById('real-audio');
            if (aud && seconds >= 0) {
                aud.currentTime = seconds;
                window.updateLyricsKaraoke(seconds);
            }
        };

        window.updateLyricsKaraoke = function(currentTime) {
            if (phpLyricsMode !== 'karaoke' || phpLyricsData.length === 0) return;
            
            let activeIdx = -1;
            for (let i = 0; i < phpLyricsData.length; i++) {
                if (currentTime >= phpLyricsData[i].time) {
                    activeIdx = i;
                } else {
                    break;
                }
            }
            
            if (activeIdx !== currentActiveLineIdx) {
                if (currentActiveLineIdx !== -1) {
                    const prevEl = document.getElementById(`karaoke-line-${currentActiveLineIdx}`);
                    if (prevEl) {
                        prevEl.className = "karaoke-line text-slate-400 font-medium text-base sm:text-lg opacity-60 transition-all duration-300 transform scale-95 origin-center cursor-pointer py-1 hover:text-white hover:scale-100";
                    }
                }
                
                currentActiveLineIdx = activeIdx;
                
                if (activeIdx !== -1) {
                    const activeEl = document.getElementById(`karaoke-line-${activeIdx}`);
                    if (activeEl) {
                        activeEl.className = "karaoke-line text-sky-400 text-lg sm:text-2xl font-black font-sans leading-relaxed transition-all duration-350 transform scale-105 origin-center brightness-125 drop-shadow-[0_0_12px_rgba(56,189,248,0.35)] py-2";
                        
                        const container = document.getElementById('lyrics-content');
                        if (container && Date.now() - (window.phpLyricsLastScroll || 0) > 4000) {
                            window.phpLyricsIsProgrammaticScroll = true;
                            const containerHeight = container.clientHeight;
                            const lineTop = activeEl.offsetTop;
                            const lineHeight = activeEl.clientHeight;
                            container.scrollTo({
                                top: lineTop - (containerHeight / 2) + (lineHeight / 2),
                                behavior: 'smooth'
                            });
                        }
                    }
                }
            }
        };

        window.fetchLyricsPhp = async function(artist, title) {
            const contentEl = document.getElementById('lyrics-content');
            const sourceEl = document.getElementById('lyrics-source');
            const titleEl = document.getElementById('lyrics-title');
            const artistEl = document.getElementById('lyrics-artist');
            
            if (!contentEl) return;
            
            if (titleEl) titleEl.textContent = title || "Sem título";
            if (artistEl) artistEl.textContent = artist || "Sem artista";
            
            if (sourceEl) {
                sourceEl.classList.add('hidden');
                sourceEl.textContent = '';
            }
            contentEl.innerHTML = '<div class="flex flex-col items-center gap-2 py-10"><i data-lucide="loader" class="w-6 h-6 animate-spin text-sky-400"></i><span class="text-xs text-slate-500">Buscando letra...</span></div>';
            if (window.lucide) window.lucide.createIcons();
            
            window.phpCurrentTrackLyricsRaw = '';
            window.updateLyricsButtonsState();
            
            try {
                const res = await fetch(API + '?route=lyrics&title=' + encodeURIComponent(title) + '&artist=' + encodeURIComponent(artist));
                if (res.ok) {
                    const data = await res.json();
                    if (data.lyrics) {
                        window.phpCurrentTrackLyricsRaw = data.lyrics;
                        if (sourceEl && data.source) {
                            sourceEl.textContent = data.source;
                            sourceEl.classList.remove('hidden');
                            sourceEl.className = "text-[8px] bg-sky-500/15 text-sky-400 px-1.5 py-0.5 rounded font-black tracking-wider uppercase";
                        }
                        window.renderLyricsContent();
                    } else {
                        contentEl.innerHTML = `<p class="text-sm text-slate-500">Letras não encontradas para "${title}". Corrija o nome do artista/música e tente novamente.</p>`;
                    }
                } else {
                    contentEl.innerHTML = '<p class="text-sm text-slate-500">Erro na requisição das letras.</p>';
                }
            } catch (err) {
                contentEl.innerHTML = '<p class="text-sm text-slate-500">Falha ao se conectar ao serviço de letras.</p>';
            }
            if (window.lucide) window.lucide.createIcons();
        };

        window.searchLyricsCustom = function() {
            const artistInput = document.getElementById('lyrics-search-artist');
            const titleInput = document.getElementById('lyrics-search-title');
            if (!artistInput || !titleInput) return;
            
            const artist = artistInput.value.trim();
            const title = titleInput.value.trim();
            
            if (!artist || !title) {
                alert("Por favor, preencha o Artista e o nome da Música.");
                return;
            }
            
            window.fetchLyricsPhp(artist, title);
        };

        async function showLyricsPhp() {
            const track = window.phpCurrentTrack;
            const modal = document.getElementById('lyrics-modal');
            const titleEl = document.getElementById('lyrics-title');
            const artistEl = document.getElementById('lyrics-artist');
            const contentEl = document.getElementById('lyrics-content');
            const sourceEl = document.getElementById('lyrics-source');
            const artistInput = document.getElementById('lyrics-search-artist');
            const titleInput = document.getElementById('lyrics-search-title');
            
            if (!modal || !titleEl || !artistEl || !contentEl) return;
            
            if (!window.phpLyricsListenerAttached) {
                contentEl.addEventListener('scroll', () => {
                    if (window.phpLyricsIsProgrammaticScroll) {
                        window.phpLyricsIsProgrammaticScroll = false;
                        return;
                    }
                    window.phpLyricsLastScroll = Date.now();
                });
                window.phpLyricsListenerAttached = true;
            }
            window.phpLyricsLastScroll = 0;
            window.updateLyricsButtonsState();
            
            if (track) {
                titleEl.textContent = track.title;
                artistEl.textContent = track.artist;
                if (artistInput) artistInput.value = track.artist || '';
                if (titleInput) titleInput.value = track.title || '';
                
                if (sourceEl) {
                    sourceEl.classList.add('hidden');
                    sourceEl.textContent = '';
                }
                modal.classList.remove('hidden');
                window.fetchLyricsPhp(track.artist, track.title);
            } else {
                titleEl.textContent = 'Pesquisa de Letras';
                artistEl.textContent = 'Busque no Lyrics.ovh';
                if (artistInput) artistInput.value = '';
                if (titleInput) titleInput.value = '';
                
                if (sourceEl) {
                    sourceEl.classList.add('hidden');
                    sourceEl.textContent = '';
                }
                contentEl.innerHTML = `
                    <div class="flex flex-col items-center gap-3.5 py-16 text-center">
                        <div class="p-4 bg-sky-500/10 text-sky-400 rounded-full">
                            <i data-lucide="music" class="w-8 h-8"></i>
                        </div>
                        <div class="max-w-md space-y-1.5">
                            <h4 class="text-white font-bold text-sm">Pesquisa de Letras Lyrics.ovh</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">Digite o nome do artista e da música nos campos de busca acima e clique em "Buscar Letra" para obter qualquer letra instantaneamente.</p>
                        </div>
                    </div>
                `;
                modal.classList.remove('hidden');
                if (window.lucide) window.lucide.createIcons();
            }
        }
        
        window.closeLyricsModal = function() {
            const modal = document.getElementById('lyrics-modal');
            if (modal) modal.classList.add('hidden');
        };

        window.toggleVisualizerFullscreen = function() {
    const container = document.getElementById('visualizer-container');
    const toolbar = document.getElementById('visualizer-toolbar');
    const wrapper = document.getElementById('visualizer-canvas-wrapper');
    
    if (!document.fullscreenElement) {
        container.requestFullscreen().then(() => {
            toolbar.classList.add('hidden');
            wrapper.classList.remove('h-72', 'sm:h-96');
            wrapper.classList.add('h-screen');
        }).catch(err => {
            console.error(err);
        });
    } else {
        document.exitFullscreen();
    }
};

document.addEventListener('fullscreenchange', (event) => {
    const toolbar = document.getElementById('visualizer-toolbar');
    const wrapper = document.getElementById('visualizer-canvas-wrapper');
    if (!document.fullscreenElement && toolbar && wrapper) {
        toolbar.classList.remove('hidden');
        wrapper.classList.add('h-72', 'sm:h-96');
        wrapper.classList.remove('h-screen');
    }
});

// EQUALIZER WEB AUDIO CONTROLLER (PHP DESKTOP)
        let phpAudioContext = null;
        let phpSourceNode = null;
        let phpBiquadFilters = [];
        let phpEqGains = [0, 0, 0, 0, 0];
        let phpAnalyserNode = null;
        let phpVisualizerStyle = 'bars';
        let phpVisualizerColor = 'wmp';
        let phpVisualizerAnimFrame = null;
        let phpPeaks = new Array(256).fill(0);

        const phpPresets = {
            flat: [0, 0, 0, 0, 0],
            bass: [6, 4, 0, 0, -1],
            pop: [-1, 2, 4, 2, -1],
            rock: [4, 2, -2, 2, 5],
            vocal: [-2, 1, 3, 4, 2],
            electronic: [5, 3, 1, 2, 4],
            suave: [3, 1, 0, 1, -2],
            classical: [3, 2, 1, -1, 3]
        };

        function initPhpEqualizer() {
            if (phpAudioContext) return;
            const audioEl = document.getElementById('real-audio');
            if (!audioEl) return;
            
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;
                
                phpAudioContext = new AudioContextClass();
                phpSourceNode = phpAudioContext.createMediaElementSource(audioEl);
                
                const freqs = [60, 230, 910, 4000, 14000];
                phpBiquadFilters = freqs.map((freq, idx) => {
                    const filter = phpAudioContext.createBiquadFilter();
                    filter.type = 'peaking';
                    filter.frequency.value = freq;
                    filter.Q.value = 1.0;
                    filter.gain.value = phpEqGains[idx];
                    return filter;
                });
                
                phpAnalyserNode = phpAudioContext.createAnalyser();
                phpAnalyserNode.fftSize = 256;

                let lastNode = phpSourceNode;
                phpBiquadFilters.forEach(filter => {
                    lastNode.connect(filter);
                    lastNode = filter;
                });
                lastNode.connect(phpAnalyserNode);
                phpAnalyserNode.connect(phpAudioContext.destination);
            } catch (err) {
                console.warn('Web Audio error php:', err);
            }
        }

        window.showVisualizerPhp = function() {
            document.getElementById('visualizer-modal').classList.remove('hidden');
            initPhpEqualizer();
            if (phpAudioContext && phpAudioContext.state === 'suspended') {
                phpAudioContext.resume();
            }
            startPhpVisualizerLoop();
        };

        window.closeVisualizerModalPhp = function() {
            document.getElementById('visualizer-modal').classList.add('hidden');
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(err => console.error(err));
            }
        };

        window.closeVisualizerModal = window.closeVisualizerModalPhp;

        window.setVisualizerStylePhp = function(style) {
            phpVisualizerStyle = style;
            const styles = ['bars', 'scope', 'beat', 'circle', 'particles'];
            styles.forEach(s => {
                const btn = document.getElementById('v-style-' + s);
                if (btn) {
                    if (s === style) {
                        btn.className = 'px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition flex items-center gap-1.5 bg-sky-500/10 text-sky-400 cursor-pointer';
                    } else {
                        btn.className = 'px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition flex items-center gap-1.5 text-slate-400 hover:text-white hover:bg-slate-900 cursor-pointer';
                    }
                }
            });
        };

        window.setVisualizerColorPhp = function(color) {
            phpVisualizerColor = color;
            const colors = ['wmp', 'neon', 'fire', 'cyber'];
            colors.forEach(c => {
                const btn = document.getElementById('v-color-' + c);
                if (btn) {
                    if (c === color) {
                        let activeBg = 'bg-cyan-500/10 text-cyan-405 border border-cyan-500/20';
                        if (c === 'neon') activeBg = 'bg-purple-500/10 text-purple-400 border border-purple-500/20';
                        if (c === 'fire') activeBg = 'bg-orange-500/10 text-orange-400 border border-orange-500/20';
                        if (c === 'cyber') activeBg = 'bg-green-500/10 text-green-400 border border-green-500/20';
                        btn.className = 'px-2 py-1 rounded text-[9px] uppercase font-bold tracking-wider transition ' + activeBg + ' cursor-pointer';
                    } else {
                        btn.className = 'px-2 py-1 rounded text-[9px] uppercase font-bold tracking-wider transition text-slate-400 hover:text-white cursor-pointer';
                    }
                }
            });
        };

        function startPhpVisualizerLoop() {
            if (phpVisualizerAnimFrame) return;

            const canvas = document.getElementById('php-visualizer-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            let angleOffset = 0;

            function draw() {
                phpVisualizerAnimFrame = requestAnimationFrame(draw);

                const width = canvas.clientWidth;
                const height = canvas.clientHeight;

                if (canvas.width !== width || canvas.height !== height) {
                    canvas.width = width;
                    canvas.height = height;
                }

                ctx.fillStyle = '#020617';
                ctx.fillRect(0, 0, width, height);

                ctx.strokeStyle = 'rgba(15, 23, 42, 0.5)';
                ctx.lineWidth = 1;
                const gridSize = 16;
                for (let x = 0; x < width; x += gridSize) {
                    ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, height); ctx.stroke();
                }
                for (let y = 0; y < height; y += gridSize) {
                    ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(width, y); ctx.stroke();
                }

                const bufferLength = phpAnalyserNode ? phpAnalyserNode.frequencyBinCount : 128;
                const dataArray = new Uint8Array(bufferLength);

                let active = false;
                if (phpAnalyserNode) {
                    if (phpVisualizerStyle === 'scope') {
                        phpAnalyserNode.getByteTimeDomainData(dataArray);
                    } else {
                        phpAnalyserNode.getByteFrequencyData(dataArray);
                    }
                    for (let i = 0; i < bufferLength; i++) {
                        if (dataArray[i] > 0 && dataArray[i] !== 128) {
                            active = true;
                            break;
                        }
                    }
                }

                if (!active) {
                    const time = Date.now() * 0.003;
                    for (let i = 0; i < bufferLength; i++) {
                        if (phpVisualizerStyle === 'scope') {
                            dataArray[i] = 128 + Math.sin(i * 0.15 + time) * 35 * Math.sin(i * 0.03);
                        } else {
                            dataArray[i] = Math.max(10, (Math.sin(i * 0.1 + time) + 1.0) * 45 * Math.sin(i * 0.01 + 0.5));
                        }
                    }
                }

                let bassSum = 0;
                const bassBins = Math.min(10, bufferLength);
                for (let i = 0; i < bassBins; i++) {
                    bassSum += (phpVisualizerStyle === 'scope' ? Math.abs(dataArray[i] - 128) * 2 : dataArray[i]);
                }
                const bassAvg = bassSum / bassBins;
                const beatScale = 1.0 + (bassAvg / 255) * 0.35;

                let primaryColor = '#06b6d4';
                let secondaryColor = '#3b82f6';
                let glowColor = 'rgba(6, 182, 212, 0.5)';

                if (phpVisualizerColor === 'neon') {
                    primaryColor = '#a855f7'; secondaryColor = '#f43f5e'; glowColor = 'rgba(168, 85, 247, 0.5)';
                } else if (phpVisualizerColor === 'fire') {
                    primaryColor = '#f97316'; secondaryColor = '#eab308'; glowColor = 'rgba(249, 115, 22, 0.55)';
                } else if (phpVisualizerColor === 'cyber') {
                    primaryColor = '#39ff14'; secondaryColor = '#10b981'; glowColor = 'rgba(57, 255, 20, 0.6)';
                }

                if (phpVisualizerStyle === 'bars') {
                    const barWidth = (width / bufferLength) * 1.5;
                    let x = 0;
                    for (let i = 0; i < bufferLength; i++) {
                        const barHeight = (dataArray[i] / 255) * height * 0.8;
                        if (barHeight > phpPeaks[i]) {
                            phpPeaks[i] = barHeight;
                        } else {
                            phpPeaks[i] = Math.max(0, phpPeaks[i] - 1.2);
                        }

                        const grad = ctx.createLinearGradient(x, height, x, height - barHeight);
                        grad.addColorStop(0, 'rgba(15, 23, 42, 0.8)');
                        grad.addColorStop(0.5, secondaryColor);
                        grad.addColorStop(1, primaryColor);

                        ctx.fillStyle = grad;
                        ctx.fillRect(x, height - barHeight, barWidth - 1, barHeight);

                        ctx.fillStyle = primaryColor;
                        ctx.shadowBlur = beatScale > 1.15 ? 8 : 4;
                        ctx.shadowColor = glowColor;
                        ctx.fillRect(x, height - phpPeaks[i] - 2, barWidth - 1, 1.5);
                        ctx.shadowBlur = 0;

                        x += barWidth;
                        if (x >= width) break;
                    }
                } else if (phpVisualizerStyle === 'scope') {
                    ctx.beginPath();
                    ctx.lineWidth = 2.5 + (beatScale - 1.0) * 4;
                    ctx.strokeStyle = primaryColor;
                    ctx.shadowBlur = 12 * beatScale;
                    ctx.shadowColor = glowColor;

                    const sliceWidth = width / bufferLength;
                    let x = 0;
                    for (let i = 0; i < bufferLength; i++) {
                        const v = dataArray[i] / 128.0;
                        const y = (v * height) / 2;
                        if (i === 0) ctx.moveTo(x, y);
                        else ctx.lineTo(x, y);
                        x += sliceWidth;
                    }
                    ctx.lineTo(width, height / 2);
                    ctx.stroke();
                    ctx.shadowBlur = 0;

                    ctx.strokeStyle = 'rgba(51, 65, 85, 0.3)';
                    ctx.lineWidth = 1;
                    ctx.beginPath(); ctx.moveTo(0, height / 2); ctx.lineTo(width, height / 2); ctx.stroke();
                } else if (phpVisualizerStyle === 'beat') {
    const centerX = width / 2;
    const centerY = height / 2;
    const baseRadius = Math.min(width, height) * 0.25;
    const modRadius = baseRadius * beatScale;

    const grad = ctx.createRadialGradient(centerX, centerY, baseRadius * 0.5, centerX, centerY, modRadius * 1.5);
    grad.addColorStop(0, primaryColor);
    grad.addColorStop(0.5, secondaryColor);
    grad.addColorStop(1, 'transparent');

    ctx.fillStyle = grad;
    ctx.shadowBlur = beatScale > 1.2 ? 30 : 15;
    ctx.shadowColor = glowColor;
    
    ctx.beginPath();
    ctx.arc(centerX, centerY, modRadius, 0, Math.PI * 2);
    ctx.fill();
    ctx.shadowBlur = 0;

    ctx.strokeStyle = primaryColor;
    ctx.lineWidth = 1.5;
    ctx.beginPath(); ctx.arc(centerX, centerY, baseRadius, 0, Math.PI * 2); ctx.stroke();
} else if (phpVisualizerStyle === 'circle') {
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) * 0.2;
    
    ctx.shadowBlur = 10;
    ctx.shadowColor = glowColor;
    
    const totalBars = Math.min(120, bufferLength);
    const angleStep = (Math.PI * 2) / totalBars;
    
    for(let i = 0; i < totalBars; i++) {
        const val = dataArray[i] / 255.0;
        const barHeight = val * (Math.min(width, height) * 0.3) * beatScale;
        const angle = i * angleStep;
        
        // Line going outwards
        const x1 = centerX + Math.cos(angle) * radius;
        const y1 = centerY + Math.sin(angle) * radius;
        const x2 = centerX + Math.cos(angle) * (radius + barHeight);
        const y2 = centerY + Math.sin(angle) * (radius + barHeight);
        
        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(x2, y2);
        
        const grad = ctx.createLinearGradient(x1, y1, x2, y2);
        grad.addColorStop(0, secondaryColor);
        grad.addColorStop(1, primaryColor);
        
        ctx.strokeStyle = grad;
        ctx.lineWidth = 3;
        ctx.stroke();
        
        // Mirrored inner line
        const x3 = centerX + Math.cos(angle) * Math.max(0, radius - (barHeight*0.3));
        const y3 = centerY + Math.sin(angle) * Math.max(0, radius - (barHeight*0.3));
        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(x3, y3);
        ctx.strokeStyle = primaryColor;
        ctx.lineWidth = 1.5;
        ctx.stroke();
    }
    ctx.shadowBlur = 0;
    
    // Central hollow circle
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius - 2, 0, Math.PI*2);
    ctx.strokeStyle = secondaryColor;
    ctx.lineWidth = 2;
    ctx.stroke();
} else if (phpVisualizerStyle === 'particles') {
    // Simple stateless particle burst simulation based on equalizer arrays
    ctx.shadowBlur = 8;
    ctx.shadowColor = glowColor;
    
    const centerX = width / 2;
    const centerY = height / 2;
    
    // Center beat base
    ctx.fillStyle = primaryColor;
    ctx.beginPath();
    ctx.arc(centerX, centerY, 20 * beatScale, 0, Math.PI * 2);
    ctx.fill();
    
    const slice = (Math.PI * 2) / bufferLength;
    for (let i = 0; i < bufferLength; i++) {
        const val = dataArray[i] / 255;
        if(val < 0.1) continue;
        
        const dist = val * (Math.min(width, height) * 0.45) * beatScale;
        const angle = i * slice + (Date.now() * 0.0005);
        
        const px = centerX + Math.cos(angle) * dist;
        const py = centerY + Math.sin(angle) * dist;
        
        const pSize = 1 + val * 4;
        
        ctx.fillStyle = (i % 2 === 0) ? primaryColor : secondaryColor;
        ctx.beginPath();
        ctx.arc(px, py, pSize, 0, Math.PI * 2);
        ctx.fill();
    }
    
    ctx.shadowBlur = 0;
}
}

            draw();
        }

        window.showEqualizerPhp = function() {
            document.getElementById('equalizer-modal').classList.remove('hidden');
            initPhpEqualizer();
            if (phpAudioContext && phpAudioContext.state === 'suspended') {
                phpAudioContext.resume();
            }
        };

        window.closeEqualizerModal = function() {
            document.getElementById('equalizer-modal').classList.add('hidden');
        };

        window.onEqSliderChangePhp = function(bandIndex, val) {
            initPhpEqualizer();
            if (phpAudioContext && phpAudioContext.state === 'suspended') {
                phpAudioContext.resume();
            }
            
            const numVal = parseFloat(val);
            phpEqGains[bandIndex] = numVal;
            
            const label = document.getElementById('php-eq-gain-val-' + bandIndex);
            if (label) {
                label.textContent = (numVal > 0 ? '+' : '') + numVal + 'dB';
            }
            
            if (phpBiquadFilters[bandIndex]) {
                phpBiquadFilters[bandIndex].gain.value = numVal;
            }
            
            setPhpActivePresetButtonStyle(null);
        };

        window.setEqPresetPhp = function(presetName) {
            const gains = phpPresets[presetName] || phpPresets.flat;
            
            for (let i = 0; i < 5; i++) {
                const slider = document.getElementById('php-eq-slider-' + i);
                if (slider) slider.value = gains[i];
                phpEqGains[i] = gains[i];
                
                const label = document.getElementById('php-eq-gain-val-' + i);
                if (label) {
                    label.textContent = (gains[i] > 0 ? '+' : '') + gains[i] + 'dB';
                }
                
                if (phpBiquadFilters[i]) {
                    phpBiquadFilters[i].gain.value = gains[i];
                }
            }
            
            setPhpActivePresetButtonStyle(presetName);
        };

        function setPhpActivePresetButtonStyle(activeName) {
            const btns = document.querySelectorAll('.preset-btn');
            btns.forEach(btn => {
                if (activeName && btn.id === 'preset-php-' + activeName) {
                    btn.className = "preset-btn px-1.5 py-1 bg-sky-500/10 border border-sky-500 text-sky-400 rounded-lg text-[10px] transition font-bold shadow-lg shadow-sky-500/5 scale-[1.02]";
                } else {
                    btn.className = "preset-btn px-1.5 py-1 bg-slate-900 border border-slate-800 text-slate-400 rounded-lg text-[10px] hover:border-sky-500 hover:text-sky-400 transition font-medium";
                }
            });
        }

        // MUSIC REPRODUCER LOGIC
        function loadTrack(track) {
            window.phpCurrentTrack = track;
            const trackIdStr = String(track.id);
            const isRadio = track.artist === 'Rádio On-line' || track.album === 'Sintonizada' || String(track.file_name || track.fileName || "").includes('://');
            const isLocal = trackIdStr.startsWith('seed-') || trackIdStr.startsWith('radio-');
            
            if (isRadio) {
                const streamUrl = track.fileName || track.file_name || track.url;
                audio.src = API + '?route=proxy_radio&url=' + encodeURIComponent(streamUrl);
            } else {
                audio.src = isLocal ? (track.fileName || track.file_name || track.url) : (API + '?route=stream&id=' + trackIdStr);
            }
            
            document.getElementById('track-cover').src = track.coverUrl || track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100';
            document.getElementById('track-title').textContent = track.title;
            document.getElementById('track-artist').textContent = track.artist;
            
            if (phpAudioContext && phpAudioContext.state === 'suspended') {
                phpAudioContext.resume();
            }
            audio.play();
            isPlaying = true;
            document.getElementById('player-play-btn').innerHTML = `<i data-lucide="pause" class="w-4 h-4 fill-current"></i>`;
            lucide.createIcons();
            
            // update highlighting on tables
            renderTracksTable();
            renderPlayerMiniQueue();
            updatePlayerFavHeart();
            if (typeof updateReprodutorTab === 'function') {
                updateReprodutorTab();
            }
        }

        function renderPlayerMiniQueue() {
            const wrapper = document.getElementById('player-mini-queue-wrapper');
            const list = document.getElementById('player-mini-queue-list');
            if (!wrapper || !list) return;

            if (activeQueue && activeQueue.length > 1) {
                wrapper.classList.remove('hidden');
                
                let html = '';
                activeQueue.forEach((tr, index) => {
                    const highlight = activeQueueIdx === index;
                    html += `
                        <div onclick="playFromMiniQueue(${index})" class="flex items-center gap-1.5 px-2 py-0.5 rounded cursor-pointer transition select-none ${highlight ? 'bg-sky-500/15 text-sky-400 font-bold' : 'hover:bg-slate-800 text-slate-400 hover:text-slate-200'}" style="font-size: 8.5px;">
                            <span class="font-mono shrink-0 opacity-45" style="font-size: 7.5px;">${index + 1}</span>
                            <span class="truncate flex-1 block font-sans">${tr.title}</span>
                            ${highlight ? `<i data-lucide="volume-2" class="w-2.5 h-2.5 text-sky-400 shrink-0"></i>` : ''}
                        </div>
                    `;
                });
                list.innerHTML = html;
                lucide.createIcons();
            } else {
                wrapper.classList.add('hidden');
            }
        }

        window.playFromMiniQueue = function(index) {
            if (!activeQueue || index < 0 || index >= activeQueue.length) return;
            activeQueueIdx = index;
            loadTrack(activeQueue[index]);
        };

        function togglePlay() {
            if (isPartyMode) {
                alert("O Modo Festa está ativo! A pausa manual está desativada para manter a reprodução contínua.");
                return;
            }
            if (activeQueue.length === 0 && allTracks.length > 0) {
                activeQueue = allTracks;
                activeQueueIdx = 0;
                loadTrack(allTracks[0]);
                return;
            }
            if (isPlaying) {
                audio.pause();
                isPlaying = false;
                document.getElementById('player-play-btn').innerHTML = `<i data-lucide="play" class="w-4 h-4 fill-current"></i>`;
            } else {
                audio.play();
                isPlaying = true;
                document.getElementById('player-play-btn').innerHTML = `<i data-lucide="pause" class="w-4 h-4 fill-current"></i>`;
            }
            lucide.createIcons();
            if (typeof updateReprodutorPlayPauseState === 'function') {
                updateReprodutorPlayPauseState();
            }
        }

        function next() {
            if (activeQueue.length === 0) return;
            if (isShuffle) {
                activeQueueIdx = Math.floor(Math.random() * activeQueue.length);
            } else {
                activeQueueIdx = (activeQueueIdx + 1) % activeQueue.length;
            }
            loadTrack(activeQueue[activeQueueIdx]);
        }

        function prev() {
            if (activeQueue.length === 0) return;
            activeQueueIdx = (activeQueueIdx - 1 + activeQueue.length) % activeQueue.length;
            loadTrack(activeQueue[activeQueueIdx]);
        }

        function seek(val) {
            audio.currentTime = val;
        }

        window.phpUserVolume = 0.7;
        function volume(val) {
            window.phpUserVolume = parseFloat(val);
            audio.volume = val;
        }

        function mute() {
            if (audio.muted) {
                audio.muted = false;
                document.getElementById('player-mute').innerHTML = `<i data-lucide="volume-2" class="w-4 h-4"></i>`;
            } else {
                audio.muted = true;
                document.getElementById('player-mute').innerHTML = `<i data-lucide="volume-x" class="w-4 h-4 text-rose-500"></i>`;
            }
            lucide.createIcons();
        }

        function toggleShuffle() {
            isShuffle = !isShuffle;
            document.getElementById('player-shuffle').className = isShuffle ? "text-sky-400 font-bold transition cursor-pointer" : "text-slate-500 hover:text-white transition cursor-pointer";
            if (typeof updateReprodutorLoopShuffleState === 'function') {
                updateReprodutorLoopShuffleState();
            }
        }

        function toggleLoop() {
            isLoop = !isLoop;
            document.getElementById('player-loop').className = isLoop ? "text-sky-400 font-bold transition cursor-pointer" : "text-slate-500 hover:text-white transition cursor-pointer";
            if (typeof updateReprodutorLoopShuffleState === 'function') {
                updateReprodutorLoopShuffleState();
            }
        }

        window.togglePartyModePhp = function() {
            isPartyMode = !isPartyMode;
            const btn = document.getElementById('player-party-mode-btn');
            if (isPartyMode) {
                btn.className = "text-pink-400 font-bold transition cursor-pointer p-1.5 rounded-lg bg-pink-950/20 border border-pink-900/40 animate-pulse shrink-0 flex items-center gap-1";
                btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4 fill-current text-pink-400"></i> <span class="text-[9px] font-sans font-black uppercase tracking-wider hidden lg:inline-block">Festa Ativo</span>`;
                
                // Force continuous play if not currently playing
                if (!isPlaying && activeQueue.length > 0) {
                    togglePlay();
                } else if (!isPlaying && allTracks.length > 0) {
                    activeQueue = allTracks;
                    activeQueueIdx = 0;
                    loadTrack(allTracks[0]);
                }
            } else {
                btn.className = "text-slate-400 hover:text-pink-400 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0";
                btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i>`;
            }
            if (window.lucide) lucide.createIcons();
            const partyCheck = document.getElementById('reprodutor-party-checkbox');
            if (partyCheck) partyCheck.checked = isPartyMode;
        };

        // NEW REPRODUTOR SYNC & CONTROLLER LOGIC
        window.updateReprodutorTab = function() {
            const track = window.phpCurrentTrack;
            const emptyEl = document.getElementById('reprodutor-empty-state');
            const activeEl = document.getElementById('reprodutor-active-state');
            
            if (!track) {
                if (emptyEl) emptyEl.classList.remove('hidden');
                if (activeEl) activeEl.classList.add('hidden');
                return;
            }
            
            if (emptyEl) emptyEl.classList.add('hidden');
            if (activeEl) activeEl.classList.remove('hidden');
            
            // Automatically request lyrics for the active track
            if (track && window.phpLastLoadedLyricsTrackId !== String(track.id)) {
                window.phpLastLoadedLyricsTrackId = String(track.id);
                window.fetchLyricsPhp(track.artist, track.title);
            }

            // Automatically ensure visualizer is running when reproducing
            if (isPlaying) {
                initPhpEqualizer();
                if (phpAudioContext && phpAudioContext.state === 'suspended') {
                    phpAudioContext.resume();
                }
                startPhpVisualizerLoop();
            }
            
            // Sync metadata
            const coverUrl = track.coverUrl || track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=300';
            const coverEl = document.getElementById('reprodutor-cover');
            if (coverEl) coverEl.src = coverUrl;
            
            const titleEl = document.getElementById('reprodutor-title');
            if (titleEl) titleEl.textContent = track.title || 'Título';
            
            const artistEl = document.getElementById('reprodutor-artist');
            if (artistEl) artistEl.textContent = track.artist || 'Artista';
            
            const albumEl = document.getElementById('reprodutor-album');
            if (albumEl) albumEl.textContent = track.album || 'Álbum Desconhecido';
            
            const genreEl = document.getElementById('reprodutor-genre');
            if (genreEl) genreEl.textContent = track.genre || track.gênero || 'GERAL';
            
            // Sync range inputs with current audio duration
            const seekEl = document.getElementById('reprodutor-seek');
            if (seekEl) {
                seekEl.max = audio.duration || 180;
                seekEl.value = audio.currentTime || 0;
            }
            const curTimeEl = document.getElementById('reprodutor-current-time');
            if (curTimeEl) curTimeEl.textContent = formatSecs(audio.currentTime);
            const durationEl = document.getElementById('reprodutor-duration');
            if (durationEl) durationEl.textContent = formatSecs(audio.duration || 180);
            
            // Sync buttons classes
            updateReprodutorPlayPauseState();
            updateReprodutorLoopShuffleState();
            
            // Sync party mode
            const partyCheck2 = document.getElementById('reprodutor-party-checkbox');
            if (partyCheck2) partyCheck2.checked = isPartyMode;
            
            // Sync sleep timer option selector
            const sleepSelect = document.getElementById('reprodutor-sleep-timer-select');
            if (sleepSelect) {
                if (sleepTimerSecs === null) {
                    sleepSelect.value = "0";
                } else {
                    const mins = Math.ceil(sleepTimerSecs / 60);
                    if ([5, 15, 30, 60].includes(mins)) {
                        sleepSelect.value = String(mins);
                    } else if (mins > 45) {
                        sleepSelect.value = "60";
                    } else if (mins > 22) {
                        sleepSelect.value = "30";
                    } else if (mins > 10) {
                        sleepSelect.value = "15";
                    } else {
                        sleepSelect.value = "5";
                    }
                }
            }
            
            // Sync crossfade selection list option
            const crossSelect = document.getElementById('reprodutor-crossfade-select');
            if (crossSelect) {
                crossSelect.value = String(window.phpCrossfadeDuration || 0);
            }
            
            // Update favorite button heart highlight
            updatePlayerFavHeart();
            
            // Render Queue list inside Reprodutor view
            renderReprodutorQueueList();
        };

        function updateReprodutorPlayPauseState() {
            const masterPlayBtn = document.getElementById('reprodutor-master-play-btn');
            const coverEl = document.getElementById('reprodutor-cover');
            if (masterPlayBtn) {
                if (isPlaying) {
                     masterPlayBtn.innerHTML = `<i data-lucide="pause" class="w-6 h-6 fill-current"></i>`;
                     if (coverEl) {
                         coverEl.classList.remove('paused-animation');
                         coverEl.classList.add('animate-spin-slow');
                     }
                } else {
                     masterPlayBtn.innerHTML = `<i data-lucide="play" class="w-6 h-6 fill-current ml-0.5"></i>`;
                     if (coverEl) {
                         coverEl.classList.add('paused-animation');
                     }
                }
                if (window.lucide) lucide.createIcons();
            }
        }

        function updateReprodutorLoopShuffleState() {
            const shuffleBtn = document.getElementById('reprodutor-shuffle-btn');
            const loopBtn = document.getElementById('reprodutor-loop-btn');
            if (shuffleBtn) {
                if (isShuffle) {
                    shuffleBtn.className = "p-2.5 rounded-xl border transition cursor-pointer bg-sky-500/10 border-sky-500 text-sky-400 font-bold";
                } else {
                    shuffleBtn.className = "p-2.5 rounded-xl border transition cursor-pointer bg-slate-950 border-transparent text-slate-500 hover:text-slate-350";
                }
            }
            if (loopBtn) {
                if (isLoop) {
                    loopBtn.className = "p-2.5 rounded-xl border transition cursor-pointer bg-sky-500/10 border-sky-500 text-sky-400 font-bold";
                } else {
                    loopBtn.className = "p-2.5 rounded-xl border transition cursor-pointer bg-slate-950 border-transparent text-slate-500 hover:text-slate-350";
                }
            }
        }

        function renderReprodutorQueueList() {
            const listEl = document.getElementById('reprodutor-queue-list');
            const countEl = document.getElementById('reprodutor-queue-count');
            if (!listEl) return;
            
            if (!activeQueue || activeQueue.length === 0) {
                listEl.innerHTML = `<p class="text-xs text-slate-550 py-4 text-center">Nenhuma música na fila.</p>`;
                if (countEl) countEl.textContent = '0 músicas';
                return;
            }
            
            if (countEl) {
                countEl.textContent = `${activeQueueIdx + 1} de ${activeQueue.length} fatias`;
            }
            
            let html = '';
            activeQueue.forEach((tr, index) => {
                const isCurrent = activeQueueIdx === index;
                html += `
                    <div onclick="playFromReprodutorQueue(${index})" class="flex items-center justify-between p-2 rounded-xl transition cursor-pointer select-none group border ${
                        isCurrent 
                        ? 'bg-sky-500/10 border-sky-500/20 text-sky-400 font-bold' 
                        : 'bg-slate-955/30 border-transparent hover:bg-slate-900/60 text-slate-300 hover:text-white'
                    }">
                        <div class="flex items-center gap-3 truncate">
                            <span class="text-[10px] font-mono text-slate-500 tracking-tight shrink-0 flex items-center justify-center w-5">
                                ${isCurrent ? `<span class="w-1.5 h-1.5 rounded-full bg-sky-450 animate-pulse"></span>` : index + 1}
                            </span>
                            <img src="${tr.coverUrl || tr.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=50'}" class="w-6 h-6 rounded object-cover border border-slate-900 shrink-0" />
                            <div class="truncate">
                                <p class="text-[11px] truncate font-semibold leading-tight">${tr.title || 'Sem título'}</p>
                                <p class="text-[10px] text-slate-500 truncate leading-none mt-0.5">${tr.artist || 'Sem artista'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] text-slate-500 font-mono pr-2">
                            <span>FAIXA</span>
                        </div>
                    </div>
                `;
            });
            listEl.innerHTML = html;
        }

        window.playFromReprodutorQueue = function(idx) {
            playFromMiniQueue(idx);
        };

        window.togglePartyModeFromReprodutor = function(checkbox) {
            if (isPartyMode !== checkbox.checked) {
                window.togglePartyModePhp();
            }
        };

        window.changeReprodutorSleepTimer = function(val) {
            const minutes = parseInt(val, 10);
            if (minutes === 0) {
                setSleepTimerPhp(null);
            } else {
                setSleepTimerPhp(minutes);
            }
        };

        window.changeReprodutorCrossfade = function(val) {
            setCrossfadePhp(val);
        };

        window.toggleReprodutorFavorite = async function(e) {
            const track = window.phpCurrentTrack;
            if (!track) return;
            await toggleFav(e, track.id);
        };

        // DATABASE OPERATIONS / RENDER TABLES
        function renderTracksTable() {
            const tableWrapper = document.getElementById('tracks-table-wrapper');
            const headerBlock = document.getElementById('tracks-header-block');
            const artistView = document.getElementById('artist-albums-view');
            const gridLayout = document.getElementById('tracks-grid-layout');
            const tbody = document.getElementById('tracks-table-body');
            tbody.innerHTML = '';
            
            const searchVal = document.getElementById('search-input').value.toLowerCase();
            
            // Target lists based on filters/views
            let sourceList = allTracks;
            if (activeTab === 'favorites') {
                sourceList = allTracks.filter(t => allFavorites.includes(String(t.id)));
            } else if (selectedArtist) {
                sourceList = allTracks.filter(t => t.artist === selectedArtist);
            } else if (selectedPlaylistId) {
                const playlist = allPlaylists.find(pl => String(pl.id) === String(selectedPlaylistId));
                if (playlist) {
                    sourceList = allTracks.filter(t => playlist.trackIds.includes(String(t.id)));
                }
            } else if (activePlaylistAlbum) {
                sourceList = allTracks.filter(t => t.album === activePlaylistAlbum);
            }

            const favActions = document.getElementById('favorites-actions-block');
            if (activeTab === 'favorites' && sourceList.length > 0) {
                if (favActions) {
                    favActions.classList.remove('hidden');
                    favActions.innerHTML = `
                        <button onclick="playAllFavorites()" class="px-3 py-1.5 bg-sky-500 hover:bg-sky-600 active:scale-95 text-white rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 shadow-md cursor-pointer select-none">
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i> Reproduzir Todas
                        </button>
                        <button onclick="shuffleAllFavorites()" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-850 active:scale-95 text-slate-300 hover:text-white rounded-xl text-[11px] font-bold transition border border-slate-800 flex items-center gap-1.5 shadow-md cursor-pointer select-none">
                            <i data-lucide="shuffle" class="w-3.5 h-3.5 text-sky-400"></i> Ordem Aleatória (Shuffle)
                        </button>
                    `;
                }
            } else if (selectedPlaylistId && sourceList.length > 0) {
                if (favActions) {
                    favActions.classList.remove('hidden');
                    favActions.innerHTML = `
                        <button onclick="playAllPlaylist()" class="px-3 py-1.5 bg-sky-500 hover:bg-sky-600 active:scale-95 text-white rounded-xl text-[11px] font-bold transition flex items-center gap-1.5 shadow-md cursor-pointer select-none">
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i> Reproduzir Todas
                        </button>
                        <button onclick="shuffleAllPlaylist()" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-850 active:scale-95 text-slate-300 hover:text-white rounded-xl text-[11px] font-bold transition border border-slate-800 flex items-center gap-1.5 shadow-md cursor-pointer select-none">
                            <i data-lucide="shuffle" class="w-3.5 h-3.5 text-sky-400"></i> Ordem Aleatória (Shuffle)
                        </button>
                    `;
                }
            } else {
                if (favActions) {
                    favActions.classList.add('hidden');
                    favActions.innerHTML = '';
                }
            }

            if (activePlaylistAlbum) {
                if (gridLayout) gridLayout.classList.add('hidden');
                tableWrapper.classList.add('hidden');
                headerBlock.classList.add('hidden');
                artistView.classList.remove('hidden');

                const albumTracks = sourceList;
                const firstTrack = albumTracks[0] || {};
                const albumName = activePlaylistAlbum;
                const albumCover = loadedCoversCache[albumName] || firstTrack.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400';
                const albumIdSafe = albumName.replace(/[^a-zA-Z0-9]/g, '-');

                let albumYear = null;
                for (const t of albumTracks) {
                    if (t.album_year) {
                        albumYear = parseInt(t.album_year);
                        break;
                    }
                }

                let html = `
                    <div class="space-y-8 animate-fade-in text-slate-100 pb-12">
                        <!-- Navigation / Title header -->
                        <div class="flex items-center justify-between border-b border-slate-900 pb-3">
                            <div>
                                <h1 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 hover:text-white cursor-pointer" onclick="activePlaylistAlbum = ''; renderLeftSidebar(); renderTracksTable();">
                                    <i data-lucide="disc" class="w-3.5 h-3.5 text-sky-400"></i> Álbuns &rarr; <span class="text-white">${albumName}</span>
                                </h1>
                            </div>
                            <button onclick="activePlaylistAlbum = ''; renderLeftSidebar(); renderTracksTable();" class="text-xs font-semibold px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition cursor-pointer">
                                Voltar para Biblioteca
                            </button>
                        </div>

                        <!-- One-album grid -->
                        <div class="bg-slate-950/40 border border-slate-900 rounded-2xl overflow-hidden p-5 shadow-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <!-- Left side: Cover Art, info and buttons -->
                                <div class="sm:col-span-1 space-y-4">
                                    <div class="aspect-square bg-slate-900/40 rounded-2xl overflow-hidden border border-slate-900 flex items-center justify-center relative shadow-lg">
                                        <img id="album-cover-img-${albumIdSafe}" src="${albumCover}" referrerpolicy="no-referrer" class="w-full h-full object-cover shadow-md border border-slate-800/40">
                                    </div>
                                    <div class="text-center">
                                        <h3 class="font-extrabold text-[#ffffff] text-lg leading-tight line-clamp-2">${albumName}</h3>
                                        <p class="text-[12px] text-sky-450 font-semibold mt-1.5 mb-1">${firstTrack.artist || 'Artista Desconhecido'}</p>
                                        <p class="text-[10px] text-slate-500 font-mono mt-1 uppercase tracking-wide flex items-center justify-center gap-1">
                                            ${albumYear ? `<span class="bg-sky-500/10 text-sky-400 px-1 py-0.5 rounded font-black mr-1">${albumYear}</span>` : ''}
                                            ${albumTracks.length} ${albumTracks.length === 1 ? 'música' : 'músicas'} &bull; ÁLBUM
                                        </p>
                                    </div>
                                    
                                    <!-- Action buttons stack -->
                                    <div class="flex flex-col gap-2 pt-2">
                                        <div class="flex gap-2">
                                            <button onclick="playAlbumByName(event, '${albumName.replace(new RegExp("'", "g"), "\\'")}')" class="flex-grow py-1.5 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 cursor-pointer shadow-lg shadow-sky-500/15 whitespace-nowrap">
                                                <i data-lucide="play" class="w-3 h-3 text-white fill-white"></i> Tocar todas
                                            </button>
                                            <button onclick="playAlbumByNameShuffled(event, '${albumName.replace(new RegExp("'", "g"), "\\'")}')" class="flex-grow py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-slate-800 cursor-pointer whitespace-nowrap">
                                                <i data-lucide="shuffle" class="w-3 h-3"></i> Aleatório
                                            </button>
                                        </div>
                                        ${(currentUser.role === 'admin' || currentUser.can_download !== false || currentUser.can_share !== false) ? `
    <div class="flex gap-2 w-full mt-1.5">
        ${(currentUser.role === 'admin' || currentUser.can_download !== false) ? `
        <button onclick="downloadAlbum(event, '${albumName.replace(/'/g, "\\\\\\\\'")}')" class="flex-1 py-1.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-emerald-500/30 cursor-pointer">
            <i data-lucide="download" class="w-3 h-3"></i> Baixar Álbum
        </button>` : ''}
        ${(currentUser.role === 'admin' || currentUser.can_share !== false) ? `
        <button onclick="shareAlbum('${albumName.replace(/'/g, "\\\\\\\\'")}', '${selectedArtist.replace(/'/g, "\\\\\\\\'")}')" class="flex-1 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-indigo-500/30 cursor-pointer">
            <i data-lucide="share-2" class="w-3 h-3"></i> Compartilhar
        </button>` : ''}
    </div>
` : ''}
                                    </div>
                                </div>
                                
                                <!-- Right side: Track table list -->
                                <div class="sm:col-span-2">
                                    <div class="overflow-x-auto ${albumTracks.length > 15 ? 'max-h-[480px] overflow-y-auto' : 'max-h-[2000px]'} custom-scroll">
                                        <table class="w-full text-left text-xs text-slate-300">
                                            <thead>
                                                <tr class="border-b border-slate-900/60 text-slate-500 font-mono tracking-wider text-[9px] uppercase">
                                                    <th class="py-2 px-3 w-10 text-center">#</th>
                                                    <th class="py-2 px-3">Faixa</th>
                                                    <th class="py-2 px-3 text-center w-16">Duração</th>
                                                    <th class="py-2 px-3 text-right w-12">Fav</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-900/40">
                    `;
 
                    albumTracks.forEach((track, index) => {
                        const isFav = allFavorites.includes(String(track.id));
                        const highlight = activeQueue[activeQueueIdx] && String(activeQueue[activeQueueIdx].id) === String(track.id);
                        
                        html += `
                            <tr class="hover:bg-slate-900/30 group/row transition duration-150">
                                <td class="py-2 px-3 text-center font-mono text-slate-600 text-xs w-10 ${highlight ? 'text-sky-450 font-black' : ''}">${index + 1}</td>
                                <td class="py-2 px-3 font-semibold">
                                    <div class="flex items-center gap-2 max-w-full">
                                        <button class="font-bold text-white hover:text-sky-400 text-left truncate hover:underline cursor-pointer ${highlight ? 'text-sky-450' : ''}" onclick="playImmediateFromAlbum('${track.id}')">
                                            ${track.title}
                                        </button>
                                        ${currentUser.role === 'admin' ? `
                                            <button onclick="editTrackTitlePrompt(event, '\${track.id}', ${JSON.stringify(track.title).replace(/"/g, '&quot;')})" class="p-1 rounded opacity-0 group-hover/row:opacity-100 hover:bg-slate-800 text-slate-500 hover:text-sky-400 transition cursor-pointer shrink-0" title="Editar nome da música">
                                                <i data-lucide="edit-3" class="w-3 h-3"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]">
                                    s${formatSecs(track.duration || 180)}
                                </td>
                                <td class="py-2 px-3 text-right w-20">
                                    <div class="flex items-center justify-end gap-1">
                                        ${currentUser.role === 'admin' ? `
                                            <button onclick="downloadTrack(event, '\${track.id}')" class="p-1 text-slate-500 hover:text-sky-400 hover:bg-slate-900 rounded-lg transition cursor-pointer" title="Fazer Download da Faixa">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            </button>
                                        ` : ''}
                                        <button onclick="toggleFav(event, '\${track.id}')" class="p-1 rounded-lg border border-transparent hover:bg-slate-900 transition ${isFav ? 'text-[#f43f5e]' : 'text-slate-500 hover:text-white'} cursor-pointer" title="${isFav ? 'Remover dos Favoritos' : 'Marcar como Favorito'}">
                                            <i data-lucide="heart" class="w-3 h-3 ${isFav ? 'fill-current' : ''}"></i>
                                        </button>
                                        <button onclick="addToPlaylistDropdown(event, '\${track.id}')" class="p-1 text-slate-500 hover:text-sky-400 hover:bg-slate-900 rounded-lg transition cursor-pointer" title="Adicionar à Playlist">
                                            <i data-lucide="list-plus" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                artistView.innerHTML = html;
                lucide.createIcons();
                return;
            }

            if (selectedArtist) {
                if (gridLayout) gridLayout.classList.add('hidden');
                tableWrapper.classList.add('hidden');
                headerBlock.classList.add('hidden');
                artistView.classList.remove('hidden');
                
                // Group tracks by album
                const albums = {};
                sourceList.forEach(track => {
                    const alb = track.album || 'Single';
                    if (!albums[alb]) {
                        albums[alb] = [];
                    }
                    albums[alb].push(track);
                });
                
                let bannerPhoto = artistPhotoUrl || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1000&auto=format&fit=crop&q=80';
                let bioBlock = '';
                if (loadingArtistBio) {
                    bioBlock = `
                        <div class="bg-slate-900/30 border border-slate-900 rounded-2xl p-6 relative shadow-md animate-pulse">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-900 pb-2">
                                <i data-lucide="loader" class="w-3.5 h-3.5 text-sky-400 animate-spin"></i>
                                Biografia do Artista (Buscando no Last.fm...)
                            </h3>
                            <div class="py-4 text-xs text-slate-500">
                                Buscando biografia artística e imagens oficiais via Last.fm...
                            </div>
                        </div>
                    `;
                } else if (artistBioText) {
                    bioBlock = `
                        <div class="bg-slate-900/30 border border-slate-900 rounded-2xl p-6 space-y-3 relative shadow-md">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-900 pb-2">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-sky-400"></i>
                                Biografia do Artista (via Last.fm)
                            </h3>
                            <div class="text-xs/relaxed text-slate-400 font-medium whitespace-pre-line leading-relaxed pb-1">
                                <div class="${isBioExpanded ? '' : 'line-clamp-3'}">
                                    ${artistBioText}
                                </div>
                                ${artistBioText && artistBioText.trim().length > 100 ? '<button onclick="toggleBioExpanded()" class="mt-1.5 text-xs text-sky-400 hover:text-sky-300 font-bold transition flex items-center gap-1 cursor-pointer select-none">' + (isBioExpanded ? 'Ler menos' : 'Ler mais') + '</button>' : ''}
                            </div>
                        </div>
                    `;
                }

                let html = `
                    <div class="space-y-8 animate-fade-in text-slate-100">
                        <!-- Featured Artist Banner Hero -->
                        <div class="relative h-64 md:h-80 rounded-2xl overflow-hidden shadow-2xl border border-slate-900/40 bg-slate-950">
                            <!-- Blurred backdrop layer to fill potential empty space beautifully -->
                            <img src="${bannerPhoto}" referrerpolicy="no-referrer" alt="" class="absolute inset-0 w-full h-full object-cover filter blur-xl opacity-25 select-none scale-105 pointer-events-none">
                            <!-- Foreground complete uncropped artist cover image -->
                            <img src="${bannerPhoto}" referrerpolicy="no-referrer" alt="${selectedArtist}" class="absolute inset-0 w-full h-full object-contain filter brightness-[0.45]">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>
                            
                            ${currentUser.role === 'admin' ? '<div class="absolute top-4 right-4 z-20 flex gap-2"><button onclick="handleOpenArtistImageSearch()" class="p-2 px-3 bg-slate-950/80 hover:bg-slate-900 text-slate-300 hover:text-white rounded-xl text-[10px] font-black tracking-wider uppercase border border-slate-800/60 shadow-lg cursor-pointer transition select-none flex items-center gap-1.5 backdrop-blur-sm" title="Buscar logo do artista on-line"><i data-lucide=\"search\" class=\"w-3.5 h-3.5 text-sky-400\"></i> Buscar Logo On-line</button><button onclick="triggerArtistBannerUpload()" class="p-2 px-3 bg-slate-950/80 hover:bg-slate-900 text-slate-300 hover:text-white rounded-xl text-[10px] font-black tracking-wider uppercase border border-slate-800/60 shadow-lg cursor-pointer transition select-none flex items-center gap-1.5 backdrop-blur-sm" title="Alterar banner do artista"><i data-lucide=\"image\" class=\"w-3.5 h-3.5 text-sky-400\"></i> Alterar Banner</button></div><input type=\"file\" id=\"artist-banner-input\" accept=\"image/*\" class=\"hidden\" onchange=\"uploadArtistBanner(this)\" />' : ''}

                            <div class="absolute bottom-0 left-0 p-6 md:p-8 space-y-3 z-10 w-full flex flex-col md:flex-row md:items-end justify-between gap-4">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] uppercase font-black bg-sky-500/10 text-sky-400 border border-sky-500/15 mb-2.5">
                                        <i data-lucide="sparkles" class="w-3 h-3"></i> ARTISTA EXCLUSIVO
                                    </span>
                                    <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight">${selectedArtist}</h1>
                                    <p class="text-xs text-slate-400 font-medium mt-1">
                                        ${sourceList.length} ${sourceList.length === 1 ? 'música' : 'músicas'} &bull; ${Object.keys(albums).length} ${Object.keys(albums).length === 1 ? 'álbum' : 'álbuns'} em sua biblioteca
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <button onclick="playArtistByName(event, '${selectedArtist.replace(new RegExp("'", "g"), "\\'")}')" class="px-4.5 py-2.5 bg-sky-500 hover:bg-sky-600 font-bold text-xs uppercase tracking-wider text-white rounded-xl flex items-center gap-2 transition shadow-lg shadow-sky-500/15 cursor-pointer">
                                        <i data-lucide="play" class="w-4 h-4 text-white fill-white"></i> Tocar Músicas
                                    </button>
                                    <button onclick="playArtistByNameShuffled(event, '${selectedArtist.replace(new RegExp("'", "g"), "\\'")}')" class="px-4.5 py-2.5 bg-slate-900 hover:bg-slate-800 font-bold text-xs uppercase tracking-wider text-slate-330 rounded-xl flex items-center gap-2 border border-slate-800 transition cursor-pointer">
                                        <i data-lucide="shuffle" class="w-3.5 h-3.5"></i> Aleatório
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Biography block -->
                        ${bioBlock}

                        <!-- Album divider section -->
                        <div class="flex items-center justify-between border-b border-slate-900 pb-3">
                            <div>
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Discografia e Álbuns</h2>
                            </div>
                            <button onclick="selectedArtist = ''; renderLeftSidebar(); renderTracksTable();" class="text-xs font-semibold px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition cursor-pointer">
                                Voltar para Biblioteca
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-12">
                `;
                
                const albumList = Object.keys(albums).map(albumName => {
                    const albumTracks = albums[albumName];
                    let albumYear = null;
                    for (const t of albumTracks) {
                        if (t.album_year) {
                            albumYear = parseInt(t.album_year);
                            break;
                        }
                    }
                    return {
                        name: albumName,
                        tracks: albumTracks,
                        year: albumYear
                    };
                });

                albumList.sort((a, b) => {
                    if (a.year !== null && b.year !== null) {
                        return a.year - b.year; // Older albums first (ascending)
                    }
                    if (a.year !== null) return -1;
                    if (b.year !== null) return 1;
                    return a.name.localeCompare(b.name, 'pt-BR');
                });

                albumList.forEach(albumObj => { const albumName = albumObj.name; let albumTracks = albumObj.tracks; albumTracks.sort((a,b) => { const aNum=a.track_num?parseInt(a.track_num):9999; const bNum=b.track_num?parseInt(b.track_num):9999; return aNum !== bNum ? aNum-bNum : a.title.localeCompare(b.title); });
                    const albumYear = albumObj.year;
                    const firstTrack = albumTracks[0];
                    const albumCover = loadedCoversCache[albumName] || firstTrack.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400';
                    const albumIdSafe = albumName.replace(/[^a-zA-Z0-9]/g, '-');
                    const albumTrackIds = albumTracks.map(t => t.id).join(',');
                    
                    html += `
                        <div class="bg-slate-950/40 border border-slate-900 rounded-2xl overflow-hidden hover:border-slate-850 transition duration-150 p-5 shadow-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <!-- Left side: Big Cover Art, info and buttons -->
                                <div class="sm:col-span-1 space-y-4">
                                    <div class="aspect-square bg-slate-900/40 rounded-2xl overflow-hidden border border-slate-900 flex items-center justify-center relative group shadow-lg">
                                        <img id="album-cover-img-${albumIdSafe}" src="${albumCover}" referrerpolicy="no-referrer" class="w-full h-full object-cover shadow-md border border-slate-800/40 group-hover:scale-102 duration-300 transition">
                                        <div class="absolute inset-0 bg-black/45 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-200">
                                            <i data-lucide="disc" class="w-8 h-8 text-white animate-spin"></i>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h3 class="font-extrabold text-[#ffffff] text-lg leading-tight line-clamp-2">${albumName}</h3>
                                        <p class="text-[11px] text-slate-500 font-mono mt-1 uppercase tracking-wide flex items-center justify-center gap-1">
                                            ${albumYear ? `<span class="bg-sky-500/15 text-sky-400 px-1.5 py-0.5 rounded font-black mr-1">${albumYear}</span>` : ''}
                                            ${albumTracks.length} ${albumTracks.length === 1 ? 'música' : 'músicas'} &bull; ÁLBUM
                                        </p>
                                    </div>
                                    
                                    <!-- Action buttons stack -->
                                    <div class="flex flex-col gap-2 pt-2">
                                        <div class="flex gap-2">
                                            <button onclick="playAlbumByName(event, '${albumName.replace(new RegExp("'", "g"), "\\'")}')" class="flex-1 py-1.5 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 cursor-pointer shadow-lg shadow-sky-500/15 whitespace-nowrap">
                                                <i data-lucide="play" class="w-3 h-3 text-white fill-white"></i> Tocar
                                            </button>
                                            <button onclick="playAlbumByNameShuffled(event, '${albumName.replace(new RegExp("'", "g"), "\\'")}')" class="flex-1 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-slate-800 cursor-pointer whitespace-nowrap">
                                                <i data-lucide="shuffle" class="w-3 h-3"></i> Aleatório
                                            </button>
                                        </div>
                                        
                                        ${(currentUser.role === 'admin' || currentUser.can_download !== false || currentUser.can_share !== false) ? `
    <div class="flex flex-col gap-1.5">
        <div class="flex gap-2">
            ${(currentUser.role === 'admin' || currentUser.can_download !== false) ? `
            <button onclick="downloadAlbum(event, '${albumName.replace(/'/g, "\\\\\\\\'")}')" class="flex-1 py-1.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-emerald-500/30 cursor-pointer">
                <i data-lucide="download" class="w-3 h-3"></i> Baixar Álbum
            </button>` : ''}
            ${(currentUser.role === 'admin' || currentUser.can_share !== false) ? `
            <button onclick="shareAlbum('${albumName.replace(/'/g, "\\\\\\\\'")}', '${selectedArtist.replace(/'/g, "\\\\\\\\'")}')" class="flex-1 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-indigo-500/30 cursor-pointer">
                <i data-lucide="share-2" class="w-3 h-3"></i> Compartilhar
            </button>` : ''}
        </div>
` : ''}
        ${currentUser.role === 'admin' ? `
        <div class="flex gap-2">
            <button onclick="document.getElementById('album-cover-input-${albumIdSafe}').click()" class="flex-1 py-1.5 bg-slate-900 hover:bg-slate-850 text-slate-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-slate-800 cursor-pointer whitespace-nowrap">
                <i data-lucide="image" class="w-3.5 h-3.5 text-sky-400"></i> Capa
            </button>
            <button onclick="window.openAlbumBulkEditByElement(this, event)" data-track-ids="${albumTrackIds}" data-album-name="${albumName.replace(/\"/g, '&quot;')}" class="flex-1 py-1.5 bg-slate-900 hover:bg-slate-850 text-slate-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-slate-800 cursor-pointer whitespace-nowrap" title="Editar ID3 de todo este álbum">
                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-indigo-400"></i> Editar ID3
            </button>
        </div>
        <input id="album-cover-input-${albumIdSafe}" type="file" accept="image/*" class="hidden" data-artist="${selectedArtist.replace(/\"/g, '&quot;')}" data-album="${albumName.replace(/\"/g, '&quot;')}" onchange="uploadAlbumCover(this)">
        ` : ''}
    ${(currentUser.role === 'admin' || currentUser.can_download !== false || currentUser.can_share !== false) ? '</div>' : ''}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right side: Track table list -->
                                <div class="sm:col-span-2">
                                    <div class="overflow-x-auto ${albumTracks.length > 15 ? 'max-h-[480px] overflow-y-auto' : 'max-h-[2000px]'} custom-scroll">
                                        <table class="w-full text-left text-xs text-slate-300">
                                            <thead>
                                                <tr class="border-b border-slate-900/60 text-slate-500 font-mono tracking-wider text-[9px] uppercase">
                                                    <th class="py-2 px-3 w-10 text-center">#</th>
                                                    <th class="py-2 px-3">Faixa</th>
                                                    <th class="py-2 px-3 text-center w-16">Duração</th>
                                                    <th class="py-2 px-3 text-right w-12">Fav</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-900/40">
                    `;
                    
                    albumTracks.forEach((track, index) => {
                        const isFav = allFavorites.includes(String(track.id));
                        const highlight = activeQueue[activeQueueIdx] && String(activeQueue[activeQueueIdx].id) === String(track.id);
                        
                        html += `
                            <tr class="hover:bg-slate-900/30 group/row transition duration-150">
                                <td class="py-2 px-3 text-center font-mono text-slate-600 text-xs w-10 ${highlight ? 'text-sky-450 font-black' : ''}">${index + 1}</td>
                                <td class="py-2 px-3 font-semibold">
                                    <div class="flex items-center gap-2 max-w-full">
                                        <button class="font-bold text-white hover:text-sky-400 text-left truncate hover:underline cursor-pointer ${highlight ? 'text-sky-450' : ''}" onclick="playImmediateFromAlbum('${track.id}')">
                                            ${track.title}
                                        </button>
                                        ${currentUser.role === 'admin' ? `
                                            <button onclick="editTrackTitlePrompt(event, '${track.id}', ${JSON.stringify(track.title).replace(/"/g, '&quot;')})" class="p-1 rounded opacity-0 group-hover/row:opacity-100 hover:bg-slate-800 text-slate-500 hover:text-sky-400 transition cursor-pointer shrink-0" title="Editar nome da música">
                                                <i data-lucide="edit-3" class="w-3 h-3"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]">
                                    ${formatSecs(track.duration || 180)}
                                </td>
                                <td class="py-2 px-3 text-right w-20">
                                    <div class="flex items-center justify-end gap-1">
                                        ${currentUser.role === 'admin' ? `
                                            <button onclick="downloadTrack(event, '${track.id}')" class="p-1 text-slate-500 hover:text-sky-400 hover:bg-slate-900 rounded-lg transition cursor-pointer" title="Fazer Download da Faixa">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            </button>
                                        ` : ''}
                                        <button onclick="toggleFav(event, '${track.id}')" class="p-1 rounded-lg border border-transparent hover:bg-slate-900 transition ${isFav ? 'text-[#f43f5e]' : 'text-slate-500 hover:text-white'} cursor-pointer" title="${isFav ? 'Remover dos Favoritos' : 'Marcar como Favorito'}">
                                            <i data-lucide="heart" class="w-3 h-3 ${isFav ? 'fill-current' : ''}"></i>
                                        </button>
                                        <button onclick="addToPlaylistDropdown(event, '${track.id}')" class="p-1 text-slate-500 hover:text-sky-400 hover:bg-slate-900 rounded-lg transition cursor-pointer" title="Adicionar à Playlist">
                                            <i data-lucide="list-plus" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
                
                artistView.innerHTML = html;
                lucide.createIcons();
                return;
            } else {
                if (gridLayout) gridLayout.classList.remove('hidden');
                tableWrapper.classList.remove('hidden');
                headerBlock.classList.remove('hidden');
                artistView.classList.add('hidden');
                artistView.innerHTML = '';
            }

            // Extract unique artists and albums for left sidebar
            const artistsMapSidebar = {};
            const albumsMapSidebar = {};

            sourceList.forEach(t => {
                const art = t.artist || "Artista Desconhecido";
                const alb = t.album || "Álbum Desconhecido";
                artistsMapSidebar[art] = (artistsMapSidebar[art] || 0) + 1;
                albumsMapSidebar[alb] = (albumsMapSidebar[alb] || 0) + 1;
            });

            const uniqueArs = Object.keys(artistsMapSidebar).sort((a, b) => a.localeCompare(b, 'pt-BR'));
            const uniqueAlbs = Object.keys(albumsMapSidebar).sort((a, b) => a.localeCompare(b, 'pt-BR'));

            // Render Artists in sidebar
            const sidebarArtistsList = document.getElementById('sidebar-artists-list');
            if (sidebarArtistsList) {
                let artistsHtml = `
                    <button onclick="filterTracksBySidebarArtist('')" class="px-2.5 py-1.5 rounded-lg text-left text-xs font-semibold cursor-pointer w-full transition ${!phpSelectedArtist ? 'bg-sky-500/10 text-sky-400 font-extrabold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/40'}">
                        Todos Artistas
                    </button>
                `;
                uniqueArs.forEach(art => {
                    const count = artistsMapSidebar[art];
                    const active = phpSelectedArtist === art;
                    const escapedArt = art.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    artistsHtml += `
                        <div onclick="filterTracksBySidebarArtist('${escapedArt}')" class="group px-2.5 py-1.5 rounded-lg text-left text-xs flex justify-between items-center font-semibold cursor-pointer w-full transition ${active ? 'bg-sky-500/10 text-sky-400 font-extrabold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/40'}">
                            <span class="truncate pr-1 select-none pointer-events-none">${art}</span>
                            <div class="flex items-center gap-1.5 shrink-0 select-none">
                                <span class="text-[9px] text-slate-600 font-mono">(${count})</span>
                                <button onclick="event.stopPropagation(); playSidebarArtist('${escapedArt}')" class="opacity-0 group-hover:opacity-100 p-0.5 text-sky-450 hover:text-sky-300 hover:bg-sky-950/40 rounded transition-all cursor-pointer flex items-center justify-center" title="Tocar Músicas">
                                    <i data-lucide="play" class="w-3 h-3 fill-current text-sky-400"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                sidebarArtistsList.innerHTML = artistsHtml;
            }

            // Render Albums in sidebar
            const sidebarAlbumsList = document.getElementById('sidebar-albums-list');
            if (sidebarAlbumsList) {
                let albumsHtml = `
                    <button onclick="filterTracksBySidebarAlbum('')" class="px-2.5 py-1.5 rounded-lg text-left text-xs font-semibold cursor-pointer w-full transition ${!phpSelectedAlbum ? 'bg-violet-500/10 text-violet-400 font-extrabold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/40'}">
                        Todos Álbuns
                    </button>
                `;
                uniqueAlbs.forEach(alb => {
                    const count = albumsMapSidebar[alb];
                    const active = phpSelectedAlbum === alb;
                    const escapedAlb = alb.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    albumsHtml += `
                        <div onclick="filterTracksBySidebarAlbum('${escapedAlb}')" class="group px-2.5 py-1.5 rounded-lg text-left text-xs flex justify-between items-center font-semibold cursor-pointer w-full transition ${active ? 'bg-violet-500/10 text-violet-400 font-bold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/40'}">
                            <span class="truncate pr-1 select-none pointer-events-none">${alb}</span>
                            <div class="flex items-center gap-1.5 shrink-0 select-none">
                                <span class="text-[9px] text-slate-600 font-mono">(${count})</span>
                                <button onclick="event.stopPropagation(); playSidebarAlbum('${escapedAlb}')" class="opacity-0 group-hover:opacity-100 p-0.5 text-violet-400 hover:text-violet-300 hover:bg-violet-950/40 rounded transition-all cursor-pointer flex items-center justify-center" title="Tocar Álbum">
                                    <i data-lucide="play" class="w-3 h-3 fill-current text-violet-400"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                sidebarAlbumsList.innerHTML = albumsHtml;
            }

            if (searchVal !== lastSearchQuery) {
                phpCurrentPage = 1;
                lastSearchQuery = searchVal;
            }

            filteredTracks = sourceList.filter(track => {
                const matchesSearch = track.title.toLowerCase().includes(searchVal) ||
                                       track.artist.toLowerCase().includes(searchVal) ||
                                       (track.album && track.album.toLowerCase().includes(searchVal)) ||
                                       (track.genre && track.genre.toLowerCase().includes(searchVal));

                const matchesArtist = phpSelectedArtist ? (track.artist === phpSelectedArtist) : true;
                const matchesAlbum = phpSelectedAlbum ? (track.album === phpSelectedAlbum) : true;

                return matchesSearch && matchesArtist && matchesAlbum;
            });

            if (phpSortField) {
                filteredTracks.sort((a, b) => {
                    let aVal = a[phpSortField] || '';
                    let bVal = b[phpSortField] || '';
                    if (typeof aVal === 'string') {
                        return phpSortOrder === 'asc' 
                            ? aVal.localeCompare(bVal, 'pt-BR') 
                            : bVal.localeCompare(aVal, 'pt-BR');
                    } else {
                        return phpSortOrder === 'asc' ? aVal - bVal : bVal - aVal;
                    }
                });
            }

            // Update DOM header icons for PHP sort indicators
            const fields = ['title', 'artist', 'album', 'genre', 'duration'];
            fields.forEach(f => {
                const iconEl = document.getElementById('sort-icon-' + f);
                if (iconEl) {
                    if (phpSortField === f) {
                        iconEl.innerHTML = phpSortOrder === 'asc' ? ' &uarr;' : ' &darr;';
                        iconEl.className = 'text-sky-450 font-extrabold';
                    } else {
                        iconEl.innerHTML = ' &updownarrow;';
                        iconEl.className = 'text-slate-650 opacity-40';
                    }
                }
            });

            const totalItems = filteredTracks.length;
            const totalPages = Math.ceil(totalItems / phpPageSize) || 1;
            if (phpCurrentPage > totalPages) {
                phpCurrentPage = totalPages;
            }
            if (phpCurrentPage < 1) {
                phpCurrentPage = 1;
            }

            const paginated = filteredTracks.slice((phpCurrentPage - 1) * phpPageSize, phpCurrentPage * phpPageSize);

            document.getElementById('table-view-count').textContent = totalItems + " faixas encontradas";

            const pagWrapper = document.getElementById('tracks-pagination-wrapper');
            if (totalPages <= 1) {
                if (pagWrapper) pagWrapper.innerHTML = '';
            } else {
                if (pagWrapper) {
                    pagWrapper.innerHTML = `
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-5 py-3.5 bg-[#0a0f1d] border-t border-slate-900 text-xs text-slate-100">
                            <div class="text-slate-505 font-medium">
                                Mostrando <span class="text-slate-350 font-bold">${(phpCurrentPage - 1) * phpPageSize + 1}</span> a 
                                <span class="text-slate-350 font-bold">${Math.min(phpCurrentPage * phpPageSize, totalItems)}</span> de 
                                <span class="text-slate-350 font-bold">${totalItems}</span> músicas
                            </div>
                            <div class="flex items-center gap-2">
                                <button ${phpCurrentPage === 1 ? 'disabled style="opacity: 0.3; cursor: not-allowed;"' : ''} onclick="changePhpPage(${phpCurrentPage - 1})" class="p-1 px-2.5 rounded bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition cursor-pointer">&larr; Anterior</button>
                                <span class="text-slate-450 font-bold px-2">Página ${phpCurrentPage} de ${totalPages}</span>
                                <button ${phpCurrentPage === totalPages ? 'disabled style="opacity: 0.3; cursor: not-allowed;"' : ''} onclick="changePhpPage(${phpCurrentPage + 1})" class="p-1 px-2.5 rounded bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition cursor-pointer">Próxima &rarr;</button>
                            </div>
                        </div>
                    `;
                }
            }

            if (paginated.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="py-12 text-center text-slate-500 italic">Essa coleção está vazia.</td></tr>`;
                return;
            }

            paginated.forEach((track, idx) => {
                const isFav = allFavorites.includes(String(track.id));
                const highlight = activeQueue[activeQueueIdx] && String(activeQueue[activeQueueIdx].id) === String(track.id);
                
                const tr = document.createElement('tr');
                tr.className = highlight 
                    ? "bg-sky-500/5 hover:bg-sky-550/10 transition border-b border-slate-900 group"
                    : "hover:bg-slate-900/30 transition border-b border-slate-900 group";
                
                const displayIndex = (phpCurrentPage - 1) * phpPageSize + idx + 1;

                tr.innerHTML = `
                    <td class="py-2.5 px-4 text-center font-mono ${highlight ? 'text-sky-450 font-black' : 'text-slate-650'}">${displayIndex}</td>
                    <td class="py-2.5 px-4 font-semibold text-white">
                        <div class="flex items-center gap-3">
                            <img src="${track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=80'}" class="w-8 h-8 rounded-lg object-cover bg-slate-900 border border-slate-800 shrink-0" referrerpolicy="no-referrer">
                            <span class="${highlight ? 'text-sky-450 font-bold' : ''} truncate block max-w-[180px] cursor-pointer" onclick="playImmediate('${track.id}')">${track.title}</span>
                        </div>
                    </td>
                    <td class="py-2.5 px-4 text-slate-400 truncate max-w-[120px]">${track.artist}</td>
                    <td class="py-2.5 px-4 text-slate-400 truncate max-w-[120px]">${track.album || 'Single'}</td>
                    <td class="py-2.5 px-4 text-slate-550 hidden sm:table-cell truncate max-w-[110px]">${track.genre || 'Nenhum Gênero'}</td>
                    <td class="py-2.5 px-4 text-center text-slate-450 font-mono hidden sm:table-cell">${formatSecs(track.duration || 180)}</td>
                    <td class="py-2.5 px-4 text-right">
                        <div class="flex items-center justify-end gap-1 select-none">
                            <button onclick="toggleFav(event, '\${track.id}')" class="p-1.5 rounded-lg border border-transparent hover:bg-slate-900/60 transition ${isFav ? 'text-[#f43f5e]' : 'text-slate-500 hover:text-white'} cursor-pointer" title="${isFav ? 'Remover dos Favoritos' : 'Marcar como Favorito'}">
                                <i data-lucide="heart" class="w-3.5 h-3.5 ${isFav ? 'fill-current' : ''}"></i>
                            </button>
                            <button onclick="addToPlaylistDropdown(event, '\${track.id}')" class="p-1.5 text-slate-500 hover:text-sky-400 hover:bg-slate-900/60 rounded-lg transition cursor-pointer" title="Adicionar à Playlist">
                                <i data-lucide="list-plus" class="w-3.5 h-3.5"></i>
                            </button>
                            ${currentUser.role === 'admin' ? `
                                <button onclick="downloadTrack(event, '\${track.id}')" class="p-1.5 text-slate-500 hover:text-emerald-500 hover:bg-slate-900/60 rounded-lg transition cursor-pointer" title="Baixar MP3"><i data-lucide="download" class="w-3.5 h-3.5"></i></button>
                                <button onclick="deleteSong(event, '\${track.id}')" class="p-1.5 text-slate-500 hover:text-red-500 hover:bg-slate-900/60 rounded-lg transition cursor-pointer" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                            ` : ''}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            lucide.createIcons();
        }

        window.sortTracksPhp = function(field) {
            if (phpSortField === field) {
                phpSortOrder = phpSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                phpSortField = field;
                phpSortOrder = 'asc';
            }
            phpCurrentPage = 1;
            renderTracksTable();
        };

        window.filterTracksBySidebarArtist = function(artistName) {
            phpSelectedArtist = artistName;
            phpSelectedAlbum = '';
            phpCurrentPage = 1;
            const resetBtn = document.getElementById('reset-filter-pills');
            if (resetBtn) {
                if (phpSelectedArtist || phpSelectedAlbum) {
                    resetBtn.classList.remove('hidden');
                } else {
                    resetBtn.classList.add('hidden');
                }
            }
            renderTracksTable();
        };

        window.filterTracksBySidebarAlbum = function(albumName) {
            phpSelectedAlbum = albumName;
            phpSelectedArtist = '';
            phpCurrentPage = 1;
            const resetBtn = document.getElementById('reset-filter-pills');
            if (resetBtn) {
                if (phpSelectedArtist || phpSelectedAlbum) {
                    resetBtn.classList.remove('hidden');
                } else {
                    resetBtn.classList.add('hidden');
                }
            }
            renderTracksTable();
        };

        window.clearSidebarFilters = function() {
            phpSelectedArtist = '';
            phpSelectedAlbum = '';
            phpCurrentPage = 1;
            const resetBtn = document.getElementById('reset-filter-pills');
            if (resetBtn) resetBtn.classList.add('hidden');
            renderTracksTable();
        };

        window.playSidebarArtist = function(artistName) {
            if (!allTracks || allTracks.length === 0) return;
            const artistTracks = allTracks.filter(t => {
                const art = t.artist || "Artista Desconhecido";
                return art === artistName;
            });
            if (artistTracks.length > 0) {
                activeQueue = artistTracks;
                activeQueueIdx = 0;
                loadTrack(activeQueue[0]);
            }
        };

        window.playSidebarAlbum = function(albumName) {
            if (!allTracks || allTracks.length === 0) return;
            const albumTracks = allTracks.filter(t => {
                const alb = t.album || "Álbum Desconhecido";
                return alb === albumName;
            });
            if (albumTracks.length > 0) {
                activeQueue = albumTracks;
                activeQueueIdx = 0;
                loadTrack(activeQueue[0]);
            }
        };

        window.changePhpPage = function(page) {
            phpCurrentPage = page;
            renderTracksTable();
        };

        function playImmediate(trackOrId) {
            let track = trackOrId;
            if (typeof trackOrId === 'string' || typeof trackOrId === 'number') {
                track = allTracks.find(t => String(t.id) === String(trackOrId));
            }
            if (!track) return;
            activeQueue = filteredTracks;
            activeQueueIdx = activeQueue.findIndex(t => String(t.id) === String(track.id));
            if (activeQueueIdx === -1) {
                activeQueue = [track];
                activeQueueIdx = 0;
            }
            loadTrack(track);
        }

        function playImmediateFromAlbum(trackId) {
            const track = allTracks.find(t => String(t.id) === String(trackId));
            if (!track) return;
            const queue = allTracks.filter(t => (t.album || 'Single') === (track.album || 'Single'));
            activeQueue = queue;
            activeQueueIdx = activeQueue.findIndex(t => String(t.id) === String(track.id));
            if (activeQueueIdx === -1) {
                activeQueue = [track];
                activeQueueIdx = 0;
            }
            loadTrack(track);
        }

        async function editTrackTitlePrompt(event, trackId) {
            if (event) event.stopPropagation();
            const track = allTracks.find(t => String(t.id) === String(trackId));
            if (!track) return;
            const currentTitle = track.title || '';
            const newTitle = prompt("Editar nome da música:", currentTitle);
            if (!newTitle || newTitle.trim() === "" || newTitle === currentTitle) return;

            try {
                const res = await fetch(API + '?route=update_track_title', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: trackId, title: newTitle.trim() })
                });
                const d = await res.json();
                if (d.success) {
                    await loadData();
                    renderTracksTable();
                } else {
                    alert(d.error || "Erro ao salvar novo título");
                }
            } catch (err) {
                console.error(err);
                alert("Erro ao conectar e salvar novo título.");
            }
        }

        window.playAlbumByName = function(e, albumName) {
            if (e) e.stopPropagation();
            const tracks = allTracks.filter(t => (t.album || 'Single') === albumName);
            playAlbumQueue(e, tracks);
        };

        window.playAlbumByNameShuffled = function(e, albumName) {
            if (e) e.stopPropagation();
            const tracks = allTracks.filter(t => (t.album || 'Single') === albumName);
            playAlbumQueueShuffled(e, tracks);
        };

        window.playArtistByName = function(e, artistName) {
            if (e) e.stopPropagation();
            const tracks = allTracks.filter(t => t.artist === artistName);
            playAlbumQueue(e, tracks);
        };

        window.playArtistByNameShuffled = function(e, artistName) {
            if (e) e.stopPropagation();
            const tracks = allTracks.filter(t => t.artist === artistName);
            playAlbumQueueShuffled(e, tracks);
        };

        async function uploadAlbumCover(input) {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];
            const artist = input.getAttribute('data-artist');
            const album = input.getAttribute('data-album');
            
            // Show loading state under the button
            const btn = input.previousElementSibling;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-3 h-3 animate-spin text-sky-400 inline-block mr-1"></i> Carregando...';
            lucide.createIcons();
            
            const formData = new FormData();
            formData.append('cover', file);
            formData.append('artist', artist);
            formData.append('album', album);
            
            try {
                const res = await fetch(API + '?route=upload_album_cover', {
                    method: 'POST',
                    body: formData
                });
                const d = await res.json();
                if (d.success) {
                    alert('Capa do álbum atualizada com sucesso!');
                    await loadData();
                    renderLeftSidebar();
                    renderTracksTable();
                } else {
                    alert(d.error || 'Erro ao enviar capa.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao enviar capa de imagem.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
                input.value = '';
            }
        }

        window.toggleBioExpanded = function() {
            isBioExpanded = !isBioExpanded;
            renderTracksTable();
        };

        window.playAllFavorites = function() {
            const favTracks = allTracks.filter(t => allFavorites.includes(String(t.id)));
            if (favTracks.length === 0) return;
            activeQueue = favTracks;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        };

        window.shuffleAllFavorites = function() {
            const favTracks = allTracks.filter(t => allFavorites.includes(String(t.id)));
            if (favTracks.length === 0) return;
            const shuffled = [...favTracks].sort(() => Math.random() - 0.5);
            activeQueue = shuffled;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        };

        window.playAllPlaylist = function() {
            if (!selectedPlaylistId) return;
            const playlist = allPlaylists.find(pl => String(pl.id) === String(selectedPlaylistId));
            if (!playlist) return;
            const playlistTracks = allTracks.filter(t => playlist.trackIds.includes(String(t.id)));
            if (playlistTracks.length === 0) return;
            activeQueue = playlistTracks;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        };

        window.shuffleAllPlaylist = function() {
            if (!selectedPlaylistId) return;
            const playlist = allPlaylists.find(pl => String(pl.id) === String(selectedPlaylistId));
            if (!playlist) return;
            const playlistTracks = allTracks.filter(t => playlist.trackIds.includes(String(t.id)));
            if (playlistTracks.length === 0) return;
            const shuffled = [...playlistTracks].sort(() => Math.random() - 0.5);
            activeQueue = shuffled;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        };

        window.triggerArtistBannerUpload = function() {
            document.getElementById('artist-banner-input').click();
        };

        window.uploadArtistBanner = async function(input) {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('banner', file);
            formData.append('artist', selectedArtist);

            try {
                const res = await fetch(API + '?route=upload_artist_banner', {
                    method: 'POST',
                    body: formData
                });
                const d = await res.json();
                if (d.success && d.artist_photo) {
                    artistPhotoUrl = d.artist_photo;
                    renderTracksTable();
                } else {
                    alert(d.error || 'Erro ao enviar banner do artista.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao enviar banner do artista.');
            } finally {
                input.value = '';
            }
        };

        let imageSearchTargetType = 'album'; // 'artist' | 'album'
        let imageSearchAlbumTitle = '';

        window.handleOpenArtistImageSearch = function() {
            imageSearchTargetType = 'artist';
            imageSearchAlbumTitle = '';
            
            const query = selectedArtist + " logo";
            document.getElementById('image-search-query').value = query;
            document.getElementById('image-search-modal-sub').textContent = selectedArtist;
            
            openImageSearchModal(query);
        };

        window.handleOpenAlbumImageSearch = function(albumTitle) {
            imageSearchTargetType = 'album';
            imageSearchAlbumTitle = albumTitle;
            
            const query = selectedArtist + " " + albumTitle;
            document.getElementById('image-search-query').value = query;
            document.getElementById('image-search-modal-sub').textContent = selectedArtist + " — " + albumTitle;
            
            openImageSearchModal(query);
        };

        window.openImageSearchModal = function(query) {
            const tabs = document.getElementById('image-search-tabs-container');
            if (tabs) {
                tabs.classList.add('hidden');
            }
            const modalTitle = document.querySelector('#image-search-modal h3');
            if (modalTitle) {
                modalTitle.textContent = imageSearchTargetType === 'artist' 
                    ? 'Buscar Logo / Foto da Banda' 
                    : 'Buscar Capa do Álbum On-line';
            }
            document.getElementById('image-search-modal').classList.remove('hidden');
            executeImageSearch(query);
        };

        window.setImageSearchSource = function(src) {
            const sourceInput = document.getElementById('image-search-source');
            if (sourceInput) {
                sourceInput.value = src;
            }
            ['google', 'deezer', 'lastfm'].forEach(s => {
                const button = document.getElementById('src-tab-' + s);
                if (button) {
                    if (s === src) {
                        button.className = "flex-1 text-center py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition bg-slate-900 text-white shadow-sm border border-slate-800/30";
                    } else {
                        button.className = "flex-1 text-center py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition text-slate-400 hover:text-white font-black";
                    }
                }
            });
            executeImageSearch();
        };

        window.closeImageSearchModal = function() {
            document.getElementById('image-search-modal').classList.add('hidden');
        };

        window.executeImageSearch = async function(customQuery) {
            const query = customQuery || document.getElementById('image-search-query').value.trim();
            if (!query) return;
            
            const container = document.getElementById('image-search-results-container');
            container.innerHTML = `
                <div class="h-48 flex flex-col items-center justify-center gap-2.5">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin text-sky-500"></i>
                    <p class="text-[11px] font-mono text-slate-500 uppercase tracking-widest animate-pulse">
                        Varrendo o banco de imagens público...
                    </p>
                </div>
            `;
            lucide.createIcons();
            
            try {
                let url = '';
                if (imageSearchTargetType === 'artist') {
                    const src = document.getElementById('image-search-source')?.value || 'google';
                    url = API + '?route=search_artist_logo&artist=' + encodeURIComponent(query) + '&source=' + src;
                } else {
                    const src = document.getElementById('image-search-source')?.value || 'google';
                    url = API + '?route=search_images&q=' + encodeURIComponent(query) + '&source=' + src + '&artist=' + encodeURIComponent(selectedArtist || '') + '&album=' + encodeURIComponent(imageSearchAlbumTitle || '');
                }
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.success && data.images && data.images.length > 0) {
                    let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">';
                    data.images.forEach(imgUrl => {
                        html += `
                            <div onclick="selectScrapedImage('${imgUrl.split(String.fromCharCode(39)).join(String.fromCharCode(92, 39))}')" class="group aspect-square bg-slate-900 border border-slate-850 rounded-xl overflow-hidden cursor-pointer relative hover:border-sky-500/70 transition duration-150 shadow-sm">
                                <img src="${imgUrl}" alt="Scraped Result" class="w-full h-full object-cover group-hover:scale-103 duration-300 transition" referrerpolicy="no-referrer" onerror="this.style.display='none'">
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition duration-150 flex flex-col items-center justify-center p-2 text-center">
                                    <span class="text-[10px] font-black tracking-widest text-[#ffffff] uppercase leading-none bg-sky-500 px-1.5 py-0.5 rounded-sm">
                                        Selecionar
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="h-48 flex flex-col items-center justify-center gap-1.5 text-center p-4">
                            <i data-lucide="image" class="w-7 h-7 text-slate-600"></i>
                            <p class="text-xs text-slate-400 font-bold">Nenhuma imagem encontrada</p>
                            <p class="text-[10px] text-slate-500 max-w-sm">Tente digitar palavras-chave diferentes na caixa de busca acima para refinar a busca.</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            } catch (err) {
                console.error(err);
                container.innerHTML = `
                    <div class="h-48 flex flex-col items-center justify-center gap-1.5 text-center p-4 text-red-400">
                        <p class="text-xs font-bold">Erro operacional ao carregar imagens</p>
                    </div>
                `;
            }
        };

        window.selectScrapedImage = async function(url) {
            try {
                if (imageSearchTargetType === 'artist') {
                    const res = await fetch(API + '?route=update_artist_banner_url', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            artist: selectedArtist,
                            artist_photo: url
                        })
                    });
                    if (res.ok) {
                        artistPhotoUrl = url;
                        closeImageSearchModal();
                        renderTracksTable();
                    } else {
                        alert('Falha ao salvar banner do artista no servidor PHP');
                    }
                } else {
                    const res = await fetch(API + '?route=update_album_cover_url', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            artist: selectedArtist,
                            album: imageSearchAlbumTitle,
                            cover_url: url
                        })
                    });
                    if (res.ok) {
                        loadedCoversCache[imageSearchAlbumTitle] = url;
                        closeImageSearchModal();
                        renderTracksTable();
                    } else {
                        alert('Falha ao salvar capa do álbum no servidor PHP');
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Erro na comunicação com o servidor ao selecionar imagem.');
            }
        };

        window.loadDlnaSettingForUI = async function() {
            try {
                const res = await fetch(API + '?route=dlna_status');
                if (res.ok) {
                    const data = await res.json();
                    const toggle = document.getElementById('dlna-enabled-toggle');
                    if (toggle) {
                        toggle.checked = !!data.enabled;
                    }
                    const indicator = document.getElementById('dlna-status-indicator');
                    const extraDevices = document.getElementById('dlna-devices-expanded');
                    if (indicator) {
                        if (data.enabled) {
                            indicator.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 block animate-ping"></span> <span class="text-emerald-400 font-bold">ONLINE</span>';
                            if (extraDevices) extraDevices.classList.remove('hidden');
                        } else {
                            indicator.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-600 block"></span> OFFLINE';
                            if (extraDevices) extraDevices.classList.add('hidden');
                        }
                    }
                }
            } catch (err) {
                console.error("Erro ao obter status DLNA:", err);
            }
        };

        window.toggleDlnaSetting = async function(checkbox) {
            const isChecked = checkbox.checked;
            try {
                const res = await fetch(API + '?route=toggle_dlna', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enabled: isChecked ? '1' : '0' })
                });
                if (res.ok) {
                    if (window.loadDlnaSettingForUI) await window.loadDlnaSettingForUI();
                } else {
                    alert("Erro ao alterar configuração DLNA.");
                    checkbox.checked = !isChecked;
                }
            } catch (err) {
                console.error(err);
                alert("Erro operacional ao atualizar DLNA.");
                checkbox.checked = !isChecked;
            }
        };

        window.clearCurrentQueue = function() {
            activeQueue = [];
            activeQueueIdx = -1;
            audio.pause();
            audio.src = '';
            isPlaying = false;
            document.getElementById('player-play-btn').innerHTML = '<i data-lucide="play" class="w-4 h-4 fill-current"></i>';
            document.getElementById('track-title').textContent = 'Nenhuma música';
            document.getElementById('track-artist').textContent = 'Selecione para ouvir';
            document.getElementById('track-cover').src = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100';
            renderPlayerMiniQueue();
            renderTracksTable();
            lucide.createIcons();
        };

        window.renderPlaylistsGrid = function() {
            const gridEl = document.getElementById('playlists-grid');
            if (!gridEl) return;
            const emptyEl = document.getElementById('playlists-empty');
            
            if (!allPlaylists || allPlaylists.length === 0) {
                if (emptyEl) emptyEl.classList.remove('hidden');
                gridEl.innerHTML = '';
                return;
            }
            if (emptyEl) emptyEl.classList.add('hidden');
            
            gridEl.innerHTML = '';
            allPlaylists.forEach(pl => {
                const size = pl.trackIds ? pl.trackIds.length : 0;
                const safeName = (pl.name || '').replace(new RegExp("'", "g"), "\'");
                const card = document.createElement('div');
                card.className = "group bg-slate-950/60 border border-slate-900 rounded-3xl p-5 hover:border-emerald-500/35 transition-all flex flex-col justify-between space-y-4 hover:shadow-lg hover:shadow-emerald-500/5 duration-200 animate-fade-in";
                
                card.innerHTML = `
                    <div class="space-y-2 text-left">
                        <div class="flex items-center justify-between">
                            <div class="p-3 bg-gradient-to-tr from-sky-500/10 to-indigo-500/10 text-emerald-400 rounded-2xl border border-slate-900/40">
                                <i data-lucide="list-music" class="w-6 h-6"></i>
                            </div>
                            <span class="text-[10px] text-slate-500 font-mono font-bold uppercase tracking-wider">${size} música(s)</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-[15px] truncate">${pl.name}</h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Criado por ${pl.username || 'Sistema'}</p>
                        </div>
                    </div>
                    
                    <div class="pt-3 border-t border-slate-900/60 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="playPlaylistTracks('${pl.id}', false)" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-650 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition active:scale-95 flex items-center justify-center gap-1 cursor-pointer">
                                <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i> Rodar Tudo
                            </button>
                            <button onclick="playPlaylistTracks('${pl.id}', true)" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition active:scale-95 flex items-center justify-center gap-1 cursor-pointer">
                                <i data-lucide="shuffle" class="w-3.5 h-3.5"></i> Aleatório
                            </button>
                        </div>
                        
                        <div class="flex gap-2">
                            <button onclick="viewPlaylistTracks('${pl.id}', '${safeName}')" class="flex-1 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-350 hover:text-white rounded-xl text-[10px] font-bold uppercase transition flex items-center justify-center gap-1 border border-slate-800 cursor-pointer">
                                <i data-lucide="eye" class="w-3" style="width:12px; height:12px;"></i> Músicas
                            </button>
                            ${(currentUser.role === 'admin' || currentUser.can_share !== false) ? `
                            <button onclick="sharePlaylist('${pl.id}', '${safeName}')" class="px-2.5 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 rounded-xl text-[10px] font-bold uppercase tracking-wider transition flex items-center justify-center cursor-pointer" title="Compartilhar Playlist">
                                <i data-lucide="share-2" style="width:14px; height:14px;"></i>
                            </button>
                            ` : ''}
                            <button onclick="deletePlaylistAndRefresh('${pl.id}')" class="px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-xl text-[10px] font-bold uppercase tracking-wider transition flex items-center justify-center cursor-pointer">
                                <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                gridEl.appendChild(card);
            });
            lucide.createIcons();
        };

        window.viewPlaylistTracks = function(playlistId, playlistName) {
            if (isPartyMode) {
                alert("O Modo Festa está ativo! A navegação está bloqueada para manter a diversão focada no player.");
                return;
            }
            selectedPlaylistId = playlistId;
            selectedArtist = '';
            activePlaylistAlbum = '';
            document.getElementById('table-view-title').textContent = "Playlist: " + playlistName;
            
            const btns = ['dashboard', 'tracks', 'favorites', 'config', 'videos', 'playlists', 'podcast', 'radios'];
            btns.forEach(b => {
                const el = document.getElementById('tab-btn-' + b);
                if (el) el.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 transition";
            });
            const activeBtn = document.getElementById('tab-btn-playlists');
            if (activeBtn) {
                activeBtn.className = "w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20";
            }
            
            renderLeftSidebar();
            renderTracksTable();
            activeTab = 'tracks';
            document.getElementById('pane-dashboard').classList.add('hidden');
            if (document.getElementById('pane-playlists')) document.getElementById('pane-playlists').classList.add('hidden');
            document.getElementById('pane-tracks').classList.remove('hidden');
            lucide.createIcons();
        };

        window.deletePlaylistAndRefresh = async function(playlistId) {
            if (!confirm("Tem certeza que deseja remover esta playlist?")) return;
            try {
                const res = await fetch(API + '?route=delete_playlist&id=' + playlistId, {
                    method: 'POST'
                });
                if (res.ok) {
                    await loadData();
                    window.renderPlaylistsGrid();
                    renderLeftSidebar();
                } else {
                    alert("Erro ao remover playlist.");
                }
            } catch (err) {
                console.error(err);
                alert("Erro de rede ao remover playlist.");
            }
        };

        window.playPlaylistTracks = function(playlistId, isShuffle) {
            const pl = allPlaylists.find(p => String(p.id) === String(playlistId));
            if (!pl || !pl.trackIds || pl.trackIds.length === 0) {
                alert("Esta playlist está vazia.");
                return;
            }
            const tracks = pl.trackIds.map(tid => allTracks.find(t => String(t.id) === String(tid))).filter(Boolean);
            if (tracks.length === 0) {
                alert("Nenhuma música desta playlist foi encontrada no servidor.");
                return;
            }
            if (isShuffle) {
                const shuffled = [...tracks].sort(() => Math.random() - 0.5);
                activeQueue = shuffled;
            } else {
                activeQueue = tracks;
            }
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
        };

        window.loadLastfmKeyForUI = async function() {
            try {
                const res = await fetch(API + '?route=get_settings');
                if (res.ok) {
                    const data = await res.json();
                    if (data.settings && data.settings.lastfm_api_key) {
                        const input = document.getElementById('lastfm-api-key-input');
                        if (input) {
                            input.value = data.settings.lastfm_api_key;
                        }
                    }
                }
                if (window.loadDlnaSettingForUI) await window.loadDlnaSettingForUI();
            } catch (err) {
                console.error("Erro ao carregar configurações do Last.fm:", err);
            }
        };

        window.saveLastfmApiKey = async function() {
            const input = document.getElementById('lastfm-api-key-input');
            if (!input) return;
            const value = input.value.trim();
            const btn = document.getElementById('save-lastfm-key-btn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Gravando...';
            lucide.createIcons();
            
            try {
                const res = await fetch(API + '?route=save_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        key: 'lastfm_api_key',
                        setting_key: 'lastfm_api_key',
                        value: value,
                        setting_value: value
                    })
                });
                if (res.ok) {
                    alert('Chave API do Last.fm salva com sucesso!');
                } else {
                    alert('Erro ao salvar Chave API do Last.fm.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro operacional ao salvar chave.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        };

        async function triggerScan() {
            const btn = document.querySelector('[onclick="triggerScan()"]');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Sincronizando...';
            lucide.createIcons();
            try {
                const res = await fetch(API + '?route=scan');
                const d = await res.json();
                alert("Scan terminado! " + d.count + " novas faixas agregadas e " + (d.removed || 0) + " faixas de música órfãs removidas sob /music.");
                await loadData();
                renderDashboard();
            } catch (error) {
                alert("Falha operacional ao ler disco.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="scan" class="w-3.5 h-3.5"></i> Sincronizar Pasta /music';
                lucide.createIcons();
            }
        }

        function updatePlayerFavHeart() {
            const btn = document.getElementById('player-favorite-heart-btn');
            const repFavBtn = document.getElementById('reprodutor-fav-btn');
            
            const track = window.phpCurrentTrack;
            if (!track) {
                if (btn) btn.classList.add('hidden');
                if (repFavBtn) repFavBtn.classList.add('hidden');
                return;
            }
            
            if (btn) btn.classList.remove('hidden');
            if (repFavBtn) repFavBtn.classList.remove('hidden');
            
            const isFav = allFavorites.includes(String(track.id));
            if (isFav) {
                if (btn) {
                    btn.className = "text-[#f43f5e] hover:text-rose-600 transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0";
                    btn.innerHTML = `<i data-lucide="heart" class="w-4 h-4 fill-current"></i>`;
                    btn.title = "Remover dos Favoritos";
                }
                if (repFavBtn) {
                    repFavBtn.className = "p-2 bg-rose-500/10 hover:bg-rose-550/20 rounded-xl transition cursor-pointer border border-rose-500/20 text-[#f43f5e]";
                    repFavBtn.innerHTML = `<i data-lucide="heart" class="w-4 h-4 fill-current"></i>`;
                    repFavBtn.title = "Remover dos Favoritos";
                }
            } else {
                if (btn) {
                    btn.className = "text-slate-500 hover:text-white transition cursor-pointer p-1.5 rounded-lg hover:bg-slate-900 shrink-0";
                    btn.innerHTML = `<i data-lucide="heart" class="w-4 h-4"></i>`;
                    btn.title = "Adicionar aos Favoritos";
                }
                if (repFavBtn) {
                    repFavBtn.className = "p-2 bg-slate-900 hover:bg-slate-850 rounded-xl transition cursor-pointer border border-slate-850 text-slate-400 hover:text-white";
                    repFavBtn.innerHTML = `<i data-lucide="heart" class="w-4 h-4"></i>`;
                    repFavBtn.title = "Adicionar aos Favoritos";
                }
            }
            lucide.createIcons();
        }

        async function togglePlayerCurrentFav(e) {
            if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
            const track = window.phpCurrentTrack;
            if (!track) return;
            await toggleFav(e, track.id);
        }

        async function toggleFav(e, trackId) {
            if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
            try {
                const res = await fetch(API + '?route=favorites_toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: currentUser.username, trackId })
                });
                allFavorites = await res.json();
                renderTracksTable();
                updatePlayerFavHeart();
            } catch (err) {
                console.error(err);
            }
        }

        async function deleteSong(e, trackId) {
            e.stopPropagation();
            if (!confirm("Deletar permanentemente do banco?")) return;
            try {
                const res = await fetch(API + '?route=delete_track&id=' + trackId, { method: 'DELETE' });
                await loadData();
                renderTracksTable();
            } catch (err) {
                console.error(err);
            }
        }

        window.downloadTrack = function(e, trackId) {
            if (e) e.stopPropagation();
            if (!currentUser || currentUser.role !== 'admin') {
                alert("Acesso restrito a administradores.");
                return;
            }
            const url = API + '?route=download_track&id=' + trackId + '&admin_username=' + encodeURIComponent(currentUser.username);
            const a = document.createElement('a');
            a.href = url;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        };

        window.downloadAlbum = function(e, albumName, isMobile = false) {
            if (e) e.stopPropagation();
            if (!currentUser || currentUser.role !== 'admin') {
                alert("Acesso restrito a administradores.");
                return;
            }
            let decodedAlbumName = albumName;
            try {
                if (isMobile) {
                    decodedAlbumName = decodeURIComponent(albumName);
                }
            } catch(err) {
                console.error(err);
            }
            const url = API + '?route=download_album&album=' + encodeURIComponent(decodedAlbumName) + '&admin_username=' + encodeURIComponent(currentUser.username);
            const a = document.createElement('a');
            a.href = url;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        };

        async function createPlay() {
            const name = prompt("Nome da nova playlist:");
            if (!name) return;
            try {
                const res = await fetch(API + '?route=playlists', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, username: currentUser.username })
                });
                await loadData();
                renderDashboard();
            } catch (err) {
                console.error(err);
            }
        }

        window.closePlaylistSelectorModal = function() {
            document.getElementById('playlist-selector-modal').classList.add('hidden');
        };

        window.selectPlaylistForTrack = async function(playlistId, trackId) {
            const targetPl = allPlaylists.find(p => String(p.id) === String(playlistId));
            if (!targetPl) {
                alert("Playlist inválida.");
                return;
            }

            let updatedIds = [...targetPl.trackIds];
            const isAdded = !updatedIds.includes(String(trackId));
            if (updatedIds.includes(String(trackId))) {
                updatedIds = updatedIds.filter(id => id !== String(trackId));
            } else {
                updatedIds.push(String(trackId));
            }

            try {
                await fetch(API + '?route=update_playlist&id=' + targetPl.id, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ trackIds: updatedIds })
                });
                await loadData();
                renderDashboard();
                
                // Re-render modal options to show instant visual update
                renderPlaylistSelectorOptions(trackId);
            } catch (err) {
                console.error(err);
            }
        };

        function renderPlaylistSelectorOptions(trackId) {
            const listContainer = document.getElementById('playlist-selector-list');
            if (!listContainer) return;
            listContainer.innerHTML = '';
            
            allPlaylists.forEach(pl => {
                const hasTrack = pl.trackIds.includes(String(trackId));
                const btn = document.createElement('button');
                btn.className = "w-full text-left p-3.5 rounded-2xl border text-xs font-bold flex items-center justify-between transition cursor-pointer select-none " + (
                    hasTrack 
                    ? "bg-sky-500/10 border-sky-500/25 text-sky-400 hover:bg-sky-500/15" 
                    : "bg-slate-900/60 border-slate-900 hover:border-slate-800 text-slate-400 hover:text-white"
                );
                
                btn.onclick = () => selectPlaylistForTrack(pl.id, trackId);
                
                const checkIcon = hasTrack 
                    ? '<i data-lucide="check" class="w-4 h-4 text-sky-400 shrink-0"></i>' 
                    : '<i data-lucide="plus" class="w-4 h-4 text-slate-600 shrink-0"></i>';
                    
                btn.innerHTML = `
                    <div class="flex items-center gap-2.5 truncate max-w-[80%]">
                        <i data-lucide="music-2" class="w-4 h-4 text-slate-500 shrink-0"></i>
                        <span class="truncate">${pl.name}</span>
                        <span class="text-[9px] font-mono text-slate-500 ml-1.5 font-normal">(${pl.trackIds.length})</span>
                    </div>
                    ${checkIcon}
                `;
                listContainer.appendChild(btn);
            });
            lucide.createIcons();
        }

        window.addToPlaylistDropdown = async function(e, trackId) {
            e.stopPropagation();
            if (allPlaylists.length === 0) {
                alert("Crie ao menos uma playlist antes no painel do sidebar.");
                return;
            }
            
            renderPlaylistSelectorOptions(trackId);
            document.getElementById('playlist-selector-modal').classList.remove('hidden');
        };

        window.editUser = function(username, can_dl, can_sh) {
            if (username === 'admin') {
                alert("O admin tem todas permissões ativadas por padrão.");
                return;
            }
            const htmlBlock = `
                <div class="space-y-4">
                    <h3 class="font-bold text-lg text-white">Editar: ${username}</h3>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="edit-can-dl" class="rounded bg-slate-900 border-slate-700 text-sky-500" ${can_dl ? 'checked' : ''}>
                            <span class="text-sm text-slate-300">Pode baixar álbuns</span>
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="edit-can-sh" class="rounded bg-slate-900 border-slate-700 text-sky-500" ${can_sh ? 'checked' : ''}>
                            <span class="text-sm text-slate-300">Pode criar compartilhamentos</span>
                        </label>
                    </div>
                    <button onclick="saveUserRights('${username}')" class="w-full py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-sm font-bold mt-4 shadow cursor-pointer">Salvar Permissões</button>
                </div>
            `;
            window.showModalHTML(htmlBlock);
        }

        window.saveUserRights = async function(username) {
            const can_dl = document.getElementById('edit-can-dl').checked;
            const can_sh = document.getElementById('edit-can-sh').checked;
            try {
                const res = await fetch(API + '?route=update_user&username=' + encodeURIComponent(username), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ can_download: can_dl, can_share: can_sh })
                });
                if (res.ok) {
                    window.closeModalHTML();
                    renderUsersTable();
                } else {
                    alert('Erro ao salvar permissões');
                }
            } catch(e) { }
        };

        window.showModalHTML = function(html) {
            let m = document.getElementById('generic-html-modal');
            if(!m) {
                m = document.createElement('div');
                m.id = 'generic-html-modal';
                m.className = "fixed inset-0 bg-black/60 backdrop-blur-sm z-60 hidden flex items-center justify-center p-4 opacity-0 transition-opacity";
                m.innerHTML = `<div class="bg-slate-950 border border-slate-800 rounded-2xl w-full max-w-sm p-6 relative scale-95 transition-transform" id="generic-html-modal-content">
                    <button onclick="window.closeModalHTML()" class="absolute top-4 right-4 text-slate-500 hover:text-white cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button>
                    <div id="generic-html-modal-body"></div>
                </div>`;
                document.body.appendChild(m);
            }
            document.getElementById('generic-html-modal-body').innerHTML = html;
            m.classList.remove('hidden');
            setTimeout(() => {
                m.classList.remove('opacity-0');
                document.getElementById('generic-html-modal-content').classList.remove('scale-95');
                document.getElementById('generic-html-modal-content').classList.add('scale-100');
                lucide.createIcons();
            }, 10);
        };
        window.closeModalHTML = function() {
            let m = document.getElementById('generic-html-modal');
            if(!m) return;
            m.classList.add('opacity-0');
            document.getElementById('generic-html-modal-content').classList.remove('scale-100');
            document.getElementById('generic-html-modal-content').classList.add('scale-95');
            setTimeout(() => m.classList.add('hidden'), 200);
        }

        // USER MANAGER CONTROLLER (ADMIN EXCLUSIVE)
        async function renderUsersTable() {
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '';
            try {
                const res = await fetch(API + '?route=users');
                const users = await res.json();
                users.forEach(u => {
                    const tr = document.createElement('tr');
                    tr.className = "hover:bg-slate-900/30 transition border-b border-slate-900";
                    tr.innerHTML = `
                        <td class="py-2.5 px-4 text-white font-bold">${u.username}</td>
                        <td class="py-2.5 px-4 uppercase text-[10px]"><span class="px-2 py-0.5 rounded-full font-bold bg-slate-800 text-slate-400">${u.role}</span></td>
                        <td class="py-2.5 px-4 text-right">
                            <button onclick="editUser('${u.username}', ${u.can_download !== false}, ${u.can_share !== false})" class="p-1 hover:text-indigo-400 text-slate-500 transition cursor-pointer" title="Editar Permissões"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                            <button onclick="deleteUser('${u.username}')" class="p-1 hover:text-red-400 text-slate-500 transition cursor-pointer ${u.username === 'admin' ? 'hidden' : ''}" title="Excluir"><i data-lucide="user-x" class="w-4 h-4"></i></button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                lucide.createIcons();
            } catch (error) {
                console.error(error);
            }
        }

        async function handleCreateUser(e) {
            e.preventDefault();
            const username = document.getElementById('new-user-name').value;
            const password = document.getElementById('new-user-pass').value;
            const role = document.getElementById('new-user-role').value;
            
            try {
                const res = await fetch(API + '?route=users', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, role })
                });
                if (res.ok) {
                    alert("Usuário adicionado com sucesso!");
                    document.getElementById('new-user-name').value = '';
                    document.getElementById('new-user-pass').value = '';
                    renderUsersTable();
                } else {
                    const err = await res.json();
                    alert(err.error || "Erro ao criar");
                }
            } catch (error) {
                console.error(error);
            }
        }

        
        async function shareAlbum(album, artist) {
            try {
                const res = await fetch(API + '?route=create_share', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        target_type: 'album',
                        target_id: JSON.stringify({ album, artist }),
                        target_name: album + ' - ' + artist
                    })
                });
                const data = await res.json();
                if (data.success) {
                    const url = window.location.origin + window.location.pathname + '?share=' + data.hash;
                    alert('Compartilhamento criado com sucesso!\nLink: ' + url);
                    navigator.clipboard.writeText(url);
                    if (window.renderSharesTable) window.renderSharesTable();
                } else {
                    alert('Erro ao criar');
                }
            } catch (ee) { console.error(ee); }
        }

        async function sharePlaylist(id, name) {
            try {
                const res = await fetch(API + '?route=create_share', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        target_type: 'playlist',
                        target_id: String(id),
                        target_name: name
                    })
                });
                const data = await res.json();
                if (data.success) {
                    const url = window.location.origin + window.location.pathname + '?share=' + data.hash;
                    alert('Compartilhamento criado com sucesso!\nLink: ' + url);
                    navigator.clipboard.writeText(url);
                    if (window.renderSharesTable) window.renderSharesTable();
                } else {
                    alert('Erro ao criar');
                }
            } catch (ee) { console.error(ee); }
        }

async function renderSharesTable() {
    const tbody = document.getElementById('shares-table-body');
    if(!tbody) return;
    tbody.innerHTML = '';
    try {
        const res = await fetch(API + '?route=list_shares');
        const shares = await res.json();
        shares.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td class="py-2 px-4 font-bold">${s.target_name}</td>
            <td class="py-2 px-4">
                <a href="?share=${s.share_hash}" target="_blank" class="text-sky-400 hover:underline">?share=${s.share_hash}</a>
            </td>
            <td class="py-2 px-4 text-right">
                <button onclick="deleteShare('${s.share_hash}')" class="p-1 hover:text-red-400 text-slate-500 transition"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </td>
            `;
            tbody.appendChild(tr);
        });
        if(window.lucide) lucide.createIcons();
    } catch(e) {
        console.error(e);
    }
}

async function deleteShare(hash) {
    if (!confirm('Remover compartilhamento?')) return;
    try {
        const res = await fetch(API + '?route=delete_share&hash=' + hash);
        if (res.ok) {
            renderSharesTable(); loadDashSettings();
        }
    } catch(r) { console.error(r) }
}

async function loadDashSettings() {
    try {
        const res = await fetch(API + '?route=get_settings');
        if (!res.ok) return;
        const set = await res.json();
        if (set && set.settings) {
            globalSettings = set.settings;
        } else {
            globalSettings = set;
        }
        
        const elLimit = document.getElementById('dashboard-albums-count');
        if(elLimit) elLimit.value = globalSettings['dashboard_albums_count'] || 12;
        
        const elTime = document.getElementById('dashboard-rotate-time');
        if(elTime) elTime.value = (globalSettings['dashboard_rotate_time'] !== undefined) ? globalSettings['dashboard_rotate_time'] : 8;
        
    } catch(e) { console.error(e); }
}

async function saveDashboardSettings() {
    const elLimit = document.getElementById('dashboard-albums-count');
    const elTime = document.getElementById('dashboard-rotate-time');
    if(!elLimit || !elTime) return;
    
    const limitVal = elLimit.value || '12';
    const timeVal = elTime.value || '8';
    
    try {
        await fetch(API + '?route=save_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ setting_key: 'dashboard_albums_count', setting_value: limitVal })
        });
        await fetch(API + '?route=save_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ setting_key: 'dashboard_rotate_time', setting_value: timeVal })
        });
        
        // Update live
        globalSettings['dashboard_albums_count'] = limitVal;
        globalSettings['dashboard_rotate_time'] = timeVal;
        
        updateRandomDashboardAlbums();
        renderAlbumGrid();
        setupDashboardInterval();
        
        alert('Configurações do dashboard salvas com sucesso');
    } catch(e) {
        console.error(e);
    }
}

async function deleteUser(username) {
            if (!confirm("Deletar permanentemente este usuário?")) return;
            try {
                const res = await fetch(API + '?route=users&username=' + encodeURIComponent(username), { method: 'DELETE' });
                if (res.ok) {
                    renderUsersTable();
                }
            } catch (err) {
                console.error(err);
            }
        }

        // PHYSICAL FILE MUSIC UPLOADS (ADMIN EXCLUSIVE)
        async function handleMusicUpload(e) {
            e.preventDefault();
            const btn = document.getElementById('uploader-submit-btn');
            btn.disabled = true;
            btn.textContent = "Processando upload...";
            
            const form = document.getElementById('music-upload-form');
            const formData = new FormData(form);
            
            try {
                const res = await fetch(API + '?route=tracks', {
                    method: 'POST',
                    body: formData
                });
                if (res.ok) {
                    const data = await res.json();
                    alert(data.message || "Upload concluído! Verifique sob a biblioteca.");
                    form.reset();
                    await loadData();
                    setTab('dashboard');
                } else {
                    const err = await res.json();
                    alert(err.error || "Operação falhou");
                }
            } catch (e) {
                alert("Falha de limite de rede.");
            } finally {
                btn.disabled = false;
                btn.textContent = "Iniciar Upload";
            }
        }

        function showErrorModal(details) {
            document.getElementById('error-modal-details').textContent = details;
            document.getElementById('error-modal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeErrorModal() {
            document.getElementById('error-modal').classList.add('hidden');
        }

        // LOGIN ENGINE
        async function handleLoginSubmit(e) {
            e.preventDefault();
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;
            
            try {
                const response = await fetch(API + '?route=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });
                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch(err) {
                    showErrorModal("O servidor PHP não retornou uma resposta JSON válida.\n\nCódigo de Status HTTP: " + response.status + " (" + response.statusText + ")\n\nIsso geralmente ocorre quando o arquivo api.php retornou um erro interno, quando está em outra pasta, ou quando há algum bloqueio de segurança/CORS no servidor.\n\nRetorno bruto do servidor (primeiros 1000 caracteres):\n\n" + (responseText.trim() || "[Fim da resposta - Corpo em branco (verifique se as credenciais do banco de dados no config.php estão 100% corretas ou se o arquivo api.php está na mesma pasta raiz que o index.php)]"));
                    return;
                }
                
                if (response.ok && !data.error) {
                    currentUser = {
                        username: data.username,
                        role: data.role,
                        theme: data.theme || 'default',
                        sidebarBg: data.sidebarBg || '',
                        footerBg: data.footerBg || '',
                        topBg: data.topBg || ''
                    };
                    localStorage.setItem('phplayer_user', JSON.stringify(currentUser));
                    bootPlayer();
                } else {
                    showErrorModal(data.error || "Credenciais inválidas. Verifique se importou o arquivo database.sql com o usuário padrão.");
                }
            } catch (error) {
                showErrorModal("Erro de rede / operacional do servidor: " + error.message);
            }
        }

        function handleLogout() {
            localStorage.removeItem('phplayer_user');
            currentUser = null;
            
            audio.pause();
            isPlaying = false;
            activeQueue = [];
            activeQueueIdx = -1;
            
            document.getElementById('workspace-panel').classList.add('hidden');
            document.getElementById('player-toolbar').classList.add('hidden');
            document.getElementById('login-panel').classList.remove('hidden');
            lucide.createIcons();
        }

        // VIDEO GALLERY ENGINE
        async function renderVideoGallery() {
            const loadingEl = document.getElementById('video-loading');
            const emptyEl = document.getElementById('video-empty');
            const gridEl = document.getElementById('video-grid');
            
            loadingEl.classList.remove('hidden');
            emptyEl.classList.add('hidden');
            gridEl.innerHTML = '';
            
            try {
                const res = await fetch(API + '?route=videos');
                allVideos = await res.json();
                
                loadingEl.classList.add('hidden');
                
                const searchVal = document.getElementById('video-search-input').value.toLowerCase();
                const filtered = allVideos.filter(v => v.title.toLowerCase().includes(searchVal));
                
                if (filtered.length === 0) {
                    emptyEl.classList.remove('hidden');
                    return;
                }
                
                filtered.forEach(vid => {
                    const cell = document.createElement('div');
                    cell.className = "group bg-slate-950/60 border border-slate-900/40 rounded-2xl overflow-hidden hover:border-sky-500/40 cursor-pointer transition flex flex-col justify-between";
                    
                    const coverImg = vid.coverUrl || 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=350';
                    const sizeMB = (vid.fileSize / (1024 * 1024)).toFixed(1);
                    
                    let adminCoverBtn = '';
                    if (currentUser && currentUser.role === 'admin') {
                        adminCoverBtn = `
                            <div class="pt-2 border-t border-slate-900/40 flex items-center">
                                <button onclick="event.stopPropagation(); document.getElementById('upload-video-cover-input-${vid.id}').click();" id="btn-cov-${vid.id}" class="w-full py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg text-[10px] font-bold transition flex items-center justify-center gap-1.5 border border-slate-800">
                                    <i data-lucide="image" class="w-3.5 h-3.5"></i> Alterar Capa
                                </button>
                                <input id="upload-video-cover-input-${vid.id}" type="file" accept="image/*" class="hidden" onchange="uploadVideoCover(this, '${vid.id}')">
                            </div>
                        `;
                    }
                    
                    cell.innerHTML = `
                        <div onclick="playVideo('${vid.id}')" class="relative aspect-video bg-slate-900 overflow-hidden flex items-center justify-center border-b border-slate-905/45 text-slate-700">
                            <img id="vid-cover-img-${vid.id}" src="${coverImg}" class="w-full h-full object-cover group-hover:scale-105 duration-300 transition filter brightness-90 group-hover:brightness-100">
                            <div class="absolute inset-0 bg-black/45 opacity-30 group-hover:opacity-0 transition duration-300"></div>
                            <div class="absolute inset-x-0 bottom-3 left-3 opacity-0 group-hover:opacity-100 transition duration-300 flex">
                                <span class="p-2 bg-sky-500 text-white rounded-xl shadow-lg flex items-center justify-center">
                                    <i data-lucide="play" class="w-4 h-4 fill-white text-white"></i>
                                </span>
                            </div>
                        </div>
                        <div class="p-3.5 space-y-3 text-left">
                            <div onclick="playVideo('${vid.id}')">
                                <h3 class="font-extrabold text-[#ffffff] text-[13px] truncate group-hover:text-sky-400 transition" title="${vid.title}">${vid.title}</h3>
                                <div class="flex justify-between items-center mt-1 text-[10px] text-slate-500 font-mono uppercase tracking-wider">
                                    <span>${sizeMB} MB</span>
                                    <span>Video</span>
                                </div>
                            </div>
                            ${adminCoverBtn}
                        </div>
                    `;
                    
                    gridEl.appendChild(cell);
                });
                lucide.createIcons();
            } catch (err) {
                console.error(err);
                loadingEl.classList.add('hidden');
                emptyEl.classList.remove('hidden');
            }
        }

        function playVideo(id) {
            const vid = allVideos.find(v => v.id === id);
            if (!vid) return;
            
            // stop audio player if playing
            if (isPlaying) {
                audio.pause();
                isPlaying = false;
                document.getElementById('player-play-btn').innerHTML = '<i data-lucide="play" class="w-4 h-4 fill-current"></i>';
                lucide.createIcons();
            }
            
            const player = document.getElementById('modal-video-player');
            player.src = 'api.php?route=stream_video&id=' + encodeURIComponent(vid.id);
            if (vid.coverUrl) {
                player.poster = vid.coverUrl;
            } else {
                player.removeAttribute('poster');
            }
            
            document.getElementById('video-modal-title').textContent = vid.title;
            document.getElementById('video-modal').classList.remove('hidden');
            player.play();
            lucide.createIcons();
        }

        function closeVideoModal() {
            const player = document.getElementById('modal-video-player');
            player.pause();
            player.src = '';
            const modal = document.getElementById('video-modal');
            modal.classList.add('hidden');
            modal.classList.remove('pseudo-fullscreen-active');
            const btn = document.getElementById('video-maximize-btn');
            if (btn) btn.innerHTML = '<i data-lucide="maximize" class="w-4 h-4"></i>';
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function toggleVideoMaximize() {
            const modal = document.getElementById('video-modal');
            const btn = document.getElementById('video-maximize-btn');
            if (modal.classList.contains('pseudo-fullscreen-active')) {
                modal.classList.remove('pseudo-fullscreen-active');
                if (btn) btn.innerHTML = '<i data-lucide="maximize" class="w-4 h-4"></i>';
            } else {
                modal.classList.add('pseudo-fullscreen-active');
                if (btn) btn.innerHTML = '<i data-lucide="minimize" class="w-4 h-4"></i>';
            }
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        async function uploadVideoCover(input, id) {
            const file = input.files[0];
            if (!file) return;
            
            const btn = document.getElementById('btn-cov-' + id);
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin text-sky-450"></i> Carregando...';
            lucide.createIcons();
            
            const formData = new FormData();
            formData.append('cover', file);
            
            try {
                const res = await fetch(API + '?route=videos_upload_cover&id=' + encodeURIComponent(id), {
                    method: 'POST',
                    body: formData
                });
                if (res.ok) {
                    const data = await res.json();
                    document.getElementById('vid-cover-img-' + id).src = data.cover_url;
                    // update local list
                    const vid = allVideos.find(v => v.id === id);
                    if (vid) vid.coverUrl = data.cover_url;
                } else {
                    const err = await res.json();
                    alert(err.error || 'Erro ao carregar capa');
                }
            } catch(e) {
                alert('Falha de rede.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        }

        // ================= PODCASTS & RADIOS =================
        const PODCAST_PRESETS_POOL = [
            { name: 'NerdCast', url: 'https://jnfilter.gabrielgio.me/' },
            { name: 'Inteligência Ltda', url: 'https://anchor.fm/s/6cf4d5a0/podcast/rss' },
            { name: 'Flow', url: 'https://anchor.fm/s/a5637400/podcast/rss' }
        ];

        const RADIO_PRESETS_POOL = [
            { name: 'Kiss FM', url: 'https://www.radios.com.br/play/playlist/26885/listen-radio.m3u' },
            { name: 'Elite Rock', url: 'https://www.radios.com.br/play/playlist/93525/listen-radio.m3u' },
            { name: 'Jovem Pan News', url: 'https://www.radios.com.br/play/playlist/8800/listen-radio.m3u' },
            { name: 'Antena 1', url: 'https://www.radios.com.br/play/playlist/11927/listen-radio.m3u' }
        ];

        function renderPodcastSuggestions() {
            const container = document.getElementById('desktop-podcast-suggestions');
            if (!container) return;
            
            const selected = PODCAST_PRESETS_POOL;
            
            let html = '<span class="text-slate-550 font-bold text-[11px] text-slate-400">Sugestões:</span>';
            selected.forEach(item => {
                html += '<button onclick="setPodcastFeedPreset(\'' + item.url + '\')" class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800/85 px-3 py-1.5 rounded-lg transition text-[11px] cursor-pointer inline-flex items-center gap-1 font-semibold"><i data-lucide="plus" class="w-3 h-3 text-orange-400"></i> ' + item.name + '</button>';
            });
            container.innerHTML = html;
            if (window.lucide) window.lucide.createIcons();
        }

        function renderRadioSuggestions() {
            const container = document.getElementById('desktop-radio-suggestions');
            if (!container) return;
            
            const selected = RADIO_PRESETS_POOL;
            
            let html = '<span class="font-bold text-[11px] text-slate-400">Sugestões:</span>';
            selected.forEach(item => {
                html += '<button type="button" onclick="setRadioPreset(\'' + item.name + '\', \'' + item.url + '\')" class="bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800/80 px-2 rounded-lg py-1 transition text-[10px] cursor-pointer inline-flex items-center gap-1 font-semibold"><i data-lucide="plus" class="w-2.5 h-2.5 text-emerald-400"></i> ' + item.name + '</button>';
            });
            container.innerHTML = html;
            if (window.lucide) window.lucide.createIcons();
        }

        let allPodcasts = [];

        async function loadPodcastsPhp() {
            renderPodcastSuggestions();
            const loadingEl = document.getElementById('podcasts-loading');
            const emptyEl = document.getElementById('podcasts-empty');
            const gridEl = document.getElementById('podcasts-grid');
            const detailsEl = document.getElementById('podcast-details');

            if (loadingEl) loadingEl.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');
            if (gridEl) gridEl.innerHTML = '';
            if (detailsEl) detailsEl.classList.add('hidden');

            try {
                const res = await fetch(API + '?route=podcasts');
                allPodcasts = await res.json();
                if (loadingEl) loadingEl.classList.add('hidden');

                if (allPodcasts.length === 0) {
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    return;
                }

                allPodcasts.forEach(pod => {
                    const card = document.createElement('div');
                    card.className = "group bg-slate-950/40 border border-slate-900 hover:border-slate-800 rounded-2xl p-4 transition duration-300 transform hover:-translate-y-1 cursor-pointer";
                    card.onclick = () => showPodcastDetailsPhp(pod.name);

                    const imgUrl = pod.coverUrl || 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150';
                    card.innerHTML = `
                        <div class="aspect-square relative w-full rounded-xl overflow-hidden bg-slate-900 mb-3 shadow">
                            <img src="${imgUrl}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" referrerpolicy="no-referrer">
                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white shadow-md transform scale-90 group-hover:scale-100 transition">
                                    <i data-lucide="play" class="w-4 h-4 fill-white text-white ml-0.5"></i>
                                </div>
                            </div>
                        </div>
                        <h4 class="text-xs font-bold text-white truncate leading-tight">${pod.name}</h4>
                        <p class="text-[10px] text-slate-500 mt-1 truncate">${pod.episodes.length} episódios no disco</p>
                    `;
                    if (gridEl) gridEl.appendChild(card);
                });
                lucide.createIcons();
            } catch (err) {
                console.error(err);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (gridEl) gridEl.innerHTML = '<div class="col-span-1 text-center text-xs text-red-500 py-12">Falha ao ler os podcasts sincronizados.</div>';
            }
        }
        window.loadPodcastsPhp = loadPodcastsPhp;

        function showPodcastDetailsPhp(podName) {
            const pod = allPodcasts.find(p => p.name === podName);
            if (!pod) return;

            const detailsEl = document.getElementById('podcast-details');
            const detailNameEl = document.getElementById('pod-detail-name');
            const detailImgEl = document.getElementById('pod-detail-img');
            const listEl = document.getElementById('pod-episodes-list');

            if (detailNameEl) detailNameEl.textContent = pod.name;
            if (detailImgEl) detailImgEl.src = pod.coverUrl || 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150';
            if (listEl) listEl.innerHTML = '';

            // Configurar limite e botão de atualização
            const limitSelect = document.getElementById('pod-detail-limit');
            if (limitSelect) {
                limitSelect.value = String(pod.limit || 5);
            }
            const updateBtn = document.getElementById('pod-detail-update-btn');
            if (updateBtn) {
                updateBtn.onclick = async () => {
                    const currentLimit = limitSelect ? parseInt(limitSelect.value) : 5;
                    await window.runPodcastSync(pod.feedUrl, currentLimit, updateBtn);
                };
            }

            pod.episodes.forEach((ep, idx) => {
                const epEl = document.createElement('div');
                epEl.className = "group flex items-center justify-between p-3.5 bg-[#0a0f18]/45 hover:bg-orange-500/5 border border-slate-900/60 hover:border-orange-500/10 rounded-xl transition cursor-pointer";
                
                const mb = (ep.fileSize / (1024 * 1024)).toFixed(1);
                
                const minutes = Math.floor(ep.duration / 60);
                const secs = ep.duration % 60;
                const durStr = minutes + ":" + (secs < 10 ? "0" : "") + secs;

                epEl.innerHTML = `
                    <div class="flex items-center gap-3.5 min-w-0" onclick="playPodcastEpisodePhp('${pod.name}', '${ep.id}')">
                        <div class="w-8 h-8 rounded-lg bg-slate-950 flex items-center justify-center text-slate-500 shrink-0 group-hover:bg-orange-500 group-hover:text-white transition">
                            <span class="text-xs group-hover:hidden font-mono font-bold">${idx + 1}</span>
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-current hidden group-hover:block ml-0.5"></i>
                        </div>
                        <div class="truncate">
                            <h5 class="text-xs font-bold text-white group-hover:text-orange-400 transition truncate">${ep.title}</h5>
                            <p class="text-[10px] text-slate-500 mt-1 flex items-center gap-2">
                                <span class="bg-slate-900 text-slate-500 px-1.5 py-0.2 rounded uppercase font-bold text-[8px]">${mb} MB</span>
                                <span>•</span>
                                <span>Pasta: <span class="font-mono text-slate-600">${ep.fileName}</span></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-[11px] text-slate-500 pr-2">
                        <span class="font-mono">${durStr}</span>
                    </div>
                `;
                if (listEl) listEl.appendChild(epEl);
            });

            const playAllBtn = document.getElementById('pod-play-all-btn');
            if (playAllBtn) {
                playAllBtn.onclick = () => {
                    const tracksToPlay = pod.episodes.map(convertPodcastEpisodeToPlayerTrack);
                    playQueuePhp(tracksToPlay, 0);
                };
            }

            if (detailsEl) {
                detailsEl.classList.remove('hidden');
                detailsEl.scrollIntoView({ behavior: 'smooth' });
            }
            lucide.createIcons();
        }
        window.showPodcastDetailsPhp = showPodcastDetailsPhp;

        function convertPodcastEpisodeToPlayerTrack(ep) {
            return {
                id: ep.id,
                title: ep.title,
                artist: ep.artist,
                album: ep.album,
                duration: ep.duration,
                coverUrl: ep.coverUrl,
                cover_url: ep.coverUrl,
                fileName: ep.fileName,
                file_name: ep.fileName,
                fileSize: ep.fileSize,
                file_size: ep.fileSize
            };
        }

        window.playPodcastEpisodePhp = function(podName, epId) {
            const pod = allPodcasts.find(p => p.name === podName);
            if (!pod) return;
            const targetIdx = pod.episodes.findIndex(e => String(e.id) === String(epId));
            const tracksToPlay = pod.episodes.map(convertPodcastEpisodeToPlayerTrack);
            playQueuePhp(tracksToPlay, targetIdx !== -1 ? targetIdx : 0);
        };

        function playQueuePhp(queue, startIndex) {
            if (!queue || queue.length === 0) return;
            activeQueue = queue;
            activeQueueIdx = startIndex;
            loadTrack(activeQueue[activeQueueIdx]);
            if (audio.paused) {
                audio.play();
                isPlaying = true;
                const playBtn = document.getElementById('player-play-btn');
                if (playBtn) playBtn.innerHTML = '<i data-lucide="pause" class="w-4 h-4 fill-current"></i>';
            }
            renderPlayerMiniQueue();
        }

        window.setPodcastFeedPreset = function(url) {
            const inputs = document.querySelectorAll('#podcast-feed-input');
            inputs.forEach(input => {
                input.value = url;
            });
            window.runPodcastSync();
        };

        window.runPodcastSync = async function(feedUrlOverride = null, maxEpisodesOverride = null, customBtn = null) {
            if (currentUser.role !== 'admin') {
                alert("Apenas administradores podem gerenciar sincronização de Podcast.");
                return;
            }

            const input = document.getElementById('podcast-feed-input');
            const limitSelect = document.getElementById('podcast-max-episodes');
            const btn = customBtn || document.getElementById('btn-sync-podcast');
            if (!btn) return;

            const val = feedUrlOverride || (input ? input.value.trim() : '');
            if (!val) {
                alert("Insira a URL do feed RSS.");
                return;
            }

            const maxEpisodesVal = maxEpisodesOverride !== null ? maxEpisodesOverride : (limitSelect ? parseInt(limitSelect.value) : 5);

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="refresh-cw" class="w-4 h-4 animate-spin"></i> Processando...';
            if (window.lucide) lucide.createIcons();

            const statusEl = document.getElementById('podcast-status-msg');
            if (statusEl) {
                statusEl.classList.remove('hidden');
                statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-slate-900 border border-slate-800 text-slate-300";
                statusEl.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin text-orange-500"></i> Baixando e processando áudios no PHP...';
            }
            if (window.lucide) lucide.createIcons();

            try {
                const res = await fetch(API + '?route=podcasts_sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ feedUrl: val, maxEpisodes: maxEpisodesVal })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (statusEl) {
                        statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-emerald-950/20 border border-emerald-900/30 text-emerald-350";
                        statusEl.innerHTML = '<i data-lucide="check" class="w-4 h-4 shrink-0"></i> Sincronizado com sucesso! Podcast "' + data.podcastName + '" atualizado com sucesso.';
                    }
                    if (input && !feedUrlOverride) input.value = '';
                    
                    if (window.loadData) {
                        await window.loadData();
                    }
                    await loadPodcastsPhp();

                    if (data.podcastName && window.showPodcastDetailsPhp) {
                        window.showPodcastDetailsPhp(data.podcastName);
                    }
                } else {
                    const data = await res.json();
                    if (statusEl) {
                        statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-red-950/20 border border-red-900/30 text-red-200";
                        statusEl.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> Erro: ' + (data.error || 'Falha desconhecida.');
                    }
                }
            } catch (err) {
                console.error(err);
                if (statusEl) {
                    statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-red-950/20 border border-red-900/30 text-red-200";
                    statusEl.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> Erro de rede ao sincronizar.';
                }
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (window.lucide) lucide.createIcons();
            }
        };

        let allRadios = [];

        async function loadRadiosPhp() {
            renderRadioSuggestions();
            const loadingEl = document.getElementById('radios-loading');
            const emptyEl = document.getElementById('radios-empty');
            const gridEl = document.getElementById('radios-grid');

            if (loadingEl) loadingEl.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');
            if (gridEl) gridEl.innerHTML = '';

            try {
                const res = await fetch(API + '?route=radios');
                allRadios = await res.json();
                if (loadingEl) loadingEl.classList.add('hidden');

                if (!allRadios || allRadios.length === 0) {
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    return;
                }

                allRadios.forEach(radio => {
                    const card = document.createElement('div');
                    const cleanLink = radio.url.toLowerCase();
                    let formatLabel = "STREAM";
                    if (cleanLink.includes('.m3u')) formatLabel = "M3U";
                    else if (cleanLink.includes('.pls')) formatLabel = "PLS";
                    else if (cleanLink.includes('.asx')) formatLabel = "ASX";

                    const currentTrack = (activeQueueIdx >= 0 && activeQueueIdx < activeQueue.length) ? activeQueue[activeQueueIdx] : null;
                    const isCurrentRadio = currentTrack && currentTrack.id === radio.id && (currentTrack.artist === 'Rádio On-line' || currentTrack.album === 'Sintonizada') && isPlaying;

                    card.id = "radio-card-" + radio.id;
                    card.className = "group relative bg-slate-950/45 hover:bg-slate-900/50 border rounded-2xl p-4 transition-all duration-300 transform hover:-translate-y-0.5 cursor-pointer flex flex-col justify-between h-36 " +
                        (isCurrentRadio ? "border-emerald-500 shadow-lg shadow-emerald-500/5" : "border-slate-900/90 hover:border-slate-800");

                    card.onclick = () => playRadioPhp(radio.id, radio.name, radio.resolved_url || radio.url);

                    const deleteBtnHtml = (currentUser.role === 'admin') ? `<button onclick="handleDeleteRadioPhp('${radio.id}', event)" class="p-1.5 text-slate-600 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition" title="Remover Rádio"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>` : '';

                    card.innerHTML = `
                        <div class="flex justify-between items-start w-full">
                            <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full select-none ${isCurrentRadio ? 'bg-emerald-500/20 text-emerald-400 animate-pulse' : 'bg-slate-900 text-slate-500'}">
                                ${isCurrentRadio ? 'SINTONIZADA' : formatLabel}
                            </span>
                            ${deleteBtnHtml}
                        </div>
                        <div class="my-2 min-w-0 pr-6">
                            <h4 class="text-xs font-extrabold text-white truncate leading-tight group-hover:text-emerald-400 transition-colors">${radio.name}</h4>
                            <p class="text-[9px] text-slate-600 truncate mt-1 select-all font-mono" title="${radio.url}">${radio.url}</p>
                        </div>
                        <div class="flex items-center justify-between mt-1 pt-1.5 border-t border-slate-900/50">
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full ${isCurrentRadio ? 'bg-emerald-400 animate-ping' : 'bg-slate-700'}"></div>
                                <span class="text-[9px] font-mono text-slate-500 font-bold uppercase select-none">
                                    ${isCurrentRadio ? 'CONECTADA' : 'PRONTA'}
                                </span>
                            </div>
                            <div class="w-7.5 h-7.5 rounded-full flex items-center justify-center transition-all ${isCurrentRadio ? 'bg-emerald-500 text-white' : 'bg-slate-900 text-slate-400 group-hover:bg-emerald-500 group-hover:text-white'}">
                                ${isCurrentRadio 
                                    ? '<div class="flex gap-0.5 items-end justify-center h-2.5"><span class="w-0.5 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></span><span class="w-0.5 bg-white rounded-full animate-bounce" style="animation-delay: 150ms; height: 6px"></span><span class="w-0.5 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></span></div>'
                                    : '<i class="w-3" style="font-size: 8px; display: flex; align-items: center; justify-content: center;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play"><polygon points="6 3 20 12 6 21 6 3"/></svg></i>'
                                }
                            </div>
                        </div>
                    `;

                    if (gridEl) gridEl.appendChild(card);
                });
                lucide.createIcons();
            } catch (err) {
                console.error(err);
                if (loadingEl) loadingEl.classList.add('hidden');
                if (gridEl) gridEl.innerHTML = '<div class="col-span-1 text-center text-xs text-red-500 py-12">Falha ao ler rádios sintonizadas.</div>';
            }
        }
        window.loadRadiosPhp = loadRadiosPhp;

        window.handleAddRadioPhp = async function(e) {
            if (e) e.preventDefault();
            
            if (currentUser.role !== 'admin') {
                alert("Apenas administradores podem cadastrar rádios.");
                return;
            }

            const nameInput = document.getElementById('radio-name-input');
            const urlInput = document.getElementById('radio-url-input');
            const btn = document.getElementById('btn-add-radio');

            if (!nameInput || !urlInput || !btn) return;

            const name = nameInput.value.trim();
            const url = urlInput.value.trim();

            if (!name || !url) {
                alert("Por favor, preencha todos os campos.");
                return;
            }

            btn.disabled = true;
            const origText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Cadastrando...';
            lucide.createIcons();

            try {
                const res = await fetch(API + '?route=radios', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, url: url })
                });

                if (res.ok) {
                    nameInput.value = '';
                    urlInput.value = '';
                    loadRadiosPhp();
                } else {
                    const data = await res.json();
                    alert(data.error || 'Erro ao cadastrar rádio.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de comunicação.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
                lucide.createIcons();
            }
        };

        window.setRadioPreset = function(name, url) {
            const nameInput = document.getElementById('radio-name-input');
            const urlInput = document.getElementById('radio-url-input');
            if (nameInput && urlInput) {
                nameInput.value = name;
                urlInput.value = url;
                window.handleAddRadioPhp();
            }
        };

        window.handleDeleteRadioPhp = async function(id, e) {
            if (e) e.stopPropagation();
            
            if (currentUser.role !== 'admin') {
                alert("Apenas administradores podem remover rádios.");
                return;
            }

            if (!confirm('Deseja realmente remover esta rádio?')) return;

            try {
                const res = await fetch(API + '?route=radios_delete&id=' + encodeURIComponent(id), {
                    method: 'POST'
                });

                if (res.ok) {
                    loadRadiosPhp();
                } else {
                    const data = await res.json();
                    alert(data.error || 'Falha ao remover rádio.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de comunicação.');
            }
        };

        window.playRadioPhp = function(id, name, url) {
            const radioTrack = {
                id: id,
                title: name,
                artist: 'Rádio On-line',
                album: 'Sintonizada',
                duration: 0,
                cover_url: 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150',
                file_name: url,
                file_size: 0
            };
            
            activeQueue = [radioTrack];
            activeQueueIdx = 0;
            
            loadTrack(radioTrack);
            if (audio.paused) {
                audio.play();
                isPlaying = true;
                const playBtn = document.getElementById('player-play-btn');
                if (playBtn) playBtn.innerHTML = '<i data-lucide="pause" class="w-4 h-4 fill-current"></i>';
            }
            renderPlayerMiniQueue();
            
            loadRadiosPhp();
        };

        window.bootPublicSharedPlayer = async function(hash) {
            const container = document.getElementById('public-shared-player');
            if (!container) return;
            container.innerHTML = `<div class="w-full h-full flex items-center justify-center text-slate-400">
                <i data-lucide="loader" class="w-8 h-8 animate-spin"></i>
            </div>`;
            container.classList.remove('hidden');
            if (lucide) lucide.createIcons();

            try {
                const res = await fetch(API + '?route=resolve_share&hash=' + encodeURIComponent(hash));
                const data = await res.json();
                
                if (!res.ok || !data.tracks) {
                    container.innerHTML = `<div class="w-full h-full flex flex-col gap-4 items-center justify-center text-slate-400">
                        <i data-lucide="x-circle" class="w-12 h-12 text-red-500"></i>
                        <p class="font-bold text-lg text-white">Oops!</p>
                        <p class="text-sm">Link de compartilhamento inválido ou expirado.</p>
                        <button onclick="window.location.search=''; window.location.hash=''; window.location.reload();" class="mt-4 px-4 py-2 bg-slate-900 rounded-xl hover:text-white transition">Ir para Home</button>
                    </div>`;
                    if (lucide) lucide.createIcons();
                    return;
                }

                // Render the clean view
                const cover = data.tracks[0]?.cover_url || data.tracks[0]?.coverUrl || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400';
                
                let html = `
                    <div class="w-full max-w-4xl p-6 h-full flex flex-col space-y-6 pt-12 animate-fade-in custom-scroll overflow-y-auto">
                        <div class="flex items-center gap-6">
                            <img src="${cover}" referrerpolicy="no-referrer" class="w-32 h-32 md:w-48 md:h-48 object-cover rounded-3xl shadow-2xl border border-slate-800">
                            <div class="space-y-2">
                                <span class="bg-indigo-500/20 text-indigo-400 font-black uppercase text-[10px] px-2 py-0.5 rounded-full">${data.target_type === 'playlist' ? 'PLAYLIST' : 'ÁLBUM COMPARTILHADO'}</span>
                                <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white">${data.target_name}</h1>
                                <p class="text-slate-400 font-semibold flex items-center gap-2">
                                    <span>${data.tracks.length} músicas</span>
                                    <button onclick="window.playSharedTracks(false)" class="ml-4 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-xs font-bold transition">Tocar Tudo</button>
                                    <button onclick="window.playSharedTracks(true)" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-bold transition"><i data-lucide="shuffle" class="w-4 h-4"></i></button>
                                </p>
                            </div>
                        </div>

                        <div class="bg-slate-950/60 border border-slate-900/50 rounded-3xl overflow-hidden mt-6 pb-20">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead>
                                    <tr class="border-b border-slate-900/60 text-slate-500 font-mono tracking-wider text-[9px] uppercase">
                                        <th class="py-3 px-4 w-12">#</th>
                                        <th class="py-3 px-4">Música</th>
                                        <th class="py-3 px-4 text-center">Duração</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-900/40">
                `;

                data.tracks.forEach((t, i) => {
                    const dur = t.duration || 0;
                    const durText = dur ? `${Math.floor(dur/60)}:${String(dur%60).padStart(2,'0')}` : '--:--';
                    html += `
                        <tr class="hover:bg-slate-900/40 transition group cursor-pointer" onclick="window.playSharedTrackIndex(${i})">
                            <td class="py-3 px-4 text-slate-500 font-mono">${t.track_num || i + 1}</td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-white leading-tight group-hover:text-emerald-400 transition">${t.title}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">${t.artist}</div>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-slate-500 shrink-0">${durText}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div></div>`;
                
                container.innerHTML = html;
                const playerToolbar = document.getElementById('player-toolbar');
                if (playerToolbar) playerToolbar.classList.remove('hidden');
                if (lucide) lucide.createIcons();

                window.publicSharedTracks = data.tracks;
            } catch (err) {
                console.error(err);
                if (container) container.innerHTML = `<div class="text-red-500">Erro de rede.</div>`;
            }
        };

        window.playSharedTracks = function(isShuffle) {
            if (!window.publicSharedTracks || window.publicSharedTracks.length === 0) return;
            const tracks = [...window.publicSharedTracks];
            if (isShuffle) tracks.sort(() => Math.random() - 0.5);
            activeQueue = tracks;
            activeQueueIdx = 0;
            loadTrack(activeQueue[0]);
            const aud = document.getElementById('real-audio');
            if (aud) {
                aud.play();
                isPlaying = true;
                const btn = document.getElementById('player-play-btn');
                if (btn) btn.innerHTML = '<i data-lucide="pause" class="w-4 h-4 fill-current"></i>';
                renderPlayerMiniQueue();
            }
        };

        window.playSharedTrackIndex = function(idx) {
            if (!window.publicSharedTracks || !window.publicSharedTracks[idx]) return;
            activeQueue = [...window.publicSharedTracks];
            activeQueueIdx = idx;
            loadTrack(activeQueue[idx]);
            const aud = document.getElementById('real-audio');
            if (aud) {
                aud.play();
                isPlaying = true;
                const btn = document.getElementById('player-play-btn');
                if (btn) btn.innerHTML = '<i data-lucide="pause" class="w-4 h-4 fill-current"></i>';
                renderPlayerMiniQueue();
            }
        };

        
        let isCheckingUpdate = false;
        async function checkPhpUpdates() {
            if (isCheckingUpdate) return;
            const btn = document.getElementById('btn-check-updates');
            const badge = document.getElementById('update-status-badge');
            const clCont = document.getElementById('changelog-container');
            const cvLabel = document.getElementById('current-version-label');
            const rvLabel = document.getElementById('remote-version-label');
            const clText = document.getElementById('changelog-content');
            const dlWrap = document.getElementById('download-update-wrapper');
            
            isCheckingUpdate = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Verificando...';
            if(window.lucide) lucide.createIcons();

            try {
                const res = await fetch(API + '?route=check_update');
                const data = await res.json();
                
                cvLabel.textContent = data.current_version;
                rvLabel.textContent = data.remote_version;
                
                clCont.classList.remove('hidden');
                clText.textContent = data.changelog;
                
                if (data.has_update) {
                    badge.className = "inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-[11px] font-bold";
                    badge.innerHTML = '<i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Atualização Disponível!';
                    dlWrap.classList.remove('hidden');
                } else {
                    badge.className = "inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-500/10 border border-sky-500/20 text-sky-400 rounded-lg text-[11px] font-bold";
                    badge.innerHTML = '<i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Sistema Atualizado';
                    dlWrap.classList.add('hidden');
                }
            } catch (err) {
                console.error(err);
                badge.className = "inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-lg text-[11px] font-bold";
                badge.innerHTML = '<i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Erro ao verificar';
                clCont.classList.add('hidden');
            } finally {
                isCheckingUpdate = false;
                btn.innerHTML = origHtml;
                if(window.lucide) lucide.createIcons();
            }
        }

        // =====================================================
    </script>
</body>
</html>
