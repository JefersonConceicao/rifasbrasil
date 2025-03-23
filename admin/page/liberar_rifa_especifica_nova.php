<?php
session_start();
include("../class/conexao.php");

if (!empty($_POST)) {
    try {
        if (isset($_POST['cod_rifa'])) {
            // Adiciona o código da rifa à sessão
            $cod_rifa = $_POST['cod_rifa'];
            $sql_valor = "SELECT valor_por_id FROM configuracoes LIMIT 1";
            $result = $mysqli->query($sql_valor) or die($mysqli->error);
            $row = $result->fetch_assoc();

            $valor_por_id = $row['valor_por_id'];

            if (!isset($_SESSION['rifas'])) {
                $_SESSION['rifas'] = [];
            }

            $_SESSION['rifas'][] = [
                'cod_rifa' => $cod_rifa,
                'valor_por_id' => $valor_por_id
            ];

            echo "<script>alert('Rifa adicionada!'); location.href='index.php?p=liberar_rifa_especifica_nova';</script>";
        } elseif (isset($_POST['salvar'])) {
            // Processa todos os códigos de rifa armazenados na sessão
            foreach ($_SESSION['rifas'] as $cod_rifa) {
                $sql_code = "INSERT INTO tbl_situacao_rifa (
                    id_rifa,
                    situacao_rifa
                ) VALUES (
                    '$cod_rifa',
                    '2'
                )";
                $mysqli->query($sql_code) or die($mysqli->error);
            }

            // Limpa a sessão após salvar
            unset($_SESSION['rifas']);
            echo "<script>alert('Operação efetuada!'); location.href='index.php?p=rifa';</script>";
        }
    } catch (Exception $error) {
        var_dump($error->getMessage());
    }
}
?>

<div class="row">
    <form method="POST" action="index.php?p=liberar_rifa_especifica_nova">
        <input type="hidden" name="session" value="1" />
        <div class="col-md-4">
            <div class="form-group">
                <label> Informe o código da rifa (Somente números) </label>
                <input type="number" class="form-control" name="cod_rifa" />
            </div>
        </div>
        <div class="col-md-3" style="margin-top:2%;">
            <button class="btn btn-success">
                Adicionar
            </button>
        </div>
    </form>
</div>

<?php if (!empty($_SESSION['rifas'])): ?>
    <div class="row">
        <div class="col-md-12">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código da Rifa</th>
                        <th>Valor por ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0; // Inicializa o valor total
                    foreach ($_SESSION['rifas'] as $rifa):
                        $total += $rifa['valor_por_id'];
                    ?>
                        <tr>
                            <td><?php echo $rifa['cod_rifa']; ?></td>
                            <td>R$ <?php echo number_format($rifa['valor_por_id'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="index.php?p=gerar_pix">
    <?php foreach ($_SESSION['rifas'] as $rifa): ?>
        <input type="hidden" name="cod_rifa[]" value="<?php echo $rifa['cod_rifa']; ?>" />
    <?php endforeach; ?>
    <input type="hidden" name="total" value="<?php echo $total; ?>" />

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <button type="submit" name="salvar" class="btn btn-primary"> Gerar PIX </button>
            </div>
        </div>
    </div>
</form>