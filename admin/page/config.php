<?php



include("../class/conexao.php");
include("../../class/function_limparNumero.php");



function formatarCpf($n)
{



	$final = "";

	for ($i = 0; $i < strlen($n); $i++) {

		if (is_numeric($n[$i]))

			$final .= $n[$i];
	}



	return substr($final, 0, 3) . "." . substr($final, 3, 3) . "." . substr($final, 6, 3) . "-" . substr($final, 9, 2);
}



if (isset($_POST[ok])) {



	foreach ($_POST as $ch => $va) {

		$_SESSION[$ch] = $mysqli->escape_string($va);
	}



	$erro = array();



	if (strlen($_SESSION[pagseguro]) > 0 || strlen($_SESSION[token]) > 0) {



		if (strlen($_SESSION[pagseguro]) == 0)

			$erro[] = "E-mail inváido";



		if (strlen($_SESSION[token]) == 0)

			$erro[] = "Token inválido";
	}

	if (strlen($_SESSION[banco]) > 0 || strlen($_SESSION[conta]) > 0  || strlen($_SESSION[agencia]) > 0) {



		if (strlen($_SESSION[banco]) == 0)

			$erro[] = "Banco inválido";



		if (strlen($_SESSION[conta]) == 0)

			$erro[] = "Conta inváida";



		if (strlen($_SESSION[agencia]) == 0)

			$erro[] = "Agência inválido";
	}



	$alterar_senha = false;



	if (strlen($_SESSION[senha]) > 0) {



		if (strcmp($_SESSION[senha], $_SESSION[rsenha]) != 0)

			$erro[] = "As senhas não batem";



		if (strcmp($_SESSION[senha], $_SESSION[rsenha]) == 0 && strlen($_SESSION[senha]) > 0 && strlen($_SESSION[senha]) < 6)

			$erro[] = "A senha deve ter pelo menos 6 caracteres";



		$alterar_senha = true;
	}



	if (count($erro) == 0) {





		$exec = array();



		if (strlen($_SESSION[banco]) > 0 || strlen($_SESSION[conta]) > 0  || strlen($_SESSION[agencia]) > 0) {

			$cpf_limpo = limparNumero($_SESSION['cpf_banco']);


			$contar = DBSelect("SELECT count(*) as c from tbl_conta where usuario = '$_SESSION[usuario]'", $mysqli);

			if ($contar[c] == 0)

				$sqlconta = "INSERT INTO tbl_conta (banco, agencia, conta, outrasinfos, nome_completo, cpf, usuario) values(

				'$_SESSION[banco]',

				'$_SESSION[agencia]',

				'$_SESSION[conta]',

				'$_SESSION[outrasinfos]',

				'$_SESSION[nome_banco]',

				'$cpf_limpo',

				'$_SESSION[usuario]'

				)

			";

			else

				$sqlconta = "UPDATE tbl_conta set 

				banco = '$_SESSION[banco]',

				agencia = '$_SESSION[agencia]',

				conta = '$_SESSION[conta]',

				outrasinfos = '$_SESSION[outrasinfos]',

				nome_completo = '$_SESSION[nome_banco]',

				cpf = '$cpf_limpo'

				WHERE (usuario = '$_SESSION[usuario]') AND
				(codigo = '$_SESSION[codigo_conta_principal]')";





			$exec[] = $mysqli->query($sqlconta) or die($mysqli->error);
		}

		if (strlen($_SESSION[pagseguro]) > 0 || strlen($_SESSION[token]) > 0) {

			if ($alterar_senha) {

				$senha = md5($_SESSION[senha]);

				$sqlpagseguro = "UPDATE tbl_usuario 

				SET usu_psemail = '$_SESSION[pagseguro]', 

				usu_pstoken = '$_SESSION[token]',

				usu_celular = '$_SESSION[telefone]',

				usu_senha = '$senha'

				WHERE usu_cod = '$_SESSION[usuario]'";
			} else {

				$sqlpagseguro = "UPDATE tbl_usuario 

				SET usu_psemail = '$_SESSION[pagseguro]', 

				usu_pstoken = '$_SESSION[token]',

				usu_celular = '$_SESSION[telefone]'

				WHERE usu_cod = '$_SESSION[usuario]'";
			}



			$exec[] = $mysqli->query($sqlpagseguro) or die($mysqli->error);
		}



		if (count($exec) >= 1) {

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

<style>
	.obrigatorio {
		color: red;
	}
</style>


<div class="col-lg-12 text-left">

	<h3>Configurações</h3>

	<p>Campos marcados com <span class="obrigatorio">*</span> são obrigatórios</p>

	<hr>

	<div class="form-group">



	</div>

</div>

<div class="col-lg-12 text-left">



	<?php if (count($erro) > 0) { ?>

		<div class="col-lg-12 bg-warning text-center">

			<div class="col-lg-12 bg-warning">

				<?php foreach ($erro as $msg) echo "<p>{$msg}</p>"; ?>

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

				<input type="text" value="<?php echo $usu[usu_pstoken]; ?>" name="token" class="form-control">

				<div class="spacer"></div>

			</div>


			<p>Informações para depósito</p>

			<div id="panel_contas">
				<div id="div_dados_bancarios">
					<div class="col-lg-4 form-group">

						<label for=""><span class="obrigatorio">*</span>Banco</label>

						<input type="text" id="banco_cadastro" name="banco_cadastro" class="form-control" placeholder="Digite o código do banco">

					</div>
					<div class="col-lg-4 form-group">

						<label for=""><span class="obrigatorio">*</span>Agência</label>

						<input type="text" id="agencia_cadastro" name="agencia_cadastro" class="form-control" placeholder="Digite o número da agência">

					</div>

					<div class="col-lg-4 form-group">

						<label for=""><span class="obrigatorio">*</span>Conta</label>

						<input type="text" id="conta_cadastro" name="conta_cadastro" class="form-control" placeholder="Digite o número da conta">

					</div>

					<div class="col-lg-6 form-group">

						<label for="">Nome do Titular</label>

						<input type="text" id="nome_titular_cadastro" name="nome_titular_cadastro" class="form-control" placeholder="Digite o nome do titular">

					</div>

					<div class="col-lg-6 form-group">

						<label for="">CPF do Titular</label>

						<input type="text" id="cpf_titular_cadastro" name="cpf_titular_cadastro" class="cpf form-control" placeholder="xxx.xxx.xxx-xx">

					</div>

					<div class="col-lg-12 form-group">

						<label for="">Outras Informações Relevantes</label>

						<input type="text" id="outrasinfos_cadastro" name="outrasinfos_cadastro" class="form-control" placeholder="Insira qualquer informação relevante">

					</div>
				</div>
			</div>

			<div class="col-lg-12" style="text-align:center;">
				<button type="button" class="btn btn-success" data-toggle="modal" onclick="criarContaBancaria()">
					Adicionar Nova Conta
				</button>

			</div>

			<div hidden class="col-lg-12" id="outras_contas" style="margin-top:40px">
				<h4>Contas Bancárias</h4>

				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Banco</th>
								<th>Agência</th>
								<th>Conta</th>
								<th>Ação</th>
							</tr>
						</thead>
						<tbody id="tbody_outras_contas">
						</tbody>
					</table>
				</div>
			</div>

		</div>

		<div class="col-lg-6">

			<p>Informações de usuário</p>

			<div class="form-group col-lg-12">

				<label for=""><span class="obrigatorio">*</span>Nome</label>

				<input type="text" disabled required name="nome" value="<?php echo $usu[usu_nome]; ?>" class="input-lg form-control">

			</div>

			<div class="form-group col-lg-12">

				<label for=""><span class="obrigatorio">*</span>E-mail</label>

				<input type="email" disabled required name="email" value="<?php echo $usu[usu_email]; ?>" class="input-lg form-control">

			</div>

			<div class="form-group col-lg-6">

				<label for=""><span class="obrigatorio">*</span>Telefone</label>

				<input type="text" required name="telefone" value="<?php echo $usu[usu_celular]; ?>" class=" input-lg form-control">

			</div>

			<div class="form-group col-lg-6">

				<label for=""><span class="obrigatorio">*</span>CPF</label>

				<input type="text" disabled required name="cpf" value="<?php echo formatarCpf($usu[usu_cpf]); ?>" class="input-lg form-control">

			</div>

			<div class="col-lg-12 form-group">Deixe as senhas em branco se não quiser mudá-las</div>

			<div class="form-group col-lg-6">

				<label for=""><span class="obrigatorio">*</span>Senha</label>

				<input type="text" name="senha" value="" class="input-lg form-control">

			</div>

			<div class="form-group col-lg-6">

				<label for=""><span class="obrigatorio">*</span>Repita a Senha</label>

				<input type="text" name="rsenha" value="" class="input-lg form-control">

			</div>



		</div>



		<div class="form-group col-lg-12 text-center">

			<div class="spacer">

			</div>

			<?php if ($_SESSION['admin'] == 'S' || $_SESSION['grafica']) { ?>

				<button type="button" class="btn btn-lg btn-default" onclick="location.href='index.php?p=config_impressao&tipo=Pequena';">Editar Layout de Rifas PDF</button>

			<?php } ?>

			<button type="submit" name="ok" class="btn-lg btn-success btn">Salvar Configurações</button>


		</div>
	</form>
</div>


<!-- Edit Conta Bancaria Modal -->
<div class="modal fade" id="editContaBancaria" tabindex="-1" role="dialog" aria-labelledby="editContaBancariaLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content modal-lg">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="editContaBancariaLabel">Editando conta bancária</h4>
			</div>
			<div class="modal-body" id="edit_modal_body">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
				<button type="button" class="btn btn-success" onclick="editConta()">Salvar Alterações</button>
			</div>
		</div>
	</div>
</div>

<script>
	window.onload = function() {
		addContaToView();
	};

	function limpaCamposBancarios() {
		$('#div_dados_bancarios').find('input:text').val('');
	}

	function editContaModal(id) {
		$.ajax({
			type: 'POST',
			data: {
				action: 'get_conta',
				data: id
			},
			dataType: 'json',
			url: 'controller/ContaBancaria.php',
			success: function(data) {
				if (data.status === 'error') {
					console.log(data.error);
				} else if (data.status === 'success') {
					$('#edit_modal_body').html('');
					html = '<div hidden id="erros-edit-modal"></div>'

					html += '<form id="edit_dados_bancarios">';
					html += '<div class="row">';
					html += '<div class="col-lg-4 form-group">';
					html += '<label for=""><span class="obrigatorio">*</span>Banco</label> <input type="text" id="banco_edit" name="banco_edit" value="' + data.result['banco'] + '" class="form-control" placeholder="Digite o código do banco">';
					html += '</div>';


					html += '<div class="col-lg-4 form-group">';
					html += '<label for=""><span class="obrigatorio">*</span>Agência</label> <input type="text" id="agencia_edit" name="agencia_edit" value="' + data.result['agencia'] + '" class="form-control" placeholder="Digite o número da agência">';
					html += '</div>';

					html += '<div class="col-lg-4 form-group">';
					html += '<label for=""><span class="obrigatorio">*</span>Conta</label><input type="text" id="conta_edit" name="conta_edit" value="' + data.result['conta'] + '" class="form-control" placeholder="Digite o número da conta">';
					html += '</div>';

					html += '<div class="col-lg-6 form-group">';
					html += '<label for="">Nome do Titular</label><input type="text" class="form-control" id="nome_titular_edit" name="nome_titular_edit" value="' + data.result['nome_completo'] + '" placeholder="Digite o nome do titular">';
					html += '</div>';

					html += '<div class="col-lg-6 form-group">';
					html += '<label for="">CPF do Titular</label><input maxlength="14" class="cpf form-control" id="cpf_titular_edit" name="cpf_titular_edit"  value="' + data.result['cpf'] + '" placeholder="Digite o CPF do titular">';
					html += '</div>';

					html += '<div class="col-lg-12 form-group">';
					html += '<label for="">Outras Informações Relevantes</label><input type="text" class="form-control" id="outrasinfos_edit" name="outrasinfos_edit" value="' + data.result['outrasinfos'] + '" placeholder="Insira qualquer informação relevante">';
					html += '</div>';

					html += '<input hidden id="contaEditModalID" value="' + id + '"';

					html += '</div>';
					html += '</form>';

					$('#edit_modal_body').append(html);

					$('#editContaBancaria').modal();
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
				alert(JSON.stringify(data));
			}
		});
	}

	function editConta() {

		var erros = [];
		var banco = $('#banco_edit'),
			agencia = $('#agencia_edit'),
			conta = $('#conta_edit'),
			cpf = $('#cpf_titular_edit');

		if (banco.val() === '')
			erros.push('Preencha o banco')
		if (agencia.val() === '')
			erros.push('Preencha a agência')
		if (conta.val() === '')
			erros.push('Preencha a conta')
		if (cpf.val() !== "" && (cpf.val().length < 11))
			erros.push('Preencha o CPF adequadamente')


		// se houver erros, mostrar a lista para correção. Se não, prosseguir com o cadastro
		if (erros.length > 0) {
			let html_erros = '<p>Por favor, corrija os seguintes erros na conta bancária: </p><ul>';
			for (var i = 0; i < erros.length; i++) {
				html_erros += '<li style="color:red">' + erros[i] + '</li>'
			}
			html_erros += '</ul>';
			$('#erros-edit-modal').html('');
			$('#erros-edit-modal').append(html_erros);
			$('#erros-edit-modal').show('fast');
		} else {
			var form = $('#edit_dados_bancarios :input').serialize();
			console.log(form);
			var codigo_conta = $('#contaEditModalID').val();
			var codigo_usuario = <?= $_SESSION['usuario'] ?>;
			$('#erros-modal').hide('fast');
			$.ajax({
				type: 'POST',
				data: {
					action: 'edit_conta',
					data: form,
					usuario: codigo_usuario,
					conta: codigo_conta
				},
				dataType: 'json',
				url: 'controller/ContaBancaria.php',
				success: function(data) {
					if (data.status === 'error') {
						alert(data.message);
						addContaToView();
					} else if (data.status === 'success') {
						$('#editContaBancaria').modal('hide');
						addContaToView();
						alert(data.message);
					}
				},
				error: function(data) {
					console.log(JSON.stringify(data));
					//alert(JSON.stringify(data));
				}
			});
		}

	}

	function deleteConta(id) {
		var confirma = confirm("Tem certeza que deseja excluir esta conta?");

		if (confirma === true) {

			var usuario = <?= $_SESSION['usuario'] ?>;

			$.ajax({
				type: 'POST',
				data: {
					action: 'delete_conta',
					usuario: usuario,
					conta: id
				},
				dataType: 'json',
				url: 'controller/ContaBancaria.php',
				success: function(data) {
					if (data.status === 'error') {
						console.log(data.error);
					} else if (data.status === 'success') {
						addContaToView();
					}

				},
				error: function(data) {
					console.log(JSON.stringify(data));
					alert(JSON.stringify(data));
				}
			});

		}


	}

	function addContaToView() {
		var usuario = <?= $_SESSION['usuario'] ?>;
		$.ajax({
			type: 'POST',
			data: {
				action: 'get_contas',
				data: usuario
			},
			dataType: 'json',
			url: 'controller/ContaBancaria.php',
			success: function(data) {
				if (data.status === 'error') {
					console.log(data.error);
				} else if (data.status === 'success') {
					$('#tbody_outras_contas').html('');
					for (let i = 0; i < data.result.length; i++) {
						let html = '<tr>';
						html += '<td>' + data.result[i]['banco'] + '</td>';
						html += '<td>' + data.result[i]['agencia'] + '</td>';
						html += '<td>' + data.result[i]['conta'] + '</td>';
						html += '<td><button type="button" class="btn btn-warning" onclick="editContaModal(' + data.result[i]['codigo'] + ')">Editar</button>  <button onclick="deleteConta(' + data.result[i]['codigo'] + ')" type="button" class="btn btn-danger">Excluir</button></td>';
						html += '</tr>';
						$('#tbody_outras_contas').append(html);
						$('#outras_contas').show();
						//console.log(data.result[i]['codigo'])
					}
					//console.table(data.result);
				}
			},
			error: function(data) {
				console.log(JSON.stringify(data));
				//alert(JSON.stringify(data));
			}
		});

	}


	function criarContaBancaria() {
		var erros = [];
		var banco = $('#banco_cadastro'),
			agencia = $('#agencia_cadastro'),
			conta = $('#conta_cadastro'),
			nome_titular = $('#nome_titular_cadastro'),
			cpf = $('#cpf_titular_cadastro'),
			outras_infos = $('#outrasinfos_cadastro');

		if (banco.val() === '')
			erros.push('Preencha o banco')
		if (agencia.val() === '')
			erros.push('Preencha a agência')
		if (conta.val() === '')
			erros.push('Preencha a conta')
		if (cpf.val() !== "" && (cpf.val().length < 11 || cpf.val().length < 14))
			erros.push('Preencha o CPF adequadamente')


		// se houver erros, mostrar a lista para correção. Se não, prosseguir com o cadastro
		if (erros.length > 0) {
			let html_erros = '<p>Por favor, corrija os seguintes erros na conta bancária: </p><ul>';
			for (var i = 0; i < erros.length; i++) {
				html_erros += '<li style="color:red">' + erros[i] + '</li>'
			}
			html_erros += '</ul>';
			bootbox.alert({
				message: html_erros,
				backdrop: true,
			})
		} else {
			var form = $('#div_dados_bancarios :input').serialize();
			form += ('&codigo_usuario_cadastro=' + <?= $_SESSION['usuario']; ?>);
			$.ajax({
				type: 'POST',
				data: {
					action: 'criar_conta',
					data: form
				},
				dataType: 'json',
				url: 'controller/ContaBancaria.php',
				success: function(data) {
					if (data.status === 'error') {
						alert(data.message);
						addContaToView();
					} else if (data.status === 'success') {
						limpaCamposBancarios();
						addContaToView();
						alert(data.message);
					}
				},
				error: function(data) {
					console.log(JSON.stringify(data));
					//alert(JSON.stringify(data));
				}
			});
		}


	}
</script>