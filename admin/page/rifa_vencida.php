<?php

error_reporting(E_ALL);

include("../class/conexao.php");
$conexao = new Conexao();

$rifa_cod = intval($_GET['rifa']);
$serie = $conexao->escape($_GET['serie']);

$is_admin = ($_SESSION['admin']) ? 1:0;
$rifa = $conexao
	->select('rifa_maxbilhetes, rifa_dtsorteio')
	->from('tbl_rifas')
	->where("rifa_cod = '$rifa_cod' AND (1={$is_admin} OR rifa_dono = '{$_SESSION['usuario']}' OR rifa_cod IN (SELECT rifa_cod FROM tbl_revendedor WHERE usu_cod = '{$_SESSION['usuario']}' and rifa_cod = '{$_SESSION['usuario']}'))")
	->limit(1)
	->executeNGet();

// ja foi vendida?
$venda = $conexao
	->select('c.comp_cod, rev.usu_nome as revendedor, rev.usu_celular as telefone, u.usu_nome as cliente, b.bil_numero')
	->from('tbl_compra c, tbl_bilhetes b, tbl_usuario u, tbl_usuario rev')
	->where("c.comp_cod = b.bil_compra AND c.comp_serie LIKE '%{$serie}%' AND b.bil_rifa = '$rifa_cod' AND u.usu_cod = c.comp_cliente AND rev.usu_cod = c.comp_revendedor")
	->executeNGet();

$titulo = '';
if(!$venda){
	$titulo = "Série não Vendida";
	$conteudo = "";
} else {

	$query_bilhetes = $conexao->select('dezena')->from('sorteio_dezenas')->where("rifa = '$rifa_cod'")->executeNGet();

	$bilhetes_sorteados = array();
	foreach($query_bilhetes as $bs){
		$bilhetes_sorteados[intval($bs['dezena'])] = true;
	}

	$array_vendas = array();
	$vendas_com_dez_acertos = array();
	$venda_agrupada = array();

	foreach($venda as $v){

		$bilhete = intval($v['bil_numero']);

		if(!$array_vendas[$v['comp_cod']])
			$array_vendas[$v['comp_cod']] = array();

		if(!$array_vendas[$v['comp_cod']]['bilhete'])
			$array_vendas[$v['comp_cod']]['bilhete'] = array();

		$array_vendas[$v['comp_cod']]['bilhete'][$bilhete] = true;

		if($bilhetes_sorteados[$bilhete]) {
			if(!$array_vendas[$v['comp_cod']]['acerto'])
				$array_vendas[$v['comp_cod']]['acerto'] = 0;
			$array_vendas[$v['comp_cod']]['acerto']++;
		}

		if($array_vendas[$v['comp_cod']]['acerto'] == 10)
			$vendas_com_dez_acertos[] = $v['comp_cod'];

	}

	$menor_pontuacao = 0;
	$pontuacoes = array();
	foreach($array_vendas as $mp){
		$pontuacoes[$mp['acerto']] = true;
	}
	$menor_pontuacao = min(array_keys($pontuacoes));

	if(count($vendas_com_dez_acertos) > 0){
		$titulo = "Pontuação Finalizada!";
		$conteudo = "<p>Baixe os PDF abaixo:</p><p>";
		$conteudo .= "<a href=\"javascript: window.open('page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=10', '_blank');\">AQUI</a> - Prêmio Sortudo<br>";
		$conteudo .= "<a href=\"javascript: window.open('page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=9', '_blank');\">AQUI</a> - Prêmio Consolação<br>";
		$conteudo .= "<a href=\"javascript: window.open('page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=".$menor_pontuacao."', '_blank');\">AQUI</a> - Prêmio Azarão<br>";
		$conteudo .= "</p>";
	} else {
		$titulo = "Pontuação em Andamento!";
		$conteudo = "<table class=\"table table-bordered\">
			<thead>
				<tr>
					<td>Cliente</td>
					<td>Revendedor</td>
					<td>Série</td>
					<td>Num. Aposta</td>
					<td>Pontuação</td>
					<td>Dezenas</td>
				</tr>
			</thead>
			<tbody>";

		$comp_cod = -1;
		foreach($array_vendas as $comp => $av){	

			$bilhetes = array_keys($av['bilhete']);
			foreach($bilhetes as $k=>$bil){
				// adiciona o padding
				if($bilhetes_sorteados[$bil])
					$bilhetes[$k] = '<span style="font-weight:bold; color:green;">['. substr('00000' . $bil, (strlen($rifa['rifa_maxbilhetes'])-1)*-1) . ']</span>';
				else
					$bilhetes[$k] = '<span style="font-weight:bold; color:red;">['. substr('00000' . $bil, (strlen($rifa['rifa_maxbilhetes'])-1)*-1) . ']</span>';
			}

			$comp_cod = $comp;

			$conteudo .= "
			<tr>
				<td>{$v['cliente']}</td>
				<td>{$v['revendedor']} ({$v['telefone']})</td>
				<td>{$_GET['serie']}</td>
				<td>{$v['comp_cod']}</td>
				<td>" . $av['acerto'] . "</td>
				<td>". implode(', ', $bilhetes) . "</td>
			</tr>";
		}

		$conteudo .= "
			</tbody>
		</table>";
		$conteudo .= "<p>Clique <a href=\"javascript: window.open('page/gerar_relatorio_dezena_bolao.php?rifa=" . $rifa_cod . "&pontos=-1', '_blank');\">AQUI</a> baixe PDF dos Melhores pontuados!</p>";
	}

}

?>
<div class="col-lg-12 form-group">
	<h1 class="page-header"><?= $titulo; ?></h1>
</div>
<div class="col-lg-12 col-md-12">
	<?php echo $conteudo; ?>
</div>
