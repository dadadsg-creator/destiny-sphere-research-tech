<?php $CVS_Version = '$Revision$ ; $Date$';
/**
Всего:  485730
big_alpha_0_id_world_id_ring_id_combcomplex_id_comb_id_celltype_id_cell - 104422  UA_Action(732)
big_alpha_0_id_groundtype - 140409                                                UA_GetBuildMenu (87)
big_alpha_0_id_groundtype_id_cell - 67622                                         UA_Building(42)
big_alpha_0_get_days_minutes_seconds_sec - 18747                                  ResearchPanel (1307)
Итого: 331200

big_alpha_0_id_combcomplex_id_comb - 1561
big_alpha_0_artefactname__id_language_id_artefactinstance - 1174
big_alpha_0_build_names_id_language_id_b_from_id_b_to_id_userinstance - 2406      EH_UpgradeBuilding(77)
big_alpha_0_id_alliance_id_userisntance - 857
big_alpha_0_DS_Battle_GetBattleRoundResultByID - 37944
big_alpha_0_artefactbonus_id_artefactinstance - 22103
big_alpha_0_opponentfrom_id_userinstance - 1081

*/
class DS_CacheStatDBA extends DS_CacheDBA 
{
	private $totalFilesCount = 0;
	private $totalFilesSize = 0;
	private $minFilesCount = array('COUNT'=>0, 'FOLDER'=>0);
	private $maxFilesCount = array('COUNT'=>0, 'FOLDER'=>0);
	private $maxFilesSize = array('SIZE'=>0, 'FILE'=>0);
	private $minFilesSize = array('SIZE'=>0, 'FILE'=>0);
	
	public function __construct($cacheStorePath)
	{							
		parent::__construct($cacheStorePath);					
	}
	
	public function showStat()
	{
		$result = $this->_formData();
		$out = $this->_formOutput($result);
		
		return $out;
	}
	
	private function _formData()
	{
		$result = array();						

		for($i=0; $i < parent::$devisor; $i++)
		{					
			$handle = @opendir($this->cacheStorePath.'/'.$i.'/');
			
			if (!$handle) 
			{
				continue;
			}
    					
			$filesCounter = 0;
			$summarySize = 0; 
    		while (false !== ($file = readdir($handle))) 
    		{ 
    			if($file == '.' || $file == '..')
    			{
    				continue;
    			}
    			
    			$fileSize = filesize($this->cacheStorePath.'/'.$i.'/'.$file);
    			
    			if($this->minFilesSize['SIZE'] == 0 || ($this->minFilesSize['SIZE'] > $fileSize))
    			{
	    			$this->minFilesSize['SIZE'] = $fileSize;
	    			$this->minFilesSize['FILE'] = "/".$i."/".$file;
    			}
    		
    			if($this->maxFilesSize['SIZE'] == 0 || ($this->maxFilesSize['SIZE'] <  $fileSize))
    			{
	    			$this->maxFilesSize['SIZE'] = $fileSize;
	    			$this->maxFilesSize['FILE'] = "/".$i."/".$file;
    			}
    				
    			$summarySize += $fileSize;
        		$filesCounter++;        		
    		}
    		
    		$this->totalFilesSize += $summarySize;
    		$this->totalFilesCount += $filesCounter;
    		
    		if($this->maxFilesCount['COUNT'] == 0 || ($this->maxFilesCount['COUNT'] <  $filesCounter))
    		{
    			$this->maxFilesCount['COUNT'] = $filesCounter;
    			$this->maxFilesCount['FOLDER'] = "/".$i."/";
    		}
    		
    		if($this->minFilesCount['COUNT'] == 0 || ($this->minFilesCount['COUNT'] >  $filesCounter))
    		{
    			$this->minFilesCount['COUNT'] = $filesCounter;
    			$this->minFilesCount['FOLDER'] = "/".$i."/";
    		}
    		
    		$result[$i] = array
    		(
    			'FILES_COUNT' => $filesCounter,
    			'SUMMARY_SIZE' => $summarySize
    		);
    		closedir($handle); 		
		}
		
		return $result;
	}
	
	private function _formOutput($result)
	{
		$out = "<h1>DB_CACHE STATISTIC</h1>";
		
		$out .= "TOTAL_FILES = ".$this->totalFilesCount."<br>";
		$out .= "TOTAL_SIZE = ".$this->totalFilesSize."<br>";
		$out .= "TOTAL_FOLDERS = ".parent::$devisor."<br>";
		$out .= "----------------------------------<br>";
		
		$averageFilesInFolder = round($this->totalFilesCount/parent::$devisor);
		
		$out .= "AVERAGE_FILES_COUNT_IN_FOLDER = ".$averageFilesInFolder."<br>";
		$out .= "MIN_FILES_COUNT_IN_FOLDER = ".$this->minFilesCount['COUNT']." (".$this->minFilesCount['FOLDER'].") <br>";
		$out .= "MAX_FILES_COUNT_IN_FOLDER = ".$this->maxFilesCount['COUNT']." (".$this->maxFilesCount['FOLDER'].")<br>";
		$out .= "MAX_FILE_SIZE = ".$this->maxFilesSize['SIZE']." (".$this->maxFilesSize['FILE'].")<br>";
		$out .= "MIN_FILE_SIZE = ".$this->minFilesSize['SIZE']." (".$this->minFilesSize['FILE'].")<br>";
		$out .= "----------------------------------<br>";
		$out .= "<table border=1 width=300>";
		$out .= "<tr><th>Folder</th><th>Files</th><th>Size</th></tr>";
		
		foreach($result as $k=>$v)
		{
			$out .= "<tr>";	
			$out .= "<td>".$k."</td>";
			
			if($v['FILES_COUNT'] > $averageFilesInFolder)
			{
				if($v['FILES_COUNT'] > $averageFilesInFolder*2)
				{
					$filesCount = "<b><font color=red>!!!".$v['FILES_COUNT']."!!!</font></b>";
				}else{
					$filesCount = "<b><font color=red>".$v['FILES_COUNT']."</font></b>";
				}
				
			}else{
				$filesCount = $v['FILES_COUNT'];
			}
			$out .= "<td align=center>".$filesCount."</td>";
			$out .= "<td align=center>".$v['SUMMARY_SIZE']."</td>";
			$out .= "</tr>";	
		}
		
		$out .= "</table>";
		
		return $out;
	}		
}


?>