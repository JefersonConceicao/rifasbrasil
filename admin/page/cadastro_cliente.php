<!-- Conteudo Section -->
<?php

include("../class/conexao.php");

include("../class/function_validarCpf.php");

include("../class/function_limparNumero.php");
include("../class/function_primeiroNome.php");

//echo "<PRE>";
//var_dump($_SESSION);
//echo "</pre>";
// Cadastro de Usuários
if (!isset($_SESSION))
    @session_start();

if (isset($_POST['cadastrar'])) {

    $_SESSION['nome'] = $mysqli->escape_string($_POST['nome']);
    $_SESSION['telefone'] = limparNumero($_POST['telefone']);
    $_SESSION['regiao'] = $mysqli->escape_string($_POST['regiao']);
    $_SESSION['tipo_venda'] = $_POST['tipo_venda'];

    if (($_SESSION['nome']) ==  "")
        $erro[] = "Digite seu nome";

    if (($_SESSION['regiao']) ==  "")
        $erro[] = "Digite o Bairro/Região";

    if (strlen($_SESSION['telefone']) < 10)
        $erro[] = "Digite seu telefone com DDD. Por exeplo: 99 9999-9999";


    if (count($erro) == 0) {

        if (isset($_GET['editar_venda'])) {

            // insere um novo usuario e atualiza a id dele na tabela vendas

            $sql_code_usuario =
                "INSERT INTO tbl_usuario
            (
            usu_nome,
            usu_celular,
            usu_regiao,
            usu_admin
            )VALUES(
            '{$_SESSION['nome']}',
            '{$_SESSION['telefone']}',
            '{$_SESSION['regiao']}',
            'N'
            )";

            $editar_venda = intval($_GET['editar_venda']);

            $confirma_usuario = $mysqli->query($sql_code_usuario) or die($mysqli->error);
            $usu_codigo = $mysqli->insert_id;

            $mysqli->query("UPDATE tbl_compra SET comp_cliente = '$usu_codigo', comp_data = NOW(), comp_revendedor = '{$_SESSION['usuario']}' WHERE comp_cod = '$editar_venda'") or die($mysqli->error);

            // start
            $rifa_cod = -1;
            $bilhetes_f = '';
            foreach ($_SESSION['carrinho_admin'] as $rifa => $void) {
                $rifa_cod = $rifa;
                foreach ($_SESSION['carrinho_admin'][$rifa] as $void => $cod_bilhete) {
                    $bilhetes_f .= str_pad($cod_bilhete, 2, "0", STR_PAD_LEFT) . "-";
                }
            }

            $sql_query = $mysqli->query("SELECT rifa_titulo, usu_nome, rifa_dtsorteio From tbl_rifas, tbl_usuario where rifa_cod = '$rifa_cod' and usu_cod = rifa_dono") or die($mysqli->error);
            $temp = $sql_query->fetch_assoc();

            $dono_da_rifa = $temp['usu_nome'];
            $nome_rifa = substr($temp['rifa_titulo'], 0, 25);
            $dtsorteio = date("d/m/Y", strtotime($temp['rifa_dtsorteio']));

            $sql_query = $mysqli->query("SELECT comp_status_revenda, comp_valortotal FROM tbl_compra WHERE comp_cod = '$editar_venda'") or die($mysqli->error);
            $temp = $sql_query->fetch_assoc();

            if ($temp['comp_status_revenda'] == '1')
                $final = "PAGO";
            else
                $final = "NAO PAGO";

            $total_somado = $temp['comp_valortotal'];

            $_SESSION['sms'] = primeiroNome($dono_da_rifa) . ": Ola " . primeiroNome($_SESSION['nome']) . ", seu(s) jogo(s): {$editar_venda}({$bilhetes_f}) [M.F: " . $_GET['serie'] . "] (" . $nome_rifa . ") Sorteio/Inicio (" . $dtsorteio . ") $final - R$ " . number_format($total_somado, 2, ',', '.');

            unset($_SESSION['compra_ja_feita'],
            $_SESSION['rifa_dezena_bolao'],
            $_SESSION['rifa_dezena_bolao'],
            $_SESSION['qr_order'],
            $_SESSION['revendedor_admin'],
            $_SESSION['carrinho_admin'],
            $_SESSION['nome'],
            $_SESSION['telefone'],
            $_SESSION['regiao'],
            $_SESSION['comp_status_revenda'],
            $_SESSION['cliente_ja_cadastrado']);

            $url = "index.php?p=sucesso_venda&comp={$editar_venda}&usuario={$usu_codigo}&sms=true";
        } else
            $url = "index.php?p=carrinho";

        die("<script>location.href='{$url}';</script>");
    }
}

?>









<div class="col-lg-12">

    <h3><?php if (isset($_GET['editar_venda'])) echo "Editar";
        else echo 'Cadastrar'; ?> Cliente</h3>
    <hr>

    <?php

    if (isset($erro) && count($erro) > 0)

        foreach ($erro as $valor)

            echo "<div class=\"col-lg-12 bg-warning\"><p>$valor</p></div>";

    ?>



</div>



<div class="col-lg-6">

    <form id="cadastro" name="cadastro" method="POST" action="">

        <div class="form-group col-lg-12 text-left">


        </div>

        <div class="form-group col-lg-12">

            <label for="">Nome*</label>

            <input type="text" required name="nome" value="<?php echo $_SESSION[nome]; ?>" class="input-lg form-control">

        </div>

        <!--

                        <div class="form-group col-lg-12">

                            <label for="">E-mail*</label>

                            <input type="email" required name="email" value="<?php echo $_SESSION[email]; ?>" class="input-lg form-control">

                        </div>-->

        <div class="form-group col-lg-6">

            <label for="">Telefone*</label>

            <input type="tel" required name="telefone" value="<?php echo $_SESSION[telefone]; ?>" class="telefone input-lg form-control">

        </div>



        <div class="form-group col-lg-6">

            <label for="">Bairro/Região*</label>

            <input type="text" required name="regiao" value="<?php echo $_SESSION['regiao']; ?>" class="input-lg form-control">

        </div>



        <!--

                        <div class="form-group col-lg-6">

                            <label for="">CPF</label>

                            <input type="text"  name="cpf" value="<?php echo $_SESSION[cpf]; ?>" class="input-lg form-control">

                        </div> 

                        <div class="form-group col-lg-6">

                            <label for="">Senha*</label>

                            <input type="password" required name="senha"  class="input-lg form-control">

                        </div>

                        <div class="form-group col-lg-6">

                            <label for="">Repita a Senha*</label>

                            <input type="password" required name="rsenha"  class="input-lg form-control">

                        </div>-->


        <?php if (!isset($_GET['editar_venda']) && ($_SESSION['origem_revenda'] == true)) : ?>
            <div class="form-group col-lg-6">

                <p>Tipo de venda:</p>

                <label for="v1"><input id="v1" name="tipo_venda" value="paga" required type="radio"> Paga</label>

                <label for="v2"><input id="v2" name="tipo_venda" value="naopaga" required type="radio"> Não paga</label>

            </div>
        <?php endif; ?>

        <div class="form-group col-lg-12">

            <button type="submit" name="cadastrar" class="btn-lg btn-success btn"><?php if (isset($_GET['editar_venda'])) echo "Confirmar edição";
                                                                                    else echo 'Cadastrar'; ?></button>



        </div>



</div>





</form>

</div>



<div class="clearfix"></div>