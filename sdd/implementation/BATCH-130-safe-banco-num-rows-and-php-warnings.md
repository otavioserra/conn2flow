# BATCH-130: Blindagem de banco_num_rows, Proteção na Consulta de Hosts em perfil-usuario e Eliminação de Warnings de Chaves Indefinidas no Core

Intake: [req-128.md](../human-requests/req-128.md)
Validação: [VALIDATION-CHECKLIST.md#batch-130](../validation/VALIDATION-CHECKLIST.md#batch-130)
Status: `implemented-pending-homologation`

Este lote de implementação descreve as tarefas técnicas para mitigar os erros de execução reportados nos logs de produção do núcleo `conn2flow`, blindando o driver de banco de dados contra `TypeError` no PHP 8.1+, tornando defensiva a busca por hosts no perfil de usuário e sanando warnings de índices não definidos.

---

## Atividades e Checklist

### 1. [x] Blindagem em `banco.php`
* Em `banco_num_rows($result)`:
  - Verificar se `$result` é falso, nulo ou se não é uma instância de `mysqli_result` (ou `BancoResultadoRemoto`). Retornar `0` nesses casos.
* Em `banco_select()`:
  - Substituir `if(banco_num_rows($res))` por `if($res && banco_num_rows($res))`.

### 2. [x] Proteção de Consulta em `perfil-usuario.php`
* Na função `perfil_usuario_redefine_password()`:
  - Proteger a consulta de `usuarios_gestores_hosts` com verificação de tabela existente ou guard multi-host, tratando `$id_hosts = null` caso a tabela não exista.

### 3. [x] Eliminação de Warnings em `admin-paginas.php` e `gestor.php`
* Em `admin-paginas.php`:
  - Linhas 111 e 114: substituir `if($_REQUEST[$post_nome])` por `if(!empty($_REQUEST[$post_nome]))`.
* Em `gestor.php`:
  - Linha 2871: garantir `$layout_css_compiled = $layouts['css_compiled'] ?? '';`.

---

## Critérios de Aceite e Validação

1. **Sintaxe:** `php -l` verde em todos os arquivos alterados.
2. **Suíte PHPUnit:** Testes unitários do núcleo executando sem regressões.
3. **Resiliência:** `banco_num_rows(false)` e `banco_select` com query inválida não disparam fatal error.

---

## Implementação concluída

- `banco_num_rows()` preserva `BancoResultadoRemoto`, aceita somente `mysqli_result` no caminho
  MySQLi e devolve `0` para `false`, `null` ou qualquer outro tipo. `banco_select()` verifica o
  retorno de `banco_query()` antes de inspecionar linhas.
- `perfil_usuario_redefine_password()` inicializa `$id_hosts = null` e só consulta
  `usuarios_gestores_hosts` quando `gestor_schema_tabela_existe()` confirma a tabela. O gate já é
  memoizado por requisição e falha fechado em schema indisponível.
- Os campos opcionais `raiz` e `sem_permissao` usam `!empty()` tanto na inclusão quanto na clonagem
  de páginas; o CSS compilado ausente no layout passa a assumir string vazia com `?? ''`.
- `tests/Unit/PHP/Req128HardeningTest.php` cobre os cinco contratos de regressão do lote.

## Evidência de validação

- `php -l`: **OK** nos quatro arquivos de produção e no teste novo.
- Teste focado: **5/5**, 8 asserções, sem warnings ou depreciações.
- Suíte completa: **685/685**, 3.094 asserções e 4 skips de ambiente. Permanece uma depreciação
  preexistente do PHPUnit em `TwoFactorTest::testHotpBateComVetoresRfc4226()`; o teste focado prova
  que ela não foi introduzida pelo lote.
- `git diff --check`: **OK**.

## Pendência

- Homologar a redefinição de senha em uma instalação dedicada real sem
  `usuarios_gestores_hosts`, confirmando atualização da senha, histórico sem host e envio do e-mail.
