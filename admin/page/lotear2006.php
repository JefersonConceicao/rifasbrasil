<?php

include("../class/conexao.php");

$creditos = db_select($mysqli, "select usu_credito from tbl_usuario where usu_cod = '$_SESSION[usuario]' limit 1", 1);
$creditos = floatval($creditos['usu_credito']);

$rifa = intval($_GET['codigo']);

//Adição de Créditos
if (isset($_POST['valor']) && $_POST['valor'] > 0) {

    $var = array();
    $var['valor'] = $_POST['valor'];
    $var['usuario'] = $_SESSION['usuario'];
    $var['data'] = date("Y-m-d h:i:s");
    $var['aprovada'] = false;

    $r = DBInsert($mysqli, "tbl_transacao", $var, 0);

    if ($r)
        echo "<script>location.href='index.php?p=pagar_credito&transacao=" . $r . "';</script>";
    else
        $erro[] = "Ops! Alguma coisa deu errado ao solicitar seu crédito.";
}



$minimo = 100.00;
$possui_creditos = true;

// Se não possuir a quantidade mínima de créditos
if ($creditos < $minimo) {

    $erro[] = "Você não possui créditos suficientes para isso. Adicione créditos antes de prosseguir:
	<div class=\"form-inline form-group\">
		<form method=\"post\" class=\"\">
			<select class=\"form-control\" name=\"valor\">
				<option value=\"\">...</option>
				<option value=\"" . $minimo . "\">R$ " . number_format($minimo, 2, ',', '.') . "</option>
			</select>
			<button class=\"btn btn-success\">Pagar</button>
		</form>
	</div>";
    $possui_creditos = false;
}

function max_bilhetes($bilhetes)
{

    if ($bilhetes > 900 && $bilhetes < 1200)
        $bilhetes = 1000;
    elseif ($bilhetes > 3000)
        $bilhetes = 10000;

    return $bilhetes;
}

// if ($_SERVER['REMOTE_ADDR'] == "179.48.23.102") {
$sql = "
        select 
        rifa_cod, 
        rifa_titulo, 
        rifa_valorbilhete, 
        rifa_maxbilhetes 
        from tbl_rifas  r
        left join tbl_situacao_rifa tsr ON tsr.id_rifa = r.rifa_cod 
        WHERE rifa_deletada is null
        AND rifa_dono = '$_SESSION[usuario]'
    ";
// } else {
//     $sql = "select rifa_cod, rifa_titulo, rifa_valorbilhete, rifa_maxbilhetes from tbl_rifas where rifa_dono = '$_SESSION[usuario]' and rifa_deletada is null";
// }

$rifas_usuario = db_select($mysqli, $sql);

// Seleciona as Graficas
$graficas = db_select($mysqli, "select usu_cod, usu_nome from tbl_usuario where usu_grafica = 1 order by usu_nome asc");

?>

<body onload="javascript: verify();">

    <form action="" method="post">

        <style>
            .esconder {
                display: none
            }
        </style>
        <div class="col-lg-12 text-left">
            <h3>Lotear</h3>
            <hr>

            <?php if (count($erro) > 0) { ?>
                <div style="padding:10px; margin:10px 0 10px 0;" class="col-lg-12 bg-warning text-center">
                    <?php foreach ($erro as $msg) echo "<p>{$msg}</p>"; ?>
                </div>
            <?php } ?>
        </div>
    </form>

    <?php if ($possui_creditos) { ?>
        <form action="index.php?p=lotear_final_new" method="post">

            <div class="col-lg-6 text-left">
                <div class="col-lg-12 col-md-12 form-group">

                    <label for="">Escolha a Rifa</label>
                    <select required name="rifa" id="rifa" class="form-control">

                        <option value="">...</option>
                        <?php
                        foreach ($rifas_usuario as $rif) { ?>
                            <option value="<?php echo $rif['rifa_cod']; ?>"><?php echo $rif['rifa_titulo'] . " (R$ " . number_format($rif['rifa_valorbilhete'], 2, ',', '.') . " por bilhete | " . max_bilhetes($rif['rifa_maxbilhetes']) . " bilhetes)"; ?></option>
                        <?php } ?>

                    </select>

                </div>

                <div class="col-lg-6 col-md-6 form-group">
                    <label for="">Quantos Bilhetes pelo Valor</label>
                    <input required type="text" onchange="javascript: verify();" placeholder="" name="bilhetes" id="bilhetes" class="form-control">
                </div>



                <div class="col-lg-6 col-md-6 form-group">
                    <label for="">Quantidade de Bilhetes no Grupo</label>
                    <select required name="grupo" id="grupo" class="form-control">
                        <option value="">...</option>
                        <option value=""></option>
                    </select>
                </div>



                <div class="col-lg-6 form-group">

                    <label for="">Escolha a Gráfica</label>
                    <select required name="grafica" id="grafica" class="form-control">
                        <option value="">...</option>
                        <?php
                        foreach ($graficas as $rif) { ?>
                            <option value="<?php echo $rif['usu_cod']; ?>"><?php echo $rif['usu_nome']; ?></option>
                        <?php } ?>
                    </select>

                </div>



                <div class="col-lg-6 form-group">

                    <label for="">Escolha o Tipo da Rifa</label>
                    <select required name="tipo" id="tipo" class="form-control">
                        <option value="">...</option>
                        <option value="1">Tipo 01 - Pequena</option>
                        <option value="2">Tipo 02 - Grande</option>
                    </select>

                </div>



                <div class="col-lg-12 form-group text-left">

                    <div role="alert" class="alert bg-danger text-danger">Atenção: Esta operação vai descontar R$ <?php echo number_format($minimo, 2, ',', '.'); ?> da sua conta de forma irreversível.</div>

                    <button class="btn-success btn" type="submit" name="enviar" value="ok">LOTEAR - IMPRIMIR</button>

                </div>



                <div class="clearfix"></div>













            </div>





            <script>
                function verify() {



                    var input_bil = $('#bilhetes').val();

                    if (input_bil.length > 0) {



                        $("#grupo option").remove();

                        $('#grupo').append($('<option>', {

                            value: '...',

                            text: '...'

                        }));



                        var inicio = input_bil * input_bil;

                        var k = 0;

                        while (k < 7)

                            $('#grupo').append($('<option>', {

                                value: inicio + (k * input_bil),

                                text: inicio + (k++ * input_bil)

                            }));

                    }



                }
            </script>





        </form>

    <?php }


    if ($_SESSION['admin'] == 'S')
        $rifas_loteadas = db_select($mysqli, "select * from lote l, tbl_rifas r where l.rifa in (select rifa_cod from tbl_rifas) and r.rifa_cod = l.rifa");
    else
        $rifas_loteadas = db_select($mysqli, "select * from lote l, tbl_rifas r where l.rifa in (select rifa_cod from tbl_rifas where rifa_dono = '$_SESSION[usuario]') and r.rifa_cod = l.rifa");

    ?>

    <div class="col-lg-12">

        <br>

        <br>

        <div class="clearfix"></div>

    </div>

    <div class="col-lg-12">



        <h3 class="page-tittle">Rifas já loteadas</h3>

        <div class="col-lg-12 table-responsive">

            <table class="table table-hover table-bordered">

                <thead>

                    <hr>

                    <td width="50%">Rifa</td>

                    <td></td>

                    </hr>

                </thead>

                <body>

                    <?php



                    foreach ($rifas_loteadas as $r) {



                        $bil_code = "SELECT * FROM tbl_bilhetes where bil_rifa = '$r[rifa_cod]'";

                        $bil_query = $mysqli->query($bil_code) or die($mysqli->error);

                        $bil = $bil_query->fetch_assoc();

                        $bil_num = $bil_query->num_rows;



                    ?>

                        <tr>

                            <td>

                                <small><label for="">Rifa <?php echo $r['rifa_cod']; ?></label></small>

                                <p><?php echo $r['rifa_titulo']; ?></p>

                                <small><label>Data do sorteio:</label></small> <?php echo date("d/m/Y", strtotime($r['rifa_dtsorteio'])); ?><br>

                                <small><label>Valor:</label></small> R$ <?php echo number_format($r['rifa_valorbilhete'], 2, ',', '.'); ?><br>

                                <small><label>Bilhetes:</label></small> <?php echo number_format($r['rifa_maxbilhetes'], 0, ',', '.'); ?> <small><label>Vendidos:</label></small> <?php echo $bil_num; ?><br>

                            </td>

                            <td class="text-right">

                                <form action="index.php?p=lotear_final_new" method="post">

                                    <input type="hidden" name="rifa" value="<?php echo $r['rifa_cod']; ?>">

                                    <input type="hidden" name="grupo" value="<?php echo $r['grupo']; ?>">

                                    <button type="submit" class="btn btn-primary">Editar Loteamento</button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                </body>

            </table>

        </div>

    </div>

</body>