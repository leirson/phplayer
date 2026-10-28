<?php
$changelog = <<<EOT
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
