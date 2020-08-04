<?php

include("../class/conexao.php");
include("../class/function_limparNumero.php");
include("../class/function_primeiroNome.php");

function formataTelefone($telefone)
{
    $telefone = str_replace("-", "", $telefone);
    return "(" . substr($telefone, 0, 2) . ") " . substr($telefone, 2, -4) . "-" . substr($telefone, -4);
}

$usuario_id = intval($_GET['usuario']);
$usuario = DBSelect("SELECT * FROM tbl_usuario where usu_cod='$usuario_id'", $mysqli);

if ($_GET['comp'])
    $compra = DBSelect("SELECT * FROM tbl_compra where comp_cod ='" . intval($_GET['comp']) . "'", $mysqli);
else
    $compra = DBSelect("SELECT * FROM tbl_compra where comp_cliente='$usuario_id'", $mysqli);

$vendedor = DBSelect("SELECT * FROM tbl_usuario where usu_cod='{$compra['comp_revendedor']}'", $mysqli);
$bilhetes = db_select($mysqli, "SELECT * FROM tbl_bilhetes where bil_compra='{$compra['comp_cod']}'");
$rifa_cod = $bilhetes[0]['bil_rifa'];

$rifa = DBSelect("SELECT r.*, u.usu_nome, u.usu_celular 
    FROM tbl_rifas r, tbl_usuario u 
    where u.usu_cod = r.rifa_dono 
    and rifa_cod='$rifa_cod'
", $mysqli);
$dezenabolao = $rifa['dezena_bolao'];


foreach ($bilhetes as $value) {
    $rifa_cod = $value['bil_rifa'];
    if ($dezenabolao)
        $bilhete_str[] = str_pad($value['bil_numero'], 2, '0', STR_PAD_LEFT);
    else
        $bilhete_str[] = str_pad($value['bil_numero'], strlen($rifa['rifa_maxbilhetes']) - 1, '0', STR_PAD_LEFT);
}
$bilhete_str = implode("-", $bilhete_str);


$dataSorteio = date('d/m/Y', strtotime($rifa['rifa_dtsorteio']));
$data = date('d/m/Y H:i', strtotime($compra['comp_data']));
$telefoneFormatado = "(" . substr($usuario['usu_celular'], 0, 2) . ") " . substr($usuario['usu_celular'], 2, -4) . "-" . substr($usuario['usu_celular'], -4);
$telefone = $usuario['usu_celular'];


if (!$_SESSION) @session_start();

$sms = "RIFASBRASIL: Ola " . primeiroNome($usuario['usu_nome']) . ", seu(s) Bilhete(s): (" . $bilhete_str . ") Da Rifa: (" . $rifa['rifa_titulo'] . ") Sorteio dia: (" . $dataSorteio . ") - " . (($compra['comp_status_revenda'] == 1) ? 'PAGO' : 'NAO PAGO') . " - Boa Sorte!";


// Seleciona a conta do revendedor para recuperar os dados bancarios = Tela de pagamento para usuario sem login
$cod_revendedor = $_SESSION['cod_rev'];

$dados_bancarios = $mysqli->query("SELECT * FROM tbl_conta WHERE usuario = '$cod_revendedor'") or die($mysqli->error);
while ($row = $dados_bancarios->fetch_assoc()) {
    $dados_bancarios_rev[] = $row;
}


$dados_revendedor = $mysqli->query("SELECT * FROM tbl_usuario WHERE usu_cod = '$cod_revendedor'") or die($mysqli->error);
$dados_revendedor = $dados_revendedor->fetch_assoc();



?>
<style>
    .titulo {
        font-weight: bold;
    }
</style>


<div class="col-lg-12">
    <?php if ($_SESSION['usuario_sem_login']) : ?>
        <h3>Dados para pagamento</h3>
    <?php else : ?>
        <h3>Tudo certo!</h3>
    <?php endif; ?>
    <hr>
    <?php
    if (isset($erro) && count($erro) > 0)
        foreach ($erro as $valor)
            echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";
    ?>

</div>

<div class="col-lg-12">
    <?php if ($_SESSION['usuario_sem_login']) : ?>
        <p>Pre-reserva efetuada com sucesso!</p>
        <p>Cique no botão abaixo para solicitar a confirmação de sua reserva</p>
        <p><button onclick="confirmarReserva();" class="btn btn-primary">Confirmar Reserva</button></p>
        <!--<p style="margin-top:40px">Escolha uma das formas de PAGAMENTO abaixo. Efetue o pagamento e envie o comprovante para o Whatsapp <span style="font-weight:bold; color:green"><?= formataTelefone($dados_revendedor['usu_celular']); ?></span>, informando o CÓDIGO: <span style="font-weight:bold; color:red"><?= $compra["comp_cod"]; ?></span></p>-->
        <h3 style="text-transform:uppercase; margin-top:20px;">Formas de pagamento</h3>
        <h3 style="margin-top:40px"><span style="color:red">-></span>Depósito ou Transferência </h3>
        <?php $i = 0;
            foreach ($dados_bancarios_rev as $dado_bancario) : ?>
            <h5 style="font-weight:700;">Conta <?= ($i === 0) ? "Principal" : $i  ?></h5>
            <div style="margin-top:20px">
                <ul style="list-style:none;">
                    <li>Banco: <?= $dados_bancarios_rev[$i]['banco']; ?></li>
                    <li>Agência: <?= $dados_bancarios_rev[$i]['agencia']; ?></li>
                    <li>Conta: <?= $dados_bancarios_rev[$i]['conta']; ?></li>
                    <?php if ($dados_bancarios_rev[$i]['nome_completo'] != '') echo "<li>Nome do Titular: " . $dados_bancarios_rev[$i]['nome_completo'] . " " ?>
                    <?php if ($dados_bancarios_rev[$i]['cpf'] != '') echo "<li>CPF do Titular: " . $dados_bancarios_rev[$i]['cpf'] . " " ?>
                </ul>
            </div>
        <?php $i++;
            endforeach; ?>

        <?php if ($dados_revendedor['usu_psemail'] != '') : ?>
            <h3><span style="color:red">-></span>Cartão de crédito ou boleto</h3>
            <form method="post" target="pagseguro" action="https://pagseguro.uol.com.br/v2/checkout/payment.html">

                <!-- Campos obrigatórios -->
                <input name="receiverEmail" type="hidden" value="<?= $dados_revendedor['usu_psemail']; ?>">
                <input name="currency" type="hidden" value="BRL">

                <!-- Itens do pagamento (ao menos um item é obrigatório) -->
                <input name="itemId1" type="hidden" value="<?= $rifa['rifa_cod']; ?>">
                <input name="itemDescription1" type="hidden" value="<?= $rifa['rifa_titulo']; ?>">
                <input name="itemAmount1" type="hidden" value="<?= number_format($compra['comp_valortotal'], 2, '.', ''); ?>">
                <input name="itemQuantity1" type="hidden" value="1">

                <!-- Dados do comprador (opcionais) -->
                <input name="senderName" type="hidden" value="<?= $usuario['usu_nome']; ?>">
                <input name="senderPhone" type="hidden" value="<?= $usuario['usu_celular']; ?>">

                <!-- submit do form (obrigatório) -->
                <input alt="Pague com PagSeguro" name="submit" type="image" src="https://p.simg.uol.com.br/out/pagseguro/i/botoes/pagamentos/120x53-pagar.gif" />
            </form>
        <?php endif; ?>
    <?php else : ?>
        <p>Venda efetuada com sucesso. O que você deseja fazer agora?</p>
    <?php endif; ?>



    <div class="spacer"></div>
    <?php if(!isset($_SESSION['usuario_sem_login'])) { ?>
    <button class="btn btn-success" onclick="javascript:location.href='sms:+55<?php echo $telefone; ?>?body=<?php if ($_GET['sms']) echo $_SESSION['sms'];
                                                                                                            else echo $sms; ?>';">Enviar SMS com dados p/ Cliente</button>

    <button class="btn btn-primary" onclick="window.open('https://wa.me/+55<?php echo $telefone; ?>/?text=<?php if ($_GET['sms']) echo $_SESSION['sms'];
                                                                                                            else echo $sms; ?>', '_blank');">Enviar comprovante por WhatsApp</button>

    <button class="btn btn-success" onclick="imprimir('cel');">Imprimir Comprovante Celular</button>
    <button class="btn btn-success" onclick="imprimir('pc');">Imprimir Comprovante PC</button>
    <?php if (strtotime($rifa['rifa_dtsorteio'] . " 18:39:59") > time()) { ?>
        <?php if ($dezenabolao) { ?>
            <button class="btn btn-primary" onclick="javascript:location.href='index.php?p=ver_bilhetes_bolao_dezena&limpar_carrinho=true&rifa=<?= $rifa_cod; ?>';">Revender uma Rifa</button>
        <?php } else { ?>
            <button class="btn btn-primary" onclick="javascript:location.href='index.php?p=ver_bilhetes&limpar_carrinho=true&rifa=<?= $rifa_cod; ?>';">Revender uma Rifa</button>
    <?php }
    } ?>
    <button class="btn btn-primary" onclick="javascript:location.href='index.php?p=debitor';">Pagar o Promotor da Rifa</button>
    <button class="btn btn-primary" onclick="javascript:location.href='../index.php?p=inicial';">Página Inicial</button>
    <?php if (isset($_SESSION['relatorio'])) { ?>
        <button onclick="javascript:window. close();/*location.href='index.php?p=venda&loadSession=true';*/" class="btn btn-primary">Voltar para Vendas</button>
    <?php } ?>
<?php } else { ?>

<button class="btn btn-primary" onclick="javascript:gerar_pdf();">RECIBO DE RESERVA</button>
<?php } ?>
    <div class="spacer"></div>
</div>
<?php if ($dezenabolao) { ?>
    <div class="col-lg-12">
        <p><?php if ($_GET['sms']) echo $_SESSION['sms'];
                else echo $sms; ?></p>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
    </div>
<?php } else if ($_SESSION['repetir_venda']) { ?>
    <p><?php if ($_GET['sms']) echo $_SESSION['sms']; ?></p>
<?php } ?>
<div class="col-lg-12 " id="bloco-impressao" style="color:white; text-align:left; font-size:12px">
WWW.RIFASBRASIL.COM.BR 
------------------------
TITULO DA RIFA: 
<?= $rifa['rifa_titulo'] . "\n"; ?>
Sorteio: <?= $dataSorteio . "\n"; ?>
VENDEDOR: 
<?= $vendedor['usu_nome'] . "\n"; ?>
VALOR: R$ <?= number_format($rifa['rifa_valorbilhete'], 2, ',', '.') . "\n"; ?>
------------------------
Data/Hora: 
<?= $data; ?>

NOME: <?php echo $usuario['usu_nome'] . "\n"; ?>
Tel/WhatsApp: 
<?= $telefoneFormatado . "\n"; ?>
BAIRRO/REGIAO: 
<?php echo $usuario['usu_regiao'] . "\n"; ?>
BILHETE(S): <?= "\n" ?>

[ <?= $bilhete_str; ?> ]
<?= "\n" ?>
TOTAL: R$ <?php echo number_format($compra['comp_valortotal'], 2, ',', '.') . "\n"; ?>
PAGAMENTO: <?php
if ($compra['comp_status_revenda'] == 1 || $compra['comp_situacao'] == '4' || $compra['comp_situacao'] == '3') {
    echo "SIM - PAGO";
} else {
    echo "AGUARDANDO PAGAMENTO";
}
?>
<?= "\n" ?>
<?php if ($compra['comp_parcelamento_entrada'] > 0 && $compra['comp_parcelamento_entrada'] < $compra['comp_valortotal']) { ?>

ENTRADA: <?php echo number_format($compra['comp_parcelamento_entrada'], 2, ',', '.'); ?>

VALOR RESTANTE: R$ <?= number_format($compra['comp_valortotal'] - $compra['comp_parcelamento_entrada'], 2, ',', '.'); ?>

Data PROX. PGTO: <?= date('d/m/Y', strtotime($compra['comp_parcelamento_data'])); ?>
<?php } ?>
------------------------
Responsavel: <?= $rifa['usu_nome'] . "\n"; ?>
TEL: <?= formataTelefone($rifa['usu_celular']); ?>
</div>


<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.4.1/jspdf.debug.js" integrity="sha384-THVO/sM0mFD9h7dfSndI6TS0PgAGavwKvB5hAxRRvc0o9cPLohB0wb/PTA7LdUHs" crossorigin="anonymous"></script>

    <script src="https://unpkg.com/jspdf@latest/dist/jspdf.min.js"></script>
    <script src="../js/html2canvas.min.js"></script>


    <script src="https://unpkg.com/jspdf-autotable@3.5.3/dist/jspdf.plugin.autotable.js"></script>

<script>

    function confirmarReserva() {

        let texto = `RESERVA DE BILHETE\n
Rifa\n*<?= $rifa['rifa_titulo']; ?>*\n
Olá, meu nome é:\n
*<?php echo $usuario['usu_nome']; ?>*\n
Por favor, confirmar a RESERVA do(s) bilhete(s):\n
*[ <?= $bilhete_str; ?> ]*\n
O ID da transação é: \n
*<?= $compra['comp_cod'] ?>*\n
Farei o pagamento na(s) conta(s) abaixo:\n\n`;

<?php $i = 0;
foreach ($dados_bancarios_rev as $dado_bancario) { ?>
texto += `*Conta <?= ($i === 0) ? "Principal" : $i  ?>*\n`;
texto += `Banco: <?= $dados_bancarios_rev[$i]['banco']; ?>\n`;
texto += `Agência: <?= $dados_bancarios_rev[$i]['agencia']; ?>\n`;
texto += `Conta: <?= $dados_bancarios_rev[$i]['conta']; ?>\n`;
texto += `<?php if ($dados_bancarios_rev[$i]['nome_completo'] != '') echo "Nome do Titular: " . $dados_bancarios_rev[$i]['nome_completo'] . " \n" ?>`;
texto += `<?php if ($dados_bancarios_rev[$i]['cpf'] != '') echo "CPF do Titular: " . $dados_bancarios_rev[$i]['cpf'] . " \n" ?>`;
<?php $i++; } ?>

texto += `\nAssim que fizer o pagamento enviarei o comprovante!\n\n*Concordo que a reserva sera cancelada caso nao haja comprovacao do pagamento em ate 48h.*`;

        texto = window.encodeURIComponent(texto);
  
        window.open("https://api.whatsapp.com/send?phone=+55<?= formataTelefone($dados_revendedor['usu_celular']); ?>&text=" + texto, "_blank");

    }

    
    function gerar_pdf () {

        var doc = new jsPDF('p', 'pt', 'a4');

        $.post('page/venda.php', {
            comprovantes: <?= $compra['comp_cod'] ?>,
            gerarComprovantes: 'pc'
        })
        .done(
            function(r) {

                console.log(r);

                var elementHandler = {
                  '#ignorePDF': function (element, renderer) {
                    return true;
                  }
                };

                doc.fromHTML(
                    r,
                    15,
                    15,
                    {
                      'width': 180,'elementHandlers': elementHandler
                    });

                doc.save();



            })
        .fail(function(r) {
                console.log(r);
                alert('Falhou ao imprimir!');
            });
        


        

    }


    function imprimir(tipo) {

        if (tipo == 'cel') {
            //alert("Imprimiu");

            window.print();

            MyJavaScriptInterface.printHtml($('#bloco-impressao').html());
        } else {
            $.post('page/venda.php', {
                    comprovantes: <?= $compra['comp_cod'] ?>,
                    gerarComprovantes: 'pc'
                })
                .done(
                    function(r) {

                        console.log(r);

                        var mywindow = window.open('', 'PRINT', 'height=400,width=600');
                        mywindow.document.write('<html><head><title>Imprimir</title><style>body{font-family:Arial; font-size:12;}</style>');
                        mywindow.document.write('</head><body >');
                        mywindow.document.write(r);
                        mywindow.document.write('</body></html>');

                        mywindow.document.close(); // necessary for IE >= 10
                        mywindow.focus(); // necessary for IE >= 10*/

                        mywindow.print();
                        mywindow.close();

                    })
                .fail(
                    function(r) {
                        console.log(r);
                        alert('Falhou ao imprimir!');
                    });
        }

    }
</script>

<?php //unset($_SESSION['usuario_sem_login']); ?>