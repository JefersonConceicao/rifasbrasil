<?php





include("../class/conexao.php");



if(isset($_GET['destaque']) && $_SESSION[admin] == 'S'){



	$destaque = intval($_GET['destaque']);

	// processo de finalizacao da rifa



	$rifa = DBSelect("select rifa_destaque from tbl_rifas where rifa_cod = '$destaque'", $mysqli);



	if($rifa[rifa_destaque] == 1)

		$code_dest = "UPDATE tbl_rifas SET rifa_destaque = NULL WHERE rifa_cod = '$destaque'";

	else

		$code_dest = "UPDATE tbl_rifas SET rifa_destaque = '1' WHERE rifa_cod = '$destaque'";



	if(DBExecute($code_dest, $mysqli))

		echo "<script>alert('Ação efetuada!'); location.href='index.php?p=rifa';</script>";

	else

		echo "<script>alert('Falhou.'); location.href='index.php?p=rifa';</script>";



}





if(isset($_GET['finalizar']) && $_SESSION[admin] == 'S'){



	include("page/finalizar_rifa.php");



	$finalizar = intval($_GET['finalizar']);

	// processo de finalizacao da rifa



	if(DBExecute("UPDATE tbl_rifas SET rifa_finalizar = '2' WHERE rifa_cod = '$finalizar'", $mysqli)){

		//set_time_limit(0);



		finalizar_rifa($finalizar, $mysqli);

		echo "<script>alert('Rifa Finalizada!'); location.href='index.php?p=rifa'; </script>";

		//set_time_limit(40);



	}else

	echo "<script>alert('Falha ao finalizar a rifa.'); location.href='index.php?p=rifa';</script>";



}



if($_SESSION[admin] == 'S'){



	$rifa_code = "SELECT * from tbl_rifas WHERE rifa_finalizar IS NOT NULL";

	$rifa_query = $mysqli->query($rifa_code) or die($mysqli->error);

	$rifa = $rifa_query->fetch_assoc();

	$rifa_num = $rifa_query->num_rows;



	

?>

<div class="col-lg-12 text-left">

	<h3>Rifas Solicitando Finalização</h3>

	<hr>

	<p>O sistema verifica automaticamente a sua conta pagseguro. Todas as rifas cujas comissões já foram pagas serão marcadas como PAGAs abaixo. Você também pode Autorizar Finalização manualmente.</p>

	<div class="col-lg-12 form-group">

		<button onclick="javascript: window.open('index.php?p=atualizar_ps', '_blank');"  class="btn">Atualizar Pagseguro</button>

	</div>

	<div class="col-lg-12">

		<?php 

		if(isset($erro) && count($erro) > 0)

			foreach($erro as $valor)

				echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";

		?>

	</div>



</div>

<div class="col-lg-12 text-left table-responsive">

	<?php if($rifa_num == 0) echo "<div class=\"text-center\">Nenhuma rifa solicitou finalização.</div>"; else { ?>



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

			$vencedores= "";

			$codigo = $rifa[rifa_cod];







			if($rifa[rifa_premios] == 1 && $rifa[rifa_vencedor] > 0 ){

				$bilhetes = $rifa[rifa_bilhetepremiado];

				$vencedores  = DBSelect("SELECT usu_nome FROM tbl_usuario WHERE usu_cod = '$rifa[rifa_vencedor]'", $mysqli, "usu_nome"); 



			}

			else if($rifa[rifa_premios] > 1){





				$exec =  DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$codigo' ORDER BY pre_ranking ASC", $mysqli);

				$premio = $exec->fetch_assoc();

				$billhetes = "";

				do{

					if($premio[pre_vencedor] == 0)

						$vencedores .= "$premio[pre_bilhete] - Nenhum,<br>";

					else

						$vencedores .= "$premio[pre_bilhete] | " . DBSelect("Select CONCAT( usu_nome, ' (', usu_celular, ')'  ) as final

							from tbl_usuario where usu_cod = '$premio[pre_vencedor]'", $mysqli, "final").",<br>";

				}while($premio = $exec->fetch_assoc());



			}





			$vencedores = substr($vencedores, 0, -2);



			$bil_code = "SELECT * FROM tbl_bilhetes b where b.bil_rifa = '$rifa[rifa_cod]'";

			$bil_query = $mysqli->query($bil_code) or die($mysqli->error);

			$bil = $bil_query->fetch_assoc();

			$bil_num = $bil_query->num_rows;

			$debito = ($bil_num* $rifa[rifa_valorbilhete] * 0.1);



			?>

			<tr>

				<td><?php echo $rifa[rifa_titulo]; ?></td>

				<td><?php if(empty($rifa[rifa_finalizar])) echo "Ativa"; else if($rifa[rifa_finalizar] == 1) echo "Aguardado Finalização"; else  echo "Finalizada"; ?></td>

				<td>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></td>

				<td><?php echo $bil_num."/".$rifa[rifa_maxbilhetes]; ?></td>

				<td>R$ <?php echo number_format($debito, 2, ',', '.'); ?></td>

				<td><?php echo (strlen($rifa[rifa_instituicao]) == 0)? "Não especificada":DBSelect("select instituicao from instituicao where codigo = '$rifa[rifa_instituicao]'", $mysqli, "instituicao"); ?></td>



				<td><?php if(strlen($vencedores) > 0) echo $vencedores; else echo "Nenhum"; ?></td>

				<td>

					<?php 

					if($rifa[rifa_finalizar] == 3) echo "PAGA"; 

					if($rifa[rifa_finalizar] == 2) echo "FINALIZADA"; 

					if($rifa[rifa_finalizar] == 1) echo "AGUARD. PAGTO."; 

					?>

				</td>

				<td width="20%" class="text-right">

					<?php if($rifa[rifa_finalizar] != 2 && $rifa[rifa_finalizar] != 3){ ?>

					<button onclick="javascript: if(confirm('Tem certeza disso?')) window.open('index.php?p=rifa&finalizar=<?php echo $rifa[rifa_cod]; ?>', '_blank');" class="btn btn-success">Autorizar Finalização</button>

					<?php } ?>



				</td>

			</tr>

			<?php }while($rifa = $rifa_query->fetch_assoc()); ?>

		</table> <?php } ?>

	</div>







	<?php } ?>





	<?php



	$rifa_code = "SELECT * from tbl_rifas WHERE rifa_deletada is null AND rifa_dono = '$_SESSION[usuario]'";

	if($_SESSION[admin] == 'S')

		$rifa_code = "SELECT * from tbl_rifas WHERE rifa_deletada is null";



	$rifa_query = $mysqli->query($rifa_code) or die($mysqli->error);

	$rifa = $rifa_query->fetch_assoc();

	$rifa_num = $rifa_query->num_rows;





	?>

	<div class="col-lg-12 text-left">

		<h3>Rifas (<?php echo $rifa_num; ?>)</h3>

		<hr>

		<p>

			<button onclick="javascript: location.href='index.php?p=cadastrar';" class="btn btn-success">Cadastrar Rifa</button>

			<button onclick="javascript: window.open('index.php?p=lotear', '_self');" class="btn btn-primary">Lotear | Imprimir Rifas</button>

			<button onclick="javascript: location.href='index.php?p=buscarrifas';" class="btn btn-success">Pesquisar Rifas por Telefone</button>
		
		</p>



	</div>

	<div class="col-lg-12 text-left table-responsive">

		<?php if($rifa_num == 0) echo "<div class=\"text-center\">Nenhuma rifa foi cadastrada.</div>"; else { ?>



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

					<td><?php if(empty($rifa[rifa_finalizar])) echo "Ativa"; else if($rifa[rifa_finalizar] == 1) echo "Aguardado Finalização"; else  echo "Finalizada"; ?></td>



					<td><?php if($rifa[rifa_maxbilhetes] == 100) echo 100; elseif($rifa[rifa_maxbilhetes] == 999) echo 1000; elseif($rifa[rifa_maxbilhetes] == 1000) echo 1000; elseif($rifa[rifa_maxbilhetes] == 1001) echo 1000; elseif($rifa[rifa_maxbilhetes] == 9999) echo 10000; elseif($rifa[rifa_maxbilhetes] == 10001) echo 10000; elseif($rifa[rifa_maxbilhetes] == 10000) echo 10000; ?></td>

					<td>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></td>

					<td><?php echo $bil_num; ?></td>

					<td><?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></td>



					<td width="40%" class="text-right">



								<!--

								<?php if($rifa[rifa_destaque] != 1) { ?>

								<button onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">destacar rifa</button>

								<?php } ?>

							-->

							<?php if($bil_num > 0 && strlen($rifa[rifa_finalizar]) == 0) { ?>

							<button onclick="javascript: if(confirm('Tem certeza disso?')) window.open('index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>', '_blank');" class="btn btn-success">finalizar rifa</button>

							<?php }else if(strtotime($rifa[rifa_dtsorteio]) < time() && ($rifa[rifa_finalizar]) == 1){ ?>

							<button onclick="javascript: location.href='index.php?p=pagar&rifa=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-success">finalizar rifa</button>

							<?php }else if(strtotime($rifa[rifa_dtsorteio]) > time()){ ?>

							<button <?php if($_SESSION['admin'] != 'S'){  ?> onclick="javascript: alert('Você não pode finalizar uma Rifa antes da data de sorteio programada.');"<?php }else{ ?> onclick="javascript: if(confirm('Tem certeza disso?')) location.href='index.php?p=finalizar&codigo=<?php echo $rifa[rifa_cod]; ?>';" <?php } ?> class="btn btn-success">finalizar rifa</button>

							<?php } ?>



							<?php if($_SESSION['admin'] == 'S') { ?>

							<button onclick="javascript: location.href='index.php?p=deletar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-danger">deletar</button>

							<?php } /*else{ ?>

							<button onclick="javascript: alert('Você não pode excluir uma rifa que já vendeu bilhetes');" class="btn btn-danger">deletar</button>

							<?php }*/ ?>



							<?php if($_SESSION[admin] == 'S') { ?>

							<button onclick="javascript: location.href='index.php?p=rifa&destaque=<?php echo $rifa[rifa_cod]; ?>';" class="btn btn-warning"><?php echo ($rifa[rifa_destaque] == 1)? "remover destaque":"destacar"; ?></button>

							<?php } ?>



							<button onclick="javascript: location.href='index.php?p=revendedor&rifa=<?php echo $rifa[rifa_cod]; ?>';" class="btn">Revendedores</button>



							<button onclick="javascript: location.href='index.php?p=rif_detalhe&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn">detalhes</button>

							<button onclick="javascript: location.href='index.php?p=editar&codigo=<?php echo $rifa[rifa_cod]; ?>';" class="btn">editar</button>





						</td>

					</tr>

					<?php }while($rifa = $rifa_query->fetch_assoc()); ?>

				</table> <?php } ?>

			</div>



