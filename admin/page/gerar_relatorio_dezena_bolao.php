<?php

function fwrite_stream($fp, $string) {
    for ($written = 0; $written < strlen($string); $written += $fwrite) {
        $fwrite = fwrite($fp, substr($string, $written));
        if ($fwrite === false) {
            return $written;
        }
    }
    return $written;
}

function curl_get_contents($url, $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // <-- don't forget this
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // <-- and this
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    if(curl_errno($ch))
        echo curl_error($ch);
    curl_close($ch);
    return $data;
}

$pontos = -1;
if(isset($_GET['pontos']) && $_GET['pontos'] >= 0){
	$pontos = intval($_GET['pontos']);
}

$venda = -1;
if($_GET['venda'] && is_numeric($_GET['venda']) && $_GET['venda'] > 0){
	$venda = intval($_GET['venda']);
}

if($_GET['alfabetico'])
	$alfabetico = 1;
else
	$alfabetico = 0;

$rifa_codigo = intval($_GET['rifa']);
$html = curl_get_contents("http://rifasbrasil.com.br/admin/page/relatorio_sorteio_pdf.php?rifa=".$rifa_codigo."&venda=".$venda."&imprimir=true&alfabetico=".$alfabetico."&pontos=" . $pontos, 30);

$nome = md5(time());
$arq  = "../relatorio/".$nome.".html";

$fp = fopen($arq, "w");
fputs($fp, $html);
fclose($fp);


exec('xvfb-run wkhtmltopdf -L 10mm -R 10mm -T 10mm -B 10mm --page-width 297mm --page-height 210mm http://rifasbrasil.com.br/admin/relatorio/'.$nome.'.html /var/www/nevoahost/c/rifasbrasil.com.br/admin/relatorio/'.$nome.'.pdf');

//echo 'http://rifasbrasil.com.br/admin/relatorio/'.$nome.'.html';

echo '<h1>Redirecionando em 3 segundos...</h1><script>setTimeout(function(){location.href="http://rifasbrasil.com.br/admin/relatorio/'.$nome.'.pdf";}, 3000); </script>';

/*
require_once '../dompdf/lib/html5lib/Parser.php';
require_once '../dompdf/lib/php-font-lib/src/FontLib/Autoloader.php';
require_once '../dompdf/lib/php-svg-lib/src/autoload.php';
require_once '../dompdf/src/Autoloader.php';
Dompdf\Autoloader::register();

// reference the Dompdf namespace
use Dompdf\Dompdf;

$rifa_codigo = intval($_GET['rifa']);

// instantiate and use the dompdf class
$dompdf = new Dompdf(array('enable_remote' => true));
$dompdf->loadHtml(file_get_contents("http://rifasbrasil.com.br/admin/page/relatorio_sorteio_pdf.php?rifa=".$rifa_codigo."&imprimir=true"));

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

$output = $dompdf->output();
$nome_arquivo = "Relatorio_".$rifa_codigo.".pdf";

file_put_contents("../relatorio/$nome_arquivo", $output);


die("<script>location.href='../relatorio/".($nome_arquivo)."';</script>");

// Output the generated PDF to Browser
//$dompdf->stream("Orcamento #".$orc_codigo." - ".($cliente).".pdf", array("Attachment" => false));*/


?>