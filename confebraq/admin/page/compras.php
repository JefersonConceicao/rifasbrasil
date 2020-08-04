<?php include("../class/conexao.php"); 

session_start();

if(isset($_GET[cancelar])){

    $cancelar = intval($_GET[cancelar]);

    DBExecute("UPDATE tbl_bilhetes 
        set bil_situacao = 'C' 
        where comp_cod = '$cancelar'", $mysqli);

    if($contar == 1){
        DBExecute("UPDATE tbl_compra set comp_deletada = '1' WHERE comp_codigo = '$cancelar'", $mysqli);
    }
}

if(isset($_GET['confirmar'])){

    $dono = intval($_GET['confirmar']);
    $dono = DBSelect("SELECT usu_email, usu_nome FROM tbl_usuario WHERE usu_cod in (select rifa_dono from tbl_rifas where rifa_cod in(
        select bil_rifa FROM tbl_bilhetes where bil_compra = '$dono'))", $mysqli);

    $bilhete = intval($_GET['confirmar']);

    DBExecute("UPDATE tbl_bilhetes SET bil_pagamentoconfirmado = 1 WHERE bil_compra = '$bilhete'", $mysqli);
    mail($dono[usu_email], "Pagamento confirmado", "Olá $dono[usu_nome] \n\n O comprador do bilhete $bilhete disse que já realizou o pagamento.\n Verifique sua conta pagseguro ou bancária e realize a liberação do Bilhete pelo link abaixo: \n\nhttp://rifasbrasil.com.br/confebraq/admin/index.php?p=venda \n\nAtenciosamente,\nRifasBRASIL");

    echo "<script>alert('Avisamos ao vendedor que você efetuou o pagamento. Aguarde o retorno do mesmo!');</script>";

}

$sqlbil = "
select * 
FROM tbl_compra 
where comp_cliente = '$_SESSION[usuario]' 
AND comp_deletada is NULL 
order by comp_cod DESC
";

$qrybil = $mysqli->query($sqlbil) or die($mysqli->error);
$resbil = $qrybil->fetch_assoc();


?>
<div class="col-lg-12 text-left">
	<h3>Compras</h3>
	<hr>
</div>
<div class="col-lg-12 text-left">
<?php 
if($qrybil->num_rows == 0) 
    echo "<div class=\"col-lg-12 text-center\">Nenhuma compra foi feita</div>"; 
else{ 

?>
    <p>Estes são os bilhetes que você comprou. Em caso de dúvidas, <a href="../index.php?p=contato">fale conosco.</a></p>
	<div class="col-lg-12">
	<table class="table-responsive table-bordered table">
                    <tr style="font-weight: bold;">
                        <td>Rifa</td>
                        <td>Bilhete</td>
                        <td>Valor</td>
                        <td>Situação</td>
                        <td>Promotor</td>
                        <td></td>
                    </tr>
                    <?php
                    do
                    {

                    $bilhetes = "";
                    $bil_vend = DBExecute("select * from tbl_bilhetes b, tbl_rifas r where r.rifa_cod = b.bil_rifa AND b.bil_compra = '$resbil[comp_cod]'", $mysqli);
                    $bil_v = $bil_vend->fetch_assoc();

                    $rifa = "";

                    do{
                        $rifa = $bil_v[rifa_titulo];
                        $bilhetes .= str_pad($bil_v[bil_numero], strlen($bil_v[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT).", ";
                    } while( $bil_v = $bil_vend->fetch_assoc());                    
                    $bilhetes = substr($bilhetes, 0, -2);

                        ?>
                        <tr>
                            <td><?php echo $rifa; ?></td>
                            <td><?php echo $bilhetes; ?></td>
                            <td><?php echo "R$ ".number_format($resbil[comp_valortotal],'2',',','.') ?></td>
                            <td>
                            <?php 
                                 if ($resbil["comp_situacao"] == "")
                                {
                                    echo "Reservado";
                                }
                                else
                                {
                                    if ($resbil["comp_situacao"] ==  6 || $resbil["comp_situacao"] == 7)
                                    {
                                        echo "Cancelado";    
                                    }
                                    else
                                    {
                                        if ($resbil["comp_situacao"] ==  3 || $resbil["comp_situacao"] == 4)
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
                            <td>
                            <?php
                            $sql_usuario = "SELECT usu_email, usu_nome, usu_celular FROM tbl_usuario WHERE usu_cod in (select rifa_dono from tbl_rifas where rifa_cod in(
        select bil_rifa FROM tbl_bilhetes where bil_compra = '$resbil[comp_cod]'))  ";
                            $usu_q = $mysqli->query($sql_usuario);
                            $usuario = $usu_q->fetch_assoc();

                            echo "$usuario[usu_nome]<br>$usuario[usu_email]<br>$usuario[usu_celular]";
                            ?>
                            </td>
                            <td>

                                <?php  if($resbil["comp_situacao"] == '' || ($resbil["comp_situacao"])==4 ){ ?>
                                <button onclick="javascript: if(confirm('Tem certeza?')) location.href='index.php?p=compras&cancelar=<?php echo $resbil[comp_cod]; ?>';" class="btn">Cancelar Compra</button>
                                <button onclick="javascript: if(confirm('Tem certeza?')) location.href='../index.php?p=pagar&compra=<?php echo $resbil[comp_cod]; ?>';" class="btn btn-warning">Pagar</button>
                                <button onclick="javascript: location.href='index.php?p=compras&confirmar=<?php echo $resbil[comp_cod]; ?>';" class="btn btn-success">Confirmar Pagamento</button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } while ($resbil = $qrybil->fetch_assoc()); ?>
                </table>
            
    </div><?php } ?>
</div>
