<?php



if(isset($_POST['processar'])){

	$rifa = intval($_POST['rifa']);

	include("../../class/conexao.php");

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


	}

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

	$mysqli->query("delete from opcao_reserva where rifa = '$rifa'");
	$mysqli->query("insert into opcao_reserva (rifa, agrupar, embaralhar) values('$rifa', '$agrupamento', '$embaralhamento')");

	$j = 1;

	$sql_code = array();

	if(!is_array($_POST['revendedor'])){
		$resultado['sucesso'] = 1;
		$resultado['msg'] = "";
		$mysqli->query("delete from reserva where rifa = '$rifa'");
		echo json_encode($resultado);
		exit();
	}

	$tmp = verificar_bilhetes_repetidos($_POST['bilhete']);
	if($tmp){
		$resultado['sucesso'] = 0;
		$resultado['msg'] = "Erro! O bilhete $tmp consta em mais de uma linha.";
		echo json_encode($resultado);
		exit();
	}


	foreach($_POST['revendedor'] as $k=>$rev){

		$str = $_POST['bilhete'][$k];

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
							'".$mysqli->escape_string($str)."',
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

	}

	

	echo json_encode($resultado);

	exit();
}

if(isset($_POST['clonar_bilhetes'])){

	include("../class/conexao.php");

	$rifa = intval($_POST['rifa']);
	$rifa_atual = intval($_GET['rifa']);

	$reserva = db_select($mysqli, "select revendedor, bilhete, nome_grupo, rifa, embaralhar, agrupar from reserva where rifa = '$rifa'");
	$mysqli->query("delete * from reserva where rifa = '$rifa_atual'");

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

		$r['rifa'] = $rifa_atual;
		DBInsert($mysqli, 'reserva', $r, array());
	}
	echo "<script>alert('Informações copiadas com sucesso!');</script>";

}

$rifa    = intval($_GET['rifa']);
$dado    = db_select($mysqli, "select u.usu_nome, u.usu_cod from tbl_usuario u, tbl_revendedor r where u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa'");
$reserva = db_select($mysqli, "select * from reserva where rifa = '$rifa'");
$rifas   = db_select($mysqli, "select ri.rifa_cod, ri.rifa_titulo from reserva r, tbl_rifas ri where r.rifa = ri.rifa_cod and ri.rifa_cod != '$rifa'");
$res     = db_select($mysqli, "select embaralhar, agrupar from opcao_reserva where rifa = '$rifa' limit 1", 1);
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
		<button type="submit" class="btn btn-default">Clonar</button>
	</div>
	</form>
</div>
<style>
	.esconder{display:none;}
</style>
<div class="col-lg-12 col-md-12">

	
	<div class="form-group form-inline">
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
					<td>Revendedor</td>
					<td>Bilhetes</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				<?php 
				$emb = false;
				$agr = false;

				if(count($reserva) > 0) foreach($reserva as $r){ ?>
				<tr>
					<td><?php echo $r['nome_grupo']; ?></td>
					<td>
						<select name="revendedor[]" required class="form-control">
							<option value=""></option>
							<?php foreach($dado as $d){ ?>
							<option <?php if($r['revendedor'] == $d['usu_cod']) echo 'selected'; ?> value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>
							<?php } ?>
						</select>
					</td>
					<td>
						<input name="bilhete[]" value="<?php echo $r['bilhete']; ?>" required type="text" class="form-control">
					</td>
					<td class="text-right">
						<button type="button" onclick="deletar(this);" class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></button>
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
		<div class="form-group">
			<p><label><input name="embaralhamento" <?php if($res['embaralhar']) echo 'checked="checked"'; ?> value="1" type="checkbox"> Com embaralhamento</label></p>
			<p><label><input name="agrupamento" <?php if($res['agrupar']) echo 'checked="checked"'; ?> value="1" type="checkbox"> Com agrupamento</label></p>
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
					<input name="bilhete[]" required type="text" class="form-control">
				</td>
				<td class="text-right">
					<button type="button" onclick="deletar(this);" class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></button>
				</td>
			</tr>
	</table>
	<!-- fim -->
	
</div>
<script>
function deletar(elem){
	$(elem).parent().parent().remove();
}
function duplicar(){
	$('#modelo').clone().prependTo( "tbody" ).removeClass("esconder").find('input').val('');
}
function salvar(){
	console.log( $( 'form' ).serialize() );
	$.post('page/revenda_loteamento.php', $( 'form' ).serialize())
	.done(function(r){
		console.log(r);
		r = JSON.parse(r);
		console.log(r);

		if(r.sucesso == 1){
			$('#resultado').html('Dados salvos.');
			return setTimeout(function(){ $('#resultado').html(''); }, 1000);
		}

		$('#resultado').html(r.msg);
		
	});
}

</script>