
<section class="section-default" id="destaque">

      <div class="col-lg-10 center-block form-group form-inline">

        <div class="form-group">
        <p>Neste página, você poderá buscar informações sobre as rifas das quais você participa. Digite seu telefone para pesquisar.</p>
        </div>
        <form action="" method="post">
          <label for="">Seu telefone</label>
          <input type="text" value="<?php echo $_POST['telefone']; ?>" required name="telefone" class="form-control">
          <button class="btn btn-success">Buscar</button>
        </form>
      </div>
      <?php if(isset($_POST['telefone'])){


      include("../class/conexao.php");

$tel_final = "%";
for($i = 0; $i < strlen($_POST['telefone']); $i++){
  $tel_final .= $_POST['telefone'][$i]."%";
}


$sql_rifas = "SELECT * FROM tbl_rifas WHERE  rifa_cod in (select bil_rifa from tbl_bilhetes where bil_compra in (select comp_cod from tbl_compra where comp_cliente in (select usu_cod from tbl_usuario where usu_celular like '$tel_final'))) and rifa_deletada is null ORDER BY rifa_dtsorteio ASC";

$sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);

$rifa = $sql_query->fetch_assoc();

if($sql_query->num_rows > 0){
 ?>

      <div class="col-lg-10 center-block">Resultado:<hr>

<?php





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
<?php }else echo "<script>alert('Este telefone não foi encontrado em nenhuma rifa.');</script>"; } ?>

</section>