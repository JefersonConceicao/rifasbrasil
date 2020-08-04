<?php

ini_set("memory_limit", "256M");
ini_set("max_execution_time", "0");

//include("../class/dompdf/autoload.inc.php");
include('../class/phpqrcode/qrlib.php');

// reference the Dompdf namespace

function getNameFromNumber($num) {
    $numeric = ($num - 1) % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval(($num - 1) / 26);
    if ($num2 > 0) {
        return getNameFromNumber($num2) . $letter;
    } else {
        return $letter;
    }
}

function fwrite_stream($fp, $string) {
    for ($written = 0; $written < strlen($string); $written += $fwrite) {
        $fwrite = fwrite($fp, substr($string, $written));
        if ($fwrite === false) {
            return $written;
        }
    }
    return $written;
}


if(!isset($_GET['nslots'])){

		$urlfinal = "http://52.67.27.97/admin/index.php?p=pdf_multiplo";

		for($i = 1 ; $i <= $_POST['nslots']; $i++)
			$urlfinal .= "&rifas[]=".$_POST['rifa_' . $i];

		$urlfinal .= "&nslots=".$_POST['nslots']."&layout=".$_POST['layout'];

		echo "<script>if(confirm('O sistema irá agora gerar o arquivo em .pdf. Esta operação pode demorar alguns minutos. Prosseguir?')) location.href='".$urlfinal."';</script><h1>Gerando PDF...</h1>";
		break;
}


function organizar_array($arr, $slots, $max, $numero_bilhetes, $parte){

	$resultado = array();
	$inicio = 0;
	$linha = 0;
	$k = 0;
	while($k < (($slots * $max))){

		for($i = $inicio; $i < ($inicio+2); $i++)
			$resultado[$k++] = $arr[$linha][$i];

		$linha++;
		
		if($linha == $slots){
			$inicio += 2;
			$linha = 0;
		}
			
	}

	$tmp = array_chunk ($resultado, $numero_bilhetes); 
	return $tmp[$parte];
}

function get_bilhetes($mysqli, $rifa){

	$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
	$bilhetes = $bilhetes['rifa_maxbilhetes'];
	$grupo = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

	if($bilhetes > 900 && $bilhetes < 1200)
		$bilhetes = 1000;
	elseif($bilhetes > 3000)
		$bilhetes = 10000;

	$grupos = ceil($bilhetes / $grupo['grupo']);

	//echo "NUMERO DE GRUPOS $grupos<br>";

	$numero_letras = ceil($grupos/100);

	//echo "NUMERO DE LETRAS $numero_letras<br>";

	$bil = shuffleIt(range(0, $bilhetes-1), $rifa);

	$i = 0;
	$cont = 0;
	$titulo_grupos = array();

	for($i = 1; $i <= $numero_letras; $i++){

		$letra = getNameFromNumber($i);

		for($k = 1; $k <= 100; $k++){
			$titulo_grupos[] = $letra.$k;

			$cont++;
			if($cont == $grupos)
				break;
		}

	}

	//echo "<pre>".var_dump($titulo_grupos)."</pre>";

	$i = 1;
	$k = 0;
	$bilhetes_finais = array();
	$j = 0;


	foreach($bil as $val){

		$bilhetes_finais[] = "<span class=\"grupo\">".$titulo_grupos[$k]."</span>-<span class=\"milhar\">Nº ".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."</span><br><small>ID. $rifa | ".substr(md5($val), 0, 4)."</small>";
		//$bilhetes_finais[] = $titulo_grupos[$k]."-".$val."<br>$rifa";

		$j++;

		if($j == $grupo['grupo']){
			$j = 0;
			$k++;
		}

		if($i == $grupos){	
			$i = 0;
		}

		$i++;


	}

	return $bilhetes_finais;
}


function gerar_pagina($get, $parte){

	include("../class/conexao.php");

		$layout = ($get['layout']);

		$rifas = array();
		foreach($get['rifas'] as $r){
			$rifas[] = $r;
			QRcode::png("http://www.rifasbrasil.com.br/index.php?p=antifraude&rifa=".$r, "rifa_".$r.".png");
			$qr[] = "http://52.67.27.97/admin/rifa_".$r.".png";
		}
		
		$slots = $get['nslots'];
		$layout = intval($get['layout']);
		if($layout == 1)
			$numero_bilhetes = 12 * 1000;
		elseif($layout == 2)
			$numero_bilhetes = 6000;
		elseif($layout == 3)
			$numero_bilhetes = 4000;
		elseif($layout == 5)
			$numero_bilhetes = 2000;
		else
			$numero_bilhetes = 8 * 1000;


		ob_start();
		include("page/layout_rifa2.php");
		$conteudo = ob_get_contents();
		ob_end_clean();

		$nome = md5(time());
		$fp = fopen("resultado/".$nome.".html", "w");
		fwrite_stream($fp, $conteudo);
	    fclose($fp);

	    
	    if($layout == 2)
	    	exec('xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 210mm --page-height 297mm http://52.67.27.97/admin/resultado/'.$nome.'.html /var/www/rifasbrasil/admin/resultado/'.$nome.'.pdf');
	    elseif( $layout == 5)
	    	exec('xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 297mm --page-height 210mm http://52.67.27.97/admin/resultado/'.$nome.'.html /var/www/rifasbrasil/admin/resultado/'.$nome.'.pdf');
	    else
	    	exec('xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 320mm --page-height 415mm http://52.67.27.97/admin/resultado/'.$nome.'.html /var/www/rifasbrasil/admin/resultado/'.$nome.'.pdf');

	    return $nome.".pdf";
		
		//$dompdf->stream("sample.pdf",array("Attachment"=>1));
}

exec('find  /var/www/rifasbrasil/admin/resultado/ -name "*.pdf" -type f -delete');

echo "<h1>Baixe os arquivos gerados</h1><hr>";

$nome = md5(time());
$time_start = time();
for($i = 0; $i < 5; $i++)	
	echo "<a target=\"_blank\" href='http://52.67.27.97/admin/resultado/".gerar_pagina($_GET, $i)."'>Parte ".($i+1)."</a><br>";
// exec('find  /var/www/html/admin/resultado/ -name "*.html" -type f -delete');
//echo "<script>location.href='http://52.67.83.204/admin/resultado/".$nome.".pdf';</script>";

exec('find  /var/www/rifasbrasil/admin/resultado/ -name "*.html" -type f -delete');
?>
