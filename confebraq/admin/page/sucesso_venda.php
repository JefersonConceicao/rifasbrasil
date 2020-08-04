<?php

include("../class/conexao.php");
include("../class/function_limparNumero.php");

$usuario = intval($_GET[usuario]);
$telefone = limparNumero(DBSelect("SELECT usu_celular FROM tbl_usuario where usu_cod='$usuario'", $mysqli, "usu_celular"));

?><div class="col-lg-12">
 <h3>Tudo certo!</h3><hr>
 <?php 
                    if(isset($erro) && count($erro) > 0)
                        foreach($erro as $valor)
                            echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
                    ?>
        
        </div>
               
        <div class="col-lg-12">Venda efetuada com sucesso. O que você deseja fazer agora?
        	<div class="col-lg-12">
        		<div class="spacer"></div>
                <button class="btn btn-success" onclick="javascript:location.href='sms:+55<?php echo $telefone; ?>?body=<?php echo $_SESSION[sms];?>';">Enviar SMS com dados p/ Cliente</button>
	        	<button class="btn" onclick="javascript:location.href='index.php?p=revenda';">Revender uma Rifa</button>
	        	<button class="btn" onclick="javascript:location.href='index.php?p=debitor';">Pagar o Promotor da Rifa</button>
	        	<button class="btn" onclick="javascript:location.href='../index.php?p=inicial';">Página Inicial</button>
        		<div class="spacer"></div>
        	</div>	
        </div>
