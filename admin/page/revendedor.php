<?php
$erro = array();
if(isset($_POST['novaRifa'])){
	@session_start();
	include("../../class/conexao.php");
	include("../../class/function_limparNumero.php");

	if(!isset($_SESSION['usuario']))
		die(json_encode(array('erro'=>1, 'msg'=>'Você não está logado.')));
	if(!isset($_POST['novaRifa']))
		die(json_encode(array('erro'=>1, 'msg'=>'Rifa inválida.')));
	
	$novaRifa = intval($_POST['novaRifa']);
	$rifa = intval($_POST['rifa']);

	//verifica se o usuario logado atualmente é o dono da rifa
	$queryUsuario = $mysqli->query("Select rifa_dono from tbl_rifas where rifa_cod = '$rifa'") or die($mysqli->error);
	$fetchUsuario = $queryUsuario->fetch_assoc();
	do{
		$rifa_dono = $fetchUsuario['rifa_dono'];
	}while($fetchUsuario = $queryUsuario->fetch_assoc());
	if($rifa_dono != $_SESSION['usuario'])
		die(json_encode(array('erro'=>1, 'msg'=>'Você não é dono desta rifa.')));

	$queryRevendedores = $mysqli->query("Select * from tbl_revendedor where rifa_cod = '$rifa'") or die($mysqli->error);
	$fetchRevendedores = $queryRevendedores->fetch_assoc();

	do{
		if(!$fetchRevendedores['supervisor'])
			$fetchRevendedores['supervisor'] = 0;
		if(!$fetchRevendedores['pai'])
			$fetchRevendedores['pai'] = 0;
		$inserted = $mysqli->query("INSERT INTO tbl_revendedor (
			rifa_cod, 
			usu_cod, 
			comissao, 
			supervisor, 
			pai) 
			VALUES(
			'$novaRifa', 
			'{$fetchRevendedores['usu_cod']}', 
			'{$fetchRevendedores['comissao']}',
			'{$fetchRevendedores['supervisor']}',
			'{$fetchRevendedores['pai']}'
			)");

		$notDelete[] = $mysqli->insert_id;
		$deleteAfter[] = "DELETE FROM tbl_revendedor WHERE rifa_cod = '$novaRifa' AND usu_cod = '{$fetchRevendedores['usu_cod']}' ";

	}while($fetchRevendedores = $queryRevendedores->fetch_assoc());

	foreach($deleteAfter as $da){
		$mysqli->query($da . " AND codigo NOT IN (".implode(',',$notDelete).") ");
	}
	

	die(json_encode(array('erro'=>0, 'msg'=>"Revendedores importados com sucesso.")));

}

if(isset($_POST['rev_email'])){
	include("../../class/conexao.php");
	include("../../class/function_limparNumero.php");
	$rifa_cod = intval($_POST['rifa']);
	header('content-type: application/json; charset=utf-8');
	if(!filter_var($_POST['rev_email'], FILTER_VALIDATE_EMAIL))
		$erro[] = "Digite um e-mail válido";
	if(strlen(limparNumero($_POST['comissao'])) == 0)
		$erro[] = "Preencha a porcentagem de comissão do revendedor.";
	if((limparNumero($_POST['comissao'])%1) > 0)
		$erro[] = "A comissão deve ser um número inteiro.";
	if((limparNumero($_POST['comissao'])) > 100 || (limparNumero($_POST['comissao'])) < 1)
		$erro[] = "A comissão não pode ser maior que 100% nem menor que 1%.";
	if(count($erro) == 0){
		$email = $mysqli->escape_string($_POST['rev_email']);
		$comissao = limparNumero($_POST['comissao']);
		$verif_code = "SELECT usu_cod from tbl_usuario WHERE usu_email = '$email'";

		$pai = "NULL";
		if($_POST['pai'])
			$pai = "'".intval($_POST['pai'])."'";

		$verif_query = $mysqli->query($verif_code) or die($mysqli->error);
		$verif = $verif_query->fetch_assoc();
		$verif_num = $verif_query->num_rows;
		// verifica se esse revendedor já foi adicionado à essa rifa
		$con_code = "SELECT * from tbl_revendedor WHERE usu_cod = '{$verif['usu_cod']}' and rifa_cod = '$rifa_cod'";
		$con_query = $mysqli->query($con_code) or die($mysqli->error);
		$con = $con_query->fetch_assoc();
		$con_num = $con_query->num_rows;
		if($con_num ==	0){
			if($verif_num == 1){
				$confirm = $mysqli->query("INSERT INTO tbl_revendedor (rifa_cod, usu_cod, comissao, pai) values ('$rifa_cod','{$verif['usu_cod']}', '$comissao', $pai)") or die($mysqli->error);
				if($confirm){
					$retorno['erro'] = '';
					$retorno['existe'] = $rifa_cod;
				}else{
					$retorno['erro'] = 'true';
					$retorno['existe'] = $rifa_cod;
				}
				die(json_encode($retorno));
			}else{
				/*$erro[] = "Nenhum usuário do site possui o e-mail informado. Antes de adicionar um revendedor, peça pra que ele se cadastre.";
				$retorno = array();
				if(count($erro) > 0){*/
					$retorno['erro'] = '';
					$retorno['existe'] = false;
				//}
					
				die(json_encode($retorno));
			}
		}else{
			$retorno['erro'] = array("Esse revendedor já foi adicionado à essa rifa.");
			$retorno['existe'] = false;
			die(json_encode($retorno));
		}
		
	}
	$retorno = array();
	if(count($erro) > 0){
		$retorno['erro'] = $erro;
	}
	
	die(json_encode($retorno));
}
include("../class/conexao.php");
include("../class/function_limparNumero.php");
$rifa_cod = intval($_GET['rifa']);
if(isset($_POST['usuario'])){
	$remover = $_POST['usuario'];
	if($remover > 0){
		$qr1 = $mysqli->query("DELETE FROM tbl_revendedor WHERE usu_cod = '$remover' AND rifa_cod = '$rifa_cod'");
		$qr2 = $mysqli->query("DELETE FROM tbl_revendedor WHERE pai = '$remover' AND rifa_cod = '$rifa_cod'");
		if($qr1 && $qr2){
			$erro[] = "Revendedor removido com sucesso";
		}
	}
}



// PAGINAÇÂO START

$registros_por_pagina = 200;

$limit = " 0,$registros_por_pagina";
if($_POST['pagina']){
	$limit = (intval($_POST['pagina'])*$registros_por_pagina) . ",$registros_por_pagina";
}

$sup_code = "SELECT * from tbl_revendedor where rifa_cod = '$rifa_cod' and usu_cod = '{$_SESSION['usuario']}' and supervisor = 1 LIMIT 1";
$sup_qery = $mysqli->query($sup_code) or die($mysqli->error);
$supervisor = $sup_qery->fetch_assoc();

if($_POST['pesquisar']){
	$_POST['pesquisar'] = $mysqli->real_escape_string($_POST['pesquisar']);
	$extrasql = " AND (u.usu_nome like '%{$_POST['pesquisar']}%' or u.usu_email like '%{$_POST['pesquisar']}%') ";
}
else
	$extrasql = "";


$rev_code = "SELECT u.*, r.*, rev.usu_nome as nomePai from tbl_usuario u, tbl_revendedor r left join tbl_usuario rev on rev.usu_cod = r.pai WHERE u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa_cod' $extrasql LIMIT $limit";
if(isset($supervisor['supervisor']) and $supervisor['supervisor'] == 1){
	$rev_code = "SELECT u.*, r.*, rev.usu_nome as nomePai from tbl_usuario u, tbl_revendedor r left join tbl_usuario rev on rev.usu_cod = r.pai WHERE u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa_cod' and r.pai = '{$_SESSION['usuario']}' $extrasql LIMIT $limit";
}

$rev_query = $mysqli->query($rev_code) or die($mysqli->error);
$rev = $rev_query->fetch_assoc();
$rev_num = $rev_query->num_rows;

$paginacao_query = "SELECT count(*) as c from tbl_usuario u, tbl_revendedor r WHERE u.usu_cod = r.usu_cod and r.rifa_cod = '$rifa_cod'";
$paginacao_exec  = $mysqli->query($paginacao_query) or die($mysqli->error);
$paginacao       = $paginacao_exec->fetch_assoc();


$quantidade_registros = $paginacao['c'];
$quantidade_paginas = ceil($quantidade_registros / $registros_por_pagina);

// PAGINAÇÂO END

$rifa_code = "SELECT rifa_dono, rifa_titulo FROM tbl_rifas WHERE rifa_cod = '$rifa_cod'";
$rifaq = $mysqli->query($rifa_code) or die($mysqli->error);
$rifa = $rifaq->fetch_assoc();

$isDonoDaRifa = false;
$isSupervisor = false;
$isAdmin      = false;

if($rifa['rifa_dono'] == $_SESSION['usuario'])
	$isDonoDaRifa = true;

/*if(count($supervisor) > 0)
	$isRevendedor = true;*/

$isRevendedor = false;

if($supervisor['supervisor'] == 1)
	$isSupervisor = true;

if($_SESSION['admin'] == 'S')
	$isAdmin = true;

$rifasImportar = "SELECT rifa_cod, rifa_titulo FROM tbl_rifas WHERE rifa_dono = '{$_SESSION['usuario']}'";

$exec_rifasImportar = $mysqli->query($rifasImportar) or die($mysqli->error);
$fetch_rifasImportar = $exec_rifasImportar->fetch_assoc();

if($isDonoDaRifa || $isAdmin || $isSupervisor || $isRevendedor){
?>
<div class="col-lg-12 text-left">
	<h3>Revendedores</h3>
	<p>Rifa: <?php echo $rifa['rifa_titulo']; ?></p>
	<hr>
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12">
		<?php foreach($erro as $msg) echo "<div class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
		<div class="spacer"></div>
	</div>

	<?php } ?>
	<p>
		
			<div class="col-sm-6 form-group form-inline">
				<form method="post" id="form" action="">
					<label for="">Adicionar um Revendedor</label>
					<input type="hidden" name="rifa" value="<?php echo $_GET['rifa']; ?>" id="rifa">
					<input type="hidden" name="pai" value="<?php if(!$isDonoDaRifa && !$isAdmin) echo $_SESSION['usuario']; ?>">
					<input required placeholder="E-mail do revendedor" name="rev_email" id="rev_email" type="email" class="form-control"> 
					<input name="comissao" class="form-control" size="4" id="rev_comissao" placeholder="Ex: 15%" type="text">
					<button type="button" onclick="verificarEmail();" class="btn btn-success">Salvar</button>
				</form>
			</div>
			<div class="col-sm-6 form-group text-right form-inline">
				<form method="post" id="form" action="">
					<button onclick="$('#modalImportar').modal('show');" type="button" class="btn btn-primary">Importar</button> ou
					<div class="input-group">
				    <input type="text" name="pesquisar" value="<?= $_POST['pesquisar']; ?>" required class="form-control" placeholder="Pesquisar Revendedores">
				    <div class="input-group-btn">
				      <button class="btn btn-default" type="submit">
				        <i class="glyphicon glyphicon-search"></i>
				      </button>
				    </div>
			    </form>
			</div>
			</div>
		
		
	</p>
	<?php if($quantidade_paginas > 1){ ?>

		<?php 

                    $pagina_atual    = 0;
                    if($_POST['pagina'])
                        $pagina_atual = $_POST['pagina'];

                    $inicio          = $pagina_atual - 3;
                    $final           = $pagina_atual + 3;
                        
                    $limite_inferior = $pagina_atual - 6;
                    $limite_superior = $pagina_atual + 6;
                   
                    if($inicio < 0) $inicio = 0;

                    if($final > ($quantidade_paginas-1)) $final = $quantidade_paginas-1;

                    if($limite_inferior < 0) $limite_inferior = 0;

                    if($limite_superior > ($quantidade_paginas-1)) $limite_superior = ($quantidade_paginas-1);
                    
              ?>

                    


	<div class="text-center">
		<form action="" method="post">
			<?php foreach($_POST as $key=>$value){ echo '<input name="'.$key.'" value="'.$value.'" type="hidden">'; } ?>
			<div class="form-group">
				<label for="">Páginas (<?= $quantidade_paginas; ?>)</label>
			</div>
			<div class="form-group">
				<button type="submit" name="pagina" value="0" class="btn btn-default"><<</button>
                <button type="submit" name="pagina" value="<?= $limite_inferior; ?>" class="btn btn-default"><</button>

				<?php for($k = $inicio; $k < $final; $k++){ ?>
				<button type="submit" name="pagina" value="<?= $k; ?>" class="btn btn-default <?php if($_POST['pagina'] == $k) echo 'btn-success' ?>"><?= $k+1; ?></button>
				<?php } ?>

				<button type="submit" name="pagina" value="<?= $limite_superior; ?>" class="btn btn-default">></button>
                <button type="submit" name="pagina" value="<?= ($quantidade_paginas-1); ?>" class="btn btn-default">>></button>
			
			</div>
		</form>
	</div>
	<?php } ?>
	
</div>

<script>
function importar(){
	var rifa = $('#importarRifa').val();
	$.post('page/revendedor.php', {rifa:rifa, novaRifa:<?= intval($_GET['rifa']); ?>})
	.done(function(r){
		console.log(r);
		if(r.erro == 1) alert(r.msg);
		else
			location.href='index.php?p=revendedor&rifa=<?= intval($_GET['rifa']); ?>';
	})
	.fail(function(r){
		console.log(r);
	});
}
</script>
<style>
.select2-container{
	width:100%;
}
</style>
<!-- Modal -->
<div id="modalImportar" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Importar Revendedores de outra Rifa</h4>
      </div>
      <div class="modal-body">
      	<div class="form-group">
      		<select type="text" class="form-control" id="importarRifa">
      			<option value="">Lista de Rifas</option>
      			<?php do{ ?>
				<option value="<?= $fetch_rifasImportar['rifa_cod']; ?>"><?= $fetch_rifasImportar['rifa_cod']; ?> - <?= $fetch_rifasImportar['rifa_titulo']; ?></option>
      			<?php }while($fetch_rifasImportar = $exec_rifasImportar->fetch_assoc()); ?>
      			
      		</select>
      	</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" onclick="importar();" class="btn btn-success">Importar</button>
      </div>
    </div>

  </div>
</div>

<div class="col-lg-12 text-left table-responsive">


	<?php
	if($_POST['pesquisar'])
		$pesquisar = '<div class="text-center">Nenhum revendedor encontrado - <a href="index.php?p=revendedor&rifa='.$_GET['rifa'].'">Limpar Pesquisa</a></div>';
	else
		$pesquisar = "<div class=\"text-center\">Nenhuma revendedor foi adicionado à essa rifa</div>";
	
	if($rev_num == 0) echo $pesquisar; else { ?>

	<?php
	if($_POST['pesquisar'])
		echo '<div class="form-group text-center"><p><a href="index.php?p=revendedor&rifa='.$_GET['rifa'].'">Limpar Pesquisa</a></p></div>';

	?>
	
	<table class="table table-hover table-bordered">
		<tr style="font-weight:bold;">
			<td>Rifa</td>
			<td>Revendedor</td>
			<td>Comissão</td>
			<td>Vendas</td>	
			<td>Grupo</td>
			<td  width="30%"></td>
		</tr>
		<?php 
		$input_id = 0;

		do { 

			$ven_code = "SELECT count(*) as c 
			FROM tbl_bilhetes 
			WHERE bil_rifa= '$rifa_cod' AND bil_compra IN(Select comp_cod from tbl_compra where comp_revendedor = '{$rev['usu_cod']}')";
			$ven_q = $mysqli->query($ven_code) or die($mysqli->error);
			$ven = $ven_q->fetch_assoc();
			// pesquisar grupos que o revendedor revende
			$grupos_do_revendedor = DBSelect("Select gr.*, r.*, r.codigo as revenda_cod from grupo_revendedor gr, revenda r where gr.revenda = r.codigo and r.rifa = '$rifa_cod' and r.vendedor = '{$rev['usu_cod']}'", $mysqli);
		?>
		<tr>
			<td><?php echo $rifa['rifa_titulo']; ?></td>
			<td><?php echo "{$rev['usu_nome']} <{$rev['usu_email']}>"; ?>
				
				<?php if(isset($rev['nomePai'])) echo "<p><small>cadastrado por {$rev['nomePai']}</small></p>"; ?>
			</td>
			<td><?php echo $rev['comissao']; ?>%</td>
			<td><?php echo $ven['c']; ?></td>	
			<td>
				<div class="form-inline form-group">

					<form action=""  method="post">
						<input name="grupo" value="<?php echo $grupos_do_revendedor['grupo']; ?>" placeholder="GRUPO" id="gr_<?php echo $input_id; ?>" type="text" class="input-xs form-control">
						<button type="button" onclick="alterar_grupo(<?= $input_id++; ?>, '<?php echo $grupos_do_revendedor['revenda_cod']; ?>', '<?php echo $rifa_cod; ?>', '<?php echo $rev['usu_cod']; ?>');" class="btn btn-default">Salvar</button>
					</form>

				</div>
			</td>
			<td class="text-right">

				<?php if($isDonoDaRifa){ ?>

				<input type="hidden" id="function">

				<span id="isSupervisor_<?= $rev['codigo']; ?>" style="display:none;">
					<button type="Button" onclick="controller('get_revendedores', <?= $rifa_cod; ?>, <?= $rev['usu_cod'] ?>, <?= $rev['codigo']; ?>);" class="btn btn-alert btn-xs">
						Revendedores
					</button>
					<button type="Button" onclick="controller('destornar_supervisor', <?= $rifa_cod; ?>, <?= $rev['usu_cod'] ?>, <?= $rev['codigo']; ?>);" class="btn btn-default btn-xs">
						Desfazer Supervisor
					</button>
				</span>

				<span id="isNotSupervisor_<?= $rev['codigo']; ?>" style="display:none;">
					<button type="Button" onclick="controller('tornar_supervisor', <?= $rifa_cod; ?>, <?= $rev['usu_cod'] ?>, <?= $rev['codigo']; ?>);" class="btn btn-primary btn-xs">
					Supervisor
					</button>
				</span>
	
				<?php if($rev['supervisor'] == 1){ ?>
				<span id="inputArea_<?= $rev['codigo']; ?>">
					<button type="Button" onclick="controller('get_revendedores', <?= $rifa_cod; ?>, <?= $rev['usu_cod'] ?>, <?= $rev['codigo']; ?>);" class="btn btn-alert btn-xs">
						Revendedores
					</button>
					<button type="Button" onclick="controller('destornar_supervisor', <?= $rifa_cod; ?>, <?= $rev['usu_cod'] ?>, <?= $rev['codigo']; ?>);" class="btn btn-default btn-xs">
						Desfazer Supervisor
					</button>
				</span>
				<?php }else{ ?>
				<span id="inputArea_<?= $rev['codigo']; ?>">
					<button type="Button" onclick="controller('tornar_supervisor', <?= $rifa_cod; ?>, <?= $rev['usu_cod'] ?>, <?= $rev['codigo']; ?>);" class="btn btn-primary btn-xs">
					Supervisor
					</button>
				</span>
				<?php } ?>

				<?php } ?>

				<?php if($ven['c'] == 0) { ?>
				<form method="post" action="">
					<input name="usuario" value="<?php echo $rev['usu_cod']; ?>" type="hidden">
					<button type="submit" class="btn btn-xs btn-danger">remover</button>
				</form>
				<?php }else{ ?>
				<button onclick="javascript: alert('Você não pode excluir um revendedor que já vendeu bilhetes');" class="btn btn-xs btn-danger">remover</button>
				<?php } ?>
				
			</td>
		</tr>
		<?php }while($rev = $rev_query->fetch_assoc()); ?>
	</table> <?php } ?>
</div>
<?php }else echo '<h1>Acesso não autorizado.</h1>' ?>
<div class="modal fade" id="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Cadastre o Revendedor</h4>
      </div>
      <form id="cadastro" name="cadastro" method="POST"  action="">
      		<div class="modal-body">
        			
        			<input type="hidden" name="cadastrar" value="true">
        			<input type="hidden" name="innercad" value="true">
                    <div class="form-group col-lg-12">
                        <label for="">Nome*</label>
                        <input type="text" required name="nome" id="nome_cad" value="" class="form-control">
                    </div>
                    <div class="form-group col-lg-12">
                        <label for="">E-mail*</label>
                        <input type="email" readonly name="email" id="email_cad" value="" class="form-control">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="">Telefone*</label>
                        <input type="text" placeholder="(99) 9999-9999" id="telefone_cad" required name="telefone" value="" class="telefone form-control">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="">CPF*</label>
                        <input type="text" required name="cpf" value="" id="cpf_cad" class="cpf form-control">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="">Senha*</label>
                        <input type="password" required name="senha" id="senha1_cad" value="" class="form-control">
                    </div>
                    <div class="form-group col-lg-6">
                        <label for="">Repita a Senha*</label>
                        <input type="password" required name="rsenha" id="senha2_cad" value="" class="form-control">
                    </div>
                <div class="clearfix"></div>
            
      		</div>
      </form>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
        <button type="button"  onclick="cadastrarUsuario();" class=" btn-success btn">Cadastre-se</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="modal2" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Revendedores do Supervisor </h4>
      </div>

      	<div class="modal-body">

      		<form action="" id="formModal2">
			
				<div class="form-group col-sm-5">
					<label for="">E-mail do Revendedor</label>
					<input type="text" name="rev_email" class="form-control">
				</div>

				<div class="form-group col-sm-3">
					<label for="">Comissão</label>
					<input type="hidden" name="pai" value="" id="ipai">
					<input type="hidden" name="rifa" value="<?php echo $_GET['rifa']; ?>">
					<input type="text" placeholder="%" name="comissao" class="form-control">
				</div>

				<div class="form-group col-sm-4">
					<label for="">_</label>
					<button type="submit" id="disablebtn" onclick="add_revendedor();" class="btn btn-success form-control">Adicionar</button>
				</div>

			</form>

			<div id="conteudo_rev"></div>
        	
            
      	</div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>

	var functionCall;

	function add_revendedor(){

		console.log('fcall', functionCall);

		$( "#formModal2" ).on( "submit", function( event ) {
			event.preventDefault();
			$('#disablebtn').prop('disabled', true);
			console.log( $( this ).serialize() );
			var dados = $( this ).serialize();

			show_loading();

			$.post('page/revendedor.php', dados)
			.done(function(r){
				console.log('base123',r);
				end_loading();
				$('#disablebtn').prop('disabled', false);
				controller(functionCall.f,functionCall.rifa,functionCall.revendedor,functionCall.rev);
			})
			.fail(function(r){
				console.log(r);
				end_loading();
			});

		});

	}
	
	function showLoader(){
		$('#loader').removeClass('esconder');
	}
	function endLoader(){
		$('#loader').addClass('esconder');
	}
	function alterar_grupo(id, CodigoDaRevenda, CodigoDaRifa, CodigoDoRevendedor){
		var grupo = $('#gr_' + id).val();
		var limpar = 0;
		if(grupo.length == 0)
			limpar = CodigoDaRevenda; // significa que o grupo em questao deve ser deletado
		if(grupo.indexOf(',') > 0){
			return alert('Apenas um grupo por revendedor!');
			/* existe mais de um grupo no array
			var res = '[';
			grupo = grupo.split(',');
			for(var k =0; k < grupo.length; k ++){
				if(grupo[k].length > 0)
					res += '"'+grupo[k]+'",';
			}
			res = res.substring(0, res.length - 1);
			res += "]";
			grupo = res;*/
		}else{
			grupo = '["'+grupo+'"]';
		}
		if(1){
			var str = '{"rifa":"'+CodigoDaRifa+'", "resultado":[{"grupos":'+ grupo +', "vendedor":'+CodigoDoRevendedor+', "observacao":""}]}';
			showLoader();
			$.post('page/lotear_final-revenda.php', 
				{string:str, tela_revendedores:'true', limpar:limpar}).done(function(res){
					endLoader();
					console.log(res);
				}).fail(function(err){
					endLoader();
					console.log(err);
				});
		}
	}
	function verificarEmail(){
		var form = $('#form').serialize();
		console.log('cadastrando');
		console.log(form);
		showLoader();
		$.post('page/revendedor.php', form).done(function(dados){
			console.log(dados);
			endLoader();
			// verifica se deu erro
			if(dados.erro.length > 0){
				for(var k = 0; k < dados.erro.length; k++){
					alert(dados.erro[k]);
				}
				return;
			}else{
				if(dados.existe){
					// usuario já cadastrado, adicionado como revendedor!
					return location.href='index.php?p=revendedor&rifa=' + dados.existe;
				}else{
					$('#email_cad').val($('#rev_email').val());
					// revendedor nao existe
					return modalCadastro();
				}
				
			}
		}).fail(function(erro){
			endLoader();
			console.log(erro);
		});
	}



	function controller(f, rifa, revendedor, rev){

		functionCall = {f:f, rifa:rifa, revendedor:revendedor, rev:rev};

		var dados, after_function;

		$('#function').val(f);
		dados = {rifa:rifa, usuario:revendedor, function:f};

		if(f == 'tornar_supervisor'  || f == 'destornar_supervisor'){
			after_function = function(){
				$('#ipai').val(revendedor);
				console.log('#inputArea_' + rev);
				console.log($('#function').val());
				if($('#function').val() == 'tornar_supervisor')
					$('#inputArea_' + rev).html($('#isSupervisor_' + rev).html());
				else
					$('#inputArea_' + rev).html($('#isNotSupervisor_' + rev).html());
			}
		}
		if(f == 'get_revendedores'){
			after_function = function(d){

				var infos = JSON.parse(d);
				console.log('infos', infos);
				$('#ipai').val(revendedor);
				$('#modal2').modal('show');
				var codigo = '<table class="table table-bordered"><thead><tr><td>Revendedor</td><td>Comissão</td></tr></thead><tbody>';
				for(let k=0; k < infos.resultado.length; k++){
					if(infos.resultado[k]){
						codigo += '<tr><td>'+infos.resultado[k].usu_nome+'</td><td>'+infos.resultado[k].comissao+'</td></tr>';
					}
				}
				codigo += '</tbody></table><div class="clearfix"></div>';
				$('#conteudo_rev').html(codigo);

			}
		}

		showLoader();
		$.post('controller/revendedor.php', dados)
		.done(function(r){
			endLoader();
			after_function(r);
		})
		.fail(function(r){
			endLoader();
			alert('Falhou!');
		});

	}
	function cadastrarUsuario(){
		var form = $('#cadastro').serialize();
		var nome = $('#nome_cad'), 
		email = $('#email_cad'), 
		telefone = $('#telefone_cad'), 
		cpf = $('#cpf_cad'), 
		senha1 = $('#senha1_cad'), 
		senha2 = $('#senha2_cad');
		if(nome.val().length == 0)
			return alert('Preencha o nome');
		if(telefone.val().length < 10)
			return alert('Preencha o telefone com DDD');
		if(cpf.val().length < 11)
			return alert('Preencha o CPF');
		if(senha1.val().length < 6 || senha1.val().length > 16)
			return alert('A senha deve conter entre 6 e 16 caracteres.');
		if(senha1.val() != senha2.val())
			return alert('As senhas não batem');

		console.log(form);
		showLoader();
		$.post('../page/entrar.php', form).done(function(res){
			console.log(res);
			if(res.sucesso){
				//location.reload();
				return verificarEmail();
			}else{
				endLoader();
				if(res.msg){
					for(var k = 0; k < res.msg.length; k++){
						alert(res.msg[k]);
					}
					
				}


			}
		}).fail(function(err){
			console.log(err);
		});
	}
	
	function modalCadastro(){
		console.log('modal de cadastro');
		$('#modal').modal('show');
	}
	
</script>