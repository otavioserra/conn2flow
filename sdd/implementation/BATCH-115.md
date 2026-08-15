# BATCH-115 — HTML dinâmico e Tailwind por recurso

- **Intake**: [req-115](../human-requests/req-115.md)
- **Status**: in-progress
- **Data**: 2026-08-14

## Resultado funcional desta rodada

- menu de módulos da Editbar extraído do PHP para componentes localizados;
- textos visíveis migrados para variáveis pt-br/en;
- fontes dinâmicas temporárias declaradas e auditadas nos módulos privados ainda não migrados;
- layout Tailwind próprio do iframe criado, com Preflight por recurso;
- cascata ordenada como layout, dependências e recursos;
- `gestor_componente()` alinhada ao contrato de `css_precompiled`, inclusive para múltiplos IDs;
- atualização/deploy preserva `css_precompiled` mesmo quando o conteúdo autoral tem
  `user_modified`;
- geração incremental, logs e opções direcionadas de atualização validados no core e no projeto.

## Evidência

- homologação visual após limpeza de cache: Editbar próxima da produção e dropdowns recuperados;
- PHPUnit: 268 testes, 1.060 asserções, 4 ignorados e 1 depreciação preexistente;
- sincronização `snapphoton-local`: 173 recursos encontrados, 173 em cache e zero erros;
- meta viewport idêntica nas capturas local/produção; diferença tipográfica residual não foi
  mascarada com escala artificial.

## Continuidade incremental

- `busca-clinica` concluída: sete estados dinâmicos migrados para um componente de templates
  localizado, preenchido por DOM seguro no JavaScript;
- `subscriptions` concluída: cards gratuito, sob medida e pago migrados para componentes próprios,
  selecionados e preenchidos pelo controlador;
- o projeto passou de seis para dois recursos com `tailwind_sources`; restam apenas as versões
  pt-br/en do bridge de `snapphoton-system`;
- extrair `snapphoton-system` por famílias de tela (busca/síntese, documentos/editor,
  categorias/cache/arquivos) antes de remover sua cobertura transitória;
- migrar gradualmente os demais literais HTML relevantes de PHP/JavaScript conforme o inventário
  do intake;
- manter auditoria de novas fontes dinâmicas e cobertura de utilities exclusivas.

## Evidência da rodada 2

- gerador privado: 66 recursos encontrados e recompilados, zero erros, dois recursos com duas
  fontes adicionais;
- `node --test tests/js/busca-clinica-runtime-fragments.test.cjs
  tests/js/subscriptions-checkout-components.test.cjs` → 4/4;
- `node --check` da busca clínica, `php -l` da busca clínica e de assinaturas e
  `git diff --check` → OK;
- escala global mantida no padrão de 16px; o desvio visual era zoom de 110% do navegador.
- deploy local via API → HTTP 200; sincronização forçada e limitada a `componentes` → 43 sem
  alteração; leitura da base `photon` confirmou oito recursos novos com precompiled preenchido e
  `user_modified=0`.

## Correção de cascata — bundle canônico por página

- **Causa-raiz**: concatenar saídas Tailwind independentes perde a ordem global dos utilitários.
  Na Busca Clínica, `.hidden` de um sidecar posterior anulava `lg:flex` do layout e invertia o menu
  desktop/mobile.
- **Contrato novo e opt-in**: páginas que definem `$_GESTOR['tailwind-page-bundle']` emitem
  `page-precompiled`; nesse modo o runtime exclui apenas os demais sidecars Tailwind isolados e
  mantém CSS autoral e o CSS do editor.
- **Primeira rota migrada**: `busca-clinica-dashboard` compila página + `photon-admin` + fragmentos
  dinâmicos + templates clínicos default/variation em um único sidecar de 22.427 bytes.
- **Template corrigido**: a resolução SQL e o fallback físico passaram a devolver
  `css_precompiled`; o recurso entra como dependência quando a rota não usa bundle.
- **Evidência estática**: `hidden` precede `lg:flex`; `max-h-56` e `lg:grid-cols-3` estão presentes;
  lint PHP e `git diff --check` aprovados.
- **Evidência de entrega**: sincronização do core fonte, deploy privado HTTP 200 e hashes idênticos
  no destino para `gestor.php`, `bibliotecas/gestor.php`, controlador da busca e sidecar da página.
- **Regressão**: Core com 269 testes/1.064 asserções; projeto com 5/5 testes Node; quatro testes do
  Core ignorados e uma depreciação já conhecida.
- **Próximo passo**: aplicar o mesmo contrato, com dependências declaradas por rota, antes de
  remover o bridge final de `snapphoton-system`.

## Correção pós-deploy — camadas globais do bundle

- `tailwind_bundle` diferencia página canônica de sidecar isolado no descriptor e no fingerprint;
- bundles importam o input central completo e preservam `theme`, `base` e Preflight;
- Busca Clínica foi recompilada com 26.476 bytes, `@layer theme`, `@layer base`, `max-h-56` e
  `lg:grid-cols-3`;
- Editbar passou a compilar página + layout iframe + quatro componentes em um único sidecar por
  idioma, com `group-hover:block` e Preflight presentes;
- teste focal do Core: 16 casos e 94 asserções; teste estrutural privado: 3/3.

## HANDOFF — estado em 2026-08-14 21:25 BRT

### Causa-raiz confirmada

O primeiro deploy do `page-precompiled` não sobrescreveu os commits. Ele ativou um bundle que
continha apenas `@layer utilities`; como o runtime suprimiu o sidecar do layout, desapareceram
`@layer theme`, `@layer base`, Preflight, fonte Inter e tokens centrais. O snapshot que comprovou
isso é `lumix/temp/busca-clinica.html`: o primeiro `<style data-tailwind-role="page-precompiled">`
começava em `@layer properties`/`@layer utilities`, sem theme/base.

### Já implementado no working tree, ainda sem commit

- `tailwind_bundle: true` faz `tailwind_recursos_input_temporario()` importar o input central
  completo; o fingerprint inclui o modo bundle;
- bundle é restrito a páginas e exige layout/dependências;
- `dashboard_site_toolbar()` ativa `$_GESTOR['tailwind-page-bundle']`;
- `dashboard-site-toolbar` declara quatro componentes como dependências e infere o layout pelo
  campo `layout` da página;
- iniciou-se a substituição de caminhos por `tailwind_dependencies` semânticas. O resolver aceita
  `type`, `id`, `module`, `language` e `scope=global`, resolve arquivos somente no build e rejeita
  IDs com separadores de caminho;
- para layouts compartilhados entre idiomas existe `tailwind_layout_language` (necessário na página
  EN do Busca Clínica, que reutiliza o layout PT-BR).

### Estado incompleto que o próximo agente deve corrigir primeiro

1. Em `lumix/gestor/modulos/busca-clinica/busca-clinica.json`, substituir os quatro
   `tailwind_sources` físicos de cada idioma por dependências semânticas:
   - componente `busca-clinica-runtime-fragments` no módulo/idioma atual;
   - templates `clinical-search-result-default` e `clinical-search-result-variation` do módulo
     `snapphoton-system`, idioma `pt-br`;
   - não declarar o layout na lista: ele é inferido de `layout: photon-admin`;
   - na página EN, definir `tailwind_layout_language: pt-br`, pois não existe
     `gestor/resources/en/layouts/photon-admin` no projeto;
   - trocar `tailwind_sources_reason` por `tailwind_dependencies_reason`.
2. Atualizar `tests/js/busca-clinica-runtime-fragments.test.cjs`: esperar três dependências
   semânticas, ausência de `tailwind_sources` na página e `tailwind_layout_language=pt-br` em EN.
3. Corrigir em `tailwind-recursos.php` a mensagem de validação que ainda diz
   “sem declarar tailwind_sources”; o contrato agora deve mencionar dependências/fontes resolvidas.
4. Adicionar testes unitários para `tailwind_recursos_dependencies()`:
   - infere layout global pelo ID;
   - resolve componente do módulo atual;
   - resolve template de outro módulo/idioma;
   - recusa dependência inexistente e tentativa de path traversal;
   - não depende de banco nem executa no runtime.
5. Executar novamente geradores sem `--tailwind-force` primeiro; o fingerprint mudou e deve
   recompilar apenas os bundles afetados. Depois validar que os sidecars de Busca Clínica e Editbar
   contêm `@layer theme`, `@layer base`, `box-sizing:border-box` e suas utilities exclusivas.

### Validações feitas antes do refactor semântico

- Core focal: 16 testes, 94 asserções;
- projeto: 3 testes Node aprovados;
- bundle Busca Clínica com theme/base, `max-h-56`, `lg:grid-cols-3` e `hidden` antes de `lg:flex`;
- bundle Editbar com theme/base, Preflight e `group-hover:block`;
- essas validações precisam ser repetidas porque `tailwind_dependencies` foi editado depois delas.

### Estado do ambiente local e armadilha de deploy

- deploy privado `snapphoton-local` concluiu com HTTP 200 e atualizou três páginas;
- `sync-core-to-project.sh` sincronizou o Core atual;
- **não usar `update-system.sh --mode only-db` para testar working tree**: apesar do nome, a API
  executou `deploy_files` e copiou 14 arquivos do release antigo `gestor-v2.9.35`;
- os três arquivos divergentes detectados (`dashboard.php`, `dashboard.json`, `PaginasData.json`)
  foram restaurados do workspace e a tabela `paginas` foi forçada dentro do container com:
  `docker exec conn2flow-app php /var/www/sites/localhost/photon/controladores/atualizacoes/atualizacoes-banco-de-dados.php --tables=paginas --force-all`;
- essa atualização resultou em 253 updates e 3 registros sem alteração. Após o refactor semântico,
  sincronizar/recompilar/deployar novamente antes da homologação final.

### Ordem de build confirmada

- Core: `ai-workspace/en/scripts/releases/release.sh` gera recursos na linha 51 e só remove
  `resources/` da cópia temporária nas linhas 131–132;
- projeto: `deploy-project-v2.sh` executa `update-resource-data.sh` antes de criar o ZIP e exclui
  `resources/` na compactação;
- portanto o compilador pode resolver arquivos físicos somente durante o build. O artefato final e
  o runtime não podem fazer essa resolução; usam `Data.json`/banco e `css_precompiled`.

### Git e arquivos gerados

- último commit publicado do Core: `ef2fac2a`;
- último commit publicado do Lumix: `ea323e1`;
- mudanças desta correção pós-deploy estão sem commit;
- preservar alterações prévias/ruído já existente: `schema-metadata.json`, `LayoutsData.json`,
  `resources/pt-br/layouts.json` e reserialização de `snapphoton-system.json` devem ser revisados e
  não incluídos automaticamente;
- `PaginasData.json`, sidecars dos dois bundles e o manifesto Tailwind devem entrar apenas após a
  recompilação final bem-sucedida.
