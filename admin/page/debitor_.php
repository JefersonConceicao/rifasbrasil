<?php include("../class/conexao.php"); 

session_start();

if(isset($_GET[cancelar])){

    $cancelar = intval($_GET[cancelar]);
    $contar = DBSelect("SELECT count(*) as c FROM tbl_bilhetes where bil_situacao != 'C' AND bil_compra = (select bil_compra from tbl_bilhetes where bil_cod = '$cancelar')", $mysqli);
    $contar = $contar[c];

    DBExecute("UPDATE tbl_bilhetes 
        set bil_situacao = 'C' 
        where bil_cod = '$cancelar'", $mysqli);

    if($contar == 1){
        DBExecute("UPDATE tbl_compra set comp_deletada = '1' WHERE comp_codigo IN (select bil_compra from tbl_bilhetes where bil_cod = '$cancelar')", $mysqli);
    }
}

if(isset($_GET['confirmar'])){

    $dono = intval($_GET['dono']);
    $dono = DBSelect("SELECT usu_email, usu_nome FROM tbl_usuario WHERE usu_cod = '$dono'", $mysqli);

    $bilhete = intval($_GET['confirmar']);

    DBExecute("UPDATE tbl_bilhetes SET bil_pagamentoconfirmado = 1 WHERE bil_cod = '$bilhete'", $mysqli);
    mail($dono[usu_email], "Pagamento confirmado", "Olá $dono[usu_nome] \n\n O comprador do bilhete $bilhete disse que já realizou o pagamento.\n Verifique sua conta pagseguro ou bancária e realize a liberação do Bilhete pelo link abaixo: \n\nhttp://rifasbrasil.com.br/admin/index.php?p=venda \n\nAtenciosamente,\nRifasBRASIL");

}

$sqlbil = "select * from tbl_bilhetes b, tbl_rifas r where b.bil_compra IN 
    (SELECT comp_cod FROM tbl_compra where comp_revendedor = '$_SESSION[usuario]' AND comp_deletada is NULL)  AND b.bil_situacao != 'C'
    AND b.bil_rifa = r.rifa_cod";
$qrybil = $mysqli->query($sqlbil) or die($mysqli->error);
$resbil = $qrybil->fetch_assoc();


?>
<div class="col-lg-12 text-left">
	<h3>Revendas</h3>
	<hr>
</div>
<div class="col-lg-12 text-left">
<?php if($qrybil->num_rows == 0) echo "<div class=\"col-lg-12 text-center\">Você não deve nada à nenhum promotor.</div>"; else{ ?>
    <p>Abaixo se encontram os bilhetes que você revendeu. Você deve efetuar o pagamento das mesmas, ou clicar em PAGAR e enviar o link para o comprador. Se o pagamento não for feito, seu comprador não será considerado no sorteio. Em caso de dúvidas, <a href="../index.php?p=contato">fale conosco.</a></p>
	<br>
    <div class="col-lg-12">
	<table class="table-responsive table-bordered table">
                    <tr style="font-weight: bold;">
                        <td>Rifa</td>
                        <td>Bilhete</td>
                        <td>Valor</td>
                        <td>Situação</td>
                    </tr>
                    <?php
                    do
                    {
                        ?>
                        <tr>
                            <td><?php echo $resbil["rifa_titulo"] ?></td>
                            <td><?php 
                            $mb = DBSelect("Select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$resbil[rifa_cod]'", $mysqli, "rifa_maxbilhetes");
                
                            echo str_pad($resbil[bil_numero], strlen($mb), "0", STR_PAD_LEFT);
                             ?></td>
                            <td><?php echo "R$ ".number_format($resbil["rifa_valorbilhete"],'2',',','.') ?></td>
                            <td>
                            <?php 
                                if ($resbil["bil_situacao"] == "P")
                                {
                                    echo "Reservado";
                                }
                                else
                                {
                                    if ($resbil["bil_situacao"] == "C")
                                    {
                                        echo "Cancelado";    
                                    }
                                    else
                                    {
                                        if ($resbil["bil_situacao"] == "V")
                                        {
                                            echo "Vendido";    
                                        }    
                                        else
                                        {
                                            echo "Não Encontrado";
                                        }
                                    }    
                                }
                            

                            
                            
                            ?>
                            </td>
                        </tr>
                        <?php } while ($resbil = $qrybil->fetch_assoc()); ?>
                </table>
            
    </div><?php } ?>
</div>
