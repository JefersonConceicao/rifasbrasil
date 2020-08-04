<?php



header('content-type: application/json; charset=utf-8');

$bilhete = intval($_POST['bilhete']);

$rifa = intval($_POST['rifa']);


include("../../class/conexao.php");



$retorno = array();

$retorno['bilhete'] = $bilhete;

$retorno['rifa'] = $rifa;



// seleciona o numero maximo de bilhetes

$rifa_detalhes = DBSelect("select * from tbl_rifas where rifa_cod = '$rifa' limit 1", $mysqli);





function getNameFromNumber($num)
{

	$numeric = ($num - 1) % 26;

	$letter = chr(65 + $numeric);

	$num2 = intval(($num - 1) / 26);

	if ($num2 > 0) {

		return getNameFromNumber($num2) . $letter;
	} else {

		return $letter;
	}
}



function verificar_grupo_reservado($mysqli, $rifa, $bilhete)
{



	//$_POST['bilhete']



	$bilhetes = db_select($mysqli, "select revendedor, bilhete, nome_grupo from reserva where rifa = '$rifa'");



	$resultado = array();



	if (!is_array($bilhetes))

		return false;



	foreach ($bilhetes as $bil) {



		$grupo      = $bil['nome_grupo'];

		$revendedor = $bil['revendedor'];

		$bil        = $bil['bilhete'];



		if (substr_count($bil, '-') == 1) {



			$tmp = explode('-', $bil);



			$start = $tmp[0];

			$end   = $tmp[1];

			if ($tmp[0] > $tmp[1]) {

				$start = $tmp[1];

				$end   = $tmp[0];
			}



			for ($start; $start <= $end; $start++) {



				if (intval($bilhete) == $start) {

					$resultado['grupo'] = $grupo;

					$resultado['revendedor'] = $revendedor;

					return $resultado;
				}
			}
		} else {

			$var = explode(',', $bil);



			if (!is_array($var)) return false;



			foreach ($var as $v) {



				if (intval($bilhete) == intval($v)) {

					$resultado['grupo'] = $grupo;

					$resultado['revendedor'] = $revendedor;

					return $resultado;
				}
			}
		}
	}



	return false;
}





// Funcao que retorna o codigo alfanumerico do bilhete

function get_cod_alfanumerico($mysqli, $rifa, $bil_cod)
{



	/*

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



	*/

	$url = 'http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php?rifa=' . $rifa . '&layout=1&bilhete=' . $bil_cod;

	$res = trim(file_get_contents($url));

	$tmp = explode('-', $res);

	return $tmp[0] . "-Nº " . $bil_cod . " <small>" . substr(md5(intval($rifa) . intval($bil_cod)), 0, 6) . "</small>";



	/*

	foreach($bil as $val){





		//$bilhetes_finais[$titulo_grupos[$k]][] = str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT);



		if($bil_cod == $val)

			return $titulo_grupos[$k]."-Nº ".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT). " <small>".substr(md5($val), 0, 4)."</small>";

		//$bilhetes_finais[] = $titulo_grupos[$k]."-".$val."<br>$rifa";



		$j++;



		if($j == $grupo['grupo']){

			$j = 0;

			$k++;

		}





		if($i == $grupos) {$i=0;}

		$i++;



	} */
}

// Fim



$bilhetes = DBSelect("SELECT
    bil.bil_cod,
    bil.bil_rifa,
    bil.bil_numero,
    bil.bil_compra
FROM
    tbl_bilhetes AS bil
INNER JOIN
	tbl_compra AS compra ON bil.bil_compra = compra.comp_cod
WHERE
    (bil.bil_numero = '$bilhete' AND bil.bil_rifa = '$rifa')
    AND compra.comp_situacao != '7'", $mysqli);

$retorno['valido'] = false;



$bilhetes_count = $rifa_detalhes['rifa_maxbilhetes'];



if ($bilhetes_count > 900 && $bilhetes_count < 1200)

	$bilhetes_count = 1000;

elseif ($bilhetes_count > 3000)

	$bilhetes_count = 10000;



if ($bilhete >= $bilhetes_count)

	$retorno['msg'] = "Esta rifa possui apenas " . $bilhetes_count . " bilhetes, portanto o bilhete $bilhete informado não pertence à ela.";

elseif (count($bilhetes) == 0) {



	$retorno['valido'] = true;

	$retorno['msg'] = "Este bilhete não foi vendido.";



	$retorno['rifa_nome'] = $rifa_detalhes['rifa_titulo'];

	$retorno['bilhete_codigo'] = get_cod_alfanumerico($mysqli, $rifa, $bilhete);

	// pega o grupo

	$tmp = explode('-', $retorno['bilhete_codigo']);

	$retorno['grupo'] = $tmp[0];



	$grupo = $tmp[0][0] . "-";

	for ($i = 1; $i < strlen($tmp[0]); $i++)

		$grupo .= $tmp[0][$i];



	// pega o revendedor

	$grupo_tmp = str_replace('-', '', $grupo);

	$revendedor = DBSelect("

		select * 

		from grupo_revendedor g, revenda r, tbl_usuario u 

		where g.revenda = r.codigo 

		and r.rifa = '$rifa' 

		and g.revenda = r.codigo

		and g.grupo = '$grupo_tmp' 

		and u.usu_cod = r.vendedor 

		limit 1", $mysqli);



	if (count($revendedor) == 0)

		$retorno['revendedor'] = "Sem revendedor";

	else

		$retorno['revendedor'] = $revendedor['usu_nome'];



	// Còdigo de verificacao de reserva

	$abc = verificar_grupo_reservado($mysqli, $rifa, $bilhete);

	if (is_array($abc)) {

		$retorno['grupo'] = $abc['grupo'];



		$revendedor = DBSelect("

		select usu_nome

		from tbl_usuario

		where usu_cod = '" . $abc['revendedor'] . "' limit 1", $mysqli);



		$retorno['teste'] = $abc['revendedor'];



		$retorno['revendedor'] = $revendedor['usu_nome'];



		$xyz = explode('-', $retorno['bilhete_codigo']);

		$retorno['bilhete_codigo'] = $abc['grupo'] . "-" . $xyz[1];
	}

	// Fim



	$retorno['compra'] = $bilhetes['bil_compra'];



	$retorno['comprador'] = "Não vendido";
} else {



	$retorno['valido'] = true;

	$retorno['rifa_nome'] = $rifa_detalhes['rifa_titulo'];

	$retorno['bilhete_codigo'] = get_cod_alfanumerico($mysqli, $rifa, $bilhete);

	// pega o grupo

	$tmp = explode('-', $retorno['bilhete_codigo']);

	$retorno['grupo'] = $tmp[0];



	$grupo = $tmp[0][0] . "-";

	for ($i = 1; $i < strlen($tmp[0]); $i++)

		$grupo .= $tmp[0][$i];



	// pega o revendedor

	$grupo_tmp = str_replace('-', '', $grupo);

	$sql = "

		select * 

		from grupo_revendedor g, revenda r, tbl_usuario u 

		where g.revenda = r.codigo 

		and r.rifa = '$rifa' 

		and g.revenda = r.codigo

		and g.grupo = '$grupo_tmp' 

		and u.usu_cod = r.vendedor 

		limit 1";

	$revendedor = DBSelect($sql, $mysqli);



	// verifica se foi vendido por alguem
	//old sql
	//$sql = "SELECT u.usu_nome from tbl_usuario u, tbl_compra c, tbl_bilhetes b WHERE b.bil_numero = '$bilhete' and c.comp_cod = b.bil_compra and u.usu_cod = c.comp_revendedor LIMIT 1";
	
	// novo sql
	$sql = "SELECT
    compras.comp_revendedor,
    usuarios.usu_nome
	FROM
		tbl_bilhetes bilhetes
	INNER JOIN tbl_compra compras ON
		bilhetes.bil_compra = compras.comp_cod
	JOIN tbl_usuario usuarios ON
		usuarios.usu_cod = compras.comp_revendedor
	WHERE
		bilhetes.bil_numero = '$bilhete' AND bilhetes.bil_rifa = '$rifa'
	LIMIT 1";

	$vendedor = DBSelect($sql, $mysqli);



	if (count($revendedor) == 0)

		$retorno['revendedor'] = "Sem revendedor";

	else

		$retorno['revendedor'] = $revendedor['usu_nome'];



	//var_dump($vendedor);



	if ($vendedor['usu_nome']) {

		$retorno['revendedor'] .= " - Vendido por {$vendedor['usu_nome']}";
	}



	$retorno['compra'] = $bilhetes['bil_compra'];



	// Código de verificacao de reservas

	$abc = verificar_grupo_reservado($mysqli, $rifa, $bilhete);





	if (is_array($abc)) {

		$retorno['grupo'] = $abc['grupo'];

		$revendedor = DBSelect("

		select usu_nome 

		from tbl_usuario u 

		where u.usu_cod = '" . $abc['revendedor'] . "'", $mysqli);

		$retorno['revendedor'] = $revendedor['usu_nome'];



		$xyz = explode('-', $retorno['bilhete_codigo']);

		$retorno['bilhete_codigo'] = $abc['grupo'] . "-" . $xyz[1];
	}

	// Fim
	$comprador = DBSelect("select * from tbl_compra c, tbl_usuario u where c.comp_cod = '" . $bilhetes['bil_compra'] . "' and u.usu_cod = c.comp_cliente and c.comp_situacao != '7' limit 1", $mysqli);

	$retorno['comprador'] = "Comprado por " . $comprador['usu_nome'] . " (Tel: " . $comprador['usu_celular'] . ") em " . date("d/m/Y H:i", strtotime($comprador['comp_data']));
}





echo json_encode($retorno);
