<?php $CVS_Version = '$Revision: 1.1 $ ; $Date: 2010/05/11 11:04:47 $';

// закладка здания ALLIANCE_CONNECTOR - обзор членов альянса с постраничным выводом

class PN_PeacePrivate extends UA_GetBuildMenu 
{

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;

		$limit = 25;

		if ( $this->page == "" || $this->page <= 0 ) $this->page = 1;

		$offset = $limit * ( $this->page - 1 );

		if ( $offset < 0 ) $offset = 0;

		$uiid = $_SESSION['uiid'];
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><allianceprivatepeace />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		$res = DS_Peace::getPrivatePeace($uiid);
		
		//SU_DebugTools::LogAll('res='.var_export($res, true),'peace.log');
		if ($res)			
		{
		 foreach($res as $r)
		 {
			 if ($r['psec'] && (time() > $r['psec']))
			 {
          DS_Peace::delPrivatePeace($r['id_peace']);  //удалим нафиг из списка
			  	continue; //Время, когда расторгнутый мир должен перестать дествовать, наступило - 
			            //поэтому его в списке не показываем
			 }
			 if ($r['psec'])
			 {
			    continue;
			 }
       
			 if ($uiid == $r['id_userinstance1'])
        $another_user = 2;
       else  
        $another_user = 1;

       $another_uiid = $r['id_userinstance'.$another_user];
      
		   $alliance = DS_Peace::getUserAlliance($another_uiid);
		  
		   
		   if (!$r['psec'])
		   {
		   	 
		     if (($r['status']==0)  && ($r['who_offer'] != $uiid) )
          $ch = $root->new_child ("request");
         else  
          $ch = $root->new_child ("list");
		   }
		   
       
       $ch->set_attribute ( "id_alliance", $alliance['id_alliance'] );
       $ch->set_attribute ( "member", $r['username'.$another_user] );
       $ch->set_attribute ( "tag", $alliance['alliancetag'] );
       $ch->set_attribute ( "joined", $r['status'] );
 	 	 } 
		}

		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
		
		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );
	
		return true;
	}
}

?>
