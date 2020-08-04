<style>
body{
	padding:0; 
	margin:0; 
	border:0;
}

html{
	margin: 0cm 0cm 0cm 0cm; 

}
table { 
	font-family:Arial;
    border-collapse: collapse; 
    border:0;
    padding:0;
    page-break-after: always;
}
td{
	border:0;
	padding:0;
	margin:0;
}
tr{
	border:0;
	margin:0;
	padding:0;
	height: <?= $altura_bilhete; ?>cm;
	border:0;
	border-bottom:none;
}
table tr:last-child{
	border-bottom:0;
	box-sizing: border-box;
}
tr td:nth-child(1){
	/* grupo e numero */
	height: <?= $altura_bilhete; ?>cm;
	width:<?= $largura_z1; ?>cm;
}
tr td:nth-child(2){
	/* qr */
	width: <?= $altura_bilhete; ?>cm;
	height: <?= $altura_bilhete; ?>cm;
}
tr td:nth-child(2) img{
	width: <?= $altura_bilhete; ?>cm;
	height: <?= $altura_bilhete; ?>cm;

}
tr td:nth-child(3){
	/* png */
	background-color:<?= $bg_color; ?>;
	height: <?= $altura_bilhete; ?>cm;
	box-sizing: border-box;
}
tr td:nth-child(3) img{
	width:<?= $largura_png; ?>cm;
	margin-left:<?= $margem_esquerda_png; ?>cm;
	height:<?= $altura_png; ?>cm;
}
table{
	width: <?= $largura_bilhete; ?>cm;
}
.nav{
	display:block;
	position:absolute;
    -webkit-transform: rotate(90deg); 
    -moz-transform: rotate(90deg); 
}
.grupo{
	margin-left:25px;
	margin-top:-15px;
	font-size:1.1em;
}
.numero{
	margin-left:0;
	margin-top:-15px;
	font-size:1.3em;
	font-weight: bold;
}


</style>