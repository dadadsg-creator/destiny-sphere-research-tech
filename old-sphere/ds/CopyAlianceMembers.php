<?php

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once 'Main.php';

//Настройка работы  ASSERT
assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');

//Регистрация своего обработчикка ошибок
//set_error_handler("DS_ErrorHandler");

//Регитрация финализатора
register_shutdown_function ('finilazer');

define('DEBUG_MODE', 1);

$_SESSION["dbId"] = 0;
$_SESSION["lang"] = 3;
$_SESSION["world"] = 2;

SU_DebugTools::TimerStart('TEST');
echo "<xmp>";
/*********** BEGIN WORK AREA **************/

$fromDBID = 0;
$toDBID = 0;

$common = DS_Common::CreateForCurentDB($toDBID);
$common->executeQuery('BEGIN');

$common->setDBID($fromDBID);

$connFrom = $common->dbWorldAccess[$fromDBID]['conn'];
assert(is_resource($connFrom));

$connTo = $common->dbWorldAccess[$toDBID]['conn'];
assert(is_resource($connTo));

echo "START COPY ALIANCE LIST\n";
flush();

//А здесь надо скопировать табличку с альянсами
//типа как-то так
//Взяли данные из таблички в массив
$rows = pg_copy_to($connFrom, 'ds_alliance');			
assert(is_array($rows) && count($rows) > 0);	 	
print_r($rows);
echo "SELECT ".count($rows)." ALIANCES\n";
flush();

//А тперь пихаем в целевую табличку.		
$res = pg_copy_from($connTo, 'ds_alliance', $rows);
assert($res);

$common->setDBID($toDBID);
$sql = "setval ('ds_alliance_id_alliance_seq', 1000000)";
$res = $common->getResultArray("", $sql, "", "", "");
assert(empty($common->errormsg));


echo "START COPY ALIANCE MEMBERS\n";
flush();

$common->setDBID($fromDBID);
//ТОка помто незабыть сиквенс н табличку с альснсами пересавить, ЧТо  бы новые вставлялись бес проблем.
$sql = "SELECT
      		u.username,
      		uha.joindate,
      		uhas.id_status,
      		a.alliancetag
		FROM
    		ds_userinstance AS ui
    		INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
    		INNER JOIN ds_userhasalliance AS uha ON (uha.id_userinstance = ui.id_userinstance)
    		LEFT JOIN ds_userhasalliancestatus AS uhas ON (uhas.id_userinstance = ui.id_userinstance)
    		INNER JOIN ds_alliance AS a ON (a.id_alliance = uha.id_alliance)";
$usersAllianceList = $common->getResultArray("", $sql, "", "", "");
assert(empty($common->errormsg));

echo "SELECT ".count($usersAllianceList)." FOR COPY\n";
flush();

$common->setDBID($toDBID);
foreach ($usersAllianceList as $k=>$userAllianceData)
{
	$sql = "
		SELECT ui.id_userinstance
		FROM
    		ds_userinstance AS ui
    		INNER JOIN ds_user AS u ON (u.id_user = ui.id_user)
		WHERE
     		u.username = '".$userAllianceData['username']."'";
	$userInstanceData = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));
	
	$UIID = (int)$userInstanceData[0]['id_userinstance'];
	assert($UIID > 0);
	
	$sql = "
		SELECT a.id_alliance
		FROM ds_alliance AS a
		WHERE a.alliancetag = '".$userAllianceData['alliancetag']."'";
	$alianceData = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));
	
	$allianceID = (int)$alianceData[0]['id_alliance'];
	assert($allianceID) > 0;
	
	$sql = "SELECT * FROM ds_userhasalliance WHERE id_userinstance = ".$UIID;
	$testData = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));
	
	if(count($testData) > 0)
	{
		echo "User [".$userAllianceData['username']."] already have aliance\n";
		flush();
		continue;
	}
	
	//$testData = $common->getResultArray("", "BEGIN", "", "", "");

	$sql = "INSERT INTO ds_userhasalliance (id_alliance, id_userinstance, joindate)
				VALUES ($allianceID, $UIID, '".$userAllianceData['joindate']."')";
	$testData = $common->getResultArray("", $sql, "", "", "");
	assert(empty($common->errormsg));

	if($userAllianceData['id_status'])
	{
		$sql = "INSERT INTO ds_userhasalliancestatus (id_status, id_alliance, id_userinstance)
					VALUES (".$userAllianceData['id_status'].", $allianceID, ".$UIID.")";
		$testData = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
	}

	echo "User [".$userAllianceData['username']."] join to aliance [".$userAllianceData['alliancetag']."] in status [".$userAllianceData['id_status']."]\n";
	flush();
	//$testData = $common->getResultArray("", "COMMIT", "", "", "");
}

echo "START INSERT ALLIANCE ARTEFACTS\n";
flush();

$common->setDBID($toDBID);
//Вставляем альянсовые арты
$sql = "INSERT INTO ds_artefactattachedalliance
(id_artefact, id_alliance, id_attachedbuilding)
SELECT
      ai.id_artefactinstance,
      uha.id_alliance,
      atb.id_attachedbuilding
FROM
    ds_userinstance AS ui

    INNER JOIN ds_userhasalliance AS uha ON (uha.id_userinstance = ui.id_userinstance)
    INNER JOIN ds_comb AS c ON (c.id_userinstance = ui.id_userinstance)
    INNER JOIN ds_attachedbuilding AS atb ON (atb.id_comb = c.id_comb)

    INNER JOIN ds_artefactattachedbuilding AS aab ON (aab.id_attachedbuilding = atb.id_attachedbuilding)

    INNER JOIN ds_artefactinstance AS ai ON (ai.id_artefactinstance = aab.id_artefact)
    INNER JOIN ds_artefact AS ar ON (ar.id_artefact = ai.id_artefact)
    INNER JOIN ds_artefactscope AS asn ON (asn.id_artefactscope = ar.id_artefactscope)
WHERE
     asn.scopename = 'ALLIANCE'";
$testData = $common->getResultArray("", $sql, "", "", "");
assert(empty($common->errormsg));	

echo "START INSERT ALLIANCE MESSADGE SCOPES\n";
flush();

$common->setDBID($toDBID);
//ВСавляем альянсовые мессоджскоупы
$sql = "INSERT INTO ds_userallowedmessagescope
(id_userinstance, id_messagescope)
SELECT
      ui.id_userinstance,
      '4' AS id_scope
FROM
    ds_userinstance AS ui
    INNER JOIN ds_userhasalliance AS uha ON (uha.id_userinstance = ui.id_userinstance)
WHERE
     NOT EXISTS (
         SELECT *
         FROM ds_userallowedmessagescope
         WHERE
              id_userinstance =  ui.id_userinstance
              AND
              id_messagescope = 4 -- INTERNAL_ALLIANCE
     )";
$testData = $common->getResultArray("", $sql, "", "", "");
assert(empty($common->errormsg));	

$common->setDBID($toDBID);
$common->executeQuery('ROLLBACK');
echo "COMPLIT!";

/*********** END WORK AREA **************/
echo "</xmp>";
echo "<hr>WORK AREA TIME: ".DebugTools::TimerStop('TEST');

$GLOBALS['CorrectExit'] = 1;
$GLOBALS['HaveErrors'] = 0;

GB_SingletonObjectStorage::CommitAllCommitable();

?>