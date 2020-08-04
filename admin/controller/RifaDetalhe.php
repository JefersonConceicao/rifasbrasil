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

        $sql = "SELECT bil_numero FROM tbl_bilhetes WHERE bil_compra = '$codigo_compra'";
        $select = $mysqli->query($sql);

        while ($row = $select->fetch_all()) {
            $bilhetes[] = $row;
        }

        if ($select) {
            $response_array['status'] = 'success';
            $response_array['message'] = 'Bilhetes recuperados com sucesso';
            $response_array['result'] = $bilhetes;
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

        $sql = "SELECT rifa_maxbilhetes FROM tbl_rifas WHERE rifa_cod = '$codigo_rifa'";
        $select = $mysqli->query($sql) or die($mysqli->error);
        $info_rifa = $select->fetch_object();

        $max_bilhetes = $info_rifa->rifa_maxbilhetes;

        // A consulta abaixo verifica quais rifas já foram vendidas/reservadas/
        $sqlBil = "SELECT
            bilhetes.bil_rifa,
            bilhetes.bil_numero,
            bilhetes.bil_compra,
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
            $response_array['status'] = 'success';
            $response_array['message'] = 'Bilhetes disponíveis recuperados com sucesso';
            $response_array['result'] = $seriesDisponiveis;
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Não foi possível recuperar os bilhetes disponíveis';
        }
        die(json_encode($response_array));
    }
}
