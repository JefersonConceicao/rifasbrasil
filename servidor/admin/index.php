<?php 
session_start(); 


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
      <?php 
        if(isset($_GET[p])) 
          include("page/".$_GET['p'].".php");
        else{
          if($_SESSION[admin] == 'S')
            include("page/usuario.php");
          else include("page/rifa.php");
        }
        ?>
    </div>
    <div class="col-lg-12">
      <div class="col-lg-12"><hr>rifasbrasil.com.br - todos os direitos reservados</div>
    </div>
    <div class="clearfix"></div>
  </section>


      <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="../js/bootstrap.min.js"></script>

    <!-- Bootstrap Datepicker -->
    <script src="../js/bootstrap-datepicker.js"></script>
    
    <!-- jQuery Mask para valores financeiros -->
    <script src="../js/money_mask/jquery.GlobalMoneyInput.js" type="text/javascript"></script>
    <script src="../js/money_mask/jquery.glob.js" type="text/javascript"></script>
    <script src="../js/money_mask/globinfo/jQuery.glob.pt-BR.js" type="text/javascript"></script>

    <!-- jQuery Mask pro Telefone -->
    <script type="text/javascript" src="../js/jquery.maskedinput.min.js"/></script>

    <script>
      $('.datepicker').datepicker({
          format: 'dd/mm/yyyy',
          language: 'pt-BR'
      }).on('changeDate', function(e){
          $(this).datepicker('hide');
      });
      $(function($){

        /* Init Global Plugin with Brazilian Portuguese configuration */
        var cfgCulture = 'pt-BR';
        $.preferCulture(cfgCulture);
        $('.dinheiro').maskMoney();

    });

  </script>

  </body>
</html>