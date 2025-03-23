<?php
header('Content-type: application/json');
include("../../class/conexao.php");
include("../../class/function_limparNumero.php");


if (!isset($_SESSION))
    @session_start();

if (!isset($_SESSION['usuario']))
    die(json_encode(array('erro' => 1, 'msg' => "Acesso não autorizado.")));




if (isset($_POST['action'])) {

    // Recupera todos os bilhetes de uma compra
    if ($_POST['action'] === 'recuperar_bilhetes' && isset($_POST['codigo_compra'])) {
        $codigo_compra = $_POST['codigo_compra'];

        $sql = "SELECT bil.bil_numero, bil.bil_aposta, rif.rifa_maxbilhetes, rif.banca_online, rif.multiplicador FROM tbl_bilhetes bil, tbl_rifas rif  WHERE bil.bil_compra = '$codigo_compra' AND rif.rifa_cod = bil.bil_rifa";
        $select = $mysqli->query($sql);

        $extraInfo = array(
            'aposta' => 0,
            'possivel_premiacao' => 0
        );

        while($data = $select->fetch_assoc()) {
            $max_bilhetes = $data['rifa_maxbilhetes'];
            $modoBancaOnline = $data['banca_online'];
            $multiplicador = $data['multiplicador'];

            $bilhetes[] = $data['bil_numero'];

            if($modoBancaOnline && $multiplicador) {
                $extraInfo['aposta'] += $data['bil_aposta'];
                $extraInfo['possivel_premiacao'] += $data['bil_aposta'] * $multiplicador;
            }
        }

        if ($select) {
            if($extraInfo && $extraInfo['aposta']) {
                $extraInfo['aposta'] = "R$ " . number_format($extraInfo['aposta'], 2, ',', '.');
                $extraInfo['possivel_premiacao'] = "R$ " . number_format($extraInfo['possivel_premiacao'], 2, ',', '.');
            }
            $response_array['status'] = 'success';
            $response_array['message'] = 'Bilhetes recuperados com sucesso';
            $response_array['result'] = array($bilhetes);
            $response_array['banca_online'] = $extraInfo;
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Não foi possível recuperar os bilhetes';
        }
        die(json_encode($response_array));
    }

    // Marca os bilhetes como pagos
    if ($_POST['action'] === 'marcar_como_pago' && isset($_POST['codigo_compra'])) {
        $codigo_compra = $_POST['codigo_compra'];

        $sql = "UPDATE tbl_compra SET comp_situacao = '3' WHERE comp_cod ='$codigo_compra'";
        $update = $mysqli->query($sql) or die($mysqli->error);

        if ($update) {
            $response_array['status'] = 'success';
            $response_array['message'] = 'Bilhete(s) marcado(s) como pago com succeso!';
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Um erro ocorreu ao marcar os bilhetes como pagos';
        }
        die(json_encode($response_array));
    }

    // Cancela uma compra
    if ($_POST['action'] === 'cancelar_compra' && isset($_POST['codigo_compra'])) {
        $codigo_compra = $_POST['codigo_compra'];

        $sql = "UPDATE tbl_compra SET comp_situacao = '7' WHERE comp_cod ='$codigo_compra'";
        $update = $mysqli->query($sql) or die($mysqli->error);

        if ($update) {
            $response_array['status'] = 'success';
            $response_array['message'] = 'Compra cancelada com succeso!';
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Um erro ocorreu ao cancelar a compra';
        }
        die(json_encode($response_array));
    }

    // Recupera todos os bilhetes disponiveis de uma RIFA
    if ($_POST['action'] === 'recuperar_bil_disponiveis' && isset($_POST['codigo_rifa'])) {
        $codigo_rifa = $_POST['codigo_rifa'];

        $sql = "SELECT rifa_maxbilhetes, banca_online, multiplicador FROM tbl_rifas WHERE rifa_cod = '$codigo_rifa'";
        $select = $mysqli->query($sql) or die($mysqli->error);
        $info_rifa = $select->fetch_object();

        $max_bilhetes = $info_rifa->rifa_maxbilhetes;
        $modoBancaOnline = $info_rifa->banca_online;
        $multiplicador = $info_rifa->multiplicador;
        $extraInfo = array(
            'aposta' => 0,
            'possivel_premiacao' => 0
        );

        // A consulta abaixo verifica quais rifas já foram vendidas/reservadas/
        $sqlBil = "SELECT
            bilhetes.bil_rifa,
            bilhetes.bil_numero,
            bilhetes.bil_compra,
            bilhetes.bil_aposta,
            compra.comp_cod
        FROM
            tbl_bilhetes bilhetes
        INNER JOIN tbl_compra compra ON
            bilhetes.bil_compra = compra.comp_cod
        WHERE
        ((bilhetes.bil_numero < '$max_bilhetes') AND(bilhetes.bil_numero >= '0')) AND (bilhetes.bil_rifa = '$codigo_rifa') AND compra.comp_situacao != '7'";

        $queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
        $bil = $queryBil->fetch_assoc();
        $bilhetesIndisponiveis = array();

        if ($queryBil->num_rows > 0) {
            do {
                if($modoBancaOnline && $multiplicador) {
                    $extraInfo['aposta'] += $bil['bil_aposta'];
                    $extraInfo['possivel_premiacao'] += $bil['bil_aposta'] * $multiplicador;
                }

                $bilhetesIndisponiveis[] = $bil['bil_numero'];
            } while ($bil = $queryBil->fetch_assoc());
        }

        
        $seriesDisponiveis = array();
        for ($i = 0; $i < $max_bilhetes; $i++) {
            $numero_bilhete = str_pad($i, 2, "0", STR_PAD_LEFT);
            if (!in_array($i, $bilhetesIndisponiveis)) {
                $seriesDisponiveis[] = $numero_bilhete;
            }
        }

        if ($select) {
            if($extraInfo && $extraInfo['aposta']) {
                $extraInfo['aposta'] = "R$ " . number_format($extraInfo['aposta'], 2, ',', '.');
                $extraInfo['possivel_premiacao'] = "R$ " . number_format($extraInfo['possivel_premiacao'], 2, ',', '.');
            }
            $response_array['status'] = 'success';
            $response_array['message'] = 'Bilhetes disponíveis recuperados com sucesso';
            $response_array['result'] = $seriesDisponiveis;
            $response_array['banca_online'] = $extraInfo;
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Não foi possível recuperar os bilhetes disponíveis';
        }
        die(json_encode($response_array));
    }
}
