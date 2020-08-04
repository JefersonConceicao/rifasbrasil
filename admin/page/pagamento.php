<?php

//if(isset($_SESSION[usuario]) && $_SESSION[usuario] > 0){
	
if(isset($_POST[enviar])){

	include("../class/conexao.php");

	foreach($_POST as $ch=>$va){
		$_SESSION[$ch] = $mysqli->escape_string($va);
	}

	$erro = Array();

	if(strlen($_SESSION[pagseguro]) > 0){

		if(!filter_var($_SESSION[pagseguro], FILTER_VALIDATE_EMAIL))
			$erro[] = "E-mail inváido";

		if(strlen($_SESSION[token]) == 0)
			$erro[] = "Token inválido";

	}
	if(strlen($_SESSION[banco]) > 0){

		if(strlen($_SESSION[banco]) == 0)
			$erro[] = "Banco inválido";

		if(strlen($_SESSION[conta]) == 0)
			$erro[] = "Conta inváida";

		if(strlen($_SESSION[agencia]) == 0)
			$erro[] = "Agência inváido";

	}

	if(count($erro) == 0){

		$exec = Array();

		if(strlen($_SESSION[banco]) > 0){

			$sqlconta = "INSERT INTO tbl_conta (banco, agencia, conta, outrasinfos, usuario)
			values(
			'$_SESSION[banco]',
			'$_SESSION[agencia]',
			'$_SESSION[conta]',
			'$_SESSION[outrasinfos]',
			'$_SESSION[usuario]'
			)";
			$exec[] = $mysqli->query($sqlconta) or die($mysqli->error);

		}


		if(strlen($_SESSION[pagseguro]) > 0){


			$sqlpagseguro = "UPDATE tbl_usuario 
			SET usu_psemail = '$_SESSION[pagseguro]', usu_pstoken = '$_SESSION[token]' 
			WHERE usu_cod = '$_SESSION[usuario]'";
			$exec[] = $mysqli->query($sqlpagseguro) or die($mysqli->error);
		}

		if(count($exec) >= 1){
			unset($_SESSION[pagseguro], $_SESSION[token], $_SESSION[banco], $_SESSION[conta],$_SESSION[agencia], $_SESSION[outrasinfos]);
			echo "<script>location.href='index.php?p=cadastrar';</script>";
		}
		

	}
}


?><style>

	.esconde{ display:none;}
	.mostra{display:block;}

</style>
<script>
	function mostrar(nomediv){


		if(! $( "#" + nomediv ).hasClass( "esconde" )){
			$('#' + nomediv).removeClass('mostra');
			$('#' + nomediv).addClass('esconde');
		}else{
			$('#' + nomediv).removeClass('esconde');
			$('#' + nomediv).addClass('mostra');
		}

		if($( "#pagseguro_area" ).hasClass( "mostra" ) || $( "#deposito_area" ).hasClass( "mostra" )){
			$('#botao').removeClass('esconde');
			$('#botao').addClass('mostra');
		}else{
			$('#botao').removeClass('mostra');
			$('#botao').addClass('esconde');
		}

	}
</script>

<div class="col-lg-12 text-left">
	<h3>Configurar meio de pagamento</h3>
	<?php if(count($erro) > 0){ ?>
	<div class="bg-warning">
		<?php foreach($erro as $msg) echo "$msg <br>"; ?>
	</div>
	<?php } ?>
	<p>Antes de prosseguir com o cadastro da sua Rifa, você deve preencher as informações de pagamento abaixo</p>
	<p><button onclick="javascript:location.href='index.php?p=rifa';" class="btn">cancelar</button></p>
	<hr>
</div>
<div class="col-lg-6 text-left">
	<form method="post" action="">
		<div class="col-lg-12 form-group">
			<label for="">Como você deseja receber seus pagamentos?</label>
		</div>
		<div class="col-lg-12">

			<label for="pagseguro" class="checkbox-inline">
				<input name="fpagseguro" value="1" id="pagseguro" onclick="javascript: mostrar('pagseguro_area');" type="checkbox">Pagseguro
			</label>
			<label for="deposito" class="checkbox-inline">
				<input  name="fdeposito" value="2" id="deposito" onclick="javascript: mostrar('deposito_area');" type="checkbox">Depósito
			</label>
			
		</div>
		
		<div id="pagseguro_area" class="esconde">
			<div class="col-lg-12">
				<hr>
				<h3>Recebimentos via Pagseguro</h3>
			</div>
			
			<div class="col-lg-6 form-group">
				<label for="">E-mail Pagseguro</label>
				<input type="email" value="<?php echo $_SESSION[pagseguro]; ?>" name="pagseguro" class="form-control">
			</div>
			<div class="col-lg-6 form-group">
				<label for="">Token Pagseguro</label>
				<input type="text" value="<?php echo $_SESSION[token]; ?>" name="token" class="form-control">
			</div>

		</div>
		<div id="deposito_area" class="esconde">
			<div class="col-lg-12"><hr><h3>Recebimentos via Depósito</h3></div>
			<div class="col-lg-4 form-group">
				<label for="">Banco</label>
				<input type="text" value="<?php echo $_SESSION[banco]; ?>" name="banco" class="form-control">
			</div>
			<div class="col-lg-4 form-group">
				<label for="">Agência</label>
				<input type="text" value="<?php echo $_SESSION[agencia]; ?>" name="agencia" class="form-control">
			</div>
			<div class="col-lg-4 form-group">
				<label for="">Conta</label>
				<input type="text" value="<?php echo $_SESSION[conta]; ?>" name="conta" class="form-control">
			</div>
			<div class="col-lg-12 form-group">
				<label for="">Outras Informações Relevantes</label>
				<input type="text" value="<?php echo $_SESSION[outrasinfos]; ?>" name="outrasinfos" class="form-control">
			</div>
		</div>
		<div id="botao" class="esconde">
			<div class="col-lg-12">
				<p></p>
				<button class="btn btn-success" name="enviar" value="1" type="submit">salvar</button> 
			</div>
		</div>

	</form>
</div>
<?php // } else echo "<script>location.href='../index.php?p=entrar';</script>";