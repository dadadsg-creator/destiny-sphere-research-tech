<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:56:38 $';

class UA_GetCombArts extends UA_Action
{

	function __construct()
	{
		$this->UA_Action();
	}//END OF CONSTRUCTOR

	function checkLocalParams ()
	{
		return true;
	}//END OF FUNCTION

	function checkAction ()
	{
		assert(key_exists('profi_game', $_SESSION));

		if($_SESSION['profi_game'] != 1)
		{
			SU_OutputHandler::Singleton()->addString("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<key addr=\"index_art_mode_demo.php\" target_load=\"_blank\"/>", 'loadkey');
			echo SU_OutputHandler::Singleton()->fetchAll();
			exit;
		}

		return true;
	}//END OF FUNCTION

	function performAction ()
	{
		$artAndCellData = DS_Comb::GetArtsOnCombsByCells($this->comb, $this->celltype);

		$outData = array();
		foreach($artAndCellData as $k=>$v)
		{
			if($this->celltype == 2)
			{
				$mask = GB_CombCentralViewMask::GetServerBaseMask();
				$cellNumber = $mask[(int)$v['cellnumber']];
			}else{
				$cellNumber = (int)$v['cellnumber'];
			}
				
			$outData[$cellNumber]['slots'] = $v['slots_count'];
			$outData[$cellNumber]['production_type'] = $v['production_type'];
			$outData[$cellNumber]['id_building'] = $v['id_building'];
				
			$tmpArray = array();
			if(!is_null($v['artefactname']))
			{
				$tmpArray = array
				(
					'artefactname' => trim($v['artefactname']), 
					'typename' => $v['typename'],
					'attributename' => $v['attributename'], 
					'value' => $v['value'],
					'id_artefactinstance' => $v['id_artefactinstance'],					
					'immortal' => $v['immortal'],					
					'death_time' => $v['death_time'],					
				);

				$outData[$cellNumber]['arts'][] = $tmpArray;
			}else{
				$outData[$cellNumber]['arts'] = $tmpArray;
			}
				
				
		}
		$out = $this->_formOutput($outData);
		SU_OutputHandler::Singleton()->addString($out, 'xmldata');

		return true;
	}

	private function _formOutput($outData)
	{
		assert(is_array($outData));

		$out = '<?xml version="1.0" encoding="utf-8"?>'."\n<arthint>";
		foreach($outData as $cellID=>$cellData)
		{
			if($cellData['id_building'] == 26)
			{
				$buldingType = "WAR_ARTS";
			}else{
				$buldingType = "COMBO";
			}
				
			// јтрибут type должен быть один: тип производства здани€, а если его нет, то COMBO/WAR_ARTS.
			// –аньше выводились оба type сразу: Flash брал первый, а Ruffle на дубле атрибута обрывал разбор всего ответа.
			$cellType = empty($cellData['production_type']) ? $buldingType : $cellData['production_type'];
			$out .= '<cell id="'.$cellID.'" total="'.$cellData['slots'].'" type="'.$cellType.'">';
			if(count($cellData['arts']) > 0)
			{
				foreach($cellData['arts'] as $k=>$artData)
				{					
					// ≈сли артефкт верменный
					if ($artData['immortal'] == 'f') {
						// ¬ычисл€ем количество секунд до окончани€ скока его действи€
						$remain = ' remain="'.(strtotime($artData['death_time']) - strtotime("now")).'"';			
					} else {
						$remain = "";
					}
					
					if($artData['typename'] == 'BONUS_RELATIVE')
					{
						$postfix = 's';
						$artData['value'] = $artData['value'] * 100;
					}else{
						$postfix = '';
					}
					$out .= '<art id="'.$artData['artefactname'].'" bonus="'.$artData['value'].$postfix.'" type="'.$artData['attributename'].'" id_art="'.$artData['id_artefactinstance'].'"'.$remain.'/>';
				};
			}

			$out .= '</cell>';
		}

		$out .= '</arthint>';

		return 	$out;
	}
}//END OF CLASS


?>