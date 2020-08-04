<?php
header('Content-type: application/json');
include("../../class/conexao.php");
include("../../class/function_limparNumero.php");


if (!isset($_SESSION))
    @session_start();

if (!isset($_SESSION['usuario']))
    die(json_encode(array('erro' => 1, 'msg' => "Acesso não autorizado.")));


if (isset($_POST['action'])) {

    // Action de Criar Conta Bancária
    if ($_POST['action'] === 'criar_conta' && isset($_POST['data'])) {
        parse_str($_POST['data'], $data_array);

        // Verifica se todos os dados obrigatórios foram enviados
        $check_array = ['banco_cadastro', 'agencia_cadastro', 'conta_cadastro', 'codigo_usuario_cadastro'];
        if (array_diff($check_array, array_keys($data_array))) {
            die("Requisição inválida");
        }

        $banco = $data_array['banco_cadastro'];
        $agencia = $data_array['agencia_cadastro'];
        $conta = $data_array['conta_cadastro'];
        $outras_informacoes = $data_array['outrasinfos_cadastro'];
        $nome_titular = $data_array['nome_titular_cadastro'];
        $cpf = limparNumero($data_array['cpf_titular_cadastro']);
        $codigo_usuario = $data_array['codigo_usuario_cadastro'];

        // Verifica as informações vazias para inserir no banco
        // Inserir NULL é mais perfomático do que uma string vazia ''
        $outras_informacoes = !empty($outras_informacoes) ? "'$outras_informacoes'" : "NULL";
        $nome_titular = !empty($nome_titular) ? "'$nome_titular'" : "NULL";
        $cpf = !empty($cpf) ? "'$cpf'" : "NULL";


        $response_array = array();

        if (!is_numeric($codigo_usuario) || !isset($codigo_usuario)) {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Código de usuário inválido';
            die(json_encode($response_array));
        }

        $sql = "INSERT INTO tbl_conta (banco, agencia, conta, outrasinfos, nome_completo, cpf, usuario) VALUES('$banco', '$agencia', '$conta', $outras_informacoes, $nome_titular, $cpf,'$codigo_usuario')";
        $insert = $mysqli->query($sql) or die($mysqli->error);

        if ($insert) {
            $response_array['status'] = 'success';
            $response_array['message'] = 'Conta bancária cadastrada com sucesso';
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Um erro ocorreu ao inserir uma conta bancária';
        }
        die(json_encode($response_array));
    }
    // Fim action criar conta

    // Action get contas
    if ($_POST['action'] === 'get_contas' && isset($_POST['data'])) {

        $codigo_usuario = $_POST['data'];
        $sql = "SELECT * FROM tbl_conta WHERE usuario = '$codigo_usuario'";
        $select = $mysqli->query($sql) or die($mysqli->error);

        while ($row = $select->fetch_assoc()) {
            $contas_bancarias[] = $row;
        }

        //var_dump($contas_bancarias);

        if ($select) {
            $response_array['status'] = 'success';
            $response_array['message'] = 'Dados bancários recuperados com sucesso';
            $response_array['result'] = $contas_bancarias;
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Nada a fazer';
        }
        die(json_encode($response_array));
    }
    // Fim Action get contas

    // Get conta por código
    if ($_POST['action'] === 'get_conta' && isset($_POST['data'])) {

        $codigo_conta = $_POST['data'];
        $sql = "SELECT * FROM tbl_conta WHERE codigo = '$codigo_conta'";
        $select = $mysqli->query($sql) or die($mysqli->error);

        if ($select) {
            $conta = $select->fetch_assoc();
            // Transforma NULL em string vazia para o front end
            $conta['outrasinfos'] = !is_null($conta['outrasinfos']) ? "$conta[outrasinfos]" : '';
            $conta['cpf'] = !is_null($conta['cpf']) ? "$conta[cpf]" : '';
            $conta['nome_completo'] = !is_null($conta['nome_completo']) ? "$conta[nome_completo]" : '';

            $response_array['status'] = 'success';
            $response_array['message'] = 'Dados bancários recuperados com sucesso';
            $response_array['result'] = $conta;
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Não foi possível recuperar os dados da conta bancária de código ' . $codigo_conta;
        }
        die(json_encode($response_array));
    }


    // Action delete conta
    if ($_POST['action'] === 'delete_conta' && isset($_POST['conta']) && isset($_POST['usuario'])) {

        $codigo_conta = $_POST['conta'];
        $codigo_usuario = $_POST['usuario'];

        if ($_SESSION['usuario'] != $codigo_usuario) {
            die("Você não é dono desta conta bancária");
        } else {
            $sql = "DELETE FROM tbl_conta WHERE usuario = '$codigo_usuario' AND codigo = '$codigo_conta'";
            $delete = $mysqli->query($sql) or die($mysqli->error);

            if ($delete) {
                $response_array['status'] = 'success';
                $response_array['message'] = 'Dados bancários removidos com sucesso';
            } else {
                $response_array['status'] = 'error';
                $response_array['message'] = 'Não foi possível excluor o registro';
            }
            die(json_encode($response_array));
        }
    }
    // Fim Action delete conta


    // Action de editar a conta bancária
    if ($_POST['action'] === 'edit_conta' && isset($_POST['data']) && isset($_POST['usuario']) && isset($_POST['conta'])) {

        $codigo_usuario = $_POST['usuario'];
        $codigo_conta = $_POST['conta'];

        if ($_SESSION['usuario'] != $codigo_usuario) {
            die("Você não é dono desta conta bancária");
        }

        parse_str($_POST['data'], $data_array);

        // Verifica se todos os dados obrigatórios foram enviados
        $check_array = ['banco_edit', 'agencia_edit', 'conta_edit'];
        if (array_diff($check_array, array_keys($data_array))) {
            die("Requisição inválida");
        }

        $banco = $data_array['banco_edit'];
        $agencia = $data_array['agencia_edit'];
        $conta = $data_array['conta_edit'];
        $outras_informacoes = $data_array['outrasinfos_edit'];
        $nome_titular = $data_array['nome_titular_edit'];
        $cpf = limparNumero($data_array['cpf_titular_edit']);


        // Verifica as informações vazias para inserir no banco
        // Inserir NULL é mais perfomático do que uma string vazia ''
        $outras_informacoes = !empty($outras_informacoes) ? "'$outras_informacoes'" : "NULL";
        $nome_titular = !empty($nome_titular) ? "'$nome_titular'" : "NULL";
        $cpf = !empty($cpf) ? "'$cpf'" : "NULL";

        $response_array = array();

        if (!is_numeric($codigo_usuario) || !isset($codigo_usuario)) {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Código de usuário inválido';
            die(json_encode($response_array));
        }

        $sql = "UPDATE tbl_conta SET banco = '$banco', agencia = '$agencia', conta = '$conta', outrasinfos = $outras_informacoes, nome_completo = $nome_titular, cpf = $cpf WHERE (codigo = '$codigo_conta' AND usuario = '$codigo_usuario')";
        $update = $mysqli->query($sql) or die($mysqli->error);

        if ($update) {
            $response_array['status'] = 'success';
            $response_array['message'] = 'Conta bancária atualizada com sucesso';
        } else {
            $response_array['status'] = 'error';
            $response_array['message'] = 'Um erro ocorreu ao atualizar a conta bancária';
        }
        die(json_encode($response_array));
    }
}
