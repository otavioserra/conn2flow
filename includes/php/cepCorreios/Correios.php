<?php

require_once "informacoesCorreios.php";

class Correios
{
	public $informacoesCorreios;

	public function buscarCep($cep){
		$ch = curl_init();

			curl_setopt_array($ch, array
			(
				CURLOPT_URL 			=> "http://www.buscacep.correios.com.br/servicos/dnec/consultaEnderecoAction.do",
				CURLOPT_POST			=> TRUE,
				CURLOPT_POSTFIELDS		=> "relaxation={$cep}&TipoCep=ALL&semelhante=N&Metodo=listaLogradouro&TipoConsulta=relaxation&StartRow=1&EndRow=10&cfm=1",
				CURLOPT_RETURNTRANSFER	=> TRUE,
				CURLOPT_TIMEOUT			=> 5
			));

			$response = curl_exec($ch);
			curl_close($ch);

			preg_match_all("/>(.*?)<\/td>/", $response, $matches);

			return $matches[1];
	}

	public function retornaInformacoesCep($cep)
	{
	 	$informacoesCorreios = $this->buscarCep($cep);

		$this->informacoesCorreios = new informacoesCorreios();

		$this->informacoesCorreios->setLogradouro($informacoesCorreios[0]);
		$this->informacoesCorreios->setBairro($informacoesCorreios[1]);
		$this->informacoesCorreios->setLocalidade($informacoesCorreios[2]);
		$this->informacoesCorreios->setUf($informacoesCorreios[3]);
		$this->informacoesCorreios->setCep($informacoesCorreios[4]);
	}
}

?>