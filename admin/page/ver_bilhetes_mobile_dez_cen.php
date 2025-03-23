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


function curl_post_contents($url, $params, $timeout = 10) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, false);

    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36');
    curl_setopt($c, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);

    if(1) {
        // CURLOPT_VERBOSE: TRUE to output verbose information. Writes output to STDERR, 
        // or the file specified using CURLOPT_STDERR.
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
    }

    $result = curl_exec($ch);

    if (!$result) {
        printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
               htmlspecialchars(curl_error($ch)));

        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
    }

    $err = curl_error($ch);
    echo $err;
    curl_close($ch);

    return $result;

}

function enviarWhatsapp ($telefone, $mensagem) {
	$url = 'https://api.z-api.io/instances/38B4EC3BCB08802E919C0A7940722ACE/token/E686C67AC9151520C0D3479D/send-messages';
	$ch = curl_init($url);

	$data = array(
	    'phone' => '55' . preg_replace("/[^0-9]/", "", $telefone),
	    'message' => $mensagem
	);

	$res =  curl_post_contents($url, $data, 60);
	
}

if(isset($_POST['action']) && $_POST['action'] == 'getUserName') {

	if(!isset($_SESSION))
		@session_start();

	include("../../class/conexao.php");

	$telefone = preg_replace('/\D/', '', $_POST['telefone']);
	$usuario = db_select($mysqli, "SELECT usu_nome FROM tbl_usuario WHERE CAST(usu_celular AS UNSIGNED) = '$telefone'", 1);
	$result = array(
		'nome' => $usuario['usu_nome']
	);

	if($_SESSION['cod_rev']) {
		$rev = db_select($mysqli, "SELECT usu_celular FROM tbl_usuario WHERE usu_cod = '" . $_SESSION['cod_rev'] . "'", 1);
		$result['tel_revendedor'] = preg_replace('/\D/', '', $rev['usu_celular']);
	} else {
		$rev = db_select($mysqli, "SELECT usu_celular FROM tbl_usuario WHERE usu_cod IN (SELECT rifa_dono FROM tbl_rifas WHERE rifa_cod = '" . intval($_POST['rifa']) . "')", 1);
		$result['tel_revendedor'] = preg_replace('/\D/', '', $rev['usu_celular']);
	}
	

	die(json_encode($result));
}

if(isset($_POST['action']) && $_POST['action'] == 'enviar_comprovante') {

	if(!isset($_SESSION))
		@session_start();

	if(!isset($_SESSION['usuario']) && !isset($_SESSION['cod_rev']))
		die(json_encode(array('ok' => false)));

	include("../../class/conexao.php");

	$response = array();
	if(strpos($_FILES['file']['type'], 'image') === false)
		$response['error'] = "Formato de arquivo inválido";
    else if ( 0 < $_FILES['file']['error'] )
        $response['error'] = $_FILES['file']['error'];
    else {
    	$pos = strrpos($_FILES['file']['name'], ".");
 		$new_name = md5(time()) . substr($_FILES['file']['name'], $pos);
        $res = move_uploaded_file($_FILES['file']['tmp_name'], '/var/www/nevoahost/c/rifasbrasil.com.br/arquivos/docs/' . $new_name);

        $telefone = preg_replace("/[^0-9]/", "", $_POST['telefone']);
        $usuario = db_select($mysqli, "SELECT usu_nome FROM tbl_usuario WHERE CAST(usu_celular AS UNSIGNED) = '$telefone'", 1);

        $msg = "PAGAMENTO IMFORMADO: O cliente " . $usuario['usu_nome'] . " fez um pagamento, clique no link à seguir para visualizar o comprovante: ". PHP_EOL;
        $msg .= PHP_EOL . "https://rifasbrasil.com.br/arquivos/docs/" . $new_name . PHP_EOL;
        $msg .= "Ou clique no link para falar com o cliente no Whatsaap: https://api.whatsapp.com/send?phone=55" . $telefone;


        $revendedor = false;
        if($_SESSION['cod_rev']) {
			$rev = db_select($mysqli, "SELECT usu_celular FROM tbl_usuario WHERE usu_cod = '" . $_SESSION['cod_rev'] . "'", 1);
			$revendedor = preg_replace('/\D/', '', $rev['usu_celular']);
		} else {
			$rev = db_select($mysqli, "SELECT usu_celular FROM tbl_usuario WHERE usu_cod IN (SELECT rifa_dono FROM tbl_rifas WHERE rifa_cod = '" . intval($_POST['rifa']) . "')", 1);
			$revendedor = preg_replace('/\D/', '', $rev['usu_celular']);
		}

        if($revendedor)
        	enviarWhatsapp ($revendedor, $msg);
        
        $response['ok'] = $res;
        //$response['file'] = "https://rifasbrasil.com.br/arquivos/docs/" . $new_name;
    }
    

	die(json_encode($response));
}

if(isset($_POST['action']) && $_POST['action'] == 'getDetalhes') {

	include("../../class/conexao.php");

	$rifa = intval($_POST['rifa']);
	if(isset($_POST['telefone'])) {

		$telefone = preg_replace('/\D/', '', $_POST['telefone']);
		$bilhetes = db_select($mysqli, "SELECT bil.bil_numero, bil.bil_situacao, comp.comp_situacao, comp.comp_status_revenda FROM tbl_bilhetes bil, tbl_compra comp WHERE bil.bil_rifa = '$rifa' AND bil.bil_compra = comp.comp_cod AND bil.bil_compra IN (SELECT comp_cod FROM tbl_compra WHERE comp_cliente IN (SELECT usu_cod FROM tbl_usuario WHERE CAST(usu_celular AS UNSIGNED) = '$telefone'))");
		die(json_encode($bilhetes));
	}

	
	$bilhete = intval($_POST['bilhete']);
	$usuario = db_select($mysqli, "SELECT usu_nome, usu_celular FROM tbl_usuario WHERE usu_cod IN (SELECT comp_cliente FROM tbl_compra WHERE comp_cod IN (SELECT bil_compra FROM tbl_bilhetes WHERE bil_rifa = '$rifa' AND bil_numero = '$bilhete'))", 1);

	$telefone = preg_replace('/\D/', '', $usuario['usu_celular']);

	$result = array(
		'telefone' => substr($telefone, 0, 2) . ' 9****-' . substr($telefone, -4),
		'nome' => $usuario['usu_nome']
	);

	die(json_encode($result));

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
$maxbilhetes = DBSelect("select rifa_maxbilhetes as m, dezena_bolao, rifa_dtsorteio, rifa_valorbilhete FROM tbl_rifas where rifa_cod = '$cod_rifa'", $mysqli);
$dezenabolao = intval($maxbilhetes['dezena_bolao']);
$valorbilhete = $maxbilhetes['rifa_valorbilhete'];

if (strtotime($maxbilhetes['rifa_dtsorteio'] . " 18:39:59") < time())
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
	function get_count(valor, rifa) {
		var res = valor.split(',');
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (xhttp.readyState == 4 && xhttp.status == 200) {
				let count = JSON.parse(xhttp.responseText.trim());
				console.log(count);
				document.querySelector('#disponiveis_count').text += ` (${count.livres})`;
				document.querySelector('#reservados_count').text += ` (${count.reservados})`;
				document.querySelector('#outros_count').text += ` (${count.outros})`;
				document.querySelector('#vendidos_count').text += ` (${count.vendidos})`;
			}
		};
		var origem = '<?php echo $origem; ?>';
		xhttp.open("GET", "page/get_bilhetes.php?getCount=1&origem=" + origem + "&linkMovel=true&maxbilhetes=<?php echo strlen($maxbilhetes) - 1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa=" + rifa, true);
		xhttp.send();
	}
	function get_bilhetes(valor, rifa, tipo) {
		if(!tipo)
			tipo = 'disponivel';
		if (valor.length > 0) {
			var res = valor.split(',');
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (xhttp.readyState == 1) {
					document.getElementById("loader2").style.display = "";
				}
				if (xhttp.readyState == 4 && xhttp.status == 200) {
					document.getElementById("desktop").innerHTML = xhttp.responseText;
					document.getElementById("loader2").style.display = "none";
				}
			};
			var origem = '<?php echo $origem; ?>';
			var selecionado = document.getElementById('bilhetes_selecionados').value;
			xhttp.open("GET", "page/get_bilhetes.php?origem=" + origem + "&linkMovel=true&maxbilhetes=<?php echo strlen($maxbilhetes) - 1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa=" + rifa + "&selecionado=" + selecionado + '&tipo=' + tipo, true);
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


					if (count == 20) {
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

	function bilheteReservado(bilhete, tipo = 'RESERVADO') {
		// tipo = RESERVADO ou PAGO
		show_loading();
		$.post('page/ver_bilhetes_mobile_dez_cen.php', {rifa: <?php echo $codigo; ?>, bilhete: bilhete, action: 'getDetalhes'}, function(res) {
			end_loading();
			console.log(res);
			res = JSON.parse(res);

			let html = `
				<p>
				NUM: ${bilhete}<BR>
				Nome: ${res.nome}<BR>
				Tel/Zap: ${res.telefone}<BR>
				Status: <b>${tipo}</b><BR>
				</p>
			`;

			$('#custom_infos').html(html);
			$('#customModal').modal('show');

		});


	}

	var valorBilhete = <?= $valorbilhete; ?>;
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
			let count = (bil.value.match(/;/g) || []).length;
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

		let count = (bil.value.match(/;/g) || []).length;
		$('#txtBilhetes').html(`Bilhete(s): <b>${bil.value.replace(/;/g, ', ')}</b> adicionado(s). O que gostaria de Fazer?`);
		$('#somatorio').html(`<b>TOTAL:</b> R$ ${numberFormat(count*valorBilhete, 2, ',', '.')}`);

		$('#modalAdicionado').modal('show');
		$('#btn_continuar_modal_adicionar').off();
		$('#btn_continuar_modal_adicionar').click(enviar_formulario);

		//window.scrollTo(0,document.body.scrollHeight);

	}

	function numberFormat (number, decimals, dec_point, thousands_sep) {
	    // Strip all characters but numerical ones.
	    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	    var n = !isFinite(+number) ? 0 : +number,
	        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
	        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
	        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
	        s = '',
	        toFixedFix = function (n, prec) {
	            var k = Math.pow(10, prec);
	            return '' + Math.round(n * k) / k;
	        };
	    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
	    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	    if (s[0].length > 3) {
	        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	    }
	    if ((s[1] || '').length < prec) {
	        s[1] = s[1] || '';
	        s[1] += new Array(prec - s[1].length + 1).join('0');
	    }
	    return s.join(dec);
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

	function minhas_compras (){
		$('#meus_bilhetes_conteudo').addClass('text-center');
		//$('#enviar_comprovante').hide();
		$('#falar_com_o_adm').off().hide();
		$('#enviar_comprovante').off().hide();
		$('#meus_bilhetes_conteudo').html('');
		$('#meus_bilhetes_modal').modal('show');
		$('#enviar_comprovante').attr('disabled', false);
		$('#falar_com_o_adm').click(function() {
			let telefone = $('#input_telefones').val();
			$.post('page/ver_bilhetes_mobile_dez_cen.php', {telefone: telefone, action: 'getUserName', rifa: <?php echo $codigo; ?>}, function(res) {
				res = JSON.parse(res.trim());
				if(!res.nome)
					return alert("Telefone inválido");
				window.open('https://api.whatsapp.com/send?phone=55' + res.tel_revendedor +  '&text=Ol%C3%A1%2C%20me%20chamo%20' + res.nome + '.%20Gostaria%20de%20informa%C3%A7%C3%B5es...');
			});
		});
		$('#enviar_comprovante').text('Enviar Comprovante');
		$('#enviar_comprovante').click(function() {
			$('#theFile').click();
			$('#theFile').change(function() {
				console.log('upload');

				var form_data = new FormData();
				var file_data = $('#theFile').prop('files')[0];

				if(!file_data)
					return;

				form_data.append('file', file_data);
				form_data.set('action', 'enviar_comprovante');
				form_data.set('rifa', <?php echo $codigo; ?>);
				form_data.set('telefone', $('#input_telefones').val().replace(/\D/g,''));
				$('#enviar_comprovante').text("Aguarde, enviando...");
				$('#enviar_comprovante').attr('disabled', true);
				$.ajax({
					url: 'page/ver_bilhetes_mobile_dez_cen.php', 
					data: form_data, 
					dataType: 'json', // what to expect back from the PHP script
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'post',
					success: function(res) {
						console.log(res);
						$('#enviar_comprovante').attr('disabled', false);
						alert("Comprovante enviado com sucesso!");
						$('#meus_bilhetes_modal').modal('hide');
					},
					error : function(err) {
						alert("Falhou ao enviar comprovante. Avise o administrador.");
						console.log(err);
					}
				});

			});
		});
	}

	function pesquisar_telefone() {
		let telefone = $('#input_telefones').val();
		
		if(telefone.length > 8) {
			show_loading();
			$.post('page/ver_bilhetes_mobile_dez_cen.php', {rifa: <?php echo $codigo; ?>, telefone: telefone, action: 'getDetalhes'}, function(res) {
				end_loading();
				console.log(res);
				res = JSON.parse(res.trim());

				

				if(res.length == 0) {
					$('#meus_bilhetes_conteudo').html('<p>O TELEFONE INFORMADO NÃO POSSUI BILHETES/CERTIFICADOS NESSA CAMPANHA!</p>')
				} else {
					let resultados = '';
					let bil = [];
					let situacao = '';

					let bil_pago = [];
					let bil_reservado = [];
					for(let k in res) {
						situacao = (res[k].comp_situacao == '3' || res[k].comp_situacao == '4' || res[k].comp_status_revenda == '1') ? true : false;
						if(situacao)
							bil_pago.push(('00000' + res[k].bil_numero).slice(<?= (strlen($maxbilhetes)-1)*-1; ?>));
						else
							bil_reservado.push(('00000' + res[k].bil_numero).slice(<?= (strlen($maxbilhetes)-1)*-1; ?>));
					}

					let html = `<p>CONSTA NO TELEFONE CONSULTADO OS SEGUINTES
BILHETES/CERTIFICADOS:</p>`;

					if(bil_pago.length) 
						html += `<p><b>Situação: PAGO</b><br><br>${bil_pago.join(' - ')}</p>`;

					if(bil_reservado.length) 
						html += `<p><b>Situação: RESERVADO</b><br><br>${bil_reservado.join(' - ')}</p>`;

					$('#meus_bilhetes_conteudo').html(html);


					$('#falar_com_o_adm').show();
					$('#enviar_comprovante').show();
					
					
				}

			});
		}
			
	}
</script>

<!-- Modal -->
<div id="meus_bilhetes_modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Minhas Compras</h4>
      </div>
      <div class="modal-body text-center">
        <div class="form-inline">
        	<div class="form-group">
        		<label for="">Telefone:</label> <input id="input_telefones" type="text" class="form-control"> <button onclick="pesquisar_telefone();" class="btn btn-default">Pesquisar</button>
        	</div>
        	<div class="form-group" id="meus_bilhetes_conteudo">
        		
        	</div>
        	<input id="theFile" type="file" style="display:none;">
        	
        </div>
      </div>
      <div class="modal-footer" style="text-align: center;">
        
        
        <button type="button" id="falar_com_o_adm" class="btn btn-success">falar com o ADM</button>
        <button type="button" id="enviar_comprovante" class="btn btn-primary">Enviar Comprovante</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div>

  </div>
</div>


<!-- Modal -->
<div id="customModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Informações</h4>
      </div>
      <div class="modal-body text-center">
        <p id="custom_infos"></p>
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Fechar</button>
      </div>
    </div>

  </div>
</div>

<!-- Modal -->
<div id="modalAdicionado" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Numero Adicionado no Carrinho de Compras!</h4>
      </div>
      <div class="modal-body text-center">
        <p id="txtBilhetes">O que gostaria de fazer?</p>
        <p id="somatorio"></p>
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success pull-left" data-dismiss="modal">Add + Número</button>
        <button id="btn_continuar_modal_adicionar" type="button" class="btn btn-success">Continuar</button>
      </div>
    </div>

  </div>
</div>
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
	#legenda .bilhete-travado {
		cursor: pointer;
		border: 1px solid black;
		background-color: #eaeaea;
		color: black;
		text-align: center;
		padding: 5px;
		border-radius: 50%;
	    width: 20px;
	    height: 20px;
	    display: inline-block;
	    position: relative;
    top: 5px;
	}

	#legenda .bilhete-reservado {
		cursor: pointer;
		border: 1px solid brown;
		background-color: brown;
		color: white;
		text-align: center;
		padding: 5px;
		border-radius: 50%;
	    width: 20px;
	    height: 20px;
	    display: inline-block;
	    position: relative;
    top: 5px;
	}

	#legenda .bilhete-vendido {
		cursor: pointer;
		border: 1px solid #999;
		background-color: red;
		color: white;
		text-align: center;
		padding: 5px;
		border-radius: 50%;
	    width: 20px;
	    height: 20px;
	    display: inline-block;
	    position: relative;
    top: 5px;
	}

	#legenda .bilhete {
		border: 1px solid #75c138;
		background-color: #baffbf;
		text-align: center;
		cursor: pointer;
		padding: 5px;
		border-radius: 50%;
	    width: 20px;
	    height: 20px;
	    display: inline-block;
	    position: relative;
    top: 5px;
	}
</style>
<h3 class="text-center">
	<?= $rifa['rifa_titulo']; ?><br>
	<small><?= implode('/', array_reverse(explode('-', $rifa['rifa_dtsorteio']))); ?></small>
</h3>
<hr>
<?php
	
$fotos = array();
if($rifa['rifa_foto1'] /*&& file_exists('/var/www/nevoahost/c/rifasbrasil.com.br/' . $rifa['rifa_foto1'])*/)
	$fotos[] = $rifa['rifa_foto1'];
if($rifa['rifa_foto2'] /*&& file_exists('/var/www/nevoahost/c/rifasbrasil.com.br/' . $rifa['rifa_foto2'])*/)
	$fotos[] = $rifa['rifa_foto2'];
if($rifa['rifa_foto3'] /*&& file_exists('/var/www/nevoahost/c/rifasbrasil.com.br/' . $rifa['rifa_foto2'])*/)
	$fotos[] = $rifa['rifa_foto3'];

$inicio = true;

if(count($fotos) > 0) {

	?>
	<div class="col-lg-12 " style="margin-bottom:30px;">
		<div class="col-lg-3 center-block">
			<div id="myCarousel" class="carousel slide" data-ride="carousel">
			    <!-- Indicators -->
			    <ol class="carousel-indicators">
			    	<?php 
			    	$k = 0;
					foreach($fotos as $pic) {

						$active = $inicio ? 'class="active"':'';
						$inicio = false;
					?>
					<li data-target="#myCarousel" data-slide-to="<?= $k++; ?>" <?= $active; ?>></li>
					<?php } ?>
			    </ol>

			    <!-- Wrapper for slides -->
			    <div class="carousel-inner">
					
					<?php 
					$inicio = true;
					foreach($fotos as $pic) {

						$active = $inicio ? 'active':'';
						$inicio = false;
					?>
					<div class="item <?= $active; ?>">
						<img src="/<?= $pic; ?>" style="width:100%;">
						<div class="carousel-caption"></div>
					</div>
					<?php } ?>
			  
			    </div>

			    <!-- Left and right controls -->
			    <a class="left carousel-control" href="#myCarousel" data-slide="prev">
			      <span class="glyphicon glyphicon-chevron-left"></span>
			      <span class="sr-only">Previous</span>
			    </a>
			    <a class="right carousel-control" href="#myCarousel" data-slide="next">
			      <span class="glyphicon glyphicon-chevron-right"></span>
			      <span class="sr-only">Next</span>
			    </a>
			</div>
		</div>
	</div>
<?php
}
if($maxbilhetes > 1000) {
?>
<div class="col-lg-12 text-center form-group">
	<select onchange="javascript: get_bilhetes(this.value, <?php echo $cod_rifa; ?>);" class="form-control" name="" id="">
		<option value="">VEJA + NUMEROS AQUI!</option>
		<?php
		$range = 1000;
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
	</select></p>
	<img id="loader2" style="display:none;" src="../img/loader.gif" height="25px" alt="">
</div>
<?php } ?>
<div id="legenda">
	<table style="width:100%;" border="0">
		<tr>
			<td style="padding-bottom:25px;" class="text-center">
				<!--<a href="javascript:void(0);" class="btn btn-default" onclick="minhas_compras();">
					MINHAS COMPRAS
				</a>-->
			</td>
		</tr>
		<tr>
			<td style="padding-bottom:15px;" >
				<a href="javascript:void(0);" id="disponiveis_count" class="btn btn-sm btn-success" onclick="get_bilhetes('0,<?= $maxbilhetes; ?>', <?php echo $cod_rifa; ?>, 'disponivel');">
					<!-- <span class="bilhete"></span> --> LIVRES
				</a>

				<a href="javascript:void(0);" id="reservados_count"  class="btn btn-sm btn-warning pull-right" onclick="get_bilhetes('0,<?= $maxbilhetes; ?>', <?php echo $cod_rifa; ?>, 'reservado');">
					<!-- <span class="bilhete-reservado"></span> --> RESERVADOS
				</a>

			</td>
		</tr>
		<tr>
			<td style="padding-bottom:25px;">
				<a href="javascript:void(0);" id="outros_count"  class="btn btn-sm btn-primary" onclick="get_bilhetes('0,<?= $maxbilhetes; ?>', <?php echo $cod_rifa; ?>, 'travado');">
					<!-- <span class="bilhete-travado"></span> --> VENDAS FÍSICAS
				</a>

				<a href="javascript:void(0);" id="vendidos_count"  class="btn btn-sm btn-danger pull-right" onclick="get_bilhetes('0,<?= $maxbilhetes; ?>', <?php echo $cod_rifa; ?>, 'vendido');">
					<!-- <span class="bilhete-vendido"></span> --> VENDAS ONLINE
				</a>

			</td>
		</tr>
		
	</table>
	<div class="form-group text-center">
		<p><button id="meus_numeros" onclick="minhas_compras();" class="btn btn-warning" style="color:black;background-color: #ffd400;
    border: 1px solid #ffd400;
    font-weight: bold;
    text-transform: uppercase;">Meus números</button></p>
	</div>
	<p><b>Escolha seu bilhete na lista abaixo</b></p>
</div>
<div id="desktop">
</div>
<div class="col-lg-12">
	<div class="clearfix"></div>
	<p><small>Após selecionar seu bilhete clique no botão prosseguir logo abaixo</small></p>
</div>
<div class="col-lg-12 text-right form-group form-inline">
	
	<form action="" id="formulario_real" method="post">
		<label for="">Bilhetes Adicionados: </label>
		<input value="Nenhum" class="form-control" name="bilhetes_selecionados" readonly id="bilhetes_selecionados">
		<button type="button" onclick="enviar_formulario();" disabled id="finalizar" class="btn btn-success">Prosseguir</button>
	</form>
</div>
<div class="clearfix"></div>
<script>
	get_bilhetes('0,<?= $maxbilhetes; ?>', <?php echo $cod_rifa; ?>, 'disponivel');
	get_count('0,<?= $maxbilhetes; ?>', <?php echo $cod_rifa; ?>);
</script>