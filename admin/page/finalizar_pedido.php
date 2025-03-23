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

function autorizarVendaBancaOnline ($rifa, $bilhete, $valor) {

	include("../class/conexao.php");

	$sql_code = "SELECT SUM(bil_aposta) as soma, rif.valor_aposta, rif.multiplicador FROM tbl_bilhetes bil, tbl_rifas rif WHERE bil.bil_rifa = '$rifa' AND bil.bil_numero = '$bilhete' AND rif.rifa_cod = bil.bil_rifa";
	$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
	$resultado = $sql_query->fetch_assoc();

	if(!$resultado['valor_aposta'])
		return false;
	
	$resultado['soma'] = floatval($resultado['soma']);
	$resultado['valor_aposta'] = floatval($resultado['valor_aposta']);

	$max = ($resultado['valor_aposta']-$resultado['soma']);
	$pos = $max - $valor;
	$autorizar = $pos >= 0 ? true:false;
	$pode_ganhar = intval($resultado['multiplicador']) * $valor;

	if($autorizar)
		return true;

	return false;

}

function enviarWhatsapp ($telefone, $mensagem) {
	$url = 'https://api.z-api.io/instances/3979CE875006A02B0EA69AA9EABEE58E/token/39DE375A2E19720BC1FCB02A/send-messages';
	$ch = curl_init($url);

	$data = array(
	    'phone' => '55' . preg_replace("/[^0-9]/", "", $telefone),
	    'message' => $mensagem
	);

	$res =  curl_post_contents($url, $data, 60);

}

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

	$sql_code = "SELECT rifa_cod, rifa_valorbilhete, rifa_titulo, usu_cod, usu_nome, usu_celular, dezena_bolao, multiplicador, banca_online, rifa_maxbilhetes
			FROM tbl_rifas, tbl_usuario
			WHERE (" . implode('OR', $sql_add) . ") 
			AND (rifa_vencedor IS NULL OR rifa_vencedor = '')
			AND usu_cod = rifa_dono";

	$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
	$rifa_tmp = $sql_query->fetch_assoc();

	$modoBancaOnline = false;

	$valor_total = 0;
	$rifa_dono = $rifa_tmp['usu_nome'];
	$dono_da_rifa_id = $rifa_tmp['usu_cod'];
	$multiplicador = 0;
	$rifa_dono_telefone = preg_replace("/[^0-9]/", "", $rifa_tmp['usu_celular']);

	$url = 'http://rifasbrasil.com.br/admin/index.php?p=ver_bilhetes_mobile_dez_cen&limpar_carrinho=true&rifa=';

	do {
		$valor_total += (count($_SESSION['carrinho_admin'][$rifa_tmp['rifa_cod']]) * $rifa_tmp['rifa_valorbilhete']);

		if($rifa_tmp['banca_online'])
			$modoBancaOnline = true;

		if($rifa_tmp['multiplicador'])
			$multiplicador = $rifa_tmp['multiplicador'];

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

			if($modoBancaOnline) {
			// verifica se o limite nao estourou enquanto o usuario enrolava
				if(!isset($_SESSION['aposta']) || !isset($_SESSION['aposta'][$cod_rifa]) || !isset($_SESSION['aposta'][$cod_rifa][intval($cod_bilhete)])) {
					$url .= $cod_rifa;
					die("<script>alert('Valor da aposta não definida. Por favor, comece novamente.');location.href='{$url}';</script>");
				} else {
					$autorizar = autorizarVendaBancaOnline ($cod_rifa, $cod_bilhete, $_SESSION['aposta'][$cod_rifa][intval($cod_bilhete)]);
					if(!$autorizar) {
						$url .= $cod_rifa;
						die("<script>alert('Outra pessoa finalizou a aposta primeiro que você e os valores mudaram. Por favor, comece novamente.');location.href='{$url}';</script>");
					}
				}
				
			}

		}

		if(!$modoBancaOnline) {

			//echo "SELECT COUNT(*) as num FROM tbl_bilhetes WHERE bil_rifa = '$cod_rifa' AND (" . implode(" OR ", $array_bilhetes) . ")";
			$sql_query = $mysqli->query("SELECT COUNT(*) as num FROM tbl_bilhetes b, tbl_compra c WHERE c.comp_cod = b.bil_compra AND b.bil_rifa = '$cod_rifa' AND c.comp_situacao != 7 AND c.comp_situacao != '3' AND c.comp_situacao != '4' AND c.comp_status_revenda != '1' AND (" . implode(" OR ", $array_bilhetes) . ")") or die($mysqli->error);
			$rifa_tmp = $sql_query->fetch_assoc();

			//var_dump($rifa_tmp);

			if(intval($rifa_tmp['num']) > 0) {
				$url .= $cod_rifa;
				die("<script>alert('Um dos bilhetes que você escolheu foi vendido enquanto você o tinha no carrinho. Selecione outro.');location.href='{$url}';</script>");
			}
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
				if($modoBancaOnline) {
					$aposta = 0;
					foreach ($_SESSION['carrinho_admin'] as $rifa_cod => $v) {			
						foreach ($_SESSION['carrinho_admin'][$rifa_cod] as $void => $cod_bilhete) {
							$aposta = $_SESSION['aposta'][$rifa_cod][intval($cod_bilhete)];
						}
					}
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
						'$aposta',
						'{$_SESSION['cod_rev']}',
						'{$_SESSION['cod_rev']}',
						'$situacao',
						'0')
						";
				} else 
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

				if($modoBancaOnline) {
					$sql_compra = array();
					foreach ($_SESSION['carrinho_admin'] as $rifa_cod => $v) {			
						foreach ($_SESSION['carrinho_admin'][$rifa_cod] as $void => $cod_bilhete) {
							$aposta = $_SESSION['aposta'][$rifa_cod][intval($cod_bilhete)];
							$sql_compra[] = "INSERT INTO tbl_compra(
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
								'$aposta',
								'{$_SESSION['usuario']}',
								'{$_SESSION['usuario']}',
								'$situacao',
								'$sta_revenda')
								";
						}
					}
				} else
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

			if(!is_array($sql_compra)) {
				$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
				$cod_compra = $mysqli->insert_id;
			} else {
				$cod_compra = array();
				foreach($sql_compra as $sql) {
					$executar_compra = $mysqli->query($sql) or die($mysqli->error);
					$cod_compra[] = $mysqli->insert_id;
				}
			}
			
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
			
			$indice_compra = 0;
			foreach ($_SESSION['carrinho_admin'][$rifa_cod] as $void => $cod_bilhete) {

				$cod_bilhete = intval($cod_bilhete);

				$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '$cod_compra')";

				if($modoBancaOnline && $_SESSION['aposta'] && $_SESSION['aposta'][$rifa_cod] && $_SESSION['aposta'][$rifa_cod][intval($cod_bilhete)]) {
					$aposta = $_SESSION['aposta'][$rifa_cod][intval($cod_bilhete)];
					if(!is_array($cod_compra))
						$cod_compra_unica = $cod_compra;
					else
						$cod_compra_unica = $cod_compra[$indice_compra++];
					$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra, bil_aposta)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '$cod_compra_unica', '$aposta')";
				}

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

		if(isset($_SESSION['cod_rev'])) {
			$dono_da_rifa_id = $_SESSION['cod_rev'];
			$sql_query = $mysqli->query("SELECT usu_celular, usu_nome From tbl_usuario where usu_cod = '{$_SESSION['cod_rev']}'") or die($mysqli->error);
			$temp = $sql_query->fetch_assoc();
			$rifa_dono_telefone = preg_replace("/[^0-9]/", "", $temp['usu_celular']);
			$rifa_dono = $temp['usu_nome'];
		}


		if ($_SESSION['tipo_venda'] == 'paga')
			$final = "PAGO";
		else
			$final = "NAO PAGO";

		$sms = "RIFASBRASIL: Ola " . primeiroNome($nomecliente) . ", seu(s) Bilhete(s): (" . $bilhetes_f . ") Da Rifa: (" . $nome_rifa . ") Sorteio dia: (" . $data . ") - $final - Boa Sorte!";			// Limpar Carrinho


		if ($_SESSION['repetir_venda'])
			$_SESSION['sms'] = strtoupper($rifa_dono) . ": " . primeiroNome($nomecliente) . ", seu(s) Bilhete(s) na compra $cod_compra: (" . $bilhetes_f . ") Da Rifa: (" . $nome_rifa . ") Sorteio dia: (" . $data . ") - $final - Boa Sorte!";
		else
			$_SESSION['sms'] = $sms;

		$sql_code_whatsapp = "SELECT ativar_sms FROM opcao_reserva WHERE rifa = '" . $cod_rifa . "'";
		$sql_query_whatsapp = $mysqli->query($sql_code_whatsapp) or die($mysqli->error);
		$enviar_whatsapp = $sql_query_whatsapp->fetch_assoc();

		$ativarSMS = $enviar_whatsapp['ativar_sms'];

		// puxa as contas bancarias do revendedor
		$sql_code_contas = "SELECT * FROM tbl_conta WHERE usuario = '".$dono_da_rifa_id."'";
		$sql_query_contas = $mysqli->query($sql_code_contas) or die($mysqli->error);
		
		$mensagemWhatsappCliente = '*' . strtoupper($rifa_dono) . ':* Olá, ' . primeiroNome($nomecliente) . ', seu(s) BILHETE(S) [' . $bilhetes_f . '], da CAMPANHA (' . $nome_rifa . ') Foram RESERVADO(S) com SUCESSO!';

		if($sql_query_contas->num_rows > 0) {
			
			$contas = $sql_query_contas->fetch_assoc();
			if($modoBancaOnline)
				$mensagemWhatsappCliente .= 
				PHP_EOL
				. PHP_EOL
				. '*ID da Aposta:* ' . ((is_array($cod_compra)) ? implode(', ', $cod_compra) : $cod_compra) . PHP_EOL
				. '*Valor Apostado:* R$' . number_format($aposta, 2, ',', '.') . PHP_EOL
				. '*Prêmio Possível:* R$' . number_format($multiplicador * $aposta, 2, ',', '.') . PHP_EOL
				. PHP_EOL
				. PHP_EOL
				. 'EFETUE O PAGAMENTO EM UMA DAS CONTAS ABAIXO:' 
				. PHP_EOL
				. PHP_EOL;
			else
				$mensagemWhatsappCliente .= 
				PHP_EOL
				. PHP_EOL
				. '*TOTAL:* R$ ' . number_format($valor_total, 2, ',', '.')
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

		

		//echo $mensagemWhatsappCliente;
		// pronto

		/*
		$mensagemWhatsappCliente = strtoupper($rifa_dono) . ': Olá, ' . primeiroNome($nomecliente) . ', seu(s) BILHETE(S) [' . $bilhetes_f . '], da CAMPANHA (' . utf8_decode($nome_rifa) . ') Foram RESERVADO(S) com SUCESSO! Entre em contato com o ADM no WhatsApp a seguir: https://wa.me/55' . $rifa_dono_telefone;
		*/

		$mensagemWhatsappDono = 'RESERVA EFETUADA: Olá, o cliente ' . primeiroNome($nomecliente) . ',  Reservou o(s) BILHETE(S) [' . $bilhetes_f . '], da CAMPANHA (' . utf8_decode($nome_rifa) . ')! Entre em contato com o CLIENTE no WhatsApp a seguir: https://wa.me/55' . preg_replace("/[^0-9]/", "", $celular);

		//enviarWhatsapp ($_SESSION['telefone'], $mensagemWhatsappCliente);

		if($ativarSMS) {
			enviarWhatsapp ($celular, $mensagemWhatsappCliente);
			enviarWhatsapp ($rifa_dono_telefone, $mensagemWhatsappDono);
		}


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
