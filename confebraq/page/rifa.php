<?php







include("class/conexao.php");







$subpagina = explode("/", $_GET['p']);



if($subpagina[1] > 0){



  echo "<script>location.href='http://rifasbrasil.com.br/confebraq/index.php?p=rifa&codigo=".$subpagina[1]."';</script>";



}











if(!isset($_SESSION[revendedor]))



  $_SESSION[revendedor] = NULL;







if(isset($_POST[bilhetes_selecionados])){







  $bilhete = explode(";", $_POST[bilhetes_selecionados]);



  unset($bilhete[count($bilhete)-1]);







  if(count($bilhete) == 0){



    echo "<script>";



    echo "alert('Selecione os bilhetes que deseja adquirir');";



    echo "</script>";



  }else{











  // Limpar Carrinho



    if(is_array($_SESSION[carrinho]))



      foreach($_SESSION[carrinho] as $ch=>$va){







        foreach($_SESSION[carrinho][$ch] as $chave=>$valor)



          unset($_SESSION[carrinho][$ch][$chave]);







        unset($_SESSION[carrinho][$ch]);







      }







      $cod_rifa = intval($_GET['codigo']);







      if(!isset($_SESSION[carrinho]))



        $_SESSION[carrinho] = Array();







      $rifa = "";



      if(is_array($bilhete)){







        if(!isset($_SESSION[carrinho][$cod_rifa]))



          $_SESSION[carrinho][$cod_rifa] = Array();







        foreach($bilhete as $v){



          $_SESSION[carrinho][$cod_rifa][intval($v)] = true;



        }







        echo "<script>";



        echo "location.href='index.php?p=entrar&next=finalizar_pedido';";



        echo "</script>";















      }







    }







  }







  $codigo = intval($_GET['codigo']);







  $sql_rifas = "SELECT * FROM tbl_rifas WHERE rifa_cod = '$codigo' AND rifa_deletada IS NULL";



  $sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);



  $rifa = $sql_query->fetch_assoc();







// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas



  $sqlBil = "SELECT * FROM tbl_bilhetes WHERE bil_rifa = '$codigo' or (bil_rifa = '$codigo' AND bil_situacao = 'C')";



  $queryBil = $mysqli->query($sqlBil) or die($mysqli->error);



  $bil = $queryBil->fetch_assoc();



  $bilVendidos = Array();







  if($queryBil->num_rows > 0)



    do{



      $bilVendidos[] = $bil[bil_numero];



    }while($bil = $queryBil->fetch_assoc());











    ?>



    <html>



    <head>



      <script>







      function padLeft(nr, n, str){



        return Array(n-String(nr).length+1).join(str||'0')+nr;



      }







      function get_bilhetes(valor, rifa) {







        if(valor.length > 0){



          var res = valor.split(',');







          var xhttp = new XMLHttpRequest();







          xhttp.onreadystatechange = function() {







            if (xhttp.readyState == 1) {



              $("#loader2").css("display", "");



            }







            if (xhttp.readyState == 4 && xhttp.status == 200) {



             document.getElementById("desktop").innerHTML = xhttp.responseText;



             $("#loader2").css("display", "none");



           }



         };



         var selecionado = document.getElementById('bilhetes_selecionados').value;



         xhttp.open("GET", "page/get_bilhetes.php?maxbilhetes=<?php echo strlen($rifa[rifa_maxbilhetes])-1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa="+ rifa + "&selecionado="+selecionado, true);



         xhttp.send();



       }else{



        document.getElementById("desktop").innerHTML = "<div style='padding:10px;' class='col-lg-12 bg-warning'>Selecione o intervalo de bilhetes que você quer ver.</div>";



      }



    }







    function adicionar_bilhete(valor, rifa, maxbilhetes) {







      var xhttp = new XMLHttpRequest();



      var bil = document.getElementById('bilhetes_selecionados');







      xhttp.onreadystatechange = function() {







        if (xhttp.readyState == 1) {



          $("#loader").css("display", "");



        }







        if (xhttp.readyState == 4 && xhttp.status == 200) {







          var msg = "";







          if(parseInt(xhttp.responseText) <1 ){



            $('#result').removeClass('esconder');



            $('#result').removeClass('btn-success');



            $('#result').addClass('btn-danger');







          }







          if(valor >= maxbilhetes){







            $('#result').removeClass('esconder');



            $('#result').removeClass('btn-success');



            $('#result').addClass('btn-danger');



            msg = "Os bilhetes dessa rifa vão de 0 à "+ (maxbilhetes-1);







          }







          else if(parseInt(xhttp.responseText) == -2)



            msg = "Bilhete inválido";



          else if(parseInt(xhttp.responseText) == -1)



            msg = "Rifa inválida";







          else if(parseInt(xhttp.responseText) == 0)



            msg = "Bilhete indisponível";







          else if(parseInt(xhttp.responseText) == 1){











          //Limita o número de bilhetes



          var count = (bil.value.match(/;/g) || []).length;







          if(count == 5){



            alert("Você só pode adicionar 5 bilhetes por compra.");



            $("#loader").css("display", "none");



            return;



          }else{











            msg = "Bilhete adicionado";







            $('#result').removeClass('esconder');



            $('#result').removeClass('btn-danger');



            $('#result').addClass('btn-success');







            if(bil.value == "Nenhum"){



              bil.value = "";



              document.getElementById('finalizar').disabled=false;







            }







            var bfinal = padLeft(valor, <?php echo strlen($rifa[rifa_maxbilhetes])-1; ?>);







            $('#holder'+ bfinal ).addClass('bilhete_selected');



            if(document.getElementById("bilhete"+ bfinal ))



              document.getElementById("bilhete"+ bfinal ).checked = true;







            if(bil.value.indexOf( bfinal +";") > - 1)



            bil.value += "";



            else



              bil.value +=  bfinal +";";     







          }     







        }







        document.getElementById("result").innerHTML = msg;







        $("#loader").css("display", "none");



      }



    };



    



    xhttp.open("GET", "page/adicionar_bilhetes.php?rifa="+rifa+"&bilhete="+valor, true);



    xhttp.send();







  }







  function checkar(id){







    var campo_id = "bilhete" + id;



    var holder = "holder"+ id;



    var bil = document.getElementById('bilhetes_selecionados');



















    if(bil.value == "Nenhum"){



      bil.value = "";



      document.getElementById('finalizar').disabled=false;



    }



    if(document.getElementById(campo_id).checked){







      document.getElementById(campo_id).checked = false;



      $('#'+holder).removeClass('bilhete_selected');



      bil.value = bil.value.replace(id + ";", "");



    }else{







        //Limita o número de bilhetes



        var count = (bil.value.match(/;/g) || []).length;







        if(count == 5){



          alert("Você só pode adicionar 5 bilhetes por compra.");



          return;



        }







       //fim







       $('#'+holder).addClass('bilhete_selected');



       document.getElementById(campo_id).checked = true;



       bil.value += id + ';';







     }







     if(bil.value.length == 0){



      bil.value = "Nenhum";



      document.getElementById('finalizar').disabled=true;



    }



























  }



  </script>



</head>



<body>



  <?php // echo var_dump($_POST); ?>



  <section class="section-default" id="destaque">



    <div class="col-lg-10 center-block"><b>Detalhes da Rifa</b><hr>



     <div class="col-lg-4">



      <a target="_blank" href="http://rifasbrasil.com.br/confebraq/<?php echo $rifa['rifa_foto1']; ?>">



      <img src="http://rifasbrasil.com.br/confebraq/<?php echo $rifa['rifa_foto1']; ?>" alt="" class="img-responsive img-thumbnail">



    </a>



      <p></p>



      <?php if(is_file($rifa[rifa_foto2])){ ?>



      <a target="_blank" href="http://rifasbrasil.com.br/confebraq/<?php echo $rifa[rifa_foto2]; ?>">



      <img src="http://rifasbrasil.com.br/confebraq/<?php echo $rifa[rifa_foto2]; ?>" alt="" class="img-responsive col-lg-3 img-rifa img-thumbnail">



      </a>



      <?php } ?>



      <?php if(is_file($rifa[rifa_foto3])){ ?>



      <a target="_blank" href="http://rifasbrasil.com.br/confebraq/<?php echo $rifa[rifa_foto3]; ?>">



      <img src="http://rifasbrasil.com.br/confebraq/<?php echo $rifa[rifa_foto3]; ?>" alt="" class="img-responsive col-lg-3 img-rifa img-thumbnail">



      </a>



      <?php } ?>















    </div>



    <div class="col-lg-8">



      <h2><?php  echo ucwords(strtolower($rifa[rifa_titulo])); ?></h2>



      <p><?php echo DBSelect("select count(*) as t FROM tbl_bilhetes where bil_situacao != 'C' and  bil_rifa = '$codigo'", $mysqli, "t"); ?> bilhetes vendidos</p>



      <h2>R$ <?php echo number_format($rifa[rifa_valorbilhete],2,',','.'); ?></h2>



      <p>por bilhete</p>



      <?php if(strtotime($rifa['rifa_dtsorteio']) < time()){ ?>



      <button disabled class="btn btn-lg btn-default">SORTEADA</button>



      <?php }else{ ?>



      <button onclick="document.location+='#comprar';return false;" class="btn btn-lg btn-success">Comprar Bilhetes</button>



      <?php } ?>



      <p></p>



      <div class="fb-share-button" data-href="<?php echo $location; ?>" data-layout="box_count"></div>















    </div>



    <div class="clearfix"></div>











    <?php if(intval($rifa[rifa_finalizar]) == 0){ ?> 



    <div class="col-lg-3">



      <div class="spacer"></div>



      <b><i class="glyphicon glyphicon-gift"></i>  Prêmio</b><hr>



      <p><?php echo $rifa[rifa_descricao]; ?></p>



      <?php







      if($rifa[rifa_premios] > 1){







        $prem_exec = DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$rifa[rifa_cod]'", $mysqli);



        $premio = $prem_exec->fetch_assoc();



        echo "<p>";



        do{



          echo "<strong>".$premio[pre_ranking]."º Lugar:</strong> $premio[pre_descricao] <br>";



        }while($premio = $prem_exec->fetch_assoc());



        echo "</p>";







      }







      ?>



    </div>



    <?php }else{ ?>







    <div class="col-lg-3 table-responsive">



      <div class="spacer"></div>



      <b><i class="glyphicon glyphicon-gift"></i>  Vencedores</b><hr>



      <p>



    



      <?php







      if($rifa[rifa_premios] == 1 && $rifa[rifa_vencedor] > 0 ){







        $vencedor = DBSelect("SELECT usu_nome as v, usu_email, usu_celular FROM tbl_usuario where usu_cod = '$rifa[rifa_vencedor]'", $mysqli);



        



        ?><table class="table table-responsive table-bordered table-stripped">



      <tr style="font-weight:bold;">



        <td>Vencedor</td>



        <td>Prêmio</td>



        <td>Nº Bilhete</td>



      </tr>



        <tr>



          <td><?php if(strlen($vencedor[v]) > 0) echo "$vencedor[v] ($vencedor[usu_email] <!-- - $vencedor[usu_celular] -->)"; else echo "Sem ganhador"; ?></td>



          <td><?php echo $rifa[pre_descricao]; ?></td>



          <td><?php echo str_pad($rifa[rifa_bilhetepremiado], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT); ?></td>



        </tr></table>



        <?php }



        else if($rifa[rifa_premios] > 1){  ?>



        <table class="table table-responsive table-bordered table-stripped">



      <tr style="font-weight:bold;">



        <td>Vencedor</td>



        <td>Prêmio</td>



        <td>Nº Bilhete</td>



      </tr> <?php







          $exec =  DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$codigo' ORDER BY pre_ranking ASC", $mysqli);



          $premio = $exec->fetch_assoc();







          do{







            $vencedor = DBSelect("SELECT usu_nome as v, usu_email, usu_celular FROM tbl_usuario where usu_cod = '$premio[pre_vencedor]'", $mysqli);







            ?>



            <tr>



              <td>  <?php if(strlen($vencedor[v]) > 0) 



                echo $premio[pre_ranking]."º: $vencedor[v] ($vencedor[usu_email] <!-- - $vencedor[usu_celular] -->)";



                else



                echo  $premio[pre_ranking]."º: Sem ganhador"; ?></td>



              <td><?php echo $premio[pre_descricao]; ?></td>



              <td><?php echo str_pad( $premio[pre_bilhete], strlen($rifa[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT); ?></td>



            </tr>



            <?php } while( $premio = $exec->fetch_assoc()); echo "</table>"; } else echo "Sem ganhadores.<br>Bilhete premiado: ".$rifa['rifa_bilhetepremiado']."<br>Clique <a target=\"_blank\" href=\"http://loterias.caixa.gov.br/wps/portal/loterias/landing/federal/\">aqui</a> e confira o resultado oficial."; ?>



          </p>



          </div>



    <?php } ?>



        <div class="col-lg-3">



      <div class="spacer"></div>



      <b><i class="glyphicon glyphicon-pushpin"></i> Propósito da Rifa</b><hr>



      <p><?php echo $rifa[rifa_proposito]; ?></p>



    </div>



    <div class="col-lg-3">



      <div class="spacer"></div>



      <b><i class="glyphicon glyphicon-list"></i> Regulamento</b><hr>



      <p>



        <?php 



        



        echo ($rifa[rifa_regulamento]);



        ?> 



        <br><br><strong>Data do Sorteio:</strong> <?php echo date("d/m/Y", strtotime($rifa[rifa_dtsorteio])); ?></p>



      </div>



      <div class="col-lg-3">



        <div class="spacer"></div>



        <b><i class="glyphicon glyphicon-barcode"></i> Valor e Pagamento</b><hr>



        <p>



          O valor acima somente  é válido para Deposito ou Transfertencia Bancária<br>



          Para outros meios de pagamento, será aplicada a taxa de 5% + R$ 0,50 de taxa administrativa</p>



        </div>



        <?php if(intval($rifa[rifa_finalizar]) == 0 && strtotime($rifa[rifa_dtsorteio]) > time()) { ?>



        <div id="comprar" class="col-lg-12">



          <div class="spacer"></div>



          <b><i class="glyphicon glyphicon-shopping-cart"></i> Comprar Rifas</b><hr>



          <div class="col-lg-12 form-group form-inline">







            <?php







            $cod_rifa = $_GET['codigo'];



            $maxbilhetes = $rifa[rifa_maxbilhetes];







            ?>



            <form action="" method="post">



              Bilhetes Selecionados: 



              <input value="Nenhum" class="form-control" name="bilhetes_selecionados" readonly id="bilhetes_selecionados">



              <button type="submit" disabled id="finalizar" class="btn btn-success">Finalizar Compra</button>



            </form>



          </div>



          <div class="col-lg-12"><label for="">Adicionar um Bilhete Específico</label></div>



          <div class="col-lg-12 form-group form-inline">



            Digite o número do bilhete desejado:



            <input class="form-control" id="bil_busca" name="bil_busca" type="text">



            <button onclick="javascript: adicionar_bilhete(document.getElementById('bil_busca').value, <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);" class="btn btn-primary">Adicionar</button> 



            <div id="result" class="esconder btn"></div>



            <img id="loader" style="display:none;" src="img/loader.gif" height="25px" alt="">



          </div>



          <div class="col-lg-12"><label for="">Adicionar com base no Intervalo de Bilhetes</label></div>



          <div class="col-lg-12 form-group form-inline">







            Veja todos os bilhetes no intervalo à seguir: 



            <select onchange="javascript: get_bilhetes(this.value, <?php echo $cod_rifa; ?>);" class="form-control" name="" id="">











              <option value=""></option>



              <?php 







              $range = 60;







              $i = 0;



              $f = $range;







              $lim = $maxbilhetes- 1;







              while($f <= $lim){ 







                ?>



                <option value="<?php echo "$i,$f"; ?>" ><?php echo str_pad($i, strlen($maxbilhetes)-1, "0", STR_PAD_LEFT)." à ".str_pad(($f-1), strlen($maxbilhetes)-1, "0", STR_PAD_LEFT); ?></option>



                <?php 



                $i += $range;



                $f += $range;











              } 







              if($i < $lim){







                $i = $i;







                ?>



                <option value="<?php echo "$i,".($lim+1); ?>" ><?php echo "$i à $lim"; ?></option>



                <?php 











              } 



              ?>







            </select>



            <img id="loader2" style="display:none;" src="img/loader.gif" height="25px" alt="">



          </div>







          <div id="desktop" class="col-lg-12">



          </div>



        </div><?php } ?>



















        <div class="col-lg-12">



          <div class="spacer"></div>



          <b>Comentários</b><hr>







          <div class="fb-comments" data-href="<?php echo $location; ?>" data-width="100%" data-numposts="5"></div>



        </div>







      </div>







      <div class="clearfix"></div>



    </section>



  </body>







  </html>



