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

- `busca-clinica` concluída: sete estados dinâmicos migrados para um componente de templates
  localizado, preenchido por DOM seguro no JavaScript;
- `subscriptions` concluída: cards gratuito, sob medida e pago migrados para componentes próprios,
  selecionados e preenchidos pelo controlador;
- o projeto passou de seis para dois recursos com `tailwind_sources`; restam apenas as versões
  pt-br/en do bridge de `snapphoton-system`;
- extrair `snapphoton-system` por famílias de tela (busca/síntese, documentos/editor,
  categorias/cache/arquivos) antes de remover sua cobertura transitória;
- migrar gradualmente os demais literais HTML relevantes de PHP/JavaScript conforme o inventário
  do intake;
- manter auditoria de novas fontes dinâmicas e cobertura de utilities exclusivas.

## Evidência da rodada 2

- gerador privado: 66 recursos encontrados e recompilados, zero erros, dois recursos com duas
  fontes adicionais;
- `node --test tests/js/busca-clinica-runtime-fragments.test.cjs
  tests/js/subscriptions-checkout-components.test.cjs` → 4/4;
- `node --check` da busca clínica, `php -l` da busca clínica e de assinaturas e
  `git diff --check` → OK;
- escala global mantida no padrão de 16px; o desvio visual era zoom de 110% do navegador.
- deploy local via API → HTTP 200; sincronização forçada e limitada a `componentes` → 43 sem
  alteração; leitura da base `photon` confirmou oito recursos novos com precompiled preenchido e
  `user_modified=0`.
