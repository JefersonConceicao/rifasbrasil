<!-- Conteudo Section -->
<?php

include("class/conexao.php");
$compra = intval($_GET[compra]);

if($compra > 0){

    $sql_query = "SELECT * FROM tbl_compra c, tbl_usuario u
    WHERE u.usu_cod = c.comp_cliente and c.comp_cod = '$compra' ";
    $qe = $mysqli->query($sql_query) or die($mysqli->error);
    $row = $qe->fetch_assoc();


    /*
    $pagamento = DBSelect("SELECT usu_psemail as email, usu_email FROM tbl_usuario WHERE usu_cod in(
    select rifa_dono FROM tbl_rifas where rifa_cod IN (select bil_rifa from tbl_bilhetes where bil_compra = '$compra' group by bil_compra)
        )", $mysqli);*/

    $sql_rifa = "SELECT * FROM tbl_bilhetes b, tbl_rifas r, tbl_usuario u WHERE u.usu_cod = r.rifa_dono and b.bil_compra = '$compra' AND r.rifa_cod = b.bil_rifa";
    $qrifa = $mysqli->query($sql_rifa) or die($mysqli->error);
    $rifa = $qrifa->fetch_assoc();

?>

    <section class="section-default" id="destaque">
        <div class="col-lg-10 center-block">
        <?php if(strlen($rifa[usu_psemail]) > 0){ ?>
        <div class="col-lg-6 ">
            <div class="col-lg-12">
                 <b>Pagar com Pagseguro</b><hr>
                 <p>Ao pagar com pagseguro, aplicam-se as seguintes tarifas:<br><br> 5% + R$ 0,50 de tarifas administrativas.</p>
            </div>

            <div class="row">
                

                    
<form method="post" target="pagseguro"  
action="https://pagseguro.uol.com.br/v2/checkout/payment.html">  
          
        <!-- Campos obrigatórios -->  
        <input name="receiverEmail" type="hidden" value="<?php echo $rifa[usu_psemail]; ?>">  
        <input name="currency" type="hidden" value="BRL"> 
  
        <!-- Itens do pagamento (ao menos um item é obrigatório) --> 
        <?php $i = 1; $tarifa = 0; do{ 
            
            $tarifa += ($rifa[rifa_valorbilhete]*0.05);
        ?>

        <input name="itemId<?php echo $i; ?>" type="hidden" value="<?php echo $rifa[bil_cod]; ?>">  
        <input name="itemDescription<?php echo $i; ?>" type="hidden" value="Bilhete <?php echo str_pad($rifa[bil_numero], 3, "0", STR_PAD_LEFT); ?>">  
        <input name="itemAmount<?php echo $i; ?>" type="hidden" value="<?php echo number_format($rifa[rifa_valorbilhete], 2, '.', ''); ?>">  
        <input name="itemQuantity<?php echo $i; ?>" type="hidden" value="1">  
        <input name="itemWeight<?php echo $i; ?>" type="hidden" value="0"> 
        <input type="hidden" name="itemShippingCost<?php echo $i; ?>" value="0.00">

        <?php $i++; } while($rifa = $qrifa->fetch_assoc()); $tarifa+= 0.5; ?>

        <input name="itemId<?php echo $i; ?>" type="hidden" value="TAR_<?php echo $compra; ?>">  
        <input name="itemDescription<?php echo $i; ?>" type="hidden" value="Tarifas da Rifa <?php echo $rifa[rifa_cod]; ?>">  
        <input name="itemAmount<?php echo $i; ?>" type="hidden" value="<?php echo number_format($tarifa, 2, '.', ''); ?>">  
        <input name="itemQuantity<?php echo $i; ?>" type="hidden" value="1">  
        <input name="itemWeight<?php echo $i; ?>" type="hidden" value="0"> 
        <input type="hidden" name="itemShippingCost<?php echo $i; ?>" value="0.00">
  
        <!-- Código de referência do pagamento no seu sistema (opcional) -->  
        <input name="reference" type="hidden" value="<?php echo $compra; ?>">
          
        <!-- Informações de frete (opcionais) -->  
      <input name="shippingType" type="hidden" value="3">  
        
        
        <!-- Dados do comprador (opcionais) -->  
        <input name="senderName" type="hidden" value="<?php echo $row[usu_nome]; ?>">
        <input name="senderEmail" type="hidden" value="<?php echo $row[usu_email]; ?>">
                
        <input name="encoding" type="hidden" value="UTF-8">   
        
        <p class="help-block text-danger"></p>
        <p class="help-block text-danger"></p>
        <p class="help-block text-danger"></p>
        <!-- submit do form (obrigatório) -->
        <input class="btn btn-lg btn-insc" alt="Pague com PagSeguro" name="submit"  type="image"  
src="https://p.simg.uol.com.br/out/pagseguro/i/botoes/pagamentos/120x53-pagar.gif"/> 
    
</form>



                
            </div>

        </div><?php } 

        $conta = DBSelect("SELECT * FROM tbl_conta WHERE usuario in(
    select rifa_dono FROM tbl_rifas where rifa_cod IN (select bil_rifa from tbl_bilhetes where bil_compra = '$compra' group by bil_compra)
        )", $mysqli);

        if(strlen($conta[banco]) > 0 && strlen($conta[agencia]) > 0 && strlen($conta[conta]) > 0){ 

    ?>
    <div class="col-lg-6 ">
        <div class="col-lg-10">
                 <b>Pagar com Depósito Bancário</b><hr>
                 <p>Banco: <?php echo ucwords(strtolower($conta[banco])); ?></p>
                 <p>Agência: <?php echo $conta[agencia]; ?></p>
                 <p>Conta: <?php echo $conta[conta]; ?></p>
                 <?php if(strlen($conta[outrasinfos]) > 0){ ?>
                 <p><?php echo ucwords(strtolower($conta[outrasinfos])); ?></p>
                 <?php } ?>
                 <p></p>
                 <p>Ao efetuar o depósito. Envie um e-mail para <strong><?php echo $rifa[usu_email]; ?></strong> com o comprovante.</p>
            </div>

            <div class="row">
            </div>
    </div>
    <?php } ?>
    </div>
    <div class="clearfix"></div>
</section>

<?php } else echo "<script>//location.href='inicial';</script>"; ?>