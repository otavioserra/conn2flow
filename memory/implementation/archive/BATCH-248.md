# BATCH-248 — REQ-239: refinamentos da auditoria humana

- Status: in-review; implementação e validação técnica concluídas no Lab em 2026-10-05.
- Projeto: conn2flow
- Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow
- Coordenação: conn2flow-site, REQ-105 / BATCH-099.
- Autonomia: autonomo_monitorado; publicação no Lab autorizada pelo usuário.

## Live Todo List

- [x] Dashboard P/G.
- [x] admin-paginas: seções, stacking, mídia e histórico.
- [x] Publisher: cards, alertas, selects, tipo e tabela.
- [x] variables, admin-ia, admin-prompts-ia, admin-arquivos e cookie-consent.
- [x] interface: máscara monetária e widget de imagem com URL/admin-arquivos.
- [x] CA-5.3: hashes Tailwind de HTML/CSS/JS normalizados para LF, com regressão de fingerprint e preservação binária.
- [x] Vitest e PHPUnit Core/Site.
- [x] Pipeline oficial sequencial do Lab e inspeção runtime.
- [x] Evidências e revisão final.
- [ ] Homologação humana e consolidação Git.

## Estado inicial

Árvores compartilhadas com alterações locais anteriores em CSS compilado, manifestos e metadados. Preservar essas alterações; não usar reset, stash ou staging abrangente. Requisições classificadas como correção incremental decorrente da auditoria, sem alteração de SPEC.md.

## Evidências

- REQ-239 relida após atualização humana: incorporado CA-5.3, incluindo hash do CSS central, fontes HTML/CSS/JS e CSS de saída do manifesto.
- Regressão Tailwind: 24 testes / 141 asserções. Conversão LF/CRLF/CR preserva fingerprint; conteúdo alterado invalida cache; binários conservam bytes. Integração confirma zero builds após converter fonte, entrada central e saída para CRLF.
- Prova antes/depois: `sdd/validation/evidence-req239/cache-regression.json`, fingerprint antigo invalida cache com CRLF, novo preserva.
- Core Vitest: 46 arquivos / 541 testes; PHPUnit: 1.599 testes / 15.872 asserções, sem falhas, com 5 skips, 4 deprecações PHP e 3 PHPUnit. OpenSSL configurado somente no processo de teste para o arquivo do Git.
- Site Vitest: 2 arquivos / 13 testes; PHPUnit: 10 testes / 1.386 asserções.
- Navegador: 86 verificações aprovadas, rotas autenticadas em desktop/390px, sem erros JavaScript ou overflow; Quill, máscara, URL/modal admin-arquivos, trava do catálogo, Dashboard P/G, seções dinâmicas, stacking do editor, Publisher, cards IA e abas de cookies conferidos em `sdd/validation/evidence-req239/browser.json` e screenshots.
- Submissão real: 18 verificações aprovadas em `products-e2e.json`; descrição HTML, imagem, máscaras, frete e datas persistidos, variações dinâmicas, link público, variáveis do editor, modelos e herança autoritativa Stripe. Loading encerra também após falha HTTP 500 simulada. Os dois produtos próprios e suas variações foram removidos pela UI com CSRF.
- Pipeline final: `php cli/c2f.php project:update-all conn2flow-site-local --confirmar-remoto`, sequencial e exit 0; `evidence-req239/pipeline-cookie-acceptance.log`. Manutenção desligada, migração de promoções/fretes aplicada e hashes normalizados de 778 arquivos sem diferenças/sobras (`code-hashes.json`).
- Sintaxe PHP e JS (nove arquivos de autoria de cada linguagem) e `git diff --check` em ambos os repositórios aprovados.

## Revisão técnica e limites

Sem finding bloqueante restante no escopo. A inspeção corrigiu adicionalmente: ícone do Dashboard removido pelo backend e encoberto pela alça em P; altura Quill; cascata responsiva do widget de imagem; carga do controle monetário em variações; biblioteca `html-editor` ausente no dispatcher AJAX de products; variáveis consumidas antecipadamente pelo motor. Controles negativos reproduzem o problema do cache CRLF e promoção futura no preço antigo.

O pipeline mantém o aviso anterior de `admin-categorias.js` vazio, servido pela autoria sem derivado. `project:verify` cru acusa CRLF/LF; o verificador complementar usa o contrato oficial de inventário/transporte e normaliza as duas pontas, comprovando igualdade. Os skips/deprecações PHPUnit permanecem documentados acima. Navegador em pt-br; en compilado e contratos bilíngues cobertos por testes. Não houve criação de produtos nem cobrança externa pela API Stripe.

A tentativa adicional de inspecionar uma ocorrência real do histórico de `admin-paginas` não encontrou registros nas páginas de sistema/página consultadas; o roteiro registra esse cenário como skipped, sem fabricar uma ocorrência. A estrutura de linha horizontal e o ícone Lucide foram conferidos no produtor PHP e na cascata CSS publicada. Isso não impede o aceite técnico; homologação humana de um registro com histórico permanece no roteiro.

Sem staging, commits, push ou merge nesta execução. CSS/manifests/arquivos de dados e alterações anteriores/concorrentes foram preservados. Há um backup de compilação preexistente no Site (creation time 2026-09-28) e `.tailwind-build` que não foram removidos.

## Coordenação com REQ-240 / REQ-106

Usuário confirmou outro agente em paralelo em BATCH-249 (Core) e BATCH-100 (Site). Não alterar nem reverter o trabalho desse agente. Autoria alterada/validada por REQ-239/REQ-105:

- Core `gestor/assets/interface/{controles.js,controles.css,campo-moeda.js,interface-tailwind.js,html-editor-interface.js,html-editor-visual-controls.js}`, `gestor/assets/global/admin-tailwind.js`, `gestor/bibliotecas/interface.php`, `gestor/controladores/agents/arquitetura/tailwind-recursos.php` e widgets de imagem globais.
- Core módulos `dashboard`, `publisher`, `publisher-highlights`, `publisher-index`, `publisher-pages`, `variables`, `admin-ia`, `admin-arquivos`, `cookie-consent` e metadados `interface`.
- Site módulos `products`, `stripe-products`, bibliotecas `ecommerce.php`, `ecommerce-variants.php`, `ecommerce-shipping.php` e migração `20261005160000_add_product_sale_period_and_shipping_methods.php`.
- Derivados de compilação e manifestos são produzidos somente pelo pipeline oficial, sob a trava por destino; não sincronizar por cópia nem executar compiladores concorrentes.

REQ-240/REQ-106 fazem a varredura geral; BATCH-248/BATCH-099 não foram ampliados para os demais módulos. Índices/CURRENT foram relidos antes da atualização final, preservando o ponteiro e entradas do agente paralelo.

Recado recebido do agente paralelo: sua alteração em `interface_botao_tailwind_icone()` é independente das funções editadas aqui e deve ser preservada na consolidação. `cookie-consent` passou a declarar explicitamente `c2fc-abas-lista c2fc-anexa` e `c2fc-painel-aba` nas seis páginas; não depende da classe sem CSS `c2fc-abas`. Os estilos compartilhados `c2fc-campo-selecao` e `c2fc-alerta` estão em `controles.css`, disponíveis para o Pilar 4 após integração humana.
