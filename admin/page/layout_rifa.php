<?php

if(is_file("../class/conexao.php"))
	include("../class/conexao.php");
else
	include("../../class/conexao.php");

if(!isset($rifa))
	$rifa = $_GET['rifa'];

if(!isset($layout))
	$layout = $_GET['layout'];

if($layout == '' || $layout == 1){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
	$rifas_por_pagina = 12;
}
if( $layout == 2){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
	$rifas_por_pagina = 8;
}

if($layout == 3){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Grande'", 1);
	$medidas2 = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
	$rifas_por_pagina = 8;
}

if($layout == 4){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Grande'", 1);
	$rifas_por_pagina = 8;
}



$v = db_select($mysqli, "select * from vendedor_rifa where rifa = '$rifa' order by grupo ASC");
$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
$bilhetes = $bilhetes['rifa_maxbilhetes'];
$grupo = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);


if($bilhetes > 900 && $bilhetes < 1200)
	$bilhetes = 1000;
elseif($bilhetes > 3000)
	$bilhetes = 10000;

$grupos = ceil($bilhetes / $grupo['grupo']);

$numero_letras = ceil($grupos/100);

$bil = shuffleIt(gerar_array($bilhetes, $rifa), $rifa);

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

	$bilhetes_finais[] = "<span class=\"grupo\">".$titulo_grupos[$k]."</span>-<span class=\"milhar\">Nº: ".$val."</span>";

	if($i == $grupos){
		$k++;
		$i = 0;
	}

	$i++;


}

if($layout != 3){

$margem_topo = $medidas['margem_topo'];
$margem_dir = $medidas['margem_dir'];
$margem_bottom = $medidas['margem_chao'];
$margem_esq = $medidas['margem_esq'];

$rifa_altura = $medidas['rifa_altura'];
$rifa_largura = $medidas['rifa_largura'];

$largura_qr_code = $medidas['qr_code_largura'];
$altura_qr_code = $medidas['qr_code_altura'];

$altura_bloco_branco = $medidas['bloco_branco_altura'];
$largura_bloco_branco = $medidas['bloco_branco_largura'];

$distancia_bloco_branco_topo = $medidas['bloco_branco_distancia_topo'];
$distancia_bloco_branco_esquerda = $medidas['bloco_branco1_distancia_esquerda'];

$altura_bloco_branco_dir = $medidas['bloco_branco_altura'];
$largura_bloco_branco_dir = $medidas['bloco_branco_largura'];
$distancia_bloco_branco_dir_esquerda = $medidas['bloco_branco2_distancia_esquerda'];
$pos = "";



include("gerar_tamanhos.php");


if(is_file('../class/phpqrcode/qrlib.php'))
	include('../class/phpqrcode/qrlib.php');
else
	include('../../class/phpqrcode/qrlib.php');

QRcode::png("http://www.rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa, "QR_code.png");



	$trocar_tabela = 0;
	$trocar_linha = 0;
	$fim = count($bilhetes_finais);

	for($b = 0; $b < $fim; $b++){

	if($trocar_tabela == 0) echo "<table class=\"principal\">";
	if($trocar_linha == 0) echo "<tr>";

	?>
			<td valign="top" class="rifa-altura rifa-largura rifa" >
				<div class="area_pre_infos"></div>
				<div class="area_infos">
					<table class="inside">
						<tr>
							<td class="col1"><div class="bloco-branco"><?php echo $bilhetes_finais[$b]; ?></div></td>
							<td class="col2"><div class="qr-code"><img class="qr" src="QR_code.png"></div></td>
							<td class="col3"><div class="bloco-branco-dir"><?php echo $bilhetes_finais[$b]; ?></div></td>
						</tr>
					</table>
				</div>
			</td>
			
	<?php

	if($trocar_linha == 1) {echo "</tr>"; $trocar_linha = -1;}
	if($trocar_tabela == ($rifas_por_pagina-1)) {echo "</table>"; $trocar_tabela = -1;}

	$trocar_tabela++;
	$trocar_linha++;
	}

}else{ 

$margem_topo = $medidas['margem_topo'];
$margem_dir = $medidas['margem_dir'];
$margem_bottom = $medidas['margem_chao'];
$margem_esq = $medidas['margem_esq'];

$rifa_altura = $medidas['rifa_altura'];
$rifa_largura = $medidas['rifa_largura'];

$largura_qr_code = $medidas['qr_code_largura'];
$altura_qr_code = $medidas['qr_code_altura'];

$altura_bloco_branco = $medidas['bloco_branco_altura'];
$largura_bloco_branco = $medidas['bloco_branco_largura'];

$distancia_bloco_branco_topo = $medidas['bloco_branco_distancia_topo'];
$distancia_bloco_branco_esquerda = $medidas['bloco_branco1_distancia_esquerda'];

$altura_bloco_branco_dir = $medidas['bloco_branco_altura'];
$largura_bloco_branco_dir = $medidas['bloco_branco_largura'];
$distancia_bloco_branco_dir_esquerda = $medidas['bloco_branco2_distancia_esquerda'];
$pos = "";

include("gerar_tamanhos.php");

$margem_topo = $medidas2['margem_topo'];
$margem_dir = $medidas2['margem_dir'];
$margem_bottom = $medidas2['margem_chao'];
$margem_esq = $medidas2['margem_esq'];

$rifa_altura = $medidas2['rifa_altura'];
$rifa_largura = $medidas2['rifa_largura'];

$largura_qr_code = $medidas2['qr_code_largura'];
$altura_qr_code = $medidas2['qr_code_altura'];

$altura_bloco_branco = $medidas2['bloco_branco_altura'];
$largura_bloco_branco = $medidas2['bloco_branco_largura'];

$distancia_bloco_branco_topo = $medidas2['bloco_branco_distancia_topo'];
$distancia_bloco_branco_esquerda = $medidas2['bloco_branco1_distancia_esquerda'];

$altura_bloco_branco_dir = $medidas2['bloco_branco_altura'];
$largura_bloco_branco_dir = $medidas2['bloco_branco_largura'];
$distancia_bloco_branco_dir_esquerda = $medidas2['bloco_branco2_distancia_esquerda'];
$pos = "1";

include("gerar_tamanhos.php");


if(is_file('../class/phpqrcode/qrlib.php'))
	include('../class/phpqrcode/qrlib.php');
else
	include('../../class/phpqrcode/qrlib.php');

QRcode::png("http://www.rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa, "QR_code.png");

$trocar_tabela = 0;
$trocar_linha = 0;
$fim = count($bilhetes_finais);

for($b = 0; $b < $fim; $b++){

	if($trocar_tabela == 0) echo "<table class=\"principal\">";
	if($trocar_linha == 0) echo "<tr>";

	if($trocar_tabela < 4)
		$ext = "";
	else
		$ext = "1";
	?>
			<td valign="top" class="rifa-altura<?php echo $ext; ?> rifa-largura<?php echo $ext; ?> rifa<?php echo $ext; ?>" >
				<div class="area_pre_infos<?php echo $ext; ?>"></div>
				<div class="area_infos<?php echo $ext; ?>">
					<table class="inside">
						<tr>
							<td class="col1<?php echo $ext; ?>"><div class="bloco-branco"><?php echo $bilhetes_finais[$b]; ?></div></td>
							<td class="col2<?php echo $ext; ?>"><div class="qr-code"><img class="qr" src="QR_code.png"></div></td>
							<td class="col3<?php echo $ext; ?>"><div class="bloco-branco-dir"><?php echo $bilhetes_finais[$b]; ?></div></td>
						</tr>
					</table>
				</div>
			</td>
			
	<?php

	if($trocar_linha == 1) {echo "</tr>"; $trocar_linha = -1;}
	if($trocar_tabela == ($rifas_por_pagina-1)) {echo "</table>"; $trocar_tabela = -1;}

	$trocar_tabela++;
	$trocar_linha++;
	} 
?>

<?php } ?>

