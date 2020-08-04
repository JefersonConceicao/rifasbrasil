<?php

include("../class/conexao.php");	

$codigo = intval($_GET['rifa']);
	
$config = DBSelect("SELECT par_ccaridade/100 as caridade, par_crb/100 as rifasbrasil FROM tbl_parametro where par_codigo = '1'", $mysqli);

$sql_valor = "SELECT rifa_valorbilhete as v, rifa_instituicao FROM tbl_rifas WHERE rifa_cod = '$codigo'";
$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);
$rifa = $sql_exe->fetch_assoc();

// A consulta abaixo verifica quais rifas já foram vendidas
$sqlBil = "SELECT count(*) as t FROM tbl_bilhetes WHERE bil_rifa = '$codigo'";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();

//consulta as informações do usuário que vai pagar
$usu_code = "SELECT * FROM tbl_usuario WHERE usu_cod = '$_SESSION[usuario]' ";
$usu_query = $mysqli->query($usu_code) or die($mysqli->error);
$usu = $usu_query->fetch_assoc();

$bil_vendidos = $bil[t];
$valor_bilhete = $rifa[v];
$subtotal = $valor_bilhete * $bil_vendidos;
$total = ($subtotal*($config[rifasbrasil]+$config[caridade]));

$tarifaps = ($total*0.05) + 0.5;
$total_tarifas = $total + ($tarifaps);
$tarifar = $subtotal*$config[rifasbrasil];
$tarifac = $subtotal*$config[caridade];

?>
<div class="col-lg-12 text-left">
	<h3>Pagar Tarifas</h3>
	<hr>
	<p>Você pagará o total de R$ <?php echo number_format($total_tarifas, 2, ',', '.'); ?> em tarifas. Sendo que R$ <?php echo number_format($total*0.3, 2, ',', '.'); ?> serão destinados à uma instituição de caridade.</p>
</div>
<div class="col-lg-6">
	<form method="post" target="pagseguro"  
action="https://pagseguro.uol.com.br/v2/checkout/payment.html">  
          
        <!-- Campos obrigatórios -->  
        <input name="receiverEmail" type="hidden" value="suporte.local@hotmail.com">  
        <input name="currency" type="hidden" value="BRL"> 
  
        <!-- Itens do pagamento (ao menos um item é obrigatório) --> 
        <input name="itemId1" type="hidden" value="TRB_<?php echo $codigo; ?>">  
        <input name="itemDescription1" type="hidden" value="Comissão RifasBrasil">  
        <input name="itemAmount1" type="hidden" value="<?php echo number_format($tarifar, 2, '.', ''); ?>">  
        <input name="itemQuantity1" type="hidden" value="1">  
        <input name="itemWeight1" type="hidden" value="0"> 
        <input type="hidden" name="itemShippingCost1" value="0.00">

        <!-- Itens do pagamento (ao menos um item é obrigatório) --> 
        <input name="itemId2" type="hidden" value="TCA_<?php echo $codigo; ?>">  
        <input name="itemDescription2" type="hidden" value="Doação Caridade">  
        <input name="itemAmount2" type="hidden" value="<?php echo number_format($tarifac, 2, '.', ''); ?>">  
        <input name="itemQuantity2" type="hidden" value="1">  
        <input name="itemWeight2" type="hidden" value="0"> 
        <input type="hidden" name="itemShippingCost2" value="0.00">

        <!-- Itens do pagamento (ao menos um item é obrigatório) --> 
        <input name="itemId3" type="hidden" value="TPS_<?php echo $codigo; ?>">  
        <input name="itemDescription3" type="hidden" value="Tarifas Pagseguro">  
        <input name="itemAmount3" type="hidden" value="<?php echo number_format($tarifaps, 2, '.', ''); ?>">  
        <input name="itemQuantity3" type="hidden" value="1">  
        <input name="itemWeight3" type="hidden" value="0"> 
        <input type="hidden" name="itemShippingCost3" value="0.00">
  
        <!-- Código de referência do pagamento no seu sistema (opcional) -->  
        <input name="reference" type="hidden" value="FIN_<?php echo $codigo; ?>">
          
        <!-- Informações de frete (opcionais) -->  
      <input name="shippingType" type="hidden" value="3">  
        
        
        <!-- Dados do comprador (opcionais) -->  
        <input name="senderName" type="hidden" value="<?php echo $usu[usu_nome]; ?>">
        <input name="senderEmail" type="hidden" value="<?php if(strlen($usu[usu_psemail]) > 0) echo $usu[usu_psemail]; else echo $usu[usu_email]; ?>">
                
        <input name="encoding" type="hidden" value="UTF-8">   
        
        <p class="help-block text-danger"></p>
        <p class="help-block text-danger"></p>
        <p class="help-block text-danger"></p>
        <!-- submit do form (obrigatório) -->
        <input class="btn btn-lg btn-insc" alt="Pague com PagSeguro" name="submit"  type="image"  
src="https://p.simg.uol.com.br/out/pagseguro/i/botoes/pagamentos/120x53-pagar.gif"/>
    
</form>
</div>