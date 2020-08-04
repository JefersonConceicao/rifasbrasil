			<?php

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
      }

      ?>