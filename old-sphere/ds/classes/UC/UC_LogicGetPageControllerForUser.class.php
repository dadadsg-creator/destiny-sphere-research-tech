<?php $CVS_Version = '$Revision$ ; $Date$';

class UC_LogicGetPageControllerForUser
{
	static public function Get(DE_User $user)
	{
		// есть свой аль€нс
		$allianceData = DS_GlobalAlliance::IsUserLeaderAlliance($user);
		if(count($allianceData) > 0)
		{
			return 'LEADER_CONTROL_PANEL';
		}

		//≈сли игрок €вл€етс€ членом глобального альнса, но не €вл€етс€ его лидером
		$usrGlobalAllians = DS_GlobalAlliance::GetUserGlobalAlliance($user);
		
		if(count($usrGlobalAllians) > 0)
		{
			return 'MEMBER_PANEL';
		}
		
		return 'REGISTRATION_PANEL';
	}
}

?>