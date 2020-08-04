<!-- Conteudo Section -->
<?php
include("class/conexao.php");

if(!$_SESSION) @session_start();

$codigo_da_rifa    = intval($_GET['rifa']);
$codigo_do_bilhete = intval($_GET['bil']);
$sobre_a_rifa      = db_select($mysqli, "SELECT rifa_titulo, rifa_valorbilhete, rifa_maxbilhetes, rifa_dtsorteio, rifa_deletada 
    FROM tbl_rifas 
    WHERE rifa_cod = '$codigo_da_rifa' 
    LIMIT 1", 1);

if($codigo_do_bilhete >= intval($sobre_a_rifa['rifa_maxbilhetes']))
    die("<script>alert('Bilhete ou Rifa inválidos.');</script>");

if($codigo_da_rifa <= 0 OR $codigo_do_bilhete < 0)
    die("<script>alert('Bilhete ou Rifa inválidos.');</script>");

if($sobre_a_rifa['rifa_deletada'])
    die("<script>alert('A rifa já foi deletada.');</script>");

if(time() > strtotime($sobre_a_rifa['rifa_dtsorteio']))
    die("<script>alert('A rifa já venceu.');</script>");

//Login de Usários
if(isset($_POST['btn'])){

    // Captação de dados
    $senha = $mysqli->escape_string($_POST['senha']);
    $email = $mysqli->escape_string($_POST['email']);

    // Validação de dados
    if($email ==  "")
        $erro[] = "Digite seu e-mail";

    if(strlen($senha) < 6 || strlen($senha) > 16)
        $erro[] = "Senha inválida";

    //Consultas ao Banco de dados
    if(count($erro) == 0){

        $sql = "SELECT usu_cod as codigo, usu_admin as admin, usu_senha as senha, usu_validado, usu_grafica 
            FROM tbl_usuario 
            WHERE usu_email = '$email'";
        $que = $mysqli->query($sql) or die(mysql_error());
        $dado = $que->fetch_assoc();

        if($que->num_rows == 0)
            $erro[] = "Usuário não cadastrado";
        elseif(strcmp($dado['senha'], md5($senha)) == 0){

            // verifica se o usuario é revendedor da rifa ou não
            $sql    = "SELECT * FROM tbl_revendedor
                WHERE rifa_cod = '$codigo_da_rifa'
                AND usu_cod = '{$dado['codigo']}'";
            $qr     = $mysqli->query($sql) or die(mysql_error());
            $dados2 = $qr->fetch_assoc();

            if($dados2){
                $_SESSION['usuario'] = $dado['codigo'];
                $_SESSION['admin']   = $dado['admin'];
                $_SESSION['grafica'] = $dado['usu_grafica'];
            }else{
                $erro[] = "Login inválido";
            }

        }else
            $erro[] = "Senha incorreta";

        if(count($erro) != 0){
            foreach($erro as $e) { 
                echo "<script>alert('$e');</script>";
            }
        }

    }


}

if(
    !isset($_SESSION['usuario']) || 
    !$_SESSION['usuario'] || 
    $_SESSION['usuario'] <= 0 || 
    !is_numeric($_SESSION['usuario'])
){
    // precisa se logar

    ?>

    <section class="section-default" id="destaque">
        <div class="col-md-12 col-lg-10 center-block">
            <b>Login</b><hr>
            <div class="col-lg-5 col-md-6">

                <h1>Já nos conhecemos?</h1>
                <p>Bem vindo de volta</p>

                <form action="" method="post">
                    <div class="form-group">
                        <label>E-mail*</label>
                        <input type="text" required name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Senha*</label>
                        <input type="password" required name="senha" class="form-control">
                    </div>
                    <div class="form-group">
                        <p><a target="_blank" href="index.php?p=esqueceu_senha">Esqueceu sua senha?</a></p>
                    </div>   
                    <div class="form-group">
                        <button name="btn" value="true" class="btn btn-success">Entrar</button>
                    </div>
                </form>
            </div>
            <div class="clearfix"></div>
            
        </div>
    </section>

<?php

}else{ 

    // verificar se o qr foi lido pela primeira vez
    $sobre_o_bilhete   = db_select($mysqli, "SELECT q.*, u.usu_nome 
        from qrcode q, tbl_usuario u
        WHERE q.rifa = '$codigo_da_rifa' 
        AND q.bilhete = '$codigo_do_bilhete'
        AND u.usu_cod = q.cliente
        ORDER BY q.rel_codigo ASC", 1);

    if($_POST['vender_liberar']){

        // atualiza o qrcode
        if(!$sobre_o_bilhete){

            $nome = $mysqli->escape_string($_POST['nome']);
            // cadastrar usuario
            $mysqli->query("INSERT INTO tbl_usuario (usu_nome, usu_admin) VALUES('$nome', 'N')");
            $usuario = $mysqli->insert_id;

            $extensao    = end(explode('.', $_FILES["foto"]["name"]));
            $arquivo     = md5(time()) . '.' . $extensao;
            $target_file = 'foto/' . $arquivo;

            $log = "E - " . date('d/m/Y H:i'). "<br>";
            // upload da foto
            // Check if image file is a actual image or fake image
            if(isset($_FILES['foto'])) {
                $check = getimagesize($_FILES["foto"]["tmp_name"]);
                if($check !== false) {
                    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                        // upload realizado
                        // primeiro scan
                        $mysqli->query("INSERT INTO qrcode (bilhete, rifa, count, foto, cliente, data, log) 
                            VALUES('$codigo_do_bilhete', '$codigo_da_rifa', '1', '$arquivo', '$usuario', NOW(), '$log')");
                    } else
                        die("<script>alert('Falha ao enviar a imagem.');</script>");
                    
                } else {
                    die("<script>alert('O arquivo não é uma imagem.');</script>");
                    $uploadOk = 0;
                }
            }else{
                $mysqli->query("INSERT INTO qrcode (bilhete, rifa, count, cliente, data, log) 
                    VALUES('$codigo_do_bilhete', '$codigo_da_rifa', '1', '$usuario', NOW(), '$log')");
            }

            // inserir VENDA
            $sql_compra = "INSERT INTO tbl_compra(
                comp_cliente,
                comp_data,
                comp_valortotal,
                comp_revendedor,
                comp_situacao,
                comp_status_revenda
                ) VALUES(
                '$usuario',
                NOW(),
                '{$sobre_a_rifa['rifa_valorbilhete']}',
                '{$_SESSION['usuario']}',
                '4',
                '1')";
            $mysqli->query($sql_compra) or die(mysql_error());
            $id_compra = $mysqli->insert_id;

            $sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
                    VALUES('$codigo_da_rifa', '$codigo_do_bilhete', 'P', '$id_compra')";
            $mysqli->query($sql_bilhete) or die(mysql_error());

            die("<script>location.href='http://rifasbrasil.com.br/index.php?p=validar_bf&rifa=".$codigo_da_rifa."&bil=".$codigo_do_bilhete."'; </script>");

        }else{

            $count = intval($sobre_o_bilhete['count']) + 1;
            if($count > 2) $count = 1;

            if($count != 1)
                $log = $sobre_o_bilhete['log'] . "S - " . date('d/m/Y H:i'). "<br>";
            else
                $log = $sobre_o_bilhete['log'] . "R - " . date('d/m/Y H:i'). "<br>";

            $mysqli->query("UPDATE qrcode 
                SET count = '$count',
                log = '$log'
                WHERE bilhete = '$codigo_do_bilhete' 
                AND rifa = '$codigo_da_rifa'");

            die("<script>location.href='http://rifasbrasil.com.br/index.php?p=validar_bf&rifa=".$codigo_da_rifa."&bil=".$codigo_do_bilhete."'; </script>");

        }
        

        // realiza a venda

    }



    ?>
    <form action="" method="POST" enctype="multipart/form-data">
            <section class="section-default" id="destaque">
                <div class="col-md-12 col-lg-10 center-block">
                    <b><?= $sobre_a_rifa['rifa_titulo']; ?> <small style="color:#999">#<?= $codigo_da_rifa; ?></small></b><hr>
                    <div class="col-lg-6 col-md-6">
                        <form action="" method="post">
                            <?php if(!$sobre_o_bilhete or intval($sobre_o_bilhete['count']) == 0){ ?>
                            <div class="form-group">
                                <label>Convidado/Cliente*</label>
                                <input type="text" required name="nome" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Convite/Ticket*</label>
                                <p><?= str_pad($codigo_do_bilhete, strlen($sobre_a_rifa['rifa_maxbilhetes'])-1, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Foto</label>
                                <input type="file" name="foto" class="form-control">
                            </div>
                            <?php }elseif($sobre_o_bilhete['count'] == 1){ ?>
                            <div class="form-group">
                                <label for="">Convidado/Cliente*</label>
                                <p><?= $sobre_o_bilhete['usu_nome'] ?></p>
                            </div>
                            <div class="form-group">
                                <label for="">Registro/Atividade*</label>
                                <p><?= $sobre_o_bilhete['log']; ?></p>
                            </div>

                            <div class="form-group">
                                <label for="">Status</label>
                                <p><span style="color:green;">EM USO:</span> <b>Ticket Nº: <?= str_pad($codigo_do_bilhete, strlen($sobre_a_rifa['rifa_maxbilhetes'])-1, '0', STR_PAD_LEFT); ?></b></p>
                            </div>

                            <div class="form-group">
                                <label for="">Foto</label>
                                <p>
                                    <a target="_blank" href="http://www.rifasbrasil.com.br/foto/<?= $sobre_o_bilhete['foto'] ?>">
                                        <img width="200" src="foto/<?= $sobre_o_bilhete['foto'] ?>" alt="">
                                    </a>
                                </p>
                            </div>
                            <?php } elseif($sobre_o_bilhete['count'] == 2) { ?>
                            <div class="form-group">
                                <label for="">Convidado/Cliente*</label>
                                <p><?= $sobre_o_bilhete['usu_nome'] ?></p>
                            </div>
                            <div class="form-group">
                                <label for="">Registro/Atividade*</label>
                                <p><?= $sobre_o_bilhete['log']; ?></p>
                            </div>

                            <div class="form-group">
                                <label for="">Status</label>
                                <p><span style="color:red;">EM PAUSA</span></p>
                            </div>

                            <div class="form-group">
                                <label for="">Foto</label>
                                <p><img width="200" src="foto/<?= $sobre_o_bilhete['foto'] ?>" alt=""></p>
                            </div>
                            <?php }/*elseif($sobre_o_bilhete['count'] > 2) { ?>
                            <div class="form-group">
                                <label for="">Convidado/Cliente*</label>
                                <p><?= $sobre_o_bilhete['usu_nome'] ?></p>
                            </div>
                            <div class="form-group">
                                <label for="">Registro/Atividade*</label>
                                <p><?= date('d/m/Y H:i', strtotime($sobre_o_bilhete['data'])); ?></p>
                            </div>

                            <div class="form-group">
                                <label for="">Status</label>
                                <p>EM USO</p>
                            </div>

                            <div class="form-group">
                                <label for="">Foto</label>
                                <p><img width="200" src="foto/<?= $sobre_o_bilhete['foto'] ?>" alt=""></p>
                            </div>
                            <?php }*/ ?>
                            <div class="form-group">

                                <?php if($sobre_o_bilhete['count'] <= 2 or !$sobre_o_bilhete){ ?>
                                <button name="vender_liberar" value="true" class="btn btn-success"><?php 

                                    if(!$sobre_o_bilhete)
                                        echo "Vender/Liberar";
                                    elseif($sobre_o_bilhete['count'] == 1)
                                        echo "Saída Temporária";
                                    elseif($sobre_o_bilhete['count'] == 2)
                                        echo "Retorno";

                                ?></button>
                                <?php } ?>
                                <?php if($sobre_o_bilhete['count'] == 1){ ?>
                                <p><div class="fb-share-button" data-href="http://rifasbrasil.com.br/foto/<?= $sobre_o_bilhete['foto'] ?>" data-layout="button" data-size="small" data-mobile-iframe="true"><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Frifasbrasil.com.br%2Ffoto%2F<?= $sobre_o_bilhete['foto'] ?>&amp;src=sdkpreparse" class="fb-xfbml-parse-ignore">Compartilhar</a></div></p>
                                <?php } ?>
                            </div>
                        </form>
                    </div>
                    <div class="clearfix"></div>
                    
                </div>
            </section>
    </form>
<?php } ?>