<?php



include("../class/conexao.php");

if(!$_SESSION) @session_start();

if($_SESSION['admin'] != 'S' && !$_SESSION['grafica'])
	die("<script>location.href='index.php?p=config';</script>");

if(isset($_GET['azul'])){



	$res = intval($_GET['azul']);
	$tipo = $_GET['tipo'];
	$mysqli->query("update impressao_rifas set margem_topo = '$res' where tipo = '$tipo'");

	

}



if(isset($_POST['tipo'])){



	if($_POST['tipo'] == 'Grande' || $_POST['tipo'] == 'Pequena' || $_POST['tipo'] == 'a4'){

		$sql = "UPDATE impressao_rifas SET ";

		foreach($_POST as $k=>$val){

			$sql .= " $k = '".str_replace(',','.', $mysqli->escape_string($val))."',";

		}

		$sql = substr($sql, 0, strlen($sql) - 1);

		$sql .= " where tipo = '".$mysqli->escape_string($_POST['tipo'])."'";

	}





	if($mysqli->query($sql))

		echo "<script>alert('Medidas atualizadas com sucesso.');</script>";



}

$fazul = db_select($mysqli, "select margem_topo from impressao_rifas where tipo = '".$_GET['tipo']."'", 1);

$medida = db_select($mysqli, "select * from impressao_rifas where tipo = '".$mysqli->escape_string($_GET['tipo'])."'", 1);

?>

<div class="col-lg-12 text-left">

	<h3>Configurar Impressão (Rifas <?php echo $_GET['tipo']; ?>s)</h3>

	<hr>

	<div class="form-group">



		<button onclick="location.href='index.php?p=config_impressao&tipo=Grande';" class="btn btn-default">Editar Rifa Grande</button>



		<button onclick="location.href='index.php?p=config_impressao&tipo=Pequena';" class="btn btn-default">Editar Rifa Pequena</button>



		<button onclick="location.href='index.php?p=config_impressao&tipo=a4';" class="btn btn-default">Editar Rifa A4</button>



		<?php if($fazul['margem_topo'] == 10){ ?>

		<button onclick="location.href='index.php?p=config_impressao&tipo=<?php echo $_GET['tipo']; ?>&azul=0';" class="btn btn-primary">Remover Fundo Azul</button>

		<?php }else{ ?>

		<button onclick="location.href='index.php?p=config_impressao&tipo=<?php echo $_GET['tipo']; ?>&azul=10';" class="btn btn-primary">Adicionar Fundo Azul</button>

		<?php } ?>



		





	</div>

</div>

<form action="" method="post">

	<div class="col-lg-4 col-md-4 text-left">



		<input value="<?php echo $_GET['tipo']; ?>" name="tipo" type="hidden">

		

		<div class="form-group">

			<label>Margem Topo</label>

			<input type="text" value="<?php echo $medida['margem_topo']; ?>" name="margem_topo" class="form-control">

		</div>



		<div class="form-group">

			<label>Margem Direita</label>

			<input type="text" value="<?php echo $medida['margem_dir']; ?>" name="margem_dir" class="form-control">

		</div>



		<div class="form-group">

			<label>Margem de Baixo</label>

			<input type="text" value="<?php echo $medida['margem_chao']; ?>" name="margem_chao" class="form-control">

		</div>



		<div class="form-group">

			<label>Margem Esquerda</label>

			<input type="text" value="<?php echo $medida['margem_esq']; ?>" name="margem_esq" class="form-control">

		</div>



		<div class="form-group">

			<label>Altura da Rifa</label>

			<input type="text" value="<?php echo $medida['rifa_altura']; ?>" name="rifa_altura" class="form-control">

		</div>



		<div class="form-group">

			<label>Largura da Rifa</label>

			<input type="text" value="<?php echo $medida['rifa_largura']; ?>" name="rifa_largura" class="form-control">

		</div>



		<div class="form-group">

			<label>Altura do QR Code</label>

			<input type="text" value="<?php echo $medida['qr_code_altura']; ?>" name="qr_code_altura" class="form-control">

		</div>



		<div class="form-group">

			<label>Largura do QR Code</label>

			<input type="text" value="<?php echo $medida['qr_code_largura']; ?>" name="qr_code_largura" class="form-control">

		</div>



		<div class="form-group">

			<label>Altura dos Blocos Brancos</label>

			<input type="text" value="<?php echo $medida['bloco_branco_altura']; ?>" name="bloco_branco_altura" class="form-control">

		</div>



		<div class="form-group">

			<label>Largura dos Blocos Brancos</label>

			<input type="text" value="<?php echo $medida['bloco_branco_largura']; ?>" name="bloco_branco_largura" class="form-control">

		</div>



		<div class="form-group">

			<label>Distância dos Blocos Brancos do Topo</label>

			<input type="text" value="<?php echo $medida['bloco_branco_distancia_topo']; ?>" name="bloco_branco_distancia_topo" class="form-control">

		</div>



		<div class="form-group">

			<label>Distância do Bloco Branco 1 da Esquerda</label>

			<input type="text" value="<?php echo $medida['bloco_branco1_distancia_esquerda']; ?>" name="bloco_branco1_distancia_esquerda" class="form-control">

		</div>



		<div class="form-group">

			<label>Distância do Bloco Branco 2 da Esquerda</label>

			<input type="text" value="<?php echo $medida['bloco_branco2_distancia_esquerda']; ?>" name="bloco_branco2_distancia_esquerda" class="form-control">

		</div>



		<div class="form-group">

			<button type="submit" class="btn btn-primary">Salvar</button>

		</div>

		

	</div>



</form>