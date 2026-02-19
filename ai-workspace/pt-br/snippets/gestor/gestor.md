# Snippets de Gestor
Este arquivo contém snippets de funções úteis para operações comuns do gestor no Conn2Flow relativo ao gerenciamento central do sistema. Eles podem ser usados como referência ou adaptados conforme necessário para atender às necessidades específicas de uma implementação.

## Agentes
Você pode usar os seguintes snippets para interagir com agentes no PHP. Bem como caso haja necessidade, criar novos snippets e editar esse arquivo.

## Biblioteca de Gestor
A biblioteca de gestor do Conn2Flow está localizada em `gestor\bibliotecas\gestor.php`. Caso não encontre o que precisa nos snippets abaixo, consulte a biblioteca para funções adicionais.

### Funcionalidades de Gestor
- Inclui operações como renderização de componentes e layouts dinâmicos, gerenciamento de variáveis globais, sessões e autenticação, redirecionamentos, inclusão de bibliotecas e módulos.
- Suporte a componentes HTML/CSS dinâmicos, layouts completos, variáveis do sistema por módulo e idioma, sessões seguras, e muito mais. Eles podem ser usados como referência ou adaptados conforme necessário para atender às necessidades específicas de uma implementação.

## Snippets
```php
// [1] Retorna um componente HTML/CSS dinâmico
// Busca um componente do banco de dados com substituição de variáveis

// Retornar um componente simples por ID descritivo
$componenteHTML = gestor_componente([
    'id' => 'meu-componente'
]);
// Retorna o HTML do componente processado com variáveis do sistema

// Retornar um componente de um módulo dentro do mesmo módulo em execução. Detecta automaticamente o módulo atual usando a variável global $_GESTOR['modulo-id']
$componenteHTML = gestor_componente(Array(
    'id' => 'id-componente',
    'modulo' => $_GESTOR['modulo-id'],
));

// Retornar componente com retorno separado de HTML e CSS
$resultado = gestor_componente([
    'id' => 'meu-componente',
    'return_css' => true
]);
// $resultado['html'] contém o HTML, $resultado['css'] contém o CSS

// Retornar componente de um módulo específico fora do módulo em execução
$componenteHTML = gestor_componente([
    'id' => 'formulario-login',
    'modulo' => 'autenticacao'
]);

// [2] Obter/Alterar variáveis do sistema por módulo. O idioma é detectado automaticamente!
// Busca variáveis armazenadas no banco de dados com cache em memória

// Obter uma variável específica global, ou seja sem estar vinculado a um módulo.
$variavel = gestor_variaveis([
    'id' => 'id-variavel'
]);
// Retorna o valor da variável para o idioma atual

// Obter uma variável de um módulo dentro do mesmo módulo em execução. Detecta automaticamente o módulo atual usando a variável global $_GESTOR['modulo-id']
$variavelNoModulo = gestor_variaveis([
    'modulo' => $_GESTOR['modulo-id'],
    'id' => 'id-variavel'
]);

// Obter uma variável de um módulo fora do módulo em execução
$variavelNoModulo = gestor_variaveis([
    'modulo' => 'meu-modulo',
    'id' => 'id-variavel'
]);

// Obter todas as variáveis de um módulo
$todasVariaveis = gestor_variaveis([
    'modulo' => 'meu-modulo',
    'conjunto' => true
]);
// Retorna array associativo com todas as variáveis do módulo

// Obter variáveis filtradas por padrão regex
$variaveisEmail = gestor_variaveis([
    'modulo' => 'admin-environment',
    'conjunto' => true,
    'padrao' => 'email-'
]);

// Alterar o valor de uma variável do sistema. Opcionalmente 'linguagem' (se não informado, idioma atual '$_GESTOR['linguagem-codigo']').
gestor_variaveis_alterar([
    'modulo' => 'id-modulo',
    'id' => 'id-variavel',
    'tipo' => 'string',
    'valor' => 'novo valor'
]);

// Retorna apenas variáveis cujos IDs contenham 'email-'

// [3] Incluir uma biblioteca específica do sistema
// Para lista de todas as bibliotecas disponíveis, consulte `gestor/config.php` ou então a variável global $_GESTOR['bibliotecas-dados'] ou então `gestor/bibliotecas`.
// Carrega arquivo PHP da pasta bibliotecas usando require_once.

// Incluir biblioteca de comunicação (caso a mesma já tenha sido incluída, não faz nada, mas garante que será carregada)
gestor_incluir_biblioteca('comunicacao');
// Carrega gestor/bibliotecas/comunicacao.php

// Incluir biblioteca de PDF (caso a mesma já tenha sido incluída, não faz nada, mas garante que será carregada)
gestor_incluir_biblioteca('pdf');
// Carrega gestor/bibliotecas/pdf.php

// [4] Redirecionar para uma página ou URL
// Realiza redirecionamento HTTP com suporte a query strings

// Redirecionar para página interna. O sistema adiciona automaticamente o domínio base.
gestor_redirecionar('dashboard');
// Redireciona para /dashboard

// Redirecionar com query string
gestor_redirecionar('produtos', 'categoria=eletronicos&pagina=1');
// Redireciona para /produtos?categoria=eletronicos&pagina=1

// Redirecionar para URL externa
gestor_redirecionar('https://www.google.com', '', true);
// Redireciona para URL externa

// [5] Manipular variáveis de sessão
// Gerenciar dados de sessão do usuário de forma segura

// Definir uma variável de sessão
gestor_sessao_variavel('usuario_id', 123);
// Armazena o ID do usuário na sessão

// Obter uma variável de sessão
$usuarioID = gestor_sessao_variavel('usuario_id');
// Retorna 123 ou NULL se não existir

// Verificar se variável de sessão existe
if(gestor_sessao_variavel('usuario_logado') === 'sim'){
    // Usuário está logado
}

// Remover uma variável de sessão
gestor_sessao_variavel_del('usuario_temp');

// Limpar todas as variáveis de sessão
gestor_sessao_del_all();

// [6] Incluir componentes na página atual
// Adiciona componentes dinâmicos à página sendo processada

// Incluir um componente simples
gestor_componentes_incluir([
    'id' => 'menu-principal'
]);

// Incluir múltiplos componentes
gestor_componentes_incluir([
    'id' => ['header', 'footer', 'sidebar']
]);

// Incluir componente de módulo específico
gestor_componentes_incluir([
    'id' => 'formulario-contato',
    'modulo' => 'contatos'
]);

// [7] Verificar se um dado existe e não está vazio
// Função auxiliar para validação de dados

// Verificar string
if(existe($nome)){
    echo "Nome informado: " . $nome;
}

// Verificar array
$itens = ['item1', 'item2'];
if(existe($itens)){
    echo "Array tem " . count($itens) . " itens";
}

// Verificar variável numérica
$idade = 25;
if(existe($idade)){
    echo "Idade: " . $idade;
}

// [8] Retorna um layout HTML/CSS completo da página. Isso geralmente é feito automaticamente pelo sistema de roteamento do gestor. Mas caso precise, você pode chamar manualmente essa função para obter layouts específicos.
// Busca um layout do banco de dados com estrutura HTML completa

// Retornar layout padrão por ID descritivo
$layoutHTML = gestor_layout([
    'id' => 'layout-administrativo'
]);
// Retorna HTML completo com <!DOCTYPE>, <html>, <head>, <body>

// Retornar layout com retorno separado de HTML e CSS
$resultado = gestor_layout([
    'id' => 'layout-padrao',
    'return_css' => true
]);
// $resultado['html'] contém o HTML, $resultado['css'] contém o CSS

// Retornar múltiplos layouts (array de IDs)
$layoutsHTML = gestor_layout([
    'id' => ['header', 'footer']
]);

```