<?php
include('../class/phpqrcode/qrlib.php');
QRcode::png("http://www.rifasbrasil.com.br/index.php?p=rifa&codigo=556", "QR_code.png");
?>
<img height="100" src="QR_code.png">