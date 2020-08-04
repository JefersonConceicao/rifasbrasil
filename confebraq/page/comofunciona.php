<!-- Conteudo Section -->
<?php

include("class/conexao.php");

$conteudo = DBSelect("select par_comofunciona from tbl_parametro where par_codigo = 1", $mysqli, "par_comofunciona");
?>


<section class="section-default" id="destaque">

    <div class="col-md-12 col-lg-10 center-block">

        <b>Como funciona</b><hr>

        <div class="col-lg-12">
            <?php 
                    if(isset($erro) && count($erro) > 0)
                        foreach($erro as $valor)
                            echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
                    ?>
        
        </div>
        

    
    <div class="col-md-12 col-lg-12">
    <?php echo $conteudo ;?>        
    </div>
        
        <div class="clearfix"></div>

        
        </div>
        
    </section>
