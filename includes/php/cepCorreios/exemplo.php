<?php

require_once "Correios.php";

$correios = new Correios;
$correios->retornaInformacoesCep(14015120);
echo $correios->informacoesCorreios->getLogradouro();
echo "<br />";
echo $correios->informacoesCorreios->getBairro();
echo "<br />";
echo $correios->informacoesCorreios->getLocalidade();
echo "<br />";
echo $correios->informacoesCorreios->getUf();
echo "<br />";
echo $correios->informacoesCorreios->getCep();
echo "<br />";
?>