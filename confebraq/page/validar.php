<?php

if(isset($_GET[cod]) && isset($_GET[email])){

	include("class/conexao.php");

	$codigo = intval($_GET[cod]);
	$email = $mysqli->escape_string($_GET[email]);

	$code_usu = DBSelect("select usu_cod from tbl_usuario where usu_email = '$email'", $mysqli);
	$temp = $code_usu[usu_cod];
	$code_usu = md5($temp);

	if($code_usu == $codigo){
		if(DBExecute("update tbl_usuario set usu_validado = 'S' where usu_cod = '$email'", $mysqli)){
			$erro[] = "Sua conta foi validada!";
			session_start();
			$_SESSION[admin] = 'N';
			$_SESSION[usuario] = $temp;
		}else
			$erro[] = "Falha ao validar conta";
	}

}


?>
<section class="section-default" id="destaque">

    <div class="col-md-12 col-lg-10 center-block">
    <div class="col-lg-12">
        <b>Validar e-mail</b><hr>       
        <?php 

        if(isset($erro) && count($erro) > 0)
            foreach($erro as $valor)
                echo "<div style='padding:10px; margin:10px 0 10px 0;' class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
        
        ?>
        <div class="spacer"></div>
    </div>
    <div class="col-lg-12">
    	<p>Clique no botão para acessar sua conta.</p>
    	<button onclick="javascript: location.href='admin/index.php';" class="btn-success">Minha Conta</button>
    </div>
    <div class="clearfix"></div>
    </div> 
    
</section>