<?php

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
if($layout == 5)
	$bilhetes_finais = organizar_array($res[0], $slots, $num_bilhetes, $numero_bilhetes, $parte);
else
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
	include("tamanhos-a4.php");
	echo "</head><body>";

	$trocar_tabela = 0;
	$trocar_linha = 0;
	$fim = count($bilhetes_finais);
	$pagina = 0;
	$rifa_atual = 0;

	for($b = 0; $b < $fim; $b++){

		if($trocar_tabela == 0) echo "<table  class=\"principal\">";
		if($trocar_linha == 0) echo "<tr>";

		echo '
		<td valign="top" class="rifa">
			<table>
				<tr>
					<td class="complemento-bloco-branco-topo"></td>
					<td class="qr-code-espaco" ></td>
					<td class="bloco-branco-topo">'.$bilhetes_finais[$b].'</td>
				</tr>
				<tr>
					<td colspan="3" class="espacamento"></td>
				</tr>
				<tr>
					<td class="espacamento-bottom"></td>
					<td class="qr-code" align="right"><img class="qr" src="'.$qr[$rifa_atual].'"></td>
					<td valign="bottom">
						<div class="bloco-branco-topo">'.$bilhetes_finais[$b].'</div>
					</td>
				</tr>
			</table>
		</td>';

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

} ?>