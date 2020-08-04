<?php

header('content-type: application/json; charset=utf-8');
include("../../class/conexao.php");

$bilhete = intval($_POST['bilhete']);
$retorno = array();

if($bilhete > 0){

	$consulta = $mysqli->query("select * from tbl_bilhetes where bil_numero = '$bilhete'");
	$dado     = $consulta->fetch_assoc();
	$count    = $consulta->num_rows;

	if($count == 0){
		$retorno['msg'] = "Bilhete disponível (Não vendido nem reservado).";
	}else{

		if($dado['bil_situacao'] == 'V')
			$retorno['msg'] = "Bilhete vendido";
		elseif($dado['bil_situacao'] == 'C')
			$retorno['msg'] = "Bilhete cancelado";
		elseif($dado['bil_situacao'] == 'P')
			$retorno['msg'] = "Bilhete reservado";

	}




	//Qual, grupo ele ta. Com que vendedor ele esta. Se vendido. Pra quem etc...


	/*[14:36, 6/8/2016] Naldo: Criar naquela area onde coloco os revendedores uma copia do CADASTRO de revendedores
	[14:37, 6/8/2016] Naldo: Pois se o TELEFONE OU EMAIL nao existir ainda. Eu como promotor possa cadastrar naquela mesma tela.novo revendedor que tbm eh novo usuario e que pode fazer login no site
	[14:38, 6/8/2016] Naldo: Coloca o telefone do promotor como login
	[14:38, 6/8/2016] Naldo: Alem do.email
	[14:38, 6/8/2016] Naldo: Comprendeu ate aqui tbm amigao?
	*/

}

echo json_encode($retorno);

?>