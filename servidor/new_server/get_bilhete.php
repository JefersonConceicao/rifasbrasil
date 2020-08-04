			<?php

      function str_in_array_de_num($bil, $casasDecimais = 0){
    $final = array();
      if(substr_count($bil, '-') == 1){
        $tmp = explode('-', $bil);
        $start = intval($tmp[0]);
        $end   = intval($tmp[1]);
        if($tmp[0] > $tmp[1]){
          $start = intval($tmp[1]);
          $end   = intval($tmp[0]);
        }
        for($start; $start <= $end; $start++){
          if($casasDecimais > 0)
            $final[] = str_pad($start, $casasDecimais, '0', STR_PAD_LEFT);
          else
            $final[] = $start;
        }
      }else{
        $var = explode(',', $bil);
        foreach($var as $v){
          if(is_numeric(trim($v))){
            if($casasDecimais > 0)
              $final[] = str_pad(trim($v), $casasDecimais, '0', STR_PAD_LEFT);
            else
              $final[] = intval($v);
          }
        }
      }
    
    return $final;
  }

      include("../class/conexao.php");
      include("class/Loteamento.class.php");

      $rifas  = $_GET['rifa'];
      $a = array();
      $e = array();

      if(is_array($rifas)){
        foreach($rifas as $r){
          $res = db_select($mysqli, "select embaralhar, agrupar from opcao_reserva where rifa = '$r' limit 1", 1);
          if($res['embaralhar'])
            $e[$r] = true;
          else
            $e[$r] = false;

          if($res['agrupar'])
            $a[$r] = true;
          else
            $a[$r] = false;
        }
      }else{

        $rifas = intval($rifas);

        $res = db_select($mysqli, "select embaralhar, agrupar from opcao_reserva where rifa = '$rifas' limit 1", 1);
        if($res['embaralhar'])
          $e[$rifas] = true;
        else
          $e[$rifas] = false;

        if($res['agrupar'])
          $a[$rifas] = true;
        else
          $a[$rifas] = false;
      }


      $layout = intval($_GET['layout']);

      $lot    = new Loteamento($rifas, $layout, $e, $a);
      $lot->exibirCodigo(); // ativa a exibicao do código de 6 digitos
      



      if(!$_GET['grupo']){  

        
        if($_GET['bilhete']){
          $lot->buscarBilhete($_GET['bilhete']);
        }
        elseif($_GET['bilhetes']){

          $final = array();
          foreach(explode(';', $_GET['bilhetes']) as $b){
            $final['bilhete'][] = $lot->buscarBilhete($b, false);
          }

          die(json_encode($final));

        }

      }else{

        function get_grupos($mysqli, $rifa){

          $bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
          $bilhetes = $bilhetes['rifa_maxbilhetes'];
          $grupo    = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

          if($bilhetes > 900 && $bilhetes < 1200)
            $bilhetes = 1000;
          elseif($bilhetes > 3000)
            $bilhetes = 10000;

          $grupos = ceil($bilhetes / $grupo['grupo']);

          $numero_letras = ceil($grupos/100);



          $i = 0;
          $cont = 0;
          $titulo_grupos = array();

          for($i = 1; $i <= $numero_letras; $i++){

            $letra = getNameFromNumber($i);

            for($k = 1; $k <= 100; $k++){

              $titulo_grupos[] = $letra.$k;

              $cont++;
              if($cont == $grupos)
                break;
            }

          }

          return $titulo_grupos;

        }

        $todos_grupos = get_grupos($mysqli, $_GET['rifa']);

        $res = array();
        $res['rifa'] = $_GET['rifa'];
        $res['grupo'] = strtoupper($_GET['grupo']);
        $tmp = db_select($mysqli, "select rifa_titulo from tbl_rifas where rifa_cod = '" . intval($_GET['rifa'])."' limit 1", 1);
        $res['rifa_nome'] = $tmp['rifa_titulo'];

        $res['revendedor'] = "";
        $res['bilhete'] = array();

        if(strpos($res['grupo'], ';') !== false || strpos($res['grupo'], '-') !== false ){


          if(strpos($res['grupo'], '-') !== false){

            $tmp = explode('-', $res['grupo']);
            $grupo_inicial = strtoupper($tmp[0]);
            $grupo_final   = strtoupper($tmp[1]);

            $k_s = -1;
            $k_f = -1;
            $autoriza = false;

            $res['grupo'] = "";

            foreach(get_grupos($mysqli, $_GET['rifa']) as $k=>$gr){

              if($gr == $grupo_inicial){
                $k_s = $k;
                $autoriza = true;
              }

              if($autoriza) $res['grupo'] .= $gr.";";

              if($gr == $grupo_final){ 
                $k_f = $k;
                $autoriza = false;
              }


            }

            $res['grupo'] = substr($res['grupo'], 0, -1);

          }

          $res['str_gr'] = $res['grupo'];

          foreach(explode(';', $res['grupo']) as $gru){

            /*$res['sql'] = "select u.usu_nome from tbl_usuario u, reserva r where u.usu_cod = r.revendedor and 
            r.rifa ='" . intval($_GET['rifa'])."' and r.nome_grupo = '".$mysqli->escape_string($gru)."' limit 1";*/
            $res['sql'] = "
            select u.usu_nome 
            from tbl_usuario u, revenda r, grupo_revendedor gr 
            where u.usu_cod = r.vendedor 
            and r.rifa ='" . intval($_GET['rifa'])."'
            and gr.grupo = '".$mysqli->escape_string($gru)."'
            and gr.revenda = r.codigo 
            limit 1";

            $rev =(db_select($mysqli, $res['sql'], 1)['usu_nome'])? db_select($mysqli, $res['sql'], 1)['usu_nome']:"Sem revendedor";

            $res['revendedor'] .= "$rev - $gru<br>";

            if(!is_array($res['bilhete']))
              $res['bilhete'] = array($res['bilhete']);

            $res['bilhete'] = array_merge($lot->buscarGrupo($gru), $res['bilhete']);

          }

        }else{

          
          $res['sql2'] = "
            select u.usu_nome 
            from tbl_usuario u, revenda r, grupo_revendedor gr 
            where u.usu_cod = r.vendedor 
            and r.rifa ='" . intval($_GET['rifa'])."'
            and gr.grupo = '".$mysqli->escape_string($res['grupo'])."'
            and gr.revenda = r.codigo 
            limit 1";

          $res['sql'] = "select u.usu_nome from tbl_usuario u, reserva r
where r.nome_grupo = '".$mysqli->escape_string($res['grupo'])."'
and r.rifa = '".intval($_GET['rifa'])."'
and u.usu_cod = r.revendedor";
/*
          $res['sql'] = "select u.usu_nome from tbl_usuario u, reserva r where u.usu_cod = r.revendedor and r.rifa ='" . intval($_GET['rifa'])."' and r.nome_grupo = '".$mysqli->escape_string($_GET['grupo'])."' limit 1";
        */
          $tmp = db_select($mysqli, $res['sql'], 1);
          $tmp2 = db_select($mysqli, $res['sql2'], 1);
          //unset($res['sql']);

          $res['gruposs'] = $lot->buscarGrupo($_GET['grupo']);
          if($tmp2['usu_nome'])
            $res['revendedor'] = $tmp2['usu_nome'];
          else
            $res['revendedor'] = $tmp['usu_nome'];

          $res['bilhete'] = str_replace('<\/small>', ' ', str_replace('<small>', ' ', $res['gruposs']));
          
          
        }
        echo json_encode($res);

      }

      /* old_version
      if(!$_GET['grupo'])
        $lot->buscarBilhete($_GET['bilhete']);
      else{
        $res = array();
        $res['rifa'] = $_GET['rifa'];
        $res['grupo'] = strtoupper($_GET['grupo']);
        $tmp= db_select($mysqli, "select rifa_titulo from tbl_rifas where rifa_cod = '" . intval($_GET['rifa'])."' limit 1", 1);
        $res['rifa_nome'] = $tmp['rifa_titulo'];
        $res['sql'] = "select u.usu_nome from tbl_usuario u, reserva r where u.usu_cod = r.revendedor and r.rifa ='" . intval($_GET['rifa'])."' and r.nome_grupo = '".$mysqli->escape_string($_GET['grupo'])."' limit 1";
        $res['revendedor'] = db_select($mysqli, $res['sql'], 1);
        $res['bilhete'] = $lot->buscarGrupo($_GET['grupo']);
        echo json_encode($res);
      }*/

      ?>