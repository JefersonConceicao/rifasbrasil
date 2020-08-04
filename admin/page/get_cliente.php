<?php

$codigo = intval($_GET[codigo]);
include("../../class/conexao.php");

$cli = DBExecute("select usu_nome, usu_email from tbl_usuario where usu_cod = '$codigo'", $mysqli);
$cliente = $cli->fetch_assoc();

echo $cliente['usu_nome']."*".$cliente['usu_email'];
?>