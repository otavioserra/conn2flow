# BATCH-254 — REQ-245: atualização automática do sistema, com abas Manual e Automático

- Status: implemented-pending-review. Implementado, publicado no Lab e validado em 2026-10-06; aguarda revisão da chefia e homologação humana.
- Projeto: conn2flow
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow` (execução na worktree `conn2flow-req243`, branch `feat/req-245`, criada sobre `feat/req-243`)
- Requisição: [REQ-245](../human-requests/req-245.md), que absorve a [REQ-241](../human-requests/req-241.md) (BATCH-250).
- Autonomia: `autonomo_monitorado`. Commit e push na branch de trabalho; na `main` entrou só a reserva da requisição.

## Live Todo List

- [x] Biblioteca do ciclo: configuração, versões, vencimento e decisão, com testes.
- [x] Tarefa agendada declarada pelo módulo e callback.
- [x] Ações do painel: salvar, verificar agora, liberar versão recusada.
- [x] Tela com abas Manual e Automático.
- [x] `resources:sync`, suítes, Lab e roteiro de navegador.
- [ ] Revisão da chefia e homologação humana.
- [ ] Atualização real disparada pela rotina, com sucesso e com volta automática (ver Limites).

## Implementação

| Peça | Arquivo | O que faz |
| --- | --- | --- |
| Biblioteca do ciclo | `gestor/bibliotecas/atualizacoes-automatica.php` | Pura, sem Gestor. Lê e grava a configuração da instalação, compara versões, escolhe a versão estável mais recente, calcula vencimento e próxima checagem, decide o ciclo, aplica o resultado da execução e consulta as releases com verificação de certificado. |
| Rotina | `gestor/modulos/admin-atualizacoes/admin-atualizacoes.cron.php` | Callback `admin_atualizacoes_cron_automatica`. Não carrega o controlador do módulo. |
| Declaração | chave `cron` de `admin-atualizacoes.json` | Tarefa `admin-atualizacoes-automatica`, frequência `horario`, nasce inativa. A compilação passa a registrar "Tarefas de Cron: 1". |
| Painel | `admin-atualizacoes.php`, ação AJAX `auto` | `salvar`, `verificar` e `liberar`; monta o bloco de estado; liga e desliga a tarefa em `cron_tarefas` junto com o interruptor. |
| Tela | `atualizacoes-lista-tailwind` (pt-br e en) e `admin-atualizacoes.js` | Abas Manual e Automático abaixo do resumo; o fluxo manual foi só envolvido pela aba, com os mesmos ids. 57 variáveis novas por idioma. |

### Como o ciclo roda

A tarefa roda de hora em hora e é barata: sem rede na maior parte das vezes.

1. Desligada: encerra.
2. Há execução pendente: lê o log e o código de saída. Sucesso encerra; volta automática ou falha põe a versão nas recusadas.
3. Fora da hora preferida, ou checagem do período ainda em dia: encerra.
4. Consulta a versão estável mais recente (a de maior número entre as tags `gestor-v…` que não são rascunho nem pré-lançamento).
5. Dispara a atualização quando a versão é mais nova que a instalada, não está recusada, não há choque pendente e a trava de deploy está livre.
6. O disparo usa `atualizacoes-execucao.php`, o mesmo caminho da API: modo completo, com snapshot, verificação e volta automática.

### Decisões tomadas durante a implementação

- **A configuração mora em `autenticacoes/<domínio>/atualizacao-automatica.json`**, ao lado do `.env`: fora da área pública e fora do pacote da atualização. Escrita por arquivo temporário e troca.
- **As opções do atualizador saem de uma lista fechada no código** (`tag` e `backup`). O arquivo de configuração pode ser editado à mão sem que `wipe`, `no_health`, `no_rollback`, `no_verify`, `dry_run` ou modo parcial cheguem à linha de comando. Há teste com configuração adulterada.
- **A checagem manual não conta para o período.** "Verificar agora" registra a data e a versão para a tela, mas o vencimento usa só a checagem feita pela rotina. Na primeira versão a manual contava, e conferir à mão adiava a automação por um período inteiro; o teste no Lab mostrou e foi corrigido.
- **Trava ocupada não recusa a versão.** Código de saída `locked` e execução cuja pasta temporária sumiu encerram a pendência sem pôr a versão na lista de recusadas.
- **O interruptor marca a tarefa como ajustada no painel** (`user_modified`), para a sincronização dos manifestos não devolver o valor do arquivo. Se a tarefa ainda não existe no banco, é criada ali.
- **Consulta que falha não consome o período**: é repetida na hora seguinte.

## Guardas de teste

`tests/Unit/PHP/AtualizacaoAutomaticaReq245Test.php` (15 testes, 349 asserções): padrão desligado; leitura e gravação descartando chaves desconhecidas e valores fora da faixa; comparação de versões e tags malformadas; versão estável mais recente; vencimento nos três períodos; quando conferir; checagem manual que não adia a rotina; próxima checagem na hora preferida; decisão do ciclo com todos os motivos, versão mais nova que a recusada e liberação; resultado da execução pendente; opções entregues ao atualizador com configuração adulterada; declaração da tarefa; abas e variáveis da tela nos dois idiomas.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `php cli/c2f.php resources:sync` | 511 recursos Tailwind, "Tarefas de Cron: 1", "Nenhum problema detectado" |
| PHPUnit do core | 1.670 testes, 17.479 asserções, sem falhas (5 pulados, depreciações conhecidas) |
| Vitest do core | 48 arquivos, 569 testes, sem falhas |
| Pipeline `project:update-all conn2flow-site-local` | saída 0; 0 arquivos diferentes na conferência por hash |
| Roteiro `sdd/validation/req245/req245-browser.cjs` | 19 de 19 conferências, em 1366 px e 390 px |
| Regressão `sdd/validation/req243/req243-browser.cjs` | 140 de 140 conferências |
| Rotina pela engine (`admin-cron`, "Disparar agora") | desligada: "Atualizacao automatica desligada."; ligada fora da hora: "Sem checagem agora: fora-da-hora."; ligada na hora do servidor: "Versao publicada: gestor-v2.10.13. Nada a atualizar: ja-atualizado." |

O roteiro liga a automação, confere a configuração depois de recarregar, vê a tarefa ativa no `admin-cron`, usa "Verificar agora", desliga e confere a tarefa pausada. Termina com a automação desligada, como começou. Capturas em `sdd/validation/req245/evidencias/`.

## Critérios de aceite

| CA | Situação |
| --- | --- |
| CA-1, CA-4, CA-5, CA-6 | Cobertos por teste de unidade; CA-1 e CA-4 (fora da hora) também pela rotina no Lab. |
| CA-2 | Decisão e opções cobertas por teste; **o disparo real não foi exercitado** (ver Limites). |
| CA-3 | Lista de recusadas, versão mais nova e liberação cobertas por teste; **sem volta automática real**. |
| CA-7 | Navegador: abas, modo manual funcionando, textos resolvidos, 390 px. |
| CA-8 | Navegador: tarefa no `admin-cron` ativa com o interruptor ligado e pausada com ele desligado. |

## Limites e observações

- **Nenhuma atualização real foi disparada pela rotina.** O Lab está na versão publicada mais recente (`gestor-v2.10.13`), então o caminho "versão nova → disparo → resultado → recusa" só rodou em teste de unidade. O atualizador, a verificação e a volta automática que ele chama já foram validados na req-198 e na req-201; o que falta provar é a costura feita aqui. Sugestão: repetir no tenant isolado `project-test` com uma versão mais antiga instalada, uma vez com sucesso e uma vez com uma versão que quebra.
- **A rotina depende do agendador do servidor.** Ela roda quando a engine de rotinas (`gestor/cron.php`, frequência `horario`) é chamada pelo cron do host. Não conferi se o Lab tem essa entrada ativa; o disparo foi feito pelo painel.
- **Hora do servidor.** A hora preferida é comparada com o relógio do servidor (no Lab, diferente do horário de Brasília). A tela diz "no horário do servidor"; não há conversão de fuso.
- **A API pública do GitHub tem limite de requisições sem autenticação.** Com checagem no máximo diária por instalação não é problema; muitos sites atrás do mesmo IP de saída podem esbarrar nele. Nesse caso a consulta falha, fica registrada e é repetida na hora seguinte.
- **Base da branch.** `feat/req-245` nasce de `feat/req-243`. Integrar a 243 primeiro; a 245 entra limpa depois.
- **Não implementado** (sugestões da REQ-245, seção 7): aviso por e-mail, janela por dias da semana, espera de segurança antes de instalar uma versão recém-publicada, modo "só avisar".
- `sdd/implementation/` passa do limite de 10 relatórios na raiz com este lote: rodar `php cli/c2f.php ai:archive-sdd --keep=10 --repair-links` na integração.
