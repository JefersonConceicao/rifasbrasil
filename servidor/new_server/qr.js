const qrcode = require('qrcode')
const fs = require('fs');

var hrstart = process.hrtime();

if(!process.argv[2])
	return;

if(!process.argv[3])
	return;

var rifa = process.argv[2];
var bilhetes = process.argv[3].split('-');

console.info("gerando " + bilhetes.length + " bilhetes");

var num = bilhetes.length;

function step(bilhetes){

	if(bilhetes.length == 0){
		return;
		/*var hrend = process.hrtime(hrstart);
		console.info('Execution time (hr): %ds %dms <BR>', hrend[0], hrend[1] / 1000000);*/
	}

	var str_bilhete = bilhetes.shift();
	var codigo = str_bilhete.substr(0, str_bilhete.indexOf('*'));
	var str_bilhete = str_bilhete.substr(str_bilhete.indexOf('*')+1);

	try{
		fs.statSync("/var/www/nevoahost/c/rifasbrasil.com.br/servidor/new_server/qrbf/rifa_"+rifa+"_"+codigo+".png");
		console.info("Arquivo: rifa_" + rifa+"_"+codigo+'.png ja existe');
		step(bilhetes);
		//console.info('Execution time (hr): %ds %dms', hrend[0], hrend[1] / 1000000 / quantidade);

	}catch(e){ 
		qrcode.toFile(
			"/var/www/nevoahost/c/rifasbrasil.com.br/servidor/new_server/qrbf/rifa_"+rifa+"_"+codigo+".png", 
			"http://www.rifasbrasil.com.br/index.php?p=entrar&next=admin/cadastro_cliente&rifa="+rifa+"&bil="+str_bilhete, 

			function (err) {
			if (err) 
				console.info("ERROR", err);

			console.info("Arquivo: rifa_" + rifa+"_"+codigo+'.png gerado')
			step(bilhetes);
			
		});
	}
}

step(bilhetes);

