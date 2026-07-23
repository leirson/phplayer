<?php
$changelog = <<<EOT
v1.1.6
- Implementado seletor de canais de áudio e suporte a Dual Áudio nos reprodutores de filmes, séries e vídeos (Estéreo, Canal Esquerdo/Dublado e Canal Direito/Legendado via Web Audio API + faixas de áudio nativas).

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
