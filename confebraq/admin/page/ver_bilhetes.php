<?php
include("../class/conexao.php");


$cod_rifa = intval($_GET[rifa]);
$maxbilhetes = DBSelect("select rifa_maxbilhetes as m FROM tbl_rifas where rifa_cod = '$cod_rifa'", $mysqli);
$maxbilhetes = $maxbilhetes[m];

if(isset($_POST[bilhetes_selecionados])){

	$bilhete = explode(";", $_POST[bilhetes_selecionados]);
	
	unset($bilhete[count($bilhete)-1]);

	if(count($bilhete) == 0){
		echo "<script>";
		echo "alert('Selecione os bilhetes que deseja adquirir');";
		echo "</script>";
	}else{


  // Limpar Carrinho
		if(is_array($_SESSION[carrinho_admin]))
			foreach($_SESSION[carrinho_admin] as $ch=>$va){

				foreach($_SESSION[carrinho_admin][$ch] as $chave=>$valor)
					unset($_SESSION[carrinho_admin][$ch][$chave]);

				unset($_SESSION[carrinho_admin][$ch]);

			}



			$_SESSION[revendedor_admin] = intval($_SESSION[usuario]);

			if(!isset($_SESSION[carrinho_admin]))
				$_SESSION[carrinho_admin] = Array();

			if(is_array($bilhete)){

				if(!isset($_SESSION[carrinho_admin][$cod_rifa]))
					$_SESSION[carrinho_admin][$cod_rifa] = Array();

				foreach($bilhete as $v){
					$_SESSION[carrinho_admin][$cod_rifa][intval($v)] = true;
				}

				echo "<script>";
				echo "location.href='index.php?p=cadastro_cliente';";
				echo "</script>";

			}

		}

	}


	$codigo = intval($_GET['rifa']);

	$sql_rifas = "SELECT * FROM tbl_rifas WHERE rifa_cod = '$codigo' AND rifa_vencedor iS NULL AND rifa_deletada IS NULL";
	$sql_query = $mysqli->query($sql_rifas) or die($mysqli->error);
	$rifa = $sql_query->fetch_assoc();

// A consulta abaixo verifica quais rifas já foram vendidas e cria um array com estas
	$sqlBil = "SELECT * FROM tbl_bilhetes WHERE bil_rifa = '$codigo' or (bil_rifa = '$codigo' AND bil_situacao = 'C')";
	$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
	$bil = $queryBil->fetch_assoc();
	$bilVendidos = Array();

	if($queryBil->num_rows > 0)
		do{
			$bilVendidos[] = $bil[bil_numero];
		}while($bil = $queryBil->fetch_assoc());


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

					if (xhttp.readyState == 1) {
						$("#loader2").css("display", "");
					}

					if (xhttp.readyState == 4 && xhttp.status == 200) {
						document.getElementById("desktop").innerHTML = xhttp.responseText;
						$("#loader2").css("display", "none");
					}
				};
				var selecionado = document.getElementById('bilhetes_selecionados').value;
				xhttp.open("GET", "page/get_bilhetes.php?maxbilhetes=<?php echo strlen($maxbilhetes)-1; ?>&inicio=" + res[0] + "&fim=" + res[1] + "&rifa="+ rifa + "&selecionado="+selecionado, true);
				xhttp.send();
			}else{
				document.getElementById("desktop").innerHTML = "<div style='padding:10px;' class='col-lg-12 bg-warning'>Selecione o intervalo de bilhetes que você quer ver.</div>";
			}
		}

		function adicionar_bilhete(valor, rifa, maxbilhetes) {

			var xhttp = new XMLHttpRequest();
			var bil = document.getElementById('bilhetes_selecionados');

			


			xhttp.onreadystatechange = function() {

				if (xhttp.readyState == 1) {
					$("#loader").css("display", "");
				}

				if (xhttp.readyState == 4 && xhttp.status == 200) {

					var msg = "";

					if(parseInt(xhttp.responseText) < 1 ){
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


	    		//Limita o número de bilhetes
	    		var count = (bil.value.match(/;/g) || []).length;

	    		if(count == 5){
	    			alert("Você só pode adicionar 5 bilhetes por compra.");
	    			$("#loader").css("display", "none");
	    			return;
	    		}else{

	    			msg = "Bilhete adicionado";

	    			$('#result').removeClass('esconder');
	    			$('#result').removeClass('btn-danger');
	    			$('#result').addClass('btn-success');

	    			if(bil.value == "Nenhum"){
	    				bil.value = "";
	    				document.getElementById('finalizar').disabled=false;

	    			}

	    			var bfinal = padLeft(valor, <?php echo strlen($maxbilhetes)-1; ?>);

	    			$('#holder'+ bfinal ).addClass('bilhete_selected');
	    			if(document.getElementById("bilhete"+ bfinal ))
	    				document.getElementById("bilhete"+ bfinal ).checked = true;
	    			
	    			if(bil.value.indexOf( bfinal +";") > - 1)
	    			bil.value += "";
	    			else
	    				bil.value +=  bfinal +";";



	    		}}

	    		document.getElementById("result").innerHTML = msg;

	    		$("#loader").css("display", "none");
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

      	//Limita o número de bilhetes
      	var count = (bil.value.match(/;/g) || []).length;

      	if(count == 5){
      		alert("Você só pode adicionar 5 bilhetes por compra.");
      		$("#loader").css("display", "none");
      		return;
      	}

	     //fim

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
	<style>
	/*Pagina Rifa*/
	.bilhete-vendido{border:1px solid #999; background-color: #eaeaea; text-align: center; padding: 5px 0 5px 0;}
	.bilhete{border:1px solid #FFFD7A; background-color: #FFFEBA; text-align: center; cursor:pointer; padding: 5px 0 5px 0;}
	.bilhete_selected{border:1px solid #FFFC45; background-color: #FFF04D; }
	.comprar_rifa input[type=checkbox] {

		cursor: pointer;

		display: inline-block;
		display: none;
		font-size: 14px;
		text-align: center;
		font-weight: bold;
		color: #052;
	}
	.comprar_rifa input[type=checkbox]:checked {

	}
	.esconder{display:none;}
	</style>
	<h3>Selecionar Bilhetes</h3>

	<hr>
	<div class="col-lg-12 text-right form-group form-inline">
		<form action="" method="post">
			<label for="">Bilhetes Adicionados: </label>
			<input value="Nenhum" class="form-control" name="bilhetes_selecionados" readonly id="bilhetes_selecionados">
			<button type="submit" disabled id="finalizar" class="btn btn-success">Prosseguir</button>
		</form>
	</div>
	<div class="col-lg-12"><label for="">Adicionar um Bilhete Específico</label></div>
	<div class="col-lg-12 form-group form-inline">
		Digite o número do bilhete desejado:
		<input class="form-control" id="bil_busca" name="bil_busca" type="text">
		<button onclick="javascript: adicionar_bilhete(document.getElementById('bil_busca').value, <?php echo $cod_rifa; ?>, <?php echo $maxbilhetes; ?>);" class="btn btn-primary">Adicionar</button> 
		<div id="result" class="esconder btn"></div> <img id="loader" style="display:none;" src="../img/loader.gif" height="25px" alt="">
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
				<option value="<?php echo "$i,$f"; ?>" ><?php echo str_pad($i, strlen($maxbilhetes)-1, "0", STR_PAD_LEFT)." à ".str_pad(($f-1), strlen($maxbilhetes)-1, "0", STR_PAD_LEFT); ?></option>
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
		<img id="loader2" style="display:none;" src="../img/loader.gif" height="25px" alt="">
	</div>

	<div id="desktop" class="col-lg-12">
	</div>

	<div class="clearfix"></div>


