<?php

$bilhete = intval($_GET[bilhete]);
$rifa =  $_GET[rifa];

if($rifa == 0)
	echo -1;

include("../../class/conexao.php");

// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas
$sqlBil = "SELECT count(*) as C FROM tbl_bilhetes 
WHERE bil_numero = '$bilhete' 
AND bil_rifa = '$rifa'  
AND (bil_situacao = 'V'
	or bil_situacao = '' 
	or bil_situacao = 'P')";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();

if($bil[C] > 0){
	echo 0;
}else
echo 1;

?>