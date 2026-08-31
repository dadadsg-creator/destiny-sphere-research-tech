<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_DetectNewTradeVersion
{
	static public function Detect()
	{
		$common = DS_Common::CreateForCurentDB();
		
		$sql = "
			SELECT 
				count(*)
			FROM 
				pg_class
			WHERE
     			relkind = 'r'
     			AND
     			relname = 'ds_trade_lots'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));

		if($res[0]['count'])
		{
			return true;
		}
		
		return false;
	}
}

?>