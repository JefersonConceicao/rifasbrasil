<?php

include("../class/dompdf/autoload.inc.php");
// reference the Dompdf namespace
use Dompdf\Dompdf;

if(!isset($_GET['nslots'])){

	$urlfinal = "http://rifasbrasil.com.br/admin/index.php?p=pdf_multiplo";

	for($i = 1 ; $i <= $_POST['nslots']; $i++)
		$urlfinal .= "&rifas[]=".$_POST['rifa_' . $i];

	$urlfinal .= "&nslots=".$_POST['nslots']."&layout=".$_POST['layout'];

	echo "<script>location.href='".$urlfinal."';</script>";
	break;

}else{

	$layout = ($_GET['layout']);

	// instantiate and use the dompdf class
	$dompdf = new Dompdf();

	$rifas = array();
	foreach($_GET['rifas'] as $r)
		$rifas[] = $r;
	
	$slots = $_GET['nslots'];
	$layout = intval($_GET['layout']);
	$numero_bilhetes = 60;

	for($k = 0; $k < 10; $k++){
		
		$parte = $k;

		ob_start();
		include("page/layout_rifa2.php");
		$conteudo = ob_get_contents();
		ob_end_clean();

		$dompdf->loadHtml($conteudo);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper(array(0, 0, 907.09333333333, 1204.733333333333), 'portrait');

		// Render the HTML as PDF
		$dompdf->render();

		$pdf = $dompdf->output();      // gets the PDF as a string

		file_put_contents("resultado/arquivo_".$k.".pdf", $pdf);  // save the pdf file on server
	}
	//$dompdf->stream("sample.pdf",array("Attachment"=>1));
}
?>
<a href="test.html" target="_blank">clique aqui</a>