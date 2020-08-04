<?php

include("../class/conexao.php");

if(isset($_POST[ok])){

	$nome = $mysqli->escape_string($_POST[titulo]);
	$link = $mysqli->escape_string($_POST[link]);

	$arquivo = $_FILES["imagem"];

	$tam_maximo = 5; //EM MBs
    $pasta = "../arquivos/imgs/banner";

    if($arquivo["size"] > ($tam_maximo*1000000))
        $erro[] = "A foto não deve ser maior que ".$tam_maximo."mb.";

    if(strlen($arquivo["name"]) == 0)
        $erro[] = "A foto não deve ser maior que ".$tam_maximo."mb.";

    echo $arquivo["name"];    $imagem = "ban-".md5($arquivo["name"].time()).substr($arquivo["name"], -4);

	if(copy($arquivo["tmp_name"], $pasta."/".$imagem)){

		$imagem = "arquivos/imgs/banner/".$imagem;
		$execute = DBExecute("INSERT INTO tbl_banner (ban_titulo, ban_imagem, ban_link) VALUES(
			'$nome','$imagem','$link')", $mysqli);
		if($execute)
			$erro[] = "Banner cadastrada com sucesso.";
		else
			$erro[] = "Falha ao cadastrar.";

	}else
		$erro[] = "Falha ao copiar arquivo.";


}

if(isset($_GET['deletar'])){

$codigo = intval($_GET['deletar']);
$execute = DBExecute("DELETE FROM tbl_banner WHERE ban_cod = '$codigo'", $mysqli);
$ban_img = DBSelect("SELECT ban_imagem as b FROM tbl_banner where ban_cod = '$codigo'", $mysqli);

if($execute && unlink("../".$ban_img[b]))
	$erro[] = "Banner deletado com sucesso.";
else
	$erro[] = "Falha ao deletar.";

echo "<script>location.href='index.php?p=banner';</script>";
exit();

}

$code_ban = "SELECT * FROM tbl_banner ORDER BY ban_cod DESC";
$exec_ban  = DBExecute($code_ban, $mysqli);
$banner = $exec_ban->fetch_assoc();

?>
<div class="col-lg-12 text-left">
	<h3>Banners</h3>
	<hr>
	<?php if(count($erro) > 0){ ?>

	<?php foreach($erro as $msg) echo "<div style='margin:10px 0 10px 0; padding:10px;' class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
	
	<?php } ?>
	
</div>
<div class="col-lg-8 text-left">
	<?php if($exec_ban->num_rows > 0){ ?>
	<table class="table table-bordered table-hover table-responsive">
		
		<tr style="font-weight:bold;">
			<td width="25%">Imagem</td>
			<td>Título</td>
			<td>Link</td>
			<td></td>
		</tr>
		
		<?php do{ ?>
		<tr>
			<td><img width="100%" src="http://rifasbrasil.com.br/confebraq/<?php echo $banner[ban_imagem]; ?>"></td>
			<td><?php echo $banner[ban_titulo]; ?></td>
			<td><input value="<?php echo $banner[ban_link]; ?>" class="form-control" type="text"> </td>
			<td class="text-right"><button type="button" onclick="javascript: location.href='index.php?p=banner&deletar=<?php echo $banner[ban_cod]; ?>';" class="btn">Remover</button></td>
		</tr>
		<?php } while($banner = $exec_ban->fetch_assoc()); ?>
	</table>
	<?php } else echo "<p>Nenhum banner foi cadastrado.</p>"; ?>
</div>

<div class="col-lg-4 text-left">
	<form method="post" enctype="multipart/form-data"  action="">
		<p>Cadastrar um Banner</p>
		<div class="form-group col-lg-12">
			<label for="">Título</label>
			<input name="titulo" required value="" type="text" class="form-control">
		</div>
		<div class="form-group col-lg-12">
			<label for="">Imagem</label>
			<input name="imagem" class="form-control" required type="file">
		</div>
		<div class="form-group col-lg-12">
			<label for="">link</label>
			<input name="link" class="form-control" value="http://" type="link">
		</div>
		<div class="col-lg-12">
			<button name="ok" value="1" class="btn btn-success">Salvar</button>
		</div>
	</form>
</div>
