<meta charset="utf-8">
<?php

if($_GET['imprimir'])
	include('../../class/conexao.php');
else
	include('../class/conexao.php');

$rifa_codigo = intval($_GET['rifa']);
$filtrar_pontos = intval($_GET['pontos']);

$sql_venda = "";
if($_GET['venda'] > 0)
	$sql_venda = " AND c.comp_cod = ".intval($_GET['venda'])." ";

function remover_do_array($codigo_do_bilhete, $todos_bilhetes){

	foreach($todos_bilhetes as $key=>$value){
		if($value == $codigo_do_bilhete){
			unset($todos_bilhetes[$key]);
			return $todos_bilhetes;
		}
	}

}

$sorteio = db_select($mysqli, "SELECT * from rifa_sorteio where rifa = '$rifa_codigo'", 1);
$sorteios_arr = db_select($mysqli, "SELECT * from sorteio_dezenas where rifa = '$rifa_codigo' order by sorteio ASC");
$rifa = db_select($mysqli, "SELECT * from tbl_rifas where rifa_cod = '$rifa_codigo'", 1);

if($sorteio && count($sorteios_arr) > 0){

	$todos_bilhetes = db_select($mysqli, "SELECT * FROM tbl_bilhetes where bil_rifa = '$rifa_codigo' and bil_situacao = 'V' order by bil_compra asc");
	$bilhetes_por_compra = array();
	$todos_bilhetes_sorteados = array();

	foreach($todos_bilhetes as $bil){
		$bilhetes_por_compra[$bil['bil_compra']][] = str_pad($bil['bil_numero'], 2, '0', STR_PAD_LEFT);
	}

	$dono_da_rifa = db_select($mysqli, "select rifa_dono from tbl_rifas WHERE rifa_cod = '$rifa_codigo'", 1);

	$sql_code = "SELECT c.*, dono_rifa.usu_celular as dono_celular, comprador.usu_nome, comprador.usu_celular, comprador.usu_regiao, concat(u.usu_nome, ' (', u.usu_celular, ')') as revendedor, u.usu_celular as rev_tel
		from tbl_usuario comprador, tbl_usuario dono_rifa, tbl_compra c left join tbl_usuario u ON u.usu_cod = c.comp_revendedor
		where c.comp_cod in (select bil_compra from tbl_bilhetes where bil_rifa = '$rifa_codigo' and bil_situacao = 'V' group by bil_compra)
		and (c.comp_situacao = 4 OR c.comp_situacao = 3)
		and comprador.usu_cod = c.comp_cliente
		and dono_rifa.usu_cod = '{$dono_da_rifa['rifa_dono']}' $sql_venda  ORDER BY TRIM(comprador.usu_nome) ASC";
	$todas_compras = db_select($mysqli, $sql_code);

	$todos_sorteios = db_select($mysqli, "SELECT * FROM rifa_sorteio rs left join sorteio_dezenas sd on sd.sorteio = rs.codigo where rs.rifa = '$rifa_codigo' ORDER BY sd.sorteio ASC");

	$bilhetes_por_sorteio = array();
	foreach($todos_sorteios as $ts){
		$bilhetes_por_sorteio[$ts['sorteio']][] = str_pad($ts['dezena'], 2, '0', STR_PAD_LEFT);
		$todos_bilhetes_sorteados[] = str_pad($ts['dezena'], 2, '0', STR_PAD_LEFT);
	}

	$vendas_por_pontuacao = array();
	$temp = $todos_bilhetes_sorteados;

	if(!$_GET['alfabetico']){

		$k = 0;
		$todas_compras_finais = array();

		foreach($todas_compras as $compra){

			$pontos = 0;

			$cliente = $compra['usu_nome'];

			$nrevendedor = "Venda Direta";
			if($compra['revendedor'])
				$nrevendedor = $compra['revendedor'];
			
			$linha[$k] = "<tr>
			<td>{k}</td>
			<td>{$compra['comp_cod']}</td>
			<td>{$compra['comp_serie']}</td>
			<td>{$rifa['rifa_titulo']}</td>
			<td>$nrevendedor</td>
			<td>{$compra['usu_regiao']}</td>
			<td align='right'>$cliente</td>
			";
						

			$linha[$k] .= "<td>";

			$todos_bilhetes_sorteados = $temp;
			foreach($bilhetes_por_compra[$compra['comp_cod']] as $bil){
				if(in_array($bil, $todos_bilhetes_sorteados)){
					$todos_bilhetes_sorteados = remover_do_array($bil, $todos_bilhetes_sorteados);
					$linha[$k] .= "<b style=\"color:green;\">[$bil]</b> ";
					$pontos++;
				}else
					$linha[$k] .= "<b style=\"color:red;\">[$bil]</b> ";
			}
			
			$linha[$k] .= "</td>";
			$linha[$k] .= "<td>".date('d/m/Y H:i', strtotime($compra['comp_data']))."</td>";

			$linha[$k] .= "<td>";

			if(!isset($vendas_por_pontuacao[$pontos]))
				$vendas_por_pontuacao[$pontos] = 1;
			else
				$vendas_por_pontuacao[$pontos]++;

			$linha[$k] .= $pontos;

			$linha[$k] .= " pontos </td></tr>";


			$todas_compras_finais[$pontos][] = $k;

			$k++;
		}

		// ordenacao por pontuacao
		krsort($todas_compras_finais);

	}
	

}else
	die("<h1>Esta rifa não possui nenhum sorteio!</h1>");

?>
<style>

table {
    border-collapse: collapse;
    font-family: Arial;
    font-size:14px;
}

</style>

<div class="col-lg-12 text-left">

	<div class="col-lg-12 text-left">

	<h3>Relatório: <?= $rifa['rifa_titulo']; ?> <?= $filtrar_pontos; ?></h3>
	<h5>Total de Apostas: <?= count($todas_compras); ?></h5>

</div>

	<div class="table-responsive">

		<table cellpadding="5" border="1" width="100%" class="table table-bordered">
			<thead>
				<tr>
					<td>#</td>
					<td>Aposta</td>
					<td>M.Federal</td>
					<td>Título</td>
					<td>Vendedor</td>
					<td>Bairro/Região</td>
					<td align="right">Cliente</td>
					<td>Bilhetes (<span style="color:green;">ACERTOS</span>/<span style="color:red;">PENDENTES</span>)</td>
					<td>Data da Aposta</td>
					<td>Pontuação</td>
				</tr>
			</thead>
			<tbody>
				<?php 

				if(!$_GET['alfabetico']){
					$linhas = 0;
					$l = 0;
					foreach($todas_compras_finais as $pontos=>$chave){
						foreach($chave as $ch){
							$linhas++;
							if($filtrar_pontos >= 0){
								if($filtrar_pontos == $pontos) echo $linha[$ch];
							}else
								echo str_replace('{k}', $l++, $linha[$ch]);
						}
					}
				}else{

					$l = 0;
					foreach($todas_compras as $compra){

						$pontos = 0;

						$cliente = $compra['usu_nome'];

						$nrevendedor = "Venda Direta";
						if($compra['revendedor'])
							$nrevendedor = $compra['revendedor'];
						
						$linha = "<tr>
						<td>".($l++)."</td>
						<td>{$compra['comp_cod']}</td>
						<td>{$compra['comp_serie']}</td>
						<td>{$rifa['rifa_titulo']}</td>
						<td>$nrevendedor</td>
						<td>{$compra['usu_regiao']}</td>
						<td align='right'>$cliente</td>
						";
									

						$linha .= "<td>";

						$todos_bilhetes_sorteados = $temp;
						foreach($bilhetes_por_compra[$compra['comp_cod']] as $bil){
							if(in_array($bil, $todos_bilhetes_sorteados)){
								$todos_bilhetes_sorteados = remover_do_array($bil, $todos_bilhetes_sorteados);
								$linha .= "<b style=\"color:green;\">[$bil]</b> ";
								$pontos++;
							}else
								$linha .= "<b style=\"color:red;\">[$bil]</b> ";
						}
						
						$linha .= "</td>";
						$linha .= "<td>".date('d/m/Y H:i', strtotime($compra['comp_data']))."</td>";

						$linha .= "<td>";

						if(!isset($vendas_por_pontuacao[$pontos]))
							$vendas_por_pontuacao[$pontos] = 1;
						else
							$vendas_por_pontuacao[$pontos]++;

						$linha .= $pontos;

						$linha .= " pontos </td></tr>";

						echo $linha;

					}
				 
				} ?>
			</tbody>
		</table>
	</div>

</div>


<?php

$sorteios = db_select($mysqli, "SELECT b.valor as base, e.valor as extracao, rs.codigo, faixa, data from rifa_sorteio rs, sorteio_texto as b, sorteio_texto as e 
	where rs.rifa = '$rifa_codigo'
	and b.codigo = rs.base
	and e.codigo = rs.extracao
	order by rs.data DESC");
$bilhetes_sorteados = db_select($mysqli, "SELECT * FROM sorteio_dezenas where rifa = '$rifa_codigo'");
$bilhetes_por_sorteio = array();

foreach($bilhetes_sorteados as $bs){
	$bilhetes_por_sorteio[$bs['sorteio']][] = $bs;
}

krsort($vendas_por_pontuacao);
?>


		<h3>Sorteios</h3>
        <table cellpadding="5" border="1" width="100%" class="table table-bordered table-hover">
			<thead>
				<tr>
					<td>Data</td>
					<td>Base</td>
					<td>Extração</td>
					<td>Faixa</td>
					<td>Bilhetes</td>
				</tr>
			</thead>
			<tbody>
				<?php foreach($sorteios as $s){ ?>
				<tr>
					<td><?= implode('/', array_reverse(explode('-', $s['data']))); ?></td>
					<td><?= $s['base']; ?></td>
					<td><?= $s['extracao']; ?></td>
					<td><?= $s['faixa']; ?></td>
					<td><?php foreach($bilhetes_por_sorteio[$s['codigo']] as $bil){ echo "[".str_pad($bil['dezena'], 2, '0', STR_PAD_LEFT)."] "; } ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>

      	<h3>Resumo <?php if($filtrar_pontos < 0 && !$_GET['venda']) echo "($linhas)"; ?></h3>
      	<table cellpadding="5" border="1" width="100%" class="table table-bordered table-hover">

			<tbody>
				<?php 
				if($filtrar_pontos >= 0){

					foreach($vendas_por_pontuacao as $pontuacao=>$qtd){ if($pontuacao == $filtrar_pontos){ ?>
				<tr>
					<td><?= "$qtd apostas(s) com $pontuacao pontos"; ?></td>
				</tr>
				<?php } } 

				}else{
				foreach($vendas_por_pontuacao as $pontuacao=>$qtd){ ?>
				<tr>
					<td><?= "$qtd apostas(s) com $pontuacao pontos"; ?></td>
				</tr>
				<?php }} ?>
			</tbody>
		</table>

