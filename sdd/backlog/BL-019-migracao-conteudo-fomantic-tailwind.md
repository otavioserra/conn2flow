# BL-019 — Migração de layouts, páginas, templates e recursos para Tailwind

- **Tipo:** Epic/Data Migration/UX
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** dados versionados, banco instalado, editor/preview e catálogos de recursos
- **Relacionados:** BL-014, BL-016, BL-017

## Diagnóstico

Fomantic não está apenas nos assets do shell. Ele aparece em `LayoutsData`, `PaginasData`, `TemplatesData`, `ComponentesData`, recursos administrativos, miniaturas e HTML/CSS persistidos no banco. O layout administrativo canônico ainda declara `framework_css: fomantic-ui`. O editor HTML e alguns widgets carregam Fomantic por CDN em previews próprios.

Remover os arquivos da biblioteca antes de converter esses dados quebraria instalações existentes. Reinstalar manifests por cima também poderia apagar customizações de usuários.

## Escopos separados

### Runtime administrativo

- converter layout, cabeçalho, menu, login e páginas do gestor;
- remover Fomantic como fallback quando `framework_css` estiver vazio;
- atualizar editor/preview e widgets que injetam CDN.

### Catálogo distribuído pelo core

- converter páginas, layouts, templates, componentes, recursos, exemplos e miniaturas;
- definir política para variantes Fomantic: migrar, arquivar em pacote legado ou remover em major version;
- interromper a criação de novos registros com framework Fomantic na v3.

### Conteúdo já instalado/customizado

- detectar por ID/chave natural, framework, assinatura/hash e flag de modificação do usuário;
- atualizar automaticamente apenas registros conhecidos e não modificados;
- oferecer relatório/assistente para conteúdo customizado;
- manter compatibilidade temporária ou stylesheet isolado quando a conversão automática não for segura.

## Ferramentas de migração

- inventário estático e no banco com origem e proprietário;
- transformações idempotentes por versão;
- dry-run com diff de registros e arquivos;
- backup e rollback de metadados;
- mapeamento semântico de componentes, não simples substituição regex de classes;
- screenshots e testes visuais dos recursos canônicos;
- verificador final de CDN, `framework_css`, classes `ui` e inicializadores Semantic.

## Build e overlays

- compilar Tailwind considerando a união do core com o projeto privado;
- safelist derivada de schemas/tokens, não de entrada do usuário;
- conteúdo arbitrário do usuário não pode obrigar execução de build ou injetar classes não confiáveis;
- testar atualização com manifests e campos `preserve_on_user_modified`.

## Critérios de aceite para futura implementação

- o shell v3 não carrega Fomantic localmente nem por CDN;
- registros canônicos usam Tailwind e preservam chaves naturais;
- customizações não são sobrescritas silenciosamente;
- editor/preview representa o runtime real da v3;
- existe relatório para itens que exigem conversão manual;
- busca estática e inventário do banco explicam qualquer referência Fomantic residual durante a janela de compatibilidade.

## Próxima decisão

Escolher política de suporte a conteúdo Fomantic customizado: compatibilidade temporária isolada, conversor assistido ou corte explícito na v3.
