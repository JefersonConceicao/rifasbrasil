<?php

function getPSHistory($data_inicial, $data_final, $email, $token){

	if($data_final == date("Y-m-d"))
		$tt = "T".date("H:i", time()-(60*60*2.3));
	else
		$tt = "T00:00";

	$furl = "https://ws.pagseguro.uol.com.br/v2/transactions?";
	$furl .= "initialDate=".$data_inicial."T00:00";
	$furl .= "&finalDate=".$data_final.$tt;
	$furl .= "&page=1";
	$furl .= "&maxPageResults=999";
	$furl .= "&email=".$email;
	$furl .= "&token=".$token;

	$result = simplexml_load_file($furl);
	if(!$result)
		echo false;
	else
		return $result;

	/*
	Exemplo de retorno

	 'date' => string '2015-12-01T18:51:45.000-02:00' (length=29)
	  public 'reference' => string '3271' (length=4)
	  public 'code' => string '07DD0127-214A-45BB-9E15-5F06EA048758' (length=36)
	  public 'type' => string '1' (length=1)
	  public 'status' => string '7' (length=1) stat
	  public 'cancellationSource' => string 'EXTERNAL' (length=8)
	  public 'paymentMethod' => 
	    object(SimpleXMLElement)[6]
	      public 'type' => string '1' (length=1)
	  public 'grossAmount' => string '45.00' (length=5)
	  public 'discountAmount' => string '0.00' (length=4)
	  public 'feeAmount' => string '2.65' (length=4)
	  public 'netAmount' => string '42.35' (length=5)
	  public 'extraAmount' => string '0.00' (length=4)
	  public 'lastEventDate'
	

	Status

	1	Aguardando pagamento: o comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.
	2	Em análise: o comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.
	3	Paga: a transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento.
	4	Disponível: a transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta.
	5	Em disputa: o comprador, dentro do prazo de liberação da transação, abriu uma disputa.
	6	Devolvida: o valor da transação foi devolvido para o comprador.
	7	Cancelada: a transação foi cancelada sem ter sido finalizada.

	Exemplo de obtenção de transações


	$f = getPSHistory(date("Y-m-d", time()-(86400*30)), date("Y-m-d"), "contato@seuimportado.com", "681B7541184A4E5FB575B50479556F7D");
	foreach($f->transactions->transaction as $t)
		var_dump($t);

	*/



}


?>