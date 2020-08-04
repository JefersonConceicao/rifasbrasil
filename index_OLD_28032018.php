<?php

session_start();

if(isset($_GET['p'])){

  $subpagina = explode("/", $_GET['p']);

  if($subpagina[1] > 0){

    echo "<script>location.href='http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$subpagina[1]."';</script>";

    exit();

  }

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

    <link href="css/bootstrap.css" rel="stylesheet">

    <link href="css/estilo.css" rel="stylesheet">



    <link href='https://fonts.googleapis.com/css?family=Noto+Sans:400,400italic,700' rel='stylesheet' type='text/css'>



    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->

    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

    <!--[if lt IE 9]>

      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>

      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>

    <![endif]-->


  </head>

  <body>

      

      <nav class="navbar navbar-default topo">

          <div class="container-fluid col-lg-10 center-block">

            <!-- Brand and toggle get grouped for better mobile display -->        

            <div class="navbar-header">

              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">

                <span class="sr-only">Menu</span>

                <span class="icon-bar"></span>

                <span class="icon-bar"></span>

                <span class="icon-bar"></span>

              </button>

              <div style="float:left; width: 150px; margin-top: 13px;">

                      <a href="index.php?p=inicial"><img src="img/logotipo.png" alt="" class="img-responsive"></a> 

                  </div>

            </div>



            <!-- Collect the nav links, forms, and other content for toggling -->        

            <div class="collapse navbar-collapse menu " id="bs-example-navbar-collapse-1">

              <ul class="nav navbar-right navbar-nav">

                <!--<li >

                  <a href="#">

                    Quem Somos

                  </a>

                </li>-->

                <li>

                  <a href="index.php?p=entrar">

                    Painel do Cliente

                  </a>

                </li>


                <li>

                  <a href="index.php?p=rifas_abertas">

                    Rifas Abertas

                  </a>

                </li>

                <li>

                  <a href="index.php?p=entrar">

                    Cadastre-se

                  </a>

                </li>

                 <li>

                  <a href="index.php?p=quemsomos">

                    Quem somos

                  </a>

                </li>



                 <li>

                  <a href="index.php?p=comofunciona">

                    Como funciona

                  </a>

                </li>

                <li>

                  <a href="index.php?p=contato">

                    Contato

                  </a>

                </li>

                <li class="become-user">

                  <a href="index.php?p=entrar&next=admin/rifa" >

                  Crie sua rifa!

                  </a>

                </li>

              </ul>

            </div>

          </div>

        </nav>

    

    <?php



    



    if(isset($_GET[p])){



      

        include("page/".$_GET['p'].".php");



    }else

      include("page/inicial.php");

    ?>

  



    <section class="rodape">



        <div class="col-md-12 text-center col-lg-10 center-block">

          <p><img src='http://contador.s12.com.br/img-bDZwzzdb-3.gif' border='0' alt='contador'><script type='text/javascript' src='http://contador.s12.com.br/ad.js?id=bDZwzzdb'></script> visitas</p><p>RifasBRASIL - Todos os direitos reservados</p>

        </div>



    </section>





    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->

    <script src="js/bootstrap.min.js"></script>



    <!-- jQuery Mask pro Telefone -->

    <script type="text/javascript" src="js/jquery_mask/jquery.mask.min.js"/></script>





    <div id="fb-root"></div>

    <script>(function(d, s, id) {

      var js, fjs = d.getElementsByTagName(s)[0];

      if (d.getElementById(id)) return;

      js = d.createElement(s); js.id = id;

      js.src = "//connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.5&appId=238271186321151";

      fjs.parentNode.insertBefore(js, fjs);

    }(document, 'script', 'facebook-jssdk'));</script>



    <script>

    $(document).ready(function(){

  $('.telefone').mask('(99) 99999-9999');

});



    $(document).ready(function(){

  $('.cpf').mask('999.999.999-99');

});

    function show_loading(){
      $('#loading').removeClass('esconder');
    }

    function end_loading(){
    $('#loading').addClass('esconder');
    }

</script>



  </body>

</html>