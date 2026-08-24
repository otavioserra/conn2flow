# BATCH-130: Blindagem de banco_num_rows, Proteção na Consulta de Hosts em perfil-usuario e Eliminação de Warnings de Chaves Indefinidas no Core

Este lote de implementação descreve as tarefas técnicas para mitigar os erros de execução reportados nos logs de produção do núcleo `conn2flow`, blindando o driver de banco de dados contra `TypeError` no PHP 8.1+, tornando defensiva a busca por hosts no perfil de usuário e sanando warnings de índices não definidos.

---

## Atividades e Checklist

### 1. [ ] Blindagem em `banco.php`
* Em `banco_num_rows($result)`:
  - Verificar se `$result` é falso, nulo ou se não é uma instância de `mysqli_result` (ou `BancoResultadoRemoto`). Retornar `0` nesses casos.
* Em `banco_select()`:
  - Substituir `if(banco_num_rows($res))` por `if($res && banco_num_rows($res))`.

### 2. [ ] Proteção de Consulta em `perfil-usuario.php`
* Na função `perfil_usuario_redefine_password()`:
  - Proteger a consulta de `usuarios_gestores_hosts` com verificação de tabela existente ou guard multi-host, tratando `$id_hosts = null` caso a tabela não exista.

### 3. [ ] Eliminação de Warnings em `admin-paginas.php` e `gestor.php`
* Em `admin-paginas.php`:
  - Linhas 111 e 114: substituir `if($_REQUEST[$post_nome])` por `if(!empty($_REQUEST[$post_nome]))`.
* Em `gestor.php`:
  - Linha 2871: garantir `$layout_css_compiled = $layouts['css_compiled'] ?? '';`.

---

## Critérios de Aceite e Validação

1. **Sintaxe:** `php -l` verde em todos os arquivos alterados.
2. **Suíte PHPUnit:** Testes unitários do núcleo executando sem regressões.
3. **Resiliência:** `banco_num_rows(false)` e `banco_select` com query inválida não disparam fatal error.
