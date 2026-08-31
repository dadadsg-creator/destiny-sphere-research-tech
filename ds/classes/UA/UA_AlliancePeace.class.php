<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_AlliancePeace extends UA_Action 
{
/**
	 * Enter description here...
	 *
	 * @var float
	 */	
	private $nick; //ник игрока, с которым заключаетс€ миро	
	
	private $status;//статус мира - прин€ть/отклонить
	
  private $id_userinstance_with_whom; //uiid игрока, с которым заключаем/расторгаем мир	
	function __construct() 
	{
//		SU_DebugTools::LogAll('UA_PrivatePeace construct..'.var_export($this->root ,true),'peace.log');		
		parent::UA_Action();	
	}
	
	function checkLocalParams()
	{
		$timev   = $this->root->get_attribute('time');
		
		//SU_DebugTools::LogAll(var_export($timev, true), 'alliance.log');

		//$peace_duration = $timev->get_attribute('value');
		
		
		$this->status = $this->root->get_attribute('status');
    //SU_DebugTools::LogAll('CheckParams', 'alliance.log');
		return true;
	}

	function checkAction()
	{
		return true;
	}

	function performAction()
	{
		//SU_DebugTools::LogAll('status='.$this->status, 'alliance.log');

		//ѕолучим аль€нс юзера
		$uiid = intval($_SESSION['uiid']);
    $alliance = DS_Peace::getUserAlliance($uiid);
    $my_alliance = intval($alliance['id_alliance']);
		$pD = array();
	  $offer_al = intval($this->root->get_attribute('alliId'));
 	  $offer_al_info = DS_Alliance::GetByID($offer_al);
	  $al_leader = DS_Alliance::GetAllianceLeader($offer_al);

    if ($offer_al > $my_alliance  )
    {
      $my_n = 1; $he_n = 2; //меньший ID имеет N 1
    }  
    else 
    {
      $my_n = 2; $he_n = 1; //меньший ID имеет N 1
    }
    		 
	  if ($this->status == 'setPeace')
		{
       $pD['id_alliance_'.$my_n]  = $my_alliance;
       $pD['id_alliance_'.$he_n]  = $offer_al;
		   $pD['price_silver_'.$my_n] = $this->root->get_attribute('my_s'); 
		   $pD['id_comb_'.$my_n]      = $this->comb; 
		   $pD['id_comb_'.$he_n]      = 0; 

       $pD['status'] = 0;
       
       $pD['peace_date'] = date("Y-m-d H:i:s"); 
       $pD['who_offer']  = $my_alliance;
       
		   $pD['peace_duration'] = $this->root->get_attribute('time');

		   $pD['price_prodium_'.$my_n] = $this->root->get_attribute('my_p'); 
		   $pD['price_kad_'.$my_n] = $this->root->get_attribute('my_k'); 
		   $pD['price_nano_'.$my_n] = $this->root->get_attribute('my_n'); 
		   $pD['price_food_'.$my_n] = $this->root->get_attribute('my_f'); 
		   $pD['price_energy_'.$my_n] = $this->root->get_attribute('my_e'); 
		   $pD['id_comb_'.$my_n] = $this->comb; 
		   
		   $pD['price_prodium_'.$he_n] = $this->root->get_attribute('he_p'); 
		   $pD['price_kad_'.$he_n] = $this->root->get_attribute('he_k'); 
		   $pD['price_nano_'.$he_n] = $this->root->get_attribute('he_n'); 
		   $pD['price_food_'.$he_n] = $this->root->get_attribute('he_f'); 
		   $pD['price_energy_'.$he_n] = $this->root->get_attribute('he_e'); 
		   $pD['price_silver_'.$he_n] = $this->root->get_attribute('he_s'); 
		   
       
		   $curcomb = DE_CombAttributes::CreateFromCombID($this->comb);
		   $r = $this->checkRes($curcomb, $pD, $my_n);
		   if ($r)
		   {
				   $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ALLIANCE_NOT_ENOUGH_RES', array(), null, null, false);
		    	//$alertmsg  = str_replace(array('{','}'),array('<','>'), $alertmsg);
			
		       $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';
		        
		       SU_OutputHandler::Singleton()->addReadyString($alertText);			
		   }
		   else 
		   {
		      DS_PeaceAlliance::offerAlliancePeace($pD); 
		      $this->changeRes($curcomb, $pD, $my_n, $uiid, -1);
		 		   
          $this->sendMessage(0, 'ALLIANCE_PEACE_OFFER_SUBJECT', 'ALLIANCE_PEACE_OFFER', 
          array($alliance['alliancename'], $_SESSION['s_login'], $offer_al_info['alliancename']), 'PRIVATE', intval($al_leader['id_userinstance']));
             
          DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_PEACE_OFFER_SYSMSG", array($offer_al_info['alliancename']) );
		   }
		}
		else if ($this->status == 'confirmPeace')
		{
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al);
		  if ($peace)
		  {
		  	$curcomb = DE_CombAttributes::CreateFromCombID($this->comb);
		    $r = $this->checkRes($curcomb, $peace, $my_n);
		    if ($r)
		    {
				   $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ALLIANCE_NOT_ENOUGH_RES', array(), null, null, false);
		       $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';
		        
		       SU_OutputHandler::Singleton()->addReadyString($alertText);			
		    }
		  	else 
		  	{
    		  $peace['id_comb_'.$my_n] = $this->comb;
		  		DS_PeaceAlliance::confirmPeace($peace, $my_n);   
		      $this->changeRes($curcomb, $peace, $my_n, $uiid, -1);
			    
	 	      //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
			    $this->sendMessage(0, 'ALLIANCE_PEACE_OFFER_ACCEPTED_SUBJECT', 'ALLIANCE_PEACE_OFFER_ACCEPTED', 
          array( $alliance['alliancename'], $_SESSION['s_login']), 'PRIVATE', intval($al_leader['id_userinstance']));       
          DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_PEACE_OFFER_ACCEPTED_SYSMSG", array($offer_al_info['alliancename']) );

  		    $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
        
		      $uaAl = new UA_Alliance();
		    
  		    $uaAl->sendAllianceMessage($my_alliance, $this->langId, 0, 'ALLIANCE_CONFIRM_PEACE_ALMSG_SUBJECT', 'ALLIANCE_CONFIRM_PEACE_ALMSG', array($offer_al_info['alliancename'], $peace['peace_end_date']));
  		    $uaAl->sendAllianceMessage($offer_al, $this->langId, 0, 'ALLIANCE_CONFIRM_PEACE_ALMSG_SUBJECT', 'ALLIANCE_CONFIRM_PEACE_ALMSG', array($alliance['alliancename'], $peace['peace_end_date']));
  		    
  		    //остановим армии
  		    $my_alliance_obj = DE_Alliance::CreateFromID($my_alliance);
  		    $he_alliance_obj = DE_Alliance::CreateFromID($offer_al);
  		    
  		    $my_al_users = DS_Alliance::GetAllAllianceMembers($my_alliance_obj);
  		    $he_al_users = DS_Alliance::GetAllAllianceMembers($he_alliance_obj);
  		    
  		    //ѕриходитс€ провер€ть комобинацию каждого с каждым
          $now = GB_TypeFactory::CreateConstant('GB_Date', time());
  		    foreach($my_al_users as $u1)
  		    {
  		      $u1['id_userinstance'] = intval($u1['id_userinstance']);
  		    	foreach ($he_al_users  as $u2)
  		      {
  		          $u2['id_userinstance'] = intval($u2['id_userinstance']);
    		      	$r1 = DS_Peace::StopArmy($u1['id_userinstance'], $u2['id_userinstance']);

         		    if ($r1)            
                {
         		       DE_SystemMessages::AddMessageFromMessageCompiler($now, $u1['id_userinstance'], 'ARMY_RETURN1', array($offer_al_info['alliancename'], DE_UserInstance::CreateFromID($u2['id_userinstance'])->getUser()->getName()), 1, 'RED');
                   DE_SystemMessages::AddMessageFromMessageCompiler($now, $u2['id_userinstance'], 'ARMY_RETURN2', array($alliance['alliancename'], DE_UserInstance::CreateFromID($u1['id_userinstance'])->getUser()->getName()), 1, 'RED');
                }
         		    $r2 = DS_Peace::StopArmy($u2['id_userinstance'], $u1['id_userinstance']);
         		    if ($r2)
         		    {
         		       DE_SystemMessages::AddMessageFromMessageCompiler($now, $u2['id_userinstance'], 'ARMY_RETURN1', array($alliance['alliancename'], DE_UserInstance::CreateFromID($u1['id_userinstance'])->getUser()->getName()), 1, 'RED');
                   DE_SystemMessages::AddMessageFromMessageCompiler($now, $u1['id_userinstance'], 'ARMY_RETURN2', array($offer_al_info['alliancename'], DE_UserInstance::CreateFromID($u2['id_userinstance'])->getUser()->getName()), 1, 'RED');
         		    }
  		      }
  		    }
		  	}  
		  }  
		}
		else if ($this->status == 'declinePeace')
		{
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
		  if ($peace)
		  {
 		    $he_comb = DE_CombAttributes::CreateFromCombID((int)$peace['id_comb_'.$he_n]);
        $this->changeRes($he_comb, $peace, $he_n, $al_leader['id_userinstance'],  +1, 0);
		  	
  		  DS_PeaceAlliance::delAlliancePeace($peace['id_peace']);
	   	  //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
		  	$this->sendMessage(0, 'ALLIANCE_PEACE_OFFER_DECLINE_SUBJECT', 'ALLIANCE_PEACE_OFFER_DECLINE', 
        array($alliance['alliancename'], $_SESSION['s_login']), 'PRIVATE', intval($al_leader['id_userinstance']));       
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_PEACE_OFFER_DECLINE_SYSMSG", array($offer_al_info['alliancename']) );
		  }  
		}
		else if ($this->status == 'cancelPeace')
		{
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
		  //SU_DebugTools::LogAll(var_export($peace, true).", my_n={$my_n}", 'alliance.log');
		  if ($peace)
		  {
 		    $my_comb = DE_CombAttributes::CreateFromCombID(intval($peace['id_comb_'.$my_n]));
        $this->changeRes($my_comb, $peace, $my_n, $uiid,  +1, 0);
		  	
  		  DS_PeaceAlliance::delAlliancePeace($peace['id_peace']);
	   	  //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
		  	$this->sendMessage(0, 'ALLIANCE_PEACE_OFFER_CANCEL_SUBJECT', 'ALLIANCE_PEACE_OFFER_CANCEL', 
        array($alliance['alliancename'], $_SESSION['s_login']), 'PRIVATE', intval($al_leader['id_userinstance']));       
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_PEACE_OFFER_CANCEL_SYSMSG", array($offer_al_info['alliancename']) );
		  }  
		}
	  else if ($this->status == 'terminatePeace')
	  {
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
		  //SU_DebugTools::LogAll(var_export($peace, true).", my_n={$my_n}", 'alliance.log');
		  if ($peace)
		  {
		  	$status =  ($peace['who_offer']==$my_alliance)?4:5;
  		  DS_PeaceAlliance::setPeaceStatus($peace['id_peace'], $status);
	   	  //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
		  	$this->sendMessage(0, 'ALLIANCE_TERMINATE_PEACE_SUBJECT', 'ALLIANCE_TERMINATE_PEACE', 
        array($alliance['alliancename'], $_SESSION['s_login'], $offer_al_info['alliancename']), 'PRIVATE', intval($al_leader['id_userinstance']));       
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_TERMINATE_PEACE_SYSMSG", array($offer_al_info['alliancename']) );
		  }  
	  }
	  else if ($this->status == 'declineTerminatePeace')
	  {
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
		  //SU_DebugTools::LogAll(var_export($peace, true).", my_n={$my_n}", 'alliance.log');
		  if ($peace)
		  {
  		  DS_PeaceAlliance::setPeaceStatus($peace['id_peace'], 1);
	   	  //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
		  	$this->sendMessage(0, 'ALLIANCE_DECLINE_TERMINATE_PEACE_SUBJECT', 'ALLIANCE_DECLINE_TERMINATE_PEACE', 
        array($alliance['alliancename'], $_SESSION['s_login'], $offer_al_info['alliancename']), 'PRIVATE', intval($al_leader['id_userinstance']));       
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_DECLINE_TERMINATE_PEACE_SYSMSG", array($offer_al_info['alliancename']) );
		  }  
	  }
	  else if ($this->status == 'cancelTerminate')
	  {
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
		  //SU_DebugTools::LogAll(var_export($peace, true).", my_n={$my_n}", 'alliance.log');
		  if ($peace)
		  {
  		  DS_PeaceAlliance::setPeaceStatus($peace['id_peace'], 1);
	   	  //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
		  	$this->sendMessage(0, 'ALLIANCE_TERMINATE_PEACE_CANCEL_SUBJECT', 'ALLIANCE_TERMINATE_PEACE_CANCEL', 
        array($alliance['alliancename'], $_SESSION['s_login'], $offer_al_info['alliancename']), 'PRIVATE', intval($al_leader['id_userinstance']));       
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_TERMINATE_PEACE_CANCEL_SYSMSG", array($offer_al_info['alliancename']) );
		  }  
	  }
	  else if ($this->status == 'confirmTerminatePeace')
	  {
		  $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance, $offer_al); 
		  //SU_DebugTools::LogAll(var_export($peace, true).", my_n={$my_n}", 'alliance.log');
		  if ($peace)
		  {
  		  DS_PeaceAlliance::deletePeace($peace['id_peace']);
 		    $my_comb = DE_CombAttributes::CreateFromCombID(intval($peace['id_comb_'.$my_n]));
        $this->changeRes($my_comb, $peace, $my_n, $uiid,  +1);
 		    $he_comb = DE_CombAttributes::CreateFromCombID(intval($peace['id_comb_'.$he_n]));
        $this->changeRes($he_comb, $peace, $he_n, $al_leader['id_userinstance'],  +1);
          		  
	   	  //SU_DebugTools::LogAll('al_leader='.var_export($al_leader, true), 'alliance.log');
		  	$this->sendMessage(0, 'ALLIANCE_TERMINATE_PEACE_ACCEPT_SUBJECT', 'ALLIANCE_TERMINATE_PEACE_ACCEPT', 
        array($alliance['alliancename'], $_SESSION['s_login'], $offer_al_info['alliancename']), 'PRIVATE', intval($al_leader['id_userinstance']));       
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ALLIANCE_TERMINATE_PEACE_ACCEPT_SYSMSG", array($offer_al_info['alliancename']) );

        $uaAl = new UA_Alliance();
	    
  		  $uaAl->sendAllianceMessage($my_alliance, $this->langId, 0, 'ATTACK_ON_PEACE_USER_BREAK_GLOBAL_SUBJECT', 'ALLIANCE_TERMINATE_PEACE_ALMSG', array($offer_al_info['alliancename']));
  		  $uaAl->sendAllianceMessage($offer_al, $this->langId, 0, 'ATTACK_ON_PEACE_USER_BREAK_GLOBAL_SUBJECT', 'ALLIANCE_TERMINATE_PEACE_ALMSG', array($alliance['alliancename']));
		  }  
	  }
	  					  				
		$menu = new PN_PeaceAlliance();
    //$menu = new PN_AllianceManagement();
		$menu->setBID(14);
		$menu->setTAB('PN_PeaceAlliance');

		assert($menu->getPanel(true));		
		return true;
	}

	function changeRes($curcomb, $pD, $my_n, $uiid, $sign, $getTax=1)
	{
		 $tax = 1;
		 if ($getTax && $sign>0)
		 {
		   $curTax = DE_WorldSettings::CreateForCurentWorld()->get('ALLIANCE_PEACE_TAX');
		   $tax = 1 - 0.01*$curTax;
		 }
	
		 if ($pD['price_kad_'.$my_n])
       $curcomb->deltaAttribute('CURRENT_AMOUNT', 'KADERIUM', (float)$sign*intval($pD['price_kad_'.$my_n]*$tax) );
     if ($pD['price_nano_'.$my_n])
       $curcomb->deltaAttribute('CURRENT_AMOUNT', 'NANO_CRYSTAL', (float)$sign*intval($pD['price_nano_'.$my_n]*$tax));
     if ($pD['price_prodium_'.$my_n])
       $curcomb->deltaAttribute('CURRENT_AMOUNT', 'PRODIUM', (float)$sign*intval($pD['price_prodium_'.$my_n]*$tax));
     if ($pD['price_food_'.$my_n])
       $curcomb->deltaAttribute('CURRENT_AMOUNT', 'FOOD', (float)$sign*intval($pD['price_food_'.$my_n]*$tax));
     if ($pD['price_energy_'.$my_n])
       $curcomb->deltaAttribute('CURRENT_AMOUNT', 'STASIS_ENERGY', (float)$sign*intval($pD['price_energy_'.$my_n]*$tax));
     
     if ($pD['price_silver_'.$my_n] && $sign==-1)    
     {
        UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(DE_User::CreateFromID(DE_UserInstance::CreateFromID($uiid)->getUserID()), 'ALLIANCE_PEACE_SET', (int)$pD['price_silver_'.$my_n]*$tax, 'SC', DE_World::CreateForCurrentWorld()->getShortName());
     }
     else if ($pD['price_silver_'.$my_n] && ($sign==1))    
     {
     	  if (intval(ceil($pD['price_silver_'.$my_n]*$tax)) > 0)
          UC_PaymentServiceMoneyOperation::EnterMoney(DE_UserInstance::CreateFromID(intval($uiid))->getUserID(), intval($pD['price_silver_'.$my_n]*$tax), 'SC', 'ALLIANCE_PEACE_TERMINATE',  DE_World::CreateForCurrentWorld()->getShortName());
     }        
	}	
	
	function checkRes($curcomb, $pD, $my_n)
	{
		if ($curcomb->getAttribute('CURRENT_AMOUNT', 'KADERIUM', true) < 
		         intval($pD['price_kad_'.$my_n]))
		{
		  return 'KADERIUM';
		}
		elseif ($curcomb->getAttribute('CURRENT_AMOUNT', 'NANO_CRYSTAL', true) < 
		        intval($pD['price_nano_'.$my_n]))
		{
		  return 'NANO_CRYSTAL';
		}
		elseif ($curcomb->getAttribute('CURRENT_AMOUNT', 'PRODIUM', true) < 
		        intval($pD['price_prodium_'.$my_n]))
		{
		  return 'PRODIUM';
		}
		elseif ($curcomb->getAttribute('CURRENT_AMOUNT', 'FOOD', true) < 
		        intval($pD['price_food_'.$my_n]))
		{
		  return 'FOOD';
		}					
		elseif ($curcomb->getAttribute('CURRENT_AMOUNT', 'STASIS_ENERGY', true)	< 
		        intval($pD['price_energy_'.$my_n]))
		{
		  return 'STASIS_ENERGY';
		}

    if (!UC_PaymentServiceMoneyOperation::CheckMoney(DE_User::CreateFromID(DE_UserInstance::CreateFromID($this->uiid)->getUserID()), intval($pD['price_silver_'.$my_n]), 'SC', DE_World::CreateForCurrentWorld()->getShortName()))
    {
		  return 'SILVER';
    }
    return 0;
	}
	
	function sendMessage($fromUserInstanceID, $subjectname, $messagename, array $messageparams, $scope, $toUserInstanceID)
	{
  	$subject = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($subjectname, array(), null, null, false);
		
  	$str = 'Pasi&#363;lyti aljansin&#303; taik&#261;';
    //if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
	//			$message = GL_EncodeGameMessage::Encode($str, $this->langId);
		//else 
		    $message = GL_EncodeGameMessage::Encode(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage($messagename, $messageparams, null, null, false), $this->langId);		
		$messageScopeID = DS_Message::GetMessageScopeIDByName($scope);
		assert($messageScopeID > 0);
		
		$msg = new UA_SendMessage();
		$msg->to = DE_UserInstance::CreateFromID($toUserInstanceID)->getUser()->getName();
		
		
		$msg->uiid = (int)$fromUserInstanceID;
		$msg->messageScopeID = (int)$messageScopeID;
		$msg->messageScopeName = $scope;

		//if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
		//  $msg->subject = GL_EncodeGameMessage::Encode($str);
		//else 
		  $msg->subject = GL_EncodeGameMessage::Encode($subject);
		  
		$msg->messagetext = $message;
		//if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
		//  SU_DebugTools::LogAll("subj=".$msg->subject.",orig=".$subject,'alp.log');
		//$msg->original_messagetext = $msg->convert_to_utf8($message);
				
		$res = $msg->checkAction();
		$res = $msg->performAction(false);
	}	
}
?>