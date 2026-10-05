# Validation Checklist

## BATCH-247 — REQ-238

- [x] CA-A/B: perfil, atalhos e sidebar uniformes nos 22 módulos.
- [x] CA-C1–C7: badges, resize, blueprint, seletor, isolamento, switch e docs privadas.
- [x] CA-D1–D3: cards, títulos, abas funcionais e 38 telas desktop/390px.
- [x] Core JS 537/PHP 1.597; Site JS 13/PHP 7 sem falhas; 261 checks de navegador.
- [x] Pipeline Lab saída 0, manutenção desligada; 775 arquivos com conteúdo idêntico.
- [x] Provas negativas, revisão, versões e evidências registradas.
- [ ] Homologação humana.

Detalhes: [BATCH-247](../implementation/BATCH-247.md) e [inventário](req238-validation.json).

## BATCH-246 — req-237

- [x] Bases Core/Site preservadas e compilação oficial sequencial no Lab.
- [x] 19 novas capas Core e 14 Site, 1024×1024, menos de 100.000 bytes; 70 assets conferidos.
- [x] Dashboard pt-br/en: alturas M/G, topo -20px, links de capa/SVG, arrasto isolado e preferências restauradas.
- [x] Site REQ-100 concluída, 98/98 checks; cinco fixtures removidas.
- [x] Core JS 532 e PHP 1.594 sem falhas; Site JS 13 e PHP 7 aprovados.
- [x] Pipeline final saída 0 e manutenção desligada; screenshots desktop/390px e revisão sem bloqueantes.
- [ ] Homologação humana.

Detalhes: [BATCH-246](../implementation/BATCH-246.md) e [inventário](req237-validation.json).


## BATCH-243 — Tabelas JSON particionadas (req-234)

- [x] Biblioteca `db-data.php` com as quatro funções requeridas e carregamento pelo bootstrap/entradas CLI.
- [x] Round-trip estrito com registros completos, Unicode, floats e limite padrão real de 80 MiB.
- [x] Ausência, corrupção de um byte/hash e manifesto inválido abortam com `RuntimeException`; manifesto inválido não recorre a monolítico antigo.
- [x] Crescimento, redução de partes e retorno a monolítico/array vazio limpam arquivos obsoletos.
- [x] Falha ao gravar a segunda parte temporária preserva a tabela publicada e limpa os temporários já gravados.
- [x] Comparação SQL recebe todas as partes; nenhuma retirada falsa; parte ausente aborta antes de mudanças SQL. SQLite em memória com adaptação apenas de descoberta de colunas MySQL.
- [x] Compilador escreve partes, relê versão/checksum e publica `partitioned`/`total_parts`; sincronizador de plugins também compara o conjunto completo.
- [x] Exportação reversa → ZIP → extração → recuperação de HTML/metadados preserva todos os registros.
- [x] Checksums detectam mudanças além da primeira parte e ignoram alterações do timestamp de geração.
- [x] PHPUnit focado aprovado; `php -l` e `git diff --check` limpos.
- [x] Suíte geral comparada com checkout limpo de `3e2ad2e9`: mesmos 3 erros de Stripe e 2 falhas de CRLF.

Evidências, comandos e limites: [BATCH-243](../implementation/BATCH-243.md). Banco principal e arquivos de outros lotes preservados; validação com fixtures temporárias, sem deploy de instalação.

## BATCH-239 — Integração Oficial da V3.0 e Rota Canônica das Capas (req-230)

- [x] CA-1 (Capas 200 OK): Todas as capas dos módulos carregam com sucesso (HTTP 200 OK) no Dashboard sem erro 404 (validado via curl e `req227-covers-test.php` 13/13).
- [x] CA-2 (Branches Integradas): Frentes `feat/req-226`, `req-227`, `feat/req-228` e `feat/req-229` consolidadas na branch `main`.
- [x] CA-3 (Navegação & UI Fluida): Topbar com perfil e favoritos, abas do dashboard, seletor P/M/G e cartões integrados sem conflitos de estilização ou quebra de layout.
- [x] CA-4 (Suíte de Testes Aprovada): `GestorStateTest` (28/28), `LayoutAdministrativoTailwindTest` (15/15), `PainelTailwindReq224Test` (2/2), `TailwindRecursosTest` (21/21), `req228-topbar-test.php` (28/28) e `req227-cards-test.cjs` (6/6) com 100% de sucesso.
- [x] CA-5 (Limpeza de Worktrees): Worktrees prontas para desalocação segura após confirmação da mesclagem.

Evidências: [BATCH-239](../implementation/BATCH-239.md).

## BATCH-234 — Conferência final da UI Tailwind (req-225)

- [x] Preparação: dependências conferidas, [inventário preliminar](req225-inventory.md) com 236 páginas/idiomas e [detalhes das divergências](req225-inventory.json).
- [x] Detector contra deriva provado com fixtures: 1 teste/8 asserções; sintaxe PHP aprovada. Auditoria completa focada: 3 testes/12 asserções, 2 falhas e 3 depreciações PHPUnit.
- [ ] CA-1: inventário final de todas as páginas sem pendências, incluindo controles/componentes gerados.
- [ ] CA-2: todas as telas no navegador do Lab, sem Fomantic, overflow a 390 px ou erro de script.
- [ ] CA-3: teste PHPUnit de deriva verde (atualmente detecta 2 ocorrências no core e 493 no site).
- [ ] CA-4: roteiro humano final no arquivão do site, com todas as telas.
- [ ] Integração das frentes dependentes, correções em branch isolada, pipeline e revisão final.

Evidências e limites: [BATCH-234](../implementation/archive/BATCH-234.md). Preparação em 2026-10-04; fontes das árvores locais, sem validação SQL/browser ou alteração do produto. A ordem da req-225 exige conclusão das frentes anteriores antes da conferência final.

## BATCH-238 — Biblioteca de Estado e Acesso Controlado a Variáveis Globais (req-229)

- [x] CA-1 (Funções Implementadas): `gestor_get()`, `gestor_set()`, `gestor_has()` e `gestor_contexto()` criadas e testadas.
- [x] CA-2 (Retrocompatibilidade Integral 100%): Nenhuma alteração quebra códigos legados que ainda utilizam `$_GESTOR['chave']` diretamente.
- [x] CA-3 (Proteção de Chaves do Core): Tentativas de alteração em chaves do sistema protegidas são barradas ou registradas com aviso.
- [x] CA-4 (Suíte de Testes no PHPUnit): Testes unitários cobrindo leitura, escrita, fallbacks e integridade do estado global (28 testes, 93 asserções, 100% passing).
- [x] CA-5 (Documentação Técnica): Guia em `ai-workspace/` (pt-br e en) e atualização da skill `c2f-global-variables` orientando novos desenvolvimentos a utilizarem a nova API.
- [ ] Revisão humana / homologação do Arquiteto.

Detalhes: [BATCH-238](../implementation/BATCH-238.md).

## BATCH-237 — Topbar administrativa (req-228)

- [x] CA-1: menu acessível por clique/teclado, Escape, clique fora, nome/e-mail/perfil; perfil, segurança, PT-BR/EN e logout exercitados no navegador.
- [x] CA-2: personalização com busca, adicionar/remover e favoritar tela atual; clique no atalho abre a tela correspondente.
- [x] CA-3: favoritos persistidos por conta no SQL, restaurados após reload e em contexto de navegador separado; idempotência e isolamento de contas nos testes focados.
- [x] CA-4: editbar real e topbar coexistem em 1366 e 390 px; topo da topbar abaixo da editbar, sem overflow.
- [x] CA-5: 1366/1024/768/390/320 px sem overflow; seis/quatro/zero atalhos diretos, menu de atalhos e perfil contidos na tela.
- [x] 28 testes focados; PHPUnit 112 testes / 530 assertions (três depreciações informadas); Vitest 83/83; navegador 60/60. Falha 503 simulada conserva estado e exibe erro; CSRF sem token retorna 403. Sem erro inesperado de página/console.
- [x] Compilação e pipeline local `project-test`: migração aplicada, SQL e CSS atualizados, 462 arquivos conferidos por hash. Publicação direta `dist/` falhou no rsync do Windows; entrega pelo controlador de arquivos validada.
- [x] Preferências de teste restauradas; sessão encerrada pelo roteiro; nenhum modo de ambiente alterado.
- [ ] Revisão técnica/humana e integração da branch `feat/req-228`.

Evidências: [BATCH-237](../implementation/BATCH-237.md), [roteiro](req228-browser.cjs), [resultado](req228-evidence/results.json), [perfil mobile](req228-evidence/profile-390.png), [editbar desktop](req228-evidence/editbar-1366.png).

## BATCH-231 — Menus, galerias, formulários e cookies (req-222)

- [x] CA-1: páginas administrativas pt-br/en no layout Tailwind, sem assets Fomantic no documento principal.
- [x] CA-2: salvar/recarregar sem 403; selects, chaves, abas, editor, picker/upload e modais; schemas persistidos; 390 px sem overflow.
- [x] CA-3: nenhum diálogo nativo nos seis scripts.
- [x] CA-4 (testes): contrato 4/565, Vitest 509/509 e PHPUnit completo 1526 sem falhas/erros; contrato falsificado no manifesto anterior.
- [x] CA-4 (navegador executado): 369/372 verificações, 30 navegações e capturas desktop/mobile; controle Fomantic preservado.
- [ ] Prévia de Variáveis do editor compartilhado: três verificações de console falham por imagem com placeholder cru/404; pedido registrado na req-219. Não declarar rodada integralmente verde.
- [x] CA-5: [BATCH-231](../implementation/archive/BATCH-231.md), [métricas](evidence-req222.json), roteiro/capturas em `conn2flow-site/sdd/validation/core/evidence-req222/` e item humano no site.
- [ ] Revisão humana conjunta; nenhum deploy de produção.


## BATCH-232 — Módulos de administração em Tailwind (req-223)

- [x] Widget compartilhado `configuracao_administracao` e campos Tailwind pt-br/en; originais legados preservados.
- [x] `variables` e `modulos-variaveis` usam layout/bundle Tailwind e dependências globais declaradas.
- [x] Selects nativos e fallback Fomantic cobertos por Vitest (5/5); PHPUnit focado 13/13, 463 asserções.
- [x] Vitest completo 507/507; transformação Node validou templates, manifests, tokens e minificados.
- [ ] CSS sync/build, inspeção no Lab e viewport de 390 px: não executados por restrição explícita.
- [ ] Suíte PHPUnit completa limpa: 1.523 testes, 4 erros ambientais (OpenSSL/Stripe) e 1 falha LF/CRLF em `CssRegeneracaoTest`.
- [x] Roteiro humano adicionado ao [PENDENCIAS-HUMANAS.md do site](../../../conn2flow-site/sdd/PENDENCIAS-HUMANAS.md).

Detalhes: [BATCH-232](../implementation/archive/BATCH-232.md).

## BATCH-229 — Módulos do editor, listagens e correções da revisão (req-219)

- [x] `admin-layouts` e `admin-componentes` (listar, adicionar, editar) e a listagem do `admin-paginas` no layout Tailwind; salvar sem 403.
- [x] Achados do Engenheiro Chefe: backup do editor, SEO com imagepick, cache da primeira carga (minificação antes da cópia). Barra de rolagem das docs não reproduzida (pedido de print).
- [x] Integração da req-220 conferida no Lab (listagem do `modulos-grupos`).
- [x] PHPUnit 1.505 (1 falha anterior), Vitest 484/484, navegador 31/31.
- [ ] Revisão humana (no fim do programa).

Detalhes: [BATCH-229](../implementation/archive/BATCH-229.md).

## BATCH-227 — Painel em Tailwind: controles, diálogos, editor HTML e `admin-paginas` (req-219)

- [x] CA-1: controles sem Fomantic, com teclado (select com busca, múltiplo e AJAX; chave; abas; diálogos; avisos) — Vitest 13 testes; 390 px sem rolagem no `admin-paginas`.
- [x] CA-2: no layout Fomantic nada muda (`admin-layouts/editar`: ponte desligada, editor na variante antiga, sem erro).
- [x] CA-3: nenhum `alert`/`confirm`/`prompt` nativo no editor e no `admin-paginas`.
- [x] CA-4: `admin-paginas` adicionar/editar/clonar no layout Tailwind, sem JS nem CSS do Fomantic no painel, editor gravando (salvar sem 403).
- [x] CA-5: PHPUnit 1.498 (1 falha anterior, CRLF), Vitest 474/474, navegador 18/18 (`conn2flow-site/sdd/validation/core/req219-controles-e2e.cjs`).
- [ ] Revisão humana (abrir uma página no Lab, editar no visual e salvar).

Detalhes: [BATCH-227](../implementation/archive/BATCH-227.md).

## BATCH-226 — Módulos distribuídos: imagens do layout dentro do painel (req-218)

- [x] CA-1: a logo do portal carrega dentro do painel distribuído (302 → 200; nenhum 404 em `favicon/` no roteiro da REQ-099 do site).
- [x] CA-2: rota de módulo dentro do prefixo mantém o prefixo; destino sempre do mesmo host; 3 testes.
- [ ] Revisão humana.

Detalhes: [BATCH-226](../implementation/archive/BATCH-226.md).

## BATCH-225 — Módulos distribuídos: confirmação de origem e chave de sessão (req-217)

- [x] CA-1: com o segredo, mas fora do endereço cadastrado, nenhuma sessão abre nas duas direções (unidade e Lab, 6/6).
- [x] CA-2: requisição sem sessão, com sessão vencida, forjada, de outra instalação ou assinada com o segredo é recusada.
- [x] CA-3: roteiros dos módulos distribuídos no Lab passam com a confirmação ligada (req092, req094 a req098).
- [x] CA-4: `confirmacao-origem` desligada volta ao canal anterior.
- [ ] Revisão humana.

Detalhes: [BATCH-225](../implementation/archive/BATCH-225.md).

## BATCH-224 — Módulos distribuídos: catálogo local e estado da conta (req-216)

- [x] CA-1: cliente sem lista no `.env` usa o catálogo do pacote; Central sem `app-id` não proxia os próprios módulos.
- [x] CA-2: estado vindo do provedor do projeto, assinado, guardado no cliente; Central fora do ar mantém o último.
- [x] CA-3: conta suspensa: canal recusa escrita e rotina que não é de leitura; formulário recebe "somente visualização".
- [x] CA-4: conta encerrada e perfil sem o módulo saem para o destino do projeto.
- [x] CA-5: testes de unidade; suíte sem regressão; E2E do site 18/18.
- [ ] Revisão humana.

Detalhes: [BATCH-224](../implementation/archive/BATCH-224.md).

## BATCH-223 — Módulos distribuídos: rotina local pedida pelo Central (req-215)

- [x] CA-1: rotina declarada roda no cliente e o retorno chega ao Central; não declarada, de outro módulo, de módulo sem cópia, assinatura inválida e repetição recusadas.
- [x] CA-2: fora de contexto distribuído nada muda.
- [x] CA-3: testes de unidade do protocolo; suíte sem regressão.
- [x] Cópia de execução não contratada fica inerte (página, widget, gancho, tarefa, webhook).
- [x] Parâmetros do endereço chegam ao iframe, limpos.
- [ ] Retorno de autorização de terceiros com aplicativo real.
- [ ] Revisão humana.

Detalhes: [BATCH-223](../implementation/archive/BATCH-223.md).

## BATCH-222 — Menus do painel e apoio à execução no cliente (req-214)

- [x] Item do módulo marcado e no meio do menu, nos dois layouts, em 14 páginas e duas alturas: 61/61.
- [x] Rolagem guardada não tira o item atual do lugar.
- [x] Decisão de cookies lida no servidor; biblioteca registrada no mapa do core.
- [x] Gancho `db.escrita` no cliente; contexto distribuído consultável pelo módulo.
- [ ] Menu de celular do layout Fomantic.
- [ ] Revisão humana.

Detalhes: [BATCH-222](../implementation/archive/BATCH-222.md).

## BATCH-221 — Módulos distribuídos: melhorias da revisão (req-213)

- [x] `confirm()` e nova aba dentro do iframe, em três motores.
- [x] Provedor de instalações: ativa, desativada, desconhecida e falha.
- [x] Custo por consulta medido antes e depois.
- [x] Sobras do login antigo fora do código.
- [x] `project:verify` no fim do pipeline, listando divergências reais.
- [x] Login distribuído com segundo fator ponta a ponta.
- [x] Memória de execução podada (obrigatória em 300 linhas): 296 linhas / 32 KB para 231 linhas, cinco lotes de setembro movidos para o histórico.
- [ ] Revisão humana.

Detalhes: [BATCH-221](../implementation/archive/BATCH-221.md).

## BATCH-220 — Modal de edição do editor visual no documento Tailwind (req-212)

- [x] Modal íntegro nos modos texto, código e imagem; salvar aplica.
- [x] Seletor de arquivos abre na janela do painel, uma vez, e devolve a escolha.
- [x] Documento do editor sem folha do Fomantic; raiz de 16px.
- [x] Demais elementos gráficos do editor abertos e conferidos.
- [ ] Editor visual de layout aberto no navegador.
- [ ] Revisão humana.

Detalhes: [BATCH-220](../implementation/archive/BATCH-220.md).

## Validações arquivadas

| Batch | Resumo | Evidências |
| --- | --- | --- |
| BATCH-219 | Infraestrutura comum para módulos distribuídos (req-211) | [Arquivo](../implementation/archive/BATCH-219.md) |

## BATCH-218 — Tela de atualização no deploy e prévia de widgets completa (req-210)

- [x] Manutenção ligada: 503 com a tela e a logo, JSON para AJAX, CLI e `_api/` isentos.
- [x] Tela volta sozinha ao endereço pedido; página já aberta mostra e retira o aviso.
- [x] Manutenção vencida ou ilegível não bloqueia.
- [x] Deploy do pipeline de projeto sem nenhum 500 (sondagem a cada segundo).
- [x] Prévia do editor: controlador de widget de projeto carregado; nenhuma imagem com variável sem resolver.
- [x] Módulo de apresentações retirado do core.
- [ ] Manutenção no deploy por API exercitada num ambiente.
- [ ] Revisão humana.

Detalhes: [BATCH-218](../implementation/archive/BATCH-218.md).

## BATCH-217 — Prévia de widgets no editor, toque na galeria (req-209)

- [x] Prévia do editor de páginas mostra o widget com CSS e controlador.
- [x] Galeria: toque nos dois tipos de trilho.
- [x] Docs em inglês com layout próprio; ícones válidos.
- [ ] Toque em aparelho real.
- [ ] Revisão humana.

Detalhes: [BATCH-217](../implementation/archive/BATCH-217.md).

## BATCH-216 — Módulo `cookie-consent` (req-208)

- [x] Aviso sem decisão, recusa com o mesmo peso do aceite, revogação, script por categoria, Consent Mode v2.
- [x] Saída escapada; endereço da política restrito; nenhum marcador sobrando.
- [x] Painel: listagem, edição, pré-visualização e adição abrem sem erro.
- [ ] Gravação pelo formulário do painel exercitada no navegador.
- [ ] Revisão humana.

Detalhes: [BATCH-216](../implementation/archive/BATCH-216.md).

## BATCH-215 — Docs: referência de funções em cartões (req-207)

- [x] Cada função com âncora, assinatura copiável, parâmetros com tipo e link para a linha no repositório.
- [x] Filtro reduz cartões e índice; número de cartões igual ao de funções do bloco.
- [x] Testes das docs: 32, todos passam; `docs:audit` com 0 erros.
- [x] Lab: biblioteca `banco` nos dois idiomas, a 1280 e 390 px (14/14).
- [ ] Revisão humana.

Detalhes: [BATCH-215](../implementation/archive/BATCH-215.md).

## BATCH-214 — Deploy de projeto depois da req-202/203 (req-206)

- [x] `insert_only` vale em chave natural: a semente não altera usuário existente e ainda insere o que falta.
- [x] Compilador em modo projeto não grava dados do core no `db/data` do projeto.
- [x] `jsonWrite()` não regrava conteúdo igual; `db/data` copiado por conteúdo nos dois scripts de sincronização.
- [x] PHPUnit 1380 testes, 1 falha de fim de linha do ambiente (anterior ao lote); Vitest 461/461.
- [x] Lab: `project:update-all` sem alteração em módulos, permissões e usuários; `--tables usuarios --force-all` registra `SKIP_UPDATE_INSERT_ONLY`.
- [ ] Homologação humana.

Detalhes: [BATCH-214](../implementation/archive/BATCH-214.md).

## BATCH-211 — Segregação de recursos globais vs multilíngues (req-203)

- [x] Os quatro seeds globais foram movidos da pasta de idioma para a raiz sem perda: 1 usuário, 37 vínculos perfil-módulo, 3 vínculos perfil-operação e 1 categoria; sem cópias em `pt-br/` ou `en/`.
- [x] `resources.map.php` mantém os recursos multilíngues; os quatro contratos têm `language_agnostic: true` e preservam as chaves naturais.
- [x] A regressão compila os registros reais uma única vez, confere payload integral e ausência de `language`, e grava os quatro Data.json em pasta temporária.
- [x] `main()` completou as oito etapas com `--only`, `--skip-css`, `--no-origin-update` e `--no-assets` no fixture temporário; contagens 1/37/3/1, sem duplicações.
- [x] Testes relacionados: 7 testes/68 asserções, exit 0 (duas depreciações do PHPUnit); `php -l`, JSON parse e `git diff --check` aprovados. Avisos Git apenas de conversão LF/CRLF.
- [x] Os Data.json locais já modificados não foram sobrescritos; não houve deploy. A CLI não foi executada diretamente contra o checkout, pois isso regravaria esses artefatos preexistentes.

## BATCH-210 — Autoria em resources e compilação seletiva (req-202)

- [x] As 16 sementes (`pt-br`/`en`) foram comparadas aos oito Data.json legados; `resources.map.php` cobre os oito recursos.
- [x] `tables_config.json` declara as oito tabelas e chaves naturais; a configuração efetiva de `usuarios` é `insert_only` e preserva `senha`, `email`, `nome`, `usuario` e `status`.
- [x] `php cli/c2f.php resources:sync --only=modulos,modulos_grupos,modulos_operacoes,usuarios,usuarios_perfis,usuarios_perfis_modulos,usuarios_perfis_modulos_operacoes,categorias --skip-css --no-origin-update --no-assets`: exit 0; oito Data.json válidos/ordenados e `schema-metadata.json` coerente, sem órfãos.
- [x] `--only` seleciona e rejeita alvos desconhecidos; `--skip-css` pulou o passo Tailwind na compilação real; `--resource=home` selecionou os dois idiomas, repassado pela CLI.
- [x] Teste com runner Tailwind simulado: build inicial de três recursos, hit sem build, miss após mudança no HTML recompilando só `home/en`; manifest e sidecar de `footer/en` preservados.
- [x] PHPUnit focado: 26 testes, 133 asserções; PHP lint e `git diff --check` aprovados. O diff-check emitiu apenas avisos de conversão LF/CRLF do worktree.
- [x] Nenhum deploy remoto/produção executado. O subpasso opcional de publicação em `dist/` avisou que `PUBLIC_PATH`/DocumentRoot não está configurado; sincronização de Data.json concluiu com sucesso.

## BATCH-212 — Layout por perfil 1:N e compilação multi-layout (req-204)

- [x] Mapa `{perfil: layout}`: dois perfis no mesmo layout gravados e lidos; migração converte o formato antigo e não toca no novo.
- [x] `css:rebuild` compila a página mapeada com o layout padrão e os alternativos, e carimba os layouts cobertos.
- [x] Roteador usa o CSS da página sozinho só quando o layout em uso está no carimbo.
- [x] Editor HTML: seletor de preview por layout; captura mantém a regra que falte em qualquer layout; salvamento refaz a captura quando o conjunto de layouts muda.
- [x] Popup da página inicial fechado por padrão, aberto sob o campo, fechado ao clicar fora e ao escolher.
- [x] `action=version` na API de sistema.
- [x] Vitest 461/461; PHPUnit do lote 125/125; suíte completa com falhas só de fim de linha no ambiente.
- [x] Lab: `/perfil-usuario/` sob os dois layouts a 1280 e 390 px sem classe sem regra.
- [ ] Revisão humana e mesclagem.

Detalhes: [BATCH-212](../implementation/archive/BATCH-212.md).

## BATCH-213 — README e descrição do GitHub (req-205)

- [x] `README.md` e `README-PT-BR.md` com a mesma estrutura; 39 links relativos em cada, todos existentes.
- [x] `conn2flow.com/`, `/plataforma/` e `/pro/` respondem 200.
- [x] Descrição, site e tópicos do repositório atualizados no GitHub.
- [ ] Revisão humana do texto e mesclagem.

Detalhes: [BATCH-213](../implementation/archive/BATCH-213.md).

## BATCH-208 — Recuperação de arquivos do servidor (req-200)

- [x] Inventário unitário: projeto editado, core ausente, precedência, arquivo fora do manifesto, filtros e pastas privadas.
- [x] ZIP: teste de extração recusa `.env`, `autenticacoes/`, `logs/`, `backups/`, `temp/`, `..` e caminhos absolutos; confere hash do arquivo aceito.
- [x] Motor comum: `manter`, `mesclar`, `sobrescrever` na descida sem regra de deploy local.
- [x] PHPUnit focado da req-198/199 e testes novos: 54 testes, 264 asserções (dois avisos de depreciação).
- [x] `docs:audit` sem avisos nos arquivos tocados; `git diff --check` limpo.
- [ ] Homologação HTTP e CLI no tenant isolado depois que a rota nova for integrada ao ambiente. Nenhum deploy no tenant compartilhado durante este lote.

## BATCH-200 — Página inicial e layout por perfil (req-196)

- [x] Migração Phinx aplicada e revertida no MariaDB 11.8.8 isolado; colunas `pagina_inicial` VARCHAR NULL e `layouts_users_profiles` LONGTEXT NULL, com registros legados preservados.
- [x] Perfil grava apenas rotas ativas; login valida a rota novamente e conserva fallback `dashboard/`.
- [x] CRUDs aceitam apenas pares de layout e perfil existentes no idioma corrente e exigem layout padrão ativo ao ligar o toggle; roteador aplica o par compatível e conserva o fallback.
- [x] `resources:sync`: exit 0, 2.924 recursos, incluindo dez componentes bilíngues; manifests legados omitem o campo, preservando mapeamentos criados no Gestor.
- [x] Biblioteca registrada no bootstrap e incluída por `gestor_incluir_biblioteca`; HTML em componentes de recursos; selects de Layout e Perfil gerados por `interface_formulario_campos` com Fomantic-UI; página inicial buscada por AJAX (mínimo de 2 caracteres, máximo de 20 resultados).
- [x] PHPUnit: 1.289 testes, 10.152 asserções, exit 0 com `OPENSSL_CONF` válido; `Req196LayoutPorPerfilTest`: 6 testes, 16 asserções, inclusive sincronização SQL e renderização de componentes.
- [x] Vitest: 34 arquivos, 455 testes, exit 0; autocomplete AJAX, toggle e repetidor dos dois CRUDs exercitados no DOM; sintaxe PHP/JS aprovada.
- [x] MariaDB 11.8 isolado: 11 verificações dos validadores Layout/Perfil, campo de acesso e destino de login passaram com registros ativos, inativos e de outro idioma.
- [x] `project:update-all conn2flow-site-local`: oito estágios, exit 0; `assets:minify --verificar` e `git diff --check`: exit 0.
- [x] Playwright no Lab: três formulários HTTP 200 sem erros de console; busca AJAX retornou oito sugestões para `dash`; perfil salvo e recarregado com `dashboard/`; `publisher-pages` gravou JSON; toggle desligado no `admin-paginas` gravou `NULL`.
- [x] Rota temporária autenticada usou layout 3D mapeado e visitante usou layout padrão. Os dois registros de página, o vínculo de publicação e o perfil de teste foram removidos em transação.
- [x] Destino de login válido e fallback exercitados por testes PHP em banco isolado. Não houve login interativo com usuário temporário; a sessão administrativa do Lab foi obtida por `auth:cookie`.

## BATCH-197 — Ativação de parcelamento no PaymentIntent Stripe (req-193)

- [x] Payload legado sem `payment_method_options` quando a opção não é informada.
- [x] Opção habilitada serializa `payment_method_options[card][installments][enabled]`.
- [x] Teste PHPUnit focado (3 testes/5 asserções), `php -l` e `git diff --check` aprovados.

## BATCH-184 — Migração do acervo de docs, ondas 1 e 2 (req-179)

- [x] `docs:audit --json`: 0 erros; `legacy` = 0 em pt-br e en; 41 bibliotecas e 4 conceitos da onda 1 documentados do código.
- [x] PHPUnit das docs (`DocsBuildReq178Test`, `DocsToolingReq177Test`): 16/16 (100 asserções) no último commit de ferramental do batch.
- [x] Comportamentos citados nas docs conferidos executando o código quando possível (`formato.php`, callouts, extrator).
- [x] Lab: `docs:build` + `project:update-all conn2flow-site-local`; `page:inspect` sem erros de console em `/docs/reference/libraries/cron/` e `/docs/reference/modules/usuarios/`; screenshots conferidos.
- [ ] Revisão manual do Humano no Lab (lista de testes no relatório final).

## BATCH-183 — Parser docs:build (req-178)

- [x] `vendor/bin/phpunit --filter "DocsBuildReq178Test|DocsToolingReq177Test"`: 12/12 (84 asserções).
- [x] Suíte completa no Windows: 1.231/1.232 — única falha `CoreHelpersTest` (openssl.cnf, ambiente).
- [x] `docs:build --project=conn2flow-site-local` idempotente (2ª execução: 0 arquivos).
- [x] Lab: `project:update-all` sem órfãos; HTTP 200 em `/docs/`, índices, 3 docs, `/en/docs/...` e `/docs/llms.txt`; `page:inspect` sem erros de console; screenshots pt-br/en conferidos (`temp/docs-*.png`).

## BATCH-181 e BATCH-182 — Limpeza do ai-workspace e ferramentas de documentação (req-176, req-177)

- [x] Branch `legacy/ai-workspace-pre-docs` preserva `agents-history/`, `prompts/`, `templates/`; 179 arquivos removidos da `main`, `scripts/` intocado.
- [x] `php cli/c2f.php docs:audit --json`: 8 docs piloto (pt-br + en) com score 0; 0 erros.
- [x] `php cli/c2f.php docs:extract --all --check`: exit 0.
- [x] `vendor/bin/phpunit --filter DocsToolingReq177Test`: 8/8 (34 asserções).
- [x] Suíte completa no Windows: 1.227/1.228 — única falha `CoreHelpersTest::testCriptografiaBasicaComChavesRsa` (`openssl.cnf` ausente no PHP WinGet, ambiente, conhecida).

## BATCH-180 — Renovação silenciosa de CSRF e retry transparente (req-175)

- [x] `gestor_csrf_resposta_invalida()` devolve `code: CSRF_INVALID_OR_EXPIRED` no JSON e o cabeçalho `X-Gestor-Csrf-Error` nos ramos JSON e HTML.
- [x] Rota `_gestor-csrf-token/` isenta de CSRF: visitante e usuário logado recebem `200` com token; login expirado recebe `401 AUTH_EXPIRED` com `X-Gestor-Auth-Redirect`, sem token; resposta `no-store`.
- [x] `global.js`: renovação única com fila; retry transparente em `fetch` e XHR (o que cobre o `$.ajax`); propagação para a `<meta>`, `gestor.csrfToken`, campos ocultos e a página hospedeira.
- [x] 403 legítimo (ACL) não entra em retry; no máximo uma repetição por requisição; XHR síncrono e GET ficam fora.
- [x] `visibilitychange` com limite de 30 s; a checagem proativa não redireciona.
- [x] Cache-bust: `global.min.js` regenerado e owner `global` do `asset-versions.json` atualizado.
- [x] PHPUnit focado 15/15; Vitest focado 23/23.
- [x] PHPUnit completo 1.220/1.220 (4 skipped, `OPENSSL_CONF` explicitado), exit 0; Vitest completo 449/449, exit 0; `git diff --check` exit 0.
- [ ] Homologação runtime (expirar sessão e confirmar retry transparente em tela real).

## BATCH-179 — Sincronização automática de hooks em deploy de projeto (req-174)

- [x] O fluxo normal de `atualizacoes-banco-de-dados.php`, com ou sem `--project`, executa `atualizacoes_hooks_sincronizar()` depois de migrações/dados.
- [x] O resumo final reporta hooks processados por módulos, plugins, projeto e total.
- [x] `project:sync-hooks <projeto-id>` está registrado e usa `--hooks-only` sobre o transporte compartilhado SSH/Host/Docker.
- [x] Idempotência e remoção declarativa cobertas por teste comportamental: 3 testes, 27 asserções, exit 0.
- [x] PHPUnit completo: 1.205 testes, 7.908 asserções, 4 pulados, exit 0 (com `OPENSSL_CONF` do PHP 8.5 explicitado).
- [x] Vitest completo: 30 arquivos, 426 testes, exit 0.
- [x] Sintaxe PHP/Bash/JSON e `git diff --check`: exit 0.
- [x] Lab HestiaCP: Deploy em `conn2flow-site-local` concluiu com HTTP 200, `Hooks => total=80 (módulos=12, plugins=0, projeto=68)`, confirmando a sincronização automática de hooks sem necessidade de intervenção manual no banco.

## BATCH-178 — Query String em 301 e Expurgamento de Blocos no Widget Forms (req-173)

- [x] Roteador 301 repassa `'querystring' => true` na chamada de `gestor_roteador_erro()` em `gestor/gestor.php`.
- [x] Concatenação extraída para `gestor_redirecionar_montar_url()` em `gestor/bibliotecas/gestor.php`, tratando com segurança destinos já parametrizados (`&`), sem deixar `?` órfão.
- [x] `forms_widget_limpar_fragmentos()` criada e aplicada no retorno de `forms_widget_render_inline()` em `gestor/modulos/forms/forms.widget.php`, expurgando `option-choice`, `option-select`, `password-toggle` e `<template>` vazio, além de sanitizar marcadores residuais `@[[item#*]]@`, `@[[option#*]]@` e `@[[password#*]]@`.
- [x] Selects, rádios, checkboxes e alternância de visibilidade de senha permanecem 100% operacionais no formulário gerado.
- [x] PHPUnit focado REQ-173: 12 testes, 33 asserções, exit 0 (`Req173Redirecionamento301Test` e `Req173FormsWidgetFragmentosTest`).
- [x] PHPUnit completo: 1.202 testes, 7.881 asserções, 4 pulados, exit 0.
- [x] Vitest: 30 arquivos, 426 testes, exit 0.
- [x] `assets:minify --verificar`: 0 derivados desatualizados; `git diff --check`: exit 0.
- [x] Runtime Lab (`conn2flow.local`): confirmado 301 preservando parâmetros/UTMs, checkout sem contorno renderizando 0 marcadores crus.

## BATCH-177 — Fallback reCAPTCHA v3/v2 em autenticação Tailwind (req-172)

- [x] Interceptador nativo cobre login, OAuth, cadastro e recuperação de senha, com ações fixas no cliente e submissão nativa após obter o token.
- [x] Backend usa ação esperada definida pelo servidor e solicita reCAPTCHA v2 quando o v3 falha ou fica abaixo do score aceito.
- [x] Resposta v2 é validada por `gestor_captcha_validar(null, ['v2' => true])` e, quando aprovada, libera a validação de credenciais.
- [x] PHPUnit focado: 147 testes/825 asserções. PHPUnit completo: 1.190 testes/7.848 asserções, 4 pulados, exit 0.
- [x] Vitest focado: 81 testes. Vitest completo: 30 arquivos/426 testes, exit 0.
- [x] `php -l`, `node --check`, `assets:minify --verificar`, `resources:sync` e `git diff --check`: aprovados.
- [x] Lab HestiaCP: `project:update-all conn2flow-site-local` concluiu oito estágios; Playwright confirmou v3 inválido → checkbox v2 → validação de credenciais em `conn2flow.local` com chaves oficiais de teste.
- [x] Configuração temporária do Lab restaurada e review findings-first concluído sem finding bloqueante.

## BATCH-176 — Cloudflare Turnstile (req-171)

- [x] `php -l` nos 6 PHP alterados/criados e `node --check` nos 3 JS de autoria: sem erros.
- [x] PHPUnit com `OPENSSL_CONF` válido: 1.187 testes, 7.843 asserções, 4 pulados, exit 0. Os 6 testes de `CaptchaTurnstileTest` cobrem sucesso, bloqueio, rede, JSON inválido, segredo inválido, token ausente e Google.
- [x] Vitest: 423/423, exit 0.
- [x] `assets:minify --verificar`: 0 derivados desatualizados; `resources:sync`: 2.882 recursos, exit 0; `git diff --check`: exit 0.
- [x] Lab HestiaCP: `project:update-all conn2flow-site-local` concluiu 8 estágios; SQL `paginas` confirmou `admin-environment` em `en`/`pt-br` com Turnstile. `dist/` opcional não foi publicado por ausência de `PUBLIC_PATH`.
- [x] Playwright em `https://conn2flow.local/` com chaves oficiais de teste: `/signin/`, `/signup/`, `/forgot-password/` e `/contact/` HTTP 200, widget e `cf-turnstile-response` presentes, zero erros de console. Capturas em `temp/batch-176-*-turnstile.png`.
- [x] Painel: seletor e bloco Turnstile visíveis na aba Usuário; teste AJAX retornou “Chaves e token do Turnstile válidos.”; salvar retornou HTTP 200/`status=success` e gravou as quatro variáveis esperadas. `.env` do Lab restaurado após os testes.
- [x] Limite da inspeção: o `page:inspect` padrão usa `networkidle`, que expirou com o script da Cloudflare; Playwright com `domcontentloaded` e captura visual confirmou a renderização. O helper `auth:cookie --project` falhou na montagem de aspas SSH no Windows; o gerador existente foi executado diretamente no Lab.

Use este checklist para validar batches no conn2flow sem perder de vista o baseline operacional do repositÃ³rio.

## Onboarding SDD repo-wide

- [x] CLAUDE.md instalado na raiz do repositÃ³rio
- [x] .claude/ instalado com agents, rules, skills e settings do Claude Code
- [x] .github/copilot-instructions.md instalado
- [x] .github/instructions/, .github/prompts/, .github/skills/ e .github/agents/ com artefatos SDD do Copilot
- [x] sdd/scripts/hooks/ criado com hooks de sessÃ£o SDD
- [x] sdd/human-requests/ ativo
- [x] sdd/README.md, process/, implementation/, validation/ e decisions/ criados
- [x] sdd/00-baseline-architecture.md criado com preservaÃ§Ã£o do legado

## Checklist mÃ­nimo por batch

- [ ] O batch estÃ¡ registrado em sdd/implementation/BATCH-INDEX.md
- [ ] O impacto foi comparado contra sdd/00-baseline-architecture.md
- [ ] A menor validaÃ§Ã£o executÃ¡vel do slice foi definida antes de editar mais do que o necessÃ¡rio
- [ ] Scripts, tasks ou paths alterados continuam coerentes com dev-environment/data/environment.json
- [ ] NÃ£o houve reescrita ampla do legado sem mudanÃ§a normativa aprovada
- [ ] O review findings-first foi feito quando a mudanÃ§a ficou pronta para avaliaÃ§Ã£o

## Quando o batch tocar operaÃ§Ã£o local

- [ ] Validar a task do VS Code mais prÃ³xima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execuÃ§Ã£o correspondente
- [ ] Se tocar sincronizaÃ§Ã£o de projeto, validar source/target/path no environment.json
- [ ] Se tocar plugins, validar o fluxo na Ã¡rvore dev-plugins/

## EvidÃªncia mÃ­nima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendÃªncias ou riscos restantes

## Regra final

Se nÃ£o houver validaÃ§Ã£o executÃ¡vel no slice atual, o batch deve registrar explicitamente por que a validaÃ§Ã£o ficou documental ou manual.

## ValidaÃ§Ãµes de Batches Arquivados

Para manter o checklist de validaÃ§Ãµes leve e eficiente (teto de 25 blocos ativos na REQ-051), as validaÃ§Ãµes anteriores foram arquivadas:
- **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)** (BATCH-001 a BATCH-017)
- **[validation-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-018-053.md)** (BATCH-018 a BATCH-053)
- **[validation-054-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-054-093.md)** (BATCH-054 a BATCH-093)
- **[validation-094-110.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-094-110.md)** (BATCH-094 a BATCH-110)
- **[validation-111-134.md](archive/validation-111-134.md)** (17 blocos históricos entre BATCH-111 e BATCH-134; ordem documental preservada)
- **[validation-136-173.md](archive/validation-136-173.md)** (BATCH-136 a BATCH-173)

## BATCH-228 — Listagem Tailwind (req-220)

- [x] CA-1: piloto sem assets Fomantic/DataTables; layout/menu Tailwind usam Lucide.
- [x] CA-2: cadastro, edição, clonagem, busca, ordenação, paginação, status e exclusão conferidos em desktop e 390 px; sem overflow da página.
- [x] CA-3: admin-paginas/admin-layouts continuam listando com DataTables.
- [x] CA-4: PHPUnit completo 1.475 testes/11.720 asserções (Linux com LF); focado final 44/591; Vitest 483 testes; Playwright 21 verificações e zero erros de console.
- [x] Pipeline oficial project-test concluído, banco sincronizado e 458 arquivos conferidos por hash. Publicação direta de dist falhou por caminho Windows no rsync; fallback estático validado.
- [x] Dados antes/depois: 7 grupos ativos, registros de teste removidos. Sintaxe PHP/JS e git diff --check aprovados.
- [x] Review técnico do slice sem finding bloqueante; mudanças alheias mantidas fora do diff.
- [ ] Revisão independente e consolidação pela req-219/humano.

Evidências: [BATCH-228](../implementation/archive/BATCH-228.md), [roteiro](req220-browser.cjs), [resultados](req220-browser-results.json). Runtime inspecionado em pt-br; en compilado e verificado por unidade. Depreciações/pulados e problemas iniciais de CRLF registrados no lote.

## BATCH-230 — Família Publisher em Tailwind (req-221)

- [x] CA-1: cinco módulos e três variantes do editor, pt-br/en; documento do painel sem Fomantic.
- [x] CA-2: listar/adicionar/editar/clonar, selects, switches, abas, campos/vínculos, curadoria, salvar e recarregar; desktop/390 px sem overflow. Workspace Social carregado e reaberto nas duas larguras.
- [x] CA-3: sem alert/confirm/prompt nativos nas telas migradas.
- [x] CA-4: PHPUnit completo 1.515/14.094; Vitest completo 495; contratos core 10/1.543; site PHP 3/92 e JS 9; Playwright 439 verificações sem falhas.
- [x] CA-5: relatório BATCH-230 e roteiro humano no PENDENCIAS-HUMANAS.md do site.
- [x] Pipeline oficial sequencial, CSS final reconstruído, manutenção desligada e fixture própria removida pela UI.
- [x] Review de contratos/dependências/CSRF e git diff --check; artefatos fora do escopo invertidos antes dos commits.
- [ ] Revisão independente, teste humano e consolidação pela req-219.

Evidências e limitações: [BATCH-230](../implementation/archive/BATCH-230.md). Roteiro/resultados no site em `sdd/validation/core/req221-publisher-e2e.cjs` e `evidence-req221/`. Runtime pt-br; en compilado e verificado por contratos. JS compartilhados mais recentes da req-219 preservados; controle legado admin-templates e cadastro ausente de sua simulação original descritos no lote.

## BATCH-245 / REQ-236 — integração homologada no Lab

- [x] Integrações Core REQ-222/223/224 sequenciais e preservação do stash/checkouts anteriores.
- [x] Dashboard V3.1: título/abas/Opções, tooltip, capa única M/G, edição, resize e seleção tipo/registro persistida.
- [x] Core Vitest 529/529; PHPUnit 1.594 testes / 15.795 assertions sem falhas/erros, quatro skipped e deprecações registradas.
- [x] Site Vitest 9/9; PHPUnit cinco testes / 462 assertions.
- [x] Navegador Site 88/88 e Dashboard 17/17; 390px; seis fixtures removidas e preferências restauradas.
- [x] Pipeline oficial completo sequencial, manutenção desligada; configuração local temporária restaurada.
- [x] Inventário 386 páginas/idiomas sem violações; SQL 316 páginas administrativas sem resíduos visuais. Resíduos globais de componentes/templates e stale documentados.
- [x] Revisão findings-first, controles negativos de instâncias/ícones e diff sem erros de whitespace.
- [ ] Revisão humana e consolidação final.

Relatório: [BATCH-245](../implementation/BATCH-245.md). Runtime pt-br; contratos bilíngues; OAuth e operações externas reais não exercitados.
