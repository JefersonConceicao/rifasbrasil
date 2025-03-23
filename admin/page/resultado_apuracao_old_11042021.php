<?php



if(!$_SESSION ) @session_start();

function curl_post_contents($url, $params, $timeout = 10) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, false);

    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36');
    curl_setopt($c, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);

    if(1) {
        // CURLOPT_VERBOSE: TRUE to output verbose information. Writes output to STDERR, 
        // or the file specified using CURLOPT_STDERR.
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
    }

    $result = curl_exec($ch);

    if (!$result) {
        printf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
               htmlspecialchars(curl_error($ch)));

        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
    }

    $err = curl_error($ch);
    echo $err;
    curl_close($ch);

    return $result;

}


function enviarWhatsapp ($telefone, $mensagem) {
      $url = 'https://api.z-api.io/instances/38B4EC3BCB08802E919C0A7940722ACE/token/E686C67AC9151520C0D3479D/send-messages';
      $ch = curl_init($url);

      $data = array(
          'phone' => '55' . preg_replace("/[^0-9]/", "", $telefone),
          'message' => $mensagem
      );

      $res =  curl_post_contents($url, $data, 60);
      
}

function onlyNumbers($str){



      $final = '';

      for($k = 0; $k < strlen($str); $k++){



            if(is_numeric($str[$k])) $final .= $str[$k];



      }

      return $final;



}





if($_POST['sms'] && $_POST['tel']){



      include("../../class/function_enviarSMS.php");

      $res = enviarSMS(array('+55' . onlyNumbers($_POST['tel'])), $_POST['sms'], 1);

      var_dump($res);

      die();



}



if(isset($_POST['enviarWhatsapp'])) {

      include("../../class/conexao.php");

      $venc        = implode('-', array_reverse(explode('/', $_POST['vencimento'])));
      $sql         = "SELECT rifa_cod, rifa_premios, rifa_titulo, rifa_dtsorteio, rifa_maxbilhetes, usu_celular FROM tbl_rifas, tbl_usuario WHERE rifa_dtsorteio = '$venc' and usu_cod = rifa_dono and rifa_deletada is NULL and rifa_cod in (select rifa from lote)";
      $rifas       = db_select($mysqli, $sql);

      if(count($rifas) == 0)
            die("Nenhuma Rifa Loteada vence na data informada");
      
      $resultado = array();
      foreach($rifas as $rifa){
            $tam = (strlen($rifa['rifa_maxbilhetes'])-1)*-1;
            
            $arr_bilhetes[$rifa['rifa_cod']] = array();
            foreach($_POST['numero'] as $n){
                  $arr_bilhetes[$rifa['rifa_cod']][] = substr($n, $tam);
            }

            $lista_bilhetes = implode(';', $arr_bilhetes[$rifa['rifa_cod']]);
            $resultado[$rifa['rifa_cod']] = get_cod_alfanumerico($mysqli, $rifa['rifa_cod'], $lista_bilhetes);

            $enviar_texto = "Cód. {$rifa['rifa_cod']} | Título: {$rifa['rifa_titulo']} | Vencimento: " . implode('/', array_reverse(explode('-',$rifa['rifa_dtsorteio']))) . PHP_EOL;

            foreach($resultado[$rifa['rifa_cod']]['bilhete'] as $bil){
                  $tmp = explode('-', $bil);
                  $enviar_texto .= PHP_EOL . "$tmp[0]-Nº {$tmp[1]}";
            }
            enviarWhatsapp (preg_replace("/[^0-9]/", "", $rifa['usu_celular']), $enviar_texto);
      }
      
      die();

}



include("../class/conexao.php");



function get_cod_alfanumerico($mysqli, $rifa, $bilhetes){



      $res = trim(file_get_contents('http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php?rifa='.$rifa.'&layout=1&bilhetes='.$bilhetes));

      return json_decode($res, 1);



      //return $tmp[0]."-Nº ".$tmp[1]." <small>".$tmp[2]."</small>";



      //return $tmp[0]."-Nº ".$tmp[1]." <small>".$tmp[2]."</small>";



}





if($_POST['vencimento'] && $_POST['premio'] && is_array($_POST['numero'])){



      $venc        = implode('-', array_reverse(explode('/', $_POST['vencimento'])));

      $sql         = "SELECT rifa_cod, rifa_premios, rifa_titulo, rifa_dtsorteio, rifa_maxbilhetes, usu_celular FROM tbl_rifas, tbl_usuario WHERE rifa_dtsorteio = '$venc' and usu_cod = rifa_dono and rifa_deletada is NULL and rifa_cod in (select rifa from lote)";

      $rifas       = db_select($mysqli, $sql);



      //var_dump($rifas);

      $bilhetes = array();

      $resultad = array();



      foreach($rifas as $rifa){



            $tam = (strlen($rifa['rifa_maxbilhetes'])-1)*-1;



            $arr_bilhetes[$rifa['rifa_cod']] = array();

            foreach($_POST['numero'] as $n){

                  $arr_bilhetes[$rifa['rifa_cod']][] = substr($n, $tam);

            }



            $lista_bilhetes = implode(';', $arr_bilhetes[$rifa['rifa_cod']]);



            $resultado[$rifa['rifa_cod']] = get_cod_alfanumerico($mysqli, $rifa['rifa_cod'], $lista_bilhetes);



            //var_dump($resultado[$rifa['rifa_cod']] );



      }



}else

      die("<script>alert('Dados faltando!'); location.href='index.php?p=apuracao';</script>");



?>

<style>

label.simple{font-weight: normal;}

</style>



<div class="col-lg-12 text-center">

      <h3>Resultado Parcial na Data Selecionada</h3>

      <hr>

      <div class="col-lg-12">

            <?php if(count($rifas) == 0) { ?>

            <p>Nenhuma Rifa Loteada vence na data informada.</p>

            <div class="form-group">

                  <button type="button" onclick="location.href='index.php?p=apuracao';" class="btn btn-default">Voltar</button>

            </div>

            <?php }else{ ?>

            <div id="all">

                  <p>Mostrando resultados parciais de <?= count($rifas); ?> rifas da data <?= $_POST['vencimento']; ?></p>

                  <?php foreach($rifas as $rifa){ ?>

                  <div class="col-lg-8 center-block text-center form-group">

                        <p><b>Cód. <?= $rifa['rifa_cod'] ?> | Título: <?= $rifa['rifa_titulo']; ?> | Vencimento: <?= implode('/', array_reverse(explode('-',$rifa['rifa_dtsorteio']))); ?></b></p>

                        <p><?php 

                        $str_sms = "";

                        foreach(($resultado[$rifa['rifa_cod']]['bilhete']) as $bil){

                              $tmp = explode('-', $bil);

                              $str_sms .= "%0D%0A$tmp[0]-Nº {$tmp[1]}, ";

                              echo "$tmp[0]-Nº {$tmp[1]}<br>";

                        }?></p>

                        <p><button class="btn btn-default" onclick="javascript:location.href='sms:+55<?php echo $rifa['usu_celular']; ?>?body=Cód. <?= $rifa['rifa_cod'] ?> | Título: <?= $rifa['rifa_titulo']; ?> | Vencimento: <?= implode('/', array_reverse(explode('-',$rifa['rifa_dtsorteio']))); ?>%0D%0A<?php echo substr($str_sms, 0, -2);?>';">Enviar SMS Nativo</button> <button class="btn btn-default" onclick="javascript:enviar_sms('Cód. <?= $rifa['rifa_cod'] ?> | Título: <?= $rifa['rifa_titulo']; ?> | Vencimento: <?= implode('/', array_reverse(explode('-',$rifa['rifa_dtsorteio']))); ?>%0D%0A<?php echo substr($str_sms, 0, -2);?>', '<?php echo $rifa['usu_celular']; ?>');">Enviar SMS Servidor</button></p>

                  </div>

                  <?php } ?>

            </div>

            <div class="form-group form-inline">

                  <label for="">Enviar resultados por Whatsapp aos donos: </label>

                  <button type="button" onclick="enviar_por_email();" class="btn btn-success">Enviar</button>

            </div>

            <?php } ?>

      </div>

</div>

<script>



      function enviar_sms(txt, tel){

            $.post('page/resultado_apuracao.php', {sms:txt, tel:tel}).done(

                  function(r){

                        console.log(r);



                        if(confirm("Apuração enviada para o Telefone " + tel + ". Deseja enviar para outro número?")){

                              var ntel = prompt('Digite o novo número com DDD');

                              enviar_sms(txt, ntel);

                        }



                  }).fail(

                  function(r){

                        console.log(r);

                        alert("Falhou!");

                  });

      }

      function enviar_por_email(){

            let data = <?php echo json_encode($_POST); ?>;
            data.enviarWhatsapp = true;
            $.post('page/resultado_apuracao.php', data).done(
                  function(r){
                        console.log(r);
                        if(r.length == 0)
                              return alert("Apuração enviada por Whatsapp.");
                        return alert(r);
                  }).fail(

                  function(r){

                        console.log(r);

                        alert("Apuração enviada por e-mail. Verifica sua caixa de SPAM e Lixeira, se não encontrá-la.");

                  });



      }

</script>

