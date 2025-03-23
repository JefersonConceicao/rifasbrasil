<?php

if($_POST['deletar']){
	include("../../class/conexao.php");
	$mysqli->query("delete from tbl_custos where codigo = '".intval($_POST['deletar'])."'");
	die();
}

if($_POST['get_custos']){

	include("../../class/conexao.php");
	$rifa = intval($_POST['get_custos']);

	if($_POST['vendedor'] && $_POST['vendedor'] > 0)
		$sql_add = " and vendedor = '".intval($_POST['vendedor'])."'";
	else
		$sql_add = " and (vendedor is null or vendedor = '' or vendedor <= 0)";

	$sql_valor = "SELECT * from tbl_custos where rifa = '$rifa' ".$sql_add;
	$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);
	$data = $sql_exe->fetch_assoc();

	$retorno = array();

	if($sql_exe->num_rows > 0)
	do{
		$data['data'] = date('d/m/Y', strtotime($data['data']));
		$retorno[] = $data;
	}while($data = $sql_exe->fetch_assoc());

	$sql_valor = "SELECT sum(valor) as valor from tbl_custos where rifa = '$rifa' and (vendedor is null or vendedor = '' or vendedor <= 0)";
	$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);
	$data = $sql_exe->fetch_assoc();

	$sql_comissaototal = "SELECT  count(*) as Vendas, count(*) * 
	(
		(SELECT comissao 
			FROM tbl_revendedor 
			WHERE usu_cod = (
				select comp_revendedor from tbl_compra where comp_cod = bil_compra
			) 
			AND rifa_cod = '$codigo'
			limit 1
		)/100
	) as ComissaoTotal, 
	(select comp_revendedor from tbl_compra where comp_cod = bil_compra) as rev
	from tbl_bilhetes
	where bil_rifa = '$codigo' 
	and bil_compra in (select comp_cod from tbl_compra where comp_revendedor is not null and (comp_status_revenda != 1 and comp_situacao != 3 and comp_situacao != 4)) group by rev";
	$qr_comissao = $mysqli->query($sql_comissaototal) or die($mysqli->error);
	$fe_comissao = $qr_comissao->fetch_assoc();

	$comissaoTotalFinal  = 0;
	do{

		$comissaoTotalFinal += $fe_comissao['ComissaoTotal']*$rifa['rifa_valorbilhete'];

	}while($fe_comissao = $qr_comissao->fetch_assoc());


	die(json_encode(array('res'=>$retorno, 'total'=>floatval($data['valor']), 'nconfirmada'=>$comissaoTotalFinal)));

}

if($_POST['valor']){

	include("../../class/conexao.php");

	$_POST['valor'] = floatval(trim(str_replace('R$', '', str_replace(',', '.', str_replace('.', '', $_POST['valor'])))));
	$_POST['descricao'] = $mysqli->escape_string($_POST['descricao']);
	$_POST['rifa']  = intval($_POST['rifa']);
	$_POST['vendedor']  = intval($_POST['vendedor']);
	$_POST['data']  = implode('-', array_reverse(explode('/', $_POST['data'])));
 
	$mysqli->query("INSERT INTO tbl_custos (vendedor, descricao, valor, data, rifa) values('{$_POST['vendedor']}', '{$_POST['descricao']}', '{$_POST['valor']}', '{$_POST['data']}', '{$_POST['rifa']}')");

	die(json_encode(array('insert'=>$mysqli->insert_id)));
	
}

$codigo = intval($_GET['codigo']);

include("../class/conexao.php");



$config = DBSelect("SELECT par_ccaridade/100 as caridade, par_crb/100 as rifasbrasil FROM tbl_parametro where par_codigo = '1'", $mysqli);

$sql_valor = "SELECT * FROM tbl_rifas WHERE rifa_cod = '$codigo'";
$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);
$rifa = $sql_exe->fetch_assoc();

// A consulta abaixo verifica quais rifas já foram vendidas
$sqlBil = "SELECT count(*) as t FROM tbl_bilhetes WHERE bil_situacao != 'C' and bil_rifa = '$codigo'";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();

//Consulta as comissões de cada revendedor
$sql_com = DBExecute("SELECT * FROM tbl_revendedor r where r.rifa_cod = '$codigo'", $mysqli);
$comissao = $sql_com->fetch_assoc();

do{
	$com[$comissao[usu_cod]] = $comissao[comissao]/100;

}while($comissao = $sql_com->fetch_assoc());

$sql_rev = "SELECT count(*) as t FROM tbl_bilhetes WHERE bil_situacao = 'V' AND bil_rifa = '$codigo' and bil_compra IN (select comp_cod from tbl_compra where comp_revendedor is not null)";
$qr_rev = $mysqli->query($sql_rev) or die($mysqli->error);
$fe_rev = $qr_rev->fetch_assoc();

$bil_vendidos = $bil[t];
$valor_bilhete = $rifa[rifa_valorbilhete];
$subtotal = $valor_bilhete * $bil_vendidos;
$total = $subtotal*($config[rifasbrasil] + $config[caridade]);
$tarifar = $subtotal*$config[rifasbrasil];
$tarifaps = ($subtotal * ($config[rifasbrasil] + $config[caridade]) * 0.05);
$tarifac = $subtotal * $config[caridade];
$lucro = $subtotal*(1-($config[rifasbrasil] + $config[caridade]));

if($lucro > 0.5)
	$tarifaps += 0.5;

$sql_comissaototal = "SELECT  count(*) as Vendas, count(*) * 
(
	(SELECT comissao 
		FROM tbl_revendedor 
		WHERE usu_cod = (
			select comp_revendedor from tbl_compra where comp_cod = bil_compra
		) 
		AND rifa_cod = '$codigo'
		limit 1
	)/100
) as ComissaoTotal, 
(select comp_revendedor from tbl_compra where comp_cod = bil_compra) as rev
from tbl_bilhetes
where bil_rifa = '$codigo' 
and bil_compra in (select comp_cod from tbl_compra where comp_revendedor is not null and (comp_status_revenda = 1 or comp_situacao = 3 or comp_situacao = 4)) group by rev";
$qr_comissao = $mysqli->query($sql_comissaototal) or die($mysqli->error);
$fe_comissao = $qr_comissao->fetch_assoc();

$cont = db_select($mysqli, "select usu_mensalista from tbl_usuario where usu_cod = '".$_SESSION['usuario']."'", 1);
$is_mensalista = $cont['usu_mensalista'];

$desc = $mysqli->query("SELECT sum(comp_desconto) as desconto from tbl_compra where comp_revendedor is not null and comp_cod in (select bil_compra from tbl_bilhetes where bil_rifa = '$codigo') and (comp_status_revenda = 1 or comp_situacao = 3 or comp_situacao = 3)") or die($mysqli->error);
$desconto = $desc->fetch_assoc();

$comissaoTotal         = 0;
$nVendasRevenda        = 0;
$comissaoNaoConfirmada = 0;
$comissaoTotalFinal  = 0;
do{

	$comissaoTotalFinal += $fe_comissao['ComissaoTotal']*$rifa['rifa_valorbilhete'];

	if($fe_comissao['comp_status_revenda'] == 1 || $fe_comissao['comp_status_revenda'] == 3 || $fe_comissao['comp_status_revenda'] == 4){

		$comissaoUsuario[$fe_comissao['rev']] = $fe_comissao['ComissaoTotal']*$rifa['rifa_valorbilhete'];

		$comissaoTotal += $fe_comissao['ComissaoTotal']*$rifa['rifa_valorbilhete'];
		$nVendasRevenda += $fe_comissao['Vendas'];

		if(!$comissaoRecebida[$fe_comissao['rev']]) $comissaoRecebida[$fe_comissao['rev']] = 0;
		$comissaoRecebida[$fe_comissao['rev']] += $fe_comissao['Vendas'];

	}else
		$comissaoNaoConfirmada += $fe_comissao['ComissaoTotal']*$rifa['rifa_valorbilhete'];
	

}while($fe_comissao = $qr_comissao->fetch_assoc());


$sql_comissaototal = "SELECT  count(*) as Vendas, count(*) * 
(
	(SELECT comissao 
		FROM tbl_revendedor 
		WHERE usu_cod = (
			select comp_revendedor from tbl_compra where comp_cod = bil_compra
		) 
		AND rifa_cod = '$codigo'
		limit 1
	)/100
) as ComissaoTotal, 
(select comp_revendedor from tbl_compra where comp_cod = bil_compra) as rev
from tbl_bilhetes
where bil_rifa = '$codigo' 
and bil_compra in (select comp_cod from tbl_compra where comp_revendedor is not null and (comp_status_revenda != 1 and comp_situacao != 3 and comp_situacao != 4)) group by rev";
$qr_comissao2 = $mysqli->query($sql_comissaototal) or die($mysqli->error);
$fe_comissao2 = $qr_comissao2->fetch_assoc();
$comissaoAReceber = 0;
do{
	$areceber[$fe_comissao2['rev']] = $fe_comissao2[ComissaoTotal]*$rifa[rifa_valorbilhete];
	$comissaoAReceber += $fe_comissao2[ComissaoTotal]*$rifa[rifa_valorbilhete];
	if(!$comissaoAReceberNum[$fe_comissao2['rev']]) $comissaoAReceberNum[$fe_comissao2['rev']] = 0;
	$comissaoAReceberNum[$fe_comissao2['rev']] += $fe_comissao2[Vendas];
}while($fe_comissao2 = $qr_comissao2->fetch_assoc());





?><div class="col-lg-12 text-left">
<h3>Detalhes da Rifa</h3>
<hr>
</div>
<div class="col-lg-6 text-left">
	<div class="col-lg-12">
		<p>Detalhes</p>
		<?php if(!$is_mensalista){

			$lucro = $lucro - $comissaoTotal - $tarifaps;
			?>
		<table class="table table-bordered">
			<tr>
				<td style="font-weight:bold">Vendas diretas</td>
				<td><?php echo $bil_vendidos-$nVendasRevenda; ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Vendas por Revenda</td>
				<td><?php echo $nVendasRevenda; ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Valor do bilhete</td>
				<td>R$ <?php echo number_format($valor_bilhete, 2, ',', '.'); ?></td>
			</tr>
			
			<tr>
				<td style="font-weight:bold">Subtotal</td>
				<td>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
			</tr>


			<tr>
				<td style="font-weight:bold">Comissão dos Vendedores Confirmada</td>
				<td>R$ <?php echo number_format($comissaoTotal-$desconto['desconto'], 2, ',', '.'); ?></td>
			</tr>

			<tr>
				<td style="font-weight:bold">Comissão dos Vendedores à Receber</td>
				<td>R$ <?php echo number_format($comissaoAReceber, 2, ',', '.'); ?></td>
			</tr>
		
			<tr>
				<td style="font-weight:bold">Tarifa RifasBrasil (<?php echo intval($config['rifasbrasil']*100).'%'; ?>)</td>
				<td>R$ <?php echo number_format($tarifar, 2, ',', '.'); ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Tarifa Pagseguro</td>
				<td>R$ <?php echo number_format($tarifaps, 2, ',', '.'); ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Doação (<?php echo intval($config['caridade']*100).'%'; ?>)</td>
				<td>R$ <?php echo number_format($tarifac, 2, ',', '.'); ?></td>
			</tr>

			<tr class="bg-success">
				<td style="font-weight:bold">Seu Saldo Não Confirmado</td>
				<td>R$ <span id="lucro_nao_confirmado"><?php echo number_format($comissaoTotalFinal, 2, ',', ''); ?></span></td>
			</tr>

			<tr class="bg-success">
				<td style="font-weight:bold">Seu Saldo Confirmado</td>
				<td>R$ <span id="lucro"><?php echo number_format($lucro, 2, ',', ''); ?></span></td>
			</tr>
		
			
		</table>
		<?php }else{

			$lucro = $subtotal - (($comissaoTotal-$desconto['desconto']) + $comissaoAReceber);

			?>
		<table class="table table-bordered">
			<tr>
				<td style="font-weight:bold">Vendas diretas</td>
				<td><?php echo $bil_vendidos-$nVendasRevenda; ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Vendas por Revenda</td>
				<td><?php echo $nVendasRevenda; ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Valor do bilhete</td>
				<td>R$ <?php echo number_format($valor_bilhete, 2, ',', '.'); ?></td>
			</tr>
			
			<tr>
				<td style="font-weight:bold">Subtotal</td>
				<td>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
			</tr>
			<tr>
				<td style="font-weight:bold">Comissão dos Vendedores Confirmada</td>
				<td>R$ <?php echo number_format($comissaoTotal-$desconto['desconto'], 2, ',', '.'); ?></td>
			</tr>

			<tr>
				<td style="font-weight:bold">Total de Descontos nas Comissões</td>
				<td>R$ <?php echo number_format($desconto['desconto'], 2, ',', '.'); ?></td>
			</tr>
		
			<tr>
				<td style="font-weight:bold">Comissão dos Vendedores à Receber</td>
				<td>R$ <?php echo number_format($comissaoAReceber, 2, ',', '.'); ?></td>
			</tr>
			
			<tr>
				<td style="font-weight:bold">Despesas Totais</td>
				<td>R$ <?php echo number_format(($comissaoTotal-$desconto['desconto']) + $comissaoAReceber, 2, ',', '.'); ?></td>
			</tr>

			<tr>
				<td style="font-weight:bold">Custos Totais</td>
				<td id="custos_totais"></td>
			</tr>

			<tr class="bg-success">
				<td style="font-weight:bold">Seu Lucro</td>
				<td>R$ <span id="lucro"><?php   echo number_format($lucro, 2, ',', ''); ?></span></td>
			</tr>
		
			
		</table>
		<?php } ?>
			
		<div class="form-group">
			<button type="button" onclick="abrir_modal(false);" class="btn btn-xs btn-danger">Adicionar Custo</button>
		</div>

		


		<?php if($rifa[rifa_finalizar] > 1){ ?>
		
		<p>Vencedor(es)</p>
		<table class="table table-responsive table-bordered table-stripped">
			<tr style="font-weight:bold;">
				<td>Vencedor</td>
				<td>Prêmio</td>
				<td>Nº Bilhete</td>
			</tr>
			<?php

			if($rifa[rifa_premios] == 1 ){

				$vencedor = DBSelect("SELECT usu_nome as v, usu_email, usu_celular FROM tbl_usuario where usu_cod = '$rifa[rifa_vencedor]'", $mysqli);
				
				?>
				<tr>
					<td><?php if(strlen($vencedor[v]) > 0) echo "$vencedor[v] ($vencedor[usu_email] - $vencedor[usu_celular])"; else echo "Sem ganhador"; ?></td>
					<td><?php echo $rifa[pre_descricao]; ?></td>
					<td><?php echo str_pad($rifa[rifa_bilhetepremiado], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT); ?></td>
				</tr>
				<?php }
				if($rifa[rifa_premios] > 1){ 

					$exec =  DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$codigo' ORDER BY pre_ranking ASC", $mysqli);
					$premio = $exec->fetch_assoc();

					do{

						$vencedor = DBSelect("SELECT usu_nome as v, usu_email, usu_celular FROM tbl_usuario where usu_cod = '$premio[pre_vencedor]'", $mysqli);

						?>
						<tr>
							<td>
								<?php if(strlen($vencedor[v]) > 0) 
								echo $premio[pre_ranking]."º: $vencedor[v] ($vencedor[usu_email] - $vencedor[usu_celular])";
								else
								echo $premio[pre_ranking]."º: Sem ganhador"; ?></td>
							<td><?php echo $premio[pre_descricao]; ?></td>
							<td><?php echo  str_pad( $premio[pre_bilhete], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT); ?></td>
						</tr>
						<?php } while($premio = $exec->fetch_assoc()); } ?>
					</table>
					<?php } ?>
				</div>
				
			</div>
			<div class="col-lg-6">

				<div class="col-lg-12 text-center" id="div_link">
					<p>Extrato oculto. <a href="javascript: void(0);" onclick="toggleExtrato();">Clique aqui</a> para exibir.</p>
					<hr>
				</div>

				<div class="hidden" id="div_extrato">
				<p>Revenda <small><a href="javascript:void(0);" onclick="toggleExtrato();">(ocultar)</a></small></p>
				<?php

				$sql_rev = "SELECT tbl_bilhetes.*, tbl_compra.comp_desconto, 
					(select usu_nome 
					from tbl_usuario 
					where usu_cod in 
						(select comp_revendedor 
							from tbl_compra 
							where comp_cod = bil_compra
						)
					) as Usuario, 
					(select usu_cod 
						from tbl_usuario 
						where usu_cod in 
						(
							select comp_revendedor 
							from tbl_compra 
							where comp_cod = bil_compra
						)
					) as UsuarioCod,
					(select comissao FROM tbl_revendedor where rifa_cod = bil_rifa AND usu_cod = UsuarioCod limit 1)/100 as Comissao
					FROM tbl_bilhetes, tbl_compra WHERE bil_rifa = '$codigo'  AND bil_compra IN(select comp_cod from tbl_compra where comp_revendedor is not null) and comp_cod = bil_compra 
					
					ORDER BY bil_compra DESC  ";
				$qr_rev = $mysqli->query($sql_rev) or die($mysqli->error);
				$fe_rev = $qr_rev->fetch_assoc();

				$compra_atual = -1;
				$desconto = array();

				if($qr_rev->num_rows > 0){

					?>
					<table class="table table-responsive table-bordered ">
						<tr style="font-weight:bold;">
							<td>Bilhete</td>
							<td>Revendedor</td>
							<td>Comissão</td>
						</tr>
						<?php 
						$revend = Array();
						do { 

							if( $fe_rev['bil_compra'] != $compra_atual){
								if(!$desconto[$fe_rev['UsuarioCod']]) $desconto[$fe_rev['UsuarioCod']] = 0;
								$desconto[$fe_rev['UsuarioCod']] += $fe_rev['comp_desconto'];
								$compra_atual = $fe_rev['bil_compra'];
							} 

							$revend[$fe_rev[UsuarioCod]]++;
							$nome[$fe_rev[UsuarioCod]] = $fe_rev[Usuario];

							?>
						<tr>
							<td><?php echo  str_pad($fe_rev[bil_numero], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT); ?></td>
							<td><?php echo $fe_rev[Usuario]; ?></td>
							<td>R$ <?php echo number_format($fe_rev[Comissao]*$rifa[rifa_valorbilhete], 2, ',', '.'); ?></td>
						</tr>
						<?php } while($fe_rev = $qr_rev->fetch_assoc()); ?>
							</table>
					</div>

							<p>Pagar aos Revendedores</p>	
							<table class="table table-bordered">
								<tr style="font-weight:bold;">
									<td>Revendedor</td>
									<td>Comissão Confirmada</td>
									<td>Comissão À Receber</td>
								</tr>
								<?php 
								foreach($revend as $rev_codigo=>$rifas_vendidas){ ?>
									<tr>
										<td><?php echo $nome[$rev_codigo]; ?></td>
										<td>
											R$ <span id="comissao_vendedor_<?= $rev_codigo; ?>"><?php echo number_format($comissaoUsuario[$rev_codigo], 2, ',', ''); ?></span>
											<span class="hidden" id="comissao_original_<?= $rev_codigo; ?>"><?= ($comissaoUsuario[$rev_codigo]); ?></span>
											<?php if($desconto[$rev_codigo]){ ?><small>Desconto: R$ <?= $desconto[$rev_codigo]; ?></small><?php } ?>
											<br><?= $comissaoRecebida[$rev_codigo]; ?> vendas
											<div class="form-group">
												<button type="button" onclick="abrir_modal(<?= $rev_codigo; ?>);" class="btn btn-xs btn-danger">Vale/Repasse</button>
											</div>
										</td>
										<td>R$ <?= number_format( $areceber[$rev_codigo] , 2, ',', '.'); ?> 
											<br><?= $comissaoAReceberNum[$rev_codigo] ?> vendas</td>
									</tr>
									<?php } ?>
								</table>

								<?php  } else { ?>
								<p style="padding:10px;" class="bg-warning">Nenhum bilhete foi vendido por revenda.</p>
								<?php } ?>

								<button onclick="javascript: location.href='index.php?p=revendedor&rifa=<?php echo $codigo; ?>';" class="btn">Revendedores</button>
								<?php if($bil_vendidos > 0) { ?>
								<button onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">finalizar rifa</button>
								<?php } ?>
								<button onclick="javascript: location.href='index.php?p=cadastrar&codigo=<?php echo $codigo; ?>';" class="btn">editar</button>
								<?php if($bil_vendidos == 0) { ?>
								<button onclick="javascript: location.href='index.php?p=deletar&codigo=<?php echo $codigo; ?>';" class="btn btn-danger">deletar</button>
								<?php }else{ ?>
								<button onclick="javascript: alert('Você não pode excluir uma rifa que já vendeu bilhetes');" class="btn btn-danger">deletar</button>
								<?php } ?>
							</div>

<!-- Modal -->
<div id="detalhes" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Visualizar Gastos</h4>
      </div>
      <div class="modal-body" id="content_custos">
        <div class="col-lg-12" id="custos"></div>
        <div class="clearfix"></div>
        <div class="col-lg-12 text-center" id="total_custos"></div>
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
      	<button type="button" onclick="printElem();" class="btn btn-primary">Imprimir</button>
        <button type="button" class="btn pull-right btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div>

  </div>
</div>
<style>
	.modal-footer{
		text-align: left;
	}
</style>
<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Adicionar Custo</h4>
      </div>
      <div class="modal-body">
      	<div class="form-group col-lg-5">
      		<label for="">Descrição</label>
      		<input type="hidden" id="usuario" value="">
      		<input type="text" name="cus_descricao" id="cus_descricao" class="form-control">
      	</div>
      	<div class="form-group col-lg-4">
      		<label for="">Data</label>
      		<input type="text" name="cus_data" placeholder="01/01/18" id="cus_data" class="data form-control">
      	</div>
      	<div class="form-group col-lg-3">
      		<label for="">Valor</label>
      		<input type="text" name="cus_valor" id="cus_valor" class="dinheiro form-control">
      	</div>

      	<div class="clearfix"></div>
      </div>
      <div class="modal-footer text-left">
      	<button id="lancar_custo" type="button" class="btn btn-danger">Relatório</button>
        <button type="button" onclick="lancar_custo();"  class="btn pull-right btn-success" >Salvar</button>
      </div>
    </div>

  </div>
</div>

<script>

	function printElem() {

			
		$('#custos table').attr('border', 1).attr('width', '100%');
	    var content = document.getElementById('content_custos').innerHTML;

	    
	    var mywindow = window.open('', 'Print', 'height=600,width=800');

	    mywindow.document.write('<html><head><title>Print</title>');
	    mywindow.document.write('</head><body ><h1>Relatório da Rifa <?php echo $rifa['rifa_titulo']; ?></h1>');
	    mywindow.document.write(content);
	    mywindow.document.write('</body></html>');

	    mywindow.document.close();
	    mywindow.focus()
	    mywindow.print();
	    mywindow.close();
	    return true;
	}

	function visualizar_gastos(vendedor){
		$('#myModal').modal('hide');
		$('#custos').html('');
		atualizar_tabela(vendedor);
		$('#detalhes').modal('show');
		
	}

	function abrir_modal(rev = false){

		if(rev == false)
			rev = -1;

		if(rev != false && parseInt(rev) > 0)
			$('#usuario').val(rev);
		else
			$('#usuario').val('-1');

		console.log('usuario definido: ' + rev);

		$('#lancar_custo').off();

		$('#lancar_custo').on('click', function(){
			console.log('visualizar bindado para vendedor: ' + rev);
			visualizar_gastos(rev);
		});

		$('#myModal').modal('show');

	}
	
	function lancar_custo(){


		var descricao = $('#cus_descricao');
		var valor     = $('#cus_valor');
		var data      = $('#cus_data');
		var vendedor  = $('#usuario').val();
		var rifa      = <?= intval($codigo); ?>;

		if(!vendedor || vendedor < 0)
			vendedor = -1;


		if(valor && rifa > 0){

			show_loading();
			$.post('page/rif_detalhe.php', {descricao:descricao.val(), vendedor:vendedor, valor:valor.val(), data:data.val(), rifa:rifa})
			.done(function(r){

				console.log(r);
				descricao.val('');
				valor.val('');
				data.val('');
				$('#myModal').modal('hide');
				end_loading();
				visualizar_gastos(vendedor);


			})
			.fail(function(r){
				console.log(r);
				end_loading();
			});

		}

	}

	function deletar_custo(cod, btn){



		$(btn).parent().parent().remove();
		$.post('page/rif_detalhe.php', {deletar:cod})
		.done(function(r){
			console.log(r);
			var v = $('#usuario').val();
			atualizar_tabela(v);
		})
		.fail(function(r){
			console.log(r);
		});
	}

	function toggleExtrato(){

		$('#div_extrato').toggleClass('hidden');
		$('#div_link').toggleClass('hidden');

	}

	function atualizar_tabela(v){

		var rifa      = <?= intval($codigo); ?>;
		var vendedor  = -1;

		if(v > 0)
			vendedor = v;

		var content_place = $('#custos');
		content_place.html('');
		show_loading();

		var total;

		if(v > 0)
			total = parseFloat($('#comissao_original_' + v).html());
		else
			total = parseFloat(<?= $lucro ;?>);

		console.log("PEGANDO GASTOS DO VENDEDOR " + vendedor);

		$.post('page/rif_detalhe_backup.php', {get_custos:rifa, vendedor:vendedor})
		.done(function(r){
			console.log(r);
			r = JSON.parse(r);
			content_place.html('');
			end_loading();

			if(!r.res)
				return;

			console.log("TAMANHO:" + r.res.length);

			$('#custos_totais').html('R$ ' + r.total);
			$('#lucro_nao_confirmado').html(r.nconfirmada);
			console.log(r);

			if(r.res.length > 0){

				var subtotal_custos = 0.00;
				
				html = '<table class="table table-bordered">\
					<thead>\
						<tr>\
							<td>Descrição</td>\
							<td>Valor</td>\
							<td>Data</td>\
							<td></td>\
						</tr>\
					</thead>\
				<tbody>';

				for(var k = 0; k < r.res.length; k++){
					subtotal_custos+= parseFloat(r.res[k].valor);
					total -= r.res[k].valor;
					html += ("<tr><td>" + r.res[k].descricao + "</td><td>" + r.res[k].valor + "</td><td>" + r.res[k].data 
						+ "</td><td><button class=\"btn btn-danger btn-xs\" type=\"button\" onclick=\"deletar_custo("+r.res[k].codigo+", this);\">DEL</button></td></tr>");

				}

				$('#total_custos').html('TOTAL: R$ ' + subtotal_custos.toFixed(2));

				html+= ('</tbody></table>');

				content_place.html(html);

				if(vendedor > 0){
					$('#comissao_vendedor_' + v).html(total.toFixed(2));
				}else
					$('#lucro').html(total.toFixed(2));
				
			}else{
				$('#total_custos').html('');
				$('#custos').html("<p>Nenhum gasto foi Lançado</p>");
				if(vendedor > 0)
					$('#comissao_vendedor_' + v).html(total.toFixed(2));
				else
					$('#lucro').html(total.toFixed(2));
			}

		})
		.fail(function(r){
			console.log('Deu erro!');
			console.log(r);
			end_loading();
		});

	}
	

</script>