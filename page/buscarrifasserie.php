<?php

error_reporting(E_ALL);
set_time_limit(10);

if(!isset($_SESSION)) session_start();

if(isset($_POST['montar_carrinho'])) {

    $rifa_cod = intval($_POST['montar_carrinho']);
    $_SESSION['rifa_dezena_bolao'] = true;
    if(isset($_SESSION['qr_order']))
    	unset($_SESSION['qr_order']);
    $_SESSION['carrinho_admin'] = array();
    
    if(!isset($_SESSION['carrinho_admin'][$rifa_cod]))
        $_SESSION['carrinho_admin'][$rifa_cod] = Array();

    foreach(explode(',', $_POST['bil']) as $v){
        $_SESSION['carrinho_admin'][$rifa_cod][] = intval($v);
    }

    die('ok');

}


include("class/conexao.php");
$conexao = new Conexao(true);

$rifa_cod = intval($_GET['rifa']);

if(!isset($_GET['rifa']) || $rifa_cod <= 0)
	die();

if(isset($_POST['serie']) || isset($_POST['venda']) || isset($_POST['telefone'])) {

	$rifa = $conexao
		->select('rifa_maxbilhetes, rifa_dtsorteio')
		->from('tbl_rifas')
		->where("rifa_cod = '$rifa_cod'")
		->limit(1)
		->executeNGet();

	if(isset($_POST['serie'])) {
		$_POST['serie'] = str_replace(' ', '%', $_POST['serie']);
		$sql_code = " AND c.comp_serie like '%{$_POST['serie']}%' ";
	} else if (isset($_POST['venda'])) {
		$sql_code = " AND c.comp_cod = '{$_POST['venda']}' ";
	} else if(isset($_POST['telefone'])) {
		$telefoneValido = $_POST['telefone'];
		$sql_code = " AND u.usu_celular LIKE '%$telefoneValido%' ";
	}

	// ja foi vendida?
	$venda = $conexao
		->select('c.comp_cod, rev.usu_nome as revendedor, rev.usu_celular as telefone, u.usu_cod as cliente_corrente, u.usu_nome as cliente, u.usu_celular as cliente_telefone, b.bil_numero')
		->from('tbl_compra c, tbl_bilhetes b, tbl_usuario u, tbl_usuario rev')
		->where("c.comp_cod = b.bil_compra $sql_code AND b.bil_rifa = '$rifa_cod' AND u.usu_cod = c.comp_cliente AND rev.usu_cod = c.comp_revendedor")
		->orderby('u.usu_cod ASC')
		->executeNGet();

	$titulo = '';
	if(!$venda){
		$titulo = "Série não Vendida!";
		$conteudo = "<p><a href=\"http://www.rifasbrasil.com.br/index.php?p=buscarrifas&rifa={$_GET['rifa']}\">Pesquisar novamente</a></p>";
	} else {

		$vendas = $conexao
			->select('c.comp_cod, rev.usu_nome as revendedor, rev.usu_celular as telefone, u.usu_nome as cliente, b.bil_numero')
			->from('tbl_compra c, tbl_bilhetes b, tbl_usuario u, tbl_usuario rev')
			->where("c.comp_cod = b.bil_compra AND b.bil_rifa = '$rifa_cod' AND u.usu_cod = c.comp_cliente AND rev.usu_cod = c.comp_revendedor")
			->executeNGet();

		$query_bilhetes = $conexao
			->select('dezena')
			->from('sorteio_dezenas')
			->where("rifa = '$rifa_cod'")
			->executeNGet();

		$bilhetes_sorteados = array();
		foreach($query_bilhetes as $bs){
			$bilhetes_sorteados[intval($bs['dezena'])] = true;
		}

		$array_vendas = array();
		$vendas_com_dez_acertos = array();
		$venda_agrupada = array();

		$vendas_originais = array();
		foreach($venda as $v){
			$vendas_originais[$v['comp_cod']] = $v;
		}


		foreach($vendas as $v){

			$bilhete = intval($v['bil_numero']);

			if(!$array_vendas[$v['comp_cod']])
				$array_vendas[$v['comp_cod']] = array();

			if(!$array_vendas[$v['comp_cod']]['bilhete'])
				$array_vendas[$v['comp_cod']]['bilhete'] = array();

			$array_vendas[$v['comp_cod']]['bilhete'][$bilhete] = true;

			if(!$array_vendas[$v['comp_cod']]['acerto'])
				$array_vendas[$v['comp_cod']]['acerto'] = 0;

			if($bilhetes_sorteados[$bilhete]) {
				$array_vendas[$v['comp_cod']]['acerto']++;
			}

			if($array_vendas[$v['comp_cod']]['acerto'] == 10)
				$vendas_com_dez_acertos[] = $v['comp_cod'];

		}

		$menor_pontuacao = 0;
		$maior_pontuacao = 0;

		$pontuacoes = array();
		foreach($array_vendas as $mp){
			$pontuacoes[$mp['acerto']] = true;
		}
		$menor_pontuacao = min(array_keys($pontuacoes));
		$maior_pontuacao = max(array_keys($pontuacoes));
		$segunda_melhor = $maior_pontuacao;

		while($segunda_melhor > 0){
			$segunda_melhor--;
			if($pontuacoes[$segunda_melhor])
				break;
		}

		$extra = '';
		if(isset($_GET['checkbox']))
			$extra = "<td></td>";

		$extra2 = '';
		if(isset($_GET['checkbox']))
			$extra2 = '<div class="form-group text-right form-inline"><button onclick="show_modal();" type="button" class="btn-success">Prosseguir</button></div>';


		$conteudo = $extra2 . "<div class=\"table-responsive\"><table class=\"table table-bordered\">
		<thead>
			<tr>
				<td>Aposta</td>
				<td>Pontos</td>
				" . $extra . "
			</tr>
		</thead>
				<tbody>";

		$cliente_corrente = -1;
		foreach($vendas_originais as $comp_cod=>$v){
			$bilhetes = array_keys($array_vendas[$v['comp_cod']]['bilhete']);
			$todos_bilhetes = array_keys($array_vendas[$v['comp_cod']]['bilhete']);
			foreach($bilhetes as $k=>$bil){
				// adiciona o padding
				if($bilhetes_sorteados[$bil])
					$bilhetes[$k] = '<span style="font-weight:bold; color:green;">['. substr('00' . $bil, -2) . ']</span>';
				else
					$bilhetes[$k] = '<span style="font-weight:bold; color:red;">['. substr('00' . $bil, -2) . ']</span>';
			}

			$extra = '2';
			if(isset($_GET['checkbox']))
				$extra = "3";
			
			if($v['cliente_corrente'] != $cliente_corrente){
				$cliente_corrente = $v['cliente_corrente'];
				$conteudo .= '<tr><td colspan="'.$extra.'">' . $v['cliente'] . ' (Tel.: ' . $v['cliente_telefone'] . ')</td></tr>';
			}

			
			if(isset($_GET['checkbox']))
				$extra = "<td><input class=\"compra_marcada\" value=\"" . implode(', ', $todos_bilhetes) . "\" type=\"checkbox\"></td>";
			
			$conteudo .= "<tr><td>" . implode(', ', $bilhetes) . "</td><td>". $array_vendas[$v['comp_cod']]['acerto'] . "</td>".$extra."</tr>";
			
		}

		$conteudo .= "
				</tbody>
			</table></div>";

		if(count($vendas_com_dez_acertos) > 0){
			$titulo = "Pontuação Finalizada!";
			$conteudo .= "<p>Baixe os PDF abaixo:</p><p>";
			if(!isset($_GET['checkbox']))
				$_GET['checkbox'] = 0;
			else
				$_GET['checkbox'] = 1;
			$conteudo .= "<a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?checkbox=" . $_GET['checkbox'] . "&rifa=" . $rifa_cod . "&pontos=" . $maior_pontuacao . "', '_blank');\">AQUI</a> - Prêmio Sortudo<br>";
			$conteudo .= "<a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?checkbox=" . $_GET['checkbox'] . "&rifa=" . $rifa_cod . "&pontos=" . $segunda_melhor . "', '_blank');\">AQUI</a> - Prêmio Consolação<br>";
			$conteudo .= "<a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?checkbox=" . $_GET['checkbox'] . "&rifa=" . $rifa_cod . "&pontos=" . $menor_pontuacao . "', '_blank');\">AQUI</a> - Prêmio Azarão<br>";
			$conteudo .= "</p>";
		} else {
			$titulo = "Pontuação em Andamento!";
			$conteudo .= "<p>Clique <a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?checkbox=" . $_GET['checkbox'] . "&rifa=" . $rifa_cod . "&pontos=" . $maior_pontuacao . "', '_blank');\">AQUI</a> baixe PDF dos Melhores pontuados!</p>";
		}

	}

?>
	<section class="section-default" id="destaque">
		<div class="col-md-12 col-lg-10 center-block">
		    <b><?= $titulo; ?></b><hr>
			<div class="col-md-12 col-lg-12 table-responsive">
			<?php echo $conteudo ;?>        
			</div>
	    	<div class="clearfix"></div>
	    </div>
	</section>
<?php } else { ?>
<section class="section-default" id="destaque">
	<div class="col-md-12 col-lg-10 center-block">
	    <b>Busque por telefone, série ou Número da Venda</b><hr>
	    <div class="col-md-12">
	    	<?php if($_GET['tipo'] == 'telefone') { ?>
			<form action="" method="post">
				<div class="form-group form-inline" style="padding-top: 20px;">
					<label for="">Busca por telefone</label>
					<input type="text" class="form-control" name="telefone">
					<button class="btn btn-success">Pesquisar</button>
				</div>
			</form>
	    	<?php } else if($_GET['tipo'] == 'serie') { ?>
	    	<form action="" method="post">
				<div class="form-group form-inline" style="padding-top: 20px;">
					<label for="">Busca por Série</label>
					<input type="text" class="form-control" name="serie">
					<button class="btn btn-success">Pesquisar</button>
				</div>
			</form>
			<?php } else if($_GET['tipo'] == 'venda') { ?>
			<form action="" method="post">
				<div class="form-group form-inline" style="padding-top: 20px;">
					<label for="">Busca por Número da Venda</label>
					<input type="text" class="form-control" name="venda">
					<button class="btn btn-success">Pesquisar</button>
				</div>
			</form>
			<?php } ?>
	    </div>
    	<div class="clearfix"></div>
    </div>
</section>
<?php } ?>

<div id="rifa_destino" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">Selecione a Rifa de Destino</h4>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <select name="rifa_destino" id="rifa_destino_input" class="form-control">
                                <?php
                                if($_SESSION['admin'] == 'S')
                                    $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where u.usu_cod = r.rifa_dono order by r.rifa_cod DESC";
                                else
                                    $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where (u.usu_cod = r.rifa_dono and r.rifa_dono = '{$_SESSION['usuario']}') or r.rifa_cod in (SELECT rifa_cod from tbl_revendedor where usu_cod = '{$_SESSION['usuario']}' or pai = '{$_SESSION['usuario']}') group by r.rifa_cod order by r.rifa_cod DESC";
                                
                                $consulta = $mysqli->query($rifas_do_usuario) or die($mysqli->error);
                                $assoc    = $consulta->fetch_assoc();
                                do{ 
                                    if(time() <= (strtotime($assoc['rifa_dtsorteio']) + 86400)) { ?>
                                        <option value="<?= $assoc['rifa_cod']; ?>"><?= $assoc['rifa_cod']; ?> - <?= $assoc['rifa_titulo']; ?></option>
                                <?php
                                    } 
                                }while($assoc    = $consulta->fetch_assoc());
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn_pross" class="btn btn-primary">Enviar</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<script>

	function show_modal(){
		let bilhetes = [];
		$('.compra_marcada').each(function(){
			let el = $(this);
			if(el.is(':checked'))
				bilhetes.push(el.val());
		});
		if(bilhetes.length == 0)
			return alert('Nenhum item foi selecionado!');
		$('#rifa_destino_input option[value="<?= intval($_GET['rifa']); ?>"]').attr('selected', true);
		$('#rifa_destino').modal('show');
		$('#btn_pross').off();
		$('#btn_pross').click(function(){
			
			console.log(bilhetes);
			$.post('page/buscarrifas.php', {
				montar_carrinho: $('#rifa_destino_input option:selected').val(),
				bil: bilhetes.join(',')
			}).done(function(r){
				console.log(r);
				location.href='//rifasbrasil.com.br/admin/index.php?p=cadastro_cliente';
			}).fail(function(r){
				console.log(r);
				alert("Algum erro ocorreu! Tente novamente");
			});
		});
	}

</script>