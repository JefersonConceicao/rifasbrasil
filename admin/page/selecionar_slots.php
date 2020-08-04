<?php
if($_POST['verificar_tipo']){
	include("../../class/conexao.php");	
	foreach($_POST['rifa'] as $codigo){
		$res = db_select($mysqli, "select embaralhar, agrupar, numeros_por_bilhete from opcao_reserva where rifa = '$codigo' limit 1", 1);
		if($res['numeros_por_bilhete'] > 1)
			die(json_encode(array('servidor'=>'new_server2')));
	}
	die(json_encode(array('servidor'=>'new_server')));
}
include("../class/conexao.php");
$nslots['1'] = 6;
$nslots['2'] = 3;
$nslots['3'] = 2;
$nslots['4'] = 2;
$nslots['5'] = 2;
$nslots['6'] = 10;
$rifa = db_select($mysqli, "select r.rifa_titulo, r.rifa_cod, u.usu_nome, u.usu_celular, u.usu_residencial, r.rifa_maxbilhetes from lote l, tbl_rifas r, tbl_usuario u where l.grafica = '".$_SESSION['usuario']."' and l.rifa = r.rifa_cod and u.usu_cod = r.rifa_dono and r.rifa_cod");
?>
<div class="col-lg-12">
	<h1 class="page-header">Insira as Rifas em seus Slots</h1>
</div>
<div class="col-lg-12 col-md-12">
	<p class="text-center">
		Você deseja imprimir uma única rifa?<br>
		<label>
			<input onclick="rifa_unica(this);" value="1" type="checkbox"> Sim, layout com rifa única.
		</label>
	</p>
	<form id="varias_rifas" action="http://rifasbrasil.com.br/servidor/new_server/index.php" method="get">
		<input type="hidden" name="verificar_tipo" value="true">
	<div  class="form-group col-lg-12 col-md-12">
		<table class="table table-hover table-bordered">	
			<thead>
				<tr>
					<td>Slots</td>
					<td>Rifa</td>
				</tr>
			</thead>
			<tbody>
				<?php for($i=1; $i <= $nslots[$_GET['layout']]; $i++){ ?>
				<tr>
					<td valign="middle">
						<label for="">Slot <?php echo $i; ?>:</label>
					</td>
					<td class="submit_varias_rifas form-inline">
						<select class="form-control" onchange="rifa_select();" name="rifa[]" class="rifa" id="rifa_<?php echo $i; ?>">
							<option value="">Selecione</option>
							<?php foreach($rifa as $r){ if($r['rifa_maxbilhetes'] > 150) { ?>
							<option value="<?php echo $r['rifa_cod']; ?>"><?php echo $r['rifa_titulo']; ?> - <?php echo $r['rifa_maxbilhetes']; ?> bilhetes</option>
							<?php } } ?>
						</select>
						<button type="button" onclick="reservar(this);" class="btn btn-xs btn-default">Organizar Revenda</button>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class=" form-group form-inline">
			<input name="layout" value="<?php echo $_GET['layout']; ?>" type="hidden">
			<button type="button" onclick="submit_varias_rifas();" class="btn btn-primary">Prosseguir</button>
		</div>
</form>
	</div>
		<form style="display:none;" id="rifa_unica" action="http://rifasbrasil.com.br/servidor/new_server/index.php" method="get">
			<div  class="form-group col-lg-12 col-md-12 form-inline">
				<label>Seleciona a Rifa</label>
				<select name="rifa" class="rifa" id="rifa" class="form-control">
					<option value="">Selecione</option>
					<?php foreach($rifa as $r){ ?>
					<option value="<?php echo $r['rifa_cod']; ?>"><?php echo $r['rifa_titulo']; ?> - <?php echo $r['rifa_maxbilhetes']; ?> bilhetes</option>
					<?php } ?>
				</select>
				<button type="button" onclick="reservar(this);" class="btn btn-xs btn-default">Organizar Revenda</button>
			</div>
			<div class="form-group col-lg-12 col-md-12">
				<input name="layout" value="<?php echo $_GET['layout']; ?>" type="hidden">
				<button type="button" onclick="submit_unica_rifa();" class="btn btn-primary">Prosseguir</button>
			</div>
		</form>
</div>
<script>
var rifas = [];
function rifa(maxb, c, n){
		this.maxb = maxb;
		this.codigo = c;
		this.nome = n;
}
	<?php foreach($rifa as $r){
		echo 'rifas.push(new rifa('.$r['rifa_maxbilhetes'].', '.$r['rifa_cod'].', "'.$r['rifa_titulo'].'"));'."\n";
	} ?>	
function rifa_unica(val){
	if($(val).prop('checked')){
		$('#rifa_unica').css('display', '');
		$('#varias_rifas').css('display', 'none');
	}else{
		$('#varias_rifas').css('display', '');
		$('#rifa_unica').css('display', 'none');
	}
}
function get_max(cod){
	var resultado;
	for(var i = 0; i < rifas.length; i++){
		if(rifas[i].codigo == cod)
			resultado = rifas[i].maxb;
	}
	if(resultado % 10 == 1)
		return (resultado-1);
	else
		return resultado;
}
function rifa_select(){
	var qtd_base = $('#rifa_1').val(), tmp;
	// Desabilitar rifas que não contém mesma quantidade de bilhetes
	for(var i = 0; i < rifas.length; i++){
		if(rifas[i].codigo == qtd_base)
			qtd_base = rifas[i].maxb;
	}
	if(qtd_base % 10 == 1) qtd_base--;
	$.each($('#rifa_1 option:selected'), function(i, v){
		maxb = get_max($(v).val());
		if(maxb != qtd_base)
			$('select option[value='+$(v).val()+']').attr('disabled', true);
	});
	$('#rifa_unica option').attr('disabled', false);
}
function submit_varias_rifas(){
	var autorizar = true;
	$('.submit_varias_rifas select').each(function(index){
		if($(this).val() == '') autorizar = false;
	});
	if(autorizar){
		//if($('#bilhetesPorRifa option:selected').val() > 1)
			//$('#varias_rifas').attr('action', 'http://rifasbrasil.com.br/servidor/new_server/index.php');
		$.post('page/selecionar_slots.php', $('#varias_rifas').serialize()).done(function(res){
			
			console.log(res);
			var fim = JSON.parse(res);
			$('#varias_rifas').attr('action', "http://rifasbrasil.com.br/servidor/"+ fim.servidor +"/index.php");
			$('#varias_rifas').submit();
		}).fail(function(res){
			console.log(res);
		});
		
	}else
		alert('Preencha todos os slots.');
}
function submit_unica_rifa(){
	if($('#rifa').val() != '')
		$('#rifa_unica').submit();
	else
		alert('Escolha a rifa');
}
function reservar(elem){
	var valor = $(elem).parent().find('select').val();
	if(valor != '')
		window.open('index.php?p=revenda_loteamento&rifa=' + valor, '_blank');
	else
		alert('Selecione a rifa');
}
</script>