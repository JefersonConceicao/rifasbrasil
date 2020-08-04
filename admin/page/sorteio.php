<script>
	
function toExcelHeader(index) {
    if(index <= 0) {
        throw new Error("index must be 1 or greater");
    }
    index--;
    var charCodeOfA = ("a").charCodeAt(0); // you could hard code to 97
    var charCodeOfZ = ("z").charCodeAt(0); // you could hard code to 122
    var excelStr = "";
    var base24Str = (index).toString(charCodeOfZ - charCodeOfA + 1);
    for(var base24StrIndex = 0; base24StrIndex < base24Str.length; base24StrIndex++) {
        var base24Char = base24Str[base24StrIndex];
        var alphabetIndex = (base24Char * 1 == base24Char) ? base24Char : (base24Char.charCodeAt(0) - charCodeOfA + 10);
        // bizarre thing, A==1 in first digit, A==0 in other digits
        if(base24StrIndex == 0) {
            alphabetIndex -= 1;
        }
        excelStr += String.fromCharCode(charCodeOfA*1 + alphabetIndex*1);
    }
    return excelStr.toUpperCase();
}


function criar_grupos(numero_bilhetes, numero_grupos){

	var numero_headers = numero_bilhetes/numero_grupos, i = 0, k =0;

	for(i = 2; i <= numero_headers; i++){

		for(k = 1; k <= numero_grupos; k++){
			$('#sel').append($('<option>', {
			    value: toExcelHeader(i) + k,
			    text: toExcelHeader(i) + k
			}));
		}

	}


}

</script>