<?php

include("../class/conexao.php");
include("class/LoteamentoSurpresinha2.class.php");

if($_GET['action'] == 'get_serie' || $_GET['action'] == 'get_sequencia' || $_GET['action'] == 'get_all'){

	$a = array();
	$e = array();
	$rifa = intval($_GET['rifa']);
	$bilhete = $_GET['bilhete'];

	$res = db_select($mysqli, "select embaralhar, agrupar, numeros_por_bilhete, bolaodezena, starting_group from opcao_reserva where rifa = '$rifa' limit 1", 1);

    $setStartingGroup = false;
    $ativarBolao = false;
    if($res['bolaodezena']){
    	$ativarBolao = $res['bolaodezena'];
    	$setStartingGroup = $res['starting_group'];
    }

    if($res['embaralhar'])
    	$e[$rifa] = true;
    else
    	$e[$rifa] = false;

    if($res['agrupar'])
    	$a[$rifa] = true;
    else
    	$a[$rifa] = false;

    $layout = 2;
    $lot    = new Loteamento($rifa, $layout, $e, $a);

  	if($ativarBolao){
	    $lot->setNumeroSurpresinhas(intval($ativarBolao));
	    $lot->setStartingGroup($setStartingGroup);
  	}

  	$formatar = true;
  	if(!$_GET['formatar'])
  		$formatar = false;

    $tam = db_select($mysqli, "select rifa_maxbilhetes FROM tbl_rifas WHERE rifa_cod = '$rifa' limit 1", 1);
    $tam = strlen($tam['rifa_maxbilhetes'])-1;

  	if(strpos($bilhete, ',') !== false){

  		$bilhetes = explode(',', $bilhete);
  		$res = explode('-', $lot->buscarSerieDaSequencia($bilhetes));

  		die(
  		json_encode(
	  		array(
	  			'serie' => array(
		  			'grupo'=>$res[0], 
		  			'bilhete'=>str_pad($res[1], $tam, '0', STR_PAD_LEFT), 
		  			'token'=>$res[2]
	  			)
	  		)
  		)
	     );

  	}

  	$res = explode('-', $lot->buscarBilhete($bilhete, false));

    

  	die(
  		json_encode(
	  		array(
	  			'serie' => array(
		  			'grupo'=>$res[0], 
		  			'bilhete'=>str_pad($res[1], $tam, '0', STR_PAD_LEFT), 
		  			'token'=>$res[2]
	  			),
	  			'sequencia'=>$lot->buscarSequencia($bilhete, $formatar)
	  		)
  		)
	);

}

?>