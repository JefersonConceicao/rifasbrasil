<?php

include("../class/conexao.php");
include("../class/function_pagseguro.php");
include("page/finalizar_rifa.php");

session_start();
//error_reporting(0);

$sql_code = "Select usu_psemail, usu_pstoken from tbl_usuario WHERE usu_cod = '$_SESSION[usuario]'";
$sqlq = $mysqli->query($sql_code) or die($mysqli->error);
$dado = $sqlq->fetch_assoc();
$sql = array();

//Primeiro, obtem-se os dados de login do pagseguro do usuário
$historico_pagseguro = getPSHistory(date("Y-m-d", time()-(86400*10)), date("Y-m-d"), $dado[usu_psemail], $dado[usu_pstoken]);

if($historico_pagseguro){
	foreach($historico_pagseguro->transactions->transaction as $compra){

		if(substr($compra->reference, 0, 3) == 'FIN'){
			$codigo_rifa = substr($compra->reference, 4);     
			$sql[] = "UPDATE tbl_rifas SET rifa_finalizar = '3' WHERE rifa_cod = '$codigo_rifa'";
			finalizar_rifa($codigo_rifa, $mysqli);
			$erro[] = "Rifa $codigo_rifa finalizada automaticamente pelo PagSeguro.";				
		}
	}
}else
	$erro[] = "Seus e-mail ou token do Pagseguro estão inválidos. <a href='index.php?p=config'>Clique aqui</a> para atualizá-los.";
	
if(count($sql) > 0){
	foreach($sql as $code){
		$erro[] = DBExecute($code, $mysqli);
	}
}

echo "<script>alert('Status atualizados de acordo com o Pagseguro'); location.href='index.php?p=rifa';</script>"
//error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
?>
<div class="col-lg-12">
	<h2>Atualizando Status...</h2>
	<hr>
	<?php
	if(count($erro) > 0){
	    foreach($erro as $valor){
	        echo "<div class=\"col-lg-12 form-group bg-warning\"><p>$valor</p></div>";
	    }
	}else
		echo "<script>alert('Atualizado com sucesso!');</script><div class=\"col-lg-12\"><a href='/admin/index.php?p=rifa'>Clique aqui</a> para voltar.</div>";
	
	?>
	<div class="clearfix"></div>
</div>