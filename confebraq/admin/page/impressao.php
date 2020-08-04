<?php



include("../class/conexao.php");



if(isset($_GET['impresso'])){



	$mysqli->query("update lote set impresso = 1 where codigo = ".intval($_GET['impresso']));

	echo "<script>Status atualizado!</script>";



}



$rifa = db_select($mysqli, "select r.rifa_titulo, u.usu_nome, u.usu_celular, u.usu_residencial, l.impresso, l.codigo as lote from lote l, tbl_rifas r, tbl_usuario u where l.grafica = '".$_SESSION['usuario']."' and l.rifa = r.rifa_cod and u.usu_cod = r.rifa_dono");



?>



<div class="col-lg-12 form-group">



	<h1 class="page-header">Pedidos de Impressão (<?php echo count($rifa); ?>)</h1>



	<button type="button" onclick="location.href='index.php?p=selecionar_layout';" class="btn btn-default">Gerar PDF</button>



</div>



<div class="col-lg-12 col-md-12">



	<p><label for="">Legenda</label><br><img src="http://rifasbrasil.com.br/confebraq/img/impressa.png" height="30" alt=""> Marcar como impressa</p>



	<table class="table table-hover table-bordered">



		<thead>



			<tr>



				<td>Rifa</td>

					<td></td>

				<td>Cliente</td>



			</tr>



		</thead>



		<tbody>



			<?php foreach($rifa as $r){ ?>



			<tr>



				<td><?php echo $r['rifa_titulo']; ?> </td>

				<td><?php if($r['impresso'] == 0) echo '<a href="index.php?p=impressao&impresso='.$r['lote'].'"><img src="http://rifasbrasil.com.br/img/impressa.png" height="30" alt=""></a>'; else echo "<small>[IMPRESSO]</small>"; ?></td>

				<td><?php echo $r['usu_nome']; ?> <small>Tel(s): <?php echo $r['usu_celular']; ?> <?php echo $r['usu_residencial']; ?></small></td>



			</tr>



			<?php } ?>



		</tbody>



	</table>



</div>