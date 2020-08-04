<?php

include("../class/conexao.php");

function formatarCpf($n){

	$final = "";
	for($i = 0; $i < strlen($n); $i++){
		if(is_numeric($n[$i]))
			$final .= $n[$i];
	}

	return substr($final, 0, 3).".".substr($final, 3, 3).".".substr($final, 6, 3)."-".substr($final, 9, 2);

}

if(isset($_POST[ok])){

	foreach($_POST as $ch=>$va){
		$_SESSION[$ch] = $mysqli->escape_string($va);
	}

	$erro = Array();

	if(strlen($_SESSION[pagseguro]) > 0 || strlen($_SESSION[token]) > 0){

		if(strlen($_SESSION[pagseguro]) == 0)
			$erro[] = "E-mail inváido";

		if(strlen($_SESSION[token]) == 0)
			$erro[] = "Token inválido";

	}
	if(strlen($_SESSION[banco]) > 0 || strlen($_SESSION[conta]) > 0  || strlen($_SESSION[agencia]) > 0){

		if(strlen($_SESSION[banco]) == 0)
			$erro[] = "Banco inválido";

		if(strlen($_SESSION[conta]) == 0)
			$erro[] = "Conta inváida";

		if(strlen($_SESSION[agencia]) == 0)
			$erro[] = "Agência inválido";

	}

	$alterar_senha = false;

	if(strlen($_SESSION[senha]) > 0){

		if(strcmp($_SESSION[senha], $_SESSION[rsenha]) != 0)
			$erro[] = "As senhas não batem";

		if(strcmp($_SESSION[senha], $_SESSION[rsenha]) == 0 && strlen($_SESSION[senha]) > 0 && strlen($_SESSION[senha]) < 6)
			$erro[] = "A senha deve ter pelo menos 6 caracteres";

		$alterar_senha = true;

	}

	if(count($erro) == 0){


		$exec = Array();

		if(strlen($_SESSION[banco]) > 0 || strlen($_SESSION[conta]) > 0  || strlen($_SESSION[agencia]) > 0){

			$contar = DBSelect("SELECT count(*) as c from tbl_conta where usuario = '$_SESSION[usuario]'", $mysqli);
			if($contar[c] == 0)
				$sqlconta = "INSERT INTO tbl_conta (banco, agencia, conta, outrasinfos, usuario) values(
				'$_SESSION[banco]',
				'$_SESSION[agencia]',
				'$_SESSION[conta]',
				'$_SESSION[outrasinfos]',
				'$_SESSION[usuario]'
				)
			";
			else
				$sqlconta = "UPDATE tbl_conta set 
				banco = '$_SESSION[banco]',
				agencia = '$_SESSION[agencia]',
				conta = '$_SESSION[conta]',
				outrasinfos = '$_SESSION[outrasinfos]'
				WHERE usuario = '$_SESSION[usuario]'
				";

			$exec[] = $mysqli->query($sqlconta) or die($mysqli->error);

		}
		if(strlen($_SESSION[pagseguro]) > 0 || strlen($_SESSION[token]) > 0){

			

			if($alterar_senha){
				$senha = md5($_SESSION[senha]);
				$sqlpagseguro = "UPDATE tbl_usuario 
				SET usu_psemail = '$_SESSION[pagseguro]', 
				usu_pstoken = '$_SESSION[token]',
				usu_celular = '$_SESSION[telefone]',
				usu_senha = '$senha'
				WHERE usu_cod = '$_SESSION[usuario]'";
			}else{
				$sqlpagseguro = "UPDATE tbl_usuario 
				SET usu_psemail = '$_SESSION[pagseguro]', 
				usu_pstoken = '$_SESSION[token]',
				usu_celular = '$_SESSION[telefone]'
				WHERE usu_cod = '$_SESSION[usuario]'";
			}

			$exec[] = $mysqli->query($sqlpagseguro) or die($mysqli->error);
		}

		if(count($exec) >= 1){
			echo "<script>alert('Informações alteradas com sucesso.'); location.href='index.php?p=config';</script>";
		}

	}

}


$usu_code = "SELECT * FROM tbl_usuario WHERE usu_cod = '$_SESSION[usuario]' ";
$usu_query = $mysqli->query($usu_code) or die($mysqli->error);
$usu = $usu_query->fetch_assoc();


$conta_code = "SELECT * FROM tbl_conta WHERE usuario = '$_SESSION[usuario]'";
$conta_query = $mysqli->query($conta_code) or die($mysqli->error);
$conta = $conta_query->fetch_assoc();
?>

<div class="col-lg-12 text-left">
	<h3>Configurações</h3>
	<hr>
	<div class="form-group">
		
	</div>
</div>
<div class="col-lg-12 text-left">
	
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12 bg-warning text-center">
		<div class="col-lg-12 bg-warning">
			<?php foreach($erro as $msg) echo "<p>{$msg}</p>"; ?>
		</div>
	</div>
	<?php } ?>
	
	<form method="post" action="">
		<div class="col-lg-6">
		<p>Informações do Pagseguro</p>
		
		<div class="col-lg-6 form-group">
				<label for="">E-mail Pagseguro</label>
				<input type="email" value="<?php echo $usu[usu_psemail]; ?>" name="pagseguro" class="form-control">
			</div>
			<div class="col-lg-6 form-group">
				<label for="">Token Pagseguro</label>
				<input type="text"  value="<?php echo $usu[usu_pstoken]; ?>" name="token" class="form-control">
				<div class="spacer"></div>
			</div>


			
				<p>Informações para depósito</p>
					
		<div class="col-lg-4 form-group">
				<label for="">Banco</label>
				<input type="text" value="<?php echo $conta[banco]; ?>" name="banco" class="form-control">
			</div>
			<div class="col-lg-4 form-group">
				<label for="">Agência</label>
				<input type="text" value="<?php echo $conta[agencia]; ?>" name="agencia" class="form-control">
			</div>
			<div class="col-lg-4 form-group">
				<label for="">Conta</label>
				<input type="text" value="<?php echo $conta[conta]; ?>"  name="conta" class="form-control">
			</div>
			<div class="col-lg-12 form-group">
				<label for="">Outras Informações Relevantes</label>
				<input type="text" value="<?php echo $conta[outrasinfos]; ?>"  name="outrasinfos" class="form-control">
			</div>
		</div>
		<div class="col-lg-6">
		<p>Informações de usuário</p>
		<div class="form-group col-lg-12">
                            <label for="">Nome*</label>
                            <input type="text" disabled required name="nome" value="<?php echo $usu[usu_nome]; ?>" class="input-lg form-control">
                        </div>
                        <div class="form-group col-lg-12">
                            <label for="">E-mail*</label>
                            <input type="email" disabled required name="email" value="<?php echo $usu[usu_email]; ?>" class="input-lg form-control">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="">Telefone*</label>
                            <input type="text" required name="telefone" value="<?php echo $usu[usu_celular]; ?>" class=" input-lg form-control">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="">CPF*</label>
                            <input type="text" disabled required name="cpf" value="<?php echo formatarCpf($usu[usu_cpf]); ?>" class="input-lg form-control">
                        </div>
                        <div class="col-lg-12 form-group">Deixe as senhas em branco se não quiser mudá-las</div>
                        <div class="form-group col-lg-6">
                            <label for="">Senha*</label>
                            <input type="text"  name="senha" value="" class="input-lg form-control">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="">Repita a Senha*</label>
                            <input type="text"  name="rsenha" value="" class="input-lg form-control">
                        </div>
                       
        </div>
	
		<div class="form-group col-lg-12 text-center">
		 	<div class="spacer">
		</div>
		<button type="button" class="btn btn-lg btn-default" onclick="location.href='index.php?p=config_impressao&tipo=Pequena';">Editar Layout de Rifas PDF</button>
	    <button type="submit" name="ok" class="btn-lg btn-success btn">salvar</button>

    </div> 

    </form> 
</div>
