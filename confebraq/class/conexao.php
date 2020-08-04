<?php











// Conexão OFFLINE



$hostname_conexao = "localhost";



$database_conexao = "rifasbrasil";



$username_conexao = "rifasbrasil";



$password_conexao = "gsp2013";











// Conexão ONLINE



$hostname_conexao = "mysql1.rifasbrasil.com.br";
$database_conexao = "rifasbrasil1";
$username_conexao = "rifasbrasil1";
$password_conexao = "j053l170atio";


// Execução da Conexão
$mysqli = new mysqli($hostname_conexao, $username_conexao, $password_conexao, $database_conexao);

if ($mysqli->connect_errno) 
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;


if(!function_exists("DBSelect")){



	 function DBSelect($q, $mysqli, $coluna = false){







	 	$f = $mysqli->query($q) or die($mysqli->error);







	 	if($coluna != false){



	 		$result = $f->fetch_assoc();



	 		return $result[$coluna];



	 	}else 	



	 		return $f->fetch_assoc();



	 }



}







if(!function_exists("DBExecute")){



	 function DBExecute($q, $mysqli){



	 	$f = $mysqli->query($q) or die($mysqli->error);



	 	return $f;



	 }



}







if(!function_exists("DBCount")){



	 function DBCount($q, $mysqli){



	 	$f = $mysqli->query($q) or die($mysqli->error);



	 	return $f->num_rows;



	 }



}







if(!function_exists("db_select")){







	function db_select($m, $q, $num_row = null){







		$retorno = array();



		$exec = $m->query($q) or die($m->error);



		$dado = $exec->fetch_assoc();







		if($exec->num_rows > 0){







			do{







				$retorno[] = $dado;



				if($num_row == 1)



					break;







			}while($dado = $exec->fetch_assoc());



		}







		$exec->free();



		



		if($num_row == 1)



			return $retorno[0];



		else



			return $retorno;







	}







}







if(!function_exists("DBInsert")){







	function DBInsert($m, $tabela, $arr, $col_invalida){







		$code = "";



		$cols = "";







		if(is_array($col_invalida)){



			foreach($arr as $col=>$val){



				if(!in_array($col, $col_invalida) && !is_array($val)){







					$code .= "'".$m->escape_string($val)."', ";



					$cols .= $m->escape_string($col).", ";



					



				}



			}



		}else{



			foreach($arr as $col=>$val){



				$code .= "'".$m->escape_string($val)."', ";



				$cols .= $m->escape_string($col).", ";



			}



		}







		$cols = substr($cols, 0, -2);



		$code = substr($code, 0, -2);







		$codigo_sql = "insert into $tabela ($cols) values($code)";



		



		$r = $m->query($codigo_sql) or die($m->error);



		if($r)



			return $m->insert_id;



		else



			return false;











	}







}









if(!class_exists("mwc")){

	class mwc



	{



	    private static $def_m_w = 1712; /* must not be zero */



	    private static $def_m_z = 23;   /* must not be zero */



	    private $m_w, $m_z;



	    public function __construct($seed = NULL)



	    {



	        $this->m_w = self::$def_m_w;



	        $this->m_z = self::$def_m_z;



	        if (NULL !== $seed)



	            $this->seed($seed);



	    }



	    public function seed($seed)



	    {



	        $seed = (int) $seed;



	        if (!$seed) throw new InvalidArgumentException('Must not be zero.');



	        $this->m_z = $seed;



	        $this->random();



	    }



	    public function random()



	    {



	        $this->m_z = 36969 * ($this->m_z & 65535) + ($this->m_z >> 16);



	        $this->m_w = 18000 * ($this->m_w & 65535) + ($this->m_w >> 16);



	        return ($this->m_z << 16) + $this->m_w;  /* 32-bit result */



	    }



	}

}

if(!function_exists("shuffleIt")){



	function shuffleIt($array, $seed)



	{



	    $mwc = new mwc($seed);



	    $order = array();



	    $count = count($array);



	    while($count--)



	        $order[] = $mwc->random()



	    ;







	    array_multisort($order, $array);



	    return $array;



	}



}



if(!function_exists("getNameFromNumber")){



	function getNameFromNumber($num) {



		$numeric = ($num - 1) % 26;



		$letter = chr(65 + $numeric);



		$num2 = intval(($num - 1) / 26);



		if ($num2 > 0) {



			return getNameFromNumber($num2) . $letter;



		} else {



			return $letter;



		}



	}



}



if(!function_exists("gerar_array")){



	function gerar_array($bilhetes, $rifa){







		$bil = array();







		for($start = 0; $start < $bilhetes; $start++)



			$bil[] = str_pad($start, strlen($bilhetes)-1, "0", STR_PAD_LEFT); 







		return $bil;







	}



}







?>



