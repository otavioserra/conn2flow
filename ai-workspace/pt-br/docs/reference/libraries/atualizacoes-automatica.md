---
title: "Biblioteca atualizacoes-automatica.php"
description: "Agendamento, decisões e versões recusadas na atualização automática por instalação."
section: reference
order: 100
sources:
  - gestor/bibliotecas/atualizacoes-automatica.php
verified_at: 914c7b10
---

# Biblioteca atualizacoes-automatica.php


Biblioteca pura: o chamador fornece pasta da instalação, relógio e contexto da atualização. A tarefa cron chama a biblioteca de execução compartilhada. Veja [Atualizações](../modules/admin-atualizacoes.md).

- `atualizacao_automatica_padrao`: Configuração desligada, semanal, hora 3 e backup; inicializa estado.
- `atualizacao_automatica_arquivo`: Monta o caminho a partir da pasta privada da instalação.
- `atualizacao_automatica_normalizar`: Valida período, hora, tags e tipos; descarta chaves desconhecidas.
- `atualizacao_automatica_ler`: Arquivo ausente, inválido ou ilegível retorna configuração normalizada padrão.
- `atualizacao_automatica_gravar`: Normaliza, escreve temporário e troca; retorna bool.
- `atualizacao_automatica_tag_valida`: Aceita somente gestor-v e de dois a quatro grupos numéricos.
- `atualizacao_automatica_numero`: Extrai a versão numérica; retorna null se ilegível.
- `atualizacao_automatica_mais_nova`: Compara com version_compare; versão ilegível retorna false.
- `atualizacao_automatica_ultima_estavel`: Seleciona a maior tag válida, ignorando draft e prerelease.
- `atualizacao_automatica_vencida`: Usa ultima_automatica e dias × 86400 menos 7200 segundos.
- `atualizacao_automatica_proxima`: Calcula a próxima hora elegível; desligada retorna null.
- `atualizacao_automatica_deve_conferir`: Recusa desligada, execução pendente, fora da hora ou período em dia.
- `atualizacao_automatica_decidir`: Usa instalada, publicada, choques_pendentes e trava_ocupada; devolve atualizar, motivo e tag.
- `atualizacao_automatica_opcoes`: Lista fechada: tag e backup; não permite desligar verificação ou rollback.
- `atualizacao_automatica_aplicar_resultado`: running mantém pendente; success e locked não recusam; outros resultados recusam.
- `atualizacao_automatica_liberar`: Remove uma tag da lista recusadas, sem disparar atualização.
- `atualizacao_automatica_consultar`: Consulta HTTPS com certificado verificado, timeout de 30 s e conexão de 10 s; erro traz ok=false.

## Referência extraída de funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/atualizacoes-automatica.php` por `c2f docs:extract` — 17 funções. Não edite dentro deste bloco.

- `atualizacao_automatica_padrao(): array` — [linha 24](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L24)
  Configuração e estado iniciais.
- `atualizacao_automatica_arquivo(string $pastaDoHost): string` — [linha 43](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L43)
  Caminho do arquivo da instalação.
- `atualizacao_automatica_normalizar(array $dados): array` — [linha 51](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L51)
  Normaliza o que veio do arquivo ou do formulário: tipos, período conhecido, hora de 0 a 23 e lista de recusadas só com tags bem formadas. Chave desconhecida é descartada.
- `atualizacao_automatica_ler(string $pastaDoHost): array` — [linha 89](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L89)
  Lê a configuração da instalação; arquivo ausente ou ilegível devolve o padrão (desligado).
- `atualizacao_automatica_gravar(string $pastaDoHost, array $dados): bool` — [linha 97](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L97)
  Grava a configuração (escrita em arquivo temporário e troca, para não deixar o arquivo pela metade).
- `atualizacao_automatica_tag_valida(string $tag): bool` — [linha 108](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L108)
  Tag de release do sistema (`gestor-v1.2.3`).
- `atualizacao_automatica_numero(string $versao): ?string` — [linha 113](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L113)
  Número da versão a partir da tag ou do texto da versão instalada (`gestor-v2.10.13`, `v2.10.13`, `2.10.13`).
- `atualizacao_automatica_mais_nova(string $tag, string $instalada): bool` — [linha 118](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L118)
  A tag publicada é mais nova que a versão instalada? Versão ilegível nunca é "mais nova".
- `atualizacao_automatica_ultima_estavel(array $releases): ?string` — [linha 129](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L129)
  Versão estável mais recente numa resposta da API de releases do GitHub: a de maior número entre as tags `gestor-v…` que não são rascunho nem pré-lançamento.
- `atualizacao_automatica_vencida(array $config, int $agora): bool` — [linha 145](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L145)
  A checagem está vencida? Uma folga de duas horas absorve o atraso da própria tarefa: sem ela, uma checagem diária feita às 03:05 nunca estaria vencida às 03:00 do dia seguinte.
- `atualizacao_automatica_proxima(array $config, int $agora): ?int` — [linha 154](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L154)
  Momento da próxima checagem (para a tela): a hora preferida, no primeiro dia em que estiver vencida.
- `atualizacao_automatica_deve_conferir(array $config, int $agora): array` — [linha 170](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L170)
  Deve a tarefa conferir a versão publicada agora? Devolve o motivo quando não.
  Retorno: ['conferir' => bool, 'motivo' => string]
- `atualizacao_automatica_decidir(array $config, array $contexto): array` — [linha 185](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L185)
  Decisão do ciclo depois de conferida a versão publicada.
  Parâmetros:
  - `$contexto`: ['instalada' => string, 'publicada' => ?string, 'choques_pendentes' => int, 'trava_ocupada' => bool]
  Retorno: ['atualizar' => bool, 'motivo' => string, 'tag' => ?string]
- `atualizacao_automatica_opcoes(array $config, string $tag): array` — [linha 202](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L202)
  Opções que a automação entrega ao atualizador para uma tag. Sempre modo completo, com verificação e volta automática: só `tag` e, se pedido, `backup`.
- `atualizacao_automatica_aplicar_resultado(array $config, string $status, int $agora): array` — [linha 215](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L215)
  Aplica ao estado o resultado de uma execução pendente. Sucesso encerra; volta automática ou falha põe a versão nas recusadas; ainda rodando, nada muda.
  Parâmetros:
  - `$status`: Um dos valores de `ATUALIZACOES_EXECUCAO_CODIGOS` ou `running`.
- `atualizacao_automatica_liberar(array $config, string $tag): array` — [linha 229](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L229)
  Libera uma versão recusada para nova tentativa.
- `atualizacao_automatica_consultar(string $url = 'https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=30'): array` — [linha 241](../../../../../gestor/bibliotecas/atualizacoes-automatica.php#L241)
  Consulta as releases publicadas. Com verificação de certificado: uma resposta forjada aqui decidiria qual versão o site instala sozinho.
  Retorno: ['ok' => bool, 'tag' => ?string, 'erro' => string]

<!-- c2f:extract:end -->
