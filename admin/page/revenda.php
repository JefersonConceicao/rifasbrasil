<?php
include("../class/conexao.php");
$erro = array();
$rev_code = "SELECT * from tbl_rifas WHERE rifa_finalizar is null and rifa_vencedor is null and rifa_cod in(select rifa_cod FROM tbl_revendedor WHERE usu_cod = '{$_SESSION['usuario']}')";
$rev_query = $mysqli->query($rev_code) or die($mysqli->error);
$rev = $rev_query->fetch_assoc();
$rev_num = $rev_query->num_rows;

?>
<div class="col-lg-12 text-left">
	<h3>Minhas Revendas</h3>
	<hr>
	<?php if (count($erro) > 0) { ?>
		<div class="col-lg-12 bg-warning">
			<?php foreach ($erro as $msg) echo "<p>$msg</p>"; ?>
			<div class="spacer"></div>
		</div>
	<?php } ?>
</div>
<div class="col-lg-12 text-left">
	<?php if ($rev_num == 0) echo "<div class=\"text-center\">No momento você não pode revender nenhuma rifa.</div>";
	else { ?>
		<div class="table-responsive">
			<table class="table table-hover table-bordered">
				<tr style="font-weight:bold;">
					<td>Rifa</td>
					<td>Valor do Bilhete</td>
					<td>Vendas</td>
					<td>Comissão Confirmada</td>
					<td>Comissão a Confirmar</td>

					<td width="30%">Ação</td>
				</tr>
				<?php do {
						$comissao = DBSelect("Select comissao as c from tbl_revendedor where usu_cod = '{$_SESSION['usuario']}' AND rifa_cod = '{$rev['rifa_cod']}'", $mysqli, 'c');
						if ($_SESSION['admin'] == 'S') {
							$ven_code = "SELECT count(*) as c 
				FROM tbl_bilhetes
				 WHERE bil_rifa = '{$rev['rifa_cod']}' 
				 AND bil_compra IN(Select comp_cod from tbl_compra)";
							$dono_code = "SELECT usu_nome FROM tbl_usuario WHERE usu_cod IN (SELECT rifa_dono FROM tbl_rifas WHERE rifa_cod = '{$rev['rifa_cod']}')";
							$dono_q = $mysqli->query($dono_code) or die($mysqli->error);
							$dono = $dono_q->fetch_assoc();
							$ven_q = $mysqli->query($ven_code) or die($mysqli->error);
							$ven = $ven_q->fetch_assoc();
						} else {
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
						if (strtotime($rev['rifa_dtsorteio'] . " 18:39:59") > time()) {
							$rifa_codigo = $rev['rifa_cod'];
							$revendedor_responsavel = $_SESSION['usuario'];
							?>

						<tr>
							<td><?php echo $rev['rifa_titulo']; ?></td>
							<td>R$ <?php echo number_format($rev['rifa_valorbilhete'], 2, ',', '.'); ?> (Comissão: <?php echo $comissao; ?>%)</td>
							<td><?php echo $ven['c']; ?></td>
							<td>R$ <?php echo number_format(($ven['c'] * $rev['rifa_valorbilhete'] * ($comissao / 100)) - floatval($desconto['desconto']), 2, ',', '.'); ?></td>
							<td>R$ <?php echo number_format($ven2['c'] * $rev['rifa_valorbilhete'] * ($comissao / 100), 2, ',', '.'); ?></td>

							<td class="text-right">


								<button name="gerar_link" class="btn btn-warning" onclick="gerarLink('<?= $rifa_codigo; ?>', '<?= $revendedor_responsavel ?>')">Gerar Link</button>

								<button name="gerar_link" class="btn btn-primary" onclick="location.href='index.php?p=rif_detalhe&codigo=<?php echo $rifa_codigo; ?>';">Extrato</button>

								<?php if ($ven['c'] == 0) { ?>
									<form method="post" action="">
										<input name="rifa" value="<?php echo $rev['rifa_cod']; ?>" type="hidden">
										<input name="usuario" value="<?php echo $_SESSION['usuario']; ?>" type="hidden">
										<button type="submit" class="btn btn-danger">Remover</button>
									</form>
								<?php } else { ?>
									<button onclick="javascript: alert('Você não pode excluir um revendedor que já vendeu bilhetes');" class="btn btn-danger">deixar de revender</button>
								<?php } ?>
								<?php if (strtotime($rev['rifa_dtsorteio'] . " 18:39:59") < time()) {  ?>
									<button onclick="javascript: alert('Esta rifa só podia ser vendida até as 18h e 40 min.');" type="button" class="btn btn-success">Efetuar Venda</button>
								<?php } else if (strtotime($rev['rifa_dtsorteio'] . " 18:39:59") > time()) { ?>
									<?php if ($rev['dezena_bolao'] > 0) { ?>
										<button onclick="javascript: location.href='index.php?p=ver_bilhetes_bolao_dezena&origem=revenda&limpar_carrinho=true&rifa=<?php echo $rev['rifa_cod']; ?>'" type="button" class="btn btn-success">Efetuar Venda</button>
									<?php } else { ?>
										<button onclick="javascript: location.href='index.php?p=ver_bilhetes&origem=revenda&rifa=<?php echo $rev['rifa_cod']; ?>'" type="button" class="btn btn-success">Efetuar Venda</button>
									<?php } ?>
								<?php } ?>
							</td>
						</tr>
				<?php }
					} while ($rev = $rev_query->fetch_assoc()); ?>
			</table> <?php } ?>
		</div>
</div>

<!-- Modal contendo o link gerado -->
<div class="modal fade" id="modal_link" tabindex="-1" role="dialog" aria-labelledby="modalLinkGerado">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modal_link_label">Link Gerado</h4>
			</div>
			<div class="modal-body">
				<input readonly id="link_gerado" value="" style="width:100%"></input>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
				<button type="button" class="btn btn-primary" onclick="copyToClipboard()">Copiar Link</button>
			</div>
		</div>
	</div>
</div>




<script>
	function gerarLink(rifa_id, revendedor_id) {
		const obj = {
			rifa_id: rifa_id,
			revendedor_id: revendedor_id
		};
		const dataString = JSON.stringify(obj);
		$.ajax({
			type: 'POST',
			data: {
				data: dataString
			},
			dataType: 'json',
			url: 'controller/generateLink.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else if (data.status === 'success') {
					$('#link_gerado').val(data.url);
					$('#modal_link').modal('show');
				}
			},
			error: function(data) {
				console.log(data);
			}
		});
	}

	function copyToClipboard() {
		/* Get the text field */
		var copyText = document.getElementById("link_gerado");

		/* Select the text field */
		copyText.select();

		/* Copy the text inside the text field */
		document.execCommand("copy");

		/* Alert the copied text */
		alert("Link copiado!");
	}
</script>