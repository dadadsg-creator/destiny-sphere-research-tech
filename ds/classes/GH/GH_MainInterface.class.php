<?php $CVS_Version = '$Revision$ ; $Date$';

class GH_MainInterface
{
	private $jsCheck = false;
	
	private $jsEnabled = false;
	
	private $helpSection;
	
	private $errorMsg = '';
	
	private $buildInfo = false;
	
	private $buildId;
	
	private $race;
	
	/**
	 * DE_BuildingByRace
	 *
	 * @var DE_BuildingByRace
	 */
	private $building;
	
	private $language;
	
	/**
	 * ID страницы с хелпом по зданиям
	 * Обманываем динамический компонент Building.php
	 *
	 * @var unknown_type
	 */
	public $Page = array('id' => 'buildings_voraner');
	
	private $pageIDByRace = array(
			1 => 'buildings_voraner',
			2 => 'buildings_liensu',
			3 => 'buildings_psolao',
		);
	
	public function __construct($data)
	{
		$this->_getParams($data);
		
		$this->_initLanguage();
		
		if($this->buildInfo)
		{
			$this->_outBuildingHelp();
		} else {
			$this->_outHelpSection();
		}
	}
	
	private function _getParams($data)
	{
		assert(is_array($data));
		
		if(key_exists('js', $data))
		{
			$this->jsCheck = true;
			$this->jsEnabled = (int)$data['js'];
		}
		
		if(key_exists('lang', $data))
		{
			$this->language = (int)$data['lang'];
		}
		
		if(key_exists('name', $data))
		{
			$this->helpSection = pg_escape_string($data['name']);
		} else {
			$this->errorMsg = 'NO_HELP_SECTION_NAME';
		}
		
		if($this->helpSection == 'BUILD_INFO')
		{
			if(key_exists('buildid', $data) && key_exists('race', $data))
			{
				$this->buildId = $this->_getBuildIdByName($data['buildid']);
				$this->race = (int)$data['race'];
				if($this->race > 0 && $this->race < 3 && $this->buildId > 0)
				{
					$this->building = DE_BuildingByRace::CreateFromRaceIDAndBuildingID($this->race, $this->buildId);
					$this->buildInfo = true;
				}
			}
		}
	}
	
	private function _outJSTest()
	{
		$out = "
			<html><head><title>DestinySphere HELP</title>
			<script language=\"JavaScript\" type=\"text/javascript\">
				newWin = window.open(\"index.php?js=1&name=".$this->name."\", \"_self\", \"height=340,width=460,status=no,toolbar=no,menubar=no,location=no\", true);
			</script>
			<noscript>
				<meta http-equiv=\"refresh\" content=\"0;url=index.php?js=0&name=".$this->name."\">
			</noscript></head><body></body></html>
			";
		die($out);
	}
	
	private function _initLanguage()
	{
		if(is_int($this->language) && $this->language > 0)
		{
			return;
		}
		
		$this->language = constant('DEFAULT_LANGUAGE');
	}
	
	private function _outBuildingHelp()
	{
		$buildName = $this->building->GetBuilding()->getName();
		$buildingRaceID = $this->race;
		$this->Page['id'] = $this->pageIDByRace[$this->race];
		
		include "help_page.tpl";
	}
	
	private function _outHelpSection()
	{
		$helpData = $this->_getHelpSectionDataByName();
		if(empty($this->errorMsg) && count($helpData) > 0)
		{
			$helpTitle = $helpData['help_title'];
			$helpContent = stripcslashes($helpData['help_content']);
		} else {
			$helpTitle = '';
			$helpContent = "<font color=red>Данный раздел помощи пока отсутствует.</font>";
		}
		include "help_page.tpl";
	}
	
	private function _getBuildIdByName($buildName)
	{
		assert(!empty($buildName));		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				id_building
			FROM
				ds_building
			WHERE
				buildingname = E'".pg_escape_string($buildName)."'
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		if(!empty($common->errormsg))
		{
			$this->errorMsg = 'CANT_GET_BUILDING_ID';
			return 0;
		}
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return (int)$res[0]['id_building'];
	}
	
	private function _getHelpSectionDataByName()
	{
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
				SELECT
					h.help_content,
					h.help_title
				FROM
					ds_help AS h
					INNER JOIN ds_help_section AS hs USING (id_help_section)
				WHERE
					h.id_language = ".$this->language."
					AND
					hs.help_section_name = E'".$this->helpSection."'
				";

		$res = $common->getResultArray("", $sql, "", "", "");
		if(!empty($common->errormsg))
		{
			$this->errorMsg = 'CANT_GET_SECTION_DATA';
			return 0;
		}
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return $res[0];
	}
}

?>