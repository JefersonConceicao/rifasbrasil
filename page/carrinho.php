<?php

include("class/conexao.php");

if(isset($_GET[remover])){

	if(is_array($_SESSION[carrinho][intval($_GET[remover])]))
		foreach($_SESSION[carrinho][intval($_GET[remover])] as $ch=>$va){
			unset($_SESSION[carrinho][intval($_GET[remover])][$ch]);
		}

	unset($_SESSION[carrinho][intval($_GET[remover])]);

}

if(isset($_SESSION[carrinho]) && is_array($_SESSION[carrinho]) && count($_SESSION[carrinho]) > 0){

	foreach($_SESSION[carrinho] as $ch=>$v){
		$sql_add .= " rifa_cod = '$ch' OR";
	}
	$sql_add = substr($sql_add, 0, -2);

	$sql_code = "SELECT rifa_cod, rifa_valorbilhete, rifa_titulo 
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
		$valor_total += (count($_SESSION[carrinho][$rifa_tmp[rifa_cod]]) * $rifa_tmp[rifa_valorbilhete]);

	}while($rifa_tmp = $sql_query->fetch_assoc());



}

?>
<html>
	<head>
		<title></title>
	</head>
	<body>
		<section class="section-default" id="destaque">
      		<div class="col-lg-10 center-block"><strong>Carrinho</strong><hr>
      			<div class="col-lg-12">
      				<?php if(count($_SESSION[carrinho])){ ?>
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
                                                if(is_array($_SESSION[carrinho][$cod])){
            							foreach($_SESSION[carrinho][$cod] as $ch=>$void){ 
            								$r .= "$ch, ";
            								$nBilhetes++;
            							}

      							echo substr($r, 0, -2);

                                                }

      							?>
      						</td>
      						<td>R$ <?php echo number_format(($rPreco[$cod]), 2, ',', '.'); ?></td>
      						<td>R$ <?php echo number_format(($nBilhetes*$rPreco[$cod]), 2, ',', '.'); ?></td>
      						<td class="text-right">
      							<a href="index.php?p=carrinho&remover=<?php echo $cod; ?>"><img width="16" src="img/deletar.png"  alt=""></a>
      						</td>
      					</tr>
      					<?php } ?>
      				</table>
      				<?php }else echo "<p>Carrinho vazio</p>"; ?>

      			</div>
      			<?php if(count($_SESSION[carrinho]) > 0){ ?>
      			<div class="col-lg-12 text-right">
                              <p>Atenção!! O RifasBRASIL apenas divulga as RIFAS. A administração bem como a entrega dos PRÊMIOS é de responsabilidade de seus respectivos PROMOTORES. Mas, voce pode denunciar um PROMOTOR que nao entregou o PRÊMIO, <a href="index.php?p=contato">CLIQUE AQUI</a> E FAÇA SUA DENUNCIA</p>
      				<?php if($valor_total > 0) 
      				echo "<h5>TOTAL R$ ".number_format($valor_total, 2, ',', '.')."</h5>"; ?>
      				<button onclick="javascript: location.href='index.php?p=entrar&next=finalizar_pedido';" class="btn-success btn">Finalizar Compra</button>
      			</div>
      			<?php } ?>
      			
        	</div>
        	<div class="clearfix"></div>
        </section>
	</body>
</html>