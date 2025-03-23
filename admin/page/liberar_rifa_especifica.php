<?php
include("../class/conexao.php");

if (!empty($_POST)) {
    try {
        $sql_code = "INSERT INTO tbl_situacao_rifa (
            id_rifa,
            situacao_rifa
        ) VALUES (
            '{$_POST['cod_rifa']}',
            '2'
        )";

        $mysqli->query($sql_code) or die($mysqli->error);
        echo "<script>alert('Operação efetuada!'); location.href='index.php?p=rifa';</script>";
    } catch (Exception $error) {
        var_dump($error->getMessage());
    }
}
?>

<form method="POST" action="index.php?p=liberar_rifa_especifica">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label> Informe o código da rifa (Somente números) </label>
                <input type="number" class="form-control" name="cod_rifa" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <button type="submit" class="btn btn-primary"> Salvar </button>
            </div>
        </div>
    </div>
</form>