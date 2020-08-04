<!-- Conteudo Section -->
<?php

include("class/conexao.php");

if(isset($_POST[ok])){

    $erro = Array();

    /// captar tdados
    $email = $mysqli->escape_string($_POST[email]);

    // validar e-mail
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        $erro[] = "E-mail inválido.";

    if(count($erro) == 0){

        // cria nova senha
        $senha = substr(md5(time().$email), 0, 6);
        $senhabd = md5($senha);

        $result = false;

        $exe_contar = $mysqli->query("select count(*) as c from tbl_usuario where usu_email = '$email'") or die($mysqli->error);
        $fet_contar = $exe_contar->fetch_assoc();

        if($fet_contar['c'] == 0)
            $erro[] ="E-mail não cadastrado.";
        else{

            $enviar = mail($email, "Nova senha", "Olá\n\nSua nova senha é: $senha \n\nAtenciosamente\nConfebraq");
            // envia ela por e-mail
            if($enviar) $result = $mysqli->query("update tbl_usuario set usu_senha = '$senhabd' where usu_email = '$email'");
            else $result = false;

            if($result)
                $erro[] ="Senha alterada com sucesso.";
            else
                $erro[] ="Falha ao alterar senha.";

        }

    }

}


?>

<section class="section-default" id="destaque">

    <div class="col-md-12 col-lg-10 center-block">
    <div class="col-lg-12">
        <b>Recuperar Senha</b><hr>
        <p>Informe seu e-mail. Uma nova senha será enviada para ele.</p>
        
        <?php 

        if(isset($erro) && count($erro) > 0)
            foreach($erro as $valor)
                echo "<div style='padding:10px; margin:10px 0 10px 0;' class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
        
        ?>
        <div class="spacer"></div>
    </div>
    <div class="col-lg-12">
        <form method="post" action="">
                <div class="col-lg-12 form-group form-inline">
                    <label for="">E-mail</label>
                    <input name="email" required  type="email" class="form-control">
                    <button name="ok" value="1" class="btn-success btn">Enviar</button>
                </div>    
        </form>      
    </div>
    <div class="clearfix"></div>
    </div> 
    
</section>