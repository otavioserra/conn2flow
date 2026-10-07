# REQ-256 — põe as miniaturas geradas no repositório e declara `thumbnail` em cada modelo, no recurso que o define.
# Uso: python integrar.py <pasta webp> <relatorio.json> <raiz do repositório> core|site
import glob, io, json, os, re, shutil, sys

webp, relatorio, raiz, dono = sys.argv[1:5]
itens = [r for r in json.load(open(relatorio, encoding='utf-8')) if (r['project'] is None) == (dono == 'core')]
quero = {(r['id'], r['language']) for r in itens}
print(len(itens), 'miniaturas para', dono)

# 1) arquivos
for r in itens:
    destino = os.path.join(raiz, 'gestor', 'assets', 'templates', 'images', r['language'], r['id'] + '.webp')
    os.makedirs(os.path.dirname(destino), exist_ok=True)
    shutil.copyfile(os.path.join(webp, r['language'], r['id'] + '.webp'), destino)

# 2) metadados: insere a linha de `thumbnail` logo depois da linha do `id` do modelo, no mesmo recuo
feitos = set()
arquivos = glob.glob(os.path.join(raiz, 'gestor', 'modulos', '*', '*.json')) + glob.glob(os.path.join(raiz, 'gestor', 'resources', '*', 'templates.json'))
for arquivo in arquivos:
    texto = io.open(arquivo, encoding='utf-8', newline='').read()
    if '"target"' not in texto:
        continue
    try:
        dados = json.loads(texto)
    except ValueError:
        continue
    # (id, idioma) dos modelos deste arquivo, na ordem em que aparecem
    if isinstance(dados, list):
        lingua = os.path.basename(os.path.dirname(arquivo))
        modelos = [(m.get('id'), lingua, m) for m in dados if isinstance(m, dict) and 'target' in m]
    else:
        modelos = [(m.get('id'), lingua, m) for lingua, rec in (dados.get('resources') or {}).items() if isinstance(rec, dict) for m in rec.get('templates', [])]
    alvo = [(i, l) for i, l, m in modelos if (i, l) in quero and not m.get('thumbnail')]
    if not alvo:
        continue
    nl = '\r\n' if '\r\n' in texto else '\n'
    barra = '\\/' if '\\/' in texto else '/'
    linhas = texto.split(nl)
    # ocorrências de cada id entre os modelos, na ordem do arquivo: a n-ésima ocorrência é o n-ésimo idioma
    ordem = {}
    for i, l, m in modelos:
        ordem.setdefault(i, []).append(l)
    visto = {}
    saida = []
    for n, linha in enumerate(linhas):
        saida.append(linha)
        achou = re.match(r'^(\s*)"id":\s*"([^"]+)",\s*$', linha)
        if not achou or achou.group(2) not in ordem:
            continue
        # só conta quando o objeto é um modelo: tem `target` nas linhas vizinhas
        vizinhas = ''.join(linhas[max(0, n - 4):n + 8])
        if '"target"' not in vizinhas:
            continue
        k = visto.get(achou.group(2), 0)
        visto[achou.group(2)] = k + 1
        if k >= len(ordem[achou.group(2)]):
            continue
        lingua = ordem[achou.group(2)][k]
        if (achou.group(2), lingua) in alvo:
            saida.append('%s"thumbnail": "templates%simages%s%s%s%s.webp",' % (achou.group(1), barra, barra, lingua, barra, achou.group(2)))
            feitos.add((achou.group(2), lingua))
    io.open(arquivo, 'w', encoding='utf-8', newline='').write(nl.join(saida))
    json.loads(nl.join(saida))
    print(' ', os.path.relpath(arquivo, raiz), len([1 for a in alvo if a in feitos]))
faltou = sorted(quero - feitos)
print('declarados', len(feitos), '; sem recurso encontrado:', faltou)
