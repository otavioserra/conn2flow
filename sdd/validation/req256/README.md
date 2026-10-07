# Miniaturas dos modelos (REQ-256)

Gera a miniatura de cada modelo do cadastro de modelos que ainda não tem: renderiza o modelo com dados de exemplo no quadro de prévia do editor de modelos, fotografa e grava em WebP.

## Quando usar

Sempre que um modelo novo entrar em `resources/<idioma>/templates/` (do core, de um módulo ou de um projeto). O teste `TemplatesMiniaturasReq256Test` falha enquanto um modelo listado no painel estiver sem miniatura.

## Passos

Precisa de um painel no ar com os modelos já sincronizados (ambiente de teste), de uma sessão de administrador e do Playwright.

1. **Exportar os modelos sem miniatura** do banco do ambiente de teste, uma linha JSON por modelo:

   ```sql
   SELECT JSON_OBJECT('id',id,'language',language,'target',target,'framework',framework_css,'nome',nome,
          'html',html,'css',css,'project',project,'plugin',plugin)
   FROM templates WHERE status='A' AND (thumbnail IS NULL OR thumbnail='') ORDER BY target,id,language;
   ```

   Rodar com `mariadb -N -r` e guardar a saída num arquivo `.jsonl`.

2. **Fotografar** (1024 × 696 px por modelo):

   ```bash
   C2F_BASE=https://<painel> C2F_PLAYWRIGHT=<pasta do playwright> C2F_COOKIES=<cookies do administrador> \
   MODELOS=modelos.jsonl SAIDA=fotos node gerar-miniaturas.cjs
   ```

   `SO=id1,id2` (ou um alvo) refaz só alguns. No Git Bash, prefixe com `MSYS_NO_PATHCONV=1`.

3. **Converter e conferir**: `python montar.py fotos webp folhas` grava as miniaturas (580 × 394, WebP, qualidade 82) e uma folha de conferência por alvo e idioma. **Abra as folhas.** O gerador não sabe se a imagem ficou boa.

4. **Integrar**: `python integrar.py webp fotos/relatorio.json <raiz do repositório> core|site` copia para `gestor/assets/templates/images/<idioma>/` e declara `thumbnail` no recurso de cada modelo. Depois, `resources:sync` e o pipeline.

## Como a amostra é montada

- O bloco `<!-- item < --> … <!-- item > -->` é repetido (quantidade por alvo, em `ITENS`); pontos, recursos, categorias e opções também.
- Blocos de estado vazio, erro ou desligado (`no-item`, `results-box`, `link-disabled-css`…) saem.
- Marcadores `[[x]]` e `@[[x]]@` recebem valor pelo nome: título, resumo, data, preço, endereço, imagem (um desenho em gradiente), rótulo de menu, campo de formulário.
- Em formulários, cada campo fica com um tipo só (texto, texto, seleção, área de texto).
- Conteúdo baixo (menu, busca, aviso) é ampliado até 1,5 vez e centrado.
- Modelos feitos para fundo escuro (lista em `gerar-miniaturas.cjs`) ganham palco escuro.
- Scripts do modelo não rodam: o que depende de script (carrossel, aviso de cookies) aparece no estado inicial, e o aviso de cookies é mostrado à força.

## Limites conhecidos

- O valor de exemplo vem do nome do marcador. Marcador com nome fora do comum sai vazio; alguns botões recebem um título no lugar do rótulo.
- Modelo novo para fundo escuro, ou com bloco de nome novo, pode pedir um ajuste nas listas do gerador.
- O alvo `galleries-estados` não aparece na lista de modelos e não recebe miniatura.

## Recurso que já tinha `thumbnail` vazio

`integrar.py` insere a linha de `thumbnail` depois do `id`. Se o recurso já trazia `"thumbnail": ""`, rode `python vazias.py <raiz do repositório>` em seguida: ele tira a linha vazia que sobrou no mesmo objeto (com as duas, a vazia vence).
