// REQ-240 — resumo por pilar do resultado de req240-browser.cjs.
// Uso: node sdd/validation/req240-browser-summary.cjs [--detalhe]
const fs = require('node:fs'), path = require('node:path');
const dados = JSON.parse(fs.readFileSync(path.join(__dirname, 'req240-browser-results.json'), 'utf8'));
const detalhe = process.argv.includes('--detalhe');
const telas = dados.resultados;
const BRANCO = ['rgb(255,255,255)'];
const CABECALHO_OK = ['rgb(248,250,252)', 'oklch(0.9840.003247.858)'];

const grupos = {};
const marcar = (pilar, tela, info) => { (grupos[pilar] = grupos[pilar] || []).push({tela: tela.url, modulo: tela.modulo, info}); };

let ok200 = 0, redirecionadas = 0;
for (const t of telas) {
  if (t.falha) { marcar('00 falha de navegação', t, t.falha); continue; }
  if (t.status === 200 && !t.final) ok200++;
  if (t.final) { redirecionadas++; continue; }
  if (t.status !== 200) marcar('00 status diferente de 200', t, t.status);
  if (t.errosJs && t.errosJs.length) marcar('00 erro de JavaScript', t, t.errosJs[0]);
  const d = t.desktop || {}, m = t.mobile || {};
  if (d.circulos && d.circulos.length) marcar('P1 círculo de fallback', t, d.circulos.length + 'x ' + d.circulos[0]);
  if (d.iconesNaoDesenhados && d.iconesNaoDesenhados.length) marcar('P1 ícone não desenhado', t, [...new Set(d.iconesNaoDesenhados)].join(', ').slice(0, 110));
  if (d.botoesSemDica && d.botoesSemDica.length) marcar('P1 botão só-ícone sem dica', t, d.botoesSemDica.length + 'x ' + d.botoesSemDica[0]);
  for (const a of d.abas || []) {
    if (!a.canonica) marcar('P2 barra fora da folha c2fc-abas-lista', t, a.classe);
    else if (a.gap !== null && a.gap > 1) marcar('P2 abas com gap até o painel', t, a.gap + 'px ' + a.classe);
    if (!a.ativa) marcar('P2 barra sem aba ativa', t, a.classe);
  }
  if (d.cartao && d.cartao !== 'sem-alvo' && !BRANCO.includes(d.cartao)) marcar('P3 bloco principal sem cartão branco', t, d.cartao);
  if (d.classeSemRegra && d.classeSemRegra.length) marcar('P3 classe de fundo sem regra CSS', t, d.classeSemRegra.join(' | '));
  if (d.selectsForaDoPadrao && d.selectsForaDoPadrao.length) marcar('P4 select fora do padrão', t, d.selectsForaDoPadrao.length + 'x ' + d.selectsForaDoPadrao[0]);
  if (d.mensagensLegadas && d.mensagensLegadas.length) marcar('P4 mensagem fora do c2fc-alerta', t, d.mensagensLegadas[0]);
  if (d.listasComMarcador) marcar('P4 lista com marcador em mensagem', t, d.listasComMarcador);
  for (const tb of d.tabelas || []) {
    const fundos = [tb.cabecalho, tb.cabecalhoLinha, tb.cabecalhoThead];
    if (tb.cabecalho !== null && !fundos.some(f => CABECALHO_OK.includes(f))) marcar('P5 cabeçalho de tabela sem bg-slate-50', t, tb.id + ' ' + fundos.join('/'));
  }
  if (d.overflow > 1) marcar('P6 overflow horizontal a 1366px', t, d.overflow + 'px');
  if (m.overflow > 1) marcar('P6 overflow horizontal a 390px', t, m.overflow + 'px');
  for (const tb of m.tabelas || []) if (tb.vaza && !tb.rola) marcar('P6 tabela vaza a 390px sem rolagem própria', t, tb.id);
  if (d.entradaOculta || m.entradaOculta) marcar('P6 input de arquivo maior que a tela', t, '');
  if (d.marcadores && d.marcadores.length) marcar('00 marcador cru no texto', t, d.marcadores.join(' '));
}

console.log('telas: ' + telas.length + ' | 200 direto: ' + ok200 + ' | redirecionadas (sem registro/id): ' + redirecionadas);
for (const pilar of Object.keys(grupos).sort()) {
  const itens = grupos[pilar];
  const porModulo = {};
  for (const i of itens) porModulo[i.modulo] = (porModulo[i.modulo] || 0) + 1;
  console.log('\n## ' + pilar + ' — ' + itens.length + ' ocorrência(s) em ' + new Set(itens.map(i => i.tela)).size + ' tela(s)');
  console.log('   ' + Object.entries(porModulo).sort((a, b) => b[1] - a[1]).map(([k, v]) => k + '=' + v).join(', '));
  const amostra = detalhe ? itens : itens.slice(0, 4);
  for (const i of amostra) console.log('   - ' + i.tela + ' :: ' + String(i.info).slice(0, 150));
}
if (!Object.keys(grupos).length) console.log('\nNenhuma ocorrência.');
