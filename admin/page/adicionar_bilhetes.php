<?php

$bilhete             = intval($_GET['bilhete']);
$rifa                = intval($_GET['rifa']);
$vendedor_online_all = 16869;

if(!isset($_SESSION)) @session_start();

include("../../class/conexao.php");

// A consulta abaixo verifica quais bilhetes já foram vendidas e cria um array com estas
$sqlBil   = "SELECT count(*) as C FROM tbl_bilhetes 
WHERE bil_numero = '$bilhete' 
AND bil_rifa = '$rifa'  
AND (bil_situacao = 'V' or bil_situacao = '' or bil_situacao = 'P')";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil      = $queryBil->fetch_assoc();

$jaFoiVendido = false;
if($bil['C'] > 0) // bilhete ja foi vendido, não tem mais o que fazer
	$jaFoiVendido = true;


// verifica se a rifa é travada
$sqlBil     = "SELECT travar_bilhetes, rifa_maxbilhetes, banca_online, multiplicador, valor_aposta, rifa_dono, rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1";
$queryBil   = $mysqli->query($sqlBil) or die($mysqli->error);
$bil        = $queryBil->fetch_assoc();

if(!$bil['banca_online'] && $jaFoiVendido)
	die("[-2, null]");
else if($bil['banca_online']) {
	// faz a outra verificacao do valor maximo e da autorizacao
	$sql_query = "SELECT SUM(bil_aposta) as apostas, bil_numero FROM tbl_bilhetes WHERE bil_rifa = '$rifa' AND bil_numero = '$bilhete' GROUP BY bil_numero HAVING apostas >= '{$bil['valor_aposta']}'";
	$query = $mysqli->query($sql_query) or die($mysqli->error);
	$ja_vendeu = $query->num_rows;
	//echo $sql_query ;
	if($ja_vendeu)
		die("[-2, null]");
}

$tam        = strlen($bil['rifa_maxbilhetes'])-1;

$travado    = false;
$revendedor = "";
$reservado  = false;
$dono       = $bil['rifa_dono'];


if($dono == $_SESSION['usuario']) die('[1, null]'); // o dono da rifa pode vender em todos os casos

// venda direta
if($bil['travar_bilhetes'] == 1){

	$sqlBil = "SELECT gr.grupo, concat(u.usu_nome, ' (', u.usu_celular, ')') as rev, r.vendedor
	FROM grupo_revendedor gr, revenda r, tbl_usuario u
	WHERE r.rifa = '$rifa'
	and u.usu_cod = r.vendedor
	and gr.revenda = r.codigo";
	$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
	$bil = $queryBil->fetch_assoc();

	include('../../class/Rifa.php');
	$bilhetes_separados_por_grupo = getBilhetesESeusGrupos($rifa);

	do{ 

		$temp = $bilhetes_separados_por_grupo[strtoupper($bil['grupo'])];
		//echo implode('-', $temp);
	    // puxa os bilhetes do grupo em questão
		if(in_array($bilhete, $temp) && ($bil['vendedor'] == $_SESSION['usuario'] || $bil['vendedor'] == '16869'))
			die('[1, null]');
	    else if(in_array($bilhete, $temp))
	    	die("[-1, \"{$bil['rev']}\"]");

	}while($bil = $queryBil->fetch_assoc());

	$bilhete_extenso = str_pad ( $bilhete , $tam, '0', STR_PAD_LEFT);

	$sqlBil = "SELECT r.*, concat(u.usu_nome, ' (', u.usu_celular, ')') as rev from reserva r, tbl_usuario u 
	WHERE r.bilhete like '%$bilhete_extenso%' 
	and r.rifa = '$rifa' 
	and u.usu_cod = r.revendedor
	LIMIT 1";
	$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
	$bil = $queryBil->fetch_assoc();
	$rows = $queryBil->num_rows;

	if($rows > 0){

		do{

			if($bil['revendedor'] != $vendedor_online_all && $bil['revendedor'] != $_SESSION['usuario']){
					// bilhete esta atribuido a outra revendedor e NAO é o vendas online
				die("[-1, \"{$bil['rev']}\"]");
			}

		}while($bil = $queryBil->fetch_assoc());

		// vendedor = rifasonline ou vendedor = revendedor oficial da rifa
		die("[1, null]");

	}else{ // rifa travada, sem revendedor e disponivel
		
		$sqlBil = "SELECT concat(u.usu_nome, ' (', u.usu_celular, ')') as rev, u.usu_cod from tbl_rifas r, tbl_usuario u 
		WHERE r.rifa_cod = '$rifa' 
		and u.usu_cod = r.rifa_dono
		LIMIT 1";
		$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
		$bil = $queryBil->fetch_assoc();

		die("[0, \"{$bil['rev']}\"]");
	}

}else // não travado, pode vender normalmente
	die("[1, null]");

?>