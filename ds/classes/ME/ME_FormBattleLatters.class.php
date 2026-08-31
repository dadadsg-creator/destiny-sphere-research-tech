<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_FormBattleLatters
{
	static public function Exec(DE_Battle $battle)
	{

		$common = DS_Common::CreateForCurentDB();
		
		$ehsql = sprintf ( "select id_messagescope
							from ds_messagescope
							where messagescopename = 'BATTLE'" );
		$ehres = $common->getResultArray ( "id_messagescope_batte", $ehsql, "", "", "GLOBAL_CACHE" );
		assert(empty($common->errormsg) && is_array($ehres) && count($ehres)>0);		
		
		assert(key_exists('id_messagescope', $ehres[0]));		
		$messageScopeID = (int)$ehres[0]['id_messagescope'];
		assert($messageScopeID > 0);
		
		$langID = (int)$_SESSION['lang'];
		assert(is_int($langID) && $langID>0);
		
		$ehsql = sprintf ( "select ltl.text
							from ds_localizedtextlanguage ltl,
							ds_systemmessage sm
							where sm.id_localizedtext = ltl.id_localizedtext
							and ltl.id_language = %d
							and sm.messagename = 'BATTLE_RESULTS_SUBJ'",
							$langID );
		$ehres = $common->getResultArray ( "message_battle_results_subj", $ehsql, "", "", "GLOBAL_CACHE" );
		assert(empty($common->errormsg));		
		assert(is_array($ehres) && count($ehres)>0);
		assert(key_exists('text', $ehres[0]));

		$subject = $ehres[0]['text'];
		assert(!empty($subject));
		
		$battleMessage = strip_tags(DE_BattleReport::CreateFromBattleID($battle->getID())->getLatter());
		
		$msg = new UA_SendMessage;
		$messageID = $msg->createMessage ($messageScopeID, 0, $subject, $battleMessage );
		assert(!empty($messageID) && $messageID>0);
		
		assert($msg->sendMessage($battle->getAttacker()->getUserInstance()->getID(), $messageID)); 
		
		$battle->getDefendersCollection()->resetNext();
		while($defender = $battle->getDefendersCollection()->next())
		{
			assert($msg->sendMessage($defender->getUserInstance()->getID(), $messageID)); 
		}					
	}
}


?>