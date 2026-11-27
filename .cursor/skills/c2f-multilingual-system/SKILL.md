---
name: c2f-multilingual-system
description: "LEIA ANTES de adicionar novos idiomas, rotas i18n ou consultas multi-idioma. Se não ler: conteúdo de idiomas diferentes vaza na mesma página ou URLs internacionais quebram com erro 404."
user-invocable: false
---

# Sistema Híbrido Multilíngue Conn2Flow (`CONN2FLOW-MULTILINGUAL-HYBRID-SYSTEM.md`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Manipular tabelas com coluna `language`, registrar rotas internacionalizadas (`/en/`, `/pt-br/`) ou gerenciar dicionários de idiomas.
- **SKIP APENAS SE**: Módulos utilitários globais agnósticos a idioma.
- **CONSEQUÊNCIA DE IGNORAR**: Vazamento de registros em inglês na visualização em português (e vice-versa), falha na resolução de slugs traduzidos e desindexação de SEO.

---

Consulte e aplique as seguintes convenções ao desenvolver para suporte a múltiplos idiomas no Conn2Flow:

## 1. Estrutura de Idiomas e Resolução

* **Idiomas Ativos** — `LANGUAGES` no `.env` define `$_GESTOR['languages']` em runtime. A base típica é `pt-br` (padrão) e `en`. Qualquer idioma adicional (`es`, `de`, `fr`) exige o checklist completo para aparecer na interface.
* **Código de Idioma Ativo**: Acessível via `$_GESTOR['linguagem-codigo']`.
* **Estrutura FÍSICA de Pastas**:
  - Recursos globais: `gestor/resources/<idioma>/` (ex: `pt-br/pages/`, `en/pages/`).
  - Recursos de módulo: `modulos/<modulo>/resources/<idioma>/`.

---

## 2. Tabelas Relacionais e Chave Natural (`language`)

* Tabelas com suporte multilíngue possuem a coluna `language VARCHAR(10) NOT NULL DEFAULT 'pt-br'`.
* A chave única natural é geralmente composta por `['id', 'language']` (ou `['language', 'module', 'id']`).

---

## 3. Ferramentas de Tradução

* Scripts de automação em `ai-workspace/en/scripts/translates/` para geração e sincronização de dicionários e recursos em novos idiomas.

## 4. Checklist Inviolável de Ativação de Novo Idioma

Ao iniciar a tradução ou ativação de `<lang>`, confira os requisitos e valide o fluxo antes da entrega.

1. **Ative `LANGUAGES` no `.env`**. Confira `gestor/autenticacoes/<dominio>/.env` no Lab/local e `gestor/autenticacoes.exemplo/dominio/.env` como template. Em `gestor/config.php`, `$_GESTOR['languages'] = $_ENV['LANGUAGES'] ? explode(',', $_ENV['LANGUAGES']) : [];`. Inclua o código na lista CSV, por exemplo `LANGUAGES=pt-br,en,es`. Com o código ausente, o painel, `/admin-environment/` e o filtro de idiomas do roteador ignoram seus recursos mesmo quando já sincronizados no SQL. Registre no recibo do lote o alerta para atualizar manualmente o `.env` em Dev/Staging/Produção.
2. **Confira `resources.map.php`**. Declare `<lang>` em `gestor/resources/resources.map.php` do projeto e confira o mapa do Core se a ativação depender de recursos do Core.
3. **Cadastre `language-label-<codigo>` no `variables.json` de todos os idiomas ativos**. Tanto `admin-environment.php` quanto o seletor do painel em `gestor.php` buscam o nome via `gestor_variaveis(['id' => 'language-label-' . $lang])`.
4. **Varrer listas hardcoded**. Audite módulos, layouts, topbars e telas de perfil como `user-settings`. Troque listas fixas como `['pt-br', 'en']` pela leitura de `$_GESTOR['languages']`.
5. **Teste E2E no fluxo real, em navegador autenticado**. Abra a aba Linguagem de `/admin-environment/` e confirme `<lang>` em `#language_default`. Use o seletor de idiomas do painel para alternar `pt-br → <lang> → pt-br` e confira a interface traduzida sem chaves cruas. A entrega exige essas duas verificações além de eventuais testes por URL/cookie direto.
