# BL-015 — Migração dos módulos do core para interface v2

- **Tipo:** Epic/Migration
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** consumidores de `interface.php` dentro do core
- **Relacionados:** BL-013, BL-014, BL-016, BL-018

## Inventário inicial

Foram encontradas aproximadamente 468 chamadas estáticas da interface v1 em 35 arquivos PHP do core, sem contar as próprias bibliotecas. As APIs mais frequentes são alertas, validação de obrigatórios, formatação, leitura de variáveis do módulo, inicialização/finalização de fluxo, histórico e backup.

Consumidores de maior risco/volume incluem `perfil-usuario`, `publisher-pages`, `admin-plugins`, `usuarios`, `admin-paginas`, `galleries`, `forms`, `forms-search`, `grupos`, `templates`, `categorias`, `menus`, `layouts`, `componentes`, `publisher` e `admin-arquivos`.

## Estratégia por ondas

### Onda 0 — Contratos e telemetria

- fechar a API v2;
- mapear chamadas v1 em tempo de execução;
- criar adaptadores temporários e regra de CI que bloqueie novas chamadas v1.

### Onda 1 — Piloto

- recuperar `admin-paginas-v2` conforme BL-014;
- validar CRUD, histórico, backup, AJAX, segurança e Tailwind.

### Onda 2 — Módulos CRUD simples

- migrar módulos com poucos hooks e relacionamentos;
- consolidar padrões reutilizáveis de campos, filtros, ações e permissões.

### Onda 3 — Módulos compartilhados e de alto uso

- usuários, grupos, perfis, arquivos, plugins, categorias, menus, layouts, componentes e templates;
- executar testes de regressão entre módulos dependentes.

### Onda 4 — Publisher, forms e fluxos complexos

- publisher e suas extensões, formulários, galerias e módulos com JavaScript/hook próprio;
- validar performance e transações compostas.

### Onda 5 — Remoção

- zerar consumidores v1 no core e nos overlays privados;
- manter compatibilidade externa por janela definida;
- remover a biblioteca v1 somente em release major posterior ao aviso de depreciação.

## Regras por módulo

Cada migração deve registrar:

- chamadas v1 substituídas e contratos v2 utilizados;
- operações de banco e limites transacionais;
- permissões e ações disponíveis;
- componentes Fomantic/DataTables a substituir;
- recursos/manifestos/dados afetados;
- testes de caracterização e critérios visuais;
- compatibilidade com overlays privados que sobrescrevam ou complementem o módulo.

## Critérios de aceite globais

- inventário automático mostra zero novas dependências v1;
- cada onda pode ser lançada e revertida sem misturar arquivos incompatíveis;
- protocolos de módulos privados continuam documentados;
- testes cobrem desktop, mobile, teclado, sessão expirada e respostas AJAX;
- métricas de tempo, queries e memória não pioram além do orçamento aprovado.

## Próxima decisão

Dividir a Epic em batches por onda somente após o piloto provar os contratos. Os overlays privados precisam de requisitos próprios sincronizados, não alterações silenciosas neste backlog.
