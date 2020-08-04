<?php

include("class/conexao.php");

$sqleve = "select * from tbl_banner order by rand()";
$qryeve = $mysqli->query($sqleve) or die($mysqli->error);

?>
<div id="carousel-example-generic0" class="carousel slide" data-ride="carousel">
  <!-- Indicators -->
  <ol class="carousel-indicators">
    <?php for($i = 0; $i<$qryeve->num_rows; $i++){ ?>
    <li data-target="#carousel-example-generic0" data-slide-to="<?php echo $i; ?>" <?php if($i ==0) echo ' class="active"'; ?> ></li>
    <?php } ?>
  </ol>

  <!-- Wrapper for slides -->
  <div class="carousel-inner" role="listbox">

    <?php $i = 0;  while ($reseve = $qryeve->fetch_array()){  ?>

    <div class="item <?php if($i == 0) echo "active"; ?>">

      <div style=" background: no-repeat center center fixed; height:<?php echo $img_h; ?>px;   -webkit-background-size: cover;">
        <a href="http://<?php echo str_replace("http://","",strtolower($reseve["ban_link"])) ?>" target="_blank" >
          <img src="http://rifasbrasil.com.br/<?php echo $reseve["ban_imagem"] ?>" width="100%" alt="">
        </a>
      </div>
      
    </div>
    <?php $i++; } ?>
  </div>

  <!-- Controls -->
  <a class="left carousel-control" href="#carousel-example-generic0" role="button" data-slide="prev">
    <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
  </a>
  <a class="right carousel-control" href="#carousel-example-generic0" role="button" data-slide="next">
    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a>
</div>

 
