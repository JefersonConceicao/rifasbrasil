<?php

if(!class_exists('Venda')){

	class Venda {

		public $sql_code;
		private $m;

		function __construct($mysqli, $usu_cod = -1){

			$user = '';

			$this->m = $mysqli;

			if($usu_cod > -1)
				$user .= " and r.rifa_dono = '$usu_cod' ";

			$this->sql_code = "select 
		        r.rifa_maxbilhetes, 
		        r.rifa_titulo, 
		        c.comp_cod,
		        c.comp_status_revenda,
		        c.comp_situacao,
		        c.comp_cliente as dono, 
		        (select usu_nome from tbl_usuario where usu_cod = c.comp_cliente) as cliente,
		        (select usu_celular from tbl_usuario where usu_cod = c.comp_cliente) as tel_cliente,
		        (select usu_nome from tbl_usuario where usu_cod = c.comp_revendedor) as revendedor, 
		        b.bil_compra as compra, 
		        c.comp_situacao, 
		        c.comp_valortotal 
		        from tbl_bilhetes b, tbl_compra c, tbl_rifas r 
		        where r.rifa_cod = b.bil_rifa 
		        and c.comp_cod=b.bil_compra 
		        $user 
		        group by b.bil_compra order by b.bil_compra desc";

		}

		function paginacao(){

			$res = DBSelect($this->sql_code, $this->m);
			echo ' '.count($res);

		}

	}

}

?>