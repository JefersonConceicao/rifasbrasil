<?php


if(!isset($_POST['function']))
	die(json_encode(array('erro'=>1, 'msg'=>"Função inválida!")));

if(!isset($_SESSION))
	@session_start();

if(!isset($_SESSION['usuario']))
	die(json_encode(array('erro'=>1, 'msg'=>"Acesso não autorizado.")));

function post_error($err = ''){
	die(json_encode(array('erro'=>1, 'msg'=>"Data missing: " . $err)));
}

function sucesso($content = ''){
	die(json_encode(array('erro'=>0, 'msg'=>"Sucesso! " . $content)));
}

include('../../class/conexao.php');


if(isset($_POST['function'])){

	if($_POST['function'] == 'tornar_supervisor'){

		if(isset($_POST['usuario']) && isset($_POST['rifa'])){
			$mysqli->query(sprintf("UPDATE tbl_revendedor SET supervisor = 1 WHERE usu_cod = '%d' and rifa_cod = '%d'", intval($_POST['usuario']), intval($_POST['rifa'])));
			sucesso();
		}else
			post_error();
	}

	if($_POST['function'] == 'destornar_supervisor'){

		if(isset($_POST['usuario']) && isset($_POST['rifa'])){
			$mysqli->query(sprintf("UPDATE tbl_revendedor SET supervisor = NULL WHERE usu_cod = '%d' and rifa_cod = '%d'", intval($_POST['usuario']), intval($_POST['rifa'])));
			sucesso();
		}else
			post_error();

	}

	if($_POST['function'] == 'get_revendedores'){

		if(isset($_POST['rifa']) && isset($_SESSION['usuario'])){

			$rev_query = $mysqli->query(sprintf("SELECT usu_nome, comissao from tbl_revendedor r, tbl_usuario u WHERE r.rifa_cod = '%d' and r.pai = '%d' and u.usu_cod = r.usu_cod", intval($_POST['rifa']), intval($_POST['usuario']))) or die($mysqli->error);
			$dados = $rev_query->fetch_assoc();

			$resultado = array();
			do{
				if($dados) $resultado[] = array_map("utf8_encode", $dados);
			}while($dados = $rev_query->fetch_assoc());

			die(json_encode(array(
				'erro'=>0,
				'resultado'=>$resultado
			)));

		}

	}

	post_error('errof');

}else
	post_error('errof2');


?>