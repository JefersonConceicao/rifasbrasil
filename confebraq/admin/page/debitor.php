<?php include("../class/conexao.php"); 

session_start();

if(isset($_GET[cancelar])){

    $cancelar = intval($_GET[cancelar]);

    DBExecute("UPDATE tbl_bilhetes 
        set bil_situacao = 'C' 
        where bil_compra = '$cancelar'", $mysqli);

    DBExecute("UPDATE tbl_compra set comp_deletada = '1', comp_situacao = '7' WHERE comp_cod = '$cancelar'", $mysqli);
    
}


$sqlbil = "select * from  tbl_compra where comp_revendedor = '$_SESSION[usuario]'";
$qrybil = $mysqli->query($sqlbil) or die($mysqli->error);
$resbil = $qrybil->fetch_assoc();


?>
<div class="col-lg-12 text-left">
	<h3>Débitos</h3>
	<hr>
</div>
<div class="col-lg-12 text-left">
<?php if($qrybil->num_rows == 0) echo "<div class=\"col-lg-12 text-center\">Você não deve nada à nenhum promotor.</div>"; else{ ?>
    <p>Abaixo se encontram as rifas que você revendeu. Você deve efetuar o pagamento das mesmas, ou clicar em PAGAR e enviar o link para o comprador. Se o pagamento não for feito, seu comprador não será considerado no sorteio. Em caso de dúvidas, <a href="../index.php?p=contato">fale conosco.</a></p>
	<div class="col-lg-12">
	<table class="table-responsive table-bordered table">
                    <tr style="font-weight: bold;">
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
                        do{
                            $rifa = DBSelect("select rifa_titulo FROM tbl_rifas where rifa_cod = '$dados_Bil[bil_rifa]'"
                                ,$mysqli, "rifa_titulo"); 
                            $mb = DBSelect("Select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$dados_Bil[bil_rifa]'", $mysqli, "rifa_maxbilhetes");
                          
                            $bilhetes .= str_pad($dados_Bil[bil_numero], strlen($mb)-1, "0", STR_PAD_LEFT).", ";
                        }while($dados_Bil = $query_Bil->fetch_assoc());
                        $bilhetes = substr($bilhetes, 0, -2);
                        ?>
                        <tr <?php if($resbil["comp_deletada"] == 1) echo 'class="bg-danger"'; ?>>
                            <td><?php echo "$rifa ($bilhetes)"; ?></td>
                            <td><?php echo "R$ ".number_format($resbil["comp_valortotal"],'2',',','.') ?></td>
                            <td>
                            <?php 
                                if ($resbil["comp_situacao"] == 4 || $resbil["comp_situacao"] == 3)
                                {
                                    echo "Paga";
                                }else if ($resbil["comp_situacao"] == 6 || $resbil["comp_situacao"] ==  7){
                                    echo "Cancelada";
                                }else
                                    echo "Aguardando Pagto.";
                            
                            ?>
                            </td>
                            <td>
                                 <?php if($resbil["comp_situacao"] != 3 && $resbil["comp_situacao"] != 4  && $resbil["comp_situacao"] != 6  && $resbil["comp_situacao"] != 7){ ?>
                                <?php if($resbil["comp_deletada"] != 1 ){ ?>
                                <button onclick="javascript: if(confirm('Tem certeza?')) location.href='index.php?p=debitor&cancelar=<?php echo $resbil[comp_cod]; ?>';" class="btn">Cancelar Compra</button>
                                <?php  if($resbil["bil_pagamentoconfirmado"] != 1){ ?>
                               
                                <button onclick="javascript: window.open('../index.php?p=pagar&compra=<?php echo $resbil[comp_cod]; ?>', '_blank');" class="btn btn-warning">Pagar Compra</button>
                                <?php
                                } ?><?php } } ?>
                            </td>
                        </tr>
                        <?php } while ($resbil = $qrybil->fetch_assoc()); ?>
                </table>
            
    </div><?php } ?>
</div>
