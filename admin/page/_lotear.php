<?php

include("../class/conexao.php");

$creditos = db_select($mysqli, "select usu_credito from tbl_usuario where usu_cod = '$_SESSION[usuario]' limit 1", 1);
$creditos = floatval($creditos['usu_credito']);

$rifa = intval($_GET['codigo']);

// Cadastro de Vendedores na Rifa
if($_POST['qtd_lote']){

	$qtd = intval($_POST['qtd_lote']);
	echo "<script>
	
	if(confirm('Tem certeza? Esta ação vai descontar seu crédito e será irreversível.'))
		location.href='index.php?p=lotear_final&rifa=".$rifa."&divisor=".$_POST['qtd_lote']."';
	else
		location.href='index.php?p=rifa';

	</script>";

}

//Adição de Créditos
if(isset($_POST['valor']) && $_POST['valor'] > 0){

	$var = array();
	$var['valor'] = $_POST['valor'];
	$var['usuario'] = $_SESSION['usuario'];
	$var['data'] = date("Y-m-d h:i:s");
	$var['aprovada'] = false;

	$r = DBInsert($mysqli, "tbl_transacao", $var, 0);

	if($r){
		echo "<script>location.href='index.php?p=pagar_credito&transacao=".$r."';</script>";
	}else
		$erro[] = "Ops! Alguma coisa deu errado ao solicitar seu crédito.";

}

$minimo = 15.00;
$possui_creditos = true;

// Se não possuir a quantidade mínima de créditos
if($creditos < $minimo){

	$erro[] = "Você não possui créditos suficientes para isso. Adicione créditos antes de prosseguir:
	<form method=\"post\" class=\"form-inline\">
	<select class=\"form-control\" name=\"valor\">
		<option value=\"\">...</option>
		<option value=\"".$minimo."\">R$ ".number_format($minimo, 2, ',', '.')."</option>
	</select>
	<button class=\"btn btn-success\">Pagar</button></form>";
	$possui_creditos = false;

}

$v = db_select($mysqli, "select * from vendedor_rifa where rifa = '$rifa' order by grupo ASC");
$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
$bilhetes = $bilhetes['rifa_maxbilhetes'];

if($bilhetes > 900 && $bilhetes < 1200)
	$bilhetes = 1000;
elseif($bilhetes > 3000)
	$bilhetes = 10000;

?>
<form action="" method="post">
	<style>.esconder{display:none}</style>
	<div class="col-lg-12 text-left">
		<h3>Lotear</h3>
		<hr>
		
		<?php if(count($erro) > 0){ ?>
		<div style="padding:10px; margin:10px 0 10px 0;" class="col-lg-12 bg-warning text-center">
			<?php foreach($erro as $msg) echo "<p>{$msg}</p>"; ?>
		</div>
		<?php } ?>
	</div>
	<?php if($possui_creditos){ ?>
	<div class="col-lg-6 text-left">
		
		<div class="col-lg-3 form-group">
			<label for="">Bilhetes Nesta Rifa </label>
			<input name="qtd_lote" disabled value="<?php echo $bilhetes; ?>" id="qtd_lote" type="number" class="form-control">
		</div>
	

		<div class="col-lg-3 form-group">
			<label for="">Divisor</label>
			<input name="qtd_lote" onblur="javascript: criar_grupos(<?php echo $bilhetes; ?>, this.value, this);" id="qtd_lote" type="number" class="form-control">
		</div>

		<div class="col-lg-3 form-group">
			<label>Número de Grupos</label>
			<input disabled id="bil_grupo" value="" class="form-control" type="text">
		</div>

		<div class="clearfix"></div>

		




	</div>
	<!--
	<div class="col-lg-6 table-responsive">
		<div class="col-lg-12">
		<label for="">Adicionar Vendedores</label>
		
		<table class="table table-responsive table-hover">
			<thead>
				<tr>
					<td>Vendedor</td>
					<td>Grupo</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				<?php
				$k = 0;
				?>
				<tr>
					<td>
						<input value="<?php echo $v[$k]['vendedor']; ?>" name="vendedor[]" class="form-control">
					</td>
					<td>
						<select name="grupo[]" class="grupo form-control">
							<option value="">...</option>

						</select>
					</td>

					<td>
						<button onclick="novo_vendedor();" type="button" class="btn btn-success"><i class="glyphicon glyphicon-plus-sign"></i></button>
					</td>
				</tr>
				<tr class="esconder" id="tr_vendedor">
					<td>
						<input value="" name="vendedor[]" class="form-control">
					</td>
					<td>
						<select name="grupo[]" class="grupo form-control">
							<option value="">...</option>

						</select>
					</td>

					<td>
						<button type="button" onclick="remover_vendedor(this);" class="btn btn-danger">
							<i class="glyphicon glyphicon-trash"></i>
						</button>
					</td>
				</tr>
				<?php
				$k= 1;
				while($v[$k]){
				?>
				<tr>
					<td>
						<input value="<?php echo $v[$k]['vendedor']; ?>" name="vendedor[]" class="form-control">
					</td>
					<td>
						<select name="grupo[]" class="grupo form-control">
							<option value="">...</option>

						</select>
					</td>

					<td>
						<button type="button" onclick="remover_vendedor(this);" class="btn btn-danger">
							<i class="glyphicon glyphicon-trash"></i>
						</button>
					</td>
				</tr>
				<?php
					$k++;
				}
				?>
			</tbody>
		</table>
		
		</div>
		<div class="clearfix"></div>
	</div>-->

	<script>
/*
	function novo_vendedor(){
		$('#tr_vendedor').clone().insertAfter("#tr_vendedor").attr("id","").removeClass("esconder");
	}

	function remover_vendedor(elemento){
		$(elemento).parent().parent().remove();
	}

	
function toExcelHeader(index) {
    if(index <= 0) {
        throw new Error("index must be 1 or greater");
    }
    index--;
    var charCodeOfA = ("a").charCodeAt(0); // you could hard code to 97
    var charCodeOfZ = ("z").charCodeAt(0); // you could hard code to 122
    var excelStr = "";
    var base24Str = (index).toString(charCodeOfZ - charCodeOfA + 1);
    for(var base24StrIndex = 0; base24StrIndex < base24Str.length; base24StrIndex++) {
        var base24Char = base24Str[base24StrIndex];
        var alphabetIndex = (base24Char * 1 == base24Char) ? base24Char : (base24Char.charCodeAt(0) - charCodeOfA + 10);
        // bizarre thing, A==1 in first digit, A==0 in other digits
        if(base24StrIndex == 0) {
            alphabetIndex -= 1;
        }
        excelStr += String.fromCharCode(charCodeOfA*1 + alphabetIndex*1);
    }
    return excelStr.toUpperCase();
}


function criar_grupos(numero_bilhetes, numero_grupos, input){


	if(numero_bilhetes % numero_grupos > 0){
		input.value = '';
		input.focus();
		alert('A quantidade de Bilhetes por Lote não pode ser ' + numero_grupos + ' porque ' + numero_bilhetes + ' (Número de bilhetes dessa Rifa) não é divisível por ' + numero_grupos);
		return;
	}

	var numero_headers = numero_bilhetes/numero_grupos, i = 0, k =0;


	$('.grupo')
	    .find('option')
	    .remove()
	;

	$('.grupo').append($('<option>', {
			    value: '',
			    text: '...'
			}));

	for(i = 2; i <= numero_headers+1; i++){

		for(k = 1; k <= numero_grupos; k++){
			$('.grupo').append($('<option>', {
			    value: toExcelHeader(i) +'-' + k,
			    text: toExcelHeader(i) +'-' + k
			}));

		}

	}


}*/

function criar_grupos(numero_bilhetes, numero_grupos, input){


	if(numero_bilhetes % numero_grupos > 0){
		input.value = '';
		input.focus();
		alert('A quantidade de Bilhetes por Lote não pode ser ' + numero_grupos + ' porque ' + numero_bilhetes + ' (Número de bilhetes dessa Rifa) não é divisível por ' + numero_grupos);
		$('#bil_grupo').val('');
		return;
	}

	if(numero_bilhetes % numero_grupos == 0)
		$('#bil_grupo').val(numero_bilhetes / numero_grupos);
	else
		$('#bil_grupo').val('');

}

</script>

	<div class="col-lg-12 form-group text-left">
				<button class="btn-success btn" type="submit" name="enviar" value="ok">LOTEAR</button>
			</div>
</form>
	<?php } ?>
