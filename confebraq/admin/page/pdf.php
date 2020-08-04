<?php

include("../class/dompdf/autoload.inc.php");

$layout = ($_GET['layout']);

// reference the Dompdf namespace
use Dompdf\Dompdf;

// instantiate and use the dompdf class
$dompdf = new Dompdf();

$rifa = intval($_GET['rifa']);
$layout = intval($_GET['layout']);
ob_start();
include("page/layout_rifa.php");
$conteudo = ob_get_contents();
ob_end_clean();

$dompdf->loadHtml($conteudo);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper(array(0, 0, 907.09333333333, 1204.733333333333), 'portrait');

// Render the HTML as PDF
$dompdf->render();

$pdf = $dompdf->output();      // gets the PDF as a string

//file_put_contents("pages/pdf/arquivo.pdf", $pdf);  // save the pdf file on server
$dompdf->stream("sample.pdf",array("Attachment"=>0));
?>