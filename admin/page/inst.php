<?php

include("../class/conexao.php");

if(isset($_POST[ok])){

	$execute = DBExecute("INSERT INTO instituicao (instituicao, cnpj, pessoa, telefone, psemail,cidade, estado) VALUES(
		'".$mysqli->escape_string($_POST[instituicao])."',
		'".$mysqli->escape_string($_POST[cnpj])."',
		'".$mysqli->escape_string($_POST[pessoa])."',
		'".$mysqli->escape_string($_POST[telefone])."',
		'".$mysqli->escape_string($_POST[psemail])."',
		'".$mysqli->escape_string($_POST[cidade])."',
		'".$mysqli->escape_string($_POST[estado])."'
		)", $mysqli);
	if($execute)
		$erro[] = "Instituição cadastrada com sucesso.";
	else
		$erro[] = "Falha ao cadastrar.";

}

if(isset($_GET['deletar'])){

$codigo = intval($_GET['deletar']);
$execute = DBExecute("DELETE FROM instituicao WHERE codigo = '$codigo'", $mysqli);
	if($execute)
		$erro[] = "Instituição deletada com sucesso.";
	else
		$erro[] = "Falha ao deletar.";

echo "<script>location.href='index.php?p=inst';</script>";
exit();

}

$code_inst = "SELECT * FROM instituicao ORDER BY instituicao";
$exec_inst  = DBExecute($code_inst, $mysqli);
$inst = $exec_inst->fetch_assoc();

?>
<div class="col-lg-8 text-left">
	<h3>Instituições de Caridade</h3>
	<hr>
</div>
<div class="col-lg-4 text-left">
	<h3>Cadastrar uma Instituição</h3>
	<hr>
	
	
</div>
<div class="col-lg-12">
	<?php if(count($erro) > 0){ ?>

	<?php foreach($erro as $msg) echo "<div style='margin:10px 0 10px 0; padding:10px;' class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
	
	<?php } ?>
</div>
<div class="col-lg-8 text-left">
	<?php if($exec_inst->num_rows > 0){ ?>
	<table class="table table-bordered table-hover table-responsive">
		
		<tr style="font-weight:bold;">
			<td>Instituição</td>
			<td>CNPJ</td>
			<td>Pessoa de Contato</td>
			<td>Telefone</td>
			<td>E-mail Pagseguro</td>
			<td>Cidade</td>

			<td></td>
		</tr>
		
		<?php do{ ?>
		<tr>
			<td><?php echo $inst[instituicao]; ?></td>
			<td><?php echo $inst[cnpj]; ?></td>
			<td><?php echo $inst[pessoa]; ?></td>
			<td><?php echo $inst[telefone]; ?></td>
			<td><?php echo $inst[psemail]; ?></td>
			<td><?php echo "$inst[cidade]/$inst[estado]"; ?></td>
			<td class="text-right"><button type="button" onclick="javascript: location.href='index.php?p=inst&deletar=<?php echo $inst[codigo]; ?>';" class="btn">Remover</button></td>
		</tr>
		<?php } while($inst = $exec_inst->fetch_assoc()); ?>
	</table>
	<?php } else echo "<p>Nenhuma Instituição foi cadastrada.</p>"; ?>
</div>
<div class="col-lg-4 text-left">
	<form method="post" action="">
		<div class="form-group col-lg-12">
			<label for="">Nome</label>
			<input name="instituicao" required value="" type="text" class="form-control">
		</div>
		<div class="form-group col-lg-6">
			<label for="">CNPJ</label>
			<input name="cnpj" required value="" type="text" class="form-control">
		</div>
		<div class="form-group col-lg-6">
			<label for="">Pessoa de Contato</label>
			<input name="pessoa" required value="" type="text" class="form-control">
		</div>

		<div class="form-group col-lg-4">
			<label for="">Telefone</label>
			<input name="telefone" required value="" type="text" class="form-control">
		</div>
		<div class="form-group col-lg-8">
			<label for="">E-mail Pagseguro</label>
			<input name="psemail" required value="" type="email" class="form-control">
		</div>

		<div class="form-group col-lg-8">
			<label for="">Cidade</label>
			<input name="cidade" required value="" type="text" class="form-control">
		</div>
		<?php $e_est = DBExecute("SELECT * FROM estado ORDER BY est_uf ASC", $mysqli); $est = $e_est->fetch_assoc(); ?>
		<div class="form-group col-lg-4">
			<label for="">Estado</label>
			<select name="estado" required class="form-control">
				<option value="">Selecione</option>
				<?php do{ ?>
				<option value="<?php echo $est[est_uf]; ?>"><?php echo $est[est_uf]; ?></option>
				<?php } while($est = $e_est->fetch_assoc()); ?>
			</select>
		</div>
		<div class="col-lg-12">
			<button name="ok" value="1" class="btn btn-success">Salvar</button>
		</div>
	</form>
</div>