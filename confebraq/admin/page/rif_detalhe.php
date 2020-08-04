<?php

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

$sql_comissaototal = "
SELECT  count(*) as Vendas, count(*) * ((SELECT comissao 
	FROM tbl_revendedor 
	WHERE usu_cod = (select comp_revendedor from tbl_compra where comp_cod = bil_compra) 
	AND rifa_cod = '$codigo')/100) as ComissaoTotal, 
(select comp_revendedor from tbl_compra where comp_cod = bil_compra) as rev
from tbl_bilhetes 
where bil_rifa = '$codigo' 
and bil_compra in (select comp_cod from tbl_compra where comp_revendedor is not null) group by rev";
$qr_comissao = $mysqli->query($sql_comissaototal) or die($mysqli->error);
$fe_comissao = $qr_comissao->fetch_assoc();

$comissaoTotal = 0;
$nVendasRevenda = 0;
do{
	$comissaoUsuario[$fe_comissao[rev]] = $fe_comissao[ComissaoTotal]*$rifa[rifa_valorbilhete];
	$comissaoTotal += $fe_comissao[ComissaoTotal]*$rifa[rifa_valorbilhete];
	$nVendasRevenda += $fe_comissao[Vendas];
}while($fe_comissao = $qr_comissao->fetch_assoc());


$lucro = $lucro - $comissaoTotal - $tarifaps;



?><div class="col-lg-12 text-left">
<h3>Detalhes da Rifa</h3>
<hr>
</div>
<div class="col-lg-6 text-left">
	<div class="col-lg-12">
		<p>Detalhes</p>
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
				<td style="font-weight:bold">Comissão dos Vendedores</td>
				<td>R$ <?php echo number_format($comissaoTotal, 2, ',', '.'); ?></td>
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
				<td style="font-weight:bold">Seu Lucro</td>
				<td>R$ <?php echo number_format($lucro, 2, ',', '.'); ?></td>
			</tr>
		</table>
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
				<p>Revenda</p>
				<?php

				$sql_rev = "SELECT *, (select usu_nome from tbl_usuario where usu_cod in (select comp_revendedor from tbl_compra where comp_cod = bil_compra)) as Usuario, (select usu_cod from tbl_usuario where usu_cod in (select comp_revendedor from tbl_compra where comp_cod = bil_compra)) as UsuarioCod,
(select comissao FROM tbl_revendedor where rifa_cod = bil_rifa AND usu_cod = UsuarioCod)/100 as Comissao
FROM tbl_bilhetes WHERE bil_rifa = '$codigo' AND bil_compra IN(select comp_cod from tbl_compra where comp_revendedor is not null) ORDER BY `bil_cod` DESC  ";
				$qr_rev = $mysqli->query($sql_rev) or die($mysqli->error);
				$fe_rev = $qr_rev->fetch_assoc();

				if($qr_rev->num_rows > 0){

					?>
					<table class="table table-responsive table-bordered">
						<tr style="font-weight:bold;">
							<td>Bilhete</td>
							<td>Revendedor</td>
							<td>Comissão</td>
						</tr>
						<?php 
						$revend = Array();
						do { 
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

							<p>Pagar aos Revendedores</p>	
							<table class="table table-responsive table-bordered">
								<tr style="font-weight:bold;">
									<td>Revendedor</td>
									<td>Comissão Total</td>
								</tr>
								<?php 
								foreach($revend as $rev_codigo=>$rifas_vendidas){


									?>
									<tr>
										<td><?php echo $nome[$rev_codigo]; ?></td>
										<td>R$ <?php echo number_format($comissaoUsuario[$rev_codigo], 2, ',', '.'); ?></td>
									</tr>
									<?php } ?>
								</table>

								<?php } else { ?>
								<p style="padding:10px;" class="bg-warning">Nenhum bilhete foi vendido por revenda.</p>
								<?php } ?>

								<button onclick="javascript: location.href='index.php?p=revendedor&rifa=<?php echo $codigo; ?>';" class="btn">Revendedores</button>
								<?php if($bil_vendidos > 0) { ?>
								<button onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">finalizar rifa</button>
								<?php } ?>
								<button onclick="javascript: location.href='index.php?p=editar&codigo=<?php echo $codigo; ?>';" class="btn">editar</button>
								<?php if($bil_vendidos == 0) { ?>
								<button onclick="javascript: location.href='index.php?p=deletar&codigo=<?php echo $codigo; ?>';" class="btn btn-danger">deletar</button>
								<?php }else{ ?>
								<button onclick="javascript: alert('Você não pode excluir uma rifa que já vendeu bilhetes');" class="btn btn-danger">deletar</button>
								<?php } ?>
							</div>