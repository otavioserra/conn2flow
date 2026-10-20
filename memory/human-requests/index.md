# Index — human-requests

| ID | Title | Executive summary | Relative link | Status |
| --- | --- | --- | --- | --- |
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
| REQ-277 | Hotfix do atualizador pelo painel: trava órfã depois de erro fatal e memória da sincronização de dados | O Humano atualizou pelo `admin-atualizacoes` às 14:28. A etapa `db` morreu com `PHP Fatal error: Allowed memory size ... | [source](<req-277.md>) | implemented-pending-homologation |
| REQ-278 | Atualizador do core sempre entregue, mesmo com manifesto desatualizado | A atualização pelo painel para a 3.0.5 escreveu 1 arquivo e **preservou 28** como "editados". A linha de base do mani... | [source](<req-278.md>) | implemented-pending-homologation |
| REQ-279 | Sessão de banco da atualização não cai com wait_timeout curto do servidor | A 3.0.6 entrou inteira (29 arquivos, sem preservados) e a memória não estourou, mas a etapa de banco falhou: `&#91;BANCO&#93;... | [source](<req-279.md>) | implemented-pending-homologation |
| REQ-280 | Trava da atualização pelo painel: dono morto sob open_basedir e etapa com erro comum | A atualização para a 3.0.7 recusou: "Outro deploy está em execução … (PID 1386087), desde 16:44:44". Era a sessão das... | [source](<req-280.md>) | complete |
| REQ-281 | Deploy SSH grava os arquivos com o dono do destino também entrando como root (BL-033) | Durante o deploy SSH do `temp.conn2flow.com` o site deu **500** (`require_once(gestor.php): Permission denied`) e o c... | [source](<req-281.md>) | complete |
| REQ-282 | Estabilização determinística do teste de retry assíncrono em XHR no global-csrf-refresh (Vitest) | Na execução do pipeline de CI do release (`release-gestor.yml`), o teste unitário de frontend em `tests/Unit/JS/globa... | [source](<req-282.md>) | complete |
| REQ-283 | Exposição de CRUD via API RESTful para os módulos básicos do Core | O Conn2Flow possui uma camada de API (`/_api/`) que hoje atende prioritariamente à infraestrutura, rotinas mobile de ... | [source](<req-283.md>) | ready-for-intake |
| REQ-284 | Cache do Tailwind por recurso que não sobrevive a outra pasta ou terminal, e varredura automática que lê os seeds | "Toda hora fica recompilando sem precisar." Um `project:update-all conn2flow-site-local` levou cerca de 20 minutos e ... | [source](<req-284.md>) | implemented-pending-homologation |
| REQ-285 | Falha na etapa de finalização (finalize) do atualizador do sistema pelo painel | Ao atualizar uma instância do site via painel administrativo (`admin-atualizacoes`) para a tag `gestor-v3.0.9`: | [source](<req-285.md>) | implemented-pending-homologation |

## Directories

- [archive](<archive/index.md>)
