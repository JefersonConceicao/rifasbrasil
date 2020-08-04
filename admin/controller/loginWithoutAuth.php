<?php

include('../../class/conexao.php');


if($_SERVER['SERVER_NAME'] == 'localhost')
  $configs = require_once('../../config/local.config.php');
else
  $configs = require_once('../../config/production.config.php');

// Buscar a hash na tabela do banco => retornar o usuario

$hash = $_GET['id'];
$query = $mysqli->query("SELECT * FROM usuarios_sem_login WHERE hash = '$hash'") or die($mysqli->error);
$check = $query->fetch_assoc();

// verificando o tipo de rifa => retorna $tipo_rifa['dezena_bolao']
$tipo_rifa = $mysqli->query("SELECT dezena_bolao, rifa_maxbilhetes FROM tbl_rifas WHERE rifa_cod = '{$check['rifa_id']}'") or die($mysqli->error);
$tipo_rifa = $tipo_rifa->fetch_assoc();

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/admin";

if ($tipo_rifa['dezena_bolao'] > 0) {
  $url = $actual_link . '/index.php?p=ver_bilhetes_bolao_dezena&limpar_carrinho=true&rifa=' . $check['rifa_id'] . '';
} else if ($tipo_rifa['rifa_maxbilhetes'] == 100 && $tipo_rifa['dezena_bolao'] == 0) {
  $url = $actual_link . '/index.php?p=ver_bilhetes_centena&limpar_carrinho=true&rifa=' . $check['rifa_id'] . '';
} else if (($tipo_rifa['rifa_maxbilhetes'] == 1000 || $tipo_rifa['rifa_maxbilhetes'] == 10000) && $tipo_rifa['dezena_bolao'] == 0) {
  $url = $actual_link . '/index.php?p=ver_bilhetes_mobile_dez_cen&limpar_carrinho=true&rifa=' . $check['rifa_id'] . '';
} else {
  $url = $actual_link . '/index.php?p=ver_bilhetes&limpar_carrinho=true&rifa=' . $check['rifa_id'] . '';
}

// inicia a session e redireciona o usuario

header('P3P: CP="CAO PSA OUR"');
session_start();

$query = $mysqli->query("SELECT usu_nome FROM tbl_usuario WHERE usu_cod = '{$check['cod_rev']}'") or die($mysqli->error);
$revendedor = $query->fetch_assoc();

$_SESSION['usuario_sem_login'] = $check['id'];
$_SESSION['rifa_id'] = $check['rifa_id'];
$_SESSION['nome_rev'] = $revendedor['usu_nome'];
$_SESSION['cod_rev'] = $check['cod_rev'];


header('Location: ' . $url . '');
