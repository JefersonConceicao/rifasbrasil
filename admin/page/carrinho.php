<?php
if (!isset($_SESSION)) @session_start();
// Verifica se o usuário veio da página de revenda para exibir as opções de paga/não paga na tela seguinte
if (isset($_GET['origem']) && $_GET['origem'] == 'revenda') {
	$_SESSION['origem_revenda'] = true;
} else {
	unset($_SESSION['origem_revenda']);
}
if (isset($_POST['action']) && $_POST['action'] == 'set_series') {
      if (!isset($_POST['values']))
            die('{"error":"Defina todas as séries"}');
      $temp = array();
      $repetido = array();
      foreach ($_POST['values'] as $n => $val) {
            $val['serie'] = trim($val['serie']);
            if (!isset($val['serie']) || strlen($val['serie']) == 0)
                  die('{"error":"Defina a série ' . ($n + 1) . '"}');
            if (isset($_POST['proibidas']) && in_array($val['serie'], $_POST['proibidas']))
                  die('{"error":"Série ' . $val['serie'] . ' já reservada! Escolha outra."}');
            if (isset($repetido[$val['serie']]))
                  die('{"error":"Série ' . $val['serie'] . ' repetida. Altere-a por favor."}');
            $temp[] = $val['serie'];
            $repetido[$val['serie']] = true;
      }
      $_SESSION['series_customizadas'] = $temp;
      die('{"success":"true"}');
}
if ($_POST['cancelar_parcelamento']) {
      unset($_SESSION['entrada'], $_SESSION['proximo_pagamento']);
      die();
}
if ($_POST['bilhete_pedido']) {
      $_SESSION['bilhete_pedido'] = $_POST['bilhete_pedido'];
      die();
}
if ($_POST['data']) {
      $tmp = explode('/', $_POST['data']);
      if (count($tmp) != 3) {
            die(json_encode(array('erro' => 1, 'msg' => 'Data informada inválida!')));
      }
      $_SESSION['proximo_pagamento'] = strtotime("$tmp[2]-$tmp[1]-$tmp[0]");
      die(json_encode(array('erro' => 0, 'nova_data' => date('d/m/Y', $_SESSION['proximo_pagamento']))));
}
if ($_POST['somar']) {
      $_SESSION['entrada'] += str_replace(',', '.', $_POST['somar']);
      die(json_encode(array('novo_valor' => $_SESSION['entrada'])));
}
if ($_POST['proximo_pagamento']) {
      $pp = $_POST['proximo_pagamento'];
      $_SESSION['entrada'] = $_POST['entrada'];
      $_SESSION['proximo_pagamento'] = strtotime("+$pp days");
      die(json_encode(array_merge($_POST, array('data' => date('d/m/Y', $_SESSION['proximo_pagamento'])))));
}
if ($_POST['change_tipo_compra']) {
      $_SESSION['tipo_venda'] = $_POST['change_tipo_compra'];
      echo "Tipo de venda alterado para: " . $_POST['change_tipo_compra'];
      die();
}
if ($_POST['remover_bilhete']) {
      $rifa = intval($_POST['rifa']);
      $ch   = intval($_POST['remover_bilhete']);
      if (is_array($_SESSION['carrinho_admin'][$rifa])) {
            unset($_SESSION['carrinho_admin'][$rifa][$ch]);
      }
      die('Tudo ok!');
}
include("../class/conexao.php");
include("../class/function_surpresinha.php");
if ($_GET['carregar_compra']) {

      $_SESSION['carrinho_admin'] = array();
      $_SESSION['compra_ja_feita'] = intval($_GET['carregar_compra']);
      $comp_cod = intval($_GET['carregar_compra']);
      $sql_code = "SELECT *
      FROM tbl_compra, tbl_usuario
      WHERE comp_cod = '$comp_cod' and usu_cod = comp_cliente limit 1";
      $sql_query = $mysqli->query($sql_code) or die($mysqli->error);
      $compra = $sql_query->fetch_assoc();
      $_SESSION['entrada'] = $compra['comp_parcelamento_entrada'];
      $_SESSION['proximo_pagamento'] = strtotime($compra['comp_parcelamento_data']);
      //var_dump($compra);
      $_SESSION['nome']       = $compra['usu_nome'];
      $_SESSION['telefone']   =  $compra['usu_celular'];
      $_SESSION['regiao']     = $compra['usu_regiao'];
      $_SESSION['tipo_venda'] = ($compra['comp_status_revenda']) ? 'paga' : 'naopaga';
      $_SESSION['cliente_ja_cadastrado'] = $compra['comp_cliente'];
      $sql_code = "SELECT *
      FROM tbl_bilhetes 
      WHERE bil_compra = '$comp_cod'";

      $change_rifa = 0;
      if ($_GET['change_rifa'] > 0)
            $change_rifa = intval($_GET['change_rifa']);


      $sql_query = $mysqli->query($sql_code) or die($mysqli->error);
      $bilhetes = $sql_query->fetch_assoc();
      do {
            if ($change_rifa)
                  $_SESSION['carrinho_admin'][$change_rifa][] = $bilhetes['bil_numero'];
            else
                  $_SESSION['carrinho_admin'][$bilhetes['bil_rifa']][] = $bilhetes['bil_numero'];
      } while ($bilhetes = $sql_query->fetch_assoc());
}
if (isset($_GET['remover'])) {
      if (is_array($_SESSION['carrinho_admin'][intval($_GET['remover'])]))
            foreach ($_SESSION['carrinho_admin'][intval($_GET['remover'])] as $ch => $va) {
                  unset($_SESSION['carrinho_admin'][intval($_GET['remover'])][$ch]);
            }
      unset($_SESSION['carrinho_admin'][intval($_GET['remover'])]);
}
if (isset($_SESSION['carrinho_admin']) && is_array($_SESSION['carrinho_admin']) && count($_SESSION['carrinho_admin']) > 0) {
      foreach ($_SESSION['carrinho_admin'] as $ch => $v) {
            $sql_add .= " rifa_cod = '$ch' OR";
      }
      $sql_add = substr($sql_add, 0, -2);
      $sql_code = "SELECT rifa_cod, rifa_valorbilhete, dezena_bolao, rifa_titulo, rifa_maxbilhetes 
	FROM tbl_rifas 
	WHERE ({$sql_add}) 
	AND (rifa_vencedor IS NULL OR rifa_vencedor = '')";
      $sql_query = $mysqli->query($sql_code) or die($mysqli->error);
      $rifa_tmp = $sql_query->fetch_assoc();
      $rifa = array();
      $valor_total = 0;
      do {
            $rifa[] = $rifa_tmp['rifa_cod'];
            $rTitulo[$rifa_tmp['rifa_cod']] = $rifa_tmp['rifa_titulo'];
            $rPreco[$rifa_tmp['rifa_cod']] = $rifa_tmp['rifa_valorbilhete'];
            $valor_total += (count($_SESSION['carrinho_admin'][$rifa_tmp['rifa_cod']]) * $rifa_tmp['rifa_valorbilhete']);

            $nmax[$rifa_tmp['rifa_cod']] = $rifa_tmp['rifa_maxbilhetes'];
            $dezenabolao[$rifa_tmp['rifa_cod']] = $rifa_tmp['dezena_bolao'];
      } while ($rifa_tmp = $sql_query->fetch_assoc());

      // seleciona as series já utilizadas
      $sql_add_2 = str_replace('rifa_cod', 'bil_rifa', $sql_add);
      $run_query = $mysqli->query("SELECT c.comp_serie FROM tbl_compra c WHERE c.comp_serie != '' AND c.comp_serie IS NOT NULL AND c.comp_cod IN (SELECT bil_compra FROM tbl_bilhetes WHERE $sql_add_2 GROUP BY bil_compra)") or die($mysqli->error);
      $series_proibidas = array();
      $serisProibidasAsKey = array();
      while ($get_data = $run_query->fetch_assoc()) {
            $series_proibidas[] = $get_data['comp_serie'];
      }
} else
      die("<script>location.href='index.php?p=ver_bilhetes&rifa=" . $ch . "';</script>");

$cli = DBExecute("SELECT * FROM tbl_usuario where usu_cod = '{$_SESSION['usuario_admin']}'", $mysqli);
$cliente = $cli->fetch_assoc();
$com      = DBExecute("SELECT comissao FROM tbl_revendedor where rifa_cod = '$ch' and usu_cod = '{$_SESSION['usuario']}'", $mysqli);
$comissao = $com->fetch_assoc();
?>
<div class="col-lg-8">
      <h3>Carrinho</h3>
      <hr>
      <div class="col-lg-12">
            <?php if (count($_SESSION['carrinho_admin'])) { ?>
                  <table class="table table-responsive table-bordered table-stripped">
                        <tr style="font-weight:bold;">
                              <td width="50%">Rifa</td>
                              <td><?php if ($_SESSION['rifa_dezena_bolao']) echo "Apostas/Bilhetes";
                                          else echo 'Bilhetes'; ?></td>
                              <td>Subtotal</td>
                              <td>Total</td>
                              <td></td>
                        </tr>
                        <?php
                              if (is_array($rifa))
                                    foreach ($rifa as $cod) { ?>
                              <tr>
                                    <td><?php echo $rTitulo[$cod]; ?></td>
                                    <td>
                                          <?php
                                                      $nBilhetes = 0;
                                                      $r = "";
                                                      $trocar_aposta = 0;
                                                      $current_aposta = 0;
                                                      $apostas_bolao = array();
                                                      if (is_array($_SESSION['carrinho_admin'][$cod])) {
                                                            foreach ($_SESSION['carrinho_admin'][$cod] as $indice => $bilhete) {

                                                                  if (!$_SESSION['rifa_dezena_bolao'])
                                                                        $r .= str_pad($bilhete, strlen($nmax[$cod]) - 1, "0", STR_PAD_LEFT) . " [<a href='javascript: void(0);' onclick='remover_bilhete($cod, $indice);'>X</a>], ";
                                                                  else {
                                                                        if (!isset($apostas_bolao[$current_aposta]))
                                                                              $apostas_bolao[$current_aposta] = array();
                                                                        $apostas_bolao[$current_aposta][] = $bilhete;
                                                                        $r .= str_pad($bilhete, 2, "0", STR_PAD_LEFT) . "<!--[<a href='javascript: void(0);' onclick='remover_bilhete($cod, $indice);'>X</a>]-->, ";
                                                                        /*str_pad($bilhete, 2, "0", STR_PAD_LEFT).", ";*/
                                                                  }

                                                                  $nBilhetes++;
                                                                  if ($_SESSION['rifa_dezena_bolao']) {
                                                                        $trocar_aposta++;
                                                                        if ($trocar_aposta == $dezenabolao[$cod]) {
                                                                              $r .= "<hr>";
                                                                              $current_aposta++;
                                                                              $trocar_aposta = 0;
                                                                        }
                                                                  }
                                                            }
                                                            if (!isset($_SESSION['rifa_dezena_bolao']) || $_SESSION['rifa_dezena_bolao'] == false)
                                                                  echo substr($r, 0, -2);
                                                            else
                                                                  echo $r;
                                                      }

                                                      ?>
                                    </td>
                                    <td>R$ <?php echo number_format(($rPreco[$cod]), 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format(($nBilhetes * $rPreco[$cod]), 2, ',', '.'); ?></td>
                                    <td class="text-right">
                                          <a href="index.php?p=carrinho&remover=<?php echo $cod; ?>"><img width="16" src="../img/deletar.png" alt=""></a>
                                    </td>
                              </tr>
                        <?php } ?>
                  </table>

                  <?php
                        if (isset($_SESSION['rifa_dezena_bolao']) && $_SESSION['rifa_dezena_bolao'] != false && (!isset($_SESSION['qr_order']) || $_SESSION['qr_order'] == false)) {

                              unset($_SESSION['series_customizadas']);
                              //var_dump($series_proibidas);
                              echo "<p><b>" . count($apostas_bolao) . "</b> aposta(s) registrada(s).<br>Escolha <b>" . count($apostas_bolao) . "</b> séries abaixo:</p>";

                              if (isset($_SESSION['surpresinha_serie'])) {
                                    $mysql_series = array();
                                    foreach ($apostas_bolao as $k => $ab) {
                                          $final_arr = array();
                                          foreach ($ab as $c) {
                                                $final_arr[] = str_pad($c, 2, '0', STR_PAD_LEFT);
                                          }
                                          $mysql_series[] = " sequencia = '" . implode('-', $final_arr) . "' ";
                                    }

                                    $mysql_series = "SELECT * FROM loteamento_rifa_bilhetes WHERE rifa = '{$rifa[0]}' AND (" . implode(' OR ', $mysql_series) . ") ORDER BY serie DESC";
                                    $sql_query = $mysqli->query($mysql_series) or die($mysqli->error);
                                    $tmp = $sql_query->fetch_assoc();

                                    $series = array();


                                    do {
                                          $series[$tmp['sequencia']] = $tmp['serie'];
                                    } while ($tmp = $sql_query->fetch_assoc());
                              }

                              foreach ($apostas_bolao as $k => $ab) {

                                    $final_arr = array();
                                    foreach ($ab as $c) {
                                          $final_arr[] = str_pad($c, 2, '0', STR_PAD_LEFT);
                                    }
                                    if (isset($_SESSION['surpresinha_serie']) && count($final_arr) > 0 && $series[implode('-', $final_arr)]) {
                                          $seriebuscada = true;
                                          $serieRes = $series[implode('-', $final_arr)];
                                    } else {
                                          $seriebuscada = false;
                                          do {
                                                $serieRes = mt_rand(0, 9999);
                                          } while (in_array($serieRes, $series_proibidas));
                                    }


                                    ?>
                              <div class="form-group form-inline">
                                    <input type="text" size="<?php if ($seriebuscada) echo '15'; else echo '5'; ?>" readonly value="<?= str_pad($serieRes, 4, '0', STR_PAD_LEFT); ?>" maxlength="5" data-bilhetes="<?= implode(',', $ab); ?>" id="serie_personalizada_<?= $k; ?>" class="serie_escolher form-control">
                                    <button <?php if ($seriebuscada) echo 'disabled'; ?> type="button" onclick="escolher_serie('<?= $k; ?>');" class="btn-success btn">Escolher</button>

                                    <button type="button" class="btn btn-primary" onclick="pesquisa_serie_modal('<?= $k; ?>');">Pesquisar/Trocar</button>
                              </div>
                        <?php } ?>

                        <hr>
                  <?php } ?>

                  <div <?php if(isset($_SESSION['usuario_sem_login']) || (isset($_SESSION['origem_revenda']) && $_SESSION['origem_revenda'])) echo 'style="display:none;"'; ?> class="form-group form-inline">
                        <label for="">Desconto: </label>
                        <input type="text" name="desconto" id="desconto" class="form-control">
                        <button type="button" onclick="aplicarDesconto();" class="btn">Aplicar Desconto</button>
                  </div>
                  <div <?php if(isset($_SESSION['usuario_sem_login']) || (isset($_SESSION['origem_revenda']) && $_SESSION['origem_revenda'])) echo 'style="display:none;"'; ?> class="form-group">
                        <small>Você pode informar um valor em reais (ex: 50) ou uma porcentagem (ex: 15%)</small>
                  </div>
                  <div <?php if(isset($_SESSION['usuario_sem_login']) || (isset($_SESSION['origem_revenda']) && $_SESSION['origem_revenda'])) echo 'style="display:none;"'; ?> class="form-group form-inline">
                        <button onclick="$('#mParcelado').modal('show');" class="btn btn-default">PARCELADO</button>
                  </div>
                  <?php if (!$_SESSION['rifa_dezena_bolao'] && !isset($_SESSION['usuario_sem_login'])) { ?>
                        <div <?php if(isset($_SESSION['origem_revenda']) && $_SESSION['origem_revenda']) echo 'style="display:none;"'; ?> class="form-group form-inline">
                              <label for="c">
                                    <input id="c" onclick="bilhete_pedido();" name="bilhete_pedido" value="1" type="checkbox"> Adicionar aos Bilhetes Pedidos
                              </label>
                        </div>
                  <?php } ?>
            <?php } else echo "<p>Carrinho vazio</p>"; ?>
      </div>
</div>
<div class="col-lg-4">
      <h3>Comprador</h3>
      <hr>
      <div class="col-lg-12 form-group">
            <label for="">Nome</label>
            <p><?php echo $_SESSION[nome]; ?></p>
      </div>
      <div class="col-lg-6 form-group">
            <label for="">Telefone</label>
            <p><?php echo $_SESSION[telefone]; ?></p>
      </div>
      <div class="col-lg-6 form-group">
            <label for="">CPF</label>
            <p><?php echo $_SESSION[cpf]; ?></p>
      </div>
      <div class="col-lg-12 form-group">
            <label for="">Bairro/Região</label>
            <p><?php echo $_SESSION['regiao']; ?></p>
      </div>
      <div id="parcelado" class="<?php if (!$_SESSION['entrada']) echo 'hidden'; ?>">
            <div class="col-lg-12 form-group">
                  <label for="">Valor da Compra</label>
                  <p id="valor_compra"><?= "R$ $valor_total"; ?></p>
            </div>
            <div class="col-lg-12 form-group">
                  <label for="">Valor de Entrada</label>
                  <p id="valor_entrada"><?= "R$ " . $_SESSION['entrada']; ?> <a href="javascript: void(0);" onclick="adicionar_valor();">adicionar</a></p>
            </div>
            <div class="col-lg-12 form-group">
                  <label for="">Valor Restante</label>
                  <p id="valor_restante"><?= "R$ " . ($valor_total - $_SESSION['entrada']); ?></p>
            </div>
            <div class="col-lg-12 form-group">
                  <label for="">Data do Próximo Pagamento</label>
                  <p id="data_proximo"><?= date('d/m/Y', $_SESSION['proximo_pagamento']); ?> <a href="javascript: void(0);" onclick="alterar_data();">alterar</a></p>
            </div>
            <div class="col-lg-12 form-group">
                  <button id="cancelar_parcelamento" onclick="cancelar_parcelamento();" type="button" class="btn btn-danger">Cancelar Parcelamento</button>
            </div>
      </div>
      <?php if (isset($_SESSION['origem_revenda']) && $_SESSION['origem_revenda'] == true) { ?>
            <div class="col-lg-12 form-group">
                  <h3>Tipo de Venda</h3>
                  <hr>
            </div>
            <div class="form-group col-lg-12">
                  <label for="v1"><input id="v1" <?php if ($_SESSION['tipo_venda'] == 'paga') echo 'checked'; ?> onchange="change_tipo_compra(this.value);" name="tipo_venda" value="paga" required type="radio"> Paga</label>
                  <label for="v2"><input id="v2" <?php if ($_SESSION['tipo_venda'] == 'naopaga') echo 'checked'; ?> onchange="change_tipo_compra(this.value);" name="tipo_venda" value="naopaga" required type="radio"> Não paga</label>
            </div>
      <?php } ?>
</div>
<?php if (count($_SESSION['carrinho_admin']) > 0) { ?>
      <div class="col-lg-12 text-center">
            <?php if ($valor_total > 0)
                        echo "<h4>TOTAL R$ <span id='valor_total'>" . $valor_total . "</span></h4>"; ?>
            <button onclick="javascript: goToPage();" class="btn-success btn">Finalizar Compra</button>
      </div>
<?php } ?>
<!-- Modal -->
<div id="mParcelado" class="modal fade" role="dialog">
      <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                  <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Parcelado</h4>
                  </div>
                  <div class="modal-body">
                        <div class="form-group">
                              <label for="">Valor de Entrada</label>
                              <input type="text" class="form-control" id="entrada" name="entrada">
                        </div>
                        <div class="form-group">
                              <label for="">Próximo Pagamento</label>
                              <select name="proximo_pagamento" id="proximo_pagamento" class="form-control">
                                    <option value="">Selecione</option>
                                    <option value="10">10 dias</option>
                                    <option value="15">15 dias</option>
                                    <option value="30">30 dias</option>
                              </select>
                        </div>
                  </div>
                  <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                        <button onclick="salvar_parcelamento();" class="btn btn-success">Salvar</button>
                  </div>
            </div>
      </div>
</div>
<?php if (isset($_SESSION['rifa_dezena_bolao']) && (!isset($_SESSION['qr_order']) || $_SESSION['qr_order'] == false)) {
      foreach ($apostas_bolao as $k => $ab) { ?>


            <div id="modal_escolha_pesquisa_<?= $k; ?>" class="modal fade" role="dialog">
                  <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content">
                              <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Pesquise uma série</h4>
                              </div>
                              <div class="modal-body">
                                    <div class="form-group">
                                          <p hidden id="msg-serie-disponivel<?= $k; ?>">Este número de série está <b>disponível</b>!</p>
                                          <p hidden id="msg-serie-indisponivel<?= $k; ?>" style="color:red">Número de série <b>indisponível</b>. Por favor, digite outro.</p>
                                          <label for="pesquisa_serie">Série</label>

                                          <input type="text" class="form-control" id="pesquisa_serie_input<?= $k; ?>" placeholder="Digite uma série...">
                                          <input hidden id="serie-selecionada-pesquisa" value="<?= $k; ?>">
                                          <button class="btn btn-primary" id="btn_pesquisar_serie" style="margin-top:2%;" onclick="pesquisar_serie('<?= $k; ?>');">Pesquisar</button>
                                          <button id="adicionar-nova-serie<?= $k; ?>" class="btn btn-success" style="margin-top:2%; margin-left:2%; display:none" onclick="set_serie_pesquisada('<?= $k; ?>')">Adicionar Série</button>
                                    </div>
                                    <div id=" result">
                                    </div>
                              </div>
                        </div>
                  </div>
            </div>

            <div id="modal_escolha_<?= $k; ?>" class="modal fade" role="dialog">
                  <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content">
                              <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Escolha uma outra série</h4>
                              </div>
                              <div class="modal-body">
                                    <div class="form-group">
                                          <input type="hidden" id="id_serie_selecionada">
                                          <ul>
                                                <?php
                                                            $bilhetes = array();
                                                            for ($k = 0; $k < 10; $k++) {
                                                                  do {
                                                                        $number = mt_rand(0, 9999);
                                                                  } while (in_array($number, $series_proibidas));
                                                                  echo '<li><a href="javascript:void(0);" onclick="set_serie_selecionada(' . $number . ');">' . str_pad($number, 4, '0', STR_PAD_LEFT) . '</a></li>';
                                                            }
                                                            ?>
                                          </ul>
                                    </div>
                              </div>
                        </div>
                  </div>
            </div>

<?php
      }
} ?>

<!-- Modal -->
<div id="modal" class="modal fade" role="dialog">
      <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                  <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">As seguintes séries já foram vendidas:</h4>
                  </div>
                  <div class="modal-body">
                        <div class="form-group">
                              <p id="series_lista"></p>
                              <p>Quer editar a venda?</p>
                        </div>
                        <div class="clearfix"></div>
                  </div>
                  <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                        <button id="btn_modal" type="button" class="btn btn-success">Sim</button>
                  </div>
            </div>

      </div>
</div>

<script>
      ////Quando o operador ler o QRCODE a a serie ja tiver VENDIDA  numa rifa ainda nao VENCIDA o pop up avisa e pergunta se operador quer EDITAR a VENDA  se sim permitir mudar NOME,  TELEFONE E BAIRRO

      <?php

      if (isset($_SESSION['qr_order']) && $_SESSION['qr_order'] != false) {

            $todas_series_do_carrinho = array();
            $sql_series = array();
            foreach ($apostas_bolao as $k => $ab) {

                  $final_arr = array();
                  foreach ($ab as $d) {
                        $final_arr[] = str_pad($d, 2, '0', STR_PAD_LEFT);
                  }
                  $consulta_serie = db_select($mysqli, "SELECT serie 
          FROM loteamento_rifa_bilhetes 
          WHERE sequencia = '" . implode('-', $final_arr) . "' AND rifa = '{$rifa[0]}'", 1);
                  $serieRes = $consulta_serie['serie'];

                  $seriebuscada = false;
                  if (!isset($serieRes) || $serieRes == '') {
                        do {
                              $serieRes = mt_rand(0, 9999);
                        } while (in_array($serieRes, $series_proibidas));
                  } else {
                        $seriebuscada = true;
                        $todas_series_do_carrinho[] = $serieRes;
                  }
            }

            if (count($todas_series_do_carrinho) > 0) {
                  $sql_check = array();
                  foreach ($todas_series_do_carrinho as $serie_carrinho) {
                        $sql_check[] = " comp.comp_serie like '%{$serie_carrinho}%' ";
                  }

                  $sql_check = implode(' OR ', $sql_check);
                  $sql = "SELECT comp.comp_cod, comp.comp_serie FROM tbl_bilhetes bil, tbl_rifas rifas, tbl_compra comp WHERE bil.bil_rifa = '{$rifa[0]}' AND rifas.rifa_cod = bil.bil_rifa AND rifas.rifa_dtsorteio >= DATE(NOW()) AND bil.bil_compra = comp.comp_cod AND ($sql_check) GROUP BY comp.comp_serie ORDER BY bil.bil_numero";

                  $bilhetes_vendidos = db_select($mysqli, $sql);
                  $series_ja_vendidas = false;
                  $venda_original = false;
                  if (count($bilhetes_vendidos) > 0) {
                        foreach ($bilhetes_vendidos as $row) {
                              $venda_original = $row['comp_cod'];
                              $series_ja_vendidas = $row['comp_serie'];
                        }
                  }

                  if ($series_ja_vendidas !== false) {
                        ?>
                        window.onload = function() {
                              alert("As seguintes séries já foram vendidas: <?php echo $series_ja_vendidas; ?>. Você será redirecionado para a tela de Edição.");
                              location.href = "index.php?p=cadastro_cliente&editar_venda=<?= $venda_original; ?>&serie=<?php echo $series_ja_vendidas; ?>";
                        };
      <?php

                  }
            }
      }
      ?>

      function set_serie_selecionada(number) {
            let input = $('#id_serie_selecionada').val();
            $('#serie_personalizada_' + input).val(number);
            $('#modal_escolha_' + input).modal('hide');
      }


      <?php if ($nBilhetes == 0) echo "location.href='index.php?p=ver_bilhetes&rifa=" . $rifa[0] . "'"; ?>

      function alterar_data() {
            var data = prompt("Digite a nova data:");
            $.post('page/carrinho.php', {
                        data: data
                  })
                  .done(function(r) {
                        console.log(r);
                        r = JSON.parse(r);
                        if (r.erro == 1)
                              return alert(r.msg);
                        $('#data_proximo').html(r.nova_data);
                  })
                  .fail(function(r) {
                        console.log(r);
                  });
      }

      function adicionar_valor() {
            var valor_total = parseFloat($('#valor_total').html());
            var valor = prompt("Digite o valor:").replace(',', '.');
            $.post('page/carrinho.php', {
                        somar: valor
                  })
                  .done(function(r) {
                        console.log(r);
                        r = JSON.parse(r);
                        $('#valor_entrada').html('R$ ' + r.novo_valor);
                        $('#valor_restante').html('R$ ' + (valor_total - r.novo_valor));
                        //location.href='index.php?p=carrinho';
                  })
                  .fail(function(r) {
                        console.log(r);
                  });
      }

      function cancelar_parcelamento() {
            show_loading();
            $.post('page/carrinho.php', {
                        cancelar_parcelamento: true
                  })
                  .done(function(r) {
                        console.log(r);
                        end_loading();
                        $('#parcelado').addClass('hidden');
                  })
                  .fail(function(r) {
                        console.log(r);
                        end_loading();
                  });
      }

      function salvar_parcelamento() {
            var entrada = parseFloat($('#entrada').val().replace(',', '.'));
            var proximo_pagamento = $('#proximo_pagamento').val();
            var valor_total = parseFloat($('#valor_total').html());
            if (entrada > valor_total)
                  return alert('A entrada não pode ser maior que o valor total!');
            if (!proximo_pagamento)
                  return alert('Preencha o Próximo Pagamento.');
            if (!entrada)
                  entrada = 0;
            show_loading();
            $.post('page/carrinho.php', {
                        entrada: entrada,
                        proximo_pagamento: proximo_pagamento
                  })
                  .done(function(r) {
                        console.log(r);
                        r = JSON.parse(r);
                        // preencher as informações
                        // valor_compra, valor_entrada, valor_restante, data_proximo

                        $('#valor_compra').html('R$ ' + valor_total);
                        $('#valor_entrada').html('R$ ' + entrada);
                        $('#valor_restante').html('R$ ' + (valor_total - entrada));
                        $('#data_proximo').html(r.data);
                        $('#parcelado').removeClass('hidden');
                        end_loading();
                        $('#mParcelado').modal('hide');
                  })
                  .fail(function(r) {
                        console.log(r);
                        end_loading();
                        $('#mParcelado').modal('hide');
                  });
      }

      function bilhete_pedido() {
            console.log("FINAL: " + $('#c').prop('checked'));
            $.post('page/carrinho.php', {
                        bilhete_pedido: $('#c').prop('checked')
                  })
                  .done(function(r) {
                        console.log(r);
                  })
                  .fail(function(r) {
                        console.log(r);
                  });
      }

      function pesquisa_serie_modal(id) {
            $('#modal_escolha_pesquisa_' + id).modal('show');
            $('#id_serie_selecionada').val(id);
      }


      function verifica_serie(value) {
            var json_array_series = <?php echo json_encode($series_proibidas); ?>;
            //console.log(json_array_series);
            /*
            verificação do número
            if (value <= 0 || value >= 99999) {
                  alert('Númeração inválida. Por favor, digite outro número');
                  return true;
            }*/
            if (value != '') {
                  var found = json_array_series.includes(value);
                  if (!found) {
                        return false;
                  } else {
                        return true;
                  }
            }
      }


      function set_serie_pesquisada(id) {
            let value = $('#pesquisa_serie_input' + id).val();
            let found = verifica_serie(value);
            if (found === false) {
                  //preenchimento com 0
                  //let new_value = ('0' + value).slice(-2);
                  $('#serie_personalizada_' + id).val(value);
                  $('#modal_escolha_pesquisa_' + id).modal('hide');
                  $("#msg-serie-disponivel" + id).hide('fast');
                  $("#adicionar-nova-serie" + id).hide('fast');
            } else {
                  $("#msg-serie-disponivel" + id).hide('fast');
                  $("#adicionar-nova-serie" + id).hide('fast');
                  $("#msg-serie-indisponivel" + id).show('fast');
            }

      }

      function pesquisar_serie(id) {
            var value = $('#pesquisa_serie_input' + id).val();
            var found = verifica_serie(value);
            if (found === false) {
                  $("#msg-serie-disponivel" + id).show('fast');
                  $("#adicionar-nova-serie" + id).show('fast');
                  $("#msg-serie-indisponivel" + id).hide('fast');
            } else {
                  $("#msg-serie-disponivel" + id).hide('fast');
                  $("#adicionar-nova-serie" + id).hide('fast');
                  $("#msg-serie-indisponivel" + id).show('fast');
            }
      }

      function escolher_serie(id) {
            $('#modal_escolha_' + id).modal('show');
            $('#id_serie_selecionada').val(id);
      }

      function goToPage() {

            function callback() {
                  let vTotal = <?= $valor_total; ?>,
                        nTotal = parseFloat($('#valor_total').html());
                  if (vTotal > nTotal) {
                        // significa que houve desconto
                        <?php
                        if ($_SESSION['rifa_dezena_bolao'])
                              $link = "finalizar_pedido_bolao_dezena";
                        else
                              $link = "finalizar_pedido";
                        ?>

                        location.href = 'index.php?p=<?= $link; ?>&desconto=' + document.getElementById('desconto').value;
                  } else {
                        location.href = 'index.php?p=<?= $link; ?>';
                  }
            }
            <?php if (isset($_SESSION['rifa_dezena_bolao']) && $_SESSION['rifa_dezena_bolao'] != false && (!isset($_SESSION['qr_order']) || $_SESSION['qr_order'] == false)) { ?>

                  let series = [];
                  $('.serie_escolher').each(function() {
                        let el = $(this);
                        series.push({
                              bilhetes: el.data('bilhetes'),
                              serie: el.val()
                        });
                  });
                  <?php
                        if (count($series_proibidas) > 0)
                              echo "let series_proibidas = ['" . implode("','", $series_proibidas) . "'];";
                        else
                              echo "let series_proibidas = [];";
                        ?>
                  $.post('page/carrinho.php', {
                        action: 'set_series',
                        values: series,
                        proibidas: series_proibidas
                  }).done(function(r) {
                        console.log(r);
                        let tmp = JSON.parse(r);
                        if (tmp.error)
                              return alert(tmp.error);
                        callback();
                  }).fail(function(r) {
                        console.log(r);
                  });
            <?php } else echo 'callback();' ?>


      }

      function aplicarDesconto() {
            var desconto = $('#desconto').val().replace(',', '.'),
                  vTotal = <?= $valor_total; ?>;
            var comissao = <?= $valor_total * ($comissao['comissao'] / 100); ?>;
            var pct = 0;
            if (desconto.indexOf('%') >= 0) {
                  pct = parseFloat(desconto.replace('%', '')) / 100;
                  if (pct > 1)
                        return alert('A porcentagem de desconto não pode ser maior que 100%');
                  desconto = vTotal * pct;
                  if (desconto > comissao)
                        return alert('Desconto acima do permitido.');
                  $('#desconto').val(desconto);
            }
            if (desconto > vTotal)
                  return alert('O desconto não pode ser maior que o total.');
            if (desconto > comissao)
                  return alert('Desconto acima do permitido.');
            var total = vTotal - desconto;
            $('#valor_total').html(total.toFixed(2));
      }

      function change_tipo_compra(tipo) {
            show_loading();
            $.post('page/carrinho.php', {
                        change_tipo_compra: tipo
                  })
                  .done(function(res) {
                        end_loading();
                        console.log(res);
                  })
                  .fail(function(res) {
                        end_loading();
                        console.log(res);
                  });
      }

      function remover_bilhete(rifa, bil_chave) {
            $.post('page/carrinho.php', {
                        remover_bilhete: bil_chave,
                        rifa: rifa
                  })
                  .done(function(res) {
                        console.log(res);
                        location.href = 'index.php?p=carrinho';
                  })
                  .fail(function(res) {
                        console.log(res);
                        location.href = 'index.php?p=carrinho';
                  });

      }
</script>