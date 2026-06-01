# PHPlayer
Player PHP para reprodução de musicas e videos<br>
<br>
Usando PHP e Mysql, você terá um player feito em php, para ser hospedagem em qualquer hospedagem que aceite php e mysql ou criando um servidor local.<br>
No arquivo index.php, direcionado para desktop, você terá diversos recursos, como lista de artistas, favoritos, playlist, usando api last.fm para coletar dados e id3tag.<br>
Por padrão aconselha-se fazer upload das suas músicas para /music, o sistema fará uma varredura do diretório ao clicar nas Configurações, Sincronização e Mídia, o sistema entende o diretório com /music/artista/album/*.mp3 e nos vídeos o diretório /video sem subpastas, uma informação sobre vídeos, servidores compartilhados, os videos podem não ter recurso suficiente para executar com qualidade e podendo causar travamentos.<br>


- Implantação:<br>
Copie os arquivos para o diretório que deseja hospedar seu player.<br>
Segue lista de arquivos<br>
-index.php (arquivo deskop inteiro)<br>
-api.php (arquivo de api)<br>
-mobile.php (arquivo mobile inteiro)<br>
-config.php (configuração de diretórios e banco de dados.<br>
-/upload (diretório de trabalho do upload de musicas pelo config)<br>
-/music (diretório de músicas com permissão 775)<br>
-/video (diretório de videos com permissão 775)<br>
-/images (diretorio onde ficam armazenadas imagens de capas feitas por upload)<br>
<br>
Depois de realizar o upload desses arquivos e criar as pastas, edite o arquivo config.php <br>
<br>
define('DB_HOST', 'localhost'); //local de hospedagem <br>
define('DB_NAME', ''); //nome do banco de dados <br>
define('DB_USER', ''); //usuario do banco <br>
define('DB_PASS', ''); //senha do banco de dados <br>
define('UPLOAD_DIR', __DIR__ . '/uploads/'); <br>
define('IMAGES_DIR', __DIR__ . '/images/'); <br>
define('VIDEOS_DIR', __DIR__ . '/videos/'); <br>
<br>
No arquivo database.sql fica o banco de dados padrão, importe para o seu mysql.<br>
<br>
Acesse o endereço que ficou sua aplicação<br>
Na tela de login, tem 2 usuários padrão<br>
Admin: admin / admin (todas as permissões)<br>
Ouvinte: ouvinte / ouvinte (apenas escutar musicas)<br>
<br>
Caso você acesse o endereço pelo celular, ele identifica automaticamente que o navegador é de celular, e redireciona para mobile.php.<br>
Este diretório exclusivo para mobile tem outros recursos como cache, mudança de linguagem, mudança de tema, listagem de artistas, álbuns pesquisa e favoritos.<br>
<br>
Espero que você se divirta com esta aplicação, dúvidas e sugestões de novos recursos ou melhorias, leirson@gmail.com.<br>

Segue imagens.
<img src="https://meuappvirtual.com/uploads/produtos/img_6a1dad917b02e0.27751437.png"></img><br>
<img src="https://meuappvirtual.com/uploads/produtos/img_6a1dad917b6dd4.52215196.png"></img><br>
<img src="https://meuappvirtual.com/uploads/produtos/img_6a1dad917a4527.17553299.png"></img><br>
<img src="https://meuappvirtual.com/uploads/produtos/img_6a1dad917a86e8.07749400.png"></img><br>
<img src="https://meuappvirtual.com/uploads/produtos/img_6a1dad917ab963.78760558.png"></img><br>
<img src="https://meuappvirtual.com/uploads/produtos/img_6a1dad917ad415.89671941.png"></img><br>


