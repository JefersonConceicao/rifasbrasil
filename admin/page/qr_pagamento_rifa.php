<?php
$idRifaContext = $_GET['rifa_id'];

if (empty($idRifaContext)) {
    var_dump("Parametros inválidos");
    exit;
}

$arrayRequest = $_GET;
$stringJsonArrayRequest = json_encode($arrayRequest);

$sql_code = "INSERT INTO tbl_configuracao_pagamento_rifa (
    descricao_config_pagamento,
    id_rifa
) VALUES (
   '{$stringJsonArrayRequest}',
   '{$idRifaContext}'
)";

$mysqli->query($sql_code) or die($mysqli->error);

$valorTotalPagamento = null;
$valorTaxaTarifa = null;
$numeroVendedor = null;

$stringHeader = "";
$stringCalculoTaxas = "";


if (isset($arrayRequest['tipo_pix']) && $arrayRequest['tipo_pix'] == "grafica") {
    $valorTotalPagamento = 100;
    $valorTaxaTarifa = 10;
    $numeroVendedor = "71989517152";

    $stringHeader = "Valor Total + Taxas";
    $stringCalculoTaxas = "$valorTotalPagamento = " . ($valorTotalPagamento);
} else {
    if (isset($arrayRequest['tiposValores']) && $arrayRequest['tiposValores'] == 'adicionarValor') {
        $valorTotalPagamento = 350;

        if (empty($arrayRequest['valor_pix'])) {
            echo "Por favor preencha o valor da transação";
            exit;
        }

        $valorAdicional = (float)$arrayRequest['valor_pix'];
        $valorTaxaTarifa = 110;

        $stringHeader = "Valor Pagamento + Valor Adicional + Taxas";

        $valorTotal = $valorTotalPagamento + $valorAdicional;
        $stringCalculoTaxas = "$valorTotalPagamento + $valorAdicional";
        $valorTotalPagamento = $valorTotalPagamento + $valorAdicional;
    } else {
        $valorTotalPagamento = 350;
        $valorTaxaTarifa = 100;

        $valorTotal = $valorTotalPagamento;
        $stringHeader = "Valor Pagamento + Taxas";
        $stringCalculoTaxas = "$valorTotalPagamento = " . $valorTotal;
    }

    if (isset($arrayRequest['tipoTaxa']) && $arrayRequest['tipoTaxa'] == "BPN") {
        $valorTotalPagamento = $valorTotalPagamento + 50;

        $stringCalculoTaxas .= " + 50 = " . ($valorTotal + 50);
    }

    if (isset($arrayRequest['tipoTaxa']) && $arrayRequest['tipoTaxa'] == "BPA") {
        $valorTotalPagamento = $valorTotalPagamento + 25;

        $stringCalculoTaxas .= " + 25 = " . ($valorTotal + 50);
    }
}

$urlLoginRifasBrasil = "http://premiacoes.online/api/auth/login";
$data = array(
    'phone' => '71983583300',
    'password' => 'jeferson10',
);

$dataLoginRifasBrasil = curlRequestPost($urlLoginRifasBrasil, json_encode($data));
$dados = json_decode($dataLoginRifasBrasil, true);

if (!empty($dados['token_access'])) {
    $urlGerarPagamento = 'http://premiacoes.online/api/generatePayment';
    $data = array(
        'nome_comprador' => "Jeferson Conceição de Oliveira",
        'cpf_comprador' => "06812825506",
        'id_rifa_antigo' => $idRifaContext,
        'valor_transacao' => $valorTotalPagamento,
        'valor_repasse' => $valorTaxaTarifa,
        'numero_rifeiro' => $numeroVendedor
    );

    $dataGerarPagamento = curlRequestPost(
        $urlGerarPagamento,
        json_encode($data),
        $dados['token_access']
    );

    $dadosQrCode = json_decode($dataGerarPagamento, true);
}

function curlRequestPost($url, $data, $token = null)
{
    $curl = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    );

    if (!empty($token)) {
        array_push($headers, 'Authorization: Bearer ' . $token);
    }

    // Set the necessary options for the cURL session
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url, // URL to send the request to
        CURLOPT_RETURNTRANSFER => true, // Return the response as a string
        CURLOPT_POST => true, // Send as POST request
        CURLOPT_POSTFIELDS => $data, // Attach the data
        CURLOPT_HTTPHEADER => $headers
    ));

    // Execute the cURL session and store the response
    $response = curl_exec($curl);

    // Check for errors
    if ($response === false) {
        // Print error information if any
        $response = 'cURL Error: ' . curl_error($curl);
    }

    // Close the cURL session
    curl_close($curl);
    return $response;
}
?>
<style>
</style>

<div class="container text-center">
    <div class="row">
        <?php if (!empty($dadosQrCode)) { ?>
            <img src="data:image/png;base64,<?= $dadosQrCode['qr_code_base_64_image'] ?>" width="250" height="250" />

            <div class="row mt-2">
                <p> <?= $stringHeader ?></p>
                <p> <?= $stringCalculoTaxas ?? "" ?> </p>
            </div>

            <div style="margin-top:3%">
                <label> Código Cópia e Cola </label>
                <input id="inputCopiaCola" type="text" class="form-control" value="<?= $dadosQrCode['qr_code'] ?>" />
            </div>

            <div style="margin-top:3%">
                <button class="btn btn-primary" id="copiarCodigo"> Copiar Código </button>
            </div>
        <?php } else { ?>
            <p> Não foi possível gerar o QRCODE </p>
        <?php } ?>
    </div>

    <div class="row" style="margin-top:3%;">
        <div class="col-md-12">
            <div class="alert alert-warning">
                Efetue o pagamento apontando para o pix acima para a liberação da rifa
                <small> A rifa só será exibida na listagem após o pagamento. </small>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script>
    $("#copiarCodigo").on("click", function(e) {
        e.preventDefault();

        document.getElementById("inputCopiaCola").select();
        document.execCommand("copy");

        alert("Código copiado");
    });
</script>