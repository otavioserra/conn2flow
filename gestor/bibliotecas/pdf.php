<?php
/**
 * Biblioteca de geração de PDFs.
 *
 * Fornece funções para geração de documentos PDF utilizando a biblioteca tFPDF.
 * Inclui funções especializadas para criação de vouchers e outros documentos.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-pdf']							=	Array(
	'versao' => '1.0.0',
);

// ===== Inclusão do FPDF

require_once $_GESTOR['bibliotecas-path'].'fpdf184/tfpdf.php';

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Gera um PDF de voucher com informações do serviço e QR Code.
 *
 * Cria um PDF formatado contendo imagem do serviço, QR Code para validação,
 * informações de identificação do cliente e logo da empresa. O PDF é salvo
 * em arquivo temporário e o caminho é retornado.
 *
 * @global array $_GESTOR Configurações globais do sistema.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['servicoImg'] Caminho da imagem do serviço (obrigatório).
 * @param string $params['qrCodeImg'] Caminho da imagem do QR Code (obrigatório).
 * @param string $params['voucherTitulo'] Título do voucher (obrigatório).
 * @param string $params['voucherSubtitulo'] Subtítulo do voucher (opcional).
 * @param string $params['nome'] Nome do cliente (obrigatório).
 * @param string $params['documento'] Documento do cliente (obrigatório).
 * @param string $params['telefone'] Telefone do cliente (obrigatório).
 * @param bool $params['loteVariacao'] Indica se é lote e variação (obrigatório).
 * 
 * @return string|void Caminho do arquivo PDF temporário ou void se parâmetros inválidos.
 */
function pdf_voucher($params = false){
	global $_GESTOR;
	
	// Extrai parâmetros do array
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// Valida parâmetros obrigatórios
	if(isset($servicoImg) && isset($qrCodeImg) && isset($voucherTitulo) && isset($nome) && isset($documento) && isset($telefone)){
		// ===== Preparar arquivo temporário para o PDF
		$path_temp = sys_get_temp_dir().'/';
		$temp_id = '-'.md5(uniqid(rand(), true));
		$tmpPDF = $path_temp.'pdf'.$temp_id;
		
		// ===== Processar e limitar tamanho dos textos do voucher
		$titMaxLen = 37;
		$voucherTitulo = strlen($voucherTitulo) > $titMaxLen ? substr($voucherTitulo,0,$titMaxLen)."..." : $voucherTitulo;
		
		// Processa subtítulo apenas se for lote e variação
		if($loteVariacao){
			$voucherSubtitulo = strlen($voucherSubtitulo) > $titMaxLen ? substr($voucherSubtitulo,0,$titMaxLen)."..." : $voucherSubtitulo;
		}
		
		// ===== Processar nome (primeiro e último nome apenas)
		$nomeArr = explode(' ',$nome);
		$nome = $nomeArr[0]. ' ' . $nomeArr[count($nomeArr) - 1];
		
		// ===== Limitar tamanho dos campos de identificação
		$idMaxLen = 28;
		$nome = strlen($nome) > $idMaxLen ? substr($nome,0,$idMaxLen)."..." : $nome;
		$documento = strlen($documento) > $idMaxLen ? substr($documento,0,$idMaxLen)."..." : $documento;
		$telefone = strlen($telefone) > $idMaxLen ? substr($telefone,0,$idMaxLen)."..." : $telefone;
		
		// ===== Definir labels dos campos
		$labelNome = 'Nome';
		$labelDocumento = 'Documento';
		$labelTelefone = 'Telefone';
		
		// ===== Inicializar PDF com tFPDF
		$logoPrincipal = $_GESTOR['assets-path'].'images/logo-principal.png';
		
		$pdf = new tFPDF();
		$pdf->AddPage();
		
		// ===== Definir coordenadas conforme layout (com ou sem subtítulo)
		if($loteVariacao){
			// Layout com subtítulo
			$xLab = 85;
			$xCam = 120;
			
			$yTit = 20;
			$ySubTit = 27;
			$yNom = 40;
			$yDoc = 50;
			$yTel = 60;
		} else {
			// Layout sem subtítulo
			$xLab = 85;
			$xCam = 120;
			
			$yTit = 20;
			$yNom = 30;
			$yDoc = 40;
			$yTel = 50;
		}
		
		// ===== Adicionar fontes DejaVu para suporte a Unicode
		$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
		$pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
		
		// ===== Renderizar título do voucher
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('DejaVu','B',16);
		$pdf->Text($xLab,$yTit,$voucherTitulo);
		
		// ===== Renderizar subtítulo se for lote e variação
		if($loteVariacao){
			$pdf->SetTextColor(140,140,140);
			
			$pdf->SetFont('DejaVu','',15);
			$pdf->Text($xLab,$ySubTit,$voucherSubtitulo);
		}
		
		// ===== Renderizar dados de identificação
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('DejaVu','',15);
		
		// Labels dos campos
		$pdf->Text($xLab,$yNom,$labelNome);
		$pdf->Text($xLab,$yDoc,$labelDocumento);
		$pdf->Text($xLab,$yTel,$labelTelefone);
		
		// Valores dos campos
		$pdf->Text($xCam,$yNom,$nome);
		$pdf->Text($xCam,$yDoc,$documento);
		$pdf->Text($xCam,$yTel,$telefone);
		
		// ===== Desenhar linhas de separação entre os campos
		$line1X = (($yNom + $yDoc) / 2) - 2;
		$line2X = (($yDoc + $yTel) / 2) - 2;
		
		$pdf->SetDrawColor(200, 200, 200);
		$pdf->SetLineWidth(0.3);
		
		$pdf->Line($xLab, $line1X, 200, $line1X);
		$pdf->Line($xLab, $line2X, 200, $line2X);
		
		// ===== Inserir imagens (serviço, QR Code e logo)
		$pdf->Image($servicoImg, 15, 15, 60, 60);
		$pdf->Image($qrCodeImg, 40, 90, 130, 130);
		$pdf->Image($logoPrincipal, 80, 260, 60, 22);
		
		// ===== Salvar PDF no arquivo temporário
		$pdf->Output('F',$tmpPDF);
		
		// ===== Retornar caminho do arquivo temporário
		return $tmpPDF;
	}
}

?>