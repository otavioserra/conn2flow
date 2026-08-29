# Governança SDD e Regras Invioláveis de Operação

## 1. Fonte da Verdade Normativa
- Os arquivos numerados em `sdd/` (`SPEC.md`, `0X-*.md`) e os registros de decisão em `sdd/decisions/` são a fonte única e soberana da verdade.
- `sdd/human-requests/` é a área de intake. Antes de alterar qualquer arquivo, consulte `sdd/human-requests/CURRENT.md` para validar a requisição e lote autorizados.

## 2. Proibição Absoluta de `git add -A` e `git add .`
- Commits devem SEMPRE listar caminhos específicos explicitamente (`git add <caminho1> <caminho2>`).
- Nunca inclua arquivos acidentais, caches, `.env` ou alterações de outras branches.

## 3. Proibição Estrita de Cópia Manual para Ambientes de Teste
- NUNCA copie arquivos manualmente (`cp`, `copy`, `Copy-Item`) para pastas de teste ou espelhos (`dev-environment/data/sites/`).
- Use sempre o pipeline oficial: `./c2f manager:update-all` (sistema) ou `./c2f project:update-all <id>` (projetos).

## 4. Execução Sequencial Exclusiva
- Comandos de compilação em lote (`manager:update-all`, `project:update-all`, `css:rebuild`, `resources:sync`) devem executar um por vez em primeiro plano com logs desbufferizados, sem supressão de warnings PHP.

## 5. Eliminação do Estado Híbrido Pós-Deploy
- Todo deploy ou atualização de projeto/sistema DEVE finalizar com `c2f css:rebuild` para assegurar sincronia entre autoria e classes derivadas do Tailwind v4.

## 6. Gestão de Memórias de Engenharia
- Respeite os tetos canônicos de memória: 35 KB (alerta), 50 KB (teto de poda mandatório) e ~15 KB (alvo pós-poda).
- Nunca modifique `MEMORIA-ENGENHARIA-CHEFIA.md` sem autorização expressa do usuário humano.
