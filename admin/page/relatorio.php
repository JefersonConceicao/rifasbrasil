<?php
@session_start();
include('../../class/conexao.php');

$dado = DBSelect("Select usu_psemail, usu_mensalista, usu_pstoken from tbl_usuario WHERE usu_cod = '{$_SESSION['usuario']}'", $mysqli);
$isMENSALISTA = intval($dado['usu_mensalista']);

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Relatório de Vendas</title>
	<style>table{border-collapse: collapse;width:100%;font-family:arial;}thead{font-weight:bold;}
	</style>
</head>
<body>
	
	<h1>Carregando...</h1>
	<div style="display:none;">
	<h1>Relatório</h1>
	<table border="1" id="imp" cellpadding="5">
		<thead>
			<tr>
				<td>Venda</td>
				<td>M.Federal</td>
				<td>Campanha</td>
				<td>Vendedor</td>
				<td>Bairro/Região</td>
				<td width="40%">Cliente</td>
				
				<td align="right">Apostas</td>
			</tr>
		</thead>
		<tbody>
			<?php

			$array_de_supervisores_rifa = array();
            $array_de_supervisores_rev = array();
            $vendaDireta = 0;

           

            $qrybil = $mysqli->query($_SESSION['relatorio']) or die($mysqli->error);
    		$resbil = $qrybil->fetch_assoc();

    		

    		//unset($_SESSION['relatorio']);

    		do{
    			$array_de_supervisores_rifa[$resbil['rifa_cod']] = true;
				$array_de_supervisores_rev[$resbil['comp_revendedor']] = true;
    		} while($resbil  = $qrybil->fetch_assoc());

    		// puxa as comissoes dos revendedores
			$qrybil = $mysqli->query("SELECT * from tbl_revendedor WHERE rifa_cod IN (".implode(',', array_keys($array_de_supervisores_rifa)).") AND usu_cod IN (".implode(',', array_keys($array_de_supervisores_rev)).")") or die($mysqli->error);
		   	$resbil = $qrybil->fetch_assoc();

		   	$comissao_dos_revendedores_por_rifa = array();
		   	do {
		   		if(!isset($comissao_dos_revendedores_por_rifa[$resbil['rifa_cod']]))
		   			$comissao_dos_revendedores_por_rifa[$resbil['rifa_cod']] = array();
		   		if(!isset($comissao_dos_revendedores_por_rifa[$resbil['rifa_cod']][$resbil['usu_cod']]))
		   			$comissao_dos_revendedores_por_rifa[$resbil['rifa_cod']][$resbil['usu_cod']] = $resbil['comissao']/100;
		   	} while($resbil  = $qrybil->fetch_assoc());


    		$qrybil = $mysqli->query($_SESSION['relatorio']) or die($mysqli->error);
    		$resbil = $qrybil->fetch_assoc();

    		$countTotal = 0;
    		$countFisicas = 0;
    		$countOnline = 0;

			do{

				if(isset($resbil['comp_revendedor'])){
					
					$countTotal++;

					if(strpos($resbil['comp_serie'], '-') !== false)
						$countFisicas++;
					else
						$countOnline++;

					if(!$listaResumo[$resbil['comp_revendedor']]['count'])
                        $listaResumo[$resbil['comp_revendedor']]['count'] = 0;

                    if(!$listaResumo[$resbil['comp_revendedor']]['soma'])
                        $listaResumo[$resbil['comp_revendedor']]['soma'] = 0;

                    if(!$listaResumo[$resbil['comp_revendedor']]['comissao'])
                    	$listaResumo[$resbil['comp_revendedor']]['comissao'] = 0;

                    if(!$listaResumo[$resbil['comp_revendedor']]['repasse'])
                    	$listaResumo[$resbil['comp_revendedor']]['repasse'] = 0;


                    $listaResumo[$resbil['comp_revendedor']]['count']++;
                    $listaResumo[$resbil['comp_revendedor']]['nome'] = $resbil['revendedor'];
                    $listaResumo[$resbil['comp_revendedor']]['soma'] += $resbil['comp_valortotal'];


                    $listaResumo[$resbil['comp_revendedor']]['comissao'] += ($resbil['comp_valortotal'] * $comissao_dos_revendedores_por_rifa[$resbil['rifa_cod']][$resbil['comp_revendedor']]);
                    $listaResumo[$resbil['comp_revendedor']]['repasse'] += $resbil['comp_valortotal'] * 0.4;
				
				}else
					$vendaDireta++;

				$bil_vend = DBExecute("select b.*, r.dezena_bolao from tbl_bilhetes b, tbl_rifas r where b.bil_compra = '{$resbil['compra']}' AND r.rifa_cod = b.bil_rifa  ORDER BY b.bil_numero ASC", $mysqli);
                $bil_v = $bil_vend->fetch_assoc();
                $bilhetes = "";
                do{
                	if($bil_v['dezena_bolao'])
                		$bilhetes .= str_pad($bil_v['bil_numero'], 2, "0", STR_PAD_LEFT).", ";
                	else
                		$bilhetes .= str_pad($bil_v['bil_numero'], strlen($resbil['rifa_maxbilhetes'])-1, "0", STR_PAD_LEFT).", ";
                } while( $bil_v = $bil_vend->fetch_assoc());                    
                $bilhetes = substr($bilhetes, 0, -2);
				?>
				<tr>
					<td><?php echo $resbil['comp_cod']; ?></td>
					<td><?php echo $resbil['comp_serie']; ?></td>
					<td><?php echo $resbil['rifa_titulo'];  ?></td>
					<td><?php echo ($resbil['revendedor'] != '')? $resbil['revendedor']:"Venda Direta"; ?></td>
					<td><?php echo $resbil['usu_regiao']; ?></td>
					
					<td width="10%" align="right"><?php echo $resbil['cliente']; ?></td>
					<td width="40%">
						<?php 
						if($isMENSALISTA == 1 || $resbil['rifa_finalizar'] == 2 || $_SESSION['admin'] == 'S')
							echo $bilhetes;
						else 
							echo "Indisponível até a finalização.";
						?>
					</td>
				</tr>
			<?php } while($resbil  = $qrybil->fetch_assoc()); ?>
		</tbody>
	</table>

	<div style="height:100px; width:100%;"></div>

	<table border="1" id="tbl" cellpadding="5">
		<thead>
			<tr>
				<td>Código</td>
				<td>Nome</td>
				<td>Vendas</td>
				<td>Total</td>
				<td>Comissão</td>
				<td>Repasse</td>
				<td>Lucro</td>
			</tr>
		</thead>
		<tbody>
			<?php 

			if(is_array($listaResumo)){
				$totalComissao = 0;
				$totalRepasse = 0;
				$totalLucro = 0;
				$totalSoma = 0;
            	foreach($listaResumo as $rev=>$data){

            		$totalComissao += $data['comissao'];
            		$totalRepasse += $data['repasse'];
            		$totalSoma += $data['soma'];
            		$totalLucro += $data['soma']-$data['comissao']-$data['repasse'];
            		$totalCount += $data['count'];

            		?>
					<tr>
						<td><?= $rev; ?></td>
						<td><?= $data['nome']; ?></td>
						<td><?= $data['count']; ?></td>
						<td>R$ <?= number_format($data['soma'], 2, ',', '.'); ?></td>
						<td>R$ <?= number_format($data['comissao'], 2, ',', '.'); ?></td>
						<td>R$ <?= number_format($data['repasse'], 2, ',', '.'); ?></td>
						<td>R$ <?= number_format($data['soma']-$data['comissao']-$data['repasse'], 2, ',', '.'); ?></td>
					</tr>
					<?php } ?>

					<tr><td colspan="5"></td></tr>
					
					<tr>
						<td><b>Total</b></td>
						<td></td>
						<td><?= $totalCount; ?></td>
						<td>R$ <?= number_format($totalSoma, 2, ',', '.'); ?></td>
						<td>R$ <?= number_format($totalComissao, 2, ',', '.'); ?></td>
						<td>R$ <?= number_format($totalRepasse, 2, ',', '.'); ?></td>
						<td>R$ <?= number_format($totalLucro, 2, ',', '.'); ?></td>
					</tr>

				<?php
				} ?>

			<?php if($vendaDireta > 0) { ?>
			<tr>
				<td colspan="2">Vendas Diretas</td>
				<td><?= $vendaDireta; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>

	</div>
	

	<!--<script src="http://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>-->
	<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js" integrity="sha384-THVO/sM0mFD9h7dfSndI6TS0PgAGavwKvB5hAxRRvc0o9cPLohB0wb/PTA7LdUHs" crossorigin="anonymous"></script>

	<script src="//rifasbrasil.com.br/js/script.js"></script>

	<script>



	var doc = new jsPDF('l', 'pt', 'a4');



	window.onload = function(){

		var el = "<?php if(intval($_GET['tipo']) == 1) echo "tbl"; else echo "imp"; ?>";

		doc.setFontSize(12);
		doc.text(40, 30, "<?= "Total de Apostas: $countTotal | Apostas Online: $countOnline | Apostas Físicas: $countFisicas"; ?>");

		var res = doc.autoTableHtmlToJson(document.getElementById(el));
	    doc.autoTable(res.columns, res.data, {
	    	showHead: 'firstPage',
	      	startY: 60,
	      	columnStyles: {
			    0: {columnWidth: 50},
			    1: {columnWidth: 50},
			    2: {columnWidth: 150},
			    3: {columnWidth: 60},
			    4: {columnWidth: 100},
			    5: {columnWidth: 150},
			    6: {columnWidth: 200}
			    // etc
			},
	      	styles: {
	    		fontSize: 8,
	    		overflow: "linebreak"
	    	}
	    });
	    //return doc;

	    //doc.autoTable(columns, rows, {
	    //  theme: "grid",
	    //  margin: 10,
	    //  styles: {
	    //    font: "courier",
	    //    fontSize: 12,
	    //    // overflow: "linebreak",
	    //    rowHeight: 8,
	    //    cellPadding: 1,
	    //    halign: "left"
	    //  }
	    //});
	    doc.save();

	}

	</script>

</body>
</html>