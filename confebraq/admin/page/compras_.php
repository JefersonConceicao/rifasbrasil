<?php 
include("../class/conexao.php"); 
include("../class/function_pagseguro.php"); 
session_start();

//Enviar e-mail para usuário

if(isset($_POST[usuario])){

    $confirm = mail($_POST[usuario], $_POST[assunto], $_POST[mensagem]);
    if($confirm)
        echo "<script>alert('Mensagem enviada com sucesso');</script>";
    else
        echo "<script>alert('Falha ao enviar mensagem');</script>"; 

}

//fim

//Caso o usuário tenha cadastrado uma conta pagseguro
$dado = DBSelect("Select usu_psemail, usu_pstoken from tbl_usuario WHERE usu_cod = '$_SESSION[usuario]'", $mysqli);
$psemail = $dado[usu_psemail];
$pstoken = $dado[usu_pstoken];

// Atualiza os status de todos os pedidos com base no PagSeguro
if(filter_var($psemail, FILTER_VALIDATE_EMAIL) && strlen($pstoken) > 0){

    error_reporting(0);
    //Primeiro, obtem-se os dados de login do pagseguro do usuário

    $historico_pagseguro = getPSHistory(date("Y-m-d", time()-(86400*30)), date("Y-m-d"), $psemail, $pstoken);
    if($historico_pagseguro)
        foreach($historico_pagseguro->transactions->transaction as $compra){
            
            if($compra->reference > 0){
                if($compra->status == 4 || $compra->status == 3)
                    $sql[] = "UPDATE tbl_bilhetes SET bil_situacao = 'V' WHERE bil_compra = '".$compra->reference."'";
                
                elseif($compra->status == 6 || $compra->status == 7)
                    $sql[] = "UPDATE tbl_bilhetes SET bil_situacao = 'C' WHERE bil_compra = '".$compra->reference."'";
                

                $sql[] = "UPDATE tbl_compra SET comp_transacao = '".$compra->code."', comp_situacao = '".$compra->status."' WHERE comp_cod = '".$compra->reference."'";
            }

            foreach($sql as $code){
                DBExecute($code, $mysqli);
            }
        }
    else
        $erro[] = "Seus e-mail ou token do Pagseguro estão inválidos. <a href='index.php?p=config'>Clique aqui</a> para atualizá-los.";

    error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
}
//Fim


    if ((isset($_REQUEST["liberar"])))
    {
        $liberar = intval($_REQUEST["liberar"]);
        $sqld = "select rifa_dono from tbl_rifas where rifa_cod in (select bil_rifa from tbl_bilhetes where bil_compra = '$liberar')   ";
        $qryd = $mysqli->query($sqld) or die($mysqli->error);
        $dono = $qryd->fetch_assoc();

        if ($_SESSION[admin] == 'S' || ($dono[rifa_dono] == $_SESSION[usuario]))
        {
            $sqll = "
            update tbl_compra 
            set comp_situacao = '4', 
            comp_dttransacao = '".date("Y-m-d H:i:s")."',
            comp_transacao = 'Liberado Manualmente' 
            where comp_cod = '$liberar'";

            $qryl = $mysqli->query($sqll) or die($mysqli->error);
            
            $sqlbils = "
            update tbl_bilhetes 
            set bil_situacao = 'V' 
            where bil_compra = '$liberar'";
            $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);     

            if($qryl && $qrybils) echo "<script>alert('Liberado com sucesso'); location.href='index.php?p=venda';</script>"; 
                     
        }
        else
            echo "<script>alert('Essa venda não pertence a sua rifa.'); location.href='index.php?p=venda';</script>"; 

    }
    if ((isset($_REQUEST["cancelar"])))
    {

        $cancelar = intval($_REQUEST['cancelar']);
        $sqld = "select rifa_dono from tbl_rifas where rifa_cod  in (select bil_rifa from tbl_bilhetes where bil_compra = '$cancelar')";
        $qryd = $mysqli->query($sqld) or die($mysqli->error);
        $dono = $qryd->fetch_assoc();

        if ($_SESSION[admin] == 'S' || ($dono[rifa_dono] == $_SESSION[usuario]))
        {
            $sqll = "update tbl_compra set comp_deletada = 1, comp_transacao = 'Cancelada' where comp_cod = '$cancelar'";
            $qryl = $mysqli->query($sqll) or die($mysqli->error);
            
            $sqlbils = "UPDATE tbl_bilhetes set bil_situacao = 'C' where bil_compra = '$cancelar'";
            $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);      
            
            if($qryl && $qrybils) echo "<script>alert('Cancelada com sucesso'); location.href='index.php?p=venda';</script>";           
        }
        else
        {
            echo "<script>alert('Essa venda não pertence a sua rifa.'); location.href='index.php?p=venda';</script>";
        }
    }  

?>
<div class="col-lg-12 text-left">
	<h3>Vendas</h3>
	<hr>
    <?php if(count($erro) > 0){ ?>
    <div style="padding:10px; margin:10px 0 10px 0;" class="col-lg-12 bg-warning">
        <?php foreach($erro as $msg) echo "<p>$msg</p>"; ?>
    </div>
    <?php } ?>
</div>

<div class="col-lg-12 text-left">
    <form method="POST" action="">
        <div class="col-lg-12">
            <label for="">Filtrar</label>
        </div>
        <div class="col-lg-4 form-group form-inline">
            <p>Período</p>
            <input type="text" value="<?php echo $_POST[dtini]; ?>" name="dtini" class=" datepicker form-control">
            <input type="text" value="<?php echo $_POST[dtfim]; ?>" name="dtfim" class=" datepicker form-control">
        
        </div><!--
        <div class="col-lg-3 form-group">
            <p>Organizar por</p>
            <label for="organizar2"><input type="radio" <?php if($_POST[organizar] != 'r') echo "checked=\"checked\""; ?> value="v"  id="organizar2"  name="organizar" class=""> Vendas</label>
            <label for="organizar1"><input type="radio" <?php if($_POST[organizar] == 'r') echo "checked=\"checked\""; ?> value="r" id="organizar1" name="organizar" class=""> Rifas</label>
            
        </div>-->

        <div class="col-lg-5 form-group">
            <p>Situação do Pagamento</p>
            <label for="pagamento1"><input type="radio" <?php if($_POST[situacao] == '') echo "checked=\"checked\""; ?> id="pagamento1" value="" name="situacao" class=""> Todos </label>
            <label for="pagamento3"><input type="radio" <?php if($_POST[situacao] == '4') echo "checked=\"checked\""; ?> id="pagamento3" value="4" name="situacao" class=""> Pago </label>
             <label for="pagamento4"><input type="radio" <?php if($_POST[situacao] == '6') echo "checked=\"checked\""; ?> id="pagamento4" value="6" name="situacao" class=""> Cancelado </label>
        </div>

        <div class="col-lg-12 text-center"> <button type="submit" name="ok" value="1" class="btn  btn-success">Filtrar</button>  
            <button type="button" onclick="javascript: location.href='index.php?p=venda';" class="btn">Limpar Filtro</button><hr></div>

    </form>
	<?php
	$sqlbil = "select *  FROM tbl_compra where comp_cliente = '$_SESSION[usuario]' order by comp_cod DESC";
    if($_SESSION[admin] == 'S')
        $sqlbil = "select *  FROM tbl_compra order by comp_cod DESC";

    // Filtro

    if(isset($_POST[ok])){

        $sql = "";
        if(strlen($_POST[dtini]) > 0){

            $d = explode("/", $_POST[dtini]);
            $sql .= " AND comp_data >= '$d[2]-$d[1]-$d[0]'";

        }
        if(strlen($_POST[dtfim]) > 0){
            $d = explode("/", $_POST[dtini]);
            $sql .= " AND comp_data <= '$d[2]-$d[1]-$d[0]'";
        }

        $organizar = "";
        if($_POST[organizar] == 'r'){
            $organizar = "ORDER BY b.bil_rifa DESC";
        }else
            $organizar = "ORDER BY b.bil_cod DESC";

        $situacao = "";
        if(strlen($_POST[situacao]) > 0){
            if($_POST[situacao] == 4)
                $situacao = "AND (comp_situacao = '3' or comp_situacao = '4')";

            if($_POST[situacao] == 6)
                $situacao = "AND (comp_situacao = '6' or comp_situacao = '7')";
        }

        $sqlbil = "select *  FROM tbl_compra where comp_cliente = '$_SESSION[usuario]' $sql  $situacao  $organizar
        ";

        if($_SESSION[admin] == 'S')
            $sqlbil = "SELECT comp_cod FROM tbl_compra where 1=1 $sql $situacao $organizar";

    }

    $qrybil = $mysqli->query($sqlbil) or die($mysqli->error);
    $resbil = $qrybil->fetch_assoc();

    if($qrybil->num_rows == 0) echo "<div class=\"col-lg-12 text-center\">Nenhuma venda foi feita</div>";
    else { ?>
	
	<div class="col-lg-12">
	<table class="table-responsive table-bordered table">
                    <tr style="font-weight: bold;">
                        
                        <td width="40%">Rifa</td>
                        <td>Vendedor</td>
                        <td>Bilhetes</td>
                        <td>Valor</td>
                        <td>Situação</td>
                        <td></td>
                    </tr>
                    <?php
                    do
                    {

                    $bilhetes = "";
                    $bil_vend = DBExecute("select * from tbl_bilhetes b, tbl_rifas r where r.rifa_cod = b.bil_rifa AND b.bil_compra = '$resbil[comp_cod]'", $mysqli);
                    $bil_v = $bil_vend->fetch_assoc();

                    $rifa = "";

                    do{
                        $rifa = $bil_v[rifa_titulo];
                        $bilhetes .= str_pad($bil_v[bil_numero], strlen($bil_v[rifa_maxbilhetes])-1, "0", STR_PAD_LEFT).", ";
                    } while( $bil_v = $bil_vend->fetch_assoc());                    
                    $bilhetes = substr($bilhetes, 0, -2);    ?>
                        <tr>
                            <td><?php echo $rifa; ?></td>
                            <td>
                            <?php 

                                $revendedor = DBSelect("Select usu_nome from tbl_usuario where usu_cod = '$resbil[comp_revendedor]'", $mysqli, "usu_nome");
                                echo ($revendedor != '')? $revendedor:"Venda Direta";
                            ?></td>
                            <td><?php echo $bilhetes; ?></td>
                            <td><?php echo "R$ ".number_format($resbil[comp_valortotal],'2',',','.') ?></td>
                            <td>
                            <?php 
                                if ($resbil["comp_situacao"] == "")
                                {
                                    echo "Reservado";
                                }
                                else
                                {
                                    if ($resbil["comp_situacao"] ==  6 || $resbil["comp_situacao"] == 7)
                                    {
                                        echo "Cancelado";    
                                    }
                                    else
                                    {
                                        if ($resbil["comp_situacao"] ==  3 || $resbil["comp_situacao"] == 4)
                                        {
                                            echo "Vendido";    
                                        }    
                                        else
                                        {
                                            echo "Não Encontrado";
                                        }
                                    }    
                                }

                            $sql_usuario = "SELECT usu_email, usu_nome FROM tbl_usuario WHERE usu_cod = '$resbil[comp_cod]' ";
                            $usu_q = $mysqli->query($sql_usuario);
                            $usuario = $usu_q->fetch_assoc();
                            ?>
                            </td>
                            <td class="text-right">
                                <button onclick="javascript: abrirJanela('<?php echo $usuario[usu_email]; ?>', '<?php echo $usuario[usu_nome]; ?>');" class="btn">Enviar E-mail</button>
                                <?php if ($resbil["bil_situacao"] != "C"){ ?>
                                <button onclick="javascript: location.href='index.php?p=venda&cancelar=<?php echo $resbil[comp_cod]; ?>';" class="btn btn-danger">Cancelar Venda</button>
                                <button onclick="javascript: location.href='index.php?p=venda&liberar=<?php echo $resbil[comp_cod]; ?>';" class="btn btn-success">Liberar Manualmente</button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } while ($resbil = $qrybil->fetch_assoc()); ?>
                </table>
            
    </div><?php } ?>
</div>
<div id="email" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Enviar Mensagem</h4>
      </div>
      <form method="post" action="">
      <div class="modal-body">
        
            <div class="col-lg-12">
                <label for="">Para</label>
                <input id="email_usuario" value="" disabled type="text" class="form-control">
            </div>
            <div class="col-lg-12">
                <label for="">Assunto</label>
                <input name="assunto" required value="Contato CONFEBRAQ" type="text" class="form-control">
            </div>
            <div class="col-lg-12">
                <label for="">Mensagem</label>
                <input name="usuario" id="usuario" value="" type="hidden">
                <textarea name="mensagem" required  type="text" class="form-control"></textarea>
            </div>
        
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Enviar</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>
    function abrirJanela(email, nome){

        document.getElementById('email_usuario').value = nome + ' <' + email + '>';
        document.getElementById('usuario').value = email;
        $('#email').modal('show');
    }
</script>