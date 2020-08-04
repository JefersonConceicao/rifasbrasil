<?php
// Cálculos
$dist_bloco_branco_esquerda_rifa = $distancia_bloco_branco_esquerda-$margem_esq;
$dist_bloco_branco_topo_rifa = $distancia_bloco_branco_topo - $margem_topo;
$altura_area_preinfos = $rifa_altura - ($rifa_altura - $distancia_bloco_branco_topo) - $altura_qr_code;
$distancia_bloco_branco_dir_topo = $distancia_bloco_branco_topo; 

$fazul = db_select($mysqli, "select margem_topo from impressao_rifas where tipo = 'Final'", 1);
if($fazul['margem_topo'] > 0){
	$azul = 'background-color:cyan;';
}else
	$azul = '';
?>
<style>
	body{
		padding:0; 
		margin:0; 
		border:0;
	}

	html{
		margin: <?php echo $margem_topo; ?>cm <?php echo $margem_dir; ?>cm <?php echo $margem_bottom; ?>cm <?php echo $margem_esq; ?>cm; 
		
	}
	
	.rifa-altura<?php echo $pos; ?>{
		height:<?php echo $rifa_altura; ?>cm;
	}

	.rifa-largura<?php echo $pos; ?>{
		width:<?php echo $rifa_largura; ?>cm;
	}

	.rifa<?php echo $pos; ?>{

		border:0; 
		padding:0; 
		margin:0; 
		border-spacing: 0; 

		<?php echo $azul; ?>
		box-sizing: border-box;
	    -moz-box-sizing: border-box;
	    -webkit-box-sizing: border-box;

    }

	.bloco-branco<?php echo $pos; ?>{

		margin-left:<?php echo $dist_bloco_branco_esquerda_rifa; ?>cm; 
		top:0; 
		left:0; 
		margin-top:<?php echo ($altura_qr_code - $altura_bloco_branco); ?>cm; 
	 	
	 	<?php if(strlen($azul) > 0) echo 'background-color:white;'; ?>

		width:<?php echo $largura_bloco_branco; ?>cm; 
		height:<?php echo ($altura_bloco_branco-(number_format($altura_bloco_branco_dir/2*0.5, 2, '.', '.'))); ?>cm;
		text-align: center;
		padding-top: <?php echo number_format($altura_bloco_branco_dir/2*0.5, 2, '.', '.'); ?>cm;

	}

	.qr-code<?php echo $pos; ?>{

		background-color:red; 
		margin-left:<?php echo ($distancia_bloco_branco_dir_esquerda - $largura_qr_code - ($rifa_largura/2)); ?>cm; 
		top:0; 
		left:0;  
		margin-top:0; 
		background-color:transparent;  
		width:<?php echo $largura_qr_code; ?>cm; 
		height:<?php echo $altura_qr_code; ?>cm;
	}

	.bloco-branco-dir<?php echo $pos; ?>{
		margin-left:0;
		top:0;  
		left:0; 
		text-align: center; 
		margin-top:<?php echo ($altura_qr_code - $altura_bloco_branco); ?>cm; 
		background-color:white;  
		width:<?php echo $largura_bloco_branco_dir; ?>cm; 
		height:<?php echo ($altura_bloco_branco_dir-(number_format($altura_bloco_branco_dir/2*0.5, 2, '.', '.'))); ?>cm; 
		padding-top: <?php echo number_format($altura_bloco_branco_dir/2*0.5, 2, '.', '.'); ?>cm;
	}

	.principal<?php echo $pos; ?>{

		width:<?php echo ($rifa_largura*2); ?>cm; 
		background-color: transparent; 
		border-spacing: 0; 
		border:0; 
		margin:0; 
		page-break-after: always;
	}

	.inside<?php echo $pos; ?>{
		border-spacing: 0; 
		border:0; 
		margin:0; 
		height:<?php echo $altura_qr_code; ?>cm;
	}

	.area_pre_infos<?php echo $pos; ?> {
		height:<?php echo $altura_area_preinfos; ?>cm; 
		padding:0; 
		margin:0; 
		border-spacing: 0; 
		border:0;
	}
	.area_infos<?php echo $pos; ?>{
		height:<?php echo $altura_qr_code; ?>cm; 
		padding:0; 
		margin:0; 
		border-spacing: 0; 
		border:0;
	}

	.col1<?php echo $pos; ?>{
		width:<?php echo ($rifa_largura/2); ?>cm; 
		padding:0; 
		margin:0; 
		border-spacing: 0; 
		border:0;
	}
	.col2<?php echo $pos; ?>{
		width:<?php echo $distancia_bloco_branco_dir_esquerda-($rifa_largura/2); ?>cm; 
		padding:0; 
		margin:0; 
		border-spacing: 0; 
		border:0;
	}
	.col3<?php echo $pos; ?>{
		width:<?php echo $rifa_largura - ($distancia_bloco_branco_dir_esquerda-($rifa_largura/2)) - ($rifa_largura/2); ?>cm; 
		padding:0; 
		margin:0; 
		border-spacing: 0; 
		border:0;
	}

	tr { 
		margin:0; 
		padding:0; 
		border:0; 
		border-collapse:0; 
		border-spacing: 0;
	}
	
	td { 
		margin:0; 
		padding:0; 
		border:0; 
		border-collapse:0; 
		border-spacing: 0;
	}
	.grupo<?php echo $pos; ?>{
		font-family: 'Times New Roman', Arial; 
		font-size: 14pt; 
		color:black;
	}
	.milhar<?php echo $pos; ?>{
		font-family: 'Times New Roman', Arial; 
		font-size: 14pt; 
		color:red;
	}

	.qr<?php echo $pos; ?>{
		width:<?php echo $largura_qr_code; ?>cm; 
		height:<?php echo $altura_qr_code; ?>cm;
	}

</style>