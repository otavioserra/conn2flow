---
title: "Biblioteca pdf.php"
label: "PDF"
description: "Carrega o tFPDF e oferece pdf_voucher(), um gerador de voucher com layout fixo herdado de um sistema antigo e sem chamadores no core."
section: reference
order: 320
sources:
  - gestor/bibliotecas/pdf.php
verified_at: a5ae8605
---

# Biblioteca `pdf.php`

Ao ser incluída (`gestor_incluir_biblioteca('pdf')`), carrega o **tFPDF** (FPDF 1.84 com suporte a UTF-8) de `gestor/bibliotecas/fpdf184/`. Depois disso, `new tFPDF()` está disponível para gerar qualquer PDF; as fontes DejaVu ficam em `fpdf184/font/unifont/`.

A única função própria é `pdf_voucher()`, resto de um sistema de vendas de serviços. Nenhum módulo do core a usa.

## `pdf_voucher($params)`

Gera um PDF A4 com a imagem do serviço, o QR Code, título, nome, documento e telefone do cliente, e o logo `gestor/assets/images/logo-principal.png`. Grava em um arquivo temporário (`sys_get_temp_dir()/pdf-<hash>`) e devolve o caminho; **apagar o arquivo é com quem chamou**.

Obrigatórios: `servicoImg`, `qrCodeImg`, `voucherTitulo`, `nome`, `documento`, `telefone`. Opcionais: `loteVariacao` (mostra `voucherSubtitulo`).

- Faltando um obrigatório, não faz nada e devolve `null`.
- `loteVariacao` ausente gera *warning* de variável indefinida.
- O nome é reduzido ao primeiro e ao último, os textos são cortados em 37/28 **bytes** (acentos podem ser partidos ao meio) e os rótulos são fixos em português.

Para PDFs novos, use o tFPDF direto em vez desta função.

> [!NOTE]
> A visualização de PDFs nas páginas (PDF.js) não passa por esta biblioteca: é feita por `gestor_pdf_viewer_detectar()`/`gestor_pdf_viewer_assets()` na [biblioteca gestor.php](gestor.md).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/pdf.php` por `c2f docs:extract` — 1 funções. Não edite dentro deste bloco.

- `pdf_voucher(array|false $params = false): string|void` — [linha 49](../../../../../gestor/bibliotecas/pdf.php#L49)

<!-- c2f:extract:end -->
