<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";  
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";

include $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";

$unitNames = array(
	1 => 'Ученый',
	8 => 'Рабочий',

    60 => 'MIMICRY_DRONE',
    61 => 'MIMICRY_SERVANT',
    62 => 'MIMICRY_MASTER',
    
    63 => 'ELEMENT_OF_ENERGY',
    64 => 'ELEMENT_OF_EAT',
    65 => 'ELEMENT_OF_KADERIUM',
    66 => 'ELEMENT_OF_NANO',
    67 => 'ELEMENT_OF_PRODIUM',
    
    68 => 'MISS_BEAUTY',
    
    81 => 'DED_MOROZ',
    82 => 'SNEGUROCHKA',

	84 => 'Холера',
	85 => 'Антибиотик',
	86 => 'Слепота',
	87 => 'Антидот',
);

$totalSum = array();

foreach ($unitNames as $k => $v)
{
	$totalSum[$k] = 0;
}

$units = implode(',', array_keys($unitNames));

function Out($str)
{
	echo $str."\n";
}

function GetSum(array $set1, array $set2)
{
	global $unitNames;
	
	$res = array();
	
	foreach ($unitNames as $k => $v)
	{
		assert(key_exists($k, $set1));
		assert(key_exists($k, $set2));
		$res[$k] = $set1[$k] + $set2[$k];
	}
	
	return $res;
}

function ReformatArray(array $data)
{
	global $totalSum, $unitNames;
	
	$res = array();
	
	foreach ($data as $k => $a)
	{
		$res[$a['id_unit']] = $a['count'];
		$totalSum[$a['id_unit']] += $a['count'];
	}
	
	foreach ($unitNames as $k => $v)
	{
		if(!key_exists($k, $res))
		{
			$res[$k] = 0;
		}
	}
	
	return $res;
}

function PrintData(array $data)
{
	global $unitNames;
	
	foreach ($unitNames as $k => $u)
	{
		assert(key_exists($k, $data));
		Out('<td class=maintxt align=center>'.$data[$k].'</td>');
	}
}


$sql1 = "
	SELECT
		uchu.id_unit as id_unit,
		SUM(ceil(uchu.amount)) as count
	FROM
		ds_unitcontainerhasunit AS uchu
	WHERE 
		uchu.id_unit IN ($units)
	GROUP BY uchu.id_unit
	";

$sql2 = "
	SELECT
    	eho2.value AS id_unit,
    	SUM(eho3.value) AS count
	FROM
    	ds_eventqueue AS eq
    	INNER JOIN ds_event AS ev USING (id_event)
    
	    INNER JOIN ds_eventhasoption AS eho USING (id_eventqueue)
    	INNER JOIN ds_eventoption AS eo ON (eo.id_eventoption = eho.id_eventoption)
    
    	INNER JOIN ds_eventhasoption AS eho2 USING (id_eventqueue)
    	INNER JOIN ds_eventoption AS eo2 ON (eo2.id_eventoption = eho2.id_eventoption)
    
    	INNER JOIN ds_eventhasoption AS eho3 USING (id_eventqueue)
    	INNER JOIN ds_eventoption AS eo3 ON (eo3.id_eventoption = eho3.id_eventoption)
	WHERE
    	ev.eventname IN ('TRANSFER_RESOURCE', 'TRADING_DELIVERY_GOOD')
    	AND
    	NOT eq.istrash
    	AND
    	eo.optionname = 'ID_Category'
    	AND
    	eho.value = 2
    	AND
    	eo2.optionname = 'ID_Article'
    	AND
    	eho2.value IN (".$units.")
    	AND
    	eo3.optionname = 'Amount'
    GROUP BY eho2.value
	";

$sql3 = "
	SELECT
		tl.id_article AS id_unit,
		SUM(tl.amount) AS count
	FROM
		ds_trade_lots AS tl
		INNER JOIN ds_trade_type AS tt USING (id_tradetype)
		INNER JOIN ds_trade_category AS tc ON (tc.id_tradecategory = tt.id_trade_on_category)
	WHERE
        tc.categoryname = 'UNIT'
        AND
        tl.id_article IN (".$units.")
	GROUP BY tl.id_article 
	";

$common = DS_Common::CreateForCurentDB(0);

Out('<table cellspacing="0" cellpadding="3" border="1">');

Out('<tr class=th_2><td>&nbsp;</td>');
PrintData($unitNames);
Out('</tr>');

foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
{
	$common->setDbId($dbId);
	$res1 = $common->getResultArray("", $sql1);
	assert(empty($common->errormsg));
	$res2 = $common->getResultArray("", $sql2);
	assert(empty($common->errormsg));
	$res3 = $common->getResultArray("", $sql3);
	assert(empty($common->errormsg));
/*
Out('<xmp>');
print_r($res1);
print_r($res2);
print_r($res3);
*/	
	$res1 = ReformatArray($res1);
	$res2 = ReformatArray($res2);
	$res3 = ReformatArray($res3);
/*	
print_r($res1);
print_r($res2);
print_r($res3);
Out('</xmp>');
*/
	
	$res = GetSum($res1, GetSum($res2, $res3));
	
	Out('<tr><td align="center" class=maintxt><b>'.$db['server'].'</b></td>');
	PrintData($res);
	Out('</tr>');
}

Out('<tr><td align="center" class=maintxt><b>TOTAL</b></td>');
PrintData($totalSum);
Out('</tr>');

Out('</table>');


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";

?>