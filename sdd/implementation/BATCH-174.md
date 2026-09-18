# BATCH-174: Controle de acessos — separar erro de validação de abuso e informar o prazo de liberação

Execução da [req-169](../human-requests/req-169.md).

---

## Atividades e Checklist

### 1. [x] Origem da falha no controle de formulários
* `formulario_acesso_falha()` recebe `origem` (`validacao` | `abuso`, padrão `abuso` para compatibilidade).
* Falha de validação conta contra `FORMULARIOS_MAXIMO_VALIDACOES`; abuso mantém `FORMULARIOS_MAXIMO_CADASTROS`.
* Os ramos de validação de campo passam a declarar `origem = 'validacao'`; reCAPTCHA continua como abuso.

### 2. [x] Prazo de liberação nas telas de bloqueio
* `autenticacao_acesso_verificar()` devolve `tempo_bloqueio`.
* Componente de login exibe data e hora de liberação por marcador substituível.

### 3. [x] Valores padrão de instalação
* `.env` de template com os novos valores da req-169 §2.3.

### 4. [x] Validação
* `php -l` nos arquivos alterados e suíte do núcleo sem regressão.

---

## Valores a aplicar nos `.env` de produção (operador)

```
ACESSOS_TEMPO_BLOQUEIO_IP=900
ACESSOS_MAXIMO_CADASTROS_SIGNUP=6
FORMULARIOS_TEMPO_BLOQUEIO_IP=3600
FORMULARIOS_MAXIMO_VALIDACOES=40
```

Sites: `conn2flow.com`, `transformamp.com` e `snapphoton.com`.

---

## Critérios de Aceite e Validação

1. Erro de validação não consome a cota de envios válidos.
2. reCAPTCHA reprovado continua contando como abuso.
3. Telas de bloqueio informam data e hora de liberação.
4. Suíte do núcleo sem regressão.

---

## Execução (2026-09-18)

- **`formulario_acesso_falha()` ganhou `origem`.** Os 12 ramos de validação de campo (obrigatório vazio, tamanho mínimo e máximo, e-mail inválido, URL inválida) passaram a declarar `origem = 'validacao'` e contam contra `formularios-maximo-validacoes` (padrão 40). Os dois ramos de reCAPTCHA continuam como abuso, no teto original. O padrão do parâmetro é `abuso`, então qualquer chamador fora do núcleo mantém o comportamento anterior.
- **`autenticacao_acesso_verificar()` devolve `tempo_bloqueio`**, e as quatro telas de bloqueio do `perfil-usuario` substituem `#bloqueio_liberacao#` pela data e hora de liberação. Os componentes de login (pt-br e en) e de recuperação de senha passaram a dizer "Você poderá tentar novamente a partir de …", em vez de "tente mais tarde".
- **`.env` de template** atualizado: `ACESSOS_TEMPO_BLOQUEIO_IP=900`, `ACESSOS_MAXIMO_CADASTROS_SIGNUP=6`, `FORMULARIOS_TEMPO_BLOQUEIO_IP=3600` e o novo `FORMULARIOS_MAXIMO_VALIDACOES=40`. Tetos de tentativa de login mantidos.

### Validação

- `php -l` limpo em `config.php`, `bibliotecas/formulario.php`, `bibliotecas/autenticacao.php` e `modulos/perfil-usuario/perfil-usuario.php`.
- PHPUnit no ambiente `lab` (WSL Ubuntu com PHP 8.5 do HestiaCP, após instalar `php8.5-sqlite3`): **1181 testes, 7828 asserções, 0 erros e 0 falhas** — apenas 4 deprecations e 4 skips preexistentes.
- No PHP do Windows a mesma suíte fecha com 1 erro em `CoreHelpersTest::testCriptografiaBasicaComChavesRsa`, por ausência de `openssl.cnf` naquele host; no `lab` o mesmo `openssl_pkey_new()` retorna chave válida. É limitação do ambiente do executor, não da mudança.

### Aplicação em produção (2026-09-18, autorizada pelo operador)

Os quatro valores foram aplicados nos `.env` ativos dos três sites, com backup `*.bak-req169-20260918-071703` ao lado de cada arquivo e dono/permissão (`600`) preservados:

| Site | Arquivo |
|---|---|
| conn2flow.com | `/home/admin/web/conn2flow.com/conn2flow-gestor/autenticacoes/conn2flow.com/.env` |
| transformamp.com | `/home/transformamp/web/transformamp.com/conn2flow-gestor/autenticacoes/transformamp.com/.env` |
| snapphoton.com | `/home/snapphoton/web/snapphoton.com/conn2flow-gestor/autenticacoes/snapphoton.com/.env` |

Os `.env` de `temp.conn2flow.com` e `dev.snapphoton.com` não foram tocados. Após a alteração, os três sites responderam HTTP 200.

`FORMULARIOS_MAXIMO_VALIDACOES` fica inerte até o deploy do núcleo, porque é `config.php` que passa a lê-la; os outros três valores já valem imediatamente.

---

## Correção do prazo de liberação (18/09/2026, achado no smoke test)

O critério 3 não estava cumprido. O smoke test do operador mostrou a tela de bloqueio ainda dizendo
"Tente novamente mais tarde", e a investigação achou dois problemas:

1. **O marcador estava na origem errada.** O texto foi alterado no componente `acessar-sistema`, mas
   a tela do SnapPhoton — como qualquer projeto com login próprio — renderiza a variável
   `login-blocked-message`, que seguia com o texto antigo. As variáveis `login-blocked-message` e
   `forgot-password-blocked-message` (pt-br e en) passaram a carregar `#bloqueio_liberacao#`.
2. **A ordem da substituição impedia a troca.** `gestor_pagina_variaveis()` roda no fim do pipeline,
   depois do controlador do módulo. Trocar o marcador enquanto o módulo monta a página não alcança
   texto que só será injetado adiante — o marcador chegaria literal ao navegador.

Para resolver o segundo ponto, `gestor_pagina_ultimas_operacoes()` passou a aplicar
`$_GESTOR['pagina-marcadores-finais']`, um mapa que qualquer módulo preenche para marcadores que
dependem de conteúdo injetado tardiamente. É a última etapa antes da higienização do HTML. O
`perfil-usuario` registra ali o instante de liberação nas quatro telas de bloqueio, em vez de trocar
na hora. Assim o mesmo marcador vale para o componente do núcleo e para a variável.

Verificado no banco do `snapphoton.local` após `c2f project:update-all`: `login-blocked-message`
[pt-br] já contém `#bloqueio_liberacao#`, com `user_modified=0`.

### Nota sobre a suíte do núcleo

`ForcarAtualizacaoTest` **não é idempotente**: passa numa execução limpa e falha na seguinte. A causa
é o cache de resultado do PHPUnit, que reordena os testes colocando os que falharam antes — e a
classe depende da ordem de declaração por causa do cache estático de `schemaMetadata()`. Com
`--do-not-cache-result`, ou apagando `.phpunit.result.cache`, passa sempre. É defeito de isolamento
preexistente, não relacionado à req-169, mas vale uma correção própria: ele transforma qualquer
execução após uma falha em duas falhas fantasma.

---

## Pendências do operador

- Deploy do núcleo nos três sites, para que a separação entre validação e abuso e o prazo nas telas entrem em vigor.
- Projetos que tenham personalizado o texto de bloqueio (`user_modified=1` na variável) não recebem a atualização pelo deploy e precisam incluir `#bloqueio_liberacao#` à mão. Quem usa o texto padrão já recebe o prazo.
- Avaliar a reativação do reCAPTCHA: com o degrau do meio funcionando, o bloqueio pode ficar ainda mais curto.
