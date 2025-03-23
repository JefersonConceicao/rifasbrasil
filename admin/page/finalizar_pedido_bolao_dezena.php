<?php

if (!isset($_SESSION))
	session_start();


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

$path = "../";
if(isset($_GET['ajax']))
	$path = "../../";

if (count($_SESSION['carrinho_admin']) > 0) {

	include($path . "class/function_surpresinha.php");
	include($path . "class/function_curl_get_contents.php");
	include($path . "class/conexao.php");
	include($path . "class/function_enviarSMS.php");
	include($path . "class/function_primeiroNome.php");

	if ($_SESSION['cliente_ja_cadastrado']) {
		$usu_codigo = $_SESSION['cliente_ja_cadastrado'];
		$_SESSION['usuario_admin'] = $_SESSION['cliente_ja_cadastrado'];
	} else {

		$sql_code_usuario =
			"INSERT INTO tbl_usuario
	            (
	            usu_nome,
	            usu_celular,
	           	usu_regiao,
	            usu_admin
	            )VALUES(
	            '{$_SESSION['nome']}',
	            '{$_SESSION['telefone']}',
	            '{$_SESSION['regiao']}',
	            'N'
	            )";



		$confirma_usuario = $mysqli->query($sql_code_usuario) or die($mysqli->error);
		$usu_codigo = $mysqli->insert_id;

		$_SESSION['usuario_admin'] = $usu_codigo;
	}

	//mail($_SESSION[email], "Valide sua conta", "Olá\n\nSua conta foi criada com sucesso.\nClique no link para validar sua conta: http://rifasbrasil.com.br/index.php?p=validar&cod=".md5($usuario)."&email=".$_SESSION[email]."\n\nAtenciosamente,\nRifasBRASIL");
	$celular = $_SESSION['telefone'];
	$nomecliente = $_SESSION['nome'];
	unset($_SESSION['nome'],
	$_SESSION['email'],
	$_SESSION['telefone'],
	$_SESSION['regiao'],
	$_SESSION['cpf'],
	$_SESSION['senha'],
	$senha,
	$_SESSION['rsenha']);


	// FIM

	$total_somado = 0;
	$lista_de_compras = array();
	$lista_de_series = array();


	// Calcular valor Total

	foreach ($_SESSION['carrinho_admin'] as $ch => $v) {
		$sql_add .= " rifa_cod = '$ch' OR";
	}
	$sql_add = substr($sql_add, 0, -2);

	$sql_code = "SELECT rifa_cod, dezena_bolao, coluna_grupo_bd, travar_bilhetes, rifa_maxbilhetes, rifa_valorbilhete, rifa_titulo 
		FROM tbl_rifas 
		WHERE ({$sql_add}) 
		AND (rifa_vencedor IS NULL OR rifa_vencedor = '')";

	$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
	$rifa_tmp = $sql_query->fetch_assoc();

	$travarBilhete = false;
	$valor_total = 0;
	$dezenabolao = 0;
	$valor_bilhete = 0;
	$coluna_grupo_qtd = -1;

	$url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes_mobile_dez_cen&limpar_carrinho=true&rifa=';

	do {
		$travarBilhete = $rifa_tmp['travar_bilhetes'] && $rifa_tmp['dezena_bolao'];
		$dezenabolao = $rifa_tmp['dezena_bolao'];
		$valor_bilhete = $rifa_tmp['rifa_valorbilhete'];
		$valor_total += (count($_SESSION['carrinho_admin'][$rifa_tmp['rifa_cod']]) * $rifa_tmp['rifa_valorbilhete']);

		$coluna_grupo_qtd = intval($rifa_tmp['coluna_grupo_bd']);

		if ($rifa_tmp['dezena_bolao'] > 0) {
		  $url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes_bolao_dezena&limpar_carrinho=true&rifa=';
		} else if ($rifa_tmp['rifa_maxbilhetes'] == 100 && $rifa_tmp['dezena_bolao'] == 0) {
		  $url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes_centena&limpar_carrinho=true&rifa=';
		} else if (($rifa_tmp['rifa_maxbilhetes'] == 1000 || $rifa_tmp['rifa_maxbilhetes'] == 10000) && $rifa_tmp['dezena_bolao'] == 0) {
		  $url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes_mobile_dez_cen&limpar_carrinho=true&rifa=';
		} else {
		  $url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes&limpar_carrinho=true&rifa=';
		}

	} while ($rifa_tmp = $sql_query->fetch_assoc());

	// Fim


	// Verifica se alguma das rifas tem Bug
	$erro = 0;
	foreach ($_SESSION['carrinho_admin'] as $cod_rifa => $void)
		if (count($_SESSION['carrinho_admin'][$cod_rifa]) == 0)
			$erro++;

	//FIM

	if ($erro > 0) {

		//Nenhum bilhete foi selecionado para alguma rifa.
		echo "<script>";
		echo "alert('Selecione os bilhetes que deseja adquirir na página à seguir.');";
		echo "location.href='index.php?p=ver_bilhetes&rifa=" . intval($cod_rifa) . "';";
		echo "</script>";
	} else {


		if ($_SESSION['tipo_venda'] == 'paga')
			$sta_revenda = 1;
		else
			$sta_revenda = 0;

		$situacao = "";

		// Finalizar Registro
		$comp_desconto = 0;
		if ($_GET['desconto']) {
			$desconto = floatval($_GET['desconto']);
			$comp_desconto = $desconto;
			$valor_total -= $desconto;
		}

		$array_bilhetes_serie = array();
		foreach ($_SESSION['carrinho_admin'] as $ch => $v) {
			$array_bilhetes_serie = array();
			$array_bilhetes = array();
			foreach ($_SESSION['carrinho_admin'][$ch] as $ch2 => $bil) {
				$array_bilhetes_serie[] = str_pad($bil, 2, '0', STR_PAD_LEFT);
				$array_bilhetes[] = " b.bil_bilhete_original = '$bil' ";
			}

			if($travarBilhete) {
				$sql_query = $mysqli->query("SELECT COUNT(*) as num FROM tbl_bilhetes b, tbl_compra c WHERE c.comp_cod = b.bil_compra AND b.bil_rifa = '$cod_rifa' AND (c.comp_situacao = 7 OR c.comp_situacao = '3' OR c.comp_situacao = '4' OR c.comp_status_revenda = '1') AND (" . implode(" OR ", $array_bilhetes) . ")") or die($mysqli->error);
				$rifa_tmp = $sql_query->fetch_assoc();

				if(intval($rifa_tmp['num']) > 0) {
					$url .= $cod_rifa;
					die("<script>alert('Um dos bilhetes que você escolheu foi vendido enquanto você o tinha no carrinho. Selecione outro.');location.href='{$url}';</script>");
				}
			}

		}

		if ($_SESSION['qr_order']) {

			$consulta_serie = DBSelect("
					SELECT serie 
					FROM loteamento_rifa_bilhetes 
					WHERE sequencia = '" . implode('-', $array_bilhetes_serie) . "' AND rifa = '$cod_rifa' LIMIT 1", $mysqli);
			$serie_array = array($consulta_serie['serie']);

			unset($_SESSION['qr_order']);
		}

		$total_somado = 0;
		$cod_compra = array();
		$compra_nova = false;
		if(isset($_SESSION['compra_ja_feita']) && is_array($_SESSION['compra_ja_feita']) && $travarBilhete) { // so rifa travada vai ter edicao multipla

			$delete_array = array();
			
			foreach($_SESSION['compra_ja_feita'] as $cod_bilhete => $id_compra) {
				$sql_compra = "UPDATE tbl_compra set comp_valortotal = $valor_bilhete, comp_data = NOW(), comp_revendedor = {$_SESSION['cod_rev']}, comp_status_revenda = 1, comp_situacao = '', comp_cliente = {$_SESSION['usuario_admin']} where comp_cod = '$id_compra'";
				$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
				$delete_array[] = " bil_compra = '$id_compra' ";
				$cod_compra[$cod_bilhete] = $id_compra;
				$total_somado += ($valor_bilhete);
			}
			$mysqli->query("DELETE FROM tbl_bilhetes where " . implode(' OR ', $delete_array)) or die($mysqli->error);


		} else if (isset($_SESSION['compra_ja_feita'])) {
			$sql_compra = "UPDATE tbl_compra set comp_valortotal = $valor_total where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";
			if($travarBilhete) {
				$sql_compra = "UPDATE tbl_compra set comp_valortotal = $valor_total, comp_data = NOW(), comp_revendedor = {$_SESSION['cod_rev']}, comp_status_revenda = 1, comp_situacao = '', comp_cliente = {$_SESSION['usuario_admin']} where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";
			}
			$total_somado += $valor_bilhete;
			if ($_SESSION['entrada'] && $_SESSION['proximo_pagamento']) {

				if (($valor_total - $_SESSION['entrada']) <= 0)
					$sql_compra = "UPDATE tbl_compra set 
						comp_valortotal = '$valor_total', 
						comp_desconto = '$comp_desconto',
						comp_parcelamento_data = NULL,
						comp_parcelamento_entrada = NULL,
						comp_serie = '$serie',
						comp_status_revenda = 1,
						comp_situacao = ''

						where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";
				else
					$sql_compra = "UPDATE tbl_compra set 
						comp_valortotal = '$valor_total', 
						comp_desconto = '$comp_desconto',
						comp_serie = '$serie',
						comp_parcelamento_data = '" . date('Y-m-d', $_SESSION['proximo_pagamento']) . "',
						comp_parcelamento_entrada = '" . floatval($_SESSION['entrada']) . "'
						where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";
			}

			$cod_compra = $_SESSION['compra_ja_feita'];
			$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
			$mysqli->query("DELETE FROM tbl_bilhetes where bil_compra = '$cod_compra'") or die($mysqli->error);

		} else {

			$compras  = array();
			$qtd      = 0;
			$count    = 0;
			$n_compra = 0;

			if ($_SESSION['surpresinha_serie']) {
				$serie_array = array();
				foreach ($_SESSION['surpresinha_serie'] as $ss) {

					$arr = explode(',', $ss);
					$final_arr = array();
					foreach ($arr as $d) {
						$final_arr[] = str_pad($d, 2, '0', STR_PAD_LEFT);
					}
					$consulta_serie = DBSelect("
							SELECT serie 
							FROM loteamento_rifa_bilhetes 
							WHERE sequencia = '" . implode('-', $final_arr) . "' AND rifa = '$cod_rifa' LIMIT 1", $mysqli);
					$serie_array[] = $consulta_serie['serie'];
				}
			}



			$lista_de_rifas = array();
			$compra = array();
			foreach ($_SESSION['carrinho_admin'] as $rifa => $void) {
				foreach ($_SESSION['carrinho_admin'][$rifa] as $k => $bilhete) {
					$lista_de_rifas[$rifa] = true;
					$compra[$n_compra] = true;
					$qtd++;
					if ($qtd == $dezenabolao) {
						$qtd = 0;
						$compra[$n_compra++] = true;
						$count++;
					}
				}
			}


			unset($_SESSION['surpresinha_serie']);
			if (isset($_SESSION['series_customizadas'])) {
				$serie_array = $_SESSION['series_customizadas'];
				foreach ($serie_array as $k => $v) {
					if (strpos($v, '-') === false)
						$serie_array[$k] = str_pad($v, 4, '0', STR_PAD_LEFT);
					else
						$serie_array[$k] = $v;
				}
			}

			if (count($lista_de_rifas) > 0) {
				$todas_keys = implode(',', array_keys($lista_de_rifas));
				$run_query = $mysqli->query("SELECT c.comp_serie FROM tbl_compra c WHERE c.comp_serie != '' AND c.comp_serie IS NOT NULL AND c.comp_cod IN (SELECT bil_compra FROM tbl_bilhetes WHERE bil_rifa IN ($todas_keys) GROUP BY bil_compra)") or die($mysqli->error);
				while ($get_data = $run_query->fetch_assoc()) {
					if (in_array($get_data['comp_serie'], $serie_array))
						die("<script>alert('Duas vendas foram feitas ao mesmo tempo. Você será redirecionado ao carrinho.'); location.href='index.php?p=carrinho'; </script>");
				}
			}

			$query_grupo = $mysqli->query("SELECT MAX(comp_grupo) as grupo, COUNT(*) as num
				FROM tbl_compra
				WHERE comp_cod IN (
					SELECT bil_compra 
					FROM tbl_bilhetes 
					WHERE bil_rifa = '$cod_rifa'
					GROUP BY bil_compra
				)
				AND comp_grupo IS NOT NULL
				GROUP BY comp_grupo
                ORDER BY grupo DESC
                LIMIT 1
			");
			if($query_grupo->num_rows > 0) {

				$grupo = $query_grupo->fetch_assoc();
				if($grupo['num'] < $coluna_grupo_qtd)
					$grupo = intval($grupo['grupo']);
				else
					$grupo = intval($grupo['grupo'])+1;

			} else
				$grupo = 27;

			$l = 0;
			foreach ($compra as $k => $void) {

				$valor_total = $dezenabolao * $valor_bilhete;

				if (isset($_SESSION['usuario_sem_login'])) {
					$sql_compra = "INSERT INTO tbl_compra(
							comp_cliente,
							comp_data,
							comp_desconto,
							comp_valortotal,
							comp_revendedor,
							comp_debitor,
							comp_situacao,
							comp_status_revenda,
							comp_serie,
							comp_grupo
							) VALUES(
							'{$_SESSION['usuario_admin']}',
							NOW(),
							'$comp_desconto',
							'$valor_total',
							'{$_SESSION['cod_rev']}',
							'{$_SESSION['cod_rev']}',
							'$situacao',
							'$sta_revenda',
							'" . $serie_array[$k] . "',
							'$grupo')
							";
				} else {
					$sql_compra = "INSERT INTO tbl_compra(
						comp_cliente,
						comp_data,
						comp_desconto,
						comp_valortotal,
						comp_revendedor,
						comp_debitor,
						comp_situacao,
						comp_status_revenda,
						comp_serie,
						comp_grupo
						) VALUES(
						'{$_SESSION['usuario_admin']}',
						NOW(),
						'$comp_desconto',
						'$valor_total',
						'{$_SESSION['usuario']}',
						'{$_SESSION['usuario']}',
						'$situacao',
						'$sta_revenda',
						'" . $serie_array[$k] . "',
						'$grupo')
						";
				}



				if ($_SESSION['entrada'] && $_SESSION['proximo_pagamento'])
					$sql_compra = "INSERT INTO tbl_compra(
							comp_cliente,
							comp_data,
							comp_valortotal,
							comp_revendedor,
							comp_debitor,
							comp_situacao,
							comp_status_revenda,
							comp_parcelamento_entrada,
							comp_parcelamento_data,
							comp_serie,
							comp_grupo
							) VALUES(
							'{$_SESSION['usuario_admin']}',
							NOW(),
							'$valor_total',
							'{$_SESSION['usuario']}',
							'{$_SESSION['usuario']}',
							'$situacao',
							'$sta_revenda',
							'" . floatval($_SESSION['entrada']) . "',
							'" . date('Y-m-d', $_SESSION['proximo_pagamento']) . "',
							'" . $serie_array[$k] . "',
							'$grupo'
							)
							";

				$total_somado += $valor_total;

				$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
				$cod_compra[$l] = $mysqli->insert_id;
				$compra_nova = true;
				$lista_de_compras[$cod_compra[$l]] = array();
				$lista_de_series[$cod_compra[$l]] = $serie_array[$k];

				$l++;
			}
		}

		//var_dump($cod_compra);

		unset($_SESSION['entrada'], $_SESSION['proximo_pagamento']);

		$bilhetes_f = "";


		foreach ($_SESSION['carrinho_admin'] as $rifa_cod => $v) {

			$exec_bp = $mysqli->query("select nome_grupo from reserva where rifa = '$rifa_cod' order by nome_grupo DESC limit 1") or die($mysqli->error);
			$grupo   = $exec_bp->fetch_assoc();
			$grupo   = $grupo['nome_grupo'];
			if (strlen($grupo) > 2)
				$grupo = str_replace('BP', '', $grupo);
			else
				$grupo = 1;


			$reservados = "";

			//echo "<PRE>";
			//var_dump($_SESSION['carrinho_admin'][$rifa_cod]);
			//echo "</pre>";

			$num_final = 0;
			$compra_inicial = 0;


			$tradutor_grupos = array();
			//var_dump($_SESSION['carrinho_admin'][$rifa_cod]);
			$smsTravarBilhete = "";

			$maxbilhetes = DBSelect("select rifa_maxbilhetes as max, rifa_dtsorteio as data from tbl_rifas where rifa_cod = '$rifa_cod'", $mysqli);
			$data = date("d/m/Y", strtotime($maxbilhetes[data]));
			$maxbilhetes = $maxbilhetes[max];

			foreach ($_SESSION['carrinho_admin'][$rifa_cod] as $void => $cod_bilhete) {

				$cod_bilhete = intval($cod_bilhete);

				/*echo "<p>$cod_compra[$compra_inicial]</p>";
					echo "<p>$cod_bilhete</p>";*/

				//echo "<p>Inserindo na compra $cod_compra[$compra_inicial]</p>";



				$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '" . $cod_compra[$compra_inicial] . "')";

				$bilhete_formatado = str_pad($cod_bilhete, 2, "0", STR_PAD_LEFT);

				if($travarBilhete)
					$bilhete_formatado = str_pad($cod_bilhete, strlen($maxbilhetes) - 1, "0", STR_PAD_LEFT);

				$bilhetes_f .= $bilhete_formatado . "-";
				$reservados .= str_pad($cod_bilhete, 2, "0", STR_PAD_LEFT) . ",";

				if($travarBilhete) {

					$compra = is_array($cod_compra) ? $cod_compra[$cod_bilhete] : $cod_compra;
					if($compra_nova && is_array($cod_compra)) {
						$compra = $cod_compra[$compra_inicial];
					}

					$sql_consultar_grupo = "SELECT * 
						FROM ( SELECT * 
						       FROM tbl_bilhetes 
						       WHERE bil_bilhete_original = '$cod_bilhete' AND bil_rifa = '$rifa_cod' AND bil_grupo IS NOT NULL) AS A
						LEFT JOIN ( SELECT * FROM cache_bilhetes_do_grupo WHERE rifa = '$rifa_cod' AND bilhete = '$cod_bilhete') AS B
						ON A.bil_rifa=B.rifa";
					$query_consultar_grupo = $mysqli->query($sql_consultar_grupo) or die($mysqli->error);
					$dados_grupo   = $query_consultar_grupo->fetch_assoc();
					
					$tradutor_grupos[$compra] = $dados_grupo['grupo'] ? $dados_grupo['grupo'] : $dados_grupo['bil_grupo'];

					if(!$tradutor_grupos[$compra]) {
						$bilhetes = curl_get_contents("http://rifasbrasil.com.br/servidor/new_server/buscar_bilhetes.php?action=get_serie&rifa={$rifa_cod}&layout=1&bilhete={$cod_bilhete}");
						$bilhetes = json_decode($bilhetes, 1);
						$tradutor_grupos[$compra] = $bilhetes['serie']['grupo'];
					}
					
					$bilhetes = gerarDezenas($rifa_cod, $cod_bilhete);
					$bilhetesInsert = array();

					foreach($bilhetes as $bil) {
					    $bilhetesInsert[] = "('$rifa_cod', '$bil', 'P', '$compra', '$cod_bilhete', '{$tradutor_grupos[$compra]}')";
					    $lista_de_compras[$compra][] = str_pad($bil, 2, "0", STR_PAD_LEFT);
					}

					$smsTravarBilhete .= $compra . " [" . $tradutor_grupos[$compra] . ":".$bilhete_formatado."] (".implode('-', $lista_de_compras[$compra]).") \n";

					$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra, bil_bilhete_original, bil_grupo)
					VALUES " . implode(",", $bilhetesInsert);
					$_SESSION['sql_insert'][] = $sql_bilhete;


				} else
					$lista_de_compras[$cod_compra[$compra_inicial]][] = $bilhete_formatado;
				

				$executar_bilhete = $mysqli->query($sql_bilhete) or die($mysqli->error);

				$num_final++;
				if ($num_final == $dezenabolao) {
					$num_final = 0;
					$compra_inicial++;
				}
			}

			$reservados = substr($reservados, 0, -1);

			if ($_SESSION['bilhete_pedido']) {
				$mysqli->query("INSERT into reserva (revendedor, bilhete, nome_grupo, rifa, embaralhar, agrupar)
						values(
							'" . $_SESSION['usuario'] . "',
							'$reservados',
							'BP" . ($grupo++) . "',
							'$rifa_cod',
							'1',
							'1')");
			}
		}
		unset($_SESSION['bilhete_pedido']);

		$bilhetes_f = substr($bilhetes_f, 0, -1);
		//enviarSMS($celular, "RifasBrasil - Seu(s) bilhete(s): $bilhetes_f da rifa: http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa_cod." - Sorteio dia: ".$data, true);


		$sql_query = $mysqli->query("SELECT rifa_titulo, travar_bilhetes, dezena_bolao, usu_celular, usu_nome From tbl_rifas, tbl_usuario where rifa_cod = '$rifa_cod' and usu_cod = rifa_dono") or die($mysqli->error);
		$temp = $sql_query->fetch_assoc();

		$dono_da_rifa = $temp['usu_nome'];
		$rifa_dono_telefone = preg_replace("/[^0-9]/", "", $rifa_tmp['usu_celular']);
		$nome_rifa = substr($temp['rifa_titulo'], 0, 25);
			

		//$sms = "RifasBrasil - Seu(s) bilhete(s):  da rifa: http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa_cod." - Sorteio dia: ".;

		if ($_SESSION['tipo_venda'] == 'paga')
			$final = "PAGO";
		else
			$final = "NAO PAGO";

		$sms = "RIFASBRASIL: Ola " . primeiroNome($nomecliente) . ", seu(s) Bilhete(s): (" . $bilhetes_f . ") Da Rifa: (" . $nome_rifa . ") Sorteio dia: (" . $data . ") - $final - Boa Sorte!";			// Limpar Carrinho

		$sms = primeiroNome($dono_da_rifa) . ": Ola " . primeiroNome($nomecliente) . ", seu(s) jogo(s): \n";

		//SYS10: Ola Naldo, seu(s) jogo(s): 440565 [A1: 0753] (06-09-14-18-21-27-30-34-35-39) 440566[A1:2519] (as dezenas desse) (TOP10 ED-02) Sorteio/Inicio (26/12/2020) NAO PAGO - R$ 10,00

		foreach ($lista_de_compras as $cod_compra => $arr_bilhetes) {
			asort($arr_bilhetes);
			if ($cod_compra > 0) {
				if($travarBilhete) {
					//$sms .= $cod_compra . " ";
					$bilhetes_formatados = [];
					foreach($lista_de_compras[$cod_compra] as $bil) {
						//$bilhetes = file_get_contents("http://rifasbrasil.com.br/servidor/new_server/buscar_bilhetes.php?action=get_serie&rifa={$rifa_cod}&layout=1&bilhete={$bil}");
						//$bilhetes = json_decode($bilhetes, 1);
						//$grupo = $bilhetes['serie']['grupo'];
						
						//foreach($bilhetes['sequencia'] as $bilnum) {
							$bilhetes_formatados[] = str_pad($bil, 2, '0', STR_PAD_LEFT);
						//}
						
					}
					
				} else 
					$sms .= $cod_compra . "(" . implode('-', $arr_bilhetes) . ") ";
			}

			if (isset($_SESSION['series_customizadas']) && isset($lista_de_series[$cod_compra]))
				$sms .= '[M.F: ' . $lista_de_series[$cod_compra] . '] ';
		}
		
		$sms .= $smsTravarBilhete . "(" . $nome_rifa . ")\nSorteio/Inicio (" . $data . ")\nSituação: $final \nR$ " . number_format($total_somado, 2, ',', '.');
		

		unset($_SESSION['series_customizadas']);

		$sql_code_whatsapp = "SELECT ativar_sms FROM opcao_reserva WHERE rifa = '" . $cod_rifa . "'";
		$sql_query_whatsapp = $mysqli->query($sql_code_whatsapp) or die($mysqli->error);
		$enviar_whatsapp = $sql_query_whatsapp->fetch_assoc();

		$ativarSMS = $enviar_whatsapp['ativar_sms'];

		if($travarBilhete) {
			$sql_query = $mysqli->query("SELECT usu_celular, usu_nome From tbl_usuario where usu_cod = '{$_SESSION['cod_rev']}'") or die($mysqli->error);
			$temp = $sql_query->fetch_assoc();
			$rifa_dono_telefone = preg_replace("/[^0-9]/", "", $temp['usu_celular']);
			$dono_da_rifa = $temp['usu_nome'];
		}

		if($ativarSMS) {

			$celular = preg_replace("/[^0-9]/", "", $celular);

			// NEW
			$sql_code_contas = "SELECT * FROM tbl_conta WHERE usuario = '".$_SESSION['cod_rev']."'";
			$sql_query_contas = $mysqli->query($sql_code_contas) or die($mysqli->error);
			
			$mensagemWhatsappCliente = '*' . strtoupper($dono_da_rifa) . ':* Olá, ' . primeiroNome($nomecliente) . ', seu(s) BILHETE(S) ' . $smsTravarBilhete . ', da CAMPANHA (' . $nome_rifa . ') Foram RESERVADO(S) com SUCESSO!';

			if($sql_query_contas->num_rows > 0) {
				
				$contas = $sql_query_contas->fetch_assoc();
				$mensagemWhatsappCliente .= 
				PHP_EOL
				. PHP_EOL
				. '*TOTAL:* R$ ' . number_format($total_somado, 2, ',', '.')
				. PHP_EOL
				. PHP_EOL
				. 'EFETUE O PAGAMENTO EM UMA DAS CONTAS ABAIXO:' 
				. PHP_EOL
				. PHP_EOL;

				do {

					$mensagemWhatsappCliente .= '*Banco:* ' . $contas['banco'] . PHP_EOL;
					$mensagemWhatsappCliente .= '*Agência:* ' . $contas['agencia'] . PHP_EOL;
					$mensagemWhatsappCliente .= '*Conta:* ' . $contas['conta'] . PHP_EOL;
					if($contas['outrasinfos'])
						$mensagemWhatsappCliente .= '*Outras Informações:* ' . $contas['outrasinfos'] . PHP_EOL;

					if($contas['nome_completo'])
						$mensagemWhatsappCliente .= '*Nome Completo:* ' . $contas['nome_completo'] . PHP_EOL;

					if($contas['cpf'])
						$mensagemWhatsappCliente .= '*CPF:* ' . $contas['cpf'] . PHP_EOL;

					$mensagemWhatsappCliente .= PHP_EOL;

				} while ($contas = $sql_query_contas->fetch_assoc());

				$mensagemWhatsappCliente .= 'ENVIE O COMPROVANTE PARA O RESPONSAVEL  NO WHATSAPP a seguir:' . PHP_EOL . 'https://wa.me/55' . $rifa_dono_telefone;

				//die($mensagemWhatsappCliente);

			} else
				$mensagemWhatsappCliente .= 'Entre em contato com o ADM no WhatsApp a seguir: https://wa.me/55' . $rifa_dono_telefone;

			$mensagemWhatsappDono = 'RESERVA EFETUADA: Olá, o cliente ' . primeiroNome($nomecliente) . ',  Reservou o(s) BILHETE(S) [' . $bilhetes_f . '], da CAMPANHA (' . utf8_decode($nome_rifa) . ')! Entre em contato com o CLIENTE no WhatsApp a seguir: https://wa.me/55' . $celular;

			enviarWhatsapp ($celular, $mensagemWhatsappCliente);
			enviarWhatsapp ($rifa_dono_telefone, $mensagemWhatsappDono);

		}


		//die();


		foreach ($_SESSION['carrinho_admin'] as $ch => $va) {

			foreach ($_SESSION['carrinho_admin'][$ch] as $chave => $valor)
				unset($_SESSION['carrinho_admin'][$ch][$chave]);

			unset($_SESSION['carrinho_admin'][$ch]);
		}

		unset($_SESSION['usuario_admin']);

		// Só pra garantir

		if (count($_SESSION['carrinho_admin']) > 0)
			unset($_SESSION['carrinho_admin']);

		// Redirecionar usuário

		unset($_SESSION['compra_ja_feita'],
		$_SESSION['nome'],
		$_SESSION['telefone'],
		$_SESSION['regiao'],
		$_SESSION['comp_status_revenda'],
		$_SESSION['cliente_ja_cadastrado']);


		$_SESSION['sms'] = $sms;

		if(isset($_GET['ajax'])) {
			echo json_encode(array('url' => "/admin/index.php?p=sucesso_venda&usuario=" . $usu_codigo . "&sms=true"));
		} else {
			echo "<script>";
			echo "location.href='index.php?p=sucesso_venda&usuario=" . $usu_codigo . "&sms=true';";
			echo "</script>";
		}
		
	}
} else {

	//Carrinho vazio
	echo "<script>";
	echo "alert('Carrinho vazio!');";
	echo "location.href='index.php?p=revenda';";
	echo "</script>";
}
