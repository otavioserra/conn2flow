# BL-029 — Erro intermitente na suíte PHPUnit no Windows (suspeita: bloqueio do antivírus)

- **Tipo**: Spike/Research (Reliability/Testing)
- **Status**: ICEBOX
- **Severidade sugerida**: BAIXA
- **Origem**: Executor, 2026-10-07, nas entregas da REQ-252 e da REQ-253 (linha 3.1); hipótese do Humano no mesmo dia.
- **Componentes**: `tests/Unit/PHP/*` que rodam PHP num processo à parte (arquivo temporário + `exec`), ambiente Windows do desenvolvedor.

## O que foi visto

- A suíte completa (`php vendor/bin/phpunit`, 1.702 e depois 1.709 testes) acusou **1 erro** em uma execução, em duas entregas diferentes. Nas execuções seguintes (três e cinco seguidas), nenhum erro.
- As duas ocorrências foram logo depois de rodar roteiros de navegador (Playwright) contra o Lab.
- **O teste não foi identificado**: nas duas vezes só a linha de resumo foi lida; quando a saída passou a ser guardada, o erro não voltou.

## Hipótese do Humano

Há dois ou três dias o Windows mostra um aviso de vírus encontrado; a verificação manual do antivírus não acha nada. O bloqueio pode estar atingindo arquivo ou processo criado pelos testes.

## O que sustenta a hipótese

Vários testes escrevem um script PHP na pasta temporária (`tempnam`) e o executam com `exec`. É o padrão que um antivírus em tempo real costuma inspecionar ou segurar: arquivo recém-criado, executado em seguida e apagado. Um atraso ou bloqueio ali vira erro de leitura de saída ou código de saída diferente de zero.

Isso é suposição: não há registro que ligue o aviso do Windows ao horário das falhas.

## Proposta de investigação (quando for promovido)

1. Rodar a suíte em laço guardando a saída completa de cada execução, até capturar o nome do teste e a mensagem.
2. Cruzar o horário com o histórico de proteção do Windows (Segurança do Windows > Histórico de proteção) e com o Visualizador de Eventos.
3. Repetir com a pasta temporária dos testes fora da verificação em tempo real, para confirmar ou descartar.
4. Rodar a mesma suíte no `ssh lab` (referência de testes PHP) no mesmo laço: se lá nunca falha, o problema é do ambiente Windows.
5. Conforme o resultado: pasta temporária própria dos testes, nova tentativa na leitura do processo filho, ou só a exclusão documentada.

## Fora do escopo

Mudar o antivírus da máquina do desenvolvedor sem ele.
