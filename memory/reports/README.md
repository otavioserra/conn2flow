# Reports

Relatórios consolidados de sessão, escritos para leitura humana — tipicamente para a chefia ou para
retomar contexto meses depois.

## Por que um diretório próprio

O SDD já tinha três gêneros vizinhos, e nenhum serve a este propósito:

| Diretório | Pergunta que responde | Recorte |
| --- | --- | --- |
| `implementation/` | O que foi feito neste lote? | **um** batch |
| `reviews/` | O que está errado neste código? | findings, não narrativa |
| `handoffs/` | Onde o próximo agente continua? | estado, não histórico |
| **`reports/`** | **O que aconteceu nesta sessão, e por quê?** | **vários lotes, uma sessão** |

Um relatório atravessa lotes. Uma sessão que abre três batches encadeados tem uma história que
nenhum `BATCH-XXX.md` conta sozinho: qual defeito levou ao seguinte, que abordagem foi descartada e
por quê, o que a medição refutou. É esse encadeamento que se perde quando o registro é só por lote.

## Quando escrever

- A sessão fechou **mais de um lote** e eles se explicam mutuamente.
- Houve **mudança de rumo** que vale registrar: uma hipótese refutada por medição, uma correção
  barrada por um teste, um diagnóstico corrigido no meio do caminho.
- O operador vai **apresentar o trabalho** a terceiros.

Sessão de um lote só não precisa de relatório: o `BATCH-XXX.md` já a cobre.

## Convenções

- Nome: `REPORT-<AAAA-MM-DD>-<escopo>.md` — mesmo padrão de `reviews/`.
- O relatório **não é fonte normativa**. Ele narra e consolida; a autoridade continua sendo o batch,
  a decision e o intake, que ele deve referenciar.
- Números vão com a medição que os produziu. "Melhorou" não é dado; `99 → 5 classes sem regra` é.
- Registrar também o que **não** deu certo. A abordagem descartada costuma ser a informação mais
  cara de reconstruir depois.

## Arquivamento

Máximo de 10 relatórios correntes; os mais antigos vão para `archive/`, com uma linha em
[REPORTS-INDEX.md](REPORTS-INDEX.md) apontando para o arquivo — mesma regra dos demais diretórios do
SDD.
