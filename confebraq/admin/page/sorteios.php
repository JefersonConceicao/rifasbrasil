<?php

include("../class/conexao.php");

// Configurações
$tabela = "tbl_transacao";
$where = " where aprovada = 0";
$nome_dessa_pagina = "index.php";
$colunas_mostrar = array("valor", "usuario", "data");
$colunas_nomes = array("Valor", "Usuário", "Data");
$colunas_buscar = $colunas_mostrar;
$colunas_puxar = array("codigo", "valor", "usuario", "data", "aprovada");


if(isset($_GET['aprovar'])){

	$codigo = intval($_GET['aprovar']);
	

	$result = db_select($mysqli, "select valor, aprovada, usuario, usu_credito from tbl_transacao, tbl_usuario where tbl_usuario.usu_cod = tbl_transacao.usuario and tbl_transacao.codigo = '$codigo' limit 1", 1);

	if($result['aprovada'] == 0){



		$new_credito = $result['valor'];

		$r = DBExecute("update tbl_transacao set aprovada = 1 where codigo = '$codigo'", $mysqli);
		$final = $result['usu_credito'] + $new_credito;
		$sql_code = "update tbl_usuario set usu_credito = '$final' where usu_cod = '$result[usuario]'";
		$r = DBExecute($sql_code, $mysqli);

		if($r)
			$msg[] = "Crédito aprovado com sucesso.";
		else
			$erro[] = "Falha ao aprovar crédito: ".$r;

	}

}



$usuarios = db_select($mysqli, "select usu_cod, usu_nome from tbl_usuario where usu_cod in (select usuario from tbl_transacao where aprovada = 0)");
$user = array();
foreach($usuarios as $u){
	$user[$u['usu_cod']] = $u['usu_nome'];
}

function formatar_saida($dado, $col, $user){

	switch($col){
		
		case 'valor':
			return "R$ ".number_format($dado, 2, ',', '.');

        case 'data':
           	return date("d/m/Y h:i", strtotime($dado));

        case 'usuario':
           	return $user[$dado];

		default:
			return $dado;

	}

}


// Fixo

$codigo = "select ".implode(", ", $colunas_puxar)." from $tabela";
$total = DBCount("$codigo $where", $mysqli);
$dados = db_select($mysqli, "$codigo $where $limit");

?>

<div class="col-lg-12">
	<h1 class="page-header">Solicitações de Crédito  <small>[<?php echo $total; ?>]</small></h1>
</div>
<?php if(count($msg) > 0 || count($erro) > 0){ ?>
<div class="col-lg-12">

	<?php if(count($msg) > 0){ ?>
		<div class="alert alert-success  alert-dismissible animated fadeIn" role="alert">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
			<?php foreach($msg as $m){ echo "<p>$m</p>"; } ?>
		</div>
	<?php } if(count($erro) > 0){ ?>
	<div class="alert alert-danger  alert-dismissible  animated fadeIn" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		  <span aria-hidden="true">&times;</span>
		</button>
		<?php foreach($erro as $m){ echo "<p>$m</p>"; } ?>
	</div>
	<?php } ?>

</div>
<?php } ?>

<div class="col-lg-12 table-responsive">
	
	<?php if(count($dados) > 0){ ?>
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<?php foreach($colunas_nomes as $col) echo "<td>$col</td>"; ?>
				<td></td>
			</tr>
		</thead>
		<tbody>
			<?php foreach($dados as $dado){ ?>
			<tr id="tr_<?php echo $dado['codigo']; ?>">
				<?php foreach($colunas_mostrar as $col) echo "<td>".formatar_saida($dado[$col], $col, $user)."</td>"; ?>
				<td class="text-right">
					<button onclick="javascript: location.href='index.php?p=sorteios&aprovar=<?php echo $dado['codigo']; ?>';" class="btn btn-success btn-xs"><i class="glyphicon glyphicon-ok"></i> Aprovar</button>
					
					
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php }else{ ?>
	<div class="col-lg-12 text-center">
		<strong>Nenhum registro encontrado.</strong>
	</div>
	<?php } ?>

</div>
