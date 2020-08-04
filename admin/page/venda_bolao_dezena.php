<?php 
if(!isset($_SESSION)) session_start();


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

}else
    include("../class/conexao.php"); 

?>
<div class="col-lg-12 table-responsive">
    <div class="col-lg-12 text-center">
        <p>Carregando...</p>
    </div>
</div>
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
                                if($_SESSION['admin'] == 'S')
                                    $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where u.usu_cod = r.rifa_dono order by r.rifa_cod DESC";
                                else
                                    $rifas_do_usuario = "select r.rifa_cod, r.rifa_dtsorteio, r.rifa_titulo, u.usu_mensalista from tbl_rifas r, tbl_usuario u where (u.usu_cod = r.rifa_dono and r.rifa_dono = '{$_SESSION['usuario']}') or r.rifa_cod in (SELECT rifa_cod from tbl_revendedor where usu_cod = '{$_SESSION['usuario']}' or pai = '{$_SESSION['usuario']}') group by r.rifa_cod order by r.rifa_cod DESC";

                                $consulta = $mysqli->query($rifas_do_usuario) or die($mysqli->error);
                                $assoc    = $consulta->fetch_assoc();
                                do{ 
                                    if(time() <= (strtotime($assoc['rifa_dtsorteio']) + 86400)) { ?>
                                        <option value="<?= $assoc['rifa_cod']; ?>"><?= $assoc['rifa_cod']; ?> - <?= $assoc['rifa_titulo']; ?></option>
                                <?php
                                    } 
                                }while($assoc    = $consulta->fetch_assoc());
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
window.onload = function(){
    function buscarrifas(){
        show_loading();
        $('#rifa_destino_input option[value="<?= intval($_GET['buscarrifas']); ?>"]').attr('selected', true);
        $('#btn_pross').click(function(){
            var novarifa = $('#rifa_destino_input').val();
            $.post('page/venda.php', {montar_carrinho:novarifa, bil:'<?= $_GET['bil']; ?>'})
            .done(function(r){
                console.log(r);
                location.href='index.php?p=cadastro_cliente';
            }).fail(function(r){
                console.log(r);
            });
        });
        $('#rifa_destino').modal('show');
        end_loading();
    }
    buscarrifas();
}
</script>
