# BL-022 — `configuracao`: POST truncado por `max_input_vars` apaga variáveis

- **Tipo**: Bug / Perda de dados
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: ALTA (perda silenciosa de configuração)
- **Origem**: reescrita de `reference/libraries/configuracao.md` (req-179), 2026-09-25
- **Componentes**: `gestor/bibliotecas/configuracao.php`

## Contexto observado

1. Ao salvar, a biblioteca apaga as variáveis do módulo que **não vieram** no POST ("exclusão por ausência").
2. Cada variável ocupa 6 campos no POST. Com o padrão do PHP (`max_input_vars = 1000`), um módulo com mais de ~160 variáveis tem o POST cortado.
3. As variáveis que ficaram de fora do POST são apagadas sem aviso.
4. `modulo` e `linguagemCodigo` entram no SQL sem escape (os chamadores do core passam valores já escapados).

## Proposta

1. Enviar um campo de controle com a contagem esperada (ou a lista de ids) e recusar o salvamento quando o POST chegar menor.
2. Alternativa: exclusão explícita (botão por linha) em vez de exclusão por ausência.
3. Escapar `modulo`/`linguagemCodigo` dentro da função.

## Critérios de aceite (rascunho)

- Com `max_input_vars` baixo no teste, salvar não apaga nada e mostra erro.
- Excluir uma variável continua possível.
