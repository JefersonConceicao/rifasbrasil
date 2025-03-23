<?php

if ($_POST['deletar']) {

	include("../../class/conexao.php");
	$mysqli->query("delete from tbl_custos where codigo = '" . intval($_POST['deletar']) . "'");
	die();
}

if ($_POST['get_custos']) {

	include("../../class/conexao.php");
	$rifa = intval($_POST['get_custos']);

	if ($_POST['vendedor'] && $_POST['vendedor'] > 0)
		$sql_add = " and vendedor = '" . intval($_POST['vendedor']) . "'";
	else
		$sql_add = " and (vendedor is null or vendedor = '' or vendedor <= 0)";

	$sql_valor = "SELECT * from tbl_custos where rifa = '$rifa' " . $sql_add;

	$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);

	$data = $sql_exe->fetch_assoc();



	$retorno = array();



	if ($sql_exe->num_rows > 0)

		do {
			$data['data'] = date('d/m/Y', strtotime($data['data']));
			$retorno[] = $data;
		} while ($data = $sql_exe->fetch_assoc());



	$sql_valor = "SELECT sum(valor) as valor from tbl_custos where rifa = '$rifa' and (vendedor is null or vendedor = '' or vendedor <= 0)";

	$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);

	$data = $sql_exe->fetch_assoc();



	die(json_encode(array('res' => $retorno, 'total' => floatval($data['valor']))));
}



if ($_POST['valor']) {



	include("../../class/conexao.php");



	$_POST['valor'] = floatval(trim(str_replace('R$', '', str_replace(',', '.', str_replace('.', '', $_POST['valor'])))));

	$_POST['descricao'] = $mysqli->escape_string($_POST['descricao']);

	$_POST['rifa']  = intval($_POST['rifa']);

	$_POST['vendedor']  = intval($_POST['vendedor']);

	$_POST['data']  = implode('-', array_reverse(explode('/', $_POST['data'])));



	$mysqli->query("INSERT INTO tbl_custos (vendedor, descricao, valor, data, rifa) values('{$_POST['vendedor']}', '{$_POST['descricao']}', '{$_POST['valor']}', '{$_POST['data']}', '{$_POST['rifa']}')");



	die(json_encode(array('insert' => $mysqli->insert_id)));
}





$codigo = intval($_GET['codigo']);



// pega o valor do bilhete da rifa

$rifa_sql  = "SELECT rifa_valorbilhete, rifa_titulo, dezena_bolao, travar_bilhetes, banca_online, multiplicador, rifa_maxbilhetes, rifa_dtsorteio, rifa_dono from tbl_rifas where rifa_cod = '$codigo' limit 1";
$rifa_exec = $mysqli->query($rifa_sql) or die($mysqli->error);
$rifa_data = $rifa_exec->fetch_assoc();

$modoBancaOnline = $rifa_data['banca_online'];
$multiplicador = $rifa_data['multiplicador'];
$travarBilhetesDezenaBolao = $rifa_data['travar_bilhetes'] && $rifa_data['dezena_bolao'];

$is_revendedor = false;
if($_SESSION['usuario'] != $rifa_data['rifa_dono']) {

	// verifica se é um revendedor
	$sql_revendedores = "SELECT COUNT(*) as num FROM tbl_revendedor WHERE rifa_cod = '$codigo' AND usu_cod = '{$_SESSION['usuario']}'";
	$query_revendedores = $mysqli->query($sql_revendedores) or die($mysqli->error);
	$is_revendedor = $query_revendedores->fetch_assoc();

	if($is_revendedor['num'] == 0 || !$is_revendedor['num'] || !$is_revendedor)
		die("Não autorizado");
	else
		$is_revendedor = true;
}

$valor_bilhete = $rifa_data['rifa_valorbilhete'] || 1;
$max_bilhetes = $rifa_data['rifa_maxbilhetes'];

$rifa_dtsorteio = $rifa_data['rifa_dtsorteio'];
$rifa_titulo = $rifa_data['rifa_titulo'];

// pega a quantidade total de vendas

$rifa_sql  = "SELECT count(*) as n from tbl_bilhetes where bil_rifa = '$codigo' limit 1";

$rifa_exec = $mysqli->query($rifa_sql) or die($mysqli->error);

$rifa_data = $rifa_exec->fetch_assoc();



$NumeroTotalDeVendas = $rifa_data['n'];


// CRIA A LISTA DE VALORES DE COMISSAO PARA OS VENDEDORES
$sql_comissoes = "SELECT * 
from tbl_revendedor 
WHERE rifa_cod = '$codigo'";
if($is_revendedor)
	$sql_comissoes .= " AND usu_cod = '{$_SESSION['usuario']}' ";
$qr_comissoes  = $mysqli->query($sql_comissoes) or die($mysqli->error);
$dados = $qr_comissoes->fetch_assoc();

$listaDeComissoesDosRevendedores = array();
do {
	$listaDeComissoesDosRevendedores[$dados['usu_cod']] = $dados['comissao'] / 100;
} while ($dados = $qr_comissoes->fetch_assoc());

// CRIA A LISTA DE DESCONTOS
$sql_descontos = "SELECT count(*) as n, c.comp_valortotal, b.bil_compra
FROM tbl_bilhetes b, tbl_compra c
WHERE b.bil_rifa = '$codigo'
AND c.comp_cod = b.bil_compra
GROUP BY b.bil_compra";
if($is_revendedor) {
	$sql_descontos = "SELECT count(*) as n, c.comp_valortotal, b.bil_compra
	FROM tbl_bilhetes b, tbl_compra c
	WHERE b.bil_rifa = '$codigo'
	AND c.comp_cod = b.bil_compra
	AND c.comp_revendedor = '{$_SESSION['usuario']}'
	GROUP BY b.bil_compra";
}
$qr_descontos  = $mysqli->query($sql_descontos) or die($mysqli->error);
$dados = $qr_descontos->fetch_assoc();

$listaDeDescontos = array();
do {
	$listaDeDescontos[$dados['bil_compra']] = floatval($dados['n'] * $valor_bilhete) - floatval($dados['comp_valortotal']);
} while ($dados = $qr_comissoes->fetch_assoc());

// variaveis

$sql_rev = "SELECT 
tbl_bilhetes.*, 
tbl_compra.comp_cod,
tbl_compra.comp_status_revenda, 
tbl_compra.comp_situacao, 
tbl_compra.comp_desconto, 
tbl_compra.comp_valortotal,
tbl_compra.comp_data,
usu.usu_nome as Usuario,
usu.usu_cod as UsuarioCod,
usu2.usu_nome as Cliente,
usu2.usu_cod as ClienteCod,
usu2.usu_celular as ClienteCelular,
(((select count(*) as n from tbl_bilhetes where bil_compra = tbl_compra.comp_cod)*$valor_bilhete)-tbl_compra.comp_valortotal) as desconto
FROM tbl_bilhetes, tbl_compra 
LEFT JOIN tbl_usuario usu ON usu.usu_cod = tbl_compra.comp_revendedor
LEFT JOIN tbl_usuario usu2 ON usu2.usu_cod = tbl_compra.comp_cliente
WHERE bil_rifa = '$codigo'  
AND bil_compra IN(select comp_cod from tbl_compra where comp_revendedor is not null and comp_revendedor > 0) 
AND comp_cod = bil_compra 
ORDER BY tbl_compra.comp_cod DESC";
if($is_revendedor) {
	$sql_rev = "SELECT 
	tbl_bilhetes.*, 
	tbl_compra.comp_cod,
	tbl_compra.comp_status_revenda, 
	tbl_compra.comp_situacao, 
	tbl_compra.comp_desconto, 
	tbl_compra.comp_valortotal,
	tbl_compra.comp_data,
	usu.usu_nome as Usuario,
	usu.usu_cod as UsuarioCod,
	usu2.usu_nome as Cliente,
	usu2.usu_cod as ClienteCod,
	usu2.usu_celular as ClienteCelular,
	(((select count(*) as n from tbl_bilhetes where bil_compra = tbl_compra.comp_cod)*$valor_bilhete)-tbl_compra.comp_valortotal) as desconto
	FROM tbl_bilhetes, tbl_compra 
	LEFT JOIN tbl_usuario usu ON usu.usu_cod = tbl_compra.comp_revendedor
	LEFT JOIN tbl_usuario usu2 ON usu2.usu_cod = tbl_compra.comp_cliente
	WHERE bil_rifa = '$codigo'  
	AND bil_compra IN(select comp_cod from tbl_compra where comp_revendedor = '{$_SESSION['usuario']}') 
	AND comp_cod = bil_compra 
	ORDER BY tbl_compra.comp_cod DESC";
}

$qr_rev = $mysqli->query($sql_rev) or die($mysqli->error);
$fe_rev = $qr_rev->fetch_assoc();


$lucro_confirmado = 0;

$lucro_nao_confirmado = 0;

$numeroDeVendasPorRevenda = 0;

$comissaoConfirmada = 0;

$desconto_total = 0;

$ultimo_id = -1;

$comissaoTotal = 0;

$countBilhetes = 0;

do {



	if ($ultimo_id != $fe_rev['bil_compra']) {

		$desconto_total += $listaDeDescontos[$fe_rev['bil_compra']];

		$ultimo_id = $fe_rev['bil_compra'];
	}



	$numeroDeVendasPorRevenda++;



	if ($fe_rev['bil_situacao'] == 'V') {

		$countBilhetes++;

		$lucro_confirmado += $valor_bilhete;

		$comissaoConfirmada += ($valor_bilhete * $listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']]);
	} else {

		$comissaoAReceber += ($valor_bilhete * $listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']]);

		$lucro_nao_confirmado += $valor_bilhete;
	}


	$comissaoTotal += ($valor_bilhete * $listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']]);
} while ($fe_rev = $qr_rev->fetch_assoc());



$lucro = $lucro_confirmado - $comissaoConfirmada;



// listar comissoes dos vendedores

$qr_rev = $mysqli->query($sql_rev) or die($mysqli->error);

$fe_rev = $qr_rev->fetch_assoc();

if(!$is_revendedor) {
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

				<td><?php echo $NumeroTotalDeVendas - $numeroDeVendasPorRevenda; ?> bilhetes</td>

			</tr>

			<tr>

				<td style="font-weight:bold">Vendas por Revenda</td>

				<td><?php echo $numeroDeVendasPorRevenda; ?> bilhetes</td>

			</tr>

			<tr>

				<td style="font-weight:bold">Valor do bilhete</td>

				<td>R$ <?php echo number_format($valor_bilhete, 2, ',', '.'); ?></td>

			</tr>



			<tr>

				<td style="font-weight:bold">Subtotal</td>

				<td>R$ <?php echo number_format($NumeroTotalDeVendas * $valor_bilhete, 2, ',', '.'); ?></td>

			</tr>

			<tr>

				<td style="font-weight:bold">Comissão dos Vendedores Confirmada</td>

				<td>R$ <?php echo number_format($comissaoConfirmada, 2, ',', '.'); ?></td>

			</tr>







			<tr>

				<td style="font-weight:bold">Comissão dos Vendedores à Receber</td>

				<td>R$ <?php echo number_format($comissaoAReceber, 2, ',', '.'); ?></td>

			</tr>



			<tr>

				<td style="font-weight:bold">Comissões Totais <br><small>= Comissões Confirmadas + Comissões à Receber</small></td>

				<td>R$ <?php echo number_format($comissaoConfirmada + $comissaoAReceber, 2, ',', '.'); ?></td>

			</tr>



			<tr>

				<td style="font-weight:bold">Descontos nas Comissões</td>

				<td>R$ <?php echo number_format($desconto_total, 2, ',', '.'); ?></td>

			</tr>





			<tr>

				<td style="font-weight:bold">Custos Totais</td>

				<td id="custos_totais">R$ <?php echo number_format($custosTotais, 2, ',', '.'); ?></td>

			</tr>



			<tr class="bg-success">

				<td style="font-weight:bold">Seu Lucro à Receber <br><small>= Comissões à Receber + Vendas não Pagas</small></td>

				<td>R$ <span id="lucro_a_receber"><?php echo number_format($lucro_nao_confirmado - $comissaoAReceber, 2, ',', ''); ?></span></td>

			</tr>



			<tr class="bg-success">

				<td style="font-weight:bold">Seu Lucro Confirmado (<?= $countBilhetes; ?> bilhetes) <br><small>= Lucro - Comissões Confirmadas - Custos</small></td>

				<td>R$ <span id="lucro"><?php echo number_format($lucro, 2, ',', ''); ?></span></td>

			</tr>







		</table>





		<div class="form-group">

			<button type="button" onclick="abrir_modal(false);" class="btn btn-xs btn-danger">Adicionar Custo</button>

		</div>









		<?php if ($rifa[rifa_finalizar] > 1) { ?>



			<p>Vencedor(es)</p>
			<div class="table-responsive">
				<table class="table table-bordered table-stripped">

					<tr style="font-weight:bold;">

						<td>Vencedor</td>

						<td>Prêmio</td>

						<td>Nº Bilhete</td>

					</tr>

					<?php



						if ($rifa[rifa_premios] == 1) {



							$vencedor = DBSelect("SELECT usu_nome as v, usu_email, usu_celular FROM tbl_usuario where usu_cod = '$rifa[rifa_vencedor]'", $mysqli);



							?>

						<tr>

							<td><?php if (strlen($vencedor[v]) > 0) echo "$vencedor[v] ($vencedor[usu_email] - $vencedor[usu_celular])";
										else echo "Sem ganhador"; ?></td>

							<td><?php echo $rifa[pre_descricao]; ?></td>

							<td><?php echo str_pad($rifa[rifa_bilhetepremiado], strlen($rifa[rifa_maxbilhetes]) - 1, "0", STR_PAD_LEFT); ?></td>

						</tr>

						<?php }

							if ($rifa[rifa_premios] > 1) {



								$exec =  DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$codigo' ORDER BY pre_ranking ASC", $mysqli);

								$premio = $exec->fetch_assoc();



								do {



									$vencedor = DBSelect("SELECT usu_nome as v, usu_email, usu_celular FROM tbl_usuario where usu_cod = '$premio[pre_vencedor]'", $mysqli);



									?>

							<tr>

								<td>

									<?php if (strlen($vencedor[v]) > 0)

													echo $premio[pre_ranking] . "º: $vencedor[v] ($vencedor[usu_email] - $vencedor[usu_celular])";

												else

													echo $premio[pre_ranking] . "º: Sem ganhador"; ?></td>

								<td><?php echo $premio[pre_descricao]; ?></td>

								<td><?php echo  str_pad($premio[pre_bilhete], strlen($rifa[rifa_maxbilhetes]) - 1, "0", STR_PAD_LEFT); ?></td>

							</tr>

					<?php } while ($premio = $exec->fetch_assoc());
						} ?>

				</table>
			</div>

		<?php } ?>

		<div class="clearfix"></div>

	</div>

	<div class="clearfix"></div>


</div>
<?php } ?>
<div class="col-lg-<?php if(!$is_revendedor) echo 6; else echo 12; ?>">



	<div class="col-lg-12 text-center" id="div_link">

		<?php if(!$is_revendedor) { ?>
		<p>Extrato oculto. <a href="javascript: void(0);" onclick="toggleExtrato();">Clique aqui</a> para exibir.</p>

		<hr>

	</div>



	<div class="hidden" id="div_extrato">
		<?php } ?>

		<p>Revenda <?php if(!$is_revendedor) { ?><small><a href="javascript:void(0);" onclick="toggleExtrato();">(ocultar)</a></small><?php } ?></p>

		<?php







		$compra_atual = -1;

		$desconto = array();



		if ($qr_rev->num_rows > 0) {



			?>

			<div class="<?php if(!$is_revendedor) echo 'table-responsive'; else echo ''; ?>">
				<table class="table table-bordered ">

					<tr style="font-weight:bold;">

						<td>Bilhete</td>

						<td>ID Venda</td>

						<td>Revendedor</td>

						<td>Cliente</td>

						<td>Situação</td>

						<td>Ação</td>

						<!--<td>Comissão</td>-->

					</tr>

					<?php

						$revend = array();
						$compra_ja_esta_na_lista = array();

						do {

							if($travarBilhetesDezenaBolao && $compra_ja_esta_na_lista[$fe_rev['comp_cod']])
								continue;
							else
								$compra_ja_esta_na_lista[$fe_rev['comp_cod']] = true;

							if ($fe_rev['bil_compra'] != $compra_atual) {

								if (!$desconto[$fe_rev['UsuarioCod']]) $desconto[$fe_rev['UsuarioCod']] = 0;

								$desconto[$fe_rev['UsuarioCod']] += $listaDeDescontos[$fe_rev['bil_compra']];

								$compra_atual = $fe_rev['bil_compra'];
							}



							$revend[$fe_rev[UsuarioCod]]++;

							$nome[$fe_rev[UsuarioCod]] = $fe_rev[Usuario];



							?>

						<tr>

							<td><?php if($travarBilhetesDezenaBolao) echo $fe_rev['bil_grupo'] . "-" . str_pad($fe_rev['bil_bilhete_original'], strlen($max_bilhetes) - 1, "0", STR_PAD_LEFT); else echo str_pad($fe_rev['bil_numero'], strlen($max_bilhetes) - 1, "0", STR_PAD_LEFT); ?></td>

							<td><?= $fe_rev['comp_cod']; ?></td>

							<td>
								<?= $fe_rev['Usuario']; ?>
								<?php
								if($modoBancaOnline) {
									echo "<p><br>Valor Apostado: R$ " . number_format($fe_rev['bil_aposta'], 2, ',', '.') . "<br>";
									echo "Premiação Possível: R$ " . number_format($fe_rev['bil_aposta'] * $multiplicador, 2, ',', '.') . "</p>";
								}
								?>
							</td>

							<?php $data = date('d/m/Y H:i', strtotime($fe_rev['comp_data'])); ?>
							<td><?= $fe_rev['Cliente']; ?><br><?= $data; ?></td>



							<?php switch ($fe_rev['comp_situacao']) {
										case "":
											echo '<td style="color:#FE7F1E;" class="label-situacao-acao"><div class="box-reserv reservado">&nbsp;</div>Reservado</td>';
											//echo ($fe_rev['comp_status_revenda'] == '1') ? " - (PAGO NA HORA)" : " - (A RECEBER)";
											break;
										case 6:
										case 7:
											echo '<td class="label-situacao-acao">Cancelado</td>';
											break;
										case 3:
										case 4:
											echo '<td style="color:#F41B24;" class="label-situacao-acao"><div class="box-vendido vendido">&nbsp;</div>Vendido</td>';
											break;
										default:
											echo '<td class="label-situacao-acao">Não encontrado</td>';
											break;
									}
									?>

								<?php
										switch ($fe_rev['comp_situacao']) {
											case "":
												$grupo = "";
												if($travarBilhetesDezenaBolao) 
													$grupo = ", '" .  $fe_rev['bil_grupo'] . ":" . str_pad($fe_rev['bil_bilhete_original'], strlen($max_bilhetes) - 1, "0", STR_PAD_LEFT) . "'";
												echo '<td class="label-situacao-acao"><div class="box-vendido vendido">&nbsp;</div><span onclick="marcarBilhetePago(' . $fe_rev['comp_cod'] . ', \'' . $fe_rev['ClienteCelular'] . '\', \'' . $fe_rev['Usuario'] . '\', \'' . $fe_rev['Cliente'] . '\', \'' . $rifa_titulo . '\', \'' . implode('/', array_reverse(explode('-', $rifa_dtsorteio))) . '\' '. $grupo . ')" style="color:#F41B24; cursor:pointer" >Marcar Pago</span>';
												echo '<br><button style="margin-top:15px" class="btn btn-sm btn-default btn-lembrete" onclick="enviarLembreteWhatsapp(\'' . $fe_rev['Cliente'] . '\',\'' . $fe_rev['ClienteCelular'] . '\',\'' . $fe_rev['comp_cod'] . '\' ' . $grupo . ');">Lembrete</button>';
												echo '<br><button class="btn btn-sm btn-danger btn-lembrete" onclick="cancelarBilhete(' . $fe_rev['comp_cod'] . ')">Cancelar</button>';
												echo '</td>';
												//echo ($fe_rev['comp_status_revenda'] == '1') ? " - (PAGO NA HORA)" : " - (A RECEBER)";
												break;
											case 3:
											case 4:
												$grupo = "";
												if($fe_rev['bil_bilhete_original'] && $fe_rev['bil_grupo'])
													$grupo = ", '" .  $fe_rev['bil_grupo'] . ":" . str_pad($fe_rev['bil_bilhete_original'], strlen($max_bilhetes) - 1, "0", STR_PAD_LEFT) . "'";
												
												echo '<td style="color:grey; cursor:pointer" class="label-situacao-acao" onclick="comprovanteViaWhatsap(' . $fe_rev['comp_cod'] . ', \'' . $fe_rev['ClienteCelular'] . '\', \'' . $fe_rev['Usuario'] . '\', \'' . $fe_rev['Cliente'] . '\', \'' . $rifa_titulo . '\', \'' . implode('/', array_reverse(explode('-', $rifa_dtsorteio))) . '\' '. $grupo . ');">Comprovante</td>';
												
												break;
											default:
												echo '<td></td>';
												break;
										}

										?>



									<!-- <td>R$ <?php echo number_format($listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']] * $valor_bilhete, 2, ',', '.'); ?></td> -->

						</tr>

					<?php



							if (!$comissaoUsuario[$fe_rev['UsuarioCod']]) $comissaoUsuario[$fe_rev['UsuarioCod']] = 0;

							if (!$comissaoAReceberNum[$fe_rev['UsuarioCod']]) $comissaoAReceberNum[$fe_rev['UsuarioCod']] = 0;

							if (!$comissaoRecebida[$fe_rev['UsuarioCod']]) $comissaoRecebida[$fe_rev['UsuarioCod']] = 0;

							if (!$comissaoAReceberNum[$fe_rev['UsuarioCod']]) $comissaoAReceberNum[$fe_rev['UsuarioCod']] = 0;



							if (!$repassada[$fe_rev['UsuarioCod']]) $repassada[$fe_rev['UsuarioCod']] = 0;

							if (!$repassadaNum[$fe_rev['UsuarioCod']]) $repassadaNum[$fe_rev['UsuarioCod']] = 0;

							if (!$revendedorCount[$fe_rev['UsuarioCod']]) $revendedorCount[$fe_rev['UsuarioCod']] = 0;



							//if(!$comissaoTotal[$fe_rev['UsuarioCod']]) $comissaoTotal[$fe_rev['UsuarioCod']] = 0;





							$revendedorCount[$fe_rev['UsuarioCod']]++;





							if (($fe_rev['comp_situacao'] == '' || !$fe_rev['comp_situacao']) && $fe_rev['comp_status_revenda'] == '1') {

								$comissaoRecebida[$fe_rev['UsuarioCod']]++;

								$comissaoUsuario[$fe_rev['UsuarioCod']] += ($listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']] * $valor_bilhete);
							} elseif (($fe_rev['comp_situacao'] == '' || !$fe_rev['comp_situacao']) && $fe_rev['comp_status_revenda'] == '0') {

								$areceber[$fe_rev['UsuarioCod']] += ($listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']] * $valor_bilhete);

								$comissaoAReceberNum[$fe_rev['UsuarioCod']]++;
							} elseif ($fe_rev["comp_situacao"] ==  3 || $fe_rev["comp_situacao"] == 4) {

								$repassada[$fe_rev['UsuarioCod']] += ($listaDeComissoesDosRevendedores[$fe_rev['UsuarioCod']] * $valor_bilhete);

								$repassadaNum[$fe_rev['UsuarioCod']]++;
							}
						} while ($fe_rev = $qr_rev->fetch_assoc()); ?>

				</table>
			</div>

	</div>


	<?php if(!$is_revendedor) { ?>
	<p>Pagar aos Revendedores</p>

	<div class="table-responsive">
		<table width="100%" id="tabela">
			<thead>
				<tr style="font-weight:bold;">

					<td>Revendedor/Vendas</td>

					<td class="no-sort">Comissão Confirmada</td>

					<td class="no-sort">Comissão À Receber</td>

					<td class="no-sort">Comissão Finalizada / Repasses</td>

				</tr>
			</thead>
			<tbody>

				<?php

					foreach ($revend as $rev_codigo => $rifas_vendidas) { ?>

					<tr>

						<td>

							<?php echo $nome[$rev_codigo]; ?> (<?= $revendedorCount[$rev_codigo]; ?>) <br>

							<small>

								<b>Prestar Conta</b><br>

								R$ <span id="prestar_conta"><?php echo number_format(($comissaoRecebida[$rev_codigo] * $valor_bilhete) - $comissaoUsuario[$rev_codigo], 2, ',', ''); ?></span>

							</small>



						</td>

						<td>

							R$ <span class="comissao_vendedor_<?= $rev_codigo; ?>"><?php echo number_format($comissaoUsuario[$rev_codigo], 2, ',', ''); ?></span>

							<span class="hidden" id="comissao_original_<?= $rev_codigo; ?>"><?= ($comissaoUsuario[$rev_codigo]); ?></span>



							<br><?= $comissaoRecebida[$rev_codigo]; ?> bilhetes



						</td>

						<td>R$ <?= number_format($areceber[$rev_codigo], 2, ',', '.'); ?>

							<br><?= $comissaoAReceberNum[$rev_codigo] ?> bilhetes</td>

						<td>

							R$ <span id="finalizada_<?= $rev_codigo; ?>"><?= number_format($repassada[$rev_codigo] - $desconto[$rev_codigo], 2, ',', '.'); ?></span>

							<br><span id="finalizada_original_<?= $rev_codigo; ?>" style="display:none;"><?= ($repassada[$rev_codigo] - $desconto[$rev_codigo]); ?></span>

							<?php if ($desconto[$rev_codigo]) { ?><small>Descontado: R$ <?= $desconto[$rev_codigo]; ?></small><?php } ?>

							<br><?= $repassadaNum[$rev_codigo] ?> bilhetes



							<div class="form-group">

								<button type="button" onclick="abrir_modal(<?= $rev_codigo; ?>);" class="btn btn-xs btn-danger">Vale/Repasse</button>

							</div>

						</td>

					</tr>

				<?php } ?>

			</tbody>

		</table>
	</div>
	<?php } ?>


<?php  } else { ?>

	<p style="padding:10px;" class="bg-warning">Nenhum bilhete foi vendido por revenda.</p>

<?php } ?>
<?php if(!$is_revendedor) { ?>
<div class="form-group">

	<button onclick="javascript: location.href='index.php?p=revendedor&rifa=<?php echo $codigo; ?>';" class="btn">Revendedores</button>

	<?php if ($bil_vendidos > 0) { ?>

		<button onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">finalizar rifa</button>

	<?php } ?>

	<button onclick="javascript: location.href='index.php?p=cadastrar&codigo=<?php echo $codigo; ?>';" class="btn">editar</button>

	<?php if ($bil_vendidos == 0) { ?>

		<button onclick="javascript: location.href='index.php?p=deletar&codigo=<?php echo $codigo; ?>';" class="btn btn-danger">deletar</button>

	<?php } else { ?>

		<button onclick="javascript: alert('Você não pode excluir uma rifa que já vendeu bilhetes');" class="btn btn-danger">deletar</button>

	<?php } ?>

</div>
<?php } ?>
<div class="clearfix"></div>

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
	.modal-footer {
		text-align: left;
	}

	.label-situacao-acao {
		font-weight: 600;
		text-transform: uppercase;
	}

	.btn-lembrete {
		display: block;
		margin: auto;
	}

	.box-reserv {
		width: 25px;
		margin-right: 10px;
		display: inline-block;
	}

	.box-vendido {
		vertical-align: middle;
		width: 12px;
		height: 12px;
		margin-right: 10px;
		display: inline-block;
	}


	.reservado {
		background: #FE7F1E;
	}

	.vendido {
		background: #F41B24;
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
				<button type="button" onclick="lancar_custo();" class="btn pull-right btn-success">Salvar</button>
			</div>

		</div>



	</div>

</div>


<div class="clearfix"></div>

</div>


<div id="modalDialogOK" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Tudo certo!</h4>
      </div>
      <div class="modal-body text-center">
        <p id="textOK">O que gostaria de fazer?</p>
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button id="ok_faz_nada" type="button" class="btn btn-primary pull-left">OK</button>
        <button id="ok_enviar_comprovante" type="button" class="btn btn-success">OK, enviar comprovante.</button>
      </div>
    </div>

  </div>
</div>

<script>

	var modoBancaOnline = <?php echo $modoBancaOnline ? 'true': 'false';  ?>;

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



	function visualizar_gastos(vendedor) {

		$('#myModal').modal('hide');

		$('#custos').html('');

		atualizar_tabela(vendedor);

		$('#detalhes').modal('show');



	}



	function abrir_modal(rev = false) {



		if (rev == false)

			rev = -1;



		if (rev != false && parseInt(rev) > 0)

			$('#usuario').val(rev);

		else

			$('#usuario').val('-1');



		console.log('usuario definido: ' + rev);



		$('#lancar_custo').off();



		$('#lancar_custo').on('click', function() {

			console.log('visualizar bindado para vendedor: ' + rev);

			visualizar_gastos(rev);

		});



		$('#myModal').modal('show');



	}



	function lancar_custo() {





		var descricao = $('#cus_descricao');

		var valor = $('#cus_valor');

		var data = $('#cus_data');

		var vendedor = $('#usuario').val();

		var rifa = <?= intval($codigo); ?>;



		if (!vendedor || vendedor < 0)

			vendedor = -1;





		if (valor && rifa > 0) {



			show_loading();

			$.post('page/rif_detalhe.php', {
					descricao: descricao.val(),
					vendedor: vendedor,
					valor: valor.val(),
					data: data.val(),
					rifa: rifa
				})

				.done(function(r) {



					console.log(r);

					descricao.val('');

					valor.val('');

					data.val('');

					$('#myModal').modal('hide');

					end_loading();

					visualizar_gastos(vendedor);





				})

				.fail(function(r) {

					console.log(r);

					end_loading();

				});



		}



	}



	function deletar_custo(cod, btn) {







		$(btn).parent().parent().remove();

		$.post('page/rif_detalhe.php', {
				deletar: cod
			})

			.done(function(r) {

				console.log(r);

				var v = $('#usuario').val();

				atualizar_tabela(v);

			})

			.fail(function(r) {

				console.log(r);

			});

	}

	function toggleExtrato() {
		$('#div_extrato').toggleClass('hidden');
		$('#div_link').toggleClass('hidden');
	}


	function confirmaCancelarPedido(codigo_compra) {
		$.ajax({
			type: 'POST',
			data: {
				action: 'cancelar_compra',
				codigo_compra: codigo_compra,
			},
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else if (data.status === 'success') {
					alert(data.message);
					location.reload();
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
			}
		});
	}

	function cancelarBilhete(codigo_compra) {
		$.ajax({
			type: 'POST',
			data: {
				action: 'recuperar_bilhetes',
				codigo_compra: codigo_compra,
			},
			async: false,
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else {

					for (let i = 0; i < data.result[0].length; i++) {
						data.result[0][i] = ('000000' + data.result[0][i]).slice(-<?php if($travarBilhetesDezenaBolao) echo 2; else echo strlen($max_bilhetes)-1; ?>);
					}

					var bils = (data.result).toString();
					bils = bils.replace(/,/g, '-');
					conf = bootbox.confirm({
						message: "Você está <>CANCELANDO</> a venda <span style='color:red'>" + codigo_compra + "</span>, com os bilhetes ( <span style='color:blue'>" + bils + " </span>) , deseja continuar?",
						buttons: {
							confirm: {
								label: 'Sim',
								className: 'btn-success '
							},
							cancel: {
								label: 'Não',
								className: 'btn-danger pull-right'
							}
						},
						callback: function(result) {
							if (result === true) {
								confirmaCancelarPedido(codigo_compra);
							}
						}
					});
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
			}
		});
	}

	function comprovanteViaWhatsap (codigo_compra, tel_cliente, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupoSorteio) {

		$.ajax({
			type: 'POST',
			data: {
				action: 'recuperar_bilhetes',
				codigo_compra: codigo_compra,
			},
			async: false,
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else {

					for (let i = 0; i < data.result[0].length; i++)
						data.result[0][i] = ('000000' + data.result[0][i]).slice(-<?php if($travarBilhetesDezenaBolao) echo 2; else echo strlen($max_bilhetes)-1; ?>);

					var bils = (data.result).toString();
					bils = bils.replace(/,/g, '-');

					let aposta = null;
					let possivel_premiacao = null;
					let id_compra = null;
					if(modoBancaOnline && data && data.banca_online && data.banca_online.aposta) {
						aposta = data.banca_online.aposta;
						possivel_premiacao = data.banca_online.possivel_premiacao;
						id_compra = codigo_compra;
					}

					window.open(`https://api.whatsapp.com/send?phone=+55${tel_cliente}&text=${enviarWhatsapp(bils, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupoSorteio, id_compra, aposta, possivel_premiacao)}`, "_blank");

				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
				//alert(JSON.stringify(data));
			}
		});

	}

	function enviarWhatsapp(bilhetes, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupo, idAposta, valorAposta, possivelPremiacao) {
		if(grupo)
			grupo += ' ';
		else
			grupo = "";
		let texto = `*${revendedorNome}:* Olá *${clienteNome}*, seu(s) bilhete(s): ${grupo}[${bilhetes}] da rifa (${rifaTitulo}) Sorteio dia: (${dataSorteio})\n
Situação: *PAGO*\n
-------------------------\n
*NÃO É VÁLIDO COMO COMPROVANTE BANCARIO/DEPOSITO*\n
-------------------------\n
*PODERÁ SER SOLICITADO COMPROVANTE DE PAGTO NA ENTREGA DO PRÊMIO*\n
-------------------------\n
Boa sorte!`;

		if(modoBancaOnline)
			texto = `*${revendedorNome}:* Olá *${clienteNome}*, seu(s) bilhete(s): ${grupo}[${bilhetes}] da rifa (${rifaTitulo}) Sorteio dia: (${dataSorteio})\n
Situação: *PAGO*\n
Id Aposta: ${idAposta}
Valor Aposta: ${valorAposta}
Possível Premiação: ${possivelPremiacao}
-------------------------\n
*NÃO É VÁLIDO COMO COMPROVANTE BANCARIO/DEPOSITO*\n
-------------------------\n
*PODERÁ SER SOLICITADO COMPROVANTE DE PAGTO NA ENTREGA DO PRÊMIO*\n
-------------------------\n
Boa sorte!`;

        return window.encodeURIComponent(texto);

    }


	function confirmaMarcarBilhete(codigo_compra, tel_cliente, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupo) {
		$.ajax({
			type: 'POST',
			data: {
				action: 'marcar_como_pago',
				codigo_compra: codigo_compra,
			},
			async: false,
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else if (data.status === 'success') {
					$('#modalDialogOK #textOK').html(data.message);
					$('#ok_enviar_comprovante').off();
					$('#modalDialogOK').modal('show');
					$('#ok_faz_nada').click(function() {
						location.reload();
					});
					$('#ok_enviar_comprovante').click(function() {

						$.ajax({
							type: 'POST',
							data: {
								action: 'recuperar_bilhetes',
								codigo_compra: codigo_compra,
							},
							dataType: 'json',
							url: 'controller/RifaDetalhe.php',
							success: function(data) {

								if (data.status === 'error') {
									alert(data.message);
								} else {

									for (let i = 0; i < data.result[0].length; i++) {
										data.result[0][i] = ('00000' + data.result[0][i]).slice(-<?php if($travarBilhetesDezenaBolao) echo 2; else echo strlen($max_bilhetes)-1; ?>);
									}
									var bils = (data.result).toString();
									bils = bils.replace(/,/g, '-');

									let aposta = null;
									let possivel_premiacao = null;
									let id_compra = null;
									if(modoBancaOnline && data && data.banca_online && data.banca_online.aposta) {
										aposta = data.banca_online.aposta;
										possivel_premiacao = data.banca_online.possivel_premiacao;
										id_compra = codigo_compra;
									}

									window.open(`https://api.whatsapp.com/send?phone=+55${tel_cliente}&text=${enviarWhatsapp(bils, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupo, id_compra, aposta, possivel_premiacao)}`, "_blank");
									location.reload();

								}
							},
							error: function(data) {
								console.log(JSON.stringify(data));
							}
						});
						
					});


					
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
				//alert(JSON.stringify(data));
			}
		});
	}

	function enviarLembreteWhatsapp(nome_cliente, celular_cliente, codigo_compra, grupo) {
		if(grupo)
			grupo = `*${grupo}* `;
		else
			grupo = '';
		$.ajax({
			type: 'POST',
			data: {
				action: 'recuperar_bilhetes',
				codigo_compra: codigo_compra,
			},
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else {
					for (let i = 0; i < data.result[0].length; i++) {
						data.result[0][i] = ('000000' + data.result[0][i]).slice(-<?php if($travarBilhetesDezenaBolao) echo 2; else echo strlen($max_bilhetes)-1; ?>);
					}
					var bils = (data.result).toString();
					bils = bils.replace(/,/g, '-');
					var mensagem = 'Olá, ' + nome_cliente + '. Sua aposta: ' + codigo_compra + ' BILHETE(S): ' + grupo + '[' + bils + '] está como RESERVADA. Efetue o PAGAMENTO e GARANTA sua Sorte!';
					var whatsapp = 'https://api.whatsapp.com/send?phone=55' + celular_cliente + '&text=' + mensagem + '';
					window.open(whatsapp, '_blank');
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
			}
		});
	}


	function marcarBilhetePago(codigo_compra, tel_cliente, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupoSorteio) {
		$.ajax({
			type: 'POST',
			data: {
				action: 'recuperar_bilhetes',
				codigo_compra: codigo_compra,
			},
			async: false,
			dataType: 'json',
			url: 'controller/RifaDetalhe.php',
			success: function(data) {
				if (data.status === 'error') {
					alert(data.message);
				} else {

					for (let i = 0; i < data.result[0].length; i++) {
						data.result[0][i] = ('000000' + data.result[0][i]).slice(-<?php if($travarBilhetesDezenaBolao) echo 2; else echo strlen($max_bilhetes)-1; ?>);
					}
					console.log(data.result);

					var bils = (data.result).toString();
					bils = bils.replace(/,/g, '-');
					let a_mensagem = "Você está marcando os seguintes bilhetes ( <span style='color:blue'>" + bils + " </span>) da venda <span style='color:red'>" + codigo_compra + "</span> como <b>pagos</b>, deseja continuar?";
					if(grupoSorteio)
						a_mensagem = "Você está marcando os seguintes bilhetes ( <span style='color:blue'>" + bils + " </span>) " + grupoSorteio + " da venda <span style='color:red'>" + codigo_compra + "</span> como <b>pagos</b>, deseja continuar?";

					conf = bootbox.confirm({
						message: a_mensagem,
						buttons: {
							confirm: {
								label: 'Sim',
								className: 'btn-success '
							},
							cancel: {
								label: 'Não',
								className: 'btn-danger pull-right'
							}
						},
						callback: function(result) {
							if (result === true) {
								confirmaMarcarBilhete(codigo_compra, tel_cliente, revendedorNome, clienteNome, rifaTitulo, dataSorteio, grupoSorteio);
							}
						}
					});
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
				//alert(JSON.stringify(data));
			}
		});
	}



	function atualizar_tabela(v) {



		var rifa = <?= intval($codigo); ?>;

		var vendedor = -1;



		if (v > 0)

			vendedor = v;



		var content_place = $('#custos');

		content_place.html('');

		//show_loading();



		var total;



		if (v > 0)

			total = parseFloat($('#comissao_original_' + v).html());

		else

			total = parseFloat(<?= $lucro; ?>);



		console.log("PEGANDO GASTOS DO VENDEDOR " + vendedor);



		var valor_finalizado;



		$.post('page/rif_detalhe.php', {
				get_custos: rifa,
				vendedor: vendedor
			})

			.done(function(r) {

				console.log(r);

				r = JSON.parse(r);

				content_place.html('');

				end_loading();



				if (!r.res)

					return;



				console.log("TAMANHO:" + r.res.length);





				$('#custos_totais').html('R$ ' + r.total);



				console.log(r);



				if (r.res.length > 0) {

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


					for (var k = 0; k < r.res.length; k++) {

						subtotal_custos += parseFloat(r.res[k].valor);
						total -= r.res[k].valor;
						html += ("<tr><td>" + r.res[k].descricao + "</td><td>" + r.res[k].valor + "</td><td>" + r.res[k].data +
							"</td><td><button class=\"btn btn-danger btn-xs\" type=\"button\" onclick=\"deletar_custo(" + r.res[k].codigo + ", this);\">DEL</button></td></tr>");



					}



					$('#total_custos').html('TOTAL: R$ ' + subtotal_custos.toFixed(2));



					html += ('</tbody></table>');



					content_place.html(html);



					if (vendedor > 0) {

						valor_finalizado = parseFloat($('#finalizada_original_' + vendedor).html()) - subtotal_custos;

						console.log('removendo ' + subtotal_custos + ' do valor total. ' + vendedor);

						$('#finalizada_' + vendedor).html(valor_finalizado);

						//$('#finalizada_original_' + v).html(total.toFixed(2));

					} else

						$('#lucro').html(total.toFixed(2));



				} else {

					$('#total_custos').html('');

					$('#custos').html("<p>Nenhum gasto foi Lançado</p>");

					if (vendedor > 0)

						$('#finalizada_' + vendedor).html(total.toFixed(2));

					else

						$('#lucro').html(total.toFixed(2));

				}



			})

			.fail(function(r) {

				console.log('Deu erro!');

				console.log(r);

				end_loading();

			});
	}

	function gerarComprovante(codigo_compra) {

		$.post('page/venda.php', {
				comprovantes: codigo_compra,
				gerarComprovantes: 'jspdf'
			})
			.done(
				function(response) {
					var doc = new jsPDF();
					bootbox.alert({
						message: "Comprovante gerado com sucesso!",
						size: 'small',
						backdrop: true
					});
					doc.fromHTML(response, 15, 15, {
						"width": 170
					});
					doc.save('venda_' + codigo_compra + '.pdf');

				})
			.fail(
				function(response) {
					console.log(response);
					alert('Falhou ao imprimir!');
				});


	}
</script>