---
title: "pdf.php library"
label: "PDF"
description: "Loads tFPDF and offers pdf_voucher(), a fixed-layout voucher generator inherited from an old system and with no callers in the core."
section: reference
order: 320
sources:
  - gestor/bibliotecas/pdf.php
verified_at: a5ae8605
---

# `pdf.php` library

When included (`gestor_incluir_biblioteca('pdf')`), it loads **tFPDF** (FPDF 1.84 with UTF-8 support) from `gestor/bibliotecas/fpdf184/`. After that, `new tFPDF()` is available to generate any PDF; the DejaVu fonts live in `fpdf184/font/unifont/`.

Its only function is `pdf_voucher()`, a leftover of a service sales system. No core module uses it.

## `pdf_voucher($params)`

Generates an A4 PDF with the service image, the QR Code, title, customer name, document and phone, and the `gestor/assets/images/logo-principal.png` logo. It writes to a temporary file (`sys_get_temp_dir()/pdf-<hash>`) and returns the path; **deleting the file is up to the caller**.

Required: `servicoImg`, `qrCodeImg`, `voucherTitulo`, `nome`, `documento`, `telefone`. Optional: `loteVariacao` (shows `voucherSubtitulo`).

- With a required one missing, it does nothing and returns `null`.
- A missing `loteVariacao` raises an undefined-variable warning.
- The name is reduced to the first and last names, texts are cut at 37/28 **bytes** (accented characters may be split) and the labels are hard-coded in Portuguese.

For new PDFs, use tFPDF directly instead of this function.

> [!NOTE]
> Displaying PDFs on pages (PDF.js) does not go through this library: it is done by `gestor_pdf_viewer_detectar()`/`gestor_pdf_viewer_assets()` in the [gestor.php library](gestor.md).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/pdf.php` by `c2f docs:extract` — 1 functions. Do not edit inside this block.

- `pdf_voucher(array|false $params = false): string|void` — [line 49](../../../../../gestor/bibliotecas/pdf.php#L49)

<!-- c2f:extract:end -->
