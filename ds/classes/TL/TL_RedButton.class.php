<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Êëàññ ãåíåğàöèè ïğîòîêîëà äëÿ êğàñíîé êíîïêè
 *
 */
class TL_RedButton 
{
	static public function FormMenu(TI_RedButton $data)
	{
		$out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		
		$out .= '<scanner maxzond="0">';
			
			foreach($data->armys AS $k=>$v)
			{
				$out .= '<object ';
				$out .= 'id="'.$v['ID'].'" scan="0"';
				$out .= 'name="'.$v['NAME'].'" '; 
				$out .= 'type="'.$v['TYPE'].'" '; 
				$out .= 'speed="'.$v['SPEED'].'" '; 
				$out .= 'power="-" '; 
				$out .= 'action="'.$v['ACTION'].'" ';
				$out .= 'namefrom="'.$v['USER_FROM'].'" ';
				$out .= 'nameto="'.$v['USER_TO'].'" ';  
				$out .= 'startcomb="'.$v['START_COMB'].'" '; 
				$out .= 'endcomb="'.$v['END_COMB'].'" '; 
				$out .= 'startdate="'.$v['START_DATE'].'" ';
				$out .= 'enddate="'.$v['END_DATE'].'" ';
				$out .= '/>';
			}
			
			$out .= "<filter>";
				
				$out .= "<types>";
				
				foreach($data->armyTypes as $k=>$v)
				{
					$out .= '<type id="'.$v['INTERNAL_NAME'].'" name="'.$v['LOCALIZED_NAME'].'" cur="'.$v['CUR'].'" />';
				}
				
				$out .= "</types>";
				
				$out .= '<name def="'.$data->userName.'"/>';
				
				$out .= '<time val="'.$data->timeCurrent.'" min="'.$data->timeMin.'" max="'.$data->timeMax.'"/>';
				
				$out .= "<colonys>";
				
				foreach($data->colonis as $k=>$v)
				{
					$out .= '<colony name="'.$v['NAME'].'" addr="'.$v['ADDRESS'].'" id="'.$v['ID'].'" cur="'.$v['CUR'].'" />';
				}
				
				$out .= "</colonys>";
				
			$out .= "</filter>";
	
		$out .= '<alltabs><tab id="PN_RedButton" ln="" icon="BATTLE_OVERVIEW" cur="1"/></alltabs>';
          
		$out .= '</scanner>';
		
		return $out;
	}
}

?>