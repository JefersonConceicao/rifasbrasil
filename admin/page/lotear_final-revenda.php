<?php
include("../../class/conexao.php");


function get_bilhetes($mysqli, $rifa){

	$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
	$bilhetes = $bilhetes['rifa_maxbilhetes'];
	$grupo    = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

	if($bilhetes > 900 && $bilhetes < 1200)
		$bilhetes = 1000;
	elseif($bilhetes > 3000)
		$bilhetes = 10000;

	$grupos = ceil($bilhetes / $grupo['grupo']);

	$numero_letras = ceil($grupos/100);

	$bil = shuffleIt(range(0, $bilhetes-1), $rifa);

	$i = 0;
	$cont = 0;
	$titulo_grupos = array();

	for($i = 1; $i <= $numero_letras; $i++){

		$letra = getNameFromNumber($i);

		for($k = 1; $k <= 100; $k++){

			$titulo_grupos[] = $letra.$k;

			$cont++;
			if($cont == $grupos)
				break;
		}

	}


	$i = 1;
	$k = 0;
	$j = 0;
	$bilhetes_finais = array();

	foreach($bil as $val){


		$bilhetes_finais[$val] = $titulo_grupos[$k]; //str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT);
		//$bilhetes_finais[] = $titulo_grupos[$k]."-".$val."<br>$rifa";

		$j++;

		if($j == $grupo['grupo']){
			$j = 0;
			$k++;
		}


		if($i == $grupos) {$i=0;}
		$i++;

	} 

	return $bilhetes_finais;
}


$string_json = $_POST["string"];
$res = json_decode($string_json, true);

$rifa = $mysqli->escape_string($res['rifa']);

//var_dump($string_json);

if(!$_POST['tela_revendedores']){ 

	// se o cadastro é feito na tela de Revendedores, nao precisa excluir isso não!
	$mysqli->query("delete from grupo_revendedor where revenda in(select codigo from revenda where rifa = '$rifa')");
	$mysqli->query("delete from revenda where rifa = '$rifa'");

}

if($_POST['deletarGrupo'] > 0){

	$grupo = intval($_POST['deletarGrupo']);
	$mysqli->query("delete from grupo_revendedor where codigo = '$grupo'");

}

if($_POST['limpar'] > 0){

	$revenda = intval($_POST['limpar']);
	$mysqli->query("delete from grupo_revendedor where revenda = '$revenda'");
	$mysqli->query("delete from revenda where codigo = '$revenda'");

}

$erro = array();

// verifica se os grupos presentes já possuem vendas realizadas
/*
if(is_array($res['resultado'])){

	$qr_exec = $mysqli->query("select bil_numero from tbl_bilhetes where bil_rifa = '$rifa'");
	$assoc   = $qr_exec->fetch_assoc();

	$todos_bilhetes = get_bilhetes($mysqli, $rifa);

	$grupos_impedidos = array();

	do{
		$grupos_impedidos[] = $todos_bilhetes[$assoc['bil_numero']];
	}while($assoc   = $qr_exec->fetch_assoc());

	foreach($res["resultado"] as $tmp){

		$vendedor = $mysqli->escape_string($tmp['vendedor']);
		$obs = $mysqli->escape_string(trim($tmp['observacao']));

		if(intval($vendedor) > 0){

			if(is_array($tmp['grupos'])){
				foreach($tmp['grupos'] as $grupo){

					if(in_array($grupo, $grupos_impedidos)){
						// grupo ja possui bilhetes vendidos
						$erro[] = "O grupo $grupo do vendedor {$tmp['vendedor_nome']} já possui bilhetes vendidos. Remova-o do loteamento para salvar.";
					}

				}
			}

		}

	}
}*/

$retorno = array();

if(count($erro) > 0){
	$retorno['erro'] = 1;
	$retorno['msg'] = $erro;
	die(json_encode($retorno));
}

$retorno['erro'] = 0;
$retorno['msg'] = "Dados salvos com sucesso!";

if($res['new'] == "true"){
	$mysqli->query("delete from grupo_revendedor where revenda in(select codigo from revenda where rifa = '$rifa')");
	$mysqli->query("delete from cache_bilhetes_do_grupo where rifa = '$rifa'");
	$mysqli->query("delete from revenda where rifa = '$rifa'");
	//echo "Todos os registros foram deletados\n";
}

if(is_array($res['resultado'])){
	foreach($res["resultado"] as $tmp){

		$vendedor = $mysqli->escape_string($tmp['vendedor']);
		$obs = $mysqli->escape_string(trim($tmp['observacao']));

		if(intval($vendedor) > 0){
		
			$sql_code = "insert into revenda (rifa, pagamento, vendedor) 
						values('$rifa', '$obs', '$vendedor')";

			$mysqli->query($sql_code);
			$rev_codigo = $mysqli->insert_id;

			$sql_code = "insert into grupo_revendedor (grupo, revenda) values ";

			$auth = false;

			if(is_array($tmp['grupos']))
				foreach($tmp['grupos'] as $grupo){

					//echo "grupo $grupo $rev_codigo salvo\n";
					
					if($grupo && $rev_codigo) $auth = true; // autoriza a insercao no banco
					
					$sql_code .= "('$grupo', '$rev_codigo'),";
					
				}

			if($auth)
				$mysqli->query(substr($sql_code, 0, -1));

		}

	}
}

die(json_encode($retorno));
?>