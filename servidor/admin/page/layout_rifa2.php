<?php


if($layout == '' || $layout == 1){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
	$rifas_por_pagina = 12;
}
if( $layout == 2){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
	$rifas_por_pagina = 6;
}

if($layout == 3){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Grande'", 1);
	$medidas2 = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
	$rifas_por_pagina = 4;
}

if($layout == 4){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Grande'", 1);
	$rifas_por_pagina = 8;
}

if($layout == 5){
	$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'a4'", 1);
	$rifas_por_pagina = 2;
}



$res = array();
$i = 0;
$num_bilhetes = 0;
foreach($rifas as $r){
	$res[$i] = get_bilhetes($mysqli, $r);
	$num_bilhetes = count($res[$i]);
	$i++;
}

//echo "Numero total de bilhetes: $num_bilhetes<br>Slots: $slots<br>Numero Bilhetes: $numero_bilhetes <br> Parte: $parte";


$bilhetes_finais = array();
$bilhetes_finais = organizar_array($res, $slots, $num_bilhetes, $numero_bilhetes, $parte);

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


echo "<!DOCTYPE html><head><meta charset=\"utf-8\">";
include("gerar_tamanhos.php");
echo "</head><body>";



	$trocar_tabela = 0;
	$trocar_linha = 0;
	$fim = count($bilhetes_finais);
	$pagina = 0;
	$rifa_atual = 0;

	for($b = 0; $b < $fim; $b++){

		if($trocar_tabela == 0) echo "<table class=\"principal\">";
		if($trocar_linha == 0) echo "<tr>";

		echo '<td valign="top" class="rifa-altura rifa-largura rifa" ><div class="area_pre_infos"></div><div class="area_infos"><table class="inside"><tr><td class="col1"><div class="bloco-branco">'.$bilhetes_finais[$b].'</div></td><td class="col2"><div class="qr-code"><img class="qr" src="'.$qr[$rifa_atual].'"></div></td><td class="col3"><div class="bloco-branco-dir">'.$bilhetes_finais[$b].'</div></td></tr></table></div></td>';

		if($trocar_linha == 1) {
			echo "</tr>"; 
			$trocar_linha = -1; 
			$rifa_atual++; 
			if($rifa_atual == count($rifas)) $rifa_atual = 0;
		}
		if($trocar_tabela == ($rifas_por_pagina-1)) {echo "</table>"; $trocar_tabela = -1; }

		$trocar_tabela++;
		$trocar_linha++;
	}

echo "</body>";

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
echo "<!DOCTYPE html><head><meta charset=\"utf-8\"></head><body>";
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


$trocar_tabela = 0;
$trocar_linha = 0;
$fim = count($bilhetes_finais);
$rifa_atual = 0;
$pagina = 0;

for($b = 0; $b < $fim; $b++){

	if($trocar_tabela == 0) echo "<table class=\"principal\">";
	if($trocar_linha == 0) echo "<tr>";

	if($trocar_tabela < 4)
		$ext = "";
	else
		$ext = "";
	?><td valign="top" class="rifa-altura<?php echo $ext; ?> rifa-largura<?php echo $ext; ?> rifa<?php echo $ext; ?>" ><div class="area_pre_infos<?php echo $ext; ?>"></div><div class="area_infos<?php echo $ext; ?>"><table class="inside"><tr><td class="col1<?php echo $ext; ?>"><div class="bloco-branco"><?php echo $bilhetes_finais[$b]; ?></div></td><td class="col2<?php echo $ext; ?>"><div class="qr-code"><img class="qr" src="<?php echo $qr[$rifa_atual]; ?>"></div></td><td class="col3<?php echo $ext; ?>"><div class="bloco-branco-dir"><?php echo $bilhetes_finais[$b]; ?></div></td></tr></table></div></td><?php

	if($trocar_linha == 1) {echo "</tr>"; $trocar_linha = -1; $rifa_atual++; if($rifa_atual == count($rifas)) $rifa_atual = 0;}
	if($trocar_tabela == ($rifas_por_pagina-1)) {echo "</table>"; $trocar_tabela = -1;  }

	$trocar_tabela++;
	$trocar_linha++;
	}

	echo "</body>"; 
 } ?>