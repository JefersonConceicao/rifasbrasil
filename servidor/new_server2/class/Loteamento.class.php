<?php

/*

Descrição do Algoritmo

1. Instancie o objeto Loteamento(Codigo da Rifa, Código do Layout);
2. Descubra a quantidade de páginas que será necessaria usando getQuantidadeDePaginasNecessarias();
3. Gere o código HTML usando gerarHTML($k), onde $k é a pagina desejada. A função retornará o código HTML
4. Gere o PDF baseado no código html usando a funcao gerarPDF(código html ou gerarHTML($k));

*/

class Loteamento{

	public 	$rifas,
			$layout,
			$pasta,
			$slots,
			$qr_code,
			$maxpages = 1000,
			$embaralhamento = true,
			$agrupamento = true,
			$impressao_multipla = 0, // quantidade de numeros em cada bilhete
			$semhtml = true,
			$bilhetesDaMesmaRifaPorLinha,
			$tradutorRifasPorLinha;
						
	function __construct($r, $l, $npb, $e = true, $a = true, $bpr = 1){

		/*
		$r = codigo da rifa
		$l = código do layout desejado
		$e = booleano que diz se a rifa vai ser embaralhada ou não
		*/

		$this->impressao_multipla = array();
		$this->impressao_multipla = $npb;

		$this->maxpages = 1500 - ( 1500 % $this->getNumeroDeBilhetesPorPagina() ) ;

		$this->semhtml = 2;

		include('../class/phpqrcode/qrlib.php');

		if(!$r)
			die('Rifa inválida');

		if(!$l)
			die('Layout inválido');

		if(!$e)
			$this->embaralhamento = false;
		else
			$this->embaralhamento = $e;

		if(!$a)
			$this->agrupamento = false;
		else
			$this->agrupamento = $a;

		$this->rifas = $r;
		$this->layout = $l;

		if(is_array($r)){
			$this->slots = $this->getSlots($l);

			if(count($r) != $this->slots)
				die('Este layout precisa de pelo menos '.$this->slots.' rifas.');

		}else
			$this->slots = 1;

	}

	function getSlots($l){

		/*
		$l = numero inteiro que represente o layout
		Retorna a quantidade de slots disponiveis no layout $l
		*/

		$nslots = array();
		$nslots['1'] = 6;
		$nslots['2'] = 3;
		$nslots['3'] = 2;
		$nslots['4'] = 2;
		$nslots['5'] = 2;

		$bilhetesDaMesmaRifaPorLinha['1'] = 2;
		$bilhetesDaMesmaRifaPorLinha['2'] = 2;
		$bilhetesDaMesmaRifaPorLinha['3'] = 2;
		$bilhetesDaMesmaRifaPorLinha['4'] = 1;
		$bilhetesDaMesmaRifaPorLinha['5'] = 1;

		$this->bilhetesDaMesmaRifaPorLinha = $bilhetesDaMesmaRifaPorLinha[$l];

		return $nslots[$l];

	}


	function gerarQR($r){

		if($this->qr_code[$r])
			return $this->qr_code[$r];

		if(is_file("../qr/rifa_".$r.".png"))
			return "http://rifasbrasil.com.br/new_server/qr/rifa_".$r.".png";
		
		QRcode::png("http://www.rifasbrasil.com.br/index.php?p=antifraude&rifa=".$r, "qr/rifa_".$r.".png");
		
		$link = "http://rifasbrasil.com.br/new_server/qr/rifa_".$r.".png";
		$this->qr_code[$r] = $link;

		return $link;

	}

	function getRifasPorPagina(){

		// Retorna a quantidade maxima de BILHETES que cabem em cada página

		$layout = $this->layout;

		if($layout == 1)
			return 12;
		
		if($layout == 2)
			return 6;
		
		if($layout == 3)
			return 4;
		
		if($layout == 4)
			return 4;

		if($layout == 5)
			return 2;
		

	}


	function getCss(){

		include("../class/conexao.php");

		$layout = $this->layout;
		$medidas = array();
		$rifas_por_pagina = $this->getRifasPorPagina();

		if($layout == '' || $layout == 1){
			$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'PequenaMul'", 1);
		}
		if( $layout == 2){
			$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'PequenaMul'", 1);
		}

		if($layout == 3){
			$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Grande'", 1);
			$medidas2 = db_select($mysqli, "select * from impressao_rifas where tipo = 'Pequena'", 1);
		}

		if($layout == 4){
			$medidas = db_select($mysqli, "select * from impressao_rifas where tipo = 'Grande'", 1);
		}

		if($layout == 1 || $layout == 2 || $layout == 3 || $layout == 4){

			$margem_topo = $medidas['margem_topo'];
			$margem_dir = $medidas['margem_dir'];
			$margem_bottom = $medidas['margem_chao'];
			$margem_esq = $medidas['margem_esq'];

			$rifa_altura = $medidas['rifa_altura'];
			$rifa_largura = $medidas['rifa_largura'];

			$largura_qr_code = $medidas['qr_code_largura'];
			$altura_qr_code = $medidas['qr_code_altura'];

			$altura_bloco_branco = $medidas['bloco_branco_altura'];
			$largura_bloco_branco = $medidas['bloco_branco_largura'];

			$distancia_bloco_branco_topo = $medidas['bloco_branco_distancia_topo'];
			$distancia_bloco_branco_esquerda = $medidas['bloco_branco1_distancia_esquerda'];

			$altura_bloco_branco_dir = $medidas['bloco_branco_altura'];
			$largura_bloco_branco_dir = $medidas['bloco_branco_largura'];
			$distancia_bloco_branco_dir_esquerda = $medidas['bloco_branco2_distancia_esquerda'];
			$pos = "";

			ob_start();
			include("lcss/gerar_tamanhos.php");
			$conteudo = ob_get_contents();
			ob_end_clean();

			if($layout == 3)
				$conteudo .= '
				<style>
					.bloco-branco small {
					    color: black;
					    position: relative;
					    top: -40px;
					    left: 25px;
					    font-weight:bold;
					    font-size: 9pt;
					} 
					.bloco-branco { 
						font-weight:bold;
					}
					.serie_name {
						background-color: black;
						font-size: 9pt;
						color:white;
					}
					.bloco-branco .grupo {
					    position: relative;
					    left: 30px;
					}
					.qr2 {
						position: relative;
	    				top: 28px;
					}
					.bloco-branco-dir small {
						position: relative;
					        top: -40px;
					    color: black;
					    font-weight:bold;
					    font-size: 9pt;
					} 
					.bloco-branco-dir { 
						font-weight:bold;
					}
			</style>';

		}elseif($layout == 5){

			ob_start();
			include("lcss/tamanhos-a4.php");
			$conteudo = ob_get_contents();
			ob_end_clean();

		}


		return $conteudo;


	}

	function gerarHTML($parte){

		ob_start();
		// inicio da captura
		echo 
		"<!DOCTYPE html>
			<head>
				<meta charset=\"utf-8\">";
					
					echo $this->getCss();

		echo "
			</head>
			<body>";

		echo $this->getBody($parte);

		echo 
			"</body>";

		// fim da captura
		$conteudo = ob_get_contents();
		ob_end_clean();

		$nome = md5(time());
		$arq  = "/var/www/nevoahost/c/rifasbrasil.com.br/admin/resultado/".$nome.".html";
		echo $arq.'<br>';
		$fp   = fopen($arq, "w");
		$this->fwrite_stream($fp, $conteudo);
	    fclose($fp);

	    return $nome;

	}

	function gerarPDF($nome){

		if($this->layout == 2)
	    	exec('xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 210mm --page-height 297mm http://rifasbrasil.com.br/servidor/admin/resultado/'.$nome.'.html /var/www/nevoahost/c/rifasbrasil.com.br/admin/resultado/'.$nome.'.pdf');
	    elseif($this->layout == 5)
	    	exec('xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 297mm --page-height 211mm http://rifasbrasil.com.br/servidor/admin/resultado/'.$nome.'.html /var/www/nevoahost/c/rifasbrasil.com.br/admin/resultado/'.$nome.'.pdf');
	    else {
	    	echo 'xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 320mm --page-height 415mm http://rifasbrasil.com.br/servidor/admin/resultado/'.$nome.'.html /var/www/nevoahost/c/rifasbrasil.com.br/admin/resultado/'.$nome.'.pdf<br>';
	    	exec('xvfb-run wkhtmltopdf -L 0mm -R 0mm -T 0mm -B 0mm --page-width 320mm --page-height 415mm http://rifasbrasil.com.br/servidor/admin/resultado/'.$nome.'.html /var/www/nevoahost/c/rifasbrasil.com.br/admin/resultado/'.$nome.'.pdf');
	    }

	    return $nome.".pdf";

	}

	function apagarHTMLs(){
		exec('find  /var/www/rifasbrasil/admin/resultado/ -name "*.html" -type f -delete');
	}

	function apagarPDFs(){
		exec('find  /var/www/rifasbrasil/admin/resultado/ -name "*.pdf" -type f -delete');
	}


	// Funções auxiliares

	function fwrite_stream($fp, $string) {
	    for ($written = 0; $written < strlen($string); $written += $fwrite) {
	        $fwrite = fwrite($fp, substr($string, $written));
	        if ($fwrite === false) {
	            return $written;
	        }
	    }
	    return $written;
	}

	function shuffleIt($array, $seed){

		// embaralha um array de acordo com a semente
	    $mwc = new mwc($seed);
	    $order = array();
	    $count = count($array);

	    while($count--)
	        $order[] = $mwc->random();

	    array_multisort($order, $array);
	    return $array;
	}

	function getGrupos($rifa){

		include("../class/conexao.php");

		$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
		$bilhetes = $bilhetes['rifa_maxbilhetes'];
		$grupo = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

		if($bilhetes > 900 && $bilhetes < 1200)
			$bilhetes = 1000;
		elseif($bilhetes > 3000)
			$bilhetes = 10000;

		$grupos = ceil($bilhetes / $grupo['grupo']); // Descobre a quantidade de grupos que será necessária
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

	function range_reservado($reservados, $start, $end){

		/*
		gera um array com todos os valores entre os numeros $start e $end, exceto os numeros presentes
		no vetor $reservados
		*/

		if(count($reservados) == 0 || !is_array($reservados))
			return range($start, $end);


		$resultado = array();
		$k = 0;
		$fim = $end - $start;

		for($k; $k <= $fim; $k++){
			if(!$reservados[$k]) $resultado[] = $k;
		} 

		return $resultado;

	}

	function getNumBilhetes($rifa){

		include("../class/conexao.php");


		$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
		$bilhetes = $bilhetes['rifa_maxbilhetes'];
		$grupo = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

		if($bilhetes > 900 && $bilhetes < 1200)
			$bilhetes = 1000;
		elseif($bilhetes > 3000)
			$bilhetes = 10000;

		return $bilhetes;

	}


	function gerarBilhetesReservadosComGrupos($rifa, $comgrupo = false){

		/*
		esta funcao pega as strings armazenadas na tabela reserva e converte elas em um vetor de bilhetes que pode ser com o nome do grupo ou
		sem o nome do grupo
		*/

		$array_final = array();
		include("../class/conexao.php");
		$bilhetes = db_select($mysqli, "select * from reserva where rifa = '$rifa'");

		if(count($bilhetes) == 0)
			return;

		$indice = 0;
		$num_bilhetes = $this->getNumBilhetes($rifa);
		$tamanho = strlen($num_bilhetes) - 1;

		foreach($bilhetes as $str){

			if(strlen($str['bilhete']) == 0){
				//nao faz nada
			}elseif(substr_count($str['bilhete'], '-') > 0){

				$tmp = explode('-', $str['bilhete']);

				if($tmp[0] < $tmp[1]){
					$inicio = intval($tmp[0]);
					$fim    = intval($tmp[1]);
				}else{
					$inicio = intval($tmp[1]);
					$fim    = intval($tmp[0]);
				}

				for($inicio; $inicio <= $fim; $inicio++){

					if(($num_bilhetes) == $inicio){
						//nao faz nada
						break;
					}elseif($comgrupo){

						if($this->semhtml)
							$array_final[$indice++] = "<span class=\"grupo\">".$str['nome_grupo']."</span> <span class=\"milhar\">".str_pad($inicio, $tamanho, '0', STR_PAD_LEFT)."</span>";
						else
							$array_final[$indice++] = "<span class=\"grupo\">".$str['nome_grupo']."</span>-<span class=\"milhar\">Nº".str_pad($inicio, $tamanho, '0', STR_PAD_LEFT)."</span><br><small>ID. $rifa | ".substr(md5($rifa.$inicio), 0, 4)."</small>";
					}else
						$array_final[$inicio] = true;


				}
				

			}else{
				$tmp = explode(',', $str['bilhete']);
				$k   = count($array_final);
				foreach($tmp as $bil){
					if($comgrupo){
						if($this->semhtml)
							$array_final[$indice++] = "<span class=\"grupo\">".$str['nome_grupo']."</span> <span class=\"milhar\">".str_pad(intval($bil), $tamanho, '0', STR_PAD_LEFT)."</span>";
						else
							$array_final[$indice++] = "<span class=\"grupo\">".$str['nome_grupo']."</span>-<span class=\"milhar\">Nº".str_pad(intval($bil), $tamanho, '0', STR_PAD_LEFT)."</span><br><small>ID. $rifa | ".substr(md5($rifa.$bil), 0, 4)."</small>";
					}else
						$array_final[intval($bil)] = true;
				}
			}

		}


		return $array_final;

	}


	function getBilhetes($rifa, $semhtml = false){

		include("../class/conexao.php");
		$this->semhtml = $semhtml;

		$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
		$bilhetes = $bilhetes['rifa_maxbilhetes'];
		$grupo = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

		if($bilhetes > 900 && $bilhetes < 1200)
			$bilhetes = 1000;
		elseif($bilhetes > 3000)
			$bilhetes = 10000;


		$bil_reservados = $this->gerarBilhetesReservadosComGrupos($rifa);

		$grupos = ceil(($bilhetes-count($bil_reservados)) / $grupo['grupo']); // Descobre a quantidade de grupos que será necessária
		$numero_letras = ceil($grupos/100);

		if($this->embaralhamento[$rifa]){
			$bil = $this->shuffleIt($this->range_reservado($bil_reservados, 0, $bilhetes-1), $rifa);
		}else{
			$bil = $this->range_reservado($bil_reservados, 0, $bilhetes-1);
		}
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


		$i = 1;
		$k = 0;
		$j = 0;
		$bilhetes_finais = array();

		$reservados = $this->gerarBilhetesReservadosComGrupos($rifa, true);

		if(count($reservados) > 0 && is_array($reservados)){
			foreach($reservados as $r){
				$bilhetes_finais[] = $r;
			}
		}

		foreach($bil as $val){

			if($this->agrupamento[$rifa]){

				if($semhtml == 2)
					$bilhetes_finais[] = "<span class=\"grupo\">".$titulo_grupos[$k]."</span> <span class=\"milhar\">".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."</span>";
				elseif($semhtml == 1)
					$bilhetes_finais[] = $titulo_grupos[$k]."-".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."-".substr(md5($rifa.$val), 0, 4);
				else
					$bilhetes_finais[] = "<span class=\"grupo\">".$titulo_grupos[$k]."</span>-<span class=\"milhar\">Nº".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."</span><br><small>ID. $rifa | ".substr(md5($rifa.$val), 0, 4)."</small>";
			
			}else{

				if($semhtml == 2)
					$bilhetes_finais[] = "<span class=\"milhar\">".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."</span>";
				elseif($semhtml == 1)
					$bilhetes_finais[] = "-".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."-".substr(md5($rifa.$val), 0, 4);
				else
					$bilhetes_finais[] = "<span class=\"milhar\">Nº".str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT)."</span><br><small>ID. $rifa | ".substr(md5($rifa.$val), 0, 4)."</small>";
			}

			$j++;

			if($j == $grupo['grupo']){
				$j = 0;
				$k++;
			}

			if($i == $grupos) {$i=0;}
			$i++;

		} 

		return $bilhetes_finais;

	}

	function getQuantidadeDePaginasNecessarias(){

		include("../class/conexao.php");

		if(is_array($this->rifas))
			$rifa = $this->rifas[0];
		else
			$rifa = $this->rifas;

		$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
		$bilhetes = $bilhetes['rifa_maxbilhetes'];
		$grupo = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);

		if($bilhetes > 900 && $bilhetes < 1200)
			$bilhetes = 1000;
		elseif($bilhetes > 3000)
			$bilhetes = 10000;
		else
			$bilhetes = 100;

		if($this->layout == 1)
			$numero_bilhetes = 12 * $this->maxpages;

		elseif($this->layout == 2)
			$numero_bilhetes = 6 * $this->maxpages;

		elseif($this->layout == 3)
			$numero_bilhetes = 4 * $this->maxpages;

		elseif($this->layout == 5)
			$numero_bilhetes = 2 * $this->maxpages;

		elseif($this->layout == 4)
			$numero_bilhetes = 4 * $this->maxpages;


		$num = 0;
		if(is_array($this->rifas))
			$num = count($this->rifas) * $bilhetes;
		else
			$num = $bilhetes;


		if($numero_bilhetes >= $num)
			return 1;
		else
			return ceil($this->getNumeroFinalDeBilhetesNaImpressaoMultipla($bilhetes)/$numero_bilhetes);

	}

	function organizar_array($arr, $parte){

		/*
		Pega uma matriz $arr no formato
		$matriz[0] = getBilhetes(rifa);
		$matriz[1] = getBilhetes(rifa);
		$matriz[2] = getBilhetes(rifa);
		e sorteia ela de acordo com a quantidade de slots.

		$max = numero de bilhetes em cada rifa
		$parte = parte a ser retornada
		$numero_bilhetes = Quantidade de bilhetes que será inserida em cada arquivo PDF
		*/



		if($this->layout == 1)
			$numero_bilhetes = 12* $this->maxpages;

		elseif($this->layout == 2)
			$numero_bilhetes = 6 * $this->maxpages;

		elseif($this->layout == 3)
			$numero_bilhetes = 4 * $this->maxpages;

		elseif($this->layout == 5)
			$numero_bilhetes = 2 * $this->maxpages;

		elseif($this->layout == 4)
			$numero_bilhetes = 4 * $this->maxpages;


		// FIM TESTE

		if(1){

			if(is_array($arr[0]))
				$max = count($arr[0]);
			else
				$max = count($arr);

			$slots = $this->slots;
		
			$resultado = array();
			$inicio = 0;
			$linha = 0;
			$k = 0;

			$limite_superior = 0;

			while($k < (($slots * $max))){

				if($this->layout == 5 || $this->layout == 4)
					$limite_superior = ($inicio+1);
				else
					$limite_superior = ($inicio+2);

				for($i = $inicio; $i < $limite_superior; $i++)
					$resultado[$k++] = $arr[$linha][$i];


				$linha++;
				
				if($linha == $slots){
					
					if($this->layout == 5 || $this->layout == 4) $inicio += 1;
					else $inicio += 2;

					$linha = 0;
				}
					
			}



			$tmp = array_chunk ($resultado, $numero_bilhetes); 
			return $tmp[$parte];

		}else{
			$tmp = array_chunk ($arr, $numero_bilhetes); 
			return $tmp[$parte];
		}
	}

	function getNumeroDeBilhetesPorPagina(){

			$somaDosNumeros = 0;
			foreach($this->impressao_multipla as $num){
				$somaDosNumeros += $num;
			}

			return $somaDosNumeros;

	}

	function getNumeroFinalDeBilhetesNaImpressaoMultipla($limite){

		// $limite = numero de bilhetes

				// determinar numero de voltas no array
			$somaDosNumeros = 0;
			$menor = 999;
			foreach($this->impressao_multipla as $linha=>$num){
				$somaDosNumeros += $num;
				if($num < $menor) $menor = $num;

			}

			$maiorNumeroDeLoops = ceil($limite / $menor);

			return ($maiorNumeroDeLoops * $somaDosNumeros);

	}

	function organizar_array_multiplas_rifas($arr, $parte){

		/*
		Pega uma matriz $arr no formato
		$matriz[0] = getBilhetes(rifa);
		$matriz[1] = getBilhetes(rifa);
		$matriz[2] = getBilhetes(rifa);
		e sorteia ela de acordo com a quantidade de slots.

		$max = numero de bilhetes em cada rifa
		$parte = parte a ser retornada
		$numero_bilhetes = Quantidade de bilhetes que será inserida em cada arquivo PDF
		*/

		if($this->layout == 1)
			$numero_bilhetes = 12* $this->maxpages;

		elseif($this->layout == 2)
			$numero_bilhetes = 6 * $this->maxpages;

		elseif($this->layout == 3)
			$numero_bilhetes = 4 * $this->maxpages;

		elseif($this->layout == 5)
			$numero_bilhetes = 2 * $this->maxpages;

		elseif($this->layout == 4)
			$numero_bilhetes = 4 * $this->maxpages;


		// FIM TESTE
		if(1){

			$limite = 0;
			if(is_array($arr[0])){

				$limite = count($arr[0]);
				$numeroDeBilhetes = $limite * count($arr);
				$numeroDeRifas = count($arr);

			}else{

				$limite = count($arr);
				$numeroDeBilhetes = $limite;
				$numeroDeRifas = 0;

			}

		
			$resultado = array();
			$inicioAtual = array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0);
			$k = 0;
			$linha = 0;

			$contagem_final = $this->getNumeroFinalDeBilhetesNaImpressaoMultipla($limite);

			//echo "CONTAGEM FINAL: " . $contagem_final;

			while($k < ($contagem_final)){
			//while($){

				for($linha = 0; $linha < $numeroDeRifas; $linha++){

					if($inicioAtual[$linha] < $limite){

						for($x = $inicioAtual[$linha]; $x < ($inicioAtual[$linha] + ($this->bilhetesDaMesmaRifaPorLinha * $this->impressao_multipla[$this->tradutorRifasPorLinha[$linha]]) ); $x++){
							
							if($x < $limite)
								$resultado[$k++] = $arr[$linha][$x];
							else
								$resultado[$k++] = '';	

							//$bilhetesJaDescarregados[$linha]++;
							//echo $arr[$linha][$x]."<br>";
							//echo " l $linha de $x à ".($inicioAtual[$linha] + ($this->bilhetesDaMesmaRifaPorLinha * $this->impressao_multipla[$this->tradutorRifasPorLinha[$linha]]) )."<br>";
						}

					}else{

						for($x = $inicioAtual[$linha]; $x < ($inicioAtual[$linha] + ($this->bilhetesDaMesmaRifaPorLinha * $this->impressao_multipla[$this->tradutorRifasPorLinha[$linha]]) ); $x++){
							
								$resultado[$k++] = '';							

							//$bilhetesJaDescarregados[$linha]++;
							//echo $arr[$linha][$x]."<br>";
							//echo " l $linha de $x à ".($inicioAtual[$linha] + ($this->bilhetesDaMesmaRifaPorLinha * $this->impressao_multipla[$this->tradutorRifasPorLinha[$linha]]) )."<br>";
						}
					}

					$inicioAtual[$linha] += ($this->bilhetesDaMesmaRifaPorLinha * $this->impressao_multipla[$this->tradutorRifasPorLinha[$linha]]);		

				}


					
			}

			//
			
			$tmp = array_chunk ($resultado, $numero_bilhetes); 
			return $tmp[$parte];
			
				

			

		}else{
			$tmp = array_chunk ($arr, $numero_bilhetes); 
			return $tmp[$parte];
		}
	}

	function getBilhetesFinais($parte, $semhtml = false, $semorganizacao = false){

		/*
		Mistura as rifas ou a rifa à ser loteada;
		*/

		$this->semhtml = $semhtml;

		$res          = array();
		$i            = 0;
		$num_bilhetes = 0;

		if(is_array($this->rifas)){

			foreach($this->rifas as $r){

				$this->tradutorRifasPorLinha[$i] = $r;

				$res[$i++] = $this->getBilhetes($r, $semhtml);
			}

		}else
			return $this->getBilhetes($this->rifas, $semhtml);

		//var_dump($res);
		
		if($semorganizacao)
			return $this->organizar_array_multiplas_rifas($res, $parte);
		else
			return $this->organizar_array($res, $parte);

	}

	function buscarGrupo($grupo){

		$resultado = array();

		for($parte = 0; $parte < $this->getQuantidadeDePaginasNecessarias(); $parte++){

			$bilhetes_finais = $this->getBilhetesFinais($parte, true);
			$rifas_por_pagina = $this->getRifasPorPagina();

			$fim = count($bilhetes_finais);

			for($b = 0; $b < $fim; $b++){

				$tmp = explode('-', $bilhetes_finais[$b]);

				if(strcmp(strtoupper($grupo), strip_tags(strtoupper($tmp[0]))) == 0 || strcmp(strtoupper(str_replace('-', '', $grupo)), strip_tags(strtoupper($tmp[0]))) == 0){
					$resultado[] = $bilhetes_finais[$b];
				}
			}

		}

		return $resultado;

	}

	function buscarBilhete($bil){


		for($parte = 0; $parte < $this->getQuantidadeDePaginasNecessarias(); $parte++){

			$bilhetes_finais = $this->getBilhetesFinais($parte, true);
			$rifas_por_pagina = $this->getRifasPorPagina();

			$fim = count($bilhetes_finais);

			for($b = 0; $b < $fim; $b++){
				$tmp = explode('-', $bilhetes_finais[$b]); 

				if(intval($bil) == intval($tmp[1]))
					echo $bilhetes_finais[$b]."<br>";
			}

		}
		
		//return $final;

	}

	function getBody($parte){

		// TODO
		$bilhetes_finais = $this->getBilhetesFinais($parte, $this->semhtml, true);
		$rifas_por_pagina = $this->getRifasPorPagina();

		$trocar_tabela = 0;
		$trocar_linha = 0;
		$fim = count($bilhetes_finais);
		$rifa_atual = 0;

		$final = "";

		if($this->layout == 5){

			$b = 0;
			do{

				if($trocar_tabela == 0) $final .= "<table class=\"principal\">";
				if($trocar_linha == 0) $final .= "<tr>";

				if(is_array($this->rifas))
					$codigo_rifa_tmp = $this->rifas[$rifa_atual];
				else
					$codigo_rifa_tmp = $this->rifas;

				$start = 0;
				if($this->impressao_multipla[$codigo_rifa_tmp] > 1){

					$tmp = "";
						//$tmp = "<small>ID: ".$codigo_rifa_tmp."</small> - ";
					for($k = $b; $k < ($b+ $this->impressao_multipla[$codigo_rifa_tmp] ); $k++){

							if($start == 0){
								$tmp .= $bilhetes_finais[$k].'-';
								$start++;
							}else{

								$tmp .= preg_replace('/<span class="grupo">(.*?)<\/span>(.*?)</', '<', $bilhetes_finais[$k]);

								switch($start){

									case 1:
										$tmp.='<br>';
										break;
									case 2:
										$tmp.='-';
										break;
									case 3:
										$tmp.='<br>';
										break;
									case 4:
										$tmp.='';
										break;

								}

								$start++;
								
							}
								
							
					}

					$tmp = substr($tmp, 0, -2);

				}else
					$tmp = $bilhetes_finais[$b];

				$tmp_grupo = preg_match("/<span class=\"grupo\">(.*?)<\/span>/", $tmp, $output_array);
				$grupo = $output_array[1];

				if(!$grupo) $grupo = '';

				$tmp = preg_replace('/<span class="grupo">(.*?)<\/span>/', '', $tmp);

				$final .= '
				<td valign="top" class="rifa">
					<table>
						<tr>
							<td class="complemento-bloco-branco-topo"></td>
							<td class="qr-code-espaco" ></td>
							<td class="bloco-branco-topo">'.$tmp.'</td>
						</tr>
						<tr>
							<td colspan="3" class="espacamento"></td>
						</tr>
						<tr>
							<td class="espacamento-bottom"></td>
							<td class="qr-code" align="center"><span class="rifa_codigo">'.$grupo.' </span><img class="qr" src="'.$this->gerarQR($codigo_rifa_tmp).'"><span class="rifa_codigo">ID '.$codigo_rifa_tmp.' </span></td>
							<td valign="bottom">
								<div class="bloco-branco-topo">'.$tmp.'</div>
							</td>
						</tr>
					</table>
				</td>';

				$rifa_atual++; 

				if($rifa_atual == count($this->rifas)) $rifa_atual = 0;

				if($trocar_linha == 1) {
					$final .= "</tr>"; 
					$trocar_linha = -1;
				}

				if($trocar_tabela == ($rifas_por_pagina-1)) {$final .= "</table>"; $trocar_tabela = -1; }

				$trocar_tabela++;
				$trocar_linha++;

				$b = $b+$this->impressao_multipla[$codigo_rifa_tmp];

			}while($b < $fim);

		}elseif($this->layout == 4){

			for($b = 0; $b < $fim; $b++){

				if($trocar_tabela == 0) $final .= "<table class=\"principal\">";
				if($trocar_linha == 0)  $final .= "<tr>";

				if(is_array($this->rifas))
					$codigo_rifa_tmp = $this->rifas[$rifa_atual];
				else
					$codigo_rifa_tmp = $this->rifas;

				if($this->impressao_multipla[$codigo_rifa_tmp] > 1){

						$tmp = '';
						for($k = $b; $k < ($b+$this->impressao_multipla[$codigo_rifa_tmp]); $k++){
							$tmp .= $bilhetes_finais[$k].'<br>'; 
						}

				}else
					$tmp = $bilhetes_finais[$b];



				$final .= '
				<td valign="top" class="rifa-altura rifa-largura rifa" >
					<div class="area_pre_infos"></div>
					<div class="area_infos">
						<table class="inside">
							<tr>
								<td class="col1">
									<div class="bloco-branco">'.$tmp.'</div>
								</td>
								<td class="col2">
									<div class="qr-code"><img class="qr" src="'.$this->gerarQR($codigo_rifa_tmp).'"></div>
								</td>
								<td class="col3">
									<div class="bloco-branco-dir">'.$tmp.'</div>
								</td>
							</tr>
						</table>
					</div>
				</td>';

				if($trocar_linha == 1) {
					$final .= "</tr>"; 
					$trocar_linha = -1; 
					$rifa_atual++; 
					if($rifa_atual == count($this->rifas)) $rifa_atual = 0;
				}
				
				if($trocar_tabela == ($rifas_por_pagina-1)) {$final .= "</table>"; $trocar_tabela = -1; }

				$trocar_tabela++;
				$trocar_linha++;
			}

		}else{

			// distribuicao multipla

			if(1){ // se for distribuicao multipla

				//for($b = 0; $b < $fim; $b++)

				$b = 0;

				do{

					if($trocar_tabela == 0) $final .= "<table class=\"principal\">";
					if($trocar_linha == 0)  $final .= "<tr>";

					if(is_array($this->rifas))
						$codigo_rifa_tmp = $this->rifas[$rifa_atual];
					else
						$codigo_rifa_tmp = $this->rifas;

					$start = 0;
					if($this->impressao_multipla[$codigo_rifa_tmp] > 1){

						$tmp = "";




						//$tmp = "<small>ID: ".$codigo_rifa_tmp."</small> - ";
						for($k = $b; $k < ($b+$this->impressao_multipla[$codigo_rifa_tmp]); $k++){

							if($start == 0){
								$tmp .= $bilhetes_finais[$k].'-';
								$start++;
							}else{

								$tmp .= preg_replace('/<span class="grupo">(.*?)<\/span>(.*?)</', '<', $bilhetes_finais[$k]);

								switch($start){

									case 1:
										$tmp.='<br>';
										break;
									case 2:
										$tmp.='-';
										break;
									case 3:
										$tmp.='<br>';
										break;
									case 4:
										$tmp.='';
										break;

								}

								$start++;
								
							}

								
						}



						$tmp = substr($tmp, 0, -2);

					

						//$tmp .= ;

					}else
						$tmp = $bilhetes_finais[$b];

					$tmp_grupo = preg_match("/<span class=\"grupo\">(.*?)<\/span>/", $tmp, $output_array);
					$grupo = $output_array[1];

					if(!$grupo)
						$grupo = '';

					$tmp = preg_replace('/<span class="grupo">(.*?)<\/span>/', '', $tmp);

					$final .=  '
					<td valign="top" class="rifa-altura rifa-largura rifa" >
						<div class="area_pre_infos"></div>
						<div class="area_infos">
							<table class="inside">
								<tr>
									<td class="col1">
										<div class="bloco-branco">'.$tmp.'</div>
									</td>
									<td class="col2">
										<div class="qr-code"><span class="rifa_codigo">'.$grupo.'</span><img class="qr" src="'.$this->gerarQR($codigo_rifa_tmp).'"><span class="rifa_codigo">ID '.$codigo_rifa_tmp.' </span></div>
									</td>
									<td class="col3">
										<div class="bloco-branco-dir">'.$tmp.'</div>
									</td>
								</tr>
							</table>
						</div>
					</td>';

					if($trocar_linha == 1) {
						$final .= "</tr>"; 
						$trocar_linha = -1; 
						$rifa_atual++; 
						if($rifa_atual == count($this->rifas)) $rifa_atual = 0;
					}
					
					if($trocar_tabela == ($rifas_por_pagina-1)) {$final .= "</table>"; $trocar_tabela = -1; }

					$trocar_tabela++;
					$trocar_linha++;

					$b = $b+$this->impressao_multipla[$codigo_rifa_tmp];

				}while($b < $fim);

			}else{

			// fim
			
				for($b = 0; $b < $fim; $b++){

					if($trocar_tabela == 0) $final .= "<table class=\"principal\">";
					if($trocar_linha == 0)  $final .= "<tr>";

					if(is_array($this->rifas))
						$codigo_rifa_tmp = $this->rifas[$rifa_atual];
					else
						$codigo_rifa_tmp = $this->rifas;

					if($this->impressao_multipla[$codigo_rifa_tmp] > 1){

						$tmp = '';
						for($k = $b; $k < ($b+$this->impressao_multipla[$codigo_rifa_tmp]); $k++){
							$tmp .= $bilhetes_finais[$k].'<br>'; 
						}

					}else
						$tmp = $bilhetes_finais[$b];

					$final .=  '
					<td valign="top" class="rifa-altura rifa-largura rifa" >
						<div class="area_pre_infos"></div>
						<div class="area_infos">
							<table class="inside">
								<tr>
									<td class="col1">
										<div class="bloco-branco">'.$tmp.'</div>
									</td>
									<td class="col2">
										<div class="qr-code"><img class="qr" src="'.$this->gerarQR($codigo_rifa_tmp).'"></div>
									</td>
									<td class="col3">
										<div class="bloco-branco-dir">'.$tmp.'</div>
									</td>
								</tr>
							</table>
						</div>
					</td>';

					if($trocar_linha == 1) {
						$final .= "</tr>"; 
						$trocar_linha = -1; 
						$rifa_atual++; 
						if($rifa_atual == count($this->rifas)) $rifa_atual = 0;
					}
					
					if($trocar_tabela == ($rifas_por_pagina-1)) {$final .= "</table>"; $trocar_tabela = -1; }

					$trocar_tabela++;
					$trocar_linha++;
				}

			}

		}

		return $final;

	}

}

?>