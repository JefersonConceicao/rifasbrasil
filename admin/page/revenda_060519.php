<?php
include("../class/conexao.php");
$erro = Array();
$rev_code = "SELECT * from tbl_rifas WHERE rifa_finalizar is null and rifa_vencedor is null and rifa_cod in(select rifa_cod FROM tbl_revendedor WHERE usu_cod = '{$_SESSION['usuario']}')";
$rev_query = $mysqli->query($rev_code) or die($mysqli->error);
$rev = $rev_query->fetch_assoc();
$rev_num = $rev_query->num_rows;

?>
<div class="col-lg-12 text-left">
	<h3>Minhas Revendas</h3>
	<hr>
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12 bg-warning">
		<?php foreach($erro as $msg) echo "<p>$msg</p>"; ?>
		<div class="spacer"></div>
	</div>
	<?php } ?>
</div>
<div class="col-lg-12 text-left">
	<?php if($rev_num == 0) echo "<div class=\"text-center\">No momento você não pode revender nenhuma rifa.</div>"; else { ?>
	
	<table class="table table-hover table-bordered">
		<tr style="font-weight:bold;">
			<td>Rifa</td>
			<td>Valor do Bilhete</td>
			<td>Vendas</td>	
			<td>Comissão Confirmada</td>
			<td>Comissão a Confirmar</td>	
			
			<td  width="30%"></td>
		</tr>
		<?php do {	
			$comissao = DBSelect("Select comissao as c from tbl_revendedor where usu_cod = '{$_SESSION['usuario']}' AND rifa_cod = '{$rev['rifa_cod']}'", $mysqli, 'c');
			if($_SESSION['admin'] == 'S'){
				$ven_code = "SELECT count(*) as c 
				FROM tbl_bilhetes
				 WHERE bil_rifa = '{$rev['rifa_cod']}' 
				 AND bil_compra IN(Select comp_cod from tbl_compra)";
				$dono_code = "SELECT usu_nome FROM tbl_usuario WHERE usu_cod IN (SELECT rifa_dono FROM tbl_rifas WHERE rifa_cod = '{$rev['rifa_cod']}')";
				$dono_q = $mysqli->query($dono_code) or die($mysqli->error);
				$dono = $dono_q->fetch_assoc();
				$ven_q = $mysqli->query($ven_code) or die($mysqli->error);
				$ven = $ven_q->fetch_assoc();
			}else{
				$ven_confirmada_code = "SELECT count(*) as c 
				FROM tbl_bilhetes WHERE bil_rifa = '{$rev['rifa_cod']}' AND bil_compra
				IN(Select comp_cod from tbl_compra where comp_revendedor = '{$_SESSION['usuario']}' and (comp_status_revenda = 1 or comp_situacao = 3 or comp_situacao = 4))";
				
				$ven_nao_confirmada_code = "SELECT count(*) as c 
				FROM tbl_bilhetes WHERE bil_rifa = '{$rev['rifa_cod']}' AND bil_compra
				IN(Select comp_cod from tbl_compra where comp_revendedor = '{$_SESSION['usuario']}' and (comp_status_revenda != 1 and comp_situacao != 3 and comp_situacao != 4))";
 
				$ven_q = $mysqli->query($ven_confirmada_code) or die($mysqli->error);
				$ven = $ven_q->fetch_assoc();
				$ven_nao_confirmada_code = "SELECT count(*) as c 
				FROM tbl_bilhetes WHERE bil_rifa = '{$rev['rifa_cod']}' AND bil_compra
				IN(Select comp_cod from tbl_compra where comp_revendedor = '{$_SESSION['usuario']}' and (comp_status_revenda != 1 and comp_situacao != 3 and comp_situacao != 4))";
 
				$ven_q2 = $mysqli->query($ven_nao_confirmada_code) or die($mysqli->error);
				$ven2 = $ven_q2->fetch_assoc();
				$desc = $mysqli->query("SELECT sum(comp_desconto) as desconto from tbl_compra where comp_revendedor = '{$_SESSION['usuario']}' and comp_cod in (select bil_compra from tbl_bilhetes where bil_rifa = '{$rev['rifa_cod']}') and (comp_status_revenda = 1 or comp_situacao = 4 or comp_situacao = 3)") or die($mysqli->error);
				$desconto = $desc->fetch_assoc();
			}
		if(strtotime($rev['rifa_dtsorteio']." 18:39:59") > time()){
		?>
		<tr>
			<td><?php echo $rev['rifa_titulo']; ?></td>
			<td>R$ <?php echo number_format($rev['rifa_valorbilhete'],2,',','.'); ?> (Comissão: <?php echo $comissao; ?>%)</td>
			<td><?php echo $ven['c']; ?></td>	
			<td>R$ <?php echo number_format(($ven['c']*$rev['rifa_valorbilhete']*($comissao/100)) - floatval($desconto['desconto']) ,2,',','.'); ?></td>
			<td>R$ <?php echo number_format($ven2['c']*$rev['rifa_valorbilhete']*($comissao/100),2,',','.'); ?></td>
			
			<td class="text-right">
				<?php if($ven['c'] == 0) { ?>
				<!--<form method="post" action="">
					<input name="rifa" value="<?php echo $rev['rifa_cod']; ?>" type="hidden">
					<input name="usuario" value="<?php echo $_SESSION['usuario']; ?>" type="hidden">
					<button type="submit" class="btn btn-danger">remover</button>
				</form>-->
				<?php }else{ ?>
				
				<?php } ?>
				
				<?php if(strtotime($rev['rifa_dtsorteio']." 18:39:59") < time()){  ?>
				<button onclick="javascript: alert('Esta rifa só podia ser vendida até as 18h e 40 min.');" type="button" class="btn btn-success">Efetuar Venda</button>
				<?php }else if(strtotime($rev['rifa_dtsorteio']." 18:39:59") > time()){ ?>
					<?php if($rev['dezena_bolao'] > 0){ ?>
					<button onclick="javascript: location.href='index.php?p=ver_bilhetes_bolao_dezena&limpar_carrinho=true&rifa=<?php echo $rev['rifa_cod']; ?>'" type="button" class="btn btn-success">Efetuar Venda</button>
					<?php }else { ?>
					<button onclick="javascript: location.href='index.php?p=ver_bilhetes&rifa=<?php echo $rev['rifa_cod']; ?>'" type="button" class="btn btn-success">Efetuar Venda</button>
					<?php } ?>
				<?php } ?>
			</td>
		</tr>
		<?php } }while($rev = $rev_query->fetch_assoc()); ?>
	</table> <?php } ?>
</div>