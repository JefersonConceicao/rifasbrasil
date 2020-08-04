<?php 
include('../class/conexao.php');
if(isset($_GET['grafica'])){

	$usuario = intval($_GET['grafica']);
	$mysqli->query("update tbl_usuario set usu_grafica = '1' where usu_cod = '$usuario'");
	echo "<script>alert('O usuário agora é uma gráfica.');</script>";

}
if(isset($_GET['ngrafica'])){

	$usuario = intval($_GET['ngrafica']);
	$mysqli->query("update tbl_usuario set usu_grafica = NULL where usu_cod = '$usuario'");
	echo "<script>alert('O usuário agora não é uma gráfica.');</script>";

}

$pagina = explode(",", $_GET[pages]);
$pagina[0] = intval($pagina[0]);
$pagina[1] = intval($pagina[1]);

if($pagina[0] == 0 && $pagina[1] == 0)
	$pagina[1] = 100;

if(isset($_GET['filtrar'])){
	$filtro = $mysqli->escape_string($_GET['filtrar']);
	$sql_extra = "(usu_nome like '%{$filtro}%' or usu_email like '%{$filtro}%' or usu_cpf like '%{$filtro}%' or usu_celular like '%{$filtro}%' or usu_residencial like '%{$filtro}%' or usu_psemail like '%{$filtro}%')";
	$sql = "SELECT * FROM `tbl_usuario` WHERE $sql_extra order by usu_nome asc limit $pagina[0], $pagina[1]";
	$result = $mysqli->query($sql) or die($mysqli->error);
}else
$result = $mysqli->query("SELECT * FROM `tbl_usuario` order by usu_nome asc limit $pagina[0], $pagina[1]") or die($mysqli->error);

$resultPg = $mysqli->query("SELECT * FROM `tbl_usuario`") or die($mysqli->error);
$registrosPorPagina = 100;
$paginas =  ceil($resultPg->num_rows/$registrosPorPagina);
$resto = $resultPg->num_rows%$registrosPorPagina;
?>
<div class="col-lg-12 text-left">
	<h3>Usuários (<?php echo $resultPg->num_rows; ?>)</h3>
	<hr>
</div>
<div class="col-lg-8 form-inline">
	<form method="get" action="">
		<label for="">Filtrar:</label> 

		<input type="hidden" name="p" value="usuario">

		<input value="<?php echo $filtro; ?>" placeholder="Nome, e-mail, telefone ou cpf" type="text" name="filtrar" class="form-control"> 

		<button type="submit" class="btn btn-success">Filtrar</button>

		<?php if(strlen($filtro) > 0){	 ?><button onclick="javascript: location.href='index.php?p=usuario';" type="button" class="btn">Limpar Filtro</button><?php } ?>

	</form>

</div>

<div class="col-lg-4 text-right">



	<button onclick="javascript: location.href='index.php?p=usu_cadastrar';" class="btn btn-success">Cadastrar Novo Usuário</button><p>	</p>



</div>

<div class="col-lg-12"><hr>



	<div class="col-lg-12 text-center form-group">

		<label for="">Páginas</label> 

		<?php 

		for($i = 0; $i < $paginas; $i++){



			if($pagina[0]/$registrosPorPagina == $i)

				$estilo = "btn-success";

			else

				$estilo = "";



			echo "<button onclick=\"javascript:location.href='index.php?p=usuario&pages=".($i*$registrosPorPagina).",".$registrosPorPagina."';\" class=\"btn btn-sm $estilo \">".($i+1)."</button> "; 

		}

		?>

	</div>

</div>







<div class="col-lg-12">

	<?php  



	echo "<table class='table table-responsive table-bordered' >"; 

	echo "<tr>"; 



	echo "<td><b>Nome</b></td>"; 

	echo "<td><b>Cpf</b></td>"; 



	echo "<td><b>Email</b></td>"; 



	echo "<td><b>Telefone</b></td>"; 



	echo "<td></td></tr>"; 



	while($row = $result->fetch_assoc()){ 

		foreach($row AS $key => $value) { $row[$key] = stripslashes($value); } 

		echo "<tr>";  



		echo "<td valign='top'>" . nl2br( $row['usu_nome']) . "</td>";  

		echo "<td valign='top'>" . nl2br( $row['usu_cpf']) . "</td>";  

		echo "<td valign='top'>" . nl2br( $row['usu_email']) . "</td>";  



		echo "<td valign='top'>" . nl2br( $row['usu_celular']) . "</td>";  



		if($row['usu_grafica'] != 1)

			$btn_extra = "<button onclick=\"javascript: location.href='index.php?p=usuario&grafica=".$row['usu_cod']."';\" class=\"btn btn-default\">Tornar Gráfica</button> ";

		else

			$btn_extra = "<button onclick=\"javascript: location.href='index.php?p=usuario&ngrafica=".$row['usu_cod']."';\" class=\"btn btn-default\">Remover Gráfica</button> ";



		echo "<td>".$btn_extra."<button class='btn' onclick=\"javascript: location.href='index.php?p=usu_editar&usu_cod={$row['usu_cod']}';\">Editar</button> <button class='btn btn-danger' onclick=\"javascript: if(confirm('tem certeza?')) location.href='index.php?p=usu_deletar&usu_cod={$row['usu_cod']}';\">Deletar</button>"; 

		echo "</tr>"; 

	} 

	echo "</table>"; 







	?>



</div>