---
title: "Como escrever e publicar a documentação"
description: "O contrato das docs do Conn2Flow: onde ficam, o frontmatter obrigatório, a rotina de auditoria contra o código e a publicação no site."
section: guides
order: 90
sources:
  - cli/src/Support/Docs
  - cli/src/Commands/DocsAuditCommand.php
  - cli/src/Commands/DocsExtractCommand.php
  - cli/src/Commands/DocsBuildCommand.php
verified_at: a2cf13cd
---

# Como escrever e publicar a documentação

A documentação do Conn2Flow tem **uma fonte só**: os arquivos Markdown de `ai-workspace/<idioma>/docs/` no repositório do Core. O site público (`/docs/`) é gerado a partir deles; nada é escrito direto no painel.

A regra de ouro é que **o código é a autoridade**. Uma doc só vale se foi conferida contra o código, e o commit dessa conferência fica registrado nela.

## Onde cada doc mora

```
ai-workspace/<idioma>/docs/
├── index.md          página inicial das docs
├── guides/           passo a passo orientado a tarefas
├── concepts/         como o sistema funciona e por quê
├── reference/
│   ├── libraries/    uma doc por gestor/bibliotecas/<nome>.php
│   ├── modules/      uma doc por gestor/modulos/<id>/
│   ├── api/  cli/  hooks/
└── whats-new/        novidades por versão
```

- **Pastas e nomes de arquivo são iguais nos dois idiomas.** `pt-br/docs/guides/x.md` tem o par `en/docs/guides/x.md`. É o que liga as traduções e define a URL (`/docs/guides/x/`).
- Escreva as duas versões **na mesma passagem**, com o mesmo contexto na cabeça. Não existe "traduzir depois".
- Os arquivos em MAIÚSCULAS na raiz de `docs/` são **legado**. Ao migrar um assunto, a doc nova nasce na árvore acima e o legado correspondente é removido.

## Frontmatter

```yaml
---
title: "Biblioteca modelo.php"
description: "Uma frase: aparece nos índices, no compartilhamento e no llms.txt."
section: reference          # guides | concepts | reference | whats-new (igual à pasta)
order: 20                   # posição no menu (padrão 100)
visibility: public          # public (padrão) | restricted (reservado)
module: menus               # opcional: id do módulo documentado
sources:                    # obrigatório em reference/: o código que a doc descreve
  - gestor/bibliotecas/modelo.php
verified_at: 5b4348ab       # commit do Core contra o qual a doc foi conferida
---
```

`sources` e `verified_at` são o que torna a defasagem mensurável: se alguma fonte mudar depois do `verified_at`, a doc sobe no ranking do `docs:audit`.

## Como escrever uma doc

1. **Leia o código antes do texto antigo.** Para uma biblioteca, leia o arquivo inteiro, os chamadores (`grep -rn "nome_da_funcao(" gestor`) e os testes. Para um módulo, leia `<id>.php`, `<id>.json`, o JS, o widget (`<id>.widget.php`), os `resources/` (pages, templates, `ai_modes`), as migrations da tabela e os hooks.
2. **Descreva o comportamento real**, inclusive o surpreendente: caixa de letras, só a primeira ocorrência, valores padrão, o que acontece quando algo falta. Se o código contradiz o próprio docblock, a doc segue o código e aponta a divergência.
3. **Marque o legado.** Função sem chamadores, função que chama algo inexistente e opção morta são informação útil, não constrangimento.
4. **Mostre um exemplo** tirado de um uso real do core, quando existir.
5. Preencha `sources` e `verified_at` com o commit atual (`git rev-parse --short HEAD`).

Blocos de destaque disponíveis (viram caixas coloridas no site):

```markdown
> [!NOTE]
> Informação complementar.

> [!TIP]
> Atalho ou boa prática.

> [!IMPORTANT]
> Algo que muda o resultado.

> [!WARNING]
> Risco de erro.

> [!CAUTION]
> Risco de segurança ou perda de dados.
```

Links entre docs são relativos e apontam para o `.md` (`../modules/menus.md`). O build os converte em URLs do site e **falha** se o destino não existir.

## Referência gerada a partir do código

As docs de `reference/libraries/` terminam com um bloco gerado:

```markdown
<!-- c2f:extract:start -->
…lista de funções com assinatura e linha…
<!-- c2f:extract:end -->
```

- `php cli/c2f.php docs:extract modelo` regenera o bloco da `modelo.php` nos dois idiomas.
- `php cli/c2f.php docs:extract --all --check` falha se algum bloco estiver desatualizado.
- `php cli/c2f.php docs:extract 2fa --create` cria o esqueleto da doc de uma biblioteca ainda não documentada.

Não edite dentro do bloco. O texto fora dele é seu, e o audit avisa quando alguma função do código não é mencionada no texto.

## A rotina: `docs:audit`

```bash
php cli/c2f.php docs:audit            # ranking (30 primeiros)
php cli/c2f.php docs:audit --limit=0  # tudo
php cli/c2f.php docs:audit --json     # para ferramentas
php cli/c2f.php docs:audit --strict   # exit 1 se houver erro (CI)
```

O ranking soma 10 pontos por erro e 3 por aviso. O comando verifica:
- frontmatter;
- par de idioma;
- fontes inexistentes;
- fontes alteradas depois do `verified_at`;
- links quebrados;
- bloco extraído desatualizado;
- funções sem explicação;
- bibliotecas e módulos ainda sem doc.

Em cada passagem de agente pela documentação:

1. Rode `docs:audit` e pegue os itens do topo.
2. Para cada item, faça a leitura profunda do código e reescreva as duas línguas.
3. Rode `docs:extract --all` e `docs:audit` de novo até o item sair do ranking.
4. Publique localmente com `docs:build` (abaixo) e confira no navegador.

## Publicação no site: `docs:build`

```bash
php cli/c2f.php docs:build --project=conn2flow-site-local --dry-run
php cli/c2f.php docs:build --project=conn2flow-site-local
```

- **Configuração:** o comando lê `docs.config.json` na pasta `gestor/` do projeto. Ele define idiomas, caminho base, layout, qual publisher recebe cada seção e o id do menu lateral.
- **O que é gerado:** o Markdown vira HTML com classes Tailwind e é gravado como **recursos do sistema** do projeto:
  - publicações (`publisher_pages`);
  - páginas com `publisher_id`;
  - o menu da barra lateral;
  - o `llms.txt`.
- **Publicação:** a partir daí o caminho é o de qualquer recurso. Rode `project:update-all` para sincronizar e reconstruir o CSS no ambiente **local**. O deploy de produção é feito pelo operador.

> [!IMPORTANT]
> O build sobrescreve as páginas de documentação a cada execução. Correções feitas pelo painel se perdem; corrija sempre o Markdown.

## Veja também

- [Biblioteca modelo.php](../reference/libraries/modelo.md): exemplo de doc de referência de biblioteca.
- [Módulo menus](../reference/modules/menus.md): exemplo de doc de módulo.
