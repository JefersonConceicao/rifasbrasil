<?php

include('../../class/conexao.php');

switch ($_SERVER['SERVER_NAME']):

    // localhost
  case 'localhost':
    $configs = require_once('../../config/local.config.php');
    break;

  default:
    $configs = require_once('../../config/production.config.php');
    break;
endswitch;

if (!isset($configs)) {
  $response_array['status'] = 'error';
  $response_array['message'] = 'Um erro ocorreu ao carregar os arquivos de configuração';
  die(json_encode($response_array));
}

// 1) Chave do revendedor 
// 2) Chave da rifa desejada 

if (isset($_POST['data'])) {
  $credenciais = json_decode(($_POST['data']));

  $revendendor_responsavel = $credenciais->revendedor_id;
  $rifa_id = $credenciais->rifa_id;

  $hash = md5($rifa_id . $revendendor_responsavel);

  $url = ('' . $configs['default_admin_url'] . '/controller/loginWithoutAuth.php?id=' . $hash . '');

  $check = $mysqli->query("SELECT * FROM usuarios_sem_login WHERE hash = '$hash'") or die($mysqli->error);
  $check = $check->fetch_assoc();

  // verificar se o link já existe no banco
  // se existir, retorna junto com a url formatada
  // se não, cria ele e insere no banco
  if (is_null($check)) {
    $query = $mysqli->query("INSERT INTO usuarios_sem_login (rifa_id, cod_rev, hash) VALUES ('$rifa_id', '$revendendor_responsavel', '$hash')") or die($mysqli->error);

    if ($query) {
      $response_array['status'] = 'success';
      $response_array['message'] = 'Link gerado com sucesso';
      $response_array['url'] = $url;
    } else {
      $response_array['status'] = 'error';
      $response_array['message'] = 'Um erro ocorreu ao gerar o link';
      die(json_encode($response_array));
    }
  } else {
    $url = ('' . $configs['default_admin_url'] . '/controller/loginWithoutAuth.php?id=' . $check['hash'] . '');
    $response_array['status'] = 'success';
    $response_array['message'] = 'Link retornado com sucesso';
    $response_array['url'] = $url;
  }
  die(json_encode($response_array));
}

function get_tiny_url($url)
{
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url=' . $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}
