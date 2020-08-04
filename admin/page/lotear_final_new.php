<?php

include("../class/conexao.php");

if(!$_POST['rifa'])
	$_POST['rifa'] = $_GET['rifa'];

$rifa = intval($_POST['rifa']);

$credito_minimo = 15;

function get_grupos($mysqli, $rifa){
	$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);
	$bilhetes = $bilhetes['rifa_maxbilhetes'];
	$grupo    = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);
	if($bilhetes > 900 && $bilhetes < 1200)
		$bilhetes = 1000;
	elseif($bilhetes > 3000)
		$bilhetes = 10000;
	$grupos = ceil($bilhetes / $grupo['grupo']);
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

//Adição de Créditos

if(!empty($_POST['rifa'])){
	$creditos = db_select($mysqli, "select usu_credito from tbl_usuario where usu_cod = '$_SESSION[usuario]' limit 1", 1);
	$lotes = db_select($mysqli, "select count(*) as t from lote where rifa = '$rifa'", 1);
	$creditos = floatval($creditos['usu_credito']);
	if($credito_minimo >= 15 || $lotes['t'] > 0){
		$var = array();
		$tra = array();
		$tra['valor'] = -15;
		$tra['usuario'] = $_SESSION['usuario'];
		$tra['data'] = $var['data'];
		$tra['aprovada'] = 1;
		$var['rifa'] = $rifa;
		$var['grafica'] = $_POST['grafica'];
		$var['grupo'] = $_POST['grupo'];
		$var['data'] = date("Y-m-d h:i:s");
		if($lotes['t'] == 0){
			$r = DBInsert($mysqli, "lote", $var, 0);
			$r = DBInsert($mysqli, "tbl_transacao", $tra, 0);
			DBExecute("update tbl_usuario set usu_credito = usu_credito - '$credito_minimo' where usu_cod = '$_SESSION[usuario]'", $mysqli);
		}
		$v = db_select($mysqli, "select * from vendedor_rifa where rifa = '$rifa' order by grupo ASC");
	//$bilhetes_finais = get_bilhetes($mysqli, $rifa);
		$titulo_grupos = get_grupos($mysqli, $rifa);
		?>
		<body >
			<div class="col-lg-12">
				<h1 class="page-header">
					Grupos e Bilhetes Gerados
				</h1>
			</div>
			<div class="col-lg-12 form-group form-inline">
				<button onclick="location.href='index.php?p=lotear'" class="btn btn-default">Voltar</button>
				<input id="codigo_rifa" type="text" class="form-control">
				<button onclick="pesquisar_bilhetes();" class="btn btn-primary">Pesquisar Bilhetes</button>
				<button onclick="pesquisar_grupo();" class="btn btn-primary">Pesquisar GRUPO</button>
			</div>
			<div class="col-lg-12 table-responsive">
				<div class="col-lg-12 table-responsive" id="resultado"></div>
<!--
<table class="table table-hover table-bordered">
	<thead>
		<tr style="font-weight:bold;">
			<?php
				foreach($bilhetes_finais as $t=>$v){
					echo "<td>$t</td>";
					$titulo_grupos[] = $t;
				}
			?>
		</tr>
	</thead>
	<tbody>
			<?php
			for($i = 0; $i < count($bilhetes_finais['A1']); $i++){
				echo "<tr>";
				foreach($bilhetes_finais as $t=>$v)
					echo "<td>".$bilhetes_finais[$t][$i]."</td>";
				echo "</tr>";
			}
			?>
	</tbody>
</table>-->


</div>

<div class="col-lg-12">
	<h1 class="page-header">
		Revendedores
	</h1>

</div>

<div class="col-lg-12">

	<?php

	$dado = db_select($mysqli, "SELECT u.usu_nome, u.usu_cod 
		from tbl_usuario u, tbl_revendedor r where u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa' group by r.usu_cod");
	
	if(count($dado) == 0) echo "<p>Você não cadastrou nenhum revendedor para esta rifa.</p>"; else {  ?>

	<div class="form-group">
		<button onclick="novo();" type="button" class="btn  btn-success"><i class="glyphicon glyphicon-plus"></i> adicionar</button>
		<button type="button" onclick="save();" class="btn pull-right btn-primary">Salvar</button>
	</div>

	<table class="table">
		<thead>
			<tr>
				<td>Vendedor</td>
				<td>Lotes</td>
				<td>Observação</td>
			</tr>
		</thead>
		<tbody id="_tbody">
			<?php
			$linha = db_select($mysqli, "select * from revenda where rifa= '$rifa'");
			$tds_grupos =  db_select($mysqli, "select grupo from grupo_revendedor where revenda in(select codigo from revenda where rifa = '$rifa' and pagamento = 10)");
			$tds_grupos_tmp = array();
			foreach($tds_grupos as $td)
				$tds_grupos_tmp[] = $td['grupo'];
			foreach($linha as $l){
				$grupos = db_select($mysqli, "select * from grupo_revendedor where revenda = '$l[codigo]'");
				$grupos_tmp = array();
				if(count($grupos) > 0){
					foreach($grupos as $g)
						$grupos_tmp[] = $g['grupo'];
					?>
					<tr id="" class=" item form-group form-inline">
						<td>
							<?php 
							foreach($dado as $d){
								if($l['vendedor'] == $d['usu_cod']) echo "<p class=\"esconder\">{$d['usu_cod']}</p>{$d['usu_nome']}";
							}
							?>
						</td>
						<td>
							<?php 
							if(count($grupos) == 0){
						// faz nada
							} else { 
								foreach($grupos as $g){
									$grupos_ja_eliminados[] = $g['grupo'];
									?>
									<span style="margin-right:10px;" class="label label-default">
										<i><?= $g['grupo']; ?></i> 
										<button onclick="delete_one_lote(this, '<?= $g['codigo']; ?>');" type="button" class="btn btn-danger btn-xs">X</button>
									</span>  
									<?php 
								}
							} ?> 
						</td>
						<td>
							<?php echo $l['pagamento']; ?>
						</td>
						<td class="text-right">
							<button type="button" onclick="delete_one_line(this, '<?= $l['codigo']; ?>');" class="btn btn-danger">Deletar</button>
							<button type="button" onclick="modal_editar(this);" class="btn btn-primary">Editar</button>
						</td>
					</tr>
					<?php } } ?>
				</tbody>
			</table>
			<?php 
		} ?>
	</div>
	<!-- Modal -->
	<div id="modal_editar" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Editar Loteamento</h4>
				</div>
				<form id="inputs_loteamento">
					<div class="modal-body">
						<div class="form-group">
							<label for="">Revendedor</label>
							<select name="editar_revendedor" id="editar_revendedor" class="form-control">
								<option selected value="-1">Revendedor</option>
								<?php foreach($dado as $d){ ?>
								<option value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="form-group">
							<label for="">Observações</label>
							<textarea name="editar_observacao" id="editar_observacao" class="form-control" id="" cols="3" rows="2"></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" id="salvar_alteracao" class="btn btn-default" >Salvar</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<!-- Modal -->
	<div id="modal_add" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Adicionar Loteamento</h4>
				</div>
				<form id="inputs_loteamento">
					<div class="modal-body">
						<div class="form-group">
							<label for="">Revendedor</label>
							<select name="revendedor_codigo" id="revendedor_codigo" class="form-control">
								<option value="-1">Revendedor</option>
								<?php foreach($dado as $d){ ?>
								<option value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="form-group">
							<label for="">Lotes</label>
							<select type="text"  name="lotes_revendedor" id="lotes_revendedor" style="margin-right:5px;"  onchange="unset_intervalos();" class="form-control lotes">
								<option value="-1">Lote</option>
								<?php $grupos_str = ""; foreach($titulo_grupos as $t) { if(!in_array($t, $grupos_ja_eliminados)) echo "<option value=\"$t\">$t</option>"; else echo "<option disabled value=\"$t\">$t</option>"; $grupos_str .= "'$t',";} $grupos_str = substr($grupos_str, 0, -1); ?>
							</select>
						</div>
						<div class="form-group">
							<label for="">Intervalo de Lotes</label>
							<p><select type="text"  name="lotes_intervalo_start" required id="lotes_intervalo_start" style="margin-right:5px;"  onchange="unset_lote();" class="form-control lotes">
								<option value="-1">Lote</option>
								<?php foreach($titulo_grupos as $t) if(!in_array($t, $grupos_ja_eliminados)) echo "<option value=\"$t\">$t</option>"; else echo "<option disabled value=\"$t\">$t</option>"; ?>
							</select></p>
							<p><select type="text"  name="lotes_intervalo_end" id="lotes_intervalo_end" style="margin-right:5px;"  onchange="unset_lote();" class="form-control lotes">
								<option value="-1">Lote</option>
								<?php foreach($titulo_grupos as $t) if(!in_array($t, $grupos_ja_eliminados)) echo "<option value=\"$t\">$t</option>"; else echo "<option disabled value=\"$t\">$t</option>"; ?>
							</select></p>
						</div>
						<div class="form-group">
							<label for="">Observações</label>
							<textarea name="observacao" id="observacao" class="form-control" id="" cols="3" rows="2"></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" id="btn_loteamento" onclick="adicionar_lote();" class="btn btn-default" >Adicionar</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php 

}else{
	echo "
	<script>
	alert('Créditos insuficientes'); 
	location.href='index.php?p=lotear&codigo=".$_GET['rifa']."';
	</script>
	";

}

}else{
	echo "
	<script>
	location.href='index.php?p=rifa';
	</script>
	";

}

?>

<script>

var LOTEAMENTOS = [];

var LOTES_JA_USADOS = [];

var GRUPOS = [<?=$grupos_str?>];

function modal_editar(el){
	var linha      = $(el).parent().parent(); // seleciona a tr
	var revendedor = $('#editar_revendedor option:selected');
	var obs        = linha.children(':eq(2)').html().trim();
	$('#editar_revendedor option[value="' + linha.children(':first').children(':first').html() + '"]').attr('selected', true);
	//revendedor.attr('selected', false);
	$('#editar_observacao').val(obs);
	$('#modal_editar').modal('show');
	$('#salvar_alteracao').click(function(){
		
		$('#salvar_alteracao').click(function(){});
		obs        = $('#editar_observacao').val();
		revendedor = $('#editar_revendedor option:selected');
		if(revendedor.val() == -1)
			return alert("Selecione um revendedor!");
		linha.children(':first').html("<p class=\"esconder\">" + revendedor.val() + "</p>" + revendedor.text());
		linha.children(':eq(2)').html(obs);
		$('#modal_editar').modal('hide');
	});
	
}
function adicionar_lote(){
	console.log($('#inputs_loteamento').serialize());
	// VARIABLES
	var vendedor = $('#revendedor_codigo option:selected').val();
	var nome_vendedor = $('#revendedor_codigo option:selected').text();
	var lote = $('#lotes_revendedor option:selected').val();
	var obs = $('#observacao').val();
	var str_grupos = "";
	if(vendedor <= 0)
		return alert("Selecione o Revendedor.");
	if(lote <= 0){
		// VARIABLES
		var start = $('#lotes_intervalo_start option:selected').val();
		var end   = $('#lotes_intervalo_end option:selected').val();
		var adicionar = false;
		var i_start = -1, i_end = -1;
		// validade
		if(!start || !end || start <= 0 || end <= 0)
			return alert("Selecione os dois campos do intervalo.");
		// cria o vetor de grupos que fazem parte
		for(var k = 0; k < GRUPOS.length; k++){
			if(start == GRUPOS[k]){
				adicionar = true;
				i_start = k;
			}
			if(adicionar){
				str_grupos += '"'+GRUPOS[k]+'",';
				LOTES_JA_USADOS.push(GRUPOS[k]);
			}
			if(end == GRUPOS[k]){
				adicionar = false;
				i_end = k;
			}	
		}
		// validade
		if(i_end < i_start)
			return alert("O intervalo inicial deve ser menor que o intervalo final.");
		for(i_start; i_start <= i_end; i_start++){
			$('#lotes_intervalo_start option:eq(' + (i_start+1) + ')').attr('disabled', true);
			$('#lotes_revendedor option:eq(' + (i_start+1) + ')').attr('disabled', true);
			$('#lotes_intervalo_end option:eq(' + (i_start+1) + ')').attr('disabled', true);
		}
		str_grupos = str_grupos.slice(0, -1);
	}else if(lote){
		LOTES_JA_USADOS.push(lote);
		str_grupos = '"'+lote+'"';
		$('#lotes_revendedor option:selected').attr('disabled', true);
	}else
	return alert("Selecione o Lote ou o Intervalo de Lotes para prosseguir.");
	
	// MORE VARIABLES
	var temporary_str = '{"vendedor":"'+vendedor+'", "observacao":"'+obs+'", "grupos":['+str_grupos+']}';
	var el_id = LOTEAMENTOS.push(temporary_str)-1;
	var table_str = "<tr><td><p class=\"esconder\">"+vendedor+"</p> "+nome_vendedor+"</td><td>";
	var grupos_str = "";
	grupos = JSON.parse('{"grupos":['+str_grupos+']}');
	for(var k = 0; k < grupos.grupos.length; k++){
		disable_group(grupos.grupos[k]);
		grupos_str += ' <span class="label label-default"><i>'+grupos.grupos[k]+'</i> <button onclick="delete_one_lote(this);" type="button" class="btn btn-danger btn-xs">X</button></span> ';
	}
	table_str += grupos_str + "</td><td>"+ obs +"</td><td class='text-right'><button type=\"button\" onclick=\"delete_one_line(this);\" class=\"btn btn-danger\">Deletar</button></td></tr>";
	var element = $("p:contains('"+ vendedor +"')");
	if(element.html() != undefined){
		// ja existe uma linha na tabela para aquele vendedor
		element.parent().parent().children(':eq(1)').append(grupos_str);
		
	}else // linha nova na tabela
	$('#_tbody').append(table_str);
	console.log(grupos);
	console.log(table_str);
	console.log(temporary_str)
	
	$('#modal_add').modal('hide');
}

function delete_one_line(el, id = false){
	$.each($(el).parent().parent().find('i'), function(){
		enable_group($(this).html().trim());
	});
	$(el).parent().parent().remove();
	/*if(id > 0){
		$.post('page/lotear_final-revenda.php', {string:'{"rifa":"<?= $rifa; ?>", "limpar":"'+ id +'"}'})
		.done(function(response){
			console.log(response);
		})
		.fail(function(response){
			console.log(response);
		});

}*/

}

function disable_group(group_name){
	console.log("GRUPO: " + group_name + " disabled.");
	$('#lotes_intervalo_start option[value="' + group_name + '"]').attr('disabled', true);
	$('#lotes_revendedor option[value="' + group_name + '"]').attr('disabled', true);
	$('#lotes_intervalo_end option[value="' + group_name + '"]').attr('disabled', true);

}

function enable_group(group_name){
	console.log("GRUPO: " + group_name + " enabled.");
	$('#lotes_intervalo_start option[value="' + group_name + '"]').attr('disabled', false);
	$('#lotes_revendedor option[value="' + group_name + '"]').attr('disabled', false);
	$('#lotes_intervalo_end option[value="' + group_name + '"]').attr('disabled', false);

}

function delete_one_lote(element, id = false){
	var group_name = $(element).parent().children(":first").text();
	enable_group(group_name);

	$(element).parent().remove();
	/*if(id > 0){
		$.post('page/lotear_final-revenda.php', {string:'{"rifa":"<?= $rifa; ?>", "deletarGrupo":"'+ id +'"}'})
		.done(function(response){
			console.log(response);
		})
		.fail(function(response){
			console.log(response);
		});

}*/

}

function save(){
	var tmp, str_resultado = "", obs, k;
	$.each($('#_tbody tr'), function(){
		vendedor = $(this).children(":first").children(":first").text();
		vendedor_nome = $(this).children(':first').html().split('</p>');
		vendedor_nome = vendedor_nome[1].trim();
		obs = $(this).children(":eq(2)").text();
		tmp = $(this).children(":eq(1)").children("span");
		if(tmp.length > 0){
			str_resultado += '{"observacao":"'+ obs.trim() +'", "vendedor_nome":"'+vendedor_nome+'", "vendedor":"'+vendedor+'", "grupos":[';
			//console.log($(this).children(":eq(1)").text());
			
			$.each(tmp, function(){
				k++;
				str_resultado += '"'+ $(this).children(":first").text() + '",';
			});
			str_resultado = str_resultado.slice(0,-1) + "]},";
		}
		//console.log(str_resultado);
	});
	str_resultado = str_resultado.slice(0,-1);
	str_resultado = '{"rifa":"<?= $rifa; ?>", "new":"true", "resultado":['+str_resultado+']}';
	console.log(str_resultado);
	show_loading();
	var result;
	$.post('page/lotear_final-revenda.php', {string:str_resultado})
	.done(function(response){
		console.log(response);

		result = JSON.parse(response);
		if(result.erro == 0)
			alert('Dados salvos com sucesso!');
		else{
			for(var k = 0; k < result.msg.length; k++){
				alert(result.msg[k]);
			}
		}
		end_loading();
	//location.href="index.php?p=lotear_final_new&rifa=<?= $rifa; ?>";
})
	.fail(function(response){
		console.log(response);
		alert('Falha ao salvar!');
		end_loading();
		//location.href="index.php?p=lotear_final_new&rifa=<?= $rifa; ?>";
	});

}

function unset_intervalos(){
	$('#lotes_intervalo_start option:selected').attr('selected', false);
	$('#lotes_intervalo_end option:selected').attr('selected', false);

}

function unset_lote(){
	$('#lotes_revendedor option:selected').attr('selected', false);

}

function novo(){
	$('#lotes_revendedor option:selected').attr('selected', false);
	$('#modal_add').modal('show');

}

function pesquisar_grupo(){
	var grupo = $('#codigo_rifa').val(); 
	if(grupo.length == 0)
		return;
	show_loading();
	$.get('https://rifasbrasil.com.br/servidor/new_server/get_bilhete.php', {rifa:<?php echo $rifa; ?>, layout:1, grupo:grupo}).done(function(res){
		console.log(res);
		var data = JSON.parse(res);
		console.log(data);
		var str = '<table class="table table-bordered table-hover"><thead><tr><td>Id Rifa</td><td>Nome da Rifa</td><td>Grupo do Bilhete</td><td>Revendedor</td><td>Grupos</td><td>Bilhetes</td></tr></thead>';
		str += "<tbody><tr>";
		str += "<td>"+data.rifa+"</td>";
		str += "<td>"+data.rifa_nome+"</td>";
		str += "<td>"+data.grupo+"</td>";
		if(data.revendedor != '' && data.revendedor != ';' && data.revendedor != null && data.revendedor)
		str += "<td>"+data.revendedor+"</td>";
		else
			str += "<td>Sem Revendedor</td>";
		str += "<td>";
		var bilhete;
		for(var k =0; k < data.bilhete.length; k++){
			bilhete = data.bilhete[k];
			bilhete = bilhete.replace(/<br>.*ll>/, '');
			var count = (bilhete.match(/-/g) || []).length;
			if(count == 2)
				bilhete = bilhete.substr(0, bilhete.length-5);
			str += bilhete.split('-')[0] + "<BR>";
		}
		str += "</td>";
		str += "<td>";
		var bilhete;
		var bilhete_original;
		for(var k =0; k < data.bilhete.length; k++){
			bilhete = data.bilhete[k];
			bilhete_original = data.bilhete[k];
			bilhete = bilhete.replace(/<br>.*ll>/, '');
			var count = (bilhete.match(/-/g) || []).length;
			if(count == 2)
				bilhete = bilhete.substr(0, bilhete.length-5);
			str += "-" + bilhete.split('-')[1] + " "/*+bilhete_original.split('-')[2]*/+"<BR>";
		}
		str += "</td>";
		str += "</tr></tbody>";
		$('#resultado').html(str);
		end_loading();
	}).fail(function(){
		end_loading();
	});

}

function pesquisar_bilhetes(){
	var codigo_bil = $('#codigo_rifa').val();
	if(codigo_bil.length == 0)
		return;
	if(parseInt(codigo_bil) < 0)
		return;
	show_loading();
	$.ajax({
		url: "page/lotear_final-get_rifas.php",
		method: "POST",
		data: {bilhete:codigo_bil, rifa:<?php echo $rifa; ?>}, 
		success: function(result){
			end_loading();
			console.log(result);
			var resultado = JSON.parse(JSON.stringify(result));
			if(resultado.valido){
				var str = '<table class="table table-bordered table-hover"><thead><tr><td>Id Rifa</td><td>Nome da Rifa</td><td>Grupo do Bilhete</td><td>Revendedor</td><td>Cod. Alfanumerico</td><td>Comprador</td></tr></thead>';
				str += "<tbody><tr>";
				str += "<td>"+resultado.rifa+"</td>";
				str += "<td>"+resultado.rifa_nome+"</td>";
				str += "<td>"+resultado.grupo+"</td>";
				str += "<td>"+resultado.revendedor+"</td>";
				str += "<td>"+resultado.bilhete_codigo+"</td>";
				if(resultado.comprador )
					str += "<td>"+resultado.comprador+"</td>";
				else
					str += "<td>Não reconhecido</td>";
				str += "</tr></tbody>";
				$('#resultado').html(str);
			}else
			alert(resultado.msg);
		},
		error: function(result){
			end_loading();
			console.log('erro:' + result.responseText);
		}
	});

}

</script>

<style>.esconder{display: none;}</style>

</body>