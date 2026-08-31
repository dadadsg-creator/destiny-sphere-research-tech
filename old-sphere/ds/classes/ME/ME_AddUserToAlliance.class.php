<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_AddUserToAlliance
{
	static public function Add(DE_Userinstance $userInstance, DE_Alliance $alliance)
	{
		DS_Alliance::AddUserToAlliance($alliance, $userInstance);
	}
}

?>