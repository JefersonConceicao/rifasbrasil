<?php

if (!$_SESSION)
    @session_start();

if (isset($_POST['innercad']))
    $prefix = '../';
else
    $prefix = '';

include($prefix . "class/conexao.php");
include($prefix . "class/function_validarCpf.php");
include($prefix . "class/function_limparNumero.php");


//Variáveis de Base
$dado = explode("/", $_SERVER["REQUEST_URI"]);
$pagina = strtolower($mysqli->escape_string($_GET['next']));

$destino = explode("/", $pagina);
if (strcmp($destino[0], "admin") == 0) {
    $destino = "admin/index.php?p=" . $destino[1];
} else {
    if (strlen($pagina[0]) > 0)
        $destino = "index.php?p=" . $destino[0];
    else
        $destino = "admin/index.php";
}

if ($_GET['buscarrifas'] && $_GET['serie'])
    $destino = 'admin/index.php?p=venda_bolao_dezena&buscarrifas=' . intval($_GET['buscarrifas']) . '&serie=' . $_GET['serie'] . '&bil=' . $_GET['bil'];

//Verifica se já existe um usuário Logado
if ($_GET['rifa'] && $_GET['bil']) {

    $rifa_cod = intval($_GET['rifa']);
    /*$serie = file_get_contents('http://www.rifasbrasil.com.br/servidor/new_server/buscar_bilhetes.php?action=get_serie&rifa=' . intval($_GET['rifa']) . '&bilhete=' . $_GET['bil']);*/

    $sql_venc = "SELECT serie 
        FROM loteamento_rifa_bilhetes 
        WHERE sequencia = '" . implode('-', explode(',', $_GET['bil'])) . "' AND rifa = '$rifa_cod' LIMIT 1";
    $que_venc = $mysqli->query($sql_venc) or die(mysql_error());
    $dado_venc = $que_venc->fetch_assoc();
    $serie = $dado_venc['serie'];

    $sql_venc = "SELECT rifa_dtsorteio FROM tbl_rifas WHERE rifa_cod = '$rifa_cod'";
    $que_venc = $mysqli->query($sql_venc) or die(mysql_error());
    $dado_venc = $que_venc->fetch_assoc();

    if (strtotime($dado_venc['rifa_dtsorteio'] . '23:59:00') < time())
        die("<script>location.href='http://rifasbrasil.com.br/index.php?p=rifa_vencida&rifa=" . $rifa_cod . "&serie=" . $serie . "&bil=" . $_GET['bil'] . "';</script>");

    if (isset($_SESSION['usuario'])) {

        // verifica se a venda ja foi feita
        /*$sql = "SELECT * FROM tbl_compra c, tbl_bilhetes b WHERE b.bil_compra = c.comp_cod AND c.comp_serie = '$serie' AND b.bil_rifa = '$rifa_cod'";
        $que = $mysqli->query($sql) or die(mysql_error());
        $dado = $que->fetch_assoc();
        $ja_vendido = $que->num_rows;
        
        if($ja_vendido > 0)
            die("<script>alert('Esta sequência já foi vendida!');</script>");*/

        $_SESSION['rifa_dezena_bolao'] = true;
        $_SESSION['qr_order'] = true;
        $_SESSION['revendedor_admin'] = $usu_codigo;
        $_SESSION['carrinho_admin'] = array();

        if (!isset($_SESSION['carrinho_admin'][$rifa_cod]))
            $_SESSION['carrinho_admin'][$rifa_cod] = array();

        foreach (explode(',', $_GET['bil']) as $v) {
            $_SESSION['carrinho_admin'][$rifa_cod][] = intval($v);
        }

        die("<script>location.href='" . $destino . "';</script>");
    }
}

//Login de Usários
if (isset($_POST['login'])) {

    $_SESSION['qr_order'] = false;

    // Captação de dados
    $_SESSION['senha'] = $_POST['senha'];
    $_SESSION['email'] = $mysqli->escape_string($_POST['email']);

    // Validação de dados
    if (($_SESSION['email']) ==  "")
        $erro[] = "Digite seu e-mail";

    if (strlen($_SESSION['senha']) < 6 || strlen($_SESSION['senha']) > 16)
        $erro[] = "Senha inválida";

    //Consultas ao Banco de dados
    if (count($erro) == 0) {

        $sql = "SELECT usu_cod as codigo, usu_admin as admin, usu_senha as senha, usu_validado, usu_grafica FROM tbl_usuario WHERE usu_email = '{$_SESSION['email']}'";
        $que = $mysqli->query($sql) or die(mysql_error());
        $dado = $que->fetch_assoc();

        /*if($dado[usu_validado] != 'S')
            $erro[] = "Sua conta ainda não foi validada. Acesse seu e-mail para confirmar sua conta. <a href='index.php?p=entrar&cod=".md5($dado[codigo])."&reenviar=".$_SESSION[email]."'>Clique aqui</a> para reenviar o e-mail de confirmação.";
        else */
        if ($que->num_rows == 0)
            $erro[] = "Usuário não cadastrado";

        elseif (strcmp($dado['senha'], md5($_SESSION['senha'])) == 0) {
            $_SESSION['usuario'] = $dado['codigo'];
            $_SESSION['admin'] = $dado['admin'];
            $_SESSION['grafica'] = $dado['usu_grafica'];
        } else
            $erro[] = "Senha incorreta";

        if (!empty($_POST['surpresinha_rifa']) && !empty($_POST['surpresinha_bilhetes'])) {

            $serie = file_get_contents('http://www.rifasbrasil.com.br/servidor/new_server/buscar_bilhetes.php?action=get_serie&rifa=' . intval($_POST['surpresinha_rifa']) . '&bilhete=' . $_POST['surpresinha_bilhetes']);

            $serie = json_decode($serie, 1);
            $serie = $serie['serie']['grupo'] . "-" . $serie['serie']['bilhete'];

            // verifica se a venda ja foi feita
            $sql = "SELECT * FROM tbl_compra c, tbl_bilhetes b WHERE b.bil_compra = c.comp_cod AND c.comp_serie = '$serie' AND b.bil_rifa = '" . intval($_POST['surpresinha_rifa']) . "'";
            $que = $mysqli->query($sql) or die(mysql_error());
            $dado = $que->fetch_assoc();
            $ja_vendido = $que->num_rows;

            $sql_venc = "SELECT rifa_dtsorteio FROM tbl_rifas WHERE rifa_cod = '" . intval($_POST['surpresinha_rifa']) . "'";
            $que_venc = $mysqli->query($sql_venc) or die(mysql_error());
            $dado_venc = $que_venc->fetch_assoc();

            if (strtotime($dado_venc['rifa_dtsorteio'] . '23:59:00') < time())
                die("<script>location.href='index.php?p=rifa_vencida&rifa=" . intval($_POST['surpresinha_rifa']) . "&serie=" . $serie . "&bil=" . $_GET['bil'] . "';</script>");

            if ($ja_vendido > 0)
                die("<script>alert('Esta sequência já foi vendida!');</script>");

            $_SESSION['qr_order'] = true;
            $_SESSION['rifa_dezena_bolao'] = true;
            $_POST['surpresinha_rifa'] = intval($_POST['surpresinha_rifa']);
            $_SESSION['revendedor_admin'] = $usu_codigo;
            $_SESSION['carrinho_admin'] = array();

            if (!isset($_SESSION['carrinho_admin'][$_POST['surpresinha_rifa']]))
                $_SESSION['carrinho_admin'][$_POST['surpresinha_rifa']] = array();

            foreach (explode(',', $_POST['surpresinha_bilhetes']) as $v) {
                $_SESSION['carrinho_admin'][$_POST['surpresinha_rifa']][] = intval($v);
            }
        }

        if (count($erro) == 0)
            die("<script>location.href='" . $destino . "';</script>");
    }
}

if (isset($_GET['reenviar'])) {
    mail($_GET['reenviar'], "Valide sua conta", "Olá\n\nClique no link para validar sua conta: http://rifasbrasil.com.br/index.php?p=validar&cod=" . $_GET[cod] . "&email=" . $_GET[reenviar] . "\n\nAtenciosamente,\nRifasBRASIL");
}

// Cadastro de Usuários
if (isset($_POST['cadastrar'])) {

    // Captação de dados   
    $_SESSION['nome'] = $mysqli->escape_string($_POST['nome']);
    $_SESSION['email'] = $mysqli->escape_string($_POST['email']);
    $_SESSION['telefone'] = limparNumero($_POST['telefone']);
    $_SESSION['cpf'] = limparNumero($_POST['cpf']);
    $_SESSION['senha'] = $_POST['senha'];
    $senha = $_SESSION['senha'];
    $_SESSION['rsenha'] = $_POST['rsenha'];

    // Validação de dados
    $sql = "SELECT COUNT(*) as NumUsuarios FROM tbl_usuario WHERE usu_email = '$_SESSION[email]'";
    $que = $mysqli->query($sql) or die(mysql_error());
    $dado = $que->fetch_assoc();

    if ($dado['NumUsuarios'] > 0) {

        $erro[] = "E-mail já cadastrado";

        unset($_SESSION['nome'], $_SESSION['email'], $_SESSION['telefone'], $_SESSION['cpf'], $_SESSION['senha'], $_SESSION['rsenha']);

        header('content-type: application/json; charset=utf-8');
        $retorno = array();
        $retorno['sucesso'] = false;
        $retorno['msg'] = $erro;
        die(json_encode($retorno));
    }

    if (($_SESSION['email']) ==  "")
        $erro[] = "Digite seu e-mail";

    if (($_SESSION['nome']) ==  "")
        $erro[] = "Digite seu nome";

    if (strlen($_SESSION['telefone']) < 10)
        $erro[] = "Digite seu telefone";

    if (strlen($_SESSION['senha']) < 6 || strlen($_SESSION['senha']) > 16)
        $erro[] = "A senha deve ter entre 6 e 16 caracteres";

    if ($_SESSION['senha'] != $_SESSION['rsenha'])
        $erro[] = "As senhas não batem";


    // Operações com o Banco de Dados 
    if (count($erro) == 0) {

        $resenha = $_SESSION['senha'];
        $_SESSION['senha'] = md5($_SESSION['senha']);

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
            '{$_SESSION['nome']}',
            '{$_SESSION['email']}',
            '{$_SESSION['telefone']}',
            '{$_SESSION['cpf']}',
            '{$_SESSION['senha']}',
            'N'
            )";

        include($prefix . "class/function_enviarSMS.php");
        //enviarSMS($_SESSION[telefone], "RifasBrasil - Cadastro efetuado! Login: $_SESSION[email] Senha: $presenha", true);

        $confirma_usuario = $mysqli->query($sql_code_usuario) or die($mysqli->error);
        $usu_codigo = $mysqli->insert_id;


        if (count($erro) == 0) {

            /*require_once "classe/mail.send.php";

                        $mensagem = file_get_contents("templates/notificacao-boasvindas.html");
                        $mensagem = str_replace("|**EMAIL**|", $_SESSION[email], $mensagem);
                        $mensagem = str_replace("|**SENHA**|", $senha, $mensagem);
                        $params = array(
                            'to'        => $_SESSION[email],
                            'subject'   => "Talugo",
                            'html'      => $mensagem,
                            'from'      => 'suporte@talugo.com'
                        );

                sendgridMail($params);*/

            mail($_SESSION['email'], "Valide sua conta", "Olá\n\nSua conta foi criada com sucesso. Clique no link para validar sua conta: http://rifasbrasil.com.br/index.php?p=validar&cod=" . md5($usu_cod) . "&email=" . $_SESSION['email'] . "\n\nAtenciosamente,\nRifasBRASIL");






            $erro[] = "Sua conta foi criada com sucesso. Acesse seu e-mail para validá-la e acessar o painel de controle.";

            if ($_POST['innercad']) {

                unset($_SESSION['nome'], $_SESSION['email'], $_SESSION['telefone'], $_SESSION['cpf'], $_SESSION['senha'], $_SESSION['rsenha']);

                header('content-type: application/json; charset=utf-8');
                $retorno = array();
                $retorno['sucesso'] = true;
                $retorno['msg'] = $erro;
                die(json_encode($retorno));
            } else {

                foreach ($_SESSION as $ch => $va) {
                    unset($_SESSION[$ch]);
                }

                $_SESSION['usuario'] = $usu_codigo;
                $_SESSION['admin'] = 'N';

                echo "
                    <script>
                        location.href='" . $destino . "';
                    </script>";
            }
        }
    }
}

?>


<section class="section-default" id="destaque">

    <div class="col-md-12 col-lg-10 center-block">

        <b>Entrar</b>
        <hr>

        <div class="col-lg-12">
            <?php
            if (isset($erro) && count($erro) > 0)
                foreach ($erro as $valor)
                    echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
            ?>

        </div>



        <div class="col-lg-6">
            <form id="login" name="login" method="POST" action="">
                <div class="form-group col-lg-12 text-left">
                    <h1>Já nos conhecemos?</h1>
                    <p>Bem vindo de volta</p>
                </div>

                <input type="hidden" value="<?php echo $_GET['rifa']; ?>" name="surpresinha_rifa">
                <input type="hidden" value="<?php echo $_GET['bil']; ?>" name="surpresinha_bilhetes">

                <div class="form-group col-lg-6 text-left">

                    <label>E-mail*</label>
                    <input value="<?php echo $_SESSION[email]; ?>" type="email" class="input-lg form-control" name="email">

                </div>

                <div class="form-group col-lg-6 text-left">
                    <label>Senha*</label>
                    <input type="password" class="input-lg form-control" name="senha">
                </div>
                <div class="form-group col-lg-12 text-left">
                    <p><a href="index.php?p=esqueceu_senha">Esqueceu sua senha?</a></p>
                </div>
                <div class="form-group col-lg-12 text-left">
                    <input value="Entrar" name="login" type="submit" class="btn btn-success btn-lg">
                </div>
                <div class="spacer"></div>

            </form>
        </div>


        <div class="col-lg-6">

        </div>

        <div class="clearfix"></div>


    </div>

</section>