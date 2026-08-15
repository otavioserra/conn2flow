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

## Correção de cascata — bundle canônico por página

- **Causa-raiz**: concatenar saídas Tailwind independentes perde a ordem global dos utilitários.
  Na Busca Clínica, `.hidden` de um sidecar posterior anulava `lg:flex` do layout e invertia o menu
  desktop/mobile.
- **Contrato novo e opt-in**: páginas que definem `$_GESTOR['tailwind-page-bundle']` emitem
  `page-precompiled`; nesse modo o runtime exclui apenas os demais sidecars Tailwind isolados e
  mantém CSS autoral e o CSS do editor.
- **Primeira rota migrada**: `busca-clinica-dashboard` compila página + `photon-admin` + fragmentos
  dinâmicos + templates clínicos default/variation em um único sidecar de 22.427 bytes.
- **Template corrigido**: a resolução SQL e o fallback físico passaram a devolver
  `css_precompiled`; o recurso entra como dependência quando a rota não usa bundle.
- **Evidência estática**: `hidden` precede `lg:flex`; `max-h-56` e `lg:grid-cols-3` estão presentes;
  lint PHP e `git diff --check` aprovados.
- **Evidência de entrega**: sincronização do core fonte, deploy privado HTTP 200 e hashes idênticos
  no destino para `gestor.php`, `bibliotecas/gestor.php`, controlador da busca e sidecar da página.
- **Regressão**: Core com 269 testes/1.064 asserções; projeto com 5/5 testes Node; quatro testes do
  Core ignorados e uma depreciação já conhecida.
- **Próximo passo**: aplicar o mesmo contrato, com dependências declaradas por rota, antes de
  remover o bridge final de `snapphoton-system`.
