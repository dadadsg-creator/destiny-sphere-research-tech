<?php

class Search
{
	static public function GetWorldList()
	{
		$common = DS_Common::CreateForCurentDB();
		$sql = "SELECT * FROM ds_world";
		$res = $common->getMultyArray('', $sql);
		assert(empty($common->errormsg) && count($res) > 0);
		return $res;
	}

	static public function GetUnit($a, $b, $data1, $data2)
	{
		$mas = explode("_", $a);
		$idb = $mas[0];
		$idw = $mas[1];
		$common = DS_Common::CreateForCurentDB($idb);

		$sql = "SELECT
				    u.username,
				    cn.combname,
				    udl.before_amount,
				    udl.death_amount,
				    udl.add_date,
				    udl.check_date,
				    udr.reason_name,
				    un.unitname
				FROM
				    ds_user AS u
				    INNER JOIN ds_userinstance AS ui ON (ui.id_user = u.id_user)
				    INNER JOIN ds_units_death_log AS udl ON (udl.id_userinstance = ui.id_userinstance)
				    INNER JOIN ds_units_death_reasons AS udr ON (udr.id_death_reason = udl.id_death_reason)
				    INNER JOIN ds_combname AS cn ON (cn.id_comb = udl.id_comb)
				    INNER JOIN ds_unit AS un ON (un.id_unit = udl.id_unit)
				WHERE
				    u.username = '$b'
				    AND
				    ui.id_world = $idw
					AND
				    udl.add_date >= '$data1 00:00:01'
				    AND
				    udl.add_date <= '$data2 23:59:59'
				 ";
		$res = $common->getResultArray('', $sql);
		assert(empty($common->errormsg));

		return $res;
	}
}
//Black_Redgard
?>