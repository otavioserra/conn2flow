# 🧩 Contexto Especializado: Desenvolvimento de Módulos Gestor (CRUD V2)

Ao atuar no diretório `gestor/modulos/`, você está manipulando a camada de controladores e lógica de negócios administrativa do Conn2Flow.

## ⛔ Regras Invioláveis de Módulos
1. **Arquitetura CRUD V2 (`interface.php`)**:
   - Todo módulo administrativo padrão deve seguir o scaffold canônico V2 baseado em `modulos-grupos` (`c2f-module-crud-scaffolding`).
   - Telas de listagem, adição e edição utilizam a biblioteca `interface.php` (`interface_tabela()`, `interface_formulario()`).
2. **Proibição Estrita de Strings Hardcoded (`variables.json`)**:
   - **NUNCA** insira textos, mensagens de erro, alertas de warning ou labels diretamente em PHP, HTML ou JavaScript (`c2f-variables-system`).
   - Todo texto deve ser registrado em `variables.json` do módulo (ou `gestor/resources/<idioma>/variaveis/`) e referenciado via `gestor_variaveis()` ou `__t()`.
3. **Proteção Contra CSRF e Requisições AJAX**:
   - Formulários utilizam tokens de segurança do Gestor (`_gestor-atualizar`, `_gestor-registro-id`).
   - Requisições AJAX via Vanilla Fetch devem obrigatoriamente incluir `ajax: 'sim'` no payload para evitar erro `403 Forbidden` (`c2f-javascript-ajax`).
4. **Sanitização e Segurança**:
   - Valide tipos de dados, parâmetros numéricos via `banco_escape_field()` e uploads via `c2f-file-system-operations`.
