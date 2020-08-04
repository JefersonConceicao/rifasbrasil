<?php

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

if($_POST['deletar']){
	include('../../class/conexao.php');
	$sorteio = intval($_POST['deletar']);
	DBExecute("DELETE FROM rifa_sorteio WHERE codigo = '$sorteio'", $mysqli);
	DBExecute("DELETE FROM sorteio_dezenas WHERE sorteio = '$sorteio'", $mysqli);
	die('1');
}

if($_POST['novo_texto']){

	include('../../class/conexao.php');
	$tipo = $mysqli->escape_string($_POST['tipo']);
	$valor = $mysqli->escape_string($_POST['novo_texto']);

	$codigo = DBInsert($mysqli, 'sorteio_texto', array('valor'=>$valor, 'tipo'=>$tipo), array());

	die(json_encode(array('codigo'=>$codigo)));

}

include('../class/conexao.php');
$base = db_select($mysqli, "SELECT * FROM sorteio_texto WHERE tipo = 'base'");
$extrato = db_select($mysqli, "SELECT * FROM sorteio_texto WHERE tipo = 'extracao'");
$rifa = intval($_GET['codigo']);
?>
<div class="col-lg-12 text-left">

	<h3>Adicionar Sorteios</h3>
	<hr>

</div>

<div class="col-lg-6 text-left">

	<form method="post" action="index.php?p=adicionar_sorteio2&codigo=<?= $_GET['codigo']; ?>">

		<input type="hidden" name="rifa" value="<?= $_GET['codigo'] ?>">
		<input type="hidden" name="adicionar_sorteio1" value="true">

		<div class="form-group">
			<label for="">Informe a Data</label>
			<input name="data" placeholder="00/00/0000" type="text" class="datepicker form-control">
		</div>

		<div class="form-group">
			<label for="">Selecione a Base</label>
			<select name="base" id="base" onchange="abrirModal('base');" class="form-control">
				<option value="">Selecione</option>
				<?php foreach($base as $d){ ?>
				<option value="<?= $d['codigo']; ?>"><?php echo $d['valor']; ?></option>
				<?php } ?>
				<option value="-1">Cadastrar uma Nova</option>
			</select>
		</div>

		<div class="form-group">
			<label for="">Informe a Extração</label>
			<select name="extracao" id="extracao" onchange="abrirModal('extracao');" class="form-control">
				<option value="">Selecione</option>
				<?php foreach($extrato as $d){ ?>
				<option value="<?= $d['codigo']; ?>"><?php echo $d['valor']; ?></option>
				<?php } ?>
				<option value="-1">Cadastrar uma Nova</option>
			</select>
		</div>

		<div class="form-group">
			<label for="">Informe a Faixa</label>
			<select name="faixa" id="" required class="form-control">
				<option value="">Selecione</option>
				<option value="5">1 ao 5</option>
				<option value="10">1 ao 10</option>
			</select>
		</div>

		<div class="form-group">
			<button type="submit" class="btn btn-success">Adicionar!</button>
		</div>
	</form>
</div>

<?php

$sorteios = db_select($mysqli, "SELECT b.valor as base, e.valor as extracao, rs.codigo, faixa, data from rifa_sorteio rs, sorteio_texto as b, sorteio_texto as e 
	where rs.rifa = '$rifa'
	and b.codigo = rs.base
	and e.codigo = rs.extracao");
$bilhetes_sorteados = db_select($mysqli, "SELECT * FROM sorteio_dezenas where rifa = '$rifa'");
$bilhetes_por_sorteio = array();



foreach($bilhetes_sorteados as $bs){
	$bilhetes_por_sorteio[$bs['sorteio']][] = $bs;
}

?>

<div class="col-lg-6 text-left">
	
	<div class="form-group">
		<label for="">Sorteios já realizados</label>
	</div>

	<div class="form-group">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td>Data</td>
					<td>Base</td>
					<td>Extração</td>
					<td>Faixa</td>
					<td>Bilhetes</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach($sorteios as $s){ ?>
				<tr>
					<td><?= implode('/', array_reverse(explode('-', $s['data']))); ?></td>
					<td><?= $s['base']; ?></td>
					<td><?= $s['extracao']; ?></td>
					<td><?= $s['faixa']; ?></td>
					<td><?php foreach($bilhetes_por_sorteio[$s['codigo']] as $bil){ echo "[".str_pad($bil['dezena'], 2, '0', STR_PAD_LEFT)."] "; } ?></td>
					<td><button onclick="deletar_sorteio('<?= $s['codigo']; ?>', this);" class="btn btn-xs btn-danger">Deletar</button></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	
</div>

<!-- Modal -->
<div id="modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Qual o nome?</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
        	<label for="">Nome</label>
        	<input type="text" name="nome" id="nome" class="form-control">
        </div>
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
        <button id="btn_modal" type="button" class="btn btn-success">SALVAR</button>
      </div>
    </div>

  </div>
</div>

<!-- Modal -->

<script>
	function novo_elemento(tabela){

		var dados = $('#nome');
		
		$.post('page/adicionar_sorteio.php', {novo_texto:dados.val(), tipo:tabela})
		.done(function(r){

			r = JSON.parse(r);
			$('#' + tabela + ' option:first-child').after('<option value="'+ r.codigo +'" selected>'+ dados.val() +'</option>');
			dados.val('');
			$('#modal').modal('hide');

		})
		.fail(function(r){
			console.log(r);
		});

	}
	
	function abrirModal(tipo){

		if($('#' + tipo + " option:selected").val() > 0)
			return;

		$('#' + tipo + " option:selected").attr('selected', false);
		$('#btn_modal').off().click(
			function(){novo_elemento(tipo);}
		);
		$('#modal').modal('show');

	}

	function deletar_sorteio(codigo, el){
		$.post('page/adicionar_sorteio.php', {deletar:codigo})
		.done(function(r){
			console.log(r);
			if(r == 1)
				$(el).parent().parent().remove();
		})
		.fail(function(r){
			console.log(r);
		});
	}

</script>