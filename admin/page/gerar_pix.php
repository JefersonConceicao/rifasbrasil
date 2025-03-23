<?php
session_start();
include("../class/conexao.php"); // Inclui a conexão com o banco de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera os códigos das rifas e o valor total
    $cod_rifas = $_POST['cod_rifa'];
    $total = $_POST['total'];

    // 1. Requisição de Login
    $url_login = "https://premiacoes.online/api/auth/login";
    //$url_login = "http://localhost:8000/api/auth/login";
    $data_login = [
        "phone" => "71983583300",
        "password" => "jeferson10"
    ];

    $ch = curl_init($url_login);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_login));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Erro na requisição de login: " . curl_error($ch);
        exit;
    }

    curl_close($ch);

    $response_data = json_decode($response, true);

    if ($response_data && isset($response_data['error']) && $response_data['error'] === false) {
        $token_access = $response_data['token_access']; // Obtém o token de acesso

        // 2. Consulta as configurações da Gerencianet
        $sql_config = "SELECT client_id, client_secret, chave_pix, documento_certificado_gerencianet FROM configuracoes LIMIT 1";
        $result_config = $mysqli->query($sql_config) or die($mysqli->error);
        $config = $result_config->fetch_assoc();

        if ($config) {
            // Dados da configuração
            $client_id = $config['client_id'];
            $client_secret = $config['client_secret'];
            $chave_pix = $config['chave_pix'];
            $caminho_relativo = $config['documento_certificado_gerencianet']; // URL completa do certificado

            $sql_repasse = "SELECT conta_repasse, cpf_conta_repasse, porcentagem_valor_repasse FROM configuracoes_repasse";
            $result_repasse = $mysqli->query($sql_repasse) or die($mysqli->error);
            $repasses = [];
            
            while ($row = $result_repasse->fetch_assoc()) {
                // Verifica se é CPF ou CNPJ
                $documento = strlen($row['cpf_conta_repasse']) === 11 ? 'cpf' : 'cnpj';
        
                $repasses[] = [
                    "tipo" => "porcentagem",
                    "valor" => number_format($row['porcentagem_valor_repasse'], 2, '.', ''),
                    "favorecido" => [
                        $documento => $row['cpf_conta_repasse'],
                        "conta" => $row['conta_repasse']
                    ]
                ];
            }

            // Dados do devedor (exemplo)
            $nome_devedor = "Jeferson Conceição de Oliveira"; // Substitua pelo nome real
            $cpf_devedor = "06812825506"; // Substitua pelo CPF real

            // 3. Requisição para gerar o PIX
            $url_gerar_pix = "https://premiacoes.online/api/generatePaymentGerenciaNet";
            //$url_gerar_pix = "http://localhost:8000/api/generatePaymentGerenciaNet";
            //$url_base = "http://" . $_SERVER['HTTP_HOST'] . "/";
            $url_base = "https://" . 'rifasbrasil.com.br' . "/";
            $certificado_url = $url_base . str_replace('../', '', $caminho_relativo);

            $data_pix = [
                "certificate" => $certificado_url, // URL completa do certificado
                "client_id" => $client_id,
                "client_secret" => $client_secret,
                "chave_pix" => $chave_pix,
                "valor_pix" => number_format($total, 2, '.', ''), // Valor formatado com 2 casas decimais
                "nome_devedor" => $nome_devedor,
                "cpf_devedor" => $cpf_devedor,
                'cod_rifas' => $cod_rifas,
                'repasses' => $repasses 
            ];

            $ch = curl_init($url_gerar_pix);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_pix));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer $token_access" // Passa o token como Bearer
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo "Erro na requisição: " . curl_error($ch);
                exit;
            }

            curl_close($ch);

            // Decodifica a resposta JSON
            $response_data = json_decode($response, true);

            $codePIX = null;
            $qrCodeBase64 = null;

            if (isset($response_data['codePIX']) && isset($response_data['qrCode'])) {
                $codePIX = $response_data['codePIX'];
                $qrCodeBase64 = $response_data['qrCode'];
            }
        } else {
            echo "<p>Erro: Configurações da Gerencianet não encontradas.</p>";
            exit;
        }
    } else {
        echo "<p>Erro no login: " . ($response_data['msg'] ?? 'Resposta inválida') . "</p>";
        exit;
    }
} else {
    echo "<p>Nenhum dado recebido.</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento via PIX</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .qr-code {
            margin-bottom: 20px;
        }

        .pix-code {
            margin-top: 20px;
        }

        .pix-code input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
        }

        .pix-code button {
            margin-top: 10px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .pix-code button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <?php if (!empty($qrCodeBase64)) { ?>
        <div class="container">
            <h1>Pagamento via PIX</h1>
            <div class="qr-code">
                <img src="<?php echo $qrCodeBase64; ?>" alt="QR Code PIX">
            </div>
        <?php } ?>

        <?php if (!empty($codePIX)) { ?>
            <div class="pix-code">
                <p>Pix Copia e Cola:</p>
                <input type="text" id="pixCode" value="<?php echo $codePIX; ?>" readonly>
                <button onclick="copyPixCode()">Copiar Código</button>
            </div>
        </div>
    <?php } ?>

    <script>
        function copyPixCode() {
            // Seleciona o campo de texto
            var copyText = document.getElementById("pixCode");
            // Seleciona o conteúdo do campo
            copyText.select();
            copyText.setSelectionRange(0, 99999); // Para dispositivos móveis
            // Copia o texto selecionado
            document.execCommand("copy");
            // Alerta o usuário que o texto foi copiado
            alert("Código PIX copiado: " + copyText.value);
        }
    </script>
</body>

</html>