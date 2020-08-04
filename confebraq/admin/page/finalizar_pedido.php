<?php

if(!isset($_SESSION))
	session_start();

if(count($_SESSION[carrinho_admin]) > 0){


		include("../class/conexao.php");


		// REGISTRO DO CLIENTE
        $sql_code_usuario = 
            "INSERT INTO tbl_usuario
            (
            usu_nome,
            
            usu_celular,
           
            
            usu_admin
            )VALUES(
            '$_SESSION[nome]',
            
            '$_SESSION[telefone]',
 
            'N'
            )";

		include("../class/function_enviarSMS.php");
		include("../class/function_primeiroNome.php");
		
        $confirma_usuario = $mysqli->query($sql_code_usuario) or die($mysqli->error);
        $usu_codigo = $mysqli->insert_id;

        $_SESSION[usuario_admin] = $usu_codigo;
        
        //mail($_SESSION[email], "Valide sua conta", "Olá\n\nSua conta foi criada com sucesso.\nClique no link para validar sua conta: http://rifasbrasil.com.br/index.php?p=validar&cod=".md5($usuario)."&email=".$_SESSION[email]."\n\nAtenciosamente,\nRifasBRASIL");
       	$celular = $_SESSION['telefone'];
       	$nomecliente = $_SESSION['nome'];
        unset($_SESSION['nome'],
	    $_SESSION['email'],
	    $_SESSION['telefone'],
	    $_SESSION['cpf'],
	    $_SESSION['senha'],
	    $senha,
	    $_SESSION['rsenha']);

		// FIM


		

		// Calcular valor Total

		foreach($_SESSION[carrinho_admin] as $ch=>$v){
			$sql_add .= " rifa_cod = '$ch' OR";

		}
		$sql_add = substr($sql_add, 0, -2);

		$sql_code = "SELECT rifa_cod, rifa_valorbilhete, rifa_titulo 
		FROM tbl_rifas 
		WHERE ({$sql_add}) 
		AND (rifa_vencedor IS NULL OR rifa_vencedor = '')";

		$sql_query = $mysqli->query($sql_code) or die($mysqli->error);
		$rifa_tmp = $sql_query->fetch_assoc();

		$valor_total = 0;

		do{
			$valor_total += (count($_SESSION[carrinho_admin][$rifa_tmp[rifa_cod]]) * $rifa_tmp[rifa_valorbilhete]);
		}while($rifa_tmp = $sql_query->fetch_assoc());

		// Fim


		// Verifica se alguma das rifas tem Bug
		$erro = 0;
		foreach($_SESSION[carrinho_admin] as $cod_rifa=>$void)
			if(count($_SESSION[carrinho_admin][$cod_rifa]) == 0)
				$erro++;

		//FIM

		if($erro > 0){

			//Nenhum bilhete foi selecionado para alguma rifa.
			echo "<script>";
			echo "alert('Selecione os bilhetes que deseja adquirir na página à seguir.');";
			echo "location.href='index.php?p=ver_bilhetes&rifa=".intval($cod_rifa)."';";
			echo "</script>";

		}else{


			if($_SESSION['tipo_venda'] == 'paga')
				$sta_revenda = 1;
			else
				$sta_revenda = 0;

			$situacao = "";
			
			// Finalizar Registro

			$sql_compra = "INSERT INTO tbl_compra(
						comp_cliente,
						comp_data,
						comp_valortotal,
						comp_revendedor,
						comp_debitor,
						comp_situacao,
						comp_status_revenda
						) VALUES(
						'$_SESSION[usuario_admin]',
						NOW(),
						'$valor_total',
						'$_SESSION[usuario]',
						'$_SESSION[usuario]',
						'$situacao',
						'$sta_revenda')
						";

			$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
			$cod_compra = $mysqli->insert_id;

			$bilhetes_f = "";

			

			foreach($_SESSION[carrinho_admin] as $rifa_cod => $v){
				foreach($_SESSION[carrinho_admin][$rifa_cod] as $cod_bilhete=>$void){

					$cod_bilhete = intval($cod_bilhete);

					$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '$cod_compra')";

					$maxbilhetes = DBSelect("select rifa_maxbilhetes as max, rifa_dtsorteio as data from tbl_rifas where rifa_cod = '$rifa_cod'", $mysqli);
					$data = date("d/m/Y", strtotime($maxbilhetes[data]));
					$maxbilhetes = $maxbilhetes[max];

					$bilhetes_f .= str_pad($cod_bilhete, strlen($maxbilhetes)-1, "0", STR_PAD_LEFT)."-";


					$executar_bilhete = $mysqli->query($sql_bilhete) or die($mysqli->error);

					
						
				}
			}

			$bilhetes_f = substr($bilhetes_f, 0, -1);
			//enviarSMS($celular, "RifasBrasil - Seu(s) bilhete(s): $bilhetes_f da rifa: http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa_cod." - Sorteio dia: ".$data, true);
			$nome_rifa = substr(DBSelect("SELECT rifa_titulo From tbl_rifas where rifa_cod = '$rifa_cod'", $mysqli, "rifa_titulo"), 0, 25);
			//$sms = "RifasBrasil - Seu(s) bilhete(s):  da rifa: http://rifasbrasil.com.br/index.php?p=rifa&codigo=".$rifa_cod." - Sorteio dia: ".;
			
			if($_SESSION['tipo_venda'] == 'paga')
				$final = "PAGO";
			else
				$final = "NAO PAGO";

			$sms = "RIFASBRASIL: Ola ".primeiroNome($nomecliente).", seu(s) Bilhete(s): (".$bilhetes_f.") Da Rifa: (".$nome_rifa.") Sorteio dia: (".$data.") - $final - Boa Sorte!";			// Limpar Carrinho

			foreach($_SESSION[carrinho_admin] as $ch=>$va){

				foreach($_SESSION[carrinho_admin][$ch] as $chave=>$valor)
					unset($_SESSION[carrinho_admin][$ch][$chave]);

				unset($_SESSION[carrinho_admin][$ch]);

			}

			unset($_SESSION[usuario_admin]);

			// Só pra garantir

			if(count($_SESSION[carrinho_admin]) > 0)
				unset($_SESSION[carrinho_admin]);

			// Redirecionar usuário


			$_SESSION[sms] = $sms;
			echo "<script>";
			echo "location.href='index.php?p=sucesso_venda&usuario=".$usu_codigo."';";
			echo "</script>";
			
		}			



}else{

	//Carrinho vazio
	echo "<script>";
	echo "alert('Carrinho vazio!');";
	echo "location.href='index.php?p=revenda';";
	echo "</script>";

}




?>