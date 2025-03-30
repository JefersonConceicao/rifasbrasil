<?php
if (!isset($_SESSION))
  @session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] == 0 || !is_numeric($_SESSION['usuario'])) {

  // verificando se a pagina e para logar sem usuario (link gerado)
  $check_array = ['ver_bilhetes', 'ver_bilhetes_bolao_dezena', 'ver_bilhetes_centena', 'cadastro_cliente', 'carrinho', 'finalizar_pedido', 'finalizar_pedido_bolao_dezena', 'sucesso_venda', 'ver_bilhetes_mobile_dez_cen'];

  if (in_array($_GET['p'], $check_array)) {
    
    if (!isset($_SESSION['usuario_sem_login']) || !isset($_SESSION['cod_rev'])) {
      echo "<script>location.href='../index.php?p=entrar';</script>";
    }
  } else {
    echo "<script>location.href='../index.php?p=entrar';</script>";
  }
}

include("../class/conexao.php");
$cont = db_select($mysqli, "select count(*) as t from lote l, tbl_rifas r, tbl_usuario u where l.grafica = '" . $_SESSION['usuario'] . "' and l.rifa = r.rifa_cod and u.usu_cod = r.rifa_dono and l.impresso = 0", 1);
$contagem_impressao = $cont['t'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
  <title><?= $_SERVER['SERVER_NAME']; ?></title>
  <!-- Bootstrap -->
  <link href="/css/bootstrap.css" rel="stylesheet">
  <link href="../../css/estilo_admin.css" rel="stylesheet">
  <link rel="stylesheet" href="../../css/easy-autocomplete.themes.min.css">
  <link rel="stylesheet" href="../../css/easy-autocomplete.min.css">
  <link href='https://fonts.googleapis.com/css?family=Noto+Sans:400,400italic,700' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/css/bootstrap-select.min.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<?php
if ($_SERVER['SERVER_NAME'] === 'localhost')
  echo "<div style='padding:0.5%; background:red; color:white; font-weight:bold'>Você está no ambiente de desenvolvimento</div>";
?>

<body>
  <div id="loader" class="esconder"><progress id="progresso"></progress></div>
  <?php if (!isset($_POST['imprimir_vendas'])) { ?>
    <nav <?php if(isset($_SESSION['usuario_sem_login']) && !isset($_SESSION['usuario'])) echo 'style="display:none;"'; ?> class="navbar navbar-default">
      <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <span class="navbar-brand"><a href="../index.php">Voltar ao Site</a></span>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <?php if ($_SESSION['admin'] != 'S') {
            ?>
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">

              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "rifa") == 0 || !isset($_GET['p']) || $_GET['p'] == '')
                        echo 'class="active"';
                      ?>><a href="index.php?p=rifa">Rifas</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "debitor") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=debitor">Débitos</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "revenda") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=revenda">Revender</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "venda") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=venda">Vendas</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "compras") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=compras">Compras</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "config") == 0)
                        echo 'class="active"';
                      ?>>

                <?php if ($_SESSION['grafica'] == 1) {  ?>

              <li <?php
                        if (isset($_GET['p'])) if (strcmp($_GET['p'], "impressao") == 0)
                          echo 'class="active"';
                        ?>>
                <a href="index.php?p=impressao">Pedidos de Impressão (<?php echo $contagem_impressao; ?>)</a></li>

            <?php } ?>
            <li <?php
                    if (isset($_GET['p'])) if (strcmp($_GET['p'], "impressao") == 0)
                      echo 'class="active"';
                    ?>><a href="index.php?p=config">Configurações</a></li>
            <li><a href="index.php?p=logout">Logout</a></li>
            </ul>
          </div><!-- /.navbar-collapse -->
        <?php  } else {  ?>

          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "usuario") == 0 || strlen($_GET['p']) == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=usuario">Usuários</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "inst") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=inst">Instituições</a></li>

              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "banner") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=banner">Banners</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "rifa") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=rifa">Rifas</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "venda") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=venda">Vendas</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "sorteios") == 0)
                        echo 'class="active"';
                      ?>>
                <a href="index.php?p=sorteios">Créditos de Sorteios</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "grafica") == 0)
                        echo 'class="active"';
                      ?>>
                <a href="index.php?p=grafica">Gráficas</a></li>

              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "config") == 0)
                        echo 'class="active"';
                      ?>>
                <a href="index.php?p=config">Configurações</a></li>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "conteudo") == 0)
                        echo 'class="active"';
                      ?>>
                <a href="index.php?p=conteudo">Conteúdos</a></li>
              <?php if ($_SESSION['grafica'] == 1) {  ?>

                <li <?php
                          if (isset($_GET['p'])) if (strcmp($_GET['p'], "impressao") == 0)
                            echo 'class="active"';
                          ?>>
                  <a href="index.php?p=impressao">Pedidos de Impressão (<?php echo $contagem_impressao; ?>)</a></li>

              <?php } ?>
              <li <?php
                      if (isset($_GET['p'])) if (strcmp($_GET['p'], "apuracao") == 0)
                        echo 'class="active"';
                      ?>><a href="index.php?p=apuracao">Apuração Parcial</a></li>

              <li><a href="index.php?p=logout">Logout</a></li>
            </ul>
          </div><!-- /.navbar-collapse -->
        <?php } ?>
      </div><!-- /.container-fluid -->
    </nav>

  <?php } ?>
  <section class="holder">
    <div class="col-lg-12">
      <?php
      if (isset($_GET['p']))
        include("page/" . $_GET['p'] . ".php");
      else {
        if ($_SESSION['admin'] == 'S')
          include("page/usuario.php");
        else include("page/rifa.php");
      }
      ?>
    </div>
    <div class="col-lg-12">
      <div class="col-lg-12">
        <hr><b>2002/2020</b> - todos os direitos reservados</div>
    </div>
    <div class="clearfix"></div>
  </section>
  <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <!-- Include all compiled plugins (below), or include individual files as needed -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

  <script src="../js/jquery-ui.min.js"></script>
  <!-- Bootstrap Datepicker -->
  <script src="../js/bootstrap-datepicker.js"></script>

  <script src="../js/jquery.easy-autocomplete.min.js"></script>
  <!-- jQuery Mask para valores financeiros -->
  <script src="../js/money_mask/jquery.GlobalMoneyInput.js" type="text/javascript"></script>
  <script src="../js/money_mask/jquery.glob.js" type="text/javascript"></script>
  <script src="../js/money_mask/globinfo/jQuery.glob.pt-BR.js" type="text/javascript"></script>
  <!-- jQuery Mask pro Telefone -->
  <script type="text/javascript" src="../js/jquery.maskedinput.min.js" />
  </script>
  <!-- jQuery Mask pro Telefone -->
  <script type="text/javascript" src="../js/jquery_mask/jquery.mask.min.js" />
  </script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/js/bootstrap-select.min.js"></script>

  <!-- bootbox code -->
  <script src="../js/bootbox.all.min.js"></script>
  <script>
    <?php if ($_GET['p'] == 'revendedor') { ?>
      $('#importarRifa').select2({
        "width": '100%',
        "language": {
          "noResults": function() {
            return "Nenhum resultado encontrado";
          }
        },
        escapeMarkup: function(markup) {
          return markup;
        }
      });
    <?php } ?>

    <?php if ($_GET['p'] == 'venda') { ?>
      $('#rifa_destino_input').select2({
        "width": '100%',
        "language": {
          "noResults": function() {
            return "Nenhum resultado encontrado";
          }
        },
        escapeMarkup: function(markup) {
          return markup;
        }
      });
    <?php } ?>


    $(document).ready(function() {
      $('.telefone').mask('(99) 9999-99999');
      $('.cpf').mask('999.999.999-99');
      $('.data').mask('99/99/99');
      <?php if ($_GET['p'] == 'rif_detalhe') {

        echo "atualizar_tabela();\n";
        $rev_qr = "SELECT sum(valor) as soma, vendedor FROM tbl_custos where rifa = '$codigo' and vendedor > 0 group by vendedor";
        $qr_rev = $mysqli->query($rev_qr) or die($mysqli->error);
        $rev = $qr_rev->fetch_assoc();
        do {
          echo "atualizar_tabela(" . $rev['vendedor'] . ");\n";
        } while ($rev = $qr_rev->fetch_assoc());
      } ?>

    });
    $('.datepicker').datepicker({
      format: 'dd/mm/yyyy',
      language: 'pt-BR'
    }).on('changeDate', function(e) {
      $(this).datepicker('hide');
    });
    $(function($) {
      /* Init Global Plugin with Brazilian Portuguese configuration */
      var cfgCulture = 'pt-BR';
      $.preferCulture(cfgCulture);
      $('.dinheiro').maskMoney();
    });

    function show_loading() {
      $('#loader').removeClass('esconder');
    }

    function end_loading() {
      $('#loader').addClass('esconder');
    }
  </script>
  <?php
  if ($_GET['p'] == 'rif_detalhe') { ?>
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/jspdf@latest/dist/jspdf.min.js"></script>
    <script src="../js/html2canvas.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#tabela').DataTable({
          "bPaginate": false,
          "bLengthChange": false,
          "bFilter": true,
          "bInfo": false,
          "searching": false,
          "columnDefs": [{
            "targets": 'no-sort',
            "orderable": false,
          }]
        });
      });
    </script>

  <?php } ?>
</body>

</html>