<?php

function finalizar_rifa($rifa_codigo, $mysqli){

	//include("../class/function_primeiroNome.php");
	include("../class/function_limparNumero.php");

	$erro = array();

	$code_rifa = "SELECT * FROM tbl_rifas r, tbl_usuario u WHERE u.usu_cod =  r.rifa_dono AND r.rifa_cod = '$rifa_codigo'";
	$exec_rifa = $mysqli->query($code_rifa) or die($mysqli->error);
	$rifa = $exec_rifa->fetch_assoc();
	$cont_rifa = $exec_rifa->num_rows;

	mail($rifa[usu_email], "RifasBrasil - Rifa finalizada!", "Olá.\n\nSua rifa foi finalizada. Entre em contato com o vencedor para entregar o prêmio.\n\nAcesse seu Painel de Controle > Rifas > Detalhes e pegue os dados de contato dos vencedores.\n\nAtenciosamente,\nRifasBRASIL");

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
				$lista_perdedores[] = $perdedor[usu_celular];
			}while($perdedor = $qr_perdedor->fetch_assoc());

		echo 1;

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

			echo 2;


		}else{

			echo 3;

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
					$lista_vencedores[] = $vencedor[usu_celular];

				}while($vencedor = $qr_vencedor->fetch_assoc());

			echo 4;

		}


	}else
		$erro[] = "Nenhuma rifa com esse código.";

	include("../class/function_enviarSMS.php");

	$ativar_sms = true;
	//var_dump($lista_bilhetes);


	if(count($lista_vencedores) > 0){

		echo 5;

		//SMS para Vencedores
		//echo "VENCEDORES";
		//var_dump($lista_vencedores);
		$msg_vencedor = "RifasBRASIL: Parabens, vc foi um dos ganhadores da rifa rifasbrasil.com.br/confebraq/rifa/".$rifa_codigo.". Premiado(s): ".implode(", ", $lista_bilhetes).". Clique no link ou ligue ".limparNumero($rifa[usu_celular]);
		$erro[] = enviarSMS($lista_vencedores, $msg_vencedor, $ativar_sms);
				
	}

	if($rifa['rifa_maxbilhetes'] <= 100){
		if(count($lista_perdedores) > 0){

			echo 6;

			//SMS para Perdedores
			//echo "PERDEDORES";
			$msg_perdedor = "RifasBRASIL: Rifa rifasbrasil.com.br/confebraq/rifa/".$rifa_codigo." finalizou, infelizmente vc nao ganhou. Premiado(s): ".implode(", ", $lista_bilhetes).". A proxima podera ser sua!";
			$erro[] = enviarSMS($lista_perdedores, $msg_perdedor, $ativar_sms);

		}
	}

	return $erro;

}


?>