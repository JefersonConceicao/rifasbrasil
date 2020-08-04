<?php

$inicio = intval($_GET[inicio]);
$fim = intval($_GET[fim]);
$rifa = intval($_GET[rifa]);
$selecionado = $_GET[selecionado];
$maxbilhettes = intval($_GET[maxbilhetes]);

include("../../class/conexao.php");


// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas
$sqlBil = "SELECT * FROM tbl_bilhetes WHERE bil_numero < '$fim' AND bil_numero >= '$inicio' AND bil_rifa = '$rifa' AND (bil_situacao IS NULL OR bil_situacao != 'C')";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();
$bilVendidos = Array();

if($queryBil->num_rows > 0)
  do{
    $bilVendidos[] = $bil[bil_numero];
  }while($bil = $queryBil->fetch_assoc());


for($inicio; $inicio<$fim;$inicio++){

    $numero_bilhete = str_pad($inicio, $maxbilhettes, "0", STR_PAD_LEFT);

    if(!in_array($inicio, $bilVendidos)){

    ?>

  	<div style="margin-bottom:10px;" onclick="javascript: checkar('<?php echo $numero_bilhete; ?>');"  class="col-xs-2 col-sm-2 col-md-2 col-lg-1">
        <div id="holder<?php echo $numero_bilhete; ?>" class="col-lg-12 bilhete <?php if($_SESSION['bilhete'.$j] == 1 || strpos($selecionado, $numero_bilhete.";")  !== false) echo "bilhete_selected"; ?>">
            <input 
            <?php if(strpos($selecionado, $numero_bilhete.";")  !== false) echo "checked=\"checked\";" ?> 

            class="esconder" 

            <?php if($_SESSION['bilhete'.$inicio] == 1) echo "checked"; ?> 

            value="<?php echo $numero_bilhete; ?>" 
            name="bilhete[]" 
            id="bilhete<?php echo $numero_bilhete; ?>" 
            type="checkbox">
            <?php echo $numero_bilhete; ?>
        </div>
    </div>
	
	<?php }else{ ?>

    <div style="margin-bottom:10px;" class="col-xs-2 col-sm-2 col-md-2 col-lg-1">
        <div  class="col-lg-12 bilhete-vendido">
            <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
		</div>
    </div>

<?php
    }

}
?>