<?php

include("../class/conexao.php");

if(isset($_GET[remover])){

	if(is_array($_SESSION[carrinho_admin][intval($_GET[remover])]))
		foreach($_SESSION[carrinho_admin][intval($_GET[remover])] as $ch=>$va){
			unset($_SESSION[carrinho_admin][intval($_GET[remover])][$ch]);
		}

	unset($_SESSION[carrinho_admin][intval($_GET[remover])]);

}

if(isset($_SESSION[carrinho_admin]) && is_array($_SESSION[carrinho_admin]) && count($_SESSION[carrinho_admin]) > 0){

	foreach($_SESSION[carrinho_admin] as $ch=>$v){
		$sql_add .= " rifa_cod = '$ch' OR";
	}
	$sql_add = substr($sql_add, 0, -2);

	$sql_code = "SELECT rifa_cod, rifa_valorbilhete, rifa_titulo, rifa_maxbilhetes 
	FROM tbl_rifas 
	WHERE ({$sql_add}) 
	AND (rifa_vencedor IS NULL OR rifa_vencedor = '')";

	$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
	$rifa_tmp = $sql_query->fetch_assoc();

	$rifa = Array();
	$valor_total = 0;



	do{

		$rifa[] = $rifa_tmp[rifa_cod];
		$rTitulo[$rifa_tmp[rifa_cod]] = $rifa_tmp[rifa_titulo];
		$rPreco[$rifa_tmp[rifa_cod]] = $rifa_tmp[rifa_valorbilhete];
		$valor_total += (count($_SESSION[carrinho_admin][$rifa_tmp[rifa_cod]]) * $rifa_tmp[rifa_valorbilhete]);
            $nmax[$rifa_tmp[rifa_cod]]= $rifa_tmp[rifa_maxbilhetes];
	}while($rifa_tmp = $sql_query->fetch_assoc());



}

$cli = DBExecute("SELECT * FROM tbl_usuario where usu_cod = '$_SESSION[usuario_admin]'", $mysqli);
$cliente = $cli->fetch_assoc();
?>
<div class="col-lg-8">
      		<h3>Carrinho</h3><hr>
      			<div class="col-lg-12">
      				<?php if(count($_SESSION[carrinho_admin])){ ?>
      				<table class="table table-responsive table-bordered table-stripped">
      					<tr style="font-weight:bold;">
      						<td width="50%">Rifa</td>
      						<td>Bilhetes</td>
      						<td>Subtotal</td>
      						<td>Total</td>
      						<td></td>
      					</tr>

      					<?php 
                                    if(is_array($rifa))
                                          foreach($rifa as $cod){ ?>
      					<tr>
      						<td><?php echo $rTitulo[$cod]; ?></td>
      						<td>
      							<?php  
      							$nBilhetes = 0;
      							$r = "";
                                                if(is_array($_SESSION[carrinho_admin][$cod])){
            							foreach($_SESSION[carrinho_admin][$cod] as $ch=>$void){ 
            								$r .= str_pad($ch, strlen($nmax[$cod])-1, "0", STR_PAD_LEFT).", ";
            								$nBilhetes++;
            							}

      							echo substr($r, 0, -2);

                                                }

      							?>
      						</td>
      						<td>R$ <?php echo number_format(($rPreco[$cod]), 2, ',', '.'); ?></td>
      						<td>R$ <?php echo number_format(($nBilhetes*$rPreco[$cod]), 2, ',', '.'); ?></td>
      						<td class="text-right">
      							<a href="index.php?p=carrinho&remover=<?php echo $cod; ?>"><img width="16" src="../img/deletar.png"  alt=""></a>
      						</td>
      					</tr>
      					<?php } ?>
      				</table>
      				<?php }else echo "<p>Carrinho vazio</p>"; ?>

      			</div>
      			
      			
        	</div>

<div class="col-lg-4">
      <h3>Comprador</h3><hr>
      <div class="col-lg-12 form-group">
            <label for="">Nome</label>
            <p><?php echo $_SESSION[nome]; ?></p>            
      </div>
      <div class="col-lg-6 form-group">
            <label for="">Telefone</label>
            <p><?php echo $_SESSION[telefone]; ?></p>            
      </div>
      <div class="col-lg-6 form-group">
            <label for="">CPF</label>
            <p><?php echo $_SESSION[cpf]; ?></p>            
      </div>
</div>
<?php if(count($_SESSION[carrinho_admin]) > 0){ ?>
                        <div class="col-lg-12 text-center">
                              <?php if($valor_total > 0) 
                              echo "<h4>TOTAL R$ ".number_format($valor_total, 2, ',', '.')."</h4>"; ?>
                              <button onclick="javascript: location.href='index.php?p=finalizar_pedido';" class="btn-success btn">Finalizar Compra</button>
                        </div>
                        <?php } ?>
