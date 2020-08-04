<?php

include('../class/conexao.php');

if($_POST['adicionar_sorteio1']){

	$codigo_da_rifa = intval($_POST['rifa']);
	$faixa = intval($_POST['faixa']);

	//DBExecute("DELETE FROM rifa_sorteio WHERE rifa = '$codigo_da_rifa'", $mysqli);
	$codigo_do_sorteio = DBInsert($mysqli, 'rifa_sorteio', array(
		'data'=> implode('-', array_reverse(explode('/', $_POST['data']))), 
		'base'=> intval($_POST['base']),
		'extracao'=>intval($_POST['extracao']),
		'faixa' =>$faixa,
		'rifa' => $codigo_da_rifa
	), array());

	die("<script>location.href='index.php?p=adicionar_sorteio2&codigo={$codigo_da_rifa}&sorteio={$codigo_do_sorteio}';</script>");
	
	unset($_POST);

}

$codigo_do_sorteio = intval($_GET['sorteio']);
$sorteio           = db_select($mysqli, "Select * from rifa_sorteio where codigo = '$codigo_do_sorteio'", 1);
$faixa             = $sorteio['faixa'];

if(count($_POST['numero']) > 0 && $_POST['rifa'] > 0){

	$codigo_da_rifa = intval($_POST['rifa']); 

	//DBExecute("DELETE FROM sorteio_dezenas WHERE rifa = '$codigo_da_rifa'", $mysqli);
	foreach($_POST['numero'] as $n){
		if(strlen($n) > 0){
			DBInsert($mysqli, 'sorteio_dezenas', array(
				'dezena'=> intval($n), 
				'rifa' => $codigo_da_rifa,
				'sorteio'=> intval($_POST['sorteio'])
			), array());
		}
	}

	die("<script>location.href='index.php?p=relatorio_sorteio&rifa=".$codigo_da_rifa."';</script>");

}

?>
<div class="col-lg-12 text-left">

	<h3>Adicionar Sorteios</h3>

	<hr>
</div>

<div class="col-lg-4 text-left">

	<form action="" method="POST">

		<input type="hidden" name="rifa" value="<?= intval($_GET['codigo']); ?>">
		<input type="hidden" name="sorteio" value="<?= intval($_GET['sorteio']); ?>">

		<?php for($k = 0; $k < $faixa; $k++){ ?>
		<div class="form-group">
			<input type="text" required name="numero[]" maxlength="2" placeholder="Número <?= ($k+1); ?>" class="form-control">
		</div>
		<?php } ?>

		<div class="form-group">
			<button type="submit" class="btn btn-success">Cadastrar</button>
		</div>
	
	</form>

</div>