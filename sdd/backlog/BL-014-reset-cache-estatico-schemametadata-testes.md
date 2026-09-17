# BL-014 — Desacoplamento e reset do cache estático em `schemaMetadata()` para testes unitários PHPUnit

- **Tipo**: Reliability / Testing / Architecture
- **Status**: OPEN
- **Severidade sugerida**: BAIXA (afeta execução local com ordem `depends,defects`; CI executa em ordem padrão e passa 100%)
- **Origem**: Achado reportado na validação do BATCH-168 em 2026-09-17
- **Componentes**: `gestor/bibliotecas/atualizacoes-banco-de-dados.php`, `tests/Unit/PHP/ForcarAtualizacaoTest.php`, `tests/Unit/PHP/ProjectIdentityPassthroughTest.php`

## Contexto observado

1. Ao rodar a suíte completa do PHPUnit com a configuração definida no `phpunit.xml` (`executionOrder="depends,defects"`, que reaproveita cache local de testes com falha ou dependência), a suíte alterna entre verde e 2 falhas em `ForcarAtualizacaoTest`.
2. A causa raiz foi isolada e medida: a função `schemaMetadata()` em `gestor/bibliotecas/atualizacoes-banco-de-dados.php` (linha 277) armazena metadados lidos em uma variável interna `static $meta`.
3. Quando `ProjectIdentityPassthroughTest` é executado antes na mesma sessão do PHPUnit, ele instancia um diretório temporário isolado e popula `static $meta` apontando para esse diretório temporário.
4. Quando `ForcarAtualizacaoTest` executa em seguida, ele tenta ler metadados confiando na raiz padrão do Gestor, mas a função retorna o cache congelado da execução anterior, gerando asserções com falha intermitente.
5. No CI (onde o cache não existe e a execução roda na ordem padrão), os testes passam 100% (1.174/1.174). O defeito é puramente de acoplamento por estado estático em memória entre casos de teste concorrentes/sequenciais.

## Proposta de melhoria

1. **Adicionar Parâmetro de Reset / Invalidação em `schemaMetadata()`**:
   - Permitir `schemaMetadata($caminho = null, $reset = false)` para limpar `static $meta = null` quando solicitado explicitamente em teardowns ou setups de testes.
2. **Método Helper / Hook de Teste**:
   - Expor um helper formal de teste (ex: `gestor_schema_metadata_reset()`) chamado no `tearDown()` de classes que manipulam diretórios temporários de schemas.
3. **Isolamento de Processo Alternativo**:
   - Avaliar a anotação `@runInSeparateProcess` nos testes que criam diretórios temporários caso a modificação da assinatura da função não seja recomendada.

## Critérios de aceite (rascunho)

- Execução repetida de `composer test` com `executionOrder="depends,defects"` passa 100% de forma determinística, independente de caches prévios.
- Testes que utilizam fixtures temporárias limpam seu estado após a execução sem vazar variáveis estáticas.
- Suíte no CI permanece com 100% de aprovação.

> Item de backlog — registrado em `sdd/backlog/` para agendamento e promoção futura de intake.
