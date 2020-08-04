<?php


include("../../class/conexao.php");

function onlyNumbers($str){

	$final = '';
	for($k = 0; $k < strlen($str); $k++){

		if(is_numeric($str[$k])) $final .= $str[$k];

	}
	return $final;

}

if($_POST){

	include("../../class/function_limparNumero.php");
	include("../../class/function_enviarSMS.php");

	$erro = array();

	$rifa_codigo = $_POST['rifa_codigo'];

	$code_rifa = "SELECT * FROM tbl_rifas r, tbl_usuario u WHERE u.usu_cod =  r.rifa_dono AND r.rifa_cod = '$rifa_codigo'";
	$exec_rifa = $mysqli->query($code_rifa) or die($mysqli->error);
	$rifa = $exec_rifa->fetch_assoc();
	$cont_rifa = $exec_rifa->num_rows;

	//mail($rifa[usu_email], "RifasBrasil - Rifa finalizada!", "Olá.\n\nSua rifa foi finalizada. Entre em contato com o vencedor para entregar o prêmio.\n\nAcesse seu Painel de Controle > Rifas > Detalhes e pegue os dados de contato dos vencedores.\n\nAtenciosamente,\nRifasBRASIL");

	if($cont_rifa > 0){

		$lista_perdedores = array();
		$lista_vencedores = array();
		$lista_bilhetes = array();

		// Se a rifa existir
		// Encontramos os perdedores

		$sql_perdedor = "
		SELECT usu_celular 
		FROM tbl_usuario 
		WHERE usu_cod in (
			select comp_cliente 
			from tbl_compra 
			where comp_cod in(
				select bil_compra 
				FROM tbl_bilhetes 
				where bil_rifa = '$rifa_codigo')) 
		and usu_cod not in(
			select pre_vencedor 
			from tbl_premio 
			where pre_rifa = '$rifa_codigo') 
		and usu_cod not in (
			select rifa_vencedor 
			from tbl_rifas 
			where rifa_cod = '$rifa_codigo')";

		$qr_perdedor = $mysqli->query($sql_perdedor) or die($mysqli->error);
		$perdedor = $qr_perdedor->fetch_assoc();

		if( $qr_perdedor->num_rows > 0)
			do{
				$lista_perdedores[] = onlyNumbers($perdedor[usu_celular]);
			}while($perdedor = $qr_perdedor->fetch_assoc());

		//echo 1;

		//Encontra os vencedores

		if($rifa[rifa_premios] == 1){

			// dados do vencedor
			$sql_vencedor = "SELECT usu_celular FROM tbl_usuario WHERE usu_cod = '$rifa[rifa_vencedor]'";
			$qr_vencedor = $mysqli->query($sql_vencedor) or die($mysqli->error);
			$vencedor = $qr_vencedor->fetch_assoc();

			if( $qr_vencedor->num_rows > 0){
				$bilhete = str_pad($rifa[rifa_bilhetepremiado], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT);
				$lista_bilhetes[] = $bilhete;
				$lista_vencedores[] = $vencedor[usu_celular];
				$premio[$bilhete] = substr($rifa[rifa_descricao], 0, 20);
			}

			//echo 2;


		}else{

			//echo 3;

			// dados dos vencedores
			$sql_vencedor = "
			SELECT usu_celular, pre_bilhete 
			FROM tbl_usuario u, tbl_premio p 
			WHERE u.usu_cod = p.pre_vencedor 
			AND p.pre_rifa = '$rifa_codigo'
			order by pre_ranking
			";
			$qr_vencedor = $mysqli->query($sql_vencedor) or die($mysqli->error);
			$vencedor = $qr_vencedor->fetch_assoc();
			if( $qr_vencedor->num_rows > 0)
				do{	

					$bilhete = str_pad($vencedor[pre_bilhete], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT);
					$lista_bilhetes[] = $bilhete;
					$lista_vencedores[] = onlyNumbers($vencedor[usu_celular]);

				}while($vencedor = $qr_vencedor->fetch_assoc());

			//echo 4;

		}

		$retorno['perdedores'] = $lista_perdedores;
		$retorno['vencedores'] = $lista_vencedores;
		//var_dump($retorno['numeros']);

		//var_dump($lista_vencedores);
		//var_dump($lista_perdedores);

		if(!$rifa['rifa_bilhetepremiado'] || empty($rifa['rifa_bilhetepremiado']))
			$bilhete_premiado = 'XX';
		else
			$bilhete_premiado = '['.substr($rifa['rifa_bilhetepremiado'], -2).']';

		$msg_vencedor = 'RIFASBRASIL: Parabéns! voce foi o GANHADOR da rifa: '.substr($rifa['rifa_titulo'], 0, 25).', sorteio dia '.date('d/m/Y', strtotime($rifa['rifa_dtsorteio'])).'  com o bilhete '.$bilhete_premiado.'. ligue para '.$rifa['usu_celular'];
		$msg_perdedor = 'RIFASBRASIL: O resultado da rifa: '.$rifa['rifa_titulo'].', sorteio dia '.date('d/m/Y', strtotime($rifa['rifa_dtsorteio'])).' foi '.$bilhete_premiado.'. Obrigado! Boa Sorte na Proxima!';

		$retorno['sms_vencedor'] = $msg_vencedor;
		$retorno['sms_perdedor'] = $msg_perdedor;

		if($_POST['action'] == 'getNumeros'){
			// SMS Celular
			die(json_encode($retorno));

		}elseif($_POST['action'] == 'smsServidor'){

			if(!$_SESSION) @session_start();

			if($_SESSION['admin'] == 'S'){

				enviarSMS($lista_vencedores, $msg_vencedor, $ativar_sms);
				enviarSMS($lista_perdedores, $msg_perdedor, $ativar_sms);
				//enviarSMS(array('+5535991315266'), $msg_vencedor, $ativar_sms);
				//die('retornado');

			}else{

				$cont = $mysqli->query("select * from tbl_autorizar_sms where rifa = '$rifa_codigo'")  or die($mysqli->error);
				if($cont->num_rows == 0)
					$mysqli->query("insert into tbl_autorizar_sms (rifa, enviado, data, aguardando_envio) values ('$rifa_codigo', 0, NOW(), 1)");

			}

			

			// SMS Servidor
			//enviarSMS($lista_vencedores, $msg_vencedor, $ativar_sms);
			//enviarSMS($lista_perdedores, $msg_perdedor, $ativar_sms);

		}


	}else
		$erro[] = "Nenhuma rifa com esse código.";

}

?>