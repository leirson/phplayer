<?php
@error_reporting(E_ALL);
@ini_set('display_errors', 0);
if (!file_exists('config.php')) {
    die("<div style='font-family:sans-serif;background:#0f172a;color:#f87171;padding:30px;border-radius:12px;margin:50px auto;max-width:600px;border:1px solid #ef444430;'><h3>Arquivo de Configuração Ausente</h3><p>O arquivo <strong>config.php</strong> não foi encontrado na mesma pasta raiz.</p></div>");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>PHPlayer Mobile</title>
    <!-- Tailwind CSS -->
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
                            600: 'var(--theme-sky-600)'
                        },
                        indigo: {
                            500: 'var(--theme-indigo-500)',
                            600: 'var(--theme-indigo-600)'
                        },
                        slate: {
                            950: '#020617',
                            900: '#0f172a',
                            800: '#1e293b',
                            700: '#334155'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap');
        
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

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--theme-bg, #070b13);
            color: #f1f5f9;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            user-select: none;
        }

        /* Safe margins for mobile display with notch */
        .safe-pb {
            padding-bottom: env(safe-area-inset-bottom);
        }

        .safe-bottom-nav {
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* Custom scrollbar for clean visual aesthetic */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(2, 6, 23, 0.4);
        }
        ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 99px;
        }

        /* Ambient rotating vinyl effect when playing */
        @keyframes rotate-vinyl {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-vinyl {
            animation: rotate-vinyl 20s linear infinite;
        }
        .paused-vinyl {
            animation-play-state: paused;
        }
        @keyframes rotate-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: rotate-slow 10s linear infinite;
        }

        /* Input Range styling override to fit Spotify/modern sliders */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 4px;
            background: #1e293b;
            border-radius: 99px;
            outline: none;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #38bdf8;
            cursor: pointer;
            transition: transform 0.1s ease;
        }
        input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.3);
        }

        /* Bottom Sheet Transition classes */
        .bottom-sheet {
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bottom-sheet-hidden {
            transform: translateY(100%);
        }
        .bottom-sheet-visible {
            transform: translateY(0);
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
    </style>
</head>
<body class="h-screen w-full flex flex-col justify-between overflow-hidden select-none safe-pb">

    <!-- INITIAL SPLASH / LOGIN LOADER -->
    <div id="login-screen" class="fixed inset-0 z-50 flex flex-col items-center justify-center p-6 hidden" style="background-color: var(--theme-bg, #070b13);">
        <div class="w-full max-w-sm space-y-8 text-center">
            <div class="flex flex-col items-center gap-4">
                <div class="p-4 bg-sky-500/10 rounded-3xl border border-sky-500/20 text-sky-450 animate-bounce">
                    <i data-lucide="music" class="w-12 h-12"></i>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-white">Subsonic PHP</h1>
                <p class="text-xs text-slate-400">Entre na sua conta para iniciar reprodutor de áudio</p>
            </div>

            <form id="login-form" class="space-y-4 text-left">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-wider font-bold text-sky-400 pl-1">Usuário</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="username" required placeholder="Ex: admin" class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-slate-600 focus:border-sky-500 outline-none transition">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-wider font-bold text-sky-400 pl-1">Senha</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input type="password" id="password" required placeholder="• • • • • •" class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-3 pl-11 pr-4 text-sm text-white placeholder-slate-600 focus:border-sky-500 outline-none transition">
                    </div>
                </div>

                <div id="login-error" class="hidden text-xs text-rose-500 font-semibold bg-rose-500/5 border border-rose-500/10 p-3 rounded-xl">
                    Usuário ou senha incorretos!
                </div>

                <button type="submit" class="w-full py-3.5 bg-sky-500 hover:bg-sky-600 text-white rounded-2xl font-black text-xs uppercase tracking-wider transition shadow-lg shadow-sky-500/10">
                    Acessar Biblioteca
                </button>
            </form>
        </div>
    </div>

    <!-- MAIN APP STRUCTURE -->
    
    <!-- TOP APP BAR -->
    <header class="bg-slate-950/40 border-b border-slate-900/60 p-4 shrink-0 flex items-center justify-between z-10">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-sky-500/10 flex items-center justify-center border border-sky-500/2s text-sky-400">
                <i data-lucide="disc" class="w-4 h-4 text-sky-400"></i>
            </div>
            <div>
                <h1 class="text-xs font-black tracking-tight text-white leading-none">PHPlayer Mobile</h1>
                <p id="top-greeting" class="text-[9px] text-slate-500 font-medium font-mono mt-0.5 uppercase tracking-wide">Olá, Ouvinte</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <!-- Search Button -->
            <button onclick="switchTab('buscar')" class="p-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-white rounded-xl transition cursor-pointer" title="Buscar">
                <i data-lucide="search" class="w-3.5 h-3.5 text-sky-450"></i>
            </button>
            <!-- Settings Button -->
            <button onclick="openConfigSheet()" class="p-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-white rounded-xl transition cursor-pointer" title="Configurações">
                <i data-lucide="settings" class="w-3.5 h-3.5 text-sky-400"></i>
            </button>
            <!-- Admin Scan button -->
            <button id="admin-scan-btn" onclick="triggerLibraryScan()" class="hidden p-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-white rounded-xl transition cursor-pointer" title="Sincronizar Músicas do Servidor">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            </button>
            <!-- Logout Button -->
            <button onclick="handleLogout()" class="p-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-rose-400 hover:text-rose-300 rounded-xl transition cursor-pointer" title="Desconectar">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </header>

    <!-- TOP TAB NAVIGATION BAR -->
    <nav class="bg-slate-950/80 border-b border-slate-900/80 p-2 shrink-0 flex items-center justify-around z-15 select-none overflow-x-auto gap-1 no-scrollbar scroll-smooth">
        
        <!-- Tab Início -->
        <button id="navbtn-inicio" onclick="switchTab('inicio')" class="flex flex-col items-center justify-center p-1.5 px-2.5 text-sky-450 cursor-pointer shrink-0">
            <i data-lucide="home" class="w-4 h-4"></i>
            <span class="text-[9px] font-bold mt-1" data-i18n="m-nav-start">Início</span>
        </button>

        <!-- Tab Álbuns -->
        <button id="navbtn-albuns" onclick="switchTab('albuns')" class="flex flex-col items-center justify-center p-1.5 px-2.5 text-slate-500 hover:text-white cursor-pointer shrink-0">
            <i data-lucide="disc" class="w-4 h-4"></i>
            <span class="text-[9px] font-bold mt-1" data-i18n="m-nav-albums">Álbuns</span>
        </button>

        <!-- Tab Artistas -->
        <button id="navbtn-artistas" onclick="switchTab('artistas')" class="flex flex-col items-center justify-center p-1.5 px-2.5 text-slate-500 hover:text-white cursor-pointer select-none shrink-0">
            <i data-lucide="users" class="w-4 h-4"></i>
            <span class="text-[9px] font-bold mt-1" data-i18n="m-nav-artists">Artistas</span>
        </button>

        <!-- Tab Podcasts -->
        <button id="navbtn-podcast" onclick="switchTab('podcast')" class="flex flex-col items-center justify-center p-1.5 px-2.5 text-slate-500 hover:text-white cursor-pointer shrink-0">
            <i data-lucide="mic" class="w-4 h-4"></i>
            <span class="text-[9px] font-bold mt-1" data-i18n="m-nav-podcasts">Podcasts</span>
        </button>

        <!-- Tab Rádios -->
        <button id="navbtn-radios" onclick="switchTab('radios')" class="flex flex-col items-center justify-center p-1.5 px-2.5 text-slate-500 hover:text-white cursor-pointer shrink-0">
            <i data-lucide="radio" class="w-4 h-4"></i>
            <span class="text-[9px] font-bold mt-1" data-i18n="m-nav-radios">Rádios</span>
        </button>

        <!-- Tab Favoritos -->
        <button id="navbtn-favoritos" onclick="switchTab('favoritos')" class="flex flex-col items-center justify-center p-1.5 px-2.5 text-slate-500 hover:text-white cursor-pointer shrink-0">
            <i data-lucide="heart" class="w-4 h-4"></i>
            <span class="text-[9px] font-bold mt-1" data-i18n="m-nav-favorites">Favoritos</span>
        </button>

    </nav>

    <!-- CONTENT WORKSPACE CONTAINER (SCROLLABLE) -->
    <main class="flex-grow overflow-y-auto px-4 pb-24 pt-2 space-y-6 custom-scroll">
        
        <!-- SECTION 1: INÍCIO (GREETINGS, STATS & RECENT ADDS) -->
        <section id="pane-inicio" class="pane space-y-6">

            <!-- Library Summary stats -->
            <div id="mobile-home-stats" class="grid grid-cols-3 gap-2">
                <div class="bg-[#0b1322]/40 border border-slate-900/80 p-2.5 rounded-2xl text-center">
                    <span id="stat-tracks-count" class="block font-black text-[#ffffff] text-sm font-mono">0</span>
                    <span class="text-[8px] text-slate-500 uppercase tracking-widest font-bold">Músicas</span>
                </div>
                <div class="bg-[#0b1322]/40 border border-slate-900/80 p-2.5 rounded-2xl text-center">
                    <span id="stat-albums-count" class="block font-black text-[#ffffff] text-sm font-mono">0</span>
                    <span class="text-[8px] text-slate-500 uppercase tracking-widest font-bold">Álbuns</span>
                </div>
                <div class="bg-[#0b1322]/40 border border-slate-900/80 p-2.5 rounded-2xl text-center">
                    <span id="stat-artists-count" class="block font-black text-[#ffffff] text-sm font-mono">0</span>
                    <span class="text-[8px] text-slate-500 uppercase tracking-widest font-bold">Artistas</span>
                </div>
            </div>

            <!-- Recommendations container (10 random albums) -->
            <div id="mobile-random-albums-section" class="space-y-3 pb-4">
                <div class="flex items-center justify-between border-b border-slate-900 pb-1.5 select-none">
                    <div>
                        <h4 class="text-[10px] font-black uppercase text-sky-400 tracking-wider">Recomendações</h4>
                        <p class="text-[8px] text-slate-500 uppercase tracking-wider mt-0.5">10 Álbuns Aleatórios</p>
                    </div>
                </div>
                <div id="mobile-random-albums-grid" class="grid grid-cols-2 gap-3">
                    <!-- Updated dynamically every 10 seconds -->
                </div>
            </div>

            <!-- Fila de Reprodução (Visible only when playing) -->
            <div id="mobile-queue-section" class="bg-[#0a111e]/80 border border-slate-900/80 p-3.5 rounded-2xl space-y-2.5 hidden">
                <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="flex h-1.5 w-1.5 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-sky-500"></span>
                        </span>
                        <h4 class="text-[10px] font-black uppercase text-sky-400 tracking-wider" data-i18n="m-sec-queue">Fila de Reprodução</h4>
                    </div>
                    <button onclick="clearMobileQueue()" class="text-[9px] text-slate-500 hover:text-rose-400 font-extrabold uppercase tracking-wider flex items-center gap-1 transition cursor-pointer" data-i18n="m-btn-clear-queue">
                        <i data-lucide="trash-2" class="w-3 h-3"></i> Limpar
                    </button>
                </div>
                <div id="mobile-queue-container" class="max-h-64 overflow-y-auto divide-y divide-slate-900/30 custom-scroll space-y-1 pr-1">
                    <!-- Dynamic Queue list via js -->
                </div>
            </div>

            <!-- Sleep Timer & Equalizer Controls for Mobile -->
            <div id="mobile-addons-section" class="pt-2 space-y-2">
                <button onclick="toggleMobileAddons()" class="w-full flex items-center justify-between p-3.5 bg-[#0a111e]/90 border border-slate-900/80 rounded-2xl text-[10px] font-black uppercase tracking-wider text-sky-400 hover:text-white transition cursor-pointer select-none">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-sky-400 animate-pulse"></i>
                        <span>Sleep & Equalizador</span>
                    </div>
                    <i id="mobile-addons-chevron" data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-300"></i>
                </button>

                <div id="mobile-addons-content" class="hidden space-y-4 pt-1">
                    <!-- Sleep Timer Container -->
                    <div class="bg-[#0a111e]/80 border border-slate-900/80 p-3.5 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-900 pb-2 shadow-sm">
                            <div class="flex items-center gap-1.5">
                                <i id="mobile-sleep-icon" data-lucide="moon" class="w-4 h-4 text-slate-500 transition-all duration-300"></i>
                                <h4 class="text-[10px] font-black uppercase text-sky-450 tracking-wider">Temporizador para Dormir (Sleep)</h4>
                            </div>
                            <div id="mobile-sleep-status" class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Desativado</div>
                        </div>
                        
                        <div class="grid grid-cols-5 gap-1.5 pt-0.5">
                            <button onclick="setMobileSleepTimer(5)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                5 min
                            </button>
                            <button onclick="setMobileSleepTimer(15)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                15 min
                            </button>
                            <button onclick="setMobileSleepTimer(30)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                30 min
                            </button>
                            <button onclick="setMobileSleepTimer(60)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                1h
                            </button>
                            <button onclick="setMobileSleepTimer(null)" class="py-1.5 px-0.5 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20 text-rose-450 rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center font-extrabold pb-1">
                                Parar
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Crossfade Container -->
                    <div class="bg-[#0a111e]/80 border border-slate-900/80 p-3.5 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-900 pb-2 shadow-sm">
                            <div class="flex items-center gap-1.5">
                                <i id="mobile-crossfade-icon" data-lucide="layers" class="w-4 h-4 text-slate-500 transition-all duration-300"></i>
                                <h4 class="text-[10px] font-black uppercase text-sky-450 tracking-wider">Efeito de Crossfade (Transição)</h4>
                            </div>
                            <div id="mobile-crossfade-status" class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Desativado</div>
                        </div>
                        
                        <div class="grid grid-cols-5 gap-1.5 pt-0.5">
                            <button onclick="setMobileCrossfade(2)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                2s
                            </button>
                            <button onclick="setMobileCrossfade(4)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                4s
                            </button>
                            <button onclick="setMobileCrossfade(7)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                7s
                            </button>
                            <button onclick="setMobileCrossfade(10)" class="py-1.5 px-0.5 bg-slate-950/60 border border-slate-900 hover:border-sky-500/50 hover:bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                10s
                            </button>
                            <button onclick="setMobileCrossfade(0)" class="py-1.5 px-0.5 bg-[#ef4444]/15 border border-[#ef4444]/35 hover:bg-rose-500/20 text-[#f87171] rounded-xl text-[9px] font-black uppercase tracking-wider transition cursor-pointer text-center">
                                Parar
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Audio Visualizer Container (WMP Style) -->
                    <div class="bg-[#0a111e]/80 border border-slate-900/80 p-3.5 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                            <div class="flex items-center gap-1.5 animate-pulse">
                                <i data-lucide="activity" class="w-4 h-4 text-sky-400"></i>
                                <h4 class="text-[10px] font-black uppercase text-sky-455 tracking-wider">Visualizador de Música</h4>
                            </div>
                            <!-- Style Switcher -->
                            <div class="flex items-center gap-1 bg-slate-950/80 border border-slate-900/50 p-0.5 rounded-lg shrink-0">
                                <button onclick="setMobileStyle('bars')" id="m-v-bars" class="px-1.5 py-0.5 bg-sky-500/10 text-sky-400 text-[8px] font-black uppercase rounded tracking-wider cursor-pointer">Barras</button>
                                <button onclick="setMobileStyle('scope')" id="m-v-scope" class="px-1.5 py-0.5 text-slate-400 hover:text-white text-[8px] font-black uppercase rounded tracking-wider cursor-pointer">Onda</button>
                                <button onclick="setMobileStyle('beat')" id="m-v-beat" class="px-1.5 py-0.5 text-slate-400 hover:text-white text-[8px] font-black uppercase rounded tracking-wider cursor-pointer">Batida</button>
                            </div>
                        </div>
                        
                        <!-- Mobile Canvas -->
                        <div class="h-40 w-full relative bg-slate-950/95 border border-slate-900/40 rounded-xl overflow-hidden shadow-inner flex items-center justify-center p-0.5">
                            <canvas id="mobile-visualizer-canvas" class="w-full h-full block rounded-lg"></canvas>
                        </div>

                        <!-- Palette Selector -->
                        <div class="flex justify-between items-center gap-2 pt-0.5">
                            <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest leading-none">Paleta:</span>
                            <div class="flex items-center gap-1 bg-slate-950 p-0.5 rounded-lg border border-slate-900/40 shrink-0">
                                <button onclick="setMobileColor('wmp')" id="m-c-wmp" class="px-1.5 py-0.5 text-[8px] font-black uppercase rounded bg-cyan-500/10 text-cyan-405 border border-cyan-500/10 cursor-pointer">WMP</button>
                                <button onclick="setMobileColor('neon')" id="m-c-neon" class="px-1.5 py-0.5 text-[8px] font-black uppercase rounded text-slate-400 hover:text-white cursor-pointer">Neon</button>
                                <button onclick="setMobileColor('fire')" id="m-c-fire" class="px-1.5 py-0.5 text-[8px] font-black uppercase rounded text-slate-400 hover:text-white cursor-pointer">Fogo</button>
                                <button onclick="setMobileColor('cyber')" id="m-c-cyber" class="px-1.5 py-0.5 text-[8px] font-black uppercase rounded text-slate-400 hover:text-white cursor-pointer">Cyber</button>
                            </div>
                        </div>
                    </div>

                    <!-- Equalizer Container (Horizontal Rows) -->
                    <div class="bg-[#0a111e]/80 border border-slate-900/80 p-3.5 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="sliders" class="w-4 h-4 text-sky-400"></i>
                                <h4 class="text-[10px] font-black uppercase text-sky-450 tracking-wider">Equalizador de Áudio</h4>
                            </div>
                            <div class="relative inline-block text-left">
                                <select id="mobile-eq-preset-select" onchange="applyMobilePreset(this.value)" class="bg-slate-950 border border-slate-900 text-slate-300 text-[9px] font-extrabold uppercase tracking-wider rounded-lg px-2 py-1 outline-none focus:border-sky-500 transition pr-4 appearance-none cursor-pointer">
                                    <option value="flat">PRESET: FLAT</option>
                                    <option value="bass">BASS BOOST</option>
                                    <option value="pop">POP</option>
                                    <option value="rock">ROCK</option>
                                    <option value="vocal">VOCAL</option>
                                    <option value="electronic">ELECTRONIC</option>
                                    <option value="suave">SUAVE</option>
                                    <option value="classical">CLASSICAL</option>
                                </select>
                                <span class="absolute inset-y-0 right-1.5 flex items-center pointer-events-none text-slate-500">
                                    <i data-lucide="chevron-down" class="w-2.5 h-2.5"></i>
                                </span>
                            </div>
                        </div>
                        
                        <!-- 5 Band Sliders -->
                        <div class="space-y-2 pt-1">
                            <!-- Band 0 -->
                            <div class="flex items-center gap-3">
                                <span class="w-12 text-[9px] font-bold text-slate-400 uppercase tracking-wider">60 Hz</span>
                                <input type="range" min="-12" max="12" step="1" value="0" id="mobile-eq-band-0" oninput="onMobileEqChange(0, this.value)" class="flex-grow accent-sky-400 h-1.5 bg-slate-950 border border-slate-900 rounded-lg outline-none cursor-pointer">
                                <span id="mobile-eq-gain-val-0" class="w-8 text-[9px] font-mono font-bold text-right text-sky-400">0dB</span>
                            </div>
                            <!-- Band 1 -->
                            <div class="flex items-center gap-3">
                                <span class="w-12 text-[9px] font-bold text-slate-400 uppercase tracking-wider">230 Hz</span>
                                <input type="range" min="-12" max="12" step="1" value="0" id="mobile-eq-band-1" oninput="onMobileEqChange(1, this.value)" class="flex-grow accent-sky-400 h-1.5 bg-slate-950 border border-slate-900 rounded-lg outline-none cursor-pointer">
                                <span id="mobile-eq-gain-val-1" class="w-8 text-[9px] font-mono font-bold text-right text-sky-400">0dB</span>
                            </div>
                            <!-- Band 2 -->
                            <div class="flex items-center gap-3">
                                <span class="w-12 text-[9px] font-bold text-slate-400 uppercase tracking-wider">910 Hz</span>
                                <input type="range" min="-12" max="12" step="1" value="0" id="mobile-eq-band-2" oninput="onMobileEqChange(2, this.value)" class="flex-grow accent-sky-400 h-1.5 bg-slate-950 border border-slate-900 rounded-lg outline-none cursor-pointer">
                                <span id="mobile-eq-gain-val-2" class="w-8 text-[9px] font-mono font-bold text-right text-sky-400">0dB</span>
                            </div>
                            <!-- Band 3 -->
                            <div class="flex items-center gap-3">
                                <span class="w-12 text-[9px] font-bold text-slate-400 uppercase tracking-wider">4 kHz</span>
                                <input type="range" min="-12" max="12" step="1" value="0" id="mobile-eq-band-3" oninput="onMobileEqChange(3, this.value)" class="flex-grow accent-sky-400 h-1.5 bg-slate-950 border border-slate-900 rounded-lg outline-none cursor-pointer">
                                <span id="mobile-eq-gain-val-3" class="w-8 text-[9px] font-mono font-bold text-right text-sky-400">0dB</span>
                            </div>
                            <!-- Band 4 -->
                            <div class="flex items-center gap-3">
                                <span class="w-12 text-[9px] font-bold text-slate-400 uppercase tracking-wider">14 kHz</span>
                                <input type="range" min="-12" max="12" step="1" value="0" id="mobile-eq-band-4" oninput="onMobileEqChange(4, this.value)" class="flex-grow accent-sky-400 h-1.5 bg-slate-950 border border-slate-900 rounded-lg outline-none cursor-pointer">
                                <span id="mobile-eq-gain-val-4" class="w-8 text-[9px] font-mono font-bold text-right text-sky-400">0dB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Recent tracks container to prevent JS errors -->
            <div id="recent-tracks-container" class="hidden"></div>
        </section>

        <!-- SECTION 2: ÁLBUNS (BENTO ALBUM GRID) -->
        <section id="pane-albuns" class="pane space-y-4 hidden">
            <div class="flex items-center justify-between border-b border-slate-900 pb-2">
                <div>
                    <h4 class="text-[10px] font-black uppercase text-slate-300 tracking-wider" data-i18n="m-sec-avail-albums">Álbuns Disponíveis</h4>
                    <p class="text-[9px] text-slate-500" data-i18n="m-sec-avail-sub">Selecione um álbum para reproduzir sua lista de faixas</p>
                </div>
                <button onclick="playRandomMobileAlbum()" class="px-2.5 py-1.5 bg-sky-500 hover:bg-sky-600 active:scale-95 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition flex items-center gap-1 shadow-lg shadow-sky-500/10 cursor-pointer shrink-0" data-i18n="m-btn-random-album">
                    <i data-lucide="shuffle" class="w-3 h-3"></i> Álbum Aleatório
                </button>
            </div>
            
            <div id="albums-grid-container" class="grid grid-cols-2 gap-3.5">
                <!-- Loaded dynamically via JS -->
            </div>
        </section>

        <!-- SECTION 3: BUSCAR (LIVE FILTER TEXT BAR) -->
        <section id="pane-buscar" class="pane space-y-4 hidden">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" id="search-input" oninput="performSearch()" placeholder="Buscar por música, artista ou álbum..." class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-3 pl-11 pr-10 text-xs text-white placeholder-slate-600 focus:border-sky-500 outline-none transition" data-i18n-placeholder="m-search-placeholder">
                <button onclick="clearSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div id="search-results-container" class="space-y-2">
                <div class="text-center text-xs text-slate-600 py-8" data-i18n="m-search-empty">Digite termos para encontrar faixas.</div>
            </div>
        </section>

        <!-- SECTION 4: FAVORITOS -->
        <section id="pane-favoritos" class="pane space-y-4 hidden">
            <div class="border-b border-slate-900 pb-1.5 flex items-center justify-between">
                <div>
                    <h4 class="text-[10px] font-black uppercase text-rose-400 tracking-wider" data-i18n="m-sec-likes">Suas Curtidas</h4>
                    <p class="text-[9px] text-slate-500" data-i18n="m-sec-likes-sub">Músicas adicionadas aos favoritos</p>
                </div>
                <div class="p-1 px-2.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-lg text-[9px] font-bold font-mono" data-i18n="m-tag-favs">
                    FAVORITAS
                </div>
            </div>

            <!-- Play & Shuffle Actions on Favorites -->
            <div id="favorites-actions-mobile" class="flex gap-2 hidden">
                <button onclick="playAllMobileFavorites()" class="flex-1 py-2 bg-rose-500 hover:bg-rose-600 font-bold text-white rounded-xl text-[10px] uppercase tracking-wider flex items-center justify-center gap-1.5 cursor-pointer transition select-none h-9" data-i18n="m-btn-play-all">
                    <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i> Tocar Todas
                </button>
                <button onclick="shuffleAllMobileFavorites()" class="flex-1 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 font-bold rounded-xl text-[10px] uppercase tracking-wider flex items-center justify-center gap-1.5 cursor-pointer transition select-none h-9" data-i18n="m-btn-shuffle">
                    <i data-lucide="shuffle" class="w-3.5 h-3.5 text-sky-400"></i> Ordem Aleatória
                </button>
            </div>

            <div id="favorites-container" class="space-y-1">
                <!-- Loaded dynamically via JS -->
            </div>
        </section>

        <!-- SECTION 5: ARTISTAS -->
        <section id="pane-artistas" class="pane space-y-4 hidden">
            <div class="border-b border-slate-900 pb-1.5 flex items-center justify-between">
                <div>
                    <h4 class="text-[10px] font-black uppercase text-sky-400 tracking-wider" data-i18n="m-sec-artists">Artistas</h4>
                    <p class="text-[9px] text-slate-500" data-i18n="m-sec-artists-sub">Seus artistas e discografias</p>
                </div>
                <div class="p-1 px-2.5 bg-sky-500/10 border border-sky-500/20 text-sky-400 rounded-lg text-[9px] font-bold font-mono" data-i18n="m-tag-catalog">
                    CATÁLOGO
                </div>
            </div>

            <!-- Dynamic Search Bar for Artists -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" id="artists-search-input" oninput="performArtistsSearch()" placeholder="Buscar artista..." class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-3 pl-11 pr-10 text-xs text-white placeholder-slate-600 focus:border-sky-500 outline-none transition">
                <button onclick="clearArtistsSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Bento Artists List -->
            <div id="artists-grid-container" class="grid grid-cols-2 gap-3 pb-6">
                <!-- Loaded dynamically via JS -->
            </div>
        </section>

        <!-- SECTION 6: PODCASTS -->
        <section id="pane-podcast" class="pane space-y-4 hidden">
            <div class="border-b border-slate-900 pb-1.5 flex items-center justify-between">
                <div>
                    <h4 class="text-[10px] font-black uppercase text-orange-400 tracking-wider">Podcasts</h4>
                    <p class="text-[9px] text-slate-500">Canais sincronizados via feed RSS</p>
                </div>
                <div class="p-1 px-2.5 bg-orange-500/10 border border-orange-500/20 text-orange-400 rounded-lg text-[9px] font-bold font-mono">
                    RSS FEED
                </div>
            </div>

            <!-- Admin Podcast Synchronizer Card -->
            <div class="admin-only bg-slate-950/40 p-4 border border-slate-900/60 rounded-2xl space-y-3 shadow-md">
                <h5 class="text-[10px] font-bold text-orange-400 tracking-widest uppercase flex items-center gap-1.5">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Sincronizar Novo Feed
                </h5>
                <div class="space-y-2">
                    <div class="flex flex-col gap-2">
                        <input
                            id="podcast-feed-input"
                            type="text"
                            placeholder="Endereço RSS..."
                            class="w-full bg-slate-900 border border-slate-800/80 focus:border-orange-500 rounded-xl px-3 py-2.5 text-xs outline-none transition font-semibold text-white placeholder-slate-500"
                        />
                        <div class="flex gap-2">
                            <div class="flex-1 flex items-center justify-between gap-2 bg-slate-900 border border-slate-800/80 rounded-xl px-3 py-2 text-xs">
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-wider whitespace-nowrap">Máx:</span>
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
                                class="bg-gradient-to-r from-orange-500 to-amber-600 px-4 font-extrabold text-white rounded-xl text-xs transition flex items-center justify-center cursor-pointer shadow-md"
                            >
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 mr-1"></i> Sincronizar
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Suggestions -->
                <div class="space-y-1.5 pt-1">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block pl-0.5">Sugestões (Podcast Addict):</span>
                    <div class="flex flex-wrap gap-1.5" id="mobile-podcast-suggestions">
                    </div>
                </div>
                <div id="podcast-status-msg" class="p-3 rounded-xl text-[10px] flex items-start gap-2 transition border border-yellow-500/20 bg-yellow-500/5 text-yellow-400 hidden"></div>
            </div>

            <!-- Library of Podcasts -->
            <div class="space-y-3">
                <h5 class="text-[10px] font-black tracking-widest uppercase text-slate-500 pl-1">Biblioteca</h5>
                <div id="podcasts-loading" class="py-10 text-center text-xs text-slate-500 flex flex-col items-center gap-1.5 font-mono">
                    <i data-lucide="loader" class="w-5 h-5 animate-spin text-orange-500"></i>
                    Carregando biblioteca...
                </div>
                <div id="podcasts-empty" class="text-center py-10 bg-slate-950/20 border border-slate-900 border-dashed rounded-2xl space-y-2.5 hidden">
                    <i data-lucide="headphones" class="w-10 h-10 text-slate-700 mx-auto"></i>
                    <p class="text-[11px] text-slate-500">Nenhum canal de Podcast sincronizado ainda.</p>
                </div>
                <div id="podcasts-grid" class="grid grid-cols-2 gap-3 pb-4"></div>
            </div>

            <!-- Detalhes do Canal -->
            <div id="podcast-details" class="bg-slate-950/65 p-4 rounded-2xl border border-slate-900/80 shadow-md space-y-4 hidden animate-fade-in">
                <div class="flex flex-col gap-3 pb-3 border-b border-slate-900/60">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-lg bg-slate-950 overflow-hidden shrink-0 border border-slate-900">
                            <img id="pod-detail-img" src="" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[8px] bg-orange-500/15 text-orange-400 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Episódios</span>
                            <h5 id="pod-detail-name" class="text-xs font-black text-white mt-0.5 truncate">Podcast</h5>
                        </div>
                        <button id="pod-play-all-btn" class="p-2 bg-orange-500 hover:bg-orange-600 text-white rounded-xl transition cursor-pointer flex items-center justify-center shrink-0">
                            <i data-lucide="play" class="w-3.5 h-3.5 fill-white text-white"></i>
                        </button>
                    </div>
                    <div class="admin-only flex items-center gap-2">
                        <div class="flex-1 flex items-center justify-between gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-[10px]">
                            <span class="text-[9px] text-slate-500 uppercase font-black tracking-wider whitespace-nowrap">Máx:</span>
                            <select id="pod-detail-limit" class="bg-transparent font-bold text-white outline-none">
                                <option value="3" class="bg-slate-950 text-white">3</option>
                                <option value="5" class="bg-slate-950 text-white">5</option>
                                <option value="10" class="bg-slate-950 text-white">10</option>
                                <option value="20" class="bg-slate-950 text-white">20</option>
                                <option value="50" class="bg-slate-950 text-white">50</option>
                            </select>
                        </div>
                        <button id="pod-detail-update-btn" class="py-2 px-3 bg-slate-900 hover:bg-slate-850 border border-slate-800 text-orange-400 font-bold rounded-xl text-[10px] flex items-center justify-center gap-1 cursor-pointer transition select-none">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Atualizar
                        </button>
                    </div>
                </div>
                <div id="pod-episodes-list" class="space-y-2 max-h-96 overflow-y-auto no-scrollbar"></div>
            </div>
        </section>

        <!-- SECTION 7: RADIOS -->
        <section id="pane-radios" class="pane space-y-4 hidden">
            <div class="border-b border-slate-900 pb-1.5 flex items-center justify-between">
                <div>
                    <h4 class="text-[10px] font-black uppercase text-emerald-400 tracking-wider">Rádios</h4>
                    <p class="text-[9px] text-slate-500">Estações de rádio on-line compiladas</p>
                </div>
                <div class="p-1 px-2.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-[9px] font-bold font-mono">
                    LIVE STREAM
                </div>
            </div>

            <!-- Admin Emissora Creator Card -->
            <div class="admin-only bg-slate-950/40 p-4 border border-slate-900/60 rounded-2xl space-y-3 shadow-md">
                <h5 class="text-[10px] font-bold text-emerald-400 tracking-widest uppercase flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Cadastrar Nova Emissora
                </h5>
                <form onsubmit="handleAddRadioPhp(event)" class="space-y-2.5">
                    <div class="grid grid-cols-2 gap-2">
                        <input
                            id="radio-name-input"
                            type="text"
                            required
                            placeholder="Nome..."
                            class="w-full bg-slate-900 border border-slate-800/80 focus:border-emerald-500 rounded-xl px-2.5 py-2 text-xs outline-none transition font-semibold text-white placeholder-slate-500"
                        />
                        <input
                            id="radio-url-input"
                            type="text"
                            required
                            placeholder="Stream M3U..."
                            class="w-full bg-slate-900 border border-slate-800/80 focus:border-emerald-500 rounded-xl px-2.5 py-2 text-xs outline-none transition font-semibold text-white placeholder-slate-500"
                        />
                    </div>
                    <button
                        type="submit"
                        id="btn-add-radio"
                        class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 font-extrabold text-white rounded-xl py-2 text-xs tracking-wide transition flex items-center justify-center gap-1.5 cursor-pointer shadow-md"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Adicionar Rádio
                    </button>
                </form>
                
                <!-- Suggestions -->
                <div class="space-y-1.5 pt-1">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block pl-0.5">Sugestões (M3U - radios.com.br):</span>
                    <div class="flex flex-wrap gap-1.5" id="mobile-radio-suggestions">
                    </div>
                </div>
                
                <div id="radio-status-msg" class="p-3 rounded-xl text-[10px] flex items-start gap-2.5 transition border border-yellow-500/20 bg-yellow-500/5 text-yellow-400 hidden"></div>
            </div>

            <!-- Radios Grid -->
            <div class="space-y-3">
                <h5 class="text-[10px] font-black tracking-widest uppercase text-slate-500 pl-1">Emissoras Cadastradas</h5>
                <div id="radios-loading" class="py-10 text-center text-xs text-slate-500 flex flex-col items-center gap-1.5 font-mono">
                    <i data-lucide="loader" class="w-5 h-5 animate-spin text-emerald-500"></i>
                    Carregando emissoras...
                </div>
                <div id="radios-empty" class="text-center py-10 bg-slate-950/20 border border-slate-900 border-dashed rounded-2xl space-y-2.5 hidden">
                    <i data-lucide="radio" class="w-10 h-10 text-slate-700 mx-auto animate-pulse"></i>
                    <p class="text-[11px] text-slate-500">Nenhuma emissora cadastrada ainda.</p>
                </div>
                <div id="radios-grid" class="grid grid-cols-1 gap-3 pb-6"></div>
            </div>
        </section>

        <!-- Hidden Videos container to prevent JS errors -->
        <div id="mobile-videos-container" class="hidden"></div>

    </main>

    <!-- FLOATING ALBUM COMPACT BOTTOM SHEET / COMPONENT -->
    <div id="album-sheet" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden flex items-end">
        <div class="bg-[#0c1424] border-t border-slate-800/60 rounded-t-[2.5rem] w-full max-h-[82vh] flex flex-col bottom-sheet bottom-sheet-hidden shadow-2xl overflow-hidden">
            <!-- Sheet Puller Indicator -->
            <div class="w-12 h-1 bg-slate-700/60 rounded-full mx-auto my-3 shrink-0"></div>

            <div class="flex items-center justify-between px-6 pb-2 border-b border-slate-900/60">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center text-sky-400 shrink-0">
                        <i data-lucide="disc" class="w-4 h-4 text-sky-400"></i>
                    </span>
                    <div>
                        <h4 id="sheet-album-title" class="text-xs font-black text-white truncate max-w-[200px]">Álbum</h4>
                        <p id="sheet-album-artist" class="text-[9px] text-sky-400 font-semibold truncate max-w-[200px]">Artista</p>
                    </div>
                </div>
                <button onclick="closeAlbumSheet()" class="p-1.5 bg-slate-900 hover:bg-slate-850 text-slate-400 hover:text-white rounded-xl text-xs cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Buttons toolbar inside Album Sheet -->
            <div class="p-4 grid grid-cols-2 gap-2 border-b border-slate-900/40 shrink-0">
                <button id="sheet-play-all" class="py-2.5 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-[10px] font-black transition flex items-center justify-center gap-1 cursor-pointer whitespace-nowrap">
                    <i data-lucide="play" class="w-3 h-3 fill-white text-white"></i> Tocar
                </button>
                <button id="sheet-shuffle" class="py-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 rounded-xl text-[10px] font-bold transition flex items-center justify-center gap-1 cursor-pointer whitespace-nowrap">
                    <i data-lucide="shuffle" class="w-3 h-3"></i> Aleatório
                </button>
            </div>

            <!-- List inside sheet -->
            <div class="overflow-y-auto p-4 space-y-1 custom-scroll" id="sheet-tracks-container">
                <!-- Populated dynamically via JS -->
            </div>
        </div>
    </div>

    <!-- FLOATING ARTIST COMPACT BOTTOM SHEET -->
    <div id="artist-sheet" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden flex items-end">
        <div class="bg-[#0c1424] border-t border-slate-800/60 rounded-t-[2.5rem] w-full max-h-[82vh] flex flex-col bottom-sheet bottom-sheet-hidden shadow-2xl overflow-hidden">
            <!-- Sheet Puller Indicator -->
            <div class="w-12 h-1 bg-slate-700/60 rounded-full mx-auto my-3 shrink-0"></div>

            <div class="flex items-center justify-between px-6 pb-2 border-b border-slate-900/60 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center text-sky-400 shrink-0">
                        <i data-lucide="users" class="w-4 h-4 text-sky-400"></i>
                    </span>
                    <div>
                        <h4 id="sheet-artist-title" class="text-xs font-black text-white truncate max-w-[200px]">Artista</h4>
                        <p class="text-[9px] text-sky-450 font-semibold tracking-wider">ÁLBUNS RELACIONADOS</p>
                    </div>
                </div>
                <button onclick="closeArtistSheet()" class="p-1.5 bg-slate-900 hover:bg-slate-850 text-slate-400 hover:text-white rounded-xl text-xs cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Artist Quick Actions -->
            <div class="flex gap-2 px-6 py-2 border-b border-slate-900/40 shrink-0 bg-slate-950/10 select-none">
                <button id="artist-btn-play" class="flex-grow flex-1 py-1.5 px-3 bg-sky-500 hover:bg-sky-600 font-bold text-white rounded-xl text-[10px] uppercase tracking-wider flex items-center justify-center gap-1.5 cursor-pointer transition select-none h-9">
                    <i data-lucide="play" class="w-3.5 h-3.5 fill-current text-white"></i> Play
                </button>
                <button id="artist-btn-random" class="flex-grow flex-1 py-1.5 px-3 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 font-bold rounded-xl text-[10px] uppercase tracking-wider flex items-center justify-center gap-1.5 cursor-pointer transition select-none h-9">
                    <i data-lucide="shuffle" class="w-3.5 h-3.5 text-sky-400"></i> Aleatório
                </button>
            </div>

            <!-- Albums list inside artist sheet in bento format -->
            <div class="overflow-y-auto p-4 grid grid-cols-2 gap-3 custom-scroll" id="sheet-artist-albums-container">
                <!-- Populated dynamically via JS -->
            </div>
        </div>
    </div>

    <!-- FLOATING CONFIGURATION BOTTOM SHEET (THEME & PASSWORD CHANGER) -->
    <div id="config-sheet" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden flex items-end">
        <div class="bg-[#0c1424] border-t border-slate-800/60 rounded-t-[2.5rem] w-full max-h-[85vh] flex flex-col bottom-sheet bottom-sheet-hidden shadow-2xl overflow-hidden">
            <!-- Sheet Puller Indicator -->
            <div class="w-12 h-1 bg-slate-700/60 rounded-full mx-auto my-3 shrink-0"></div>
 
            <div class="flex items-center justify-between px-6 pb-2 border-b border-slate-900/60 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center text-sky-400 shrink-0">
                        <i data-lucide="settings" class="w-4 h-4 text-sky-400"></i>
                    </span>
                    <div>
                        <h4 class="text-xs font-black text-white" data-i18n="m-config-title">Configurações</h4>
                        <p class="text-[9px] text-sky-450 font-semibold tracking-wider uppercase" data-i18n="m-config-sub">TEMA E ALTERAR SENHA</p>
                    </div>
                </div>
                <button onclick="closeConfigSheet()" class="p-1.5 bg-slate-900 hover:bg-slate-850 text-slate-400 hover:text-white rounded-xl text-xs cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
 
            <!-- Content Area inside Config Sheet -->
            <div class="overflow-y-auto p-6 space-y-6 custom-scroll flex-grow pb-12">
                <!-- Theme Selector Subsection -->
                <div class="space-y-3">
                    <h5 class="text-[10px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-sky-400"></i> <span data-i18n="m-theme-title">Tema de Destaque</span>
                    </h5>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="selectMobileTheme('default')" id="mtheme-default" class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition">
                            <span class="w-3.5 h-3.5 rounded-full bg-sky-500 block shrink-0"></span>
                            <span class="text-[10px] text-white font-bold" data-i18n="m-theme-celeste">Celeste</span>
                        </button>
                        <button onclick="selectMobileTheme('emerald')" id="mtheme-emerald" class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition">
                            <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 block shrink-0"></span>
                            <span class="text-[10px] text-white font-bold" data-i18n="m-theme-esmeralda">Esmeralda</span>
                        </button>
                        <button onclick="selectMobileTheme('rose')" id="mtheme-rose" class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition">
                            <span class="w-3.5 h-3.5 rounded-full bg-rose-500 block shrink-0"></span>
                            <span class="text-[10px] text-white font-bold" data-i18n="m-theme-rosa">Rosa</span>
                        </button>
                        <button onclick="selectMobileTheme('amber')" id="mtheme-amber" class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition">
                            <span class="w-3.5 h-3.5 rounded-full bg-amber-500 block shrink-0"></span>
                            <span class="text-[10px] text-white font-bold" data-i18n="m-theme-ambar">Âmbar</span>
                        </button>
                        <button onclick="selectMobileTheme('violet')" id="mtheme-violet" class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition">
                            <span class="w-3.5 h-3.5 rounded-full bg-violet-500 block shrink-0"></span>
                            <span class="text-[10px] text-white font-bold" data-i18n="m-theme-violeta">Violeta</span>
                        </button>
                        <button onclick="selectMobileTheme('crimson')" id="mtheme-crimson" class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition">
                            <span class="w-3.5 h-3.5 rounded-full bg-rose-600 block shrink-0"></span>
                            <span class="text-[10px] text-white font-bold" data-i18n="m-theme-rubi">Rubi</span>
                        </button>
                    </div>
                    <button onclick="saveMobileTheme()" class="w-full mt-3 py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white font-bold rounded-2xl text-[11px] uppercase tracking-wider shadow-lg transition active:scale-98 flex items-center justify-center gap-1.5 cursor-pointer" data-i18n="m-theme-apply">
                        <i data-lucide="check" class="w-4 h-4"></i> Aplicar Tema
                    </button>
                </div>

                <!-- CUSTOM LAYOUT COLOR FOR MOBILE -->
                <div class="space-y-3 pt-4 border-t border-slate-900 text-left">
                    <h5 class="text-[10px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                        <i data-lucide="sliders" class="w-3.5 h-3.5 text-sky-400"></i> Personalizar Cor do Layout
                    </h5>
                    <p class="text-[10px] text-slate-400 leading-normal">
                        Mude a cor de fundo do sidebar, de reprodução e top simultaneamente para sintonizar o seu layout.
                    </p>
                    
                    <!-- Quick Presets -->
                    <div class="flex flex-wrap gap-2 pt-1">
                        <button
                            type="button"
                            onclick="applyMobileLayoutColor('#020617')"
                            class="px-2.5 py-1.5 text-[9px] uppercase tracking-wider font-bold rounded-xl border border-slate-900 bg-slate-100/5 text-slate-300 active:scale-95 transition cursor-pointer"
                        >
                            Slate
                        </button>
                        <button
                            type="button"
                            onclick="applyMobileLayoutColor('#000000')"
                            class="px-2.5 py-1.5 text-[9px] uppercase tracking-wider font-bold rounded-xl border border-slate-900 bg-black text-slate-300 active:scale-95 transition cursor-pointer"
                        >
                            Preto
                        </button>
                        <button
                            type="button"
                            onclick="applyMobileLayoutColor('#09101d')"
                            class="px-2.5 py-1.5 text-[9px] uppercase tracking-wider font-bold rounded-xl border border-slate-900 bg-[#09101d] text-slate-300 active:scale-95 transition cursor-pointer"
                        >
                            Naval
                        </button>
                        <button
                            type="button"
                            onclick="applyMobileLayoutColor('#0c0517')"
                            class="px-2.5 py-1.5 text-[9px] uppercase tracking-wider font-bold rounded-xl border border-slate-900 bg-[#0c0517] text-slate-300 active:scale-95 transition cursor-pointer"
                        >
                            Roxo
                        </button>
                    </div>

                    <!-- Picker Controls -->
                    <div class="flex items-center gap-2 pt-2 bg-slate-950/40 border border-slate-900/60 p-3 rounded-2xl">
                        <input
                            type="color"
                            id="m-layout-bg-picker"
                            oninput="onMobileLayoutBgLiveChange(this.value)"
                            class="w-10 h-7 rounded bg-transparent border-0 cursor-pointer shrink-0"
                        />
                        <input
                            type="text"
                            id="m-layout-bg-text"
                            value="#020617"
                            oninput="onMobileLayoutBgLiveChange(this.value)"
                            class="flex-grow bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-center text-[10px] font-mono text-white"
                        />
                        <button
                            onclick="saveMobileLayoutColor()"
                            class="py-1.5 px-3 bg-sky-500 hover:bg-sky-600 text-white rounded-lg text-[10px] font-black uppercase tracking-wider transition active:scale-95 cursor-pointer"
                        >
                            Salvar
                        </button>
                    </div>
                </div>
 
                <!-- Language Selector Subsection -->
                <div class="space-y-3 pt-4 border-t border-slate-900">
                    <h5 class="text-[10px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                        <i data-lucide="globe" class="w-3.5 h-3.5 text-sky-400"></i> <span data-i18n="m-lang-title">Idioma do Sistema</span>
                    </h5>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button onclick="selectMobileLang('pt')" id="mlang-pt" class="py-2 px-1 rounded-xl bg-slate-950/40 border border-slate-900 flex flex-col items-center justify-center text-center active:scale-95 transition">
                            <span class="text-lg">🇧🇷</span>
                            <span class="text-[9px] text-white font-bold mt-1">Português</span>
                        </button>
                        <button onclick="selectMobileLang('en')" id="mlang-en" class="py-2 px-1 rounded-xl bg-slate-950/40 border border-slate-900 flex flex-col items-center justify-center text-center active:scale-95 transition">
                            <span class="text-lg">🇺🇸</span>
                            <span class="text-[9px] text-white font-bold mt-1">English</span>
                        </button>
                        <button onclick="selectMobileLang('es')" id="mlang-es" class="py-2 px-1 rounded-xl bg-slate-950/40 border border-slate-900 flex flex-col items-center justify-center text-center active:scale-95 transition">
                            <span class="text-lg">🇪🇸</span>
                            <span class="text-[9px] text-white font-bold mt-1">Español</span>
                        </button>
                    </div>
                </div>
 
                <!-- Password Changer Subsection -->
                <div class="space-y-3 pt-4 border-t border-slate-900">
                    <h5 class="text-[10px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-sky-400"></i> <span data-i18n="m-pass-title">Alterar Senha</span>
                    </h5>
                    
                    <form onsubmit="handleMobilePasswordChange(event)" class="space-y-3">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                                <i data-lucide="key-round" class="w-4 h-4"></i>
                            </span>
                            <input type="password" id="mobile-new-password" placeholder="Digite sua nova senha" required class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-2.5 pl-9 pr-4 text-xs text-white placeholder-slate-600 outline-none focus:border-sky-500 transition" data-i18n-placeholder="m-pass-placeholder">
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-2xl text-xs transition active:scale-98" data-i18n="m-pass-btn">
                            Atualizar Senha
                        </button>
                    </form>
                </div>

                <!-- Caching Option Subsection -->
                <div class="space-y-3 pt-4 border-t border-slate-900">
                    <h5 class="text-[10px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                        <i data-lucide="hard-drive" class="w-3.5 h-3.5 text-sky-400"></i> <span data-i18n="m-cache-title">Armazenamento & Cache (Offline)</span>
                    </h5>
                    
                    <div class="space-y-3">
                        <!-- Toggle -->
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/40 border border-slate-900">
                            <div>
                                <span class="text-[10px] text-white font-bold block" data-i18n="m-cache-toggle">Auto-salvar no Cache</span>
                                <span class="text-[8px] text-slate-500">Guarda faixas tocadas para ouvir offline</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="mobile-cache-enabled" class="sr-only peer" onchange="toggleMobileCacheState(this.checked)">
                                <div class="w-9 h-5 bg-slate-900 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 peer-checked:after:bg-sky-400 after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-sky-500/10 peer-checked:border peer-checked:border-sky-500/30"></div>
                            </label>
                        </div>

                        <!-- Fila Cache Selector (Queue Prefetch Auto-download) -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block">Fila Cache (Pré-download automático)</label>
                            <select id="mobile-queue-prefetch-select" class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-2.5 px-3 text-xs text-white outline-none focus:border-sky-500 transition" onchange="changeMobileQueuePrefetch(this.value)">
                                <option value="0">Desativado (Não pré-baixar)</option>
                                <option value="1">1 próxima música</option>
                                <option value="2">2 próximas músicas</option>
                                <option value="3">3 próximas músicas</option>
                                <option value="5">5 próximas músicas</option>
                                <option value="10">10 próximas músicas</option>
                            </select>
                            <span class="text-[8px] text-slate-500 block leading-tight">Quantas músicas da fila o sistema pré-carregará em segundo plano</span>
                        </div>

                        <!-- Selector -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block" data-i18n="m-cache-limit">Limite de Armazenamento</label>
                            <select id="mobile-cache-limit-select" class="w-full bg-slate-950 border border-slate-900 rounded-2xl py-2.5 px-3 text-xs text-white outline-none focus:border-sky-500 transition" onchange="changeMobileCacheLimit(this.value)">
                                <option value="50">50 MB</option>
                                <option value="100">100 MB</option>
                                <option value="250">250 MB</option>
                                <option value="500">500 MB</option>
                                <option value="1000">1 GB</option>
                                <option value="2000">2 GB</option>
                            </select>
                        </div>

                        <!-- Info/Stats -->
                        <div class="p-3 rounded-2xl bg-slate-950/40 border border-slate-900 text-[10px] text-slate-400 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold flex items-center gap-1"><i data-lucide="database" class="w-3.5 h-3.5 text-slate-550"></i> <span data-i18n="m-cache-status">Uso de Armazenamento</span></span>
                                <span id="mobile-cache-usage-lbl" class="font-mono text-sky-400 font-bold">0.0 MB / 250 MB</span>
                            </div>
                            <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                <div id="mobile-cache-progress" class="bg-sky-500 h-1.5 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Clear Cache btn -->
                        <button onclick="clearMobileCacheStorage()" class="w-full py-2.5 bg-red-550/10 hover:bg-red-500/15 border border-red-500/20 active:scale-95 text-red-400 hover:text-red-300 font-bold rounded-2xl text-xs transition flex items-center justify-center gap-1.5 cursor-pointer" data-i18n="m-cache-clear-btn">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Limpar Cache Local
                        </button>
                    </div>
                </div>

                <!-- Servidor DLNA / UPnP Subsection -->
                <div class="space-y-3 pt-4 border-t border-slate-900">
                    <h5 class="text-[10px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5">
                        <i data-lucide="cast" class="w-3.5 h-3.5 text-sky-400"></i> Servidor DLNA (UPnP)
                    </h5>
                    <p class="text-[9px] text-slate-500 leading-tight">
                        Ative o suporte a DLNA no seu servidor PHPlayer para parear e reproduzir músicas e vídeos em Smart TVs, consoles de videogame ou receptores de áudio compatíveis na sua rede doméstica.
                    </p>
                    
                    <div class="space-y-3">
                        <!-- Toggle -->
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/40 border border-slate-900">
                            <div>
                                <span class="text-[10px] text-white font-bold block">Status do Servidor</span>
                                <span class="text-[8px] tracking-wider uppercase font-mono" id="m-dlna-status-indicator">OFFLINE</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="mobile-dlna-enabled" class="sr-only peer" onchange="toggleMobileDlna(this.checked)">
                                <div class="w-9 h-5 bg-slate-900 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 peer-checked:after:bg-sky-400 after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-sky-500/10 peer-checked:border peer-checked:border-sky-500/30 border border-slate-800"></div>
                            </label>
                        </div>

                        <!-- Debian/Ubuntu Requirements & Script Download -->
                        <div class="mt-3 p-3 rounded-2xl bg-slate-950/25 border border-slate-900/60 space-y-2">
                            <span class="text-[9px] uppercase tracking-wider font-extrabold text-sky-400 block flex items-center gap-1">
                                <i data-lucide="terminal" class="w-3 h-3 text-sky-400"></i> Instalação no Debian / Ubuntu
                            </span>
                            <p class="text-[8.5px] text-slate-500 leading-tight">
                                O suporte a DLNA no Debian com PHP & MySQL exige a extensão <code class="text-white font-bold font-mono text-[8px]">sockets</code> ativa no PHP e a liberação de tráfego multicast UDP (porta 1900) e HTTP (porta 3000) no firewall.
                            </p>
                            <div class="bg-slate-950/80 rounded-xl p-2 border border-slate-900 font-mono text-[8px] text-slate-400 space-y-0.5">
                                <p class="text-[7px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">Comandos rápidos SSH:</p>
                                <p class="text-sky-300 break-all select-all">wget -O setup_dlna.sh http://endereço-do-servidor/setup_dlna.sh</p>
                                <p class="text-slate-400">chmod +x setup_dlna.sh</p>
                                <p class="text-slate-400">sudo ./setup_dlna.sh</p>
                            </div>
                            <div class="flex flex-col gap-1.5 pt-1">
                                <a href="setup_dlna.sh" download="setup_dlna.sh" class="w-full text-center py-2 bg-slate-900 hover:bg-slate-850 border border-slate-800 text-[9px] text-white font-bold rounded-xl transition flex items-center justify-center gap-1 select-none active:scale-95 cursor-pointer">
                                    <i data-lucide="download" class="w-3.5 h-3.5 text-sky-400"></i> Baixar setup_dlna.sh
                                </a>
                                <p class="text-[7.5px] text-yellow-500 font-semibold leading-tight flex items-start gap-0.5">
                                    <i data-lucide="alert-triangle" class="w-2.5 h-2.5 shrink-0 text-yellow-500"></i>
                                    <span>Docker: Caso use contêineres, execute com <code class="text-white font-mono bg-slate-950/40 px-0.5 rounded">--network host</code>.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PLAYLIST SELECT / CREATE DIALOG SHEET FOR MOBILE -->
    <div id="playlist-sheet" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden flex items-end">
        <div class="bg-[#0c1424] border-t border-slate-800/60 rounded-t-[2.5rem] w-full max-h-[80vh] flex flex-col bottom-sheet bottom-sheet-hidden shadow-2xl overflow-hidden">
            <!-- Sheet Header -->
            <div class="p-4 border-b border-slate-900/60 shrink-0 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="list-plus" class="w-4 h-4 text-sky-450"></i>
                    <span class="text-xs font-black text-white uppercase tracking-wider">Adicionar à Playlist</span>
                </div>
                <button onclick="closePlaylistSheet()" class="p-1 px-2 text-slate-500 hover:text-white transition cursor-pointer text-[10px] font-bold">
                    Fechar
                </button>
            </div>
            
            <div class="p-4 space-y-4 flex flex-col overflow-y-auto custom-scroll">
                <!-- Create new playlist component -->
                <div class="space-y-2">
                    <label class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Nova Playlist</label>
                    <div class="flex gap-2">
                        <input type="text" id="mobile-new-playlist-input" placeholder="Nome da Playlist..." class="bg-slate-950 border border-slate-900 text-xs px-3 py-2 rounded-xl text-white flex-1 focus:outline-none focus:border-sky-500">
                        <button onclick="createPlaylistFromMobilePlayer()" class="bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-black hover:bg-sky-600 transition cursor-pointer">
                            Criar
                        </button>
                    </div>
                </div>

                <!-- Playlists lists -->
                <div class="space-y-2 flex-grow">
                    <label class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Suas Playlists</label>
                    <div id="mobile-playlists-picker-container" class="space-y-1.5 max-h-60 overflow-y-auto custom-scroll">
                        <p class="text-[10px] text-slate-650 italic">Buscando playlists...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AUDIO PLAYER CORE ENGINE (HTML5 AUDIO ELEMENT) -->
    <audio id="audio-app-element"></audio>

    <!-- PERSISTENT MINI PLAYER BAR (At Bottom, above Nav) -->
    <div id="mini-player" class="fixed bottom-3 left-2 right-2 z-30 bg-[#0e1726]/90 border border-slate-800/80 backdrop-blur-md rounded-2xl p-2.5 flex items-center justify-between shadow-2xl transition duration-300 hidden" onclick="expandFullPlayer()">
        <div class="flex items-center gap-3 select-none flex-grow min-w-0">
            <div class="w-10 h-10 bg-slate-900 rounded-xl overflow-hidden shrink-0 border border-slate-800/40 relative">
                <img id="mini-cover-art" src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100" class="w-full h-full object-cover rounded-xl" referrerpolicy="no-referrer">
                <!-- Pulsing disk -->
                <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                    <div id="mini-pulse-dot" class="w-2 h-2 rounded-full bg-sky-400 hidden animate-ping"></div>
                </div>
            </div>
            <div class="truncate flex-grow min-w-0 pr-2">
                <h4 id="mini-title" class="text-xs font-black text-white leading-tight truncate">Selecione uma faixa</h4>
                <p id="mini-artist" class="text-[9px] text-sky-400 font-semibold truncate mt-0.5">Nenhuma reprodução ativa</p>
                <!-- Mini progress visual bar -->
                <div class="w-full bg-slate-850 h-[2px] rounded-full mt-1.5 overflow-hidden">
                    <div id="mini-progress-filled" class="bg-sky-400 h-full w-0"></div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 shrink-0" onclick="event.stopPropagation()">
            <!-- Back / Previous -->
            <button onclick="playPrevious()" class="p-1 px-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition">
                <i data-lucide="skip-back" class="w-4 h-4 fill-current"></i>
            </button>
            <!-- Play / Pause -->
            <button onclick="togglePlayPause()" id="mini-play-btn" class="p-2 bg-sky-500 text-white rounded-full transition shadow-md shadow-sky-500/10">
                <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
            </button>
            <!-- Next -->
            <button onclick="playNext()" class="p-1 px-1.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition">
                <i data-lucide="skip-forward" class="w-4 h-4 fill-current"></i>
            </button>
        </div>
    </div>

    <!-- FULL SCREEN EXPANDED GLASSMORPHIC PLAYER OVERLAY -->
    <div id="expanded-player" class="fixed inset-0 z-50 overflow-y-auto hidden flex-col p-6 custom-scroll" style="background-color: var(--theme-bg, #070b13); background-image: radial-gradient(circle at 50% 0%, rgba(14, 165, 233, 0.08) 0%, rgba(0,0,0,0) 70%);">
        <!-- Top header of full-screen player -->
        <div class="flex items-center justify-between border-b border-slate-900/65 pb-3 shrink-0">
            <button onclick="collapseFullPlayer()" class="p-2.5 bg-slate-950/60 border border-slate-900 text-slate-400 hover:text-white rounded-2xl transition cursor-pointer">
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            <div class="text-center select-none flex-1 mx-4">
                <p class="text-[8px] text-slate-500 uppercase tracking-widest font-black">REPRODUTOR SUBSONIC</p>
                <p id="full-player-playlist-name" class="text-[10px] text-sky-400 font-bold truncate mt-0.5 max-w-[140px]">Sintonizado</p>
            </div>
            <div class="flex items-center gap-1.5">
                <!-- Add Active Song to favorites -->
                <button id="full-player-fav-btn" onclick="toggleFavoriteActiveSong()" class="p-2.5 bg-slate-950/60 border border-slate-900 text-slate-400 hover:text-white rounded-2xl transition cursor-pointer" title="Favoritar música">
                    <i data-lucide="heart" class="w-4 h-4"></i>
                </button>
                <!-- Add Active Song to playlist -->
                <button onclick="openAddActiveSongToPlaylistSheet()" class="p-2.5 bg-slate-950/60 border border-slate-900 text-sky-400 hover:text-white rounded-2xl transition cursor-pointer" title="Adicionar à Playlist">
                    <i data-lucide="list-plus" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Big Rotating Vinyl Cover Area -->
        <div class="flex-grow flex flex-col items-center justify-center py-6 shrink-0">
            <div class="relative w-56 h-56 sm:w-64 sm:h-64 aspect-square rounded-full bg-slate-950 flex items-center justify-center shadow-2xl border-4 border-slate-900/80 overflow-hidden group">
                <!-- Vinyl center grooves pattern -->
                <div class="absolute inset-1 rounded-full border border-slate-800 opacity-20"></div>
                <div class="absolute inset-4 rounded-full border border-slate-800 opacity-30"></div>
                <div class="absolute inset-8 rounded-full border border-slate-800 opacity-45"></div>
                <div class="absolute inset-12 rounded-full border border-slate-800 opacity-55"></div>
                <div class="absolute inset-16 rounded-full border border-slate-800 opacity-65"></div>
                
                <!-- Album cover art block -->
                <div class="w-[52%] h-[52%] rounded-full overflow-hidden z-10 border-2 border-slate-950 shadow-lg">
                    <img id="full-cover-art" src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400" class="w-full h-full object-cover animate-vinyl paused-vinyl" referrerpolicy="no-referrer">
                </div>

                <!-- Vinyl center pinhole -->
                <div class="absolute w-3 h-3 bg-slate-900 border border-[#070b13] rounded-full z-20"></div>
            </div>

            <!-- Track Meta Info Block -->
            <div class="text-center mt-5 w-full max-w-xs space-y-1 select-none">
                <h3 id="full-title" class="text-sm font-black text-white truncate">Título da Música</h3>
                <p id="full-artist" class="text-[11px] text-sky-400 font-bold truncate">Artista Desconhecido</p>
                <div class="flex items-center justify-center gap-1 pt-1">
                    <span id="full-genre-pill" class="text-[7.5px] font-black bg-[#0c1424]/90 border border-slate-900 text-slate-400 rounded-full px-2 py-0.5 uppercase tracking-widest">Audio</span>
                </div>
            </div>
        </div>

        <!-- Progression and Controls Bottom Block -->
        <div class="space-y-4 shrink-0">
            <!-- Timeline seeking controls -->
            <div class="space-y-1 px-1">
                <input type="range" id="seek-slider" min="0" value="0" step="1" oninput="handleSeekChange(event)" class="w-full h-1 bg-slate-900 rounded-lg appearance-none cursor-pointer accent-sky-500">
                <div class="flex items-center justify-between text-[9px] text-slate-500 font-mono">
                    <span id="time-current">0:00</span>
                    <span id="time-total">3:00</span>
                </div>
            </div>

            <!-- Micro control panel (Shuffle, Skip, Play, Next, Repeat) -->
            <div class="flex items-center justify-between px-2">
                <!-- Shuffle -->
                <button id="full-shuffle-btn" onclick="toggleShuffle()" class="p-2.5 bg-slate-950/40 border border-transparent rounded-2xl text-slate-500 hover:text-white transition cursor-pointer">
                    <i data-lucide="shuffle" class="w-4 h-4"></i>
                </button>

                <!-- Previous -->
                <button onclick="playPrevious()" class="p-3 bg-slate-950 border border-slate-900 text-slate-200 hover:text-white rounded-2xl shadow transition cursor-pointer">
                    <i data-lucide="skip-back" class="w-4 h-4 fill-current"></i>
                </button>

                <!-- Playing toggle circle -->
                <button onclick="togglePlayPause()" id="full-play-btn" class="p-4 bg-sky-500 text-white rounded-full shadow-lg shadow-sky-500/15 transform scale-110 active:scale-95 transition cursor-pointer">
                    <i data-lucide="play" class="w-5 h-5 fill-current text-white" id="full-play-icon"></i>
                </button>

                <!-- Next -->
                <button onclick="playNext()" class="p-3 bg-slate-950 border border-slate-900 text-slate-200 hover:text-white rounded-2xl shadow transition cursor-pointer">
                    <i data-lucide="skip-forward" class="w-4 h-4 fill-current"></i>
                </button>

                <!-- Loop -->
                <button id="full-loop-btn" onclick="toggleLoop()" class="p-2.5 bg-slate-950/40 border border-transparent rounded-2xl text-slate-500 hover:text-white transition cursor-pointer">
                    <i data-lucide="repeat" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Volume Slider Line Row -->
            <div class="flex items-center gap-3 px-3 bg-slate-950/50 border border-slate-900 rounded-2xl py-2 shrink-0">
                <span><i data-lucide="volume-1" class="w-3.5 h-3.5 text-slate-500"></i></span>
                <input type="range" id="volume-slider" min="0" max="1" step="0.05" value="0.8" oninput="handleVolumeChange(event)" class="flex-grow h-1 bg-slate-900 rounded-lg appearance-none cursor-pointer accent-sky-500">
                <span><i data-lucide="volume-2" class="w-3.5 h-3.5 text-slate-500"></i></span>
            </div>

            <!-- INTERACTIVE PLAYBACK QUEUE PANEL BELOW CONTROLS -->
            <div class="pt-3 border-t border-slate-900/60 font-sans select-none pb-4">
                <div class="flex items-center justify-between mb-2 text-[10px] font-bold text-slate-400">
                    <span class="flex items-center gap-1 uppercase tracking-wider"><i data-lucide="list-music" class="w-3.5 h-3.5 text-sky-400"></i> Fila de reprodução</span>
                    <span id="player-queue-count" class="text-[8px] bg-slate-950 text-sky-400 px-2 py-0.5 rounded-full font-mono">0 músicas</span>
                </div>
                <div id="player-queue-list-container" class="space-y-1.5 max-h-40 overflow-y-auto custom-scroll pr-1">
                    <!-- Populated dynamically via renderPlayerQueueList() -->
                    <div class="text-center py-4 text-[10px] text-slate-650">A fila de reprodução está vazia.</div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- VIDEO PLAYER MODAL FOR MOBILE -->
    <div id="video-modal" class="fixed inset-0 bg-black/95 flex items-center justify-center z-50 p-4 hidden">
        <div id="video-modal-container" class="relative w-full max-w-4xl bg-slate-950 border border-slate-900 rounded-3xl overflow-hidden shadow-2xl flex flex-col font-sans">
            <!-- Modal Title bar -->
            <div class="flex items-center justify-between p-4 border-b border-slate-900 bg-[#0d131f]/15">
                <div class="flex items-center gap-2 max-w-[65vw]">
                    <i data-lucide="film" class="w-4 h-4 text-sky-450 shrink-0"></i>
                    <span id="video-modal-title" class="text-xs font-bold text-white truncate">Video</span>
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

    <!-- CORE PLAYBACK SYSTEM IMPLEMENTATION -->
    <script>
        // Core Web Player State variables
        const API = 'api.php';

        window.getAbsoluteUrl = function(urlString) {
            if (!urlString) return urlString;
            if (urlString.startsWith('http://') || urlString.startsWith('https://') || urlString.startsWith('data:') || urlString.startsWith('blob:')) {
                return urlString;
            }
            const serverUrl = localStorage.getItem('mobile_server_url');
            if (!serverUrl) return urlString;
            const cleanServer = serverUrl.replace(/\/+$/, '');
            const delimiter = urlString.startsWith('/') ? '' : '/';
            return cleanServer + delimiter + urlString;
        };

        // Interceptador global do fetch no index.php para propagar o cabeçalho X-Username e resolver a URL absoluta do servidor
        const origFetch = window.fetch;
        try {
            Object.defineProperty(window, 'fetch', {
                value: async function(...args) {
                    let [resource, config] = args;
                    if (typeof resource === 'string' && (resource.includes('api.php') || resource.startsWith('api.php'))) {
                        resource = window.getAbsoluteUrl(resource);
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

        let allTracks = [];
        window.allTracksOriginal = [];
        let allFavorites = [];
        let albumsMap = {};
        let mobileAlbumsPage = 1;
        const mobileAlbumsPageSize = 10;
        let mobileArtistsPage = 1;
        const mobileArtistsPageSize = 10;
        let allVideos = [];
        let activeTab = 'inicio';
        
        // Playlist/Queue states
        let activeQueue = [];
        let activeQueueIdx = -1;
        let isVolumeMuted = false;
        let isShuffleActive = false;
        let isLoopActive = false;

        // Current Profile Session
        let currentUser = null;

        // Document elements cache
        const audio = document.getElementById('audio-app-element');
        const loginScreen = document.getElementById('login-screen');
        const loginForm = document.getElementById('login-form');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginError = document.getElementById('login-error');

        // On document launch initialization
        window.addEventListener('DOMContentLoaded', () => {
            initApp();
        });

        // App Entry Bootstrapper
        function initApp() {
            lucide.createIcons();
            
            // Check for user login stored persistence
            const storedProfile = localStorage.getItem('music_user_profile');
            if (storedProfile) {
                try {
                    currentUser = JSON.parse(storedProfile);
                    bootstrapPlayer();
                } catch (e) {
                    showLogin();
                }
            } else {
                showLogin();
            }

            // Bind native sound audio engine events
            audio.addEventListener('timeupdate', updateAudioProgress);
            audio.addEventListener('durationchange', updateAudioDuration);
            audio.addEventListener('ended', handleTrackEnded);

            // Global Keyboard Shortcuts
            window.addEventListener('keydown', (e) => {
                const target = e.target;
                if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
                    return;
                }
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    playNext();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    playPrevious();
                } else if (e.key === '+' || e.key === '=') {
                    e.preventDefault();
                    const volSlider = document.getElementById('volume-slider');
                    if (audio) {
                        let newVol = Math.min(1, audio.volume + 0.05);
                        audio.volume = newVol;
                        if (volSlider) volSlider.value = newVol;
                    }
                } else if (e.key === '-' || e.key === '_') {
                    e.preventDefault();
                    const volSlider = document.getElementById('volume-slider');
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
                            window.toggleVideoMaximize();
                        } else {
                            window.closeVideoModal();
                        }
                    }
                }
            });

            // Handle standard login submitting
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                loginError.classList.add('hidden');

                const u = usernameInput.value.trim();
                const p = passwordInput.value.trim();

                try {
                    const response = await fetch('api.php?route=login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username: u, password: p })
                    });
                    
                    const data = await response.json();
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    if (data.username) {
                        currentUser = {
                            username: data.username,
                            role: data.role,
                            theme: data.theme || 'default',
                            sidebarBg: data.sidebarBg || '',
                            footerBg: data.footerBg || '',
                            topBg: data.topBg || ''
                        };
                        localStorage.setItem('music_user_profile', JSON.stringify(currentUser));
                        bootstrapPlayer();
                    } else {
                        loginError.classList.remove('hidden');
                    }
                } catch (err) {
                    alert('Erro de rede ao logar: ' + err.message);
                }
            });
        }

        function showLogin() {
            loginScreen.classList.remove('hidden');
        }

        // Initialize user library dashboard
        async function bootstrapPlayer() {
            loginScreen.classList.add('hidden');
            
            const userTheme = (currentUser && currentUser.theme) ? currentUser.theme : 'default';
            if (typeof applyUserTheme === 'function') {
                applyUserTheme(userTheme);
            } else if (window.applyUserTheme) {
                window.applyUserTheme(userTheme);
            }
            if (window.applyUserLayoutBg) {
                window.applyUserLayoutBg();
            }
            
            const activeLang = localStorage.getItem('phplayer_lang') || 'pt';
            if (window.applyMobileLanguage) {
                window.applyMobileLanguage(activeLang);
            }

            // Display admin panel scan triggers if appropriate role
            if (currentUser && currentUser.role === 'admin') {
                document.getElementById('admin-scan-btn').classList.remove('hidden');
            } else {
                document.getElementById('admin-scan-btn').classList.add('hidden');
            }

            document.getElementById('top-greeting').textContent = (activeLang === 'en' ? 'Hello, ' : activeLang === 'es' ? 'Hola, ' : 'Olá, ') + (currentUser.username || 'Ouvinte').toUpperCase();

            // Load catalogs from PHP API
            await loadCatalogData();
            switchTab('inicio');
        }

        window.applyOfflineFilter = function() {
            const isOffline = localStorage.getItem('mobile_offline_mode') === 'true';
            
            // Get offline indicator elements
            let offlineBanner = document.getElementById('mobile-offline-banner');
            if (isOffline) {
                if (!offlineBanner) {
                    offlineBanner = document.createElement('div');
                    offlineBanner.id = 'mobile-offline-banner';
                    offlineBanner.className = 'bg-amber-500/20 text-amber-300 px-4 py-2 text-[10px] font-bold text-center border-b border-amber-500/30 tracking-wider font-mono flex items-center justify-center gap-1.5 animate-pulse shrink-0';
                    offlineBanner.innerHTML = '⚡ MODO OFFLINE ATIVO — APENAS MÚSICAS DISPONÍVEIS OFFLINE';
                    document.body.insertBefore(offlineBanner, document.body.firstChild);
                }
            } else {
                if (offlineBanner) offlineBanner.remove();
            }

            if (isOffline) {
                try {
                    const meta = JSON.parse(localStorage.getItem('mobile_cached_tracks_meta') || '[]');
                    const cachedSet = new Set(meta.map(item => String(item.trackId)));
                    allTracks = window.allTracksOriginal.filter(track => cachedSet.has(String(track.id)));
                } catch (e) {
                    allTracks = [];
                }
            } else {
                allTracks = [...window.allTracksOriginal];
            }

            // Group tracks into Albums Map
            albumsMap = {};
            allTracks.forEach(track => {
                const albumTitle = track.album || 'Álbum Desconhecido';
                if (!albumsMap[albumTitle]) {
                    albumsMap[albumTitle] = {
                        title: albumTitle,
                        artist: track.artist || 'Artista Desconhecido',
                        coverUrl: track.cover_url || track.coverUrl || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=300',
                        tracks: []
                    };
                }
                albumsMap[albumTitle].tracks.push(track);
            });

            // Update compilation albums to say "Vários Artistas"
            Object.values(albumsMap).forEach(alb => {
                const uniqueArtList = new Set(alb.tracks.map(t => t.artist).filter(Boolean));
                if (uniqueArtList.size > 1) {
                    alb.artist = "Vários Artistas";
                }
            });

            // Update stats
            const statTracks = document.getElementById('stat-tracks-count');
            const statAlbums = document.getElementById('stat-albums-count');
            const statArtists = document.getElementById('stat-artists-count');

            if (statTracks) statTracks.textContent = allTracks.length;
            if (statAlbums) statAlbums.textContent = Object.keys(albumsMap).length;

            if (statArtists) {
                const uniqueArtists = new Set();
                allTracks.forEach(t => {
                    if (t.artist) {
                        uniqueArtists.add(t.artist);
                    }
                });
                statArtists.textContent = uniqueArtists.size;
            }

            // Render lists across panes
            renderInicioCatalog();
            renderAlbumsCatalog();
            renderFavoritesCatalog();
            if (typeof renderArtistsCatalog === 'function') {
                renderArtistsCatalog();
            }
            
            lucide.createIcons();
        };

        // Fetch tracks and favorites catalog
        // Fetch tracks and favorites catalog
        async function loadCatalogData() {
            try {
                // Fetch tracks catalog
                let tData;
                try {
                    const tRes = await fetch('api.php?route=tracks');
                    tData = await tRes.json();
                } catch (netErr) {
                    console.warn("API offline - tentando carregar do cache offline:", netErr);
                    try {
                        const meta = JSON.parse(localStorage.getItem('mobile_cached_tracks_meta') || '[]');
                        // Filter the metadata items that have trackData
                        const localTracks = meta.map(m => m.trackData).filter(Boolean);
                        if (localTracks.length > 0) {
                            tData = localTracks;
                        } else {
                            throw netErr;
                        }
                    } catch (e) {
                        throw netErr;
                    }
                }
                
                if (tData && tData.error) {
                    allTracks = [];
                    showCatalogLoadError(tData.error);
                    return;
                }
                
                let rawTracks = [];
                if (Array.isArray(tData)) {
                    rawTracks = tData;
                } else if (tData && Array.isArray(tData.tracks)) {
                    rawTracks = tData.tracks;
                } else {
                    rawTracks = [];
                }
                
                // Normalizar capas relativas para urls absolutas
                rawTracks.forEach(track => {
                    if (track.cover_url) {
                        track.cover_url = window.getAbsoluteUrl(track.cover_url);
                    }
                    if (track.coverUrl) {
                        track.coverUrl = window.getAbsoluteUrl(track.coverUrl);
                    }
                });
                
                window.allTracksOriginal = [...rawTracks];

                // Fetch favorites catalog with protective sub-try block
                if (currentUser) {
                    try {
                        const fRes = await fetch('api.php?route=favorites&username=' + encodeURIComponent(currentUser.username));
                        const fData = await fRes.json();
                        
                        if (Array.isArray(fData)) {
                            allFavorites = fData.map(item => String(item.song_id || item.id || item));
                        } else {
                            allFavorites = [];
                        }
                    } catch (e) {
                        console.error('Falha ao carregar favoritos:', e);
                        allFavorites = [];
                    }
                }

                // Apply offline filter to populate and render the catalogs
                window.applyOfflineFilter();

            } catch (err) {
                console.error('Falha ao processar dados PHP:', err);
                showCatalogLoadError('Erro de requisição / JSON inválido da API. Detalhes: ' + err.message);
            }
        }

        // Show a diagnostic error panel in the player lists when API fails
        function showCatalogLoadError(errorText) {
            const warningHtml = `
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl space-y-2 text-xs">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400"></i>
                        <span>Erro de Banco de Dados / API</span>
                    </div>
                    <p class="text-[10px] leading-relaxed text-slate-400 mt-1">
                        O arquivo <strong>api.php</strong> não retornou os dados de músicas esperados. Verifique se o arquivo <strong>config.php</strong> possui as credenciais de banco de dados corretas ou se você executou a leitura das músicas do servidor.
                    </p>
                    <div class="p-2 bg-slate-950 border border-slate-900 rounded-lg text-[9px] font-mono break-all text-rose-350">
                        ${errorText}
                    </div>
                </div>
            `;
            document.getElementById('recent-tracks-container').innerHTML = warningHtml;
            document.getElementById('albums-container').innerHTML = warningHtml;
            document.getElementById('favorites-container').innerHTML = warningHtml;
            lucide.createIcons();
        }

        function renderMobileQueue() {
            const queueSection = document.getElementById('mobile-queue-section');
            const queueContainer = document.getElementById('mobile-queue-container');
            if (!queueSection || !queueContainer) return;

            const statsEl = document.getElementById('mobile-home-stats');
            const recsEl = document.getElementById('mobile-random-albums-section');

            if (!activeQueue || activeQueue.length === 0) {
                queueSection.classList.add('hidden');
                if (statsEl) statsEl.classList.remove('hidden');
                if (recsEl) recsEl.classList.remove('hidden');
                return;
            }

            queueSection.classList.remove('hidden');
            if (statsEl) statsEl.classList.add('hidden');
            if (recsEl) recsEl.classList.add('hidden');
            
            // Show queue count
            const countEl = document.getElementById('mobile-queue-count');
            if (countEl) {
                const countVal = activeQueue.length;
                const lang = localStorage.getItem('phplayer_lang') || 'pt';
                if (lang === 'en') {
                    countEl.textContent = countVal + " " + (countVal === 1 ? 'track' : 'tracks');
                } else if (lang === 'es') {
                    countEl.textContent = countVal + " " + (countVal === 1 ? 'canción' : 'canciones');
                } else {
                    countEl.textContent = countVal + " " + (countVal === 1 ? 'música' : 'músicas');
                }
            }

            let html = '';
            activeQueue.forEach((track, index) => {
                const isActive = activeQueueIdx === index;
                const isFavorite = allFavorites.includes(String(track.id));
                const badgeStyle = isActive 
                    ? 'border-sky-500 bg-sky-500/10 text-sky-400' 
                    : 'border-slate-800 bg-slate-950/40 text-slate-500';

                html += `
                    <div class="flex items-center justify-between py-2 border-b border-slate-900/10 hover:bg-slate-950/20 rounded-xl px-2 transition ${isActive ? 'bg-sky-500/5' : ''}">
                        <div class="flex items-center gap-3 flex-grow min-w-0" onclick="playFromQueue(${index})">
                            <div class="w-6 h-6 border flex items-center justify-center rounded-lg text-[10px] font-mono font-bold shrink-0 ${badgeStyle}">
                                ${isActive ? '<span class="flex h-1.5 w-1.5 rounded-full bg-sky-400 animate-pulse"></span>' : index + 1}
                            </div>
                            <img src="${track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100'}" class="w-8 h-8 rounded-lg object-cover shrink-0" referrerpolicy="no-referrer">
                            <div class="truncate pr-2 select-none">
                                <h5 class="text-xs font-bold leading-tight ${isActive ? 'text-sky-400' : 'text-slate-100'}">${track.title}</h5>
                                <p class="text-[9px] text-slate-500 truncate mt-0.5">${track.artist || 'Artista Desconhecido'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button onclick="toggleFavEvent(event, '${track.id}')" class="p-1 px-1.5 text-slate-500 hover:text-white transition">
                                <i data-lucide="heart" class="w-3.5 h-3.5 ${isFavorite ? 'text-rose-500 fill-current' : ''}"></i>
                            </button>
                            <button onclick="removeTrackFromQueue(event, ${index})" class="p-1 px-1.5 text-slate-500 hover:text-rose-400 transition" title="Remover da fila">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            queueContainer.innerHTML = html;
            renderPlayerQueueList();
        }

        function renderPlayerQueueList() {
            const container = document.getElementById('player-queue-list-container');
            const countLbl = document.getElementById('player-queue-count');
            if (!container) return;
            
            if (!activeQueue || activeQueue.length === 0) {
                container.innerHTML = '<div class="text-center py-4 text-[9px] text-slate-550">A fila de reprodução está vazia.</div>';
                if (countLbl) countLbl.innerText = '0 músicas';
                return;
            }
            
            if (countLbl) countLbl.innerText = activeQueue.length + (activeQueue.length === 1 ? ' música' : ' músicas');
            
            let html = '';
            activeQueue.forEach((track, index) => {
                const isActive = activeQueueIdx === index;
                const isFavorite = allFavorites.includes(String(track.id));
                const activeCardStyle = isActive 
                    ? 'bg-sky-500/15 border-sky-500/30 text-white shadow-sm' 
                    : 'bg-slate-950/40 border-slate-900 text-slate-400 hover:text-white';
                
                html += `
                    <div class="flex items-center justify-between p-2 rounded-2xl border ${activeCardStyle} transition text-xs">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1 cursor-pointer" onclick="playFromQueue(${index})">
                            <img src="${track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=80'}" class="w-8 h-8 rounded-lg object-cover shrink-0" referrerpolicy="no-referrer">
                            <div class="min-w-0 flex-1 select-none">
                                <span class="block truncate font-bold text-[11px] leading-tight ${isActive ? 'text-sky-400' : 'text-slate-200'}">${track.title}</span>
                                <span class="block truncate text-[9px] text-slate-500 leading-none mt-1">${track.artist || 'Artista Desconhecido'}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            ${isActive ? '<span class="text-[7.5px] bg-sky-500 text-white px-2 py-0.5 rounded-full font-black uppercase tracking-widest mr-1.5 shadow-sm animate-pulse">TOCANDO</span>' : ''}
                            <button onclick="toggleFavEvent(event, '${track.id}')" class="p-1 px-1.5 text-slate-500 hover:text-white transition cursor-pointer">
                                <i data-lucide="heart" class="w-3.5 h-3.5 ${isFavorite ? 'text-rose-500 fill-current' : ''}"></i>
                            </button>
                            <button onclick="removeTrackFromQueue(event, ${index})" class="p-1.5 text-slate-550 hover:text-rose-500 transition cursor-pointer" title="Remover da Fila">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        window.playFromQueue = function(index) {
            if (!activeQueue || index < 0 || index >= activeQueue.length) return;
            activeQueueIdx = index;
            loadAndPlayTrack();
        };

        window.clearMobileQueue = function() {
            audio.pause();
            audio.src = '';
            activeQueue = [];
            activeQueueIdx = -1;
            
            // Hide player bars
            document.getElementById('mini-player').classList.add('hidden');
            const expPlayer = document.getElementById('expanded-player');
            if (expPlayer) {
                expPlayer.classList.add('hidden');
                expPlayer.classList.remove('flex');
            }
            
            // Update queue view and restart catalogs
            renderInicioCatalog();
        };

        window.removeTrackFromQueue = function(event, index) {
            if (event) {
                event.stopPropagation();
            }
            if (!activeQueue || index < 0 || index >= activeQueue.length) return;
            
            if (activeQueueIdx === index) {
                if (activeQueue.length <= 1) {
                    clearMobileQueue();
                    return;
                } else {
                    playNext();
                }
            }
            
            if (index < activeQueueIdx) {
                activeQueueIdx--;
            }
            
            activeQueue.splice(index, 1);
            renderInicioCatalog();
            lucide.createIcons();
        };

        // Render Início contents (Stats & Recommendations)
        function renderInicioCatalog() {
            renderMobileQueue();
            
            // Update Stats
            const statTracks = document.getElementById('stat-tracks-count');
            const statAlbums = document.getElementById('stat-albums-count');
            const statArtists = document.getElementById('stat-artists-count');

            if (statTracks) statTracks.textContent = allTracks.length;
            if (statAlbums) statAlbums.textContent = Object.keys(albumsMap).length;

            if (statArtists) {
                const uniqueArtists = new Set();
                allTracks.forEach(t => {
                    if (t.artist) {
                        uniqueArtists.add(t.artist);
                    }
                });
                statArtists.textContent = uniqueArtists.size;
            }

            // Start or refresh our rotating 10 random albums
            if (typeof startRandomAlbumsRotation === 'function') {
                startRandomAlbumsRotation();
            }
        }

        window.render10RandomAlbums = function() {
            const grid = document.getElementById('mobile-random-albums-grid');
            if (!grid) return;

            const albumKeys = Object.keys(albumsMap);
            if (albumKeys.length === 0) {
                grid.innerHTML = '<div class="col-span-2 text-center text-xs text-slate-650 py-4">Nenhum álbum encontrado.</div>';
                return;
            }

            // Pick 10 random albums
            const shuffled = [...albumKeys].sort(() => 0.5 - Math.random());
            const selected = shuffled.slice(0, 10);

            let html = '';
            selected.forEach(title => {
                const alb = albumsMap[title];
                html += `
                    <div onclick="openAlbumSheet('${encodeURIComponent(title)}')" class="bg-[#0b1322]/40 border border-slate-900/80 p-2 rounded-2xl flex flex-col gap-2 hover:border-sky-500/20 active:scale-95 transition duration-150 cursor-pointer">
                        <img src="${alb.coverUrl}" class="w-full aspect-square object-cover rounded-xl border border-slate-800/40" referrerpolicy="no-referrer" onerror="this.src='https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=300'">
                        <div class="truncate px-1 select-none">
                            <h5 class="text-[10px] font-black leading-tight text-white truncate">${alb.title}</h5>
                            <p class="text-[8px] text-slate-500 truncate mt-0.5">${alb.artist}</p>
                            <span class="text-[8px] font-mono text-sky-400 mt-0.5 block">${alb.tracks.length} ${alb.tracks.length === 1 ? 'música' : 'músicas'}</span>
                        </div>
                    </div>
                `;
            });
            grid.innerHTML = html;
        };

        window.startRandomAlbumsRotation = function() {
            if (window.randomAlbumsTimerStarted) {
                // Pre-render immediately on load/pane switch
                render10RandomAlbums();
                return;
            }
            window.randomAlbumsTimerStarted = true;
            
            // Initial call
            render10RandomAlbums();
            
            // Setup interval (every 10s)
            setInterval(() => {
                // Only rotate when queue is empty/hidden
                if (!activeQueue || activeQueue.length === 0) {
                    render10RandomAlbums();
                }
            }, 10000);
        };

        // Render Bento Grid of Albums
        function renderAlbumsCatalog() {
            const container = document.getElementById('albums-grid-container');
            const albumsKeys = Object.keys(albumsMap);

            if (albumsKeys.length === 0) {
                container.innerHTML = '<div class="col-span-2 text-center text-xs text-slate-650 py-10">Nenhum álbum agrupado.</div>';
                return;
            }

            const totalPages = Math.ceil(albumsKeys.length / mobileAlbumsPageSize);
            if (mobileAlbumsPage > totalPages) {
                mobileAlbumsPage = 1;
            }

            const startIndex = (mobileAlbumsPage - 1) * mobileAlbumsPageSize;
            const endIndex = startIndex + mobileAlbumsPageSize;
            const pageAlbums = albumsKeys.slice(startIndex, endIndex);

            let html = '';
            if (totalPages > 1) {
                html += `
                    <div class="col-span-2 flex items-center justify-between pb-3 border-b border-slate-900/60 mb-2 select-none">
                        <button onclick="changeMobileAlbumPage(${mobileAlbumsPage - 1})" ${mobileAlbumsPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:pointer-events-none text-[10px] font-bold rounded-lg text-slate-400 hover:text-white transition cursor-pointer flex items-center gap-1 h-8">
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Anterior
                        </button>
                        <span class="text-[10px] text-slate-500 font-mono font-bold">Pág. ${mobileAlbumsPage} de ${totalPages}</span>
                        <button onclick="changeMobileAlbumPage(${mobileAlbumsPage + 1})" ${mobileAlbumsPage === totalPages ? 'disabled' : ''} class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:pointer-events-none text-[10px] font-bold rounded-lg text-slate-400 hover:text-white transition cursor-pointer flex items-center gap-1 h-8">
                            Próxima <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                `;
            }

            pageAlbums.forEach(title => {
                const item = albumsMap[title];
                html += `
                    <div onclick="openAlbumSheet('${encodeURIComponent(title)}')" class="bg-slate-950/40 border border-slate-900 p-2.5 rounded-2xl flex flex-col gap-2.5 shadow hover:border-sky-500/20 transition active:scale-95 duration-200">
                        <div class="aspect-square w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-800/40">
                            <img src="${item.coverUrl}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                        </div>
                        <div class="truncate select-none pr-1">
                            <h5 class="text-[11px] font-black leading-tight text-white truncate">${item.title}</h5>
                            <p class="text-[9px] text-slate-500 truncate mt-0.5">${item.artist}</p>
                            <span class="text-[8px] font-mono font-medium text-sky-450 uppercase tracking-widest mt-1 block">
                                ${item.tracks.length} ${item.tracks.length === 1 ? 'música' : 'músicas'}
                            </span>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        window.changeMobileAlbumPage = function(newPage) {
            const albumsKeys = Object.keys(albumsMap);
            const totalPages = Math.ceil(albumsKeys.length / mobileAlbumsPageSize);
            if (newPage < 1 || newPage > totalPages) return;
            mobileAlbumsPage = newPage;
            renderAlbumsCatalog();
            lucide.createIcons();
            
            const pane = document.getElementById('pane-albuns');
            if (pane) pane.scrollTop = 0;
        };

        window.changeMobileArtistPage = function(newPage) {
            mobileArtistsPage = newPage;
            renderArtistsCatalog();
            lucide.createIcons();
            
            const pane = document.getElementById('pane-artistas');
            if (pane) pane.scrollTop = 0;
        };

        // Render Favorites list
        function renderFavoritesCatalog() {
            const container = document.getElementById('favorites-container');
            const favorites = allTracks.filter(track => allFavorites.includes(String(track.id)));
            const actionsBlock = document.getElementById('favorites-actions-mobile');

            if (favorites.length === 0) {
                if (actionsBlock) actionsBlock.classList.add('hidden');
                container.innerHTML = '<div class="text-center text-xs text-slate-600 py-12">Você ainda não curtiu nenhuma música. Marque faixas com o ícone de coração para salvá-las aqui!</div>';
                return;
            }

            if (actionsBlock) actionsBlock.classList.remove('hidden');

            let html = '';
            favorites.forEach(track => {
                const highlight = isTrackCurrentlyPlaying(track.id);

                html += `
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-900/30 hover:bg-slate-950/20 rounded-xl px-2 transition">
                        <div class="flex items-center gap-3 flex-grow min-w-0" onclick="playSingleTrackImmediate('${track.id}')">
                            <img src="${track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100'}" class="w-9 h-9 rounded-lg object-cover shrink-0" referrerpolicy="no-referrer">
                            <div class="truncate pr-2">
                                <h5 class="text-xs font-bold leading-tight ${highlight ? 'text-sky-400' : 'text-slate-100'}">${track.title}</h5>
                                <p class="text-[9px] text-slate-500 truncate mt-0.5">${track.artist || 'Artista Desconhecido'}</p>
                            </div>
                        </div>
                        <button onclick="toggleFavEvent(event, '${track.id}')" class="p-1 px-2 text-rose-500 hover:text-white">
                            <i data-lucide="heart" class="w-3.5 h-3.5 text-rose-500 fill-current hover:scale-110 transition" title="Remover dos favoritos"></i>
                        </button>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Check if track is active playing highlight
        function isTrackCurrentlyPlaying(id) {
            if (activeQueue.length > 0 && activeQueueIdx >= 0) {
                return String(activeQueue[activeQueueIdx].id) === String(id);
            }
            return false;
        }

        // Switch bottom navigational tabs
        function switchTab(tabId) {
            activeTab = tabId;
            
            // Toggle panes visibility
            const panes = ['inicio', 'albuns', 'buscar', 'favoritos', 'artistas', 'podcast', 'radios'];
            panes.forEach(p => {
                const element = document.getElementById('pane-' + p);
                if (element) {
                    if (p === tabId) {
                        element.classList.remove('hidden');
                    } else {
                        element.classList.add('hidden');
                    }
                }
            });

            // Toggle active navigation colors
            panes.forEach(p => {
                const navBtn = document.getElementById('navbtn-' + p);
                if (navBtn) {
                    if (p === tabId) {
                        navBtn.className = "flex flex-col items-center justify-center p-1.5 px-2.5 text-sky-450 cursor-pointer shrink-0";
                    } else {
                        navBtn.className = "flex flex-col items-center justify-center p-1.5 px-2.5 text-slate-500 hover:text-white cursor-pointer shrink-0";
                    }
                }
            });

            // Re-render favorites inside tab if clicked
            if (tabId === 'inicio') {
                renderInicioCatalog();
                lucide.createIcons();
            } else if (tabId === 'favoritos') {
                renderFavoritesCatalog();
                lucide.createIcons();
            } else if (tabId === 'artistas') {
                renderArtistsCatalog();
                lucide.createIcons();
            } else if (tabId === 'podcast') {
                loadPodcastsPhp();
                lucide.createIcons();
            } else if (tabId === 'radios') {
                loadRadiosPhp();
                lucide.createIcons();
            }
        }

        // Perform instant quick text queries
        function performSearch() {
            const input = document.getElementById('search-input');
            const q = input.value.toLowerCase().trim();
            const resultsBox = document.getElementById('search-results-container');

            if (q === '') {
                resultsBox.innerHTML = '<div class="text-center text-xs text-slate-650 py-8">Resultados da pesquisa aparecerão aqui.</div>';
                return;
            }

            const results = allTracks.filter(track => {
                return (track.title || '').toLowerCase().includes(q) || 
                       (track.artist || '').toLowerCase().includes(q) ||
                       (track.album || '').toLowerCase().includes(q) ||
                       (track.genre || '').toLowerCase().includes(q);
            });

            if (results.length === 0) {
                resultsBox.innerHTML = '<div class="text-center text-xs text-slate-550 py-8">Nenhum resultado encontrado.</div>';
                return;
            }

            let html = '';
            results.forEach(track => {
                const highlight = isTrackCurrentlyPlaying(track.id);
                const isFavorite = allFavorites.includes(String(track.id));

                html += `
                    <div class="flex items-center justify-between py-2 border-b border-slate-900/40 hover:bg-slate-950/20 rounded-xl px-2.5 transition">
                        <div class="flex items-center gap-3 flex-grow min-w-0" onclick="playSingleTrackImmediate('${track.id}')">
                            <img src="${track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=100'}" class="w-8 h-8 rounded-lg object-cover shrink-0" referrerpolicy="no-referrer">
                            <div class="truncate pr-2">
                                <h5 class="text-xs font-bold leading-tight ${highlight ? 'text-sky-400' : 'text-slate-100'}">${track.title}</h5>
                                <p class="text-[9px] text-slate-500 truncate mt-0.5">${track.artist || 'Artista Desconhecido'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button onclick="toggleFavEvent(event, '${track.id}')" class="p-1 px-2 text-slate-500 hover:text-white">
                                <i data-lucide="heart" class="w-3.5 h-3.5 ${isFavorite ? 'text-rose-500 fill-current' : ''}"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            resultsBox.innerHTML = html;
            lucide.createIcons();
        }

        function clearSearch() {
            const input = document.getElementById('search-input');
            input.value = '';
            performSearch();
        }

        // Open Bottom Sheet containing all album tracks
        function openAlbumSheet(encodedTitle) {
            const title = decodeURIComponent(encodedTitle);
            const album = albumsMap[title];
            if (!album) return;

            document.getElementById('sheet-album-title').textContent = album.title;
            document.getElementById('sheet-album-artist').textContent = album.artist;

            // Generate tracks inside bottom sheet list
            const container = document.getElementById('sheet-tracks-container');
            let html = '';

            album.tracks.forEach((track, index) => {
                const highlight = isTrackCurrentlyPlaying(track.id);
                
                html += `
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-900/30 hover:bg-slate-950/20 px-2 rounded-xl transition">
                        <div class="flex items-center gap-3.5 flex-grow min-w-0" onclick="playFromAlbumTracks('${encodedTitle}', ${index})">
                            <span class="text-[10px] font-mono text-slate-600 font-bold w-4 text-center ${highlight ? 'text-sky-450' : ''}">${index + 1}</span>
                            <div class="truncate">
                                <h5 class="text-xs font-bold leading-tight ${highlight ? 'text-sky-400 font-extrabold' : 'text-slate-150'}">${track.title}</h5>
                                <p class="text-[9px] text-slate-500 truncate mt-0.5">${track.artist || 'Artista Desconhecido'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pl-2">
                            <span class="text-[9px] text-slate-600 font-mono shrink-0">${formatSecs(track.duration || 180)}</span>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            if (album.tracks.length > 15) {
                container.style.maxHeight = '480px';
                container.style.overflowY = 'auto';
            } else {
                container.style.maxHeight = 'none';
                container.style.overflowY = 'visible';
            }

            // Set up Play All / Shuffle buttons
            document.getElementById('sheet-play-all').onclick = () => {
                playFullQueueList(album.tracks, 0);
                closeAlbumSheet();
            };
            document.getElementById('sheet-shuffle').onclick = () => {
                playFullQueueListShuffled(album.tracks);
                closeAlbumSheet();
            };

            const sheetBg = document.getElementById('album-sheet');
            sheetBg.classList.remove('hidden');
            setTimeout(() => {
                const sheetInner = sheetBg.querySelector('.bottom-sheet');
                sheetInner.classList.remove('bottom-sheet-hidden');
                sheetInner.classList.add('bottom-sheet-visible');
            }, 10);
        }

        function closeAlbumSheet() {
            const sheetBg = document.getElementById('album-sheet');
            const sheetInner = sheetBg.querySelector('.bottom-sheet');
            sheetInner.classList.add('bottom-sheet-hidden');
            sheetInner.classList.remove('bottom-sheet-visible');
            setTimeout(() => {
                sheetBg.classList.add('hidden');
            }, 300);
        }

        // Instant play a single song
        function playSingleTrackImmediate(idStr) {
            const track = allTracks.find(t => String(t.id) === String(idStr));
            if (!track) return;

            activeQueue = [track];
            activeQueueIdx = 0;
            loadAndPlayTrack();
        }

        // Play from sheet contextual tracklist
        function playFromAlbumTracks(encodedTitle, index) {
            const title = decodeURIComponent(encodedTitle);
            const album = albumsMap[title];
            if (!album) return;

            playFullQueueList(album.tracks, index);
        }

        // Set list as active playback queue
        function playFullQueueList(trackList, startIndex = 0) {
            activeQueue = [...trackList];
            activeQueueIdx = startIndex;
            loadAndPlayTrack();
        }

        // Shuffle queue and trigger play
        function playFullQueueListShuffled(trackList) {
            const shuffled = [...trackList].sort(() => Math.random() - 0.5);
            activeQueue = shuffled;
            activeQueueIdx = 0;
            loadAndPlayTrack();
        }

        // core streaming loader
        async function loadAndPlayTrack() {
            if (activeQueue.length === 0 || activeQueueIdx < 0 || activeQueueIdx >= activeQueue.length) return;

            // Go to home page when starting to play a track
            switchTab('inicio');

            const track = activeQueue[activeQueueIdx];
            
            const isRadio = track.artist === 'Rádio On-line' || track.album === 'Sintonizada' || String(track.file_name || track.fileName || "").includes('://');

            if (isRadio) {
                const streamUrl = track.fileName || track.file_name || track.url;
                audio.src = window.getAbsoluteUrl(API + '?route=proxy_radio&url=' + encodeURIComponent(streamUrl));
            } else {
                // Build streaming URL from api with Cache Support
                const rawUrl = window.getAbsoluteUrl(API + '?route=stream&id=' + track.id);
                try {
                    if (window.getAudioSourceCachedAndFetch) {
                        audio.src = await window.getAudioSourceCachedAndFetch(track.id, rawUrl, track);
                    } else {
                        audio.src = rawUrl;
                    }
                } catch (cacErr) {
                    console.error('Erro de Cache, tocando do link direto:', cacErr);
                    audio.src = rawUrl;
                }
            }
            
            // Show bottom mini player bar
            document.getElementById('mini-player').classList.remove('hidden');
            
            // Update Mini Player metadata label UI
            document.getElementById('mini-title').textContent = track.title;
            document.getElementById('mini-artist').textContent = track.artist || 'Artista Desconhecido';
            document.getElementById('mini-cover-art').src = track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=200';
            document.getElementById('mini-pulse-dot').classList.remove('hidden');

            // Update Expanded Full Player metadata UI
            document.getElementById('full-title').textContent = track.title;
            document.getElementById('full-artist').textContent = track.artist || 'Artista Desconhecido';
            document.getElementById('full-cover-art').src = track.cover_url || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=450';
            document.getElementById('full-genre-pill').textContent = track.genre || 'Audio';
            
            // Reset cover spinning rotation
            const coverImg = document.getElementById('full-cover-art');
            coverImg.classList.add('paused-vinyl');
            coverImg.style.animation = 'none';
            coverImg.offsetHeight; // force reflow
            coverImg.style.animation = null;

            // Favorite status on heart icon
            updateFavPillIconStatus(track.id);

            audio.play()
                .then(() => {
                    coverImg.classList.remove('paused-vinyl');
                    updatePlayToggleUIState(true);
                })
                .catch(err => {
                    console.error('Falha de reprodução automática de áudio:', err);
                });

            // Refresh dashboards highlight state colors
            renderInicioCatalog();
            if (activeTab === 'favoritos') {
                renderFavoritesCatalog();
            }
            lucide.createIcons();

            // Fire queue prefetching (Fila Cache) automatically in background
            triggerMobileQueuePrefetch().catch(err => console.warn('[Prefetch Trigger Failed]', err));
        }

        // Toggle music session play/pause state
        function togglePlayPause() {
            if (audio.paused) {
                audio.play()
                    .then(() => {
                        updatePlayToggleUIState(true);
                        document.getElementById('full-cover-art').classList.remove('paused-vinyl');
                        document.getElementById('mini-pulse-dot').classList.remove('hidden');
                    });
            } else {
                audio.pause();
                updatePlayToggleUIState(false);
                document.getElementById('full-cover-art').classList.add('paused-vinyl');
                document.getElementById('mini-pulse-dot').classList.add('hidden');
            }
        }

        function playPrevious() {
            if (activeQueue.length === 0) return;
            activeQueueIdx--;
            if (activeQueueIdx < 0) {
                activeQueueIdx = activeQueue.length - 1; // go to last
            }
            loadAndPlayTrack();
        }

        function playNext() {
            if (activeQueue.length === 0) return;
            
            if (isShuffleActive) {
                activeQueueIdx = Math.floor(Math.random() * activeQueue.length);
            } else {
                activeQueueIdx++;
                if (activeQueueIdx >= activeQueue.length) {
                    activeQueueIdx = 0; // wrap back
                }
            }
            loadAndPlayTrack();
        }

        // Handle native browser ended state
        function handleTrackEnded() {
            if (isLoopActive) {
                audio.currentTime = 0;
                audio.play();
            } else {
                playNext();
            }
        }

        // Update UI control icons based on playback state
        function updatePlayToggleUIState(playing) {
            const miniBtn = document.getElementById('mini-play-btn');
            const fullBtn = document.getElementById('full-play-btn');

            if (playing) {
                miniBtn.innerHTML = '<i data-lucide="pause" class="w-3.5 h-3.5 fill-current"></i>';
                fullBtn.innerHTML = '<i data-lucide="pause" class="w-6 h-6 fill-current text-white"></i>';
            } else {
                miniBtn.innerHTML = '<i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>';
                fullBtn.innerHTML = '<i data-lucide="play" class="w-6 h-6 fill-current text-white"></i>';
            }
            lucide.createIcons();
        }

        // Favorite Toggle functions
        async function toggleFavEvent(e, idStr) {
            e.stopPropagation();
            if (!currentUser) return;

            try {
                const response = await fetch('api.php?route=favorites_toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: currentUser.username, trackId: idStr })
                });
                
                allFavorites = await response.json();
                
                // Refresh visuals
                renderInicioCatalog();
                renderFavoritesCatalog();
                updateFavPillIconStatus(idStr);
                lucide.createIcons();
            } catch (err) {
                console.error('Falha ao alternar favoritos:', err);
            }
        }

        async function toggleFavoriteActiveSong() {
            if (activeQueue.length === 0 || activeQueueIdx < 0) return;
            const track = activeQueue[activeQueueIdx];
            
            // Synthetic click behavior
            const mockEvent = { stopPropagation: () => {} };
            await toggleFavEvent(mockEvent, track.id);
        }

        function updateFavPillIconStatus(idStr) {
            const isFav = allFavorites.includes(String(idStr));
            const favBtn = document.getElementById('full-player-fav-btn');
            if (!favBtn) return;

            if (isFav) {
                favBtn.className = "p-2.5 bg-slate-950/60 border border-slate-900 text-rose-500 rounded-2xl transition cursor-pointer";
                favBtn.innerHTML = '<i data-lucide="heart" class="w-4 h-4 fill-current"></i>';
            } else {
                favBtn.className = "p-2.5 bg-slate-950/60 border border-slate-900 text-slate-400 hover:text-white rounded-2xl transition cursor-pointer";
                favBtn.innerHTML = '<i data-lucide="heart" class="w-4 h-4"></i>';
            }
            lucide.createIcons();
        }

        // Progress timelines & seek controls
        function updateAudioProgress() {
            const total = audio.duration || 0;
            const current = audio.currentTime || 0;
            
            // Avoid sliders glitches on start block
            if (isNaN(total) || total <= 0) return;

            // Updates sliders values
            const percent = (current / total) * 100;
            document.getElementById('mini-progress-filled').style.width = percent + '%';
            
            const slider = document.getElementById('seek-slider');
            slider.max = Math.floor(total);
            slider.value = Math.floor(current);

            // Update timestamps labels text
            document.getElementById('time-current').textContent = formatSecs(current);

            // Auto-scroll lyrics smoothly in PHP mode if lyrics container text exceeds its height
            const lyricsModal = document.getElementById('lyrics-modal');
            const lyricsContent = document.getElementById('lyrics-content');
            if (lyricsModal && !lyricsModal.classList.contains('hidden') && lyricsContent && !audio.paused) {
                const lastScroll = window.phpLyricsLastScroll || 0;
                if (Date.now() - lastScroll > 5000) {
                    const scrollHeight = lyricsContent.scrollHeight;
                    const clientHeight = lyricsContent.clientHeight;
                    if (scrollHeight > clientHeight) {
                        const progress = current / total;
                        const targetScroll = Math.max(0, (scrollHeight - clientHeight) * progress);
                        if (Math.abs(lyricsContent.scrollTop - targetScroll) >= 1.5) {
                            window.phpLyricsIsProgrammaticScroll = true;
                            lyricsContent.scrollTo({
                                top: targetScroll,
                                behavior: 'smooth'
                            });
                        }
                    }
                }
            }
        }

        function updateAudioDuration() {
            const total = audio.duration || 0;
            if (!isNaN(total) && total > 0) {
                document.getElementById('time-total').textContent = formatSecs(total);
                document.getElementById('seek-slider').max = Math.floor(total);
            }
        }

        function handleSeekChange(e) {
            const time = parseFloat(e.target.value);
            if (!isNaN(time)) {
                audio.currentTime = time;
            }
        }

        function handleVolumeChange(e) {
            const vol = parseFloat(e.target.value);
            if (!isNaN(vol)) {
                audio.volume = vol;
            }
        }

        // Toggle Playback shuffle controls
        function toggleShuffle() {
            isShuffleActive = !isShuffleActive;
            const shuffleBtn = document.getElementById('full-shuffle-btn');
            if (isShuffleActive) {
                shuffleBtn.className = "p-2 bg-sky-500/10 border border-sky-500/20 rounded-xl text-sky-400 transition";
            } else {
                shuffleBtn.className = "p-2 bg-slate-950/40 border border-transparent rounded-xl text-slate-500 hover:text-white transition";
            }
        }

        // Toggle repeat loop controls
        function toggleLoop() {
            isLoopActive = !isLoopActive;
            const loopBtn = document.getElementById('full-loop-btn');
            if (isLoopActive) {
                loopBtn.className = "p-2 bg-sky-500/10 border border-sky-500/20 rounded-xl text-sky-400 transition";
            } else {
                loopBtn.className = "p-2 bg-slate-950/40 border border-transparent rounded-xl text-slate-500 hover:text-white transition";
            }
        }

        // Full Interactive HUD layout triggers
        function expandFullPlayer() {
            document.getElementById('expanded-player').classList.remove('hidden');
            document.getElementById('expanded-player').classList.add('flex');
        }

        function collapseFullPlayer() {
            document.getElementById('expanded-player').classList.add('hidden');
            document.getElementById('expanded-player').classList.remove('flex');
        }

        // Library database physical scan trigger for admins
        async function triggerLibraryScan() {
            if (!confirm('Deseja ler e sincronizar a pasta de músicas do servidor agora?')) return;
            
            const btn = document.getElementById('admin-scan-btn');
            btn.classList.add('animate-spin');

            try {
                const response = await fetch('api.php?route=scan', { method: 'POST' });
                const result = await response.json();
                
                btn.classList.remove('animate-spin');
                
                if (result.status === 'ok' || result.success) {
                    alert('Varredura completa com sucesso! Encontradas novas faixas.');
                } else {
                    alert('Resultado da Varredura: ' + JSON.stringify(result));
                }

                // Reload current lists
                await loadCatalogData();
            } catch (err) {
                btn.classList.remove('animate-spin');
                alert('Erro ao varrer diretório de músicas: ' + err.message);
            }
        }

        // Clear persistence details & logout
        window.nativeLogoutUser = function() {
            localStorage.removeItem('music_user_profile');
            currentUser = null;
            const mPlayer = document.getElementById('mini-player');
            if (mPlayer) mPlayer.classList.add('hidden');
            audio.pause();
            showLogin();
        };

        function handleLogout() {
            if (!confirm('Deseja mesmo desconectar-se do player?')) return;
            window.nativeLogoutUser();
        }

        // Formatter utilities helper
        function formatSecs(secs) {
            if (isNaN(secs) || secs < 0) return '0:00';
            const m = Math.floor(secs / 60);
            const s = Math.floor(secs % 60);
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        }

        // Render Bento Grid of Artists with Pagination on Top
        window.renderArtistsCatalog = function() {
            const container = document.getElementById('artists-grid-container');
            if (!container) return;

            // Get query if search input exists
            const queryEl = document.getElementById('artists-search-input');
            const query = queryEl ? queryEl.value.trim().toLowerCase() : '';

            // Group by artist name
            const artistsMapLocal = {};
            allTracks.forEach(track => {
                const artistName = track.artist || 'Artista Desconhecido';

                // If query is present, filter name or track details
                if (query && !artistName.toLowerCase().includes(query)) {
                    return;
                }

                if (!artistsMapLocal[artistName]) {
                    artistsMapLocal[artistName] = {
                        name: artistName,
                        photo: track.cover_url || '',
                        tracksCount: 0,
                        albums: []
                    };
                }
                artistsMapLocal[artistName].tracksCount++;
                if (track.album && !artistsMapLocal[artistName].albums.includes(track.album)) {
                    artistsMapLocal[artistName].albums.push(track.album);
                }
            });

            const artists = Object.values(artistsMapLocal).sort((a, b) => a.name.localeCompare(b.name));
            if (artists.length === 0) {
                container.innerHTML = '<div class="col-span-2 text-center text-xs text-slate-650 py-12">Nenhum artista encontrado no catálogo.</div>';
                return;
            }

            const totalPages = Math.ceil(artists.length / mobileArtistsPageSize);
            if (mobileArtistsPage > totalPages) {
                mobileArtistsPage = 1;
            }

            const startIndex = (mobileArtistsPage - 1) * mobileArtistsPageSize;
            const endIndex = startIndex + mobileArtistsPageSize;
            const pageArtists = artists.slice(startIndex, endIndex);

            let html = '';
            if (totalPages > 1) {
                html += `
                    <div class="col-span-2 flex items-center justify-between pb-3 border-b border-slate-900/60 mb-2 select-none">
                        <button onclick="changeMobileArtistPage(${mobileArtistsPage - 1})" ${mobileArtistsPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:pointer-events-none text-[10px] font-bold rounded-lg text-slate-400 hover:text-white transition cursor-pointer flex items-center gap-1 h-8">
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Anterior
                        </button>
                        <span class="text-[10px] text-slate-500 font-mono font-bold">Pág. ${mobileArtistsPage} de ${totalPages}</span>
                        <button onclick="changeMobileArtistPage(${mobileArtistsPage + 1})" ${mobileArtistsPage === totalPages ? 'disabled' : ''} class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-30 disabled:pointer-events-none text-[10px] font-bold rounded-lg text-slate-400 hover:text-white transition cursor-pointer flex items-center gap-1 h-8">
                            Próxima <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                `;
            }

            pageArtists.forEach(artist => {
                const photoUrl = artist.photo || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=200';
                
                html += `
                    <div onclick="openArtistSheet('${encodeURIComponent(artist.name)}')" class="bg-slate-950/40 border border-slate-900 p-3 rounded-2xl flex flex-col items-center text-center gap-3 shadow hover:border-sky-500/20 active:scale-95 transition">
                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-slate-800 bg-slate-900 shrink-0">
                            <img src="${photoUrl}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                        </div>
                        <div class="truncate w-full pr-1">
                            <h5 class="text-xs font-black leading-tight text-white truncate">${artist.name}</h5>
                            <p class="text-[9px] text-slate-500 truncate mt-0.5">${artist.albums.length} ${artist.albums.length === 1 ? 'álbum' : 'álbuns'}</p>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        };

        window.performArtistsSearch = function() {
            mobileArtistsPage = 1;
            renderArtistsCatalog();
            lucide.createIcons();
        };

        window.clearArtistsSearch = function() {
            const input = document.getElementById('artists-search-input');
            if (input) {
                input.value = '';
            }
            performArtistsSearch();
        };

        // Render Videos in Mobile Tab
        async function renderMobileVideos() {
            const container = document.getElementById('mobile-videos-container');
            if (!container) return;

            container.innerHTML = '<div class="col-span-1 text-center text-xs text-slate-650 py-12"><i class="w-6 h-6 animate-spin mx-auto text-sky-500"></i><p class="mt-2">Carregando vídeos...</p></div>';

            try {
                const res = await fetch(API + '?route=videos');
                allVideos = await res.json();

                if (allVideos.length === 0) {
                    container.innerHTML = '<div class="col-span-1 text-center text-xs text-slate-650 py-12">Nenhum vídeo encontrado no catálogo.</div>';
                    return;
                }

                let html = '';
                allVideos.forEach(vid => {
                    const coverImg = vid.coverUrl || 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=350';
                    const sizeMB = (vid.fileSize / (1024 * 1024)).toFixed(1);

                    html += `
                        <div onclick="playVideo('${vid.id}')" class="bg-[#0e1726]/40 border border-slate-900 p-3 rounded-2xl flex flex-col gap-3 shadow hover:border-sky-500/20 active:scale-95 transition duration-200">
                            <div class="relative aspect-video w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-800/40">
                                <img src="${coverImg}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                                <div class="absolute inset-0 bg-black/45 flex items-center justify-center">
                                    <span class="p-2.5 bg-sky-500 text-white rounded-full shadow-lg">
                                        <i data-lucide="play" class="w-4 h-4 fill-white text-white"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="px-1 select-none">
                                <h5 class="text-xs font-bold leading-tight text-white pr-1 truncate">${vid.title}</h5>
                                <div class="flex justify-between items-center mt-1 text-[8px] text-slate-500 font-mono">
                                    <span>${sizeMB} MB</span>
                                    <span class="text-sky-455 font-bold">VÍDEO</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
                lucide.createIcons();
            } catch (err) {
                console.error(err);
                container.innerHTML = '<div class="col-span-1 text-center text-xs text-red-500 py-12">Falha ao carregar a galeria de vídeos.</div>';
            }
        }

        window.playVideo = function(id) {
            const vid = allVideos.find(v => v.id === id);
            if (!vid) return;

            if (audio && !audio.paused) {
                audio.pause();
                const miniBtn = document.getElementById('mini-play-btn');
                const fullBtn = document.getElementById('full-play-btn');
                if (miniBtn) miniBtn.innerHTML = '<i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>';
                if (fullBtn) fullBtn.innerHTML = '<i data-lucide="play" class="w-6 h-6 fill-current text-white"></i>';
                lucide.createIcons();
            }

            const player = document.getElementById('modal-video-player');
            if (player) {
                player.src = window.getAbsoluteUrl('api.php?route=stream_video&id=' + encodeURIComponent(vid.id));
                if (vid.coverUrl) {
                    player.poster = window.getAbsoluteUrl(vid.coverUrl);
                } else {
                    player.removeAttribute('poster');
                }
            }

            const modalTitle = document.getElementById('video-modal-title');
            if (modalTitle) modalTitle.textContent = vid.title;

            const modal = document.getElementById('video-modal');
            if (modal) modal.classList.remove('hidden');
        };

        window.closeVideoModal = function() {
            const player = document.getElementById('modal-video-player');
            if (player) {
                player.pause();
                player.removeAttribute('src');
                player.load();
            }
            const modal = document.getElementById('video-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('pseudo-fullscreen-active');
            }
            const btn = document.getElementById('video-maximize-btn');
            if (btn) btn.innerHTML = '<i data-lucide="maximize" class="w-4 h-4"></i>';
            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

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

        function renderPodcastSuggestionsMobile() {
            const container = document.getElementById('mobile-podcast-suggestions');
            if (!container) return;
            const selected = PODCAST_PRESETS_POOL;
            let html = '';
            selected.forEach(item => {
                html += '<button onclick="setPodcastFeedPreset(\'' + item.url + '\')" class="bg-slate-900 border border-slate-800/40 hover:bg-slate-800 text-slate-300 px-2 py-1 rounded-lg transition text-[9px] cursor-pointer inline-flex items-center gap-0.5"><i data-lucide="plus" class="w-2.5 h-2.5 text-orange-400"></i> ' + item.name + '</button>';
            });
            container.innerHTML = html;
            if (window.lucide) window.lucide.createIcons();
        }

        function renderRadioSuggestionsMobile() {
            const container = document.getElementById('mobile-radio-suggestions');
            if (!container) return;
            const selected = RADIO_PRESETS_POOL;
            let html = '';
            selected.forEach(item => {
                html += '<button onclick="setRadioPreset(\'' + item.name + '\', \'' + item.url + '\')" class="bg-slate-900 border border-slate-800/40 hover:bg-slate-800 text-slate-300 px-2 py-1 rounded-lg transition text-[9px] cursor-pointer inline-flex items-center gap-0.5"><i data-lucide="plus" class="w-2.5 h-2.5 text-emerald-400"></i> ' + item.name + '</button>';
            });
            container.innerHTML = html;
            if (window.lucide) window.lucide.createIcons();
        }

        let allPodcasts = [];

        async function loadPodcastsPhp() {
            renderPodcastSuggestionsMobile();
            const loadingEl = document.getElementById('podcasts-loading');
            const emptyEl = document.getElementById('podcasts-empty');
            const gridEl = document.getElementById('podcasts-grid');
            const detailsEl = document.getElementById('podcast-details');

            loadingEl.classList.remove('hidden');
            emptyEl.classList.add('hidden');
            gridEl.innerHTML = '';
            detailsEl.classList.add('hidden');

            try {
                const res = await fetch(API + '?route=podcasts');
                allPodcasts = await res.json();
                loadingEl.classList.add('hidden');

                if (allPodcasts.length === 0) {
                    emptyEl.classList.remove('hidden');
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
                    gridEl.appendChild(card);
                });
                lucide.createIcons();
            } catch (err) {
                console.error(err);
                loadingEl.classList.add('hidden');
                gridEl.innerHTML = '<div class="col-span-1 text-center text-xs text-red-500 py-12">Falha ao ler os podcasts sincronizados.</div>';
            }
        }
        window.loadPodcastsPhp = loadPodcastsPhp;

        function showPodcastDetailsPhp(podName) {
            const pod = allPodcasts.find(p => p.name === podName);
            if (!pod) return;

            const detailsEl = document.getElementById('podcast-details');
            document.getElementById('pod-detail-name').textContent = pod.name;
            document.getElementById('pod-detail-img').src = pod.coverUrl || 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?w=150';

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

            const listEl = document.getElementById('pod-episodes-list');
            listEl.innerHTML = '';

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
                listEl.appendChild(epEl);
            });

            const playAllBtn = document.getElementById('pod-play-all-btn');
            playAllBtn.onclick = () => {
                const tracksToPlay = pod.episodes.map(convertPodcastEpisodeToPlayerTrack);
                playQueuePhp(tracksToPlay, 0);
            };

            detailsEl.classList.remove('hidden');
            detailsEl.scrollIntoView({ behavior: 'smooth' });
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
                cover_url: ep.coverUrl,
                file_name: ep.fileName,
                file_size: ep.fileSize
            };
        }

        window.playPodcastEpisodePhp = function(podName, epId) {
            const pod = allPodcasts.find(p => p.name === podName);
            if (!pod) return;
            const targetIdx = pod.episodes.findIndex(e => e.id === epId);
            const tracksToPlay = pod.episodes.map(convertPodcastEpisodeToPlayerTrack);
            playQueuePhp(tracksToPlay, targetIdx !== -1 ? targetIdx : 0);
        };

        function playQueuePhp(queue, startIndex) {
            if (!queue || queue.length === 0) return;
            activeQueue = queue;
            activeQueueIdx = startIndex;
            loadAndPlayTrack();
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

            const feedUrl = feedUrlOverride || (input ? input.value.trim() : '');
            if (!feedUrl) return;

            const maxEpisodesVal = maxEpisodesOverride !== null ? maxEpisodesOverride : (limitSelect ? parseInt(limitSelect.value) : 5);

            const statusEl = document.getElementById('podcast-status-msg');
            if (statusEl) {
                statusEl.classList.remove('hidden');
                statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-slate-900 border border-slate-800 text-slate-300";
                statusEl.innerHTML = '<span class="flex items-center gap-2"><i data-lucide="loader" class="w-4 h-4 animate-spin text-orange-500"></i> Sincronizando e baixando os episódios no PHP... Aguarde.</span>';
            }
            if (window.lucide) lucide.createIcons();

            btn.disabled = true;
            if (input) input.disabled = true;

            try {
                const res = await fetch(API + '?route=podcasts_sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Username': currentUser.username
                    },
                    body: JSON.stringify({ feedUrl, maxEpisodes: maxEpisodesVal })
                });

                const data = await res.json();
                btn.disabled = false;
                if (input) input.disabled = false;

                if (res.ok && data.success) {
                    if (statusEl) {
                        statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-emerald-950/20 border border-emerald-900/30 text-emerald-400";
                        statusEl.innerHTML = '<i data-lucide="check" class="w-4 h-4 shrink-0"></i> Sincronizado com sucesso! Podcast "' + data.podcastName + '" atualizado.';
                    }
                    if (input && !feedUrlOverride) input.value = '';
                    
                    await loadPodcastsPhp();

                    if (data.podcastName && window.showPodcastDetailsPhp) {
                        window.showPodcastDetailsPhp(data.podcastName);
                    }
                } else {
                    if (statusEl) {
                        statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-red-950/20 border border-red-900/30 text-red-100";
                        statusEl.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> Falha: ' + (data.error || 'Erro na sincronização.');
                    }
                }
            } catch (err) {
                btn.disabled = false;
                if (input) input.disabled = false;
                if (statusEl) {
                    statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-red-950/20 border border-red-900/30 text-red-100";
                    statusEl.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> Erro de rede ao sincronizar.';
                }
            }
            if (window.lucide) {
                lucide.createIcons();
            }
        };

        let allRadios = [];

        async function loadRadiosPhp() {
            renderRadioSuggestionsMobile();
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

                const currentTrack = (activeQueueIdx >= 0 && activeQueueIdx < activeQueue.length) ? activeQueue[activeQueueIdx] : null;

                allRadios.forEach(radio => {
                    const card = document.createElement('div');
                    const cleanLink = radio.url.toLowerCase();
                    let formatLabel = "STREAM";
                    if (cleanLink.includes('.m3u')) formatLabel = "M3U";
                    else if (cleanLink.includes('.pls')) formatLabel = "PLS";
                    else if (cleanLink.includes('.asx')) formatLabel = "ASX";

                    const isCurrentRadio = currentTrack && currentTrack.id === radio.id && (currentTrack.artist === 'Rádio On-line' || currentTrack.album === 'Sintonizada') && !audio.paused;

                    card.id = "radio-card-" + radio.id;
                    card.className = "group relative bg-slate-950/45 hover:bg-slate-900/50 border rounded-2xl p-4 transition-all duration-300 transform hover:-translate-y-0.5 cursor-pointer flex flex-col justify-between h-36 " +
                        (isCurrentRadio ? "border-emerald-500 shadow-lg shadow-emerald-500/5" : "border-slate-900/90 hover:border-slate-800");

                    card.onclick = () => playRadioPhp(radio.id, radio.name, radio.resolved_url || radio.url);

                    const deleteBtnHtml = (currentUser && currentUser.role === 'admin') ? `<button onclick="handleDeleteRadioPhp('${radio.id}', event)" class="p-1.5 text-slate-600 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition" title="Remover Rádio"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>` : '';

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

                    gridEl.appendChild(card);
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
                alert("Apenas administradores podem gerenciar canais de rádio.");
                return;
            }

            const nameInput = document.getElementById('radio-name-input');
            const urlInput = document.getElementById('radio-url-input');
            const btn = document.getElementById('btn-add-radio');
            const statusEl = document.getElementById('radio-status-msg');

            const name = nameInput.value.trim();
            const url = urlInput.value.trim();

            if (!name || !url) return;

            if (statusEl) {
                statusEl.classList.remove('hidden');
                statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-slate-900 border border-slate-800 text-slate-300";
                statusEl.innerHTML = '<span class="flex items-center gap-2"><i data-lucide="loader" class="w-4 h-4 animate-spin text-emerald-500"></i> Resolvendo playlist e salvando rádio no PHP...</span>';
                lucide.createIcons();
            }

            btn.disabled = true;

            try {
                const res = await fetch(API + '?route=radios', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Username': currentUser.username
                    },
                    body: JSON.stringify({ name, url })
                });

                const data = await res.json();
                btn.disabled = false;

                if (res.ok && data.success) {
                    nameInput.value = '';
                    urlInput.value = '';
                    if (statusEl) {
                        statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-emerald-950/20 border border-emerald-900/30 text-emerald-400";
                        statusEl.innerHTML = '<i data-lucide="check" class="w-4 h-4 shrink-0"></i> Rádio "' + data.radio.name + '" adicionada com sucesso!';
                    }
                    loadRadiosPhp();
                } else {
                    if (statusEl) {
                        statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-red-950/20 border border-red-900/30 text-red-100";
                        statusEl.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> Erro: ' + (data.error || 'Erro ao sincronizar rádio.');
                    }
                }
            } catch (err) {
                btn.disabled = false;
                if (statusEl) {
                    statusEl.className = "mt-4 p-4 rounded-xl text-xs flex items-center gap-2.5 bg-red-950/20 border border-red-900/30 text-red-100";
                    statusEl.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> Erro de rede ao sincronizar.';
                }
            }
            lucide.createIcons();
        };

        window.handleDeleteRadioPhp = async function(id, e) {
            if (e) e.stopPropagation();
            if (currentUser.role !== 'admin') return;

            if (!confirm("Deseja realmente remover esta sintonização de rádio?")) return;

            try {
                const res = await fetch(API + '?route=radios_delete&id=' + encodeURIComponent(id), {
                    method: 'DELETE',
                    headers: {
                        'X-Username': currentUser.username
                    }
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
            
            // Set queue & indices
            activeQueue = [radioTrack];
            activeQueueIdx = 0;
            
            loadAndPlayTrack();
            
            // Quick UI update to highlight the clicked card
            loadRadiosPhp();
        };

        window.toggleVideoMaximize = function() {
            const modal = document.getElementById('video-modal');
            const btn = document.getElementById('video-maximize-btn');
            if (!modal) return;
            if (modal.classList.contains('pseudo-fullscreen-active')) {
                modal.classList.remove('pseudo-fullscreen-active');
                if (btn) btn.innerHTML = '<i data-lucide="maximize" class="w-4 h-4"></i>';
            } else {
                modal.classList.add('pseudo-fullscreen-active');
                if (btn) btn.innerHTML = '<i data-lucide="minimize" class="w-4 h-4"></i>';
            }
            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        window.openArtistSheet = function(encodedArtistName) {
            const artistName = decodeURIComponent(encodedArtistName);
            const sheet = document.getElementById('artist-sheet');
            if (!sheet) return;

            document.getElementById('sheet-artist-title').textContent = artistName;

            // Bind quick actions
            const playBtn = document.getElementById('artist-btn-play');
            if (playBtn) {
                playBtn.onclick = () => {
                    const tracks = allTracks.filter(t => t.artist === artistName);
                    if (tracks.length === 0) return;
                    closeArtistSheet();
                    playFullQueueList(tracks);
                };
            }

            const randomBtn = document.getElementById('artist-btn-random');
            if (randomBtn) {
                randomBtn.onclick = () => {
                    const artistAlbums = [];
                    Object.values(albumsMap).forEach(album => {
                        if (album.artist === artistName) {
                            artistAlbums.push(album);
                        }
                    });
                    if (artistAlbums.length === 0) return;
                    const randomAlb = artistAlbums[Math.floor(Math.random() * artistAlbums.length)];
                    closeArtistSheet();
                    setTimeout(() => {
                        openAlbumSheet(encodeURIComponent(randomAlb.title));
                    }, 300);
                };
            }

            // Render all albums of this artist
            const albumsContainer = document.getElementById('sheet-artist-albums-container');
            
            // Filter albums belonging to this artist
            const artistAlbums = [];
            Object.values(albumsMap).forEach(album => {
                if (album.artist === artistName) {
                    artistAlbums.push(album);
                }
            });

            if (artistAlbums.length === 0) {
                albumsContainer.innerHTML = '<div class="col-span-2 text-center text-xs text-slate-650 py-6">Nenhum álbum encontrado para este artista.</div>';
            } else {
                let html = '';
                artistAlbums.forEach(album => {
                    html += `
                        <div onclick="closeArtistSheet(); setTimeout(() => openAlbumSheet('${encodeURIComponent(album.title)}'), 300)" class="bg-slate-950/50 border border-slate-900/60 p-2.5 rounded-2xl flex flex-col gap-2.5 cursor-pointer active:scale-95 transition">
                            <img src="${album.coverUrl}" class="w-full aspect-square object-cover rounded-xl border border-slate-800/40" referrerpolicy="no-referrer">
                            <div class="truncate select-none px-1">
                                <h6 class="text-[10px] font-black text-white truncate leading-tight">${album.title}</h6>
                                <p class="text-[8px] text-slate-500 mt-0.5 truncate">${album.tracks.length} músicas</p>
                            </div>
                        </div>
                    `;
                });
                albumsContainer.innerHTML = html;
            }

            sheet.classList.remove('hidden');
            setTimeout(() => {
                const sheetInner = sheet.querySelector('.bottom-sheet');
                if (sheetInner) {
                    sheetInner.classList.remove('bottom-sheet-hidden');
                }
            }, 50);
        };

        window.closeArtistSheet = function() {
            const sheet = document.getElementById('artist-sheet');
            if (!sheet) return;
            const sheetInner = sheet.querySelector('.bottom-sheet');
            if (sheetInner) {
                sheetInner.classList.add('bottom-sheet-hidden');
            }
            setTimeout(() => {
                sheet.classList.add('hidden');
            }, 300);
        };

        // Cache Management Functions
        function getCacheMeta() {
            try {
                return JSON.parse(localStorage.getItem('mobile_cached_tracks_meta') || '[]');
            } catch (e) {
                return [];
            }
        }

        function saveCacheMeta(metaList) {
            localStorage.setItem('mobile_cached_tracks_meta', JSON.stringify(metaList));
            window.updateCacheSettingsUI();
        }

        function updateTrackLastUsedTime(trackId) {
            let meta = getCacheMeta();
            const idx = meta.findIndex(m => String(m.trackId) === String(trackId));
            if (idx !== -1) {
                meta[idx].lastUsedAt = Date.now();
                localStorage.setItem('mobile_cached_tracks_meta', JSON.stringify(meta));
            }
        }

        async function fetchAndSaveToCache(trackId, rawUrl, fullTrackObj = null) {
            try {
                const cache = await caches.open('audio-tracks-cache');
                let meta = getCacheMeta();
                if (meta.some(m => String(m.trackId) === String(trackId))) {
                    return;
                }

                const response = await fetch(rawUrl);
                if (!response.ok) return;

                const blob = await response.blob();
                await cache.put(rawUrl, new Response(blob));

                const newItem = {
                    trackId: trackId,
                    url: rawUrl,
                    sizeInBytes: blob.size,
                    addedAt: Date.now(),
                    lastUsedAt: Date.now(),
                    trackData: fullTrackObj
                };
                meta.push(newItem);
                saveCacheMeta(meta);

                await pruneCacheIfNeeded();
            } catch (err) {
                console.error('[Cache] Falha ao salvar no cache em background:', err);
            }
        }

        async function deleteCachedTrack(trackItem) {
            try {
                const cache = await caches.open('audio-tracks-cache');
                await cache.delete(trackItem.url);
            } catch (e) {
                console.error('[Cache] Falha ao deletar do Cache Storage:', e);
            }
        }

        async function pruneCacheIfNeeded() {
            const maxMB = parseInt(localStorage.getItem('mobile_max_cache_size') || '250', 10);
            const maxBytes = maxMB * 1024 * 1024;

            let meta = getCacheMeta();
            let totalBytes = meta.reduce((acc, item) => acc + item.sizeInBytes, 0);

            if (totalBytes <= maxBytes) {
                return;
            }

            meta.sort((a, b) => a.lastUsedAt - b.lastUsedAt);

            while (totalBytes > maxBytes && meta.length > 0) {
                const oldest = meta.shift();
                await deleteCachedTrack(oldest);
                totalBytes -= oldest.sizeInBytes;
            }

            saveCacheMeta(meta);
        }

        async function triggerMobileQueuePrefetch() {
            try {
                const prefetchCount = parseInt(localStorage.getItem('mobile_queue_prefetch_count') || '0', 10);
                if (prefetchCount <= 0 || activeQueue.length === 0) return;
                
                const startIndex = activeQueueIdx + 1;
                const tracksToPrefetch = [];
                
                for (let i = 0; i < prefetchCount; i++) {
                    const nextIndex = (startIndex + i) % activeQueue.length;
                    if (nextIndex === activeQueueIdx) break;
                    const track = activeQueue[nextIndex];
                    if (track && track.id && track.artist !== 'Rádio On-line') {
                        tracksToPrefetch.push(track);
                    }
                }
                
                for (let i = 0; i < tracksToPrefetch.length; i++) {
                    const track = tracksToPrefetch[i];
                    const rawUrl = window.getAbsoluteUrl(API + '?route=stream&id=' + track.id);
                    if (window.fetchAndSaveToCache) {
                        await window.fetchAndSaveToCache(String(track.id), rawUrl);
                    }
                }
            } catch (prefErr) {
                console.warn('[Auto Fila Prefetch] Erro ao pré-reproduzir:', prefErr);
            }
        }

        window.toggleMobileCacheState = function(enabled) {
            localStorage.setItem('mobile_cache_enabled', enabled ? 'true' : 'false');
            window.updateCacheSettingsUI();
        };

        window.changeMobileCacheLimit = function(limit) {
            localStorage.setItem('mobile_max_cache_size', limit);
            window.updateCacheSettingsUI();
            pruneCacheIfNeeded().catch(err => console.error(err));
        };

        window.changeMobileQueuePrefetch = function(count) {
            localStorage.setItem('mobile_queue_prefetch_count', count);
            window.updateCacheSettingsUI();
        };

        window.clearMobileCacheStorage = async function() {
            try {
                const cache = await caches.open('audio-tracks-cache');
                const keys = await cache.keys();
                for (const req of keys) {
                    await cache.delete(req);
                }
                localStorage.setItem('mobile_cached_tracks_meta', '[]');
                alert('Cache limpo com sucesso!');
                window.updateCacheSettingsUI();
            } catch (e) {
                console.error(e);
                alert('Erro ao limpar cache.');
            }
        };

        window.updateCacheSettingsUI = function() {
            const cacheEnabled = localStorage.getItem('mobile_cache_enabled') !== 'false';
            const maxMB = parseInt(localStorage.getItem('mobile_max_cache_size') || '250', 10);
            const prefetchCount = localStorage.getItem('mobile_queue_prefetch_count') || '0';
            
            const checkbox = document.getElementById('mobile-cache-enabled');
            if (checkbox) checkbox.checked = cacheEnabled;

            const select = document.getElementById('mobile-cache-limit-select');
            if (select) select.value = String(maxMB);

            const pfSelect = document.getElementById('mobile-queue-prefetch-select');
            if (pfSelect) pfSelect.value = String(prefetchCount);

            const meta = getCacheMeta();
            const totalBytes = meta.reduce((acc, item) => acc + item.sizeInBytes, 0);
            const totalMB = totalBytes / 1024 / 1024;

            const usageLbl = document.getElementById('mobile-cache-usage-lbl');
            if (usageLbl) {
                usageLbl.textContent = totalMB.toFixed(1) + " MB / " + maxMB + " MB";
            }

            const progress = document.getElementById('mobile-cache-progress');
            if (progress) {
                const pct = Math.min(100, (totalMB / maxMB) * 100);
                progress.style.width = pct + "%";
                if (pct > 90) {
                    progress.className = 'bg-rose-500 h-1.5 rounded-full';
                } else if (pct > 75) {
                    progress.className = 'bg-amber-500 h-1.5 rounded-full';
                } else {
                    progress.className = 'bg-sky-500 h-1.5 rounded-full';
                }
            }
        };

        window.loadMobileDlnaSetting = async function() {
            try {
                const res = await fetch('api.php?route=dlna_status');
                if (res.ok) {
                    const data = await res.json();
                    const toggle = document.getElementById('mobile-dlna-enabled');
                    if (toggle) toggle.checked = !!data.enabled;
                    
                    const indicator = document.getElementById('m-dlna-status-indicator');
                    if (indicator) {
                        if (data.enabled) {
                            indicator.innerHTML = '<span class="text-emerald-400 font-bold">ATIVO</span>';
                        } else {
                            indicator.innerHTML = '<span class="text-slate-500">OFFLINE</span>';
                        }
                    }
                }
            } catch (err) {
                console.error("Erro ao obter status DLNA no mobile:", err);
            }
        };

        window.toggleMobileDlna = async function(checked) {
            try {
                const res = await fetch('api.php?route=toggle_dlna', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enabled: checked ? '1' : '0' })
                });
                if (res.ok) {
                    await window.loadMobileDlnaSetting();
                } else {
                    alert("Erro ao alterar configuração DLNA.");
                }
            } catch (err) {
                console.error("Erro operacional ao atualizar DLNA:", err);
                alert("Erro operacional ao atualizar DLNA.");
            }
        };

        window.getAudioSourceCachedAndFetch = async function(trackId, rawUrl, fullTrackObj = null) {
            const cacheEnabled = localStorage.getItem('mobile_cache_enabled') !== 'false';
            if (!cacheEnabled) {
                return rawUrl;
            }

            try {
                const cache = await caches.open('audio-tracks-cache');
                const cachedResponse = await cache.match(rawUrl);

                if (cachedResponse) {
                    const blob = await cachedResponse.blob();
                    updateTrackLastUsedTime(trackId);
                    return URL.createObjectURL(blob);
                }

                fetchAndSaveToCache(trackId, rawUrl, fullTrackObj).catch(err => console.error('[Cache] Falha background download:', err));
                return rawUrl;
            } catch (err) {
                console.error('[Cache] Erro de cache:', err);
                return rawUrl;
            }
        };

        let selectedMobileTheme = 'default';

        window.selectMobileTheme = function(themeName) {
            selectedMobileTheme = themeName;
            applyUserTheme(themeName);
            
            const mThemeBtns = ['default', 'emerald', 'rose', 'amber', 'violet', 'crimson'];
            mThemeBtns.forEach(t => {
                const btn = document.getElementById('mtheme-' + t);
                if (btn) {
                    if (t === themeName) {
                        btn.className = "p-3 rounded-2xl bg-sky-500/10 border-2 border-sky-500 flex items-center gap-2 text-left transition";
                    } else {
                        btn.className = "p-3 rounded-2xl bg-slate-950/40 border border-slate-900 flex items-center gap-2 text-left active:scale-95 transition";
                    }
                }
            });
        };

        window.openConfigSheet = function() {
            const sheet = document.getElementById('config-sheet');
            if (!sheet) return;
            sheet.classList.remove('hidden');
            
            selectedMobileTheme = currentUser?.theme || 'default';
            selectMobileTheme(selectedMobileTheme);
 
            const activeLang = localStorage.getItem('phplayer_lang') || 'pt';
            if (window.applyMobileLanguage) {
                window.applyMobileLanguage(activeLang);
            }

            if (currentUser) {
                const currentBg = currentUser.sidebarBg || '#020617';
                const picker = document.getElementById('m-layout-bg-picker');
                const text = document.getElementById('m-layout-bg-text');
                if (picker) picker.value = currentBg;
                if (text) text.value = currentBg;
            }

            if (window.updateCacheSettingsUI) {
                window.updateCacheSettingsUI();
            }

            if (window.loadMobileDlnaSetting) {
                window.loadMobileDlnaSetting();
            }
 
            setTimeout(() => {
                const sheetInner = sheet.querySelector('.bottom-sheet');
                if (sheetInner) {
                    sheetInner.classList.remove('bottom-sheet-hidden');
                }
            }, 50);
        };

        window.onMobileLayoutBgLiveChange = function(val) {
            const hexPattern = /^#[0-9a-f]{6}$/i;
            if (hexPattern.test(val)) {
                const picker = document.getElementById('m-layout-bg-picker');
                const text = document.getElementById('m-layout-bg-text');
                if (picker && picker.value !== val) picker.value = val;
                if (text && text.value !== val) text.value = val;

                if (currentUser) {
                    currentUser.sidebarBg = val;
                    currentUser.footerBg = val;
                    currentUser.topBg = val;
                    if (window.applyUserLayoutBg) {
                        window.applyUserLayoutBg();
                    }
                }
            }
        };

        window.applyMobileLayoutColor = async function(val) {
            window.onMobileLayoutBgLiveChange(val);
            await window.saveMobileLayoutColor();
        };

        window.saveMobileLayoutColor = async function() {
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
                    localStorage.setItem('music_user_profile', JSON.stringify(currentUser));
                    alert('Cor do layout salva com sucesso!');
                } else {
                    alert('Erro ao salvar cor do layout.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de conexão ao salvar cor.');
            }
        };

        window.closePlaylistSheet = function() {
            const sheet = document.getElementById('playlist-sheet');
            if (!sheet) return;
            const sheetInner = sheet.querySelector('.bottom-sheet');
            if (sheetInner) {
                sheetInner.classList.add('bottom-sheet-hidden');
            }
            setTimeout(() => {
                sheet.classList.add('hidden');
            }, 300);
        };

        window.openAddActiveSongToPlaylistSheet = async function() {
            if (activeQueue.length === 0 || activeQueueIdx < 0) {
                alert('Nenhuma música sendo reproduzida no momento.');
                return;
            }
            if (!currentUser) {
                alert('Faça login para gerenciar suas playlists.');
                return;
            }
            
            const sheet = document.getElementById('playlist-sheet');
            if (!sheet) return;
            
            // Show bottom sheet with animation
            sheet.classList.remove('hidden');
            setTimeout(() => {
                const sheetInner = sheet.querySelector('.bottom-sheet');
                if (sheetInner) {
                    sheetInner.classList.remove('bottom-sheet-hidden');
                }
            }, 50);
            
            await loadAndRenderPlaylistPicker();
        };

        async function loadAndRenderPlaylistPicker() {
            const container = document.getElementById('mobile-playlists-picker-container');
            if (!container) return;
            
            container.innerHTML = '<div class="text-center py-4 text-[10px] text-slate-500 animate-pulse">Carregando playlists...</div>';
            
            try {
                // Fetch user checklists
                const res = await fetch('api.php?route=playlists&username=' + encodeURIComponent(currentUser.username));
                if (!res.ok) {
                    container.innerHTML = '<p class="text-[10px] text-rose-400 italic">Erro ao buscar playlists.</p>';
                    return;
                }
                const playlists = await res.json();
                
                if (playlists.length === 0) {
                    container.innerHTML = '<p class="text-[10px] text-slate-500 py-3 italic">Nenhuma playlist pessoal encontrada. Digite um nome acima para começar!</p>';
                    return;
                }
                
                const activeTrack = activeQueue[activeQueueIdx];
                let html = '';
                playlists.forEach(pl => {
                    const hasTrack = pl.trackIds && pl.trackIds.map(String).includes(String(activeTrack.id));
                    html += `
                        <div onclick="toggleActiveTrackInPlaylist('${pl.id}', ${hasTrack})" class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/45 border border-slate-900 hover:border-slate-800 transition cursor-pointer text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="p-1 px-2.5 bg-sky-500/10 text-sky-400 rounded-lg text-[10px] font-mono shrink-0">PL</span>
                                <span class="font-bold text-slate-200 truncate pr-2">${pl.name}</span>
                            </div>
                            <div class="shrink-0">
                                <i data-lucide="${hasTrack ? 'check-circle-2' : 'plus'}" class="w-4 h-4 ${hasTrack ? 'text-sky-450 fill-sky-500/10' : 'text-slate-550'}"></i>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                lucide.createIcons();
            } catch (err) {
                console.error(err);
                container.innerHTML = '<p class="text-[10px] text-rose-450 italic">Erro de conexão ao obter playlists.</p>';
            }
        }

        window.createPlaylistFromMobilePlayer = async function() {
            const input = document.getElementById('mobile-new-playlist-input');
            if (!input || !input.value.trim()) {
                alert('Digite o nome da playlist.');
                return;
            }
            const name = input.value.trim();
            
            try {
                const response = await fetch('api.php?route=playlists', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, trackIds: [], username: currentUser.username })
                });
                
                if (response.ok) {
                    input.value = '';
                    await loadAndRenderPlaylistPicker();
                } else {
                    alert('Erro ao criar playlist no servidor.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao criar playlist.');
            }
        };

        window.toggleActiveTrackInPlaylist = async function(playlistId, isAlreadyAdded) {
            if (activeQueue.length === 0 || activeQueueIdx < 0) return;
            const activeTrack = activeQueue[activeQueueIdx];
            
            try {
                // Fetch current playlist configuration
                const plRes = await fetch('api.php?route=playlists&username=' + encodeURIComponent(currentUser.username));
                if (!plRes.ok) return;
                const playlists = await plRes.json();
                const pl = playlists.find(p => String(p.id) === String(playlistId));
                if (!pl) return;
                
                let trackIds = pl.trackIds ? pl.trackIds.map(String) : [];
                if (isAlreadyAdded) {
                    trackIds = trackIds.filter(id => String(id) !== String(activeTrack.id));
                } else {
                    trackIds.push(String(activeTrack.id));
                }
                
                const response = await fetch('api.php?route=update_playlist&id=' + encodeURIComponent(playlistId), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ trackIds: trackIds })
                });
                
                if (response.ok) {
                    await loadAndRenderPlaylistPicker();
                } else {
                    alert('Erro ao atualizar playlist no servidor.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede ao salvar faixa na playlist.');
            }
        };
 
        window.closeConfigSheet = function() {
            const sheet = document.getElementById('config-sheet');
            if (!sheet) return;
            const sheetInner = sheet.querySelector('.bottom-sheet');
            if (sheetInner) {
                sheetInner.classList.add('bottom-sheet-hidden');
            }
            setTimeout(() => {
                sheet.classList.add('hidden');
            }, 300);
        };
 
        window.saveMobileTheme = async function() {
            if (!currentUser) return;
            try {
                const res = await fetch('api.php?route=users&username=' + encodeURIComponent(currentUser.username), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: selectedMobileTheme })
                });
                if (res.ok) {
                    currentUser.theme = selectedMobileTheme;
                    localStorage.setItem('music_user_profile', JSON.stringify(currentUser));
                    closeConfigSheet();
                    window.location.reload();
                } else {
                    alert('Erro ao atualizar seu tema.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro na conexão.');
            }
        };
 
        window.applyUserTheme = function(theme) {
            const active = theme || 'default';
            document.documentElement.setAttribute('data-theme', active);
            document.body.setAttribute('data-theme', active);

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
        };

        window.applyUserLayoutBg = function() {
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
        };

        const applyUserTheme = window.applyUserTheme;
 
        window.handleMobilePasswordChange = async function(e) {
            e.preventDefault();
            if (!currentUser) return;
            const passInput = document.getElementById('mobile-new-password');
            const passVal = passInput.value.trim();
            if (!passVal) {
                alert('Nova senha inválida.');
                return;
            }
            try {
                const res = await fetch('api.php?route=users&username=' + encodeURIComponent(currentUser.username), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: passVal })
                });
                if (res.ok) {
                    alert('Sua senha foi alterada com sucesso!');
                    passInput.value = '';
                    closeConfigSheet();
                } else {
                    alert('Erro ao alterar senha.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro de rede.');
            }
        };
 
        window.playAllMobileFavorites = function() {
            const favorites = allTracks.filter(track => allFavorites.includes(String(track.id)));
            if (favorites.length === 0) return;
            playFullQueueList(favorites, 0);
        };
 
        window.shuffleAllMobileFavorites = function() {
            const favorites = allTracks.filter(track => allFavorites.includes(String(track.id)));
            if (favorites.length === 0) return;
            playFullQueueListShuffled(favorites);
        };

        window.playRandomMobileAlbum = function() {
            const albumsKeys = Object.keys(albumsMap);
            if (albumsKeys.length === 0) return;
            const randomKey = albumsKeys[Math.floor(Math.random() * albumsKeys.length)];
            const randomAlb = albumsMap[randomKey];
            if (randomAlb && randomAlb.tracks && randomAlb.tracks.length > 0) {
                playFullQueueList(randomAlb.tracks, 0);
            }
        };
 
        const mobileTranslations = {
            pt: {
                "m-nav-start": "Início",
                "m-nav-albums": "Álbuns",
                "m-nav-artists": "Artistas",
                "m-nav-podcasts": "Podcasts",
                "m-nav-radios": "Rádios",
                "m-nav-search": "Buscar",
                "m-nav-favorites": "Favoritos",
                "m-stat-tracks": "Músicas",
                "m-stat-albums": "Álbuns",
                "m-sec-recent": "Adicionadas Recentemente",
                "m-sec-view-all": "Ver Todas",
                "m-sec-queue": "Fila de Reprodução",
                "m-btn-clear-queue": "Limpar",
                "m-loading": "Carregando catálogo de áudio...",
                "m-sec-avail-albums": "Álbuns Disponíveis",
                "m-sec-avail-sub": "Selecione um álbum para reproduzir sua lista de faixas",
                "m-btn-random-album": "Álbum Aleatório",
                "m-search-placeholder": "Buscar por música, artista ou álbum...",
                "m-search-empty": "Digite termos para encontrar faixas.",
                "m-sec-likes": "Suas Curtidas",
                "m-sec-likes-sub": "Músicas adicionadas aos favoritos",
                "m-tag-favs": "FAVORITAS",
                "m-btn-play-all": "Tocar Todas",
                "m-btn-shuffle": "Ordem Aleatória",
                "m-sec-artists": "Artistas",
                "m-sec-artists-sub": "Seus artistas e discografias",
                "m-tag-catalog": "CATÁLOGO",
                "m-config-title": "Configurações",
                "m-config-sub": "TEMA E SENHA",
                "m-theme-title": "Tema de Destaque",
                "m-theme-celeste": "Celeste",
                "m-theme-esmeralda": "Esmeralda",
                "m-theme-rosa": "Rosa",
                "m-theme-ambar": "Âmbar",
                "m-theme-violeta": "Violeta",
                "m-theme-rubi": "Rubi",
                "m-theme-apply": "Aplicar Tema",
                "m-lang-title": "Idioma do Sistema",
                "m-pass-title": "Alterar Senha",
                "m-pass-placeholder": "Digite sua nova senha",
                "m-pass-btn": "Atualizar Senha",
                "m-cache-title": "Cache de Áudio (Offline)",
                "m-cache-toggle": "Auto-salvar no Cache",
                "m-cache-limit": "Limite de Armazenamento",
                "m-cache-status": "Espaço em Uso",
                "m-cache-clear-btn": "Limpar Cache"
            },
            en: {
                "m-nav-start": "Home",
                "m-nav-albums": "Albums",
                "m-nav-artists": "Artists",
                "m-nav-podcasts": "Podcasts",
                "m-nav-radios": "Radios",
                "m-nav-search": "Search",
                "m-nav-favorites": "Favorites",
                "m-stat-tracks": "Tracks",
                "m-stat-albums": "Albums",
                "m-sec-recent": "Recently Added",
                "m-sec-view-all": "View All",
                "m-sec-queue": "Playback Queue",
                "m-btn-clear-queue": "Clear",
                "m-loading": "Loading music library...",
                "m-sec-avail-albums": "Available Albums",
                "m-sec-avail-sub": "Select an album to play its tracklist",
                "m-btn-random-album": "Random Album",
                "m-search-placeholder": "Search by track, artist or album...",
                "m-search-empty": "Type keywords to find music.",
                "m-sec-likes": "Your Likes",
                "m-sec-likes-sub": "Tracks added to favorites",
                "m-tag-favs": "FAVORITES",
                "m-btn-play-all": "Play All",
                "m-btn-shuffle": "Shuffle Play",
                "m-sec-artists": "Artists",
                "m-sec-artists-sub": "Your artists and discographies",
                "m-tag-catalog": "CATALOG",
                "m-config-title": "Settings",
                "m-config-sub": "THEME & PASSWORD",
                "m-theme-title": "Accent Theme",
                "m-theme-celeste": "Sky Blue",
                "m-theme-esmeralda": "Emerald",
                "m-theme-rosa": "Rose",
                "m-theme-ambar": "Amber",
                "m-theme-violeta": "Violet",
                "m-theme-rubi": "Ruby",
                "m-theme-apply": "Apply Theme",
                "m-lang-title": "System Language",
                "m-pass-title": "Change Password",
                "m-pass-placeholder": "Enter your new password",
                "m-pass-btn": "Update Password",
                "m-cache-title": "Audio Cache (Offline)",
                "m-cache-toggle": "Auto-save to Cache",
                "m-cache-limit": "Storage Limit",
                "m-cache-status": "Space in Use",
                "m-cache-clear-btn": "Clear Cache"
            },
            es: {
                "m-nav-start": "Inicio",
                "m-nav-albums": "Álbumes",
                "m-nav-artists": "Artistas",
                "m-nav-podcasts": "Podcasts",
                "m-nav-radios": "Radios",
                "m-nav-search": "Buscar",
                "m-nav-favorites": "Favoritos",
                "m-stat-tracks": "Canciones",
                "m-stat-albums": "Álbumes",
                "m-sec-recent": "Añadidas Recientemente",
                "m-sec-view-all": "Ver Todo",
                "m-sec-queue": "Cola de Reproducción",
                "m-btn-clear-queue": "Limpiar",
                "m-loading": "Cargando catálogo musical...",
                "m-sec-avail-albums": "Álbumes Disponibles",
                "m-sec-avail-sub": "Selecciona un álbum para ver sus canciones",
                "m-btn-random-album": "Álbum Aleatorio",
                "m-search-placeholder": "Buscar por canción, artista o álbum...",
                "m-search-empty": "Escribe palabras claves para buscar.",
                "m-sec-likes": "Tus Likes",
                "m-sec-likes-sub": "Canciones marcadas como favoritas",
                "m-tag-favs": "FAVORITOS",
                "m-btn-play-all": "Reproducir Todo",
                "m-btn-shuffle": "Orden Aleatorio",
                "m-sec-artists": "Artistas",
                "m-sec-artists-sub": "Tus artistas y discografías",
                "m-tag-catalog": "CATÁLOGO",
                "m-config-title": "Configuraciones",
                "m-config-sub": "TEMA Y CONTRASEÑA",
                "m-theme-title": "Tema de Acento",
                "m-theme-celeste": "Celeste",
                "m-theme-esmeralda": "Esmeralda",
                "m-theme-rosa": "Rosa",
                "m-theme-ambar": "Ámbar",
                "m-theme-violeta": "Violeta",
                "m-theme-rubi": "Rubí",
                "m-theme-apply": "Aplicar Tema",
                "m-lang-title": "Idioma del Sistema",
                "m-pass-title": "Cambiar Contraseña",
                "m-pass-placeholder": "Introduce tu nueva contraseña",
                "m-pass-btn": "Actualizar Contraseña",
                "m-cache-title": "Caché de Audio (Offline)",
                "m-cache-toggle": "Auto-guardar en Caché",
                "m-cache-limit": "Límite de Almacenamiento",
                "m-cache-status": "Espacio en Uso",
                "m-cache-clear-btn": "Limpiar Caché"
            }
        };
 
        window.applyMobileLanguage = function(langCode) {
            localStorage.setItem('phplayer_lang', langCode);
            const dictionary = mobileTranslations[langCode] || mobileTranslations.pt;
            
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (dictionary[key]) {
                    const iconElement = el.querySelector('i, svg');
                    if (iconElement) {
                        const tempText = document.createTextNode(" " + dictionary[key]);
                        Array.from(el.childNodes).forEach(node => {
                            if (node !== iconElement) {
                                el.removeChild(node);
                            }
                        });
                        el.appendChild(tempText);
                    } else {
                        el.textContent = dictionary[key];
                    }
                }
            });
 
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                if (dictionary[key]) {
                    el.setAttribute('placeholder', dictionary[key]);
                }
            });
 
            ['pt', 'en', 'es'].forEach(l => {
                const btn = document.getElementById('mlang-' + l);
                if (btn) {
                    if (l === langCode) {
                        btn.className = "py-2 px-1 rounded-xl bg-sky-500/10 border-2 border-sky-500 flex flex-col items-center justify-center text-center transition";
                    } else {
                        btn.className = "py-2 px-1 rounded-xl bg-slate-950/40 border border-slate-900 flex flex-col items-center justify-center text-center active:scale-95 transition";
                    }
                }
            });
        };
 
        window.selectMobileLang = function(langCode) {
            window.applyMobileLanguage(langCode);
            // Dynamic greeting redraw
            if (currentUser) {
                const greeting = (langCode === 'en' ? 'Hello, ' : langCode === 'es' ? 'Hola, ' : 'Olá, ') + (currentUser.username || 'Ouvinte').toUpperCase();
                const topGreeting = document.getElementById('top-greeting');
                if (topGreeting) {
                    topGreeting.textContent = greeting;
                }
            }
        };

        window.toggleMobileAddons = function() {
            const content = document.getElementById('mobile-addons-content');
            const chevron = document.getElementById('mobile-addons-chevron');
            if (content) {
                if (content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    if (chevron) {
                        chevron.style.transform = 'rotate(180deg)';
                    }
                    initMobileEqualizer();
                    if (mobileAudioContext && mobileAudioContext.state === 'suspended') {
                        mobileAudioContext.resume();
                    }
                } else {
                    content.classList.add('hidden');
                    if (chevron) {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                }
            }
        };

        // ====== MOBILE SLEEP TIMER LOGIC ======
        let mobileSleepTimerSecs = null;
        let mobileSleepTimerInterval = null;

        window.setMobileSleepTimer = function(minutes) {
            if (mobileSleepTimerInterval) clearInterval(mobileSleepTimerInterval);
            const statusEl = document.getElementById('mobile-sleep-status');
            const iconEl = document.getElementById('mobile-sleep-icon');
            
            if (minutes === null) {
                mobileSleepTimerSecs = null;
                if (statusEl) {
                    statusEl.textContent = 'Desativado';
                    statusEl.className = 'text-[9px] text-slate-500 font-bold uppercase tracking-wider';
                }
                if (iconEl) {
                    iconEl.className = "w-4 h-4 text-slate-500 transition-all duration-300";
                    if (window.lucide) window.lucide.createIcons();
                }
                return;
            }
            
            mobileSleepTimerSecs = minutes * 60;
            updateMobileSleepDisplay();
            
            mobileSleepTimerInterval = setInterval(() => {
                if (mobileSleepTimerSecs === null) {
                    clearInterval(mobileSleepTimerInterval);
                    return;
                }
                if (mobileSleepTimerSecs <= 0) {
                    if (audio) {
                        audio.pause();
                        updatePlayToggleUIState(false);
                        const coverImg = document.getElementById('full-cover-art');
                        if (coverImg) coverImg.classList.add('paused-vinyl');
                        const pulseDot = document.getElementById('mini-pulse-dot');
                        if (pulseDot) pulseDot.classList.add('hidden');
                    }
                    clearInterval(mobileSleepTimerInterval);
                    window.setMobileSleepTimer(null);
                    return;
                }
                mobileSleepTimerSecs--;
                updateMobileSleepDisplay();
            }, 1000);
        };

        function updateMobileSleepDisplay() {
            const statusEl = document.getElementById('mobile-sleep-status');
            const iconEl = document.getElementById('mobile-sleep-icon');
            if (mobileSleepTimerSecs === null) {
                if (statusEl) {
                    statusEl.textContent = 'Desativado';
                    statusEl.className = 'text-[9px] text-slate-500 font-bold uppercase tracking-wider';
                }
                if (iconEl) {
                    iconEl.className = "w-4 h-4 text-slate-500 transition-all duration-300";
                    if (window.lucide) window.lucide.createIcons();
                }
            } else {
                const mins = Math.floor(mobileSleepTimerSecs / 60);
                const secs = String(mobileSleepTimerSecs % 60).padStart(2, '0');
                if (statusEl) {
                    statusEl.textContent = 'Ativo: ' + mins + ':' + secs;
                    statusEl.className = 'text-[9px] text-emerald-400 font-black font-mono animate-pulse uppercase tracking-wider';
                }
                if (iconEl) {
                    iconEl.className = "w-4 h-4 text-emerald-400 animate-spin-slow transition-all duration-300";
                    if (window.lucide) window.lucide.createIcons();
                }
            }
        }

        // ====== MOBILE EQUALIZER LOGIC VIA WEB AUDIO ======
        let mobileAudioContext = null;
        let mobileSourceNode = null;
        let mobileBiquadFilters = [];
        let mobileEqGains = [0, 0, 0, 0, 0];
        let mobileAnalyserNode = null;
        let mobileVisualizerStyle = 'bars';
        let mobileVisualizerColor = 'wmp';
        let mobileVisualizerAnimFrame = null;
        let mobilePeaks = new Array(256).fill(0);

        const mobilePresets = {
            flat: [0, 0, 0, 0, 0],
            bass: [6, 4, 0, 0, -1],
            pop: [-1, 2, 4, 2, -1],
            rock: [4, 2, -2, 2, 5],
            vocal: [-2, 1, 3, 4, 2],
            electronic: [5, 3, 1, 2, 4],
            suave: [3, 1, 0, 1, -2],
            classical: [3, 2, 1, -1, 3]
        };

        function initMobileEqualizer() {
            if (mobileAudioContext) return;
            
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;
                
                mobileAudioContext = new AudioContextClass();
                mobileSourceNode = mobileAudioContext.createMediaElementSource(audio);
                
                const freqs = [60, 230, 910, 4000, 14000];
                mobileBiquadFilters = freqs.map((freq, idx) => {
                    const filter = mobileAudioContext.createBiquadFilter();
                    filter.type = 'peaking';
                    filter.frequency.value = freq;
                    filter.Q.value = 1.0;
                    filter.gain.value = mobileEqGains[idx];
                    return filter;
                });
                
                mobileAnalyserNode = mobileAudioContext.createAnalyser();
                mobileAnalyserNode.fftSize = 256;

                let lastNode = mobileSourceNode;
                mobileBiquadFilters.forEach(filter => {
                    lastNode.connect(filter);
                    lastNode = filter;
                });
                lastNode.connect(mobileAnalyserNode);
                mobileAnalyserNode.connect(mobileAudioContext.destination);
            } catch (err) {
                console.warn('Web Audio error mobile:', err);
            }
        }

        window.setMobileStyle = function(style) {
            initMobileEqualizer();
            if (mobileAudioContext && mobileAudioContext.state === 'suspended') {
                mobileAudioContext.resume();
            }
            mobileVisualizerStyle = style;
            const styles = ['bars', 'scope', 'beat'];
            styles.forEach(s => {
                const btn = document.getElementById('m-v-' + s);
                if (btn) {
                    if (s === style) {
                        btn.className = 'px-1.5 py-0.5 bg-sky-500/10 text-sky-400 text-[8px] font-black uppercase rounded tracking-wider cursor-pointer';
                    } else {
                        btn.className = 'px-1.5 py-0.5 text-slate-400 hover:text-white text-[8px] font-black uppercase rounded tracking-wider cursor-pointer';
                    }
                }
            });
        };

        window.setMobileColor = function(color) {
            initMobileEqualizer();
            if (mobileAudioContext && mobileAudioContext.state === 'suspended') {
                mobileAudioContext.resume();
            }
            mobileVisualizerColor = color;
            const colors = ['wmp', 'neon', 'fire', 'cyber'];
            colors.forEach(c => {
                const btn = document.getElementById('m-c-' + c);
                if (btn) {
                    if (c === color) {
                        let activeBg = 'bg-cyan-500/10 text-cyan-405 border border-cyan-500/10';
                        if (c === 'neon') activeBg = 'bg-purple-500/10 text-purple-400 border border-purple-500/10';
                        if (c === 'fire') activeBg = 'bg-orange-500/10 text-orange-400 border border-orange-500/10';
                        if (c === 'cyber') activeBg = 'bg-green-500/10 text-green-400 border border-green-500/10';
                        btn.className = 'px-1.5 py-0.5 text-[8px] font-black uppercase rounded ' + activeBg + ' cursor-pointer';
                    } else {
                        btn.className = 'px-1.5 py-0.5 text-[8px] font-black uppercase rounded text-slate-400 hover:text-white cursor-pointer';
                    }
                }
            });
        };

        function startMobileVisualizerLoop() {
            if (mobileVisualizerAnimFrame) return;

            const canvas = document.getElementById('mobile-visualizer-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            let angleOffset = 0;

            function draw() {
                mobileVisualizerAnimFrame = requestAnimationFrame(draw);

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
                const gridSize = 12;
                for (let x = 0; x < width; x += gridSize) {
                    ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, height); ctx.stroke();
                }
                for (let y = 0; y < height; y += gridSize) {
                    ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(width, y); ctx.stroke();
                }

                const bufferLength = mobileAnalyserNode ? mobileAnalyserNode.frequencyBinCount : 128;
                const dataArray = new Uint8Array(bufferLength);

                let active = false;
                if (mobileAnalyserNode) {
                    if (mobileVisualizerStyle === 'scope') {
                        mobileAnalyserNode.getByteTimeDomainData(dataArray);
                    } else {
                        mobileAnalyserNode.getByteFrequencyData(dataArray);
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
                        if (mobileVisualizerStyle === 'scope') {
                            dataArray[i] = 128 + Math.sin(i * 0.15 + time) * 25 * Math.sin(i * 0.03);
                        } else {
                            dataArray[i] = Math.max(10, (Math.sin(i * 0.1 + time) + 1.0) * 35 * Math.sin(i * 0.01 + 0.5));
                        }
                    }
                }

                let bassSum = 0;
                const bassBins = Math.min(10, bufferLength);
                for (let i = 0; i < bassBins; i++) {
                    bassSum += (mobileVisualizerStyle === 'scope' ? Math.abs(dataArray[i] - 128) * 2 : dataArray[i]);
                }
                const bassAvg = bassSum / bassBins;
                const beatScale = 1.0 + (bassAvg / 255) * 0.35;

                let primaryColor = '#06b6d4';
                let secondaryColor = '#3b82f6';
                let glowColor = 'rgba(6, 182, 212, 0.5)';

                if (mobileVisualizerColor === 'neon') {
                    primaryColor = '#a855f7'; secondaryColor = '#f43f5e'; glowColor = 'rgba(168, 85, 247, 0.5)';
                } else if (mobileVisualizerColor === 'fire') {
                    primaryColor = '#f97316'; secondaryColor = '#eab308'; glowColor = 'rgba(249, 115, 22, 0.55)';
                } else if (mobileVisualizerColor === 'cyber') {
                    primaryColor = '#39ff14'; secondaryColor = '#10b981'; glowColor = 'rgba(57, 255, 20, 0.6)';
                }

                if (mobileVisualizerStyle === 'bars') {
                    const barWidth = (width / bufferLength) * 1.5;
                    let x = 0;
                    for (let i = 0; i < bufferLength; i++) {
                        const barHeight = (dataArray[i] / 255) * height * 0.8;
                        if (barHeight > mobilePeaks[i]) {
                            mobilePeaks[i] = barHeight;
                        } else {
                            mobilePeaks[i] = Math.max(0, mobilePeaks[i] - 1.2);
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
                        ctx.fillRect(x, height - mobilePeaks[i] - 2, barWidth - 1, 1.5);
                        ctx.shadowBlur = 0;

                        x += barWidth;
                        if (x >= width) break;
                    }
                } else if (mobileVisualizerStyle === 'scope') {
                    ctx.beginPath();
                    ctx.lineWidth = 1.8 + (beatScale - 1.0) * 3;
                    ctx.strokeStyle = primaryColor;
                    ctx.shadowBlur = 10 * beatScale;
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
                } else if (mobileVisualizerStyle === 'beat') {
                    const centerX = width / 2;
                    const centerY = height / 2;
                    const baseRadius = Math.min(width, height) * 0.22 * beatScale;

                    angleOffset += 0.005 + (beatScale - 1.0) * 0.04;

                    ctx.strokeStyle = 'rgba(30, 41, 59, 0.35)';
                    ctx.lineWidth = 1;
                    ctx.beginPath(); ctx.arc(centerX, centerY, baseRadius * 1.4, 0, Math.PI * 2); ctx.stroke();

                    const spikes = Math.min(64, bufferLength);
                    ctx.beginPath();
                    ctx.lineWidth = 1.5;
                    ctx.strokeStyle = primaryColor;
                    ctx.shadowBlur = 6 * beatScale;
                    ctx.shadowColor = glowColor;

                    for (let i = 0; i < spikes; i++) {
                        const angle = (i / spikes) * Math.PI * 2 + angleOffset;
                        const magnitude = (dataArray[i] / 125) * baseRadius * 0.45;
                        const innerR = baseRadius - (magnitude * 0.25);
                        const outerR = baseRadius + magnitude;

                        const p1X = centerX + Math.cos(angle) * innerR;
                        const p1Y = centerY + Math.sin(angle) * innerR;
                        const p2X = centerX + Math.cos(angle) * outerR;
                        const p2Y = centerY + Math.sin(angle) * outerR;

                        ctx.moveTo(p1X, p1Y);
                        ctx.lineTo(p2X, p2Y);
                    }
                    ctx.stroke();
                    ctx.shadowBlur = 0;

                    const solidG = ctx.createRadialGradient(centerX, centerY, 1, centerX, centerY, baseRadius);
                    solidG.addColorStop(0, secondaryColor);
                    solidG.addColorStop(0.8, primaryColor);
                    solidG.addColorStop(1, 'transparent');
                    ctx.fillStyle = solidG;
                    ctx.beginPath(); ctx.arc(centerX, centerY, baseRadius, 0, Math.PI * 2); ctx.fill();

                    ctx.strokeStyle = '#ffffff';
                    ctx.lineWidth = 1.2;
                    ctx.beginPath(); ctx.arc(centerX, centerY, baseRadius, 0, Math.PI * 2); ctx.stroke();
                }
            }

            draw();
        }

        // Initialize mobile visualizer immediately on setup
        setTimeout(startMobileVisualizerLoop, 600);

        window.onMobileEqChange = function(bandIdx, value) {
            initMobileEqualizer();
            if (mobileAudioContext && mobileAudioContext.state === 'suspended') {
                mobileAudioContext.resume();
            }
            
            const numVal = parseFloat(value);
            mobileEqGains[bandIdx] = numVal;
            
            const label = document.getElementById('mobile-eq-gain-val-' + bandIdx);
            if (label) {
                label.textContent = (numVal > 0 ? '+' : '') + numVal + 'dB';
            }
            
            if (mobileBiquadFilters[bandIdx]) {
                mobileBiquadFilters[bandIdx].gain.value = numVal;
            }
            
            // Set preset select to flat/custom if it doesn't match preset
            const select = document.getElementById('mobile-eq-preset-select');
            if (select) {
                let foundPreset = 'custom';
                for (const presetName in mobilePresets) {
                    const presetGains = mobilePresets[presetName];
                    const match = presetGains.every((g, idx) => g === mobileEqGains[idx]);
                    if (match) {
                        foundPreset = presetName;
                        break;
                    }
                }
                
                let hasCustom = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === 'custom') {
                        hasCustom = true;
                        break;
                    }
                }
                if (!hasCustom && foundPreset === 'custom') {
                    const opt = document.createElement('option');
                    opt.value = 'custom';
                    opt.textContent = 'PERSONALIZADO';
                    select.appendChild(opt);
                }
                select.value = foundPreset;
            }
        };

        window.applyMobilePreset = function(presetName) {
            if (!mobilePresets[presetName]) return;
            const gains = mobilePresets[presetName];
            
            gains.forEach((gain, idx) => {
                const slider = document.getElementById('mobile-eq-band-' + idx);
                if (slider) {
                    slider.value = gain;
                }
                window.onMobileEqChange(idx, gain);
            });
        };
    </script>

</body>
</html>
