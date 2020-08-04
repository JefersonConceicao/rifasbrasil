<?php

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

if (isset($_SESSION['qr_order']))
	$_SESSION['qr_order'] = false;


// Usuario logado com um link temporario gerado pelo revendedor
if (isset($_SESSION['usuario_sem_login'])) {
	if ($_SESSION['rifa_id'] != $_GET['rifa']) {
		echo "<script>alert('Você não tem acesso aos dados desta rifa');</script>";
		echo "<script>location.href='../index.php?p=entrar';</script>";
	}
}

// if rifa nao for dezena bolao e for de 100 bilhetes = essa nova tela

function str_in_array_de_num_com_chave_como_bilhete($bil)
{
	$final = array();
	if (substr_count($bil, '-') == 1) {
		$tmp = explode('-', $bil);
		$start = intval($tmp[0]);
		$end   = intval($tmp[1]);
		if ($tmp[0] > $tmp[1]) {
			$start = intval($tmp[1]);
			$end   = intval($tmp[0]);
		}
		for ($start; $start <= $end; $start++) {
			$final[] = $start;
		}
	} else {
		$var = explode(',', $bil);
		foreach ($var as $v) {
			if (is_numeric(trim($v))) $final[] = intval($v);
		}
	}

	return $final;
}
function get_bilhetes_do_grupo($grupo, $rifa)
{

	$temp = json_decode(file_get_contents("http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php?rifa=" . $rifa . "&layout=1&grupo=" . $grupo), 1);
	$retorno = array();

	foreach ($temp['bilhete'] as $bil) {

		preg_match('/-(.*?)-/', $bil, $match);
		$retorno[] = $match[1];
	}

	return $retorno;
}


include("../class/conexao.php");

$cod_rifa = intval($_GET['rifa']);

$maxbilhetes = DBSelect("select rifa_maxbilhetes as m, dezena_bolao, repetir_dezena, rifa_dtsorteio FROM tbl_rifas where rifa_cod = '$cod_rifa'", $mysqli);
$dezenabolao = intval($maxbilhetes['dezena_bolao']);
$repetirdezena = (intval($maxbilhetes['repetir_dezena']) == 1) ? true : false;

if (strtotime($maxbilhetes['rifa_dtsorteio'] . " 23:59:59") < time())
	die("<script>alert('Rifa vencida'); location.href='index.php?p=rifas';</script>");

$maxbilhetes = $maxbilhetes['m'];

if (!$_SESSION) @session_start();
if ($_GET['limpar_carrinho']) {
	unset($_SESSION['carrinho_admin']);
}

if (isset($_POST['bilhetes_selecionados'])) {

	$bilhete = explode(";", $_POST['bilhetes_selecionados']);

	unset($bilhete[count($bilhete) - 1]);
	if (count($bilhete) == 0) {
		echo "<script>";
		echo "alert('Selecione os bilhetes que deseja adquirir');";
		echo "</script>";
	} else {
		// Limpar Carrinho
		if ($dezenabolao > 0)
			$_SESSION['rifa_dezena_bolao'] = true;
		else
			$_SESSION['rifa_dezena_bolao'] = false;


		$_SESSION[revendedor_admin] = intval($_SESSION[usuario]);
		if (!isset($_SESSION[carrinho_admin]))
			$_SESSION[carrinho_admin] = array();
		if (is_array($bilhete)) {
			if (!isset($_SESSION[carrinho_admin][$cod_rifa]))
				$_SESSION[carrinho_admin][$cod_rifa] = array();
			foreach ($bilhete as $v) {
				$_SESSION[carrinho_admin][$cod_rifa][] = intval($v);
			}

			if ($_POST['voltar_e_inserir_mais'] == 'true')
				echo "";
			else {
				echo "<script>";
				echo "location.href='index.php?p=cadastro_cliente';";
				echo "</script>";
				die();
			}
		}
	}
}

unset($_SESSION['surpresinha_serie']);

$codigo = intval($_GET['rifa']);
$sql_rifas = "SELECT * FROM tbl_rifas WHERE rifa_cod = '$codigo' AND rifa_vencedor iS NULL AND rifa_deletada IS NULL";
$sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);
$rifa = $sql_query->fetch_assoc();
// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas
$sqlBil = "SELECT * FROM tbl_bilhetes WHERE bil_rifa = '$codigo' or (bil_rifa = '$codigo' AND bil_situacao = 'C')";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();
$bilVendidos = array();
if ($queryBil->num_rows > 0)
	do {
		$bilVendidos[] = $bil[bil_numero];
	} while ($bil = $queryBil->fetch_assoc());
?>
<script>
	function padLeft(nr, n, str) {
		return Array(n - String(nr).length + 1).join(str || '0') + nr;
	}

	function get_bilhetes(valor, rifa) {
		if (valor.length > 0) {
			var res = valor.split(',');
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (xhttp.readyState == 1) {
					$("#loader2").css("display", "");
				}
				if (xhttp.readyState == 4 && xhttp.status == 200) {
					document.getElementById("desktop").innerHTML = xhttp.responseText;
					$("#loader2").css("display", "none");
				}
			};
			var selecionado = document.getElementById('bilhetes_selecionados').value;
			xhttp.open("GET", "page/get_bilhetes.php?dezenabolao=1&maxbilhetes=<?php echo strlen($maxbilhetes) - 1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa=" + rifa + "&selecionado=" + selecionado, true);
			xhttp.send();
		} else {
			document.getElementById("desktop").innerHTML = "<div style='padding:10px;' class='col-lg-12 bg-warning'>Selecione o intervalo de bilhetes que você quer ver.</div>";
		}
	}

	function estourou_limite() {

		var bil = document.getElementById('bilhetes_selecionados');
		var count = (bil.value.match(/;/g) || []).length;

		if (count != <?= $dezenabolao; ?>) return console.log('estourou_limite() chamada, porém, não atingiu a quantidade.');

		// quando a quantidade de dezenas é atingida, essa função é chamada
		$('#modal').modal('show');
		$('#texto_do_modal').html("As <?= $dezenabolao; ?> dezenas desta aposta já foram selecionadas. Escolha uma opção abaixo.");
	}

	function random_number() {
		return Math.floor(Math.random() * 100);
	}


	function adicionar_bilhete(valor, rifa, maxbilhetes) {

		var xhttp = new XMLHttpRequest();
		var bil = document.getElementById('bilhetes_selecionados');

		<?php if ($dezenabolao) { ?>

			var count = (bil.value.match(/;/g) || []).length;
			if (count == <?= $dezenabolao; ?>) return estourou_limite();

			if (parseInt(valor) >= maxbilhetes) {
				$('#result').removeClass('esconder');
				$('#result').removeClass('btn-success');
				$('#result').addClass('btn-danger');
				msg = "Os bilhetes dessa rifa vão de 0 à " + (maxbilhetes - 1);
				return document.getElementById("result").innerHTML = msg;
			}

			msg = "Bilhete adicionado";
			$('#result').removeClass('esconder');
			$('#result').removeClass('btn-danger');
			$('#result').addClass('btn-success');

			var bfinal = padLeft(valor, <?php echo strlen($maxbilhetes) - 1; ?>);

			$('#holder' + bfinal).addClass('bilhete_selected');
			if (document.getElementById("bilhete" + bfinal))
				document.getElementById("bilhete" + bfinal).checked = true;

			if (bil.value.indexOf(bfinal + ";") > -1)
				bil.value += "";
			else
				bil.value += bfinal + ";";

			document.getElementById("result").innerHTML = msg;
			setTimeout(function() {
				$('#result').addClass('esconder');
			}, 1000);
			return;

		<?php } ?>

		xhttp.onreadystatechange = function() {
			if (xhttp.readyState == 1) {
				$("#loader").css("display", "");
			}
			if (xhttp.readyState == 4 && xhttp.status == 200) {
				var msg = "";

				console.log(xhttp.responseText);

				data = JSON.parse(xhttp.responseText.trim());

				res = parseInt(data[0]);

				if (res < 1) {
					$('#result').removeClass('esconder');
					$('#result').removeClass('btn-success');
					$('#result').addClass('btn-danger');

				}
				if (valor >= maxbilhetes) {
					$('#result').removeClass('esconder');
					$('#result').removeClass('btn-success');
					$('#result').addClass('btn-danger');
					msg = "Os bilhetes dessa rifa vão de 0 à " + (maxbilhetes - 1);
				}

				/*
				if($ja_vendido){
					echo "[-2, null]";
				}elseif($travado){
					echo "[-1, null]";
				}elseif($reservado){
					echo "[0, '$revendedor']";
				}else
					echo "[1, null]";
				*/
				else if (res == -1)
					msg = "Bilhete disponível, porém travado. Entre em contato com o revendedor: " + data[1];
				else if (res == 0)
					msg = "Bilhete disponível, porém travado. Entre em contato com o administrador: " + data[1];
				else if (res == -2)
					msg = "Bilhete indisponível.";
				else if (res == 1) {
					//Limita o número de bilhetes
					var count = (bil.value.match(/;/g) || []).length;

					if (count == 20) {
						alert("Você só pode adicionar 20 bilhetes por compra.");
						$("#loader").css("display", "none");
						return;
					} else {
						msg = "Bilhete adicionado";
						$('#result').removeClass('esconder');
						$('#result').removeClass('btn-danger');
						$('#result').addClass('btn-success');
						var bfinal = padLeft(valor, <?php echo strlen($maxbilhetes) - 1; ?>);
						$('#holder' + bfinal).addClass('bilhete_selected');
						if (document.getElementById("bilhete" + bfinal))
							document.getElementById("bilhete" + bfinal).checked = true;

						if (bil.value.indexOf(bfinal + ";") > -1)
							bil.value += "";
						else
							bil.value += bfinal + ";";
					}
				}
				document.getElementById("result").innerHTML = msg;
				$("#loader").css("display", "none");
			}
		};

		xhttp.open("GET", "page/adicionar_bilhetes.php?rifa=" + rifa + "&bilhete=" + valor, true);
		xhttp.send();



	}

	function checkar(id) {
		var campo_id = "bilhete" + id;
		var holder = "holder" + id;
		var bil = document.getElementById('bilhetes_selecionados');

		if (document.getElementById(campo_id).checked) {

			document.getElementById(campo_id).checked = false;
			$('#' + holder).removeClass('bilhete_selected');
			bil.value = bil.value.replace(id + ";", "");

		} else {

			//Limita o número de bilhetes
			var count = (bil.value.match(/;/g) || []).length;
			<?php if ($dezenabolao) { ?>
				if (count >= <?= $dezenabolao; ?>) {
					$("#loader").css("display", "none");
					return estourou_limite();
				}
			<?php } else { ?>
				if (count == 5) {
					alert("Você só pode adicionar 5 bilhetes por compra.");
					$("#loader").css("display", "none");
					return;
				}
			<?php } ?>

			//fim

			$('#' + holder).addClass('bilhete_selected');
			<?php if ($repetirdezena == true) { ?>
				setTimeout(function() {
					$('#' + holder).removeClass('bilhete_selected');
				}, 300);
			<?php } else { ?>
				document.getElementById(campo_id).checked = true;
			<?php } ?>
			bil.value += id + ';';

		}

	}

	function nova_aposta() {
		$('#voltar_e_inserir_mais').val('true');
		enviar_formulario();
	}

	function enviar_formulario() {

		var bilhetes = document.getElementById('bilhetes_selecionados');
		var dezenabolao = <?= ($dezenabolao > 0) ? 'true' : 'false'; ?>;
		var count = (bilhetes.value.match(/;/g) || []).length;
		var form = $('#formulario_real');

		if (bilhetes.value.slice(-1) != ';')
			bilhetes.value += ';';

		let todos_bilhetes = bilhetes.value.split(';');

		if (count == 0) {
			alert("Você precisa escolher ao menos um bilhete para prosseguir");
			$("#bilhetes_selecionados").val('');
		}

		if (todos_bilhetes.length > 0 && count != 0) {

			let repetidos = {};
			let lista_repetidos = [];

			for (let i in todos_bilhetes) {
				console.log(i, todos_bilhetes[i]);
				if (i == todos_bilhetes.length - 1)
					break;



				if (isNaN(todos_bilhetes[i]))
					return alert('Bilhete ' + todos_bilhetes[i] + ' inválido');
				todos_bilhetes[i] = ('00' + todos_bilhetes[i].trim()).slice(-2);
				if (!repetidos[todos_bilhetes[i]])
					repetidos[todos_bilhetes[i]] = true;
				else
					lista_repetidos.push(todos_bilhetes[i]);

			}

			todos_bilhetes.pop();
			<?php if ($repetirdezena != true) { ?>
				bilhetes.value = Object.keys(repetidos).join(';') + ';';
			<?php } else { ?>
				bilhetes.value = todos_bilhetes.join(';') + ';';
			<?php } ?>

			if (dezenabolao) {
				if (todos_bilhetes.length < <?= $dezenabolao; ?>) return alert('Você precisa escolher pelo menos <?= $dezenabolao; ?> bilhetes');
				if (todos_bilhetes.length > <?= $dezenabolao; ?>) return alert('Você pode escolher no máximo <?= $dezenabolao; ?> bilhetes');
				<?php if ($repetirdezena != true) { ?>
					if (Object.keys(repetidos).length != <?= $dezenabolao; ?>) return alert('Existem bilhetes repetidos na sua lista: ' + lista_repetidos.join(', ') + '. Remova-os, por favor.');
				<?php } ?>
				return form.submit();
			} else
				return form.submit();
		}

	}

	window.onload = function(e) {
		get_bilhetes('0,100', <?php echo $cod_rifa; ?>);
	}
</script>
<style>
	.col-lg-12 {
		padding-left: 0 !important;
		padding-right: 0 !important;
	}

	.holder {
		background: #F1F5F8;
	}

	/*Pagina Rifa*/
	.bilhete_holder {
		padding: 0 5px 10px 5px;
	}

	.bilhete-travado {
		cursor: pointer;
		border: 1px solid black;
		background-color: crimson;
		color: black;
		text-align: center;
		padding: 7px 0px;
	}

	.bilhete-reservado {
		cursor: pointer;
		background-color: #FE7F1E;
		border-radius: 5px;
		border: 1px solid #FFFFFF;
		color: #FFFFFF;
		text-align: center;
		padding: 7px 0px;
		font-size: 1.2em;
		font-weight: bold;
	}

	.bilhete-vendido {
		cursor: not-allowed;
		background-color: #F41B24;
		border-radius: 5px;
		border: 1px solid #FFFFFF;
		color: #FFFFFF;
		font-weight: bold;
		text-align: center;
		padding: 7px 0px;
		font-size: 1.2em;
	}

	.bilhete {
		cursor: pointer !important;
		padding: 20px;
		border-radius: 5px;
		border: 1px solid #FFFFFF;
		background-color: #B3E73D;
		font-weight: bold;
		text-align: center;
		cursor: pointer;
		padding: 7px 0px;
		font-size: 1.2em;
	}

	.bilhete_selected {
		border: 1px solid #C8E6C9;
		background-color: #C8E6C9;
	}

	.comprar_rifa input[type=checkbox] {
		cursor: pointer;
		display: inline-block;
		display: none;
		font-size: 14px;
		text-align: center;
		font-weight: bold;
		color: #052;
	}

	.comprar_rifa input[type=checkbox]:checked {}

	.esconder {
		display: none;
	}

	#result {
		position: absolute;
		z-index: 999999;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 10px;
		background-color: rgba(0, 0, 0, 0.7);
		color: white;
		text-align: center;
	}

	.color-legend div {
		margin-left: 15px;
		width: 25px;
		border-radius: 3px;
		border: 1px solid white;
		display: inline-block;
	}

	.rifa-descricoes {
		width: 50%;
		margin: auto;
		margin-bottom: 1%;
		height: 2.5em;
	}

	@media (max-width: 900px) {
		.rifa-descricoes {
			width: 100%;
			margin: auto;
			margin-bottom: 1%;
			height: 2.5em;
		}
	}
</style>
<?php
//verifica se o usuario é um usuario de link
if ($_SESSION['usuario_sem_login']) {	?>
	<div class="col-sm-12 text-center">
		<div style="background:	#FE7F1E;" class="rifa-descricoes">
			<p style="text-align: center; vertical-align: middle; line-height: 2.5em; font-weight:bold; color:white"><?= $rifa['rifa_titulo']; ?></p>
		</div>
		<div style="background:	#04A1E5;" class="rifa-descricoes">
			<p style="text-align: center; vertical-align: middle; line-height: 2.5em; font-weight:bold; color:white"><?= $rifa['rifa_descricao']; ?></p>
		</div>
		<div style="background:	#23B247;" class="rifa-descricoes">
			<p style="text-align: center; vertical-align: middle; line-height: 2.5em; font-weight:bold; color:white"><?= $rifa['rifa_proposito']; ?></p>
		</div>
	</div>
<?php } else { ?>
	<div class="col-sm-12 text-center">
		<h5>
			<b><?= $rifa['rifa_titulo']; ?></b> - <?= implode('/', array_reverse(explode('-', $rifa['rifa_dtsorteio']))); ?><br>
		</h5>
	</div>
<?php } ?>
<div class="color-legend" style="margin-top:50px;">
	<div style="background:#b3e33e">&nbsp;</div>
	Livre
	<div style="background:#FE7F1E">&nbsp;</div>
	Reservado
	<div style="background:	#F41B24">&nbsp;</div>
	Pago
</div>
<hr>
<div id="desktop" class="col-lg-12"></div>

<div class="col-xs-12 text-center form-group">
	<form action="index.php?p=ver_bilhetes_bolao_dezena&rifa=<?php echo $_GET['rifa']; ?>" id="formulario_real" method="post">
		<input type="hidden" name="voltar_e_inserir_mais" id="voltar_e_inserir_mais" value="false">
		<textarea rows="3" class="form-control" placeholder="Bilhetes separados por ponto e vírgula, ex: 01;03;12" name="bilhetes_selecionados" id="bilhetes_selecionados"></textarea>
	</form>
	<button type="button" onclick="enviar_formulario();" id="finalizar" class="btn btn-lg btn-success" style="margin:10px;">Prosseguir</button>
</div>
<div class="clearfix"></div>




<!-- Modal -->
<div id="modal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-body">
				<div id="texto_do_modal"></div>
			</div>
			<div class="modal-footer">
				<button type="button" onclick="nova_aposta();" class="btn btn-default" data-dismiss="modal">Nova Aposta</button>
				<button type="button" onclick="enviar_formulario();" class="btn btn-primary" data-dismiss="modal">OK</button>
			</div>
		</div>

	</div>
</div>


<script>
	function scrollOnClick() {
		$([document.documentElement, document.body]).animate({
			scrollTop: $("#finalizar").offset().top
		}, 700);

	}
</script>