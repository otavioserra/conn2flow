# Padrões de Módulos CRUD Interface V2 e Segurança

## 1. Arquitetura do Scaffold CRUD V2
- Todo módulo administrativo reside em `gestor/modulos/<modulo>/`.
- O módulo é composto pelo controlador `<modulo>.php`, configuração `<modulo>.json`, e templates em `resources/<idioma>/templates/<modulo>/`.

## 2. Arquivo `variables.json` Mandatório
- É estritamente proibido hardcodar strings visuais em código PHP ou HTML.
- Todas as mensagens, rótulos de campos, títulos e tooltips devem ser declarados no `variables.json` do módulo e referenciados via `gestor_variaveis()`.

## 3. Proteção CSRF e Chamadas AJAX
- Formulários do Gestor são `multipart/form-data` e exigem os campos ocultos:
  * `_gestor-atualizar`: Sinaliza envio válido;
  * `_gestor-registro-id`: Identifica o registro alvo;
  * `ajax`: `sim` para requisições assíncronas;
  * `_gestor-token`: Token CSRF obrigatório para validação server-side.

## 4. Controle de Acesso e Permissões
- Verifique permissões em cada operação com `gestor_acesso(<modulo>, <operacao>)`.

## 5. Regra de 2 Níveis do `HTML_SANITIZE`
- **Nível Público**: Sanitização rigorosa contra XSS para submissões de usuários não autenticados.
- **Nível Administrativo / Live Editor / Agentes**: Bypass 100% para usuários admin autenticados, preservando marcadores de widgets (`<!-- widget:id -->`), scripts autorizados e marcação Tailwind intacta.
