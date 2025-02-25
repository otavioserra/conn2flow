<?php
/*
Language: Português
File: lang/pt.php
*/ 


$lang = array(
    "decimal seperator"             => ".",
    "thousands separator"           => ",",
    "datetime format"               => "d/m/Y H:i", // www.php.net/manual/en/function.date.php
    "Insert"                        => "Inserir",
    "File"                          => "Arquivo",
    "Root"                          => "Raiz",
    "Close"                         => "Fechar",
    "New folder"                    => "Nova Pasta",
    "Upload"                        => "Carregar",
    "Clipboard"                     => "Prancheta",
    "items"                         => "itens",
    "Change view"                   => "Modificar a Visualização",
    "View"                          => "Visualização",
    "Large images"                  => "Imagens grande",
    "Small images"                  => "Imagens pequenas",
    "List"                          => "Lista",
    "Details"                       => "Detalhes",
    "Tiles"                         => "Miniaturas",
    "Content"                       => "Conteúdo",
    "Show the preview pane"         => "Mostrar pré-visualização",
    "Help"                          => "Ajuda",
    "Search"                        => "Buscar",
    "All files"                     => "Todos os arquivos",
    "Dimensions"                    => "Dimensões",
    "Filename"                      => "Nome",
    "Filetype"                      => "Tipo",
    "Size"                          => "Tamanho",
    "Modified on"                   => "Data de modificação",
    "Directory"                     => "Pasta",
    "Add a new folder"              => "Adicionar uma nova pasta",
    "New folder is created in"      => "Nova pasta será criado em",
    "Name of the new folder"        => "Nome da nova pasta",
    "Create folder"                 => "Criar uma nova pasta",
    "Upload a new file"             => "Carregar um novo arquivo",
    "Image editor"                  => "Editor de imagem",
    "Browse..."                     => "Localizar...",
    "Upload queue"                  => "Carregar fila",
    "Currently uploading in folder" => "Atualmente carregados na pasta",
    "Select your file"              => "Selecione o(s) seu(s) arquivo(s)",

    //Context menu
    "Edit"                          => "Editar imagem",
    "Copy"                          => "Copiar",
    "Cut"                           => "Recortar",
    "Paste"                         => "Colar",
    "Delete"                        => "Excluir",
    "Do you really want to delete this folder and its contents?" => "Você realmente deseja excluir essa pasta e os seus conteúdos?",
    "Do you really want to delete this file?"                    => "Você realmente deseja excluir esse arquivo?",
    "Do you really want to delete this image?"                   => "Você realmente deseja excluir essa imagem?",
    "Open"                          => "Abrir",
    "Refresh"                       => "Atualizar",
    "Rename"                        => "Renomear",

    //Error, success and general messages
    "The folder path was tampered with!"                         => "O caminho da pasta foi violado!",
    "Creating new folder failed!"                                => "A criação da nova pasta falhou!<br />Esses caracteres não são permitidos: ^ \\ / ? * \" ' &lt; &gt; : |",
    "A new folder was created!"                                  => "Uma nova pasta foi criada!",
    "Creating the new folder failed!"                            => "A criação da nova pasta falhou!",  
    "The files where successfully copied!"                       => "Os arquivos foram copiados com sucesso!",
    "The file or folder path was tampered with!"                 => "O caminho do arquivo ou pasta foi violado!",
    "Deleting file failed!"                                      => "A exclusão do arquivo falhou!",
    "Deleting folder failed!"                                    => "A exclusão da pasta falhou!",
    "%d file(s) successfully removed!"                           => "%d arquivo(s) removidos com sucesso!",
    "Select only one file to insert!"                            => "Selecione apenas uma arquivo para inserir!",
    "Insert cancelled because there is no target to insert to!"  => "Inserção cancelada porque não há alvo para inserir!",
    "Directory already exists!"                                  => "Pasta já existente!",
    "File already exists!"                                       => "Arquivo já existente!",
    "Name successfully changed!"                                 => "Nome modificado com sucesso!",
    "Rename failed!"                                             => "Renomeamento falhou!",
    "Please give a new name for file"                            => "Por favor dê outro nome para {0}{1}A extensão do site será preenchida automaticamente!{1}(Esses caracteres não são permitidos: {2})",
    "Please give a new name for folder"                          => "Por favor dê outro nome para {0}{1}(Esses caracteres não são permitidos: {2})",
    "Action not allowed!"                                        => "Ação não permitida!",
    "Invalid characters used!"                                   => "Esses caracteres são permitidos: ^ \\\ / ? * \\\" ' &lt; &gt; : | .",

    //Upload
    "Upload limited to %d MB!"      => "Carregamento limitado a %d MB!",
    "bytes"                         => "bytes",
    "kB"                            => "kB",
    "MB"                            => "MB",
    "Cancel all uploads"            => "Cancelar todos os carregamentos",

    //Settings
    "Settings"                      => "Preferências",
    "Language"                      => "Linguagem",
    "Theme"                         => "Tema",
    "Cookies need to be enabled to save your settings!"          => "Cookies são necessários para salvar suas preferências!",
    "Save settings"                 => "Salvar preferências",
    "Settings saved!"               => "Preferências salvas!",
	
	"Absolute URL with hostname"    => "URL absoluta com o hostname",

    "EOF" => TRUE
);

foreach($lang as $key => $val)
	$lang[$key] = $val;

?>