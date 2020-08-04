<?php

include("../class/conexao.php");
include("../class/function_limparNumero.php");

$rifa_cod = intval($_GET[rifa]);
$erro = Array();

if(isset($_POST[usuario])){

	$remover = $_POST[usuario];
	if($remover > 0){
		if($mysqli->query("DELETE FROM tbl_revendedor WHERE usu_cod = '$remover' AND rifa_cod = '$rifa_cod'"))
			$erro[] = "Revendedor removido com sucesso";
	}

}

if(isset($_POST[rev_email]) && $rifa_cod > 0){

	
	if(!filter_var($_POST[rev_email], FILTER_VALIDATE_EMAIL))
		$erro[] = "Digite um e-mail válido";

	if(strlen(limparNumero($_POST[comissao])) == 0)
		$erro[] = "Preencha a porcentagem de comissão do revendedor.";

	if((limparNumero($_POST[comissao])%1) > 0)
		$erro[] = "A comissão deve ser um número inteiro.";

	if((limparNumero($_POST[comissao])) > 100 || (limparNumero($_POST[comissao])) < 1)
		$erro[] = "A comissão não pode ser maior que 100% nem menor que 1%.";

	if(count($erro) == 0){

		$email = $mysqli->escape_string($_POST[rev_email]);

		$comissao = limparNumero($_POST[comissao]);

		$verif_code = "SELECT usu_cod from tbl_usuario WHERE usu_email = '$email'";
		$verif_query = $mysqli->query($verif_code) or die($mysqli->error);
		$verif = $verif_query->fetch_assoc();
		$verif_num = $verif_query->num_rows;

		$con_code = "SELECT * from tbl_revendedor WHERE usu_cod = '$verif[usu_cod]' and rifa_cod = '$rifa_cod'";
		$con_query = $mysqli->query($con_code) or die($mysqli->error);
		$con = $con_query->fetch_assoc();
		$con_num = $con_query->num_rows;

		if($con_num ==	0){
			if($verif_num == 1){
				$confirm = $mysqli->query("INSERT INTO tbl_revendedor (rifa_cod, usu_cod, comissao) values ('$rifa_cod','$verif[usu_cod]', '$comissao')") or die($mysqli->error);
				if($confirm)
					$erro[] = "Revendedor adicionado com sucesso";
			}else{
				$erro[] = "Nenhum usuário do site possui o e-mail informado. Antes de adicionar um revendedor, peça pra que ele se cadastre.";
			}
		}else
			$erro[] = "Esse revendedor já foi adicionado à essa rifa.";
	}

}


$rev_code = "SELECT * from tbl_usuario u, tbl_revendedor r WHERE u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa_cod'";
$rev_query = $mysqli->query($rev_code) or die($mysqli->error);
$rev = $rev_query->fetch_assoc();
$rev_num = $rev_query->num_rows;

$rifa_code = "SELECT rifa_dono, rifa_titulo FROM tbl_rifas WHERE rifa_cod = '$rifa_cod'";
$rifaq = $mysqli->query($rifa_code) or die($mysqli->error);
$rifa = $rifaq->fetch_assoc();

if($rifa[rifa_dono] == $_SESSION[usuario] || $_SESSION['admin'] == 'S'){

?>
<div class="col-lg-12 text-left">
	<h3>Revendedores</h3>
	<p>Rifa: <?php echo $rifa[rifa_titulo]; ?></p>
	<hr>
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12">
		<?php foreach($erro as $msg) echo "<div class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
		<div class="spacer"></div>
	</div>
	<?php } ?>
	<p>
		<form method="post" action="">
			<div class="col-lg-12 form-group form-inline">
				<label for="">Adicionar um Revendedor</label>

				<input required placeholder="E-mail do revendedor" name="rev_email" type="email" class="form-control"> 
				<input name="comissao" class="form-control" size="4" placeholder="Ex: 15%" type="text">
				<button type="submit" class="btn btn-success">Salvar</button>
			</div>
		</form>
		
	</p>
	
</div>
<div class="col-lg-12 text-left">
	<?php if($rev_num == 0) echo "<div class=\"text-center\">Nenhuma revendedor foi adicionado à essa rifa.</div>"; else { ?>
	
	<table class="table table-hover table-bordered">
		<tr style="font-weight:bold;">
			<td>Rifa</td>
			<td>Revendedor</td>
			<td>Comissão</td>
			<td>Vendas</td>	
			<td  width="30%"></td>
		</tr>
		<?php do { 

			$ven_code = "SELECT count(*) as c 
			FROM tbl_bilhetes 
			WHERE bil_rifa= '$rifa_cod' AND bil_compra IN(Select comp_cod from tbl_compra where comp_revendedor = '$rev[usu_cod]')";
			$ven_q = $mysqli->query($ven_code) or die($mysqli->error);
			$ven = $ven_q->fetch_assoc();

		?>
		<tr>
			<td><?php echo $rifa[rifa_titulo]; ?></td>
			<td><?php echo "$rev[usu_nome] <$rev[usu_email]>"; ?></td>
			<td><?php echo $rev[comissao]; ?>%</td>
			<td><?php echo $ven[c]; ?></td>	
			<td class="text-right">
				<?php if($ven[c] == 0) { ?>
				<form method="post" action="">
					<input name="usuario" value="<?php echo $rev[usu_cod]; ?>" type="hidden">
					<button type="submit" class="btn btn-danger">remover</button>
				</form>
				<?php }else{ ?>
				<button onclick="javascript: alert('Você não pode excluir um revendedor que já vendeu bilhetes');" class="btn btn-danger">remover</button>
				<?php } ?>
				
			</td>
		</tr>
		<?php }while($rev = $rev_query->fetch_assoc()); ?>
	</table> <?php } ?>
</div>
<?php } ?>