<?php

if (!isset($_SESSION))
	session_start();

if (count($_SESSION['carrinho_admin']) > 0) {


	include("../class/conexao.php");
	include("../class/function_enviarSMS.php");
	include("../class/function_primeiroNome.php");

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

	$sql_code = "SELECT rifa_cod, dezena_bolao, rifa_valorbilhete, rifa_titulo 
		FROM tbl_rifas 
		WHERE ({$sql_add}) 
		AND (rifa_vencedor IS NULL OR rifa_vencedor = '')";

	$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
	$rifa_tmp = $sql_query->fetch_assoc();

	$valor_total = 0;

	$dezenabolao = 0;
	$valor_bilhete = 0;
	do {
		$dezenabolao = $rifa_tmp['dezena_bolao'];
		$valor_bilhete = $rifa_tmp['rifa_valorbilhete'];
		$valor_total += (count($_SESSION['carrinho_admin'][$rifa_tmp['rifa_cod']]) * $rifa_tmp['rifa_valorbilhete']);
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
			foreach ($_SESSION['carrinho_admin'][$ch] as $ch2 => $bil) {
				$array_bilhetes_serie[] = str_pad($bil, 2, '0', STR_PAD_LEFT);
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

		if ($_SESSION['compra_ja_feita']) {
			$sql_compra = "UPDATE tbl_compra set comp_valortotal = $valor_total where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";

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
							comp_serie
							) VALUES(
							'{$_SESSION['usuario_admin']}',
							NOW(),
							'$comp_desconto',
							'$valor_total',
							'{$_SESSION['cod_rev']}',
							'{$_SESSION['cod_rev']}',
							'$situacao',
							'$sta_revenda',
							'" . $serie_array[$k] . "')
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
						comp_serie
						) VALUES(
						'{$_SESSION['usuario_admin']}',
						NOW(),
						'$comp_desconto',
						'$valor_total',
						'{$_SESSION['usuario']}',
						'{$_SESSION['usuario']}',
						'$situacao',
						'$sta_revenda',
						'" . $serie_array[$k] . "')
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
							comp_serie
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
							'" . $serie_array[$k] . "'
							)
							";

				$total_somado += $valor_total;

				$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
				$cod_compra[$l] = $mysqli->insert_id;

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



			//var_dump($_SESSION['carrinho_admin'][$rifa_cod]);

			foreach ($_SESSION['carrinho_admin'][$rifa_cod] as $void => $cod_bilhete) {

				$cod_bilhete = intval($cod_bilhete);

				/*echo "<p>$cod_compra[$compra_inicial]</p>";
					echo "<p>$cod_bilhete</p>";*/

				//echo "<p>Inserindo na compra $cod_compra[$compra_inicial]</p>";

				$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '" . $cod_compra[$compra_inicial] . "')";


				$maxbilhetes = DBSelect("select rifa_maxbilhetes as max, rifa_dtsorteio as data from tbl_rifas where rifa_cod = '$rifa_cod'", $mysqli);
				$data = date("d/m/Y", strtotime($maxbilhetes[data]));
				$maxbilhetes = $maxbilhetes[max];

				$bilhete_formatado = str_pad($cod_bilhete, 2, "0", STR_PAD_LEFT);

				$bilhetes_f .= $bilhete_formatado . "-";
				$reservados .= str_pad($cod_bilhete, 2, "0", STR_PAD_LEFT) . ",";

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


		$sql_query = $mysqli->query("SELECT rifa_titulo, usu_nome From tbl_rifas, tbl_usuario where rifa_cod = '$rifa_cod' and usu_cod = rifa_dono") or die($mysqli->error);
		$temp = $sql_query->fetch_assoc();

		$dono_da_rifa = $temp['usu_nome'];
		$nome_rifa = substr($temp['rifa_titulo'], 0, 25);
		//$sms = "RifasBrasil - Seu(s) bilhete(s):  da rifa: http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa_cod." - Sorteio dia: ".;

		if ($_SESSION['tipo_venda'] == 'paga')
			$final = "PAGO";
		else
			$final = "NAO PAGO";

		$sms = "RIFASBRASIL: Ola " . primeiroNome($nomecliente) . ", seu(s) Bilhete(s): (" . $bilhetes_f . ") Da Rifa: (" . $nome_rifa . ") Sorteio dia: (" . $data . ") - $final - Boa Sorte!";			// Limpar Carrinho

		$sms = primeiroNome($dono_da_rifa) . ": Ola " . primeiroNome($nomecliente) . ", seu(s) jogo(s): ";

		foreach ($lista_de_compras as $cod_compra => $arr_bilhetes) {
			asort($arr_bilhetes);
			if ($cod_compra > 0) $sms .= $cod_compra . "(" . implode('-', $arr_bilhetes) . ") ";
			if (isset($_SESSION['series_customizadas']) && isset($lista_de_series[$cod_compra]))
				$sms .= '[M.F: ' . $lista_de_series[$cod_compra] . '] ';
		}
		$sms .= "(" . $nome_rifa . ") Sorteio/Inicio (" . $data . ") $final - R$ " . number_format($total_somado, 2, ',', '.');

		unset($_SESSION['series_customizadas']);


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
		echo "<script>";
		echo "location.href='index.php?p=sucesso_venda&usuario=" . $usu_codigo . "&sms=true';";
		echo "</script>";
	}
} else {

	//Carrinho vazio
	echo "<script>";
	echo "alert('Carrinho vazio!');";
	echo "location.href='index.php?p=revenda';";
	echo "</script>";
}
