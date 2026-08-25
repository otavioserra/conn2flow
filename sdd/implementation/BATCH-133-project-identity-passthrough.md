# BATCH-133: Repasse da Identidade do Projeto ao Atualizador de Banco no Deploy Local

Intake: [req-131.md](../human-requests/req-131.md)
Validação: [VALIDATION-CHECKLIST.md#batch-133](../validation/VALIDATION-CHECKLIST.md#batch-133)
Status: `implemented-pending-homologation`

Este lote corrige a assimetria entre os dois deploys do mesmo projeto: o remoto (via API) sempre
informou a identidade do projeto ao atualizador de banco; o local, não. Sem ela, o deploy do projeto
era tratado como atualização normal do núcleo e ficava bloqueado pela marcação `project` que ele
mesmo havia gravado — em silêncio, com o relatório fechando em sucesso.

---

## Atividades e Checklist

### 1. [x] Repasse condicionado em `updates-manager-database.sh`

* Em `ai-workspace/en/scripts/dev-environment/updates-manager-database.sh`, após a montagem de
  `PHP_ARGS`:
  - Quando `PROJECT_TARGET` estiver definido, acrescentar `--project=$PROJECT_TARGET`.
  - Validar o identificador com `^[a-zA-Z0-9_-]+$` **antes** de compor a linha executada por
    `docker exec`, no mesmo critério já aplicado a `--tables`.
  - Registrar no log que a execução é um deploy de projeto e que os recursos serão marcados.

`PROJECT_TARGET` só é atribuído dentro do bloco que trata `--project`; sem o parâmetro ele
permanece vazio e nada é repassado — a atualização normal do núcleo segue idêntica.

### 2. [x] Teste de regressão

* `tests/Unit/PHP/ProjectIdentityPassthroughTest.php` (novo), com PDO SQLite em memória e contrato
  temporário, no mesmo padrão do `ForcarAtualizacaoTest`:
  - **o defeito reproduzido**: sem identidade, o recurso marcado não é atualizado e a linha é
    contada como "sem alteração";
  - com identidade, o recurso é atualizado e a marcação é mantida;
  - o deploy de **outro** projeto reescreve a marcação (é quem publicou por último);
  - **a garantia do operador**: com `user_modified = 1`, o conteúdo do usuário é preservado e a
    marcação dele permanece, mesmo no deploy de projeto; `system_updated` sobe para 1;
  - o script repassa a identidade, de forma **condicionada**, e valida o identificador.

---

## Decisões da execução

### O que NÃO foi alterado, e por quê

**A regra de proteção em `sincronizarTabela()` está correta** e não foi tocada. O defeito era de
alimentação, não de lógica: uma linha faltando no script. Reescrever a regra teria produzido um diff
grande sobre o caminho crítico de todo deploy — inclusive o de produção, que nunca teve o problema.

**`--force-all` não foi adotado como prática**, e a documentação passa a dizer por quê: ele só ignora
o cache de checksums por tabela; não desliga a proteção de projeto e, portanto, não resolveria o
caso. `forcar_atualizacao` resolve item a item, mas **atravessa a proteção de `user_modified` e a
reseta para 0** — usá-lo rotineiramente colocaria em risco as páginas editadas pelo cliente.

## 3. [x] Coluna espelho descartada quando estava `NULL`

Achado durante a validação do item anterior e corrigido a pedido do operador.

Quando um recurso com `user_modified = 1` recebia versão nova do sistema, o valor novo só era
copiado para `<campo>_updated` se **essa coluna já tivesse algum valor**:

```php
$dest = $campo.'_updated';
if (isset($exist[$dest]) || isset($row[$dest])) $diff[$dest] = $diff[$campo];
```

`isset(null)` é **falso**, e a coluna espelho nasce `NULL`. Pior: `comparacaoDados()` injeta
`'html_updated' => null` no payload **justamente para habilitar esse mecanismo** — a injeção só faz
sentido com verificação por existência de chave. O resultado era que a versão do sistema não era
guardada exatamente no caso que a coluna existe para atender: a **primeira** divergência entre o que
o usuário editou e o que o deploy traz. O conteúdo do usuário seguia protegido (isso nunca falhou),
mas ele perdia a chance de ver e comparar o que mudaria.

* Novo helper `colunaEspelhoDisponivel()`, aplicado aos **três** caminhos de sincronização (PK,
  chave natural e fallback) e às três variantes (`value_updated`, `<campo>_updated`,
  `system_updated`).
* Quando o schema real da tabela é conhecido, **ele** é a fonte de verdade — assim uma tabela sem a
  coluna espelho nunca recebe o campo no `UPDATE` (mesmo cuidado do guard de `project`). Sem o
  schema, cai na existência da chave.

### O que este achado NÃO resolve, e é comportamento de projeto

`preserve_on_user_modified` de `paginas` protege `nome`, `layout_id`, `caminho`, `framework_css`,
`sem_permissao`, `html`, `css` e `css_compiled`. Só `html` e `css` têm coluna espelho no schema.

Portanto, num recurso com `user_modified = 1`, alterar pelo código o **nome** ou o **caminho** de uma
página continua não chegando ao banco — e agora sem nem sequer ficar disponível para comparação,
porque não há `nome_updated`. O único rastro é `system_updated = 1`.

**Medido no ambiente local**, com a página `snapphoton-system-tokens`:

| Cenário | `nome` alterado só no código | Resultado |
| --- | --- | --- |
| `user_modified = 0` | sim | **chega ao banco** (`versao` 2 → 3) |
| `user_modified = 1` | sim | **não chega**; `system_updated` sobe para 1 e o valor novo é descartado |

Isso é a proteção funcionando como projetada — não é defeito. Mas explica o sintoma de "alterei um
valor do recurso e ele não mudou": basta que aquele recurso tenha sido editado uma vez pela
interface. Ampliar as colunas espelho, ou expor a divergência na tela, é decisão de produto e fica
registrada como candidata a requisição própria.

---

## Impacto nas tarefas do VS Code

| Tarefa | Situação |
| --- | --- |
| `🗃️ Projects - Update => Core` | **Corrigida** — depende de `Synchronize => Database -> ID` |
| `🗃️ Projects - Synchronize => Database -> ID` | **Corrigida** |
| `🗃️ Projects - Deploy Project -> ID` | Não afetada — o caminho remoto (`X-Project-ID` → `api.php`) sempre repassou |
| `🗃️ Manager - Synchronize => Database - Test Environment` | Não afetada — sem `--project`, nada é repassado e nada é marcado |

---

## Validação

- `bash -n` no script: OK.
- `tests/Unit/PHP/ProjectIdentityPassthroughTest.php`: **5 testes, 18 asserções**, verdes. A classe
  roda em **processos isolados**: `schemaMetadata()` guarda o contrato numa `static` lida do primeiro
  `DB_DATA_DIR` do processo, e sem o isolamento a segunda classe de teste lê o contrato da primeira —
  o `ForcarAtualizacaoTest` ficou vermelho, sem nenhuma alteração nele, na primeira versão do arquivo.
- Suíte completa do núcleo: **702 testes, 3.174 asserções**, sem regressão (1 deprecation e 4
  skipped pré-existentes).
- **Validação ponta a ponta no ambiente local** (projeto `snapphoton-local`): um marcador foi
  acrescentado ao CSS de uma página com `project` preenchido e o deploy foi rodado **sem**
  `--force-all` e **sem** `forcar_atualizacao`. O CSS chegou ao banco (`versao` 2 → 3), com
  `project` preservado e `user_modified` intacto em 0. O contorno declarativo que havia sido criado
  no projeto foi removido, por ter deixado de ser necessário.
