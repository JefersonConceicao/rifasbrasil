<?php 

if(!isset($_SESSION)) session_start();

if($_POST['gerarRelatorio']){

    require_once '../dompdf/lib/html5lib/Parser.php';
    require_once '../dompdf/lib/php-font-lib/src/FontLib/Autoloader.php';
    require_once '../dompdf/lib/php-svg-lib/src/autoload.php';
    require_once '../dompdf/src/Autoloader.php';

    Dompdf\Autoloader::register();

}

use Dompdf\Dompdf;

if(isset($_POST['gerarRelatorio'])){

    include('../simple_html_dom/simple_html_dom.php');

    ob_start();
    include('relatorio.php');
    $conteudo = ob_get_contents();
    ob_end_clean();

    $nome = 'rvenda_' . md5(time());

    //echo $html;

    // instantiate and use the dompdf class
    $dompdf = new Dompdf(array('enable_remote' => true));
    $dompdf->loadHtml($conteudo);

    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'landscape');

    // Render the HTML as PDF
    $dompdf->render();

    $output = $dompdf->output();

    file_put_contents("../relatorio/{$nome}.pdf", $output);

    $resultado = array('erro'=>0, "msg"=>"{$nome}.pdf");

    die(json_encode($resultado));

}

if(isset($_POST['montar_carrinho'])) {

    $rifa_cod = intval($_POST['montar_carrinho']);
    $_SESSION['rifa_dezena_bolao'] = true;
    $_SESSION['qr_order'] = true;
    $_SESSION['carrinho_admin'] = array();
    
    if(!isset($_SESSION['carrinho_admin'][$rifa_cod]))
        $_SESSION['carrinho_admin'][$rifa_cod] = Array();

    foreach(explode(',', $_POST['bil']) as $v){
        $_SESSION['carrinho_admin'][$rifa_cod][] = intval($v);
    }

    die('ok');

}

if(isset($_POST['repetir_venda'])){
    
    include("../../class/conexao.php"); 
    $con = new Conexao();
    $bilhetes = array();
    if(is_array($_POST['repetir_venda'])){
        $compra = intval($_POST['repetir_venda'][0]);
        foreach($_POST['repetir_venda'] as $ven_codigo){
            $bilhetes[]       = $con
                ->select('*')
                ->from('tbl_bilhetes')
                ->where("bil_compra = '$ven_codigo'")
                ->executeNGet();
        }
        $_SESSION['rifa_dezena_bolao'] = true;
    }else{
        $compra = intval($_POST['repetir_venda']);
        $bilhetes[]       = $con
        ->select('*')
        ->from('tbl_bilhetes')
        ->where("bil_compra = '$compra'")
        ->executeNGet();
    }

    // descobre quem era o dono original da venda
    $rifa = $con
    ->select('*')
    ->from('tbl_rifas')
    ->where("rifa_cod = (select bil_rifa from tbl_bilhetes where bil_compra = '$compra' LIMIT 1)")
    ->limit(1)
    ->executeNGet();

    if(!is_array($_POST['repetir_venda'])){
        $_SESSION['rifa_dezena_bolao'] = $rifa['dezena_bolao'];
    }

    $maxbilhetes = strlen($rifa['rifa_maxbilhetes'])-1;

    $venda          = $con
        ->select('*')
        ->from('tbl_compra, tbl_usuario')
        ->where("comp_cod = '$compra' and usu_cod = comp_cliente")
        ->limit(1)
        ->executeNGet();

    $bilhete_pedido = $con
        ->select('*')
        ->from('reserva')
        ->where("revendedor = '{$venda['comp_revendedor']}' and rifa = (select bil_rifa from tbl_bilhetes where bil_compra = '$compra' LIMIT 1)")
        ->executeNGet();

    // calcula se a opcao bilhetes pedidos foi marcada na venda ao 
    // conferir se os bilhetes do pedido estão na tabela reserva
    $bilhetes_pedidos = array();
    if(is_array($bilhete_pedido) and count($bilhete_pedido) > 0){
        foreach($bilhete_pedido as $bp){
            $bilhetes_pedidos[$bp['bilhete']] = true;
        }
    }

    unset($_SESSION['carrinho_admin'], $_SESSION['compra_ja_feita'], $_SESSION['sms']);

    $_SESSION['repetir_venda'] = true;
    $_SESSION['carrinho_admin'] = array();

    $indice = intval($_POST['rifa']);

    $bilhetes_batem = 0;
    foreach($bilhetes as $k=>$bilh){
        foreach($bilh as $bil){

            if(!$indice) $indice = $bil['bil_rifa'];

            if(!isset($_SESSION['carrinho_admin'][$indice]))
                $_SESSION['carrinho_admin'][$indice] = array();

            if($bil['bil_numero'] > $rifa['rifa_maxbilhetes'])
                die(json_encode(array('erro'=>1, 'msg'=>"A rifa de destino só comporta bilhetes até o número {$rifa['rifa_maxbilhetes']}.")));

            $_SESSION['carrinho_admin'][$indice][] = intval($bil['bil_numero']);
            
            if($bilhetes_pedidos[$indice] === true)
                $bilhetes_batem++;

        }
    }

    $_SESSION['cpf']       = $venda['usu_cpf'];
    $_SESSION['nome']       = $venda['usu_nome'];
    $_SESSION['telefone']   = $venda['usu_celular'];
    $_SESSION['regiao']     = $venda['usu_regiao'];

    $_SESSION['bilhete_pedido'] = false;
    if($bilhetes_batem == count($bilhetes))
        $_SESSION['bilhete_pedido'] = true;

    $_SESSION['cliente_ja_cadastrado'] = $venda['comp_cliente'];

    $_SESSION['revendedor_admin'] = '';
    if($venda['comp_revendedor'])
        $_SESSION['revendedor_admin'] = $venda['comp_revendedor'];
    
    $_SESSION['tipo_venda'] = ($venda['comp_status_revenda'])? 'paga':'naopaga';

    $_SESSION['proximo_pagamento'] = '';
    if($venda['comp_parcelamento_data'])
        $_SESSION['proximo_pagamento'] = strtotime($venda['comp_parcelamento_data']);

    $_SESSION['entrada'] = $venda['comp_parcelamento_entrada'];    

    $_SESSION['saved_post_enabled'] = true;

    die(json_encode(array_merge(array('erro'=>0, 'msg'=>"Tudo certo!"))));

}

if($_SESSION['saved_post_enabled']) {
    $_POST = $_SESSION['saved_post'];
    unset($_SESSION['saved_post_enabled'], $_SESSION['saved_post']);
}

if(isset($_POST['gerarComprovantes'])){
    
    include("../../class/conexao.php"); 
    include("../../class/function_limparNumero.php");
    include("../../class/function_primeiroNome.php");

    function formataTelefone($telefone) {
        $telefone = str_replace("-", "", $telefone);
        return "(".substr($telefone, 0, 2).") ".substr($telefone, 2, -4)."-".substr($telefone, -4);
    }

    $comprovantes = explode('-', $_POST['comprovantes']);
    $conteudo = "";

    foreach($comprovantes as $comp){

        if($comp > 0){
            
            $compra   = DBSelect("SELECT * FROM tbl_compra where comp_cod ='".intval($comp)."'", $mysqli);
            $usuario  = DBSelect("SELECT * FROM tbl_usuario where usu_cod='{$compra['comp_cliente']}'", $mysqli);
            $vendedor = DBSelect("SELECT * FROM tbl_usuario where usu_cod='{$compra['comp_revendedor']}'", $mysqli);

            $bilhetes = db_select($mysqli, "SELECT * FROM tbl_bilhetes where bil_compra='{$compra['comp_cod']}'");
            $rifa_cod = $bilhetes[0]['bil_rifa'];
            $rifa = DBSelect("SELECT r.*, u.usu_nome, u.usu_celular 
                FROM tbl_rifas r, tbl_usuario u 
                where u.usu_cod = r.rifa_dono and rifa_cod='$rifa_cod'", $mysqli);

            $bilhete_str = array();
            foreach ($bilhetes as $value) {
                $rifa_cod = $value['bil_rifa'];
                $bilhete_str[] = str_pad($value['bil_numero'], strlen($rifa['rifa_maxbilhetes'])-1, '0', STR_PAD_LEFT);
            }
            $bilhete_str = implode("-",$bilhete_str);

            $dataSorteio = date('d/m/Y', strtotime($rifa['rifa_dtsorteio']));
            $data = date('d/m/Y H:i', strtotime($compra['comp_data']));
            $telefoneFormatado = "(".substr($usuario['usu_celular'], 0, 2).") ".substr($usuario['usu_celular'], 2, -4)."-".substr($usuario['usu_celular'], -4);
            $telefone = $usuario['usu_celular'];

            if(strtolower($_POST['gerarComprovantes']) == 'pc'){

                $conteudo .= "<p>WWW.RIFASBRASIL.COM.BR<br>BILHETE DIGITAL</p>";
                $conteudo .= "<p>TITULO DA RIFA: <b>{$rifa['rifa_titulo']}</b><br>";
                $conteudo .= "Sorteio: <b>{$dataSorteio}</b><br>";
                $conteudo .= "Vendedor: <b>{$vendedor['usu_nome']}</b><br>";
                $conteudo .= "Valor do Bilhete: <b>R$ ".number_format($rifa['rifa_valorbilhete'],2,',','.')."</b></p>";

                $conteudo .= "<p>Data/Hora: <b>{$data}</b><br>";
                $conteudo .= "Nome: <b>{$usuario['usu_nome']}</b><br>";
                $conteudo .= "Tel: <b>{$telefoneFormatado}</b><br>";
                $conteudo .= "Bairro/Região: <b>{$usuario['usu_regiao']}</b><br>";
                $conteudo .= "BILHETE(S):</p>";
                $conteudo .= "<p>[ $bilhete_str ] </p>";

                $conteudo .= "<p>TOTAL: <b>R$ ".number_format($compra['comp_valortotal'],2,',','.')."</b><br>";
                if($compra['comp_status_revenda'] == 1) 
                    $conteudo .= "PAGAMENTO: <b>SIM - PAGO</b><br>";
                else
                    $conteudo .= "PAGAMENTO: <B>AGUARDANDO PAGAMENTO </b><br>";

                if($compra['comp_parcelamento_entrada'] > 0 && $compra['comp_parcelamento_entrada'] < $compra['comp_valortotal']){
                
                    $conteudo .= "ENTRADA: <b>R$ ". number_format($compra['comp_parcelamento_entrada'],2,',','.')."</b><br>";
                    $conteudo .= "VALOR RESTANTE: <b>R$ ".number_format($compra['comp_valortotal']-$compra['comp_parcelamento_entrada'], 2, ',', '.')."</b><br>";
                    $conteudo .= "Data PROX. PGTO: <b>" . date('d/m/Y', strtotime($compra['comp_parcelamento_data']))."<BR>";

                }

                $conteudo .= "</p><p>A responsabilidade de entrega do premio será do promotor desta ACAO ENTRE AMIGOS</p>";
                $conteudo .= "<p>PROMOTOR: <b>{$rifa['usu_nome']}</b><br>";
                $conteudo .= "TEL: <b>".formataTelefone($rifa['usu_celular'])."</b></p>";

                $conteudo .= "<p>Obrigado por participar de nossa ACAO ENTRE AMIGOS! Sendo o ganhador você recebera um SMS lhe avisando!</p>";
                $conteudo .= "<p><hr></p>";

            }else{ ?>

WWW.RIFASBRASIL.COM.BR
BILHETE DIGITAL
-------------------------------
TITULO DA RIFA:
<?= $rifa['rifa_titulo'];?> 
Sorteio: <?= $dataSorteio;?> 
VENDEDOR: <?= $vendedor['usu_nome'];?> 
VALOR DO BILHETE: R$ <?= number_format($rifa['rifa_valorbilhete'],2,',','.');?> 
-------------------------------
Data/Hora: <?= $data;?> 
NOME: <?php echo $usuario['usu_nome'];?> 
TEL: <?= $telefoneFormatado;?> 
BAIRRO/REGIAO: <?php echo $usuario['usu_regiao'];?> 
BILHETE(S):

[ <?= $bilhete_str; ?> ] 

TOTAL: R$ <?php echo number_format($compra['comp_valortotal'],2,',','.'); ?> 
PAGAMENTO: <?= ($compra['comp_status_revenda'] == 1) ? 'SIM - PAGO': 'AGUARDANDO PAGAMENTO';?>
<?php if($compra['comp_parcelamento_entrada'] > 0 && $compra['comp_parcelamento_entrada'] < $compra['comp_valortotal']){ ?>

ENTRADA: <?php echo number_format($compra['comp_parcelamento_entrada'],2,',','.'); ?>

VALOR RESTANTE: R$ <?= number_format($compra['comp_valortotal']-$compra['comp_parcelamento_entrada'], 2, ',', '.'); ?>

Data PROX. PGTO: <?= date('d/m/Y', strtotime($compra['comp_parcelamento_data'])); ?><?php } ?>

-------------------------------
A responsabilidade de entrega do premio será do promotor desta ACAO ENTRE AMIGOS 
 
PROMOTOR: <?= $rifa['usu_nome'] ?> 
TEL: <?= formataTelefone($rifa['usu_celular']); ?> 
 
Obrigado por participar de nossa ACAO ENTRE AMIGOS! Sendo o ganhador você recebera um SMS lhe avisando! 

            <?php }

        }

    }

    if(strtolower($_POST['gerarComprovantes']) == 'pc')
        die($conteudo);
    else
        die();

}

include("../class/conexao.php"); 
include("../class/function_pagseguro.php"); 

//Enviar e-mail para usuário
if(isset($_POST['usuario'])){
    $confirm = mail($_POST['usuario'], $_POST['assunto'], $_POST['mensagem']);
    if($confirm)
        echo "<script>alert('Mensagem enviada com sucesso');</script>";
    else
        echo "<script>alert('Falha ao enviar mensagem');</script>"; 
}
//fim
//Caso o usuário tenha cadastrado uma conta pagseguro
$dado = DBSelect("Select usu_psemail, usu_mensalista, usu_pstoken from tbl_usuario WHERE usu_cod = '{$_SESSION['usuario']}'", $mysqli);
$psemail = $dado['usu_psemail'];
$pstoken = $dado['usu_pstoken'];
$isMENSALISTA = intval($dado['usu_mensalista']);


if(filter_var($psemail, FILTER_VALIDATE_EMAIL) && strlen($pstoken) > 0){
    error_reporting(0);
    //Primeiro, obtem-se os dados de login do pagseguro do usuário
    $historico_pagseguro = getPSHistory(date("Y-m-d", time()-(86400*30)), date("Y-m-d"), $psemail, $pstoken);
    $sql = array();
    if($historico_pagseguro){
        foreach($historico_pagseguro->transactions->transaction as $compra){
           
            
            if($compra->reference > 0){
                if($compra->status == 4 || $compra->status == 3)
                    $sql[] = "UPDATE tbl_bilhetes SET bil_situacao = 'V' WHERE bil_compra = '".$compra->reference."'";
                
                elseif($compra->status == 6 || $compra->status == 7)
                    $sql[] = "UPDATE tbl_bilhetes SET bil_situacao = 'C' WHERE bil_compra = '".$compra->reference."'";
                
                $sql[] = "UPDATE tbl_compra SET comp_transacao = '".$compra->code."', comp_situacao = '".$compra->status."' WHERE comp_cod = '".$compra->reference."'";
            }
        }
    }else
        $erro[] = "Seus e-mail ou token do Pagseguro estão inválidos. <a href='index.php?p=config'>Clique aqui</a> para atualizá-los.";
    if(count($sql) > 0)
                foreach($sql as $code){
                    DBExecute($code, $mysqli);
                }
    
    error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
}
//Fim




if(isset($_GET['cancelarVendas'])){
    $recebido = explode('-', $_GET["cancelarVendas"]);
    $_POST['ok'] = 1;
    foreach($_GET as $k=>$v){
        $_POST[$k]= $v;
    }
    foreach($recebido as $cancelar){
        if($cancelar > 0){
            //var_dump($rec);
            
            $sqld = "select rifa_dono from tbl_rifas where rifa_cod  in (select bil_rifa from tbl_bilhetes where bil_compra = '$cancelar')";
            $qryd = $mysqli->query($sqld) or die($mysqli->error);
            $dono = $qryd->fetch_assoc();
            if ($_SESSION['admin'] == 'S' || ($dono['rifa_dono'] == $_SESSION['usuario']))
            {
                $sqll = "update tbl_compra set comp_deletada = 1, comp_situacao = 7, comp_transacao = 'Cancelada' where comp_cod = '$cancelar'";
                $qryl = $mysqli->query($sqll) or die($mysqli->error);
                
                $sqlbils = "DELETE FROM tbl_bilhetes where bil_compra = '$cancelar'";
                $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);      
                
                //if($qryl && $qrybils) echo "<script>alert('Cancelada com sucesso'); location.href='index.php?p=venda';</script>";
                
            }
            else
            {
                echo "<script>alert('Essa venda não pertence a sua rifa.'); location.href='index.php?p=venda';</script>";
            }
        }
    }
    echo "<script>alert('Cancelada com sucesso');</script>";
}
if(isset($_GET['marcarComoPago'])){
    $recebido = explode('-', $_GET["marcarComoPago"]);
    $_POST['ok'] = 1;
    foreach($_GET as $k=>$v){
        $_POST[$k]= $v;
    }
    foreach($recebido as $rec){
        //($_SESSION['admin'] == 'S' || ($dono['rifa_dono'] == $_SESSION['usuario']))
        if($rec > 0 && $_SESSION['admin'] == 'S'){
            //var_dump($rec);
            
            $sql = "UPDATE tbl_compra 
                    set comp_situacao = '',
                    comp_status_revenda = '1', 
                    comp_dttransacao = '".date("Y-m-d H:i:s")."'
                    where comp_cod = '$rec'";
            $qryl = $mysqli->query($sql) or die($mysqli->error);
                    
            $sqlbils = "UPDATE tbl_bilhetes 
                    set bil_situacao = 'P' 
                    where bil_compra = '$rec'";
            $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);
        }else if($rec > 0){
            $sql = "UPDATE tbl_compra 
                    set comp_situacao = '',
                    comp_status_revenda = '1', 
                    comp_dttransacao = '".date("Y-m-d H:i:s")."'
                    where comp_cod IN (select bil_compra from tbl_bilhetes where bil_compra = '$rec' and bil_rifa IN (select rifa_cod from tbl_rifas where rifa_dono = '{$_SESSION['usuario']}'))";
            $qryl = $mysqli->query($sql) or die($mysqli->error);
                    
            $sqlbils = "UPDATE tbl_bilhetes 
                    set bil_situacao = 'P' 
                    where bil_compra = '$rec' and bil_rifa IN (select rifa_cod from tbl_rifas where rifa_dono = '{$_SESSION['usuario']}')";
            $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);
        }
    }
}
if ((isset($_REQUEST["liberar"])))
    {
        $_POST['ok'] = 1;
        foreach($_GET as $k=>$v){
            $_POST[$k]= $v;
        }
           
        if(strpos($_REQUEST['liberar'], '-') === false){
            $liberar = intval($_REQUEST["liberar"]);
            $sqld = "select rifa_dono from tbl_rifas where rifa_cod in (select bil_rifa from tbl_bilhetes where bil_compra = '$liberar')   ";
            $qryd = $mysqli->query($sqld) or die($mysqli->error);
            $dono = $qryd->fetch_assoc();
            if ($_SESSION['admin'] == 'S' || ($dono['rifa_dono'] == $_SESSION['usuario']))
            {
                $sqll = "
                update tbl_compra 
                set comp_situacao = '4', 
                comp_dttransacao = '".date("Y-m-d H:i:s")."',
                comp_transacao = 'Liberado Manualmente',
                comp_status_revenda = NULL
                where comp_cod = '$liberar'";
                $qryl = $mysqli->query($sqll) or die($mysqli->error);
                
                $sqlbils = "
                update tbl_bilhetes 
                set bil_situacao = 'V' 
                where bil_compra = '$liberar'";
                $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);     
                if($qryl && $qrybils) echo "<script>alert('Liberado com sucesso'); location.href='index.php?p=venda';</script>"; 
                exit();
                         
            }
            else
                echo "<script>alert('Essa venda não pertence a sua rifa.'); location.href='index.php?p=venda';</script>"; 
        }else{
            $compras = explode('-', $_REQUEST['liberar']);
            foreach($compras as $c){
                if($c > 0){
                    $liberar = intval($c);
                    $sqld = "select rifa_dono from tbl_rifas where rifa_cod in (select bil_rifa from tbl_bilhetes where bil_compra = '$liberar')   ";
                    $qryd = $mysqli->query($sqld) or die($mysqli->error);
                    $dono = $qryd->fetch_assoc();
                    
                    if ($_SESSION['admin'] == 'S' || ($dono['rifa_dono'] == $_SESSION['usuario']))
                    {
 
                        $sqll = "
                        update tbl_compra 
                        set comp_situacao = '4', 
                        comp_dttransacao = '".date("Y-m-d H:i:s")."',
                        comp_transacao = 'Liberado Manualmente',
                        comp_status_revenda = NULL
                        where comp_cod = '$liberar'";
                        $qryl = $mysqli->query($sqll) or die($mysqli->error);
                        
                        $sqlbils = "
                        update tbl_bilhetes 
                        set bil_situacao = 'V' 
                        where bil_compra = '$liberar'";
                        $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);     
                     
                                 
                    }
                    else
                        echo "<script>alert('Essa venda não pertence a sua rifa.'); location.href='index.php?p=venda';</script>"; 
                    
                }
            }
        }
    }
    if(isset($_GET['marcar_pago'])){
        $comp_cod = intval($_GET['marcar_pago']);
        $mysqli->query("update tbl_compra SET comp_situacao = '', comp_status_revenda = 1 where comp_cod = '$comp_cod'") or die($mysqli->error);    
        $sqlbils = "UPDATE tbl_bilhetes 
                    set bil_situacao = 'P' 
                    where bil_compra = '$comp_cod'";
        $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);
        die("<script>location.href='index.php?p=sucesso_venda&comp=$comp_cod&usuario=".$_GET['usuario']."';</script>");
    }
    if ((isset($_REQUEST["cancelar"])))
    {
        $cancelar = intval($_REQUEST['cancelar']);
        $sqld = "select rifa_dono from tbl_rifas where rifa_cod  in (select bil_rifa from tbl_bilhetes where bil_compra = '$cancelar')";
        $qryd = $mysqli->query($sqld) or die($mysqli->error);
        $dono = $qryd->fetch_assoc();
        if ($_SESSION['admin'] == 'S' || ($dono['rifa_dono'] == $_SESSION['usuario']))
        {
            $sqll = "update tbl_compra set comp_deletada = 1, comp_situacao = 7, comp_transacao = 'Cancelada' where comp_cod = '$cancelar'";
            $qryl = $mysqli->query($sqll) or die($mysqli->error);
            
            $sqlbils = "DELETE FROM tbl_bilhetes where bil_compra = '$cancelar'";
            $qrybils = $mysqli->query($sqlbils) or die($mysqli->error);      
            
            if($qryl && $qrybils) echo "<script>alert('Cancelada com sucesso'); location.href='index.php?p=venda';</script>";
            exit();           
        }
        else
        {
            echo "<script>alert('Essa venda não pertence a sua rifa.'); location.href='index.php?p=venda';</script>";
        }
    }  

?>
<style>
    .bootstrap-select.btn-group .dropdown-menu {
        margin-left: 100%;
    }
    .table-responsive {
  overflow-y: visible !important;
}
.dropup .dropdown-menu, .navbar-fixed-bottom .dropdown .dropdown-menu {
    top: auto;
    bottom: -500%;
    margin-bottom: 2px;
    margin-left: -100%;
}

.removerRelatorio .btn{
    margin-bottom:5px;
}
</style>
<div class="col-lg-12 text-left">
	<h3>Vendas</h3>
	<hr>
    <?php if(isset($erro)) if(count($erro) > 0){ ?>
    <div style="padding:10px; margin:10px 0 10px 0;" class="col-lg-12 bg-warning">
        <?php foreach($erro as $msg) echo "<p>$msg</p>"; ?>
    </div>
    <?php } ?>
</div>
<div class="col-lg-12 text-left">
    <?php if(!isset($_POST['imprimir_vendas'])){ ?>
    <form method="POST" id="formulario_f" action="index.php?p=venda">
        <div class="col-lg-12">
            <label for="">Filtrar</label>
        </div>
        <div class="col-md-2 form-group form-inline">
            <p>Período</p>
            <input type="text" value="<?php if(isset($_POST['dtini'])) echo $_POST['dtini']; ?>" name="dtini" class=" datepicker form-control">
            <input type="text" value="<?php if(isset($_POST['dtfim'])) echo $_POST['dtfim']; ?>" name="dtfim" class=" datepicker form-control">
        
        </div><!--
        <div class="col-lg-3 form-group">
            <p>Organizar por</p>
            <label for="organizar2"><input type="radio" <?php if($_POST[organizar] != 'r') echo "checked=\"checked\""; ?> value="v"  id="organizar2"  name="organizar" class=""> Vendas</label>
            <label for="organizar1"><input type="radio" <?php if($_POST[organizar] == 'r') echo "checked=\"checked\""; ?> value="r" id="organizar1" name="organizar" class=""> Rifas</label>
            
        </div>-->
        <div class="col-md-2 form-group">
            <p>Situação do Pagamento</p>
            <select name="situacao" id="" class="form-control">
                <option value="">Todos</option>
                <option <?php if(isset($_POST['situacao'])){ if($_POST['situacao'] == '4') echo "selected";  } ?> value="4">Pago</option>
                <option <?php if(isset($_POST['situacao'])){ if($_POST['situacao'] == '6') echo "selected";  } ?> value="6">Cancelado</option>
                <option <?php if(isset($_POST['situacao'])){ if($_POST['situacao'] == '-1') echo "selected"; }  ?> value="-1">Reservado</option>
                <option <?php if(isset($_POST['situacao'])){ if($_POST['situacao'] == '-2') echo "selected"; }  ?> value="-2">à Receber</option>
                <option <?php if(isset($_POST['situacao'])){ if($_POST['situacao'] == '3') echo "selected";  } ?> value="3">Parcelado</option>

            </select>
            
        </div>
        <div class="col-md-2">
            <p>Por Rifa</p>
            <?php
            if($_SESSION['admin'] == 'S')
                $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where u.usu_cod = r.rifa_dono order by r.rifa_cod DESC";
            else
                $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where (u.usu_cod = r.rifa_dono and r.rifa_dono = '{$_SESSION['usuario']}') or r.rifa_cod in (SELECT rifa_cod from tbl_revendedor where usu_cod = '{$_SESSION['usuario']}' or pai = '{$_SESSION['usuario']}') group by r.rifa_cod order by r.rifa_cod DESC";
    $consulta = $mysqli->query($rifas_do_usuario) or die($mysqli->error);
    $assoc    = $consulta->fetch_assoc();
    
    ?>
            <select name="por_rifa" id="por_rifa" class="form-control">
                <option value="">Selecione a Rifa</option>
                <?php do{ ?>
                <option <?php if(time() >= strtotime($assoc['rifa_dtsorteio'])+86400) echo ' data-remove="true" '; if(isset($_POST['por_rifa']) && $_POST['por_rifa'] == $assoc['rifa_cod']) echo 'selected'; ?> value="<?= $assoc['rifa_cod']; ?>"><?= $assoc['rifa_cod']; ?> - <?= $assoc['rifa_titulo']; ?></option>
                <?php }while($assoc = $consulta->fetch_assoc()); ?>
            </select>
        
            <p>Filtros</p>
            <input placeholder="Nome, telefone, revendedor ou bairro/região" type="text" class="form-control" name="filtrar_por" value="<?php if(isset($_POST['filtrar_por'])) echo $_POST['filtrar_por']; ?>">
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <button type="submit" name="ok" value="1" class="btn form-control btn-default">Filtrar</button> 
            </div>

            <div class="form-group">
                <button type="button" class="btn form-control btn-primary" onclick="gerarRelatorio();">Gerar Relatório</button>
            </div>

            <div class="form-group">
                <button type="button" class="btn form-control btn-primary" onclick="gerarRelatorio(1);">REL. Totais</button>
            </div>
            
            <div class="form-group">
                <button type="button" onclick="javascript: location.href='index.php?p=venda';" class="btn btn-defaut form-control">Limpar Filtro</button>
            </div>
            
            <div class="form-group">
                <?php  if($_POST){ ?>
                <button type="submit" name="imprimir_vendas" value="true" class="btn btn-default">Imprimir</button>
                <?php } ?>
            </div>
            
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="">Supervisores</label>
                <select data-show-subtext="false" class="selectpicker form-control" data-live-search="true" name="por_rifa2" id="por_rifa2" onchange="mudarSupervisor(this.value);">
                    <option value="">Selecione a Rifa</option>
                    <?php 
                    $consulta = $mysqli->query($rifas_do_usuario) or die($mysqli->error);
    $assoc    = $consulta->fetch_assoc();
                    do{ ?>
                    <option disabled <?php if(isset($_POST['rifa']) && $_POST['por_rifa'] == $assoc['rifa_cod']) echo 'selected'; ?> value="<?= $assoc['rifa_cod']; ?>"><?= $assoc['rifa_cod']; ?> - <?= $assoc['rifa_titulo']; ?></option>
                    <?php }while($assoc = $consulta->fetch_assoc()); ?>
                </select>
            </div>
        </div>

        <div class="clearfix"></div>
        <div class="col-lg-12">
            <div class="form-group">
                <hr>
            </div>
        </div>

    </form>
    <?php } ?>
	<?php
    /* script de paginacao */
    if($_SESSION['admin'] == 'S')
        $sql = '';
    else
        $sql = "and r.rifa_dono = '{$_SESSION['usuario']}'";
    
    $order_by = "order by b.bil_compra desc";
    if(isset($_POST['ordenar_tipo']) or isset($_POST['ordenar_campo'])){
        if($_POST['ordenar_tipo'] && $_POST['ordenar_campo']){
            $order_by = " order by {$_POST['ordenar_campo']} {$_POST['ordenar_tipo']}";
        }
    }

    $exec = $mysqli->query("SELECT * from tbl_revendedor WHERE pai = '{$_SESSION['usuario']}'") or die($mysqli->error);
    $dados = $exec->fetch_assoc();

    $lista_de_filhos = array();
    if($exec->num_rows){
        do{
            $lista_de_filhos[] = $dados['usu_cod'];
        }while($dados = $exec->fetch_assoc());
        $lista_de_filhos = ' OR c.comp_revendedor IN ('.implode(',', $lista_de_filhos).') ';
    }else
        $lista_de_filhos = '';


	$sqlbil = "
        SELECT 
            r.rifa_cod,
            r.rifa_maxbilhetes, 
            r.rifa_titulo,
            r.rifa_dtsorteio,
            r.dezena_bolao,
            c.comp_situacao,
            c.comp_data,
            c.comp_serie,
            c.comp_cod,
            c.comp_parcelamento_entrada,
            c.comp_parcelamento_data,
            r.rifa_finalizar,
            c.comp_status_revenda,
            c.comp_cliente as dono, 
            u.usu_cod as cliente_cod,
            u.usu_nome as cliente,
            u.usu_regiao as usu_regiao,
            u.usu_celular as tel_cliente,
            rev.usu_nome as revendedor,
            c.comp_revendedor,
            b.bil_compra as compra, 
            c.comp_situacao, 
            c.comp_valortotal 
        FROM 
            tbl_bilhetes b, 
            tbl_compra c left join tbl_usuario rev on rev.usu_cod = c.comp_revendedor,
            tbl_usuario u,
            tbl_rifas r 
        WHERE 
            (r.rifa_dono = '{$_SESSION['usuario']}' OR c.comp_revendedor = '{$_SESSION['usuario']}' $lista_de_filhos)
            and u.usu_cod = c.comp_cliente
            and r.rifa_cod = b.bil_rifa 
            and c.comp_cod = b.bil_compra     
        GROUP BY b.bil_compra 
        $order_by
        ";

    if($_SESSION['admin'] == 'S'){

        $sqlbil = " SELECT 
        r.rifa_cod,
        r.rifa_maxbilhetes, 
        r.rifa_dtsorteio,
        r.rifa_titulo, 
        r.dezena_bolao,
        c.comp_cod,
        c.comp_data,
        r.rifa_finalizar,
        c.comp_status_revenda,
        c.comp_parcelamento_entrada,
        c.comp_parcelamento_data,
        c.comp_situacao,
        c.comp_serie,
        c.comp_revendedor,
        c.comp_cliente as dono, 
        u.usu_cod as cliente_cod,
        u.usu_nome as cliente,
        u.usu_regiao as usu_regiao,
        u.usu_celular as tel_cliente,
        rev.usu_nome as revendedor, 
        b.bil_compra as compra, 
        c.comp_situacao, 
        c.comp_valortotal
        from tbl_bilhetes b, 
        tbl_compra c  left join tbl_usuario rev  on rev.usu_cod = c.comp_revendedor,
        tbl_rifas r,
        tbl_usuario u
        where r.rifa_cod = b.bil_rifa 
        and (r.rifa_dono = '{$_SESSION['usuario']}' OR c.comp_revendedor = '{$_SESSION['usuario']}' $lista_de_filhos)
        and c.comp_cod = b.bil_compra
        and u.usu_cod = c.comp_cliente
        group by b.bil_compra  
        $order_by
        ";

        if(count($_POST) == 0){
            $sqlbil = "select * FROM tbl_rifas where rifa_cod < 0";
        }

    }



    // Filtro
    if(isset($_POST['ok']) || isset($_POST['imprimir_vendas'])){
        $rifa_cod = "";
        if($_POST['por_rifa'] > 0){
            $rifa_cod = " and r.rifa_cod = '".intval($_POST['por_rifa'])."' ";
        }
        $sql = "";
        if(isset($_POST['dtini'])) if(strlen($_POST['dtini']) > 0){
            $d = explode("/", $_POST['dtini']);
            $sql .= " AND c.comp_data >= '$d[2]-$d[1]-$d[0]'";
        }
        if(isset($_POST['dtini'])) if(strlen($_POST['dtfim']) > 0){
            $d = explode("/", $_POST['dtfim']);
            $sql .= " AND c.comp_data <= '$d[2]-$d[1]-$d[0]'";
        }
        /*$organizar = "";
        if($_POST[organizar] == 'r'){
            $organizar = "ORDER BY b.bil_rifa DESC";
        }else
            $organizar = "ORDER BY b.bil_cod DESC";*/
        $situacao = "";
        if(strlen($_POST['situacao']) > 0){
            if($_POST['situacao'] == 4)
                $situacao = "AND (c.comp_situacao = '3' or c.comp_situacao = '4' /*or c.comp_status_revenda = 1*/)";
            if($_POST['situacao'] == 6)
                $situacao = "AND (c.comp_situacao = '6' or c.comp_situacao = '7')";
            if($_POST['situacao'] == -1)
                $situacao = "AND (c.comp_situacao = '')";
            if($_POST['situacao'] == -2)
                $situacao = "AND (c.comp_status_revenda = '0' and c.comp_situacao <> '6' and c.comp_situacao <> '7')";
            if($_POST['situacao'] == -3)
                $situacao = "AND (c.comp_parcelamento_entrada > 0)";
            
        }
        $sql_filtrar_por = "";
        if($_POST['filtrar_por']){
            $filtrar_por = $mysqli->real_escape_string($_POST['filtrar_por']);
            $sql_filtrar_por = " AND (u.usu_nome like '%".$filtrar_por."%' 
                or u.usu_celular like '%".$filtrar_por."%' or u.usu_regiao like '%".$filtrar_por."%' or rev.usu_nome like '%".$filtrar_por."%')";
        }
        $sqlbil = "
            SELECT 
            r.rifa_cod,
            r.rifa_maxbilhetes, 
            r.rifa_titulo, 
            r.rifa_dtsorteio,
            r.dezena_bolao,
            c.comp_situacao,
            c.comp_serie,
            c.comp_cod,
            c.comp_data,
            c.comp_revendedor,
            c.comp_parcelamento_entrada,
            c.comp_parcelamento_data,
            r.rifa_finalizar,
            c.comp_status_revenda,
            c.comp_cliente as dono, 
            u.usu_nome as cliente,
            u.usu_regiao as usu_regiao,
            u.usu_cod as cliente_cod,
            u.usu_celular as tel_cliente,
            rev.usu_nome as revendedor, 
            b.bil_compra as compra, 
            c.comp_situacao,
            c.comp_valortotal
            from 
            tbl_bilhetes b, 
            tbl_rifas r, 
            tbl_compra c left join tbl_usuario rev on rev.usu_cod = c.comp_revendedor,
            tbl_usuario u
            WHERE 
            u.usu_cod = c.comp_cliente
            
            and r.rifa_cod = b.bil_rifa 
            and (r.rifa_dono = '{$_SESSION['usuario']}' OR c.comp_revendedor = '{$_SESSION['usuario']}' $lista_de_filhos)
            and c.comp_cod = b.bil_compra 
            $sql_filtrar_por
            $rifa_cod
            $sql $situacao
            group by b.bil_compra  $order_by";

        if($_SESSION['admin'] == 'S'){
            $sqlbil = "
            SELECT
            r.rifa_cod,
            r.dezena_bolao,
            r.rifa_maxbilhetes, 
            r.rifa_titulo, 
            r.rifa_dtsorteio,
            c.comp_situacao,
            c.comp_data,
            c.comp_serie,
            c.comp_cod,
            c.comp_parcelamento_entrada,
            c.comp_parcelamento_data,
            r.rifa_finalizar,
            c.comp_status_revenda,
            c.comp_revendedor,
            c.comp_cliente as dono, 
            u.usu_nome as cliente,
            u.usu_regiao as usu_regiao,
            u.usu_cod as cliente_cod,
            u.usu_celular as tel_cliente,
            rev.usu_nome as revendedor, 
            b.bil_compra as compra, 
            c.comp_situacao, 
            c.comp_valortotal 
            from tbl_bilhetes b,  tbl_rifas r , tbl_usuario u,
            tbl_compra c left join tbl_usuario rev on rev.usu_cod = c.comp_revendedor
            where r.rifa_cod = b.bil_rifa
            and u.usu_cod = c.comp_cliente
            $rifa_cod
            $sql_filtrar_por
            and c.comp_cod=b.bil_compra 
            /*and (r.rifa_dono = '{$_SESSION['usuario']}' OR c.comp_revendedor = '{$_SESSION['usuario']}' $lista_de_filhos)*/
            $sql $situacao 
            group by b.bil_compra $order_by";
            
        }
    }

    $_SESSION['relatorio'] = $sqlbil;
    if(isset($_POST)){
        $qrybil = $mysqli->query($sqlbil) or die($mysqli->error);
        $_SESSION['saved_post'] = $_POST;
        $count = $qrybil->num_rows;
    } else
        $count = 0;
    
    $resultadosPorPagina = 100;

    $maxPages = ceil($count/$resultadosPorPagina);
    
    if(!isset($_POST['pagina'])) 
        $strPage = 0;
    else
        $strPage = intval($_POST['pagina']);
    $sql_code_final = $sqlbil." LIMIT $strPage, ".intval($resultadosPorPagina);
    $qrybil = $mysqli->query($sql_code_final) or die($mysqli->error);


    $resbil = $qrybil->fetch_assoc();
    
    include('../class/Venda.class.php');
    
   
    $k = 0;
    if($count == 0){ 
        if(($_SESSION['admin'] == 'S' && count($_POST) == 0) || !$_POST)
            echo "<div class=\"col-lg-12 text-center\">Use os filtros para visualizar as vendas.</div>";
        else
            echo "<div class=\"col-lg-12 text-center\">Nenhuma venda foi encontrada</div>";
    } else { ?>
	
	<div class="row">
    
    <div class="col-sm-5">
        <form action="" method="POST">
            <?php foreach($_POST as $k=>$v){ ?>
            <input type="hidden" name="<?= $k; ?>" value="<?= $v; ?>">
            <?php } ?>
            <div class="form-group form-inline text-left">
                <label for="">Páginas (<?= $count; ?>)</label>

                    

                    <?php 

                    $pagina_atual    = 0;
                    if($_POST['pagina'])
                        $pagina_atual = ($_POST['pagina']/$resultadosPorPagina);

                    $inicio          = $pagina_atual - 3;
                    $final           = $pagina_atual + 3;
                        
                    $limite_inferior = $pagina_atual - 6;
                    $limite_superior = $pagina_atual + 6;
                   
                    if($inicio < 0) $inicio = 0;

                    if($final > ($maxPages-1)) $final = $maxPages-1;

                    if($limite_inferior < 0) $limite_inferior = 0;

                    if($limite_superior > ($maxPages-1)) $limite_superior = ($maxPages-1);
                    
                    ?>

                    <button type="submit" name="pagina" value="0" class="btn btn-xs btn-default"><<</button>
                    <button type="submit" name="pagina" value="<?= $limite_inferior*$resultadosPorPagina; ?>" class="btn btn-xs btn-default"><</button>

                    <?php for($k = $inicio; $k <= $final; $k++){ ?>
                    <button type="submit" name="pagina" value="<?php echo $k*$resultadosPorPagina; ?>" class="btn btn-xs btn-default <?php if($pagina_atual == $k) echo 'btn-success'; ?>"><?php echo $k+1; ?></button>
                    <?php } ?>
                    <button type="submit" name="pagina" value="<?= $limite_superior*$resultadosPorPagina; ?>" class="btn btn-xs btn-default">></button>
                    <button type="submit" name="pagina" value="<?= ($maxPages-1)*$resultadosPorPagina; ?>" class="btn btn-xs btn-default">>></button>
          

            </div>
            
        
        </form>
    </div>
   
    <div class="form-group col-sm-7 text-right">
        <button onclick="repetirVendas();" class="btn btn-xs btn-default">Repetir Vendas</button>
        <button onclick="liberarManualmente();" class="btn btn-xs  btn-success">Recebido pelo Vendedor</button>
        <button onclick="recebidoVendedor();" class="btn btn-xs  btn-primary">Marcar como Pago</button>
        <button onclick="cancelarVendas();" class="btn btn-xs  btn-danger">Cancelar Vendas</button>
        <button onclick="comprovante('pc');" class="btn  btn-xs btn-warning">Comprovante PC</button>
        <button onclick="comprovante('celular');" class="btn btn-xs  btn-warning">Comprovante Celular</button>
    </div>
    <div class="clearfix"></div>
    <div class="col-lg-12 table-responsive" id="removerRelatorio">
	<table  class=" table-hover table-bordered table">
        <thead>
            <form id="ff" action="index.php?<?php foreach($_GET as $k=>$v) echo "$k=$v&"; ?>" method="post">
            <?php foreach($_POST as $k=>$v) echo "<input type=\"hidden\" name=\"$k\" value=\"$v\">"; ?>
            <input type="hidden" class="removerRelatorio" id="ordenar_tipo" value="<?php echo $_POST['ordenar_tipo']; ?>" name="ordenar_tipo">
            <input type="hidden" class="removerRelatorio" id="ordenar_campo" value="<?php echo $_POST['ordenar_campo']; ?>" name="ordenar_campo">

                <tr id="topoT">
                    
                        <td class="removerRelatorio"><input type="checkbox" id="marcar" onclick="marcarTodos();"></td>
                        <td>Venda</td>
                        <td>M.Federal</td>
                        <td width="20%"><a href="javascript: void(0);" onclick="ordenar('rifa_titulo', '<?php if($_POST['ordenar_tipo'] == 'ASC') echo "DESC"; else echo "ASC"; ?>');">Campanha <i class="glyphicon glyphicon-<?php if($_POST['ordenar_tipo'] == 'ASC') echo "sort-by-alphabet-alt"; else echo "sort-by-alphabet"; ?>"></i></a></td>
                        <td><a href="javascript: void(0);" onclick="ordenar('cliente', '<?php if($_POST['ordenar_tipo'] == 'ASC') echo "DESC"; else echo "ASC"; ?>');">Cliente <i class="glyphicon glyphicon-<?php if($_POST['ordenar_tipo'] == 'ASC') echo "sort-by-alphabet-alt"; else echo "sort-by-alphabet"; ?>"></i></a></td>
                        <td><a href="javascript: void(0);" onclick="ordenar('usu_regiao', '<?php if($_POST['ordenar_tipo'] == 'ASC') echo "DESC"; else echo "ASC"; ?>');">Bairro/Região <i class="glyphicon glyphicon-<?php if($_POST['ordenar_tipo'] == 'ASC') echo "sort-by-alphabet-alt"; else echo "sort-by-alphabet"; ?>"></i></a></td>
                        <td><a href="javascript: void(0);" onclick="ordenar('revendedor', '<?php if($_POST['ordenar_tipo'] == 'ASC') echo "DESC"; else echo "ASC"; ?>');">Vendedor <i class="glyphicon glyphicon-<?php if($_POST['ordenar_tipo'] == 'ASC') echo "sort-by-alphabet-alt"; else echo "sort-by-alphabet"; ?>"></i></a></td>
                        <td width="40%">Bilhetes</td> 
                        <td>Total</td>
                        <td>Situação</td>
                        <td class="removerRelatorio"></td>
                </tr>

            </form>
        </thead>
        <tbody>
                    <?php
                    $listaResumo = array();
                    $vendaDireta = 0;
                    $array_de_supervisores_rifa = array();
                    $array_de_supervisores_rev = array();
                    $ids_de_todas_as_vendas = array();
                    do
                    {
                        
                        if(isset($resbil['comp_revendedor'])){

                            $array_de_supervisores_rifa[$resbil['rifa_cod']] = true;
                            $array_de_supervisores_rev[$resbil['comp_revendedor']] = true;

                            if(!$listaResumo[$resbil['comp_revendedor']]['count'])
                                $listaResumo[$resbil['comp_revendedor']]['count'] = 0;

                            if(!$listaResumo[$resbil['comp_revendedor']]['soma'])
                                $listaResumo[$resbil['comp_revendedor']]['soma'] = 0;

                            $listaResumo[$resbil['comp_revendedor']]['count']++;
                            $listaResumo[$resbil['comp_revendedor']]['nome'] = $resbil['revendedor'];
                            $listaResumo[$resbil['comp_revendedor']]['soma'] += $resbil['comp_valortotal'];

                        }else{
                            $vendaDireta++;
                        }
                    
                        if(!isset($ids_de_todas_as_vendas[$resbil['compra']]) && ($isMENSALISTA == 1 || $resbil['rifa_finalizar'] == 2 || $_SESSION['admin'] == 'S'))
                            $ids_de_todas_as_vendas[$resbil['compra']] = array('maxbilhetes'=>$resbil['rifa_maxbilhetes'], 'bolaodezena'=>$resbil['dezena_bolao']);

                    
                    ?>
                        <tr onclick="$(this).children().children().prop('checked', !$(this).children().children().prop('checked'));">
                            <td class="removerRelatorio"><input type="checkbox" class="liberarManualmente <?php if($resbil['dezena_bolao']) echo 'dezenaBolao'; ?>" value="<?php echo $resbil['comp_cod']; ?>"></td>
                            <td><?php echo $resbil['comp_cod']; ?></td>
                            <td><?php echo $resbil['comp_serie']; ?></td>
                            <td><?php echo $resbil['rifa_titulo'];  ?></td>
                            <td><?php echo $resbil['cliente']; ?> <span>(<?php echo $resbil['tel_cliente']; ?>)</span>

                                <p><br><small><?php echo date('d/m/Y H:i', strtotime($resbil['comp_data'])); ?></small></p>
                            </td>
                            <td><?php echo $resbil['usu_regiao']; ?></td>
                            <td>
                            <?php 
                                echo ($resbil['revendedor'] != '')? $resbil['revendedor']:"Venda Direta";
                            ?></td>
                            <td>
                            <?php if($isMENSALISTA == 1 || $resbil['rifa_finalizar'] == 2 || $_SESSION['admin'] == 'S'){ ?>
                            <?php echo 'TESTE'; ?>
                            <?php }else echo "Indisponível até a finalização."; ?>
                            </td>
                            <td><?php echo "R$ ".number_format($resbil['comp_valortotal'],'2',',','.') ?>
                                <?php if($resbil['comp_parcelamento_entrada']) echo "<br><span style='color:red'; ?>R$ ".($resbil['comp_valortotal']-$resbil['comp_parcelamento_entrada'])."</span><br><span style='color:#e5bb00;'>".date('d/m/Y', strtotime($resbil['comp_parcelamento_data']))."</span>"; ?>
                            </td>
                            <td>
                            <?php 
                                if ($resbil["comp_situacao"] == "" || !$resbil['comp_situacao'])
                                {
                                    echo "Reservado";
                                    if($resbil['comp_parcelamento_entrada'])
                                        echo " - <span style='color:blue;'>(PARCELADO)</span>";
                                    elseif($resbil['comp_status_revenda'] == '1')
                                    echo " - (PAGO NA HORA)";
                                    elseif($resbil['comp_status_revenda'] == '0')
                                        echo " - (A RECEBER)";
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
                                
                            ?>
                            </td>
                            <td class="text-right removerRelatorio form-inline">

                                <a class="btn btn-default" href="javascript: abrirJanela('<?php echo $resbil['dono']; ?>');">Enviar E-mail</a>
                                <?php if(/*time() < strtotime($resbil['rifa_dtsorteio'])*/ 1){ ?>
                                <a class="btn btn-default" href="javascript: repetirVenda(<?= $resbil['comp_cod']; ?>, <?= $resbil['rifa_cod']; ?>);" >Repetir Venda</a>
                                <?php } ?>

                                <?php 
                                    if($resbil['comp_status_revenda'] == '1' 
                                        || $resbil['comp_status_revenda'] == '0' 
                                        || ($resbil["comp_situacao"] ==  3 
                                        || $resbil["comp_situacao"] == 4)){ ?>
                                    
                                <a class="btn btn-default" href="javascript: window.open('index.php?p=sucesso_venda&comp=<?php echo $resbil['comp_cod']; ?>&usuario=<?php echo $resbil['cliente_cod']; ?>', '_blank');" >Comprovante</a>
                                    <?php } ?>

                                <?php 
                                if(($resbil["comp_situacao"] ==  3 || $resbil["comp_situacao"] == 4) && $resbil['dezena_bolao']){ ?>
                                
                                    <a class="btn btn-default" href="javascript: window.open('page/gerar_relatorio_dezena_bolao.php?venda=<?= $resbil['comp_cod'] ?>&rifa=<?= $resbil['rifa_cod']; ?>&pontos=-1', '_blank');">Gerar PDF</a>
                                
                                <?php }else{ ?>
                                    <a class="btn btn-default" href="javascript: location.href='index.php?p=carrinho&carregar_compra=<?php echo $resbil['comp_cod']; ?>';">Editar Venda</a>
                                <?php } 

                                if ($resbil["comp_situacao"] != 6 && $resbil["comp_situacao"] != 7 && $resbil["comp_situacao"] != 3 && $resbil["comp_situacao"] != 4){ ?>
                                    <a class="btn btn-danger" href="javascript: location.href='index.php?p=venda&cancelar=<?php echo $resbil['comp_cod']; ?>';">
                                            Cancelar Venda
                                            </a>
                                <?php 
                                if($resbil['comp_status_revenda'] == '0'){ ?>
                                    <a class="btn btn-primary" href="javascript: location.href='index.php?p=venda&marcar_pago=<?php echo $resbil['comp_cod']; ?>&usuario=<?php echo $resbil['cliente_cod']; ?>'">Marcar como Pago</a>
                                <?php 
                                } ?>
                                        
                                <a class="btn btn-primary" href="javascript: liberarManualmente2(<?php echo $resbil['comp_cod']; ?>);">Liberar Manualmente</a><?php 
                                foreach($_POST as $k=>$v) 
                                    echo '<input type="hidden" name="'.$k.'" value="'.$v.'">'; 
                                } ?>

                                <!--<button  class="supervisor_<?= $resbil['rifa_cod'].'_'.$resbil['comp_revendedor']; ?> btn btn-default" onclick="location.href='index.php?p=revendedor&rifa=<?php echo $resbil['rifa_cod']; ?>';" style="display:none;">SUP: Adicionar Usuário</button>-->

                                <!--<div class="dropup no-print">
                                  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Opções
                                    <span class="caret"></span>
                                  </button>
                                  <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                    

                                    <li><a href="javascript: abrirJanela('<?php echo $resbil['dono']; ?>');">Enviar E-mail</a></li>

                                    <li class=""><a href="javascript: repetirVenda(<?= $resbil['comp_cod']; ?>, <?= $resbil['rifa_cod']; ?>);" >Repetir Venda</a></li>
                                    
                                    <?php 
                                    if($resbil['comp_status_revenda'] == '1' 
                                        || $resbil['comp_status_revenda'] == '0' 
                                        || ($resbil["comp_situacao"] ==  3 
                                        || $resbil["comp_situacao"] == 4)){ ?>
                                    
                                    <li class="bg-warning"><a href="javascript: window.open('index.php?p=sucesso_venda&comp=<?php echo $resbil['comp_cod']; ?>&usuario=<?php echo $resbil['cliente_cod']; ?>', '_blank');" >Comprovante</a></li>
                                    <?php } ?>

                                     <?php if(($resbil["comp_situacao"] ==  3 || $resbil["comp_situacao"] == 4) && $resbil['dezena_bolao']){ ?>
                                    <li><a href="javascript: window.open('page/gerar_relatorio_dezena_bolao.php?venda=<?= $resbil['comp_cod'] ?>&rifa=<?= $resbil['rifa_cod']; ?>&pontos=-1', '_blank');">Gerar PDF</a></li>
                                    <?php }else{ ?>
                                    <li><a href="javascript: location.href='index.php?p=carrinho&carregar_compra=<?php echo $resbil['comp_cod']; ?>';">Editar Venda</a></li>
                                    <?php } ?>
                                    
                                    <?php if ($resbil["comp_situacao"] != 6 && $resbil["comp_situacao"] != 7 && $resbil["comp_situacao"] != 3 && $resbil["comp_situacao"] != 4){ ?>
                                        <li class="bg-danger">
                                            <a href="javascript: location.href='index.php?p=venda&cancelar=<?php echo $resbil['comp_cod']; ?>';">
                                            Cancelar Venda
                                            </a>
                                        </li>
                                        <?php if($resbil['comp_status_revenda'] == '0'){ ?>
                                        <li>
                                            <a href="javascript: location.href='index.php?p=venda&marcar_pago=<?php echo $resbil['comp_cod']; ?>&usuario=<?php echo $resbil['cliente_cod']; ?>'">Marcar como Pago</a>
                                        </li>    
                                        <?php } ?>
                                        
                                            
                                        <li>
                                            <a href="javascript: liberarManualmente2(<?php echo $resbil['comp_cod']; ?>);">Liberar Manualmente</a><?php 
                                            foreach($_POST as $k=>$v) 
                                                echo '<input type="hidden" name="'.$k.'" value="'.$v.'">'; 
                                            ?>
                                        </li>
                                       
                                        <?php } ?>
                                  </ul>
                                </div>-->
                            </td>
                        </tr>
                        <?php } while ($resbil = $qrybil->fetch_assoc());

                        

                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            $bil_vend = DBExecute("select * from tbl_bilhetes b where b.bil_compra IN (" . implode(',', array_keys($ids_de_todas_as_vendas)) . ")", $mysqli);
            $bil_v = $bil_vend->fetch_assoc();
            $bilhetes_organizados = array();
            do{
                if(!isset($bilhetes_organizados[$bil_v['bil_compra']]))
                    $bilhetes_organizados[$bil_v['bil_compra']] = array();
                if($ids_de_todas_as_vendas[$bil_v['bil_compra']]['bolaodezena'] > 0)
                    $bilhetes_organizados[$bil_v['bil_compra']][] = "[".str_pad($bil_v['bil_numero'], 2, "0", STR_PAD_LEFT)."], ";
                else
                    $bilhetes_organizados[$bil_v['bil_compra']][] = str_pad($bil_v['bil_numero'], strlen($ids_de_todas_as_vendas[$bil_v['bil_compra']]['maxbilhetes'])-1, "0", STR_PAD_LEFT);
                
            } while( $bil_v = $bil_vend->fetch_assoc());                    
            
            ?>
            <script>
                
                function adjust_rifas() {
                    let dados = <?= json_encode($bilhetes_organizados); ?>;
                }

            </script>
        <div class="col-lg-12 form-group esconder" id="listaResumo" style="border-radius:1px solid black; padding:10px;">
            <?php 
            if(is_array($listaResumo)){
                foreach($listaResumo as $rev=>$data){
                    echo "<p>$rev - {$data['nome']} ({$data['count']})</p>";
                }
            }

            if($vendaDireta > 0) echo "<p>Vendas Diretas ($vendaDireta)</p>";

            ?>
        </div>
    </div><?php } ?>
</div>
<div id="email" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md">
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
                <input name="assunto" required value="Contato RifasBrasil.com.br" type="text" class="form-control">
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

<div id="rifa_destino" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">Selecione a Rifa de Destino</h4>
      </div>
      <form method="post" action="">
      <div class="modal-body">
        
            <div class="col-lg-12">
                <div class="form-group">
                    <select name="rifa_destino" id="rifa_destino_input" class="form-control">
                        <?php

                        $consulta = $mysqli->query($rifas_do_usuario) or die($mysqli->error);
    $assoc    = $consulta->fetch_assoc();
    do{ if(time() <= (strtotime($assoc['rifa_dtsorteio']) + 86400)){ ?>
        <option <?php if(isset($_POST['rifa']) && $_POST['por_rifa'] == $assoc['rifa_cod']) echo 'selected'; ?> value="<?= $assoc['rifa_cod']; ?>"><?= $assoc['rifa_cod']; ?> - <?= $assoc['rifa_titulo']; ?></option><?php
   } }while($assoc    = $consulta->fetch_assoc());
    ?>

                    </select>
                </div>
            </div>
        
        <div class="clearfix"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="button" id="btn_pross" class="btn btn-primary">Enviar</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>

<script>
    var codigo_da_venda = -1;
    window.onload = function(){
        function buscarrifas(rifa){
            show_loading();
            //$('#rifa_destino_input').html($('#por_rifa').html());
            $('#rifa_destino_input option[value="'+rifa+'"]').attr('selected', true);
            $('#btn_pross').click(function(){
                var rifa = $('#rifa_destino_input').val();
                $.post('page/venda.php', {montar_carrinho:rifa, bil:'<?= $_GET['bil']; ?>'})
                .done(function(r){
                    console.log(r);
                    location.href='index.php?p=cadastro_cliente';
                })
                .fail(function(r){
                    console.log(r);
                });
                
            });
            $('#rifa_destino').modal('show');
            end_loading();
        }

        <?php if($_GET['buscarrifas']) echo 'buscarrifas('.intval($_GET['buscarrifas']).');'; ?>

        $('.table-responsive').on('show.bs.dropdown', function () {
             $('.table-responsive').css( "overflow", "inherit" );
        });

        $('.table-responsive').on('hide.bs.dropdown', function () {
             $('.table-responsive').css( "overflow", "auto" );
        });
    }

    function repetirVendas(prosseguir = false){

        $('#btn_pross').off();

        if(prosseguir){
            var destino = $('#rifa_destino_input');
            if(destino.val() > 0){

                var rifa = $('#rifa_destino_input').val();

                var codigo_da_venda = [];
                var qtd_vendas = 0;

                // define quais vendas serão adicionadas ao carrinho
                $.each($('.liberarManualmente'), function(){
                    if($(this).prop('checked')){
                        if($(this).hasClass('dezenaBolao')){
                            qtd_vendas++;
                            codigo_da_venda.push($(this).val());
                        }else{
                            return alert('A rifa "' + $(this).parent().next().next().text() + '" não pertence à modalidade Dezena Bolão.');
                        }
                    }
                });

                if(qtd_vendas == 1)
                    return alert("Você selecionou apenas uma venda. Utilize a função Repetir Venda neste caso.");

                console.log("repetir_venda = ", codigo_da_venda);
                console.log("Rifa de Destino = " + rifa);

                $.post('page/venda.php', {repetir_venda:codigo_da_venda, rifa:rifa})
                .done(function(r){
                    console.log(r);
                    location.href="index.php?p=carrinho";
                })
                .fail(function(r){
                    console.log(r);
                    location.href="index.php?p=carrinho";
                });

            }else
                return alert('Selecione alguma rifa');
        }

        show_loading();
        //$('#rifa_destino_input').html($('#por_rifa').html());
        $('#rifa_destino_input option[value="'+rifa+'"]').attr('selected', true);
        $('#btn_pross').click(function(){
            repetirVendas(true);
        });
        $('#rifa_destino').modal('show');
        end_loading();


    }

    function repetirVenda(cod, rifa = ''){

        $('#btn_pross').off();

        console.log()

        if(cod == 'prosseguir'){
            var destino = $('#rifa_destino_input');
            if(destino.val() > 0){

                var rifa = $('#rifa_destino_input').val();

                console.log("repetir_venda = " + codigo_da_venda);
                console.log("Rifa = " + rifa);

                $.post('page/venda.php', {repetir_venda:codigo_da_venda, rifa:rifa})
                .done(function(r){
                    console.log(r);
                    location.href="index.php?p=carrinho";
                })
                .fail(function(r){
                    console.log(r);
                    location.href="index.php?p=carrinho";
                });

            }else
                return alert('Selecione alguma rifa');
        }

        codigo_da_venda = cod;

        show_loading();
        //$('#rifa_destino_input').html($('#por_rifa').html());
        $('#rifa_destino_input option[value="'+rifa+'"]').attr('selected', true);
        $('#btn_pross').click(function(){
            repetirVenda('prosseguir');
        });
        $('#rifa_destino').modal('show');

        end_loading();


    }

    function ativarBotao(rifa, revendedor){
        console.log("Ativar botao para " + revendedor);
        console.log('.supervisor_'+rifa+'_'+revendedor);
        $('#por_rifa2 option[value="'+rifa+'"]').removeAttr('disabled');
        $('.supervisor_'+rifa+'_'+revendedor).css('display', '');
    }
    <?php if(count($array_de_supervisores_rev) > 0 and count($array_de_supervisores_rifa) > 0){ ?>
    window.onload = function(){
    <?php 

        $sql_code = "SELECT *  from tbl_revendedor  WHERE rifa_cod IN (".implode(',', array_keys($array_de_supervisores_rifa)).") and usu_cod IN (".implode(',', array_keys($array_de_supervisores_rev)).")";
        $exec = $mysqli->query($sql_code) or die($mysqli->error);

        echo 'var teste = "'.$sql_code.'";';

        $dados = $exec->fetch_assoc();

        if($exec->num_rows > 0)
        do{
            echo "ativarBotao({$dados['rifa_cod']}, {$dados['usu_cod']});";
        }while($dados = $exec->fetch_assoc());
    ?>
    }   
    <?php } ?>

    function marcarTodos(){
        var ch = $('#marcar').prop('checked');
        console.log(ch);
        if(ch)
            $('.liberarManualmente').prop('checked', true);
        else
            $('.liberarManualmente').prop('checked', false);
        
    }
    function cancelarVendas(){
        var str = "", el;
        var formulario = $('#formulario_f').serialize();
        $.each($('.liberarManualmente'), function(){
            el = $(this);
            if(el.prop('checked'))
                str += '-' + el.val();
        });
        location.href="index.php?p=venda&cancelarVendas=" + str + "&ok=1&" + formulario;
    }
    
    function liberarManualmente(){
        var str = "", el;
        var formulario = $('#formulario_f').serialize();
        $.each($('.liberarManualmente'), function(){
            el = $(this);
            if(el.prop('checked'))
                str += '-' + el.val();
        });
        location.href="index.php?p=venda&liberar=" + str + "&" + formulario;
    }
    function recebidoVendedor(){
        var formulario = $('#formulario_f').serialize();
        var str = "", el;
        $.each($('.liberarManualmente'), function(){
            el = $(this);
            if(el.prop('checked'))
                str += '-' + el.val();
        });
        location.href="index.php?p=venda&marcarComoPago=" + str + "&" + formulario;
    }
    function abrirJanela(codigo){
         var xhttp = new XMLHttpRequest();
          xhttp.onreadystatechange = function() {
            if (xhttp.readyState == 4 && xhttp.status == 200) {
                    var resposta = xhttp.responseText;
                    
                    var indice = resposta.indexOf("*");
                    var nome = resposta.substr(0, indice);
                    var email = resposta.substr(indice+1, xhttp.responseText.length);

                    if(!email) return alert('Usuário sem e-mail cadastrado. Não podemos enviar.');

                    document.getElementById('email_usuario').value = nome + ' <' + email + '>';
                    document.getElementById('usuario').value = email;
                    $('#email').modal('show');
             //document.getElementById("demo").innerHTML = xhttp.responseText;
            }
          };
          xhttp.open("GET", "page/get_cliente.php?codigo="+codigo, true);
          xhttp.send();
       
    }

    function liberarManualmente2(comp_cod){

        var dados = {};
        <?php foreach($_POST as $key=>$val){ echo "dados.{$key} = '$val';";} ?>
        $.redirect('index.php?p=venda&liberar=' + comp_cod, dados);

    }

    function filtrar(){
        var VAR_RIFAS = <?php if(isset($array_rifas)) echo json_encode($array_rifas); else echo '[]'; ?>;
        console.log(VAR_RIFAS);
        var options = {
                data: VAR_RIFAS,
                getValue: "nome",
                list: {
                    onSelectItemEvent: function() {
                        $('#por_rifa').val($("#rifa").getSelectedItemData().codigo);
                    },
                    match: {
                        enabled: true
                    }
                },
                theme: "bootstrap"
            };
            $('#rifa').easyAutocomplete(options);
    }
    function ordenar(campo, tipo){
        $('#ordenar_tipo').val(tipo); 
        $('#ordenar_campo').val(campo); 
        $('#ff').submit();
    }

    function show_loading(){
      $('#loader').removeClass('esconder');
    }
    function end_loading(){
      $('#loader').addClass('esconder');
    }

    function gerarRelatorio(tipo = 0){

        window.open('page/relatorio.php?tipo=' + tipo, '_blank');

        /*show_loading();
        $.ajax({
            url:'page/venda.php',
            type:"post",
            dataType:"json",
            data:{gerarRelatorio:true}
        }).done(function(res){
            end_loading();
            if(!res.erro)
                window.open('relatorio/' + res.msg);
            else
                alert(res.msg);

        }).fail(function(r){
            console.log("Erro:", r);
        });*/

    }
    function mudarSupervisor(rifa){
        console.log("RIFA: " + rifa);
        window.open('index.php?p=revendedor&rifa=' + rifa, '_blank'); 
        $('#por_rifa2 option:selected').prop('selected', false);
    }
    function liberarManualmante(){

    }
    function comprovante(tipo = pc){

        var str = "", el;
        var k = 0;
        $.each($('.liberarManualmente'), function(){
            el = $(this);
            if(el.prop('checked')){
                str += '-' + el.val();
                k++;
            }
        });

        if(k == 0) return alert('Selecione as compras que deseja imprimir');

        $.post('page/venda.php', {comprovantes:str, gerarComprovantes:tipo})
        .done(
        function(r){

            console.log(r);
            if(tipo == 'pc'){
                var mywindow = window.open('', 'PRINT', 'height=400,width=600');
                mywindow.document.write('<html><head><title>Imprimir</title><style>body{font-family:Arial; font-size:12;}</style>');
                mywindow.document.write('</head><body >');
                mywindow.document.write(r);
                mywindow.document.write('</body></html>');

                mywindow.document.close(); // necessary for IE >= 10
                mywindow.focus(); // necessary for IE >= 10*/

                mywindow.print();
                mywindow.close();
            }else{
                window.print();
                MyJavaScriptInterface.printHtml(r);
            }

        })
        .fail(
        function(r){
            console.log(r);
            alert('Falhou ao imprimir!');
        });

    }
</script>
