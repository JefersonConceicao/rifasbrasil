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

          <img src="http://rifasbrasil.com.br/confebraq/<?php echo $reseve["ban_imagem"] ?>" width="100%" alt="">

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



 <!-- Rifas em DESTAQUE -->



  <?php



  // Where para rifas em destaque

  $where_destaque = " WHERE rifa_deletada is null and rifa_finalizar is NULL AND rifa_destaque = '1' and rifa_dtsorteio > NOW() order by rifa_dtsorteio ASC ";



  $total = DBSelect("SELECT count(*) as c FROM tbl_rifas $where_destaque", $mysqli);



  if($total['c'] > 0){



    $sql_rifas = "SELECT * FROM tbl_rifas $where_destaque LIMIT 6";

    $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

    $rifa = $sql_query->fetch_assoc();





    ?>

    <section class="section-default" id="destaque">

      <div class="col-lg-10 center-block">Rifas em DESTAQUE <hr>

        <div class="row">



          <div id="carousel-example-generic4" class="carousel slide" data-ride="carousel">



            <!-- Wrapper for slides -->

            <div class="carousel-inner" role="listbox">

              <div class="item active">

                <?php do{



               ?>

                <!-- Rifas -->



                <div style="font-size:0.8em;" class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">

                  <a href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                    <div class="col-lg-12 oferta" >

                      <div class="rifa-imagem">

                        <?php



                          $img_rifa = $rifa['rifa_foto1'];

                          if(!is_file($img_rifa))

                            $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                          else

                            $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                        ?>

                        <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                      </div>

                      <div class="rifa-titulo">

                        <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                      </div>





                      <div>

                        <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                      </div>

                      <span>sorteio dia <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                    </div>

                  </a>

                  

                </div>

                

                <!-- Fim Rifa -->

                <?php  } while($rifa = $sql_query->fetch_assoc());



                ?>

              </div>

              <?php if($total['c'] > 6){?>

              <div class="item">

                <?php

                $sql_rifas = "SELECT * FROM tbl_rifas $where_destaque LIMIT 6, 7";

                $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

                $rifa = $sql_query->fetch_assoc();



                do{ 



                  if($rifa['rifa_cod']){ ?>

                <!-- Rifas -->

                <div style="font-size:0.8em; " class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">

                  <a href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                    <div style="margin-bottom:15px;W" class="col-lg-12 oferta">

                      <div class="rifa-imagem">

                        <?php



                          $img_rifa = $rifa['rifa_foto1'];

                          if(!is_file($img_rifa))

                            $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                          else

                            $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                        ?>

                        <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                      </div>

                      <div class="rifa-titulo">

                        <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                      </div>





                      <div>

                        <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                      </div>

                      <span>sorteio dia <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                    </div></a>



                  </div>

                  <!-- Fim Rifa -->

                  <?php } } while($rifa = $sql_query->fetch_assoc());







                  ?>

                </div><?php } ?>

              </div>



              <?php if($total[c] > 6){?>

              <!-- Controls -->

              <a class="left carousel-control" href="#carousel-example-generic4" role="button" data-slide="prev">

                <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>

                <span class="sr-only">Previous</span>

              </a>

              <a class="right carousel-control" href="#carousel-example-generic4" role="button" data-slide="next">

                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>

                <span class="sr-only">Next</span>

              </a>

              <?php } ?>

            </div>











          </div>

        </div>



      </section><?php } ?>





      <!-- FIM -->









      <!-- RIFAS EM ANDAMENTO -->





      <?php

      $where_andamento = "WHERE rifa_deletada is null and (rifa_vencedor IS NULL OR rifa_vencedor = '') AND rifa_finalizar IS NULL and rifa_dtsorteio > NOW() order by rifa_dtsorteio ASC";



      $total = DBSelect("SELECT count(*) as c FROM tbl_rifas $where_andamento", $mysqli);



      if($total[c] > 0){

        $sql_rifas = "SELECT * FROM tbl_rifas $where_andamento LIMIT 6";

        $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

        $rifa = $sql_query->fetch_assoc();







        ?>

        <section class="section-default" id="destaque">

          <div class="col-lg-10 center-block">Rifas em Andamento <hr>

            <div class="row">



              <div id="carousel-example-generic2" class="carousel slide" data-ride="carousel">



                <!-- Wrapper for slides -->

                <div class="carousel-inner" role="listbox">

                  <div class="item active">

                    <?php do{ ?>

                    <!-- Rifas -->

                    <a class="oferta-link" href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                      <div style="font-size:0.8em;" class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">



                        <div class="col-lg-12 oferta">

                          <div class="rifa-imagem">

                            <?php



                              $img_rifa = $rifa['rifa_foto1'];

                              if(!is_file($img_rifa))

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                              else

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                            ?>

                            <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                          </div>

                          <div class="rifa-titulo">

                            <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                          </div>





                          <div>

                            <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                          </div>

                          <span>sorteio dia <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                        </div>



                      </div>

                    </a>

                    <!-- Fim Rifa -->

                    <?php } while($rifa = $sql_query->fetch_assoc());



                    ?>

                  </div>

                  <?php if($total[c] > 6){ ?>

                  <div class="item">

                    <?php

                    $sql_rifas = "SELECT * FROM tbl_rifas $where_andamento LIMIT 6, 6";

                    $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

                    $rifa = $sql_query->fetch_assoc();

                    ?>

                    <?php 



                    do{ ?>

                    <!-- Rifas -->

                    <div style="font-size:0.8em; " class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">

                      <a href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                        <div style="margin-bottom:15px;" class="col-lg-12 oferta">

                          <div class="rifa-imagem">

                            <?php



                              $img_rifa = $rifa['rifa_foto1'];

                              if(!is_file($img_rifa))

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                              else

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                            ?>

                            <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                          </div>

                          <div class="rifa-titulo">

                            <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                          </div>





                          <div>

                            <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                          </div>

                          <span>sorteio dia <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                        </div>

                      </a>

                    </div>

                    <!-- Fim Rifa -->

                    <?php } while($rifa = $sql_query->fetch_assoc());







                    ?>

                  </div><?php } ?>

                </div>



                <?php if($total[c] > 6){?>

                <!-- Controls -->

                <a class="left carousel-control" href="#carousel-example-generic2" role="button" data-slide="prev">

                  <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>

                  <span class="sr-only">Previous</span>

                </a>

                <a class="right carousel-control" href="#carousel-example-generic2" role="button" data-slide="next">

                  <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>

                  <span class="sr-only">Next</span>

                </a>

                <?php } ?>



              </div>











            </div>

          </div>



        </section><?php } ?>



        <!-- FIM RIFAS EM ANDAMENTO -->





      

<!-- RIFAS RECENTES -->





<?php

$total = DBSelect("SELECT count(*) as c FROM tbl_rifas WHERE rifa_deletada is null and rifa_vencedor IS NULL and rifa_finalizar is null and rifa_dtsorteio > now() and rifa_dtcriacao is not null and rifa_dtcriacao >= '".date("Y-m-d", time()-(30*86400))."'", $mysqli);

$sql_rifas = "SELECT * FROM tbl_rifas WHERE rifa_deletada is null and rifa_vencedor IS NULL and rifa_finalizar is null and rifa_dtsorteio > now() and rifa_dtcriacao is not null and rifa_dtcriacao >= '".date("Y-m-d", time()-(30*86400))."'  LIMIT 6";

$sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

$rifa = $sql_query->fetch_assoc();



if($total[c] > 0){

  ?>

  <section class="section-default" id="destaque">

          <div class="col-lg-10 center-block">Rifas RECENTES <hr>

            <div class="row">



              <div id="carousel-example-generic2" class="carousel slide" data-ride="carousel">



                <!-- Wrapper for slides -->

                <div class="carousel-inner" role="listbox">

                  <div class="item active">

                    <?php do{ ?>

                    <!-- Rifas -->

                    <a class="oferta-link" href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                      <div style="font-size:0.8em;" class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">



                        <div class="col-lg-12 oferta">

                          <div class="rifa-imagem">

                            <?php



                              $img_rifa = $rifa['rifa_foto1'];

                              if(!is_file($img_rifa))

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                              else

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                            ?>

                            <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                          </div>

                          <div class="rifa-titulo">

                            <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                          </div>





                          <div>

                            <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                          </div>

                          <span>sorteio dia <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                        </div>



                      </div>

                    </a>

                    <!-- Fim Rifa -->

                    <?php } while($rifa = $sql_query->fetch_assoc());



                    ?>

                  </div>

                  <?php if($total[c] > 6){ ?>

                  <div class="item">

                    <?php

                    $sql_rifas = "SELECT * FROM tbl_rifas WHERE rifa_deletada is null and (rifa_vencedor IS NULL OR rifa_vencedor = '') AND rifa_finalizar IS NULL order by rifa_dtsorteio DESC LIMIT 12, 6";

                    $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

                    $rifa = $sql_query->fetch_assoc();

                    ?>

                    <?php 



                    do{ ?>

                    <!-- Rifas -->

                    <div style="font-size:0.8em; " class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">

                      <a href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                        <div style="margin-bottom:15px;" class="col-lg-12 oferta">

                          <div class="rifa-imagem">

                            <?php



                              $img_rifa = $rifa['rifa_foto1'];

                              if(!is_file($img_rifa))

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                              else

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                            ?>

                            <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                          </div>

                          <div class="rifa-titulo">

                            <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                          </div>





                          <div>

                            <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                          </div>

                          <span>sorteio dia <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                        </div>

                      </a>

                    </div>

                    <!-- Fim Rifa -->

                    <?php } while($rifa = $sql_query->fetch_assoc());







                    ?>

                  </div><?php } ?>

                </div>



                <?php if($total[c] > 6){?>

                <!-- Controls -->

                <a class="left carousel-control" href="#carousel-example-generic2" role="button" data-slide="prev">

                  <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>

                  <span class="sr-only">Previous</span>

                </a>

                <a class="right carousel-control" href="#carousel-example-generic2" role="button" data-slide="next">

                  <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>

                  <span class="sr-only">Next</span>

                </a>

                <?php } ?>



              </div>











            </div>

          </div>



        </section><?php } ?>



  <!-- FIM RIFAS RECENTES -->





 





        <!-- RIFAS FINALIZADAS -->





        <?php

        $where_finalizada = "WHERE rifa_deletada is null and rifa_finalizar = 2 and rifa_dtsorteio < now() order by rifa_dtsorteio DESC";

        $total = DBSelect("SELECT count(*) as c FROM tbl_rifas $where_finalizada", $mysqli);



        if($total[c] > 0){



          $sql_rifas = "SELECT * FROM tbl_rifas $where_finalizada LIMIT 6";

          $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

          $rifa = $sql_query->fetch_assoc();





          ?>

          <section class="section-default" id="destaque">

            <div class="col-lg-10 center-block">Rifas Sorteadas <hr>

              <div class="row">



                <div id="carousel-example-generic3" class="carousel slide" data-ride="carousel">



                  <!-- Wrapper for slides -->

                  <div class="carousel-inner" role="listbox">

                    <div class="item active">

                      <?php do{ ?>

                      <!-- Rifas -->



                      <div style="font-size:0.8em;" class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">

                        <a href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                        <div class="col-lg-12 oferta" style="background-color:#FFC23C;">

                          <div class="rifa-imagem">

                            <?php



                              $img_rifa = $rifa['rifa_foto1'];

                              if(!is_file($img_rifa))

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                              else

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                            ?>

                            <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                          </div>

                          <div class="rifa-titulo">

                            <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                          </div>





                          <div>

                            <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                          </div>

                          <span>sorteada em <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                        </div>

                        </a>



                      </div>



                      <!-- Fim Rifa -->

                      <?php } while($rifa = $sql_query->fetch_assoc());



                      ?>

                    </div>

                    <?php if($total[c] > 6){ ?>

                    <div class="item">

                      <?php

                      $sql_rifas = "SELECT * FROM tbl_rifas $where_finalizada LIMIT 6, 6";

                      $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

                      $rifa = $sql_query->fetch_assoc();

                      ?>

                      <?php 



                      do{ ?>

                      <!-- Rifas -->

                      <div style="font-size:0.8em; " class="col-xs-12 col-sm-6 col-md-4 col-lg-2 ">

                        <a href="index.php?p=rifa&codigo=<?php echo $rifa[rifa_cod]; ?>">

                        <div style="margin-bottom:15px;" class="col-lg-12 oferta" style="background-color:#FFC23C;">

                          <div class="rifa-imagem">

                            <?php



                              $img_rifa = $rifa['rifa_foto1'];

                              if(!is_file($img_rifa))

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/arquivos/default.jpg";

                              else

                                $img_rifa = "http://rifasbrasil.com.br/confebraq/" . $img_rifa;



                            ?>

                            <img src="<?php echo $img_rifa; ?>" class="img-responsive" alt="">

                          </div>

                          <div class="rifa-titulo">

                            <h5><?php echo ucwords(strtolower($rifa[rifa_titulo])); ?></h5>

                          </div>





                          <div>

                            <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete], 2, ',', '.'); ?></h2>

                          </div>

                          <span>sorteada em <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></span>

                        </div>

                        </a>



                      </div>

                      <!-- Fim Rifa -->

                      <?php } while($rifa = $sql_query->fetch_assoc());







                      ?>

                    </div><?php } ?>

                  </div>\



                  <?php if($total[c] > 6){?>

                  <!-- Controls -->

                  <a class="left carousel-control" href="#carousel-example-generic3" role="button" data-slide="prev">

                    <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>

                    <span class="sr-only">Previous</span>

                  </a>

                  <a class="right carousel-control" href="#carousel-example-generic3" role="button" data-slide="next">

                    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>

                    <span class="sr-only">Next</span>

                  </a>

                  <?php } ?>

                </div>











              </div>

            </div>



          </section><?php } ?>



          <!-- FIM RIFAS EM ANDAMENTO -->

