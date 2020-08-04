<?php
include("../../class/conexao.php");

$string_json = $_POST["string"];
$res = json_decode($string_json, true);

$rifa = $mysqli->escape_string($res['rifa']);

$mysqli->query("delete from grupo_revendedor where revenda in(select codigo from revenda where rifa = '$rifa')");
$mysqli->query("delete from revenda where rifa = '$rifa'");

foreach($res["resultado"] as $tmp){

	$vendedor = $mysqli->escape_string($tmp['vendedor']);
	$obs = $mysqli->escape_string($tmp['observacao']);

	if(intval($vendedor) > 0){
	
		$sql_code = "insert into revenda (rifa, pagamento, vendedor) 
					values('$rifa', '$obs', '$vendedor')";
		$mysqli->query($sql_code);
		$rev_codigo = $mysqli->insert_id;

		foreach($tmp['grupos'] as $grupo){

			$sql_code = "insert into grupo_revendedor (grupo, revenda) values('$grupo', '$rev_codigo')";
			if(strlen($grupo) > 0)
				$mysqli->query($sql_code);
		}

	}

}
?>