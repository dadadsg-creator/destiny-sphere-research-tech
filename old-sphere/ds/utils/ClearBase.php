<?php $CVS_Version = '$Revision$ ; $Date$';
require_once('../Main.php');

class ClearTables
{
	static private $common;
	
	static private $dbId = 5;
	
	static private $tableList = array();
	
	static public $tables = array
	(
		'ds_user',
		'ds_userprofidata',
		'ds_localizedtext',
		'ds_localizedtextlanguage',
		'ds_interface',
		'ds_language',
		'ds_messagescope',
		'ds_specialfunctionrelation',
		'ds_ability',
		'ds_artefact', 
		'ds_artefacthasbonus',
		'ds_artefactprobability',
		'ds_artefactscope',
		'ds_artefactscopehasprobability',
		'ds_artefacttype',
		'ds_artefacttypeforbuilding',		
		'ds_attributename',
		'ds_attributetype',
		'ds_attribute',		
		'ds_specialfunctionrelation',
		'ds_auctionsort',
		'ds_battleaction',
		'ds_battleendreasons',
		'ds_building',
		'ds_buildingcanbebuildon',
		'ds_buildingcanbeupgraded',
		'ds_buildingcanbuildbyrace',
		'ds_buildinghasattribute',
		'ds_specialfunction',
		'ds_buildinghasspecialfunction',
		'ds_buildinghastradescope',
		'ds_buildingneedsbuilding',
		'ds_buildingneedsscience',
		'ds_buildingtype',
		'ds_celltemplate',
		'ds_celltemplateelement',
		'ds_celltype',
		'ds_combcomplextype',
		'ds_event',
		'ds_eventhandler',		
		'ds_eventoption',		
		'ds_gamemodifiertemplate',
		'ds_groundtype',
		'ds_partner',
		'ds_produceableunit',
		'ds_race',
		'ds_racehasattribute',
		'ds_racehasbasering',		
		'ds_religion',
		'ds_religionhasattribute',
		'ds_religionneedsbuilding',
		'ds_religionstatus',
		'ds_resource',
		'ds_ringtemplate',
		'ds_science',
		'ds_sciencebranch',
		'ds_sciencebranchattributename',
		'ds_sciencecanberesearchedbyrace',
		'ds_sciencehasability',
		'ds_sciencehasattribute',
		'ds_scienceneedsbuilding',
		'ds_scienceneedsscience',
		'ds_systemmessage',
		'ds_tradecategory',
		'ds_tradescope',
		
		'ds_buildingdeactivatepriority',
		
		'ds_unit',
		'ds_unitattacktype',
		'ds_unitcanbebuildbyrace',
		'ds_unitclass',
		'ds_unitclassbonus',
		'ds_unitcontaineractiontype',
		'ds_unithasattacktype',
		'ds_unithasattribute',
		'ds_unithasclass',
		'ds_unithasvulnerability',
		'ds_unitneedsbuilding',
		'ds_unitneedsscience',
		'ds_unitorcontainertype',
		'ds_unitvsunit',
		
		
		'ds_useractiontype',				
		'ds_worldtype',
		'ds_terraforming',
		'ds_terraforminghasattribute',
		'ds_terraformingneedscience',
		'ds_serversideinterface',
		'ds_powershild_status_name',
		'ds_powershild_status_canchangeto',
		
		'ds_userinstancecollection',
		'ds_powershild_status_cc2',
		'ds_paymentduration',
		'ds_useralliancestatus',
		'ds_alliancerelation',
		'ds_paymenttype',
		'ds_paymentkind',
		'ds_paymentkindhascurrency',
		'ds_recommendation',
		'ds_version',
		'ds_userprofitechcpu',
		'ds_userprofitechcpu',
		'ds_usermessage',
		'ds_partnerhaspaymenttype',
		'ds_userprofitechos',
		'ds_usersallowedcollection',
		'ds_worldcollection',
		'ds_payment',
		'ds_paymenttypewebmoney',
		'ds_userland',
		'ds_userprovidedbypartner',
		'ds_usersex',
		'ds_paymentkindhastax',
		'ds_currency',
		'ds_region',
		'ds_useradvertisedby',
		'ds_userprofitechinternetspeed',
		'ds_tax',
		'ds_userprofitechdata',
		'ds_usercheckcode',
		'ds_userprofitechbrowser',
		'ds_useroccupation',
		'ds_paymentmodel',
		'ds_userprofitechflash',
		'ds_userprofitechram',
		'ds_partnerhaspaymentmodel'
	);

	
	public function clear()
	{
		self::$common = DS_Common::CreateForCurentDB(self::$dbId);
		
		self::_formTablesList();
		
		$res  = false;
		$count = 0; 
		
		while(!$res)
		{
			echo "======== ITERATION ".$count." =========<br>\n";
			shuffle(self::$tableList);
			$res = self::_clearTables();
			$count++;
			echo "==============================<br>\n";
		}
	}
	
	private function _formTablesList()
	{
		$sql = "
			SELECT *
			FROM pg_class
			WHERE
     			reltuples > 0
     			AND
     			relkind = 'r'
     			AND
     			relname LIKE 'ds_%'";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(!empty($res));

		foreach($res as $k => $table)
		{
			if(!in_array(strtolower($table['relname']), self::$tables))
			{
				self::$tableList[] = $table['relname'];	
			}
		}
	}
	
	private function _clearTables()
	{
		$res = true;
		
		foreach(self::$tableList as $k => $tableName)
		{
			if(self::_clearTable($tableName))
			{
				unset(self::$tableList[$k]);
			} else {
				$res = false;
			}
		}
		
		return $res;
	}
	
	private function _clearTable($tableName)
	{
		echo "DELETE ".$tableName." : ";
		flush();
		self::$common->startTrans();
		self::$common->executeQuery("set STATEMENT_TIMEOUT to 0;");
		$sql = "DELETE FROM ".$tableName;		
		$res = self::$common->executeQuery($sql);
		$res = true;
		
		if(!empty(self::$common->errormsg))
		{
			echo "ERROR! <br>\n";
			self::$common->unBlock();
			self::$common->closeTrans('ROLLBACK');
			self::$common->errormsg = '';
		} else {
			echo "OK! <br>\n";
			self::$common->closeTrans('COMMIT');
		}
		flush();
		return $res;
	}
}

ClearTables::clear();
?>