<?php

error_reporting(E_ALL);

include("class/conexao.php");
$conexao = new Conexao(true);

$rifa_cod = intval($_GET['rifa']);
$serie = $conexao->escape($_GET['serie']);

if(empty($_GET['serie']) && !empty($_GET['bil'])) {
	$fix = explode(',', $_GET['bil']);
	$res = array();
	foreach($fix as $v) {
		$res[] = substr('0' . $v, -2);
	}
	$serie = $conexao->select('*')->from('loteamento_rifa_bilhetes')->where("sequencia = '" . implode('-', $res) . "' AND rifa = '$rifa_cod'")->limit(1)->executeNGet('serie');
}

$rifa = $conexao
	->select('rifa_maxbilhetes, rifa_dtsorteio')
	->from('tbl_rifas')
	->where("rifa_cod = '$rifa_cod'")
	->limit(1)
	->executeNGet();

// ja foi vendida?
$venda = $conexao
	->select('c.comp_cod, rev.usu_nome as revendedor, rev.usu_celular as telefone, u.usu_nome as cliente, b.bil_numero')
	->from('tbl_compra c, tbl_bilhetes b, tbl_usuario u, tbl_usuario rev')
	->where("c.comp_cod = b.bil_compra AND c.comp_serie LIKE '%{$serie}%' AND b.bil_rifa = '$rifa_cod' AND u.usu_cod = c.comp_cliente AND rev.usu_cod = c.comp_revendedor")
	->limit(1)
	->executeNGet();

$titulo = '';
if(!$venda){
	$titulo = "Série não Vendida";
	$conteudo = '<div class="col-lg-3"><p>Vender na próxima edição?</p><div class="form-group form-inline"><button onclick="location.href=\'index.php?p=entrar&buscarrifas='.$rifa_cod.'&serie='.$serie.'&bil='.$_GET['bil'].'\';" class="btn btn-primary">Sim</button></div></div>';
} else {

	

	$vendas = $conexao
		->select('c.comp_cod, rev.usu_nome as revendedor, rev.usu_celular as telefone, u.usu_nome as cliente, b.bil_numero')
		->from('tbl_compra c, tbl_bilhetes b, tbl_usuario u, tbl_usuario rev')
		->where("c.comp_cod = b.bil_compra AND b.bil_rifa = '$rifa_cod' AND c.comp_serie LIKE '%{$serie}%' AND u.usu_cod = c.comp_cliente AND rev.usu_cod = c.comp_revendedor")
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

	$bilhetes = array_keys($array_vendas[$venda['comp_cod']]['bilhete']);
	foreach($bilhetes as $k=>$bil){
		// adiciona o padding
		if($bilhetes_sorteados[$bil])
			$bilhetes[$k] = '<span style="font-weight:bold; color:green;">['. substr('00' . $bil, -2) . ']</span>';
		else
			$bilhetes[$k] = '<span style="font-weight:bold; color:red;">['. substr('00' . $bil, -2) . ']</span>';
	}
	
	$conteudo = "<table class=\"table table-bordered\">
		<tbody>";
	$conteudo .= "
	<tr>
		<td>Cliente</td><td>{$venda['cliente']}</td>
	</tr>
	<tr>
		<td>Revendedor</td><td>{$venda['revendedor']} ({$venda['telefone']})</td>
	</tr>
	<tr>
		<td>Série</td><td>{$serie}</td>
	</tr>
	<tr>
		<td>Num. Aposta</td><td>{$venda['comp_cod']}</td>
	</tr>
	<tr>
		<td>Pontuação</td><td>" . $array_vendas[$venda['comp_cod']]['acerto'] . "</td>
	</tr>
	<tr>
		<td>Dezenas</td><td>". implode(', ', $bilhetes) . "</td>
	</tr>";
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