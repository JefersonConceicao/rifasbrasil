
<section class="section-default" id="destaque">
      <div class="col-lg-10 center-block">Rifas Abertas<hr>
<?php

include("class/conexao.php");

$sql_rifas = "SELECT * FROM tbl_rifas WHERE (rifa_vencedor IS NULL OR rifa_vencedor = '') AND rifa_finalizar IS NULL AND rifa_dtsorteio > NOW() ORDER BY rifa_dtsorteio ASC";
$sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);
$rifa = $sql_query->fetch_assoc();
do{ ?>

<a class="oferta-link" href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">
  <div style="font-size:0.8em;" class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">
                  
    <div class="col-lg-12 oferta">
      <div class="rifa-imagem">
        <?php

                          $img_rifa = $rifa['rifa_foto1'];
                          if(!is_file($img_rifa))
                            $img_rifa = "http://rifasbrasil.com.br/arquivos/default.jpg";
                          else
                            $img_rifa = "http://rifasbrasil.com.br/" . $img_rifa;

                        ?>
        <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">
      </div>
      <div class="rifa-titulo">
        <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>
      </div>
                      
                      
      <div>
        <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>
      </div>
      <span>vence em <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>
    </div>
                  
  </div>
</a>
<?php } while($rifa = $sql_query->fetch_assoc()); ?>
</div>
<div class="clearfix"></div>
</section>