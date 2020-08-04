<?php

function primeiroNome($n){

	$i = 0;
	while($a != ' ' && $i <= strlen($n)){
		$a = $n[$i];
		$i++;
	}

	return substr($n, 0, $i-1);

}
?>