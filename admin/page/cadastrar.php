<?php
include("../class/conexao.php");
include("../class/function_limparNumero.php");
function nomeImagem($cod_usuario, $arq_nome, $tmp_nome)
{
	return $cod_usuario . "-" . md5("1-" . time() . "-" . $tmp_nome) . "." . substr($arq_nome, -3);
}
if (!isset($_SESSION)) @session_start();
$banco_code = "SELECT count(*) as c from tbl_conta WHERE usuario = '{$_SESSION['usuario']}'";
$banco_query = $mysqli->query($banco_code) or die($mysqli->error);
$bb = $banco_query->fetch_assoc();
$ps_code = "SELECT usu_psemail as i1, usu_pstoken as i2 from tbl_usuario where usu_cod = '{$_SESSION['usuario']}' ";
$ps_query = $mysqli->query($ps_code) or die($mysqli->error);
$ps = $ps_query->fetch_assoc();
if ((strlen($ps['i1']) == 0 || strlen($ps['i2']) == 0) && $bb['c'] == 0) {
	echo "<script>location.href='index.php?p=pagamento';</script>";
} else {
	if (isset($_POST['enviar']) && isset($_SESSION['usuario'])) {

		foreach ($_POST as $ch => $va) {
			if (is_array($_POST[$ch]))
				foreach ($_POST[$ch] as $cha => $val) {
					$_SESSION[$ch][$cha] = $mysqli->escape_string($val);
				} else
				$_SESSION[$ch] = $mysqli->escape_string($va);
		}

		$_SESSION['comissao'] = limparNumero($_SESSION['comissao']);
		$_SESSION['repetir_dezena'] = ($_SESSION['repetir_dezena'] == '1') ? 1 : 0;

		if (substr($_SESSION['nbilhete'], -1) == 'x') {

			$_SESSION['nbilhete'] = substr($_SESSION['nbilhete'], 0, -1);
			$_SESSION['dezenabolao'] = 1;

			if (!$_POST['qtd_dezenas'] || $_POST['qtd_dezenas'] == 0)
				$erro[] = "Especifique a quantidade de dezenas do bolão.";
			else
				$_SESSION['dezenabolao'] = intval($_POST['qtd_dezenas']);
		} else
			$_SESSION['dezenabolao'] = 0;

		if ($_SESSION['comissao'] > 100)
			$erro[] = "A comissão o vendedor não pode ser maior que 100%";

		$erro = array();

		if (strlen($_SESSION['titulo']) == 0)
			$erro[] = "Preencha o título da rifa";

		$etapa1 = 'NULL';
		$etapa2 = 'NULL';
		$qtd_dezenas_1 = 'NULL';
		$qtd_dezenas_2 = 'NULL';
		if($_SESSION['selecao_2_etapas'] == 1) {
			if(empty($_SESSION['faixa_inicial']) || empty($_SESSION['faixa_final']) || empty($_SESSION['faixa_inicial2']) || empty($_SESSION['faixa_final2']) || empty($_SESSION['quant_dezenas1']) || empty($_SESSION['quant_dezenas2'])) 
				$erro[] = "Preencha a faixa da etapa 1 e da etapa2";
			else {
				$etapa1 = "'" . $_SESSION['faixa_inicial'] . "-" . $_SESSION['faixa_final'] . "'";
				$etapa2 = "'" . $_SESSION['faixa_inicial2'] . "-" . $_SESSION['faixa_final2'] . "'";
				$qtd_dezenas_1 = intval($_SESSION['quant_dezenas1']);
				$qtd_dezenas_2 = intval($_SESSION['quant_dezenas2']);
			}
		}

		if (strlen($_SESSION['datasorteio']) == 0)
			$erro[] = "Preencha a data de sorteio da rifa";

		$data = explode("/", $_SESSION['datasorteio']);

		if (strtotime("$data[2]-$data[1]-$data[0] 23:59:00") <= strtotime(date("Y-m-d") . " 23:59:00"))
			$erro[] = "A data do sorteio não pode ser igual ou anterior à hoje.";

		if (strlen($_SESSION['nbilhete']) == 0 || !is_numeric($_SESSION['nbilhete']))
			$erro[] = "Preencha o número de bilhetes da rifa";

		if (strlen($_SESSION['valorbilhete']) == 0)
			$erro[] = "Preencha o valor do bilhete da rifa";

		if (strlen($_SESSION['proposito']) == 0)
			$erro[] = "Preencha o propósito da rifa";

		if (strlen($_SESSION['premiounico']) == 0)
			$erro[] = "Preencha os detalhes do prêmio da rifa";

		$npremio = 1;
		if ($_SESSION['nbilhete'] == 10001)
			$npremio = 5;


		if ($_SESSION['nbilhete'] == 10002)
			$npremio = 10;

		if ($_SESSION['nbilhete'] > 10000)
			$_SESSION['nbilhete'] = 10000;
		// padrões de upload

		$arquivo[1] = $_FILES["foto1"];
		$arquivo[2] = $_FILES["foto2"];
		$arquivo[3] = $_FILES["foto3"];
		$tam_maximo = 5; //EM MBs
		$pasta = "../arquivos/imgs/banner";

		if ($arquivo[1]["size"] > ($tam_maximo * 1000000))
			$erro[] = "A foto 1 não deve ser maior que " . $tam_maximo . "mb.";

		if ($arquivo[2]["size"] > ($tam_maximo * 1000000))
			$erro[] = "A foto 2 não deve ser maior que " . $tam_maximo . "mb.";

		if ($arquivo[3]["size"] > ($tam_maximo * 1000000))
			$erro[] = "A foto 3 não deve ser maior que " . $tam_maximo . "mb.";

		if (count($erro) == 0) {
			$d = explode("/", $_SESSION['datasorteio']);
			$df = "$d[2]-$d[1]-$d[0]";
			$valorbilhete = str_replace(',', '.', str_replace("R$", "", $_SESSION['valorbilhete']));
			$imagem[1] = "";
			$imagem[2] = "";
			$imagem[3] = "";
			//Criamos o nome da nova imagem
			$imagem[1] = nomeImagem($_SESSION['usuario'], $arquivo[1]["name"], $arquivo[1]["tmp_name"]);
			$res = move_uploaded_file($arquivo[1]["tmp_name"], $pasta . "/" . $imagem[1]);
			if(!$res)
				die('falha ao enviar imagem');
			if (strlen($arquivo[2]["name"]) > 0) {
				$imagem[2] = nomeImagem($_SESSION['usuario'], $arquivo[2]["name"], $arquivo[2]["tmp_name"]);
				$copia[2] = copy($arquivo[2]["tmp_name"], $pasta . "/" . $imagem[2]);
			}
			if (strlen($arquivo[3]["name"]) > 0) {
				$imagem[3] = nomeImagem($_SESSION['usuario'], $arquivo[3]["name"], $arquivo[3]["tmp_name"]);
				$copia[3] = copy($arquivo[3]["tmp_name"], $pasta . "/" . $imagem[3]);
			}
			foreach ($imagem as $ch => $va) {
				if (strlen($va) > 0)
					$imagem[$ch] = "arquivos/imgs/banner/" . $va;
			}
			$sql_code = "INSERT INTO tbl_rifas (dezena_bolao, rifa_premios, rifa_titulo, rifa_dtsorteio, rifa_valorbilhete, 
				rifa_maxbilhetes, rifa_descricao, rifa_proposito, rifa_foto1, rifa_foto2, rifa_foto3, rifa_dono, rifa_regulamento, rifa_dtcriacao, repetir_dezena, etapa1, etapa2, qtd_dezenas_etapa_1, qtd_dezenas_etapa_2)
			VALUES(
				'{$_SESSION['dezenabolao']}',
				'$npremio',
				'{$_SESSION['titulo']}',
				'$df',
				'$valorbilhete',
				'{$_SESSION['nbilhete']}',
				'{$_SESSION['premiounico']}',
				'{$_SESSION['proposito']}',
				'$imagem[1]',
				'$imagem[2]',
				'$imagem[3]',
				'{$_SESSION['usuario']}', 
				'{$_SESSION['regulamento']}', 
				now(), 
				'{$_SESSION['repetir_dezena']}', 
				$etapa1, 
				$etapa2,
				$qtd_dezenas_1,
				$qtd_dezenas_2
			)";
			$sucesso = $mysqli->query($sql_code);
			$rifa_codigo = $mysqli->insert_id;
			foreach ($_SESSION[premio] as $cod => $pre) {
				if (strlen($pre) > 0) {
					$indice = $cod + 1;
					$mysqli->query("INSERT INTO tbl_premio (pre_rifa, pre_descricao, pre_ranking)
						values('$rifa_codigo', '$pre', '$indice')");
				}
			}

			if ($sucesso) {
				foreach ($_SESSION as $ch => $va) {
					if ($ch != "usuario" && $ch != "admin")
						unset($_SESSION[$ch]);
				}
				//echo "<script>location.href='index.php?p=rifa';</script>";
			}
		}
	}
	?>
	<style>
		.esconder {
			display: none;
		}
	</style>
	<div class="col-lg-12 text-left">
		<h3>Cadastrar Rifa</h3>
		<hr>
		<?php if (count($erro) > 0) { ?>
			<div class="col-lg-12">
				<?php foreach ($erro as $msg) echo "<div class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>
			</div>
		<?php } ?>

	</div>
	<form action="" enctype="multipart/form-data" method="post">
		<div class="col-lg-6 text-left">

			<div class="form-group col-lg-8	">
				<label for="">Título da Rifa*</label>
				<input value="<?php if (isset($_SESSION['titulo'])) echo $_SESSION['titulo']; ?>" type="text" required placeholder="ex: Fiat Uno 4 Portas" name="titulo" class=" form-control">
			</div>
			<div class="form-group col-lg-4">
				<label for="">Data do Sorteio*</label>
				<input placeholder="00/00/0000" value="<?php if (isset($_SESSION['datasorteio'])) echo $_SESSION['datasorteio']; ?>" type="text" required name="datasorteio" class="datepicker form-control">
			</div>
			<div class="form-group col-lg-6">
				<label for="">Modalidade da Rifa*</label>
				<select onchange="javascript: formulario(this.value);" type="text" required name="nbilhete" class=" form-control">
					<option value="">Selecione</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] >= 100 && $_SESSION['dezenabolao']) echo "selected"; ?> value="10000x">(Dezena-Bolão) 00 à 99</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] == 100 && !$_SESSION['dezenabolao']) echo "selected"; ?> value="100">(Dezena) 00 à 99</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] == 1000) echo "selected"; ?> value="1000">(Centena) 000 à 999</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] == 10000) echo "selected"; ?> value="10000">(Milhar) 0000 à 9999</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] == 10001) echo "selected"; ?> value="10001">(Milhar) 0000 à 9999 Premiação do 1º ao 5º prêmio</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] == 10002) echo "selected"; ?> value="10002">(Milhar) 0000 à 9999 Premiação do 1º ao 10º prêmio</option>
					<option <?php if (isset($_SESSION['nbilhete'])) if ($_SESSION['nbilhete'] == 10003) echo "selected"; ?> value="10003">(JBOnline) 0000-9999</option>
				</select>
			</div>
			<div class="form-group col-lg-6">
				<label for="">Valor do Bilhete*</label>
				<input value="<?php if (isset($_SESSION['valorbilhete'])) echo $_SESSION['valorbilhete']; ?>" type="text" required name="valorbilhete" class="dinheiro form-control">
			</div>
			<div class="form-group col-lg-4">
				<label for="">Foto Principal*</label>
				<input type="file" required name="foto1" class=" form-control">
			</div>
			<div class="form-group col-lg-4">
				<label for="">Foto 2</label>
				<input type="file" name="foto2" class=" form-control">
			</div>
			<div class="form-group col-lg-4">
				<label for="">Foto 3</label>
				<input type="file" name="foto3" class=" form-control">
			</div>
			<div class="form-group col-lg-4">
				<label for="">Propósito da Rifa*</label>
				<textarea name="proposito" required id="" cols="30" rows="4" class="form-control" placeholder="Pra que você está criando esta rifa?"><?php if (isset($_SESSION['proposito'])) echo $_SESSION['proposito']; ?></textarea>
			</div>
			<div class="form-group col-lg-4">
				<label for="">Prêmio*</label>
				<textarea name="premiounico" required id="" cols="30" rows="4" class="form-control" placeholder="Descreva os detalhes do prêmio"><?php if (isset($_SESSION['premiounico'])) echo $_SESSION['premiounico']; ?></textarea>
			</div>
			<div class="form-group col-lg-4">
				<label for="">Regulamento</label>
				<textarea name="regulamento" id="" cols="30" rows="4" class="form-control" placeholder="Descreva as regras da rifa"><?php if (isset($_SESSION['regulamento'])) echo $_SESSION['regulamento']; ?></textarea>
			</div>
			<div class="form-group col-lg-12">
				<button type="submit" name="enviar" value="1" class="btn-success btn">salvar</button>
				<button type="button" onclick="javascript:location.href='index.php?p=rifa';" class="btn">voltar</button>
			</div>


		</div>
		<div id="dezena_bolao" class="<?php if ($_SESSION['nbilhete'] >= 100 && $_SESSION['dezenabolao']) echo '';
											else echo 'esconder'; ?> col-lg-6">
			<div class="form-group col-lg-12">
				<label for="">Quantidade de Dezenas</label>
				<input type="text" name="qtd_dezenas" value="<?php echo $_SESSION['dezenabolao']; ?>" class="form-control" value="10" placeholder="10">
			</div>
			<div class="form-group col-lg-12">
				<label for="repetir">
					<input value="1" <?php if ($_SESSION['repetir_dezena'] == 1) echo 'checked'; ?> name="repetir_dezena" id="repetir" type="checkbox"> Repetir Dezenas?
				</label>
			</div>

			<div class="form-group col-lg-12">
				<label for="selecao_2_etapas">
					<input value="1" <?php if ($_SESSION['selecao_2_etapas'] == 1) echo 'checked'; ?> value="1" onchange="change2etapas(this);" name="selecao_2_etapas" id="selecao_2_etapas" type="checkbox"> Seleção em 2 etapas
				</label>
			</div>

		</div>
		<div id="selecao_2_etapas_holder" class="<?php if ($_SESSION['selecao_2_etapas'] != 1) echo 'esconder'; ?> col-lg-6 text-left">
			
			<div class="col-lg-6">

				<p>Etapa 01: </p>
				<div class="form-group">
					<label for="">Quant. Dezenas:</label>
					<input name="quant_dezenas1" value="" type="text" class="form-control">
				</div>
				<div class="form-inline">
					<label for="">Faixa:</label>
					<input name="faixa_inicial" size="3" value="" type="text" class="form-control"> a <input size="3" name="faixa_final" value="" type="text" class="form-control">
				</div>

			</div>
			
			<div class="col-lg-6">

				<p>Etapa 02: </p>
				<div class="form-group">
					<label for="">Quant. Dezenas:</label>
					<input name="quant_dezenas2" value="" type="text" class="form-control">
				</div>
				<div class="form-inline">
					<label for="">Faixa:</label>
					<input name="faixa_inicial2" size="3" value="" type="text" class="form-control"> a <input size="3" name="faixa_final2" value="" type="text" class="form-control">
				</div>
				
			</div>
			
		</div>

		<div id="detalhes" class="<?php if ($_SESSION[nbilhete] == 10001 || $_SESSION[nbilhete] == 10002) echo "";
										else echo "esconder"; ?> esconder col-lg-6 text-left">
			<p>Detalhes do Sorteio</p>
			<div class="<?php if ($_SESSION[nbilhete] == 10001 || $_SESSION[nbilhete] == 10002) echo "";
							else echo "esconder"; ?>" id="milhar1">
				<div class="form-group col-lg-6">
					<label for="">1º Prêmio</label>
					<input name="premio[]" value="<?php $k = 0;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">2º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">3º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">4º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">5º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
			</div>
			<div class="<?php if ($_SESSION[nbilhete] == 10002) echo "";
							else echo "esconder"; ?>" id="milhar2">
				<div class="form-group col-lg-6">
					<label for="">6º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">7º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">8º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">9º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
				<div class="form-group col-lg-6">
					<label for="">10º Prêmio</label>
					<input name="premio[]" value="<?php $k++;
														echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">
				</div>
			</div>
		</div>

		<!-- Formulario para rifa (JBOnline) 0000-9999 -->
		<div id="detalhes_jbonline" class="<?php if ($_SESSION[nbilhete] == 10003) echo "";
												else echo "esconder"; ?> esconder col-lg-6 text-left ">
			<h4>Tipos de Aposta</h4>

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Grupo (G)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput0')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput0" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6 mt-15px" style="margin-top:2%">
				<label for="">Milhar Centena Invertido (MCI)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput1')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput1" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Milhar Invertido (MI)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput2')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput2" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Bicho (B)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput3')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput3" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Terno de Grupo (TG)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput4')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput4" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Centena (C)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput5')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput5" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Milhar Seco (M)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput6')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput6" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Dezena (DZ)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput7')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput7" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Milhar e Centena (MC)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput8')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput8" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Terno de Dezena (TDZ)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput9')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput9" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Duque de Grupo (DG)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput10')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput10" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->

			<div class="col-lg-6" style="margin-top:2%">
				<label for="">Centena Invertida (CI)</label>
				<div class="input-group">
					<span class="input-group-addon">
						<input type="checkbox" name="jbcheck[]" onclick="enabledCheck(this, 'jbinput11')">
					</span>
					<input disabled type="number" class="form-control" name="jbinput[]" id="jbinput11" placeholder="Digite um multiplicador">
				</div><!-- /input-group -->
			</div><!-- /.col-lg-6 -->
		</div>
		<!-- Fim (JBOnline) 0000-9999 -->
	</form>
	<script>
	
		function change2etapas(el) {
			el = $(el);
			if(el.is(':checked'))
				$('#selecao_2_etapas_holder').removeClass('esconder');
			else
				$('#selecao_2_etapas_holder').addClass('esconder');
		}

		function enabledCheck(t, textBoxId) {
			$("#" + textBoxId).prop("disabled", !$(t).prop("checked"));
		}

		function formulario(valor) {

			if (valor == '10000x') {
				$("#detalhes_jbonline").addClass("esconder");
				// dezena bolao
				$('#dezena_bolao').removeClass('esconder');
			} else
				$('#dezena_bolao').addClass('esconder');

			if (valor == 10001 || valor == 10002) {
				$("#detalhes_jbonline").addClass("esconder");
				if (valor == 10001) {
					$("#milhar1").removeClass("esconder");
					$("#milhar2").addClass("esconder");
				} else {
					$("#milhar1").removeClass("esconder");
					$("#milhar2").removeClass("esconder");
				}
				$("#detalhes").removeClass("esconder");
			} else {
				$("#milhar1").removeClass("esconder");
				$("#milhar2").removeClass("esconder");
				$("#detalhes").removeClass("esconder");
				$("#milhar1").addClass("esconder");
				$("#milhar2").addClass("esconder");
				$("#detalhes").addClass("esconder");
				$("#detalhes_jbonline").addClass("esconder");
			}

			if (valor == 10003) {
				$("#detalhes_jbonline").removeClass("esconder");
			}
		}
	</script>
<?php } ?>