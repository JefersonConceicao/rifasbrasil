<?php

$codigo = intval($_GET['codigo']);

include("../class/conexao.php");


$sucesso = $mysqli->query("UPDATE tbl_rifas SET rifa_deletada = 1 WHERE rifa_cod = '$codigo'") or die($mysqli->error);
if($sucesso)
	echo "<script>alert('Rifa deletada!'); location.href='index.php?p=rifa';</script>";

?>