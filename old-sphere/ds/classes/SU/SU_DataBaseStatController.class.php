<?php $CVS_Version = "$Revision: 1.5 $ ; $Date: 2007/04/23 14:14:16 $";


class SU_DataBaseStatController
{
	static public function Show()
	{
		$params = SU_DataBaseStatInputParams::Singleton();
		$action = $params->get('action');
		$dataBase = $params->get('dataBase');
		
		$ShowCountOfRows = $params->get('ShowCountOfRows');
		
		SU_DataBaseStatOutForm::OutForm($action, $dataBase);

		if (key_exists('SUBMIT', $_POST))
		{
			$db = explode("_", $dataBase);
			$dbId = $db[0];
			$dbName = $db[1];
			
			$tables =  $params->get('tables');

			if ($tables != 'all tables')
			{	
				$tablesArr = explode(",", $tables);
			}else 
			{
				$tablesArr = 'all tables';
			}

			$query = SU_DataBaseStatGenerateQuery::GenerateQuery($tablesArr, $ShowCountOfRows, $action);

			$statArr = SU_DataBaseStatExecQuery::ExecQuery($dbId, $query);
			
			SU_DataBaseStatOutStatistics::OutStatistics($statArr, $dataBase, $ShowCountOfRows, $tables, $action);
		}
	}
}

?> 