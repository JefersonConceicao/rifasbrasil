<?php

include("../class/conexao.php");

if (isset($_GET['destaque']) && $_SESSION[admin] == 'S') {

	$destaque = intval($_GET['destaque']);

	// processo de finalizacao da rifa

	$rifa = DBSelect("select rifa_destaque from tbl_rifas where rifa_cod = '$destaque'", $mysqli);

	if ($rifa[rifa_destaque] == 1)

		$code_dest = "UPDATE tbl_rifas SET rifa_destaque = NULL WHERE rifa_cod = '$destaque'";

	else

		$code_dest = "UPDATE tbl_rifas SET rifa_destaque = '1' WHERE rifa_cod = '$destaque'";

	if (DBExecute($code_dest, $mysqli))

		echo "<script>alert('Ação efetuada!'); location.href='index.php?p=rifa';</script>";

	else

		echo "<script>alert('Falhou.'); location.href='index.php?p=rifa';</script>";
}

if (isset($_GET['finalizar']) && $_SESSION[admin] == 'S') {

	include("page/finalizar_rifa.php");

	$finalizar = intval($_GET['finalizar']);

	// processo de finalizacao da rifa

	if (DBExecute("UPDATE tbl_rifas SET rifa_finalizar = '2' WHERE rifa_cod = '$finalizar'", $mysqli)) {

		//set_time_limit(0);

		finalizar_rifa($finalizar, $mysqli);

		echo "<script>alert('Rifa Finalizada!'); location.href='index.php?p=rifa'; </script>";

		//set_time_limit(40);

	} else

		echo "<script>alert('Falha ao finalizar a rifa.'); location.href='index.php?p=rifa';</script>";
}
if ($_GET['travarBilhete'] || $_GET['destravarBilhete']) {

	if ($_GET['destravarBilhete']) {
		$valor = 'NULL';
		$rifa_codigo = intval($_GET['destravarBilhete']);
	} else {
		$valor = "'1'";
		$rifa_codigo = intval($_GET['travarBilhete']);
	}

	$rifa_code = "SELECT rifa_dono from tbl_rifas where rifa_cod = '$rifa_codigo' limit 1";
	$rifa_query = $mysqli->query($rifa_code) or die($mysqli->error);
	$rifa = $rifa_query->fetch_assoc();
	$rifa_num = $rifa_query->num_rows;

	if ($rifa_num <= 0)
		echo "";
	else if ($_SESSION['admin'] == 'S')
		$mysqli->query("UPDATE tbl_rifas set travar_bilhetes = $valor WHERE rifa_cod = '$rifa_codigo'") or die($mysqli->error);
	else if ($_SESSION['usuario'] == $rifa['rifa_dono'])
		$mysqli->query("UPDATE tbl_rifas set travar_bilhetes = $valor WHERE rifa_cod = '$rifa_codigo'") or die($mysqli->error);
}

if ($_SESSION[admin] == 'S') {

	$rifa_code = "SELECT r.*, a.aguardando_envio from tbl_rifas r left join tbl_autorizar_sms a on a.rifa = r.rifa_cod  WHERE r.rifa_finalizar IS NOT NULL";

	$rifa_query = $mysqli->query($rifa_code) or die($mysqli->error);

	$rifa = $rifa_query->fetch_assoc();

	$rifa_num = $rifa_query->num_rows;



	?>

	<div class="col-lg-12 text-left">

		<h3>Rifas Solicitando Finalização</h3>

		<hr>

		<p>O sistema verifica automaticamente a sua conta pagseguro. Todas as rifas cujas comissões já foram pagas serão marcadas como PAGAs abaixo. Você também pode Autorizar Finalização manualmente.</p>

		<div class="col-lg-12 form-group">

			<button onclick="javascript: window.open('index.php?p=atualizar_ps', '_blank');" class="btn">Atualizar Pagseguro</button>

		</div>

		<div class="col-lg-12">

			<?php

				if (isset($erro) && count($erro) > 0)

					foreach ($erro as $valor)

						echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";

				?>

		</div>

	</div>

	<div class="col-lg-12 text-left table-responsive">

		<?php if ($rifa_num == 0) echo "<div class=\"text-center\">Nenhuma rifa solicitou finalização.</div>";
			else { ?>

			<table class="table table-hover table-bordered">

				<tr style="font-weight:bold;">

					<td>Rifa</td>

					<td>Status</td>

					<td width="10%">Valor do Bilhete</td>

					<td>Vendas</td>

					<td>Valor à receber</td>

					<td>Instituição de Caridade</td>

					<td>Bilhete(s) / Vencedor(es)</td>

					<td>Status</td>

					<td></td>

				</tr>

				<?php do {

							$bilhetes = "";

							$vencedores = "";

							$codigo = $rifa[rifa_cod];

							if ($rifa[rifa_premios] == 1 && $rifa[rifa_vencedor] > 0) {

								$bilhetes = $rifa[rifa_bilhetepremiado];

								$vencedores  = DBSelect("SELECT usu_nome FROM tbl_usuario WHERE usu_cod = '$rifa[rifa_vencedor]'", $mysqli, "usu_nome");
							} else if ($rifa[rifa_premios] > 1) {

								$exec =  DBExecute("SELECT * FROM tbl_premio p  WHERE p.pre_rifa = '$codigo' ORDER BY p.pre_ranking ASC", $mysqli);

								$premio = $exec->fetch_assoc();

								$billhetes = "";

								do {

									if ($premio[pre_vencedor] == 0)

										$vencedores .= "$premio[pre_bilhete] - Nenhum,<br>";

									else

										$vencedores .= "$premio[pre_bilhete] | " . DBSelect("Select CONCAT( usu_nome, ' (', usu_celular, ')'  ) as final

							from tbl_usuario where usu_cod = '$premio[pre_vencedor]'", $mysqli, "final") . ",<br>";
								} while ($premio = $exec->fetch_assoc());
							}

							$vencedores = substr($vencedores, 0, -2);

							$bil_code = "SELECT * FROM tbl_bilhetes b where b.bil_rifa = '$rifa[rifa_cod]'";

							$bil_query = $mysqli->query($bil_code) or die($mysqli->error);

							$bil = $bil_query->fetch_assoc();

							$bil_num = $bil_query->num_rows;

							$debito = ($bil_num * $rifa[rifa_valorbilhete] * 0.1);

							?>

					<tr>

						<td><?php echo $rifa[rifa_titulo]; ?></td>

						<td><?php if (empty($rifa[rifa_finalizar])) echo "Ativa";
										else if ($rifa[rifa_finalizar] == 1) echo "Aguardado Finalização";
										else  echo "Finalizada"; ?></td>

						<td>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></td>

						<td><?php echo $bil_num . "/" . $rifa[rifa_maxbilhetes]; ?></td>

						<td>R$ <?php echo number_format($debito, 2, ',', '.'); ?></td>

						<td><?php echo (strlen($rifa[rifa_instituicao]) == 0) ? "Não especificada" : DBSelect("select instituicao from instituicao where codigo = '$rifa[rifa_instituicao]'", $mysqli, "instituicao"); ?></td>

						<td><?php if (strlen($vencedores) > 0) echo $vencedores;
										else echo "Nenhum"; ?></td>

						<td>

							<?php

										if ($rifa[rifa_finalizar] == 3) echo "PAGA";

										if ($rifa[rifa_finalizar] == 2) echo "FINALIZADA";

										if ($rifa[rifa_finalizar] == 1) echo "AGUARD. PAGTO.";

										?>

						</td>

						<td width="20%" class="text-right">

							<?php if ($rifa[rifa_finalizar] != 2 && $rifa[rifa_finalizar] != 3) { ?>

								<button onclick="javascript: if(confirm('Tem certeza disso?')) window.open('index.php?p=rifa&finalizar=<?php echo $rifa[rifa_cod]; ?>', '_blank');" class="btn btn-success">Autorizar Finalização</button>

							<?php } ?>

							<?php if ($rifa['aguardando_envio'] == 1 && $rifa['enviado'] == 0) { ?>

								<button onclick="javascript: if(confirm('Tem certeza disso?')){ enviar_sms_servidor('<?php echo $rifa['rifa_cod']; ?>');}" class="btn btn-success">Autorizar SMS</button>

							<?php } ?>

						</td>

					</tr>

				<?php } while ($rifa = $rifa_query->fetch_assoc()); ?>

			</table> <?php } ?>

	</div>

<?php } ?>

<?php

$rifa_code = "SELECT r.*, l.grupo from tbl_rifas r left join lote l on l.rifa = r.rifa_cod WHERE r.rifa_deletada is null AND r.rifa_dono = '$_SESSION[usuario]'";

if ($_SESSION[admin] == 'S')

	$rifa_code = "SELECT * from tbl_rifas r left join lote l on l.rifa = r.rifa_cod WHERE rifa_deletada is null";

$rifa_query = $mysqli->query($rifa_code) or die($mysqli->error);

$rifa = $rifa_query->fetch_assoc();

$rifa_num = $rifa_query->num_rows;

?>

<div class="col-lg-12 text-left">

	<h3>Rifas (<?php echo $rifa_num; ?>)</h3>

	<hr>

	<p>
		<div class="form-group">
			<button onclick="javascript: location.href='index.php?p=cadastrar';" class="btn btn-success">Cadastrar Rifa</button>
		</div>
		<div class="form-group">

			<button onclick="javascript: window.open('index.php?p=lotear', '_self');" class="btn btn-primary">Lotear | Imprimir Rifas</button>
		</div>
		<div class="form-group">

			<button onclick="javascript: location.href='index.php?p=buscarrifas';" class="btn btn-success">Pesquisar Rifas por Telefone</button>
		</div>

	</p>

</div>

<div class="col-lg-12 text-left table-responsive">

	<?php if ($rifa_num == 0) echo "<div class=\"text-center\">Nenhuma rifa foi cadastrada.</div>";
	else { ?>

		<table class="table table-hover table-bordered">

			<tr style="font-weight:bold;">

				<td>Rifa</td>

				<td>Status</td>

				<td>Bilhetes</td>

				<td width="10%">Valor do Bilhete</td>

				<td>Vendas</td>

				<td>Data do Sorteio</td>

				<td></td>

			</tr>

			<?php do {

					$bil_code = "SELECT * FROM tbl_bilhetes where bil_rifa = '$rifa[rifa_cod]'";

					$bil_query = $mysqli->query($bil_code) or die($mysqli->error);

					$bil = $bil_query->fetch_assoc();

					$bil_num = $bil_query->num_rows;

					?>

				<tr>

					<td><?php echo $rifa[rifa_titulo]; ?></td>

					<td><?php if (empty($rifa[rifa_finalizar])) echo "Ativa";
								else if ($rifa[rifa_finalizar] == 1) echo "Aguardado Finalização";
								else  echo "Finalizada"; ?></td>

					<td><?php if ($rifa[rifa_maxbilhetes] == 100) echo 100;
								elseif ($rifa[rifa_maxbilhetes] == 999) echo 1000;
								elseif ($rifa[rifa_maxbilhetes] == 1000) echo 1000;
								elseif ($rifa[rifa_maxbilhetes] == 1001) echo 1000;
								elseif ($rifa[rifa_maxbilhetes] == 9999) echo 10000;
								elseif ($rifa[rifa_maxbilhetes] == 10001) echo 10000;
								elseif ($rifa[rifa_maxbilhetes] == 10000) echo 10000; ?></td>

					<td>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></td>

					<td><?php echo $bil_num; ?></td>

					<td><?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></td>

					<td width="40%" class="text-right">

						<!--

								<?php if ($rifa[rifa_destaque] != 1) { ?>

								<button onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">destacar rifa</button>

								<?php } ?>

							-->

						<?php if ($rifa['rifa_finalizar'] == 2 && $rifa['rifa_maxbilhetes'] == 100) { ?>
							<button onclick="javascript: enviar_sms('<?php echo $rifa['rifa_cod']; ?>');" class="btn btn-success">Enviar SMS</button>
						<?php } ?>

						<?php if ($rifa['banca_online']) { ?>
							<button onclick="javascript: location.href='https://rifasbrasil.com.br/admin/page/relatorio_mais_vendidas.php?rifa=<?php echo $rifa['rifa_cod']; ?>';" class="btn btn-primary">Rel +Vendidas</button>
						<?php } ?>

						<form action="index.php?p=venda" method="POST" style="display:inline">
							<input hidden name="ok" value="1">
							<input hidden name="por_rifa" value="<?= $rifa['rifa_cod']; ?>">
							<button type="submit" class="btn btn-default">Vendas</button>
						</form>

						<?php if ($bil_num > 0 && strlen($rifa[rifa_finalizar]) == 0) { ?>

							<button onclick="javascript: if(confirm('Tem certeza disso?')) window.open('index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>', '_blank');" class="btn btn-success">finalizar rifa</button>

						<?php } else if (strtotime($rifa[rifa_dtsorteio]) < time() && ($rifa[rifa_finalizar]) == 1) { ?>



							<button onclick="javascript: location.href='index.php?p=pagar&rifa=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">finalizar rifa</button>

						<?php } else if (strtotime($rifa[rifa_dtsorteio]) > time()) { ?>

							<button <?php if ($_SESSION['admin'] != 'S') {  ?> onclick="javascript: alert('Você não pode finalizar uma Rifa antes da data de sorteio programada.');" <?php } else { ?> onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" <?php } ?> class="btn btn-success">finalizar rifa</button>

						<?php } ?>

						<?php if ($_SESSION['admin'] == 'S') { ?>

							<button onclick="javascript: location.href='index.php?p=deletar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-danger">deletar</button>

						<?php } /*else{ ?>

							<button onclick="javascript: alert('Você não pode excluir uma rifa que já vendeu bilhetes');" class="btn btn-danger">deletar</button>

							<?php }*/ ?>

						<?php if ($_SESSION[admin] == 'S') { ?>

							<button onclick="javascript: location.href='index.php?p=rifa&destaque=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-warning"><?php echo ($rifa[rifa_destaque] == 1) ? "remover destaque" : "destacar"; ?></button>

						<?php } ?>

						<button onclick="javascript: location.href='index.php?p=revendedor&rifa=<?php echo $rifa['rifa_cod']; ?>';" class="btn">Revendedores</button>

						<?php if ($rifa['grupo']) { ?>
							<form action="index.php?p=lotear_final_new" method="post">

								<input type="hidden" name="rifa" value="<?php echo $rifa['rifa_cod']; ?>">

								<input type="hidden" name="grupo" value="<?php echo $rifa['grupo']; ?>">

								<button type="submit" class="btn btn-primary">Pesquisar Bilhetes | Grupos</button>

								<button type="button" onclick="location.href='http://rifasbrasil.com.br/admin/index.php?p=revenda_loteamento&rifa=<?php echo $rifa['rifa_cod']; ?>';" class="btn btn-primary">Organizar Revenda</button>

							</form>
						<?php } ?>

						<button onclick="javascript: location.href='index.php?p=rifa&<?php if ($rifa['travar_bilhetes']) echo 'destravarBilhete';
																								else echo 'travarBilhete'; ?>=<?php echo $rifa['rifa_cod']; ?>';" class="btn btn-danger"><?php if ($rifa['travar_bilhetes']) echo 'Destravar';
																																																	else echo 'Travar'; ?> Bilhetes</button>

						<button onclick="javascript: location.href='index.php?p=rif_detalhe&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn">Caixa desta Rifa</button>

						<?php if ($rifa['dezena_bolao'] == '0' || $rifa['dezena_bolao'] == NULL) : ?>
							<button name="ver_bilhetes_disponiveis" class="btn btn-default" onclick="mostrarBilhetes('<?= $rifa['rifa_cod'] ?>')">Dezenas Livres</button>
						<?php endif; ?>

						<button onclick="javascript: location.href='index.php?p=cadastrar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn">editar</button>

						<?php if ($rifa['dezena_bolao'] > 0) { ?>
							<button onclick="javascript: location.href='index.php?p=adicionar_sorteio&codigo=<?php echo $rifa['rifa_cod']; ?>';" class="btn btn-success">Adicionar Sorteio</button>

							<button onclick="javascript: location.href='index.php?p=relatorio_sorteio&rifa=<?php echo $rifa['rifa_cod']; ?>';" class="btn btn-primary">Relatório</button>

						<?php } ?>

					</td>

				</tr>

			<?php } while ($rifa = $rifa_query->fetch_assoc()); ?>

		</table> <?php } ?>

</div>
<!-- Modal -->
<div id="modal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Enviar SMS</h4>
			</div>
			<div class="modal-body">

				<div class="form-group">
					<button id="sms_servidor" class="btn btn-default">SMS Servidor</button>
				</div>

				<div class="form-group">
					<label for="">SMS Celular</label>
				</div>
				<div class="form-group" id="area_send">

					<button class="btn btn-success" onclick="javascript:location.href='sms:+55<?php echo $telefone; ?>?body=<?php echo $_SESSION[sms]; ?>';">SMS Celular</button>

				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>

	</div>
</div>


<!-- Modal contendo os bilhetes disponiveis -->
<div class="modal fade" id="modalBilhetesDisponiveis" tabindex="-1" role="dialog" aria-labelledby="modalBilhetes">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modalBilhetesDisponiveis_label">Bilhete Disponíveis</h4>
			</div>
			<div class="modal-body">
				<textarea readonly type="text" id="series_disponiveis" value="" style="width:100%; height:150px"></textarea>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
				<button type="button" class="btn btn-primary" onclick="copyBilhetesToClipboard()">Copiar Bilhetes</button>
			</div>
		</div>
	</div>
</div>

<script>
	function mostrarBilhetes(codigo_rifa) {
		$.ajax({
			type: 'POST',
			data: {
				action: 'recuperar_bil_disponiveis',
				codigo_rifa: codigo_rifa,
			},
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else if (data.status === 'success') {
					var series_disponiveis = (data.result).toString();
					series_disponiveis = series_disponiveis.replace(/,/g, '-');
					$('#series_disponiveis').val(series_disponiveis);
					$('#modalBilhetesDisponiveis').modal('show');
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
			}
		});
	}

	function copyBilhetesToClipboard() {
		$("#series_disponiveis").select();
		document.execCommand('copy');
		alert('Bilhetes copiados!')
	}

	function enviar_sms_servidor(cod) {

		$.post('page/webservice_sms.php', {
			action: 'smsServidor',
			rifa_codigo: cod
		}).done(function(res) {

			// formatar resposta
			console.log(res);
			alert("Os SMS serão enviados após autorização do administrador. Informações no e-mail: contato@rifasbrasil.com.br");

			$('#modal').modal('hide');

		}).fail(function(res) {
			console.log(res);
		});

	}

	function enviar_sms(cod) {

		// puxar telefones do banco de dados
		$('#sms_servidor').click(function() {
			enviar_sms_servidor(cod);
		});

		$.post('page/webservice_sms.php', {
			action: 'getNumeros',
			rifa_codigo: cod
		}).done(function(res) {

			// formatar resposta
			console.log(res);
			res = JSON.parse(res);

			// declaracar variaveis
			var max_contatos = 20;
			var k = 0,
				j = 0,
				prox_topo = 0,
				numero_btn = 1;
			var html = '';
			var sms_sucesso = res.sms_vencedor,
				sms_perdeu = res.sms_perdedor;
			var numero_perdedores = res.perdedores.length;
			var numero_vencedores = res.vencedores.length;

			if (numero_perdedores == 0 && numero_vencedores == 0)
				return alert('Esta rifa não possui nenhum vencedor ou perdedor.');


			$('#area_send').html('');

			// botoes de enviar
			if (numero_vencedores > 0) {

				html += '<p><button class="btn btn-success" onclick="javascript:location.href=\'sms:';
				for (k = 0; k < numero_vencedores; k++) {

					html += '+55' + res.vencedores[k];
					if (res.vencedores[k + 1]) html += ',';

				}
				html += '?body=' + sms_sucesso + '\';">SMS Vencedores</button></p>';

			}

			if (res.perdedores.length > 0) {

				while (j < numero_perdedores) {

					if ((numero_perdedores - j) >= 20)
						prox_topo += 20;
					else prox_topo = numero_perdedores;

					html += '<p><button class="btn btn-default" onclick="javascript:location.href=\'sms:';
					for (j; j < prox_topo; j++) {
						html += '+55' + res.perdedores[j];
						if (res.perdedores[j + 1] && j < (prox_topo - 1)) html += ',';
					}

					html += '?body=' + sms_perdeu + '\';">SMS Perdedores ' + numero_btn + '</button></p>';
					numero_btn++;

				}

			}

			$('#area_send').html(html);
			$('#modal').modal('show');

		}).fail(function(res) {
			console.log(res);
		});


	}
</script>