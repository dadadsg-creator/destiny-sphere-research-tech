<?php $CVS_Version = '$Revision$ ; $Date$';
class SI_ResultComb
{	
	private $combID;
	
	private $combName;
	
	private $allowedCombTypes = array('COLONY', 'HOME');
		
	private $combType;
	
	private $isCapture;
	
	private $protectField = array();
	
	/**	 
	 * «десь храним атрибуты комба
	 * $this->combAttributes[$attributeTypeName][$attributeName] = $val	 
	 * */
	private $combAttributes = array();		
		
	/** 
	 * —ведени€ о здани€х на €чейках и артефактах здани€
	 * 	 
	 * */		
	private $combCells = array();
	
	/**
	 * јрты хранилища
	 *
	 * @var array
	 */
	private $arhiologistArts = array();
	
	/**
	 * “рейдскопы доступные соте
	 *
	 * @var array
	 */
	private $tradeScopes = array();
	
	
	/**
	 * ёниты соты
	 *	 
	 * @var array
	 */
	private $units = array();
	
	public function __construct($combID)
	{
		assert((int)$combID > 0);
		$this->combID = (int)$combID;
		
		$combData = SI_SearchComb::GetCombDataByID($this->combID);
		$this->combName = $combData['combname'];
		assert(!empty($this->combName));
		$this->isCapture = $combData['is_capture'];
		assert($this->isCapture == 't' || $this->isCapture == 'f');
		
		if ($this->isCapture == 't') {
			$this->protectField = SI_SearchComb::GetProtectColonyByID($this->combID);
		}
	}
	
	public function getCombID()
	{
		return $this->combID;
	}
	
	public function setCombName($combName)
	{
		$this->combName = $combName;
	}
	
	public function getCombName()
	{
		return $this->combName;
	}
	
	public function getIsCapture()
	{
		return $this->isCapture;
	}
	
	
	public function getProtectField()
	{
		return $this->protectField;
	}
	
	public function setIsCapture()
	{
		$this->isCapture = 't';
	}
	
	public function setProtectField()
	{
		$this->protectField[0]['protected_field'] = 100;
		$nextDay = time() + (24 * 60 * 60);
		$endtime = date("Y-m-d H:i:s", $nextDay);
		$this->protectField[0]['end_temporary_defend'] = $endtime;
	}
	
	public function setCombAttribute($attributeTypeName, $attributeName, $val)
	{
		assert(!empty($attributeTypeName) && !empty($attributeName));
		@$this->combAttributes[$attributeTypeName][$attributeName] = $val;
	}
	
	public function getCombAttributes()
	{
		return $this->combAttributes;
	}
	
	public function setCell($cellData, $artefactData, $attachedBuildingAttributes)
	{		
		assert(is_array($cellData));
		assert(is_array($artefactData));
		assert(is_array($attachedBuildingAttributes));

		$arts = array();
		foreach($artefactData as $k=>$artefactArr)
		{
			$key = count($arts);
			
			$arts[$key]['id_artefact'] = $artefactArr['id_artefact'];
			$arts[$key]['immortal'] = $artefactArr['immortal'];
			$arts[$key]['birth_time'] = $artefactArr['birth_time'];
			$arts[$key]['death_time'] = $artefactArr['death_time'];
		}
		
		$attributes = array();
		foreach($attachedBuildingAttributes as $k=>$attributeData)
		{
			$attributes[] = $attributeData['id_attribute'];
		}
		
		$cellData['arts'] = $arts;
		$cellData['attributes'] = $attributes;
		$this->combCells[] = $cellData;		
	}		
	
	public function getCellData()
	{
		return $this->combCells;
	}
	
	public function setUnits($unitsData)
	{				
		assert(is_array($unitsData));
				
		$this->units = $unitsData;		
	}			
	
	public function getUnits()
	{
		return $this->units;
	}
	
	public function setArchArt($artData)
	{
		assert(is_array($artData));
		
		$key = count($this->arhiologistArts);	
		
		$this->arhiologistArts[$key]['id_artefact'] = $artData['id_artefact'];
		$this->arhiologistArts[$key]['immortal'] = $artData['immortal'];
		$this->arhiologistArts[$key]['birth_time'] = $artData['birth_time'];
		$this->arhiologistArts[$key]['death_time'] = $artData['death_time'];
	}
	
	public function getArchArt()
	{		
		return $this->arhiologistArts;
	}
	
	public function setBuildingArtefacts($type, $number, $artefactsArray)
	{
		assert(key_exists($type, $this->combCells));
		assert((int)$number > 0);
		
		assert(is_array($artefactsArray));
				
		@$this->buildingArtefacts[$type][$number] = $artefactsArray;
	}
	
	public function setTradeScope($scopeID)
	{
		assert((int)$scopeID > 0);
		
		$this->tradeScopes[] = $scopeID;
	}
	
	public function getTradeScopes()
	{		
		return $this->tradeScopes;
	}
	
}

?>