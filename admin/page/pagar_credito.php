<?php
include("../class/conexao.php");    

$codigo = intval($_GET['transacao']);

//consulta as informações do usuário que vai pagar
$usu_code = "SELECT * FROM tbl_usuario WHERE usu_cod = '$_SESSION[usuario]' ";
$usu_query = $mysqli->query($usu_code) or die($mysqli->error);
$usu = $usu_query->fetch_assoc();

$transacao = db_select($mysqli, "select * from tbl_transacao where codigo = '$codigo' limit 1", 1);


?>
<div class="col-lg-12 text-left">
    <h3>Adicionar Créditos</h3>
    <hr>
    <p>Você irá adicionar o total de R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?> em créditos.</p>
</div>
<div class="col-lg-6">
    <!--
    <h4>Pagamento via Pagseguro</h4>
    -->
	<form method="post" target="pagseguro"  
action="https://pagseguro.uol.com.br/v2/checkout/payment.html">  
          
        <!-- Campos obrigatórios -->  
        <input name="receiverEmail" type="hidden" value="suporte.local@hotmail.com">  
        <input name="currency" type="hidden" value="BRL"> 
  
        <!-- Itens do pagamento (ao menos um item é obrigatório) --> 
        <input name="itemId1" type="hidden" value="TRB_<?php echo $codigo; ?>">  
        <input name="itemDescription1" type="hidden" value="Crédito no RifasBrasil">  
        <input name="itemAmount1" type="hidden" value="<?php echo number_format($transacao['valor'], 2, '.', ''); ?>">  
        <input name="itemQuantity1" type="hidden" value="1">  
        <input name="itemWeight1" type="hidden" value="0"> 
        <input type="hidden" name="itemShippingCost1" value="0.00">

        <!-- Código de referência do pagamento no seu sistema (opcional) -->  
        <input name="reference" type="hidden" value="CRED_<?php echo $codigo; ?>">
          
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
<!--
<div class="col-lg-6">
    <h4>Pagamento via Depósito Bancário</h4>
</div>
-->