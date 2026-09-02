# BATCH-137 — Controle de animações do sistema operacional via CLI

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-135.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / CLI / acessibilidade / DX

## Objetivo

Adicionar ao CLI `c2f` comandos determinísticos e reversíveis para consultar e alternar a preferência
de animações do sistema operacional usada pelos navegadores em `prefers-reduced-motion`.

## Slice aprovado

1. Registrar um `MotionCommand` para `motion:status`, `motion:on`, `motion:off` e `motion:toggle`.
2. Suportar os aliases `motion:get` e `anim:status|on|off|toggle`.
3. No Windows, usar exclusivamente `SystemParametersInfoW` com
   `SPI_GETCLIENTAREAANIMATION`/`SPI_SETCLIENTAREAANIMATION` e notificação persistente.
4. No Linux GNOME, usar `gsettings`; no macOS, usar `defaults`.
5. Tratar outras plataformas informativamente com código de saída `0`.
6. Cobrir despacho, aliases, leitura, escrita, toggle, falhas e plataforma não suportada em PHPUnit.

## Decisões de implementação

- Um único comando canônico recebe todas as operações porque o dispatcher preserva o nome original
  em `InputInterface::getCommandName()` ao resolver aliases.
- A execução de processos será injetável nos testes para que nenhum teste altere a preferência real
  da estação do operador.
- As mensagens permanecem no vocabulário técnico em inglês do CLI existente; o subsistema CLI não
  inicializa o runtime de recursos/variáveis do CMS.

## Fora do escopo

- Alterar `MinAnimate`, `UserPreferencesMask` ou qualquer chave do Registro do Windows.
- Emular `prefers-reduced-motion` dentro do navegador ou alterar `page:inspect`.
- Instalar/configurar GNOME, `gsettings`, PowerShell ou ferramentas do sistema operacional.
- Fazer deploy, commit ou push.

## Contrato de validação

- `php -l` limpo em `MotionCommand.php`, `Application.php` e no teste novo.
- Testes focados do comando aprovados, sem mutação real do sistema operacional.
- Suíte PHPUnit completa aprovada.
- `c2f motion:status` reporta o estado real na plataforma local.
- Ciclo runtime `status → toggle → restauração` somente após captura explícita do estado inicial.
- `git diff --check` limpo e review findings-first concluído.

## Evidências

- `php -l`: **3/3** arquivos limpos (`MotionCommand.php`, `Application.php` e teste novo).
- Teste focado: **9/9**, 39 asserções, cobrindo aliases, Windows/PInvoke, Linux/GNOME,
  macOS, toggle, falhas e plataforma não suportada.
- Suíte PHPUnit completa: **774/774**, 3.324 asserções, 4 skips e 1 depreciação preexistente
  (baseline anterior: 765 testes).
- `git diff --check`: limpo.
- Runtime Windows real pelo wrapper `c2f.ps1`:
  - estado inicial capturado: `ON - prefers-reduced-motion: no-preference`;
  - `motion:toggle`: `OFF - prefers-reduced-motion: reduce`;
  - leitura intermediária confirmou `OFF`;
  - restauração em bloco `finally` com `motion:on`;
  - leitura final confirmou o estado original `ON`.
- A execução dentro do sandbox retornou Win32 `ERROR_ACCESS_DENIED (5)` sem alterar o estado; a
  mesma chamada fora do sandbox foi aprovada. Isso confirma restrição do ambiente de execução, não
  fallback para Registro ou necessidade de mudar a API.
- Busca no arquivo de produção confirmou ausência de `MinAnimate`, `UserPreferencesMask` e APIs de
  Registro. O teste também mantém guardas explícitas contra essas regressões.
- Review findings-first: sem findings funcionais, regressões, spec drift ou batch drift.
- Linux e macOS foram validados por testes de contrato com runner injetado; homologação runtime
  nessas plataformas depende de estações com GNOME/macOS, indisponíveis neste ambiente Windows.
- Memória de execução medida antes da poda: **3.608 bytes / 45 linhas**; após o registro deste lote,
  permaneceu abaixo do teto mediante substituição da tarefa recente mais antiga.
- Nível 1 respeitado: nenhum commit, push ou deploy executado.
