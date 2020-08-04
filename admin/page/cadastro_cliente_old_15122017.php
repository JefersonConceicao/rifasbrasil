<!-- Conteudo Section -->
<?php

include("../class/conexao.php");
include("../class/function_validarCpf.php");
include("../class/function_limparNumero.php");


// Cadastro de Usuários
if(isset($_POST['cadastrar'])){

    // Captação de dados   
    $_SESSION['nome'] = $mysqli->escape_string($_POST['nome']);
    //$_SESSION['email'] = $mysqli->escape_string($_POST['email']);
    $_SESSION['telefone'] = limparNumero($_POST['telefone']);
    $_SESSION['regiao'] = $mysqli->escape_string($_POST['regiao']);
    $_SESSION['tipo_venda'] = $_POST['tipo_venda'];
    //$_SESSION['cpf'] = limparNumero($_POST['cpf']);
    //$_SESSION['senha'] = $_POST['senha'];
    //$senha = $_SESSION['senha'];
    //$_SESSION['rsenha'] = $_POST['rsenha'];

    /* Validação de dados
    $sql = "SELECT COUNT(*) as NumUsuarios FROM tbl_usuario WHERE usu_email = '$_SESSION[email]'";
    $que = $mysqli->query($sql) or die(mysql_error());
    $dado = $que->fetch_assoc();

    if($dado['NumUsuarios'] > 0)
        $erro[] = "E-mail já cadastrado. Peça pro usuário fazer login e comprar.";

    if(($_SESSION['email']) ==  "")
        $erro[] = "Digite seu e-mail";

        */

    if(($_SESSION['nome']) ==  "")
        $erro[] = "Digite seu nome";

    if(($_SESSION['regiao']) ==  "")
        $erro[] = "Digite o Bairro/Região";

    if(strlen($_SESSION['telefone']) < 10)
        $erro[] = "Digite seu telefone com DDD. Por exeplo: 99 9999-9999";


    /*

    if(strlen($_SESSION['senha']) < 6 || strlen($_SESSION['senha']) > 16)
        $erro[] = "A senha deve ter entre 6 e 16 caracteres";

    if($_SESSION['senha'] != $_SESSION['rsenha'])
        $erro[] = "As senhas não batem";

    */
    // Operações com o Banco de Dados 
    if(count($erro) == 0){

        /*

        $_SESSION[senha] = md5($_SESSION[senha]);

        $sql_code_usuario = 
            "INSERT INTO tbl_usuario
            (
            usu_nome,
            usu_email,
            usu_celular,
            usu_cpf,
            usu_senha,
            usu_admin
            )VALUES(
            '$_SESSION[nome]',
            '$_SESSION[email]',
            '$_SESSION[telefone]',
            '$_SESSION[cpf]',
            '$_SESSION[senha]',
            'N'
            )";

        $confirma_usuario = $mysqli->query($sql_code_usuario) or die($mysqli->error);
        $usu_codigo = $mysqli->insert_id;

        $_SESSION[usuario_admin] = $usu_codigo;
        
            
        if(count($erro) == 0){
           
            mail($_SESSION[email], "Valide sua conta", "Olá\n\nClique no link para validar sua conta: http://rifasbrasil.com.br/index.php?p=validar&cod=".md5($usuario)."&email=".$_SESSION[email]."\n\nAtenciosamente,\nRifasBRASIL");
            
            unset($_SESSION['nome'],
    $_SESSION['email'],
    $_SESSION['telefone'],
    $_SESSION['cpf'],
    $_SESSION['senha'],
    $senha,
    $_SESSION['rsenha']);

            $erro[] = "Sua conta foi criada com sucesso. Acesse seu e-mail para validá-la e acessar o painel de controle.";

        */
            
            echo "<script>location.href='index.php?p=carrinho';</script>";
            
        
        //}
    }       

}

?>




        <div class="col-lg-12">
            <h3>Cadastrar Cliente</h3><hr>
            <?php 
                    if(isset($erro) && count($erro) > 0)
                        foreach($erro as $valor)
                            echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
                    ?>
        
        </div>
               
        <div class="col-lg-6">
            <form id="cadastro" name="cadastro" method="POST"  action="">
                        <div class="form-group col-lg-12 text-left">
                            
                        </div>
                        <div class="form-group col-lg-12">
                            <label for="">Nome*</label>
                            <input type="text" required name="nome" value="<?php echo $_SESSION[nome]; ?>" class="input-lg form-control">
                        </div>
                        <!--
                        <div class="form-group col-lg-12">
                            <label for="">E-mail*</label>
                            <input type="email" required name="email" value="<?php echo $_SESSION[email]; ?>" class="input-lg form-control">
                        </div>-->
                        <div class="form-group col-lg-6">
                            <label for="">Telefone*</label>
                            <input type="text" required name="telefone" value="<?php echo $_SESSION[telefone]; ?>" class="telefone input-lg form-control">
                        </div>

                        <div class="form-group col-lg-6">
                            <label for="">Bairro/Região*</label>
                            <input type="text" required name="regiao" value="<?php echo $_SESSION['regiao']; ?>" class="input-lg form-control">
                        </div>

                        <!--
                        <div class="form-group col-lg-6">
                            <label for="">CPF</label>
                            <input type="text"  name="cpf" value="<?php echo $_SESSION[cpf]; ?>" class="input-lg form-control">
                        </div> 
                        <div class="form-group col-lg-6">
                            <label for="">Senha*</label>
                            <input type="password" required name="senha"  class="input-lg form-control">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="">Repita a Senha*</label>
                            <input type="password" required name="rsenha"  class="input-lg form-control">
                        </div>-->

                        <div class="form-group col-lg-6">
                            <p>Tipo de venda:</p>
                            <label for="v1"><input id="v1" name="tipo_venda" value="paga" required type="radio"> Paga</label>
                            <label for="v2"><input id="v2" name="tipo_venda" value="naopaga" required type="radio"> Não paga</label>
                        </div>
                        <div class="form-group col-lg-12">
                            <button type="submit" name="cadastrar" class="btn-lg btn-success btn">Cadastre-se</button>
                          
                        </div>  
    
                </div>

                 
            </form>
        </div>
        
        <div class="clearfix"></div>
