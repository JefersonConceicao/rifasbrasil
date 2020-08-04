<?php

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

// Usuario logado com um link temporario gerado pelo revendedor
if (isset($_SESSION['usuario_sem_login'])) {
	//echo '<p><b>Revendedor Responsável:</b> ' . $_SESSION['nome_rev'] . '</p>';
	if ($_SESSION['rifa_id'] != $_GET['rifa']) {
		echo "<script>alert('Você não tem acesso aos dados desta rifa');</script>";
		echo "<script>location.href='../index.php?p=entrar';</script>";
	}
}

// Verifica se o usuário veio da página de revenda para exibir as opções de paga/não paga na tela seguinte
if (isset($_GET['origem']) && $_GET['origem'] == 'revenda') {
	$_SESSION['origem_revenda'] = true;
	$origem = 'revenda';
} else {
	unset($_SESSION['origem_revenda']);
}

if ($_POST['action'] == 'get_grupo') {
	include("../../class/conexao.php");
	$rifa = intval($_POST['rifa']);
	$grupo = strtoupper($_POST['grupo']);
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

	$bilhetes = db_select($mysqli, "select bilhete from reserva where rifa = '$rifa'");
	$reservados = array();
	foreach ($bilhetes as $bil) {
		$reservados = array_merge($reservados, str_in_array_de_num_com_chave_como_bilhete($bil['bilhete']));
	}

	$grupos_bilhetes = get_bilhetes_do_grupo($grupo, $rifa);

	$removidos = array();
	foreach ($grupos_bilhetes as $k => $bil) {

		if (in_array($bil, $reservados)) {
			// remove o bilhete ja reservado da lista
			$removidos[] = $bil;
			$grupos_bilhetes[$k] = -1;
			//echo "Bilhete $bil removido.\n";
		}
	}

	$retorno              = array();
	$retorno['removidos'] = implode('-', $removidos);

	$temp = json_decode(file_get_contents("http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php?rifa=" . $rifa . "&layout=1&grupo=" . $grupo), 1);
	foreach ($temp['bilhete'] as $bil) {

		preg_match('/-(.*?)-/', $bil, $match);
		$retorno['bilhetes'][] = $match[1];
	}

	die(json_encode($retorno));
}
include("../class/conexao.php");

$cod_rifa = intval($_GET['rifa']);
unset($_SESSION['repetir_venda']);
$maxbilhetes = DBSelect("select rifa_maxbilhetes as m, dezena_bolao, rifa_dtsorteio FROM tbl_rifas where rifa_cod = '$cod_rifa'", $mysqli);
$dezenabolao = intval($maxbilhetes['dezena_bolao']);

if (strtotime($maxbilhetes['rifa_dtsorteio'] . " 23:59:59") < time())
	die("<script>alert('Rifa vencida'); location.href='index.php?p=rifas';</script>");

$maxbilhetes = $maxbilhetes['m'];

if (!$_SESSION) @session_start();
if ($dezenabolao > 0)
	$_SESSION['rifa_dezena_bolao'] = true;
else
	$_SESSION['rifa_dezena_bolao'] = false;


if (isset($_POST['bilhetes_selecionados'])) {

	$bilhete = explode(";", $_POST['bilhetes_selecionados']);

	unset($bilhete[count($bilhete) - 1]);
	if (count($bilhete) == 0) {
		echo "<script>";
		echo "alert('Selecione os bilhetes que deseja adquirir');";
		echo "</script>";
	} else {
		// Limpar Carrinho
		if (is_array($_SESSION[carrinho_admin]))
			foreach ($_SESSION[carrinho_admin] as $ch => $va) {
				foreach ($_SESSION[carrinho_admin][$ch] as $chave => $valor)
					unset($_SESSION[carrinho_admin][$ch][$chave]);
				unset($_SESSION[carrinho_admin][$ch]);
			}
		$_SESSION[revendedor_admin] = intval($_SESSION[usuario]);
		if (!isset($_SESSION[carrinho_admin]))
			$_SESSION[carrinho_admin] = array();
		if (is_array($bilhete)) {
			if (!isset($_SESSION[carrinho_admin][$cod_rifa]))
				$_SESSION[carrinho_admin][$cod_rifa] = array();
			foreach ($bilhete as $v) {
				$_SESSION[carrinho_admin][$cod_rifa][] = intval($v);
			}
			echo "<script>";
			echo "location.href='index.php?p=cadastro_cliente';";
			echo "</script>";
		}
	}
}
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
			var origem = '<?php echo $origem; ?>';
			var selecionado = document.getElementById('bilhetes_selecionados').value;
			xhttp.open("GET", "page/get_bilhetes.php?origem=" + origem + "&linkMovel=true&maxbilhetes=<?php echo strlen($maxbilhetes) - 1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa=" + rifa + "&selecionado=" + selecionado, true);
			xhttp.send();
		} else {
			document.getElementById("desktop").innerHTML = "<div style='padding:10px;' class='col-lg-12 bg-warning'>Selecione o intervalo de bilhetes que você quer ver.</div>";
		}
	}

	function pesquisar_grupo() {
		var grupo = $('#grupo_inteiro').val();
		if (grupo.length == 0)
			return;
		show_loading();
		$.get('http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php', {
			rifa: <?php echo $codigo; ?>,
			layout: 1,
			grupo: grupo
		}).done(function(res) {
			console.log(res);
			var data = JSON.parse(res);
			console.log(data);
			var str = '<table class="table table-bordered table-hover"><thead><tr><td>Id Rifa</td><td>Nome da Rifa</td><td>Grupo do Bilhete</td><td>Revendedor</td><td>Bilhetes</td></tr></thead>';
			str += "<tbody><tr>";
			str += "<td>" + data.rifa + "</td>";
			str += "<td>" + data.rifa_nome + "</td>";
			str += "<td>" + data.grupo + "</td>";
			if (data.revendedor != '' && data.revendedor != ';' && data.revendedor != null && data.revendedor)
				str += "<td>" + data.revendedor + "</td>";
			else
				str += "<td>Sem Revendedor</td>";
			str += "<td>";
			var bilhete;
			for (var k = 0; k < data.bilhete.length; k++) {
				bilhete = data.bilhete[k];
				bilhete = bilhete.replace(/<br>.*ll>/, '');
				var count = (bilhete.match(/-/g) || []).length;
				if (count == 2)
					bilhete = bilhete.substr(0, bilhete.length - 5);
				str += bilhete + "<BR>";
			}
			str += "</td>";
			str += "</tr></tbody>";
			$('#resultado').html(str);
			end_loading();
		}).fail(function() {
			end_loading();
		});
	}

	function adicionar_grupo_inteiro() {
		show_loading();
		var grupo = $('#grupo_inteiro').val();
		$.post('page/ver_bilhetes.php', {
				grupo: grupo,
				action: 'get_grupo',
				rifa: <?php echo $cod_rifa; ?>
			})
			.done(function(res) {
				end_loading();
				console.log(res);
				var res = JSON.parse(res);
				if (res.erro)
					return alert(res.msg);
				if (!res.bilhetes)
					return alert("Falha ao obter bilhetes!");
				for (var k = 0; k < res.bilhetes.length; k++) {
					if (res.bilhetes[k] >= 0) {
						console.log(k);
						adicionar_bilhete(res.bilhetes[k], <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);
					}
				}
				if (res.removidos.length > 0)
					alert("Os bilhetes à seguir já estão reservados e não foram adicionados: " + res.removidos);
			})
			.fail(function(res) {
				end_loading();
				console.log(res);
			});
	}

	function adicionar_bilhete(valor, rifa, maxbilhetes) {

		var xhttp = new XMLHttpRequest();
		var bil = document.getElementById('bilhetes_selecionados');

		<?php if ($dezenabolao) { ?>

			var count = (bil.value.match(/;/g) || []).length;
			if (count == <?= $dezenabolao; ?>) return alert('Você só pode escolher <?= $dezenabolao; ?> bilhetes por compra');

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

			if (bil.value == "Nenhum") {
				bil.value = "";
				document.getElementById('finalizar').disabled = false;
			}

			var bfinal = padLeft(valor, <?php echo strlen($maxbilhetes) - 1; ?>);

			$('#holder' + bfinal).addClass('bilhete_selected');
			if (document.getElementById("bilhete" + bfinal))
				document.getElementById("bilhete" + bfinal).checked = true;

			if (bil.value.indexOf(bfinal + ";") > -1)
				bil.value += "";
			else
				bil.value += bfinal + ";";

			document.getElementById("result").innerHTML = msg;
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


					if (<?php if($_GET['origem'] == 'revenda') echo 'false'; else echo 'count == 20'; ?>) {
						alert("Você só pode adicionar 20 bilhetes por compra.");
						$("#loader").css("display", "none");
						return;
					} else {
						msg = "Bilhete adicionado";
						$('#result').removeClass('esconder');
						$('#result').removeClass('btn-danger');
						$('#result').addClass('btn-success');
						if (bil.value == "Nenhum") {
							bil.value = "";
							document.getElementById('finalizar').disabled = false;
						}
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

		if (bil.value == "Nenhum") {
			bil.value = "";
			document.getElementById('finalizar').disabled = false;
		}

		if (document.getElementById(campo_id).checked) {

			document.getElementById(campo_id).checked = false;
			$('#' + holder).removeClass('bilhete_selected');
			bil.value = bil.value.replace(id + ";", "");

		} else {

			//Limita o número de bilhetes
			var count = (bil.value.match(/;/g) || []).length;
			<?php if ($dezenabolao) { ?>
				if (count >= <?= $dezenabolao; ?>) {
					alert("Você só pode adicionar <?= $dezenabolao; ?> bilhetes por compra.");
					$("#loader").css("display", "none");
					return;
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
			document.getElementById(campo_id).checked = true;
			bil.value += id + ';';
		}
		if (bil.value.length == 0) {
			bil.value = "Nenhum";
			document.getElementById('finalizar').disabled = true;
		}


	}

	function enviar_formulario() {

		var bilhetes = document.getElementById('bilhetes_selecionados');
		var dezenabolao = <?= ($dezenabolao > 0) ? 'true' : 'false'; ?>;
		var count = (bilhetes.value.match(/;/g) || []).length;
		var form = $('#formulario_real');

		if (dezenabolao) {
			if (count < <?= $dezenabolao; ?>) return alert('Você precisa escolher pelo menos <?= $dezenabolao; ?> bilhetes');
			if (count > <?= $dezenabolao; ?>) return alert('Você pode escolher no máximo <?= $dezenabolao; ?> bilhetes');
			return form.submit();
		} else
			return form.submit();

	}
</script>
<style>
	/*Pagina Rifa*/
	.bilhete-travado {
		cursor: pointer;
		border: 1px solid black;
		background-color: #eaeaea;
		color: black;
		text-align: center;
		padding: 5px 0 5px 0;
	}

	.bilhete-reservado {
		cursor: pointer;
		border: 1px solid brown;
		background-color: brown;
		color: white;
		text-align: center;
		padding: 5px 0 5px 0;
	}

	.bilhete-vendido {
		cursor: pointer;
		border: 1px solid #999;
		background-color: red;
		color: white;
		text-align: center;
		padding: 5px 0 5px 0;
	}

	.bilhete {
		border: 1px solid #75c138;
		background-color: #baffbf;
		text-align: center;
		cursor: pointer;
		padding: 5px 0 5px 0;
	}

	.bilhete_selected {
		border: 1px solid #FFFC45;
		background-color: #FFF04D;
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
</style>
<h3><?= $rifa['rifa_titulo']; ?> <span class="pull-right"><?= implode('/', array_reverse(explode('-', $rifa['rifa_dtsorteio']))); ?> </span></h3>
<hr>
<div class="col-lg-12 text-right form-group form-inline">
	<form action="" id="formulario_real" method="post">
		<label for="">Bilhetes Adicionados: </label>
		<input value="Nenhum" class="form-control" name="bilhetes_selecionados" readonly id="bilhetes_selecionados">
		<button type="button" onclick="enviar_formulario();" disabled id="finalizar" class="btn btn-success">Prosseguir</button>
	</form>
</div>
<div class="col-lg-12"><label for="">Adicionar um Bilhete Específico</label></div>
<div class="col-lg-12 form-group form-inline">
	Digite o número do bilhete desejado:
	<input class="form-control" id="bil_busca" name="bil_busca" type="text">
	<button onclick="javascript: adicionar_bilhete(document.getElementById('bil_busca').value, <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);" class="btn btn-primary">Adicionar</button>
	<div id="result" class="esconder btn"></div> <img id="loader" style="display:none;" src="../img/loader.gif" height="25px" alt="">
</div>
<div class="col-lg-12"><label for="">Adicionar Grupo Inteiro</label></div>
<div class="col-lg-12 form-group form-inline">
	Digite o nome do grupo: <input type="text" id="grupo_inteiro" name="grupo_inteiro" class="form-control">
	<button type="button" onclick="adicionar_grupo_inteiro();" id="adicionar_grupo" class="btn btn-primary">Pesquisar e Adicionar</button>
</div>
<div class="col-lg-12">
	<label for="">Adicionar com base no Intervalo de Bilhetes</label>



</div>
<div class="col-lg-12 form-group form-inline">
	Veja todos os bilhetes no intervalo à seguir:
	<select onchange="javascript: get_bilhetes(this.value, <?php echo $cod_rifa; ?>);" class="form-control" name="" id="">
		<option value=""></option>
		<?php
		$range = 60;
		$i = 0;
		$f = $range;
		$lim = $maxbilhetes - 1;
		while ($f <= $lim) {
			?>
			<option value="<?php echo "$i,$f"; ?>"><?php echo str_pad($i, strlen($maxbilhetes) - 1, "0", STR_PAD_LEFT) . " à " . str_pad(($f - 1), strlen($maxbilhetes) - 1, "0", STR_PAD_LEFT); ?></option>
		<?php
			$i += $range;
			$f += $range;
		}
		if ($i < $lim) {
			$i = $i;
			?>
			<option value="<?php echo "$i," . ($lim + 1); ?>"><?php echo "$i à $lim"; ?></option>
		<?php
		}
		?>
	</select>
	<img id="loader2" style="display:none;" src="../img/loader.gif" height="25px" alt="">
</div>
<div style="padding:35px; text-align:center;">
	<span class="bilhete">00</span> Bilhete disponível
	<span class="bilhete-travado">00</span> Bilhete Travado
	<span class="bilhete-reservado">00</span> Bilhete disponível, mas Reservado
	<span class="bilhete-vendido">00</span> Bilhete indisponível (já vendido)
</div>
<div id="desktop" class="col-lg-12">
</div>
<div class="clearfix"></div>