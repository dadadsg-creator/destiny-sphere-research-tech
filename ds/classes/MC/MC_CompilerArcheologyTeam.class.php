<?php $CVS_Version = '$Revision$ ; $Date$';

class MC_CompilerArcheologyTeam extends MC_CoompilerBase 
{
	/**
	 * Enter description here...
	 *
	 * @var array()
	 */
	private $data = array();
		
	
	public function __construct()
	{
		
	}
		
	static public function GetSingl()
	{
		return parent::CreateSingl(__CLASS__);
	}
	
	public function addData(DE_Comb $comb, $cellTypeId, $found, $artefacts)
	{
		assert(is_int($cellTypeId) && $cellTypeId > 0);
		assert(is_int($found) && $found >= 0);
		assert(is_array($artefacts));
		
		$this->data[$comb->getID()][$cellTypeId][] = array(
			'FOUND' => $found,
			'ARTEFACTS' => $artefacts
		);
	}
	
	public function compile(GB_Date $messageDate)
	{
		$UIID = (int)$_SESSION['uiid'];
		
		foreach ($this->data as $combID => $cellTypes)
		{
			foreach ($cellTypes as $cellTypeID => $arts)
			{
				$totalReturnedEmpty = 0;
				$totalDontHavePlace = 0;
				$totalGroups = 0;
				$foundArts = array();
				
				foreach ($arts as $k => $art)
				{
					switch ($art['FOUND']) 
					{
						case 0:
							$totalReturnedEmpty++;
							break;
					
						case 1:
							foreach ($art['ARTEFACTS'] as $k => $artData)
							{
								if(key_exists($artData['SCOPE'], $foundArts) && key_exists($artData['PREFIX'], $foundArts[$artData['SCOPE']]) && key_exists($artData['NAME'], $foundArts[$artData['SCOPE']][$artData['PREFIX']]))
								{
									$foundArts[$artData['SCOPE']][$artData['PREFIX']][$artData['NAME']]++;
								} else {
									$foundArts[$artData['SCOPE']][$artData['PREFIX']][$artData['NAME']] = 1;
								}
							}
							asort($foundArts);
							foreach ($foundArts as $k => $v)
							{
								asort($foundArts[$k]);
							}
							break;
						
						case 2:
							$totalDontHavePlace++;
							break;
					}
					
					$totalGroups++;
				}
				
				$foundArtsString = '';
				
				$foundArtsCount = 0;
							
				foreach ($foundArts as $scope => $foundArtsScope)
				{
					foreach ($foundArtsScope as $prefix => $foundArtsPrefix)
					{
						foreach ($foundArtsPrefix as $artName => $count)
						{
							$foundArtsString .= $artName;
							if($count > 1)
							{	
								$foundArtsString .= ' (x'.$count.')';
							}
							$foundArtsString .= "\n";
							$foundArtsCount += $count;
						}
					}
				}
				
				if(empty($foundArtsString))
				{
					$foundArtsString = '0';
				} else {
					$foundArtsString = $foundArtsCount."\n".$foundArtsString;
				}
				
				$messageParams[0] = DE_Comb::CreateFromID((int)$combID)->getName();
				$messageParams[1] = DS_Archeology::getCellLocalizedName((int)$cellTypeID);
				$messageParams[2] = $totalGroups;
				//$messageParams[3] = $totalReturnedEmpty;
				$messageParams[3] = $foundArtsString;
				DE_SystemMessages::AddMessageFromMessageCompiler($messageDate, $UIID, "ARCHEOLOGY_TEAM_RETURN", $messageParams, 0, 'BROWN', $accumulatorStatus = 'ACCUMULATE');	
			}
		}
		
		unset($messageParams);
		if($totalDontHavePlace > 0)
		{
			$messageParams[0] = $totalDontHavePlace;
			DE_SystemMessages::AddMessageFromMessageCompiler($messageDate, $UIID, "ARCHEOLOGY_TEAM_DONT_HAVE_PLACE", $messageParams, 0, 'BROWN', $accumulatorStatus = 'ACCUMULATE');	
		}
/*
		if ((constant('DEFAULT_LANGUAGE_SHORT') == 'L'))
		{
		   $token = DS_User::GetOAuthInfoByUserId($_SESSION['uid']);
		   if ($token)
		   {
		      $msg = str_replace(array('{b}','{/b}'), array('',''),  DE_SystemMessages::$clearMessage);
          
		      if ($_SESSION['lang']==5)
		      {
            //$msg = iconv("windows-1257","UTF-8", $msg);
            //SU_DebugTools::LogAll('msg='.$msg,'oauth.log'); 
            //$msg = mb_convert_encoding($msg, "UTF-8", "windows-1257");
            $msg = iconv("windows-1257", "UTF-8",$msg );
		      }  
          else
		        $msg = DE_SystemMessages::win2utf($msg);
    		  $params = array('type' => 'artifact',
	    	  'text' => $msg, 'time'=> time());
		  
     		  $odata = MakeOAuthNotification($token['open_key'], $token['secret_key'], $params );    
		   }
		}
*/		
	}
	
}


?>