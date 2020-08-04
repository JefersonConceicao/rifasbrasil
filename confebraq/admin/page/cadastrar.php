<?php


include("../class/conexao.php");
include("../class/function_limparNumero.php");

function nomeImagem($cod_usuario, $arq_nome, $tmp_nome){
	return $cod_usuario."-".md5("1-".time()."-".$tmp_nome).".".substr($arq_nome, -3);
}

$banco_code = "SELECT count(*) as c from tbl_conta WHERE usuario = '$_SESSION[usuario]'";
$banco_query = $mysqli->query($banco_code) or die($mysqli->error);
$bb = $banco_query->fetch_assoc();

$ps_code = "SELECT usu_psemail as i1, usu_pstoken as i2 from tbl_usuario where usu_cod = '$_SESSION[usuario]' ";
$ps_query = $mysqli->query($ps_code) or die($mysqli->error);
$ps = $ps_query->fetch_assoc();

if((strlen($ps[i1]) == 0 || strlen($ps[i2]) == 0) && $bb[c] == 0){
	echo "<script>location.href='index.php?p=pagamento';</script>";
}else{

	if(isset($_POST[enviar])){

		foreach($_POST as $ch=>$va){
			if(is_array($_POST[$ch]))
				foreach($_POST[$ch] as $cha=>$val){
					$_SESSION[$ch][$cha] = $mysqli->escape_string($val);
				}
			else
				$_SESSION[$ch] = $mysqli->escape_string($va);
		}



		$_SESSION[comissao] = limparNumero($_SESSION[comissao]);

		if($_SESSION[comissao] > 100)
			$erro[] = "A comissão o vendedor não pode ser maior que 100%";

		$erro = Array();

		if(strlen($_SESSION[titulo]) == 0)
			$erro[] = "Preencha o título da rifa";

		if(strlen($_SESSION[datasorteio]) == 0)
			$erro[] = "Preencha a data de sorteio da rifa";

		$data = explode("/", $_SESSION[datasorteio]);

		if(strtotime("$data[2]-$data[1]-$data[0] 23:59:00") <= strtotime(date("Y-m-d")." 23:59:00"))
			$erro[] = "A data do sorteio não pode ser igual ou anterior à hoje.";

		if(strlen($_SESSION[nbilhete]) == 0 || !is_numeric($_SESSION[nbilhete]))
			$erro[] = "Preencha o número de bilhetes da rifa";

		if(strlen($_SESSION[valorbilhete]) == 0)
			$erro[] = "Preencha o valor do bilhete da rifa";

		if(strlen($_SESSION[proposito]) == 0)
			$erro[] = "Preencha o propósito da rifa";

		if(strlen($_SESSION[premiounico]) == 0)
			$erro[] = "Preencha os detalhes do prêmio da rifa";

		$npremio = 1;

		if($_SESSION[nbilhete] == 10001){
			$npremio = 5;
			
		}
		if($_SESSION[nbilhete] == 10002)
			$npremio = 10;

		if($_SESSION[nbilhete] > 10000)
			$_SESSION[nbilhete] = 10000;




		// padrões de upload
		$arquivo[1] = $_FILES["foto1"];
		$arquivo[2] = $_FILES["foto2"];
		$arquivo[3] = $_FILES["foto3"];

		$tam_maximo = 5; //EM MBs
    	$pasta = "../arquivos/imgs/banner";

    	if($arquivo[1]["size"] > ($tam_maximo*1000000))
        	$erro[] = "A foto 1 não deve ser maior que ".$tam_maximo."mb.";

	    if($arquivo[2]["size"] > ($tam_maximo*1000000))
	        $erro[] = "A foto 2 não deve ser maior que ".$tam_maximo."mb.";

	    if($arquivo[3]["size"] > ($tam_maximo*1000000))
	        $erro[] = "A foto 3 não deve ser maior que ".$tam_maximo."mb.";

		if(count($erro) == 0){

			$d = explode("/", $_SESSION[datasorteio]);
			$df = "$d[2]-$d[1]-$d[0]";

			$valorbilhete = str_replace(',', '.', str_replace("R$", "", $_SESSION[valorbilhete]));

			$imagem[1] = "";
			$imagem[2] = "";
			$imagem[3] = "";

 			//Criamos o nome da nova imagem
            $imagem[1] = nomeImagem($_SESSION[usuario], $arquivo[1]["name"],$arquivo[1]["tmp_name"]);
            copy($arquivo[1]["tmp_name"], $pasta."/".$imagem[1]);

            if(strlen($arquivo[2]["name"]) > 0){
            	$imagem[2] = nomeImagem($_SESSION[usuario], $arquivo[2]["name"],$arquivo[2]["tmp_name"]);
            	$copia[2] = copy($arquivo[2]["tmp_name"], $pasta."/".$imagem[2]);
            }

            if(strlen($arquivo[3]["name"]) > 0){
            	$imagem[3] = nomeImagem($_SESSION[usuario], $arquivo[3]["name"],$arquivo[3]["tmp_name"]);
            	$copia[3] = copy($arquivo[3]["tmp_name"], $pasta."/".$imagem[3]);
            }

            foreach($imagem as $ch=>$va){
            	if(strlen($va) > 0)
            		$imagem[$ch] = "arquivos/imgs/banner/".$va;
            }

            $sql_code = "INSERT INTO tbl_rifas (rifa_premios, rifa_titulo, rifa_dtsorteio, rifa_valorbilhete, 
				rifa_maxbilhetes, rifa_descricao, rifa_proposito, rifa_foto1, rifa_foto2, rifa_foto3, rifa_dono, rifa_regulamento, rifa_dtcriacao)
			VALUES(
				'$npremio',
				'$_SESSION[titulo]',
				'$df',
				'$valorbilhete',
				'$_SESSION[nbilhete]',
				'$_SESSION[premiounico]',
				'$_SESSION[proposito]',
				'$imagem[1]',
				'$imagem[2]',
				'$imagem[3]',
				'$_SESSION[usuario]', '$_SESSION[regulamento]', now())";

			$sucesso = $mysqli->query($sql_code);
			$rifa_codigo = $mysqli->insert_id;


			foreach($_SESSION[premio] as $cod=>$pre){
				if(strlen($pre) > 0){
					$indice = $cod+1;
					$mysqli->query("INSERT INTO tbl_premio (pre_rifa, pre_descricao, pre_ranking)
						values('$rifa_codigo', '$pre', '$indice')");
				}
			}
			
			if($sucesso){

				foreach($_SESSION as $ch=>$va){
					if($ch != "usuario" && $ch != "admin")
						unset($_SESSION[$ch]);
				}
				echo "<script>location.href='index.php?p=rifa';</script>";

			}
		}

	}

?>

<style>
	.esconder{display:none;}
</style>

<div class="col-lg-12 text-left">
	<h3>Cadastrar Rifa</h3>
	<hr>
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12">
		<?php foreach($erro as $msg) echo "<div class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
	</div>
	<?php } ?>
	
</div>
<form action="" enctype="multipart/form-data"  method="post">
<div class="col-lg-6 text-left">
	
		<div class="form-group col-lg-8	">
			<label for="">Título da Rifa*</label>
			<input value="<?php echo $_SESSION[titulo]; ?>" type="text" required placeholder="ex: Fiat Uno 4 Portas" name="titulo" class=" form-control">
		</div>
		<div class="form-group col-lg-4">
			<label for="">Data do Sorteio*</label>
			<input placeholder="00/00/0000" value="<?php echo $_SESSION[datasorteio]; ?>" type="text" required name="datasorteio" class="datepicker form-control">
		</div>
		<div class="form-group col-lg-6">
			<label for="">Modalidade da Rifa*</label>
			<select onchange="javascript: formulario(this.value);" type="text" required name="nbilhete" class=" form-control">
				<option value="">Selecione</option>
				<option <?php if($_SESSION[nbilhete]== 100) echo "selected"; ?> value="100">(Dezena) 00 à 99</option>
				<option <?php if($_SESSION[nbilhete]== 1000) echo "selected"; ?>  value="1000">(Centena) 000 à 999</option>
				<option <?php if($_SESSION[nbilhete]== 10000) echo "selected"; ?>  value="10000">(Milhar) 0000 à 9999</option>
				<option <?php if($_SESSION[nbilhete]== 10001) echo "selected"; ?>  value="10001">(Milhar) 0000 à 9999 Premiação do 1º ao 5º prêmio</option>
				<option <?php if($_SESSION[nbilhete]== 10002) echo "selected"; ?>  value="10002">(Milhar) 0000 à 9999 Premiação do 1º ao 10º prêmio</option>
			</select>

		</div>
		<div class="form-group col-lg-6">
			<label for="">Valor do Bilhete*</label>
			<input value="<?php echo $_SESSION[valorbilhete]; ?>" type="text" required name="valorbilhete" class="dinheiro form-control">
		</div>
		<div class="form-group col-lg-4">
			<label for="">Foto Principal*</label>
			<input  type="file" required name="foto1" class=" form-control">
		</div>
		<div class="form-group col-lg-4">
			<label for="">Foto 2</label>
			<input  type="file" name="foto2" class=" form-control">
		</div>
		<div class="form-group col-lg-4">
			<label for="">Foto 3</label>
			<input  type="file" name="foto3" class=" form-control">
		</div>
		<div class="form-group col-lg-4">
			<label for="">Propósito da Rifa*</label>
			<textarea name="proposito" required id="" cols="30" rows="4" class="form-control" placeholder="Pra que você está criando esta rifa?"><?php echo $_SESSION[proposito]; ?></textarea>
		</div>
		<div class="form-group col-lg-4">
			<label for="">Prêmio*</label>
			<textarea name="premiounico" required id="" cols="30" rows="4" class="form-control" placeholder="Descreva os detalhes do prêmio"><?php echo $_SESSION[premiounico]; ?></textarea>
		</div>
		<div class="form-group col-lg-4">
			<label for="">Regulamento</label>
			<textarea name="regulamento" id="" cols="30" rows="4" class="form-control" placeholder="Descreva as regras da rifa"><?php echo $_SESSION[regulamento]; ?></textarea>
		</div>

		<div class="form-group col-lg-12">
			<button type="submit" name="enviar" value="1" class="btn-success btn">salvar</button>
			<button type="button" onclick="javascript:location.href='index.php?p=rifa';" class="btn">voltar</button>
		</div>
		
	
</div>
<div id="detalhes" class="<?php if($_SESSION[nbilhete] == 10001 || $_SESSION[nbilhete] == 10002) echo ""; else echo "esconder"; ?> esconder col-lg-6 text-left">
	<p>Detalhes do Sorteio</p>
	<div class="<?php if($_SESSION[nbilhete] == 10001 || $_SESSION[nbilhete] == 10002) echo ""; else echo "esconder"; ?>" id="milhar1">
	<div class="form-group col-lg-6">
		<label for="">1º Prêmio</label>
		<input name="premio[]" value="<?php $k=0; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">2º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">3º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">4º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">5º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	</div>
	<div class="<?php if($_SESSION[nbilhete] == 10002) echo ""; else echo "esconder"; ?>" id="milhar2">
	<div class="form-group col-lg-6">
		<label for="">6º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">7º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">8º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">9º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	<div class="form-group col-lg-6">
		<label for="">10º Prêmio</label>
		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
	</div>
	</div>
</div>
</form>
<script>
	function formulario(valor){

		if(valor == 10001 || valor == 10002){


			if(valor == 10001){
				$("#milhar1").removeClass("esconder");
				$("#milhar2").addClass("esconder");
			}else{
				$("#milhar1").removeClass("esconder");
				$("#milhar2").removeClass("esconder");
			}

			$("#detalhes").removeClass("esconder");

		}else{

			$("#milhar1").removeClass("esconder");
			$("#milhar2").removeClass("esconder");
			$("#detalhes").removeClass("esconder");

			$("#milhar1").addClass("esconder");
			$("#milhar2").addClass("esconder");
			$("#detalhes").addClass("esconder");


		}

	}
</script>
<?php } ?>