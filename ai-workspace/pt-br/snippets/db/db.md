# Snippets de Banco de Dados
Este arquivo contém snippets de funções úteis para operações comuns em bancos de dados no Conn2Flow. Eles podem ser usados como referência ou adaptados conforme necessário para atender às necessidades específicas de uma implementação.

## Agentes
Você pode usar os seguintes snippets para interagir com agentes no banco de dados. Bem como caso haja necessidade, criar novos snippets e editar esse arquivo.

## Biblioteca de Banco de Dados
A biblioteca de banco de dados do Conn2Flow está localizada em `gestor\bibliotecas\banco.php`. Caso não encontre o que precisa nos snippets abaixo, consulte a biblioteca para funções adicionais.

## Snippets

```php
// [1] Selecionar vários dados e interar sobre os resultados (conecta automaticamente no banco de dados)
$nome_da_tabela = banco_select_name
(
    banco_campos_virgulas(Array(
        'campo_nome1',
        'campo_nome2',
        'campo_nomeN',
    ))
    ,
    'nome_da_tabela',
    "WHERE sql_condicional"
);

if($nome_da_tabela){
	foreach($nome_da_tabela as $item){
        // Usar os dados selecionados
        $campo1 = $item['campo_nome1'];
        $campo2 = $item['campo_nome2'];
        $campoN = $item['campo_nomeN'];
    }
}

// [2] Selecionar uma única linha de dados (conecta automaticamente no banco de dados)
$nome_da_tabela = banco_select(Array(
    'unico' => true,
    'tabela' => 'nome_da_tabela',
    'campos' => Array(
        'campo_nome1',
        'campo_nome2',
        'campo_nomeN',
    ),
    'extra' => 
        "WHERE sql_condicional"
));

// Usar os dados selecionados
$campo1 = $nome_da_tabela['campo_nome1'];
$campo2 = $nome_da_tabela['campo_nome2'];
$campoN = $nome_da_tabela['campo_nomeN'];

// [3] Inserir dados (conecta automaticamente no banco de dados)
$campos = null;
$campo_sem_aspas_simples = false;

// Registro sem filtros
$campo_nome = "campo_nome"; $campo_valor = "campo_valor"; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
// Registro com filtro de escape e verificação de existência enviada via formulário
$campo_nome = "campo_nome2"; $post_nome = "post_nome";      			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
// Registro usando função NOW() do MySQL, ou para enviar valor sem aspas simples
$campo_nome = "campo_nome3"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);

banco_insert_name
(
    $campos,
    "nome_da_tabela"
);

// [4] Editar dados (conecta automaticamente no banco de dados)
// Campos do banco de dados a serem verificados antes de iniciar a edição
$camposBanco = Array(
    'campo_nome1',
    'campo_nome2',
    'campo_nomeN',
);
// Recuperar o estado dos dados do banco de dados antes de editar.
if(!banco_select_campos_antes_iniciar(
    banco_campos_virgulas($camposBanco)
    ,
    "nome_da_tabela",
    "WHERE sql_condicional"
)){
    interface_alerta(Array(
        'redirect' => true,
        'msg' => gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-database-field-before-error'))
    ));
    
    gestor_redirecionar_raiz();
}

// Preparar os dados para edição
$editar = Array(
    'tabela' => "nome_da_tabela",
    'extra' => "WHERE sql_condicional",
);

// Registro sem filtros
$campo_nome = "campo_nome1"; $campo_valor = "campo_valor"; $editar['dados'][] = $campo_nome."='" . $campo_valor . "'";
// Registro com filtros e verificação de alteração
$campo_nome = "campo_nome2"; $request_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";}

// Executar a edição
if(isset($editar['dados'])){
    $editar['sql'] = banco_campos_virgulas($editar['dados']);
    
    if($editar['sql']){
        banco_update
        (
            $editar['sql'],
            $editar['tabela'],
            $editar['extra']
        );
    }
    $editar = false;
}

// [5] Deletar dados (conecta automaticamente no banco de dados)
banco_delete(
    "nome_da_tabela",
    "WHERE sql_condicional"
);
```