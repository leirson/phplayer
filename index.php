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
    die("<div style='font-family:sans-serif;background:#0f172a;color:#f87171;padding:30px;border-radius:12px;margin:50px auto;max-width:600px;border:1px solid #ef444430;'><h3>Arquivo de Configuração Ausente</h3><p>O arquivo <strong>config.php</strong> não foi encontrado na mesma pasta raiz que o <strong>index.php</strong>. Crie-o primeiro com as definições de conexão corretas antes de abrir o reprodutor.</p></div>");
}
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
                        <span class="text-[9px] text-sky-450 font-mono tracking-wider">HOSTINGER WEB</span>
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
                    <div class="flex items-center justify-between mb-3 border-b border-slate-900 pb-2">
                        <h3 class="text-xs font-black text-slate-400 tracking-wider uppercase" data-i18n="album-collection-title">Coleção de Álbuns</h3>
                        <button onclick="playRandomAlbum()" class="px-3 py-1.5 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-[11px] font-black uppercase tracking-wider transition active:scale-95 flex items-center gap-1.5 shadow-lg shadow-sky-500/10 cursor-pointer" data-i18n="btn-random-album">
                            <i data-lucide="shuffle" class="w-3.5 h-3.5"></i> Tocar Álbum Aleatório
                        </button>
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

                <div id="tracks-table-wrapper" class="bg-slate-950/60 border border-slate-900 rounded-2xl overflow-hidden min-h-[300px]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/50 text-slate-500 uppercase font-mono tracking-wider text-[9px] border-b border-slate-900 select-none">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center" data-i18n="col-idx">#</th>
                                <th class="py-3 px-4 cursor-pointer hover:bg-slate-900 transition" onclick="sortTracksPhp('title')" data-i18n="col-track">Faixa <span id="sort-icon-title" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                <th class="py-3 px-4 cursor-pointer hover:bg-slate-900 transition" onclick="sortTracksPhp('artist')" data-i18n="col-artist">Artista <span id="sort-icon-artist" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                <th class="py-3 px-4 cursor-pointer hover:bg-slate-900 transition" onclick="sortTracksPhp('album')" data-i18n="col-album">Álbum <span id="sort-icon-album" class="text-slate-600 opacity-40"> &updownarrow;</span></th>
                                <th class="py-3 px-4 text-right w-28 font-semibold" data-i18n="col-operations">Operações</th>
                            </tr>
                        </thead>
                        <tbody id="tracks-table-body" class="divide-y divide-slate-900/40 text-slate-300"></tbody>
                    </table>
                    <div id="tracks-pagination-wrapper"></div>
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
                    <button onclick="setConfigSubTab('users')" id="subtab-btn-users" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only" data-i18n="subnav-users">
                        Editar Usuários
                    </button>
                    <button onclick="setConfigSubTab('password')" id="subtab-btn-password" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none" data-i18n="subnav-password">
                        Alterar Senha
                    </button>
                    <button onclick="setConfigSubTab('files')" id="subtab-btn-files" class="pb-2 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-300 cursor-pointer select-none hidden admin-only" data-i18n="subnav-files">
                        Arquivos
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

                    <!-- DLNA Server Settings Card -->
                    <div class="bg-slate-900/10 border border-slate-900 rounded-2xl p-6 space-y-4 text-left mt-6 animate-fade-in font-sans">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-1.5 align-middle">
                                <i data-lucide="cast" class="w-4 h-4 text-sky-400"></i> Servidor DLNA (UPnP MediaServer)
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Ative o suporte a DLNA no seu servidor PHPlayer para parear e reproduzir músicas e vídeos diretamente em Smart TVs, consoles de videogame ou receptores de áudio compatíveis na sua rede doméstica.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-950/20 border border-slate-900 p-4 rounded-xl">
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input id="dlna-enabled-toggle" type="checkbox" onchange="toggleDlnaSetting(this)" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-900 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500 peer-checked:after:bg-white border border-slate-800"></div>
                                    <span class="ml-3 text-xs font-bold text-slate-300 uppercase tracking-wider">Habilitar DLNA</span>
                                </label>
                            </div>
                            <!-- DLNA active status dynamic micro indicators -->
                            <div id="dlna-status-indicator" class="flex items-center gap-1.5 text-[11px] font-mono text-slate-500 bg-slate-950/45 px-3 py-1.5 rounded-lg border border-slate-900">
                                <span class="w-2 h-2 rounded-full bg-slate-600 block"></span>
                                OFFLINE
                            </div>
                        </div>
                        <!-- Paired renderers view, loaded when enabled -->
                        <div id="dlna-devices-expanded" class="space-y-2.5 hidden">
                            <span class="text-[10px] uppercase tracking-wider font-extrabold text-indigo-400 block">Dispositivos de Mídia Pareados (UPnP-AV Renderer) IP:3000:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="bg-indigo-950/15 border border-indigo-500/15 rounded-xl p-3 flex flex-col justify-between hover:border-indigo-500/25 transition animate-fade-in">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="tv" class="w-4 h-4 text-emerald-400 animate-pulse"></i>
                                        <span class="text-xs font-bold text-white truncate">Sala de Estar TV</span>
                                    </div>
                                    <span class="text-[9px] font-mono text-slate-500 mt-2 block uppercase font-bold">LG webOS TV (Ativo)</span>
                                </div>
                                <div class="bg-indigo-950/15 border border-indigo-500/15 rounded-xl p-3 flex flex-col justify-between hover:border-indigo-500/25 transition animate-fade-in">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="tv" class="w-4 h-4 text-emerald-400 animate-pulse"></i>
                                        <span class="text-xs font-bold text-white truncate">Quarto Principal TV</span>
                                    </div>
                                    <span class="text-[9px] font-mono text-slate-500 mt-2 block uppercase font-bold">Samsung QLED (Ativo)</span>
                                </div>
                                <div class="bg-indigo-950/15 border border-indigo-500/15 rounded-xl p-3 flex flex-col justify-between hover:border-indigo-500/25 transition animate-fade-in">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="gamepad-2" class="w-4 h-4 text-emerald-400 animate-pulse font-bold"></i>
                                        <span class="text-xs font-bold text-white truncate font-bold">Xbox Series X</span>
                                    </div>
                                    <span class="text-[9px] font-mono text-slate-500 mt-2 block uppercase font-bold">UPnP AV Player (Ativo)</span>
                                </div>
                            </div>
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

                <!-- SUBTAB 4: CHANGE MY PASSWORD (Visible to all users) -->
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
            </div>
            <div class="w-full flex items-center gap-2">
                <span id="player-current-time" class="text-[9px] font-mono text-slate-500 w-8 text-right">0:00</span>
                <input id="player-seek" oninput="seek(this.value)" type="range" min="0" value="0" step="0.5" class="flex-1 h-1 bg-slate-800 accent-sky-500 rounded-lg cursor-pointer">
                <span id="player-duration" class="text-[9px] font-mono text-slate-500 w-8">0:00</span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 w-1/3">
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
            
            <!-- Modal Footer -->
            <div class="p-4 bg-slate-950/60 border-t border-slate-900 text-center">
                <span class="text-[9px] font-mono text-slate-600 uppercase tracking-widest">
                    Imagens carregadas via scraping dinâmico e APIs públicas
                </span>
            </div>
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

        let currentUser = null;
        let activeTab = 'dashboard';
        
        let allTracks = [];
        let allPlaylists = [];
        let allFavorites = [];
        let filteredTracks = [];
        let allVideos = [];
        let uploadingVideoId = null;
        
        let selectedArtist = '';
        let artistBioText = '';
        let artistPhotoUrl = '';
        let loadingArtistBio = false;
        let loadedCoversCache = {};
        let isBioExpanded = false;
        
        let selectedPlaylistId = '';
        let activePlaylistAlbum = ''; // for album detail views

        // Player engine variables
        let isPlaying = false;
        let isShuffle = false;
        let isLoop = false;
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
                });
                audio.addEventListener('loadedmetadata', () => {
                    const seek = document.getElementById('player-seek');
                    const dur = document.getElementById('player-duration');
                    if (seek) seek.max = audio.duration || 180;
                    if (dur) dur.textContent = formatSecs(audio.duration || 180);
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

        async function bootPlayer() {
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

            if (!dashboardRandomInterval) {
                dashboardRandomInterval = setInterval(() => {
                    const pane = document.getElementById('pane-dashboard');
                    if (pane && !pane.classList.contains('hidden')) {
                        updateRandomDashboardAlbums();
                        renderAlbumGrid();
                    }
                }, 8000);
            }
        }

        async function loadData() {
            try {
                const r1 = await fetch(API + '?route=tracks');
                const t1 = await r1.text();
                try {
                    allTracks = JSON.parse(t1);
                } catch(e) {
                    throw new Error("Erro de dados de músicas: resposta inválida do servidor (não-JSON). Detalhes:\n" + t1.substring(0, 150));
                }
                if (allTracks && allTracks.error) {
                    throw new Error(allTracks.error);
                }
                
                const r2 = await fetch(API + '?route=playlists&username=' + encodeURIComponent(currentUser.username));
                const t2 = await r2.text();
                try {
                    allPlaylists = JSON.parse(t2);
                } catch(e) {
                    throw new Error("Erro de dados de playlists: resposta inválida do servidor (não-JSON). Detalhes:\n" + t2.substring(0, 150));
                }
                if (allPlaylists && allPlaylists.error) {
                    throw new Error(allPlaylists.error);
                }

                const r3 = await fetch(API + '?route=favorites&username=' + encodeURIComponent(currentUser.username));
                const t3 = await r3.text();
                try {
                    allFavorites = JSON.parse(t3);
                } catch(e) {
                    throw new Error("Erro de dados de favoritos: resposta inválida do servidor (não-JSON). Detalhes:\n" + t3.substring(0, 150));
                }
                if (allFavorites && allFavorites.error) {
                    throw new Error(allFavorites.error);
                }
                if (activeTab === 'playlists' && window.renderPlaylistsGrid) {
                    window.renderPlaylistsGrid();
                }
            } catch (err) {
                console.error(err);
                showErrorModal("Erro de conexão com o banco de dados PHP: " + err.message);
            }
        }

        function selectArtist(art) {
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
            const btns = ['dashboard', 'tracks', 'favorites', 'config', 'videos', 'playlists'];
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
            const adminTabs = ['media', 'users', 'files'];
            if (adminTabs.includes(subTabName) && (!currentUser || currentUser.role !== 'admin')) {
                subTabName = 'theme';
            }
            configActiveSubTab = subTabName;
            
            // Hide all subtab panes
            document.getElementById('subtab-pane-theme').classList.add('hidden');
            document.getElementById('subtab-pane-media').classList.add('hidden');
            document.getElementById('subtab-pane-users').classList.add('hidden');
            const pwdPane = document.getElementById('subtab-pane-password');
            if (pwdPane) pwdPane.classList.add('hidden');
            const filesPane = document.getElementById('subtab-pane-files');
            if (filesPane) filesPane.classList.add('hidden');
            
            // Hide all nav button markers
            const subBtns = ['theme', 'media', 'users', 'password', 'files'];
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
            lucide.createIcons();
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
        }

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
            activeTab = tabName;
            document.getElementById('pane-dashboard').classList.add('hidden');
            document.getElementById('pane-tracks').classList.add('hidden');
            if (document.getElementById('pane-config')) document.getElementById('pane-config').classList.add('hidden');
            document.getElementById('pane-videos').classList.add('hidden');
            if (document.getElementById('pane-playlists')) document.getElementById('pane-playlists').classList.add('hidden');
            
            // Clear navigation classes
            const btns = ['dashboard', 'tracks', 'favorites', 'config', 'videos', 'playlists'];
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
                if (window.loadLastfmKeyForUI) window.loadLastfmKeyForUI();
            } else if (tabName === 'videos') {
                renderVideoGallery();
                document.getElementById('pane-videos').classList.remove('hidden');
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
                    albsMap[key] = { name: key, artist: t.artist, genre: t.genre || 'Desconhecido', cover: t.cover_url || t.coverUrl, tracks: [] };
                } else if (t.genre && (!albsMap[key].genre || albsMap[key].genre === 'DESCONHECIDO' || albsMap[key].genre === 'Local Scan' || albsMap[key].genre === 'Desconhecido')) {
                    albsMap[key].genre = t.genre;
                }
                albsMap[key].tracks.push(t);
            });

            const albumsArray = Object.values(albsMap);
            if (albumsArray.length === 0) {
                randomDashboardAlbums = [];
                return;
            }

            // Shuffle and pick up to 12 random albums
            const shuffled = [...albumsArray].sort(() => Math.random() - 0.5);
            randomDashboardAlbums = shuffled.slice(0, 12);
        }

        window.playRandomAlbum = function() {
            if (!allTracks || allTracks.length === 0) return;
            const albsMap = {};
            allTracks.forEach(t => {
                const key = t.album || 'Single';
                if (!albsMap[key]) {
                    albsMap[key] = { name: key, artist: t.artist, genre: t.genre || 'Desconhecido', cover: t.cover_url || t.coverUrl, tracks: [] };
                }
                albsMap[key].tracks.push(t);
            });
            const albumsArray = Object.values(albsMap);
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
                        <img src="${alb.cover || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=300'}" class="w-[70%] h-[70%] rounded-xl object-cover shadow-md border border-slate-800/40 group-hover:scale-105 duration-300 transition" referrerpolicy="no-referrer">
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
            let countLabel = "";
            let rotateBtnText = "";
            if (currentLang === 'en') {
                countLabel = `Showing ${Math.min(12, totalAlbumsCount)} of ${totalAlbumsCount} random albums (Auto-rotated every 8s)`;
                rotateBtnText = "Next Albums ⟳";
            } else if (currentLang === 'es') {
                countLabel = `Mostrando ${Math.min(12, totalAlbumsCount)} de ${totalAlbumsCount} álbumes aleatorios (Rotación automática cada 8s)`;
                rotateBtnText = "Siguientes álbumes ⟳";
            } else {
                countLabel = `Mostrando ${Math.min(12, totalAlbumsCount)} de ${totalAlbumsCount} álbuns aleatórios (Rotação a cada 8s)`;
                rotateBtnText = "Próximos Álbuns ⟳";
            }

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

        // MUSIC REPRODUCER LOGIC
        function loadTrack(track) {
            const trackIdStr = String(track.id);
            const isLocal = trackIdStr.startsWith('seed-');
            audio.src = isLocal ? (track.fileName || track.file_name) : (API + '?route=stream&id=' + trackIdStr);
            
            document.getElementById('track-cover').src = track.coverUrl || track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100';
            document.getElementById('track-title').textContent = track.title;
            document.getElementById('track-artist').textContent = track.artist;
            
            audio.play();
            isPlaying = true;
            document.getElementById('player-play-btn').innerHTML = `<i data-lucide="pause" class="w-4 h-4 fill-current"></i>`;
            lucide.createIcons();
            
            // update highlighting on tables
            renderTracksTable();
            renderPlayerMiniQueue();
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

        function volume(val) {
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
        }

        function toggleLoop() {
            isLoop = !isLoop;
            document.getElementById('player-loop').className = isLoop ? "text-sky-400 font-bold transition cursor-pointer" : "text-slate-500 hover:text-white transition cursor-pointer";
        }

        // DATABASE OPERATIONS / RENDER TABLES
        function renderTracksTable() {
            const tableWrapper = document.getElementById('tracks-table-wrapper');
            const headerBlock = document.getElementById('tracks-header-block');
            const artistView = document.getElementById('artist-albums-view');
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
                tableWrapper.classList.add('hidden');
                headerBlock.classList.add('hidden');
                artistView.classList.remove('hidden');

                const albumTracks = sourceList;
                const firstTrack = albumTracks[0] || {};
                const albumName = activePlaylistAlbum;
                const albumCover = loadedCoversCache[albumName] || firstTrack.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400';
                const albumIdSafe = albumName.replace(/[^a-zA-Z0-9]/g, '-');

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
                                        <img id="album-cover-img-${albumIdSafe}" src="${albumCover}" referrerpolicy="no-referrer" class="w-[70%] h-[70%] rounded-xl object-cover shadow-md border border-slate-800/40">
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-[#ffffff] text-sm leading-tight line-clamp-2">${albumName}</h3>
                                        <p class="text-[11px] text-sky-450 font-semibold mt-1">${firstTrack.artist || 'Artista Desconhecido'}</p>
                                        <p class="text-[10px] text-slate-500 font-mono mt-1 uppercase tracking-wide">
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
                                <td class="py-2 px-3 text-right w-12">
                                    <button onclick="toggleFav(event, '\${track.id}')" class="p-1 rounded-lg border border-transparent hover:bg-slate-900 transition ${isFav ? 'text-[#f43f5e]' : 'text-slate-500 hover:text-white'} cursor-pointer" title="${isFav ? 'Remover dos Favoritos' : 'Marcar como Favorito'}">
                                        <i data-lucide="heart" class="w-3 h-3 ${isFav ? 'fill-current' : ''}"></i>
                                    </button>
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
                        <div class="relative h-64 md:h-80 rounded-2xl overflow-hidden shadow-2xl border border-slate-900/40">
                            <img src="${bannerPhoto}" referrerpolicy="no-referrer" alt="${selectedArtist}" class="absolute inset-0 w-full h-full object-cover scale-102 filter brightness-[0.35]">
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
                
                Object.keys(albums).forEach(albumName => {
                    const albumTracks = albums[albumName];
                    const firstTrack = albumTracks[0];
                    const albumCover = loadedCoversCache[albumName] || firstTrack.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400';
                    const albumIdSafe = albumName.replace(/[^a-zA-Z0-9]/g, '-');
                    
                    html += `
                        <div class="bg-slate-950/40 border border-slate-900 rounded-2xl overflow-hidden hover:border-slate-850 transition duration-150 p-5 shadow-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <!-- Left side: Big Cover Art, info and buttons -->
                                <div class="sm:col-span-1 space-y-4">
                                    <div class="aspect-square bg-slate-900/40 rounded-2xl overflow-hidden border border-slate-900 flex items-center justify-center relative group shadow-lg">
                                        <img id="album-cover-img-${albumIdSafe}" src="${albumCover}" referrerpolicy="no-referrer" class="w-[70%] h-[70%] rounded-xl object-cover shadow-md border border-slate-800/40 group-hover:scale-102 duration-300 transition">
                                        <div class="absolute inset-0 bg-black/45 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-200">
                                            <i data-lucide="disc" class="w-8 h-8 text-white animate-spin"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-[#ffffff] text-sm leading-tight line-clamp-2">${albumName}</h3>
                                        <p class="text-[11px] text-slate-500 font-mono mt-1 uppercase tracking-wide">
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
                                        
                                        <div class="${currentUser.role === 'admin' ? 'space-y-1.5' : 'hidden'}">
                                            <button onclick="document.getElementById('album-cover-input-${albumIdSafe}').click()" class="w-full py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1 border border-slate-800 cursor-pointer whitespace-nowrap">
                                                <i data-lucide="image" class="w-3 h-3"></i> Alterar Capa
                                            </button>
                                            <input id="album-cover-input-${albumIdSafe}" type="file" accept="image/*" class="hidden" data-artist="${selectedArtist.replace(/"/g, '&quot;')}" data-album="${albumName.replace(/"/g, '&quot;')}" onchange="uploadAlbumCover(this)">
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
                                <td class="py-2 px-3 text-right w-12">
                                    <button onclick="toggleFav(event, '${track.id}')" class="p-1 rounded-lg border border-transparent hover:bg-slate-900 transition ${isFav ? 'text-[#f43f5e]' : 'text-slate-500 hover:text-white'} cursor-pointer" title="${isFav ? 'Remover dos Favoritos' : 'Marcar como Favorito'}">
                                        <i data-lucide="heart" class="w-3 h-3 ${isFav ? 'fill-current' : ''}"></i>
                                    </button>
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
                tableWrapper.classList.remove('hidden');
                headerBlock.classList.remove('hidden');
                artistView.classList.add('hidden');
                artistView.innerHTML = '';
            }

            if (searchVal !== lastSearchQuery) {
                phpCurrentPage = 1;
                lastSearchQuery = searchVal;
            }

            filteredTracks = sourceList.filter(track => {
                return track.title.toLowerCase().includes(searchVal) ||
                       track.artist.toLowerCase().includes(searchVal) ||
                       (track.album && track.album.toLowerCase().includes(searchVal));
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
            const fields = ['title', 'artist', 'album', 'genre'];
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
                tbody.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-slate-500 italic">Essa coleção está vazia.</td></tr>`;
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
                    <td class="py-2.5 px-4 text-right">
                        <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition">
                            <button onclick="toggleFav(event, '\${track.id}')" class="p-1 text-slate-500 hover:text-rose-500 transition cursor-pointer" title="Favoritar"><i data-lucide="heart" class="w-4 h-4 ${isFav ? 'fill-rose-500 text-rose-500' : ''}"></i></button>
                            <button onclick="deleteSong(event, '\${track.id}')" class="p-1 text-slate-500 hover:text-red-500 transition cursor-pointer ${currentUser.role === 'admin' ? '' : 'hidden'}" title="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            <button onclick="addToPlaylistDropdown(event, '\${track.id}')" class="p-1 text-slate-500 hover:text-indigo-400 transition cursor-pointer" title="Adicionar à Playlist"><i data-lucide="list-plus" class="w-4 h-4"></i></button>
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
            
            const query = selectedArtist + " artist music headshot";
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
                if (imageSearchTargetType === 'artist') {
                    tabs.classList.remove('hidden');
                } else {
                    tabs.classList.add('hidden');
                }
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
                            <button onclick="viewPlaylistTracks('${pl.id}', '${safeName}')" class="flex-1 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-slate-350 hover:text-white rounded-xl text-[10px] font-bold uppercase transition flex items-center justify-center gap-1 border border-slate-800">
                                <i data-lucide="eye" class="w-3" style="width:12px; height:12px;"></i> Músicas
                            </button>
                            <button onclick="deletePlaylistAndRefresh('${pl.id}')" class="px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-xl text-[10px] font-bold uppercase tracking-wider transition flex items-center justify-center">
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
            selectedPlaylistId = playlistId;
            selectedArtist = '';
            activePlaylistAlbum = '';
            document.getElementById('table-view-title').textContent = "Playlist: " + playlistName;
            
            const btns = ['dashboard', 'tracks', 'favorites', 'config', 'videos', 'playlists'];
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

        async function toggleFav(e, trackId) {
            e.stopPropagation();
            try {
                const res = await fetch(API + '?route=favorites_toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: currentUser.username, trackId })
                });
                allFavorites = await res.json();
                renderTracksTable();
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
                            <button onclick="deleteUser('${u.username}')" class="p-1 hover:text-red-400 text-slate-500 transition cursor-pointer ${u.username === 'admin' ? 'hidden' : ''}"><i data-lucide="user-x" class="w-4 h-4"></i></button>
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
                    currentUser = { username: data.username, role: data.role, theme: data.theme || 'default' };
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
    </script>
</body>
</html>
