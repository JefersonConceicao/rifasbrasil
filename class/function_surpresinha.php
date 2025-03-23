<?php

if(!function_exists('gerarDezenas')){
	function gerarDezenas($id_rifa, $id_bilhete, $formatted = false){

		$dezenas = array();
		$id_rifa = intval($id_rifa);
		$id_bilhete = intval($id_bilhete);
		$dezenas_geradas = array();

		for($k = 0; $k < 10; $k++){
			$seed = $id_rifa.$id_bilhete.$k;
			srand($seed);

			$numero = rand(0, 99);
			while($dezenas_geradas[$numero] == true){
				$numero = rand(0, 99);
			}
			$dezenas_geradas[$numero] = true;

			if($formatted === true)
				$dezenas[] = '['.str_pad($numero, 2, '0', STR_PAD_LEFT).']';
			else if($formatted == 1)
				$dezenas[] = str_pad($numero, 2, '0', STR_PAD_LEFT);
			else
				$dezenas[] = $numero;
		}

		return $dezenas;
	}
}

if(isset($_GET['teste_da_surpresinha'])) {
	//var_dump(gerarDezenas(5079, 9369, 1));
}

?>