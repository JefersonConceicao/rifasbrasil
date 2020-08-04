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
				<!--<button onclick="location.href='//rifasbrasil.com.br/index.php?p=buscarrifas&rifa=<?php echo $rev['rifa_cod']; ?>&checkbox=enabled';" class="btn btn-primary">Repetir Venda</button>-->
				<?php } ?>
				<button onclick="abrir_modal();" type="button" class="btn btn-primary">Repetir Venda</button>
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
<div id="rifa_destino" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">SELECIONE A RIFA DE ORIGEM</h4>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <select name="rifa_destino" id="rifa_destino_input" class="form-control">
                                <?php
                                $data = date('Y-m-d h:i:s', time()-86400);
                                if($_SESSION['admin'] == 'S')
                                    $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where r.rifa_dtsorteio >= '$data and u.usu_cod = r.rifa_dono order by r.rifa_cod DESC";
                                else
                                    $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where r.rifa_dtsorteio >= '$data' AND (u.usu_cod = r.rifa_dono and r.rifa_dono = '{$_SESSION['usuario']}') or r.rifa_cod in (SELECT rifa_cod from tbl_revendedor where usu_cod = '{$_SESSION['usuario']}' or pai = '{$_SESSION['usuario']}') group by r.rifa_cod order by r.rifa_cod DESC";

                                $consulta = $mysqli->query($rifas_do_usuario) or die($mysqli->error);
                                $assoc    = $consulta->fetch_assoc();
                                do{ 
                                    if(time() > (strtotime($assoc['rifa_dtsorteio']))) { ?>
                                        <option value="<?= $assoc['rifa_cod']; ?>"><?= $assoc['rifa_cod']; ?> - <?= $assoc['rifa_titulo']; ?></option>
                                <?php
                                    } 
                                }while($assoc    = $consulta->fetch_assoc());
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn_pross" class="btn btn-primary">Prosseguir</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<script>
	function abrir_modal() {
		$('#rifa_destino').modal('show');
		$('#btn_pross').off();
		$('#btn_pross').click(function() {
            var novarifa = $('#rifa_destino_input').val();
            location.href='//rifasbrasil.com.br/index.php?p=buscarrifas&rifa=' + novarifa + '&checkbox=enabled';
        });
        $('#rifa_destino').modal('show');
	}
</script>