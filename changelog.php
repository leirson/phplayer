<?php
$changelog = <<<EOT
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
