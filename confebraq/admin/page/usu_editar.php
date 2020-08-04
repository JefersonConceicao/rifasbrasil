<?php  
include('../class/conexao.php');
if (isset($_GET['usu_cod']) ) { 
$usu_cod = (int) $_GET['usu_cod']; 
if (isset($_POST['submitted'])) { 
foreach($_POST AS $key => $value) { $_POST[$key] = $mysqli->escape_string($value); } 
$sql = "
UPDATE `tbl_usuario` 
SET  `usu_nome` =  '{$_POST['usu_nome']}' ,   
`usu_celular` =  '{$_POST['usu_celular']}' ,  
`usu_residencial` =  '{$_POST['usu_residencial']}' ,  
`usu_recado` =  '{$_POST['usu_recado']}' ,  
`usu_endereco` =  '{$_POST['usu_endereco']}' ,  
`usu_usuario` =  '{$_POST['usu_usuario']}' ,  
`usu_senha` =  '{$_POST['usu_senha']}' ,  
`usu_admin` =  '{$_POST['usu_admin']}' ,  
`usu_validado` =  '{$_POST['usu_validado']}' ,  
`usu_psemail` =  '{$_POST['usu_psemail']}' ,  
`usu_pstoken` =  '{$_POST['usu_pstoken']}'   
WHERE `usu_cod` = '$usu_cod' "; 
$mysqli->query($sql) or die($mysqli->error); 
echo "<script>location.href='index.php?p=usuario';</script>"; 
} 
$r =  $mysqli->query("SELECT * FROM `tbl_usuario` WHERE `usu_cod` = '$usu_cod' "); 
$row = $r->fetch_assoc();
?>
<div class="col-lg-12 text-left">
    <h3>Editar Usuário</h3>
    <hr>
</div>
<div class="col-lg-6">
<form action='' method='POST'> 
    <div class='form-group col-lg-8'>
        <label>Nome</label>
        <input  class='form-control' type='text' name='usu_nome' value='<?php echo stripslashes($row['usu_nome']) ?>' />
    </div> 
    <div class='form-group col-lg-4'>
        <label>Cpf</label>
        <input disabled class='form-control' type='text' name='usu_cpf' value='<?php echo stripslashes($row['usu_cpf']) ?>' />
    </div> 
    <div class='form-group col-lg-6'>
        <label>Email</label>
        <input disabled class='form-control' type='text' name='usu_email' value='<?php echo strtolower($row['usu_email']) ?>' />
    </div> 
    <div class='form-group col-lg-3'>
        <label>Celular</label>
        <input  class='form-control' type='text' name='usu_celular' value='<?php echo stripslashes($row['usu_celular']) ?>' />
    </div> 
    <div class='form-group col-lg-3'>
        <label>Residencial</label>
        <input  class='form-control' type='text' name='usu_residencial' value='<?php echo stripslashes($row['usu_residencial']) ?>' />
    </div> 
    <div class='form-group col-lg-4'>
        <label>Recado</label>
        <input  class='form-control' type='text' name='usu_recado' value='<?php echo stripslashes($row['usu_recado']) ?>' />
    </div> 
    <div class='form-group col-lg-8'>
        <label>Endereco</label>
        <textarea class='form-control' name='usu_endereco'><?php echo stripslashes($row['usu_endereco']) ?></textarea></div> 
    <div class='form-group col-lg-6'>
        <label>Usuario</label>
        <input  class='form-control' type='text' name='usu_usuario' value='<?php echo stripslashes($row['usu_usuario']) ?>' />
    </div> 
    <div class='form-group col-lg-6'>
        <label>Admin</label>
        <select class='form-control' type='text' name='usu_admin'>
            <option value=""></option>
            <option <?php if(strcmp($row['usu_admin'], 'S')==0) echo 'selected="selected"'; ?> value="S">Sim</option>
            <option <?php if(strcmp($row['usu_admin'], 'N')==0) echo 'selected="selected"'; ?> value="N">Não</option>
        </select>
        
    </div> 
    <div class='form-group col-lg-6'>
        <label>Email Pagseguro</label>
        <input  class='form-control' type='text' name='usu_psemail' value='<?php echo strtolower($row['usu_psemail']) ?>' />
    </div> 
    <div class='form-group col-lg-6'>
        <label>Token Pagseguro</label>
        <input  class='form-control' type='text' name='usu_pstoken' value='<?php echo stripslashes($row['usu_pstoken']) ?>' />
    </div> 
    <div class='form-group col-lg-12'>
        <button class='btn btn-success' type='submit'  >Salvar</button>
        <input type='hidden' value='1' name='submitted' />
        <button class='btn' onclick="javascript:location.href='index.php?p=<?php if($_GET['usu_graf']) echo 'grafica'; else echo 'usuario'; ?>';" type='button'  >Cancelar</button>
    </div> 
</form>
</div>
<?php  } ?> 
