<?php



include("../class/conexao.php");







$rifa = db_select($mysqli, "select r.rifa_titulo, u.usu_nome, u.usu_celular, u.usu_residencial from lote l, tbl_rifas r, tbl_usuario u where l.grafica = '".$_SESSION['usuario']."' and l.rifa = r.rifa_cod and u.usu_cod = r.rifa_dono");



?>



<div class="col-lg-12">



	<h1 class="page-header">Selecione o Layout</h1>



	<div class="form-group">

		

		<div class="clearfix"></div>

	</div>



</div>



<div class="col-lg-12 col-md-12">



	<table class="table table-hover table-bordered">



		<thead>



			<tr>



				<td>Layouts</td><td></td><td></td>



			</tr>



		</thead>



		<tbody>

			

			<tr>



				<td valign="middle"><img height="60" src="../img/l5.gif" alt=""></td><td valign="middle"><small>2 rifas (meia folha a4)</small></td><td valign="middle"><button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=5';" class="btn btn-default">Selecionar Este</button></td>



			</tr>





			<tr>



				<td valign="middle"><img src="../img/l1.gif" alt=""></td><td valign="middle"><small>6 rifas pequenas</small></td><td valign="middle"><button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=1';" class="btn btn-default">Selecionar Este</button></td>



			</tr>



			<tr>



				<td valign="middle"><img src="../img/l2.gif" alt=""></td><td valign="middle"><small>3 rifas pequenas</small></td><td valign="middle"><button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=2';" class="btn btn-default">Selecionar Este</button></td>



			</tr>



			<tr>



				<td valign="middle"><img src="../img/l3.gif" alt=""></td><td valign="middle"><small>2 rifas grandes</small></td><td valign="middle"><button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=3';" class="btn btn-default">Selecionar Este</button></td>



			</tr>



			<tr>



				<td valign="middle"><img src="../img/l4.gif" alt=""></td><td valign="middle"><small>2 rifas grandes</small></td><td valign="middle"><button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=4';" class="btn btn-default">Selecionar Este</button></td>



			</tr>


			<tr>



				<td valign="middle"></td><td valign="middle"><small>10 bilhetes</small></td><td valign="middle"><button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=6';" class="btn btn-default">Selecionar Este</button></td>



			</tr>




		</tbody>



	</table>



</div>