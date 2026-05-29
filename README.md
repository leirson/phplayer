# PHPlayer
Player PHP para reprodução de musicas e videos

Usando PHP e Mysql, você terá um player feito em php, para ser hospedagem em qualquer hospedagem que aceite php e mysql ou criando um servidor local.
No arquivo index.php, direcionado para desktop, você terá diversos recursos, como lista de artistas, favoritos, playlist, usando api last.fm para coletar dados e id3tag.
Por padrão aconselha-se fazer upload das suas músicas para /music, o sistema fará uma varredura do diretório ao clicar nas Configurações, Sincronização e Mídia, o sistema entende o diretório com /music/artista/album/*.mp3 e nos vídeos o diretório /video sem subpastas, uma informação sobre vídeos, servidores compartilhados, os videos podem não ter recurso suficiente para executar com qualidade e podendo causar travamentos.


- Implantação:
Copie os arquivos para o diretório que deseja hospedar seu player.
Segue lista de arquivos
-index.php (arquivo deskop inteiro)
-api.php (arquivo de api)
-mobile.php (arquivo mobile inteiro)
-config.php (configuração de diretórios e banco de dados.
-/upload (diretório de trabalho do upload de musicas pelo config)
-/music (diretório de músicas com permissão 775)
-/video (diretório de videos com permissão 775)
-/images (diretorio onde ficam armazenadas imagens de capas feitas por upload)

Depois de realizar o upload desses arquivos e criar as pastas, edite o arquivo config.php 

define('DB_HOST', 'localhost'); //local de hospedagem <br>
define('DB_NAME', ''); //nome do banco de dados <br>
define('DB_USER', ''); //usuario do banco <br>
define('DB_PASS', ''); //senha do banco de dados <br>
define('UPLOAD_DIR', __DIR__ . '/uploads/'); <br>
define('IMAGES_DIR', __DIR__ . '/images/'); <br>
define('VIDEOS_DIR', __DIR__ . '/videos/'); <br>

No arquivo database.sql fica o banco de dados padrão, importe para o seu mysql.

Acesse o endereço que ficou sua aplicação
Na tela de login, tem 2 usuários padrão
Admin: admin / admin (todas as permissões)
Ouvinte: ouvinte / ouvinte (apenas escutar musicas)

Caso você acesse o endereço pelo celular, ele identifica automaticamente que o navegador é de celular, e redireciona para mobile.php.
Este diretório exclusivo para mobile tem outros recursos como cache, mudança de linguagem, mudança de tema, listagem de artistas, álbuns pesquisa e favoritos.

Espero que você se divirta com esta aplicação, dúvidas e sugestões de novos recursos ou melhorias, leirson@gmail.com.

- Desktop
<img src="https://meuappvirtual.com/uploads/produtos/img_6a174bfd44acc4.70006303.png">
