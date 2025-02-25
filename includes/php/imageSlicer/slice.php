<?php
include("slicer.php");

$picture = "mailing_lancamento.jpg";
$pie = 10;
$hor = 150;
$ver = 100;

$slicer = new Slicer();
$slicer->set_picture($picture);
$slicer->set_slice_res($hor,$ver);
$slicer->save_slices_res("jpg","foto");
?>
<html>
<body>
<?php
	$tabela = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
	$c = 0;
	for($i=0;$i<$slicer->slice_hor;$i++){
		$tabela .= "<tr>\n";
		for($j=0;$j<$slicer->slice_ver;$j++){
			$tabela .= "<td><img src=foto".$c.".jpg></td>\n";
			$c++;
		}
		$tabela .= "</tr>\n";
	}
	$tabela .= "</table>\n";
?>
</body>
</html>