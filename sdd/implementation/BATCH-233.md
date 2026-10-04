# BATCH-233: usuários, perfis, módulos, operações e painel inicial em Tailwind (req-224)

Frente da fatia 6 da [req-219](../human-requests/req-219.md) descrita na [req-224](../human-requests/req-224.md). O Engenheiro Chefe passou a frente ao agente da req-219, que já mantinha os índices e a biblioteca.

**Status**: `in-review`. Validado no Lab; a revisão humana fica para o fim do programa.

## Commits (core)

| Commit | Conteúdo |
|---|---|
| `877cec06` | `usuarios`, `usuarios-perfis`, `modulos`, `modulos-operacoes` e `dashboard` em Tailwind, com o teste de contrato `PainelTailwindReq224Test` |
| `32409385` | `modulos-variaveis` sai desta frente e vai para a req-223, junto com o widget `configuracao_administracao` |
| `883c6d7b` | A biblioteca de controles entra em toda página do `layout-administrativo-tailwind`, mesmo sem formulário (o painel inicial precisava dela) |
| `af03fc48` | Artefatos do pipeline |

## O que mudou

| Onde | Mudança |
|---|---|
| `usuarios`, `modulos`, `modulos-operacoes` | Listar, adicionar, editar e demais opções no layout Tailwind, com bundle e campos `c2fc`. |
| `usuarios-perfis` | Matriz de permissões em checkbox nativo, com os mesmos `name` de antes no POST. Autocomplete da página inicial em variante (`home-page-autocomplete-tailwind`). |
| `dashboard` | Painel comum em Tailwind. Os cartões montados em PHP saem do componente `dashboard-cards-tailwind`, com CSS próprio. As páginas `layout-administrativo-do-gestor-3d` e `layout-iframe-tailwindcss` ficam fora, como pedia o escopo. |

## Validação

- PHPUnit no Lab com o contrato `PainelTailwindReq224Test`: variantes x originais, metadados e HTML sem `class="ui `. A suíte não tem regressão nova; a única falha é a anterior, de CRLF (`ProjectSshDeployReq034Test`).
- Navegador no Lab, roteiro `conn2flow-site/sdd/validation/core/req219-controles-e2e.cjs`: **40/40**. Cobre as telas desta frente, a 390 px, sem diálogo nativo e com a biblioteca no painel inicial.

## Pendências

- Roteiro humano no item do roteiro único da req-219, em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`.
- A conferência visual final de todos os módulos Tailwind fica na req-225.
