<?php

include("../class/conexao.php");



$rifa = intval($_POST['rifa']);

$credito_minimo = 15;



function get_bilhetes($mysqli, $rifa){



	$bilhetes = db_select($mysqli, "select rifa_maxbilhetes from tbl_rifas where rifa_cod = '$rifa' limit 1", 1);

	$bilhetes = $bilhetes['rifa_maxbilhetes'];

	$grupo    = db_select($mysqli, "select * from lote where rifa = '$rifa' limit 1", 1);



	if($bilhetes > 900 && $bilhetes < 1200)

		$bilhetes = 1000;

	elseif($bilhetes > 3000)

		$bilhetes = 10000;



	$grupos = ceil($bilhetes / $grupo['grupo']);



	$numero_letras = ceil($grupos/100);



	$bil = shuffleIt(range(0, $bilhetes-1), $rifa);



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



	foreach($bil as $val){





		$bilhetes_finais[$titulo_grupos[$k]][] = str_pad($val, strlen($bilhetes)-1, '0', STR_PAD_LEFT);

		//$bilhetes_finais[] = $titulo_grupos[$k]."-".$val."<br>$rifa";



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

		$bilhetes_finais = get_bilhetes($mysqli, $rifa);







?>

<body onload="pre_pago()">

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







		$dado = db_select($mysqli, "select u.usu_nome, u.usu_cod from tbl_usuario u, tbl_revendedor r where u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa'");







		?>



		<?php if(count($dado) == 0) echo "<p>Você não cadastrou nenhum revendedor para esta rifa.</p>"; else {  ?>



		<div class="form-group">



			



			<button onclick="novo();" type="button" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i> adicionar</button>



			



		</div>



	<form action="index.php?p=lotear_final" method="post">



		<table class="table">



			<thead>



				<tr>



					<td>Vendedor</td>



					<td>Lotes</td>



					<td>Observação</td>



				</tr>



			</thead>



			<tbody>



				<tr id="item_fonte" class="esconder item form-group form-inline">



					<td>



						<select name="vendedor[]" class="form-control vendedor">



							<option value="">Revendedor</option>



							<?php foreach($dado as $d){ ?>



							<option value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>



							<?php } ?>



						</select>



					</td>



					<td>

			



						<select type="text"  name="lote[]" style="margin-right:5px;"  onchange="pre_pago();" class="form-control lotes">



							<option value="">Lote</option>

	

							<?php foreach($titulo_grupos as $t) echo "<option value=\"$t\">$t</option>"; ?>



						</select> 



						<button type="button" onclick="novo_lote($(this));" class="btn btn-success btn-xs">



							<i class="glyphicon glyphicon-plus"></i>



						</button>



					</td>



					<td>



						<input name="observacao[]" class="form-control obs">



					</td>



					<td class="text-right">



						<button type="button" onclick="deletar(this);" class="btn btn-danger btn-xs">



							<i class="glyphicon glyphicon-trash"></i>



						</button>



					</td>



				</tr>



				<?php







				$linha = db_select($mysqli, "select * from revenda where rifa= '$rifa'");



				$tds_grupos =  db_select($mysqli, "select grupo from grupo_revendedor where revenda in(select codigo from revenda where rifa = '$rifa' and pagamento = 10)");



				$tds_grupos_tmp = array();



				foreach($tds_grupos as $td)

					$tds_grupos_tmp[] = $td['grupo'];







				foreach($linha as $l){



					



					$grupos = db_select($mysqli, "select * from grupo_revendedor where revenda = '$l[codigo]'");



					







					$grupos_tmp = array();



					foreach($grupos as $g)



						$grupos_tmp[] = $g['grupo'];







					?>







				<tr id="" class=" item form-group form-inline">



		



					<td>



						<select name="vendedor[]" class="form-control vendedor">



							<option value="">Revendedor</option>



							<?php foreach($dado as $d){ ?>



							<option <?php if($l['vendedor'] == $d['usu_cod']) echo 'selected'; ?> value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>



							<?php } ?>



						</select>



					</td>



					<td>



						<?php $nome = "input".substr(md5(rand()),0, 3)."[]";



						$nome2 = "input".substr(md5(rand()),0, 6);





						if(count($grupos) == 0){?>







						<select type="text"  name="<?php echo $nome; ?>" style="margin-right:5px;"  onchange="pre_pago();" class="form-control lotes">



							<option value="">Lote</option>



							<?php 



							foreach($titulo_grupos as $t) 



								echo "<option value=\"$t\">$t</option>";







							?>



						</select>



						<?php } else{ foreach($grupos as $g){ ?>



						<select type="text"  name="<?php echo $nome; ?>" style="margin-right:5px;"  onchange="pre_pago();" 

							class="form-control lotes">



							<option value="">Lote</option>



							<?php 



							foreach($titulo_grupos as $t) 



								if($g['grupo'] == $t)



									echo "<option selected value=\"$t\">$t</option>";



								elseif(in_array($t, $tds_grupos_tmp))



									echo "<option disabled=\"disabled\" value=\"$t\">$t</option>";



								else



									echo "<option value=\"$t\">$t</option>";



							?>



						</select>



						<?php } }?> 



						<button type="button" onclick="novo_lote(this);" class="btn btn-success btn-xs">



							<i class="glyphicon glyphicon-plus"></i>



						</button>



					</td>



					<td>



						<input name="observacao[]" value="<?php echo $l['pagamento']; ?>" class="form-control obs">



					</td>



					<td class="text-right">



						<button type="button" onclick="deletar(this);" class="btn btn-danger btn-xs">



							<i class="glyphicon glyphicon-trash"></i>



						</button>



					</td>



				</tr>



				<?php } ?>



			</tbody>



		</table>



		<input type="hidden" value="<?php echo $_POST['rifa']; ?>" id="rifa">



		<input type="hidden" value="<?php echo $_POST['grupo']; ?>" id="grupo">







		<button type="button" onclick="organizar_envio();" class="btn btn-primary">Salvar</button>



	</form>



	<?php 



			} ?>



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

      <div class="modal-body">

        <div class="form-group">

        	<label for="">Revendedor</label>

        	<select name="revendedor_codigo" id="revendedor_codigo" class="form-control">

        		<option value="-1">Revendedor</option>

        		<?php foreach($dado as $d){ ?>



				<option <?php if($l['vendedor'] == $d['usu_cod']) echo 'selected'; ?> value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>



				<?php } ?>

        	</select>

        </div>

        <div class="form-group">

        	<label for="">Lotes</label>

        	<select type="text"  name="lotes_revendedor" id="lotes_revendedor" style="margin-right:5px;"  onchange="pre_pago();" class="form-control lotes">

				<option value="">Lote</option>

				<?php foreach($titulo_grupos as $t) echo "<option value=\"$t\">$t</option>"; ?>

			</select>

        </div>

      </div>

      <div class="modal-footer">

        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

      </div>

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







	function makeid(){

	    var text = "";

	    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

	    for( var i=0; i < 5; i++ )

	        text += possible.charAt(Math.floor(Math.random() * possible.length));



	    return text;

	}



	function pre_pago(){



		var bloqueados = [], tmp;



		$('option').prop('disabled', false);

		$.each($('.lotes'), function(index, value){



			tmp = $(value).find('option:selected').val();

			if(tmp)

				$('.lotes option[value='+ tmp +']').prop('disabled', true);



		});



	}



	function deletar(elem){

	 	$(elem).parent().parent().remove();

	 	pre_pago();

	 }



	function novo(){



		/*var id = makeid() + "[]";

		$('#item_fonte')

		.clone()

		.insertAfter("#item_fonte")

		.attr("id","")

		.removeClass("esconder")

		.find('.lotes')

		.attr("name", id)

		.attr("id", makeid());



		pre_pago();

		// alterar id*/



		$('#modal_add').modal('show');



	}



	function organizar_envio(){



		var vendedor, grupos = [], tmp = "", observacao = "";

		$.each($('.vendedor'), function(index, value){

			// pega o vendedor

			vendedor = $(value).val();

			observacao = $(value).parent().next().next().children('input').val();



			if(vendedor){



				grupos = [];

				

				$(value).parent().next().children('select').each(function(i, v){

					grupos.push($(v).find('option:selected').val());

					console.log($(v).find('option:selected').val());

				});



				tmp += "{\"observacao\":\""+observacao+"\",  \"vendedor\":"+vendedor+", \"grupos\":"+JSON.stringify(grupos)+"},";



			}

		});



		tmp = "{\"resultado\":[" + tmp.substr(0, tmp.length-1) + "], \"rifa\":" + $('#rifa').val() + ", \"grupo\":" + $('#grupo').val() + "}";

		

		$.ajax({

			url: "page/lotear_final-revenda.php",

			method: "POST",

			data: {string:tmp}, 

			success: function(result){



				alert('Revendedores salvos com sucesso!');

		        //var resultado = JSON.parse(JSON.stringify(result));



		        

		    },

		    error: function(result){

		        console.log('erro:' + result.responseText);

		    }

		});





	}



	function novo_lote(campo){



		var val_anterior = $(campo).prev().find('option:selected').val();

		$(campo).prev().clone().attr("id", makeid()).val('').insertBefore(campo);



	}



	function pesquisar_grupo(){



		var grupo = $('#codigo_rifa').val(); 



		if(grupo.length == 0)

			return;



		$.get('http://rifasbrasil.com.br/servidor/new_server/get_bilhete.php', {rifa:<?php echo $rifa; ?>, layout:1, grupo:grupo}).done(function(res){



			console.log(res);

			var data = JSON.parse(res);

			console.log(data);



			var str = '<table class="table table-bordered table-hover"><thead><tr><td>Id Rifa</td><td>Nome da Rifa</td><td>Grupo do Bilhete</td><td>Revendedor</td><td>Bilhetes</td></tr></thead>';



			str += "<tbody><tr>";

			str += "<td>"+data.rifa+"</td>";

			str += "<td>"+data.rifa_nome+"</td>";

			str += "<td>"+data.grupo+"</td>";

			if(data.revendedor)

				str += "<td>"+data.revendedor.usu_nome+"</td>";

			else

				str += "<td>Sem Revendedor</td>";

			str += "<td>";



			for(var k =0; k < data.bilhete.length; k++)

				str += data.bilhete[k] + "<br>";



			str += "</td>";



			str += "</tr></tbody>";



			$('#resultado').html(str);





		});



	}



	function pesquisar_bilhetes(){



		var codigo_bil = $('#codigo_rifa').val();



		if(codigo_bil.length == 0)

			return;



		if(parseInt(codigo_bil) < 0)

			return;



		$.ajax({

			url: "page/lotear_final-get_rifas.php",

			method: "POST",

			data: {bilhete:codigo_bil, rifa:<?php echo $rifa; ?>}, 

			success: function(result){



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

		        console.log('erro:' + result.responseText);

		    }

		});



	}

	







</script>



<style>.esconder{display: none;}</style>

</body>