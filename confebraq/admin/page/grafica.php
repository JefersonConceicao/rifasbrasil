<?php 
include('../class/conexao.php');

$pagina = explode(",", $_GET[pages]);
$pagina[0] = intval($pagina[0]);
$pagina[1] = intval($pagina[1]);

if($pagina[0] == 0 && $pagina[1] == 0)
	$pagina[1] = 100;


if(isset($_GET[filtrar])){
	$filtro = $mysqli->escape_string($_GET[filtrar]);
	$sql_extra = "(usu_nome like '%{$filtro}%' or usu_email like '%{$filtro}%' or usu_cpf like '%{$filtro}%' or usu_celular like '%{$filtro}%' or usu_residencial like '%{$filtro}%' or usu_psemail like '%{$filtro}%')";
	$sql = "SELECT * FROM `tbl_usuario` WHERE $sql_extra order by usu_nome asc limit $pagina[0], $pagina[1]";
	$result = $mysqli->query($sql) or die($mysqli->error);
}else
	$result = $mysqli->query("SELECT * FROM `tbl_usuario` where usu_grafica = 1 order by usu_nome asc") or die($mysqli->error);

$resultPg = $mysqli->query("SELECT * FROM `tbl_usuario` where usu_grafica = 1") or die($mysqli->error);
$registrosPorPagina = 100;
$paginas =  ceil($resultPg->num_rows/$registrosPorPagina);
$resto = $resultPg->num_rows%$registrosPorPagina;
?>

<div class="col-lg-12 text-left">
	<h3>Gráficas (<?php echo $resultPg->num_rows; ?>)</h3>
	<hr>

	
</div>

		<div class="col-lg-12 text-center">
		
			<button onclick="javascript: location.href='index.php?p=usu_cadastrar&graf=true';" class="btn btn-success">Cadastrar Nova Gráfica</button><p>	</p>
		
		</div>



<div class="col-lg-12">
<?php  
if($resultPg->num_rows > 0){

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

			echo "<td><button class='btn' onclick=\"javascript: location.href='index.php?p=usu_editar&usu_graf=true&usu_cod={$row['usu_cod']}';\">Editar</button> <button class='btn btn-danger' onclick=\"javascript: if(confirm('tem certeza?')) location.href='index.php?p=usu_deletar&usu_cod={$row['usu_cod']}';\">Deletar</button>"; 
			echo "</tr>"; 
		}

	echo "</table>"; 

} else echo "<p class=\"text-center\">Nenhuma Gráfica foi Cadastrada.</p>";

?>

</div>