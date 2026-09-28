---
name: c2f-gestor-functions
description: "LEIA ANTES de chamar funções do núcleo gestor.php (layouts, sessões, redirecionamentos, componentes). Se não ler: redirecionamentos emitem exit() no meio de rotinas, componentes quebram por escopo ou sessões expiram incorretamente."
user-invocable: false
---

# Funções da Biblioteca de Gestor (`gestor.php`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Invocar ou refatorar chamadas a funções centrais de `gestor/bibliotecas/gestor.php` (`gestor_componente`, `gestor_redirecionar`, `gestor_pagina_*`, `gestor_usuario`).
- **SKIP APENAS SE**: Código isolado em bibliotecas utilitárias puras sem acoplamento ao runtime do Gestor.
- **CONSEQUÊNCIA DE IGNORAR**: Quebras de fluxo de execução (ex: `gestor_redirecionar_raiz()` abortando gravações), perda de estado de sessão ou falhas na montagem de páginas.

---

Consulte e aplique as seguintes convenções ao utilizar as funções centrais do Gestor no Conn2Flow (`gestor/bibliotecas/gestor.php`):

1. **Obtenção de Componentes Dinâmicos (`gestor_componente`)**:
   - Componente do módulo atual: `gestor_componente(['id' => 'id-comp', 'modulo' => $_GESTOR['modulo-id']])`.
   - Componente de outro módulo: `gestor_componente(['id' => 'formulario-login', 'modulo' => 'autenticacao'])`.
   - Retorno separado de HTML e CSS: `gestor_componente(['id' => 'comp-id', 'return_css' => true])` (retorna array `['html' => ..., 'css' => ...]`).

2. **Gerenciamento de Variáveis do Sistema (`gestor_variaveis` / `gestor_variaveis_alterar`)**:
   - Variável global: `gestor_variaveis(['id' => 'id-var'])`.
   - Variável de módulo: `gestor_variaveis(['modulo' => $_GESTOR['modulo-id'], 'id' => 'id-var'])`.
   - Conjunto completo de variáveis: `gestor_variaveis(['modulo' => 'meu-modulo', 'conjunto' => true])`.
   - Filtrar por prefixo/regex: `gestor_variaveis(['modulo' => 'admin-env', 'conjunto' => true, 'padrao' => 'email-'])`.
   - Alterar variável: `gestor_variaveis_alterar(['modulo' => 'id-modulo', 'id' => 'id-var', 'tipo' => 'string', 'valor' => 'novo valor'])`.

3. **Inclusão Segura de Bibliotecas (`gestor_incluir_biblioteca`)**:
   - Garanta o carregamento idempotente de arquivos em `gestor/bibliotecas/{nome}.php`: `gestor_incluir_biblioteca('comunicacao')`, `gestor_incluir_biblioteca('pdf')`.

4. **Redirecionamentos HTTP (`gestor_redirecionar`)**:
   - Rota interna: `gestor_redirecionar('dashboard')`.
   - Com query string: `gestor_redirecionar('produtos', 'categoria=eletronicos&pagina=1')`.
   - URL externa: `gestor_redirecionar('https://www.google.com', '', true)`.

5. **Manipulação de Sessão (`gestor_sessao_variavel`)**:
   - Definir: `gestor_sessao_variavel('usuario_id', 123)`.
   - Obter: `$id = gestor_sessao_variavel('usuario_id')` (retorna `null` se inexistente).
   - Remover item: `gestor_sessao_variavel_del('chave')`.
   - Limpar sessão: `gestor_sessao_del_all()`.

6. **Inclusão de Componentes na Página (`gestor_componentes_incluir`)**:
   - Incluir simples ou múltiplo: `gestor_componentes_incluir(['id' => 'menu-principal'])` ou `gestor_componentes_incluir(['id' => ['header', 'footer']])`.

7. **Auxiliar de Validação (`existe`)**:
   - Use `if(existe($var))` para validar se strings, arrays, números ou coleções estão definidos e não vazios.

8. **Layouts de Página (`gestor_layout`)**:
   - Retornar layout HTML completo: `gestor_layout(['id' => 'layout-administrativo'])` ou com `'return_css' => true`.

---

## 9. Contrato de `nome_especifico` na Interface

- Quando a tabela de um módulo **não possui a coluna `nome`** (ex: tabelas com `titulo`, `descricao` ou apenas campos técnicos), o array de interface (`interface.php`) **deve** declarar `'nome_especifico'` apontando para o campo equivalente:
```php
$_GESTOR_INTERFACE = [
    'tabela' => 'minha_tabela',
    'nome_especifico' => 'titulo',  // ← Obrigatório se a tabela não tem coluna 'nome'
    // ...
];
```
- **Consequência de Omissão**: Sem `nome_especifico`, o Gestor executa `SELECT nome FROM tabela` que falha com erro SQL ou retorna valores vazios, causando desvios silenciosos na listagem e nos breadcrumbs.

---

## 10. `gestor_redirecionar()` com Caminhos Relativos

- A função `gestor_redirecionar()` **já prefixa automaticamente** o `url-raiz` do projeto e o idioma ativo. Portanto, **sempre passe caminhos relativos**:
```php
// ✅ CORRETO — caminho relativo (a função adiciona url-raiz + idioma)
gestor_redirecionar('modulo/acao');
gestor_redirecionar('dashboard', 'mensagem=ok');

// ❌ ERRADO — caminho absoluto duplica o prefixo
gestor_redirecionar('/pt-br/modulo/acao');
gestor_redirecionar($_GESTOR['url-raiz'] . '/modulo/acao');
```
- **Exceção**: Para URLs externas, passe o terceiro parâmetro como `true`: `gestor_redirecionar('https://externo.com', '', true);`

---

## 11. Roteamento AJAX Público (`<modulo>.ajax.public.php`)

- Para endpoints AJAX que devem funcionar **sem autenticação** (ex: formulários públicos, webhooks de gateway, APIs anônimas), use o arquivo `<modulo>.ajax.public.php` em vez de `<modulo>.ajax.php`.
- **Razão**: O arquivo `.ajax.php` padrão passa por `interface_ajax_iniciar()`, que exige sessão autenticada. O `.ajax.public.php` é roteado pelo Gestor com o flag `without_permission`, ignorando a verificação de sessão.

```
modulos/
  meu-modulo/
    meu-modulo.ajax.php           ← AJAX autenticado (padrão)
    meu-modulo.ajax.public.php    ← AJAX público (without_permission)
```

- **Segurança Mandatória**: Mesmo em endpoints públicos, **sempre valide e sanitize** os dados de entrada. O `without_permission` remove apenas a exigência de sessão, não a responsabilidade de validação:
```php
// meu-modulo.ajax.public.php
$acao = isset($_REQUEST['acao']) ? banco_escape_field($_REQUEST['acao']) : '';
switch ($acao) {
    case 'webhook-gateway':
        // Validar assinatura do webhook, processar dados
        break;
    default:
        echo json_encode(['erro' => 'Ação inválida']);
}
```
