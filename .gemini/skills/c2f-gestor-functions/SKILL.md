---
name: c2f-gestor-functions
description: Use ao realizar operações com o gestor Conn2Flow: componentes, variáveis globais/módulo, bibliotecas, sessões, redirecionamentos e layouts.
user-invocable: false
---

# Funções da Biblioteca de Gestor (`gestor.php`)

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
