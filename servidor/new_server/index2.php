<?php

ini_set('max_execution_time', 180); //300 seconds = 5 minutes

      include("../class/conexao.php");
      //if($_GET['testgrupos'])
      include("class/LoteamentoSurpresinha2.class.php");
      /*else
        include("class/Loteamento.class.php");*/

      $rifas  = $_GET['rifa'];
      $a = array();
      $e = array();

      if(is_array($rifas)){

        $setStartingGroup = false;
        $ativarBolao = false;

        foreach($rifas as $r){
          $res = db_select($mysqli, "select embaralhar, agrupar, bolaodezena, starting_group, numeros_por_bilhete from opcao_reserva where rifa = '$r' limit 1", 1);
          if($res['numeros_por_bilhete'] > 1){
            $url = str_replace('new_server', 'new_server2', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            echo file_get_contents($url);
              die();
          }

          if($res['bolaodezena']){
            $ativarBolao = $res['bolaodezena'];
            $setStartingGroup = $res['starting_group'];
          }

          if($res['embaralhar'])
            $e[$r] = true;
          else
            $e[$r] = false;


          if($res['agrupar'])
            $a[$r] = true;
          else
            $a[$r] = false;
        }
      }else{

        $rifas = intval($rifas);

        $res = db_select($mysqli, "select embaralhar, agrupar, numeros_por_bilhete, bolaodezena, starting_group from opcao_reserva where rifa = '$rifas' limit 1", 1);


        /*if($res['numeros_por_bilhete'] > 1){
          $url = str_replace('new_server', 'new_server2', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
          echo file_get_contents($url);
            die();
        }*/
        $setStartingGroup = false;
        $ativarBolao = false;
        if($res['bolaodezena']){
          $ativarBolao = $res['bolaodezena'];
          $setStartingGroup = $res['starting_group'];
        }
          

          if($res['embaralhar'])
            $e[$rifas] = true;
          else
            $e[$rifas] = false;

          if($res['agrupar'])
            $a[$rifas] = true;
          else
            $a[$rifas] = false;
      }

?>

<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Rifas Brasil</title>

    <!-- Bootstrap -->
    <link href="../css/bootstrap.css" rel="stylesheet">

    <link href="../css/estilo_admin.css" rel="stylesheet">


    <link href='https://fonts.googleapis.com/css?family=Noto+Sans:400,400italic,700' rel='stylesheet' type='text/css'>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
    
    </style>
  </head>
  <body>
    <nav class="navbar navbar-default">
    <div class="container-fluid">
     
    </div><!-- /.container-fluid -->
  </nav>

  <section class="holder">
    <div class="col-lg-12">
    	<div class="col-lg-12">
    		<h1 class="page-header">Baixar Páginas</h1>
    		<div class="form-group">
          
			<?php




			$layout = intval($_GET['layout']);
			
			$lot    = new Loteamento($rifas, $layout, $e, $a);

			$lot->apagarPDFs();

      if($ativarBolao){
        $lot->setDezenaBolao(true);
        $lot->setNumeroSurpresinhas(intval($ativarBolao));
        $lot->setStartingGroup($setStartingGroup);
      }

      if(isset($_POST['registrarBilhetes'])) {
        $lot->gerarCodigoMySQL();
        echo "<p>Registros inseridos no Banco de Dados <b>com sucesso!</b></p>";
      } else { ?>
        <div class="" style="display:none;" id="log">
        <?php

        // exec("rm -R /var/www/nevoahost/c/rifasbrasil.com.br/servidor/new_server/qrbf/rifa_" . intval($rifas) . "_*");

        if($layout == 6){
          $lot->setPng();
          for($k = 0; $k < $lot->getQuantidadeDePaginasNecessarias(); $k++){
            echo "</div><p><a target='_blank' href='../admin/resultado/".$lot->gerarPDF($lot->gerarHTML($k))."'>Baixar Página ".$k."</a></p>";
          }
        }else{
          echo "</div><a target='_blank' href='../admin/resultado/".$lot->gerarPDF($lot->gerarHTML())."'>Baixar Páginas</a>";
        }

        $k = 0;
      }

			?>

			</div>
      <?php if(!isset($_POST['registrarBilhetes'])) { ?>
  			<div class="form-group">
          <form action="" method="post">
    				<button type="button" onclick="window.history.go(-1);" class="btn btn-primary">Voltar</button>
            <button type="submit" name="registrarBilhetes" value="registrar" class="btn btn-success">Registrar bilhetes no banco de dados</button>
          </form>
  			</div>
      <?php } ?>
    	</div>
    </div>
    <div class="col-lg-12">
      <div class="col-lg-12"><hr>rifasbrasil.com.br - todos os direitos reservados</div>
    </div>
    <div class="clearfix"></div>

  </body>
</html>