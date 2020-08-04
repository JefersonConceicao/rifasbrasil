<?php

include("../class/conexao.php");

if(isset($_POST[add])){

  if(count($_POST[bilhete]) == 0){
    echo "<script>";
    echo "alert('Selecione os bilhetes que deseja adquirir');";
    echo "</script>";
  }else{


  // Limpar Carrinho

      foreach($_SESSION[carrinho_admin] as $ch=>$va){

        foreach($_SESSION[carrinho_admin][$ch] as $chave=>$valor)
          unset($_SESSION[carrinho_admin][$ch][$chave]);

        unset($_SESSION[carrinho_admin][$ch]);

      }



  $_SESSION[revendedor_admin] = intval($_SESSION[usuario]);

  $cod_rifa = intval($_GET['rifa']);

  if(!isset($_SESSION[carrinho_admin]))
    $_SESSION[carrinho_admin] = Array();

  $rifa = "";
  if(is_array($_POST[bilhete])){

    if(!isset($_SESSION[carrinho_admin][$cod_rifa]))
      $_SESSION[carrinho_admin][$cod_rifa] = Array();

    foreach($_POST[bilhete] as $v){
      $_SESSION[carrinho_admin][$cod_rifa][$v] = true;
    }

    echo "<script>";
    echo "location.href='index.php?p=cadastro_cliente';";
    echo "</script>";

  }

  }

}


$codigo = intval($_GET['rifa']);

$sql_rifas = "SELECT * FROM tbl_rifas WHERE rifa_cod = '$codigo' AND rifa_vencedor iS NULL AND rifa_deletada IS NULL";
$sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);
$rifa = $sql_query->fetch_assoc();

// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas
$sqlBil = "SELECT * FROM tbl_bilhetes WHERE bil_rifa = '$codigo' or (bil_rifa = '$codigo' AND bil_situacao = 'C')";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();
$bilVendidos = Array();

if($queryBil->num_rows > 0)
  do{
    $bilVendidos[] = $bil[bil_numero];
  }while($bil = $queryBil->fetch_assoc());


?>
<style>
	/*Pagina Rifa*/
.bilhete-vendido{border:1px solid #999; background-color: #eaeaea; text-align: center;}
.bilhete{border:1px solid #FFFD7A; background-color: #FFFEBA; text-align: center; cursor:pointer;}
.bilhete_selected{border:1px solid #FFFC45; background-color: #FFF04D; }
.comprar_rifa input[type=checkbox] {

cursor: pointer;

display: inline-block;
display: none;
font-size: 14px;
text-align: center;
font-weight: bold;
color: #052;
}
.comprar_rifa input[type=checkbox]:checked {

}
</style>
<div class="col-lg-12 text-left">
	<h3><i class="glyphicon glyphicon-shopping-cart"></i> Vender Bilhetes</h3><hr>
                <p>Selecione as rifas que deseja adquirir. </p>
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12 bg-warning">
		<?php foreach($erro as $msg) echo "<p>$msg</p>"; ?>
		<div class="spacer"></div>
	</div>
	<?php } ?>
</div>
<div class="col-lg-12 text-left">
	<form id="" action="" method="POST" class="comprar_rifa">

                <?php 

                for($i = 0; $i < $rifa[rifa_maxbilhetes]; $i++){
                  if(!in_array($i, $bilVendidos)){
                    $j = $i;
                    $i =   str_pad($i, 3, "0", STR_PAD_LEFT);
                  ?>
                
                  <div style="margin-bottom:10px;" onclick="javascript: checkar('<?php echo $i; ?>');"  class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                    <div id="holder<?php echo $i; ?>" class="col-lg-12 bilhete <?php if($_SESSION['bilhete'.$j] == 1) echo "bilhete_selected"; ?>">
                      <input <?php if($_SESSION['bilhete'.$j] == 1) echo "checked"; ?> value="<?php echo $i; ?>" name="bilhete[]" id="bilhete<?php echo $i; ?>" type="checkbox">
                      <?php echo $i; ?>
                    </div>
                    
                  </div>

                
                <?php }else{ ?>

                <div style="margin-bottom:10px;" class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                    <div  class="col-lg-12 bilhete-vendido">
                      <input type="checkbox">
                      <?php echo $i; ?>

                    </div>
                    
                  </div>

                <?php } } ?>



              <div class="col-lg-12">
                <div class="spacer"></div>
                <button name="add" value="1" class="btn-success btn">Adicionar ao Carrinho</button>
              </div>
            </form>
</div>
<script>
    function checkar(id){
      var campo_id = "bilhete" + id;
      var holder = "holder"+ id;

      if(document.getElementById(campo_id).checked){
        document.getElementById(campo_id).checked = false;
        // exibe / oculta descrições
        $('#'+holder).removeClass('bilhete_selected');
        

      }else{
        $('#'+holder).addClass('bilhete_selected');
        document.getElementById(campo_id).checked = true;
        

      }
    }
    </script>