<?php include("../class/conexao.php"); 

error_reporting(0);

session_start();



if(isset($_GET[cancelar])){



    $cancelar = intval($_GET[cancelar]);



    DBExecute("UPDATE tbl_bilhetes 

        set bil_situacao = 'C' 

        where bil_compra = '$cancelar'", $mysqli);



    DBExecute("UPDATE tbl_compra set comp_deletada = '1', comp_situacao = '7' WHERE comp_cod = '$cancelar'", $mysqli);

    

}



if($_GET['marcarComoPago'] && $_GET['usuario']){



    $comp = intval($_GET['marcarComoPago']);

    $usuario = intval($_GET['usuario']);



    DBExecute("UPDATE tbl_compra set comp_situacao = '', 

        comp_transacao = 'Liberado manualmente', 

        comp_status_revenda = 1 WHERE comp_cod = '$comp'", $mysqli);

    echo "<script>window.open('index.php?p=sucesso_venda&usuario=$usuario&comp=$comp', '_blank');</script>";



}





function firstName($palheiro, $agulha = ' '){



    $retorno = '';

    for($k = 0; $k < strlen($palheiro); $k++){

        

        if($palheiro[$k] == $agulha)

            return $retorno;



        $retorno .= $palheiro[$k];



    }

    return $palheiro;



}



$sql = "select c.*, u.usu_nome, u.usu_celular, u.usu_regiao from tbl_compra c, tbl_usuario u where c.comp_revendedor = '$_SESSION[usuario]' and u.usu_cod = c.comp_cliente";



if($_POST){



    // filtrar por periodo

    if($_POST['dtini']){

        $dtini = implode('-', array_reverse(explode('/', $_POST['dtini'])));

        $sql .= " and date(c.comp_dttransacao) >= '$dtini'";

    }



    if($_POST['dtfim']){

        $dtfim = implode('-', array_reverse(explode('/', $_POST['dtfim'])));

        $sql .= " and date(c.comp_dttransacao) <= '$dtfim'";

    }





    if($_POST['situacao'] == 4)

        $sql .= " and (c.comp_situacao = 3 or c.comp_situacao = 4)";



    if($_POST['situacao'] == 6)

        $sql .= " and (c.comp_situacao = 6 or c.comp_situacao = 7)";



    if($_POST['situacao'] == -1)

        $sql .= " AND (c.comp_situacao = '') ";



    if($_POST['situacao'] == -2)

        $sql .= " AND (c.comp_status_revenda = '0' and c.comp_situacao <> '6' and c.comp_situacao <> '7') ";



    if($_POST['situacao'] == -3)

        $sql .= " AND (c.comp_parcelamento_entrada > 0) ";

    

    if($_POST['por_rifa'])

        $sql .= " and c.comp_cod in (select bil_compra from tbl_bilhetes where bil_rifa = '".intval($_POST['por_rifa'])."') ";



        if($_POST['por_nome']){

            $filtrar_por = $mysqli->real_escape_string($_POST['por_nome']);

            $sql .= " AND (u.usu_nome like '%".$filtrar_por."%' 

                or u.usu_celular like '%".$filtrar_por."%' )";

        }



}



$sqlbil = $sql;

$qrybil = $mysqli->query($sqlbil) or die($mysqli->error);

$resbil = $qrybil->fetch_assoc();



$itens_por_pagina = 200;

$count_itens      = $qrybil->num_rows;

$paginas          = ceil($count_itens/$itens_por_pagina);



$qrybil = $mysqli->query($sqlbil." limit ".(intval($_POST['pagina'])*$itens_por_pagina).", $itens_por_pagina") or die($mysqli->error);

$resbil = $qrybil->fetch_assoc();





$array_rifas = array();

$rifa_adicionada = array();



$sql_code = "select rifa_cod, rifa_titulo from tbl_rifas where rifa_cod in (select bil_rifa from tbl_bilhetes where bil_compra in (select comp_cod from tbl_compra where comp_revendedor = '$_SESSION[usuario]'))";

$sql_query = $mysqli->query($sql_code) or die($mysqli->error);

$res_rifas = $sql_query->fetch_assoc();



do{

    $cod_rifa = $res_rifas['rifa_cod'];

    $rifa = $res_rifas['rifa_titulo'];



    if(!$rifa_adicionada[$cod_rifa] && $cod_rifa){

        $rifa_adicionada[$cod_rifa] = true;

        $array_rifas[] = array('codigo'=>$cod_rifa, 'nome'=>$rifa);

    }

} while($res_rifas = $sql_query->fetch_assoc());

?>

<div class="col-lg-12 text-left">

	<h3>Débitos</h3>

	<hr>

    <form method="POST" action="">

        <div class="col-lg-12">

            <label for="">Filtrar</label>

        </div>

        <div class="col-lg-3 form-group form-inline">

            <p>Período</p>

            <input type="text" value="<?php echo $_POST['dtini']; ?>" name="dtini" class=" datepicker form-control">

            <input type="text" value="<?php echo $_POST['dtfim']; ?>" name="dtfim" class=" datepicker form-control">

        

        </div>



        <div class="col-lg-3 form-group">

            <p>Situação do Pagamento</p>

            <label for="pagamento1"><input type="radio" <?php if($_POST['situacao'] == '') echo "checked=\"checked\""; ?> id="pagamento1" value="" name="situacao" class=""> Todos </label><br>

            <label for="pagamento3"><input type="radio" <?php if($_POST['situacao'] == '4') echo "checked=\"checked\""; ?> id="pagamento3" value="4" name="situacao" class=""> Pago </label><br>

            <label for="pagamento4"><input type="radio" <?php if($_POST['situacao'] == '6') echo "checked=\"checked\""; ?> id="pagamento4" value="6" name="situacao" class=""> Cancelado </label><br>

            <label for="pagamento5"><input type="radio" <?php if($_POST['situacao'] == '-1') echo "checked=\"checked\""; ?> id="pagamento5" value="-1" name="situacao" class=""> Reservado </label><br>

            <label for="pagamento6"><input type="radio" <?php if($_POST['situacao'] == '-2') echo "checked=\"checked\""; ?> id="pagamento6" value="-2" name="situacao" class=""> À Receber </label><br>

            <label for="pagamento7"><input type="radio" <?php if($_POST['situacao'] == '-3') echo "checked=\"checked\""; ?> id="pagamento7" value="-3" name="situacao" class=""> Parcelado </label>

        </div>



        <div class="col-md-3">

            <p>Por Rifa</p>

            <input type="hidden" id="por_rifa" value="<?php echo $_POST['por_rifa']; ?>" name="por_rifa">

            <input type="text" id="rifa" name="rifa_nome" value="<?php echo $_POST['rifa_nome']; ?>" class="form-control">



            <p>Por Nome</p>

            <input type="text" id="por_nome" name="por_nome" value="<?php echo $_POST['por_nome']; ?>" class="form-control">

        </div>



        <div class="col-lg-12 text-center"> <button type="submit" name="ok" value="1" class="btn  btn-success">Filtrar</button>  

            <button type="button" onclick="javascript: location.href='index.php?p=debitor';" class="btn">Limpar Filtro</button>

            <hr></div>



    </form>

</div>



<div class="col-lg-12 text-center">



    <label for="">Páginas</label>

    

    <form action="" method="post">



        <?php foreach($_POST as $k=>$v){

            echo "<input type=\"hidden\" name=\"$k\" value=\"$v\">";

        } ?>



        <?php

        $inicio = intval($_POST['pagina'])-3;

        if($inicio < 0)

            $inicio = 0;



        $final = intval($_POST['pagina'])+3;

        if($final > $paginas)

            $final = $paginas;

        ?>



        <button type="submit" name="pagina" value="0" class="btn btn-default"><<</button>

        <?php for($k = $inicio; $k < ($final); $k++){

            echo '<button type="submit" name="pagina" value="'.$k.'" class="btn btn-default">'.($k+1).'</button>';

        } ?>

        <button type="submit" name="pagina" value="<?= $paginas; ?>" class="btn btn-default">>></button>

    </form>



</div>



<div class="col-lg-12 text-left">

<?php if($qrybil->num_rows == 0) echo "<div class=\"col-lg-12 text-center\">Você não deve nada à nenhum promotor.</div>"; else{ ?>

    <p>Abaixo se encontram as rifas que você revendeu. Você deve efetuar o pagamento das mesmas, ou clicar em PAGAR e enviar o link para o comprador. Se o pagamento não for feito, seu comprador não será considerado no sorteio. Em caso de dúvidas, <a href="../index.php?p=contato">fale conosco.</a></p>

	<div class="col-lg-12">

	<table class="table-responsive table-bordered table">

                    <tr style="font-weight: bold;">

                        <td>ID/Vendas</td>

                        <td>Comprador</td>

                        <td>Rifa/Bilhetes</td>

                        <td>Valor</td>

                        <td>Situação</td>

                        <td></td>

                    </tr>

                    <?php

                    do

                    {



                        $rifa = "";

                        $query_Bil = DBExecute("SELECT * FROM tbl_bilhetes where bil_compra = '$resbil[comp_cod]'", $mysqli);

                        $dados_Bil = $query_Bil->fetch_assoc();



                        $bilhetes = "";

                        $cod_rifa = 0;

                        do{

                            $rifa = DBSelect("select rifa_titulo, dezena_bolao, rifa_cod, rifa_maxbilhetes FROM tbl_rifas where rifa_cod = '$dados_Bil[bil_rifa]' limit 1"

                                ,$mysqli); 



                            $rifa = $rifa['rifa_titulo'];

                            $cod_rifa = $dados_Bil['bil_rifa'];

                            $mb = $rifa['rifa_maxbilhetes'];

                            $dezena_bolao = $rifa['dezena_bolao'];

                            $bilhetes .= str_pad($dados_Bil[bil_numero], strlen($mb)-1, "0", STR_PAD_LEFT).", ";

                        }while($dados_Bil = $query_Bil->fetch_assoc());

                        $bilhetes = substr($bilhetes, 0, -2);

                        

                        ?>

                        <tr <?php if($resbil["comp_deletada"] == 1) echo 'class="bg-danger"'; ?>>

                            <td><?php echo $resbil['comp_cod']; ?></td>

                            <td><?= firstName($resbil['usu_nome']); ?> (tel. <?= $resbil['usu_celular']; ?>)<br><small><b><?= $resbil['usu_regiao']; ?></b></small></td>

                            <td><?php echo "$rifa ($bilhetes)"; ?></td>

                            <td><?php echo "R$ ".number_format($resbil["comp_valortotal"],'2',',','.') ?>

                                 <?php if($resbil['comp_parcelamento_entrada']) echo "<br><span style='color:red'; ?>R$ ".($resbil['comp_valortotal']-$resbil['comp_parcelamento_entrada'])."</span><br><span style='color:#e5bb00;'>".date('d/m/Y', strtotime($resbil['comp_parcelamento_data']))."</span>"; ?>

                            

                            </td>

                            <td>

                            <?php 

                                if ($resbil["comp_situacao"] == 4 || $resbil["comp_situacao"] == 3)

                                {

                                    echo "Paga"; ?>



                                    <button onclick="javascript: window.open('index.php?p=sucesso_venda&usuario=<?= $resbil['comp_cliente']; ?>&comp=<?= $resbil['comp_cod']; ?>', '_blank');" type="button" class="btn btn-warning">Comprovante</button>

                                    <?php 

                                }else if ($resbil["comp_situacao"] == 6 || $resbil["comp_situacao"] ==  7){

                                    echo "Cancelada";

                                }else

                                    echo "Aguardando Pagto.";



                                if($resbil['comp_parcelamento_entrada'])

                                        echo "<br><span style='color:blue;'>(PARCELADO)</span>";

                            

                            ?>

                            </td>

                            <td>

                                 <?php if($resbil["comp_situacao"] != 3 && $resbil["comp_situacao"] != 4  && $resbil["comp_situacao"] != 6  && $resbil["comp_situacao"] != 7){ ?>

                                <?php if($resbil["comp_deletada"] != 1 ){ ?>

                                <button onclick="javascript: if(confirm('Tem certeza?')) location.href='index.php?p=debitor&cancelar=<?php echo $resbil[comp_cod]; ?>';" class="btn">Cancelar Compra</button>

                                <?php  if($resbil["bil_pagamentoconfirmado"] != 1){ ?>

                                <button onclick="javascript: window.open('../index.php?p=pagar&compra=<?php echo $resbil[comp_cod]; ?>', '_blank');" class="btn btn-warning">Pagar Compra</button>

                                <button onclick="javascript: location.href='index.php?p=debitor&marcarComoPago=<?= $resbil['comp_cod']; ?>&usuario=<?= $resbil['comp_cliente']; ?>';" type="button" class="btn btn-success">Marcar como Pago</button>

                                <?php

                                }  } } ?>

                                <?php if($resbil["comp_situacao"] != 3 && $resbil["comp_situacao"] != 4){ ?>

                                <div class="form-group">

                                    <button onclick="location.href='index.php?p=carrinho&carregar_compra=<?php echo $resbil['comp_cod']; ?>';" type="Button" class="btn btn-default">Editar Venda</butotn>

                                </div>

                                <?php }elseif( $dezena_bolao ){ ?> 
                                <div class="form-group">
                                    <button onclick="window.open('page/gerar_relatorio_dezena_bolao.php?venda=<?= $resbil['comp_cod'] ?>&rifa=<?= $cod_rifa; ?>&pontos=-1', '_blank');" class="btn btn-primary">Gerar PDF</button>
                                </div>
                                <?php } ?>

                            </td>

                        </tr>

                        <?php } while ($resbil = $qrybil->fetch_assoc()); ?>

                </table>

            

    </div><?php } ?>

</div>

<script>

    function filtrar(){



        var VAR_RIFAS = <?php echo json_encode($array_rifas); ?>;



        console.log(VAR_RIFAS);



        var options = {

                data: VAR_RIFAS,

                getValue: "nome",

                list: {

                    onSelectItemEvent: function() {

                        $('#por_rifa').val($("#rifa").getSelectedItemData().codigo);

                    },

                    match: {

                        enabled: true

                    }

                },



                theme: "bootstrap"

            };



            $('#rifa').easyAutocomplete(options);



    }



    document.addEventListener("DOMContentLoaded", function(event) { 

      //do work

      filtrar();

    });



</script>