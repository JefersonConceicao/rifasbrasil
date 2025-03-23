<?php
@session_start();
include('../../class/conexao.php');

require_once '../dompdf/lib/html5lib/Parser.php';
require_once '../dompdf/lib/php-font-lib/src/FontLib/Autoloader.php';
require_once '../dompdf/lib/php-svg-lib/src/autoload.php';
require_once '../dompdf/src/Autoloader.php';

Dompdf\Autoloader::register();
use Dompdf\Dompdf;

$dompdf = new Dompdf();

$rifa = intval($_GET['rifa']);
$sql_query = "SELECT 
bil_numero, 
u.usu_nome,
u.usu_celular,
c.comp_valortotal,
c.comp_data,
c.comp_cod,
multiplicador,
bil_aposta
FROM tbl_bilhetes, tbl_rifas, tbl_usuario u, tbl_compra c
WHERE bil_rifa = '$rifa' 
AND rifa_cod = bil_rifa 
AND u.usu_cod = c.comp_cliente
AND c.comp_cod = bil_compra
";

$qrybil = $mysqli->query($sql_query) or die($mysqli->error);

$numApostas = 0;
$totalApostado = 0;
$totalPossivel = 0;

$countBilhetes = array();
$totalApostadoPorBilhete = array();
$premioPossivelPorBilhete = array();
$comprasDoBilhete = array();

while($resbil = $qrybil->fetch_assoc()) {

	$numApostas ++;
	$totalApostado += $resbil['bil_aposta'];
	$totalPossivel += $resbil['bil_aposta'] * $resbil['multiplicador'];

	if(!isset($totalApostadoPorBilhete[$resbil['bil_numero']]))
		$totalApostadoPorBilhete[$resbil['bil_numero']] = 0;

	if(!isset($countBilhetes[$resbil['bil_numero']]))
		$countBilhetes[$resbil['bil_numero']] = 0;

	if(!isset($comprasDoBilhete[$resbil['bil_numero']]))
		$comprasDoBilhete[$resbil['bil_numero']] = array();

	if(!isset($premioPossivelPorBilhete[$resbil['bil_numero']]))
		$premioPossivelPorBilhete[$resbil['bil_numero']] = 0;

	$countBilhetes[$resbil['bil_numero']]++;
	$totalApostadoPorBilhete[$resbil['bil_numero']] += $resbil['bil_aposta'];
	$premioPossivelPorBilhete[$resbil['bil_numero']] += $resbil['bil_aposta'] * $resbil['multiplicador'];

	$comprasDoBilhete[$resbil['bil_numero']][] = array(
		'nome' => $resbil['usu_nome'],
		'celular' => $resbil['usu_celular'],
		'valor' => $resbil['bil_aposta'],
		'premio' => $resbil['bil_aposta'] * $resbil['multiplicador'],
		'id_aposta' => $resbil['comp_cod'],
		'data' => date('d/m/Y H:i', strtotime($resbil['comp_data']))
	);
	
}

$tabela = '<h2>Número de Apostas: ' . $numApostas . ' | Apostado: R$ ' . number_format($totalApostado, 2, ',', '.') . '</h2><table style="font-family: Arial;" border="1" width="100%" id="imp" cellpadding="3">
	<thead>
		<tr style="background-color:#eaeaea; font-weight:bold;">
			<td>Bilhete</td>
			<td>Num Apostas</td>
			<td>Total Apostado</td>
			<td>Total Prêmio Possível</td>
		</tr>
	</thead>
	<tbody>';
		
	foreach($comprasDoBilhete as $bil_numero => $compras) {
		
		$tabela .= "
		<tr>
			<td>" .str_pad($bil_numero, 2, '0', STR_PAD_LEFT) . "</td>
			<td>" . $countBilhetes[$bil_numero] . "</td>
			<td>R$ " . number_format($totalApostadoPorBilhete[$bil_numero], 2, ',', '.') . "</td>
			<td>R$ " . number_format($premioPossivelPorBilhete[$bil_numero], 2, ',', '.') . '</td>
		</tr>
		<tr>
			<td colspan="4">';
				foreach($compras as $comp) {
					$tabela .=  "<b>Nome: </b> {$comp['nome']} | <b>Tel/Zap: </b> {$comp['celular']} | <b>Valor: </b> R$ " . number_format($comp['valor'], 2, ',', '.') . " | <b>Prêmio: </b> R$ " . number_format($comp['premio'], 2, ',', '.') . " | <b>ID Da Aposta: </b> {$comp['id_aposta']} | <b>Data: </b> {$comp['data']}<BR>"; 
				}
			$tabela .= "
			</td>
		</tr>";
	}  
	$tabela .= "
	</tbody>
</table>";

$dompdf->loadHtml($tabela);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

$dompdf->stream("relatorio.pdf", array("Attachment" => true));

?>