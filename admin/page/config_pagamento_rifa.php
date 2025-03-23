<?php
include("../class/conexao.php");
$idRifa = $_GET['rifa_id'];

if (empty($idRifa)) {
    $idRifa = 8532;
}
?>

<form method="GET" action="index.php">
    <input type="hidden" name="p" value="qr_pagamento_rifa" />
    <input type="hidden" name="rifa_id" value="<?= $idRifa ?>" />

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label> Tipo do Pix</label>
                <select name="tipo_pix" class="form-control" id="tipoPix">
                    <option value="grafica" selected> Pix Grafica </option>
                    <option value="cliente"> Pix Cliente </option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="containerValoresPadrao" style="display:none">
        <div class="col-md-4">
            <div class="form-group">
                <label> Valores </label>
                <select name="tiposValores" class="form-control" disabled id="taxas">
                    <option value="valorPadrao" selected> Valor Padrao </option>
                    <option value="adicionarValor"> Adicionar Valor </option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="containerInputValor" style="display:none">
        <div class="col-md-4">
            <div class="form-group">
                <label> Informe o Valor </label>
                <input type="text" class="form-control dinheiro" name="valor_pix" disabled required />
            </div>
        </div>
    </div>

    <div class="row" id="containerTaxas" style="display:none">
        <div class="col-md-4">
            <div class="form-group">
                <label> Tipos de Taxas </label>
                <select name="tipoTaxa" class="form-control" disabled>
                    <option value=""> Selecione </option>
                    <option value="BPN"> BPN </option>
                    <option value="BPA"> BPA </option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top:2%">
        <div class="col-md-12">
            <button class="btn btn-success"> Gerar Pix <i class="fa fa-arrow-right"></i> </button>
        </div>
    </div>
</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $("#tipoPix").on("change", function(e) {
            let valTipoPix = $(this).val();
            if (valTipoPix == 'cliente') {
                $("#containerValoresPadrao").show()
                $("#containerValoresPadrao")
                    .find('select')
                    .removeAttr('disabled');
            } else {
                $("#containerValoresPadrao").hide()
                $("#containerValoresPadrao").find('select').attr("disabled", true);

                $("#taxas").val("valorPadrao").trigger('change')
            }
        });

        $("#taxas").on("change", function(e) {
            let tipoTaxa = $(this).val();

            if (tipoTaxa == 'adicionarValor') {
                $("#containerInputValor").show();
                $("#containerInputValor").find('input').removeAttr('disabled')

                $("#containerTaxas").show();

                $("#containerTaxas")
                    .find('select')
                    .removeAttr('disabled');
            } else {
                $("#containerInputValor").hide();
                $("#containerInputValor").find('input').attr('disabled', true);

                $("#containerTaxas").hide();
                $("#containerTaxas")
                    .find('select')
                    .attr('disabled', true);
            }
        })
    });
</script>