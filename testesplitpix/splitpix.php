<?php
        $valor                 = 1;
        $porcentagem_comissao  = 10;
        $descricao             = "Pgto GORJETA";
        $email_cliente         = "gspimenta@gmail.com";
        $referencia_externa    = "TESTE-NALDO";
        $url_notificacao       = "https://rifasbrasil.com.br/testesplitpix";
        $comissao_vendedor     = $valor * ($porcentagem_comissao / 100);
        $valor_loja            = floatval(number_format($valor - $comissao_vendedor, 2, '.', ''));
        $access_token_loja     = "APP_USR-248601370298676-051200-49c692bd4ba507dd22534d345b00a103-12331968";
        $access_token_vendedor = "APP_USR-6022611950569056-052120-e879d38d641bf8bdf9705b38d011af9d-1460641900";
        $sponsor_id_loja       = "12331968";

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "transaction_amount": '.$valor.',
            "description": "'.$descricao.'",
            "payment_method_id": "pix",
            "payer": {
                "email": "'.$email_cliente.'"
            },
            "binary_mode": true,
            "application_fee": '.$valor_loja.',
            "external_reference": "'.$referencia_externa.'",
            "notification_url": "'.$url_notificacao.'",
            "additional_info": {
                "items": [
                    {
                        "id": "1",
                        "title": "'.$descricao.'",
                        "description": "'.$descricao.'",
                        "quantity": 1,
                        "unit_price": '.$valor.'
                    }
                ]
            },
            "sponsor_id": '.$sponsor_id_loja.'
        }',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$access_token_vendedor,
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        echo '<pre>';
        print_r(json_decode($response));

        $dados_payment = json_decode($response);
        $qrcodepix     = "data:image/jpeg;base64,{$dados_payment->point_of_interaction->transaction_data->qr_code_base64}";
        echo "<img width='200' src='{$qrcodepix}' />";