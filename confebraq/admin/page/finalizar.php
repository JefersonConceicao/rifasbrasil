<?php

$codigo = intval($_GET['codigo']);

include("../class/conexao.php");

$config = DBSelect("SELECT par_ccaridade/100 as caridade, par_crb/100 as rifasbrasil FROM tbl_parametro where par_codigo = '1'", $mysqli);
$qr_finalizar = $mysqli->query("UPDATE tbl_rifas SET rifa_finalizar = 1 WHERE rifa_cod = '$codigo'");

function limparNumero($n){

	$final = "";
	for($i = 0; $i < strlen($n); $i++){
		if(is_numeric($n[$i]))
			$final .= $n[$i];
	}

	return $final;
}


if(isset($_POST[enviar])){

	// Captura dos dados
	$rifa_codigo = intval($_GET['codigo']);
	$instituicao = intval($_POST[instituicao]);
	if(is_array($_POST[bilhete])){
		foreach($_POST[bilhete] as $cod=>$val)
			$bilhete[$cod] = limparNumero($val);
	}else
		$bilhete = limparNumero($_POST[bilhete]);

	if(strlen($_POST['inst']) > 0){
		
		$instituicao = $mysqli->escape_string($_POST[inst]);
		$result = $mysqli->query("INSERT INTO instituicao(instituicao) values('$instituicao')") or die($mysqli->error);
		$instituicao = $mysqli->insert_id;
		
	}

	include("../class/function_getVencedor.php");

	$erro = Array();



	if($_POST[enviar] > 1){
		foreach($_POST[bilhete] as $ch=>$va){
			if(strlen($va) == 0)
				$erro[] = "Preencha o bilhete vencedor do ".$ch."º lugar.";

			$va = $mysqli->escape_string($va);



			$vencedor = getVencedor($rifa_codigo, $va, $mysqli);

			if($vencedor < 0)
				$vencedor = 0;

			$bil_SQL[] = "UPDATE tbl_premio SET pre_bilhete = '$va', pre_vencedor = '$vencedor' 
			where pre_rifa = '$rifa_codigo' AND pre_ranking = '$ch'";
		}
	}else{

		$bilhete = $mysqli->escape_string($_POST[bilhete]);
		$vencedor = getVencedor($rifa_codigo, $bilhete, $mysqli);


		if(strlen($bilhete) < 5)
			$erro[] = "Preencha os 5 dígitos do bilhete vencedor.";
		else
			$bil_SQL[] = "update tbl_rifas set rifa_bilhetepremiado = '$bilhete',
			rifa_vencedor = '$vencedor' where rifa_cod = '$rifa_codigo'";
	}

	
	if(($rifa_codigo) == 0)
		$erro[] = "Rifa inválida.";

	
	// Execução das ações
	if(count($erro) == 0){


		foreach($bil_SQL as $code){
			$mysqli->query($code) or die($mysqli->error);
		}
		$suc = $mysqli->query("
			UPDATE tbl_rifas 
			SET rifa_instituicao = '$instituicao',
			rifa_finalizar = '1'
			WHERE rifa_cod = '$codigo' ") or die($mysqli->error);

		if($suc){
			echo "<script>alert('Rifa finalizada com sucesso.'); location.href='index.php?p=pagar&rifa=".$rifa_codigo."';</script>";
			exit();
		}

	}

}

$sql_premio = DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$codigo' ORDER BY pre_ranking", $mysqli);
$premio = $sql_premio->fetch_assoc();
$num_premio = $sql_premio->num_rows;

$sql_valor = "SELECT rifa_valorbilhete as v, rifa_premios, rifa_instituicao FROM tbl_rifas WHERE rifa_cod = '$codigo'";
$sql_exe = $mysqli->query($sql_valor) or die($mysqli->error);
$rifa = $sql_exe->fetch_assoc();

// A consulta abaixo verifica quais rifas já foram vendidas
$sqlBil = "SELECT count(*) as t FROM tbl_bilhetes WHERE bil_situacao = 'V' AND bil_rifa = '$codigo'";
$queryBil = $mysqli->query($sqlBil) or die($mysqli->error);
$bil = $queryBil->fetch_assoc();

//consulta as informações do usuário que vai pagar
$usu_code = "SELECT * FROM tbl_usuario WHERE usu_cod = '$_SESSION[usuario]' ";
$usu_query = $mysqli->query($usu_code) or die($mysqli->error);
$usu = $usu_query->fetch_assoc();

$bil_vendidos = $bil[t];
$valor_bilhete = $rifa['v'];
$subtotal = $valor_bilhete * $bil_vendidos;
$total = $subtotal*($config[rifasbrasil] + $config[caridade]);
$tarifar = $subtotal*$config[rifasbrasil];
$tarifaps = ($subtotal* ($config[rifasbrasil] + $config[caridade]) *0.05);
$tarifac = $subtotal*$config[caridade];
$lucro = $subtotal*(1-($config[rifasbrasil] + $config[caridade]));

$sql_comissaototal = "
SELECT  count(*) as Vendas, count(*) * ((SELECT comissao 
	FROM tbl_revendedor 
	WHERE usu_cod = (select comp_revendedor from tbl_compra where comp_cod = bil_compra) 
	AND rifa_cod = '$codigo')/100) as ComissaoTotal, 
(select comp_revendedor from tbl_compra where comp_cod = bil_compra) as rev
from tbl_bilhetes 
where bil_rifa = '$codigo' 
and bil_compra in (select comp_cod from tbl_compra where comp_revendedor is not null) group by rev";
$qr_comissao = $mysqli->query($sql_comissaototal) or die($mysqli->error);
$fe_comissao = $qr_comissao->fetch_assoc();

$comissaoTotal = 0;
$nVendasRevenda = 0;
do{
	$comissaoUsuario[$fe_comissao[rev]] = $fe_comissao[ComissaoTotal];
	$comissaoTotal += $fe_comissao[ComissaoTotal];
	$nVendasRevenda += $fe_comissao[Vendas];
}while($fe_comissao = $qr_comissao->fetch_assoc());


$lucro = $lucro - $comissaoTotal - $tarifaps;

?>
<style>
	.esconder{display:none;}
</style>
<script>
function mostrarInst(valor){
	if(valor == -1){
		$( "#instituicao2" ).removeClass( "esconder" ).prop('required',true);
	}else{
		$( "#instituicao2" ).addClass( "esconder" ).prop('required',false);
	}
}
</script>
<div class="col-lg-12 text-left">
	<h3>Finalizar Rifa</h3>
	<p>Sua rifa só será finalizada quando o pagamento da comissão de vendas do RifasBrasil for confirmado. Doaremos uma parte para uma instituição de caridade de sua escolha.</p>
	<hr>
	<?php if(count($erro) > 0){ ?>
	<div class="col-lg-12">
		<?php foreach($erro as $msg) echo "<div class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
	</div>
	<?php } ?>
</div>
<div class="col-lg-6 text-left">
	<div class="col-lg-12">
	<p>Sua conta</p>
	<table class="table table-bordered">
		<tr>
			<td style="font-weight:bold">Vendas diretas</td>
			<td><?php echo $bil_vendidos-$nVendasRevenda; ?></td>
		</tr>
		<tr>
			<td style="font-weight:bold">Vendas por Revenda</td>
			<td><?php echo $nVendasRevenda; ?></td>
		</tr>
		<tr>
			<td style="font-weight:bold">Valor do bilhete</td>
			<td>R$ <?php echo number_format($valor_bilhete, 2, ',', '.'); ?></td>
		</tr>
		
		<tr>
			<td style="font-weight:bold">Subtotal</td>
			<td>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
		</tr>
		<tr>
			<td style="font-weight:bold">Comissão dos Vendedores</td>
			<td>R$ <?php echo number_format($comissaoTotal, 2, ',', '.'); ?></td>
		</tr>
		<tr>
			<td style="font-weight:bold">Tarifa RifasBrasil (7%)</td>
			<td>R$ <?php echo number_format($tarifar, 2, ',', '.'); ?></td>
		</tr>
		<tr>
			<td style="font-weight:bold">Tarifa Pagseguro</td>
			<td>R$ <?php echo number_format($tarifaps, 2, ',', '.'); ?></td>
		</tr>
		<tr>
			<td style="font-weight:bold">Doação p/ Caridade (3%)</td>
			<td>R$ <?php echo number_format($tarifac, 2, ',', '.'); ?></td>
		</tr>

	<tr class="bg-danger">
			<td style="font-weight:bold">Total de Tarifas</td>
			<td>R$ <?php echo number_format($subtotal*0.1 + $tarifaps, 2, ',', '.'); ?></td>
		</tr>
		<tr class="bg-success">
			<td style="font-weight:bold">Seu Lucro</td>
			<td>R$ <?php echo number_format($lucro, 2, ',', '.'); ?></td>
		</tr>
	</table>
	</div>
	
</div>
<div class="col-lg-6">

	<form id="form1" action="" method="post">
		<div class="form-group col-lg-12">
			<label for="instituicao">Selecione a instituição de caridade</label>
			<p>Caso tenha alguma preferência de instituição, selecione abaixo antes de clicar em Pagar</p>
			<select onchange="javascrit: mostrarInst(this.value);" required name="instituicao" id="instituicao" class="form-control">
				<option value="">Selecionar</option>
				<?php

				$sql_ins = "SELECT * FROM instituicao ORDER BY instituicao ASC";
				$ins_exe = $mysqli->query($sql_ins) or die($mysqli->error);
				$ins = $ins_exe->fetch_assoc();

				do{ ?>
				<option value="<?php echo $ins[codigo]; ?>"><?php echo "$ins[instituicao] ($ins[cidade]/$ins[estado])"; ?></option>
				<?php }while($ins = $ins_exe->fetch_assoc()); ?>
				<option value="-1">Outras</option>

			</select>


		</div>
		<div id="instituicao2" class="esconder col-lg-12 form-group form-inline">
			<label for="">Instituição</label>
			<input required name="inst" id="inst" type="text" class="form-control">
		</div>
		<div class="col-lg-12 form-group"><label for="">Bilhete(s) Sorteado(s)</label><p>Preencha os 5 dígitos de todos os bilhetes sorteados.</p></div>
		<?php if($rifa[rifa_premios] > 1) do{ ?>
		<div class="col-lg-12 form-group">
			<p><?php echo $premio[pre_ranking]; ?>º lugar - Prêmio: <?php echo $premio[pre_descricao]; ?> </p>
			<input placeholder="Bilhete vencedor"  required name="bilhete[<?php echo $premio[pre_ranking]; ?>]"  type="text" class="form-control">
		</div>
		<?php }while($premio = $sql_premio->fetch_assoc()); else{ ?>
		<div class="col-lg-12 form-group">
			<input placeholder="Bilhete vencedor"  required name="bilhete" id="bil" type="text" class="form-control">
		</div>

		<?php } ?>
		<div class="form-group col-lg-12 text-right">
			<input type="hidden" name="enviar" value="<?php echo $rifa[rifa_premios]; ?>">
			<button 
			onclick="javascript: document.getElementById('form1').submit();"
			name="ins" id="prosseguir" type="button"  value="1" class="btn btn-success">Prosseguir</button>
			
		</div>
	</form>

</div>
</div>

<script>
function showHint(rifa, bilhete) {
    if (rifa.length == 0 || bilhete.length == 0) { 
        document.getElementById("txtHint").innerHTML = '<div style="padding:10px;" class="col-lg-12 bg-warning">Preencha o número do Bilhete Sorteado pela Loteria Federal.</div>';
        document.getElementById("prosseguir").disabled = true;
        return false;
    }
    else if(bilhete.length < 5){
    	document.getElementById("txtHint").innerHTML = '<div style="padding:10px;" class="col-lg-12 bg-warning">O número do Bilhete Sorteado deve possuir 5 algorismos.</div>';
        document.getElementById("prosseguir").disabled = true;
        return false;
    } else {
        var xmlhttp = new XMLHttpRequest();

        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            	var msg = "";

            	if(xmlhttp.responseText == 1){
            		msg = "Rifa inválida.";
            	}
            	if(xmlhttp.responseText == 2){
            		msg = "Ninguém foi premiado com base nesse número.";
            	}

            	if(xmlhttp.responseText == 3){
            		msg = "Esta rifa possui um ganhador. Ao confirmar o pagamento das tarifas, avisaremos automaticamente você, o ganhador e todos os participantes por SMS.";
            	}
            	
            	  
                
                xmlhttp.abort();
                document.getElementById("txtHint").innerHTML = '<div style="padding:10px;" class="col-lg-12 bg-warning">'+msg+'</div>';
                document.getElementById("prosseguir").disabled = false;

                return true;

            }
        };

        xmlhttp.open("GET", "page/get_vencedor.php?rifa=" + rifa + "&bilhete=" + bilhete, true);
        xmlhttp.send();

        return true;

        


    }
}


</script>