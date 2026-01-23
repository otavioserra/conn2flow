# Snippets de Modelos
Este arquivo contém snippets de funções úteis para operações comuns de modelos no Conn2Flow relativo a processamento de texto. Eles podem ser usados como referência ou adaptados conforme necessário para atender às necessidades específicas de uma implementação.

## Agentes
Você pode usar os seguintes snippets para interagir com agentes no php. Bem como caso haja necessidade, criar novos snippets e editar esse arquivo.

## Biblioteca de Modelos
A biblioteca de modelos do Conn2Flow está localizada em `gestor\bibliotecas\modelo.php`. Caso não encontre o que precisa nos snippets abaixo, consulte a biblioteca para funções adicionais.

### Funcionalisades de Modelos
- Inclui operações como substituição de variáveis, extração/inserção de blocos delimitados por tags.
- Carregamento de arquivos de template e remoção de seções condicionais. Eles podem ser usados como referência ou adaptados conforme necessário para atender às necessidades específicas de uma implementação.

## Snippets
```php
// [1] Substituir variáveis em um modelo de texto/HTML
$modelo_texto = "Olá, #nome#! Bem-vindo ao #local#.";

$variaveis = array(
    '#nome#' => 'João',
    '#local#' => 'Conn2Flow'
);

// Para substituir a primeira ocorrência de múltiplas variáveis (usando array)
$modelo_processado = modelo_var_troca($modelo_texto, $variaveis);
// Resultado: "Olá, João! Bem-vindo ao Conn2Flow."

// Para substituir a primeira ocorrência de uma única variável
$modelo_processado = modelo_var_troca($modelo_texto, '#nome#', 'Maria');
// Resultado: "Olá, Maria! Bem-vindo ao #local#."

// Para substituir todas as ocorrências de múltiplas variáveis
$modelo_processado = modelo_var_troca_tudo($modelo_texto, $variaveis);

// [2] Extrair uma célula delimitada por tags específicas, tanto para caso pontual quanto para múltiplas ocorrências

// Exemplo de modelo com célula delimitada por tags:
$modelo_texto = "<!-- cel < -->
<div class=\"wrapper\">
    <div>#nome#</div>
    <div>#local#</div>
</div><!-- cel > -->
"

// Inicializar array para armazenar células extraídas
$pessoas = [
    [
        '#nome#' => 'Ana',
        '#local#' => 'São Paulo'
    ],
    [
        '#nome#' => 'Bruno',
        '#local#' => 'Rio de Janeiro'
    ],
    [
        '#nome#' => 'Carla',
        '#local#' => 'Belo Horizonte'
    ],
];

// Extrair o conteúdo da célula 'cel' do modelo '<!-- cel < -->CONTEUDO<!-- cel > -->', colocar o valor na variável $cel['cel'] e substituir a célula no modelo por uma tag de marcador '<!-- cel -->' para posterior processamento se necessário.
$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($modelo_texto,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $modelo_texto = modelo_tag_troca_val($modelo_texto,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

// Iterar na célula extraída
foreach($pessoas as $item){
    // Pegar valores da célula
    $cel_nome = 'cel';
	$cel_aux = $cel[$cel_nome];
    
    // Processar os dados conforme necessário
    $cel_aux = modelo_var_troca($cel_aux, $item);

    // Inserir a célula processada de volta no modelo principal
    $modelo_processado = modelo_var_in($modelo_processado,'<!-- '.$cel_nome.' -->',$cel_aux);
}

// Excluir a célula original do modelo se necessário
$modelo_processado = modelo_var_troca($modelo_processado,'<!-- '.$cel_nome.' -->','');

// [3] Excluir célula de um modelo de texto/HTML
$cel_nome = 'cel'; $modelo_texto = modelo_tag_del($modelo_texto,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');

```