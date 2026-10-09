# REQ-242 — utilitário de autoria: acrescenta `data-c2f-dica` a abas e botões de recursos HTML.
# Idempotente: tag que já tem a dica fica como está. Preserva o fim de linha do arquivo.
import io, re, sys

def ler(p):
    s = io.open(p, encoding='utf-8', newline='').read()
    return s, ('\r\n' if '\r\n' in s else '\n')

def gravar(p, s):
    io.open(p, 'w', encoding='utf-8', newline='').write(s)

def dica(html, padrao, texto, pos=None, extra_classes=None):
    """padrao: regex que casa um trecho DENTRO da tag de abertura (ex.: data-tab="x" ou id="y")."""
    total = 0
    def troca(m):
        nonlocal total
        tag = m.group(0)
        if not re.search(padrao, tag):
            return tag
        if extra_classes:
            def cls(c):
                atuais = c.group(1).split()
                for e in extra_classes.split():
                    if e not in atuais: atuais.append(e)
                return 'class="' + ' '.join(atuais) + '"'
            tag = re.sub(r'class="([^"]*)"', cls, tag, count=1)
        if texto and 'data-c2f-dica=' not in tag:
            attr = ' data-c2f-dica="' + texto + '"' + (' data-c2f-dica-pos="' + pos + '"' if pos else '')
            tag = tag[:-1] + attr + '>'
            total += 1
        return tag
    return re.sub(r'<(?:a|button|label|summary|input|select|div|span)\b[^>]*>', troca, html), total

def aplicar(caminho, regras):
    s, nl = ler(caminho)
    n = 0
    for r in regras:
        s, k = dica(s, *r)
        n += k
    gravar(caminho, s)
    return n
