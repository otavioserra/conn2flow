# BATCH-272 — Índice de idioma + caminho em `paginas`

- **Requisição:** [REQ-263](../human-requests/req-263.md)
- **Status:** `implemented-pending-homologation`
- **Entrega:** `main`, `3.0` e `3.1`
- **Data:** 2026-10-08
- **Ambiente de teste:** `https://conn2flow.local/` e `https://v3.1-conn2flow.local/` (MariaDB 11.8, memória do InnoDB de 128 MB)

## Live Todo List

- [x] Achar por que o site ficou lento
- [x] Migração do índice
- [x] Publicação nos dois ambientes de teste e medição de antes e depois
- [ ] Homologação humana e publicação em produção (do Humano)

## Causa

A busca da página pelo caminho, que roda em toda requisição, não tinha índice que cobrisse o caminho. O banco percorria todas as páginas do idioma carregando HTML e CSS de cada uma. O diagnóstico completo está na requisição.

## O que mudou

- `gestor/db/migrations/20261008100000_add_language_caminho_index_to_paginas.php`: cria `idx_paginas_language_caminho` (`language`, `caminho(191)`). Não faz nada se a tabela não existe ou o índice já existe; `down()` remove.
- `tests/Unit/PHP/PaginasIndiceCaminhoReq263Test.php`: confere a migração e que as buscas por caminho em `gestor.php` filtram caminho e idioma.
- `tests/Unit/PHP/HtmlEditorModelosInclusaoReq257Test.php`: o teste recortava uma função do código procurando `"\n}\n"` e falhava quando o arquivo estava com fim de linha do Windows. Passou a normalizar o fim de linha antes. Não é deste assunto; apareceu ao rodar a suíte.

Nenhuma consulta foi alterada.

## Medição

Primeiro byte, em milissegundos, logo depois de uma publicação (o pior caso):

| Página | Antes | Depois |
|---|---|---|
| Home (3.0) | 492 | 61 |
| Loja (3.0) | 414 | 62 |
| Documentação (3.0) | 187 | 75 |
| Home (3.1) | 539 | 59 |
| Loja (3.1) | 548 | 61 |

A primeira visita de cada ambiente logo após a publicação ainda levou 250 a 280 ms; da segunda em diante, os valores da tabela.

| No banco | Antes | Depois |
|---|---|---|
| Linhas examinadas pela busca da página | 1.061 | 1 |
| Leituras de disco por visita | 4.356 | 0 |
| Tempo da consulta | 0,4 a 0,7 s | não aparece mais acima de 20 ms |

O índice foi criado nos dois bancos (`admin_conn2flow` e `u11_c2f`) pelo pipeline.

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit do core | 1.757 testes, 20.565 asserções — OK |
| Plano da consulta no ambiente 3.0 | usa `idx_paginas_language_caminho`, 1 linha |
| Tempo de resposta | tabela acima |

## O que não foi exercitado

- **Servidor de produção.** A medição é do ambiente de teste. Em produção o ganho depende de a tabela `paginas` ser maior que a memória do banco; o índice não tem como piorar a leitura.
- **MySQL 8** (desenvolvimento em Docker): a sintaxe de índice com prefixo é a mesma, mas a migração só rodou em MariaDB.
- **Tempo de criação do índice numa tabela grande em produção**: nos ambientes de teste (220 MB) passou dentro do pipeline sem ser notado.
- **Carga com muitos visitantes ao mesmo tempo.**

## O que continua valendo observar

- **A memória do banco é pequena para o volume**: 128 MB de InnoDB para quase 1 GB de dados nesse servidor de teste. Outras consultas que leem conteúdo grande continuam dependendo dela. Vale conferir o valor em produção.
- **A tabela `paginas` guarda conteúdo pesado** (HTML, CSS compilado, dois idiomas da documentação). Qualquer consulta que percorra muitas linhas pedindo essas colunas repete o problema. A busca por caminho era a que rodava em toda requisição.
- Há outras buscas em `paginas` por `modulo` e `opcao`; têm índice em `modulo`, e não medi cada uma.
