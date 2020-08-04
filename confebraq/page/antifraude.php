<!-- Conteudo Section -->

<?php

include("class/conexao.php");



if(($_POST['btn'])){



    if(strtolower($_POST['alfa']) == substr(md5($_GET['rifa'].$_POST['bilhete']), 0, 4) || strtolower($_POST['alfa']) == substr(md5($_GET['rifa'].intval($_POST['bilhete'])), 0, 4)){



        $rifa = intval($_GET['rifa']);

        $bilhete = intval($_POST['bilhete']);

        $venda = db_select($mysqli, "select usu_nome, usu_celular from tbl_usuario u, tbl_compra c where u.usu_cod = c.comp_cliente and c.comp_cod in (select bil_compra from tbl_bilhetes where bil_numero = '$bilhete' and bil_rifa = '$rifa')", 1);



        echo "<section class=\"section-default\" id=\"destaque\">



    <div class=\"col-md-12 col-lg-10 center-block\"><h3>Bilhete válido!</h3><hr>

    <div class=\"col-lg-6 form-group col-md-6\">

        <p>Nome: ".$venda['usu_nome']."</p>

        <p>Telefone: ".$venda['usu_celular']."</p>

    </div>

    </div></div>";



    }else{



        echo "<section class=\"section-default\" id=\"destaque\">



    <div class=\"col-md-12 col-lg-10 center-block\"><h3>Bilhete inválido!</h3><hr>



    </div></div>";



    }



}else{



?>

<section class="section-default" id="destaque">



    <div class="col-md-12 col-lg-10 center-block">



        <b>Anti Fraude RifasBrasil</b><hr>



        <div class="col-lg-6 col-md-6">

            <form action="" method="post">



                <div class="form-group">

                    <label>Digite o código alfa-numérico:</label>

                    <input type="text" name="alfa" class="form-control">

                </div>



                <div class="form-group">

                    <label>Digite o número do bilhete:</label>

                    <input type="text" name="bilhete" class="form-control">

                </div>



                <div class="form-group">

                    <button name="btn" value="true" class="btn btn-success">Verificar</button>

                </div>



            </form>



        </div>

        <div class="clearfix"></div>



        

    </div>



</section>

<?php  } ?>