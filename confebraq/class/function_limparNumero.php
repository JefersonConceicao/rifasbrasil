<?php

if(!function_exists("limparNumero")){
	
	function limparNumero($n){

		$final = "";
		for($i = 0; $i < strlen($n); $i++){
			if(is_numeric($n[$i]))
				$final .= $n[$i];
		}

		return $final;
		
	}

}

?>