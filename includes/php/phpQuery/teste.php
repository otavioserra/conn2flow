<?php

$html = '<div id="b2make-site" style="float: right; width: 1653px; margin-left: 250px; height: 932px;">
	<div id="b2make-pagina-options" data-pagina-menu-bolinhas-layout="14|1;solid;rgb(153,152,157);14;99989dff|ffffffff|1;solid;rgb(0,0,0);14;000000ff|000000ff"></div>
<div class="b2make-widget" id="area25" data-type="conteiner" data-area="conteiner-area26" data-area-largura="1000" style="position: relative; overflow: hidden; height: 628px; top: auto; z-index: auto; left: auto; border: none; background-color: rgb(212, 235, 132); background-size: 100%; width: 1653px; cursor: default;" data-zindex="1"><div class="b2make-widget b2make-conteiner-area" id="conteiner-area26" data-type="conteiner-area" style="border-left: none; border-right: none; background-color: transparent; cursor: s-resize;"><div class="b2make-widget b2make-texto" id="texto27" data-type="texto" data-bordas-atual="todas" style="position: absolute; font-size: 20px; color: rgb(0, 0, 0); top: 30px; left: 30px; width: 806px; height: 38px; border: 0px solid rgb(0, 0, 0); border-radius: 0px; font-family: &quot;Advent Pro&quot;; background-size: 100%;" data-marcador="@conteudo#nome" data-zindex="1" data-font-family="Advent Pro" data-google-font="sim"><div class="b2make-texto-table" data-type="texto"><div class="b2make-texto-cel" data-type="texto">Título</div></div></div><div class="b2make-widget" id="imagem28" data-type="imagem" data-bordas-atual="todas" style="position: absolute; font-size: 16px; color: rgb(0, 0, 0); top: 81px; left: 31px; width: 239.5px; height: 272px; border: 0px solid rgb(0, 0, 0); border-radius: 0px; background-color: rgb(255, 255, 255);" data-marcador="@conteudo#imagem" data-zindex="1"><img src="//platform.b2make.com/design/images/b2make-banners-sem-imagem.png" id="b2make-imagem-1" class="b2make-imagem" data-type="imagem" data-image-id="240" data-image-galeria="undefined" data-image-width="179" data-image-height="202" style="width: 240px; height: 270px;"></div><div class="b2make-widget b2make-texto" id="texto29" data-type="texto" data-bordas-atual="todas" style="position: absolute; font-size: 20px; color: rgb(0, 0, 0); top: 82px; left: 286px; width: 443.5px; height: 270px; border: 0px solid rgb(0, 0, 0); border-radius: 0px; background-size: 100%;" data-marcador="@conteudo#texto" data-zindex="1"><div class="b2make-texto-table" data-type="texto"><div class="b2make-texto-cel" data-type="texto">Descrição - no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono no nonono nono nononono no nono </div></div></div></div></div>
<div class="b2make-widget" id="area-campo-teste" data-type="conteiner" data-area="conteiner-area-campo-teste" data-area-largura="1000" style="background-color: rgb(232, 233, 235); position: relative; overflow: hidden; height: 300px; left: auto; top: auto; z-index: auto; border: none; background-size: 100%; width: 1653px; cursor: default;" data-zindex="1">
        <div class="b2make-widget b2make-conteiner-area" id="conteiner-area-campo-teste" data-type="conteiner-area" style="border-left: none; border-right: none; background-color: transparent; cursor: default;">
            <div class="b2make-widget b2make-texto" id="texto-campo-teste" data-type="texto" data-bordas-atual="todas" style="position: absolute; font-size: 20px; color: rgb(0, 0, 0); top: 30px; left: 30px; width: 300px; height: 150px; background-size: 100%; border: 0px solid rgb(0, 0, 0); border-radius: 0px;" data-marcador="@campo-teste#" data-zindex="1">
                <div class="b2make-texto-table" data-type="texto">
                    <div class="b2make-texto-cel" data-type="texto" style="cursor: move;">Texto</div>
                </div>
            </div>
        </div>
    </div>
<div id="b2make-shadow" style="display: none;"></div></div>';

require_once('phpQuery/phpQuery.php');

phpQuery::newDocumentHTML($html);

echo pq('#area-campo-teste')->htmlOuter();

?>