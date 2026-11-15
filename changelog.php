<?php
$changelog = <<<EOT
v1.4.9
- Fallback automático para o nome da pasta do artista no PHP (`api.php`): quando a tag ID3 do arquivo estiver ausente, vazia ou com marcador genérico (`Artista Desconhecido`, `Unknown Artist`, `Unknown`, `Desconhecido`, etc.), o escaneamento (`?route=scan`), a reparação do banco (`?route=repair_db`) e a listagem (`?route=tracks`) extraem automaticamente o artista (e o álbum, quando aplicável) a partir da estrutura de pastas `/music/ARTISTA/ALBUM`.
- Correção do parser ID3v2 (`$getMp3Meta` em `api.php`) para ignorar frames vazios (`size <= 0`) em vez de ler bytes de frames subsequentes e atualização automática de faixas já cadastradas no banco que estavam sem artista identificado ao re-escanear a pasta `/music`.

v1.4.8
- Correção da **Sincronização de Capas de Artistas** (`api.php` e `index.php`): removido o limite que processava apenas de 10 em 10 artistas; agora o sincronismo processa **todos os artistas sem capa** automaticamente via requisições paralelas `curl_multi_init` no Deezer (1ª opção) e fallback automático para o Spotify (API oficial, busca direta de perfil `open.spotify.com/artist`, Wikidata `P1902` e MusicBrainz + Spotify oEmbed), baixando todas as imagens para `images/artist_...` no servidor.
- Separação estrita entre **Capa do Artista** e **Capa dos Álbuns** (`index.php`, `mobile.php` e `api.php`): os cartões de artistas, barra lateral, página do artista e versão mobile agora utilizam exclusivamente a **capa oficial do artista** (`artist_metadata.artist_photo`), sem reutilizar capas de álbuns (`songs.cover_url`), limpando automaticamente qualquer capa de álbum (`images/cover_...`) atribuída por engano ao cadastro de artistas.

v1.4.7
- Adicionado botão e sincronismo **"Sincronizar Capas de Artistas"** em **Configurações → Sincronização e Mídia** (`index.php` e `api.php` via `?route=artist_covers_sync`): verifica todos os artistas sem imagem (ou com link externo pendente), atribui automaticamente a **1ª capa válida localizada no Deezer** e, caso não encontre no Deezer, busca e aplica automaticamente a **1ª capa localizada no Spotify** (com relatório em tempo real de lotes, contadores Deezer/Spotify e arquivos salvos).
- Implementado **download automático para o servidor local (`images/`)** de todas as imagens atribuídas a artistas e álbuns (`phplayer_download_and_save_image` em `api.php`): todas as fotos sincronizadas ou selecionadas manualmente (`artist_covers_sync`, `update_artist_banner_url`, `update_album_cover_url`, `deezer_sync`, `lastfm_sync`, `wikipedia_sync`, `artist_bio` e `album_cover`) são baixadas e salvas fisicamente na pasta `/images/` do servidor e registradas com caminho local no banco de dados.

v1.4.6
- Adicionada **Verificação e Diagnóstico Completo de Conexão com o GitHub** no PHP (`api.php`, `index.php`, `update.php` e `debug.php`): nova rota `?route=github_status` e botão **"Verificar Conexão com GitHub"** na aba de Atualizações, exibindo status de 5 endpoints em tempo real (`api.github.com/repos`, `raw.githubusercontent.com/.../version.php`, `changelog.php`, `commits/main` e pacote `.zip` no Codeload), latência em `ms`, limite de requisições da API (`X-RateLimit-Remaining`), informações do último commit (`SHA`, autor, data e mensagem) e verificação do ambiente PHP (`cURL`, `allow_url_fopen`, `OpenSSL`, `ZipArchive` e permissões de escrita).
- Correção das rotas de atualização (`check_update` e `do_update` em `api.php` e `update.php`): substituição do uso exclusivo de `file_get_contents` por cliente híbrido **cURL + Stream fallback** com `User-Agent`, suporte a redirecionamentos HTTPS (`CURLOPT_FOLLOWLOCATION`), contorno de certificados SSL locais ausentes, fallback automático via GitHub Contents API (`api.github.com/repos/.../contents`) caso `raw.githubusercontent.com` seja bloqueado, parser de `changelog.php` compatível com quebras de linha `CRLF`/`LF` e opção para configurar repositório/branch/token do GitHub.

v1.4.5
- Correção da **Busca por Capa / Logo do Artista** no PHP (`api.php` e `index.php`): remoção do **Google Imagens** e adição da **Wikipedia** (`pt.wikipedia.org`, `en.wikipedia.org`, `Wikidata` `P154`/`P18` e `Wikimedia Commons` com filtro `filetype:bitmap|drawing` e `User-Agent` compatível com a política da Wikimedia Foundation) como fonte oficial junto ao **Spotify** e **Deezer**.
- Correção do carregamento e seleção de capas de artistas (`index.php` e `api.php`): liberação das rotas de busca `search_artist_logo` e `search_images` contra bloqueios `HTTP 403`, seleção segura por índice (evitando falhas com apóstrofos e parênteses em URLs da Wikipedia/Wikimedia) e filtragem de marcadores `'no_photo'`/`'no_cover'` em `artist_photos` e `artist_bio`.

v1.4.4
- Correção da **Sincronização de Podcasts** no PHP (`api.php`, `index.php` e `mobile.php`): substituição do parser regex de blocos `<item>` por extrator determinístico via `stripos`/`substr` (resolvendo falhas de `pcre.backtrack_limit` em feeds RSS grandes acima de 1 MB como *NerdCast*, *Inteligência Ltda* e *Flow*), decodificação de `CDATA` e entidades XML (`&amp;`) nas URLs de áudio e capas, gravação de arquivos sidecar `.url` com janela segura de download e streaming transparente sob demanda em `?route=stream` (evitando *504 Gateway Timeout* ao sincronizar múltiplos episódios longos), além de propagação automática de `admin_username` e `X-Username`.
- Correção da **Reprodução de Rádios Online** no PHP (`api.php`, `index.php` e `mobile.php`): substituição das URLs `.m3u` bloqueadas por proteção Cloudflare (`HTTP 403`) por streams diretos HTTPS de alta disponibilidade (`Kiss FM`, `89 FM A Rádio Rock`, `Jovem Pan News`, `Antena 1`, `Mix FM`, `Alpha FM`), migração automática de rádios antigas cadastradas no banco MySQL e fallback via API *Radio Browser*.
- Melhoria no proxy de streaming de rádios (`?route=proxy_radio` em `api.php`): descarte automático de corpos HTML de redirecionamento (`HTTP 301/302/307`) que corrompiam o início do buffer de áudio no navegador, repasse do `Content-Type` real (`audio/aacp`, `audio/mpeg`, `audio/ogg`), suporte a `ICY 200 OK` (Shoutcast) e fallback automático no player (`index.php` e `mobile.php`) para conexão direta caso o proxy falhe.

v1.4.3
- Correção da reprodução de **Rádios Online** no **Aplicativo Android (APK)** (`MainActivity.kt` e `api.php`): uso de `resolved_url`, resolução automática em segundo plano de listas `.m3u`, `.pls` e `.asx` para extrair o link real do stream Icecast/Shoutcast, reprodução direta com cabeçalhos HTTP personalizados e fallback automático para `proxy_radio` via cURL, além de rádios sugeridas e botão **➕ Nova Rádio**.
- Correção do carregamento de **Podcasts** no **Aplicativo Android (APK)** (`MainActivity.kt` e `api.php`): busca multi-tabela case-insensitive na rota `?route=podcasts`, persistência local de `podcasts.json`, preservação de canais com RSS mesmo sem episódios baixados, desacoplamento entre a chave Sync e a visibilidade online de podcasts/rádios e adição dos controles **🔄 Sync Feeds**, **➕ RSS** e **🔄 Atualizar Episódios (RSS)**.
- Eliminação de **travamentos ao clicar para reproduzir** no **Aplicativo Android (APK)** (`MainActivity.kt` e `api.php`): criação de thread pool dedicado e isolado (`playerExecutor`) para o `MediaPlayer` separado do carregamento de capas (`imageExecutor`), execução de `reset()`/`release()`/`prepareAsync()` fora da UI thread com timeout de segurança, índice em memória $O(1)$ (`offlineCachedSafeIds`) para checagem instantânea de arquivos offline sem bloqueio de disco na interface, atraso inteligente de 1,5s no cache em segundo plano e liberação de sessão PHP (`session_write_close()`) no streaming.

v1.4.2
- Adicionado modo **"🎧 Sincronizar somente o que for tocado"** (`syncOnlyPlayed`) no **Aplicativo Android (APK)** (`MainActivity.kt`), permitindo sincronizar o catálogo da API e salvar no armazenamento local do celular apenas as músicas, capas e episódios de podcast que forem efetivamente reproduzidos.
- Adicionado seletor rápido de 3 modos de sincronização na aba **⚙ Config** e no botão do cabeçalho (**`🎧 Só o Tocado`**, **`📥 Baixar Tudo`** e **`⏸ Sync Off`**), com gerenciamento inteligente de espaço (LRU) que preserva as músicas mais tocadas dentro do limite de armazenamento escolhido.

v1.4.1
- Visual do **Aplicativo Android (APK)** (`MainActivity.kt`) alinhado com o design do `mobile.php`: cabeçalho `PHPlayer Mobile` com saudação `OLÁ, {USUÁRIO}`, painel superior de 3 cartões de estatísticas (`MÚSICAS`, `ÁLBUNS`, `ARTISTAS`), botão gradiente **🔀 50 Aleatórias**, barra de filtro alfabético (`🎲 10 Álbuns`, `TODOS`, `#`, `A-Z`), **Grade Bento de 2 Colunas** com capas quadradas e botão Play flutuante para **Álbuns**, **Artistas** e **Podcasts**, seletor com os 6 temas de cores do `mobile.php` (`Celeste`, `Esmeralda`, `Rosa`, `Âmbar`, `Violeta` e `Rubi`), mini-player com barra fina de progresso sob o nome do artista e player expandido em tela cheia com **Disco de Vinil Giratório** animado e fila de reprodução.
- Adicionada opção para **Habilitar ou Desabilitar a Sincronização (Sync)** tanto no botão rápido do topo (`🔄 SYNC ON` / `⏸ SYNC OFF`) quanto na chave mestra da aba **⚙ Config** (incluindo controle independente para download automático offline), interrompendo downloads em segundo plano imediatamente quando desativado e operando 100% com o catálogo e arquivos MP3 locais.

v1.4.0
- Atualização do **Aplicativo Android (APK)** (`MainActivity.kt`): removidas as abas **Músicas** e **Web** e adicionadas as abas dedicadas **🎙 Podcast** e **⚙ Config**.
- Na nova aba **⚙ Config** do aplicativo Android, centralizados os botões **🔑 API / Login**, **🔄 Sync Biblioteca**, chave de **Modo Offline**, **Equalizador** e o seletor de **Limite de Espaço do Celular para Músicas Offline** (`512 MB`, `1 GB`, `2 GB`, `5 GB`, `10 GB` ou `Ilimitado`), com barra de progresso de armazenamento e espaço livre do dispositivo.
- Armazenamento local automático de todas as músicas e capas sincronizadas até o limite escolhido e exibição automática das músicas salvas no celular quando estiver offline, acompanhado de visual moderno em estilo vidro escuro com capas arredondadas e mini-player flutuante.

v1.3.9
- Adicionado botão direto no painel React (**"Baixar ZIP p/ Gerar APK"** no topo, **"App Android (APK)"** na barra lateral e aba dedicada em **Exportar PHP/MySQL**) para baixar o pacote `PHPlayer_Android_APK_Project.zip` com todos os arquivos Android organizados e pré-configurados com a URL do servidor PHP.
- Incluído workflow automático do GitHub Actions (`.github/workflows/build-apk.yml`), script `GERAR_APK_WINDOWS.bat` e guia `COMO_GERAR_O_APK.md` dentro do pacote ZIP para compilar o `.apk` tanto no Android Studio quanto automaticamente na nuvem sem instalar nada no computador.

v1.3.8
- Criado **Aplicativo Android Nativo (Kotlin)** (`/android/app/src/main/java/com/musicplayer/php/MainActivity.kt`) que se conecta diretamente ao sistema de música via `api.php` (`tracks`, `stream`, `login`, `artist_photos`, `favorites`, `favorites_toggle`, `playlists`, `radios`, `lyrics`, `register_play`), com reprodutor de áudio nativo, equalizador de 5 bandas, cache offline de músicas, busca e abas de Álbuns, Artistas, Músicas, Playlists e Rádios/Podcasts.
- Adicionadas rotas `android_ping` e `download_android_app` em `api.php` e painel **App Android (API)** na barra lateral do `index.php`, permitindo testar a API e baixar o projeto Android Studio (`.ZIP`) já pré-configurado com a URL do servidor PHP.

v1.3.7
- Na escolha de **Foto de Capa do Artista** (`index.php` e `api.php`), adicionada busca integrada por **Logo do Artista** e **Foto do Artista** na internet consultando **Spotify**, **Deezer**, **Google Imagens** e **Logo Oficial (Wikimedia/Wikidata)**, com filtros por fonte e seleção direta com um clique.

v1.3.6
- Correção do botão **Artista Aleatório** na página de Artistas (`index.php`): corrigido erro de referência nula (`e.stopPropagation`) em `playAlbumQueue` e `playAlbumQueueShuffled`, além de normalização de nomes de artistas e prevenção de repetição do artista atual.
- Remoção do modo **Karaoke** na janela de Letras (`index.php`), mantendo a visualização direta e limpa das letras em modo leitura sem sincronização/botões de alternância de karaoke.
- Correção do tamanho e proporções do **Equalizador de Áudio** (`#equalizer-modal` em `index.php`): ajuste da largura do modal (`max-w-lg`), grade de predefinições (4 colunas), altura da câmara de bandas (`h-72`) e dimensões explícitas dos controles deslizantes verticais para evitar cortes e colapso dos faders.

v1.3.5
- Remoção de canais e episódios de **Podcast** da lista de artistas em todo o sistema PHP (`index.php`, `mobile.php` e `api.php`), garantindo que podcasts não apareçam na página de Artistas, nas barras laterais de artistas, nos filtros nem na varredura da pasta `/music`.

v1.3.4
- Na página do artista (`index.php`), adicionado seletor dedicado de **Foto de Capa do Artista** (permitindo escolher entre as capas dos álbuns do artista, enviar foto do computador, colar URL direta ou buscar foto on-line), exibindo a foto de capa em destaque ao lado do título do artista e sincronizando-a com a página de Artistas e barras laterais.
- No banner da página do artista (`index.php`), implementada geração automática de um **Mix de Capas dos Álbuns** em mosaico com todas as capas dos álbuns daquele artista na biblioteca.

v1.3.3
- Adicionada página dedicada **Artistas** no menu principal (`index.php`), exibindo todos os artistas da biblioteca em grade com capa, contagem de álbuns e músicas, busca por nome, barra de filtro alfabético (A-Z) e ordenação.
- Adicionados botões diretos de **Play** (reproduzir todas as músicas do artista em ordem) e **Shuffle** (reproduzir o artista em modo aleatório) em cada card da página de Artistas, além do botão **Artista Aleatório**.

v1.3.2
- Separação dos álbuns por tipo (`Álbum`, `EP`, `Single`, `Álbum ao Vivo` e `Compilação`) na página do artista em `index.php`, com cabeçalhos de categoria dedicados, contadores, selos visuais nas capas e botões de filtro rápido por categoria.
- Sincronização completa do campo `album_type` nos modais de edição ID3 individual, edição de álbum completo e edição em massa no `index.php`.

v1.3.1
- Correção de erro fatal de sintaxe (Parse Error) no interpretador PHP em `api.php`: corrigido fechamento de bloco da função `syncSinglePodcastFeed` onde uma chave (`}`) faltante encapsulava inadvertidamente o restante do arquivo (incluindo `switch (\$route)` e o `catch (Throwable)` final), provocando `500 Internal Server Error` com resposta vazia em servidores Apache/cPanel externos para todas as rotas (`tracks`, `get_settings`, `podcast_config`).
- Otimização do `set_error_handler` em `api.php`: filtragem explícita para que avisos menores, notices e avisos de depreciação de versões recentes do PHP (8.1, 8.2, 8.3) não abortem o fluxo de requisições da API nem causem encerramento inesperado de respostas JSON.

v1.3.0
- Correção de erro 500 com corpo vazio: sincronizada a função `exitWithJsonError` em `config.php` e `api.php` para limpar buffers de saída e evitar o envio forçado de cabeçalho HTTP 500 quando há falha de conexão MySQL ou exceção PHP, prevenindo que servidores Apache/Nginx descartem a mensagem de erro JSON em favor de tela de erro genérica vazia.
- Diagnóstico detalhado de conexão: inclusão automática das credenciais em uso e arquivo de configuração nas mensagens de erro de banco de dados, facilitando a identificação imediata de credenciais incorretas ou serviços de banco inativos.

v1.2.9
- Correção no parsing e entrega de respostas JSON do servidor PHP: ativação de buffer de saída (`ob_start`) no início de `api.php` e limpeza total do buffer antes de emitir erros e respostas das rotas (`route=tracks`, `exitWithJsonError`), prevenindo corrupção de JSON por avisos ou espaços em branco.
- Implementação de parser resiliente `safeParseJson` no cliente (`index.php`), capaz de recuperar e extrair payloads JSON mesmo em caso de avisos ou wrappers emitidos por servidores web Apache/Nginx ou PHP compartilhados.

v1.2.8
- Adicionado suporte a sincronização seletiva por artista/pasta específica diretamente no Gerenciador de Arquivos do Servidor (aba de arquivos).
- Implementado botão "Sincronizar Pasta" dinâmico na barra de ferramentas superior do gerenciador quando dentro de uma pasta de artista, além de botão dedicado de sincronização rápida em cada linha de pasta da listagem.
- Atualizada rota scan (`api.php?route=scan`) com suporte ao parâmetro de subpasta/artista (`path` e `folder`), realizando limpeza seletiva e varredura recursiva exclusiva para os arquivos da pasta informada sem afetar ou escanear as demais músicas da biblioteca.

v1.2.7
- Correção no arrastar e soltar (drag & drop) de arquivos e pastas no Gerenciador de Arquivos do Servidor: correção de interferência de eventos de ponteiro no overlay com contagem de profundidade (drag counter) estável e extração recursiva robusta de hierarquias de pastas via FileSystem API.
- Exibição de quantidade de subpastas ao passar o cursor sobre as pastas (tooltip informativo e badge de contagem de subpastas ao lado do nome).
- Exibição do tamanho total calculado das pastas na coluna "Tamanho" do gerenciador com cálculo recursivo em tempo real no servidor.

v1.2.6
- Adicionada barra de progresso no Dashboard durante o carregamento, filtragem e rotação dos álbuns com status percentual e esqueletos de carregamento.
- Exibição de capa aleatória do artista na lista de artistas da barra lateral à direita (tanto no painel lateral global quanto na lista de artistas da biblioteca), posicionada à esquerda do nome do artista com fallback suave.

v1.2.5
- Correção do escopo global da função syncSinglePodcastFeed() no arquivo api.php: a declaração da função foi movida para fora do switch (\$route), garantindo carregamento incondicional pelo interpretador PHP e eliminando o erro fatal "Call to undefined function syncSinglePodcastFeed()".

v1.2.4
- Correção no sincronizador de podcasts: resolução do erro de mensagem "Podcast undefined atualizado com sucesso" decorrente de respostas sem verificação de sucesso e ausência de cabeçalho de autenticação.
- Otimização do leitor de feeds RSS de podcasts: suporte a descompactação automática de feeds compactados (gzip/deflate com CURLOPT_ENCODING), decodificação aprimorada de blocos CDATA e entidades HTML para títulos e nomes de canais.
- Download de episódios via streaming direto em disco com CURLOPT_FILE, eliminando estouro de memória (memory exhaust) no download de arquivos MP3 volumosos (100MB+).
- Envio explícito e consistente do cabeçalho de autenticação X-Username e parâmetro admin_username na sincronização via index.php e mobile.php, com detecção no api.php via headers, GET, POST e payload JSON.

v1.2.3
- Correção na área de podcasts: canais cadastrados permanecem salvos e visíveis mesmo se os episódios forem sincronizados, limpos ou se a varredura de músicas da biblioteca (scan) for executada.
- Proteção das faixas de podcast na rotina de escaneamento de músicas (`scan` em `api.php`), evitando a exclusão indevida de episódios armazenados fora do diretório `/music`.
- Persistência completa dos metadados de cada canal (feed RSS, limite de retenção, capa do canal e autor) na tabela `settings`.
- Suporte a exclusão explícita de podcasts e episódios pelo painel de detalhes tanto na interface Desktop (`index.php`) quanto Mobile (`mobile.php`) com a nova rota `podcast_delete`.
- Resolução e streaming de áudio dos episódios com suporte a caminhos em `PODCAST_DIR`, `UPLOAD_DIR/podcast` e na raiz.

v1.2.2
- Correção do carregamento de Estatísticas em Configurações > Estatísticas: implementada função loadPlayStats, sincronização de tempo total e exibição do Top 5 Mais Tocadas com capa, contagem de reproduções e medalhas.
- Registro em tempo real de reprodução de músicas via log_play tanto no Desktop (index.php) quanto no Mobile (mobile.php).
- Ajustadas consultas SQL de estatísticas em api.php para total compatibilidade com MySQL 8.0 ONLY_FULL_GROUP_BY e fallback automático para play_count existente na biblioteca.

v1.2.1
- Correção do erro 403 no envio de arquivos pelo Gerenciador de Arquivos do Servidor (propagação de autenticação via XMLHttpRequest, headers X-Username, FormData e parâmetros de URL).
- Suporte a envio de pastas completas com subpastas (função handleArtistFolderUpload e drag-and-drop recursivo via FileSystem API).
- Criação automática e recursiva de diretórios com permissões adequadas (0777) e suporte a nomes com acentuação e caracteres Unicode.
- Envio a partir da raiz virtual automaticamente direcionado para o diretório /music.

v1.1.31
- Atualizadas as credenciais padrão do administrador para o usuário/e-mail `admin@admin.com` e senha `admin`.
- Atualizada a rotina de auto-migração do banco de dados em `api.php` para criar e garantir o usuário `admin@admin.com` com privilégios totais de administrador.
- Atualizados os formulários e campos de login em `index.php`, `mobile.php` e `update.php` com suporte e placeholders para `admin@admin.com`.
- Atualizados os manuais de instalação para Docker e UGREEN NAS (UGOS Pro) informando o novo acesso inicial (`admin@admin.com` / `admin`).

v1.1.30
- Removido container opcional `phpmyadmin/phpmyadmin:latest` do `docker-compose.yml` para evitar erro `exec /docker-entrypoint.sh: exec format error` de incompatibilidade de arquitetura no UGREEN NAS e economizar memória RAM (todas as tabelas e usuários já são criados automaticamente pelo `api.php`).

v1.1.29
- Corrigido erro `failed to read dockerfile: open Dockerfile: no such file or directory` no UGREEN NAS Docker: o `docker-compose.yml` agora utiliza diretamente a imagem oficial `php:8.2-apache` com instalação automática de extensões (PDO MySQL, GD, Zip, Sockets, FFmpeg) e descompactação automática do `.zip` na pasta do projeto, sem depender de `Dockerfile` externo.

v1.1.28
- Adicionado exemplo completo de `docker-compose.yml` com botão "Copiar YAML" dentro da seção "Como instalar no UGREEN NAS (UGOS Pro Docker)" em Configurações > Atualizações.

v1.1.27
- Adicionado guia passo a passo integrado de instalação e deploy no UGREEN NAS (UGOS Pro Docker via Projeto / Docker Compose) diretamente no card de Container Docker em Configurações > Atualizações.

v1.1.26
- Conversão completa da aplicação PHP para Container Docker: adicionados Dockerfile (PHP 8.2 + Apache + FFmpeg + extensões PDO MySQL, Zip, GD, Sockets, cURL), docker-compose.yml (orquestrando phplayer-web, MySQL 8.0 com healthcheck e phpMyAdmin), docker-entrypoint.sh (configuração automática de permissões de volumes e espera ativa do banco de dados), docker/php.ini (upload de até 512MB) e suporte a variáveis de ambiente no config.php mantendo compatibilidade total com hospedagem compartilhada.
- Adicionado painel Container Docker em Configurações > Atualizações com download em 1 clique do pacote Docker completo (.ZIP).

v1.1.25
- Separação da discografia na página do artista por Tipo de Álbum (Álbuns, EPs, Singles, Álbuns ao Vivo e Compilações) tanto no Desktop (index.php) quanto no Mobile (mobile.php), com abas de filtro rápido por categoria e badges identificadores em cada álbum.
- Correção nos modais de edição ID3 (Edição Individual, Edição do Álbum Completo e Edição em Massa) para carregar e salvar corretamente o campo Tipo de Álbum (album_type) no banco de dados.

v1.1.24
- Controle Remoto e Transmissão para Android TV e Smart TVs via UPnP / DLNA AVTransport (Opção 4): envio direto de músicas, podcasts e rádios do PC para a Android TV (Kodi, BubbleUPnP, AirScreen, VLC e Smart TVs compatíveis), controle remoto de reprodução (Play, Pause, Stop, Volume) diretamente pela interface web do PHPlayer, descoberta de dispositivos na rede local, gerenciamento e teste de TVs cadastradas com detecção automática do IP do servidor.

v1.1.23
- Correção de erro de sintaxe PHP (500 Internal Server Error) no arquivo api.php: corrigido escape de aspas em expressões regulares de feeds de podcasts e remoção de bloco de código residual.

v1.1.22
- Sistema de atualização automática para podcasts em PHP: sincronização periódica configurável em segundo plano com opções de retenção de episódios selecionáveis (5 últimos, 10 últimos, 15 últimos ou todos), botão de sincronização em lote de todos os canais salvos, alteração dinâmica da retenção por podcast com limpeza imediata de excedentes e interface integrada tanto no desktop quanto no mobile.

v1.1.21
- Integração com Last.fm na página do artista: busca automática das músicas mais tocadas do artista no Last.fm e exibição em destaque das 10 faixas mais populares que existem na biblioteca local com reprodução direta e em fila contínua.

v1.1.19
- Remoção completa dos recursos de Séries, Filmes e Galeria de Vídeos do sistema PHP. Foco exclusivo na biblioteca e reprodução de música, podcasts e rádios.

v1.1.10
- Corrigido problema onde a tela de splash "Iniciando sistema..." não era ocultada para usuários deslogados.

v1.1.9
- Suprimido o aviso no console sobre o uso do Tailwind CSS CDN.
- Atualizado o seletor obsoleto do CSS (slider-vertical) substituindo-o pela propriedade \`writing-mode: vertical-lr\` para compatibilidade futura com os navegadores.
- Fechamento da tag script corrigido no mobile.php

v1.1.8
- Correção no carregamento e preview de capas de filmes (remoção da variável indefinida API_BASE_URL).

v1.1.7
- Correção no envio de capa de filmes (upload_movie_cover) e salvamento de metadados de filmes com autenticação de administrador e tratamento de erros.

v1.1.6
- Ajuste no layout e tamanho das capas de Filmes com suporte a upload de imagem e edição de capa de filme.
- Suporte a upload completo de pasta do Artista com preservação da estrutura de subpastas (Álbuns e Músicas) no Gerenciador de Arquivos e sincronização automática da biblioteca.

v1.1.5
- Adicionado seletor de ativação/desativação para 'Vídeo Library', 'Series Library' e 'Movies Library' na aba Sincronização e Mídia das Configurações, ocultando ou exibindo os menus correspondentes no menu lateral.

v1.1.4
- Adicionado suporte a foto/capa e descrição/sinopse para Séries com modal de edição para administradores e armazenamento no banco de dados.

v1.1.3
- Correção para compatibilidade com instalações existentes: definição fallback de constantes de diretório (MOVIES_DIR, SERIES_DIR, VIDEOS_DIR, etc.) caso não existam no config.php do usuário.

v1.1.2
- Adicionados botões de escaneamento para as pastas 'movies' e 'series' na aba Sincronização e Mídia das Configurações.

v1.1.1
- Adicionadas as pastas 'movies' e 'series' na lista da raiz do Gerenciador de Arquivos (Aba de Arquivos nas Configurações).

v1.1.0
- Modal para renomear arquivos e pastas no Gerenciador de Arquivos com sincronização automática do banco de dados (músicas e vídeos).

v1.0.3
- Removido compartilhamento de álbuns e playlists

v1.0.2
- Melhorias no layout de detalhes do artista (Banner de Destaque na view)
- Layout da lista de músicas do álbum aprimorado

v1.0.1
- Correção na exportação das atualizações (version.php e changelog.php)
- O arquivo config.php nunca será substituído durante as atualizações
- Adicionada área para verificação de novas versões

v1.0.0
- Lançamento inicial da versão independente em PHP do PHPlayer.
- Suporte a reprodução de músicas e vídeos nativamente.
- Gerenciamento de bibliotecas e DLNA.
- Interface customizável.
EOT;
