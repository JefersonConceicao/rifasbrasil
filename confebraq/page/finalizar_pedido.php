<?php

if(!isset($_SESSION))
	session_start();

if(count($_SESSION[carrinho]) > 0){

	if(isset($_SESSION[usuario])){

		include("class/conexao.php");

		// Calcular valor Total

		foreach($_SESSION[carrinho] as $ch=>$v){
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
			$valor_total += (count($_SESSION[carrinho][$rifa_tmp[rifa_cod]]) * $rifa_tmp[rifa_valorbilhete]);
		}while($rifa_tmp = $sql_query->fetch_assoc());

		// Fim


		// Verifica se alguma das rifas tem Bug
		$erro = 0;
		foreach($_SESSION[carrinho] as $cod_rifa=>$void)
			if(count($_SESSION[carrinho][$cod_rifa]) == 0)
				$erro++;

		//FIM

		if($erro > 0){

			//Nenhum bilhete foi selecionado para alguma rifa.
			echo "<script>";
			echo "alert('Selecione os bilhetes que deseja adquirir na página à seguir.');";
			echo "location.href='index.php?p=rifa&codigo=".intval($cod_rifa)."';";
			echo "</script>";

		}else{

			// Finalizar Registro

			if($_SESSION[revendedor] > 0)
				$rev = "'$_SESSION[revendedor]'";
			else
				$rev = "NULL";

			$sql_compra = "INSERT INTO tbl_compra(
						comp_cliente,
						comp_data,
						comp_valortotal,
						comp_revendedor
						) VALUES(
						'$_SESSION[usuario]',
						NOW(),
						'$valor_total',
						$rev)
						";

			$executar_compra = $mysqli->query($sql_compra) or die($mysqli->error);
			$cod_compra = $mysqli->insert_id;

			foreach($_SESSION[carrinho] as $rifa_cod => $v){
				foreach($_SESSION[carrinho][$rifa_cod] as $cod_bilhete=>$void){

					$cod_bilhete = intval($cod_bilhete);
					$sql_bilhete = "INSERT INTO tbl_bilhetes (bil_rifa, bil_numero, bil_situacao, bil_compra)
					VALUES('$rifa_cod', '$cod_bilhete', 'P', '$cod_compra')";
					$executar_bilhete = $mysqli->query($sql_bilhete) or die($mysqli->error);
						
				}
			}
				
			// Limpar Carrinho

			foreach($_SESSION[carrinho] as $ch=>$va){

				foreach($_SESSION[carrinho][$ch] as $chave=>$valor)
					unset($_SESSION[carrinho][$ch][$chave]);

				unset($_SESSION[carrinho][$ch]);

			}

			// Só pra garantir

			if(count($_SESSION[carrinho]) > 0)
				unset($_SESSION[carrinho]);

			// Redirecionar usuário

			echo "<script>";
			echo "location.href='index.php?p=pagar&compra=".$cod_compra."';";
			echo "</script>";
			
		}			

	}else{

		//Usuário não está logado
		echo "<script>";
		echo "alert('Faça login para prosseguir.');";
		echo "location.href='index.php?p=entrar&next=finalizar_pedido';";
		echo "</script>";

	}

}else{

	//Carrinho vazio
	echo "<script>";
	echo "alert('Carrinho vazio!');";
	echo "location.href='index.php?p=inicial';";
	echo "</script>";

}




?>