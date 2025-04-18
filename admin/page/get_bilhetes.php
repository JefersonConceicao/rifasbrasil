<?php
error_reporting(1);

$inicio = intval($_GET['inicio']);
$fim = intval($_GET['fim']);
$rifa = intval($_GET['rifa']);
$selecionado = $_GET['selecionado'];
$maxbilhettes = intval($_GET['maxbilhetes']);
$dezenabolao = (isset($_GET['dezenabolao'])) ? true : false;

if (isset($_GET['origem'])) {
    $origem = $_GET['origem'];
} else {
    $origem = NULL;
}


include("../../class/conexao.php");
include("../../class/function_surpresinha.php");

if (!$_SESSION) @session_start();

$query_rifa = $mysqli->query("SELECT rifa_maxbilhetes, rifa_dono, dezena_bolao, travar_bilhetes, banca_online, multiplicador, valor_aposta, dezena_bolao, etapa1, etapa2 FROM tbl_rifas WHERE rifa_cod = '$rifa'");
$query_rifa = $query_rifa->fetch_assoc();
$rifa_dezena_bolao = $query_rifa['dezena_bolao'];
$rifa_max_bilhetes = $query_rifa['rifa_maxbilhetes'];
$etapa1 = $query_rifa['etapa1'];
$etapa2 = $query_rifa['etapa2'];
$modoBancaOnline = $query_rifa['valor_aposta'];
$multiplicador = $query_rifa['multiplicador'];
    
$selecao_2_etapas = false;

if($etapa1 && $etapa2) {
    $etapa = array();
    $etapa_atual = isset($_GET['etapa']) ? intval($_GET['etapa']) : 1;
    $etapa[1] = explode('-', $etapa1);
    $etapa[2] = explode('-', $etapa2);
    $selecao_2_etapas = true;
} else if($etapa1) {
    $etapa = array();
    $etapa_atual = isset($_GET['etapa']) ? intval($_GET['etapa']) : 1;
    $etapa[1] = explode('-', $etapa1);
    $selecao_2_etapas = true;
}

$dezenabolao = ($rifa_dezena_bolao) ? true : false;
if ($query_rifa['travar_bilhetes'] == '1') $travarBilhetes = true;

if($travarBilhetes && $rifa_dezena_bolao) {
    $fim = $rifa_max_bilhetes;
}


function getBilhetesDaLinhaNoBd ($linha) {
    $resultado = array();
    if(strpos($linha, '-') !== false) {
        $tmp = explode('-', $linha);
        for($k = intval($tmp[0]); $k < intval($tmp[1]); $k++) {
            $resultado[$k] = true;
        }
    } else if(strpos($linha, ',') !== false) {
        $tmp = explode(',', $linha);
        
        foreach($tmp as $t) {
            $resultado[intval($t)] = true;
        }
    }
    return $resultado;
}


$bilhetes_disponiveis_revendedor = false;
if($travarBilhetes && ((isset($_SESSION['cod_rev']) && $query_rifa['rifa_dono'] != $_SESSION['cod_rev']) || (isset($_SESSION['usuario']) && $query_rifa['rifa_dono'] != $_SESSION['usuario']))) {
    
    $travado = $dezenabolao;
    $user = isset($_SESSION['cod_rev']) ? $_SESSION['cod_rev'] : $_SESSION['usuario'];
    $bilhetes_disponiveis_revendedor = array();
    $query_opcao_reserva = $mysqli->query("SELECT * FROM reserva WHERE rifa = '$rifa' AND revendedor = '{$user}'") or die($mysqli->error);
    $resultado_opcao_reserva = $query_opcao_reserva->fetch_assoc();

    if($query_opcao_reserva->num_rows) 
        do {
            $bilhetes_disponiveis_revendedor = $bilhetes_disponiveis_revendedor + getBilhetesDaLinhaNoBd($resultado_opcao_reserva['bilhete']);
        } while ($resultado_opcao_reserva = $query_opcao_reserva->fetch_assoc());


    $query_opcao_reserva = $mysqli->query("SELECT * FROM grupo_revendedor gr, revenda r WHERE gr.revenda = r.codigo AND r.rifa = '$rifa' AND r.vendedor = '{$user}'") or die($mysqli->error);
    $resultado_opcao_reserva = $query_opcao_reserva->fetch_assoc();

    if($query_opcao_reserva->num_rows) {

        $todos_grupos = array();
        do {
            $todos_grupos[$resultado_opcao_reserva['grupo']] = true;
        } while ($resultado_opcao_reserva = $query_opcao_reserva->fetch_assoc());

        if(array_keys($todos_grupos)) {
            $array_keys_string = "'" . implode("', '", array_keys($todos_grupos)) . "'";

            $query_cache = $mysqli->query("SELECT * FROM cache_bilhetes_do_grupo WHERE rifa = '$rifa' AND grupo IN ({$array_keys_string})") or die($mysqli->error);

           //var_dump("SELECT * FROM cache_bilhetes_do_grupo WHERE rifa = '$rifa' AND grupo IN ({$array_keys_string})");

            $resultado_query_cache = $query_cache->fetch_assoc();

            $cache_de_bilhetes = array();
            if($query_cache->num_rows)
                do {

                    if(!isset($cache_de_bilhetes[$resultado_query_cache['grupo']]))
                        $cache_de_bilhetes[$resultado_query_cache['grupo']] = array();

                    $cache_de_bilhetes[$resultado_query_cache['grupo']][intval($resultado_query_cache['bilhete'])] = true;

                } while($resultado_query_cache = $query_cache->fetch_assoc());

            $paraInserirNoBd = array();

            foreach($todos_grupos as $grupo=>$whatever) {

                if(isset($cache_de_bilhetes[$grupo])) {
                    //echo 'isset';
                    foreach($cache_de_bilhetes[$grupo] as $bil=>$void) {
                        $bilhetes_disponiveis_revendedor[$bil] = true;
                    }
                } else {
                    //echo 'nao isset';
                    $bilhetes = file_get_contents("http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php?rifa={$rifa}&layout=1&grupo={$grupo}");
                    $bilhetes = (json_decode($bilhetes, 1));
                    $bilhetes = $bilhetes['bilhete'];
                    foreach($bilhetes as $bil) {
                        $tmp = explode('-', $bil);
                        $bilhetes_disponiveis_revendedor[intval($tmp[1])] = true;
                        if(!isset($paraInserirNoBd[$grupo]))
                            $paraInserirNoBd[$grupo] = array();
                        $paraInserirNoBd[$grupo][] = intval($tmp[1]);
                    }
                }
            }

            if(count(array_keys($paraInserirNoBd)) > 0) {
                $cache_values = array();
                foreach($paraInserirNoBd as $grupo=>$bilhetes_cache) {
                    foreach($bilhetes_cache as $bilhete_inserir) {
                        $cache_values[] = "('$grupo', '$bilhete_inserir', '$rifa')";
                    }
                }
                $inserir_no_cache = "INSERT INTO cache_bilhetes_do_grupo (grupo, bilhete, rifa) VALUES ". implode(', ', $cache_values);
                $mysqli->query($inserir_no_cache) or die('FALHOU AO INSERIR NO CACHE: ' . $mysqli->error);
            }

            /*
            do {
                $bilhetes = file_get_contents("http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php?rifa={$rifa}&layout=1&grupo={$resultado_opcao_reserva['grupo']}");
                $bilhetes = (json_decode($bilhetes, 1));
                $bilhetes = $bilhetes['bilhete'];
                foreach($bilhetes as $bil) {
                    $tmp = explode('-', $bil);
                    $bilhetes_disponiveis_revendedor[intval($tmp[1])] = true;
                }
                //$bilhetes_disponiveis_revendedor = $bilhetes_disponiveis_revendedor + getBilhetesDaLinhaNoBd($resultado_opcao_reserva['bilhete']);
            } while ($resultado_opcao_reserva = $query_opcao_reserva->fetch_assoc());
            */
        }
    }
}



// Verifica se o bilhete esta reservado (pagamento pendente)
// Para esta verificacao demos selecionar na tbl_bilhetes os bilhetes de mesma compra 
// E na tbl_compra verificar o status destes (reservado == '' ou NULL)
$stmt = $mysqli->prepare(
    "SELECT
    bilhetes.bil_rifa,
    bilhetes.bil_numero,
    bilhetes.bil_compra,
    compra.comp_cod
FROM
    tbl_bilhetes bilhetes
INNER JOIN tbl_compra compra ON
    bilhetes.bil_compra = compra.comp_cod
WHERE
    (bilhetes.bil_rifa = '$rifa') AND
    (compra.comp_situacao = '' OR compra.comp_situacao IS NULL) "
);

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bilhetesReservados[] = $row['bil_numero'];
}

$user = isset($_SESSION['cod_rev']) ? $_SESSION['cod_rev'] : $_SESSION['usuario'];

// Puxa os grupos que pertencem a outros revendedores e todos os bilhetes que se encaixam nesses grupos
$sqlBil = "SELECT gr.grupo, concat(u.usu_nome, ' (', u.usu_celular, ')') as rev
FROM grupo_revendedor gr, revenda r, tbl_usuario u
WHERE r.rifa = '$rifa'
and r.vendedor != '{$user}'
and r.vendedor != '16869'
and r.vendedor = u.usu_cod
and gr.revenda = r.codigo";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();

include('../../class/Rifa.php');
$bilhetes_separados_por_grupo = getBilhetesESeusGrupos($rifa);

do {
    // puxa os bilhetes do grupo em questão
    $temp = $bilhetes_separados_por_grupo[strtoupper($bil['grupo'])];
    if (!is_array($temp))
        $temp = array($temp);

    $bilhetesReservados = array_merge($bilhetesReservados, $temp);
    foreach ($temp as $bilhete_add) {
        $bilheteVendedor[$bilhete_add] = $bil['rev'];
    }
} while ($bil = $queryBil->fetch_assoc());


// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas
$sqlBil = "SELECT
bilhetes.bil_rifa,
bilhetes.bil_numero,
bilhetes.bil_compra,
bilhetes.bil_bilhete_original,
compra.comp_cod
FROM
tbl_bilhetes bilhetes
INNER JOIN tbl_compra compra ON
bilhetes.bil_compra = compra.comp_cod
WHERE
(
    (bilhetes.bil_numero < '$fim') AND(bilhetes.bil_numero >= '$inicio')
) AND(bilhetes.bil_rifa = '$rifa') AND(
    compra.comp_situacao = '3' OR compra.comp_situacao = '4' OR compra.comp_status_revenda = '1'
)";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();
$bilVendidos = array();

/*
if($_GET['teste'] && $travarBilhetes && $rifa_dezena_bolao) {
    // agrupa os bilhetes por compra, pra poder traduzir cada compra em 1 bilhete
    // montar uma lista de traducao
    $listaBilhetes = array();
    for($k = 0; $k <= $fim; $k++) {
        $tmp = gerarDezenas($rifa, $k);
        foreach($tmp as $num) {
            if(!isset($listaBilhetes[$num]))
                $listaBilhetes[$num] = array();
            $listaBilhetes[$num][] = $k;
        }
    }

    $bilhetesVendidosPorCompra = array();
    if ($queryBil->num_rows > 0)
        do {
            //$bilVendidos[] = $bil['bil_numero'];
            if(!isset($bilhetesVendidosPorCompra[$bil['comp_cod']]))
                $bilhetesVendidosPorCompra[$bil['comp_cod']] = array();
            $bilhetesVendidosPorCompra[$bil['comp_cod']][] = intval($bil['bil_numero']);
        } while ($bil = $queryBil->fetch_assoc());
    //var_dump($listaBilhetes);

    foreach($bilhetesVendidosPorCompra as $compra => $arrBilhetes) {

        $provaveis = array();
        foreach($arrBilhetes as $bilhete) {
            if(count($provaveis) == 0)
                $provaveis = $listaBilhetes[$bilhete];
            else
                $provaveis = array_intersect($provaveis, $listaBilhetes[$bilhete]);
            if(count($provaveis) == 1)
                break;
        }
        $bilVendidos[] = array_pop($provaveis);

    }

    var_dump($bilVendidos);
    
} else {
    if ($queryBil->num_rows > 0)
        do {
            $bilVendidos[] = $bil['bil_numero'];
            
        } while ($bil = $queryBil->fetch_assoc());
}
*/


//$travarBilhetes && $rifa_dezena_bolao
$bil_adicionado = array();
if ($queryBil->num_rows > 0)
    do {
        if($travarBilhetes && $rifa_dezena_bolao) {
            if($bil['bil_bilhete_original'] && !isset($bil_adicionado[$bil['bil_bilhete_original']])) {
                $bil_adicionado[$bil['bil_bilhete_original']] = true;
                $bilVendidos[] = $bil['bil_bilhete_original'];
            }
        } else
            $bilVendidos[] = $bil['bil_numero'];
    } while ($bil = $queryBil->fetch_assoc());

$bilhetes_da_etapa = array();
if($selecao_2_etapas) {

    $bilhetes_da_etapa_1 = array();
    for($k = intval($etapa[1][0]); $k <= intval($etapa[1][1]); $k++) {
        $bilhetes_da_etapa_1[$k] = true;
    }

    if($etapa_atual == 1) {
        $bilhetes_da_etapa = $bilhetes_da_etapa_1;
    } else {
        for($k = 0; $k <= intval($etapa[2][1]); $k ++) {
            if(!$bilhetes_da_etapa_1[$k])
                $bilhetes_da_etapa[$k] = true;
        }
    }
    
    
}

if($modoBancaOnline) {

    
    $bilhetesReservados = array();
    //$sql_query = "SELECT SUM(bil.bil_aposta) as apostas, bil.bil_numero FROM tbl_bilhetes bil WHERE bil.bil_rifa = '$rifa' GROUP BY bil.bil_numero";
    $sql_query = "SELECT bil.bil_aposta, u.usu_cod as cliente_id, u.usu_nome as cliente_nome, bil.bil_aposta, bil.bil_numero FROM tbl_bilhetes bil, tbl_usuario u, tbl_compra c WHERE bil.bil_rifa = '$rifa' AND c.comp_cod = bil.bil_compra AND u.usu_cod = c.comp_cliente";
    $query = $mysqli->query($sql_query) or die($mysqli->error);

    $tooltip = array();
    $bilVendidos = array();
    $parciais = array();
    $nomesClientes = array();

    $somatorioApostas = array();
    $porComprador = array();

    while ($bil = $query->fetch_assoc()) {
        if(!isset($somatorioApostas[$bil['bil_numero']])) 
            $somatorioApostas[$bil['bil_numero']] = 0;

        if(!isset($porComprador[$bil['bil_numero']])) 
            $porComprador[$bil['bil_numero']] = array();

        if(!isset($porComprador[$bil['bil_numero']][$bil['cliente_id']])) 
            $porComprador[$bil['bil_numero']][$bil['cliente_id']] = 0;

        if(!isset($nomesClientes[$bil['cliente_id']]))
            $nomesClientes[$bil['cliente_id']] = $bil['cliente_nome'];
        
        $somatorioApostas[$bil['bil_numero']] += $bil['bil_aposta'];  
        $porComprador[$bil['bil_numero']][$bil['cliente_id']] += $bil['bil_aposta']; 

    }


    foreach($porComprador as $bil_numero => $arr ) {
        foreach($arr as $cliente_id => $apostado) {

            $podeganhar = $apostado * $multiplicador;

            if(!isset($tooltip[$bil_numero]))
                $tooltip[$bil_numero] = array();

            $tooltip[$bil_numero][] = "{$nomesClientes[$cliente_id]}: {$apostado}x{$podeganhar}";

        }
    }

    foreach($somatorioApostas as $bil=>$valor) {
        if($valor >= $modoBancaOnline)
            $bilVendidos[] = $bil;
        else {
            unset($tooltip[$bil]);
            $parciais[] = $bil;
        }
            
    }
}

if($travarBilhetes && isset($_GET['linkMovel'])) {

    if(isset($_GET['getCount'])) {
        // START START
        $count = 0;
        $final_count = array(
            'outros' => 0,
            'livres' => 0,
            'reservados' => 0,
            'vendidos' => 0
        );

        $range = array();
        if($travado) {
            foreach($bilhetes_disponiveis_revendedor as $bil=>$void)
                $range[] = $bil;
        } else {
            for ($inicio; $inicio < $rifa_max_bilhetes; $inicio++)
                $range[] = $inicio;
        }
        foreach($range as $inicio) {
        //for ($inicio; $inicio < $rifa_max_bilhetes; $inicio++) {
            
            if($selecao_2_etapas && !$bilhetes_da_etapa[$inicio]) 
                continue;

            // se for dezena bolao, não precisa descobrir quais bilhetes foram vendidos
            if ($dezenabolao) {
                $bilVendidos = array();
                $bilhetesReservados = array();
            }

            if(!in_array($inicio, $bilVendidos) && !in_array($inicio, $bilhetesReservados)) {
                if ($bilhetes_disponiveis_revendedor[$inicio] !== true) {
                    $count ++;
                    $final_count['outros']++;
                } else {
                    $count ++;
                    $final_count['livres']++;
                }
            } else if(in_array($inicio, $bilVendidos)) {
                $count ++;
                $final_count['vendidos']++;
            } else if(in_array($inicio, $bilhetesReservados)) {
                $count ++;
                $final_count['reservados']++;
            } else
                continue;

            if($count == $rifa_max_bilhetes)
                break;
            

        }

        die(json_encode($final_count));

        // END END
    }

    ?>
    <style>
        .bilhete-travado {
            background-color: #337ab7;
            border-color: #2e6da4;
            color:white;
        }
        .venda_parcial {
            background-color:	#04A1E5!important;
        }
        .bilhete-vendido {
            background-color: #d9534f;
            border-color: #d43f3a;
            color:white;
        }
        .bilhete-reservado {
            background-color: #f0ad4e;
            border-color: #eea236;
            color: white;
        }
        .bilhete {
            color: #fff;
            background-color: #5cb85c;
            border-color: #4cae4c;
        }
        .bilhete_holder {
            padding: 3px;
        }
        .bilhete_selected {
        border: 1px solid #FFFC45;
        background-color: #FFF04D;
        color:black;
    }
    </style>

    <?php

    $somenteVendidos = isset($_GET['tipo']) && $_GET['tipo'] == 'vendido' ? true:false;
    $somenteReservados = isset($_GET['tipo']) && $_GET['tipo'] == 'reservado' ? true:false;
    $somenteDisponivel = isset($_GET['tipo']) && $_GET['tipo'] == 'disponivel' ? true:false;
    $somenteTravado = isset($_GET['tipo']) && $_GET['tipo'] == 'travado' ? true:false;

    $max = 1000;
    $count = 0;

    if( $somenteReservados || $somenteVendidos) {
        $fim = 10000;
        $max = 10000;
    }

    //$inicio_original = $inicio;
    $range = array();
    if($travado) {
        foreach($bilhetes_disponiveis_revendedor as $bil=>$void)
            $range[] = $bil;
    } else {
        for ($inicio; $inicio < $fim; $inicio++)
            $range[] = $inicio;
    }
    foreach($range as $inicio) {
    //for ($inicio; $inicio < $fim; $inicio++) {


        if($selecao_2_etapas && !$bilhetes_da_etapa[$inicio])
            continue;

        if ($dezenabolao)
            $numero_bilhete = str_pad($inicio, 2, "0", STR_PAD_LEFT);
        else
            $numero_bilhete = str_pad($inicio, $maxbilhettes, "0", STR_PAD_LEFT);

        // se for dezena bolao, não precisa descobrir quais bilhetes foram vendidos
        if ($dezenabolao && !$travarBilhetes) {
            $bilVendidos = array();
            $bilhetesReservados = array();
        }

        /*
        if($bilhetes_disponiveis_revendedor[$inicio]) {
            var_dump($inicio, in_array($inicio, $bilVendidos), in_array($inicio, $bilhetesReservados));
            echo "<BR>";
        }
        */
        //echo $somenteDisponivel . ' ' . $inicio . '-' . ($bilhetes_disponiveis_revendedor[$inicio] ? 1:0 ).' <br>';

        if($somenteTravado && !in_array($inicio, $bilVendidos) && !in_array($inicio, $bilhetesReservados)) {
            
            if ($bilhetes_disponiveis_revendedor[$inicio] !== true) {

                $count ++;
                ?>
                 <div style="margin-bottom:10px;" class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-2">
                    <div class="col-lg-12 bilhete-travado">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
            <?php 
            }
            

        } else if($somenteVendidos && in_array($inicio, $bilVendidos)) {

            $count ++;
            if ($rifa_max_bilhetes == 100 && $rifa_dezena_bolao == 0 && $origem == NULL) { ?>
                <div class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-2">
                    <div onclick="bilheteReservado('<?php echo $numero_bilhete; ?>', 'PAGO');" class="col-lg-12 bilhete-vendido">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
            <?php } else { ?>

                <div style="margin-bottom:10px;" class="bilhete_holder col-xs-3 col-sm-4 col-md-2 col-lg-1">
                    <div onclick="bilheteReservado('<?php echo $numero_bilhete; ?>', 'PAGO');" class="col-lg-12 bilhete-vendido">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
            <?php }


        } else if($somenteReservados && in_array($inicio, $bilhetesReservados)) {
            $count ++;
            if ($rifa_max_bilhetes == 100 && $rifa_dezena_bolao == 0 && $origem == NULL) { ?>
                <div class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                    <div onclick="bilheteReservado('<?php echo $numero_bilhete; ?>');" class="col-lg-12 bilhete-reservado">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
                <?php } else { ?>
                <div style="margin-bottom:10px;" class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                    <div onclick="bilheteReservado('<?php echo $numero_bilhete; ?>');" class="col-lg-12 bilhete-reservado">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
                <?php 
            }
        } else if($somenteDisponivel && !in_array($inicio, $bilVendidos) && !in_array($inicio, $bilhetesReservados)) {
            $bloqueado = false;

            if($bilhetes_disponiveis_revendedor[$inicio] === true) {
                $count ++;
                if ($rifa_max_bilhetes == 100 && $rifa_dezena_bolao == 0 && $origem == NULL) : ?>
                    <div onclick="javascript: checkar('<?php echo $numero_bilhete; ?>');" class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                        <div onclick="scrollOnClick()" id="holder<?php echo $numero_bilhete; ?>" class="col-lg-12 bilhete <?php if ($_SESSION['bilhete' . $j] == 1 || strpos($selecionado, $numero_bilhete . ";")  !== false) echo "bilhete_selected"; ?>">
                            <input <?php if (strpos($selecionado, $numero_bilhete . ";")  !== false) echo "checked=\"checked\";" ?> class="esconder" <?php if ($_SESSION['bilhete' . $inicio] == 1) echo "checked"; ?> value="<?php echo $numero_bilhete; ?>" name="bilhete[]" id="bilhete<?php echo $numero_bilhete; ?>" type="checkbox">
                            <?php echo $numero_bilhete; ?>
                        </div>
                    </div>
                    <?php else : ?>
                    <div style="margin-bottom:10px;" onclick="javascript: checkar('<?php echo $numero_bilhete; ?>');" class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                        <div id="holder<?php echo $numero_bilhete; ?>" class="col-lg-12 bilhete <?php if ($_SESSION['bilhete' . $j] == 1 || strpos($selecionado, $numero_bilhete . ";")  !== false) echo "bilhete_selected"; ?>">
                            <input <?php if (strpos($selecionado, $numero_bilhete . ";")  !== false) echo "checked=\"checked\";" ?> class="esconder" <?php if ($_SESSION['bilhete' . $inicio] == 1) echo "checked"; ?> value="<?php echo $numero_bilhete; ?>" name="bilhete[]" id="bilhete<?php echo $numero_bilhete; ?>" type="checkbox">
                            <?php echo $numero_bilhete; ?>
                        </div>
                    </div>
                <?php 
                endif;
            }
        } else
            continue;


        if($count == $max) {
            break;
        }

        /*if($count == 1000  ||  || ($inicio == ($fim-1))) {

            $btns = '';

            //$btns .= '<button onclick="javascript: get_bilhetes(\''.($inicio_original-1000).','.($inicio_original).'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-left">Página Anterior</button>';
            $btns .= '<button onclick="javascript: get_bilhetes(\''.$inicio.','.($inicio+1000).'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-right">Próxima Página</button>';

            echo '<div class="clearfix"></div><div style="margin-top:20px; margin-bottom:20px;" class="col-lg-12 form-group text-right">
                '.$btns.'
                </div>';

                break;

        }

        
        /*if($count == 1000 && $rifa_max_bilhetes > 1000) {




            /*

            //$btns .= '<button onclick="javascript: get_bilhetes(\''.$first.','.$last.'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-left">Página Anterior</button>';
            if(($inicio_original-1000) > 0)
                $btns .= '<button onclick="javascript: get_bilhetes(\''.($inicio_original-1000).','.($inicio_original).'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-left">Página Anterior</button>';
            else
                $btns .= '<button onclick="javascript: get_bilhetes(\'0,1000\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-left">Página Anterior</button>';

            $btns .= '<button onclick="javascript: get_bilhetes(\''.$inicio.','.($inicio+1000).'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-right">Próxima Página</button>';

            echo '<div class="clearfix"></div><div style="margin-top:20px; margin-bottom:20px;" class="col-lg-12 form-group text-right">
                '.$btns.'
                </div>';



            /*if($inicio_original == 0)
                $btns .= '<button onclick="javascript: get_bilhetes(\'1000,2000\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-right">Próxima Página</button>';
            else { 

                $pagina = ceil(($inicio_original+1000)/1000);

                $first = 1000 * ($pagina-1);
                $last = 1000 * $pagina;
                $btns .= '<button onclick="javascript: get_bilhetes(\''.$first.','.$last.'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-left">Página Anterior</button>';

                $first = ($pagina+1) * 1000;
                $last = ($pagina+2) * 1000;
                $btns .= '<button onclick="javascript: get_bilhetes(\''.$first.','.$last.'\', '.$rifa.', \''. $_GET['tipo'].'\');" class="btn btn-primary pull-right">Próxima Página</button>';

            }

            echo '<div class="clearfix"></div><div style="margin-top:20px; margin-bottom:20px;" class="col-lg-12 form-group text-right">
                '.$btns.'
                </div>';

            
            break;
        }*/
        

    }

    //var_dump($bilhetes_disponiveis_revendedor);

    die('<div class="clearfix"></div>');

}

$range = array();
if($travado) {
    foreach($bilhetes_disponiveis_revendedor as $bil=>$void)
        $range[] = $bil;
} else {
    for ($inicio; $inicio < $fim; $inicio++)
        $range[] = $inicio;
}
foreach($range as $inicio) {
//for ($inicio; $inicio < $fim; $inicio++) {

    if($selecao_2_etapas && !$bilhetes_da_etapa[$inicio])
        continue;

    if ($dezenabolao && !$travado)
        $numero_bilhete = str_pad($inicio, 2, "0", STR_PAD_LEFT);
    else
        $numero_bilhete = str_pad($inicio, $maxbilhettes, "0", STR_PAD_LEFT);

    // se for dezena bolao, não precisa descobrir quais bilhetes foram vendidos
    if ($dezenabolao && !$travarBilhetes && !$modoBancaOnline) {
        $bilVendidos = array();
        $bilhetesReservados = array();
    }

    if (in_array($inicio, $bilVendidos)) {
        if(!($travarBilhetes && $rifa_dezena_bolao)) {
            if ($rifa_max_bilhetes == 100 && $rifa_dezena_bolao == 0 && $origem == NULL) { ?>
                <div class="col-xs-2 bilhete_holder tooltip-custom col-sm-4 col-md-2 col-lg-1">
                <?php if($modoBancaOnline && isset($tooltip[$inicio]) && is_array($tooltip[$inicio]) && count($tooltip[$inicio]) > 0){ ?><span class="tooltiptext"><?= implode('<br>', $tooltip[$inicio]); ?></span><?php } ?>
                    <div class="col-lg-12 bilhete-vendido">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
            <?php } else { ?>

                <div style="margin-bottom:10px;" class="bilhete_holder col-xs-3 col-sm-4 col-md-2 col-lg-1">
                    <div class="col-lg-12 bilhete-vendido">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
            <?php 
            } 
        }
    } else if (in_array($inicio, $bilhetesReservados)) {

        $errMsg = "Este bilhete não foi vendido, mas está reservado.";
        if($modoBancaOnline)
            $errMsg = "Este bilhete já atingiu o máximo de apostas.";
               
               if ($rifa_max_bilhetes == 100 && $rifa_dezena_bolao == 0 && $origem == NULL) { ?>
                <div class="col-xs-2 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                    <div onclick="alert('<?= $errMsg; ?>');" class="col-lg-12 teste bilhete-reservado">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
                <?php } else { ?>
                <div style="margin-bottom:10px;" class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                    <div onclick="alert('<?= $errMsg; ?>');" class="col-lg-12 bilhete-reservado">
                        <input class="esconder" type="checkbox"><?php echo $numero_bilhete; ?>
                    </div>
                </div>
                <?php 
            
            } ?>
    <?php
    } else {

        $parcial = '';
        if(isset($parciais) && in_array($inicio, $parciais))
            $parcial = "venda_parcial";

        if ($rifa_max_bilhetes == 100 && $rifa_dezena_bolao == 0 && $origem == NULL) : ?>
            <div onclick="javascript: checkar('<?php echo $numero_bilhete; ?>');" class="col-xs-2 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                <div onclick="scrollOnClick()" id="holder<?php echo $numero_bilhete; ?>" class="col-lg-12 <?= $parcial; ?> bilhete <?php if ($_SESSION['bilhete' . $j] == 1 || strpos($selecionado, $numero_bilhete . ";")  !== false) echo "bilhete_selected"; ?>">
                    <input <?php if (strpos($selecionado, $numero_bilhete . ";")  !== false) echo "checked=\"checked\";" ?> class="esconder" <?php if ($_SESSION['bilhete' . $inicio] == 1) echo "checked"; ?> value="<?php echo $numero_bilhete; ?>" name="bilhete[]" id="bilhete<?php echo $numero_bilhete; ?>" type="checkbox">
                    <?php echo $numero_bilhete; ?>
                </div>
            </div>
            <?php else : ?>
            <div style="margin-bottom:10px;" onclick="javascript: checkar('<?php echo $numero_bilhete; ?>');" class="col-xs-3 bilhete_holder col-sm-4 col-md-2 col-lg-1">
                <div id="holder<?php echo $numero_bilhete; ?>" class="col-lg-12 bilhete <?php if ($_SESSION['bilhete' . $j] == 1 || strpos($selecionado, $numero_bilhete . ";")  !== false) echo "bilhete_selected"; ?>">
                    <input <?php if (strpos($selecionado, $numero_bilhete . ";")  !== false) echo "checked=\"checked\";" ?> class="esconder" <?php if ($_SESSION['bilhete' . $inicio] == 1) echo "checked"; ?> value="<?php echo $numero_bilhete; ?>" name="bilhete[]" id="bilhete<?php echo $numero_bilhete; ?>" type="checkbox">
                    <?php echo $numero_bilhete; ?>
                </div>
            </div>
        <?php 
        endif;
        ?>
<?php 
    }
}
?>
<div class="clearfix"></div>
