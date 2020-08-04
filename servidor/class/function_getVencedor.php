<?php

function getVencedor($rifa_codigo, $bilhete, $mysqli){

	if($rifa_codigo > 0){

		$code_rifa = "SELECT rifa_maxbilhetes as bil_num FROM tbl_rifas WHERE rifa_cod = '$rifa_codigo'";
		$query_rifa = DBExecute($code_rifa, $mysqli);
		$rifa = $query_rifa->fetch_assoc();

		if($rifa[bil_num] == 100)
			$resultado = substr($bilhete, -2);
		elseif($rifa[bil_num] == 1000)
			$resultado = substr($bilhete, -3);
		elseif($rifa[bil_num] == 10000)
			$resultado = substr($bilhete, -4);
		else{
			return -3;
			exit();
		}

		$code_venc = "
		SELECT count(*) as c 
		FROM tbl_bilhetes 
		WHERE bil_numero = '$resultado' 
		AND bil_rifa = '$rifa_codigo' 
		AND bil_situacao = 'V'";
		$rifa = DBSelect($code_venc, $mysqli);

		if($rifa[c] == 0)
			return -2;
		else{
			$code_usu = "
			SELECT comp_cliente 
			FROM tbl_compra
			WHERE comp_cod in (select bil_compra from tbl_bilhetes where bil_numero = '$resultado' AND bil_rifa = '$rifa_codigo' 
		AND bil_situacao = 'V')";
			$usuario = DBSelect($code_usu, $mysqli);
			
			return $usuario['comp_cliente'];
		}
	}else return -1;

}
?>