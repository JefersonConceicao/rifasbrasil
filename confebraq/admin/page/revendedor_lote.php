<script>

	function makeid()
	{
	    var text = "";
	    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

	    for( var i=0; i < 5; i++ )
	        text += possible.charAt(Math.floor(Math.random() * possible.length));

	    return text;
	}

	function pre_pago(){

		var bloqueados = [];
		var pg;

		$('.lotes option').prop('disabled', false);
		$.each($('.lotes'), function(index, value){
			
			pg = $(value).parent().find('input[class=tipo]:checked').val();

			//alert($(value).val() + " " + pg);

			if($(value).val() != '' && pg == 1)
				bloqueados.push($(value).val());
				

		});

		for(var  i =0; i< bloqueados.length; i++)
			$('.lotes option[value='+ bloqueados[i] +']').prop('disabled', true);
		
	}

	
	function novo(){

		var id = makeid();

		$('#item_fonte')
		.clone()
		.insertAfter("#item_fonte")
		.attr("id","")
		.removeClass("esconder")
		//.addClass("animated bounceIn")
		.find('#pre_p')
		.attr("name",id)
		.attr("id",id)
		.parent()
		.next()
		.children()
		.attr("name",id)
		.attr("id",id)
		.parent().prev().prev().prev().attr("name", makeid() + "[]");

		pre_pago();
		// alterar id
	}

	function novo_lote(campo){

		campo.prev().clone().insertBefore(campo);
		
	}

	

</script>
<style>
	.esconder{display: none;}
</style>
<?php

$dado = db_select($mysqli, "select u.usu_nome, u.usu_cod from tbl_usuario u, tbl_revendedor r where u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa'");

?>
<div class="form-group">
	<button onclick="novo();" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i> adicionar</button>
</div>
<div id="item_fonte" class="esconder item form-group form-inline">

	<select name="vendedor" class="form-control" id="">
		<option value="">Revendedor</option>
		<?php foreach($dado as $d){ ?>
		<option value="<?php echo $d['usu_cod']; ?>"><?php echo $d['usu_nome']; ?></option>
		<?php } ?>
	</select>
	<select name="lote[]" style="margin-right:5px;"  onchange="pre_pago();" class="form-control lotes">
		<option value="">Lote</option>
		<?php
					foreach($titulo_grupos as $t)
						echo "<option value=\"$t\">$t</option>";
				?>
	</select> <button onclick="novo_lote($(this));" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>
	 
	<label>
		<input id="pre_p" onchange="pre_pago();" value="1"  class="tipo" type="radio"> Pré pago
	</label>
	<label>
		<input id="pos_p" onchange="pre_pago();" value="0"  class="tipo" type="radio"> Pós pago 
	</label>

</div>

