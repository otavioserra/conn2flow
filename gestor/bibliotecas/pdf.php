<?php

global $_GESTOR;

$_GESTOR['biblioteca-pdf']							=	Array(
	'versao' => '1.0.0',
);

// ===== Inclusão do FPDF

require_once $_GESTOR['bibliotecas-path'].'fpdf184/tfpdf.php';

// ===== Funções auxiliares

// ===== Funções principais

function pdf_voucher($params = false){
	/**********
		Descrição: PDF de um voucher
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// servicoImg - String - Obrigatório - Caminho da imagem do serviço.
	// qrCodeImg - String - Obrigatório - Caminho da imagem do qrCodeImg.
	// voucherTitulo - String - Obrigatório - Título do voucher.
	// voucherSubtitulo - String - Opcional - Subtítulo do voucher.
	// nome - String - Obrigatório - Nome da identificação.
	// documento - String - Obrigatório - Documento da identificação.
	// telefone - String - Obrigatório - Telefone da identificação.
	// loteVariacao - Bool - Obrigatório - Verifica se é lote e variação.
	
	// ===== 
	
	if(isset($servicoImg) && isset($qrCodeImg) && isset($voucherTitulo) && isset($nome) && isset($documento) && isset($telefone)){
		// ===== Arquivo temporário.
		
		$path_temp = sys_get_temp_dir().'/';
		$temp_id = '-'.md5(uniqid(rand(), true));
		$tmpPDF = $path_temp.'pdf'.$temp_id;
		
		// ===== Dados do voucher.
		
		$titMaxLen = 37;
		$voucherTitulo = strlen($voucherTitulo) > $titMaxLen ? substr($voucherTitulo,0,$titMaxLen)."..." : $voucherTitulo;
		
		if($loteVariacao){
			$voucherSubtitulo = strlen($voucherSubtitulo) > $titMaxLen ? substr($voucherSubtitulo,0,$titMaxLen)."..." : $voucherSubtitulo;
		}
		
		$nomeArr = explode(' ',$nome);
		$nome = $nomeArr[0]. ' ' . $nomeArr[count($nomeArr) - 1];
		
		$idMaxLen = 28;
		$nome = strlen($nome) > $idMaxLen ? substr($nome,0,$idMaxLen)."..." : $nome;
		$documento = strlen($documento) > $idMaxLen ? substr($documento,0,$idMaxLen)."..." : $documento;
		$telefone = strlen($telefone) > $idMaxLen ? substr($telefone,0,$idMaxLen)."..." : $telefone;
		
		$labelNome = 'Nome';
		$labelDocumento = 'Documento';
		$labelTelefone = 'Telefone';
		
		$logoPrincipal = $_GESTOR['assets-path'].'images/logo-principal.png';
		
		$pdf = new tFPDF();
		$pdf->AddPage();
		
		// ===== Coordenadas da identificação.
		
		if($loteVariacao){
			$xLab = 85;
			$xCam = 120;
			
			$yTit = 20;
			$ySubTit = 27;
			$yNom = 40;
			$yDoc = 50;
			$yTel = 60;
		} else {
			$xLab = 85;
			$xCam = 120;
			
			$yTit = 20;
			$yNom = 30;
			$yDoc = 40;
			$yTel = 50;
		}
		
		// ===== Dados da identidade.
		
		$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
		$pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('DejaVu','B',16);
		$pdf->Text($xLab,$yTit,$voucherTitulo);
		
		if($loteVariacao){
			$pdf->SetTextColor(140,140,140);
			
			$pdf->SetFont('DejaVu','',15);
			$pdf->Text($xLab,$ySubTit,$voucherSubtitulo);
		}
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('DejaVu','',15);
		
		$pdf->Text($xLab,$yNom,$labelNome);
		$pdf->Text($xLab,$yDoc,$labelDocumento);
		$pdf->Text($xLab,$yTel,$labelTelefone);
		
		$pdf->Text($xCam,$yNom,$nome);
		$pdf->Text($xCam,$yDoc,$documento);
		$pdf->Text($xCam,$yTel,$telefone);
		
		// ===== Linhas de separação dos nomes.
		
		$line1X = (($yNom + $yDoc) / 2) - 2;
		$line2X = (($yDoc + $yTel) / 2) - 2;
		
		$pdf->SetDrawColor(200, 200, 200);
		$pdf->SetLineWidth(0.3);
		
		$pdf->Line($xLab, $line1X, 200, $line1X);
		$pdf->Line($xLab, $line2X, 200, $line2X);
		
		// ===== Imagem do serviço e do qrCode.
		
		$pdf->Image($servicoImg, 15, 15, 60, 60);
		$pdf->Image($qrCodeImg, 40, 90, 130, 130);
		$pdf->Image($logoPrincipal, 80, 260, 60, 22);
		
		// ===== Salvar o pdf no tmpPDF.
		
		$pdf->Output('F',$tmpPDF);
		
		// ===== Retornar o arquivo temporário do PDF.
		
		return $tmpPDF;
	}
}

?>