<?php

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

if (isset($_SESSION['qr_order']))
	$_SESSION['qr_order'] = false;


// Verifica se o usuário veio da página de revenda para exibir as opções de paga/não paga na tela seguinte
if (isset($_GET['origem']) && $_GET['origem'] == 'revenda') {
	$_SESSION['origem_revenda'] = true;
} else {
	unset($_SESSION['origem_revenda']);
}

// Usuario logado com um link temporario gerado pelo revendedor
if (isset($_SESSION['usuario_sem_login'])) {
	if ($_SESSION['rifa_id'] != $_GET['rifa']) {
		echo "<script>alert('Você não tem acesso aos dados desta rifa');</script>";
		echo "<script>location.href='../index.php?p=entrar';</script>";
	}
}

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

if ($_POST['action'] == 'surpresinha_grupo') {

	include("../../class/conexao.php");
	include("../../class/function_surpresinha.php");
	$rifa = intval($_POST['rifa']);
	$grupo = strtoupper($_POST['grupo']);

	if (!$_SESSION) @session_start();
	$_SESSION['surpresinha_serie'] = array();


	// verifica quais bilhetes fazem parte do grupo
	// se for zero, retorna alguma mensagem de aviso

	// verifica quais bilhetes do grupo já foram vendidos

	$bilhetes = get_bilhetes_do_grupo($grupo, $rifa);
	if (count($bilhetes) == 0)
		die(json_encode(array('error' => 1, 'msg' => "Grupo não existe!")));
	else {

		$oldBilhetes = $bilhetes;
		$bilhetes = array_map("intval", $bilhetes);

		$str = implode(', ', $bilhetes);
		$sql = "SELECT bil.bil_numero, rifas.rifa_maxbilhetes, comp.comp_serie FROM tbl_bilhetes bil, tbl_rifas rifas, tbl_compra comp WHERE bil.bil_rifa = '$rifa' AND rifas.rifa_cod = bil.bil_rifa AND bil.bil_compra = comp.comp_cod AND comp.comp_serie like '{$grupo}%-%' GROUP BY comp.comp_serie ORDER BY bil.bil_numero";
		$bilhetes_vendidos = db_select($mysqli, $sql);

		$resultado = array();
		$ja_vendido = array();

		if (count($bilhetes_vendidos) > 0) {
			foreach ($bilhetes_vendidos as $bil) {
				$num = explode('-', $bil['comp_serie']);
				$num = intval(trim($num[1]));
				if (in_array($num, $bilhetes)) {
					$ja_vendido[$num] = true;
					$resultado['ids'][] = str_pad($num, strlen($bil['rifa_maxbilhetes']) - 1, "0", STR_PAD_LEFT);
					$resultado['strings'][] = implode(' ', gerarDezenas($rifa, $num, true));
				}
			}
		}

		$inputBilhetes = array();
		foreach ($oldBilhetes as $ob) {
			if (!$ja_vendido[intval($ob)]) {
				//$inputBilhetes = array_merge($inputBilhetes, gerarDezenas($rifa, $ob, 1));
				$temp = gerarDezenas($rifa, $ob);
				$inputBilhetes = array_merge($inputBilhetes, $temp);
				$_SESSION['surpresinha_serie'][] = implode(',', $temp);
			}
		}

		if (count($inputBilhetes) == 0)
			die(json_encode(array('error' => 1, 'msg' => "Todos os bilhetes deste grupo já foram vendidos.")));

		die(json_encode(array('error' => 0, 'bilhetes' => $bilhetes, 'msg' => $resultado, 'input' => implode(';', $inputBilhetes) . ";")));
	}


	//die(json_encode());
}
if ($_POST['action'] == 'get_grupo') {
	include("../../class/conexao.php");
	$rifa = intval($_POST['rifa']);
	$grupo = strtoupper($_POST['grupo']);


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

$maxbilhetes = DBSelect("select rifa_maxbilhetes as m, etapa1, etapa2, qtd_dezenas_etapa_1, qtd_dezenas_etapa_2, dezena_bolao, repetir_dezena, rifa_dtsorteio FROM tbl_rifas where rifa_cod = '$cod_rifa'", $mysqli);
$dezenabolao = intval($maxbilhetes['dezena_bolao']);
$repetirdezena = (intval($maxbilhetes['repetir_dezena']) == 1) ? true : false;

$proxima_etapa = false;
if($maxbilhetes['etapa1'] && $maxbilhetes['etapa2'] && $maxbilhetes['qtd_dezenas_etapa_1'] && $maxbilhetes['qtd_dezenas_etapa_2']) {
	if(isset($_GET['etapa']) && $_GET['etapa'] == 2) {
		$dezenabolao = $maxbilhetes['qtd_dezenas_etapa_2'];
		$proxima_etapa = 3;
	} else  {
		$dezenabolao = $maxbilhetes['qtd_dezenas_etapa_1'];
		$proxima_etapa = 2;
	}
}

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
				if(!$proxima_etapa)
					echo "location.href='index.php?p=cadastro_cliente';";
				else if($proxima_etapa == 3)
					echo "location.href='index.php?p=cadastro_cliente';";
				else {
					$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]&etapa=2";
					echo "location.href='" . $actual_link . "';";
				}
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
			xhttp.open("GET", "page/get_bilhetes.php?dezenabolao=1<?php if(isset($_GET['etapa'])) echo '&etapa=' . $_GET['etapa']; ?>&maxbilhetes=<?php echo strlen($maxbilhetes) - 1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa=" + rifa + "&selecionado=" + selecionado, true);
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

	function modalSurpresinhaGrupo() {

		$('#modalSurpresinhaGrupo').modal('show');
		$('#prosseguirGrupoSurpresinha').attr('disabled', true);

		$('#formSurpresinha input').val('');
		$('#procurarGrupoSurpresinha').off();

		$('#procurarGrupoSurpresinha').click(function() {
			var grupo = $('#surpresinhaGrupo').val();
			console.log(grupo);
			if (grupo.length == 0) {
				$('#resultadoGrupoSurpresinha').html('<div class="alert alert-warning">\
  						Digite o grupo.\
					</div>');
			} else {

				$('#resultadoGrupoSurpresinha').html('');

				show_loading();
				$.post('page/ver_bilhetes_bolao_dezena.php', {
						grupo: grupo,
						action: 'surpresinha_grupo',
						rifa: <?= $_GET['rifa']; ?>
					})
					.done(function(r) {
						console.log(r);
						end_loading();
						let data = JSON.parse(r);
						if (data.error) return alert(data.msg);
						else if (data.msg.ids && data.msg.ids.length > 0) {
							$('#resultadoGrupoSurpresinha').append("<p>As seguintes dezenas já foram vendidas: </p>");
							for (let i in data.msg.ids) {
								$('#resultadoGrupoSurpresinha').append("<b>" + data.msg.ids[i] + "</b><br>");
							}
							$('#resultadoGrupoSurpresinha').append("<br><p>Deseja prosseguir ainda assim?</p>");
							$('#formSurpresinha input').val(data.input);
							$('#prosseguirGrupoSurpresinha').attr('disabled', false);
							$('#prosseguirGrupoSurpresinha').click(function() {
								$('#formSurpresinha').submit();
							});
						} else if (data.msg.length == 0) {
							$('#resultadoGrupoSurpresinha').append("<p>Grupo validado! Pode prosseguir.</p>");
							$('#formSurpresinha input').val(data.input);
							$('#prosseguirGrupoSurpresinha').attr('disabled', false);
							$('#prosseguirGrupoSurpresinha').click(function() {
								$('#formSurpresinha').submit();
							});
						}
					})
					.fail(function(r) {
						console.log(r);
						end_loading();
					});
			}
		});

	}

	function surpresinha() {

		var qtd_maxima = <?= $dezenabolao; ?>;
		var bilhetes = [];
		var temp_number = -1;

		$('#bilhetes_selecionados').val('');
		document.getElementById('finalizar').disabled = false;
		$.each($('.bilhete input:checked'), function() {
			checkar($(this).val());
		});

		while (bilhetes.length < qtd_maxima) {

			temp_number = random_number();
			if (bilhetes.indexOf(temp_number) < 0) {
				adicionar_bilhete(temp_number, <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);
				bilhetes[bilhetes.length] = temp_number;
			}

		}

		estourou_limite();

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
					if (res.bilhetes[k] >= 0)
						adicionar_bilhete(res.bilhetes[k], <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);
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

		if (todos_bilhetes.length > 0) {

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
	/*Pagina Rifa*/
	.bilhete_holder {
		padding: 0 5px 10px 5px;
	}

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
</style>
<?php
//verifica se o usuario é um usuario de link
if ($_SESSION['usuario_sem_login']) {	?>
	<div class="col-sm-12 text-center">
		<div style="background:	#FE7F1E; width:50%; margin:auto; margin-bottom:1%; height:2.5em">
			<p style="text-align: center; vertical-align: middle; line-height: 2.5em; font-weight:bold; color:white"><?= $rifa['rifa_titulo']; ?></p>
		</div>
		<div style="background:	#04A1E5; width:50%; margin:auto; margin-bottom:1%; height:2.5em">
			<p style="text-align: center; vertical-align: middle; line-height: 2.5em; font-weight:bold; color:white"><?= $rifa['rifa_descricao']; ?></p>
		</div>
		<div style="background:	#23B247; width:50%; margin:auto; margin-bottom:1%; height:2.5em">
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
<div class="col-xs-12 form-group text-center">
	<small>Escolha um número clicando em cima dele ou digite aqui</small>
</div>
<hr>
<div id="result" class="esconder"></div>
<div class="col-xs-12 form-group text-center">

	<input class="form-control" id="bil_busca" name="bil_busca" type="text">
	<button onclick="javascript: adicionar_bilhete(document.getElementById('bil_busca').value, <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);" class="btn btn-xs btn-primary">Adicionar</button>
	<img id="loader" style="display:none;" src="../img/loader.gif" height="25px" alt="">
</div>
<hr>
<!-- <div class="col-lg-12"><label for="">Adicionar Grupo Inteiro</label></div>
		<div class="col-lg-12 form-group form-inline">
			Digite o nome do grupo: <input type="text" id="grupo_inteiro" name="grupo_inteiro" class="form-control">
			<button type="button" onclick="adicionar_grupo_inteiro();" id="adicionar_grupo" class="btn btn-primary">Pesquisar e Adicionar</button>
		</div>
		<div class="col-lg-12">
			<label for="">Adicionar com base no Intervalo de Bilhetes</label>
		</div> -->

<div class="form-group text-center">
	<button type="button" onclick="javascript: surpresinha();" class="btn btn-warning btn-xs btn-primary">SURPRESINHA</button>
	<button type="button" onclick="javascript: modalSurpresinhaGrupo();" class="btn btn-warning btn-xs">VENDER POR GRUPO</button>
</div>
<!--<div style="padding:35px; text-align:center;">
				<span class="bilhete">00</span> Bilhete disponível
				<span class="bilhete-travado">00</span> Bilhete Travado
				<span class="bilhete-reservado">00</span> Bilhete disponível, mas Reservado
				<span class="bilhete-vendido">00</span> Bilhete indisponível (já vendido)
		</div>-->
<div id="desktop" class="col-lg-12"></div>

<div class="col-xs-12 text-center form-group">
	<form action="index.php?p=ver_bilhetes_bolao_dezena&rifa=<?php echo $_GET['rifa']; ?><?php if ($_GET['origem']) {
																								echo '&origem=' . $_GET['origem'];
																							} ?><?php if (isset($_GET['etapa'])) {
																								echo '&etapa=' . $_GET['etapa'];
																							} ?>" id="formulario_real" method="post">
		<input type="hidden" name="voltar_e_inserir_mais" id="voltar_e_inserir_mais" value="false">
		<textarea rows="3" class="form-control" placeholder="Bilhetes separados por ponto e vírgula, ex: 01;03;12" name="bilhetes_selecionados" id="bilhetes_selecionados"></textarea>
	</form>
	<button type="button" onclick="enviar_formulario();" id="finalizar" class="btn btn-success" style="margin:10px;">Prosseguir</button>
</div>
<div class="clearfix"></div>

<!-- Modal -->
<div id="modalSurpresinhaGrupo" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Venda por Grupo</h4>
			</div>
			<div class="modal-body">
				<div class="col-lg-12">
					<div class="form-group">
						<label for="">Digite o Grupo</label>
						<input type="text" class="form-control" id="surpresinhaGrupo">
					</div>
					<div class="form-group">
						<button id="procurarGrupoSurpresinha" class="btn btn-primary">Procurar</button>
					</div>
					<div class="form-group hidden">
						<form method="POST" action="index.php?p=ver_bilhetes_bolao_dezena&rifa=<?php echo $_GET['rifa']; ?>" id="formSurpresinha">
							<input type="text" name="bilhetes_selecionados" value="" class="form-control">
						</form>
					</div>
					<div class="form-group" id="resultadoGrupoSurpresinha">

					</div>

				</div>
				<div class="clearfix"></div>
			</div>
			<div class="modal-footer">
				<button type="button" id="prosseguirGrupoSurpresinha" class="btn btn-success" data-dismiss="modal">PROSSEGUIR</button>
			</div>
		</div>

	</div>
</div>


<!-- Modal -->
<div id="modal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-body">
				<div id="texto_do_modal"></div>
			</div>
			<div class="modal-footer">
				<button type="button" id="btn_nova_aposta" onclick="nova_aposta();" class="btn btn-default" data-dismiss="modal">Nova Aposta</button>
				<button type="button" id="btn_ok_enviar" onclick="enviar_formulario();" class="btn btn-primary" data-dismiss="modal">OK</button>
				
			</div>
		</div>

	</div>
</div>