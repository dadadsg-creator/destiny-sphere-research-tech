<?php
ini_set('memory_limit', '128M');
set_time_limit(0);
define('DEBUG', 1);
define('LOG_FILE', 'ArtefactDrops');
define('TRACE', 0);

require_once '../Main.php';

assert_options(ASSERT_CALLBACK, 'DS_AssertHandler');
$sapi = php_sapi_name();
$DBID = 0;
$worldId = 0;

if(preg_match("/^apache/", $sapi)) {
	if(key_exists('dbid', $_REQUEST)) {
		$DBID = (int)$_REQUEST['dbid'];
	}
}
else {
	assert(key_exists('1', $_SERVER['argv']));
	$DBID = (int)$_SERVER['argv'][1];
}

$delete = new DropTemporaryArtefacts($DBID);
$delete->Scan();

class DropTemporaryArtefacts
{
	/**
	 * Класс для работы с БД
	 *
	 * @var DS_Common
	 */
	private $common;

	/**
	 * ID базы с коротой работем
	 *
	 * @var unknown_type
	 */
	private $DBID;

	/**
	 * ID мира, который чиститься
	 *
	 * @var unknown_type
	 */
	private $worldId;

	public function __construct($DBID)
	{
		assert(is_int($DBID) && $DBID >= 0);

		$this->DBID = $DBID;

		$this->common = DS_Common::CreateForCurentDB($this->DBID);
	}

	private function _out($msg = '')
	{
		$str = date('Y-m-d H:i:s')." ".$msg."\n";

		if(php_sapi_name() == 'apache')
		{
			echo $str;
			flush();
		}

		SU_DebugTools::WriteToLog($str, 'DropTemporaryArts_'.$GLOBALS['dbWorldAccess'][$this->DBID]['server'].'.log');
	}

	/** Main method */
	public function Scan()
	{
		$sql = "
			SELECT
				id_world
			FROM
				ds_world
			";
		$worlds = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		foreach ($worlds as $k => $world)
		{
			$this->worldId = (int)$world['id_world'];

			$_SESSION['world'] = $this->worldId;
			$_SESSION['dbId'] = $this->DBID;
			
			$this->common->startTrans();

			$this->_out();
			$this->_out("========= START DROP ART IN DB ".$this->DBID." WORLD ".$this->worldId." =============");

			$this->_out();
			$this->_out("========= Drop art from comb =============");

			$this->_DropAllArtFromComb();

			$this->_out("========= Drop art from combs complit=============");

			$this->_out();
			$this->_out("========= Drop art from buildings =============");

			$this->_DropAllArtFromBuildings();

			$this->_out("========= Drop art from buildings complit=============");

			
			// Коммитим
			GB_SingletonObjectStorage::CommitAllCommitable();
			$this->common->closeTrans('commit');

			$this->_out();
			$this->_out("================== COMPLIT ======================");
		}
	}

	private function _DropAllArtFromComb()
	{
		// Выбираем все арты с сот
		$sql = "
		SELECT 
			id_artefactinstance, 
			ui.id_userinstance,
			birth_time, 
			death_time,
			a.id_artefact
		FROM 
			ds_artefactinstance ai 
		LEFT JOIN ds_artefact a ON (a.id_artefact = ai.id_artefact)
		LEFT JOIN ds_combhasartefact cha ON (cha.id_artefact = ai.id_artefactinstance)
		LEFT JOIN ds_comb c ON (c.id_comb = cha.id_comb)
		LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = c.id_userinstance)
		WHERE 
			ai.immortal=FALSE 
			AND 
			death_time < NOW()
			AND
			NOT cha.id_comb IS NULL
			AND ui.id_world = ".$this->worldId;		
		$arts = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		// Проходимся по всем артам
		foreach ($arts as $artKey => $artData)
		{
			$artefact_instance_id = (int)$artData['id_artefactinstance'];
			assert($artefact_instance_id > 0);

			//Артефакт находится в хранилище, удаляем его оттуда
			$sql = "DELETE FROM ds_combhasartefact WHERE id_artefact = ".$artefact_instance_id;
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));

			// Логируем удаление артефакта в базу
			$sql = "INSERT INTO ds_dropped_artefacts
					(id_artefact, id_userinstance, birth_time, death_time)
					VALUES
					(".$artData['id_artefact'].", ".$artData['id_userinstance'].", '".$artData['birth_time']."', '".$artData['death_time']."')";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));

			//Удаляем артефакт
			$sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = ".$artefact_instance_id;
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			$str = "UserInstance: [".$artData['id_userinstance']."] ArtefactId: [".$artData['id_artefact']."] BirthTime: [".$artData['birth_time']."] DeathTime: [".$artData['death_time']."]";
			$this->_out($str);
		}

	}//END OF METHOD

	private function _DropAllArtFromBuildings()
	{
		// Выбираем все арты со зданий
		$sql = "
		SELECT 
			id_artefactinstance, 
			birth_time, 
			death_time,
			c.id_comb,
			ab.inupgrade,
			ui.id_userinstance,
			c.id_combcomplex,
			a.id_artefact
		FROM 
			ds_artefactinstance ai 
		LEFT JOIN ds_artefact a ON (a.id_artefact = ai.id_artefact)
		LEFT JOIN ds_artefactattachedbuilding aab ON ( ai.id_artefactinstance = aab.id_artefact)
		LEFT JOIN ds_attachedbuilding ab ON (ab.id_attachedbuilding = aab.id_attachedbuilding)
		LEFT JOIN ds_comb c ON (c.id_comb =  ab.id_comb)
		LEFT JOIN ds_userinstance ui ON (ui.id_userinstance = c.id_userinstance)
		WHERE 
			ai.immortal=FALSE 
			AND 
			death_time < NOW()
			AND
			NOT aab.id_attachedbuilding IS NULL
			AND ui.id_world = ".$this->worldId;		
		$arts = $this->common->getResultArray("", $sql, "", "", "");
		assert(empty($this->common->errormsg));

		// Проходимся по всем артам
		foreach ($arts as $artKey => $artData)
		{
			// ID инстанса арта
			$artefact_instance_id = (int)$artData['id_artefactinstance'];
			assert($artefact_instance_id > 0);

			// ID соты
			$comb_id = (int)$artData['id_comb'];
			assert($comb_id > 0);

			// ID изеринстанса
			$userinstance_id = (int)$artData['id_userinstance'];
			assert($userinstance_id > 0);

			// ID комплекса
			$combcomplex_id = (int)$artData['id_combcomplex'];
			assert($combcomplex_id > 0);

			// Находится ли здание в апгрейде
			$buildingInUpgrade = false;
			if($artData['inupgrade'] == 't')
			{
				$buildingInUpgrade = true;
			}

			// Удаляем артефакт из здания
			$sql = "DELETE FROM ds_artefactattachedbuilding WHERE id_artefact = $artefact_instance_id";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));

			// снимаем бонусы
			$sql = "SELECT
					ars.scopename
				FROM 
					ds_artefactscope ars,
					ds_artefact ar,
					ds_artefactinstance ai
				WHERE 
					ars.id_artefactscope = ar.id_artefactscope
					AND 
					ar.id_artefact = ai.id_artefact
					AND 
					ai.id_artefactinstance = $artefact_instance_id";

			$res = $this->common->getResultArray("", $sql, "", "", "");
			assert(empty($this->common->errormsg));
		
			$artefactAttributes = DE_Artefact::CreateFromArtefactInstanceID($artefact_instance_id)->getAttributes();
			$combAttributes = DE_CombAttributes::CreateFromCombID($comb_id);
			
			switch ( $res[0]['scopename'] )
			{
				case 'SPHERE':
					// если здание апгрейдится, то не изменяем атрибуты соты
					if($buildingInUpgrade)
					{
						break;
					}

					$attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
					foreach($attributeTypeNames as $k1=>$attributeTypeName)
					{
						$attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
						foreach($attributeNames as $k2=>$attributeName)
						{
							if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
							{
								$delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
								$combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
							}
						}
					}
					break;

				case 'BUILDING':

					// если здание апгрейдится, то не изменяем атрибуты соты
					if($buildingInUpgrade)
					{
						break;
					}

					$attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
					foreach($attributeTypeNames as $k1=>$attributeTypeName)
					{
						$attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
						foreach($attributeNames as $k2=>$attributeName)
						{
							if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
							{
								$delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
								$combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
							}
						}
					}
					break;

				case 'COMB':

					$sql = "DELETE FROM ds_artefactattachedcomb
						WHERE id_comb = $comb_id AND id_artefact = $artefact_instance_id";

					$res = $this->common->executeQuery ( $sql );
					assert(empty($this->common->errormsg));

					$attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
					foreach($attributeTypeNames as $k1=>$attributeTypeName)
					{
						$attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
						foreach($attributeNames as $k2=>$attributeName)
						{
							if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
							{
								$delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
								$combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
							}
						}
					}
					break;

				case 'COMBCOMPLEX':

					$sql = "DELETE FROM ds_artefactattachedcomplex
						WHERE id_combcomplex = $combcomplex_id AND  id_artefact = $artefact_instance_id";

					$res = $this->common->executeQuery ($sql);
					assert(empty($this->common->errormsg));

					break;

				case 'ALLIANCE':

					$sql = "DELETE FROM ds_artefactattachedalliance
						WHERE id_artefact = $artefact_instance_id";

					$res = $this->common->executeQuery ( $sql );
					assert(empty($this->common->errormsg));

					break;

				case 'WAR':

					$sql = "DELETE FROM ds_artefactattachedcomb
						WHERE id_comb = $comb_id AND id_artefact = $artefact_instance_id";
					$res = $this->common->executeQuery ( $sql );
					assert(empty($this->common->errormsg));

					break;
			}
			
			// Логируем удаление артефакта в базу
			$sql = "INSERT INTO ds_dropped_artefacts
					(id_artefact, id_userinstance, birth_time, death_time)
					VALUES
					(".$artData['id_artefact'].", ".$artData['id_userinstance'].", '".$artData['birth_time']."', '".$artData['death_time']."')";
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			// Удаляем артефакт
			$sql = "DELETE FROM ds_artefactinstance WHERE id_artefactinstance = ".$artefact_instance_id;
			$this->common->executeQuery($sql);
			assert(empty($this->common->errormsg));
			
			// Логируем удаление артефакта в файл
			$str = "UserInstance: [".$artData['id_userinstance']."] ArtefactId: [".$artData['id_artefact']."] BirthTime: [".$artData['birth_time']."] DeathTime: [".$artData['death_time']."]";
			$this->_out($str);
		}

	}//END OF METHOD


}//END OF CLASS


?>