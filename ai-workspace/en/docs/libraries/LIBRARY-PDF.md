# Library: pdf.php

> 📄 PDF generation using FPDF

## Overview

The `pdf.php` library provides functions for generating PDF documents, currently focused on creating vouchers. It uses the FPDF library (tFPDF for Unicode support) as a base.

**Location**: `gestor/bibliotecas/pdf.php`  
**Version**: 1.0.0  
**Total Functions**: 1  
**Base Library**: FPDF 1.84 (tFPDF)

## Dependencies

- **External Library**: FPDF 1.84 (tFPDF)
  - Location: `gestor/bibliotecas/fpdf184/tfpdf.php`
- **Fonts**: DejaVu Sans Condensed (for Unicode support)
- **Global Variables**: `$_GESTOR`

## Global Variables

```php
$_GESTOR['biblioteca-pdf'] = Array(
    'versao' => '1.0.0',
);

// Used paths
$_GESTOR['bibliotecas-path'] // Libraries path
$_GESTOR['assets-path']      // Assets path (logo)
```

---

## Main Functions

### pdf_voucher()

Generates voucher PDF with QR Code and client information.

**Signature:**
```php
function pdf_voucher($params = false)
```

**Parameters (Associative Array):**
- `servicoImg` (string) - **Required** - Service image path
- `qrCodeImg` (string) - **Required** - QR Code image path
- `voucherTitulo` (string) - **Required** - Voucher title
- `voucherSubtitulo` (string) - **Optional** - Voucher subtitle
- `nome` (string) - **Required** - Client name
- `documento` (string) - **Required** - Client document (ID, etc.)
- `telefone` (string) - **Required** - Client phone
- `loteVariacao` (bool) - **Required** - If it is batch and variation (shows subtitle)

**Return:**
- (string) - Generated temporary PDF file path

**Usage Example:**
```php
// Prepare images
$service_image = '/path/to/service.jpg';
$qrcode_image = '/path/to/qrcode.png';

// Generate voucher
$pdf_file = pdf_voucher(Array(
    'servicoImg' => $service_image,
    'qrCodeImg' => $qrcode_image,
    'voucherTitulo' => 'Balloon Ride',
    'voucherSubtitulo' => 'Premium Variation - Sunrise',
    'nome' => 'John Doe',
    'documento' => '123.456.789-00',
    'telefone' => '(11) 98765-4321',
    'loteVariacao' => true
));

// Send to browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="voucher.pdf"');
readfile($pdf_file);

// Clean temporary file
unlink($pdf_file);
```

**Voucher Layout:**
```
┌─────────────────────────────────────────────┐
│  ┌──────┐                                   │
│  │      │  Balloon Ride                     │
│  │Serv. │  Premium Variation - Sunrise      │
│  │Image │                                    │
│  │      │  Name:      John Doe              │
│  └──────┘  ─────────────────────────────────  │
│            Document:  123.456.789-00       │
│            ─────────────────────────────────  │
│            Phone:     (11) 98765-4321      │
│                                             │
│                                             │
│             ┌─────────────┐                 │
│             │             │                 │
│             │   QR Code   │                 │
│             │             │                 │
│             └─────────────┘                 │
│                                             │
│                                             │
│                                             │
│             [Main Logo]                     │
└─────────────────────────────────────────────┘
```

**Features:**
- **Format**: A4 Portrait
- **Font**: DejaVu Sans Condensed (UTF-8 support)
- **Service Image**: 60x60mm at position (15, 15)
- **QR Code**: 130x130mm at position (40, 90)
- **Logo**: 60x22mm at position (80, 260)
- **Truncation**: Long texts are truncated with "..."
  - Title: max 37 characters
  - Name/Doc/Tel: max 28 characters

**Behavior with loteVariacao:**
- `true`: Shows subtitle below title
- `false`: Hides subtitle and adjusts layout

**Notes:**
- Temporary file generated in `sys_get_temp_dir()`
- Temporary name: `pdf-{unique md5 hash}`
- Caller responsibility: delete file after use
- Main logo must be at `$_GESTOR['assets-path'].'images/logo-principal.png'`

---

## Common Use Cases

### 1. Generate and Download Voucher

```php
// Generate voucher
$pdf_path = pdf_voucher(Array(
    'servicoImg' => '/uploads/services/balloon-ride.jpg',
    'qrCodeImg' => '/tmp/qrcode-123.png',
    'voucherTitulo' => 'Balloon Ride',
    'nome' => 'Mary Smith',
    'documento' => '987.654.321-00',
    'telefone' => '(21) 91234-5678',
    'loteVariacao' => false
));

// Force download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="voucher-123.pdf"');
header('Content-Length: ' . filesize($pdf_path));
readfile($pdf_path);

// Clean
unlink($pdf_path);
exit;
```

### 2. Send Voucher by Email

```php
// Generate voucher
$pdf_path = pdf_voucher(Array(
    'servicoImg' => $service['image'],
    'qrCodeImg' => $qrcode_path,
    'voucherTitulo' => $service['name'],
    'voucherSubtitulo' => $variation['name'],
    'nome' => $client['name'],
    'documento' => $client['cpf'],
    'telefone' => $client['phone'],
    'loteVariacao' => true
));

// Use with PHPMailer (communication.php library)
gestor_incluir_biblioteca('comunicacao');

comunicacao_email(Array(
    'para' => $client['email'],
    'assunto' => 'Your Voucher - ' . $service['name'],
    'mensagem' => 'Attached is your voucher.',
    'anexos' => Array(
        Array(
            'caminho' => $pdf_path,
            'nome' => 'voucher.pdf'
        )
    )
));

// Clean
unlink($pdf_path);
```

### 3. Generate Multiple Vouchers in Batch

```php
$sales = banco_select(Array(
    'campos' => '*',
    'tabela' => 'sales',
    'extra' => "WHERE status='confirmed' AND voucher_generated IS NULL"
));

if ($sales) {
    foreach ($sales as $sale) {
        // Fetch data
        $service = get_service($sale['service_id']);
        $client = get_client($sale['client_id']);
        $qrcode = generate_qrcode($sale['validation_code']);
        
        // Generate voucher
        $pdf_path = pdf_voucher(Array(
            'servicoImg' => $service['image'],
            'qrCodeImg' => $qrcode,
            'voucherTitulo' => $service['name'],
            'nome' => $client['name'],
            'documento' => $client['document'],
            'telefone' => $client['phone'],
            'loteVariacao' => false
        ));
        
        // Save permanently
        $destination = "/files/vouchers/voucher-{$sale['id']}.pdf";
        rename($pdf_path, $destination);
        
        // Update database
        banco_update(
            "voucher_generated=1, voucher_path='$destination'",
            'sales',
            "WHERE id='{$sale['id']}'"
        );
        
        // Clean temporary QR Code
        unlink($qrcode);
    }
}
```

---

## Library Extension

### Adding New PDF Functions

To add new PDF types, follow the pattern:

```php
function pdf_new_type($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validate required parameters
    if(!isset($required_parameter)){
        return false;
    }
    
    // Create temporary file
    $path_temp = sys_get_temp_dir().'/';
    $temp_id = '-'.md5(uniqid(rand(), true));
    $tmpPDF = $path_temp.'pdf'.$temp_id;
    
    // Create PDF
    $pdf = new tFPDF();
    $pdf->AddPage();
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    
    // Add content
    $pdf->SetFont('DejaVu','',12);
    $pdf->Text(10, 10, 'My content');
    
    // Save
    $pdf->Output('F', $tmpPDF);
    
    return $tmpPDF;
}
```

---

## Limitations and Considerations

### Unicode Fonts

- Only DejaVu Sans Condensed is pre-configured
- For other fonts, add to `fpdf184/font/unifont/`
- Use `$pdf->AddFont()` before using

### Image Size

- Very large images can increase PDF size
- Resize images before passing to function
- Supported formats: JPG, PNG, GIF

### Temporary Files

- System does not clean automatically
- Always delete after use with `unlink()`
- Monitor `/tmp` in production

### Performance

- PDF generation is CPU-intensive
- For large batches, consider asynchronous processing
- Cache PDFs when possible

---

## See Also

- [FPDF Documentation](http://www.fpdf.org/en/doc/index.php) - Official documentation
- [tFPDF](http://fpdf.org/en/script/script92.php) - Unicode version
- [LIBRARY-COMMUNICATION.md](./LIBRARY-COMMUNICATION.md) - Sending emails with attachments

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
