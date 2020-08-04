<?php

function getBilhetesESeusGrupos($rifas){

	include("conexao.php");
	if(is_file("../servidor/new_server/class/Loteamento.class.php"))
    	include("../servidor/new_server/class/Loteamento.class.php");
    elseif(is_file("../../servidor/new_server/class/Loteamento.class.php"))
    	include("../../servidor/new_server/class/Loteamento.class.php");
    elseif(is_file("../../../servidor/new_server/class/Loteamento.class.php"))
    	include("../../../servidor/new_server/class/Loteamento.class.php");
    elseif(is_file("servidor/new_server/class/Loteamento.class.php"))
    	include("servidor/new_server/class/Loteamento.class.php");

    $lot    = new Loteamento($rifas, 3, array($rifas=>true), array($rifas=>true));
    return ($lot->getBilhetesEGruposComoIndice($rifas));

}
/*echo "<PRE>";
$temp = getBilhetesESeusGrupos(3358);
var_dump($temp['G90']);
echo "</pre>";*/
?>