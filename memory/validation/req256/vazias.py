# Tira a linha de `thumbnail` vazia que sobrou no mesmo objeto em que uma miniatura foi declarada.
import glob, io, json, os, re, sys
raiz = sys.argv[1]
for arquivo in glob.glob(os.path.join(raiz, 'gestor', 'modulos', '*', '*.json')) + glob.glob(os.path.join(raiz, 'gestor', 'resources', '*', 'templates.json')):
    texto = io.open(arquivo, encoding='utf-8', newline='').read()
    if 'templates' not in texto or '"thumbnail": ""' not in texto: continue
    nl = '\r\n' if '\r\n' in texto else '\n'
    linhas = texto.split(nl); saida = []; tirei = 0; cheia = -99
    for n, l in enumerate(linhas):
        if re.match(r'^\s*"thumbnail":\s*"templates', l): cheia = n
        if re.match(r'^\s*"thumbnail":\s*"",\s*$', l) and 0 < n - cheia < 8 and not any('}' in x for x in linhas[cheia:n]):
            tirei += 1; continue
        saida.append(l)
    if tirei:
        io.open(arquivo, 'w', encoding='utf-8', newline='').write(nl.join(saida)); json.loads(nl.join(saida))
        print(os.path.relpath(arquivo, raiz), tirei)
