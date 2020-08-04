<?php
$cod_rifa = $_GET['rifa'];
$maxbilhetes = DBSelect("select rifa_maxbilhetes as m FROM tbl_rifas where rifa_cod = '$cod_rifa'", $mysqli);
$maxbilhetes = $maxbilhetes[m];
?>
<script>
function padLeft(nr, n, str){
    return Array(n-String(nr).length+1).join(str||'0')+nr;
}

function get_bilhetes(valor, rifa) {

	if(valor.length > 0){
		var res = valor.split(',');

	  var xhttp = new XMLHttpRequest();

	  xhttp.onreadystatechange = function() {
	    if (xhttp.readyState == 4 && xhttp.status == 200) {
	     document.getElementById("desktop").innerHTML = xhttp.responseText;
	    }
	  };
	  var selecionado = document.getElementById('bilhetes_selecionados').value;
	  xhttp.open("GET", "page/get_bilhetes.php?inicio=" + res[0] + "&fim=" + res[1] + "&rifa="+ rifa + "&selecionado="+selecionado, true);
	  xhttp.send();
	}else{
		document.getElementById("desktop").innerHTML = "<p>Selecione o intervalo de bilhetes que você quer ver.</p>";
	}
}

function adicionar_bilhete(valor, rifa, maxbilhetes) {

	  var xhttp = new XMLHttpRequest();
	  var bil = document.getElementById('bilhetes_selecionados');

	  


	  xhttp.onreadystatechange = function() {
	    if (xhttp.readyState == 4 && xhttp.status == 200) {

	    	var msg = "";

	    	if(parseInt(xhttp.responseText) <1 ){
	    		$('#result').removeClass('esconder');
	    		$('#result').removeClass('btn-success');
	    		$('#result').addClass('btn-danger');
	    		
	    	}

	    	if(valor >= maxbilhetes){

	    		$('#result').removeClass('esconder');
	    		$('#result').removeClass('btn-success');
	    		$('#result').addClass('btn-danger');
	    		msg = "Os bilhetes dessa rifa vão de 0 à "+ (maxbilhetes-1);

	    	}

	    	else if(parseInt(xhttp.responseText) == -2)
	    		msg = "Bilhete inválido";
	    	else if(parseInt(xhttp.responseText) == -1)
	    		msg = "Rifa inválida";

	    	else if(parseInt(xhttp.responseText) == 0)
	    		msg = "Bilhete indisponível";

	    	else if(parseInt(xhttp.responseText) == 1){
	    		msg = "Bilhete adicionado";

	    		$('#result').removeClass('esconder');
	    		$('#result').removeClass('btn-danger');
	    		$('#result').addClass('btn-success');

	    		if(bil.value == "Nenhum"){
	    			bil.value = "";
	    			document.getElementById('finalizar').disabled=false;

	    		}

	    		var bfinal = padLeft(valor, 3);
	    		$('#holder'+ bfinal ).addClass('bilhete_selected');
	    		if(document.getElementById("bilhete"+ bfinal ))
	    			document.getElementById("bilhete"+ bfinal ).checked = true;
	    		
	    		if(bil.value.indexOf( bfinal +";") > - 1)
	    			bil.value += "";
	    		else
	    			bil.value +=  bfinal +";";
	    		

	    	}

	    	document.getElementById("result").innerHTML = msg;
	    }
	  };
	  
	  xhttp.open("GET", "page/adicionar_bilhetes.php?rifa="+rifa+"&bilhete="+valor, true);
	  xhttp.send();

}

function checkar(id){

      var campo_id = "bilhete" + id;
      var holder = "holder"+ id;
      var bil = document.getElementById('bilhetes_selecionados');

      if(bil.value == "Nenhum"){
      	bil.value = "";
      	document.getElementById('finalizar').disabled=false;
      }
      if(document.getElementById(campo_id).checked){
        
        document.getElementById(campo_id).checked = false;
        $('#'+holder).removeClass('bilhete_selected');
        bil.value = bil.value.replace(id + ";", "");
        

      }else{

        $('#'+holder).addClass('bilhete_selected');
        document.getElementById(campo_id).checked = true;
        bil.value += id + ';';

      }

      if(bil.value.length == 0){
      	bil.value = "Nenhum";
      	document.getElementById('finalizar').disabled=true;
      }

}
</script>

<div class="col-lg-12 form-group form-inline">
	<form action="" method="post">
		Bilhetes Selecionados: 
		<input value="Nenhum" class="form-control" name="bilhetes_selecionados" readonly id="bilhetes_selecionados">
		<button type="submit" disabled id="finalizar" class="btn btn-success">Finalizar Compra</button>
	</form>
</div>
<div class="col-lg-12"><label for="">Adicionar um Bilhete Específico</label></div>
<div class="col-lg-12 form-group form-inline">
Digite o número do bilhete desejado:
<input class="form-control" id="bil_busca" name="bil_busca" type="text">
<button onclick="javascript: adicionar_bilhete(document.getElementById('bil_busca').value, <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);" class="btn btn-primary">Adicionar</button> 
<div id="result" class="esconder btn"></div>
</div>
<div class="col-lg-12"><label for="">Adicionar com base no Intervalo de Bilhetes</label></div>
<div class="col-lg-12 form-group form-inline">
	
	Veja todos os bilhetes no intervalo à seguir: 
	<select onchange="javascript: get_bilhetes(this.value, <?php echo $cod_rifa; ?>);" class="form-control" name="" id="">


		<option value=""></option>
		<?php 

		$range = 60;

		$i = 0;
		$f = $range;

		$lim = $maxbilhetes- 1;

		while($f <= $lim){ 

		?>
		<option value="<?php echo "$i,$f"; ?>" ><?php echo "$i à ".($f-1); ?></option>
		<?php 
		$i += $range;
		$f += $range;


		} 

		if($i < $lim){

			$i = $i;

			?>
			<option value="<?php echo "$i,".($lim+1); ?>" ><?php echo "$i à $lim"; ?></option>
			<?php 


			} 
		?>

	</select>
</div>

<div id="desktop" class="col-lg-12">
</div>


