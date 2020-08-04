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
	function array_de_str_in_array_de_num($bilhetes){
		$final = array();
		foreach($bilhetes as $bil){
			if(substr_count($bil, '-') == 1){
				$tmp = explode('-', $bil);
				$start = intval($tmp[0]);
				$end   = intval($tmp[1]);
				if($tmp[0] > $tmp[1]){
					$start = intval($tmp[1]);
					$end   = intval($tmp[0]);
				}
				for($start; $start <= $end; $start++){
					$final[] = $start;
				}
			}else{
				$var = explode(',', $bil);
				foreach($var as $v){
					if(is_numeric(trim($v))) $final[] = intval($v);
				}
			}
		}
		return $final;
	}

if(isset($_POST['getReserva'])){

	include("../../class/conexao.php");
	$con = new Conexao();

	$reserva = intval($_POST['getReserva']);

	$tmp = $con->select('bilhete, rifa')->from('reserva')->where("codigo = '$reserva'")->limit(1)->executeNGet();
	$strBilhetes = $tmp['bilhete'];
	$rifa = intval($tmp['rifa']);

	if(!$strBilhetes or !$rifa)
		die(json_encode(array('erro'=>1, 'msg'=>"Bilhetes não encontrados!")));

	if(isset($_POST['onlyStr']))
		die(json_encode(array('erro'=>0, 'msg'=>$strBilhetes)));

	$numBilhetes = $con->select('rifa_maxbilhetes')->from('tbl_rifas')->where("rifa_cod = '$rifa'")->limit(1)->executeNGet('rifa_maxbilhetes');

	$arrBilhetes = str_in_array_de_num($strBilhetes, strlen($numBilhetes)-1);

	die(json_encode(array('erro'=>0, 'msg'=>implode(',', $arrBilhetes))));

}

if(isset($_GET['remover_imagem'])){

	include("../class/conexao.php");
	$rifa = intval($_GET['rifa']);

	$mysqli->query("UPDATE tbl_rifas SET rifa_png = NULL WHERE rifa_cod = '$rifa'");

	die("<script>location.href='index.php?p=revenda_loteamento&rifa=$rifa';</script>");

}

if(isset($_FILES['foto'])){

	$rifa = intval($_GET['rifa']);

	if(!$rifa)
		die("<script>alert('Rifa inválida!');</script>");

	include("../class/conexao.php");

	$cor = $_POST['cor'];

	if($cor) $mysqli->query("UPDATE tbl_rifas SET rifa_cor = '$cor' WHERE rifa_cod = '$rifa'");

    // upload da foto
    // Check if image file is a actual image or fake image
    if(isset($_FILES['foto']) && $_FILES["foto"]["name"]) {

    	$extensao    = end(explode('.', $_FILES["foto"]["name"]));
	    $arquivo     = md5(time()) . '.' . $extensao;
	    $target_file = '../foto/' . $arquivo;

	    if($extensao != 'png')
	    	echo("<script>alert('O arquivo precisa ter o formato .PNG');</script>");

        $check = getimagesize($_FILES["foto"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                // upload realizado
                // primeiro scan
                $mysqli->query("UPDATE tbl_rifas SET rifa_png = '$arquivo' WHERE rifa_cod = '$rifa'");
           	} else
                echo ("<script>alert('Falha ao enviar a imagem.');</script>");
                    
        } else {
            echo ("<script>alert('O arquivo não é uma imagem.');</script>");
            $uploadOk = 0;
        }
    }
}
if(isset($_POST['processar'])){
	$rifa = intval($_POST['rifa']);
	include("../../class/conexao.php");
	
	function verificar_bilhetes_repetidos($array_de_bilhetes){
		$contar =  array_count_values($array_de_bilhetes);
		foreach($contar as $k=>$c){
			if($c > 1)
				return $k;
		}
		return 'ok';
	}
	/*
	function verificar_bilhetes_repetidos($bilhetes){
		//$_POST['bilhete']
		$array_bilhetes = array();
		if(!is_array($bilhetes))
			return false;
		foreach($bilhetes as $bil){
			if(substr_count($bil, '-') == 1){
				$tmp = explode('-', $bil);
				$start = intval($tmp[0]);
				$end   = intval($tmp[1]);
				if($tmp[0] > $tmp[1]){
					$start = intval($tmp[1]);
					$end   = intval($tmp[0]);
				}
				for($start; $start <= $end; $start++){
					if($array_bilhetes[$start])
						return $start;
						//return true;
					else
						$array_bilhetes[$start] = true;
				}
			}else{
				$var = explode(',', $bil);
				if(!is_array($var)) return false;
				foreach($var as $v){
					if($array_bilhetes[$v])
						return $v;
						//return true;
					else
						$array_bilhetes[$v] = true;
				}
				
			}
		}
		return false;
	}*/
	function verificar_integridade($str){
		if(substr_count($str, '-') > 0 && substr_count($str, ',') > 0)
			return false;
		$str = str_replace(' ', '', $str);
		for($i = 0; $i < strlen($str); $i++){
			if(!is_numeric($str[$i]) && $str[$i] != '-' && $str[$i] != ',')
				return false;
		}
		return true;
	}

	$resultado = array();
	$resultado['sucesso'] = 1;
	$resultado['msg'] = "";
	if($_POST['embaralhamento'])
		$embaralhamento = 1;
	else
		$embaralhamento = 0;
	if($_POST['agrupamento'])
		$agrupamento = 1;
	else
		$agrupamento = 0;
	$numeros_por_bilhete = intval($_POST['numeros_por_bilhete']);
	$mysqli->query("delete from opcao_reserva where rifa = '$rifa'");
	$mysqli->query("insert into opcao_reserva (rifa, agrupar, embaralhar, numeros_por_bilhete) values('$rifa', '$agrupamento', '$embaralhamento', '$numeros_por_bilhete')");
	$j = 1;
	$sql_code = array();
	if(!is_array($_POST['revendedor'])){
		$resultado['sucesso'] = 1;
		$resultado['msg'] = "";
		$mysqli->query("delete from reserva where rifa = '$rifa'");
		echo json_encode($resultado);
		exit();
	}

	function removerBilhetesRepetidosDaStr($str){

		if(substr_count($str, '-') > 0)
			return $str;

		$arr = explode(',', $str);
		$arrayFinal = array();
		$repetidos = array();
		$tempArray = array();
		foreach($arr as $k=>$bil){
			$editedBil = intval(trim($bil));
			if(!isset($tempArray[$editedBil])){
				$arrayFinal[$bil] = true;
				$tempArray[$editedBil] = true;
			}else{
				$repetidos[$editedBil] = $bil;
			}
		}

		ksort($arrayFinal);

		return array('bilhetes'=>implode(',', array_keys($arrayFinal)), 'repetidos'=>$repetidos);

	}



	foreach($_POST['revendedor'] as $k=>$rev){

		
		$rev = intval($rev);
		if( $rev > 0 && strlen($_POST['bilhete'][$k]) == 0){ // Verifica se existem revendedores sem bilhetes
			$resultado['sucesso'] = 0;
			$resultado['msg'] = "Erro! Selecione os bilhetes de todos os vendedores.";
			echo json_encode($resultado);
			exit();
		}
		elseif(strlen($_POST['bilhete'][$k]) > 0 && $rev == 0){
			$resultado['sucesso'] = 0;
			$resultado['msg'] = "Erro! Selecione o vendedor de cada linha de bilhetes.";
			echo json_encode($resultado);
			exit();
		}
		elseif((count(str_in_array_de_num($_POST['bilhete'][$k])) % $numeros_por_bilhete) > 0 ){
			$resultado['sucesso'] = 0;
			$resultado['msg'] = "Erro! O número de bilhetes em cada grupo precisa ser múltiplo de $numeros_por_bilhete";
			echo json_encode($resultado);
			exit();
		}
		elseif(!verificar_integridade($_POST['bilhete'][$k])){
			$resultado['sucesso'] = 0;
			$resultado['msg'] = "Erro! Preencha todos os bilhetes usando a formatação correta.";
			echo json_encode($resultado);
			exit();
		}else{
			$resultado['sucesso'] = 1;
			// insere no banco de dados
			if(substr_count($str, '-') > 1){
				$resultado['sucesso'] = 0;
				$resultado['msg'] = "Erro! Preencha todos os bilhetes usando a formatação correta.";
				echo json_encode($resultado);
				exit();
			}
			if($resultado['sucesso'] == 1){
				if(strlen($_POST['bilhete'][$k]) > 0 && $rev > 0){
					$sql_code[] = "insert into reserva (revendedor, bilhete, nome_grupo, rifa, embaralhar, agrupar)
						values(
							'".$mysqli->escape_string($rev)."',
							'".implode(',', array_keys($tmp))."',
							'BP".$j++."',
							'$rifa',
							'$embaralhamento',
							'$agrupamento')";
					
				}
			}
		}
	}
	if($sql_code){
		$mysqli->query("delete from reserva where rifa = '$rifa'");
		foreach($sql_code as $sq)
			$mysqli->query($sq);
		$resultado['sucesso'] = 1;
		if(count($repetidos) > 0)
			$resultado['repetidos'] = implode(',', $repetidos);
	}
	
	echo json_encode($resultado);
	exit();
}
if(isset($_POST['clonar_total'])){

	include("../class/conexao.php");
	$rifa_origem = intval($_POST['rifa']);
	$rifa_destino = intval($_GET['rifa']);

	$con = new Conexao();

	// clonar as compras
	$compras = $con
		->select('*')
		->from('tbl_compra')
		->where("comp_cod IN (select bil_compra from tbl_bilhetes WHERE bil_rifa = '$rifa_origem' GROUP BY comp_cod)")
		->executeNGet();

	$arrayTradutorCompra = array();
	foreach($compras as $c){
		$id = $c['comp_cod'];
		unset($c['comp_cod']);
		$arrayTradutorCompra[$id] = $con->insert('tbl_compra', $c);
	}

	// clonar os bilhetes
	$bilhetes = $con->select('*')->from('tbl_bilhetes')->where("bil_rifa = '$rifa_origem'")->executeNGet();
	foreach($bilhetes as $b){
		unset($b['bil_cod']);
		$b['bil_compra'] = $arrayTradutorCompra[$b['bil_compra']];
		$b['bil_rifa'] = $rifa_destino;
		$con->insert('tbl_bilhetes', $b);
	}

	// clonar os revendedores
	$revendedores = $con->select('*')->from('tbl_revendedor')->where("rifa_cod = '$rifa_origem'")->executeNGet();
	foreach($revendedores as $rev){
		$rev['rifa_cod'] = $rifa_destino;
		unset($rev['codigo']);
		$con->insert('tbl_revendedor', $rev);
	}

	// clonar os custos

	// clonar as revendas
	$revendas = $con->select('*')->from('revenda')->where("rifa = '$rifa_origem'")->executeNGet();
	$arrayTradutor = array();
	foreach($revendas as $r){
		$id = $r['codigo'];
		unset($r['codigo']);
		$r['rifa'] = $rifa_destino;
		$arrayTradutor[$id] = $con->insert('revenda', $r);
	}

	// clonar o	grupo_revendedor
	$grupo_revendedor = $con->select('*')->from('grupo_revendedor')->where("revenda in (select codigo from revenda where rifa = '$rifa_origem')")->executeNGet();
	foreach($grupo_revendedor as $gr){
		unset($gr['codigo']);
		$gr['revenda'] = $arrayTradutor[$gr['revenda']];
		$con->insert('grupo_revendedor', $gr);
	}

	// clonar os lotes
	$lotes = $con->select('*')->from('lote')->where("rifa = '$rifa_origem'")->executeNGet();
	foreach($lotes as $l){
		unset($l['codigo']);
		$l['rifa'] = $rifa_destino;
		$con->insert('lote', $l);
	}

	// clonar opcao reserva
	$opcao_reserva = $con->select('*')->from('opcao_reserva')->where("rifa = '$rifa_origem'")->executeNGet();
	foreach($opcao_reserva as $or){
		unset($or['codigo']);
		$or['rifa'] = $rifa_destino;
		$con->insert('opcao_reserva', $or);
	}

	// clonar qrcode
	$qrcode = $con->select('*')->from('qrcode')->where("rifa = '$rifa_origem'")->executeNGet();
	foreach($qrcode as $qc){
		unset($qc['rel_codigo']);
		$qc['rifa'] = $rifa_destino;
		$con->insert('qrcode', $qc);
	}

	// clonar reservas
	$reserva = $con->select('*')->from('reserva')->where("rifa = '$rifa_origem'")->executeNGet();
	foreach($reserva as $r){
		unset($r['codigo']);
		$r['proveniente_da_compra'] = $arrayTradutorCompra[$r['proveniente_da_compra']];
		$r['rifa'] = $rifa_destino;
		$con->insert('reserva', $r);
	}

	// clonar rifa_sorteio
	$rifa_sorteio = $con->select('*')->from('rifa_sorteio')->where("rifa = '$rifa_origem'")->executeNGet();
	$arrayTradutor = array();
	foreach($rifa_sorteio as $r){
		$id = $r['codigo'];
		unset($r['codigo']);
		$r['rifa'] = $rifa_destino;
		$arrayTradutor[$id] = $con->insert('rifa_sorteio', $r);
	}

	// clonar sorteio_dezenas
	$sorteio_dezenas = $con->select('*')->from('sorteio_dezenas')->where("rifa = '$rifa_origem'")->executeNGet();
	foreach($sorteio_dezenas as $sort){
		$sort['rifa'] = $rifa_destino;
		$sort['sorteio'] = $arrayTradutor[$sort['sorteio']];
		unset($sort['codigo']);
		$con->insert('sorteio_dezenas', $sort);
	}

	echo "<script>alert('Rifa clonada com sucesso!');</script>";

}

if(isset($_POST['clonar_bilhetes'])){
	include("../class/conexao.php");
	$rifa = intval($_POST['rifa']);
	$rifa_atual = intval($_GET['rifa']);
	$reserva = db_select($mysqli, "select revendedor, bilhete, nome_grupo, rifa, embaralhar, agrupar from reserva where rifa = '$rifa'");
	$mysqli->query("delete * from reserva where rifa = '$rifa_atual'");
	$bilhetes_reservados = array();
	foreach($reserva as $r){
		// verifica se o revendedor em questao já é revendedor da rifa atual
		$tmp = db_select($mysqli, "select usu_cod, comissao from tbl_revendedor where rifa_cod = '$rifa' and usu_cod = '".$r['revendedor']."'");
		if($tmp){
			// cadastra
			foreach($tmp as $temporario){
				$temporario['rifa_cod'] = $rifa_atual;
				DBInsert($mysqli, 'tbl_revendedor', $temporario, array());
			}
		}
		if($_POST['clonar_geral'] || $_POST['clonar_bp'])
			$bilhetes_reservados = str_in_array_de_num($r['bilhete']);
		$r['rifa'] = $rifa_atual;
		DBInsert($mysqli, 'reserva', $r, array());
	}
	
	if($_POST['clonar_geral'] || $_POST['clonar_bp']){
		if($_POST['clonar_bp']){
			if(count($bilhetes_reservados) > 0)
				$str_de_bilhetes = "and b.bil_numero in (".implode(',', $bilhetes_reservados).")";
			else
				$str_de_bilhetes = "";
		}else
			$str_de_bilhetes = "";
		// depois que todos os bilhetes foram clonados, vamos agora clonar os bilhetes vendidos adicionados como BP
		$bilhetes_vendidos = db_select($mysqli, "SELECT b.*, c.* 
			FROM tbl_bilhetes b, tbl_compra c 
			WHERE b.bil_rifa = '$rifa'
			and c.comp_cod = b.bil_compra
			and c.comp_revendedor is not null
			and c.comp_revendedor > 0
			$str_de_bilhetes
			order by b.bil_compra ASC
		");
		$rifa = db_select($mysqli, "SELECT rifa_maxbilhetes, rifa_valorbilhete FROM tbl_rifas WHERE rifa_cod = '$rifa'", 1);
		$ultimo_grupo = db_select($mysqli, "SELECT max(nome_grupo) as ultimo_grupo from reserva where rifa = '$rifa'", 1);
		$opcoes = db_select($mysqli, "SELECT embaralhar, agrupar from reserva where rifa = '$rifa' order by codigo desc limit 1", 1);
		$numero_do_grupo = 1;
		if($ultimo_grupo['ultimo_grupo'] && intval(str_replace('BP', '', $ultimo_grupo['ultimo_grupo'])) > 0)
			$numero_do_grupo = intval(str_replace('BP', '', $ultimo_grupo['ultimo_grupo']))+1;
		$numero_de_pads = strlen($rifa['rifa_maxbilhetes']) - 1;
		$preco_rifa     = $rifa['rifa_valorbilhete'];
		$embaralhar =  intval( $opcoes['embaralhar'] );
		$agrupar = intval( $opcoes['agrupar'] );
		$compra_atual     = -1;
		$codigo_compra    = -1;
		$acumula_bilhetes = array();
		$rifa_nova        = intval($_GET['rifa']);
		$rifa_nova = db_select($mysqli, "SELECT rifa_valorbilhete FROM tbl_rifas WHERE rifa_cod = '$rifa_nova'", 1);
		//$reserva          = array();
		//$revendedor_atual =-1;
		if(is_array($bilhetes_vendidos)){
			foreach($bilhetes_vendidos as $bv){
				if($bv['comp_cod'] != $compra_atual){
					// avisa o sistema que a compra atual agora mudou
					$compra_atual = $bv['comp_cod'];
					$compra = array();
					// monta o array de compras
					$compra['comp_clonada'] = $bv['comp_cod'];
					unset($compra['comp_cod']);
					$compra['comp_cliente'] = $bv['comp_cliente'];
					$compra['comp_data'] = $bv['comp_data'];
					$num_bilhetes_comprados = intval($bv['comp_valortotal']/$preco_rifa);
					$compra['comp_valortotal'] = $num_bilhetes_comprados * $rifa_nova['rifa_valorbilhete'];
					$compra['comp_transacao'] = "";
					$compra['comp_situacao'] = "";
					$compra['comp_revendedor'] = $bv['comp_revendedor'];
					//$revendedor_atual = $bv['comp_revendedor'];
					$compra['comp_debitor'] = $bv['comp_debitor'];
					$compra['comp_status_revenda'] = 0;
					echo "compra inserida no bd<br>";
					//var_dump($compra);
					// insere a compra no bd
					$codigo_compra = DBInsert($mysqli, 'tbl_compra', $compra, array());
				}
				// monta o array de bilhetes
				$bilhete = array();
				$bilhete['bil_rifa'] = $rifa_atual;
				$bilhete['bil_numero'] = $bv['bil_numero'];
				$bilhete['bil_situacao'] = 'P';
				$bilhete['bil_compra'] = $codigo_compra;
				echo "bilhete inserida no bd<br>";
				//var_dump($bilhete);
				DBInsert($mysqli, 'tbl_bilhetes', $bilhete, array());
				//$reserva[$revendedor_atual][] = str_pad($bv['bil_numero'], $numero_de_pads, '0', STR_PAD_LEFT);
				$acumula_bilhetes[] = str_pad($bv['bil_numero'], $numero_de_pads, '0', STR_PAD_LEFT);
				
			}
			/*if(is_array($reserva)){
				foreach($reserva as $revendedor_codigo=>$array_bilhetes){
					$insert = array();
					$insert['revendedor'] = $revendedor_codigo;
					$insert['nome_grupo'] = 'BP'.$numero_do_grupo;
					$insert['rifa'] = $rifa_atual;
					$insert['embaralhar'] = $embaralhar;
					$insert['agrupar'] = $agrupar;
					$insert['proveniente_da_compra'] = 1;
					$insert['bilhete'] = implode(',', $array_bilhetes);
					$numero_do_grupo++;
					echo "reserva inserida no bd<br>";
					//var_dump($bilhete);
					DBInsert($mysqli, 'reserva', $insert, array());
				}
			}*/
		}
	}
	//echo "<script>alert('Informações copiadas com sucesso!');</script>";
}
$rifa    = intval($_GET['rifa']);
$dado    = db_select($mysqli, "select u.usu_nome, u.usu_cod from tbl_usuario u, tbl_revendedor r where u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa'");
$reserva = db_select($mysqli, "select * from reserva where rifa = '$rifa'");
$rifas   = db_select($mysqli, "select ri.rifa_cod, ri.rifa_png, ri.rifa_titulo from reserva r, tbl_rifas ri where r.rifa = ri.rifa_cod and ri.rifa_cod != '$rifa'");
$res     = db_select($mysqli, "select embaralhar, agrupar, numeros_por_bilhete from opcao_reserva where rifa = '$rifa' limit 1", 1);
$dados_da_rifa = db_select($mysqli, "select ri.rifa_png, ri.rifa_cor from tbl_rifas ri where ri.rifa_cod = '$rifa'", 1);
?>
<div class="col-lg-12">
	<h1 class="page-header">Reserva de Bilhetes</h1>
	<form action="" method="post">
		<div class="form-group form-inline">
			<label>Clonar bilhetes reservados da rifa: </label>
			<select name="rifa" required class="form-control">
				<option value=""></option>
				<?php foreach($rifas as $r){ ?>
				<option value="<?php echo $r['rifa_cod']; ?>"><?php echo $r['rifa_titulo']; ?></option>
				<?php } ?>
			</select>
			<input type="hidden" value="1" name="clonar_bilhetes">
			<button type="submit" name="clonar" class="btn btn-default">Clonar</button>
		</div>
		
		<div class="form-group">
			<hr>
			<label for="">Clonar bilhetes vendidos</label>
			<p><button type="submit" name="clonar_geral" value="1" class="btn btn-default">Clonar Geral</button> - 
				Clona todos os bilhetes pedidos e todas as vendas</p>
			<p><button type="submit" name="clonar_bp" value="1" class="btn btn-default">Clonar Marcados</button> - 
				Clona todos os bilhetes pedidos e apenas as vendas relacionadas a estes bilhetes pedidos</p>
		</div>
	</form>

	<form action="" method="post">
		<div class="form-group form-inline">
			<hr>
			<label>Clonar Total: </label>
			<select name="rifa" required class="form-control">
				<option value=""></option>
				<?php foreach($rifas as $r){ ?>
				<option value="<?php echo $r['rifa_cod']; ?>"><?php echo $r['rifa_titulo']; ?></option>
				<?php } ?>
			</select>
			<button type="submit" name="clonar_total" class="btn btn-default">Clonar</button>
		</div>
		
	</form>

</div>
<style>
	.esconder{display:none;}
</style>
<div class="col-lg-12 col-md-12">

	<div class="col-md-8">

			<hr>
			<label for="">Upload de Imagem</label>
			<p>Selecione o arquivo de imagem PNG da rifa. As medidas devem ser: 1,5 cm por 10 cm. 
			<form action="" method="post" enctype="multipart/form-data">
				<div class="form-group">
					<label for="">Arquivo</label>
					<input type="file"  name="foto" >
					
				</div>
				<div class="form-group">
					<label for="">Cor</label>
					<input type="color" name="cor" value="<?php if($dados_da_rifa['rifa_cor']) echo $dados_da_rifa['rifa_cor']; else echo  '#ffffff'; ?>">
				</div>
				<div class="form-group">
					<button class="btn btn-success">Enviar</button>
				</div>
			</form>
			
	</div>
	<div class="col-md-4">
		<?php if($dados_da_rifa['rifa_png']){ ?>
		<img src="../foto/<?= $dados_da_rifa['rifa_png'] ?>" class="img-responsive" alt="">
		<a href="index.php?p=revenda_loteamento&rifa=<?= $rifa; ?>&remover_imagem=1">Remover</a>
		<?php } ?>
	</div>

	<div class="clearfix"></div>
	
	<div class="form-group form-inline">
		<hr>
		<label for="">Como funciona</label>
	<p>Selecione o revendedor e defina os bilhetes, da seguinte maneira: 
		<ul>
			<li><input value="10-100"readonly class="form-control" type="text"> para separar todos os bilhetes entre 10 e 100</li>
			<li><input value="10,11,12,18"readonly class="form-control" type="text"> para separar os bilhetes 10,11,12 e 18</li>
		</ul>
	</p>
	</div>
	
	<div class="form-group">
		<button type="button" onclick="location.href='index.php?p=selecionar_slots&layout=5';" class="btn btn-default">Voltar</button>
		<button type="button" onclick="duplicar();" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i> Adicionar</button>
		<button type="button" onclick="salvar();" class="btn btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Salvar</button>
		<span id="resultado"></span>
	</div>
	<form action="">
		<input type="hidden" name="processar" value="true">
		<input type="hidden" name="rifa" value="<?php echo $rifa; ?>">
		<?php if(count($dado) > 0){ ?>
		<table class="table table-hover table-bordered">
			<thead>
				<tr>
					<td>Cód.</td>
					<td>Quant.</td>
					<td>Revendedor</td>
					<td>Bilhetes</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				<?php 
				$emb = false;
				$agr = false;
				if(count($reserva) > 0) foreach($reserva as $r){ 


					$listaBilhetes = str_in_array_de_num($r['bilhete']);

					?>
				<tr>
					<td><?php echo $r['nome_grupo']; ?></td>
					<td><?= count($listaBilhetes); ?></td>
					<td>
						<select name="revendedor[]" required class="form-control">
							<option value=""></option>
							<?php foreach($dado as $d){ ?>
							<option <?php if($r['revendedor'] == $d['usu_cod']) echo 'selected'; ?> value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>
							<?php } ?>
						</select>
					</td>
					<td style="    word-break: break-word;">
						<textarea rows="7" name="bilhete[]" <?php if($r['proveniente_da_compra']) echo 'readonly'; ?> required type="text" class="bilhete hidden form-control"><?php echo $r['bilhete']; ?></textarea>
						
					</td>
					<td class="text-right">
						<button onclick="exibir(<?= $r['codigo']; ?>, this);" type="button" class="btn btn-success">Exibir</button>
						<button onclick="editar(<?= $r['codigo']; ?>, this);" type="button" class="btn btn-primary">Editar</button>
						<button type="button"  onclick="deletar(this);" class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></button>
					</td>
				</tr>
				<?php } ?>
			</tbody>
			
		</table>
		
		<?php } else { ?>
		<div class="form-group">
			<p>Esta rifa não possui nenhum revendedor.</p>
		</div>
		<?php } ?>
		<div class="form-group col-sm-4">
			<p><label><input name="embaralhamento" <?php if($res['embaralhar']) echo 'checked="checked"'; ?> value="1" type="checkbox"> Com embaralhamento</label></p>
			<p><label><input name="agrupamento" <?php if($res['agrupar']) echo 'checked="checked"'; ?> value="1" type="checkbox"> Com agrupamento</label></p>
		</div>
		<div class="form-group col-sm-4">
			<label for="">Quantos números serão impressos em cada bilhete?</label>
			<select name="numeros_por_bilhete" id="numeros_por_bilhete" class="form-control">
				<option <?php if($res['numeros_por_bilhete'] == 1) echo 'selected'; ?> value="1">1</option>
				<option <?php if($res['numeros_por_bilhete'] == 2) echo 'selected'; ?> value="2">2</option>
				<option <?php if($res['numeros_por_bilhete'] == 3) echo 'selected'; ?> value="3">3</option>
				<option <?php if($res['numeros_por_bilhete'] == 4) echo 'selected'; ?> value="4">4</option>
				<option <?php if($res['numeros_por_bilhete'] == 5) echo 'selected'; ?> value="5">5</option>
			</select>
		</div>
	</form>
	<!-- Lugar de onde a linha é copiada -->
	<table class="esconder">
		<tr id="modelo" class="esconder">
				<td>#</td>
				<td>
					<select name="revendedor[]" required class="form-control">
						<option value=""></option>
						<?php foreach($dado as $d){ ?>
						<option value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>
						<?php } ?>
					</select>
				</td>
				<td>
					<input name="bilhete[]" required type="text" class="bilhete form-control">
				</td>
				<td class="text-right">
					<button type="button" onclick="deletar(this);" class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></button>
				</td>
			</tr>
	</table>
	<!-- fim -->
	
</div>
<script>
function getBilhetesFromReserva(reserva, onlyStr = false){

	show_loading();

	let dados = {};
	if(onlyStr)
		dados = {getReserva:reserva, onlyStr:true};
	else
		dados = {getReserva:reserva}

	$.post('page/revenda_loteamento.php', dados)
	.done(function(r){
		end_loading();
		console.log(r);
		return r.msg;
	})
	.fail(function(r){
		end_loading();
		console.log(r);
	});
}

function exibir(reserva, el){
	/*show_loading();

	let dados = {};
	if(onlyStr)
		dados = {getReserva:reserva, onlyStr:true};
	else
		dados = {getReserva:reserva}

	$.post('page/revenda_loteamento.php', dados)
	.done(function(r){
		end_loading();
		console.log(r);
		let input = $(el).parent().children();
		input.value(r.msg);
		input.removeClass('hidden');
	})
	.fail(function(r){
		end_loading();
		console.log(r);
	});*/
	let input = $(el).parent().parent().find('.bilhete');
	input.addClass('hidden');
	input.parent().find('span').remove();
	input.parent().append("<span>"+input.val()+"</span>");

}

function editar(reserva, el){
	let input = $(el).parent().parent().find('.bilhete');
	input.parent().find('span').remove();
	input.removeClass('hidden');
}

function deletar(elem){
	$(elem).parent().parent().remove();
}
function duplicar(){
	$('#modelo').clone().prependTo( "tbody" ).removeClass("esconder").find('input').val('');
}

function salvar(){
	console.log( $( 'form' ).serialize() );
	$('#resultado').html('Salvando... Aguarde!');
	$.post('page/revenda_loteamento.php', $( 'form' ).serialize())
	.done(function(r){
		console.log(r);
		r = JSON.parse(r);
		console.log(r);
		if(r.sucesso == 1){

			if(r.repetidos)
				alert("Os bilhetes:  \n\n" + r.repetidos + " \n\n foram retirados por serem repetidos. Atualize a página!");
			if(r.msg)
				$('#resultado').html(r.msg);
			else
				$('#resultado').html('Dados salvos.');
			return setTimeout(function(){ $('#resultado').html(''); }, 1000);
		}
		$('#resultado').html(r.msg);
		
	}).fail(function(res){
		console.log(res);
		$('#resultado').html('Falha ao salvar dados. Entre em contato com o administrador.');
	});
}
</script>