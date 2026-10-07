# REQ-256 — converte as fotos dos modelos em miniaturas WebP e monta folhas de conferência.
# Uso: python montar.py <pasta das fotos> <pasta de saída das miniaturas> [folhas]
import json, os, sys
from PIL import Image, ImageDraw

fotos, saida = sys.argv[1], sys.argv[2]
LARGURA, ALTURA = 580, 394  # o dobro das miniaturas antigas (290 x 197), mesma proporção
relatorio = json.load(open(os.path.join(fotos, 'relatorio.json'), encoding='utf-8'))
feitas = 0
for r in relatorio:
    origem = os.path.join(fotos, r['language'], r['id'] + '.png')
    destino = os.path.join(saida, r['language'], r['id'] + '.webp')
    os.makedirs(os.path.dirname(destino), exist_ok=True)
    im = Image.open(origem).convert('RGB').resize((LARGURA, ALTURA), Image.LANCZOS)
    im.save(destino, 'WEBP', quality=82, method=6)
    feitas += 1
print(feitas, 'miniaturas em', saida)

if len(sys.argv) > 3:
    # Folha de conferência por alvo e idioma: 4 por linha, com o identificador embaixo.
    grupos = {}
    for r in relatorio:
        grupos.setdefault((r['language'], r['target']), []).append(r)
    os.makedirs(sys.argv[3], exist_ok=True)
    for (lingua, alvo), itens in sorted(grupos.items()):
        cols = 4; w, h = 290, 197; linhas = (len(itens) + cols - 1) // cols
        folha = Image.new('RGB', (cols * (w + 10) + 10, linhas * (h + 28) + 10), '#334155')
        d = ImageDraw.Draw(folha)
        for i, r in enumerate(itens):
            x, y = 10 + (i % cols) * (w + 10), 10 + (i // cols) * (h + 28)
            folha.paste(Image.open(os.path.join(saida, lingua, r['id'] + '.webp')).resize((w, h)), (x, y))
            d.text((x + 2, y + h + 6), r['id'][:44], fill='#f8fafc')
        folha.save(os.path.join(sys.argv[3], lingua + '-' + alvo + '.jpg'), quality=80)
    print(len(grupos), 'folhas em', sys.argv[3])
