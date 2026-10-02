# BATCH-215: docs — referência de funções em cartões (req-207)

Execução da [req-207](../human-requests/req-207.md).

**Status**: `in-review` (validado no Lab com o `conn2flow-site-local`; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `cli/src/Support/Docs/FunctionReferenceHtml.php` (novo) | Lê o bloco do `docs:extract` de volta para dados e gera o HTML: filtro, índice em fichas e um cartão por função |
| `cli/src/Support/Docs/DocsBuilder.php` | O bloco de funções não passa mais pelo Markdown; o HTML dos cartões entra no lugar dele |
| `ai-workspace/{pt-br,en}/docs/reference/libraries/{gestor,html-editor,stripe}.md` | Blocos regerados |
| `ai-workspace/{pt-br,en}/docs/concepts/system-updates.md` | O que a retirada marcou volta quando o dono entrega de novo |
| `ai-workspace/{pt-br,en}/docs/concepts/resources.md` | `insert_only` nas duas estratégias; sementes de sistema num projeto; escrita que falha interrompe |
| `ai-workspace/{pt-br,en}/docs/guides/deploy-a-project.md` | Seção "Confira que o conteúdo chegou", com os avisos de deploy concorrente e de fonte incompleta |
| `README.md`, `README-PT-BR.md` | Conn2Flow Dev Tools no Ecossistema; instalação localiza o último instalador |

## O cartão de função

- Nome com âncora própria (`#fn-<nome>`), para apontar uma função de fora.
- "ver no código · linha N", para o arquivo no repositório.
- Assinatura num bloco de código, com o mesmo botão de copiar dos demais blocos.
- Descrição, parâmetros (nome, tipo, descrição) e retorno (tipo, descrição).

O tipo de cada parâmetro sai da assinatura. Vírgula dentro de um valor padrão (`array('a', 'b')`) não separa parâmetros.

O filtro é comportamento do modelo da página (`data-docs-fn-*`), que mora no projeto. Sem JavaScript, o índice e os cartões funcionam.

## Limite

O bloco é lido de volta do Markdown gerado, em vez de receber os dados direto do extrator. Foi a mudança menor: o `docs:build` lê as docs, não as bibliotecas. O formato do bloco é contrato entre as duas classes, coberto por teste.

## Validação

- `DocsFunctionReferenceReq207Test`: 3 testes (dados, HTML com escape, inglês e bloco vazio). Testes das docs: 32, todos passam.
- `docs:extract --all --check` e `docs:audit`: 0 erros; 125 itens com aviso de "fontes mudaram desde a verificação", dívida anterior a este lote.
- `docs:build --project=conn2flow-site-local` e `project:update-all`: saída 0.
- Navegador no Lab, página da biblioteca `banco`, nos dois idiomas, a 1280 e 390 px: 47 cartões e 47 fichas; filtro "select" deixa 5; filtro sem resultado avisa; ficha leva ao cartão; sem rolagem horizontal; sem erro de console (14/14).

## Pendências

- Revisão humana.
- `docs:audit` aponta 125 documentos cuja fonte mudou depois da última verificação: revisão documento a documento, não feita neste lote.
