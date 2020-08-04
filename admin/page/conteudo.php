<?php

include("../class/conexao.php");

if(isset($_POST[enviar])){

	foreach($_POST as $ch=>$va){
		$a[$ch] = $mysqli->escape_string($va);
	}

	DBExecute("UPDATE tbl_parametro SET par_quemsomos = '$a[quemsomos]',
		par_comofunciona = '$a[comofunciona]',
		par_bannerpagseguro = '$a[pagseguro]',
		par_ccaridade = '$a[pcaridade]',
		par_crb = '$a[prb]'

		WHERE par_codigo = '1'", $mysqli);	

}


$par_code = "SELECT * FROM tbl_parametro WHERE par_codigo = '1' ";
$par_query = $mysqli->query($par_code) or die($mysqli->error);
$par = $par_query->fetch_assoc();

?>

	<div class="col-lg-12 text-left">
		<h3>Conteúdos</h3>
		<hr>
		<?php if(count($erro) > 0){ ?>
		<div style="padding:10px; margin:10px 0 10px 0;" class="col-lg-12 bg-warning text-center">
			<?php foreach($erro as $msg) echo "<p>{$msg}</p>"; ?>
		</div>
		<?php } ?>
	</div>
	<div class="col-lg-12 text-left">
		<form method="POST" action="">
			
			<div class="col-lg-4 form-group">
				<label for="">Página Quem Somos</label>
				<textarea name="quemsomos" id="quemsomos" cols="30" rows="10" class="form-control"><?php echo $par[par_quemsomos];?></textarea>
			</div>
			<div class="col-lg-4 form-group">
				<label for="">Página Como Funciona</label>
				<textarea name="comofunciona" id="comofunciona" cols="30" rows="10" class="form-control"><?php echo $par[par_comofunciona];?></textarea>
			</div>
			<div class="col-lg-4 form-group">
				<label for="">Banner Pagseguro</label>
				<textarea name="pagseguro" id="pagseguro" cols="30" rows="10" class="form-control"><?php echo $par[par_bannerpagseguro];?></textarea>
			</div>
			<div class="col-lg-12 form-group">
				<label for="">Porcentagens</label>
			</div>
			<div class="col-lg-12 form-inline form-group">
				<p>Caridade: <input size="2" value="<?php echo $par[par_ccaridade];?>" name="pcaridade" type="text" class="form-control"> %</p>
				<p>RifasBRASIL: <input size="2" value="<?php echo $par[par_crb];?>" name="prb" type="text" class="form-control"> % </p>
			</div>

			<div class="col-lg-12 form-group text-center">
				<button class="btn-success btn" name="enviar" value="ok">Salvar</button>
			</div>
		</form>
	</div>
<script src="//cdn.ckeditor.com/4.5.5/standard/ckeditor.js"></script>
<script>
CKEDITOR.replace( 'quemsomos' );
CKEDITOR.replace( 'comofunciona' );
CKEDITOR.replace( 'pagseguro' );
</script>