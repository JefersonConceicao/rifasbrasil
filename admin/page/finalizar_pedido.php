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

	// Calcular valor Total
	$sql_add = array();
	foreach ($_SESSION['carrinho_admin'] as $ch => $v) {
		$sql_add[] = " rifa_cod = '$ch' ";
	}

	$sql_code = "SELECT rifa_cod, rifa_valorbilhete, rifa_titulo, usu_nome, dezena_bolao, rifa_maxbilhetes
			FROM tbl_rifas, tbl_usuario
			WHERE (" . implode('OR', $sql_add) . ") 
			AND (rifa_vencedor IS NULL OR rifa_vencedor = '')
			AND usu_cod = rifa_dono";

	$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
	$rifa_tmp = $sql_query->fetch_assoc();

	$valor_total = 0;
	$rifa_dono = $rifa_tmp['usu_nome'];

	$url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes_mobile_dez_cen&limpar_carrinho=true&rifa=';

	do {
		$valor_total += (count($_SESSION['carrinho_admin'][$rifa_tmp['rifa_cod']]) * $rifa_tmp['rifa_valorbilhete']);

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

	

	// Verifica se alguma das rifas tem Bug
	$erro = 0;
	foreach ($_SESSION['carrinho_admin'] as $cod_rifa => $void) {
		if (count($_SESSION['carrinho_admin'][$cod_rifa]) == 0) {
			$erro++;
		}

		$array_bilhetes = array();
		foreach ($_SESSION['carrinho_admin'][$cod_rifa] as $void => $cod_bilhete) {
			$cod_bilhete = intval($cod_bilhete);
			$array_bilhetes[] = "b.bil_numero = '$cod_bilhete'";
		}

		//echo "SELECT COUNT(*) as num FROM tbl_bilhetes WHERE bil_rifa = '$cod_rifa' AND (" . implode(" OR ", $array_bilhetes) . ")";
		$sql_query = $mysqli->query("SELECT COUNT(*) as num FROM tbl_bilhetes b, tbl_compra c WHERE c.comp_cod = b.bil_compra AND b.bil_rifa = '$cod_rifa' AND c.comp_situacao != 7 AND c.comp_situacao != '3' AND c.comp_situacao != '4' AND c.comp_status_revenda != '1' AND (" . implode(" OR ", $array_bilhetes) . ")") or die($mysqli->error);
		$rifa_tmp = $sql_query->fetch_assoc();

		//var_dump($rifa_tmp);

		if(intval($rifa_tmp['num']) > 0) {
			$url .= $cod_rifa;
			die("<script>alert('Um dos bilhetes que você escolheu foi vendido enquanto você o tinha no carrinho. Selecione outro.');location.href='{$url}';</script>");
		}

	}


	//die('test');
	unset($_SESSION['nome'],
	$_SESSION['email'],
	$_SESSION['telefone'],
	$_SESSION['regiao'],
	$_SESSION['cpf'],
	$_SESSION['senha'],
	$_SESSION['rsenha']);

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


		if ($_SESSION['compra_ja_feita']) {
			$sql_compra = "UPDATE tbl_compra set comp_valortotal = $valor_total where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";

			if ($_SESSION['entrada'] && $_SESSION['proximo_pagamento']) {

				if (($valor_total - $_SESSION['entrada']) <= 0)
					$sql_compra = "UPDATE tbl_compra set 
						comp_valortotal = '$valor_total', 
						comp_desconto = '$comp_desconto',
						comp_parcelamento_data = NULL,
						comp_parcelamento_entrada = NULL,
						comp_status_revenda = 1,
						comp_situacao = ''

						where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";
				else
					$sql_compra = "UPDATE tbl_compra set 
						comp_valortotal = '$valor_total', 
						comp_desconto = '$comp_desconto',
						comp_parcelamento_data = '" . date('Y-m-d', $_SESSION['proximo_pagamento']) . "',
						comp_parcelamento_entrada = '" . floatval($_SESSION['entrada']) . "'
						where comp_cod = '" . $_SESSION['compra_ja_feita'] . "'";
			}

			$cod_compra = $_SESSION['compra_ja_feita'];
			$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);

			$mysqli->query("DELETE FROM tbl_bilhetes where bil_compra = '$cod_compra'") or die($mysqli->error);
		} else {

			// verificar se e um usuario sem login para realizar a query com id de cliente
			if (isset($_SESSION['usuario_sem_login'])) {
				$sql_compra = "INSERT INTO tbl_compra(
					comp_cliente,
					comp_data,
					comp_desconto,
					comp_valortotal,
					comp_revendedor,
					comp_debitor,
					comp_situacao,
					comp_status_revenda
					) VALUES(
					'{$_SESSION['usuario_admin']}',
					NOW(),
					'$comp_desconto',
					'$valor_total',
					'{$_SESSION['cod_rev']}',
					'{$_SESSION['cod_rev']}',
					'$situacao',
					'0')
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
					comp_status_revenda
					) VALUES(
					'{$_SESSION['usuario_admin']}',
					NOW(),
					'$comp_desconto',
					'$valor_total',
					'{$_SESSION['usuario']}',
					'{$_SESSION['usuario']}',
					'$situacao',
					'$sta_revenda')
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
						comp_parcelamento_data
						) VALUES(
						'{$_SESSION['usuario_admin']}',
						NOW(),
						'$valor_total',
						'{$_SESSION['usuario']}',
						'{$_SESSION['usuario']}',
						'$situacao',
						'$sta_revenda',
						'" . floatval($_SESSION['entrada']) . "',
						'" . date('Y-m-d', $_SESSION['proximo_pagamento']) . "'

						)
						";


			$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
			$cod_compra = $mysqli->insert_id;
		}



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

			foreach ($_SESSION['carrinho_admin'][$rifa_cod] as $void => $cod_bilhete) {

				$cod_bilhete = intval($cod_bilhete);

				$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '$cod_compra')";

				$maxbilhetes = DBSelect("select rifa_maxbilhetes as max, rifa_dtsorteio as data from tbl_rifas where rifa_cod = '$rifa_cod'", $mysqli);
				$data = date("d/m/Y", strtotime($maxbilhetes['data']));
				$maxbilhetes = $maxbilhetes['max'];

				$bilhetes_f .= str_pad($cod_bilhete, strlen($maxbilhetes) - 1, "0", STR_PAD_LEFT) . "-";
				$reservados .= str_pad($cod_bilhete, strlen($maxbilhetes) - 1, "0", STR_PAD_LEFT) . ",";

				$executar_bilhete = $mysqli->query($sql_bilhete) or die($mysqli->error);
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
		$nome_rifa = substr(DBSelect("SELECT rifa_titulo From tbl_rifas where rifa_cod = '$rifa_cod'", $mysqli, "rifa_titulo"), 0, 25);
		//$sms = "RifasBrasil - Seu(s) bilhete(s):  da rifa: http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa_cod." - Sorteio dia: ".;



		if ($_SESSION['tipo_venda'] == 'paga')
			$final = "PAGO";
		else
			$final = "NAO PAGO";

		$sms = "RIFASBRASIL: Ola " . primeiroNome($nomecliente) . ", seu(s) Bilhete(s): (" . $bilhetes_f . ") Da Rifa: (" . $nome_rifa . ") Sorteio dia: (" . $data . ") - $final - Boa Sorte!";			// Limpar Carrinho


		if ($_SESSION['repetir_venda'])
			$_SESSION['sms'] = strtoupper($rifa_dono) . ": " . primeiroNome($nomecliente) . ", seu(s) Bilhete(s) na compra $cod_compra: (" . $bilhetes_f . ") Da Rifa: (" . $nome_rifa . ") Sorteio dia: (" . $data . ") - $final - Boa Sorte!";
		else
			$_SESSION['sms'] = $sms;

		// Redirecionar usuário


		echo "<script>";

		if ($_SESSION['repetir_venda'])
			echo "location.href='index.php?p=sucesso_venda&usuario=" . $usu_codigo . "&comp=" . $cod_compra . "&sms=1';";
		else
			echo "location.href='index.php?p=sucesso_venda&usuario=" . $usu_codigo . "';";

		echo "</script>";

		unset($_SESSION['compra_ja_feita'],
		$_SESSION['usuario_admin'],
		$_SESSION['nome'],
		$_SESSION['carrinho_admin'],
		$_SESSION['telefone'],
		$_SESSION['regiao'],
		$_SESSION['comp_status_revenda'],
		$_SESSION['cliente_ja_cadastrado']);
	}
} else {

	//Carrinho vazio
	echo "<script>";
	echo "alert('Carrinho vazio!');";
	echo "location.href='index.php?p=revenda';";
	echo "</script>";
}
