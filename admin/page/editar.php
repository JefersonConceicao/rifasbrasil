<?phpinclude("../class/conexao.php");function nomeImagem($cod_usuario, $arq_nome, $tmp_nome){	return $cod_usuario."-".md5("1-".time()."-".$tmp_nome).".".substr($arq_nome, -3);}$codigo = intval($_GET[codigo]);if(!isset($_SESSION)) @session_start();if(intval($_GET['codigo']) > 0  && isset($_SESSION['usuario'])){				$rifa = DBSelect("SELECT * FROM tbl_rifas WHERE rifa_cod = '$codigo'", $mysqli);		$_SESSION[titulo] = $rifa[rifa_titulo];		$_SESSION[datasorteio] = date("d/m/Y", strtotime($rifa[rifa_dtsorteio]));		$_SESSION['nbilhete'] = $rifa['rifa_maxbilhetes'];		$_SESSION['repetir_dezena'] = $rifa['repetir_dezena'];		$_SESSION[valorbilhete] = number_format($rifa[rifa_valorbilhete], 2, ',', '.');		$_SESSION[proposito] = $rifa[rifa_proposito];		$_SESSION[premiounico] = $rifa[rifa_descricao];		$_SESSION[foto1] = $rifa[rifa_foto1];		$_SESSION[foto2] = $rifa[rifa_foto2];		$_SESSION[foto3] = $rifa[rifa_foto3];		$_SESSION['dezenabolao'] = $rifa['dezena_bolao'];		$_SESSION['repetir_dezena'] = $rifa['repetir_dezena'];		$_SESSION[rifa_premios] = $rifa[rifa_premios];		if(!empty($rifa['coluna_grupo_bd'])) {			$_SESSION['coluna_grupo'] = 1;			$_SESSION['coluna_grupo_qtd'] = intval($rifa['coluna_grupo_bd']);		}		unset($_SESSION['selecao_2_etapas']);		if($rifa['etapa1'] && $rifa['etapa2']) {			$_SESSION['selecao_2_etapas'] = 1;			$_SESSION['etapa1'] = $rifa['etapa1'];			$_SESSION['etapa2'] = $rifa['etapa2'];			$_SESSION['qtd_dezenas_etapa_1'] = $rifa['qtd_dezenas_etapa_1'];			$_SESSION['qtd_dezenas_etapa_2'] = $rifa['qtd_dezenas_etapa_2'];		} else if($rifa['etapa1']) {			$_SESSION['etapa1'] = $rifa['etapa1'];			$_SESSION['qtd_dezenas_etapa_1'] = intval($rifa['qtd_dezenas_etapa_1']);		}		if($_SESSION[rifa_premios]  == 5)			$_SESSION[nbilhete] = 10001;		if($_SESSION[rifa_premios]  == 10)			$_SESSION[nbilhete] = 10002;		$prem_exec = DBExecute("SELECT * FROM tbl_premio WHERE pre_rifa = '$codigo'", $mysqli);        $premio = $prem_exec->fetch_assoc();        $_SESSION[premio] = Array();        do{            $_SESSION[premio][$premio[pre_ranking]-1] = $premio[pre_descricao];        }while($premio = $prem_exec->fetch_assoc());}$banco_code = "SELECT count(*) as c from tbl_conta WHERE usuario = '$_SESSION[usuario]'";$banco_query = $mysqli->query($banco_code) or die($mysqli->error);$bb = $banco_query->fetch_assoc();$ps_code = "SELECT usu_psemail as i1, usu_pstoken as i2 from tbl_usuario where usu_cod = '$_SESSION[usuario]' ";$ps_query = $mysqli->query($ps_code) or die($mysqli->error);$ps = $ps_query->fetch_assoc();if((strlen($ps[i1]) == 0 || strlen($ps[i2]) == 0) && $bb[c] == 0){	echo "<script>location.href='index.php?p=pagamento';</script>";}else{	if(isset($_POST['enviar'])){		unset($_SESSION['selecao_2_etapas'], $_SESSION['faixa_inicial'], $_SESSION['faixa_final'], $_SESSION['faixa_inicial2'], $_SESSION['qtd_dezenas_etapa_1'], $_SESSION['qtd_dezenas_etapa_2'], $_SESSION['dezenabolao']);		foreach($_POST as $ch=>$va){			if(is_array($_POST[$ch]))				foreach($_POST[$ch] as $cha=>$val){					$_SESSION[$ch][$cha] = $mysqli->escape_string($val);				}			else				$_SESSION[$ch] = $mysqli->escape_string($va);		}		$erro = Array();		$_SESSION['repetir_dezena'] = ($_SESSION['repetir_dezena'] == '1')? 1:0;		if(strlen($_SESSION[titulo]) == 0)			$erro[] = "Preencha o título da rifa";		/*		if(strlen($_SESSION[datasorteio]) == 0)			$erro[] = "Preencha a data de sorteio da rifa";		$data = explode("/", $_SESSION[datasorteio]);		echo "$data[2]-$data[1]-$data[0] 23:59:00";		if(strtotime("$data[2]-$data[1]-$data[0] 23:59:00") <= time())			$erro[] = "A data do sorteio não pode ser igual ou anterior à hoje.";		if(strlen($_SESSION[nbilhete]) == 0 || !is_numeric($_SESSION[nbilhete]))			$erro[] = "Preencha o número de bilhetes da rifa";		if(strlen($_SESSION[valorbilhete]) == 0)			$erro[] = "Preencha o valor do bilhete da rifa";		*/		if(strlen($_SESSION[proposito]) == 0)			$erro[] = "Preencha o propósito da rifa";		if(strlen($_SESSION[premiounico]) == 0)			$erro[] = "Preencha os detalhes do prêmio da rifa";		// padrões de upload		$arquivo[1] = $_FILES["foto1"];		$arquivo[2] = $_FILES["foto2"];		$arquivo[3] = $_FILES["foto3"];		$_SESSION['dezenabolao'] = intval($_POST['qtd_dezenas']);		$tam_maximo = 5; //EM MBs    	$pasta = "../arquivos/imgs/banner";    	if($arquivo[1]["size"] > ($tam_maximo*1000000))        	$erro[] = "A foto 1 não deve ser maior que ".$tam_maximo."mb.";	    if($arquivo[2]["size"] > ($tam_maximo*1000000))	        $erro[] = "A foto 2 não deve ser maior que ".$tam_maximo."mb.";	    if($arquivo[3]["size"] > ($tam_maximo*1000000))	        $erro[] = "A foto 3 não deve ser maior que ".$tam_maximo."mb.";	    $coluna_grupo_bd = 'NULL';		if(isset($_SESSION['coluna_grupo']) && $_SESSION['coluna_grupo'] == '1') {			if(intval($_SESSION['coluna_grupo_qtd']) <= 0) 				$erro[] = "Preencha a quantidade da Coluna Grupo ativada";			else				$coluna_grupo_bd = intval($_SESSION['coluna_grupo_qtd']);		}		if(count($erro) == 0){			$d = explode("/", $_SESSION[datasorteio]);			$df = "$d[2]-$d[1]-$d[0]";			$valorbilhete = str_replace(',', '.', str_replace("R$", "", $_SESSION[valorbilhete]));			$imagem[1] = "";			$imagem[2] = "";			$imagem[3] = ""; 			//Criamos o nome da nova imagem 			if(strlen($arquivo[1]["name"]) > 0){	            $imagem[1] = nomeImagem($_SESSION[usuario], $arquivo[1]["name"],$arquivo[1]["tmp_name"]);	            copy($arquivo[1]["tmp_name"], $pasta."/".$imagem[1]);        	}            if(strlen($arquivo[2]["name"]) > 0){            	$imagem[2] = nomeImagem($_SESSION[usuario], $arquivo[2]["name"],$arquivo[2]["tmp_name"]);            	$copia[2] = copy($arquivo[2]["tmp_name"], $pasta."/".$imagem[2]);            }            if(strlen($arquivo[3]["name"]) > 0){            	$imagem[3] = nomeImagem($_SESSION[usuario], $arquivo[3]["name"],$arquivo[3]["tmp_name"]);            	$copia[3] = copy($arquivo[3]["tmp_name"], $pasta."/".$imagem[3]);            }            $sql_final = "";            $fi=1;            foreach($imagem as $ch=>$va){            	if(strlen($va) > 0){		            		$imagem[$ch] = "arquivos/imgs/banner/".$va;            		$sql_final .= "rifa_foto".$fi." = '".$imagem[$ch]."',";            		$fi++;            	}            	            }            $d = explode("/", $_SESSION[datasorteio]);			$df = "$d[2]-$d[1]-$d[0]";            $_SESSION[datasorteio] = $df;            $etapa1 = 'NULL';			$etapa2 = 'NULL';			$qtd_dezenas_1 = 'NULL';			$qtd_dezenas_2 = 'NULL';			if($_SESSION['selecao_2_etapas'] == 1) {				if(!isset($_SESSION['faixa_inicial']) || !isset($_SESSION['faixa_final']) || !isset($_SESSION['faixa_inicial2']) || !isset($_SESSION['faixa_final2']) || !isset($_SESSION['qtd_dezenas_etapa_1']) || !isset($_SESSION['qtd_dezenas_etapa_2'])) 					$erro[] = "Preencha a faixa da etapa 1 e da etapa 2";				else {					$etapa1 = "'" . $_SESSION['faixa_inicial'] . "-" . $_SESSION['faixa_final'] . "'";					$etapa2 = "'" . $_SESSION['faixa_inicial2'] . "-" . $_SESSION['faixa_final2'] . "'";					$qtd_dezenas_1 = intval($_SESSION['qtd_dezenas_etapa_1']);					$qtd_dezenas_2 = intval($_SESSION['qtd_dezenas_etapa_2']);				}			} else if(strlen($_SESSION['1_etapa_inicio']) > 0 && strlen($_SESSION['1_etapa_fim']) > 0) {				$etapa1 = "'" . intval($_SESSION['1_etapa_inicio']) . "-" . intval($_SESSION['1_etapa_fim']) . "'";				$qtd_dezenas_1 = $_SESSION['dezenabolao'];			}             $sql_code = "UPDATE tbl_rifas  set ".$sql_final." rifa_titulo = '$_SESSION[titulo]',             rifa_dtsorteio = '$_SESSION[datasorteio]',            dezena_bolao = '$_SESSION[dezenabolao]',            repetir_dezena = '$_SESSION[repetir_dezena]',			rifa_descricao = '$_SESSION[premiounico]', 			rifa_proposito = '$_SESSION[proposito]', 			rifa_regulamento = '$_SESSION[regulamento]',			rifa_valorbilhete = '$valorbilhete',			coluna_grupo_bd = '$coluna_grupo_bd',			etapa1 = $etapa1,			etapa2 = $etapa2,			qtd_dezenas_etapa_1 = $qtd_dezenas_1, 			qtd_dezenas_etapa_2 = $qtd_dezenas_2			WHERE rifa_cod = '$codigo'";			$mysqli->query("DELETE FROM tbl_premio WHERE pre_rifa = '$codigo'") or die ($mysqli->error);			foreach($_SESSION[premio] as $cod=>$pre){				if(strlen($pre) > 0){					$indice = $cod+1;										$mysqli->query("INSERT INTO tbl_premio (pre_rifa, pre_descricao, pre_ranking)						values('$codigo', '$pre', '$indice')") or die ($mysqli->error);				}			}			$sucesso = $mysqli->query($sql_code);			if($sucesso){				foreach($_SESSION as $ch=>$va){					if($ch != "usuario" && $ch != "admin")						unset($_SESSION[$ch]);				}				echo "<script>location.href='index.php?p=rifa';</script>";			}		}	}?><style>	.esconder{display:none;}</style><div class="col-lg-12 text-left">	<h3>Cadastrar Rifa</h3>	<hr>	<?php if(count($erro) > 0){ ?>	<div class="col-lg-12">		<?php foreach($erro as $msg) echo "<div class=\"col-lg-12 bg-warning\"><p>$msg</p></div>"; ?>	</div>	<?php } ?>	</div><form action="" enctype="multipart/form-data"  method="post"><div class="col-lg-6 text-left">			<div class="form-group col-lg-8	">			<label for="">Título da Rifa*</label>			<input value="<?php echo $_SESSION[titulo]; ?>" type="text" required placeholder="ex: Fiat Uno 4 Portas" name="titulo" class=" form-control">		</div>		<div class="form-group col-lg-4">			<label for="">Data do Sorteio*</label>			<input value="<?php echo $_SESSION[datasorteio]; ?>" type="text" name="datasorteio" class="datepicker form-control">		</div>		<div class="form-group col-lg-6">			<label for="">Número Máximo de Bilhetes*</label>			<select onchange="javascript: formulario(this.value);" type="text" disabled name="nbilhete" class=" form-control">				<option value="">Selecione</option>				<option <?php if($_SESSION['nbilhete'] >=  100 && $_SESSION['dezenabolao']) echo "selected"; ?> value="100x">(Dezena Bolão) 00 à 99</option>				<option <?php if($_SESSION['nbilhete']== 100 && !$_SESSION['dezenabolao']) echo "selected"; ?> value="100">(Dezena) 00 à 99</option>				<option <?php if($_SESSION['nbilhete']== 1000 && !$_SESSION['dezenabolao']) echo "selected"; ?>  value="1000">(Centena) 000 à 999</option>				<option <?php if($_SESSION['nbilhete']== 10000 && !$_SESSION['dezenabolao']) echo "selected"; ?>  value="10000">(Milhar) 0000 à 9999</option>				<option <?php if(($_SESSION['nbilhete']== 10001 || $_SESSION['rifa_premios']== 5) && !$_SESSION['dezenabolao']) echo "selected"; ?>  value="10001">(Milhar) 0000 à 9999 Premiação do 1º ao 5º prêmio</option>				<option <?php if(($_SESSION['nbilhete']== 10002  || $_SESSION['rifa_premios']== 10) && !$_SESSION['dezenabolao']) echo "selected"; ?>  value="10002">(Milhar) 0000 à 9999 Premiação do 1º ao 10º prêmio</option>			</select>		</div>		<div class="form-group col-lg-6">			<label for="">Valor do Bilhete*</label>			<input value="<?php echo $_SESSION[valorbilhete]; ?>" type="text" name="valorbilhete" class="dinheiro form-control">		</div>		<div class="form-group col-lg-4">			<label for="">Foto Principal*</label>			<input  type="file" name="foto1" class=" form-control">		</div>		<div class="form-group col-lg-4">			<label for="">Foto 2</label>			<input  type="file" name="foto2" class=" form-control">		</div>		<div class="form-group col-lg-4">			<label for="">Foto 3</label>			<input  type="file" name="foto3" class=" form-control">		</div>		<div class="form-group col-lg-4">			<label for="">Propósito da Rifa*</label>			<textarea name="proposito" required id="" cols="30" rows="4" class="form-control" placeholder="Pra que você está criando esta rifa?"><?php echo $_SESSION[proposito]; ?></textarea>		</div>		<div class="form-group col-lg-4">			<label for="">Prêmio*</label>			<textarea name="premiounico" required id="" cols="30" rows="4" class="form-control" placeholder="Descreva os detalhes do prêmio"><?php echo $_SESSION[premiounico]; ?></textarea>		</div>		<div class="form-group col-lg-4">			<label for="">Regulamento</label>			<textarea name="regulamento" id="" cols="30" rows="4" class="form-control" placeholder="Descreva as regras da rifa"><?php echo $_SESSION[regulamento]; ?></textarea>		</div>			<div class="form-group col-lg-12">			<button type="submit" name="enviar" value="1" class="btn-success btn">salvar</button>			<button type="button" onclick="javascript:location.href='index.php?p=rifa';" class="btn">voltar</button>		</div>		</div><div id="dezena_bolao" class="<?php if(!$_SESSION['dezenabolao']) echo 'esconder'; ?> col-lg-6">	<div class="form-group col-lg-12">		<label for="">Quantidade de Dezenas</label>		<input type="text" name="qtd_dezenas" class="form-control" value="<?= $_SESSION['dezenabolao']; ?>" placeholder="10">	</div>	<?php	if(!$_SESSION['selecao_2_etapas'] && $_SESSION['etapa1']) {		$tmp = explode('-', $_SESSION['etapa1']);	}	?>	<div id="selecao_1_etapa_holder" <?php if ($_SESSION['selecao_2_etapas'] == 1) echo 'style="display:none;"'; ?> class="form-group col-lg-12 form-inline">		<div class="form-group">			<label for="Faixa" class="">				Faixa				<input value="<?php if(strlen($tmp[0])) echo (string) $tmp[0]; ?>" name="1_etapa_inicio" type="text" size="3" class="form-control"> a			</label>		</div>		<div class="form-group">			<label for="Faixa" class="">				 <input value="<?php if(strlen($tmp[1])) echo (string) $tmp[1]; ?>" name="1_etapa_fim" type="text" size="3" class="form-control"> 			</label>		</div>			</div>	<div class="form-group col-lg-12">		<label for="repetir">			<input value="1" name="repetir_dezena" <?php if($_SESSION['repetir_dezena']) echo 'checked'; ?> id="repetir" type="checkbox"> Repetir Dezenas?		</label>	</div>	<div class="form-group col-lg-12">		<label for="selecao_2_etapas">			<input value="1" <?php if ($_SESSION['selecao_2_etapas'] == 1) echo 'checked'; ?> value="1" onchange="change2etapas(this);" name="selecao_2_etapas" id="selecao_2_etapas" type="checkbox"> Seleção em 2 etapas		</label>	</div>	<div class="form-group col-lg-12">		<label for="coluna_grupo">			<input value="1" <?php if ($_SESSION['coluna_grupo'] == 1) echo 'checked'; ?> value="1" onchange="colunaGrupo(this);" name="coluna_grupo" id="coluna_grupo" type="checkbox"> Ativar Coluna Grupo <span id="coluna_grupo_qtd" class="<?php if ($_SESSION['coluna_grupo'] != 1) echo 'esconder'; ?> form-group form-inline">| Quantidade: <input type="number" value="<?php if (isset($_SESSION['coluna_grupo_qtd']) && intval($_SESSION['coluna_grupo_qtd']) > 0) echo $_SESSION['coluna_grupo_qtd']; else echo 0; ?>" name="coluna_grupo_qtd" step="1" class="form-control"></span>		</label>	</div></div><div id="selecao_2_etapas_holder" class="<?php if ($_SESSION['selecao_2_etapas'] != 1) echo 'esconder'; ?> col-lg-6 text-left">	<?php	$etapa1 = '';	$etapa2 = '';	if($_SESSION['etapa1'] && $_SESSION['etapa2']) {		$etapa1 = explode('-', $_SESSION['etapa1']);		$etapa2 = explode('-', $_SESSION['etapa2']);		$qtd_etapas_1 = $_SESSION['qtd_dezenas_etapa_1'];		$qtd_etapas_2 = $_SESSION['qtd_dezenas_etapa_2'];	}			?>				<div class="col-lg-6">		<p>Etapa 01: </p>		<div class="form-group">			<label for="">Quant. Dezenas:</label>			<input name="qtd_dezenas_etapa_1" value="<?= $qtd_etapas_1; ?>" type="text" class="form-control">		</div>		<div class="form-inline">			<label for="">Faixa:</label>			<input name="faixa_inicial" size="3" value="<?= $etapa1[0]; ?>" type="text" class="form-control"> a <input size="3" name="faixa_final" value="<?= $etapa1[1]; ?>" type="text" class="form-control">		</div>	</div>		<div class="col-lg-6">		<p>Etapa 02: </p>		<div class="form-group">			<label for="">Quant. Dezenas:</label>			<input name="qtd_dezenas_etapa_2" value="<?= $qtd_etapas_2; ?>" type="text" class="form-control">		</div>		<div class="form-inline">			<label for="">Faixa:</label>			<input name="faixa_inicial2" size="3" value="<?= $etapa2[0]; ?>" type="text" class="form-control"> a <input size="3" name="faixa_final2" value="<?= $etapa2[1]; ?>" type="text" class="form-control">		</div>			</div>	</div><div id="detalhes" class="<?php if($_SESSION[nbilhete] == 10001 || $_SESSION[nbilhete] == 10002) echo ""; else echo "esconder"; ?> col-lg-6 text-left">	<p>Detalhes do Sorteio</p>	<div class="<?php if($_SESSION[nbilhete] == 10001 || $_SESSION[nbilhete] == 10002) echo ""; else echo "esconder"; ?>" id="milhar1">	<div class="form-group col-lg-6">		<label for="">1º Prêmio</label>		<input name="premio[]" value="<?php $k=0; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">2º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">3º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">4º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">5º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	</div>	<div class="<?php if($_SESSION[nbilhete] == 10002) echo ""; else echo "esconder"; ?>" id="milhar2">	<div class="form-group col-lg-6">		<label for="">6º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">7º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">8º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">9º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	<div class="form-group col-lg-6">		<label for="">10º Prêmio</label>		<input name="premio[]" value="<?php $k++; echo $_SESSION[premio][$k]; ?>" type="text" class="form-control">	</div>	</div></div></form><script>	function colunaGrupo (el) {		if($(el).is(':checked'))			$('#coluna_grupo_qtd').removeClass('esconder');		else			$("#coluna_grupo_qtd").addClass('esconder')	}	function change2etapas(el) {			el = $(el);			if(el.is(':checked')) {				$('#selecao_1_etapa_holder').hide();				$('#selecao_2_etapas_holder').removeClass('esconder');			} else {				$('#selecao_1_etapa_holder').show();				$('#selecao_2_etapas_holder').addClass('esconder');			}		}	function formulario(valor){		if(valor == 10001 || valor == 10002){			if(valor == 10001){				$("#milhar1").removeClass("esconder");				$("#milhar2").addClass("esconder");			}else{				$("#milhar1").removeClass("esconder");				$("#milhar2").removeClass("esconder");			}			$("#detalhes").removeClass("esconder");		}else{			$("#milhar1").removeClass("esconder");			$("#milhar2").removeClass("esconder");			$("#detalhes").removeClass("esconder");			$("#milhar1").addClass("esconder");			$("#milhar2").addClass("esconder");			$("#detalhes").addClass("esconder");		}	}</script><?php } ?>