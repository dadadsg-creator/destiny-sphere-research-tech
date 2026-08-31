<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_BuyArtefacts extends UA_Action 
{
/**
	 * Enter description here...
	 *
	 * @var float
	 */	
	function __construct() 
	{
//		SU_DebugTools::LogAll('UA_PrivatePeace construct..'.var_export($this->root ,true),'peace.log');		
		parent::UA_Action();	
	}
	
	function checkLocalParams()
	{
		$timev   = $this->root->get_attribute('time');
    //SU_DebugTools::LogAll('CheckParams', 'alliance.log');
		return true;
	}

	function checkAction()
	{
		return true;
	}

	function performAction()
	{
		$artefacts = $this->root->get_elements_by_tagname('artefact');
		$sum = 0;
	
		
		$uid = $_SESSION['uid'];
		$user = DE_User::CreateFromID($uid);

    $WorldShortName = DE_World::CreateForCurrentWorld()->getShortName(); 
		
		$world = DE_WorldFromCollection::CreateFromID($WorldShortName);
		$member = new MU_EntityOperationMember($user, $world);
		$data = MU_SearchOperations::GetAccountData($member);

		$ts = array('sc'=>0,'gc'=>0);
		
		$goldVal = $data['GC'];
		$silverVal = $data['SC'];		
		$arts = array();
		
		
	  if(!$userInstance = DE_UserInstance::CreateFromID($_SESSION['uiid']))
	  {
			return false;
		}

		if(!$combs = $userInstance -> getCombsCollection() ) 
		{
			return false;
		}

		while ($comb = $combs->next())
		{
			if($comb->getCombRing()->getNumber() != 4)
			{
				$combID = $comb->getCombID();
				break;
			}
		}

		
		foreach ($artefacts as $a)
		{
		  $count = intval($a->get_attribute('count'));
		  $type  = $a->get_attribute('type');
		  $id    = intval($a->get_attribute('id'));
		  $life  = intval($a->get_attribute('life'));
		  
		  if ($count)
		  { 		  
		    $t = $type=='silver'?'sc':'gc';
		    $artinfo  = DS_Artefact::getArtInfo($id, $_SESSION['world'] );
		    $ts[$t]   = $artinfo["price{$life}{$t}"]*$count;
		    array_push($arts, array('id'=>$id, 'name'=>$artinfo['artefactname'],'count'=>$count,'price'=> $artinfo["price{$life}{$t}"], 'paymtype'=>strtoupper($t), 'life'=>$life));
		  }  
		  //SU_DebugTools::LogAll("id=$id, count=$count, type=$type",'art.log');
		}
		
		//SU_DebugTools::LogAll(var_export($ts, true)."\n data=".var_export($data, true).", arts=".var_export($arts, true),'art.log');
		if ($ts['sc']>$data['SC'] || $ts['gc']>$data['GC'])
		{
		   //iconv('cp1251', 'utf8', $errMsg)
		   $title = '';
       	
		   $errMsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_NOT_ENOUGH_MONEY_FOR_BUYING', array($ts['sc'], $ts['gc'], $data['SC'], $data['GC']), null, null, false);
		   
				
			 $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$errMsg.']]></txt></swloader>';
							
    	 SU_OutputHandler::Singleton()->addReadyString($alertText);
		}     
    else 
    {
       foreach($arts as $a)
       {
       	  DS_Artefact::AddArtefactToComb($a['id'],$a['life'], $combID, $a['count']);
          UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation($user, "BUY_ARTEFACT_{$a['life']}M", intval($a['price']*$a['count']), $a['paymtype'], $WorldShortName);       	  

          UC_PaymentServiceBase::$user= DE_User::CreateFromID(intval($_SESSION['uid']));
          UC_PaymentServiceBase::_Log('t', 'f', 't', 'f',  "BUY_ARTEFACT_{$a['life']}M", intval($a['price']*$a['count']), $a['paymtype'], "World: ".$WorldShortName);
       }
      
       $Msg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('CABINET_ARTEFACTS_ADDED', array(), null, null, false);
  	   $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$Msg.']]></txt></swloader>';
							
    	 SU_OutputHandler::Singleton()->addReadyString($alertText);
       
    }		
		return true;
	}

}
?>