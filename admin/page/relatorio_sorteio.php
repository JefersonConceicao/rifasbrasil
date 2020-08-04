<?php

function remover_do_array($codigo_do_bilhete, $todos_bilhetes){

	foreach($todos_bilhetes as $key=>$value){
		if(intval($value) == intval($codigo_do_bilhete)){
			unset($todos_bilhetes[$key]);
			return $todos_bilhetes;
		}
	}

}

if($_POST['sms']){

	include('../../class/conexao.php');
	include('../../class/function_enviarSMS.php');

	$rifa_codigo    = intval($_POST['rifa']);
	$min            = intval($_POST['min']);
	$max            = intval($_POST['max']);
	$nativo_celular = $_POST['sms'];
	$tipo           = $_POST['minmax'];

	$sorteio = db_select($mysqli, "SELECT * from rifa_sorteio where rifa = '$rifa_codigo'", 1);
	$sorteios_arr = db_select($mysqli, "SELECT * from sorteio_dezenas where rifa = '$rifa_codigo' order by sorteio ASC");
	$rifa = db_select($mysqli, "SELECT * from tbl_rifas where rifa_cod = '$rifa_codigo'", 1);

	$quantidade_maxima_pontos = $rifa['dezena_bolao'];

	$vendas_por_pontuacao = array();

	$todos_bilhetes = db_select($mysqli, "SELECT * FROM tbl_bilhetes where bil_rifa = '$rifa_codigo' and bil_situacao = 'V' order by bil_compra asc");
	$bilhetes_por_compra = array();
	$todos_bilhetes_sorteados = array();

	foreach($todos_bilhetes as $bil){
		$bilhetes_por_compra[$bil['bil_compra']][] = str_pad($bil['bil_numero'], 2, '0', STR_PAD_LEFT);
	}

	$sql_code = "SELECT c.*, dono_rifa.usu_celular as dono_celular, comprador.usu_nome, comprador.usu_celular, comprador.usu_regiao, concat(u.usu_nome, ' (', u.usu_celular, ')') as revendedor, u.usu_celular as rev_tel
		from tbl_usuario comprador, tbl_usuario dono_rifa, tbl_compra c left join tbl_usuario u ON u.usu_cod = c.comp_revendedor
		where c.comp_cod in (select bil_compra from tbl_bilhetes where bil_rifa = '$rifa_codigo' and bil_situacao = 'V' group by bil_compra)
		and (c.comp_situacao = 4 OR c.comp_situacao = 3)
		and comprador.usu_cod = c.comp_cliente
		and dono_rifa.usu_cod = (select rifa_dono from tbl_rifas WHERE rifa_cod = '$rifa_codigo')";
	$todas_compras = db_select($mysqli, $sql_code);

	$todos_sorteios = db_select($mysqli, "SELECT * FROM rifa_sorteio rs left join sorteio_dezenas sd on sd.sorteio = rs.codigo where rs.rifa = '$rifa_codigo' ORDER BY sd.sorteio ASC");

	$bilhetes_por_sorteio = array();
	foreach($todos_sorteios as $ts){
		$bilhetes_por_sorteio[$ts['sorteio']][] = str_pad($ts['dezena'], 2, '0', STR_PAD_LEFT);
		$todos_bilhetes_sorteados[] = str_pad($ts['dezena'], 2, '0', STR_PAD_LEFT);
	}

	$vendas_por_pontuacao = array();

	$numeros_menos = array();
	$numeros_mais  = array();
	$numeros_zero  = array();
	$numeros_maximo= array();

	$resultados = array();

	$temp = $bilhetes_por_compra;

	foreach($todas_compras as $compra){

		$pontos = 0;
		foreach($bilhetes_por_sorteio as $sorteio=>$void){
			$bilhetes_por_compra = $temp;
			foreach($void as $bilhete_do_sorteio){
				if(in_array($bilhete_do_sorteio, $bilhetes_por_compra[$compra['comp_cod']])){
					$bilhetes_por_compra[$compra['comp_cod']] = remover_do_array($bilhete_do_sorteio, $bilhetes_por_compra[$compra['comp_cod']]);
					$pontos++;
				}
			}
		}
		
		if($tipo == 'min'){


			if($pontos == 0 && $nativo_celular == 'nativo'){
				$sms = "{$compra['usu_nome']}: Sua aposta foi uma das que fez menos pontos, se continuar sem pontuar podera ser o azarao da rodada!";
				$resultados[] = enviarSMS($compra['usu_celular'], $sms, true);
			}elseif($pontos == 0){
				$numeros_zero[] = "+55" . $compra['usu_celular'];
			}else if($pontos == $min && $nativo_celular == 'nativo'){
				// minimo e nativo
				$sms = "{$compra['usu_nome']}: Ola, sua aposta {$compra['comp_cod']} foi uma das que fez menos pontos, ligue para {$compra['dono_celular']}.";
				//echo $sms;
				$resultados[] = enviarSMS($compra['usu_celular'], $sms, true);
			}elseif($pontos == $min){
				// minimo e celular
				$numeros_menos[] = "+55" . $compra['usu_celular'];
			}
		}else{

			if($pontos == $quantidade_maxima_pontos && $nativo_celular == 'nativo'){
				$sms = "{$compra['usu_nome']}: Parabens! Sua aposta foi uma das que acertou as $quantidade_maxima_pontos dezenas. Ligue para {$compra['dono_celular']}";
				$resultados[] = enviarSMS($compra['usu_celular'], $sms, true);
			}else if($pontos == $quantidade_maxima_pontos){
				
				$numeros_maximo[] = "+55" . $compra['usu_celular'];
			}elseif($pontos == $max && $nativo_celular == 'nativo'){
				// maximo e nativo
				$sms = "{$compra['usu_nome']}: Parabens! Sua aposta numero {$compra['comp_cod']} foi uma das que acertou as $max dezenas. Ligue para {$compra['dono_celular']}.";
				//echo $sms;
				$resultados[] = enviarSMS($compra['usu_celular'], $sms, true);
			}elseif($pontos == $max){
				// maximo e celular
				$numeros_mais[] = "+55" . $compra['usu_celular'];

			}
		}


	}

	if(count($numeros_zero) > 0)
		die(json_encode(array('numero'=>implode(',', $numeros_zero), 'mensagem'=>"Sua aposta foi uma das que fez menos pontos, se continuar sem pontuar podera ser o azarao da rodada!")));

	if(count($numeros_maximo) > 0)
		die(json_encode(array('numero'=>implode(',', $numeros_maximo), 'mensagem'=>"Parabens! Sua aposta foi uma das que acertou as $quantidade_maxima_pontos dezenas. Ligue para {$compra['dono_celular']}")));

	if(count($numeros_mais) > 0)
		die(json_encode(array('numero'=>implode(',', $numeros_mais), 'mensagem'=>"Sua aposta foi uma das que fez maior pontuacao da rodada. Você fez $max pontos. Parabens.")));

	if(count($numeros_menos) > 0)
		die(json_encode(array('numero'=>implode(',', $numeros_menos), 'mensagem'=>"Sua aposta foi uma das que fez menos pontos, se continuar sem pontuar podera ser o azarao da rodada!")));

	die(json_encode(array('resultado'=>'true', 'resultados'=>$resultados)));
}

include('../class/conexao.php');

$rifa_codigo = intval($_GET['rifa']);

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

	$sql_code = "SELECT c.*, comprador.usu_nome, comprador.usu_celular, comprador.usu_regiao, concat(u.usu_nome, ' (', u.usu_celular, ')') as revendedor
		from tbl_usuario comprador, tbl_compra c left join tbl_usuario u ON u.usu_cod = c.comp_revendedor
		where c.comp_cod in (select bil_compra from tbl_bilhetes where bil_rifa = '$rifa_codigo' and bil_situacao = 'V' group by bil_compra)
		and (c.comp_situacao = 4 OR c.comp_situacao = 3)
		and comprador.usu_cod = c.comp_cliente";
	$todas_compras = db_select($mysqli, $sql_code);

	$todos_sorteios = db_select($mysqli, "SELECT * FROM rifa_sorteio rs left join sorteio_dezenas sd on sd.sorteio = rs.codigo where rs.rifa = '$rifa_codigo' ORDER BY sd.sorteio ASC");

	$bilhetes_por_sorteio = array();
	foreach($todos_sorteios as $ts){
		$bilhetes_por_sorteio[$ts['sorteio']][] = str_pad($ts['dezena'], 2, '0', STR_PAD_LEFT);
		$todos_bilhetes_sorteados[] = str_pad($ts['dezena'], 2, '0', STR_PAD_LEFT);
	}

	$temp = $todos_bilhetes_sorteados;

}else
	die("<script>alert('Esta rifa ainda não possui nenhum sorteio.'); location.href='index.php?p=rifa';</script>");

?>



<div class="col-lg-12 text-left">

	<h3>Relatório do Bolão (<?= count($todas_compras); ?>)</h3>
	<div class="text-right">
		<p>Clique <a href="javascript: void(0);" onclick="$('#modal').modal('show');">AQUI</a> para ver as DEZENAS JÁ SORTEADAS e as datas das EXTRAÇÕES.</p>
	</div>
	<hr>

</div>

<div class="col-lg-12 text-left">

	<div class="form-group text-right">
		<button onclick="window.open('page/gerar_relatorio_dezena_bolao.php?rifa=<?= $rifa_codigo; ?>&pontos=-1', '_blank');" class="btn btn-warning">PDF Pontuação</button>
		<button onclick="window.open('page/gerar_relatorio_dezena_bolao.php?rifa=<?= $rifa_codigo; ?>&pontos=-1&alfabetico=1', '_blank');" class="btn btn-warning">PDF Cliente</button>
	</div>

	<div class="table-responsive">

		<table class="table table-bordered">
			<thead>
				<tr>
					<td></td>
					<td>Venda</td>
					<td>Rifa</td>
					<td>Cliente</td>
					<td>Bairro/Região</td>
					<td>Vendedor</td>
					<td>Bilhetes</td>
					<td>Data da Aposta</td>
					<td>Pontuação</td>
				</tr>
			</thead>
			<tbody>
				<?php 

				$vendas_por_pontuacao = array();

				foreach($todas_compras as $compra){ ?>
				<tr>
					<td><input type="checkbox"></td>
					<td><?= $compra['comp_cod']; ?></td>
					<td><?= $rifa['rifa_titulo']; ?></td>
					<td><?= "{$compra['usu_nome']} ({$compra['usu_celular']})"; ?></td>
					<td><?= $compra['usu_regiao']; ?></td>
					<td><?php echo ($compra['revendedor'])? $compra['revendedor']:"Venda Direta"; ?></td>
					<td><?php
						$pontos = 0;
						$todos_bilhetes_sorteados = $temp;
						foreach($bilhetes_por_compra[$compra['comp_cod']] as $bil){

							if(in_array($bil, $todos_bilhetes_sorteados)){
								$pontos++;
								$todos_bilhetes_sorteados = remover_do_array($bil, $todos_bilhetes_sorteados);
								echo "<b style=\"color:green;\">[$bil]</b> ";
							}
							else
								echo "<b style=\"color:red;\">[$bil]</b> ";
						}
					 ?></td>
					<td><?= date('d/m/Y H:i', strtotime($compra['comp_data'])); ?></td>
					<td>
						<?php 
						if(!isset($vendas_por_pontuacao[$pontos]))
							$vendas_por_pontuacao[$pontos] = 1;
						else
							$vendas_por_pontuacao[$pontos]++;

						echo "$pontos pontos";
						?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

</div>

<?php

$sorteios = db_select($mysqli, "SELECT b.valor as base, e.valor as extracao, rs.codigo, faixa, data 
	from rifa_sorteio rs, sorteio_texto as b, sorteio_texto as e 
	where rs.rifa = '$rifa_codigo'
	and b.codigo = rs.base
	and e.codigo = rs.extracao
	order by rs.codigo DESC");
$bilhetes_sorteados = db_select($mysqli, "SELECT * FROM sorteio_dezenas where rifa = '$rifa_codigo'");
$bilhetes_por_sorteio = array();

foreach($bilhetes_sorteados as $bs){
	$bilhetes_por_sorteio[$bs['sorteio']][] = $bs;
}

if(count($vendas_por_pontuacao) > 0){
	$min = min(array_keys($vendas_por_pontuacao));
	$max = max(array_keys($vendas_por_pontuacao));
	ksort($vendas_por_pontuacao);
}

?>

<script>
	
	function enviar_sms(tipo, min_max){

		$.post('page/relatorio_sorteio.php', {sms:tipo, min:<?= $min; ?>, minmax:min_max, max:<?= $max; ?>, rifa:<?= $rifa_codigo; ?>})
		.done(function(r){

			console.log(r);
			r = JSON.parse(r);

			if(r.resultado)
				return alert("SMS enviado!");

			if(r.numero){
				location.href="sms:" + r.numero + "?body=" + r.mensagem;
				//alert("sms:" + r.numero + "?body=" + r.mensagem);
			}	

		})
		.fail(function(r){
			console.log(r);
		});

	}

</script>

<!-- Modal -->
<div id="modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Dezenas já Sorteadas</h4>
      </div>
      <div class="modal-body">
      	<p><b>Resumo</b></p>
      	<table class="table table-bordered table-hover">

			<tbody>
				<?php foreach($vendas_por_pontuacao as $pontuacao=>$qtd){ ?>
				<tr>
					<td>
						
						<?= "$qtd aposta(s) com $pontuacao pontos. <button onclick=\"window.open('page/gerar_relatorio_dezena_bolao.php?rifa=".$rifa_codigo."&pontos=$pontuacao', '_blank');\" class=\"btn btn-xs btn-default\">PDF</button>"; ?>
						<?php if($pontuacao == $min || $pontuacao == $max){ 

							$texto =  'min';
							if($pontuacao == $min) 
								$texto = 'min';
							else 
								$texto  = 'max';

							?>
						<button type="button" onclick="javascript: enviar_sms('celular', '<?= $texto; ?>');" name="sms" value="celular" class="btn btn-xs btn-primary">SMS Celular</button>
						<button type="button" onclick="javascript: enviar_sms('nativo', '<?= $texto; ?>');" name="sms" value="nativo" class="btn btn-success btn-xs">SMS Servidor</button>
						<?php  } ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<p><b>Todas os Sorteios</b></p>
        <table class="table table-bordered table-hover">
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
					<td><?php if(is_array($bilhetes_por_sorteio[$s['codigo']])) foreach($bilhetes_por_sorteio[$s['codigo']] as $bil){ echo "[".str_pad($bil['dezena'], 2, '0', STR_PAD_LEFT)."] "; } ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div>

  </div>
</div>
