# Biblioteca: pdf.php

> 📄 Geração de PDFs usando FPDF

## Visão Geral

A biblioteca `pdf.php` fornece funções para geração de documentos PDF, atualmente focada em criação de vouchers. Utiliza a biblioteca FPDF (tFPDF para suporte Unicode) como base.

**Localização**: `gestor/bibliotecas/pdf.php`  
**Versão**: 1.0.0  
**Total de Funções**: 1  
**Biblioteca Base**: FPDF 1.84 (tFPDF)

## Dependências

- **Biblioteca Externa**: FPDF 1.84 (tFPDF)
  - Localização: `gestor/bibliotecas/fpdf184/tfpdf.php`
- **Fontes**: DejaVu Sans Condensed (para suporte Unicode)
- **Variáveis Globais**: `$_GESTOR`

## Variáveis Globais

```php
$_GESTOR['biblioteca-pdf'] = Array(
    'versao' => '1.0.0',
);

// Paths utilizados
$_GESTOR['bibliotecas-path'] // Caminho das bibliotecas
$_GESTOR['assets-path']      // Caminho dos assets (logo)
```

---

## Funções Principais

### pdf_voucher()

Gera PDF de voucher com QR Code e informações do cliente.

**Assinatura:**
```php
function pdf_voucher($params = false)
```

**Parâmetros (Array Associativo):**
- `servicoImg` (string) - **Obrigatório** - Caminho da imagem do serviço
- `qrCodeImg` (string) - **Obrigatório** - Caminho da imagem do QR Code
- `voucherTitulo` (string) - **Obrigatório** - Título do voucher
- `voucherSubtitulo` (string) - **Opcional** - Subtítulo do voucher
- `nome` (string) - **Obrigatório** - Nome do cliente
- `documento` (string) - **Obrigatório** - Documento do cliente (CPF, etc.)
- `telefone` (string) - **Obrigatório** - Telefone do cliente
- `loteVariacao` (bool) - **Obrigatório** - Se é lote e variação (mostra subtítulo)

**Retorno:**
- (string) - Caminho do arquivo PDF temporário gerado

**Exemplo de Uso:**
```php
// Preparar imagens
$imagem_servico = '/caminho/para/servico.jpg';
$imagem_qrcode = '/caminho/para/qrcode.png';

// Gerar voucher
$pdf_file = pdf_voucher(Array(
    'servicoImg' => $imagem_servico,
    'qrCodeImg' => $imagem_qrcode,
    'voucherTitulo' => 'Passeio de Balão',
    'voucherSubtitulo' => 'Variação Premium - Nascer do Sol',
    'nome' => 'João Silva Santos',
    'documento' => '123.456.789-00',
    'telefone' => '(11) 98765-4321',
    'loteVariacao' => true
));

// Enviar para navegador
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="voucher.pdf"');
readfile($pdf_file);

// Limpar arquivo temporário
unlink($pdf_file);
```

**Layout do Voucher:**
```
┌─────────────────────────────────────────────┐
│  ┌──────┐                                   │
│  │      │  Passeio de Balão                 │
│  │Serv. │  Variação Premium - Nascer do Sol │
│  │Image │                                    │
│  │      │  Nome:      João Silva Santos     │
│  └──────┘  ─────────────────────────────────  │
│            Documento: 123.456.789-00       │
│            ─────────────────────────────────  │
│            Telefone:  (11) 98765-4321      │
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
│             [Logo Principal]                │
└─────────────────────────────────────────────┘
```

**Características:**
- **Formato**: A4 Portrait
- **Fonte**: DejaVu Sans Condensed (suporte UTF-8)
- **Imagem do Serviço**: 60x60mm na posição (15, 15)
- **QR Code**: 130x130mm na posição (40, 90)
- **Logo**: 60x22mm na posição (80, 260)
- **Truncamento**: Textos longos são truncados com "..."
  - Título: máximo 37 caracteres
  - Nome/Doc/Tel: máximo 28 caracteres

**Comportamento com loteVariacao:**
- `true`: Mostra subtítulo abaixo do título
- `false`: Oculta subtítulo e ajusta layout

**Notas:**
- Arquivo temporário gerado em `sys_get_temp_dir()`
- Nome temporário: `pdf-{hash md5 único}`
- Responsabilidade do chamador: deletar arquivo após uso
- Logo principal deve estar em `$_GESTOR['assets-path'].'images/logo-principal.png'`

---

## Casos de Uso Comuns

### 1. Gerar e Baixar Voucher

```php
// Gerar voucher
$pdf_path = pdf_voucher(Array(
    'servicoImg' => '/uploads/servicos/passeio-balao.jpg',
    'qrCodeImg' => '/tmp/qrcode-123.png',
    'voucherTitulo' => 'Passeio de Balão',
    'nome' => 'Maria Oliveira',
    'documento' => '987.654.321-00',
    'telefone' => '(21) 91234-5678',
    'loteVariacao' => false
));

// Forçar download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="voucher-123.pdf"');
header('Content-Length: ' . filesize($pdf_path));
readfile($pdf_path);

// Limpar
unlink($pdf_path);
exit;
```

### 2. Enviar Voucher por Email

```php
// Gerar voucher
$pdf_path = pdf_voucher(Array(
    'servicoImg' => $servico['imagem'],
    'qrCodeImg' => $qrcode_path,
    'voucherTitulo' => $servico['nome'],
    'voucherSubtitulo' => $variacao['nome'],
    'nome' => $cliente['nome'],
    'documento' => $cliente['cpf'],
    'telefone' => $cliente['telefone'],
    'loteVariacao' => true
));

// Usar com PHPMailer (biblioteca comunicacao.php)
gestor_incluir_biblioteca('comunicacao');

comunicacao_email(Array(
    'para' => $cliente['email'],
    'assunto' => 'Seu Voucher - ' . $servico['nome'],
    'mensagem' => 'Segue em anexo seu voucher.',
    'anexos' => Array(
        Array(
            'caminho' => $pdf_path,
            'nome' => 'voucher.pdf'
        )
    )
));

// Limpar
unlink($pdf_path);
```

### 3. Gerar Múltiplos Vouchers em Lote

```php
$vendas = banco_select(Array(
    'campos' => '*',
    'tabela' => 'vendas',
    'extra' => "WHERE status='confirmado' AND voucher_gerado IS NULL"
));

if ($vendas) {
    foreach ($vendas as $venda) {
        // Buscar dados
        $servico = obter_servico($venda['servico_id']);
        $cliente = obter_cliente($venda['cliente_id']);
        $qrcode = gerar_qrcode($venda['codigo_validacao']);
        
        // Gerar voucher
        $pdf_path = pdf_voucher(Array(
            'servicoImg' => $servico['imagem'],
            'qrCodeImg' => $qrcode,
            'voucherTitulo' => $servico['nome'],
            'nome' => $cliente['nome'],
            'documento' => $cliente['documento'],
            'telefone' => $cliente['telefone'],
            'loteVariacao' => false
        ));
        
        // Salvar permanentemente
        $destino = "/arquivos/vouchers/voucher-{$venda['id']}.pdf";
        rename($pdf_path, $destino);
        
        // Atualizar banco
        banco_update(
            "voucher_gerado=1, voucher_path='$destino'",
            'vendas',
            "WHERE id='{$venda['id']}'"
        );
        
        // Limpar QR Code temporário
        unlink($qrcode);
    }
}
```

---

## Extensão da Biblioteca

### Adicionar Novas Funções de PDF

Para adicionar novos tipos de PDF, siga o padrão:

```php
function pdf_novo_tipo($params = false){
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    // Validar parâmetros obrigatórios
    if(!isset($parametro_obrigatorio)){
        return false;
    }
    
    // Criar arquivo temporário
    $path_temp = sys_get_temp_dir().'/';
    $temp_id = '-'.md5(uniqid(rand(), true));
    $tmpPDF = $path_temp.'pdf'.$temp_id;
    
    // Criar PDF
    $pdf = new tFPDF();
    $pdf->AddPage();
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    
    // Adicionar conteúdo
    $pdf->SetFont('DejaVu','',12);
    $pdf->Text(10, 10, 'Meu conteúdo');
    
    // Salvar
    $pdf->Output('F', $tmpPDF);
    
    return $tmpPDF;
}
```

---

## Limitações e Considerações

### Fontes Unicode

- Apenas DejaVu Sans Condensed está pré-configurada
- Para outras fontes, adicione em `fpdf184/font/unifont/`
- Use `$pdf->AddFont()` antes de usar

### Tamanho de Imagens

- Imagens muito grandes podem aumentar tamanho do PDF
- Redimensione imagens antes de passar para a função
- Formatos suportados: JPG, PNG, GIF

### Arquivos Temporários

- Sistema não limpa automaticamente
- Sempre delete após uso com `unlink()`
- Monitore `/tmp` em produção

### Performance

- Geração de PDF é CPU-intensiva
- Para lotes grandes, considere processamento assíncrono
- Cache PDFs quando possível

---

## Veja Também

- [FPDF Documentation](http://www.fpdf.org/en/doc/index.php) - Documentação oficial
- [tFPDF](http://fpdf.org/en/script/script92.php) - Versão Unicode
- [BIBLIOTECA-COMUNICACAO.md](./BIBLIOTECA-COMUNICACAO.md) - Envio de emails com anexos

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
