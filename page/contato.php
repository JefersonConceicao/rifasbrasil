<!-- Conteudo Section -->
<?php

include("class/conexao.php");

if(isset($_POST['contato'])){

    unset($_SESSION[nome], $_SESSION[email], $_SESSION[mensagem]);

    // Captação de dados   
    $_SESSION['nome'] = $mysqli->escape_string($_POST['nome']);
    $_SESSION['email'] = $mysqli->escape_string($_POST['email']);
    $_SESSION['mensagem'] = $mysqli->escape_string($_POST['mensagem']);

    if(($_SESSION['email']) ==  "")
        $erro[] = "Digite seu e-mail";

    if(($_SESSION['nome']) ==  "")
        $erro[] = "Digite seu nome";

    if(strlen($_SESSION['mensagem']) == 0)
        $erro[] = "Digite sua mensagem";

    // Operações com o Banco de Dados 
    if(count($erro) == 0){
        $mensagem = 
"Nome: $_SESSION[nome] \n
E-mail: $_SESSION[email] \n
\n
Mensagem: $_SESSION[mensagem]";

        mail("suporte.local@hotmail.com", "Mensagem de Usuário do RifasBrasil", $mensagem);
        unset($_SESSION[nome], $_SESSION[email], $_SESSION[mensagem]);
        echo "<script>alert('Mensagem enviada com sucesso!');</script>";
    }       

}

?>


<section class="section-default" id="destaque">

    <div class="col-md-12 col-lg-10 center-block">

        <b>Contato</b><hr>

        <div class="col-lg-12">
            <?php 
                    if(isset($erro) && count($erro) > 0)
                        foreach($erro as $valor)
                            echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
                    ?>
        
        </div>
        

    
    <div class="col-md-6 col-lg-4">
    
        <form id="login" name="login" method="POST"  action="">

            <div class="form-group col-lg-12 text-left">

                <label>Nome*</label>
                <input  
                value="<?php echo $_SESSION[nome]; ?>"  
                type="text" required
                class="input-lg form-control" 
                name="nome"
                >

            </div>

            <div class="form-group col-lg-12 text-left">

                <label>E-mail*</label>
                <input  
                value="<?php echo $_SESSION[email]; ?>"  
                type="email"  required
                class="input-lg form-control" 
                name="email"
                >

            </div>
            <div class="form-group col-lg-12 text-left">

                <label>Mensagem*</label>
                <textarea  
         
                required
                class="input-lg form-control" 
                name="mensagem"
                rows="2"
                ><?php echo $_SESSION[mensagem]; ?></textarea>

            </div>

         
            <div class="form-group col-lg-6 text-left">
                <input value="Enviar" name="contato" type="submit" class="btn btn-success btn-lg">
            </div>
            <div class="spacer"></div>  
   
        </form>
    </div>
        
        <div class="clearfix"></div>

        
        </di    v>
        
    </section>
