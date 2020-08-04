<?php require_once("php7_mysql_shim.php");
    session_start();
    include "config.php";
    include "framework/fwj_datas.php";
    include "framework/fwj_elementos.php";
    include "framework/fwj_formulario.php";
    include "framework/fwj_mensagens.php";
    include "framework/fwj_mysql.php";
    include_once ("PagSeguro/PagSeguroLibrary.php");
    
    /* Busca PArametros */
        $sqlpar = 'select * from tbl_parametro ';
        $qrypar = mysql_query($sqlpar,$con);
        $respar = mysql_fetch_array($qrypar);    
    
    /* Informando as credenciais  */ 
        if (!isset($_REQUEST["vendedor"]))
        {
            $psemail = $respar["par_emailpagseguro"];
            $pstoken = $respar["par_tokenpagseguro"];
        }
        else
        {
            $sqlusu = "select * from tbl_usuario where usu_cod = '".$_REQUEST["vendedor"]."'";
            $qryusu = mysql_query($sqlusu,$con); 
            $resusu = mysql_fetch_array($qryusu);                            
            if (($resusu["usu_psemail"]=="")||($resusu["usu_pstoken"]==""))
            {
                $psemail = $respar["par_emailpagseguro"];
                $pstoken = $respar["par_tokenpagseguro"];
            }
            else
            {
                $psemail = $respag["usu_psemail"];
                $pstoken = $respag["usu_pstoken"];
            }        
        }   
    $credentials = new PagSeguroAccountCredentials($psemail,$pstoken);  
      
    /* Tipo de notificação recebida */  
    $type = $_POST['notificationType'];  
      
    /* Código da notificação recebida */  
    $code = $_POST['notificationCode'];  
      
      
    /* Verificando tipo de notificação recebida */  
    if ($type === 'transaction') {  
          
        /* Obtendo o objeto PagSeguroTransaction a partir do código de notificação */  
        $transaction = PagSeguroNotificationService::checkTransaction($credentials,$code);
        
        //Cab Email
        $cabecalho = "From: ".$respar["par_nome"]." <".$respar["par_email"].">\nContent-type: text/html;";
        
        $reference = $transaction->getReference();
        $dt = $transaction->getLastEventDate();
        $status = $transaction->getStatus();          
        
        if (substr($reference,0,1)=="C")
        {   
      		$sqlcomp = "update tbl_compra set
                comp_transacao = '".$transaction->getCode()."',
                comp_situacao = '".$status->getValue()."',
                comp_dttransacao = '".substr($dt,0,10)." ".substr($dt,11,8)."'
                where comp_cod = '".str_replace("C","",$reference)."' and (substring(comp_transacao,1,1) > 0 or comp_transacao is null)"; 
            $qrycomp = mysql_query($sqlcomp,$con);
            
            $assunto = "COMPRA ".str_replace("C","",$reference)." ATUALIZADA";
            $mensagem = "COMPRA ".str_replace("C","",$reference)." ATUALIZADA<br>CREDENCIAIS<br>".$psemail." - ".$pstoken."<br>
                CODE: ".$transaction->getCode()."<br>DATA: ".substr($dt,0,10)." ".substr($dt,11,8)."<br>STATUS: ".$status->getValue();
            //@mail($respar["par_emailadmin"],$assunto,$mensagem,$cabecalho);              
            
            if ($status->getValue()=="3")
            {
                $sqlbils = "update tbl_bilhetes set bil_situacao = 'V' where bil_compra = '".str_replace("C","",$reference)."'";
                $qrybils = mysql_query($sqlbils,$con);      
                
                $assunto = "COMPRA ".str_replace("C","",$reference)." PAGA";
                $mensagem = "COMPRA ".str_replace("C","",$reference)." PAGA<br>CREDENCIAIS<br>".$psemail." - ".$pstoken;
                //@mail($respar["par_emailadmin"],$assunto,$mensagem,$cabecalho);                           
            }   
            if (($status->getValue()=="7")||($status->getValue()=="6"))
            {
                $sqlbils = "update tbl_bilhetes set bil_situacao = 'C' where bil_compra = '".str_replace("C","",$reference)."'";
                $qrybils = mysql_query($sqlbils,$con);
                
                $assunto = "COMPRA ".str_replace("C","",$reference)." CANCELADA";
                $mensagem = "COMPRA ".str_replace("C","",$reference)." CANCELADA<br>CREDENCIAIS<br>".$psemail." - ".$pstoken;
                //@mail($respar["par_emailadmin"],$assunto,$mensagem,$cabecalho);                 
            }         
        } 
        else
        {
      		$sqlcomp = "update tbl_meuspacotes set
                comp_transacao = '".$transaction->getCode()."',
                comp_situacao = '".$status->getValue()."',
                comp_dttransacao = '".substr($dt,0,10)." ".substr($dt,11,8)."'
                where mpac_codigo = '".str_replace("P","",$reference)."' and (substring(comp_transacao,1,1) > 0 or comp_transacao is null)";    
            $qrycomp = mysql_query($sqlcomp,$con);
                
            $assunto = "PACOTE ".str_replace("C","",$reference)." ATUALIZADO";
            $mensagem = "PACOTE ".str_replace("C","",$reference)." ATUALIZADO<br>CREDENCIAIS<br>".$psemail." - ".$pstoken."<br>
                CODE: ".$transaction->getCode()."<br>DATA: ".substr($dt,0,10)." ".substr($dt,11,8)."<br>STATUS: ".$status->getValue();
            //@mail($respar["par_emailadmin"],$assunto,$mensagem,$cabecalho);                              
                
            if ($status->getValue()=="3")
            {
                $assunto = " ".str_replace("C","",$reference)." PAGO";
                $mensagem = "PACOTE ".str_replace("C","",$reference)." PAGO<br>CREDENCIAIS<br>".$psemail." - ".$pstoken;
                //@mail($respar["par_emailadmin"],$assunto,$mensagem,$cabecalho);                           
            }   
            if (($status->getValue()=="7")||($status->getValue()=="6"))
            {
                $assunto = "PACOTE ".str_replace("C","",$reference)." CANCELADO";
                $mensagem = "PACOTE ".str_replace("C","",$reference)." CANCELADO<br>CREDENCIAIS<br>".$psemail." - ".$pstoken;
                //@mail($respar["par_emailadmin"],$assunto,$mensagem,$cabecalho);                 
            }                                    
        } 
        
        $_SESSION["regbils"] = "";
        $_SESSION["datacompra"] = "";
        
        //Redirecionar para página inicial
        ?><meta http-equiv="refresh" content="1;index.php" /><?php
    }  


?>