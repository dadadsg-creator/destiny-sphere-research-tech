<?php $CVS_Version = '$Revision$ ; $Date$';


class SU_RequestStatControllerMain
{
	static public function Show()
	{
		$params = SU_RequestStatEntityInputParams::Singleton();

		switch($params->get('ACTION'))
		{
			case "":
				SU_RequestStatOutForm::OutNewForm($params);
				break;

			case "worldandname":
				$world = explode("-", $params->get('WORLD'));
				$worldname = $world[0];
				$dbId = $world[1];
				$worldId = $world[2];

				$startDay = $params->get('YEAR')."-".$params->get('MONTH')."-".$params->get('DAY');
				$endDay = $params->get('YEAR2')."-".$params->get('MONTH2')."-".$params->get('DAY2');


				$query = SU_RequestStatSqlQuery::GenerateQueryUser
				(
				$params->get('USER_NAME'),
				$worldId,
				$startDay,
				$endDay
				);
				$table = SU_RequestStatSearch::ExecQuery($query, $dbId);

				SU_RequestStatOutLog::ShowLog
				(
				$table,
				$startDay,
				$endDay,
				$params->get('USER_NAME'),
				$worldname,
				$params->get('DB_ID')
				);

				break;

			case "world":

				$world = explode("-", $params->get('WORLD'));


				$worldname = $world[0];
				$dbId = $world[1];
				$worldId = $world[2];

				$startDay = $params->get('YEAR')."-".$params->get('MONTH')."-".$params->get('DAY');
				$endDay = $params->get('YEAR2')."-".$params->get('MONTH2')."-".$params->get('DAY2');

				$query = SU_RequestStatSqlQuery::GenerateQueryWorld
				(
				$worldId,
				$startDay,
				$endDay
				);
				$table = SU_RequestStatSearch::ExecQuery($query, $dbId);

				SU_RequestStatOutLog::ShowLog
				(
				$table,
				$startDay,
				$endDay,
				$params->get('USER_NAME'),
				$worldname,
				$params->get('DB_ID')
				);

				break;

			case "database":
				$temp = explode("_", $params->get('DB_ID'));
				$id = $temp[0];
				$dbname = $temp[1];

				$startDay = $params->get('YEAR')."-".$params->get('MONTH')."-".$params->get('DAY');
				$endDay = $params->get('YEAR2')."-".$params->get('MONTH2')."-".$params->get('DAY2');

				$query = SU_RequestStatSqlQuery::GenerateQueryDataBase
				(
				$startDay,
				$endDay
				);

				$table = SU_RequestStatSearch::ExecQuery($query, $id);

				SU_RequestStatOutLog::ShowLog
				(
				$table,
				$startDay,
				$endDay,
				$params->get('USER_NAME'),
				$params->get('WORLD'),
				$dbname
				);

				break;

			case "allworld":

				$startDay = $params->get('YEAR')."-".$params->get('MONTH')."-".$params->get('DAY');
				$endDay = $params->get('YEAR2')."-".$params->get('MONTH2')."-".$params->get('DAY2');

				$query = SU_RequestStatSqlQuery::GenerateQueryAllWorld
				(
				$startDay,
				$endDay
				);
				$table = SU_RequestStatSearch::ExecQueryAllWorld($query);

				SU_RequestStatOutLog::ShowLogAllWorld
				(
				$table,
				$startDay,
				$endDay
				);

				break;

			case "queryUser":
				$world = explode("-", $params->get('WORLD'));
				$worldname = $world[0];
				$dbId = $world[1];
				$worldId = $world[2];

				$startDay = $params->get('YEAR')."-".$params->get('MONTH')."-".$params->get('DAY');
				$endDay = $params->get('YEAR2')."-".$params->get('MONTH2')."-".$params->get('DAY2');


				$query = SU_RequestStatSqlQuery::GenerateQuerybByUserAndWorlg
				(
				$params->get('USER_NAME'),
				$worldId,
				$startDay,
				$endDay
				);
				$table = SU_RequestStatSearch::ExecQuery($query, $dbId);

				SU_RequestStatOutLog::ShowQueryLog
				(
				$table,
				$startDay,
				$endDay,
				$params->get('USER_NAME'),
				$worldname,
				$params->get('DB_ID')
				);
				break;

			default:
				break;
		}
	}
}


?> 
