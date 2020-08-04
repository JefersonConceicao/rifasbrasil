<?php

ini_set("memory_limit", "128M");
ini_set("max_execution_time", "0");

//include("../class/dompdf/autoload.inc.php");
include('../class/phpqrcode/qrlib.php');

// reference the Dompdf namespace

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
		if($_POST['layout'] == 5)
			$urlfinal = "http://52.67.27.97/admin/index.php?p=pdf_multiplo2";


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

	$numero_letras = ceil($grupos/100);

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

	$i = 1;
	$k = 0;
	$bilhetes_finais = array();
	foreach($bil as $val){

		$bilhetes_finais[] = "<span class=\"grupo\">".$titulo_grupos[$k]."</span>-<span class=\"milhar\">N: ".$val."</span><br><small>RIFA $rifa</small>";
		//$bilhetes_finais[] = $titulo_grupos[$k]."-".$val."<br>$rifa";

		if($i == $grupos){
			$k++;
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
		foreach($get['rifas'] as $r)
			$rifas[] = $r;
		
		$slots = $get['nslots'];
		$layout = intval($get['layout']);
		$numero_bilhetes = 12 * 1000;


		ob_start();
		include("page/layout_rifa2.php");
		$conteudo = ob_get_contents();
		ob_end_clean();

		$nome = md5(time());
		$fp = fopen("resultado/".$nome.".html", "w");
		fwrite_stream($fp, $conteudo);
	    fclose($fp);
	    exec('wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 320mm --page-height 415mm http://52.67.27.97/admin/resultado/'.$nome.'.html /var/www/html/admin/resultado/'.$nome.'.pdf');

	    return $nome.".pdf";
		
		//$dompdf->stream("sample.pdf",array("Attachment"=>1));
}

// exec('find  /var/www/html/admin/resultado/ -name "*.pdf" -type f -delete');

$nome = md5(time());
$time_start = time();
for($i = 0; $i < 5; $i++)	
	echo "<a href='http://52.67.27.97/admin/resultado/".gerar_pagina($_GET, $i)."'>Parte ".($i+1)."</a><br>";
// exec('find  /var/www/html/admin/resultado/ -name "*.html" -type f -delete');
//echo "<script>location.href='http://52.67.83.204/admin/resultado/".$nome.".pdf';</script>";
echo "Duração: ".(time() - $time_start)." segundos no total.<br>";

?>
