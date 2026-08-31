<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2010/07/07 10:56:52 $';

// закладка здани€ ALLIANCE_CONNECTOR - обзор членов аль€нса с постраничным выводом

class PN_PeaceAlliance extends UA_GetBuildMenu 
{

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;

		$uiid = $_SESSION['uiid'];
		
    $id_world = $_SESSION['world'];
  
    //ѕолучим аль€нс юзера
    $alliance = DS_Peace::getUserAlliance($uiid);
    if (!$alliance)
    {
    	//return true;
    	$my_alliance = 0;
    }
    else
     $my_alliance = $alliance['id_alliance'];
    
    //echo "my_alliance=$my_alliance<br>";
  
  
    //получим список всех существующих аль€нсов
    $alliance_list = DS_Alliance::GetAllAlliances($id_world);
  
    //получим список аль€нсов, которые как-то с нами контачат 
    if ($my_alliance)
      $curPeacelist =  DS_PeaceAlliance::getAlliancePeaceList($my_alliance);
    else
      $curPeacelist = array(); 
	  $alliancedata = array('allow'=>array(), 'offer' => array(), 'wait' => array(),
	  'dismiss'=>array(),'ready'=>array());
  
	  //Ѕежим по всем существующим аль€нсам, смотрим, в какую группу их отнести
    foreach ($alliance_list as $al)
    {
   	  if ($al['id_alliance']==$my_alliance) //наш собственный аль€нс - игнорируем
  	   continue;
  	  $you = 0;
  	  list($status, $peace_info) = $this->checkAlliance($al['id_alliance'], $curPeacelist, $my_alliance);
  	  //echo $status.", pi=".var_export($peace_info, true)."<br>"; 
  	  if ($status == -1)
        $ixname = 'allow';
  	  else if ($status == 1)
        $ixname = 'offer';  
  	  else if ($status == 2)
    	  $ixname = 'wait';
  	  else if ($status == 3)
        $ixname = 'ready';
  	  else if (($status == 4) || ($status == 5))
  	  {
  	  	if ( (($peace_info['who_offer']==$my_alliance) && ($status == 4)) || (($peace_info['who_offer']!=$my_alliance) && ($status == 5)))
  	  	  $you = 1;//мы €вл€емс€ инициаторами расторжени€
        $ixname = 'dismiss';
        $peace_info['you']  = $you;
  	  	//SU_DebugTools::LogAll("status=($status, my_alliance=$my_alliance, who_offer={$peace_info['who_offer']}, you={$you}",'alliance.log');
        
  	  }     
      //echo "<b>$ixname</b><br>";   
      
      $peace_info['name']  = $al['alliancename'];
      $peace_info['short'] = $al['alliancetag'];
      $peace_info['id']    = $al['id_alliance'];
  	  array_push($alliancedata[$ixname], $peace_info);
    }  

	  $xml = '<?xml version="1.0" encoding="utf-8"'."?><alliancepeace/>";

	  $doc = domxml_open_mem ( $xml );
	  $root = $doc->document_element ();		
	  $viewdata =  DS_Alliance::IsUserLeader($my_alliance, $uiid)?1:0;
    $root->set_attribute( 'name', $alliance['alliancename']);	
    $root->set_attribute( 'viewdata', $viewdata);	        
    $root->set_attribute( 'allowaction', 1);
    $root->set_attribute( 'maxdays', DE_WorldSettings::CreateForCurentWorld()->get('ALLIANCE_PEACE_DURATION_MAX'));
    //$alpeace = $root->new_child ("alliancepeace");	
    //SU_DebugTools::LogAll("2.0- ".var_export($alpeace, true),'alliance.log');

    //$alpeace->set_attribute ( 'name', $alliance['alliancename']);
    //SU_DebugTools::LogAll("2- ".var_export(alpeace, true),'alliance.log');
    
    foreach ($alliancedata as $k => $al_list)
    {
      $node = $root->new_child($k);	   
      foreach ($al_list as $al)
      {
        $ch = $node->new_child ("alliance");	    


        //echo var_export($al, true)."<br>";
        $al_leader = DS_Alliance::GetAllianceLeader($al['id']);
        $ch->set_attribute ( 'leader', $al_leader['username']);

        if (in_array($k, array('offer', 'wait','ready', 'dismiss')))
        {
          if ($al['id_alliance_1']==$my_alliance)
          {
            $my = 1;
            $he = 2;
          }  
          else 
          {  
            $my = 2;
            $he = 1;  
          }  
          
          $ch->set_attribute ( 'my_k', $al['price_kad_'.$my]);
          $ch->set_attribute ( 'my_n', $al['price_nano_'.$my]);
          $ch->set_attribute ( 'my_f', $al['price_food_'.$my]);
          $ch->set_attribute ( 'my_p', $al['price_prodium_'.$my]);
          $ch->set_attribute ( 'my_s', $al['price_silver_'.$my]);
          $ch->set_attribute ( 'my_e', $al['price_energy_'.$my]);

          $ch->set_attribute ( 'he_k', $al['price_kad_'.$he]);
          $ch->set_attribute ( 'he_n', $al['price_nano_'.$he]);
          $ch->set_attribute ( 'he_f', $al['price_food_'.$he]);
          $ch->set_attribute ( 'he_p', $al['price_prodium_'.$he]);
          $ch->set_attribute ( 'he_s', $al['price_silver_'.$he]);
          $ch->set_attribute ( 'he_e', $al['price_energy_'.$he]);
        }

        $peace_end_date = '';          
        if (in_array($k, array('offer', 'wait')))
        {
        	$messageparams = array($al['peace_duration']);
        	$peace_end_date = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ALLIANCE_PEACE_DURATION', $messageparams, null, null, false);
        }
        else if (in_array($k, array('ready', 'dismiss')))
        {
        	$messageparams = array($al['peace_duration'], $al['peace_end_date']);
        	$peace_end_date = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ALLIANCE_PEACE_DURATION_END', $messageparams, null, null, false);
        }
        
        if (in_array($k, array('ready','offer','wait','dismiss')))
        {
          $ch->set_attribute ( 'peace_duration', $al['peace_duration']);
          if (in_array($k, array('ready','dismiss')))
          {
            $ch->set_attribute ( 'peace_end_date', $al['peace_end_date']);
          }  
          if (in_array($k, array('dismiss')))
          {
            $ch->set_attribute ( 'you', $al['you']);
          }
        }
           

        $ch->set_attribute ( "peace_end_date", $peace_end_date );        
        $ch->set_attribute ( "name", $al['name'] );
        $ch->set_attribute ( "short", $al['short'] );
        $ch->set_attribute ( "id", $al['id'] );    
      }
    }
  
	
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
		
		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );
	
		return true;
	}
	
	
 function checkAlliance($id_al, $al_list, $my_alliance)
  {
   	 $curtime = time();
   	 foreach ($al_list as $al)
     {  
     	 if (($al['id_alliance_1']==$id_al) or ($al['id_alliance_2']==$id_al))
     	 {
			   	  //SU_DebugTools::LogAll('al='.var_export($al, true),'alliance.log');

     	   if (($al['status']>0) && $al['psec'] && (time() > $al['psec']))
			   {
  	        $alPeace = new UA_AlliancePeace();  

			   	  $leader1 = DS_Alliance::GetAllianceLeader($al['id_alliance_1']);
			   	  $leader2 = DS_Alliance::GetAllianceLeader($al['id_alliance_2']);

			   	  $comb1 = DE_CombAttributes::CreateFromCombID(intval($al['id_comb_1']));
            $alPeace->changeRes($comb1, $al, 1, $leader1['id_userinstance'],  +1);
 		        $comb2 = DE_CombAttributes::CreateFromCombID(intval($al['id_comb_2']));
            $alPeace->changeRes($comb2, $al, 2, $leader2['id_userinstance'],  +1);
			   	
            DS_PeaceAlliance::delAlliancePeace($al['id_peace']);  //удалим нафиг из списка
			  	  return array(-1, null);
			   }
			   
			   if ($al['status']==1)
			   {
			     return array(3, $al);//мир заключЄн
			   }
			   else if (($al['who_offer'] != $my_alliance) && ($al['status']==0))
			   {
			     return array(1, $al);//нам предложили
			   }
			   else if (($al['who_offer'] == $my_alliance) && ($al['status']==0))
			   {
			     return array(2, $al);//мы предложили
			   }
			   else if (($al['status']==4) || ($al['status']==5))
			   {
			     return array($al['status'], $al);//предложено разорвать мир
			   }			   
     	 }
     }
     return array(-1, null);
  }	
}

?>
