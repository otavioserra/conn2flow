# Sistema de Recursos e Arquitetura Tailwind CSS v4

## 1. Taxonomia de 11 Recursos
- Recursos residem em `resources/<idioma>/<tipo>/<id>/`:
  * `paginas`, `layouts`, `componentes`, `templates`, `modulos`, `plugins`, `widgets`, `emails`, `arquivos`, `scripts`, `estilos`.
- Todo recurso contém seu arquivo de conteúdo (`<id>.html`, `<id>.js`, `<id>.css`) e metadados `<id>.json`.

## 2. Regra Mandatória de Version Bump
- Ao alterar qualquer script JS ou folha de estilos CSS em `resources/`, incremente obrigatoriamente a versão no metadado `<id>.json` (ex: `1.0.0` ➔ `1.0.1`).
- Isso força a invalidação de cache nos navegadores e CDN em produção.

## 3. Fonte da Verdade em Runtime (Banco vs Disco)
- O runtime serve HTML e CSS exclusivamente do **BANCO DE DADOS SQL** (`gestor.php:2782`).
- `resources/` em disco é a semente de autoria. Alterações só chegam ao runtime após `c2f manager:update-all` ou `c2f project:deploy <id>`.

## 4. Reconstrução de CSS Derivado via `c2f css:rebuild`
- É proibido manipular manualmente `paginas.css_compiled = NULL`. Use sempre `c2f css:rebuild` para recalcular os campos derivados (`css_precompiled` e `css_compiled`) a partir do HTML vigente.

## 5. Auditoria de CSS com `c2f css:audit`
- Utilize `c2f css:audit` e `c2f css:audit --url=<rota>` para validar a procedência de estilos, detectar classes Tailwind órfãs e eliminar dívida técnica em PHP/JS.

## REQ-077 — Pipeline, worktrees e preservação (DEC-136)

- Projeto: `./c2f project:deploy <id>` é o padrão (95% dos casos). Core: `./c2f manager:update-all`. `./c2f project:update-all <id>` é proibido na rotina de projeto; permitido exclusivamente em lote que altera Core e projeto simultaneamente.
- Antes de deploy, confira `git branch --show-current` e `git status --short` na origem; não publique trabalho alheio/inacabado. Confira o destino e sua autorização. Categoria B (skills/documentação/testes/memória): somente checks locais, sem deploy. Execução sequencial, foreground, logs e locks continuam obrigatórios.
- Worktrees por conveniência são proibidas. Só criar com concorrência multi-agente comprovada no mesmo repositório; em dúvida, consultar o humano antes. Registre raiz original, caminho temporário e concorrência na requisição e lote. Ao concluir/homologar, audite trabalho único, integração na main e junctions, então `git worktree remove <caminho>` e `git worktree prune`. Nunca use força para destruir trabalho não integrado; preserve e reporte impedimentos.
- DEC-136: manter por padrão; nada é removido (arquivo, `status='D'`, `DELETE`) sem confirmação explícita do operador humano. Retirada vira choque pendente; `--confirmar-retirada` só quando pedido pelo operador. Fonte incompleta não autoriza retirada.
