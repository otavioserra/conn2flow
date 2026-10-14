# Index — implementation

| ID | Title | Executive summary | Relative link | Status |
| --- | --- | --- | --- | --- |
| BATCH-265 | BATCH-265 — Miniaturas dos modelos do cadastro de modelos (linha 3.0) | A prévia crua do editor não serve: mostra um item só, os marcadores (`&#91;&#91;item#titulo&#93;&#93;`) e até o bloco de "nenhum resu... | [source](<BATCH-265.md>) | implemented-pending-homologation |
| BATCH-266 | BATCH-266 — Aba Modelos vazia na inclusão e miniaturas cortadas na horizontal (linha 3.0) | As 55 miniaturas antigas (modelos de página, 290 × 197) não foram refeitas e continuam perdendo um pouco das laterais... | [source](<BATCH-266.md>) | implemented-pending-homologation |
| BATCH-267 | BATCH-267 — Linha 3.1, fase 4 da lousa: modelos de lousa prontos | O roteiro abre o pop-up de layouts, confere os três modelos na lista, tenta criar sem modelo, cria uma lousa pelo mod... | [source](<BATCH-267.md>) | implemented-pending-homologation |
| BATCH-268 | BATCH-268 — Linha 3.1, fase 5 da lousa: medição da lousa publicada | O módulo `analytics-manager` (do site) monta eventos do GA4 a partir de funis configurados no painel. Um dos gatilhos... | [source](<BATCH-268.md>) | implemented-pending-homologation |
| BATCH-269 | BATCH-269 — Camada de provedores de IA: Gemini, Claude, OpenAI e compatíveis | Quem chama não conhece o formato de ninguém: | [source](<BATCH-269.md>) | implemented-pending-homologation |
| BATCH-270 | BATCH-270 — Ponto de extensão geral das telas do painel (`interface` / `pagina`) | `interface_finalizar()` já disparava `&lt;módulo&gt;` / `&lt;opção&gt;.pagina` quando a tela ficava pronta. Agora dispara também,... | [source](<BATCH-270.md>) | implemented-pending-homologation |
| BATCH-271 | BATCH-271 — Pontos de extensão do uso de IA: autorizar o pedido e pedido concluído | Todo pedido de IA passa por `ia_provedor_gerar_texto()` ou `ia_provedor_gerar_imagem()`. As duas passaram a disparar: | [source](<BATCH-271.md>) | implemented-pending-homologation |
| BATCH-272 | BATCH-272 — Índice de idioma + caminho em `paginas` | A busca da página pelo caminho, que roda em toda requisição, não tinha índice que cobrisse o caminho. O banco percorr... | [source](<BATCH-272.md>) | implemented-pending-homologation |
| BATCH-273 | BATCH-273 — Instalação funcional e página inicial padrão | O domínio pelo Caddy respondia HTTP 200 com zero bytes. O VirtualHost HestiaCP direto em 8443 retornava 6.840 bytes, ... | [source](<BATCH-273.md>) | implemented-pending-homologation |
| BATCH-274 | BATCH-274 — Pagamentos, marca dos e-mails e confirmação de webhooks | `pagamentos_com_trava` protege a reconciliação financeira na mesma conexão, com `GET_LOCK`, transação, commit/rollbac... | [source](<BATCH-274.md>) | implemented-pending-homologation |
| BATCH-275 | BATCH-275 — REQ-266: compatibilidade memory/ e sdd/ | Preferência por memory/ e fallback sdd/. SddSource conserva chaves públicas sdd/ e seção sdd para preservar o contrat... | [source](<BATCH-275.md>) | implemented-pending-homologation |
| BATCH-277 | REQ-268: página com sem_permissao = 0 passa a exigir login | Roteador e telas de edição tratavam sem_permissao = 0 como página pública; agora só 1 abre a página. | [source](<BATCH-277.md>) | implemented-pending-homologation |
| BATCH-278 | REQ-269: --skip-css nos pipelines project:update-all e manager:update-all | --skip-css pula a compilação Tailwind e o css:rebuild nos dois pipelines; Lab em 36 s com a flag. | [source](<BATCH-278.md>) | implemented-pending-homologation |

## Directories

- [archive](<archive/index.md>)
