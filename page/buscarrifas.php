<?php

error_reporting(E_ALL);

include("class/conexao.php");
$conexao = new Conexao(true);

$rifa_cod = intval($_GET['rifa']);

if(!isset($_GET['rifa']) || $rifa_cod <= 0)
	die();

// TODO remove
$serie = $conexao->escape($_GET['serie']);

$telefoneValido = false;
if(isset($_POST['telefone'])){
	if(strlen(preg_replace("/[^0-9]/", "%", $_POST['telefone'])) >= 8){
		$telefoneValido = $_POST['telefone'];
	}
}

if($telefoneValido !== false) {

	$rifa = $conexao
		->select('rifa_maxbilhetes, rifa_dtsorteio')
		->from('tbl_rifas')
		->where("rifa_cod = '$rifa_cod'")
		->limit(1)
		->executeNGet();

	$telefoneQuery = implode('%', $telefoneValido);

	// ja foi vendida?
	$venda = $conexao
		->select('c.comp_cod, rev.usu_nome as revendedor, rev.usu_celular as telefone, u.usu_cod as cliente_corrente, u.usu_nome as cliente, u.usu_celular as cliente_telefone, b.bil_numero')
		->from('tbl_compra c, tbl_bilhetes b, tbl_usuario u, tbl_usuario rev')
		->where("c.comp_cod = b.bil_compra AND u.usu_celular LIKE '%$telefoneValido%' AND b.bil_rifa = '$rifa_cod' AND u.usu_cod = c.comp_cliente AND rev.usu_cod = c.comp_revendedor")
		->orderby('u.usu_cod ASC')
		->executeNGet();

	$titulo = '';
	if(!$venda){
		$titulo = "Série não Vendida!";
		$conteudo = "";
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

		$conteudo = "<table class=\"table table-bordered\">
		<thead>
			<tr>
				<td>Aposta</td>
				<td>Pontos</td>
			</tr>
		</thead>
				<tbody>";

		$cliente_corrente = -1;
		foreach($vendas_originais as $v){
			$bilhetes = array_keys($array_vendas[$v['comp_cod']]['bilhete']);
			foreach($bilhetes as $k=>$bil){
				// adiciona o padding
				if($bilhetes_sorteados[$bil])
					$bilhetes[$k] = '<span style="font-weight:bold; color:green;">['. substr('00' . $bil, -2) . ']</span>';
				else
					$bilhetes[$k] = '<span style="font-weight:bold; color:red;">['. substr('00' . $bil, -2) . ']</span>';
			}
			
			if($v['cliente_corrente'] != $cliente_corrente){
				$cliente_corrente = $v['cliente_corrente'];
				$conteudo .= '<tr><td colspan="2">' . $v['cliente'] . ' (Tel.: ' . $v['cliente_telefone'] . ')</td></tr>';
			}
			
			$conteudo .= "<tr><td>" . implode(', ', $bilhetes) . "</td><td>". $array_vendas[$v['comp_cod']]['acerto'] . "</td></tr>";
			
		}

		$conteudo .= "
				</tbody>
			</table>";

		if(count($vendas_com_dez_acertos) > 0){
			$titulo = "Pontuação Finalizada!";
			$conteudo .= "<p>Baixe os PDF abaixo:</p><p>";
			$conteudo .= "<a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=" . $maior_pontuacao . "', '_blank');\">AQUI</a> - Prêmio Sortudo<br>";
			$conteudo .= "<a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=" . $segunda_melhor . "', '_blank');\">AQUI</a> - Prêmio Consolação<br>";
			$conteudo .= "<a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=" . $menor_pontuacao . "', '_blank');\">AQUI</a> - Prêmio Azarão<br>";
			$conteudo .= "</p>";
		} else {
			$titulo = "Pontuação em Andamento!";
			$conteudo .= "<p>Clique <a href=\"javascript: window.open('admin/page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=" . $maior_pontuacao . "', '_blank');\">AQUI</a> baixe PDF dos Melhores pontuados!</p>";
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
	    <b>Buscar por Telefone</b><hr>
		<div class="col-md-12 col-lg-12 table-responsive">
		<p>Neste página, você poderá buscar informações sobre as rifas das quais você participa. Digite seu telefone para pesquisar.</p> 
		<form action="" method="post">
			<div class="form-group form-inline">
				<label for="">Seu telefone</label>
				<input type="text" class="form-control" name="telefone">
				<button class="btn btn-success">Pesquisar</button>
			</div>
		</form>
		</div>
    	<div class="clearfix"></div>
    </div>
</section>
<?php } ?>