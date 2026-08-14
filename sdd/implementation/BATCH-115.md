# BATCH-115 — HTML dinâmico e Tailwind por recurso

- **Intake**: [req-115](../human-requests/req-115.md)
- **Status**: in-progress
- **Data**: 2026-08-14

## Resultado funcional desta rodada

- menu de módulos da Editbar extraído do PHP para componentes localizados;
- textos visíveis migrados para variáveis pt-br/en;
- fontes dinâmicas temporárias declaradas e auditadas nos módulos privados ainda não migrados;
- layout Tailwind próprio do iframe criado, com Preflight por recurso;
- cascata ordenada como layout, dependências e recursos;
- `gestor_componente()` alinhada ao contrato de `css_precompiled`, inclusive para múltiplos IDs;
- atualização/deploy preserva `css_precompiled` mesmo quando o conteúdo autoral tem
  `user_modified`;
- geração incremental, logs e opções direcionadas de atualização validados no core e no projeto.

## Evidência

- homologação visual após limpeza de cache: Editbar próxima da produção e dropdowns recuperados;
- PHPUnit: 268 testes, 1.060 asserções, 4 ignorados e 1 depreciação preexistente;
- sincronização `snapphoton-local`: 173 recursos encontrados, 173 em cache e zero erros;
- meta viewport idêntica nas capturas local/produção; diferença tipográfica residual não foi
  mascarada com escala artificial.

## Continuidade incremental

- substituir as pontes `tailwind_sources` por componentes reais em `snapphoton-system`,
  `busca-clinica` e `subscriptions`;
- migrar gradualmente os demais literais HTML relevantes de PHP/JavaScript conforme o inventário
  do intake;
- manter auditoria de novas fontes dinâmicas e cobertura de utilities exclusivas.
