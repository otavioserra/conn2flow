<?php

// Script para traduzir interface_en.json
$file = __DIR__ . '/dictionaries/interface_en.json';

if (!file_exists($file)) {
    die("Arquivo não encontrado: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Erro ao decodificar JSON\n");
}

$translations = [
    "Caminho" => "Path",
    "Clique nesse botão para GRAVAR as alterações." => "Click this button to SAVE the changes.",
    "Enviar" => "Send",
    "Opções" => "Options",
    "Data Criação" => "Creation Date",
    "Data Modificação" => "Modification Date",
    "Clique para Editar" => "Click to Edit",
    "Clique para Ativar" => "Click to Activate",
    "Clique para Desativar" => "Click to Deactivate",
    "Clique para Excluir" => "Click to Delete",
    "Clique para Adicionar." => "Click to Add.",
    "Desativar" => "Deactivate",
    "Ativar" => "Activate",
    "Confirmação de Deleção" => "Deletion Confirmation",
    "Você tem certeza que quer excluir este ítem?" => "Are you sure you want to delete this item?",
    "Cancel" => "Cancel",
    "Confirm" => "Confirm",
    "alterou <b>#campo#</b> de <b>#valor_antes#</b> para <b>#valor_depois#</b>" => "changed <b>#campo#</b> from <b>#valor_antes#</b> to <b>#valor_depois#</b>",
    "alterou <b>#campo#</b>" => "changed <b>#campo#</b>",
    "Carregar Mais" => "Load More",
    "Alert" => "Alert",
    "Ok" => "Ok",
    "A tentativa de conexão com o servidor chegou no seu limite de tempo. Houve alguma falha na sua conexão com o mesmo ou então algum erro de programação não detectável automaticamente. Tente novamente, se o problema persistir, favor entrar em contato com o suporte técnico." => "The connection attempt to the server has reached its time limit. There was a failure in your connection or a programming error that cannot be automatically detected. Try again, if the problem persists, please contact technical support.",
    "alterou <b>#campo#</b> para <b>#valor_depois#</b>" => "changed <b>#campo#</b> to <b>#valor_depois#</b>",
    "removeu o registro do sistema" => "removed the record from the system",
    "É obrigatório preencher o campo <b>#label#</b>." => "It is mandatory to fill in the field <b>#label#</b>.",
    "O campo <b>#label#</b> deve ter no mínimo 3 caracteres." => "The field <b>#label#</b> must have at least 3 characters.",
    "O campo <b>#label#</b> deve ter no máximo 100 caracteres." => "The field <b>#label#</b> must have a maximum of 100 characters.",
    "O campo <b>#label#</b> é obrigatório. Deve ter no mínimo <b>#min#</b> e no máximo <b>#max#</b> caracteres." => "The field <b>#label#</b> is mandatory. It must have a minimum of <b>#min#</b> and a maximum of <b>#max#</b> characters.",
    "Ocorreu um erro na obtenção de uma variável." => "An error occurred in obtaining a variable.",
    "Variables" => "Variables",
    "Clique para alterar as Variáveis" => "Click to change the Variables",
    "Edit" => "Edit",
    "Nenhum(a)" => "None",
    "É obrigatório definir um email válido no campo <b>#label#</b>." => "It is mandatory to define a valid email in the field <b>#label#</b>.",
    "O campo <b>#campo-1#</b> é diferente do campo <b>#campo-2#</b>. É obrigatório que ambos tenham o mesmo valor." => "The field <b>#campo-1#</b> is different from the field <b>#campo-2#</b>. It is mandatory that both have the same value.",
    "O campo <b>#label#</b> deve ter no mínimo 12 caracteres." => "The field <b>#label#</b> must have at least 12 characters.",
    "O campo  <b>#label#</b> precisa ter no mínimo <b>um caracter minúsculo</b>,  no mínimo <b>um caracter maiúsculo</b>,  no mínimo <b>um número</b> e pelo menos um caracter especial igual a <b>!@#$%^&*</b>." => "The field <b>#label#</b> needs to have at least <b>one lowercase character</b>, at least <b>one uppercase character</b>, at least <b>one number</b> and at least one special character equal to <b>!@#$%^&*</b>.",
    "É obrigatório selecionar pelo menos uma opção do campo <b>#label#</b>." => "It is mandatory to select at least one option from the field <b>#label#</b>.",
    "Não alterar o identificador único se modificar o campo Nome." => "Do not change the unique identifier if you modify the Name field.",
    "Identificador" => "Identifier",
    "Já existe cadastro do campo  <b>#label#</b> com o mesmo valor preenchido. Favor escolher outro  <b>#label#</b> e tentar novamente." => "There is already a registration for the field <b>#label#</b> with the same value filled. Please choose another <b>#label#</b> and try again.",
    "Só é permitido no campo <b>#label#</b> os seguintes caracteres : <b>#permited-chars#</b>. Favor remover os caracteres  inválidos e tentar novamente." => "Only the following characters are allowed in the field <b>#label#</b>: <b>#permited-chars#</b>. Please remove the invalid characters and try again.",
    "Já existe cadastro do campo  <b>#label#</b> com o mesmo valor: <b>#value#</b>. Favor escolher outro  <b>#label#</b> e tentar novamente." => "There is already a registration for the field <b>#label#</b> with the same value: <b>#value#</b>. Please choose another <b>#label#</b> and try again.",
    "É necessário definir no campo <b>#label#</b> os seguintes caracteres : <b>#necessary-chars#</b>. Favor incluir os caracteres  e tentar novamente." => "It is necessary to define the following characters in the field <b>#label#</b>: <b>#necessary-chars#</b>. Please include the characters and try again.",
    "Restricted Area" => "Restricted Area",
    "Este local tem dados importantes sensíveis. Portanto, por segurança,  é necessário fornecer novamente a sua senha." => "This location has important sensitive data. Therefore, for security, it is necessary to provide your password again.",
    "Fornecer Senha" => "Provide Password",
    "Houve um erro na recuperação dos dados do registro que se quer alterar. É possível que o mesmo possa ter sido excluído. " => "There was an error in retrieving the data of the record you want to change. It is possible that it may have been deleted.",
    "Add" => "Add",
    "Remover" => "Remove",
    "imagem" => "image",
    "Selecione uma imagem abaixo..." => "Select an image below...",
    "O arquivo selecionado <b>NÃO</b> é uma imagem. " => "The selected file <b>is NOT</b> an image.",
    "alterou <b>#campo#</b> removendo <b>#valor_antes#</b> e incluindo <b>#valor_depois#</b>" => "changed <b>#campo#</b> removing <b>#valor_antes#</b> and including <b>#valor_depois#</b>",
    "<h3>Houve um <span class=\"ui error text\">erro</span> na comunicação com o seu hospedeiro!</h3><p>A API do cliente retornou o seguinte erro: #error-msg#</p>" => "<h3>There was an <span class=\"ui error text\">error</span> in communication with your host!</h3><p>The client API returned the following error: #error-msg#</p>",
    "Trocar" => "Change",
    "Selecione uma template abaixo..." => "Select a template below...",
    "Não foi possível carregar o widget: <span class=\"ui error text\">#msg#</span>" => "Could not load the widget: <span class=\"ui error text\">#msg#</span>",
    "categoria padrão não encontrada" => "default category not found",
    "Clique para trocar o template atual." => "Click to change the current template.",
    "Clique para adicionar uma imagem." => "Click to add an image.",
    "Clique para remover esta imagem." => "Click to remove this image.",
    "O campo <b>#campo-1#</b> tem que ser maior ou igual a zero." => "The field <b>#campo-1#</b> must be greater than or equal to zero.",
    "O campo <b>#label#</b> não é um domínio válido." => "The field <b>#label#</b> is not a valid domain."
];

foreach ($data['variables'] as $key => $value) {
    if (isset($translations[$value])) {
        $data['variables'][$key] = $translations[$value];
        echo "Traduzido: $key\n";
    }
}

$newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($file, $newContent);

echo "✅ Tradução concluída!\n";

?>