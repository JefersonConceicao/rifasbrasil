<?php

if(!function_exists('enviarSMS')){
function enviarSMS($n, $msg, $ativo = true){

	include_once("zenvia/human_gateway_client_api/HumanClientMain.php");
	include_once("function_limparNumero.php");


	$final = Array();

	/* 
	
	Tipo da Mensagem

	Tipo A: to;message
	Tipo B: to;message;from
	Tipo C: to;message;id
	Tipo D: to;message;id;from
	Tipo E: to;message;from;id;schedule

	*/

	$tipo = HumanMultipleSend::TYPE_A;

	// Retorno
	$callBack = HumanMultipleSend::CALLBACK_INACTIVE;


	// Login no Zenvia
	$humanMultipleSend = new HumanMultipleSend("zerobugs.api", "4v4JAAOqK3");



	// Enviamos as Mensagens
	$msg_list = "";
	if(is_array($n)){

		foreach($n as $ch=>$numero){

			// Tratamento da variável número
			$numero = limparNumero($numero);

			if(strlen($numero) < 10 || strlen($numero) > 11)
				$final[$numero] = -1;
			else{

				$numero = "55".$numero;
				// Mensagens
				$msg_list  = $numero.";".$msg."\n";

			}

			if($ativo){
				$responses = $humanMultipleSend->sendMultipleList($tipo, $msg_list, $callBack);
				$final[$n[$ch]] = $callBack;
			}else
				$final[$n[$ch]] = -2;
		}

		

	}else
	{

		// Tratamento da variável número
		$numero = limparNumero($n);

		if(strlen($numero) < 10 || strlen($numero) > 11)
			$final[$n] = -1;
		else{

			$numero = "55".$numero;

			// Mensagens
			$msg_list  .= $numero.";".$msg."\n";


			// Enviamos o SMS por fim
			if($ativo){
				$responses = $humanMultipleSend->sendMultipleList($tipo, $msg_list, $callBack);
				$final[$n] = $callBack;
			}else
				$final[$n] = -2;

		}

	}

	return $final;

	/*


	Possíveis valores para o retorno
	-2 - Parametro ativo = false
	-1 - Número fornecido inválido
	00 - Ok
	01 - Scheduled
	02 - Sent
	03 - Delivered
	04 - Not Received
	05 - Blocked - No Coverage
	06 - Blocked - Black listed
	07 - Blocked - Invalid Number
	08 - Blocked - Content not allowed
	08 - Blocked - Message Expired
	09 - Blocked
	10 - Error

	*/

}

/*
$numero = Array();
$result = Array();

$numero[] = "3591315266";
$numero[] = "3591315267";
$numero[] = "3591315268";
$numero[] = "3591315269";

$result = enviarSMS($numero, "Mensagem de Teste RifasBRASIL", false);

var_dump($result);

*/
}
?>