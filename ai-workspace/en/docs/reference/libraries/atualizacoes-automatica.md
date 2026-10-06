---
title: "atualizacoes-automatica.php library"
description: "Per-installation automatic update scheduling, decisions and refused versions."
section: reference
order: 100
sources:
  - gestor/bibliotecas/atualizacoes-automatica.php
verified_at: 914c7b10
---

# atualizacoes-automatica.php library


Pure library; the caller supplies installation folder, current time and update context. The cron task invokes the shared execution library. See [Updates](../modules/admin-atualizacoes.md).

- `atualizacao_automatica_padrao`: Disabled, weekly, hour 3 and backup defaults; initializes state.
- `atualizacao_automatica_arquivo`: Builds the path from the private installation folder.
- `atualizacao_automatica_normalizar`: Validates interval, hour, tags and types; discards unknown keys.
- `atualizacao_automatica_ler`: Missing, invalid or unreadable files return normalized defaults.
- `atualizacao_automatica_gravar`: Normalizes, writes a temporary file and renames it; returns bool.
- `atualizacao_automatica_tag_valida`: Accepts only gestor-v and two to four numeric groups.
- `atualizacao_automatica_numero`: Extracts the numeric version; returns null if unreadable.
- `atualizacao_automatica_mais_nova`: Uses version_compare; unreadable versions return false.
- `atualizacao_automatica_ultima_estavel`: Selects the highest valid tag, ignoring draft and prerelease.
- `atualizacao_automatica_vencida`: Uses ultima_automatica and days × 86400 minus 7200 seconds.
- `atualizacao_automatica_proxima`: Calculates the next eligible hour; disabled returns null.
- `atualizacao_automatica_deve_conferir`: Rejects disabled, pending, outside-hour or current-interval checks.
- `atualizacao_automatica_decidir`: Uses instalada, publicada, choques_pendentes and trava_ocupada; returns atualizar, motivo and tag.
- `atualizacao_automatica_opcoes`: Closed list: tag and backup; cannot disable verification or rollback.
- `atualizacao_automatica_aplicar_resultado`: running stays pending; success and locked do not refuse; other results refuse.
- `atualizacao_automatica_liberar`: Removes a tag from recusadas without starting an update.
- `atualizacao_automatica_consultar`: Queries HTTPS with certificate verification, 30 s timeout and 10 s connection timeout; errors return ok=false.

## Generated function reference

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/atualizacoes-automatica.php` by `c2f docs:extract` — 17 functions. Do not edit inside this block.

- `atualizacao_automatica_padrao(): array` — [line 24](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L24)
  Configuração e estado iniciais.
- `atualizacao_automatica_arquivo(string $pastaDoHost): string` — [line 43](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L43)
  Caminho do arquivo da instalação.
- `atualizacao_automatica_normalizar(array $dados): array` — [line 51](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L51)
  Normaliza o que veio do arquivo ou do formulário: tipos, período conhecido, hora de 0 a 23 e lista de recusadas só com tags bem formadas. Chave desconhecida é descartada.
- `atualizacao_automatica_ler(string $pastaDoHost): array` — [line 89](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L89)
  Lê a configuração da instalação; arquivo ausente ou ilegível devolve o padrão (desligado).
- `atualizacao_automatica_gravar(string $pastaDoHost, array $dados): bool` — [line 97](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L97)
  Grava a configuração (escrita em arquivo temporário e troca, para não deixar o arquivo pela metade).
- `atualizacao_automatica_tag_valida(string $tag): bool` — [line 108](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L108)
  Tag de release do sistema (`gestor-v1.2.3`).
- `atualizacao_automatica_numero(string $versao): ?string` — [line 113](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L113)
  Número da versão a partir da tag ou do texto da versão instalada (`gestor-v2.10.13`, `v2.10.13`, `2.10.13`).
- `atualizacao_automatica_mais_nova(string $tag, string $instalada): bool` — [line 118](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L118)
  A tag publicada é mais nova que a versão instalada? Versão ilegível nunca é "mais nova".
- `atualizacao_automatica_ultima_estavel(array $releases): ?string` — [line 129](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L129)
  Versão estável mais recente numa resposta da API de releases do GitHub: a de maior número entre as tags `gestor-v…` que não são rascunho nem pré-lançamento.
- `atualizacao_automatica_vencida(array $config, int $agora): bool` — [line 145](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L145)
  A checagem está vencida? Uma folga de duas horas absorve o atraso da própria tarefa: sem ela, uma checagem diária feita às 03:05 nunca estaria vencida às 03:00 do dia seguinte.
- `atualizacao_automatica_proxima(array $config, int $agora): ?int` — [line 154](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L154)
  Momento da próxima checagem (para a tela): a hora preferida, no primeiro dia em que estiver vencida.
- `atualizacao_automatica_deve_conferir(array $config, int $agora): array` — [line 170](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L170)
  Deve a tarefa conferir a versão publicada agora? Devolve o motivo quando não.
  Returns: ['conferir' => bool, 'motivo' => string]
- `atualizacao_automatica_decidir(array $config, array $contexto): array` — [line 185](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L185)
  Decisão do ciclo depois de conferida a versão publicada.
  Parameters:
  - `$contexto`: ['instalada' => string, 'publicada' => ?string, 'choques_pendentes' => int, 'trava_ocupada' => bool]
  Returns: ['atualizar' => bool, 'motivo' => string, 'tag' => ?string]
- `atualizacao_automatica_opcoes(array $config, string $tag): array` — [line 202](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L202)
  Opções que a automação entrega ao atualizador para uma tag. Sempre modo completo, com verificação e volta automática: só `tag` e, se pedido, `backup`.
- `atualizacao_automatica_aplicar_resultado(array $config, string $status, int $agora): array` — [line 215](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L215)
  Aplica ao estado o resultado de uma execução pendente. Sucesso encerra; volta automática ou falha põe a versão nas recusadas; ainda rodando, nada muda.
  Parameters:
  - `$status`: Um dos valores de `ATUALIZACOES_EXECUCAO_CODIGOS` ou `running`.
- `atualizacao_automatica_liberar(array $config, string $tag): array` — [line 229](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L229)
  Libera uma versão recusada para nova tentativa.
- `atualizacao_automatica_consultar(string $url = 'https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=30'): array` — [line 241](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L241)
  Consulta as releases publicadas. Com verificação de certificado: uma resposta forjada aqui decidiria qual versão o site instala sozinho.
  Returns: ['ok' => bool, 'tag' => ?string, 'erro' => string]

<!-- c2f:extract:end -->
