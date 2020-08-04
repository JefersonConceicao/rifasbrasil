<?php  
include('../class/conexao.php'); 
$usu_cod = (int) $_GET['usu_cod']; 
$mysqli->query("DELETE FROM `tbl_usuario` WHERE `usu_cod` = '$usu_cod' ") ; 
echo ($mysqli->affected_rows) ? "<script>alert('Deletado!'); location.href='index.php?p=usuario';</script>" : 
"<script>alert('Falhou!'); location.href='index.php?p=usuario';</script>"; 
?> 
