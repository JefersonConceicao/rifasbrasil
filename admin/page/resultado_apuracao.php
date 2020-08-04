<?php



if(!$_SESSION ) @session_start();



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



if($_POST['email'] && $_POST['html']){



      $headers = "Content-Type: text/html; charset=utf-8\r\n";



      mail($_POST['email'], "Apuração Parcial - " . $_POST['data'], $_POST['html'], $headers);

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

                  <label for="">Enviar para o e-mail à seguir: </label>

                  <input type="email" name="email" id="email" required class="form-control">

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

            var conteudo = $('#all').html();

            var email    = $('#email').val();

            var data     = "<?= $_POST['vencimento']; ?>";

            $.post('page/resultado_apuracao.php', {html:conteudo, email:email, data:data}).done(

                  function(r){

                        console.log(r);

                        alert("Apuração enviada por e-mail. Verifica sua caixa de SPAM e Lixeira, se não encontrá-la.");

                  }).fail(

                  function(r){

                        console.log(r);

                        alert("Apuração enviada por e-mail. Verifica sua caixa de SPAM e Lixeira, se não encontrá-la.");

                  });



      }

</script>

