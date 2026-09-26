---
title: "Biblioteca cron.php"
label: "Rotinas automáticas"
description: "Rotinas automáticas: declarar uma tarefa no módulo, agendar os ticks no servidor e o que a engine gestor/cron.php realmente executa."
section: reference
order: 130
sources:
  - gestor/bibliotecas/cron.php
  - gestor/cron.php
  - gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php
  - gestor/modulos/admin-cron/admin-cron.php
verified_at: f2653c2b
---

# Biblioteca `cron.php`

Rotinas automáticas têm três partes:

1. **A declaração**, na chave `cron` do JSON do módulo, que o pipeline grava na tabela `cron_tarefas`.
2. **A engine** `gestor/cron.php`, um script de linha de comando que executa as tarefas de uma frequência.
3. **O agendador do servidor** (crontab, painel da hospedagem), que chama a engine. **Nada no Conn2Flow cria esse agendamento**: nem o instalador, nem o painel. Sem ele, nenhuma tarefa roda.

A biblioteca `cron.php` concentra o que a engine, o compilador de recursos e o painel `admin-cron` compartilham.

## Declarar uma tarefa

Na **raiz** do `<modulo>.json`:

```json
{
  "cron": [
    {
      "id": "expiracao-trials",
      "nome": "Expirar assinaturas de teste",
      "funcao": "meu_modulo_cron_expirar_trials",
      "frequencia": "diario",
      "hora": "03:30",
      "parametros": { "dias": 7 },
      "ativo": true
    }
  ]
}
```

| Campo | |
|---|---|
| `id` | Único entre **todos** os módulos |
| `funcao` | A função PHP. Se ainda não existir, a engine inclui `gestor/modulos/<modulo>/<modulo>.cron.php`; o `<modulo>.php` nunca é incluído |
| `frequencia` | `minutario`, `horario`, `diario` (padrão), `mensal` ou `customizado` |
| `hora`, `dia` | `HH:MM` e dia do mês (1–31, só `mensal`), para montar `expressao_cron` |
| `expressao_cron` | Cinco campos; obrigatória em `customizado` e vence `hora`/`dia` |
| `parametros` | Objeto passado à função |
| `ativo` | `false` grava a tarefa pausada |

Tarefa sem `id` ou `funcao`, com frequência desconhecida ou expressão inválida vira **órfã** na compilação e não chega ao banco ([recursos](../../concepts/resources.md)). No painel, `ativo`, `expressao_cron` e `parametros` podem ser ajustados e são preservados no deploy.

## A função da tarefa

```php
// gestor/modulos/meu-modulo/meu-modulo.cron.php
function meu_modulo_cron_expirar_trials($parametros) {
    $dias = (int)($parametros['dias'] ?? 7);
    // ...
    echo "12 assinaturas expiradas\n";          // vira o log da execução
    return ['status' => 'aviso', 'log' => '3 sem e-mail']; // opcional
}
```

`cron_tarefa_executar()` captura tudo que a função imprime e o retorno:
- `['status' => 'sucesso|erro|aviso', 'log' => …]` define o resultado;
- `false` é erro; qualquer outro retorno é sucesso;
- uma exceção vira erro e **não interrompe** as outras tarefas.

O resultado vai para a própria linha em `cron_tarefas` (`ultimo_disparo`, `ultima_duracao_ms`, `ultimo_status` e `ultimo_log`, cortado em 4000 bytes) e para `gestor/logs/cron-<dd-mm-aaaa>.log`.

## Agendar a engine

```
php gestor/cron.php frequencia=<frequencia> [server=<dominio>] [debug]
php gestor/cron.php tarefa=<id> [debug]      # uma tarefa, fora da janela (pausada não roda)
php gestor/cron.php listar
```

Registre **um tick por frequência** no agendador do servidor, com os horários padrão da biblioteca (`cron_expressao_padrao()`):

```
*/10 * * * *  php /caminho/gestor/cron.php frequencia=minutario
0 * * * *     php /caminho/gestor/cron.php frequencia=horario
0 3 * * *     php /caminho/gestor/cron.php frequencia=diario
0 4 1 * *     php /caminho/gestor/cron.php frequencia=mensal
```

Com uma única pasta com `.env` em `autenticacoes/`, o domínio é detectado; com várias, `server=<dominio>` é obrigatório (sem ele, a engine sai com erro em vez de rodar contra o banco errado).

> [!WARNING]
> **A engine não avalia `expressao_cron`.** Cada tick executa todas as tarefas ativas daquela frequência, na hora em que o tick roda. `hora`, `dia` e `expressao_cron` só são guardados e mostrados no painel. Uma tarefa `diario` com `hora: "03:30"` roda quando o tick `diario` do servidor rodar. Tarefas `customizado` só rodam com um tick `frequencia=customizado` (que executa todas juntas) ou com `tarefa=<id>`.

> [!NOTE]
> Não há trava contra execuções sobrepostas: se uma tarefa `minutario` demora mais de 10 minutos, o tick seguinte a executa de novo em paralelo. Tarefas longas precisam da própria trava.

O painel `admin-cron` só **infere** se há agendador: se alguma tarefa ativa rodou nas últimas 24 horas.

Para compatibilidade, cada tick também dispara o hook `cron.<frequencia>` ([hooks](../../concepts/hooks.md)).

## Funções auxiliares

- `cron_frequencias_validas()`, `cron_status_validos()`: os vocabulários.
- `cron_expressao_valida($e)`: só a forma (cinco campos com dígitos e `* , - /`), não se os valores fazem sentido.
- `cron_expressao_declarada($tarefa, $frequencia)`: a expressão final, ou `null` se inválida.
- `cron_tarefas_carregar($frequencia, $tarefaId, $todas, $campos)`: lê `cron_tarefas` (sem as excluídas; sem as pausadas, a menos que `$todas`).

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `cron_callback_preparar()`, `cron_tarefa_registrar()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/cron.php` por `c2f docs:extract` — 9 funções. Não edite dentro deste bloco.

- `cron_frequencias_validas()` — [linha 24](../../../../../gestor/bibliotecas/cron.php#L24)
- `cron_status_validos()` — [linha 31](../../../../../gestor/bibliotecas/cron.php#L31)
- `cron_expressao_valida(string $expressao): bool` — [linha 45](../../../../../gestor/bibliotecas/cron.php#L45)
- `cron_expressao_padrao(string $frequencia): string|null` — [linha 60](../../../../../gestor/bibliotecas/cron.php#L60)
- `cron_expressao_declarada(array $tarefa, string $frequencia): string|null` — [linha 85](../../../../../gestor/bibliotecas/cron.php#L85)
- `cron_callback_preparar(array $tarefa): string|null` — [linha 126](../../../../../gestor/bibliotecas/cron.php#L126)
- `cron_tarefa_executar(array $tarefa): array{status: string, duracao: int, log: string}` — [linha 164](../../../../../gestor/bibliotecas/cron.php#L164)
- `cron_tarefa_registrar(string $id, string $status, int $duracaoMs, string $log): void` — [linha 222](../../../../../gestor/bibliotecas/cron.php#L222)
- `cron_tarefas_carregar(string|null $frequencia = null, string|null $tarefaId = null, bool $todas = false, array $campos = null): array` — [linha 245](../../../../../gestor/bibliotecas/cron.php#L245)

<!-- c2f:extract:end -->
