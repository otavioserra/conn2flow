# Index — human-requests

| ID | Title | Executive summary | Relative link | Status |
| --- | --- | --- | --- | --- |
| REQ-256 | REQ-256 — Miniaturas dos modelos do cadastro de modelos (linha 3.0) | Fora do escopo: refazer as 55 miniaturas que já existiam; modelos do alvo `galleries-estados`, que não aparece na lis... | [source](<req-256.md>) | implemented-pending-homologation |
| REQ-257 | REQ-257 — Aba Modelos do editor vazia nas telas de adicionar e miniaturas cortadas na horizontal (linha 3.0) | Relatório: &#91;BATCH-266&#93;(../implementation/BATCH-266.md). | [source](<req-257.md>) | implemented-pending-homologation |
| REQ-258 | REQ-258 — Linha 3.1, fase 4 da lousa: modelos de lousa prontos | Fora do escopo: parâmetros de URL (dispensados) e o widget de plano (já existe no site). | [source](<req-258.md>) | implemented-pending-homologation |
| REQ-259 | REQ-259 — Linha 3.1, fase 5 da lousa: medição da lousa publicada | Do plano aprovado: "Medição por lousa pelo módulo de analytics (visitas, cliques por objeto)". O Engenheiro Chefe def... | [source](<req-259.md>) | implemented-pending-homologation |
| REQ-260 | REQ-260 — Camada de provedores de IA: Gemini, Claude, OpenAI e compatíveis | O envio à IA está escrito para o Gemini dentro de `ia_enviar_prompt()`: endereço, formato do pedido e leitura da resp... | [source](<req-260.md>) | implemented-pending-homologation |
| REQ-261 | REQ-261 — Ponto de extensão geral das telas do painel (`interface` / `pagina`) | Ao terminar de montar uma tela, a biblioteca de interface dispara o ponto de extensão `&lt;módulo&gt;` / `&lt;opção&gt;.pagina`. ... | [source](<req-261.md>) | implemented-pending-homologation |
| REQ-262 | REQ-262 — Pontos de extensão do uso de IA: autorizar o pedido e pedido concluído | Todo pedido de IA do sistema passa pela camada de provedores (`ia-provedores.php`, REQ-260): o Assistente IA do edito... | [source](<req-262.md>) | implemented-pending-homologation |
| REQ-263 | REQ-263 — Índice de idioma + caminho em `paginas` (toda página lia a tabela inteira) | O site passou de respostas em torno de 100 ms para um segundo ou mais, de forma intermitente. Achar a causa e corrigi... | [source](<req-263.md>) | implemented-pending-homologation |
| REQ-264 | REQ-264 — Apoio Core: Ajustes no Checkout Multi-Etapas, Variáveis de E-mail e Suporte a Tenants no Lab | Esta requisição fornece o respaldo no Core para as correções coordenadas da REQ-121: | [source](<req-264.md>) | implemented-pending-homologation |
| REQ-265 | REQ-265 — Apoio Core: Deduplicação de Transações Gateway, Resolução de `#brand_name#` e Histórico de Webhooks |  | [source](<req-265.md>) | ready-for-intake |
| REQ-266 | REQ-266 — Compatibilização MDD e Suporte Dual `memory/` e `sdd/`: Testes Automatizados, CLI e Governança de Skills |  | [source](<req-266.md>) | ready-for-intake |
| REQ-267 | REQ-267 — Apoio Core: Assets Isométricos de Capa para Dashboard Pages e Interface | Entrega dos assets WebP e atualização de metadados no Core: | [source](<req-267.md>) | ready-for-intake |
| REQ-268 | REQ-268 — Apoio Core: Suporte a Apresentações com Acesso Restrito e Compilação de Notícias |  | [source](<req-268.md>) | ready-for-intake |
| REQ-269 | REQ-269 — Pipeline Ágil: Suporte a `--skip-css` em `project:update-all` e Otimização Seletiva de Compilação Tailwind |  | [source](<req-269.md>) | ready-for-intake |
| REQ-270 | REQ-270: Apoio Core — Preparação e Suporte à Limpeza do Lab e Homologação Final da Versão 3.0.0 | Apoiar a execução da limpeza do ambiente Lab (`lab.conn2flow.local`) e os testes finais de ponta a ponta do funil de ... | [source](<req-270.md>) | indexed |
| REQ-271 | REQ-271: Apoio Core — Mover pagamentos_com_trava para stripe.php e Excluir pagamentos.php | Na REQ-265 foi criada a biblioteca `gestor/bibliotecas/pagamentos.php` contendo exclusivamente uma função (`pagamento... | [source](<req-271.md>) | indexed |
| REQ-272 | Release 3.0.0 do Conn2Flow e ambiente de pré-lançamento em temp.conn2flow.com | Os testes de assinatura no Lab passaram (REQ-126 / REQ-271). O Humano quer publicar a versão 3.0.0 do sistema e monta... | [source](<req-272.md>) | ready-for-intake |
| REQ-273 | Novo Tipo de Campo 'Domínio' (domain) no Módulo Forms e Biblioteca de Formulários | Durante a contratação de planos de hospedagem e assinatura no site (`form-pro-acceleration` e formulários correlatos)... | [source](<req-273.md>) | ready-for-intake |
| REQ-274 | req-274: Autorização de Navegador Confiável para Acesso Anônimo no Site com Acesso Restrito Ativo |  | [source](<req-274.md>) | Indicador visual mostrando se o navegador que está acessando a tela possui ou não o passe ativo e data de validade |
| REQ-275 | Visibilidade e link direto privado nas páginas (admin-paginas e publisher-pages) | Nos dois módulos que criam páginas — `admin-paginas` e `publisher-pages` (publicador de páginas): | [source](<req-275.md>) | implemented-pending-homologation |
| REQ-276 | Corrigir confirmação de regeneração de link que bloqueou o release 3.0.4 | Substituir window.confirm nos dois editores de páginas pelo diálogo padrão e validar o bloqueio do Vitest. | [source](<req-276.md>) | implemented-pending-homologation |

## Directories

- [archive](<archive/index.md>)
