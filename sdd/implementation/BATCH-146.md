# BATCH-146 — Assets de terceiros servidos do disco (saída do CDN)

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-143.md`
- **Data de abertura**: 2026-08-29
- **Classificação**: arquitetura de assets, privacidade/LGPD, resiliência, supply chain

---

## 1. O que o lote anterior deixou pela metade

`bibliotecas/assets-externos.php` já resolvia "local primeiro, CDN como fallback" — mas
`gestor/assets/vendor/` **nunca existiu**. O fallback era o único caminho: o sistema seguia 100%
dependente de CDN, com a aparência de já ter migrado.

Este lote entrega a peça que faltava (`c2f assets:vendor`) e migra os pontos de uso.

## 2. Inventário medido no core

| Biblioteca | Situação encontrada |
| --- | --- |
| **jQuery** | 4 pontos, **3 versões**, **4 hosts**: 3.5.1 `ajax.googleapis.com` (`gestor.php`, toda página do gestor), 3.7.1 jsdelivr (editor HTML), 3.7.1 cdnjs (toolbar), 3.6.0 jsdelivr (widget de idioma) |
| **CodeMirror 5.65.20** | **161 tags** em 7 arquivos PHP, cada cópia com lista e ordem próprias |
| **Quill** | preso em `quill@2` — uma **faixa**, não uma versão |
| **Fomantic-UI 2.9.4** | CSS e JS emitidos direto em `gestor.php` |
| SortableJS / QRCode / FingerprintJS | já registrados no BATCH anterior |

Duas versões de jQuery na mesma tela quebram plugins de um jeito difícil de rastrear: quem carrega
por último vence, e os plugins registrados na anterior somem.

A faixa `quill@2` é mais grave do que parece: `quill-content.css` é **gerado a partir dessa versão**,
então ela permitia o editor e a página publicada divergirem sem ninguém ter mexido em nada.

## 3. `c2f assets:vendor`

Baixa os 28 arquivos declarados no registro para `gestor/assets/vendor/<lib>/<versão>/`.

**Verificação de certificado fica sempre ligada.** O PHP CLI do Windows não traz CA bundle e as 28
baixas falharam de uma vez com `unable to get local issuer certificate`. A saída fácil seria
`CURLOPT_SSL_VERIFYPEER => false`, e ela está descartada de propósito: são arquivos servidos como
biblioteca em toda tela do gestor, e baixá-los por canal não verificado é pior do que continuar no
CDN. A cadeia cai para o binário `curl` do sistema, que valida contra o repositório de certificados
do SO.

Só aceita HTTP 200: um 404 do CDN devolve corpo HTML, e gravá-lo com nome de biblioteca seria a
falha mais difícil de diagnosticar que o comando poderia produzir.

## 4. `.gitignore`

O `vendor/` do Composer também engolia `gestor/assets/vendor/`. Ignorá-los faria a migração valer só
na máquina de quem rodou o comando: em produção os arquivos não existiriam e o resolvedor cairia no
CDN **em silêncio**, com o código parecendo migrado.

Exceção explícita (`!gestor/assets/vendor/`): 2,9 MB versionados em troca de o que roda em produção
ser exatamente o que foi revisado, sem depender de rede no momento do deploy.

## 5. Validação

| Evidência | Resultado |
| --- | --- |
| Arquivos baixados | 28/28, 2,9 MB, nenhuma página de erro |
| Tela `variables` do gestor | **26 assets do disco, 0 do CDN** |
| Home pública | só `fonts.googleapis.com` (fontes — decisão separada) |
| Tags de CDN removidas do PHP | 161 (CodeMirror) + jQuery + Fomantic |
| PHPUnit | 902/902 (5 novos) |
| Vitest | 378/378 |

## 6. Pendências

- `assets/interface/html-editor-interface.js` monta o HTML do iframe de preview com URLs de CDN
  fixas (jQuery, Fomantic, Quill, CodeMirror). Exige injetar as URLs do PHP para o JS — fatia
  própria.
- `dashboard.toolbar.js`, `galleries.js`, `menus.js`, `pages-index.js`,
  `publisher-{highlights,index}.js`, `formulario.js`, `pdf-viewer.js` e dois layouts ainda têm URLs
  de CDN.
- `@tailwindcss/browser@4.3.0` fica como **exceção declarada**: é o compilador do editor online e
  não tem equivalente local viável.
- Fontes do Google na home pública: self-hosting é decisão do operador.
- `assets/datatables/` (925 KB em dois arquivos não minificados) é candidato ao registro.
