<?php

/*if(!$_SESSION) @session_start();
if(!isset($_SESSION['usuario']) || $_SESSION['usuario'] == 0 || !is_numeric($_SESSION['usuario']))
  echo "<script>location.href='http://rifasbrasil.com.br/index.php?p=entrar';</script>";*/

?><!DOCTYPE html>
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

      include("../class/conexao.php");
			include("class/Loteamento.class.php");

			$rifas  = $_GET['rifa'];
      $a = array();
      $e = array();

      $npb = array();

      if(is_array($rifas)){
        foreach($rifas as $r){
          $res = db_select($mysqli, "select embaralhar, agrupar, numeros_por_bilhete from opcao_reserva where rifa = '$r' limit 1", 1);

          if(intval($res['numeros_por_bilhete']) == 0)
            $npb[$r] = 1;
          else
            $npb[$r] = intval($res['numeros_por_bilhete']);

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

        $res = db_select($mysqli, "select embaralhar, agrupar, numeros_por_bilhete from opcao_reserva where rifa = '$rifas' limit 1", 1);

        if(intval($res['numeros_por_bilhete']) == 0)
          $npb[$rifas] = 1;
        else
          $npb[$rifas] = intval($res['numeros_por_bilhete']);

          if($res['embaralhar'])
            $e[$rifas] = true;
          else
            $e[$rifas] = false;

          if($res['agrupar'])
            $a[$rifas] = true;
          else
            $a[$rifas] = false;
      }


			$layout = intval($_GET['layout']);
			
			$lot    = new Loteamento($rifas, $layout, $npb, $e, $a, $_GET['bilhetesPorRifa']);

			$lot->apagarPDFs();


      $fim_do_loop = $lot->getQuantidadeDePaginasNecessarias();
      
			for($k = 0; $k < $fim_do_loop; $k++){
				echo "<p>";
				echo "<a target='_blank' href='../admin/resultado/".$lot->gerarPDF($lot->gerarHTML($k))."'>Baixar Página ".($k+1)."</a>";
				echo "</p>";
			}

			$lot->apagarHTMLs();

			?>
			</div>
			<div class="form-group">
				<button onclick="window.history.go(-1);" class="btn btn-primary">Voltar</button>
			</div>
    	</div>
    </div>
    <div class="col-lg-12">
      <div class="col-lg-12"><hr>rifasbrasil.com.br - todos os direitos reservados</div>
    </div>
    <div class="clearfix"></div>

  </body>
</html>