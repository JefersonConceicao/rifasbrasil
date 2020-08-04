<?php

if(!$_SESSION ) @session_start();

include("../class/conexao.php");

?>
<style>
      label.simple{font-weight: normal;}
</style>
<form action="" method="POST">
      <div class="col-lg-6">
            <h3>Apuração Parcial</h3>
            <hr>
            <div class="col-lg-12">
                  <div class="form-group form-inline">
                        <label for="">Apurar Rifas vencendo em: </label>
                        <input type="text" required value="<?= $_POST['vencimento']; ?>" name="vencimento" class="datepicker form-control">
                  </div>
                  <div class="form-group">
                        <p><label for="">Selecione a Faixa</label></p>
                        
                        <p>
                        <label class="simple" for="1p">
                              <input type="radio" required <?php if($_POST['premio'] == '1') echo 'checked'; ?> name="premio" value="1" id="1p"> 1 prêmio apenas
                        </label><br>
                        <label class="simple" for="5p">
                              <input type="radio" required <?php if($_POST['premio'] == '5') echo 'checked'; ?> name="premio" value="5" id="5p"> 1º ao 5º prêmio
                        </label><br>
                        <label class="simple" for="10p">
                              <input type="radio" required <?php if($_POST['premio'] == '10') echo 'checked'; ?> name="premio" value="10" id="10p"> 1º ao 10ª prêmio
                        </label></p>
                  </div>

                  <div class="form-group">
                        <button type="submit" class="btn btn-primary">Próximo</button>
                  </div>
            </div>
      </div>
</form>
<?php if($_POST['vencimento'] && $_POST['premio']){ ?>
<form action="index.php?p=resultado_apuracao" method="POST">
      
      <div class="col-lg-6">
            <h3>Informe os Números Sorteados</h3>
            <hr>
            <input type="hidden" name="premio" value="<?= $_POST['premio']; ?>">
            <input type="hidden" name="vencimento" value="<?= $_POST['vencimento']; ?>">

            <?php for($k = 1; $k <= $_POST['premio']; $k++){ ?>
            <div class="col-lg-6">
                  <div class="form-group">
                        <input type="text" required maxlength="5" placeholder="Número <?= $k ;?>" name="numero[]" required class="form-control">
                  </div>
            </div>
            <?php } ?>

            <div class="col-lg-12">
                  <div class="form-group">
                        <button type="submit" class="btn btn-primary">Próximo</button>
                  </div>
            </div>
      </div>

</form>
<?php } ?>